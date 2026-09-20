import { chromium } from '@playwright/test';
import { readFile, writeFile, mkdir } from 'node:fs/promises';
import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import assert from 'node:assert/strict';
const root = 'docs/research/2026-09-08-selection-catalog';
const captureRoot = `${root}/visual-quality/compare-pick-targets`;
const output = process.env.CATALOG_CAPTURE_OUTPUT || captureRoot;
const base = process.env.CATALOG_BASE_URL;
assert(base && process.env.CATALOG_BASELINE_REF, 'CATALOG_BASE_URL and CATALOG_BASELINE_REF required');
const baseline = execFileSync('git', ['rev-parse', '--verify', `${process.env.CATALOG_BASELINE_REF}^{commit}`], { encoding: 'utf8' }).trim();
const baselineCss = execFileSync('git', ['show', `${baseline}:${root}/catalog.css`]);
assert(!baselineCss.equals(await readFile(`${root}/catalog.css`)), 'Baseline CSS must differ');
const sourceDigests = {};
for (const path of [`${root}/catalog.css`, `${root}/catalog.mjs`, `${root}/index.html`, `${root}/catalog-data.json`, `${captureRoot}/capture.mjs`, 'tests/e2e/compare-pick-targets-selection-catalog.spec.ts']) {
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
    for (const collection of ['common', 'all']) {
      await page.locator(`[data-face="${collection}"]`).click();
      const tile = page.locator('.tile').first();
      await tile.scrollIntoViewIfNeeded();
      const measurement = await tile.evaluate(e => {
        const label = e.querySelector('.compare-pick');
        const r = label.getBoundingClientRect();
        const footer = e.querySelector('.tile-footer');
        return { cardWidth: e.getBoundingClientRect().width, targetWidth: r.width, targetHeight: r.height, overflow: footer.scrollWidth > footer.clientWidth };
      });
      observations.push({ stage, width, collection, ...measurement });
      if (stage === 'after') assert(measurement.targetWidth >= 96 && measurement.targetHeight >= 44 && !measurement.overflow, 'Readable target');
      if (width === 390) {
        await page.locator('.tile img').first().evaluate(image => image.decode());
        await page.locator('.compare-pick input').first().check();
        await tile.scrollIntoViewIfNeeded();
        const file = `${stage}-${collection}-390.png`;
        await page.screenshot({ path: `${output}/${file}` });
        screenshots.push({ file, sha256: createHash('sha256').update(await readFile(`${output}/${file}`)).digest('hex') });
        await page.locator('.compare-pick input').first().uncheck();
      }
    }
    await page.close();
  }
} finally { await browser.close(); }
await writeFile(`${output}/observations.json`, JSON.stringify({ schema: 'wt-compare-pick-targets.v1', baseline, sourceDigests, observations, screenshots, limitations: ['Static catalog / Chromium only; no requirement completion or real-device accessibility claim', 'Before swaps baseline CSS against the same current content'] }, null, 2) + '\n');
