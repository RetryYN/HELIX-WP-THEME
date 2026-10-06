import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { createHash, randomBytes } from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { chromium } from '@playwright/test';
import { collectLighthouseRuns } from './lighthouse-collector.mjs';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const evidenceDir = path.resolve(process.env.PERF_GATE_REPORT_DIR ?? path.join(root, '.performance-gate'));
const reportPath = path.join(evidenceDir, 'compare-pattern-inp-probe.json');
const phpPath = path.join(root, 'scripts/performance-gate/compare-pattern-fixture.php');
const theme = 'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/';
const patternRelative = `${theme}patterns/compare-article.php`;
const baseUrl = process.env.PERF_GATE_BASE_URL;
const container = process.env.PERF_GATE_WP_CONTAINER;
const webVitalsRoot = path.join(root, 'node_modules/web-vitals');
const webVitalsPath = path.join(webVitalsRoot, 'dist/web-vitals.iife.js');
const webVitalsPackage = JSON.parse(fs.readFileSync(path.join(webVitalsRoot, 'package.json'), 'utf8'));
const chromePath = process.env.PERF_GATE_CHROME_PATH;
const sha256 = value => createHash('sha256').update(value).digest('hex');
const owner = randomBytes(24).toString('hex');
const slug = `compare-pattern-probe-${randomBytes(8).toString('hex')}`;
const themePattern = fs.readFileSync(path.join(root, patternRelative));
const themePatternSha256 = sha256(themePattern);

const report = {
  schema: 'helix-local-compare-pattern-inp-probe.v1',
  completed: false,
  processExitCode: null,
  scope: 'Temporary owned WordPress pattern fixture and native FAQ interaction measured with web-vitals onINP.',
  startedAt: new Date().toISOString(),
  environment: { baseUrl, container, fixtureSlug: slug },
  versions: { node: process.version, webVitals: webVitalsPackage.version },
  sourceDigests: {},
  runtime: null,
  browser: null,
  bfcache: { unloadListenerCount: 0, noStoreResponsePaths: [], measurementCount: 0 },
  fixture: { created: false, postId: null, cleanupAttempted: false, cleanup: null },
  lighthouseRuns: {},
  checks: [],
  interactions: [],
  errors: [],
};
const save = () => fs.writeFileSync(reportPath, JSON.stringify(report, null, 2) + '\n');
const check = (name, pass, details = undefined) => report.checks.push({ name, pass: !!pass, ...(details === undefined ? {} : { details }) });

function php(action, extras = {}) {
  const env = {
    ...process.env,
    COMPARE_PROBE_ACTION: action,
    COMPARE_PROBE_SLUG: slug,
    COMPARE_PROBE_OWNER: owner,
    COMPARE_PROBE_EXPECTED_PATTERN_SHA256: themePatternSha256,
    ...extras,
  };
  const result = spawnSync('docker', ['exec', '-i', ...Object.entries(env)
    .filter(([key]) => key.startsWith('COMPARE_PROBE_'))
    .flatMap(([key, value]) => ['-e', `${key}=${value}`]), container, 'php'], {
    cwd: root,
    input: fs.readFileSync(phpPath),
    encoding: 'utf8',
    maxBuffer: 1024 * 1024,
    env: process.env,
  });
  if (result.status !== 0) throw new Error(`fixture PHP ${action} failed (exit ${result.status ?? 'unknown'})`);
  const lines = result.stdout.trim().split('\n');
  try { return JSON.parse(lines.at(-1)); }
  catch { throw new Error(`fixture PHP ${action} returned invalid JSON`); }
}

let browser;
let created = false;
let createAttempted = false;
let fixtureUrl = null;
let primaryError = null;
save();

