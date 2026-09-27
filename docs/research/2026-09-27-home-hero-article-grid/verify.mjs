import assert from 'node:assert/strict';
import { createHash, randomBytes } from 'node:crypto';
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { chromium } from '@playwright/test';
import { contentLab } from '../../../scripts/lib/content-lab-env.mjs';

const root = fileURLToPath(new URL('../../../', import.meta.url));
const here = path.dirname(fileURLToPath(import.meta.url));
const fixturePath = path.join(here, 'fixture.php');
const publicSummaryPath = path.join(here, 'verification-summary.json');
const attemptTag = new Date().toISOString().replace(/[:.]/gu, '-');
const attemptDir = path.resolve(process.env.ISSUE_377_ATTEMPT_DIR
  ?? path.join(root, 'local-evidence/issue-377/attempts', `attempt-${attemptTag}`));
const rawPath = path.join(attemptDir, 'raw.json');
const themeDir = 'docs/research/2026-09-05-design-prototype-03/theme/helix-wt';
const themeFiles = [
  'theme.json', 'style.css', 'functions.php', 'inc/home-hero-images.php',
  'patterns/home-hero.php', 'patterns/home-sections.php', 'templates/front-page.html',
  'assets/css/theme.css', 'assets/css/home-completion.css', 'assets/img/case-factory.jpg',
];
const sourcePaths = themeFiles.map(file => `${themeDir}/${file}`);
const mountedSourceDigests = localDigests => Object.fromEntries(themeFiles.map(file => [
  file, localDigests[`${themeDir}/${file}`],
]));
const sourceDigests = files => Object.fromEntries(files.map(file => [
  file, createHash('sha256').update(fs.readFileSync(path.join(root, file))).digest('hex'),
]));
const sha256 = value => createHash('sha256').update(value).digest('hex');
const owner = randomBytes(24).toString('hex');
const slug = `issue-377-image-${randomBytes(8).toString('hex')}`;
const sourceFilesJson = JSON.stringify(themeFiles);
const fixtureDigest = sha256(fs.readFileSync(fixturePath));
const probeDigest = sha256(fs.readFileSync(fileURLToPath(import.meta.url)));
const packageLock = JSON.parse(fs.readFileSync(path.join(root, 'package-lock.json'), 'utf8'));
const report = {
  schema: 'helix-home-hero-article-grid-image-probe.v2',
  completed: false,
  scope: 'Issue #377 local regression check: hidden article-grid featured-image requests and selected-image display/decode; no theme-option mutation or performance acceptance.',
  startedAt: new Date().toISOString(),
  runtime: {},
  versions: {
    node: process.version,
    playwright: packageLock.packages['node_modules/playwright']?.version ?? null,
    playwrightTest: packageLock.packages['node_modules/@playwright/test']?.version ?? null,
  },
  probeDigests: {},
  sourceDigests: {},
  optionSnapshot: { before: null, after: null, unchanged: false },
  fixture: { createAttempted: false, created: false, cleanupAttempted: false, cleanup: null, absenceConfirmed: false },
  runs: [],
  errors: [],
};
fs.mkdirSync(attemptDir, { recursive: true });
const saveRaw = () => fs.writeFileSync(rawPath, `${JSON.stringify(report, null, 2)}\n`);
saveRaw();

function runWordPress(action) {
  const result = spawnSync('docker', [
    'exec', '-i', '-e', `ISSUE_377_ACTION=${action}`, '-e', `ISSUE_377_OWNER=${owner}`,
    '-e', `ISSUE_377_SLUG=${slug}`, '-e', `ISSUE_377_SOURCE_FILES=${sourceFilesJson}`,
    contentLab.wpContainer, 'php',
  ], { cwd: root, input: fs.readFileSync(fixturePath), encoding: 'utf8', maxBuffer: 2 * 1024 * 1024 });
  if (result.error || result.status !== 0) {
    throw new Error(`WordPress ${action} operation failed (exit ${result.status ?? 'unavailable'})`);
  }
  try { return JSON.parse(result.stdout.trim().split('\n').at(-1)); }
  catch { throw new Error(`WordPress ${action} operation returned invalid JSON`); }
}

