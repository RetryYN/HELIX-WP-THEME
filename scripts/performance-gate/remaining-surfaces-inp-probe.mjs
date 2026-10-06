import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { chromium } from '@playwright/test';
import { collectLighthouseRuns } from './lighthouse-collector.mjs';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const evidenceDir = path.resolve(process.env.PERF_GATE_REPORT_DIR ?? path.join(root, '.performance-gate'));
const reportPath = path.join(evidenceDir, 'remaining-surfaces-inp-probe.json');
const articleFixturePath = path.join(root, 'scripts/performance-gate/article-fixture.php');
const themeDir = 'docs/research/2026-09-05-design-prototype-03/theme/helix-wt';
const baseArticle = process.env.PERF_GATE_BASE_URL;
const baseContent = process.env.PERF_GATE_BASE_URL;
let articleUrl = process.env.PERF_GATE_ARTICLE_URL;
const landingPageUrl = process.env.PERF_GATE_LP_URL;
const containers = {
  article: process.env.PERF_GATE_WP_CONTAINER,
  content: process.env.PERF_GATE_WP_CONTAINER,
};
const browserPath = process.env.PERF_GATE_CHROME_PATH;
const webVitalsRoot = path.join(root, 'node_modules/web-vitals');
const webVitalsPath = path.join(webVitalsRoot, 'dist/web-vitals.iife.js');
const webVitalsPackage = JSON.parse(fs.readFileSync(path.join(webVitalsRoot, 'package.json'), 'utf8'));
const webVitalsLibrary = fs.readFileSync(webVitalsPath, 'utf8');
const sha256 = value => createHash('sha256').update(value).digest('hex');
const sourceFiles = [
  'templates/single.html', 'assets/js/article.js', 'assets/img/case-tax.jpg',
  'templates/category.html', 'assets/js/category.js',
  'templates/page-lp.html', 'inc/content-faces.php',
];
const report = {
  schema: 'helix-performance-remaining-surfaces-inp-probe.v1',
  completed: false,
  processExitCode: null,
  collectionFinished: false,
  coverageStatus: 'not-started',
  missingSurfaces: [],
  scope: 'Real article-copy and LP-anchor interactions measured with web-vitals onINP.',
  startedAt: new Date().toISOString(),
  versions: { node: process.version, webVitals: webVitalsPackage.version },
  environments: {},
  sourceDigests: {},
  surfaces: {
    article: { url: articleUrl, rows: [] },
    landingPage: { url: landingPageUrl, rows: [] },
  },
  fixture: { createAttempted: false, ownerVerified: false, postId: null, featuredImageId: null,
    cleanupAttempted: false, absenceConfirmed: false },
  lighthouseRuns: {},
  bfcache: { unloadListenerCount: 0, noStoreResponsePaths: [], measurementCount: 0 },
  checks: [],
  errors: [],
};
const save = () => fs.writeFileSync(reportPath, JSON.stringify(report, null, 2) + '\n');
const check = (name, pass, details = undefined) => report.checks.push({ name, pass: !!pass, ...(details === undefined ? {} : { details }) });

function runDocker(container, args, input = undefined) {
  const result = spawnSync('docker', ['exec', ...(input === undefined ? [] : ['-i']), container, ...args], {
    cwd: root, input, encoding: 'utf8', maxBuffer: 1024 * 1024,
  });
  if (result.status !== 0) throw new Error(`runtime inspection failed for ${container} (exit ${result.status ?? 'unknown'})`);
  return result.stdout.trim();
}

function articleFixture(action) {
  const env = {
    ARTICLE_PROBE_ACTION: action,
    ARTICLE_PROBE_SLUG: process.env.PERF_GATE_ARTICLE_FIXTURE_SLUG,
    ARTICLE_PROBE_OWNER: process.env.PERF_GATE_ARTICLE_FIXTURE_OWNER,
  };
  const args = ['exec', '-i', ...Object.entries(env).flatMap(([key, value]) => ['-e', `${key}=${value}`]),
    process.env.PERF_GATE_WP_CONTAINER, 'php'];
  const result = spawnSync('docker', args, { cwd: root, input: fs.readFileSync(articleFixturePath),
    encoding: 'utf8', maxBuffer: 1024 * 1024 });
  if (result.status !== 0) throw new Error(`article fixture ${action} failed (exit ${result.status ?? 'unknown'})`);
  try { return JSON.parse(result.stdout.trim().split('\n').at(-1)); }
  catch { throw new Error(`article fixture ${action} returned invalid JSON`); }
}

