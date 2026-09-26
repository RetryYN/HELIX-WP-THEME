import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const moduleUrl = new URL('../scripts/lib/content-lab-env.mjs', import.meta.url).href;
const verifierDirectory = path.join(root, 'scripts');
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
  return spawnSync('python3', ['-c', body], { cwd: verifierDirectory, env: environment(overrides), encoding: 'utf8' });
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
  const files = fs.readdirSync(verifierDirectory).filter(name => /\.(mjs|py)$/.test(name));
  const affected = files.filter(name => legacyLabReference.test(fs.readFileSync(path.join(verifierDirectory, name), 'utf8')));
  assert.ok(affected.length > 0);
  for (const name of affected) {
    const source = fs.readFileSync(path.join(verifierDirectory, name), 'utf8');
    const usesHelper = source.includes("from './lib/content-lab-env.mjs'")
      && source.includes('contentLab.');
    const usesHelperWithoutProperty = source.includes("from './lib/content-lab-env.mjs'")
      && source.includes('contentLab');
    const usesPythonHelper = source.includes('from lib.content_lab_env import content_lab_config');
    assert.ok(usesHelper || usesHelperWithoutProperty || usesPythonHelper, `${name} can mix isolated credentials with the shared lab`);
  }
});
