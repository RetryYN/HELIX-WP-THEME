"""Start the isolated, loopback-only WordPress content-face PoC. No production mounts."""
import json
import os
from pathlib import Path
import secrets
import subprocess
import tempfile
import time

root = Path(__file__).resolve().parent.parent
state = Path(os.environ.get('WTCF_STATE_DIR', str(Path(tempfile.gettempdir()) / 'helix-content-lab')))
state.mkdir(mode=0o700, parents=True, exist_ok=True)
credentials_path = state / 'credentials.json'
if not credentials_path.exists():
    credentials_path.write_text(json.dumps({key: secrets.token_urlsafe(30) for key in
                                           ['database', 'admin', 'oneoff', 'subscription', 'expired', 'other_product', 'none']}))
    credentials_path.chmod(0o600)
credentials = json.loads(credentials_path.read_text())


def run(args, check=True):
    result = subprocess.run(args, capture_output=True, text=True)
    if check and result.returncode:
        message = result.stderr
        for value in credentials.values():
            message = message.replace(value, '[redacted]')
        raise RuntimeError(message)
    return result


network = 'helix-content-lab'
if run(['docker', 'network', 'inspect', network], False).returncode:
    run(['docker', 'network', 'create', network])
for name, values in [('db.env', ['MARIADB_ROOT_PASSWORD=' + credentials['database'], 'MARIADB_DATABASE=content_lab']),
                     ('wp.env', ['WORDPRESS_DB_HOST=helix-content-db', 'WORDPRESS_DB_USER=root',
                                 'WORDPRESS_DB_PASSWORD=' + credentials['database'], 'WORDPRESS_DB_NAME=content_lab'])]:
    file = state / name
    file.write_text('\n'.join(values) + '\n')
    file.chmod(0o600)

theme = root / 'docs/research/2026-09-05-design-prototype-03/theme/helix-wt'
plugin = root / 'docs/research/2026-09-08-content-faces/plugin'
containers = {
    'helix-content-db': ['--env-file', str(state / 'db.env'), '-v', 'helix-content-db:/var/lib/mysql', 'mariadb:10.11'],
    'helix-content-wp': ['--env-file', str(state / 'wp.env'), '-p', '127.0.0.1:8098:80',
                         '-v', 'helix-content-wp:/var/www/html',
                         '-v', str(theme) + ':/var/www/html/wp-content/themes/helix-wt:ro',
                         '-v', str(plugin) + ':/var/www/html/wp-content/plugins/helix-content-faces:ro',
                         'wordpress:7.1-php8.3-apache'],
}
for name, args in containers.items():
    existing = run(['docker', 'inspect', name], False)
    if not existing.returncode:
        data = json.loads(existing.stdout)[0]
        mounts = data['Mounts']
        if network not in data['NetworkSettings']['Networks'] or data['Config']['Image'] != args[-1]:
            raise RuntimeError('Container name already belongs to a different environment')
        if name == 'helix-content-wp' and not any(m['Source'] == str(theme) and not m['RW'] for m in mounts):
            raise RuntimeError('Existing lab uses another checkout; keep it intact and inspect the worktree')
        if not data['State']['Running']:
            run(['docker', 'start', name])
    else:
        run(['docker', 'run', '-d', '--name', name, '--network', network] + args)

cli = ['docker', 'run', '--rm', '--network', network, '--env-file', str(state / 'wp.env'),
       '--volumes-from', 'helix-content-wp', '-v', str(root / 'docs/research/2026-09-08-content-faces') + ':/poc:ro',
       '--user', '33:33', 'wordpress:cli-php8.3', 'wp']


def wp(args, check=True):
    return run(cli + args, check)


# Readiness polls inspect the actual DB/WP handles. Never replace an existing volume on timeout.
for attempt in range(30):
    if not run(['docker', 'exec', 'helix-content-wp', 'php', '-r',
                'mysqli_report(MYSQLI_REPORT_OFF); $db = @new mysqli(getenv("WORDPRESS_DB_HOST"), getenv("WORDPRESS_DB_USER"), getenv("WORDPRESS_DB_PASSWORD"), getenv("WORDPRESS_DB_NAME")); exit($db->connect_errno ? 1 : 0);'], False).returncode:
        break
    time.sleep(1)
else:
    raise RuntimeError('Lab database is not ready; containers and volumes were preserved')

if wp(['core', 'is-installed'], False).returncode:
    wp(['core', 'install', '--url=http://127.0.0.1:8098', '--title=HELIX Content Lab', '--admin_user=lab_admin',
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
print('Content lab ready on loopback port 8098. Set WTCF_LAB_CREDENTIALS to credentials.json in WTCF_STATE_DIR.')