function runtime(container) {
  const mountResult = spawnSync('docker', ['inspect', '-f', '{{range .Mounts}}{{if eq .Destination "/var/www/html/wp-content/themes/helix-wt"}}{{.Source}}{{end}}{{end}}', container], { encoding: 'utf8' });
  if (mountResult.status !== 0) throw new Error(`theme mount inspection failed for ${container}`);
  const hashesExpr = sourceFiles.map(file => `${JSON.stringify(file)}=>hash_file("sha256",$root.${JSON.stringify(file)})`).join(',');
  const code = `require "/var/www/html/wp-load.php"; $root=rtrim(get_template_directory(),"/")."/"; $files=[${hashesExpr}]; echo json_encode(["wordpressVersion"=>get_bloginfo("version"),"phpVersion"=>PHP_VERSION,"theme"=>get_stylesheet(),"name"=>wp_get_theme()->get("Name"),"files"=>$files]);`;
  const runtimeOutput = runDocker(container, ['php', '-r', code]);
  return { mount: mountResult.stdout.trim(), ...JSON.parse(runtimeOutput) };
}

function makeInit(page, metricSink) {
  return page.exposeFunction('__captureRemainingProbeINP', metric => metricSink.push(metric)).then(() =>
    page.addInitScript({ content: `${webVitalsLibrary}
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
      ;webVitals.onINP(metric=>window.__captureRemainingProbeINP({
        name:metric.name,value:metric.value,rating:metric.rating,
        entries:(metric.entries||[]).map(entry=>({name:entry.name,duration:entry.duration,interactionId:entry.interactionId}))
      }),{reportAllChanges:true,durationThreshold:16});` }));
}

async function finalizeINP(page, sink) {
  // Let click effects and the next paint settle before visibilitychange flushes the metric.
  await page.waitForTimeout(300);
  await page.goto('about:blank', { waitUntil: 'domcontentloaded' });
  for (let i = 0; i < 100 && !sink.length; i += 1) await new Promise(resolve => setTimeout(resolve, 100));
  return sink.at(-1) ?? null;
}