async function inspectRun(browser, variant, width) {
  const height = width === 390 ? 844 : 900;
  const dpr = width === 390 ? 3 : 1;
  const context = await browser.newContext({ viewport: { width, height }, deviceScaleFactor: dpr });
  const page = await context.newPage();
  const requests = [];
  const responses = [];
  const baseUrl = new URL(contentLab.baseUrl);
  baseUrl.searchParams.set('wt', `home_hero:${variant}`);
  page.on('request', request => {
    if (request.resourceType() === 'image') requests.push(request.url());
  });
  page.on('response', response => {
    if (response.request().resourceType() === 'image') responses.push({ url: response.url(), status: response.status() });
  });
  try {
    const response = await page.goto(baseUrl.href, { waitUntil: 'networkidle', timeout: 30000 });
    assert.equal(response?.status(), 200, `${variant} ${width}px page response`);
    const state = await page.evaluate(permalink => {
      const grid = document.querySelector('.wt-home-hero--article-grid');
      const image = [...(grid?.querySelectorAll('.wp-block-post-featured-image img') ?? [])]
        .find(element => element.closest('a')?.href === permalink);
      if (!grid || !image) return { gridFound: !!grid, imageFound: false };
      const candidates = [image.currentSrc, image.src, ...(image.getAttribute('srcset') ?? '').split(',')
        .map(candidate => candidate.trim().split(/\s+/u)[0])]
        .filter(Boolean).map(value => new URL(value, location.href).href);
      return {
        gridFound: true,
        imageFound: true,
        display: getComputedStyle(grid).display,
        loading: image.getAttribute('loading'),
        fetchpriority: image.getAttribute('fetchpriority'),
        decoding: image.getAttribute('decoding'),
        candidates: [...new Set(candidates)],
        preloadLinks: [...document.querySelectorAll('link[rel="preload"][as="image"]')]
          .map(link => link.href),
      };
    }, report.fixture.permalink);
    assert.equal(state.gridFound, true, `${variant} ${width}px article-grid markup present`);
    assert.equal(state.imageFound, true, `${variant} ${width}px owner image present`);
    assert.equal(state.display === 'none', variant === 'text-only', `${variant} grid selection state`);
    const candidates = state.candidates;
    assert.ok(candidates.length > 0, 'featured-image URL candidates available');
    let selectedImage = null;
    if (variant === 'text-only') {
      assert.equal(state.loading, 'lazy', `${width}px hidden image is lazy`);
      assert.equal(state.fetchpriority, null, `${width}px hidden image has no fetchpriority`);
      assert.equal(state.preloadLinks.some(url => candidates.includes(url)), false, 'hidden image has no preload link');
      await page.waitForTimeout(1200);
    } else {
      const image = page.locator(`.wt-home-hero--article-grid .wp-block-post-featured-image a[href="${report.fixture.permalink}"] img`).first();
      await image.scrollIntoViewIfNeeded();
      selectedImage = await image.evaluate(async element => {
        await element.decode();
        const rect = element.getBoundingClientRect();
        return {
          decoded: element.complete && element.naturalWidth > 0,
          naturalWidth: element.naturalWidth,
          visibleInViewport: rect.width > 0 && rect.height > 0 && rect.bottom > 0
            && rect.top < innerHeight && rect.right > 0 && rect.left < innerWidth,
        };
      });
      assert.equal(selectedImage.decoded, true, `${width}px selected image decodes`);
      assert.equal(selectedImage.visibleInViewport, true, `${width}px selected image is visible`);
    }
    const candidateRequests = requests.filter(url => candidates.includes(url));
    if (variant === 'text-only') {
      assert.equal(candidateRequests.length, 0, `${width}px hidden image produced no request`);
    } else {
      assert.ok(candidateRequests.length > 0, `${width}px selected image request observed`);
      assert.ok(responses.some(row => candidates.includes(row.url) && row.status === 200),
        `${width}px selected image response succeeded`);
    }
    const row = {
      variant,
      viewport: { width, height },
      deviceScaleFactor: dpr,
      status: response.status(),
      gridDisplay: state.display,
      imageLoading: state.loading,
      imageFetchpriority: state.fetchpriority,
      candidateRequestCount: candidateRequests.length,
      selectedImage,
      passed: variant === 'text-only'
        ? candidateRequests.length === 0 && state.display === 'none'
        : candidateRequests.length > 0 && selectedImage?.decoded === true && selectedImage.visibleInViewport === true,
    };
    report.runs.push(row);
    saveRaw();
  } finally {
    await context.close();
  }
}

