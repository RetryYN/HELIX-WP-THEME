import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';
import { PERFORMANCE_GATE, validatePerformanceEvidence } from '../scripts/validate-performance-evidence.mjs';

const digest = 'a'.repeat(64);
const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');

function makeReport({ lcp = 2000, cls = 0.05, insight = 'notApplicable', runtimeError = undefined,
  url = 'http://localhost/article/fixture/', device = 'sp' } = {}) {
  const audits = {
    'largest-contentful-paint': { numericValue: lcp },
    'cumulative-layout-shift': { numericValue: cls },
  };
  for (const id of PERFORMANCE_GATE.insightIds) {
    audits[id] = {
      id,
      score: insight === 'notApplicable' ? null : 1,
      scoreDisplayMode: insight,
    };
  }
  return {
    lighthouseVersion: PERFORMANCE_GATE.lighthouseVersion,
    userAgent: 'Mozilla/5.0 HeadlessChrome/156.0.0.0',
    requestedUrl: url,
    finalDisplayedUrl: url,
    configSettings: {
      formFactor: device === 'sp' ? 'mobile' : 'desktop',
      screenEmulation: {
        mobile: device === 'sp', width: device === 'sp' ? 390 : 1440,
        height: device === 'sp' ? 844 : 900, deviceScaleFactor: device === 'sp' ? 3 : 1, disabled: false,
      },
    },
    ...(runtimeError ? { runtimeError } : {}),
    audits,
  };
}

function makeEvidence() {
  const themeSourceDigests = Object.fromEntries([
    'theme.json', 'style.css', 'assets/css/theme.css', 'templates/front-page.html',
    'patterns/home-hero.php', 'patterns/home-sections.php', 'patterns/lp.php', 'inc/site-pages.php',
  ].map(file => [file, digest]));
  return {
    schema: PERFORMANCE_GATE.schema,
    versions: {
      lighthouse: PERFORMANCE_GATE.lighthouseVersion,
      chrome: PERFORMANCE_GATE.chromeVersion,
      chromeLauncher: PERFORMANCE_GATE.chromeLauncherVersion,
      webVitals: PERFORMANCE_GATE.webVitalsVersion,
      lighthouseLockIntegrity: 'sha512-lighthouse-lock-integrity',
      chromeLauncherLockIntegrity: 'sha512-chrome-launcher-lock-integrity',
      webVitalsLockIntegrity: 'sha512-web-vitals-lock-integrity',
    },
    sourceDigests: { 'scripts/example.mjs': digest, '.lighthouserc.json': digest,
      ...Object.fromEntries(Object.entries(themeSourceDigests).map(([file, value]) => [`${PERFORMANCE_GATE.themeDir}/${file}`, value])) },
    probeCompletion: { remaining: true, listing: true, comparison: true },
    runtime: {
      themeSourceScan: { complete: true, matchesWorktree: true,
        expectedFileCount: Object.keys(themeSourceDigests).length,
        mountedFileCount: Object.keys(themeSourceDigests).length, sourceDigests: themeSourceDigests },
      unloadListenerCount: 0,
      noStoreResponsePaths: [],
      appOwnedConnectionPresent: false,
      pagehideClosesConnection: null,
      probeScans: Object.fromEntries(['remaining', 'listing', 'comparison'].map(probe => [probe, {
        bfcacheScanComplete: true, unloadListenerCount: 0, noStoreResponsePaths: [], measurementCount: 1,
      }])),
    },
    fixtures: {
      bootstrapLandingPage: { ready: true, disposableWithWordPressService: true },
      article: { ownerVerified: true, cleanupAttempted: true, absenceConfirmed: true },
      listing: { ownerVerified: true, cleanupAttempted: true, absenceConfirmed: true },
      comparison: { ownerVerified: true, cleanupAttempted: true, absenceConfirmed: true },
    },
    conditions: PERFORMANCE_GATE.pageTypes.flatMap(pageType => PERFORMANCE_GATE.devices.map(device => ({
      pageType,
      device,
      url: `http://localhost/${pageType}/fixture/`,
      runs: [1, 2, 3].map(index => ({ index, report: makeReport({
        url: `http://localhost/${pageType}/fixture/`, device,
      }) })),
      inp: {
        positive: { source: 'web-vitals.onINP', mode: 'positive', valueMs: 80, actionCompleted: true,
          viewport: device === 'sp' ? { width: 390, height: 844 } : { width: 1440, height: 900 },
          deviceScaleFactor: device === 'sp' ? 3 : 1, reducedMotion: 'no-preference' },
        negative: { source: 'web-vitals.onINP', mode: '400ms-browser-only-negative', valueMs: 450,
          injectedDelayMs: 400, actionCompleted: true,
          viewport: device === 'sp' ? { width: 390, height: 844 } : { width: 1440, height: 900 },
          deviceScaleFactor: device === 'sp' ? 3 : 1, reducedMotion: 'no-preference' },
      },
    }))),
    homeRegression: {
      purpose: 'separate-regression-only; excluded from the eight required page/device conditions',
      url: 'http://localhost/', device: 'mobile',
      screenEmulation: { width: 412, height: 823, deviceScaleFactor: 1.75 },
      aggregation: 'maximum-of-all-three-runs', lcpLimitMs: 2500, renderBlockingInsightMaxLength: 0,
      responseScan: { complete: true, status: 200, cacheControl: 'no-cache, must-revalidate', noStore: false,
        finalUrl: 'http://localhost/' },
      runs: [1, 2, 3].map(index => ({ index, sha256: digest, report: {
        lighthouseVersion: PERFORMANCE_GATE.lighthouseVersion,
        requestedUrl: 'http://localhost/', finalDisplayedUrl: 'http://localhost/',
        configSettings: { formFactor: 'mobile', screenEmulation: {
          mobile: true, width: 412, height: 823, deviceScaleFactor: 1.75, disabled: false,
        } },
        audits: {
          'largest-contentful-paint': { numericValue: 2200 },
          'render-blocking-insight': { id: 'render-blocking-insight', score: 1,
            scoreDisplayMode: 'numeric', details: { type: 'table', items: [] } },
        },
      } })),
    },
  };
}

