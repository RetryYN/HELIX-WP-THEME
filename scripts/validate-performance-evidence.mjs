#!/usr/bin/env node

import fs from 'node:fs';
import { isDeepStrictEqual } from 'node:util';

export const PERFORMANCE_GATE = Object.freeze({
  schema: 'helix-performance-evidence.v1',
  lighthouseVersion: '13.5.0',
  chromeVersion: '156.0.8075.0',
  chromeLauncherVersion: '1.2.1',
  webVitalsVersion: '6.2.2',
  themeDir: 'docs/research/2026-09-05-design-prototype-03/theme/helix-wt',
  runsPerCondition: 3,
  limits: Object.freeze({ lcpMs: 2500, inpMs: 200, cls: 0.1 }),
  insightIds: Object.freeze([
    'lcp-discovery-insight',
    'lcp-breakdown-insight',
    'cls-culprits-insight',
    'inp-breakdown-insight',
  ]),
  pageTypes: Object.freeze(['article', 'landing-page', 'listing', 'comparison']),
  devices: Object.freeze(['sp', 'pc']),
});

const expectedCoverage = new Set(PERFORMANCE_GATE.pageTypes.flatMap(pageType =>
  PERFORMANCE_GATE.devices.map(device => `${pageType}:${device}`)));
const isFiniteNumber = value => typeof value === 'number' && Number.isFinite(value);
const sha256 = value => typeof value === 'string' && /^[a-f0-9]{64}$/u.test(value);

function fail(errors, path, message) {
  errors.push({ path, message });
}

function validateInsights(report, errors, path) {
  const audits = report?.audits;
  for (const id of PERFORMANCE_GATE.insightIds) {
    const audit = audits?.[id];
    if (!audit || audit.id !== id) {
      fail(errors, `${path}.audits.${id}`, 'required Lighthouse insight is missing or mis-keyed');
      continue;
    }
    // Lighthouse 13 insights may be informative or not applicable for a run.
    // Only a diagnostic error is a failure; a null score alone is not.
    if (audit.scoreDisplayMode === 'error' || audit.errorMessage) {
      fail(errors, `${path}.audits.${id}`, 'Lighthouse insight reported a diagnostic error');
    }
  }
}

function parseEvidenceUrl(value, base = undefined) {
  if (typeof value !== 'string') return null;
  try { return new URL(value, base); } catch { return null; }
}

