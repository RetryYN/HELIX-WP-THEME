"""Start the isolated, loopback-only WordPress content-face PoC. No production mounts."""
import json
import os
from pathlib import Path
import secrets
import subprocess
import sys
import time
from urllib.parse import urlsplit

sys.dont_write_bytecode = True
from lib.content_lab_env import content_lab_config

root = Path(__file__).resolve().parent.parent
lab = content_lab_config()
state = lab.state_dir
state.mkdir(mode=0o700, parents=True, exist_ok=True)
credentials_path = lab.credentials_file.expanduser()
if not credentials_path.is_absolute():
    credentials_path = root / credentials_path
credentials_path = credentials_path.resolve()
try:
    credentials_path.relative_to(root.resolve())
except ValueError:
    pass
else:
    raise RuntimeError('WTCF_LAB_CREDENTIALS must be outside the repository')
credentials_path.parent.mkdir(mode=0o700, parents=True, exist_ok=True)
if not credentials_path.exists():
    try:
        credentials_fd = os.open(credentials_path, os.O_CREAT | os.O_EXCL | os.O_WRONLY, 0o600)
    except FileExistsError:
        pass
    else:
        with os.fdopen(credentials_fd, 'w', encoding='utf-8') as credentials_file:
            json.dump({key: secrets.token_urlsafe(30) for key in
                       ['database', 'admin', 'oneoff', 'subscription', 'expired', 'other_product', 'none']}, credentials_file)
credentials = json.loads(credentials_path.read_text())


def run(args, check=True):
    result = subprocess.run(args, capture_output=True, text=True)
    if check and result.returncode:
        message = result.stderr
        for value in credentials.values():
            message = message.replace(value, '[redacted]')
        raise RuntimeError(message)
    return result


network = lab.network
wp_container = lab.wp_container
db_container = lab.db_container
wp_volume = lab.wp_volume
db_volume = lab.db_volume
base_url = lab.base_url
port = int(urlsplit(base_url).port)
if run(['docker', 'network', 'inspect', network], False).returncode:
    run(['docker', 'network', 'create', network])
for name, values in [('db.env', ['MARIADB_ROOT_PASSWORD=' + credentials['database'], 'MARIADB_DATABASE=content_lab']),
                     ('wp.env', [f'WORDPRESS_DB_HOST={db_container}', 'WORDPRESS_DB_USER=root',
                                 'WORDPRESS_DB_PASSWORD=' + credentials['database'], 'WORDPRESS_DB_NAME=content_lab'])]:
    file = state / name
    file.write_text('\n'.join(values) + '\n')
    file.chmod(0o600)

theme = root / 'docs/research/2026-09-05-design-prototype-03/theme/helix-wt'
plugin = root / 'docs/research/2026-09-08-content-faces/plugin'
containers = {
    db_container: ['--env-file', str(state / 'db.env'), '-v', f'{db_volume}:/var/lib/mysql', 'mariadb:10.11'],
    wp_container: ['--env-file', str(state / 'wp.env'), '-p', f'127.0.0.1:{port}:80',
                         '-v', f'{wp_volume}:/var/www/html',
                         '-v', str(theme) + ':/var/www/html/wp-content/themes/helix-wt:ro',
                         '-v', str(plugin) + ':/var/www/html/wp-content/plugins/helix-content-faces:ro',
                         'wordpress:7.1-php8.3-apache'],
}
expected_mounts = {
    db_container: [
        {'Type': 'volume', 'Name': db_volume, 'Destination': '/var/lib/mysql', 'RW': True},
    ],
    wp_container: [
        {'Type': 'volume', 'Name': wp_volume, 'Destination': '/var/www/html', 'RW': True},
        {'Type': 'bind', 'Source': str(theme), 'Destination': '/var/www/html/wp-content/themes/helix-wt', 'RW': False},
        {'Type': 'bind', 'Source': str(plugin), 'Destination': '/var/www/html/wp-content/plugins/helix-content-faces', 'RW': False},
    ],
}
expected_ports = {
    db_container: {},
    wp_container: {'80/tcp': [{'HostIp': '127.0.0.1', 'HostPort': str(port)}]},
}


def mount_signature(mount):
    kind = mount.get('Type')
    return json.dumps({
        'Type': kind,
        'Name': mount.get('Name') if kind == 'volume' else None,
        'Source': mount.get('Source') if kind == 'bind' else None,
        'Destination': mount.get('Destination'),
        'RW': mount.get('RW'),
    }, sort_keys=True)


