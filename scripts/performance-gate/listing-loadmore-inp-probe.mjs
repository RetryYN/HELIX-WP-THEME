import assert from 'node:assert/strict';
import { spawnSync } from 'node:child_process';
import { createHash, randomBytes } from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { chromium } from '@playwright/test';
import { collectLighthouseRuns } from './lighthouse-collector.mjs';
import { compareMountedSourceHashes } from './theme-input-manifest.mjs';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const evidenceDir = path.resolve(process.env.PERF_GATE_REPORT_DIR ?? path.join(root, '.performance-gate'));
const reportPath = path.join(evidenceDir, 'listing-loadmore-inp-probe.json');
const phpPath = path.join(root, 'scripts/performance-gate/listing-loadmore-fixture.php');
const themeDir = 'docs/research/2026-09-05-design-prototype-03/theme/helix-wt';
const baseUrl = process.env.PERF_GATE_BASE_URL;
const container = process.env.PERF_GATE_WP_CONTAINER;
const browserPath = process.env.PERF_GATE_CHROME_PATH;
const webVitalsRoot = path.join(root, 'node_modules/web-vitals');
const webVitalsPath = path.join(webVitalsRoot, 'dist/web-vitals.iife.js');
const webVitalsPackage = JSON.parse(fs.readFileSync(path.join(webVitalsRoot, 'package.json'), 'utf8'));
const webVitalsLibrary = fs.readFileSync(webVitalsPath, 'utf8');
const sourceFiles = ['templates/category.html', 'assets/js/category.js', 'functions.php', 'assets/img/case-factory.jpg'];
const sameDateFixture = true;
const sha256 = value => createHash('sha256').update(value).digest('hex');
const owner = randomBytes(24).toString('hex');
const token = randomBytes(8).toString('hex');
const termSlug = `local-loadmore-inp-${token}`;
let neededPosts = 0;
let postSlugs = [];

const report = {
  schema: 'helix-performance-listing-loadmore-inp-probe.v1',
  completed: false,
  collectionFinished: false,
  processExitCode: null,
  scope: 'Temporary owner-tagged posts in a unique category; load-more interaction measured with web-vitals onINP.',
  startedAt: new Date().toISOString(),
  environment: { baseUrl, container, termSlug },
  versions: { node: process.version, webVitals: webVitalsPackage.version },
  runtime: null,
  sourceDigests: {},
  fixture: { createAttempted: false, created: false, postIds: [], cleanupAttempted: false, cleanup: null },
  lighthouseRuns: {},
  bfcache: { unloadListenerCount: 0, noStoreResponsePaths: [], measurementCount: 0 },
  checks: [],
  interactions: [],
  errors: [],
};
const save = () => fs.writeFileSync(reportPath, JSON.stringify(report, null, 2) + '\n');
const check = (name, pass, details = undefined) => report.checks.push({ name, pass: !!pass, ...(details === undefined ? {} : { details }) });

function php(action) {
  const env = {
    LOADMORE_PROBE_ACTION: action,
    LOADMORE_PROBE_OWNER: owner,
    LOADMORE_PROBE_TERM_SLUG: termSlug,
    LOADMORE_PROBE_POST_SLUGS: JSON.stringify(postSlugs),
    LOADMORE_PROBE_SOURCE_FILES: JSON.stringify(sourceFiles),
    LOADMORE_PROBE_SAME_DATE: sameDateFixture ? '1' : '0',
  };
  const args = ['exec', '-i', ...Object.entries(env).flatMap(([key, value]) => ['-e', `${key}=${value}`]), container, 'php'];
  const result = spawnSync('docker', args, { cwd: root, input: fs.readFileSync(phpPath), encoding: 'utf8', maxBuffer: 1024 * 1024 });
  if (result.status !== 0) throw new Error(`fixture PHP ${action} command failed (exit ${result.status ?? 'unknown'})`);
  try { return JSON.parse(result.stdout.trim().split('\n').at(-1)); }
  catch { throw new Error(`fixture PHP ${action} returned invalid JSON`); }
}


function docker(args) {
  const result = spawnSync('docker', args, { cwd: root, encoding: 'utf8', maxBuffer: 1024 * 1024 });
  if (result.status !== 0) throw new Error(`Docker command failed (exit ${result.status ?? 'unknown'})`);
  return result.stdout.trim();
}