test('accepts complete coverage and reports the maximum of all three Lighthouse runs', () => {
  const evidence = makeEvidence();
  evidence.conditions[0].runs[2].report = makeReport({ lcp: 2499, cls: 0.09, insight: 'informative', url: evidence.conditions[0].url });
  const result = validatePerformanceEvidence(evidence);
  assert.equal(result.accepted, true, JSON.stringify(result.errors));
  assert.equal(result.aggregation, 'maximum-of-all-three-runs');
  assert.equal(result.summary.conditions, 8);
  assert.equal(result.summary.lighthouseRuns, 24);
  assert.equal(result.summary.lcpMs, 2499);
});

test('fails closed for a Lighthouse threshold breach, missing run, duplicate coverage, or runtimeError', () => {
  const overBudget = makeEvidence();
  overBudget.conditions[0].runs[1].report = makeReport({ lcp: 2501, url: overBudget.conditions[0].url });
  assert.equal(validatePerformanceEvidence(overBudget).accepted, false);

  const missing = makeEvidence();
  missing.conditions[0].runs.pop();
  assert.equal(validatePerformanceEvidence(missing).accepted, false);

  const duplicate = makeEvidence();
  duplicate.conditions[1] = { ...duplicate.conditions[0] };
  assert.equal(validatePerformanceEvidence(duplicate).accepted, false);

  const runtimeError = makeEvidence();
  runtimeError.conditions[0].runs[0].report = makeReport({ runtimeError: { code: 'PROTOCOL_TIMEOUT' }, url: runtimeError.conditions[0].url });
  assert.equal(validatePerformanceEvidence(runtimeError).accepted, false);
});

test('treats notApplicable and informative insight scores as valid unless an insight reports an error', () => {
  const valid = makeEvidence();
  valid.conditions[0].runs[0].report = makeReport({ insight: 'notApplicable' });
  assert.equal(validatePerformanceEvidence(valid).accepted, true);

  const diagnosticError = makeEvidence();
  diagnosticError.conditions[0].runs[0].report.audits['inp-breakdown-insight'].scoreDisplayMode = 'error';
  assert.equal(validatePerformanceEvidence(diagnosticError).accepted, false);
});

test('rejects the isolated 400ms interaction when it appears as positive INP', () => {
  const evidence = makeEvidence();
  evidence.conditions[0].inp.positive = {
    source: 'web-vitals.onINP', mode: 'positive', valueMs: 451, actionCompleted: true,
  };
  assert.equal(validatePerformanceEvidence(evidence).accepted, false);
});