function validateLighthouseRun(run, conditionId, errors) {
  const path = `conditions.${conditionId}.runs[${run?.index}]`;
  if (!Number.isInteger(run?.index) || run.index < 1 || run.index > PERFORMANCE_GATE.runsPerCondition) {
    fail(errors, `${path}.index`, 'run index must be 1, 2, or 3');
  }
  const report = run?.report;
  if (!report || typeof report !== 'object') {
    fail(errors, `${path}.report`, 'Lighthouse report is missing');
    return null;
  }
  if (report.lighthouseVersion !== PERFORMANCE_GATE.lighthouseVersion) {
    fail(errors, `${path}.report.lighthouseVersion`, 'unexpected Lighthouse version');
  }
  if (report.runtimeError) fail(errors, `${path}.report.runtimeError`, 'Lighthouse runtimeError is present');
  const userAgent = String(report.userAgent ?? '');
  if (!/(?:Headless)?Chrome\/156\./u.test(userAgent)) {
    fail(errors, `${path}.report.userAgent`, 'report was not produced by the pinned Chrome major');
  }
  const lcpAudit = report.audits?.['largest-contentful-paint'];
  const clsAudit = report.audits?.['cumulative-layout-shift'];
  const lcp = lcpAudit?.numericValue;
  const cls = clsAudit?.numericValue;
  for (const [id, audit] of [['largest-contentful-paint', lcpAudit], ['cumulative-layout-shift', clsAudit]]) {
    if (!audit || audit.scoreDisplayMode === 'error' || audit.errorMessage) {
      fail(errors, `${path}.report.audits.${id}`, 'required Lighthouse metric reported a diagnostic error');
    }
  }
  if (!isFiniteNumber(lcp) || lcp < 0) fail(errors, `${path}.report.lcp`, 'LCP numericValue is missing or invalid');
  if (!isFiniteNumber(cls) || cls < 0) fail(errors, `${path}.report.cls`, 'CLS numericValue is missing or invalid');
  validateInsights(report, errors, `${path}.report`);
  const expectedUrl = parseEvidenceUrl(run.expectedUrl);
  if (!expectedUrl) {
    fail(errors, `${path}.expectedUrl`, 'assigned fixture URL is missing or invalid');
  } else {
    const requestedUrl = parseEvidenceUrl(report.requestedUrl, expectedUrl);
    const finalUrl = parseEvidenceUrl(report.finalDisplayedUrl ?? report.mainDocumentUrl, expectedUrl);
    if (!requestedUrl || requestedUrl.origin !== expectedUrl.origin || requestedUrl.pathname !== expectedUrl.pathname
      || requestedUrl.search !== expectedUrl.search || requestedUrl.hash !== expectedUrl.hash) {
      fail(errors, `${path}.report.requestedUrl`, 'report URL does not match the assigned fixture route');
    }
    if (!finalUrl || finalUrl.origin !== expectedUrl.origin || finalUrl.pathname !== expectedUrl.pathname
      || finalUrl.search !== expectedUrl.search || finalUrl.hash !== expectedUrl.hash) {
      fail(errors, `${path}.report.finalDisplayedUrl`, 'final report URL does not match the assigned fixture route');
    }
  }
  const expectedDevice = conditionId.split(':')[1];
  const formFactor = expectedDevice === 'sp' ? 'mobile' : 'desktop';
  const width = expectedDevice === 'sp' ? 390 : 1440;
  const height = expectedDevice === 'sp' ? 844 : 900;
  const deviceScaleFactor = expectedDevice === 'sp' ? 3 : 1;
  const screen = report.configSettings?.screenEmulation;
  if (report.configSettings?.formFactor !== formFactor || !screen
    || screen.width !== width || screen.height !== height || screen.mobile !== (expectedDevice === 'sp')
    || screen.deviceScaleFactor !== deviceScaleFactor || screen.disabled !== false) {
    fail(errors, `${path}.report.configSettings`, 'Lighthouse form factor or screen emulation does not match the assigned device');
  }
  if (!isFiniteNumber(lcp) || !isFiniteNumber(cls)) return null;
  return { lcpMs: lcp, cls };
}

function validateInp(record, conditionId, kind, errors) {
  const path = `conditions.${conditionId}.inp.${kind}`;
  if (!record || typeof record !== 'object') {
    fail(errors, path, `${kind} INP result is missing`);
    return null;
  }
  if (record.source !== 'web-vitals.onINP') fail(errors, `${path}.source`, 'INP must come from web-vitals onINP');
  if (record.actionCompleted !== true) fail(errors, `${path}.actionCompleted`, 'real page interaction was not confirmed');
  if (!isFiniteNumber(record.valueMs) || record.valueMs < 0) fail(errors, `${path}.valueMs`, 'INP value is missing or invalid');
  const device = conditionId.split(':')[1];
  const expectedViewport = device === 'sp' ? { width: 390, height: 844, deviceScaleFactor: 3 }
    : { width: 1440, height: 900, deviceScaleFactor: 1 };
  if (!record.viewport || record.viewport.width !== expectedViewport.width
    || record.viewport.height !== expectedViewport.height
    || record.deviceScaleFactor !== expectedViewport.deviceScaleFactor
    || record.reducedMotion !== 'no-preference') {
    fail(errors, `${path}.deviceSettings`, 'INP interaction viewport, device scale, or motion preference differs from the assigned device');
  }
  if (kind === 'negative') {
    if (record.mode !== '400ms-browser-only-negative' || record.injectedDelayMs !== 400) {
      fail(errors, path, 'negative control must be the isolated 400ms browser stall');
    }
    if (isFiniteNumber(record.valueMs) && record.valueMs <= PERFORMANCE_GATE.limits.inpMs) {
      fail(errors, path, 'negative control did not exceed the INP threshold');
    }
  } else {
    if (record.mode !== 'positive') fail(errors, `${path}.mode`, 'positive result is mislabeled');
    if (isFiniteNumber(record.valueMs) && record.valueMs > PERFORMANCE_GATE.limits.inpMs) {
      fail(errors, `${path}.valueMs`, `INP exceeds ${PERFORMANCE_GATE.limits.inpMs}ms`);
    }
  }
  return isFiniteNumber(record.valueMs) ? record.valueMs : null;
}

