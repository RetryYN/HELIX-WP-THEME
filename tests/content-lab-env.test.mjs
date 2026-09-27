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
  network:inspect|inspect:*) exit 1 ;;
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
    const result = spawnSync('python3', ['scripts/start-content-lab.py'], {
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
    const rejected = spawnSync('python3', ['scripts/start-content-lab.py'], {
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