let browser;
let createAttempted = false;
let fixtureCreateSucceeded = false;
try {
  assert.ok(['127.0.0.1', 'localhost'].includes(new URL(contentLab.baseUrl).hostname), 'local loopback WordPress only');
  report.probeDigests = {
    script: probeDigest,
    fixture: fixtureDigest,
  };
  report.sourceDigests = sourceDigests(sourcePaths);
  const initial = runWordPress('inspect');
  report.runtime = {
    sourceCommit: spawnSync('git', ['rev-parse', 'HEAD'], { cwd: root, encoding: 'utf8' }).stdout.trim(),
    wordpressVersion: initial.wordpressVersion,
    phpVersion: initial.phpVersion,
    theme: initial.theme,
    themeVersion: initial.themeVersion,
    templatePresent: initial.templatePresent,
  };
  report.optionSnapshot.before = initial.homeHeroOption;
  assert.equal(initial.theme, 'helix-wt');
  assert.equal(new URL(initial.homeUrl).origin, new URL(contentLab.baseUrl).origin);
  assert.equal(initial.templatePresent, true, 'active theme has a front-page template');
  assert.equal(initial.slugAbsent, true, 'random fixture slug absent before creation');
  assert.deepEqual(initial.ownedImages, [], 'random fixture owner has no attachments before creation');
  assert.deepEqual(initial.sourceHashes, mountedSourceDigests(report.sourceDigests),
    'mounted theme source hashes match worktree');
  assert.equal(report.versions.playwright, '1.61.0');
  assert.equal(report.versions.playwrightTest, '1.61.0');
  const executable = chromium.executablePath();
  assert.ok(fs.existsSync(executable), 'Playwright Chromium executable exists');
  const version = spawnSync(executable, ['--version'], { encoding: 'utf8' });
  assert.equal(version.status, 0, 'Chromium version command succeeds');
  report.versions.chromium = version.stdout.trim();
  saveRaw();

  createAttempted = true;
  report.fixture.createAttempted = true;
  const created = runWordPress('create');
  fixtureCreateSucceeded = created.created === true;
  report.fixture.created = fixtureCreateSucceeded;
  report.fixture.inHomeQuery = created.inHomeQuery === true;
  report.fixture.ownerVerified = created.ownerVerified === true;
  report.fixture.permalink = created.permalink ?? null;
  assert.equal(fixtureCreateSucceeded, true, 'owner-tagged featured-image fixture created');
  assert.equal(report.fixture.ownerVerified, true, 'fixture ownership tags verified');
  assert.equal(report.fixture.inHomeQuery, true, 'fixture appears in the HOME article-grid query');
  saveRaw();

  browser = await chromium.launch({ headless: true });
  report.versions.chromium = browser.version();
  for (const width of [390, 1440]) {
    await inspectRun(browser, 'text-only', width);
    await inspectRun(browser, 'article-grid', width);
  }
} catch (error) {
  report.errors.push(error instanceof Error ? error.stack ?? error.message : String(error));
} finally {
  if (browser) await browser.close().catch(error => report.errors.push('browser close failed'));
  if (createAttempted) {
    report.fixture.cleanupAttempted = true;
    try {
      report.fixture.cleanup = runWordPress('delete');
      report.fixture.absenceConfirmed = report.fixture.cleanup.postAbsent === true
        && report.fixture.cleanup.ownedImagesAbsent === true && report.fixture.cleanup.notOwned.length === 0;
      assert.equal(report.fixture.absenceConfirmed, true, 'owned fixture cleanup and absence confirmed');
    } catch (error) {
      report.errors.push(error instanceof Error ? `fixture cleanup failed: ${error.message}` : 'fixture cleanup failed');
    }
  }
  try {
    const final = runWordPress('inspect');
    report.optionSnapshot.after = final.homeHeroOption;
    report.optionSnapshot.unchanged = report.optionSnapshot.before === report.optionSnapshot.after;
    report.fixture.finalSlugAbsent = final.slugAbsent === true;
    report.fixture.finalOwnedImagesAbsent = final.ownedImages.length === 0;
    report.sourceDigestsAfter = sourceDigests(sourcePaths);
    report.mountedSourceDigestsAfter = final.sourceHashes;
    assert.equal(report.optionSnapshot.unchanged, true, 'theme option unchanged');
    assert.equal(report.fixture.finalSlugAbsent, true, 'fixture post absent after cleanup');
    assert.equal(report.fixture.finalOwnedImagesAbsent, true, 'owned attachments absent after cleanup');
    assert.deepEqual(report.sourceDigestsAfter, report.sourceDigests, 'worktree theme files unchanged');
    assert.deepEqual(report.mountedSourceDigestsAfter, mountedSourceDigests(report.sourceDigests),
      'mounted theme files unchanged and still match');
    assert.equal(sha256(fs.readFileSync(fixturePath)), fixtureDigest, 'fixture file unchanged');
    assert.equal(sha256(fs.readFileSync(fileURLToPath(import.meta.url))), probeDigest, 'probe file unchanged');
  } catch (error) {
    report.errors.push(error instanceof Error ? `final read-only inspection failed: ${error.message}` : 'final read-only inspection failed');
  }
  report.completed = report.errors.length === 0 && fixtureCreateSucceeded && report.runs.length === 4
    && report.runs.every(row => row.passed) && report.fixture.absenceConfirmed === true
    && report.optionSnapshot.unchanged === true;
  report.finishedAt = new Date().toISOString();
  saveRaw();
}