def matches_existing_container(name, data, image):
    try:
        actual_networks = data['NetworkSettings']['Networks']
        actual_mounts = data['Mounts']
        actual_ports = data['HostConfig'].get('PortBindings') or {}
        publish_all_ports = data['HostConfig'].get('PublishAllPorts')
        actual_image = data['Config']['Image']
        running = data['State']['Running']
    except (KeyError, TypeError, AttributeError):
        return False
    if (not isinstance(actual_networks, dict) or network not in actual_networks
            or not isinstance(running, bool)):
        return False
    if actual_image != image or not isinstance(actual_mounts, list):
        return False
    if actual_ports != expected_ports[name]:
        return False
    if publish_all_ports is not False:
        return False
    expected = sorted(mount_signature(mount) for mount in expected_mounts[name])
    actual = sorted(
        mount_signature(mount) for mount in actual_mounts if isinstance(mount, dict)
    )
    return len(actual) == len(actual_mounts) and actual == expected


existing_containers = {}
for name, args in containers.items():
    existing = run(['docker', 'inspect', name], False)
    if not existing.returncode:
        data = json.loads(existing.stdout)[0]
        if not matches_existing_container(name, data, args[-1]):
            raise RuntimeError('Container name already belongs to a different environment')
        existing_containers[name] = data
    else:
        existing_containers[name] = None

# Validate every existing handle before starting or creating any container.
for name, args in containers.items():
    existing = existing_containers[name]
    if existing is None:
        run(['docker', 'run', '-d', '--name', name, '--network', network] + args)
    elif not existing['State']['Running']:
        run(['docker', 'start', name])

cli = ['docker', 'run', '--rm', '--network', network, '--env-file', str(state / 'wp.env'),
       '--volumes-from', wp_container, '-v', str(root / 'docs/research/2026-09-08-content-faces') + ':/poc:ro',
       '--user', '33:33', 'wordpress:cli-php8.3', 'wp']


def wp(args, check=True):
    return run(cli + args, check)


# Readiness polls inspect the actual DB/WP handles. Never replace an existing volume on timeout.
for attempt in range(30):
    if not run(['docker', 'exec', wp_container, 'php', '-r',
                'mysqli_report(MYSQLI_REPORT_OFF); $db = @new mysqli(getenv("WORDPRESS_DB_HOST"), getenv("WORDPRESS_DB_USER"), getenv("WORDPRESS_DB_PASSWORD"), getenv("WORDPRESS_DB_NAME")); exit($db->connect_errno ? 1 : 0);'], False).returncode:
        break
    time.sleep(1)
else:
    raise RuntimeError('Lab database is not ready; containers and volumes were preserved')

if wp(['core', 'is-installed'], False).returncode:
    wp(['core', 'install', '--url=' + base_url, '--title=HELIX Content Lab', '--admin_user=lab_admin',
        '--admin_password=' + credentials['admin'], '--admin_email=lab@example.test', '--skip-email'])
elif wp(['option', 'get', 'blogname']).stdout.strip() != 'HELIX Content Lab':
    raise RuntimeError('Existing site is not the dedicated content lab')
wp(['plugin', 'activate', 'helix-content-faces'])
wp(['theme', 'activate', 'helix-wt'])
ids = json.loads(wp(['eval-file', '/poc/seed.php']).stdout.strip())
for role in ['oneoff', 'subscription', 'expired', 'other_product', 'none']:
    user = wp(['user', 'get', 'lab_' + role, '--field=ID'], False)
    if user.returncode:
        user = wp(['user', 'create', 'lab_' + role, 'lab_' + role + '@example.test', '--role=subscriber',
                   '--user_pass=' + credentials[role], '--porcelain'])
    grant = {'state': role, 'posts': [ids['oneoff']] if role == 'oneoff' else [],
             'expires': 4102444800 if role == 'subscription' else 1}
    wp(['user', 'meta', 'update', user.stdout.strip(), '_wtcf_entitlement', json.dumps(grant), '--format=json'])
(state / 'fixture-ids.json').write_text(json.dumps(ids))
print(f'Content lab ready at {base_url}. Credentials use the configured WTCF_LAB_CREDENTIALS path.')