try {
  assert.equal(webVitalsPackage.version, '6.2.2');
  assert.ok(fs.existsSync(chromePath), 'pinned Chrome 156 binary exists');
  report.sourceDigests = {
    'scripts/performance-gate/compare-pattern-inp-probe.mjs': sha256(fs.readFileSync(fileURLToPath(import.meta.url))),
    'scripts/performance-gate/compare-pattern-fixture.php': sha256(fs.readFileSync(phpPath)),
    [patternRelative]: themePatternSha256,
  };

  const runtime = php('inspect');
  report.runtime = {
    wordpressVersion: runtime.wordpressVersion,
    phpVersion: runtime.phpVersion,
    theme: runtime.theme,
    patternRegistered: runtime.patternRegistered,
    containerPatternSha256: runtime.patternSha256,
  };
  assert.match(runtime.wordpressVersion, /^7\.1(?:\.|$)/u);
  assert.equal(runtime.theme, 'helix-wt');
  assert.equal(runtime.patternRegistered, true);
  assert.equal(runtime.patternSha256, themePatternSha256, 'mounted WordPress pattern matches the worktree source');
  assert.equal(runtime.slugExists, false);
  check('worktree-theme-pattern-mounted-by-wordpress', runtime.patternSha256 === themePatternSha256,
    { localSha256: themePatternSha256, wordpressSha256: runtime.patternSha256 });
  save();

  createAttempted = true;
  const creation = php('create');
  created = true;
  report.fixture.created = true;
  report.fixture.postId = creation.postId;
  report.fixture.slug = creation.slug;
  const permalink = new URL(creation.permalink);
  assert.equal(permalink.origin, baseUrl, 'fixture permalink stays on the allowed lab origin');
  fixtureUrl = permalink.href;
  report.fixture.permalink = fixtureUrl;
  assert.equal(creation.slug, slug);
  assert.ok(Number.isInteger(creation.postId) && creation.postId > 0);
  check('owned-fixture-created-through-registered-pattern', true, { postId: creation.postId, slug });
  save();

  for (const device of ['sp', 'pc']) {
    const coverageId = `comparison:${device}`;
    report.lighthouseRuns[coverageId] = await collectLighthouseRuns({
      url: fixtureUrl, coverageId, device, reportDir: evidenceDir,
    });
  }

  const library = fs.readFileSync(webVitalsPath, 'utf8');
  browser = await chromium.launch({ executablePath: chromePath, headless: true, args: ['--no-sandbox'] });
  report.browser = { version: browser.version(), expectedVersion: '156.0.8075.0' };
  assert.match(report.browser.version, /156\.0\.8075\.0/u);

  for (const width of [390, 1440]) {
    for (const negative of [false, true]) {
      const viewport = width === 390 ? { width: 390, height: 844 } : { width: 1440, height: 900 };
      const deviceScaleFactor = width === 390 ? 3 : 1;
      const context = await browser.newContext({ viewport, deviceScaleFactor, reducedMotion: 'no-preference' });
      const page = await context.newPage();
      const vitalsReports = [];
      try {
        await page.exposeFunction('__captureCompareProbeINP', metric => vitalsReports.push(metric));
        await page.addInitScript({ content: `${library}
          ;window.__helixPerfUnloadListenerCount=0;
          ;const __helixPerfAddEventListener=window.addEventListener.bind(window);
          ;window.addEventListener=(type,...args)=>{
            if(type==='unload')window.__helixPerfUnloadListenerCount++;
            return __helixPerfAddEventListener(type,...args);
          };
          ;const __helixPerfDocumentAddEventListener=document.addEventListener.bind(document);
          ;document.addEventListener=(type,...args)=>{
            if(type==='unload')window.__helixPerfUnloadListenerCount++;
            return __helixPerfDocumentAddEventListener(type,...args);
          };
          ;window.__compareProbeINP=[];
          webVitals.onINP(metric=>{
            const row={
            name:metric.name,value:metric.value,rating:metric.rating,
            entries:(metric.entries||[]).map(entry=>({name:entry.name,duration:entry.duration,interactionId:entry.interactionId}))
            };
            window.__compareProbeINP.push(row);
            window.__captureCompareProbeINP(row);
          },{reportAllChanges:true,durationThreshold:16});` });
        const response = await page.goto(fixtureUrl, { waitUntil: 'networkidle' });
        const cacheControl = response?.headers()['cache-control'] ?? '';
        if (/\bno-store\b/iu.test(cacheControl)) report.bfcache.noStoreResponsePaths.push(new URL(fixtureUrl).pathname);
        assert.equal(response?.status(), 200, `${width}px page status`);
        const deviceSettings = await page.evaluate(() => ({
          viewport: { width: innerWidth, height: innerHeight },
          deviceScaleFactor: devicePixelRatio,
          reducedMotion: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'reduce' : 'no-preference',
        }));
        assert.deepEqual(deviceSettings, { viewport, deviceScaleFactor, reducedMotion: 'no-preference' },
          `${width}px interaction uses the declared viewport, device scale, and normal motion preference`);
        const table = page.locator('main figure.is-style-wt-compare table');
        const details = page.locator('main details.wp-block-details');
        assert.ok(await table.count() > 0, `${width}px comparison table exists`);
        assert.ok(await details.count() > 0, `${width}px native details FAQ exists`);
        const summary = details.first().locator('summary');
        assert.equal(await details.first().evaluate(el => el.open), false, `${width}px FAQ starts closed`);
        if (negative) {
          await summary.evaluate(el => el.addEventListener('click', () => {
            const end = performance.now() + 400;
            while (performance.now() < end) { /* deliberate browser-only negative */ }
          }, { capture: true, once: true }));
        }
        await summary.click();
        await page.waitForFunction(() => document.querySelector('main details.wp-block-details')?.open === true);
        // The Web Vitals library finalizes pending page-view metrics when this page is actually hidden.
        await page.waitForTimeout(300);
        // Snapshot every assertion value while the fixture DOM is still active.
        // The subsequent about:blank navigation flushes pending page-view INP.
        const domSnapshot = await page.evaluate(() => {
          const table = document.querySelector('main figure.is-style-wt-compare table');
          const details = [...document.querySelectorAll('main details.wp-block-details')];
          return {
            title: document.title,
            comparisonTableCount: table ? 1 : 0,
            comparisonRowCount: table?.querySelectorAll('tr').length ?? 0,
            faqCount: details.length,
            detailsOpenAfterClick: details[0]?.open ?? null,
            eventTimingEntries: performance.getEntriesByType('event')
              .filter(entry => entry.interactionId)
            .slice(-8)
            .map(entry => ({ name: entry.name, duration: entry.duration, interactionId: entry.interactionId,
                summaryTarget: entry.target instanceof Element && entry.target.closest('summary') !== null })),
            unloadListenerCount: window.__helixPerfUnloadListenerCount,
          };
        });
        report.bfcache.unloadListenerCount = Math.max(report.bfcache.unloadListenerCount, domSnapshot.unloadListenerCount);
        report.bfcache.measurementCount += 1;
        await page.goto('about:blank', { waitUntil: 'domcontentloaded' });
        for (let attempt = 0; attempt < 100 && !vitalsReports.length; attempt += 1) {
          await new Promise(resolve => setTimeout(resolve, 100));
        }
        const observed = {
          metrics: vitalsReports,
          events: domSnapshot.eventTimingEntries,
        };
        const inpMs = observed.metrics.at(-1)?.value ?? null;
        assert.ok(Number.isFinite(inpMs), `${width}px ${negative ? 'negative' : 'native'} interaction produced an INP metric`);
        assert.ok(inpMs >= 0, `${width}px INP is a non-negative numeric value`);
        const inpUnder200ms = Number.isFinite(inpMs) && inpMs <= 200;
        if (negative) assert.ok(inpMs > 200, `${width}px injected delay must exceed 200ms INP`);
        else assert.ok(inpUnder200ms, `${width}px positive FAQ interaction INP must be <=200ms`);
        const row = {
          width,
          mode: negative ? '400ms-browser-only-negative' : 'native-faq-interaction',
          viewport: deviceSettings.viewport,
          deviceScaleFactor: deviceSettings.deviceScaleFactor,
          reducedMotion: deviceSettings.reducedMotion,
          status: response.status(),
          title: domSnapshot.title,
          comparisonTableCount: domSnapshot.comparisonTableCount,
          comparisonRowCount: domSnapshot.comparisonRowCount,
          faqCount: domSnapshot.faqCount,
          interaction: 'click:first FAQ summary; assert native details.open changed false -> true',
          detailsOpenAfterClick: domSnapshot.detailsOpenAfterClick,
          inpMs,
          inpUnder200ms,
          positivePerformancePass: !negative && inpUnder200ms,
          inpRating: observed.metrics.at(-1)?.rating ?? null,
          webVitalsEntries: observed.metrics.at(-1)?.entries ?? [],
          eventTimingEntries: observed.events,
          injectedDelayMs: negative ? 400 : 0,
        };
        report.interactions.push(row);
        check(`${width}px:${row.mode}:rendered-comparison-and-faq`, row.status === 200
          && row.comparisonTableCount > 0 && row.comparisonRowCount > 1 && row.faqCount > 0,
        { status: row.status, comparisonTableCount: row.comparisonTableCount,
          comparisonRowCount: row.comparisonRowCount, faqCount: row.faqCount });
        check(`${width}px:${row.mode}:faq-open`, row.detailsOpenAfterClick === true, { inpMs });
        if (!negative) check(`${width}px:positive-inp-at-or-below-200ms`, inpUnder200ms, { inpMs, thresholdMs: 200 });
        if (negative) check(`${width}px:negative-inp-over-200ms`, inpMs > 200, { inpMs, thresholdMs: 200 });
        save();
      } finally {
        await context.close();
      }
    }
  }
} catch (error) {
  primaryError = error;
  report.errors.push(error instanceof Error ? error.message : String(error));
} finally {
  if (browser) await browser.close().catch(error => report.errors.push(`browser close failed: ${error.message}`));
  try {
    report.fixture.cleanupAttempted = createAttempted;
    if (createAttempted) {
      const cleanup = php('delete', { COMPARE_PROBE_POST_ID: String(report.fixture.postId ?? 0) });
      report.fixture.cleanup = cleanup;
      assert.equal(cleanup.deletedOrAbsent, true);
      assert.equal(cleanup.slugAbsent, true);
    }
    const finalCheck = php('inspect');
    assert.equal(finalCheck.slugExists, false, 'fixture slug is absent after cleanup');
    report.fixture.absenceConfirmed = true;
    check('fixture-cleanup-and-absence-confirmed', created && finalCheck.slugExists === false,
      { deletedOrAbsent: report.fixture.cleanup?.deletedOrAbsent ?? false, slugAbsent: finalCheck.slugExists === false });
  } catch (error) {
    report.fixture.cleanupError = error instanceof Error ? error.message : String(error);
    report.errors.push(report.fixture.cleanupError);
  }
}

