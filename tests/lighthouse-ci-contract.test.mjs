import assert from 'node:assert/strict';
import fs from 'node:fs';
import test from 'node:test';

const root = new URL('../', import.meta.url);
const workflow = fs.readFileSync(new URL('.github/workflows/theme-quality-gate.yml', root), 'utf8');
const config = JSON.parse(fs.readFileSync(new URL('.lighthouserc.json', root), 'utf8'));
const packageJson = JSON.parse(fs.readFileSync(new URL('package.json', root), 'utf8'));
const lock = JSON.parse(fs.readFileSync(new URL('package-lock.json', root), 'utf8'));

test('performance contract pins versions, thresholds, insights, and all eight conditions', () => {
  assert.equal(config.schema, 'helix-lighthouse-performance-gate.v1');
  assert.deepEqual(config.versions, { lighthouse: '13.5.0', chrome: '156.0.8075.0', webVitals: '6.2.2' });
  assert.equal(config.runsPerCondition, 3);
  assert.equal(config.aggregation, 'maximum-of-all-three-runs');
  assert.deepEqual(config.thresholds['largest-contentful-paint'], { maxNumericValue: 2500 });
  assert.deepEqual(config.thresholds['interaction-to-next-paint'], { maxNumericValue: 200 });
  assert.deepEqual(config.thresholds['cumulative-layout-shift'], { maxNumericValue: 0.1 });
  assert.deepEqual(config.insightIds, [
    'lcp-discovery-insight', 'lcp-breakdown-insight', 'cls-culprits-insight', 'inp-breakdown-insight',
  ]);
  assert.deepEqual(Object.keys(config.conditions), ['article', 'landing-page', 'listing', 'comparison']);
  assert.equal(config.conditions.article, 'created-by-owned-fixture');
  assert.deepEqual(Object.keys(config.devices), ['sp', 'pc']);
  assert.deepEqual(config.homeRegression, { url: '/', device: 'mobile', runs: 3,
    screenEmulation: { width: 412, height: 823, deviceScaleFactor: 1.75 }, lcpMaxNumericValue: 2500,
    renderBlockingInsight: { maxLength: 0 } });
});

test('performance dependencies are exact and lockfile-resolved', () => {
  for (const [name, expected] of [['lighthouse', '13.5.0'], ['web-vitals', '6.2.2'],
    ['@playwright/test', '1.61.0'], ['chrome-launcher', '1.2.1']]) {
    assert.equal(packageJson.devDependencies[name], expected);
    assert.equal(lock.packages[`node_modules/${name}`].version, expected);
    assert.match(lock.packages[`node_modules/${name}`].integrity, /^sha512-/u);
  }
  assert.match(packageJson.scripts['test:quality-contracts'], /performance-evidence-validator\.test\.mjs/u);
  assert.match(packageJson.scripts['test:quality-contracts'], /performance-runner-contract\.test\.mjs/u);
  assert.match(packageJson.scripts['performance:gate'], /run-performance-gate\.mjs/u);
});

test('the CI job executes the pinned binary and fail-closed runner on pull requests', () => {
  assert.match(workflow, /github\.event_name == 'pull_request'/u);
  assert.match(workflow, /npm ci/u);
  assert.match(workflow, /chrome-for-testing-public\/\$\{CHROME_VERSION\}\/linux64\/chrome-linux64\.zip/u);
  assert.match(workflow, /PERF_GATE_CHROME_PATH=.*GITHUB_ENV/u);
  assert.match(workflow, /PERF_GATE_WP_CONTAINER: \$\{\{ job\.services\.wordpress\.id \}\}/u);
  assert.match(workflow, /run: npm run performance:gate/u);
  assert.doesNotMatch(workflow, /@lhci\/cli|lhci autorun|total-blocking-time|interactive/u);
  assert.match(workflow, /path: \$\{\{ runner\.temp \}\}\/performance-gate\//u);
  assert.match(workflow, /if-no-files-found: error/u);
  const collector = fs.readFileSync(new URL('../scripts/performance-gate/lighthouse-collector.mjs', import.meta.url), 'utf8');
  assert.match(collector, /launch\(\{[\s\S]*?chromePath,[\s\S]*?port: 0/u);
  assert.match(collector, /finally\s*\{\s*await chrome\.kill\(\)/u);
});

test('all performance gate source/config changes trigger the quality workflow', () => {
  for (const changedPath of [
    '.lighthouserc.json',
    'scripts/run-performance-gate.mjs',
    'scripts/validate-performance-evidence.mjs',
    'scripts/performance-gate/**',
    'package.json',
    'package-lock.json',
    'tests/lighthouse-ci-contract.test.mjs',
    'tests/performance-evidence-validator.test.mjs',
    'tests/performance-runner-contract.test.mjs',
  ]) {
    assert.equal(workflow.split(`- '${changedPath}'`).length - 1, 2, `${changedPath} must trigger push and pull_request runs`);
  }
});