test('returns structured failures for missing and invalid URLs, including malformed raw report URLs', () => {
  for (const value of [undefined, 'not-url']) {
    const evidence = makeEvidence();
    evidence.conditions[0].url = value;
    assert.doesNotThrow(() => validatePerformanceEvidence(evidence));
    assert.equal(validatePerformanceEvidence(evidence).accepted, false);
  }
  const reportUrl = makeEvidence();
  reportUrl.conditions[0].runs[0].report.requestedUrl = '%%%';
  assert.equal(validatePerformanceEvidence(reportUrl).accepted, false);
  const otherPage = makeEvidence();
  otherPage.conditions[0].runs[0].report.finalDisplayedUrl = 'http://localhost/a-different-page/';
  assert.equal(validatePerformanceEvidence(otherPage).accepted, false);
  const otherQuery = makeEvidence();
  otherQuery.conditions[0].url += '?fixture=expected';
  assert.equal(validatePerformanceEvidence(otherQuery).accepted, false);
});

test('rejects a PC raw report relabeled as SP, disabled screen emulation, and metric diagnostic errors', () => {
  const wrongDevice = makeEvidence();
  wrongDevice.conditions[0].runs[0].report = makeReport({
    url: wrongDevice.conditions[0].url, device: 'pc',
  });
  assert.equal(validatePerformanceEvidence(wrongDevice).accepted, false);

  const disabledScreen = makeEvidence();
  disabledScreen.conditions[0].runs[0].report.configSettings.screenEmulation.disabled = true;
  assert.equal(validatePerformanceEvidence(disabledScreen).accepted, false);

  const metricError = makeEvidence();
  metricError.conditions[0].runs[0].report.audits['largest-contentful-paint'] = {
    numericValue: 1200, scoreDisplayMode: 'error', errorMessage: 'diagnostic failure',
  };
  assert.equal(validatePerformanceEvidence(metricError).accepted, false);
});

test('fails closed for missing runtime scan and malformed condition/run collections', () => {
  const missingConnectionScan = makeEvidence();
  delete missingConnectionScan.runtime.appOwnedConnectionPresent;
  assert.equal(validatePerformanceEvidence(missingConnectionScan).accepted, false);

  const missingBfcacheProbe = makeEvidence();
  delete missingBfcacheProbe.runtime.probeScans.listing.measurementCount;
  assert.equal(validatePerformanceEvidence(missingBfcacheProbe).accepted, false);

  for (const malformed of [null, { ...makeEvidence().conditions[0], runs: null }]) {
    const evidence = makeEvidence();
    evidence.conditions[0] = malformed;
    assert.doesNotThrow(() => validatePerformanceEvidence(evidence));
    assert.equal(validatePerformanceEvidence(evidence).accepted, false);
  }
});

test('keeps HOME as a separate blocking three-run mobile regression gate', () => {
  const evidence = makeEvidence();
  evidence.homeRegression.runs[1].report.audits['largest-contentful-paint'].numericValue = 2501;
  assert.equal(validatePerformanceEvidence(evidence).accepted, false);
  const wrongViewport = makeEvidence();
  wrongViewport.homeRegression.runs[0].report.configSettings.screenEmulation.width = 1440;
  assert.equal(validatePerformanceEvidence(wrongViewport).accepted, false);
  const missingDigest = makeEvidence();
  delete missingDigest.homeRegression.runs[0].sha256;
  assert.equal(validatePerformanceEvidence(missingDigest).accepted, false);
  const noStore = makeEvidence();
  noStore.homeRegression.responseScan.cacheControl = 'no-store';
  noStore.homeRegression.responseScan.noStore = true;
  noStore.runtime.noStoreResponsePaths.push('/');
  assert.equal(validatePerformanceEvidence(noStore).accepted, false);
});