async function runCase(browser, width, negative, archiveUrl, expectedPageSize, expectedIds) {
  const mode = negative ? '400ms-browser-only-negative' : 'load-more-click';
  const row = { width, mode, injectedDelayMs: negative ? 400 : 0 };
  const viewport = width === 390 ? { width: 390, height: 844 } : { width: 1440, height: 900 };
  const deviceScaleFactor = width === 390 ? 3 : 1;
  const context = await browser.newContext({ viewport, deviceScaleFactor, reducedMotion: 'no-preference' });
  const page = await context.newPage();
  const metrics = [];
  let recorded = false;
  try {
    await page.exposeFunction('__captureLoadmoreProbeINP', metric => metrics.push(metric));
    await page.addInitScript({ content: `${webVitalsLibrary}
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
      ;webVitals.onINP(metric=>window.__captureLoadmoreProbeINP({
        name:metric.name,value:metric.value,rating:metric.rating,
        entries:(metric.entries||[]).map(entry=>({name:entry.name,duration:entry.duration,interactionId:entry.interactionId}))
      }),{reportAllChanges:true,durationThreshold:16});` });

    const pageResponse = await page.goto(archiveUrl, { waitUntil: 'networkidle' });
    const deviceSettings = await page.evaluate(() => ({
      viewport: { width: innerWidth, height: innerHeight },
      deviceScaleFactor: devicePixelRatio,
      reducedMotion: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'reduce' : 'no-preference',
    }));
    row.viewport = deviceSettings.viewport;
    row.deviceScaleFactor = deviceSettings.deviceScaleFactor;
    row.reducedMotion = deviceSettings.reducedMotion;
    assert.deepEqual(deviceSettings, { viewport, deviceScaleFactor, reducedMotion: 'no-preference' },
      `${width}px interaction uses the declared viewport, device scale, and normal motion preference`);
    const cacheControl = pageResponse?.headers()['cache-control'] ?? '';
    if (/\bno-store\b/iu.test(cacheControl)) report.bfcache.noStoreResponsePaths.push(new URL(archiveUrl).pathname);
    row.pageStatus = pageResponse?.status() ?? null;
    assert.equal(row.pageStatus, 200, `${width}px fixture category page HTTP ${row.pageStatus}`);
    const initial = await page.evaluate(() => {
      const list = document.querySelector('.wt-cat-list');
      const button = document.querySelector('[data-wt-load-more]');
      const next = document.querySelector('.wt-cat-pagination a.wp-block-query-pagination-next');
      return {
        ids: [...document.querySelectorAll('.wt-cat-list > li')].map(item => {
          const className = [...item.classList].find(value => /^post-\d+$/u.test(value));
          const match = className ? /^post-(\d+)$/u.exec(className) : null;
          return match ? Number(match[1]) : null;
        }),
        cardCount: list?.querySelectorAll(':scope > li').length ?? 0,
        buttonVisible: !!button && getComputedStyle(button).display !== 'none' && !button.hidden,
        buttonDisabled: button?.disabled ?? null,
        nextHref: next?.href ?? null,
      };
    });
    row.initialCount = initial.cardCount;
    row.initialPostIds = initial.ids;
    row.nextPageUrl = initial.nextHref;
    row.initialButtonVisible = initial.buttonVisible;
    row.initialNextPagePresent = !!initial.nextHref;
    assert.equal(initial.cardCount, expectedPageSize, `${width}px first page has configured posts_per_page rows`);
    assert.equal(initial.buttonVisible, true, `${width}px existing load-more button is visible`);
    assert.equal(initial.buttonDisabled, false, `${width}px load-more button is enabled`);
    assert.ok(initial.nextHref, `${width}px next-page link exists before load`);
    assert.equal(new Set(initial.ids).size, initial.ids.length, `${width}px first page IDs are unique`);

    const nextUrl = new URL(initial.nextHref);
    assert.equal(nextUrl.origin, new URL(baseUrl).origin, 'next-page request stays on the local WP origin');
    const fetchedPage = page.waitForResponse(response => response.url() === nextUrl.href
      && response.request().resourceType() === 'fetch', { timeout: 20000 });
    const button = page.locator('[data-wt-load-more]');
    if (negative) await button.evaluate(element => element.addEventListener('click', () => {
      const end = performance.now() + 400;
      while (performance.now() < end) { /* deliberate browser-only negative */ }
    }, { capture: true, once: true }));
    const clickAt = Date.now();
    await button.click();
    const fetchResponse = await fetchedPage;
    row.nextPageGetStatus = fetchResponse.status();
    row.fetchDurationMs = Date.now() - clickAt;
    const fetchedHtml = await fetchResponse.text();
    row.fetchedPagePostIds = await page.evaluate(html => {
      const doc = new DOMParser().parseFromString(html, 'text/html');
      return [...doc.querySelectorAll('.wt-cat-list > li')].map(item => {
        const className = [...item.classList].find(value => /^post-\d+$/u.test(value));
        const match = className ? /^post-(\d+)$/u.exec(className) : null;
        return match ? Number(match[1]) : null;
      });
    }, fetchedHtml);
    assert.equal(row.nextPageGetStatus, 200, `${width}px next-page GET returns HTTP 200`);

    await page.waitForFunction(expected => document.querySelectorAll('.wt-cat-list > li').length === expected,
      expectedIds.length, { timeout: 20000 });
    await page.waitForFunction(() => {
      const buttonElement = document.querySelector('[data-wt-load-more]');
      return !!buttonElement && getComputedStyle(buttonElement).display === 'none'
        && buttonElement.getAttribute('aria-hidden') === 'true';
    }, null, { timeout: 10000 });

    // Snapshot live DOM before about:blank flushes Web Vitals' page-view metric.
    const snapshot = await page.evaluate(() => {
      const cards = [...document.querySelectorAll('.wt-cat-list > li')];
      const ids = cards.map(item => {
        const className = [...item.classList].find(value => /^post-\d+$/u.test(value));
        const match = className ? /^post-(\d+)$/u.exec(className) : null;
        return match ? Number(match[1]) : null;
      });
      const hrefs = cards.map(item => item.querySelector('.wp-block-post-title a[href]')?.href ?? null);
      const button = document.querySelector('[data-wt-load-more]');
      return {
        ids,
        hrefs,
        totalCount: cards.length,
        buttonDisplay: button ? getComputedStyle(button).display : null,
        buttonAriaHidden: button?.getAttribute('aria-hidden') ?? null,
        buttonHidden: button?.hidden ?? null,
        eventTimingEntries: performance.getEntriesByType('event').filter(entry => entry.interactionId)
          .slice(-8).map(entry => ({ name: entry.name, duration: entry.duration, interactionId: entry.interactionId })),
        unloadListenerCount: window.__helixPerfUnloadListenerCount,
      };
    });
    report.bfcache.unloadListenerCount = Math.max(report.bfcache.unloadListenerCount, snapshot.unloadListenerCount);
    report.bfcache.measurementCount += 1;
    const expectedIdSet = [...expectedIds].sort((a, b) => a - b);
    const actualIdSet = [...snapshot.ids].sort((a, b) => a - b);
    const uniqueIds = new Set(snapshot.ids).size === snapshot.ids.length;
    const uniqueHrefs = new Set(snapshot.hrefs).size === snapshot.hrefs.length;
    const expectedSetMatches = JSON.stringify(actualIdSet) === JSON.stringify(expectedIdSet);
    const buttonGone = snapshot.buttonDisplay === 'none' && snapshot.buttonAriaHidden === 'true';
    row.afterCount = snapshot.totalCount;
    row.afterPostIds = snapshot.ids;
    row.pagesDoNotOverlap = !snapshot.ids.slice(0, initial.ids.length).some(id => snapshot.ids.slice(initial.ids.length).includes(id));
    row.fetchedPageIdsMatchAppendedIds = JSON.stringify(row.fetchedPagePostIds)
      === JSON.stringify(snapshot.ids.slice(initial.ids.length));
    row.afterUniquePostIds = uniqueIds;
    row.afterUniqueArticleHrefs = uniqueHrefs;
    row.afterExpectedPostSetMatches = expectedSetMatches;
    row.expectedTotalCount = expectedIds.length;
    row.buttonGoneAfterLastPage = buttonGone;
    row.eventTimingEntries = snapshot.eventTimingEntries;
    row.interaction = 'click existing category load-more; separately verify next-page fetch, appended IDs, no duplicates, total count, and final button disappearance';
    row.functionalityPass = row.nextPageGetStatus === 200 && row.afterCount === expectedIds.length
      && uniqueIds && uniqueHrefs && expectedSetMatches && buttonGone
      && row.pagesDoNotOverlap && row.fetchedPageIdsMatchAppendedIds;

    // Fetch has completed; wait for a paint settle independently from the fetch timing recorded above.
    await page.waitForTimeout(300);
    await page.goto('about:blank', { waitUntil: 'domcontentloaded' });
    for (let i = 0; i < 100 && !metrics.length; i += 1) await new Promise(resolve => setTimeout(resolve, 100));
    const inp = metrics.at(-1) ?? null;
    row.inpMs = inp?.value ?? null;
    row.inpUnder200ms = Number.isFinite(row.inpMs) && row.inpMs <= 200;
    row.positivePerformancePass = !negative && row.inpUnder200ms;
    row.inpRating = inp?.rating ?? null;
    row.webVitalsEntries = inp?.entries ?? [];
    report.interactions.push(row);
    recorded = true;
    save();
    assert.ok(Number.isFinite(row.inpMs), `${width}px ${mode} INP is null/non-numeric (got ${row.inpMs})`);
    if (negative) assert.ok(row.inpMs > 200, `${width}px negative INP ${row.inpMs}ms must exceed 200ms`);
    else assert.ok(row.inpUnder200ms, `${width}px positive INP ${row.inpMs}ms must be <=200ms`);
    assert.equal(row.functionalityPass, true, `${width}px listing load-more feature assertions failed`);
    check(`${width}px:${mode}:functionality`, row.functionalityPass, {
      nextPageGetStatus: row.nextPageGetStatus, initialCount: row.initialCount, afterCount: row.afterCount,
      expectedTotalCount: row.expectedTotalCount, uniqueIds, expectedSetMatches, pagesDoNotOverlap: row.pagesDoNotOverlap,
      fetchedPageIdsMatchAppendedIds: row.fetchedPageIdsMatchAppendedIds, buttonGone,
    });
    check(`${width}px:${mode}:inp-measured`, Number.isFinite(row.inpMs) && (negative ? row.inpMs > 200 : row.inpUnder200ms), {
      inpMs: row.inpMs, thresholdMs: 200, positivePerformancePass: row.positivePerformancePass,
    });
    save();
  } catch (error) {
    if (recorded) report.interactions.at(-1).error = error instanceof Error ? error.message : String(error);
    else report.interactions.push({ ...row, error: error instanceof Error ? error.message : String(error) });
    report.errors.push(`${width}px:${mode}: ${error instanceof Error ? error.message : String(error)}`);
    save();
  } finally {
    await context.close();
  }
}

