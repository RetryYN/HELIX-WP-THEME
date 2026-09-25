import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import fs from 'node:fs/promises';
import path from 'node:path';
import { chromium } from 'playwright';

const baseUrl = process.env.HELIX_ARTICLE_BASE_URL ?? 'http://127.0.0.1:18112';
const evidenceDir = path.resolve(process.cwd(), process.env.HELIX_ARTICLE_EVIDENCE_DIR ?? 'local-evidence/news-column-purpose-gap');
const resultPath = process.env.HELIX_ARTICLE_RESULT_PATH ? path.resolve(process.cwd(), process.env.HELIX_ARTICLE_RESULT_PATH) : null;
const browser = await chromium.launch({ headless: true });
const evidence = {
  schema: 'helix-news-column-purpose-poc.v1',
  baseUrl,
  expectedWordPress: '7.1.2',
  routes: [],
  viewports: [],
  checks: [],
};

const sourceFiles = [
  'docs/research/2026-09-26-news-column-purpose-gap/compose.yaml',
  'docs/research/2026-09-26-news-column-purpose-gap/seed.php',
  'docs/research/2026-09-26-news-column-purpose-gap/verify.mjs',
  'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/functions.php',
  'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/theme.json',
  'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/templates/category.html',
  'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/templates/single.html',
  'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/assets/css/theme.css',
];

async function addSourceDigests() {
  evidence.sourceDigests = {};
  for (const file of sourceFiles) {
    evidence.sourceDigests[file] = createHash('sha256').update(await fs.readFile(path.resolve(process.cwd(), file))).digest('hex');
  }
}

function recordCheck(name, facts = {}) {
  evidence.checks.push({ name, pass: true, ...facts });
}

async function writeEvidence() {
  evidence.generatedAt = new Date().toISOString();
  const serialized = `${JSON.stringify(evidence, null, 2)}\n`;
  await fs.mkdir(evidenceDir, { recursive: true });
  await fs.writeFile(path.join(evidenceDir, 'verification.json'), serialized);
  if (resultPath) {
    await fs.mkdir(path.dirname(resultPath), { recursive: true });
    await fs.writeFile(resultPath, serialized);
  }
  return serialized;
}

async function open(url) {
  const page = await browser.newPage();
  const response = await page.goto(url, { waitUntil: 'networkidle' });
  assert.equal(response?.status(), 200, `${url} should return HTTP 200`);
  const version = await page.locator('meta[name="generator"]').getAttribute('content');
  assert.match(version ?? '', /^WordPress 7\.1\.2\b/, `unexpected WordPress version: ${version}`);
  return page;
}

async function mainTitles(page) {
  return page.locator('main.wt-category .wt-cat-primary-list .wp-block-post-title a').allTextContents();
}

async function checkArchive({ slug, label, expected, expectedDescription }) {
  const url = new URL(`/category/${slug}/`, baseUrl).toString();
  const page = await open(url);
  assert.equal(await page.locator('main.wt-category h1').innerText(), label);
  assert.deepEqual((await mainTitles(page)).map((value) => value.trim()), expected);
  const description = (await page.locator('main.wt-category .wt-cat-head__desc').innerText()).trim();
  assert.ok(description.includes(expectedDescription), `${slug} should explain its editorial purpose`);
  const visibleCategories = await page.locator('main.wt-category .wt-cat-card__terms a').allTextContents();
  assert.ok(visibleCategories.length >= 2, `${slug} should display visible purpose/topic classifications`);
  const structure = await page.evaluate(() => {
    const primary = document.querySelector('main.wt-category .wt-cat-primary');
    const guide = document.querySelector('main.wt-category .wt-cat-minihome');
    const aside = document.querySelector('main.wt-category .wt-cat-aside');
    return {
      guideInPrimaryColumn: Boolean(primary && guide && primary.contains(guide)),
      guideBeforeSidebar: Boolean(guide && aside && guide.compareDocumentPosition(aside) & Node.DOCUMENT_POSITION_FOLLOWING),
    };
  });
  assert.deepEqual(structure, { guideInPrimaryColumn: true, guideBeforeSidebar: true });
  evidence.routes.push({
    url,
    status: 200,
    heading: label,
    description,
    primaryTitles: expected,
    classificationLinks: visibleCategories.map((value) => value.trim()),
    structure,
  });
  recordCheck(`purpose:${slug}-archive-description-and-classification`, { route: slug });
  if (slug === 'news-releases') recordCheck('layout:category-guide-in-primary-before-sidebar');

  for (const width of [390, 768, 1440]) {
    await page.setViewportSize({ width, height: width < 600 ? 844 : 1000 });
    await page.reload({ waitUntil: 'networkidle' });
    const layout = await page.evaluate(() => ({
      viewportWidth: window.innerWidth,
      documentWidth: document.documentElement.scrollWidth,
      mainWidth: Math.round(document.querySelector('main.wt-category').getBoundingClientRect().width),
      primaryWidth: Math.round(document.querySelector('.wt-cat-primary').getBoundingClientRect().width),
    }));
    assert.ok(layout.documentWidth <= layout.viewportWidth, `${slug} horizontal overflow at ${width}px: ${JSON.stringify(layout)}`);
    evidence.viewports.push({ route: slug, width, ...layout });
    recordCheck(`responsive:${slug}:${width}-no-horizontal-overflow`, { viewportWidth: layout.viewportWidth, documentWidth: layout.documentWidth });
    await page.screenshot({ path: path.join(evidenceDir, `${slug}-${width}.png`), fullPage: true });
  }
  await page.close();
}

