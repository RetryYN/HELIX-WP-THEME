#!/usr/bin/env node

import { createHash, randomBytes } from 'node:crypto';
import { execFileSync, spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { isDeepStrictEqual } from 'node:util';
import { fileURLToPath } from 'node:url';
import { PERFORMANCE_GATE, validatePerformanceEvidence } from './validate-performance-evidence.mjs';
import { runProbeProcess } from './performance-gate/probe-execution.mjs';
import { collectLighthouseRuns } from './performance-gate/lighthouse-collector.mjs';
import { collectThemeInputDigests } from './performance-gate/theme-input-manifest.mjs';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const configPath = path.join(root, '.lighthouserc.json');
const config = JSON.parse(fs.readFileSync(configPath, 'utf8'));
const packageJson = JSON.parse(fs.readFileSync(path.join(root, 'package.json'), 'utf8'));
const packageLock = JSON.parse(fs.readFileSync(path.join(root, 'package-lock.json'), 'utf8'));
const themeDir = 'docs/research/2026-09-05-design-prototype-03/theme/helix-wt';
const reportDir = path.resolve(process.env.PERF_GATE_REPORT_DIR ?? path.join(process.env.RUNNER_TEMP ?? root, 'performance-gate'));
const baseUrl = new URL(process.env.PERF_GATE_BASE_URL ?? process.env.A11Y_BASE_URL ?? 'http://localhost:8086');
const container = process.env.PERF_GATE_WP_CONTAINER;
const chromePath = process.env.PERF_GATE_CHROME_PATH;
const probePaths = {
  remaining: 'scripts/performance-gate/remaining-surfaces-inp-probe.mjs',
  listing: 'scripts/performance-gate/listing-loadmore-inp-probe.mjs',
  comparison: 'scripts/performance-gate/compare-pattern-inp-probe.mjs',
};
const sha256 = content => createHash('sha256').update(content).digest('hex');
const evidencePath = path.join(reportDir, 'performance-evidence.json');
const validationPath = path.join(reportDir, 'performance-validation.json');
const gatePath = path.join(reportDir, 'performance-gate-result.json');
const failures = [];

function recordFailure(message) {
  failures.push(message);
  process.stderr.write(`${message}\n`);
}

function readJson(file) {
  try { return JSON.parse(fs.readFileSync(file, 'utf8')); } catch { return null; }
}

function shaFile(relativePath) {
  return sha256(fs.readFileSync(path.join(root, relativePath)));
}

function lockIntegrity(name) {
  const entry = packageLock.packages?.[`node_modules/${name}`];
  return typeof entry?.integrity === 'string' ? entry.integrity : null;
}

function collectThemeSources(directory) {
  const output = [];
  for (const entry of fs.readdirSync(directory, { withFileTypes: true })) {
    const absolutePath = path.join(directory, entry.name);
    if (entry.isDirectory()) output.push(...collectThemeSources(absolutePath));
    else if (/\.(?:js|mjs|cjs)$/u.test(entry.name)) output.push(absolutePath);
  }
  return output;
}

function readRuntimeSourceConnections() {
  const sources = collectThemeSources(path.join(root, themeDir));
  const contents = sources.map(file => fs.readFileSync(file, 'utf8'));
  const connectionPattern = /new\s+(?:WebSocket|EventSource)\s*\(/u;
  const connectionPresent = contents.some(content => connectionPattern.test(content));
  const pagehideClose = contents.some(content => /addEventListener\s*\(\s*['"]pagehide['"][\s\S]{0,700}?\.close\s*\(/u.test(content));
  const staticUnloadListenerCount = contents.reduce((count, content) => count
    + [...content.matchAll(/(?:addEventListener\s*\(\s*['"]unload['"]|\.onunload\s*=)/gu)].length, 0);
  return {
    connectionPresent,
    pagehideClose: connectionPresent ? pagehideClose : null,
    staticUnloadListenerCount,
    relativeFiles: sources.map(file => path.relative(root, file).split(path.sep).join('/')),
  };
}

function runProbe(relativeScript, env) {
  const outputPath = path.join(reportDir, path.basename(relativeScript).replace(/\.mjs$/u, '.json'));
  const result = runProbeProcess({
    command: process.execPath,
    args: [path.join(root, relativeScript)],
    cwd: root,
    env,
    reportPath: outputPath,
  });
  if (!result.completed) recordFailure(`${relativeScript} did not complete successfully with a fresh complete report (exit ${result.exitCode ?? 'unknown'})`);
  return result;
}

function loadRawRuns(probeReport, conditionId) {
  const metadata = probeReport?.lighthouseRuns?.[conditionId];
  if (!Array.isArray(metadata)) return [];
  return metadata.map(row => {
    const filePath = path.resolve(reportDir, row.path ?? '');
    if (!filePath.startsWith(`${reportDir}${path.sep}`)) return { index: row.index, report: null };
    const raw = readJson(filePath);
    if (raw && sha256(fs.readFileSync(filePath)) !== row.sha256) recordFailure(`${conditionId} raw Lighthouse digest mismatch`);
    return { index: row.index, report: raw };
  });
}

function interactionRecord(rows, device, negative, success) {
  const width = device === 'sp' ? 390 : 1440;
  const expectedMode = negative ? '400ms-browser-only-negative' : null;
  const row = rows.find(item => item?.width === width
    && (negative ? item.mode === expectedMode : item.mode !== expectedMode));
  if (!row) return null;
  return {
    source: 'web-vitals.onINP',
    mode: negative ? '400ms-browser-only-negative' : 'positive',
    valueMs: row.inpMs,
    viewport: row.viewport,
    deviceScaleFactor: row.deviceScaleFactor,
    reducedMotion: row.reducedMotion,
    injectedDelayMs: negative ? row.injectedDelayMs : 0,
    actionCompleted: success(row),
  };
}

function fixtureReport(probe, checkName) {
  const checks = probe?.checks;
  const ownerVerification = Array.isArray(checks) && checks.some(check => check.name === checkName && check.pass === true);
  const cleanup = probe?.fixture?.absenceConfirmed === true;
  return { ownerVerified: ownerVerification, cleanupAttempted: probe?.fixture?.cleanupAttempted === true,
    absenceConfirmed: cleanup };
}

function sourceDigests() {
  const paths = new Set([
    '.lighthouserc.json', '.node-version', 'package.json', 'package-lock.json',
    '.github/workflows/theme-quality-gate.yml',
    'scripts/run-performance-gate.mjs', 'scripts/validate-performance-evidence.mjs',
    'scripts/performance-gate/lighthouse-collector.mjs', 'scripts/performance-gate/probe-execution.mjs',
    'scripts/performance-gate/theme-input-manifest.mjs',
    'scripts/performance-gate/article-fixture.php',
    ...Object.values(probePaths),
    'scripts/performance-gate/listing-loadmore-fixture.php',
    'scripts/performance-gate/compare-pattern-fixture.php',
    'scripts/bootstrap-quality-wordpress.sh',
    ...Object.keys(collectThemeInputDigests(root, themeDir)),
  ]);
  for (const file of readRuntimeSourceConnections().relativeFiles) paths.add(file);
  return Object.fromEntries([...paths].sort().map(file => [file, shaFile(file)]));
}

function inspectMountedThemeSources(expectedThemeDigests) {
  const code = String.raw`require '/var/www/html/wp-load.php';
$root = realpath(get_stylesheet_directory());
$digests = array();
if (!$root || get_stylesheet() !== 'helix-wt') { fwrite(STDERR, 'active theme mismatch'); exit(2); }
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
  if (!$file->isFile() || $file->isLink()) { continue; }
  $relative = str_replace(DIRECTORY_SEPARATOR, '/', substr($file->getPathname(), strlen($root) + 1));
  $digest = hash_file('sha256', $file->getPathname());
  if (!is_string($digest)) { fwrite(STDERR, 'theme source hash failed'); exit(3); }
  $digests[$relative] = $digest;
}
ksort($digests);
echo json_encode(array('theme' => get_stylesheet(), 'sourceDigests' => $digests), JSON_UNESCAPED_SLASHES);`;
  const result = spawnSync('docker', ['exec', container, 'php', '-r', code], {
    cwd: root, encoding: 'utf8', maxBuffer: 4 * 1024 * 1024,
  });
  if (result.status !== 0) throw new Error('mounted WordPress theme input scan failed');
  const runtime = JSON.parse(result.stdout.trim());
  const expected = Object.fromEntries(Object.entries(expectedThemeDigests)
    .map(([file, digest]) => [file.slice(`${themeDir}/`.length), digest]));
  const mounted = runtime?.sourceDigests;
  const complete = runtime?.theme === 'helix-wt' && mounted && typeof mounted === 'object' && !Array.isArray(mounted);
  return {
    complete,
    matchesWorktree: complete && isDeepStrictEqual(mounted, expected),
    expectedFileCount: Object.keys(expected).length,
    mountedFileCount: complete ? Object.keys(mounted).length : 0,
    sourceDigests: complete ? mounted : {},
  };
}

async function main() {
  fs.mkdirSync(reportDir, { recursive: true });
  for (const file of [evidencePath, validationPath, gatePath,
    ...Object.values(probePaths).map(relative => path.join(reportDir, path.basename(relative).replace(/\.mjs$/u, '.json')))]) {
    fs.rmSync(file, { force: true });
  }
  fs.rmSync(path.join(reportDir, 'lighthouse'), { recursive: true, force: true });
  process.env.PERF_GATE_REPORT_DIR = reportDir;
  const articleUrl = new URL(config.conditions.article, baseUrl).href;
  const landingPageUrl = new URL(config.conditions['landing-page'], baseUrl).href;
  const env = {
    ...process.env,
    PERF_GATE_REPORT_DIR: reportDir,
    PERF_GATE_BASE_URL: baseUrl.origin,
    PERF_GATE_WP_CONTAINER: container ?? '',
    PERF_GATE_CHROME_PATH: chromePath ?? '',
    PERF_GATE_ARTICLE_URL: articleUrl,
    PERF_GATE_LP_URL: landingPageUrl,
    PERF_GATE_ARTICLE_FIXTURE_SLUG: `performance-article-${randomBytes(8).toString('hex')}`,
    PERF_GATE_ARTICLE_FIXTURE_OWNER: randomBytes(24).toString('hex'),
  };
  if (!container || !chromePath) recordFailure('PERF_GATE_WP_CONTAINER and PERF_GATE_CHROME_PATH are required');
  const sourceDigestMap = sourceDigests();
  const expectedThemeDigests = Object.fromEntries(Object.entries(sourceDigestMap)
    .filter(([file]) => file.startsWith(`${themeDir}/`)));
  let themeSourceScan = { complete: false, matchesWorktree: false,
    expectedFileCount: Object.keys(expectedThemeDigests).length, mountedFileCount: 0, sourceDigests: {} };
  if (container) {
    try {
      themeSourceScan = inspectMountedThemeSources(expectedThemeDigests);
      if (!themeSourceScan.complete || !themeSourceScan.matchesWorktree) {
        recordFailure('mounted WordPress theme inputs do not exactly match the worktree');
      }
    } catch {
      recordFailure('mounted WordPress theme inputs could not be fully verified');
    }
  }
  const expectedConfig = {
    schema: 'helix-lighthouse-performance-gate.v1',
    versions: { lighthouse: '13.5.0', chrome: '156.0.8075.0', webVitals: '6.2.2' },
    runsPerCondition: 3,
    aggregation: 'maximum-of-all-three-runs',
    thresholds: {
      'largest-contentful-paint': { maxNumericValue: 2500 },
      'interaction-to-next-paint': { maxNumericValue: 200 },
      'cumulative-layout-shift': { maxNumericValue: 0.1 },
    },
    insightIds: ['lcp-discovery-insight', 'lcp-breakdown-insight', 'cls-culprits-insight', 'inp-breakdown-insight'],
    conditions: { article: 'created-by-owned-fixture', 'landing-page': '/a11y-fixture-lp/',
      listing: 'created-by-owned-fixture', comparison: 'created-by-owned-fixture' },
    devices: { sp: { width: 390, height: 844, deviceScaleFactor: 3 },
      pc: { width: 1440, height: 900, deviceScaleFactor: 1 } },
    homeRegression: { url: '/', device: 'mobile', runs: 3,
      screenEmulation: { width: 412, height: 823, deviceScaleFactor: 1.75 }, lcpMaxNumericValue: 2500,
      renderBlockingInsight: { maxLength: 0 } },
  };
  if (Object.keys(expectedConfig).some(key => !isDeepStrictEqual(config[key], expectedConfig[key]))) {
    recordFailure('Lighthouse performance-gate configuration is invalid');
  }

  const versions = {
    lighthouse: packageJson.devDependencies?.lighthouse ?? null,
    chromeLauncher: packageJson.devDependencies?.['chrome-launcher'] ?? null,
    webVitals: packageJson.devDependencies?.['web-vitals'] ?? null,
    chrome: null,
    lighthouseLockIntegrity: lockIntegrity('lighthouse'),
    chromeLauncherLockIntegrity: lockIntegrity('chrome-launcher'),
    webVitalsLockIntegrity: lockIntegrity('web-vitals'),
  };
  if (chromePath) {
    try {
      const versionOutput = execFileSync(chromePath, ['--version'], { encoding: 'utf8' }).trim();
      const match = versionOutput.match(/\b(\d+\.\d+\.\d+\.\d+)\b/u);
      versions.chrome = match?.[1] ?? null;
      if (versions.chrome !== PERFORMANCE_GATE.chromeVersion) recordFailure('Chrome binary is not the pinned 156.0.8075.0 build');
    } catch {
      recordFailure('pinned Chrome binary could not be executed for version verification');
    }
  }

  const remainingRun = runProbe(probePaths.remaining, env);
  const listingRun = runProbe(probePaths.listing, env);
  const comparisonRun = runProbe(probePaths.comparison, env);
  const remaining = remainingRun.report;
  const listing = listingRun.report;
  const comparison = comparisonRun.report;
  const appSources = readRuntimeSourceConnections();
  const routeSources = {
    article: { url: remaining?.surfaces?.article?.url ?? articleUrl, probe: remaining, rows: remaining?.surfaces?.article?.rows ?? [],
      success: row => row.clipboardMatchesCurrentUrl === true },
    'landing-page': { url: remaining?.surfaces?.landingPage?.url ?? landingPageUrl, probe: remaining, rows: remaining?.surfaces?.landingPage?.rows ?? [],
      success: row => row.hash === '#contact' && row.targetVisible === true && row.submitted === false },
    listing: { url: listing?.fixture?.archiveUrl, probe: listing, rows: listing?.interactions ?? [],
      success: row => row.functionalityPass === true },
    comparison: { url: comparison?.fixture?.permalink, probe: comparison, rows: comparison?.interactions ?? [],
      success: row => row.detailsOpenAfterClick === true },
  };

  const conditions = PERFORMANCE_GATE.pageTypes.flatMap(pageType => PERFORMANCE_GATE.devices.map(device => {
    const id = `${pageType}:${device}`;
    const source = routeSources[pageType];
    const inpRuns = source.rows;
    const lighthouseRuns = loadRawRuns(source.probe, id);
    return {
      pageType,
      device,
      url: source.url,
      runs: lighthouseRuns,
      inp: {
        positive: interactionRecord(inpRuns, device, false, source.success),
        negative: interactionRecord(inpRuns, device, true, source.success),
      },
    };
  }));

  const evidence = {
    schema: PERFORMANCE_GATE.schema,
    versions,
    sourceDigests: sourceDigestMap,
    probeCompletion: {
      remaining: remainingRun.completed,
      listing: listingRun.completed,
      comparison: comparisonRun.completed,
    },
    runtime: {
      themeSourceScan,
      probeScans: Object.fromEntries([['remaining', remaining], ['listing', listing], ['comparison', comparison]].map(([key, report]) => [key, {
        bfcacheScanComplete: Number.isInteger(report?.bfcache?.unloadListenerCount)
          && Array.isArray(report?.bfcache?.noStoreResponsePaths)
          && Number.isInteger(report?.bfcache?.measurementCount) && report.bfcache.measurementCount > 0,
        unloadListenerCount: report?.bfcache?.unloadListenerCount,
        noStoreResponsePaths: report?.bfcache?.noStoreResponsePaths,
        measurementCount: report?.bfcache?.measurementCount,
      }])),
      unloadListenerCount: Math.max(appSources.staticUnloadListenerCount,
        ...[remaining, listing, comparison].map(row => Number.isInteger(row?.bfcache?.unloadListenerCount)
          ? row.bfcache.unloadListenerCount : Number.POSITIVE_INFINITY)),
      noStoreResponsePaths: [remaining, listing, comparison].flatMap(row => Array.isArray(row?.bfcache?.noStoreResponsePaths)
        ? row.bfcache.noStoreResponsePaths : ['<missing-bfcache-scan>']),
      appOwnedConnectionPresent: appSources.connectionPresent,
      pagehideClosesConnection: appSources.pagehideClose,
    },
    fixtures: {
      bootstrapLandingPage: { ready: !!remaining?.environments?.article && !!remaining?.environments?.content,
        disposableWithWordPressService: true },
      article: fixtureReport(remaining, 'owned-performance-article-created'),
      listing: fixtureReport(listing, 'bounded-owner-tagged-fixture-created'),
      comparison: fixtureReport(comparison, 'owned-fixture-created-through-registered-pattern'),
    },
    conditions,
  };

  const homeRuns = await collectLighthouseRuns({
    url: new URL(config.homeRegression.url, baseUrl).href,
    coverageId: 'home-regression', device: 'home', reportDir,
  }).catch(error => {
    recordFailure(`HOME regression Lighthouse collection failed: ${error instanceof Error ? error.message : String(error)}`);
    return [];
  });
  const homeUrl = new URL(config.homeRegression.url, baseUrl);
  let homeResponseScan = { complete: false, status: null, cacheControl: null };
  try {
    const response = await fetch(homeUrl, { signal: AbortSignal.timeout(15000) });
    const cacheControl = response.headers.get('cache-control') ?? '';
    homeResponseScan = { complete: response.ok, status: response.status, cacheControl,
      noStore: /\bno-store\b/iu.test(cacheControl), finalUrl: response.url };
    if (!response.ok) recordFailure(`HOME cache-control response scan failed (HTTP ${response.status})`);
    if (homeResponseScan.noStore) evidence.runtime.noStoreResponsePaths.push(homeUrl.pathname);
    await response.arrayBuffer();
  } catch (error) {
    recordFailure(`HOME cache-control response scan failed: ${error instanceof Error ? error.message : String(error)}`);
  }
  evidence.homeRegression = {
    purpose: 'separate-regression-only; excluded from the eight required page/device conditions',
    url: new URL(config.homeRegression.url, baseUrl).href,
    device: config.homeRegression.device,
    screenEmulation: config.homeRegression.screenEmulation,
    aggregation: 'maximum-of-all-three-runs',
    lcpLimitMs: config.homeRegression.lcpMaxNumericValue,
    renderBlockingInsightMaxLength: config.homeRegression.renderBlockingInsight.maxLength,
    responseScan: homeResponseScan,
    runs: homeRuns.map(run => {
      const rawPath = path.resolve(reportDir, run.path ?? '');
      if (!rawPath.startsWith(`${path.join(reportDir, 'lighthouse')}${path.sep}`)) {
        recordFailure('HOME regression raw report path escaped the report directory');
        return { ...run, report: null };
      }
      let report = readJson(rawPath);
      try {
        if (sha256(fs.readFileSync(rawPath)) !== run.sha256) {
          recordFailure('HOME regression raw Lighthouse digest mismatch');
          report = null;
        }
      } catch { report = null; }
      return { ...run, report };
    }),
  };
  fs.writeFileSync(evidencePath, `${JSON.stringify(evidence, null, 2)}\n`);

  const validation = validatePerformanceEvidence(evidence);
  const corruptedPositive = structuredClone(evidence);
  for (const condition of corruptedPositive.conditions) {
    const negative = condition.inp.negative;
    condition.inp.positive = negative ? { ...negative, mode: 'positive', injectedDelayMs: 0 } : null;
  }
  const negativeRefusal = validatePerformanceEvidence(corruptedPositive);
  const negativeControl = {
    measuredControls: evidence.conditions.filter(condition => Number.isFinite(condition.inp?.negative?.valueMs)).length,
    expectedControls: 8,
    valuesKeptSeparateFromPositive: evidence.conditions.every(condition => condition.inp?.positive?.mode === 'positive'
      && condition.inp?.negative?.mode === '400ms-browser-only-negative'),
    validatorRefusedPromotingNegativeAsPositive: negativeRefusal.accepted === false,
  };
  const accepted = failures.length === 0 && validation.accepted
    && Object.values(evidence.probeCompletion).every(Boolean)
    && negativeControl.measuredControls === 8
    && negativeControl.valuesKeptSeparateFromPositive && negativeControl.validatorRefusedPromotingNegativeAsPositive
    && evidence.fixtures.listing.ownerVerified && evidence.fixtures.listing.cleanupAttempted && evidence.fixtures.listing.absenceConfirmed
    && evidence.fixtures.comparison.ownerVerified && evidence.fixtures.comparison.cleanupAttempted && evidence.fixtures.comparison.absenceConfirmed;
  const gateResult = {
    accepted,
    aggregation: validation.aggregation,
    summary: validation.summary,
    errors: [
      ...validation.errors,
      ...failures.map(message => ({ path: 'runner', message })),
    ],
    negativeControl,
    fixtureIsolation: evidence.fixtures,
    homeRegression: {
      runs: evidence.homeRegression.runs.length,
      maxLcpMs: evidence.homeRegression.runs.reduce((maximum, run) => Math.max(maximum,
        Number.isFinite(run?.report?.audits?.['largest-contentful-paint']?.numericValue)
          ? run.report.audits['largest-contentful-paint'].numericValue : Number.POSITIVE_INFINITY), 0),
      lcpLimitMs: evidence.homeRegression.lcpLimitMs,
      includedInEightConditionMatrix: false,
    },
    artifacts: {
      evidence: path.basename(evidencePath),
      validation: path.basename(validationPath),
    },
  };
  fs.writeFileSync(validationPath, `${JSON.stringify(validation, null, 2)}\n`);
  fs.writeFileSync(gatePath, `${JSON.stringify(gateResult, null, 2)}\n`);
  process.stdout.write(`${JSON.stringify({ accepted, aggregation: gateResult.aggregation, conditions: gateResult.summary?.conditions ?? 0,
    lighthouseRuns: gateResult.summary?.lighthouseRuns ?? 0, validationErrors: validation.errors.length })}\n`);
  if (!accepted) process.exitCode = 1;
}

main().catch(error => {
  recordFailure(error instanceof Error ? error.message : String(error));
  try {
    fs.mkdirSync(reportDir, { recursive: true });
    fs.writeFileSync(gatePath, `${JSON.stringify({ accepted: false, errors: failures }, null, 2)}\n`);
  } catch {}
  process.exitCode = 1;
});