async function verifySortUi(browser, archiveUrl) {
  const url = new URL(archiveUrl);
  url.searchParams.set('wt', 'cat_filter:sort,cat_pagination:load-more');
  url.searchParams.set('orderby', 'title');
  url.searchParams.set('order', 'asc');
  const context = await browser.newContext({ viewport: { width: 1440, height: 900 }, reducedMotion: 'reduce' });
  try {
    const page = await context.newPage();
    const response = await page.goto(url.href, { waitUntil: 'networkidle' });
    const queryVars = await page.evaluate(() => {
      const orderby = document.querySelector('.wt-cat-sort select[name="orderby"]');
      const order = document.querySelector('.wt-cat-sort input[name="order"]');
      return { orderby: orderby?.value ?? null, order: order?.value ?? null,
        orderbyType: typeof orderby?.value, orderType: typeof order?.value };
    });
    const pass = response?.status() === 200 && queryVars.orderby === 'title' && queryVars.order === 'asc'
      && queryVars.orderbyType === 'string' && queryVars.orderType === 'string';
    report.sortUi = { url: url.href, status: response?.status() ?? null, queryVars };
    check('category-sort-query-vars-remain-strings', pass, report.sortUi);
    assert.equal(pass, true, 'category sort UI preserves title/asc query vars as strings');
    save();
  } finally { await context.close(); }
}