export function validatePerformanceEvidence(evidence) {
  const errors = [];
  if (!evidence || evidence.schema !== PERFORMANCE_GATE.schema) {
    fail(errors, 'schema', `expected ${PERFORMANCE_GATE.schema}`);
  }
  const versions = evidence?.versions ?? {};
  for (const [key, expected] of [
    ['lighthouse', PERFORMANCE_GATE.lighthouseVersion],
    ['chrome', PERFORMANCE_GATE.chromeVersion],
    ['chromeLauncher', PERFORMANCE_GATE.chromeLauncherVersion],
    ['webVitals', PERFORMANCE_GATE.webVitalsVersion],
  ]) {
    if (versions[key] !== expected) fail(errors, `versions.${key}`, `expected pinned version ${expected}`);
  }
  for (const key of ['lighthouseLockIntegrity', 'chromeLauncherLockIntegrity', 'webVitalsLockIntegrity']) {
    if (typeof versions[key] !== 'string' || !versions[key].startsWith('sha512-')) {
      fail(errors, `versions.${key}`, 'package-lock integrity value is missing');
    }
  }

  const sourceDigests = evidence?.sourceDigests;
  if (!sourceDigests || typeof sourceDigests !== 'object' || Array.isArray(sourceDigests)
    || Object.keys(sourceDigests).length === 0) {
    fail(errors, 'sourceDigests', 'repo-relative source/config digests are missing');
  } else {
    for (const [file, digest] of Object.entries(sourceDigests)) {
      if (file.startsWith('/') || file.includes('..') || !sha256(digest)) {
        fail(errors, `sourceDigests.${file}`, 'digest entry must use a repository-relative path and SHA-256');
      }
    }
  }

  const conditions = evidence?.conditions;
  if (!Array.isArray(conditions)) {
    fail(errors, 'conditions', 'condition rows are missing');
    return { accepted: false, aggregation: 'maximum-of-all-three-runs', errors, summary: null };
  }
  const coverage = new Map();
  for (const condition of conditions) {
    const id = `${condition?.pageType}:${condition?.device}`;
    if (coverage.has(id)) fail(errors, `conditions.${id}`, 'duplicate coverage row');
    coverage.set(id, condition);
    if (!expectedCoverage.has(id)) fail(errors, `conditions.${id}`, 'unexpected page/device condition');
    if (typeof condition?.url !== 'string') {
      fail(errors, `conditions.${id}.url`, 'fixture URL is missing');
    } else {
      try {
        const parsed = parseEvidenceUrl(condition.url);
        if (!parsed) throw new Error('invalid URL');
        if (!['http:', 'https:'].includes(parsed.protocol) || parsed.hostname !== 'localhost' && parsed.hostname !== '127.0.0.1') {
          fail(errors, `conditions.${id}.url`, 'fixture URL must use the local CI origin');
        }
      } catch {
        fail(errors, `conditions.${id}.url`, 'fixture URL is invalid');
      }
    }
    const runs = condition?.runs;
    if (!Array.isArray(runs) || runs.length !== PERFORMANCE_GATE.runsPerCondition) {
      fail(errors, `conditions.${id}.runs`, 'exactly three Lighthouse runs are required');
    }
    const seenRuns = new Set();
    const metrics = [];
    for (const run of Array.isArray(runs) ? runs : []) {
      if (seenRuns.has(run?.index)) fail(errors, `conditions.${id}.runs`, 'duplicate Lighthouse run index');
      seenRuns.add(run?.index);
      const measured = validateLighthouseRun({ ...run, expectedUrl: condition?.url }, id, errors);
      if (measured) {
        metrics.push(measured);
        if (measured.lcpMs > PERFORMANCE_GATE.limits.lcpMs) {
          fail(errors, `conditions.${id}.runs[${run.index}].lcpMs`, `LCP exceeds ${PERFORMANCE_GATE.limits.lcpMs}ms`);
        }
        if (measured.cls > PERFORMANCE_GATE.limits.cls) {
          fail(errors, `conditions.${id}.runs[${run.index}].cls`, `CLS exceeds ${PERFORMANCE_GATE.limits.cls}`);
        }
      }
    }
    for (let i = 1; i <= PERFORMANCE_GATE.runsPerCondition; i += 1) {
      if (!seenRuns.has(i)) fail(errors, `conditions.${id}.runs`, `Lighthouse run ${i} is missing`);
    }
    validateInp(condition?.inp?.positive, id, 'positive', errors);
    validateInp(condition?.inp?.negative, id, 'negative', errors);
  }
  for (const id of expectedCoverage) {
    if (!coverage.has(id)) fail(errors, `conditions.${id}`, 'required page/device coverage is missing');
  }

  const probeCompletion = evidence?.probeCompletion;
  for (const probe of ['remaining', 'listing', 'comparison']) {
    if (probeCompletion?.[probe] !== true) fail(errors, `probeCompletion.${probe}`, 'probe must exit successfully and produce a fresh report');
  }

  const home = evidence?.homeRegression;
  if (home?.purpose !== 'separate-regression-only; excluded from the eight required page/device conditions'
    || home?.device !== 'mobile' || home?.screenEmulation?.width !== 412 || home?.screenEmulation?.height !== 823
    || home?.screenEmulation?.deviceScaleFactor !== 1.75
    || home?.aggregation !== 'maximum-of-all-three-runs' || home?.lcpLimitMs !== 2500
    || home?.renderBlockingInsightMaxLength !== 0) {
    fail(errors, 'homeRegression', 'separate HOME regression measurement contract is missing or altered');
  }
  const expectedHomeUrl = parseEvidenceUrl(home?.url);
  if (home?.responseScan?.complete !== true || !Number.isInteger(home.responseScan.status)
    || typeof home.responseScan.cacheControl !== 'string' || home.responseScan.noStore !== false) {
    fail(errors, 'homeRegression.responseScan', 'HOME normal-response cache-control scan is missing or invalid');
  }
  const homeResponseUrl = parseEvidenceUrl(home?.responseScan?.finalUrl);
  if (!expectedHomeUrl || !homeResponseUrl || homeResponseUrl.origin !== expectedHomeUrl.origin
    || homeResponseUrl.pathname !== '/' || homeResponseUrl.search || homeResponseUrl.hash) {
    fail(errors, 'homeRegression.responseScan.finalUrl', 'HOME response scan did not finish on the local root route');
  }
  if (!Array.isArray(home?.runs) || home.runs.length !== PERFORMANCE_GATE.runsPerCondition) {
    fail(errors, 'homeRegression.runs', 'three separate HOME regression reports are required');
  }
  if (!expectedHomeUrl || !['http:', 'https:'].includes(expectedHomeUrl.protocol)
    || !['localhost', '127.0.0.1'].includes(expectedHomeUrl.hostname)
    || expectedHomeUrl.pathname !== '/' || expectedHomeUrl.search || expectedHomeUrl.hash) {
    fail(errors, 'homeRegression.url', 'HOME regression must measure the site root');
  }
  for (const run of Array.isArray(home?.runs) ? home.runs : []) {
    const homePath = `homeRegression.runs[${run?.index}]`;
    const lcp = run?.report?.audits?.['largest-contentful-paint']?.numericValue;
    if (!Number.isInteger(run?.index) || run.index < 1 || run.index > 3) fail(errors, `${homePath}.index`, 'HOME run index must be 1, 2, or 3');
    if (!run?.report || run.report.lighthouseVersion !== PERFORMANCE_GATE.lighthouseVersion) fail(errors, `${homePath}.report`, 'HOME Lighthouse report is missing or version differs');
    if (run?.report?.runtimeError) fail(errors, `${homePath}.report.runtimeError`, 'HOME Lighthouse runtimeError is present');
    const renderBlockingAudit = run?.report?.audits?.['render-blocking-insight'];
    if (!renderBlockingAudit || renderBlockingAudit.id !== 'render-blocking-insight') {
      fail(errors, `${homePath}.report.audits.render-blocking-insight`, 'HOME render-blocking insight is missing or mis-keyed');
    } else {
      if (renderBlockingAudit.scoreDisplayMode === 'error' || renderBlockingAudit.errorMessage) {
        fail(errors, `${homePath}.report.audits.render-blocking-insight`, 'HOME render-blocking insight reported a diagnostic error');
      }
      if (renderBlockingAudit.details?.type !== 'table' || !Array.isArray(renderBlockingAudit.details.items)) {
        fail(errors, `${homePath}.report.audits.render-blocking-insight.details`, 'HOME render-blocking insight must contain a measured table of items');
      } else if (renderBlockingAudit.details.items.length > home.renderBlockingInsightMaxLength) {
        fail(errors, `${homePath}.report.audits.render-blocking-insight.details.items`,
          `HOME render-blocking insight exceeds ${home.renderBlockingInsightMaxLength} items`);
      }
    }
    for (const key of ['requestedUrl', 'finalDisplayedUrl']) {
      const measured = parseEvidenceUrl(run?.report?.[key]);
      if (!expectedHomeUrl || !measured || measured.origin !== expectedHomeUrl.origin || measured.pathname !== '/'
        || measured.search || measured.hash) fail(errors, `${homePath}.report.${key}`, 'HOME report URL does not match the root route');
    }
    const screen = run?.report?.configSettings?.screenEmulation;
    if (run?.report?.configSettings?.formFactor !== 'mobile' || !screen || screen.mobile !== true
      || screen.width !== 412 || screen.height !== 823 || screen.deviceScaleFactor !== 1.75 || screen.disabled !== false) {
      fail(errors, `${homePath}.report.configSettings`, 'HOME report must preserve the existing 412x823 mobile configuration');
    }
    if (typeof run?.sha256 !== 'string' || !sha256(run.sha256)) fail(errors, `${homePath}.sha256`, 'HOME raw-report digest is missing');
    if (!isFiniteNumber(lcp) || lcp < 0) fail(errors, `${homePath}.lcpMs`, 'HOME LCP numericValue is missing or invalid');
    else if (lcp > 2500) fail(errors, `${homePath}.lcpMs`, 'HOME regression LCP exceeds the existing 2500ms limit');
  }
  if (Array.isArray(home?.runs)) {
    const indexes = new Set(home.runs.map(run => run?.index));
    for (let index = 1; index <= 3; index += 1) if (!indexes.has(index)) fail(errors, 'homeRegression.runs', `HOME run ${index} is missing`);
  }

  const fixtures = evidence?.fixtures ?? {};
  if (fixtures.bootstrapLandingPage?.ready !== true
    || fixtures.bootstrapLandingPage?.disposableWithWordPressService !== true) {
    fail(errors, 'fixtures.bootstrapLandingPage', 'LP service fixture is not confirmed ready and disposable');
  }
  for (const key of ['article', 'listing', 'comparison']) {
    const fixture = fixtures[key];
    if (fixture?.ownerVerified !== true) fail(errors, `fixtures.${key}.ownerVerified`, 'temporary fixture ownership/meta verification is missing');
    if (fixture?.cleanupAttempted !== true) fail(errors, `fixtures.${key}.cleanupAttempted`, 'temporary fixture cleanup was not attempted');
    if (fixture?.absenceConfirmed !== true) fail(errors, `fixtures.${key}.absenceConfirmed`, 'temporary fixture absence was not confirmed after cleanup');
  }

  const runtime = evidence?.runtime ?? {};
  const themeSourceScan = runtime.themeSourceScan;
  const expectedThemeDigests = sourceDigests && typeof sourceDigests === 'object'
    ? Object.fromEntries(Object.entries(sourceDigests)
      .filter(([file]) => file.startsWith(`${PERFORMANCE_GATE.themeDir}/`))
      .map(([file, digest]) => [file.slice(`${PERFORMANCE_GATE.themeDir}/`.length), digest]))
    : {};
  if (themeSourceScan?.complete !== true || themeSourceScan?.matchesWorktree !== true
    || !Number.isInteger(themeSourceScan?.expectedFileCount)
    || themeSourceScan.expectedFileCount !== Object.keys(expectedThemeDigests).length
    || themeSourceScan.expectedFileCount < 1
    || themeSourceScan?.mountedFileCount !== themeSourceScan.expectedFileCount
    || !themeSourceScan?.sourceDigests || typeof themeSourceScan.sourceDigests !== 'object'
    || Array.isArray(themeSourceScan.sourceDigests)
    || !isDeepStrictEqual(themeSourceScan.sourceDigests, expectedThemeDigests)) {
    fail(errors, 'runtime.themeSourceScan', 'complete mounted theme inputs do not exactly match repository source digests');
  }
  if (!runtime.probeScans || typeof runtime.probeScans !== 'object' || Array.isArray(runtime.probeScans)) {
    fail(errors, 'runtime.probeScans', 'per-probe bfcache scans are missing');
  } else {
    for (const probe of ['remaining', 'listing', 'comparison']) {
      const scan = runtime.probeScans[probe];
      if (scan?.bfcacheScanComplete !== true || !Number.isInteger(scan.unloadListenerCount)
        || !Array.isArray(scan.noStoreResponsePaths) || !Number.isInteger(scan.measurementCount)
        || scan.measurementCount < 1) {
        fail(errors, `runtime.probeScans.${probe}`, 'probe bfcache measurement is incomplete');
      }
    }
  }
  if (typeof runtime.appOwnedConnectionPresent !== 'boolean') {
    fail(errors, 'runtime.appOwnedConnectionPresent', 'app-owned connection scan result is missing');
  }
  if (runtime.unloadListenerCount !== 0) fail(errors, 'runtime.unloadListenerCount', 'unload listener count must be zero');
  if (!Array.isArray(runtime.noStoreResponsePaths) || runtime.noStoreResponsePaths.length !== 0) {
    fail(errors, 'runtime.noStoreResponsePaths', 'no-store scan is missing or a measured page response used no-store');
  }
  if (runtime.appOwnedConnectionPresent === true && runtime.pagehideClosesConnection !== true) {
    fail(errors, 'runtime.pagehideClosesConnection', 'existing app-owned connection is not closed on pagehide');
  }

  const allMetrics = conditions.flatMap(condition => (Array.isArray(condition?.runs) ? condition.runs : [])
    .map(run => run?.report?.audits ?? {})
    .map(audits => ({ lcpMs: audits['largest-contentful-paint']?.numericValue,
      cls: audits['cumulative-layout-shift']?.numericValue })));
  const maxFinite = values => values.length ? Math.max(...values) : null;
  const minFinite = values => values.length ? Math.min(...values) : null;
  const summary = allMetrics.length ? {
    lcpMs: maxFinite(allMetrics.map(row => row.lcpMs).filter(isFiniteNumber)),
    cls: maxFinite(allMetrics.map(row => row.cls).filter(isFiniteNumber)),
    inpMs: maxFinite(conditions.map(condition => condition?.inp?.positive?.valueMs).filter(isFiniteNumber)),
    negativeInpMs: minFinite(conditions.map(condition => condition?.inp?.negative?.valueMs).filter(isFiniteNumber)),
    lighthouseRuns: allMetrics.length,
    conditions: coverage.size,
  } : null;
  return { accepted: errors.length === 0, aggregation: 'maximum-of-all-three-runs', errors, summary };
}

if (process.argv[1] && import.meta.url === new URL(`file://${process.argv[1]}`).href) {
  const inputPath = process.argv[2];
  const outputPath = process.argv[3];
  if (!inputPath || !outputPath) {
    process.stderr.write('Usage: node scripts/validate-performance-evidence.mjs <evidence.json> <validation.json>\n');
    process.exit(2);
  }
  let evidence;
  try {
    evidence = JSON.parse(fs.readFileSync(inputPath, 'utf8'));
  } catch {
    process.stderr.write('Performance evidence is missing or invalid JSON\n');
    process.exit(2);
  }
  const result = validatePerformanceEvidence(evidence);
  fs.writeFileSync(outputPath, `${JSON.stringify(result, null, 2)}\n`);
  process.stdout.write(`${JSON.stringify({ accepted: result.accepted, errors: result.errors.length, aggregation: result.aggregation })}\n`);
  if (!result.accepted) process.exitCode = 1;
}