async function doInteraction(browser, { surface, width, negative, url, kind }) {
  const viewport = width === 390 ? { width: 390, height: 844 } : { width: 1440, height: 900 };
  const deviceScaleFactor = width === 390 ? 3 : 1;
  const context = await browser.newContext({
    viewport,
    deviceScaleFactor,
    reducedMotion: 'no-preference',
    permissions: kind === 'copy' ? ['clipboard-read', 'clipboard-write'] : [],
  });
  const page = await context.newPage();
  const metricSink = [];
  let row;
  try {
    await makeInit(page, metricSink);
    const response = await page.goto(url, { waitUntil: 'networkidle' });
    assert.equal(response?.status(), 200, `${surface} ${width}px response`);
    const deviceSettings = await page.evaluate(() => ({
      viewport: { width: innerWidth, height: innerHeight },
      deviceScaleFactor: devicePixelRatio,
      reducedMotion: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'reduce' : 'no-preference',
    }));
    assert.deepEqual(deviceSettings, { viewport, deviceScaleFactor, reducedMotion: 'no-preference' },
      `${surface} ${width}px interaction uses the declared viewport, device scale, and normal motion preference`);
    const cacheControl = response?.headers()['cache-control'] ?? '';
    if (/\bno-store\b/iu.test(cacheControl)) report.bfcache.noStoreResponsePaths.push(new URL(url).pathname);
    if (kind === 'copy') {
      const button = page.locator('.wt-share--top [data-wt-share="copy"]');
      assert.equal(await button.count(), 1, 'one existing top article link-copy button');
      await button.evaluate(el => el.addEventListener('click', () => {
        const span = el.querySelector('span');
        if (span) window.__copyBefore = span.textContent;
      }, { capture: true, once: true }));
      if (negative) await button.evaluate(el => el.addEventListener('click', () => {
        const end = performance.now() + 400;
        while (performance.now() < end) { /* browser-only negative */ }
      }, { capture: true, once: true }));
      await button.click();
      await page.waitForFunction(() => document.querySelector('.wt-share--top [data-wt-share="copy"] span')?.textContent === 'コピーしました');
      const clipText = await page.evaluate(() => navigator.clipboard.readText());
      const snapshot = await page.evaluate(() => ({
        title: document.title,
        buttonText: document.querySelector('.wt-share--top [data-wt-share="copy"] span')?.textContent ?? null,
        locationHref: location.href,
        beforeText: window.__copyBefore ?? null,
        eventTimingEntries: performance.getEntriesByType('event').filter(entry => entry.interactionId)
          .slice(-8).map(entry => ({ name: entry.name, duration: entry.duration, interactionId: entry.interactionId })),
        unloadListenerCount: window.__helixPerfUnloadListenerCount,
      }));
      report.bfcache.unloadListenerCount = Math.max(report.bfcache.unloadListenerCount, snapshot.unloadListenerCount);
      report.bfcache.measurementCount += 1;
      snapshot.clipboardText = clipText;
      snapshot.copySucceeded = clipText === snapshot.locationHref && snapshot.buttonText === 'コピーしました';
      assert.equal(snapshot.copySucceeded, true, `${width}px article clipboard write and status succeeded`);
      const metric = await finalizeINP(page, metricSink);
      const inpMs = metric?.value ?? null;
      const inpUnder200ms = Number.isFinite(inpMs) && inpMs <= 200;
      assert.ok(Number.isFinite(inpMs), `${width}px article INP is numeric (got ${inpMs})`);
      if (negative) assert.ok(inpMs > 200, `${width}px negative INP ${inpMs}ms must exceed 200ms`);
      else assert.ok(inpUnder200ms, `${width}px positive INP ${inpMs}ms must be <=200ms`);
      row = { width, mode: negative ? '400ms-browser-only-negative' : 'article-link-copy', status: response.status(),
        viewport: deviceSettings.viewport, deviceScaleFactor: deviceSettings.deviceScaleFactor,
        reducedMotion: deviceSettings.reducedMotion,
        interaction: 'click existing link-copy button; verify clipboard URL and transient status text',
        clipboardMatchesCurrentUrl: snapshot.copySucceeded, buttonText: snapshot.buttonText,
        inpMs, inpUnder200ms, positivePerformancePass: !negative && inpUnder200ms,
        inpRating: metric?.rating ?? null, webVitalsEntries: metric?.entries ?? [],
        eventTimingEntries: snapshot.eventTimingEntries, injectedDelayMs: negative ? 400 : 0 };
    } else if (kind === 'contact') {
      const action = page.locator('a[href="#contact"]:visible').first();
      assert.ok(await action.count() > 0, 'an existing visible LP action targets #contact');
      assert.equal(await page.locator('#contact').count(), 1, 'real LP target #contact exists');
      if (negative) await action.evaluate(el => el.addEventListener('click', () => {
        const end = performance.now() + 400;
        while (performance.now() < end) { /* browser-only negative */ }
      }, { capture: true, once: true }));
      await action.click();
      await page.waitForFunction(() => location.hash === '#contact' && !!document.querySelector('#contact'));
      await page.waitForFunction(() => {
        const rect = document.querySelector('#contact')?.getBoundingClientRect();
        return !!rect && rect.top >= -2 && rect.top < innerHeight * 0.8;
      });
      const snapshot = await page.evaluate(() => {
        const target = document.querySelector('#contact');
        const rect = target.getBoundingClientRect();
        return {
          title: document.title,
          hash: location.hash,
          scrollY: scrollY,
          targetTop: rect.top,
          targetVisible: rect.bottom > 0 && rect.top < innerHeight,
          formPresent: !!target.querySelector('form'),
          eventTimingEntries: performance.getEntriesByType('event').filter(entry => entry.interactionId)
            .slice(-8).map(entry => ({ name: entry.name, duration: entry.duration, interactionId: entry.interactionId })),
          unloadListenerCount: window.__helixPerfUnloadListenerCount,
        };
      });
      report.bfcache.unloadListenerCount = Math.max(report.bfcache.unloadListenerCount, snapshot.unloadListenerCount);
      report.bfcache.measurementCount += 1;
      assert.equal(snapshot.hash, '#contact');
      assert.equal(snapshot.targetVisible, true, 'the action scrolled to the real contact target');
      const metric = await finalizeINP(page, metricSink);
      const inpMs = metric?.value ?? null;
      const inpUnder200ms = Number.isFinite(inpMs) && inpMs <= 200;
      assert.ok(Number.isFinite(inpMs), `${width}px LP INP is numeric (got ${inpMs})`);
      if (negative) assert.ok(inpMs > 200, `${width}px negative INP ${inpMs}ms must exceed 200ms`);
      else assert.ok(inpUnder200ms, `${width}px positive INP ${inpMs}ms must be <=200ms`);
      row = { width, mode: negative ? '400ms-browser-only-negative' : 'lp-contact-anchor', status: response.status(),
        viewport: deviceSettings.viewport, deviceScaleFactor: deviceSettings.deviceScaleFactor,
        reducedMotion: deviceSettings.reducedMotion,
        interaction: 'click existing visible #contact anchor; verify real target is visible after hash navigation; do not submit',
        hash: snapshot.hash, targetTop: snapshot.targetTop, targetVisible: snapshot.targetVisible,
        formPresent: snapshot.formPresent, submitted: false,
        inpMs, inpUnder200ms, positivePerformancePass: !negative && inpUnder200ms,
        inpRating: metric?.rating ?? null, webVitalsEntries: metric?.entries ?? [],
        eventTimingEntries: snapshot.eventTimingEntries, injectedDelayMs: negative ? 400 : 0 };
    }
    report.surfaces[surface].rows.push(row);
    check(`${surface}:${width}px:${row.mode}`, true, { status: row.status, inpMs: row.inpMs,
      positivePerformancePass: row.positivePerformancePass ?? undefined });
    save();
  } finally {
    await context.close();
  }
  return row;
}

