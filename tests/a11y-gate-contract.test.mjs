import test from 'node:test';
import assert from 'node:assert/strict';
import { mkdtemp, readFile, rm } from 'node:fs/promises';
import os from 'node:os';
import path from 'node:path';
import { readFileSync } from 'node:fs';
import { assertNoAxeViolations, runA11yScan, writeA11yReport } from '../scripts/verify-a11y.mjs';

const workflow = readFileSync(new URL('../.github/workflows/theme-quality-gate.yml', import.meta.url), 'utf8');
const runner = readFileSync(new URL('../scripts/verify-a11y.mjs', import.meta.url), 'utf8');
const bootstrap = readFileSync(new URL('../scripts/bootstrap-quality-wordpress.sh', import.meta.url), 'utf8');

function fakeBrowser({ failingRoutes = [], overflowing = false, violations = [] } = {}) {
  const visited = [];
  const browser = {
    async launch() {
      return {
        async newContext() {
          let activeUrl = '';
          const page = {
            async goto(url) {
              activeUrl = url;
              visited.push(url);
              if (failingRoutes.some((route) => url.includes(route))) throw new Error('fixture navigation failure');
              const status = url.includes('/missing/') ? 404 : 200;
              return { status: () => status };
            },
            locator() {
              return { evaluate: async () => {
                const template = activeUrl.match(/a11y-fixture-(lp|event|zone-catalog|canvas)/)?.[1];
                const templateSlug = template ? `page-${template}` : '';
                return { themeActive: true, className: templateSlug ? `wp-theme-helix-wt page-template-${templateSlug}` : 'wp-theme-helix-wt' };
              } };
            },
            async evaluate() {
              return { viewport: 390, content: overflowing ? 430 : 390 };
            },
          };
          return { newPage: async () => page, close: async () => {} };
        },
        close: async () => {},
      };
    },
  };
  class Axe {
    constructor() {}
    withTags() { return this; }
    async analyze() { return { violations }; }
  }
  return { browser, Axe, visited };
}

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
  assert.match(workflow, /if-no-files-found: error/);
  assert.match(workflow, /tests\/a11y-gate-contract\.test\.mjs/);
  assert.match(runner, /new Axe\(\{ page \}\)\.withTags\(wcagTags\)\.analyze\(\)/);
  assert.match(runner, /expectedStatus: 404/);
  for (const slug of ['a11y-fixture-lp', 'a11y-fixture-event', 'a11y-fixture-zone-catalog', 'a11y-fixture-canvas']) {
    assert.match(runner, new RegExp(slug));
    assert.match(bootstrap, new RegExp(slug));
  }
  assert.match(runner, /errors,\n      failedRoutes/);
  assert.match(runner, /writeFile\(reportPath/);
});

test('failed navigation is reported and later routes still run', async () => {
  const { browser, Axe, visited } = fakeBrowser({ failingRoutes: ['/bad/'] });
  const result = await runA11yScan({
    browserType: browser,
    Axe,
    scanRoutes: [
    { path: '/bad/', expectedStatus: 200 },
    { path: '/after/', expectedStatus: 200 },
    ],
    scanViewports: [{ name: 'desktop', width: 1440, height: 900 }],
    isNegativeControl: false,
    urlBase: 'http://fixture.invalid',
    log: () => {},
  });
  assert.equal(result.exitCode, 1);
  assert.equal(result.report.scanResults.length, 2);
  assert.match(result.report.scanResults[0].errors[0], /fixture navigation failure/);
  assert.ok(visited.some((url) => url.endsWith('/after/')));
});

test('404 status is accepted when expected and mobile overflow is recorded as true', async () => {
  const { browser, Axe } = fakeBrowser({ overflowing: true });
  const result = await runA11yScan({
    browserType: browser,
    Axe,
    scanRoutes: [{ path: '/missing/', expectedStatus: 404 }],
    scanViewports: [{ name: 'mobile', width: 390, height: 844 }],
    isNegativeControl: false,
    urlBase: 'http://fixture.invalid',
    log: () => {},
  });
  assert.equal(result.report.scanResults[0].httpStatus, 404);
  assert.equal(result.report.scanResults[0].horizontalOverflow, true);
  assert.equal(result.report.completed, false);
  assert.match(result.report.scanResults[0].errors.join(' '), /horizontal overflow/);
});

test('mobile stores the measured false value while desktop keeps horizontalOverflow null', async () => {
  const mobile = fakeBrowser();
  const mobileResult = await runA11yScan({
    browserType: mobile.browser,
    Axe: mobile.Axe,
    scanRoutes: [{ path: '/', expectedStatus: 200 }],
    scanViewports: [{ name: 'mobile', width: 390, height: 844 }],
    isNegativeControl: false,
    urlBase: 'http://fixture.invalid',
    log: () => {},
  });
  const desktop = fakeBrowser();
  const desktopResult = await runA11yScan({
    browserType: desktop.browser,
    Axe: desktop.Axe,
    scanRoutes: [{ path: '/', expectedStatus: 200 }],
    scanViewports: [{ name: 'desktop', width: 1440, height: 900 }],
    isNegativeControl: false,
    urlBase: 'http://fixture.invalid',
    log: () => {},
  });
  assert.equal(mobileResult.report.scanResults[0].horizontalOverflow, false);
  assert.equal(desktopResult.report.scanResults[0].horizontalOverflow, null);
});

test('negative control exits 2 only for its single injected button-name violation', async () => {
  const { browser, Axe } = fakeBrowser({ violations: [{
    id: 'button-name',
    nodes: [{ target: ['[data-a11y-negative-control="true"]'] }],
  }] });
  const result = await runA11yScan({
    browserType: browser,
    Axe,
    scanRoutes: [{ path: '/', expectedStatus: 200 }],
    scanViewports: [{ name: 'desktop', width: 1440, height: 900 }],
    isNegativeControl: true,
    urlBase: 'http://fixture.invalid',
    log: () => {},
  });
  assert.equal(result.exitCode, 2);
  assert.equal(result.report.completed, true);
  assert.equal(result.report.expectedNegativeSeen, true);
});

test('a report containing failure rows can be written for later diagnosis', async () => {
  const { browser, Axe } = fakeBrowser({ failingRoutes: ['/bad/'] });
  const result = await runA11yScan({
    browserType: browser,
    Axe,
    scanRoutes: [{ path: '/bad/', expectedStatus: 200 }],
    scanViewports: [{ name: 'desktop', width: 1440, height: 900 }],
    isNegativeControl: false,
    urlBase: 'http://fixture.invalid',
    log: () => {},
  });
  const dir = await mkdtemp(path.join(os.tmpdir(), 'wt-a11y-report-'));
  const reportPath = path.join(dir, 'nested', 'report.json');
  try {
    await writeA11yReport(reportPath, result.report);
    const saved = JSON.parse(await readFile(reportPath, 'utf8'));
    assert.equal(saved.completed, false);
    assert.match(saved.scanResults[0].errors[0], /fixture navigation failure/);
  } finally {
    await rm(dir, { recursive: true, force: true });
  }
});
