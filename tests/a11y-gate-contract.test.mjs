import test from 'node:test';
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { assertNoAxeViolations } from '../scripts/verify-a11y.mjs';

const workflow = readFileSync(new URL('../.github/workflows/theme-quality-gate.yml', import.meta.url), 'utf8');
const runner = readFileSync(new URL('../scripts/verify-a11y.mjs', import.meta.url), 'utf8');

test('a11y gate accepts zero axe violations', () => {
  assert.doesNotThrow(() => assertNoAxeViolations([]));
});

test('a11y gate rejects exactly one axe violation', () => {
  assert.throws(() => assertNoAxeViolations([{ id: 'button-name' }]), /axe found 1 violation/);
});

test('WordPress CI runs axe on real routes and the negative control', () => {
  assert.match(workflow, /npm ci\s+npx playwright install --with-deps chromium/);
  assert.match(workflow, /node scripts\/verify-a11y\.mjs\s/);
  assert.match(workflow, /node scripts\/verify-a11y\.mjs --negative-control/);
  assert.match(workflow, /name: Upload accessibility reports[\s\S]*name: a11y-axe-reports/);
  assert.match(workflow, /tests\/a11y-gate-contract\.test\.mjs/);
  assert.match(runner, /new AxeBuilder\(\{ page \}\)\.withTags\(wcagTags\)\.analyze\(\)/);
  assert.match(runner, /width: 390/);
  assert.match(runner, /\/category\/a11y-fixture\//);
  assert.match(runner, /scanResults\[0\]\.violations\.length !== 1/);
  assert.match(runner, /wt-axe-gate-report\.v1/);
});