try {
  await fs.mkdir(evidenceDir, { recursive: true });
  await checkArchive({
    slug: 'news-releases',
    label: 'ニュース・発表',
    expectedDescription: '日付・分類',
    expected: ['架空製品の発表例（2026）', '架空サービスのお知らせ例（2025）'],
  });
  await checkArchive({
    slug: 'columns',
    label: 'コラム',
    expectedDescription: 'テーマ別',
    expected: ['架空組織づくりコラム（2026）', '架空データ活用コラム（2025）'],
  });
  await checkArchive({
    slug: 'search-intent',
    label: '検索流入',
    expectedDescription: '疑問を解決',
    expected: ['架空導入ガイドの記事例（2026）'],
  });

  for (const { category, year, slug, expectedTitle, forbiddenTitle, date, purpose } of [
    {
      category: 'news-releases',
      year: '2025',
      slug: 'service-notice-sample-2025',
      expectedTitle: '架空サービスのお知らせ例（2025）',
      forbiddenTitle: '架空製品の発表例（2026）',
      date: '2025-12-15',
      purpose: 'ニュース・発表',
    },
    {
      category: 'columns',
      year: '2025',
      slug: 'data-column-sample-2025',
      expectedTitle: '架空データ活用コラム（2025）',
      forbiddenTitle: '架空組織づくりコラム（2026）',
      date: '2025-10-02',
      purpose: 'コラム',
    },
    {
      category: 'search-intent',
      year: '2026',
      slug: 'search-guide-sample-2026',
      expectedTitle: '架空導入ガイドの記事例（2026）',
      forbiddenTitle: '架空サービスのお知らせ例（2025）',
      date: '2026-06-18',
      purpose: '検索流入',
    },
  ]) {
    const url = new URL(`/category/${category}/?year=${year}`, baseUrl).toString();
    const page = await open(url);
    const titles = (await mainTitles(page)).map((value) => value.trim());
    assert.deepEqual(titles, [expectedTitle]);
    assert.ok(!titles.includes(forbiddenTitle));
    const detailLink = page.locator('main.wt-category .wt-cat-primary-list .wp-block-post-title a').first();
    await detailLink.click();
    await page.waitForLoadState('networkidle');
    assert.equal(await page.locator('main article h1, main .wp-block-post-title').first().innerText(), expectedTitle);
    assert.ok((await page.locator('time').first().getAttribute('datetime'))?.startsWith(date));
    const articleTerms = (await page.locator('main.wt-article .wt-breadcrumb .wp-block-post-terms a').allTextContents()).map((value) => value.trim());
    assert.ok(articleTerms.includes(purpose));
    const restResponse = await page.request.get(`${baseUrl}/wp-json/wp/v2/posts?slug=${slug}&_fields=id,type`);
    assert.equal(restResponse.status(), 200);
    const restRecords = await restResponse.json();
    assert.equal(restRecords.length, 1);
    assert.equal(restRecords[0].type, 'post', 'purpose classification must not require a dedicated post type');
    evidence.routes.push({ url, status: 200, primaryTitles: titles, followedDetailDate: date, articleTerms, postType: restRecords[0].type });
    recordCheck(`archive:${category}:${year}-filter-to-dated-detail`, { route: category, year, date, postType: restRecords[0].type });
    recordCheck(`type:${category}-purpose-uses-standard-post`, { route: category, postType: restRecords[0].type });
    await page.close();
  }

  for (const { path: route, title } of [
    { path: 'news-releases/product-news', title: '架空製品の発表例（2026）' },
    { path: 'news-releases/announcements', title: '架空サービスのお知らせ例（2025）' },
    { path: 'columns/organization', title: '架空組織づくりコラム（2026）' },
    { path: 'columns/data', title: '架空データ活用コラム（2025）' },
    { path: 'search-intent/guides', title: '架空導入ガイドの記事例（2026）' },
  ]) {
    const url = new URL(`/category/${route}/`, baseUrl).toString();
    const childPage = await open(url);
    const childTitles = (await mainTitles(childPage)).map((value) => value.trim());
    assert.deepEqual(childTitles, [title]);
    evidence.routes.push({ url, status: 200, primaryTitles: childTitles });
    recordCheck(`topics:${route}-child-archive`, { route });
    await childPage.close();
  }

  recordCheck('classification:five-topical-and-notice-child-archives');
  await addSourceDigests();
  evidence.completed = true;
  process.stdout.write(await writeEvidence());
} catch (error) {
  evidence.completed = false;
  evidence.error = error instanceof Error ? error.message : String(error);
  await addSourceDigests().catch(() => {});
  await writeEvidence();
  throw error;
} finally {
  await browser.close();
}
