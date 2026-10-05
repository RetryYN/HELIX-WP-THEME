import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const moduleUrl = new URL('../scripts/lib/content-lab-env.mjs', import.meta.url).href;
const verifierRoots = [path.join(root, 'scripts'), path.join(root, 'docs/research')];
const sharedHelpers = new Set([
  path.join(root, 'scripts/lib/content-lab-env.mjs'),
  path.join(root, 'scripts/lib/content_lab_env.py'),
]);
const legacyLabReference = /127\.0\.0\.1:8098|localhost:8098|helix-content-(?:wp|lab|db)|WTCF_/;
const settingNames = [
  'WTCF_BASE_URL', 'WTCF_STATE_DIR', 'WTCF_DOCKER_NETWORK', 'WTCF_WP_CONTAINER',
  'WTCF_DB_CONTAINER', 'WTCF_WP_VOLUME', 'WTCF_DB_VOLUME', 'WTCF_LAB_CREDENTIALS',
];

function environment(overrides = {}) {
  const env = { ...process.env };
  for (const key of settingNames) delete env[key];
  return Object.assign(env, overrides);
}

function loadNode(overrides = {}, body = `
  const { contentLab, contentLabWpCliArgs } = await import(${JSON.stringify(moduleUrl)});
  process.stdout.write(JSON.stringify({ contentLab, args: contentLabWpCliArgs() }));
`) {
  return spawnSync(process.execPath, ['--input-type=module', '-e', body], { env: environment(overrides), encoding: 'utf8' });
}

function loadPython(overrides = {}, body = `
import json
from dataclasses import asdict
from lib.content_lab_env import content_lab_config
value = asdict(content_lab_config())
value['state_dir'] = str(value['state_dir'])
value['credentials_file'] = str(value['credentials_file'])
print(json.dumps(value))
`) {
  return spawnSync('python3', ['-c', body], {
    cwd: path.join(root, 'scripts'),
    env: environment({ PYTHONDONTWRITEBYTECODE: '1', ...overrides }),
    encoding: 'utf8',
  });
}

function sourceFiles(directory) {
  return fs.readdirSync(directory, { withFileTypes: true }).flatMap(entry => {
    const filename = path.join(directory, entry.name);
    if (entry.isDirectory()) return sourceFiles(filename);
    return /\.(mjs|py)$/.test(entry.name) ? [filename] : [];
  });
}

test('Node and Python helpers resolve the same isolated environment settings', () => {
  const cases = [
    {},
    {
      WTCF_BASE_URL: 'http://127.0.0.1:18117/',
      WTCF_STATE_DIR: '/tmp/helix-content-lab-isolated',
      WTCF_DOCKER_NETWORK: 'helix-content-lab-isolated',
      WTCF_WP_CONTAINER: 'helix-content-wp-isolated',
      WTCF_DB_CONTAINER: 'helix-content-db-isolated',
      WTCF_WP_VOLUME: 'helix-content-wp-data-isolated',
      WTCF_DB_VOLUME: 'helix-content-db-data-isolated',
      WTCF_LAB_CREDENTIALS: '/tmp/helix-content-lab-isolated/credentials.json',
    },
  ];
  for (const overrides of cases) {
    const node = loadNode(overrides);
    const python = loadPython(overrides);
    assert.equal(node.status, 0, node.stderr);
    assert.equal(python.status, 0, python.stderr);
    const nodeValue = JSON.parse(node.stdout);
    const pythonValue = JSON.parse(python.stdout);
    assert.equal(nodeValue.contentLab.baseUrl, pythonValue.base_url);
    assert.equal(nodeValue.contentLab.stateDir, pythonValue.state_dir);
    assert.equal(nodeValue.contentLab.network, pythonValue.network);
    assert.equal(nodeValue.contentLab.wpContainer, pythonValue.wp_container);
    assert.equal(nodeValue.contentLab.dbContainer, pythonValue.db_container);
    assert.equal(nodeValue.contentLab.wpVolume, pythonValue.wp_volume);
    assert.equal(nodeValue.contentLab.dbVolume, pythonValue.db_volume);
    assert.equal(nodeValue.contentLab.credentialsFile, pythonValue.credentials_file);
  }
});