test('preserves the zero render-blocking limit on each HOME report only', () => {
  const emptyTables = makeEvidence();
  assert.equal(validatePerformanceEvidence(emptyTables).accepted, true);

  const nonemptyHomeRun = makeEvidence();
  nonemptyHomeRun.homeRegression.runs[2].report.audits['render-blocking-insight'].details.items.push({ url: 'https://example.test/app.css' });
  assert.equal(validatePerformanceEvidence(nonemptyHomeRun).accepted, false, 'one nonempty HOME run fails');

  const missingAudit = makeEvidence();
  delete missingAudit.homeRegression.runs[0].report.audits['render-blocking-insight'];
  assert.equal(validatePerformanceEvidence(missingAudit).accepted, false);

  const diagnosticError = makeEvidence();
  diagnosticError.homeRegression.runs[0].report.audits['render-blocking-insight'].scoreDisplayMode = 'error';
  assert.equal(validatePerformanceEvidence(diagnosticError).accepted, false);

  const malformedTable = makeEvidence();
  malformedTable.homeRegression.runs[0].report.audits['render-blocking-insight'].details.items = null;
  assert.equal(validatePerformanceEvidence(malformedTable).accepted, false);

  const unavailableInsight = makeEvidence();
  unavailableInsight.homeRegression.runs[0].report.audits['render-blocking-insight'] = {
    id: 'render-blocking-insight', score: null, scoreDisplayMode: 'notApplicable',
  };
  assert.equal(validatePerformanceEvidence(unavailableInsight).accepted, false,
    'details-less notApplicable is not evidence of an empty measured table');

  const otherSurfaces = makeEvidence();
  for (const condition of otherSurfaces.conditions) {
    for (const run of condition.runs) {
      run.report.audits['render-blocking-insight'] = { id: 'render-blocking-insight',
        details: { type: 'table', items: [{ url: 'https://example.test/app.css' }] } };
    }
  }
  assert.equal(validatePerformanceEvidence(otherSurfaces).accepted, true,
    'the legacy HOME-only assertion does not add a zero-row requirement to the eight CWV conditions');
});

test('rejects incomplete or mismatched mounted theme source manifests', () => {
  const missingRenderedInput = makeEvidence();
  delete missingRenderedInput.runtime.themeSourceScan.sourceDigests['patterns/home-hero.php'];
  assert.equal(validatePerformanceEvidence(missingRenderedInput).accepted, false);

  const mountedMismatch = makeEvidence();
  mountedMismatch.runtime.themeSourceScan.sourceDigests['assets/css/theme.css'] = 'b'.repeat(64);
  assert.equal(validatePerformanceEvidence(mountedMismatch).accepted, false);
});

test('rejects INP measured with a mismatched viewport, scale, or reduced-motion setting', () => {
  const wrongViewport = makeEvidence();
  wrongViewport.conditions[0].inp.positive.viewport = { width: 390, height: 900 };
  assert.equal(validatePerformanceEvidence(wrongViewport).accepted, false);

  const wrongScale = makeEvidence();
  wrongScale.conditions[0].inp.negative.deviceScaleFactor = 1;
  assert.equal(validatePerformanceEvidence(wrongScale).accepted, false);

  const reducedMotion = makeEvidence();
  reducedMotion.conditions[0].inp.positive.reducedMotion = 'reduce';
  assert.equal(validatePerformanceEvidence(reducedMotion).accepted, false);
});

test('validator CLI saves accepted and rejected validation results with matching exit status', () => {
  const temporaryDirectory = fs.mkdtempSync(path.join(os.tmpdir(), 'helix-performance-validator-cli-'));
  const inputPath = path.join(temporaryDirectory, 'evidence.json');
  const outputPath = path.join(temporaryDirectory, 'validation.json');
  const runCli = evidence => {
    fs.writeFileSync(inputPath, JSON.stringify(evidence));
    return spawnSync(process.execPath, [path.join(root, 'scripts/validate-performance-evidence.mjs'), inputPath, outputPath], {
      cwd: root, encoding: 'utf8',
    });
  };
  try {
    const positive = runCli(makeEvidence());
    assert.equal(positive.status, 0, positive.stderr);
    assert.equal(JSON.parse(fs.readFileSync(outputPath, 'utf8')).accepted, true);

    const negativeEvidence = makeEvidence();
    negativeEvidence.conditions[0].runs[0].report.audits['largest-contentful-paint'].numericValue = 2501;
    const negative = runCli(negativeEvidence);
    assert.equal(negative.status, 1, negative.stderr);
    const validation = JSON.parse(fs.readFileSync(outputPath, 'utf8'));
    assert.equal(validation.accepted, false);
    assert.ok(validation.errors.length > 0);
  } finally {
    fs.rmSync(temporaryDirectory, { recursive: true, force: true });
  }
});