const mandatoryChecksPass = report.checks.length > 0 && report.checks.every(row => row.pass);
const allFourInteractions = report.interactions.length === 4
  && report.interactions.filter(row => row.mode === 'native-faq-interaction')
    .every(row => Number.isFinite(row.inpMs) && row.inpUnder200ms === true && row.positivePerformancePass === true)
  && report.interactions.filter(row => row.mode === '400ms-browser-only-negative').every(row => row.inpMs > 200);
report.completed = !primaryError && !report.errors.length && mandatoryChecksPass && allFourInteractions
  && report.fixture.created && report.fixture.absenceConfirmed === true;
report.finishedAt = new Date().toISOString();
report.processExitCode = report.completed ? 0 : 1;
save();
console.log(JSON.stringify({
  completed: report.completed,
  processExitCode: report.processExitCode,
  wordpressVersion: report.runtime?.wordpressVersion,
  themePatternMatches: report.runtime?.containerPatternSha256 === themePatternSha256,
  browserVersion: report.browser?.version,
  fixtureCreated: report.fixture.created,
  fixtureAbsent: report.fixture.absenceConfirmed === true,
  interactions: report.interactions.map(({ width, mode, inpMs }) => ({ width, mode, inpMs })),
  errors: report.errors,
}));
if (!report.completed) process.exitCode = 1;