save();
let browser;
let createAttempted = false;
let runtimeHashesMatch = false;
let fixtureActionSuccessful = false;
try {
  assert.equal(webVitalsPackage.version, '6.2.2');
  assert.ok(fs.existsSync(browserPath), 'pinned Chrome 156 is present');
  report.versions.chromeExpected = '156.0.8075.0';
  report.sourceDigests = {
    'scripts/performance-gate/listing-loadmore-inp-probe.mjs': sha256(fs.readFileSync(fileURLToPath(import.meta.url))),
    'scripts/performance-gate/listing-loadmore-fixture.php': sha256(fs.readFileSync(phpPath)),
    ...Object.fromEntries(sourceFiles.map(file => [`${themeDir}/${file}`, sha256(fs.readFileSync(path.join(root, themeDir, file)))])),
  };
  const initialRuntime = php('inspect');
  report.runtime = { wordpressVersion: initialRuntime.wordpressVersion, phpVersion: initialRuntime.phpVersion,
    theme: initialRuntime.theme, themeDirectory: initialRuntime.themeDirectory,
    postsPerPage: initialRuntime.postsPerPage, sourceHashes: initialRuntime.sourceHashes };
  assert.equal(initialRuntime.theme, 'helix-wt');
  assert.equal(initialRuntime.themeDirectory, '/var/www/html/wp-content/themes/helix-wt');
  const sourceHashComparison = compareMountedSourceHashes(initialRuntime.sourceHashes, report.sourceDigests, themeDir, sourceFiles);
  runtimeHashesMatch = sourceHashComparison.matches;
  report.runtime.sourceHashComparison = sourceHashComparison;
  check('mounted-theme-source-hashes-match-local-inputs', runtimeHashesMatch, sourceHashComparison);
  if (!runtimeHashesMatch) throw new Error(`mounted theme source hash comparison failed: ${sourceHashComparison.errors.join('; ')}`);
  assert.equal(initialRuntime.existing.termExists, false, 'generated unique term slug is absent before creation');
  assert.deepEqual(initialRuntime.existing.posts, [], 'generated unique post slugs are absent before creation');

  const postsPerPage = Number(initialRuntime.postsPerPage);
  assert.ok(Number.isInteger(postsPerPage) && postsPerPage >= 1 && postsPerPage <= 24,
    `posts_per_page ${postsPerPage} must be a finite bounded positive value <=24`);
  const pageSize = Math.max(6, postsPerPage);
  neededPosts = pageSize + 1;
  assert.ok(neededPosts <= 25, `bounded fixture count ${neededPosts} must be <=25`);
  postSlugs = Array.from({ length: neededPosts }, (_, index) => `local-loadmore-${token}-${String(index + 1).padStart(2, '0')}`);
  report.fixture.configuredPostsPerPage = postsPerPage;
  report.fixture.expectedPageSize = pageSize;
  report.fixture.expectedTotalPosts = neededPosts;
  report.fixture.postSlugs = postSlugs;
  report.fixture.sameDateMode = sameDateFixture;
  save();

  createAttempted = true;
  report.fixture.createAttempted = true;
  const created = php('create');
  report.fixture.created = created.postIds?.length > 0;
  report.fixture.featuredImageId = created.featuredImageId ?? null;
  report.fixture.featuredImagePostCount = created.featuredImagePostCount ?? 0;
  report.fixture.featuredImageOwnerVerified = created.featuredImageOwnerVerified === true;
  report.fixture.termId = created.termId ?? null;
  report.fixture.postIds = created.postIds ?? [];
  report.fixture.termUrl = created.termUrl ?? null;
  report.fixture.postDates = created.postDates ?? [];
  report.fixture.queryComparisons = { dateOnlyPages: created.dateOnlyPages ?? [], dateThenIdPages: created.dateThenIdPages ?? [] };
  report.fixture.expectedTotalPosts = created.expectedPosts ?? null;
  fixtureActionSuccessful = created.ok === true && created.postIds?.length === neededPosts
    && Number.isInteger(created.featuredImageId) && created.featuredImagePostCount === neededPosts
    && created.featuredImageOwnerVerified === true;
  if (!fixtureActionSuccessful) throw new Error(`owned fixture create incomplete (${(created.errors ?? []).join('; ')})`);
  const archive = new URL(created.termUrl);
  assert.equal(archive.origin, baseUrl, 'fixture category URL stays on the allowed local origin');
  archive.searchParams.set('wt', 'cat_pagination:load-more');
  report.fixture.archiveUrl = archive.href;
  check('bounded-owner-tagged-fixture-created', true, { postsPerPage, expectedPageSize: pageSize,
    createdPosts: created.postIds.length, featuredImageId: created.featuredImageId,
    featuredImagePostCount: created.featuredImagePostCount });
  save();
  for (const device of ['sp', 'pc']) {
    const coverageId = `listing:${device}`;
    report.lighthouseRuns[coverageId] = await collectLighthouseRuns({
      url: archive.href, coverageId, device, reportDir: evidenceDir,
    });
  }

  browser = await chromium.launch({ executablePath: browserPath, headless: true, args: ['--no-sandbox'] });
  report.versions.chrome = browser.version();
  assert.match(report.versions.chrome, /156\.0\.8075\.0/u);
  await verifySortUi(browser, archive.href);
  const createdIds = created.postIds.map(post => post.id);
  for (const width of [390, 1440]) {
    for (const negative of [false, true]) await runCase(browser, width, negative, archive.href, pageSize, createdIds);
  }
} catch (error) {
  report.errors.push(error instanceof Error ? error.stack ?? error.message : String(error));
} finally {
  if (browser) await browser.close().catch(error => report.errors.push(`browser close failed: ${error.message}`));
  if (createAttempted) {
    report.fixture.cleanupAttempted = true;
    try {
      const cleanup = php('delete');
      report.fixture.cleanup = cleanup;
      const finalState = php('inspect');
      const absent = !finalState.existing.termExists && finalState.existing.posts.length === 0
        && cleanup.imagesAbsent === true;
      report.fixture.absenceConfirmed = absent;
      check('owned-fixture-cleanup-and-absence-confirmed', cleanup.termAbsent && cleanup.fixturePostsAbsent
        && cleanup.imagesAbsent && absent,
        { termDeleted: cleanup.termDeleted, termAbsent: cleanup.termAbsent, postsAbsent: cleanup.fixturePostsAbsent,
          imagesAbsent: cleanup.imagesAbsent, notOwned: cleanup.notOwned });
      assert.equal(cleanup.notOwned.length, 0, 'no foreign posts or term matched cleanup identity');
      assert.equal(absent, true, 'owned fixture term and all exact-slug posts are absent');
    } catch (error) {
      report.fixture.cleanupError = error instanceof Error ? error.message : String(error);
      report.errors.push(`fixture cleanup failed: ${report.fixture.cleanupError}`);
    }
  }

}