test('content lab helper preserves the existing default environment', () => {
  const result = loadNode();
  assert.equal(result.status, 0, result.stderr);
  const value = JSON.parse(result.stdout);
  assert.equal(value.contentLab.baseUrl, 'http://127.0.0.1:8098');
  assert.equal(value.contentLab.network, 'helix-content-lab');
  assert.equal(value.contentLab.wpContainer, 'helix-content-wp');
  assert.ok(value.args.includes('--network') && value.args.includes('helix-content-lab'));
  assert.ok(value.args.includes('--volumes-from') && value.args.includes('helix-content-wp'));
});

test('Node and Python reject missing ports and malformed Docker names consistently', () => {
  const rejected = [
    { WTCF_BASE_URL: 'http://127.0.0.1' },
    { WTCF_BASE_URL: 'https://127.0.0.1:18117' },
    { WTCF_BASE_URL: 'http://127.0.0.1:65536' },
    { WTCF_DOCKER_NETWORK: '-helix-content-lab' },
    { WTCF_WP_CONTAINER: 'helix/content-wp' },
    { WTCF_DB_VOLUME: 'bad name' },
  ];
  for (const overrides of rejected) {
    const node = loadNode(overrides);
    const python = loadPython(overrides);
    assert.notEqual(node.status, 0, `Node accepted ${JSON.stringify(overrides)}`);
    assert.notEqual(python.status, 0, `Python accepted ${JSON.stringify(overrides)}`);
  }
});

test('every verifier with legacy lab references uses the shared configuration helper', () => {
  const files = verifierRoots.flatMap(sourceFiles).filter(filename => !sharedHelpers.has(filename));
  const affected = files.filter(filename => legacyLabReference.test(fs.readFileSync(filename, 'utf8')));
  assert.ok(affected.length > 0);
  for (const filename of affected) {
    const source = fs.readFileSync(filename, 'utf8');
    const usesPythonHelper = source.includes('from lib.content_lab_env import content_lab_config');
    const relativeHelper = path.relative(path.dirname(filename), path.join(root, 'scripts/lib/content-lab-env.mjs')).split(path.sep).join('/');
    const moduleSpecifier = relativeHelper.startsWith('.') ? relativeHelper : `./${relativeHelper}`;
    const usesNodeHelper = source.includes(`from '${moduleSpecifier}'`)
      && source.includes('contentLab.');
    assert.ok(usesNodeHelper || usesPythonHelper, `${path.relative(root, filename)} can mix isolated credentials with the shared lab`);
  }
});