const publicSummary = {
  schema: 'helix-home-hero-article-grid-image-summary.v1',
  completed: report.completed,
  scope: 'Issue #377 local HOME image-loading regression check only; not a performance acceptance result.',
  sourceCommit: report.runtime.sourceCommit ?? null,
  versions: {
    wordpress: report.runtime.wordpressVersion ?? null,
    php: report.runtime.phpVersion ?? null,
    playwright: report.versions.playwright,
    chromium: report.versions.chromium ?? null,
  },
  sourceDigests: {
    ...report.sourceDigests,
    [`${path.relative(root, fileURLToPath(import.meta.url))}`]: probeDigest,
    [`${path.relative(root, fixturePath)}`]: fixtureDigest,
  },
  conditions: report.runs.map(row => ({
    variant: row.variant,
    viewport: row.viewport,
    deviceScaleFactor: row.deviceScaleFactor,
    passed: row.passed,
    candidateRequestCount: row.candidateRequestCount,
    decoded: row.selectedImage?.decoded ?? null,
    visibleInViewport: row.selectedImage?.visibleInViewport ?? null,
  })),
  checks: {
    fixtureHadFeaturedImageAndWasInHomeQuery: report.fixture.ownerVerified === true && report.fixture.inHomeQuery === true,
    fixtureCleanupAndAbsenceConfirmed: report.fixture.absenceConfirmed === true
      && report.fixture.finalSlugAbsent === true && report.fixture.finalOwnedImagesAbsent === true,
    themeOptionUnchanged: report.optionSnapshot.unchanged === true,
    themeSourcesUnchangedAndMountedHashesMatched: report.errors.length === 0,
  },
};
fs.writeFileSync(publicSummaryPath, `${JSON.stringify(publicSummary, null, 2)}\n`);
console.log(JSON.stringify({ completed: report.completed, conditions: report.runs.length, errors: report.errors.length,
  fixtureCleanup: publicSummary.checks.fixtureCleanupAndAbsenceConfirmed,
  optionUnchanged: publicSummary.checks.themeOptionUnchanged }));
if (!report.completed) process.exitCode = 1;