const rowsValid = report.interactions.length === 4 && report.interactions.every(row =>
  Number.isFinite(row.inpMs) && row.functionalityPass === true
  && (row.mode === '400ms-browser-only-negative' ? row.inpMs > 200 : row.inpUnder200ms === true));
const cleanupPass = report.fixture.absenceConfirmed === true;
report.collectionFinished = !!report.runtime && report.fixture.createAttempted && (cleanupPass || !!report.fixture.cleanupError);
report.completed = !report.errors.length && runtimeHashesMatch && fixtureActionSuccessful
  && rowsValid && cleanupPass;
report.coverageStatus = report.completed ? 'complete' : 'partial';
report.finishedAt = new Date().toISOString();
report.processExitCode = report.completed ? 0 : 1;
save();
console.log(JSON.stringify({ completed: report.completed, collectionFinished: report.collectionFinished,
  processExitCode: report.processExitCode, wordpressVersion: report.runtime?.wordpressVersion,
  postsPerPage: report.runtime?.postsPerPage, fixturePosts: report.fixture.postIds.length,
  fixtureAbsent: report.fixture.absenceConfirmed === true,
  interactions: report.interactions.map(({ width, mode, nextPageGetStatus, initialCount, afterCount, expectedTotalCount, buttonGoneAfterLastPage, inpMs }) =>
    ({ width, mode, nextPageGetStatus, initialCount, afterCount, expectedTotalCount, buttonGoneAfterLastPage, inpMs })),
  errors: report.errors.map(error => error.split('\n')[0]),
}));
if (!report.completed) process.exitCode = 1;
