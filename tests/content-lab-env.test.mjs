import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import test from 'node:test';

const moduleUrl = new URL('../scripts/lib/content-lab-env.mjs', import.meta.url).href;

function load(overrides = {}) {
  const env = { ...process.env };
  for (const key of ['WTCF_BASE_URL', 'WTCF_STATE_DIR', 'WTCF_DOCKER_NETWORK', 'WTCF_WP_CONTAINER']) delete env[key];
  Object.assign(env, overrides);
  return spawnSync(process.execPath, ['--input-type=module', '-e', `
    const { contentLab, contentLabWpCliArgs } = await import(${JSON.stringify(moduleUrl)});
    process.stdout.write(JSON.stringify({ contentLab, args: contentLabWpCliArgs() }));
  `], { env, encoding: 'utf8' });
}

test('content lab helper preserves the existing default environment', () => {
  const result = load();
  assert.equal(result.status, 0, result.stderr);
  const value = JSON.parse(result.stdout);
  assert.equal(value.contentLab.baseUrl, 'http://127.0.0.1:8098');
  assert.equal(value.contentLab.network, 'helix-content-lab');
  assert.equal(value.contentLab.wpContainer, 'helix-content-wp');
  assert.ok(value.args.includes('--network') && value.args.includes('helix-content-lab'));
  assert.ok(value.args.includes('--volumes-from') && value.args.includes('helix-content-wp'));
});

test('content lab helper supports isolated loopback resources', () => {
  const result = load({
    WTCF_BASE_URL: 'http://127.0.0.1:18117',
    WTCF_STATE_DIR: '/tmp/helix-content-lab-isolated',
    WTCF_DOCKER_NETWORK: 'helix-content-lab-isolated',
    WTCF_WP_CONTAINER: 'helix-content-wp-isolated',
  });
  assert.equal(result.status, 0, result.stderr);
  const value = JSON.parse(result.stdout);
  assert.equal(value.contentLab.baseUrl, 'http://127.0.0.1:18117');
  assert.equal(value.contentLab.stateDir, '/tmp/helix-content-lab-isolated');
  assert.ok(value.args.includes('helix-content-lab-isolated'));
  assert.ok(value.args.includes('helix-content-wp-isolated'));
});

test('content lab helper rejects non-loopback origins', () => {
  const result = load({ WTCF_BASE_URL: 'https://example.com' });
  assert.notEqual(result.status, 0);
  assert.match(result.stderr, /loopback HTTP origin/);
});