test('credential consumers read the configured credentials file, not the state-dir default', () => {
  const files = verifierRoots.flatMap(sourceFiles).filter(filename => !sharedHelpers.has(filename));
  for (const filename of files) {
    const source = fs.readFileSync(filename, 'utf8');
    assert.doesNotMatch(source, /path\.join\(\s*(?:contentLab\.stateDir|state)\s*,\s*['"]credentials\.json['"]\s*\)/u,
      `${path.relative(root, filename)} bypasses WTCF_LAB_CREDENTIALS`);
    if (/credentials\.json/u.test(source) && /readFileSync|read_text\(/u.test(source)) {
      assert.match(source, /contentLab\.credentialsFile|lab\.credentials_file/u,
        `${path.relative(root, filename)} reads credentials without the shared configured path`);
    }
  }
});

test('content-lab launcher honors external credentials and never writes bytecode into the checkout', () => {
  const temp = fs.mkdtempSync(path.join(os.tmpdir(), 'helix-content-lab-launcher-test-'));
  const mockBin = path.join(temp, 'bin');
  const stateDir = path.join(temp, 'state');
  const credentialsFile = path.join(temp, 'secrets', 'credentials.json');
  fs.mkdirSync(mockBin, { recursive: true });
  const docker = path.join(mockBin, 'docker');
  fs.writeFileSync(docker, `#!/bin/sh
case "$1:$2" in
  network:inspect) exit 1 ;;
  inspect:*) printf 'Error: No such object: %s\\n' "$2" >&2; exit 1 ;;
  network:create|exec:*) exit 0 ;;
  run:*)
    case "$*" in
      *"wp core is-installed"*) exit 0 ;;
      *"wp option get blogname"*) printf '%s\\n' 'HELIX Content Lab' ;;
      *"wp eval-file /poc/seed.php"*) printf '%s\\n' '{"oneoff":101}' ;;
      *"wp user get"*) printf '%s\\n' '77' ;;
      *) exit 0 ;;
    esac ;;
  *) exit 2 ;;
esac
`);
  fs.chmodSync(docker, 0o700);
  try {
    const result = spawnSync('python3', ['scripts/start-content-lab-guarded.py'], {
      cwd: root,
      encoding: 'utf8',
      env: environment({
        PATH: `${mockBin}${path.delimiter}${process.env.PATH || ''}`,
        WTCF_BASE_URL: 'http://127.0.0.1:18128',
        WTCF_STATE_DIR: stateDir,
        WTCF_DOCKER_NETWORK: 'helix-content-lab-test',
        WTCF_WP_CONTAINER: 'helix-content-wp-test',
        WTCF_DB_CONTAINER: 'helix-content-db-test',
        WTCF_WP_VOLUME: 'helix-content-wp-test',
        WTCF_DB_VOLUME: 'helix-content-db-test',
        WTCF_LAB_CREDENTIALS: credentialsFile,
      }),
    });
    assert.equal(result.status, 0, result.stderr);
    assert.equal(fs.existsSync(credentialsFile), true);
    assert.equal(fs.existsSync(path.join(stateDir, 'credentials.json')), false);
    assert.equal(fs.statSync(credentialsFile).mode & 0o777, 0o600);
    assert.deepEqual(Object.keys(JSON.parse(fs.readFileSync(credentialsFile, 'utf8'))).sort(),
      ['admin', 'database', 'expired', 'none', 'oneoff', 'other_product', 'subscription']);
    assert.equal(result.stdout.includes(credentialsFile), false);
    assert.equal(fs.existsSync(path.join(root, 'scripts/lib/__pycache__')), false);

    const repositoryCredentials = path.join(root, '.content-lab-credential-test.json');
    const rejected = spawnSync('python3', ['scripts/start-content-lab-guarded.py'], {
      cwd: root,
      encoding: 'utf8',
      env: environment({
        PATH: `${mockBin}${path.delimiter}${process.env.PATH || ''}`,
        WTCF_BASE_URL: 'http://127.0.0.1:18128',
        WTCF_STATE_DIR: path.join(temp, 'rejected-state'),
        WTCF_DOCKER_NETWORK: 'helix-content-lab-test',
        WTCF_WP_CONTAINER: 'helix-content-wp-test',
        WTCF_DB_CONTAINER: 'helix-content-db-test',
        WTCF_WP_VOLUME: 'helix-content-wp-test',
        WTCF_DB_VOLUME: 'helix-content-db-test',
        WTCF_LAB_CREDENTIALS: repositoryCredentials,
      }),
    });
    assert.notEqual(rejected.status, 0);
    assert.match(rejected.stderr, /must be outside the repository/u);
    assert.equal(fs.existsSync(repositoryCredentials), false);
  } finally {
    fs.rmSync(temp, { recursive: true, force: true });
  }
});

function runExistingLab({ mutate = () => {}, databaseRunning = true, databaseExists = true, inspectError = false } = {}) {
  const temp = fs.mkdtempSync(path.join(os.tmpdir(), 'helix-content-lab-reuse-test-'));
  const mockBin = path.join(temp, 'bin');
  const stateDir = path.join(temp, 'state');
  const credentialsFile = path.join(temp, 'secrets', 'credentials.json');
  const wpName = 'helix-content-wp-reuse-test';
  const dbName = 'helix-content-db-reuse-test';
  const wpVolume = 'helix-content-wp-data-reuse-test';
  const dbVolume = 'helix-content-db-data-reuse-test';
  const network = 'helix-content-lab-reuse-test';
  const port = '18147';
  const theme = path.join(root, 'docs/research/2026-09-05-design-prototype-03/theme/helix-wt');
  const plugin = path.join(root, 'docs/research/2026-09-08-content-faces/plugin');
  const wp = {
    Config: { Image: 'wordpress:7.1-php8.3-apache' },
    State: { Running: true },
    HostConfig: { PortBindings: { '80/tcp': [{ HostIp: '127.0.0.1', HostPort: port }] }, PublishAllPorts: false },
    NetworkSettings: { Networks: { [network]: {} } },
    Mounts: [
      { Type: 'volume', Name: wpVolume, Source: '/var/lib/docker/volumes/' + wpVolume + '/_data', Destination: '/var/www/html', RW: true },
      { Type: 'bind', Source: theme, Destination: '/var/www/html/wp-content/themes/helix-wt', RW: false },
      { Type: 'bind', Source: plugin, Destination: '/var/www/html/wp-content/plugins/helix-content-faces', RW: false },
    ],
  };
  const db = {
    Config: { Image: 'mariadb:10.11' },
    State: { Running: databaseRunning },
    HostConfig: { PortBindings: null, PublishAllPorts: false },
    NetworkSettings: { Networks: { [network]: {} } },
    Mounts: [{ Type: 'volume', Name: dbVolume, Source: '/var/lib/docker/volumes/' + dbVolume + '/_data', Destination: '/var/lib/mysql', RW: true }],
  };
  mutate({ wp, db });
  fs.mkdirSync(mockBin, { recursive: true });
  fs.mkdirSync(stateDir, { recursive: true });
  fs.writeFileSync(path.join(temp, 'wp.json'), JSON.stringify([wp]));
  if (databaseExists) fs.writeFileSync(path.join(temp, 'db.json'), JSON.stringify([db]));
  const docker = path.join(mockBin, 'docker');
  const dockerScript = [
    '#!/bin/sh',
    "printf '%s\\n' \"$*\" >> \"$MOCK_DOCKER_LOG\"",
    'case \"$1:$2\" in',
    '  network:inspect) exit 0 ;;',
    '  inspect:*)',
    '    if [ "$MOCK_INSPECT_ERROR" = "1" ]; then printf \'Cannot connect to the Docker daemon\\n\' >&2; exit 1; fi',
    '    case \"$2\" in',
    '      \"$MOCK_WP\") cat \"$MOCK_DOCKER_STATE/wp.json\" ;;',
    '      \"$MOCK_DB\") [ -f \"$MOCK_DOCKER_STATE/db.json\" ] && cat \"$MOCK_DOCKER_STATE/db.json\" ;;',
    '      *) printf \'Error: No such object: %s\\n\' "$2" >&2; exit 1 ;;',
    '    esac ;;',
    '  exec:*) exit 0 ;;',
    '  start:*) exit 0 ;;',
    '  run:*)',
    '    case \"$*\" in',
    '      *\"wp core is-installed\"*) exit 0 ;;',
    "      *\"wp option get blogname\"*) printf '%s\\n' 'HELIX Content Lab' ;;",
    "      *\"wp eval-file /poc/seed.php\"*) printf '%s\\n' '{\"oneoff\":101}' ;;",
    "      *\"wp user get\"*) printf '%s\\n' '77' ;;",
    '      *) exit 0 ;;',
    '    esac ;;',
    '  *) exit 2 ;;',
    'esac',
    '',
  ].join('\n');
  fs.writeFileSync(docker, dockerScript);
  fs.chmodSync(docker, 0o700);
  const result = spawnSync('python3', ['scripts/start-content-lab-guarded.py'], {
    cwd: root,
    encoding: 'utf8',
    env: environment({
      PATH: mockBin + path.delimiter + (process.env.PATH || ''),
      MOCK_DOCKER_LOG: path.join(temp, 'docker.log'),
      MOCK_DOCKER_STATE: temp,
      MOCK_INSPECT_ERROR: inspectError ? '1' : '0',
      MOCK_WP: wpName,
      MOCK_DB: dbName,
      WTCF_BASE_URL: 'http://127.0.0.1:' + port,
      WTCF_STATE_DIR: stateDir,
      WTCF_DOCKER_NETWORK: network,
      WTCF_WP_CONTAINER: wpName,
      WTCF_DB_CONTAINER: dbName,
      WTCF_WP_VOLUME: wpVolume,
      WTCF_DB_VOLUME: dbVolume,
      WTCF_LAB_CREDENTIALS: credentialsFile,
    }),
  });
  const calls = fs.readFileSync(path.join(temp, 'docker.log'), 'utf8').trim().split('\n');
  return { temp, result, calls };
}

test('existing content lab rejects mismatched port, mounts, and theme destination before starting or seeding', () => {
  const mismatches = [
    ['host port', ({ wp }) => { wp.HostConfig.PortBindings['80/tcp'][0].HostPort = '18148'; }],
    ['non-loopback host binding', ({ wp }) => { wp.HostConfig.PortBindings['80/tcp'][0].HostIp = '0.0.0.0'; }],
    ['WordPress PublishAllPorts with an external network port', ({ wp }) => {
      wp.HostConfig.PublishAllPorts = true;
      wp.NetworkSettings.Ports = { '80/tcp': [{ HostIp: '0.0.0.0', HostPort: '18200' }] };
    }],
    ['WordPress data volume', ({ wp }) => { wp.Mounts[0].Name = 'another-wp-volume'; }],
    ['WordPress volume destination', ({ wp }) => { wp.Mounts[0].Destination = '/var/www/html/other'; }],
    ['database volume', ({ db }) => { db.Mounts[0].Name = 'another-db-volume'; }],
    ['database volume destination', ({ db }) => { db.Mounts[0].Destination = '/var/lib/mysql/other'; }],
    ['database published port', ({ db }) => { db.HostConfig.PortBindings = { '3306/tcp': [{ HostIp: '0.0.0.0', HostPort: '3306' }] }; }],
    ['additional network attachment', ({ wp }) => { wp.NetworkSettings.Networks.bridge = {}; }],
    ['database PublishAllPorts with an external network port', ({ db }) => {
      db.HostConfig.PublishAllPorts = true;
      db.NetworkSettings.Ports = { '3306/tcp': [{ HostIp: '0.0.0.0', HostPort: '3306' }] };
    }],
    ['theme mount destination', ({ wp }) => { wp.Mounts[1].Destination = '/var/www/html/wp-content/themes/other'; }],
    ['plugin bind source', ({ wp }) => { wp.Mounts[2].Source = wp.Mounts[2].Source + '-other'; }],
    ['plugin bind destination', ({ wp }) => { wp.Mounts[2].Destination = '/var/www/html/wp-content/plugins/other'; }],
    ['plugin bind mode', ({ wp }) => { wp.Mounts[2].RW = true; }],
  ];
  for (const [label, mutate] of mismatches) {
    const fixture = runExistingLab({
      databaseRunning: label === 'theme mount destination' ? false : true,
      mutate,
    });
    try {
      const seedInvoked = fixture.calls.some(call => /wp eval-file \/poc\/seed\.php/u.test(call));
      assert.notEqual(fixture.result.status, 0, 'accepted mismatched ' + label + '; seedInvoked=' + seedInvoked);
      assert.doesNotMatch(fixture.calls.join('\n'), /^(?:start\s|run\s+-d\b)/mu,
        'started or created a container after detecting ' + label);
      assert.doesNotMatch(fixture.calls.join('\n'), /wp eval-file \/poc\/seed\.php/u,
        'seeded after detecting ' + label);
    } finally {
      fs.rmSync(fixture.temp, { recursive: true, force: true });
    }
  }

  const missingDatabase = runExistingLab({
    databaseExists: false,
    mutate: ({ wp }) => { wp.Mounts[1].Destination = '/var/www/html/wp-content/themes/other'; },
  });
  try {
    assert.notEqual(missingDatabase.result.status, 0);
    assert.doesNotMatch(missingDatabase.calls.join('\n'), /^(?:start\s|run\s+-d\b)/mu);
    assert.doesNotMatch(missingDatabase.calls.join('\n'), /wp eval-file \/poc\/seed\.php/u);
  } finally {
    fs.rmSync(missingDatabase.temp, { recursive: true, force: true });
  }
});

test('existing content lab reuses exact expected container bindings', () => {
  const fixture = runExistingLab();
  try {
    assert.equal(fixture.result.status, 0, fixture.result.stderr);
    assert.equal(fixture.calls.filter(call => /wp eval-file \/poc\/seed\.php/u.test(call)).length, 1);
    assert.doesNotMatch(fixture.calls.join('\n'), /^(?:start\s|run\s+-d\b)/mu);
  } finally {
    fs.rmSync(fixture.temp, { recursive: true, force: true });
  }
});

test('container inspection errors fail closed before starting, creating, or seeding', () => {
  const fixture = runExistingLab({ inspectError: true });
  try {
    assert.notEqual(fixture.result.status, 0);
    assert.match(fixture.result.stderr, /Could not inspect the content lab/u);
    assert.doesNotMatch(fixture.calls.join('\n'), /^(?:start\s|run\s+-d\b)/mu);
    assert.doesNotMatch(fixture.calls.join('\n'), /wp eval-file \/poc\/seed\.php/u);
  } finally {
    fs.rmSync(fixture.temp, { recursive: true, force: true });
  }
});

test('existing correctly bound stopped content-lab database is restarted after preflight', () => {
  const fixture = runExistingLab({ databaseRunning: false });
  try {
    assert.equal(fixture.result.status, 0, fixture.result.stderr);
    assert.equal(fixture.calls.filter(call => call === 'start helix-content-db-reuse-test').length, 1);
    assert.equal(fixture.calls.filter(call => /wp eval-file \/poc\/seed\.php/u.test(call)).length, 1);
    assert.doesNotMatch(fixture.calls.join('\n'), /^run\s+-d\b/mu);
  } finally {
    fs.rmSync(fixture.temp, { recursive: true, force: true });
  }
});
