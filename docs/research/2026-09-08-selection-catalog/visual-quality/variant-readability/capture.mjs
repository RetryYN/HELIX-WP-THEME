import { chromium } from '@playwright/test';
import { readFile, writeFile, mkdir } from 'node:fs/promises';
import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import assert from 'node:assert/strict';
const root = 'docs/research/2026-09-08-selection-catalog';
const captureRoot = `${root}/visual-quality/variant-readability`;
const output = process.env.CATALOG_CAPTURE_OUTPUT || captureRoot;
const base = process.env.CATALOG_BASE_URL;
assert(base && process.env.CATALOG_BASELINE_REF, 'CATALOG_BASE_URL and CATALOG_BASELINE_REF required');
const baseline = execFileSync('git', ['rev-parse', '--verify', `${process.env.CATALOG_BASELINE_REF}^{commit}`], { encoding: 'utf8' }).trim();
const baselineCss = execFileSync('git', ['show', `${baseline}:${root}/catalog.css`]);
assert(!baselineCss.equals(await readFile(`${root}/catalog.css`)), 'Baseline CSS must differ');
const sourceDigests = {};
for (const path of [`${root}/catalog.css`, `${root}/catalog.mjs`, `${root}/index.html`, `${root}/catalog-data.json`, `${captureRoot}/capture.mjs`, 'tests/e2e/variant-readability-selection-catalog.spec.ts']) {
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
  for (const width of [320, 390, 700, 1440]) for (const stage of ['before', 'after']) {
    const page = await browser.newPage({ viewport: { width, height: 844 }, reducedMotion: 'reduce' });
    if (stage === 'before') await page.route(`${base}/${root}/catalog.css`, route => route.fulfill({ body: baselineCss, contentType: 'text/css' }));
    await page.goto(`${base}/${root}/`);
    await page.locator('[data-face="all"]').click();
    for (const [scenario, query] of [['short', 'ブログカード'], ['long', 'own_category_footer_layout']]) {
      await page.locator('#search').fill(query);
      const tile = page.locator('.tile').first();
      await tile.scrollIntoViewIfNeeded();
      const measurement = await tile.locator('.variant').evaluate(e => ({
        fontSize: parseFloat(getComputedStyle(e).fontSize),
        lineHeight: parseFloat(getComputedStyle(e).lineHeight),
        labelWidth: e.clientWidth, labelHeight: e.clientHeight,
        clipped: e.scrollWidth > e.clientWidth + 1 || e.scrollHeight > e.clientHeight + 1,
        text: e.textContent,
      }));
      observations.push({ stage, width, scenario, ...measurement });
      if (stage === 'after') assert(measurement.fontSize >= 12 && measurement.lineHeight >= 19.2 && !measurement.clipped, 'Readable variant');
      if (width === 390) {
        await tile.locator('img').evaluate(image => image.decode());
        const file = `${stage}-${scenario}-390.png`;
        await page.screenshot({ path: `${output}/${file}` });
        screenshots.push({ file, sha256: createHash('sha256').update(await readFile(`${output}/${file}`)).digest('hex') });
      }
    }
    await page.close();
  }
} finally { await browser.close(); }
for (const scenario of ['short', 'long']) {
  const desktop = observations.filter(row => row.width === 1440 && row.scenario === scenario);
  const withoutStage = ({ stage, ...row }) => row;
  assert.deepEqual(withoutStage(desktop[0]), withoutStage(desktop[1]), 'Desktop labels must be unchanged');
}
await writeFile(`${output}/observations.json`, JSON.stringify({ schema: 'wt-variant-readability.v1', baseline, sourceDigests, observations, screenshots, limitations: ['Static catalog / Chromium only; no requirement completion or real-device accessibility claim', 'Before swaps baseline CSS against the same current content'] }, null, 2) + '\n');
