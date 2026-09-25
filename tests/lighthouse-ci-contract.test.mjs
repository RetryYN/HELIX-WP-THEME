import assert from 'node:assert/strict';
import fs from 'node:fs';
import test from 'node:test';

const root = new URL('../', import.meta.url);
const workflow = fs.readFileSync(new URL('.github/workflows/theme-quality-gate.yml', root), 'utf8');
const config = JSON.parse(fs.readFileSync(new URL('.lighthouserc.json', root), 'utf8'));

test('Lighthouse CI uses supported fail-closed assertion forms and a lab responsiveness proxy', () => {
  assert.equal(config.ci.collect.numberOfRuns, 3);
  assert.equal(config.ci.collect.url[0], 'http://localhost:8086/');
  assert.equal(config.ci.assert.preset, undefined);
  assert.deepEqual(config.ci.assert.assertions['largest-contentful-paint'], ['error', { maxNumericValue: 2500 }]);
  assert.deepEqual(config.ci.assert.assertions['total-blocking-time'], ['error', { maxNumericValue: 200 }]);
  assert.deepEqual(config.ci.assert.assertions['cumulative-layout-shift'], ['error', { maxNumericValue: 0.1 }]);
  assert.deepEqual(config.ci.assert.assertions['render-blocking-resources'], ['error', { maxLength: 0 }]);
  assert.equal(config.ci.assert.assertions.interactive, undefined);
  assert.equal(config.ci.upload.target, 'filesystem');
  assert.equal(config.ci.upload.outputDir, '.lighthouseci');
});

test('the Lighthouse job runs on pull requests and propagates assertion and report failures', () => {
  assert.match(workflow, /github\.event_name == 'pull_request'/u);
  assert.match(workflow, /run: lhci autorun --config=\.\/\.lighthouserc\.json/u);
  assert.doesNotMatch(workflow, /\|\|\s*true/u);
  assert.match(workflow, /path: \.lighthouseci\//u);
  assert.match(workflow, /if-no-files-found: error/u);
  assert.match(workflow, /include-hidden-files: true/u);
});
