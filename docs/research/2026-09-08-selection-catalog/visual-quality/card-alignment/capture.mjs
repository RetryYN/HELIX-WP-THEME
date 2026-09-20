import { chromium } from '@playwright/test';
import { readFile, writeFile, mkdir } from 'node:fs/promises';
import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import assert from 'node:assert/strict';
const root = 'docs/research/2026-09-08-selection-catalog';
const captureRoot = `${root}/visual-quality/card-alignment`;
const output = process.env.CATALOG_CAPTURE_OUTPUT || captureRoot;
const base = process.env.CATALOG_BASE_URL;
assert(base && process.env.CATALOG_BASELINE_REF, 'CATALOG_BASE_URL and CATALOG_BASELINE_REF required');
const baseline = execFileSync('git', ['rev-parse', '--verify', `${process.env.CATALOG_BASELINE_REF}^{commit}`], { encoding: 'utf8' }).trim();
const baselineCss = execFileSync('git', ['show', `${baseline}:${root}/catalog.css`]);
assert(!baselineCss.equals(await readFile(`${root}/catalog.css`)), 'Baseline CSS must differ');
const sourceDigests = {};
for (const path of [`${root}/catalog.css`, `${root}/catalog.mjs`, `${root}/index.html`, `${root}/catalog-data.json`, `${captureRoot}/capture.mjs`, 'tests/e2e/card-alignment-selection-catalog.spec.ts']) {
  const bytes = await readFile(path);
  if (path.startsWith(root) && !path.endsWith('capture.mjs')) {
    const response = await fetch(`${base}/${path}`);
    assert(response.ok && bytes.equals(Buffer.from(await response.arrayBuffer())), `Server source mismatch: ${path}`);
  }
  sourceDigests[path] = createHash('sha256').update(bytes).digest('hex');
}
await mkdir(output, { recursive: true });
const browser = await chromium.launch();
const observations = [];
const screenshots = [];
try {
  for (const width of [320, 390, 768, 1440]) for (const stage of ['before', 'after']) {
    const page = await browser.newPage({ viewport: { width, height: 844 }, reducedMotion: 'reduce' });
    if (stage === 'before') await page.route(`${base}/${root}/catalog.css`, route => route.fulfill({ body: baselineCss, contentType: 'text/css' }));
    await page.goto(`${base}/${root}/`);
    await page.locator('[data-face="all"]').click();
    await page.locator('.tile img').evaluateAll(images => Promise.all(images.slice(0, 2).map(image => image.decode())));
    const cards = await page.locator('.tile').evaluateAll(elements => elements.slice(0, 2).map(e => {
      const rect = e.getBoundingClientRect();
      return {
        top: rect.top, height: rect.height,
        previewTop: e.querySelector('.preview').getBoundingClientRect().top,
        titleTop: e.querySelector('h3').getBoundingClientRect().top,
        footerTop: e.querySelector('.tile-footer').getBoundingClientRect().top,
        badgeBottom: e.querySelector('.decision-badge').getBoundingClientRect().bottom,
        text: e.textContent,
      };
    }));
    const footerOffset = cards[0].footerTop - cards[1].footerTop;
    observations.push({ stage, width, cards, footerOffset });
    if (stage === 'after' && width > 360) assert(Math.abs(footerOffset) < 1, 'Actions must align');
    if ([390, 1440].includes(width)) {
      await page.locator('.tile').first().scrollIntoViewIfNeeded();
      const file = `${stage}-${width}.png`;
      await page.screenshot({ path: `${output}/${file}` });
      screenshots.push({ file, sha256: createHash('sha256').update(await readFile(`${output}/${file}`)).digest('hex') });
    }
    await page.close();
  }
} finally { await browser.close(); }
for (const width of [320, 390, 768, 1440]) {
  const pair = observations.filter(row => row.width === width);
  const contentAndGeometry = row => row.cards.map(({ footerTop, badgeBottom, ...card }) => card);
  assert.deepEqual(contentAndGeometry(pair[0]), contentAndGeometry(pair[1]), 'Card height/top/preview/title/content must stay unchanged');
}
assert(Math.abs(observations.find(row => row.width === 390 && row.stage === 'before').footerOffset) > 100, 'Baseline must reproduce staggered actions');
await writeFile(`${output}/observations.json`, JSON.stringify({ schema: 'wt-card-alignment.v1', baseline, sourceDigests, observations, screenshots, limitations: ['Static catalog / Chromium only; first mixed row, no whole-catalog accessibility claim', 'Before swaps baseline CSS against the same current content'] }, null, 2) + '\n');