let browser;
save();
try {
  assert.equal(webVitalsPackage.version, '6.2.2');
  assert.ok(fs.existsSync(browserPath), 'pinned Chrome 156 exists');
  report.versions.browserExpected = '156.0.8075.0';
  report.sourceDigests = {
    'scripts/performance-gate/remaining-surfaces-inp-probe.mjs': sha256(fs.readFileSync(fileURLToPath(import.meta.url))),
    'scripts/performance-gate/article-fixture.php': sha256(fs.readFileSync(articleFixturePath)),
    ...Object.fromEntries(sourceFiles.map(file => [`${themeDir}/${file}`, sha256(fs.readFileSync(path.join(root, themeDir, file)))])),
  };
  for (const [key, container] of Object.entries(containers)) {
    const info = runtime(container);
    assert.equal(info.theme, 'helix-wt');
    for (const file of sourceFiles) assert.equal(info.files[file], report.sourceDigests[`${themeDir}/${file}`], `${container} mounted source hash: ${file}`);
    report.environments[key] = { baseUrl: key === 'article' ? baseArticle : baseContent, container,
      wordpressVersion: info.wordpressVersion, phpVersion: info.phpVersion, theme: info.theme, themeName: info.name,
      runtimeSourceDigests: info.files };
    check(`${key}:runtime-source-hashes-match-worktree`, true, { container, runtimeVersion: info.wordpressVersion });
  }
  const priorArticle = articleFixture('inspect');
  assert.equal(priorArticle.slugExists, false, 'random owned performance article slug is initially absent');
  report.fixture.createAttempted = true;
  const createdArticle = articleFixture('create');
  report.fixture.postId = createdArticle.postId;
  report.fixture.featuredImageId = createdArticle.featuredImageId;
  report.fixture.ownerVerified = createdArticle.ownerVerified === true;
  articleUrl = new URL(createdArticle.permalink).href;
  assert.equal(new URL(articleUrl).origin, new URL(baseArticle).origin);
  assert.equal(report.fixture.ownerVerified, true);
  assert.ok(Number.isInteger(report.fixture.postId) && report.fixture.postId > 0);
  assert.ok(Number.isInteger(report.fixture.featuredImageId) && report.fixture.featuredImageId > 0);
  assert.ok(createdArticle.contentBlockCount >= 8, 'representative article has real heading and body blocks');
  report.surfaces.article.url = articleUrl;
  check('owned-performance-article-created', true, { postId: report.fixture.postId,
    featuredImageId: report.fixture.featuredImageId, contentBlockCount: createdArticle.contentBlockCount });
  save();
  assert.equal(new URL(articleUrl).origin, new URL(baseArticle).origin);
  assert.equal(new URL(landingPageUrl).origin, new URL(baseContent).origin);
  for (const [surface, url] of [['article', articleUrl], ['landing-page', landingPageUrl]]) {
    for (const device of ['sp', 'pc']) {
      const coverageId = `${surface}:${device}`;
      report.lighthouseRuns[coverageId] = await collectLighthouseRuns({ url, coverageId, device, reportDir: evidenceDir });
    }
  }
  browser = await chromium.launch({ executablePath: browserPath, headless: true, args: ['--no-sandbox'] });
  report.versions.browser = browser.version();
  assert.match(report.versions.browser, /156\.0\.8075\.0/u);
  for (const [surface, url, kind] of [
    ['article', report.surfaces.article.url, 'copy'],
    ['landingPage', report.surfaces.landingPage.url, 'contact'],
  ]) {
    for (const width of [390, 1440]) {
      for (const negative of [false, true]) {
        try {
          await doInteraction(browser, { surface, width, negative, url, kind });
        } catch (error) {
          const item = { width, mode: negative ? '400ms-browser-only-negative' : kind,
            error: error instanceof Error ? error.message : String(error) };
          report.surfaces[surface].rows.push(item);
          report.errors.push(`${surface}:${width}px:${item.mode}: ${item.error}`);
          save();
        }
      }
    }
  }
} catch (error) {
  report.errors.push(error instanceof Error ? error.stack ?? error.message : String(error));
} finally {
  if (browser) await browser.close().catch(error => report.errors.push(`browser close failed: ${error.message}`));
  if (report.fixture.createAttempted) {
    report.fixture.cleanupAttempted = true;
    try {
      const cleanup = articleFixture('delete');
      report.fixture.cleanup = cleanup;
      const finalState = articleFixture('inspect');
      report.fixture.absenceConfirmed = cleanup.slugAbsent === true && cleanup.imagesAbsent === true
        && finalState.slugExists === false && finalState.ownedImages.length === 0;
      check('owned-performance-article-cleanup-and-absence-confirmed', report.fixture.absenceConfirmed,
        { slugAbsent: cleanup.slugAbsent, imagesAbsent: cleanup.imagesAbsent, postId: report.fixture.postId });
      if (!report.fixture.absenceConfirmed) report.errors.push('owned performance article or image remained after cleanup');
    } catch (error) {
      report.fixture.cleanupError = error instanceof Error ? error.message : String(error);
      report.errors.push(`article fixture cleanup failed: ${report.fixture.cleanupError}`);
    }
  }
}

const measured = ['article', 'landingPage'].every(key => report.surfaces[key].rows.length === 4
  && report.surfaces[key].rows.every(row => Number.isFinite(row.inpMs))
  && report.surfaces[key].rows.filter(row => row.mode !== '400ms-browser-only-negative').every(row => row.inpUnder200ms)
  && report.surfaces[key].rows.filter(row => row.mode === '400ms-browser-only-negative').every(row => row.inpMs > 200));
report.collectionFinished = report.surfaces.article.rows.length === 4 && report.surfaces.landingPage.rows.length === 4;
report.coverageStatus = report.collectionFinished ? 'complete' : 'partial';
report.completed = !report.errors.length && measured && report.collectionFinished
  && report.fixture.ownerVerified && report.fixture.cleanupAttempted && report.fixture.absenceConfirmed
  && report.checks.filter(row => row.name.endsWith(':runtime-source-hashes-match-worktree')).every(row => row.pass);
report.finishedAt = new Date().toISOString();
report.processExitCode = report.completed ? 0 : 1;
save();
console.log(JSON.stringify({ completed: report.completed, processExitCode: report.processExitCode,
  collectionFinished: report.collectionFinished, coverageStatus: report.coverageStatus,
  browser: report.versions.browser,
  article: report.surfaces.article.rows.map(({ width, mode, inpMs }) => ({ width, mode, inpMs })),
  landingPage: report.surfaces.landingPage.rows.map(({ width, mode, inpMs }) => ({ width, mode, inpMs })),
  errors: report.errors.map(error => error.split('\n')[0]),
}));
if (!report.completed) process.exitCode = 1;
