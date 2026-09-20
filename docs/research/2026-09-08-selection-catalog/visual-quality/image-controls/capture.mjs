import { chromium } from '@playwright/test';
import { readFile, writeFile, mkdir } from 'node:fs/promises';
import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import assert from 'node:assert/strict';
const root = 'docs/research/2026-09-08-selection-catalog';
const captureRoot = `${root}/visual-quality/image-controls`;
const output = process.env.CATALOG_CAPTURE_OUTPUT || captureRoot;
const base = process.env.CATALOG_BASE_URL;
assert(base, 'CATALOG_BASE_URL required');
assert(process.env.CATALOG_BASELINE_REF, 'CATALOG_BASELINE_REF required');
const baseline = execFileSync('git', ['rev-parse', '--verify', `${process.env.CATALOG_BASELINE_REF}^{commit}`], { encoding: 'utf8' }).trim();
const baselineCss = execFileSync('git', ['show', `${baseline}:${root}/catalog.css`]);
assert(!baselineCss.equals(await readFile(`${root}/catalog.css`)), 'Baseline CSS must differ');
const sourceDigests = {};
for (const path of [`${root}/catalog.css`, `${root}/catalog.mjs`, `${root}/index.html`, `${root}/catalog-data.json`, `${captureRoot}/capture.mjs`, 'tests/e2e/image-controls-selection-catalog.spec.ts']) {
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
    const page = await browser.newPage({ viewport: { width, height: 640 }, reducedMotion: 'reduce' });
    if (stage === 'before') await page.route(`${base}/${root}/catalog.css`, route => route.fulfill({ body: baselineCss, contentType: 'text/css' }));
    await page.goto(`${base}/${root}/`);
    await page.locator('.tile-open').first().click();
    for (const dialog of ['detail', 'compare']) {
      if (dialog === 'compare') {
        await page.locator('#detail .close').click();
        await page.locator('.compare-pick input').nth(0).check();
        await page.locator('.compare-pick input').nth(1).check();
        await page.locator('#open-compare').click();
      }
      await page.locator(`#${dialog} img`).evaluateAll(images => Promise.all(images.map(image => image.decode())));
      const measurement = await page.locator(`#${dialog} .image-controls`).evaluate(e => ({
        height: e.getBoundingClientRect().height,
        buttons: [...e.querySelectorAll('button')].map(button => ({
          top: button.offsetTop,
          height: button.getBoundingClientRect().height,
          width: button.getBoundingClientRect().width,
          overflow: button.scrollWidth > button.clientWidth,
        })),
      }));
      if (stage === 'after') for (const button of measurement.buttons) {
        assert.equal(button.top, measurement.buttons[0].top, 'Buttons must share a row');
        assert(button.height >= 44 && button.width >= 44 && !button.overflow, 'Readable touch target');
      }
      observations.push({ stage, width, dialog, ...measurement });
      if (width === 320) {
        const file = `${stage}-${dialog}-320.png`;
        await page.screenshot({ path: `${output}/${file}` });
        screenshots.push({ file, sha256: createHash('sha256').update(await readFile(`${output}/${file}`)).digest('hex') });
      }
    }
    await page.close();
  }
} finally { await browser.close(); }
for (const dialog of ['detail', 'compare']) {
  const desktop = observations.filter(row => row.width === 1440 && row.dialog === dialog);
  assert.deepEqual(desktop[0].buttons, desktop[1].buttons, 'Desktop controls must be unchanged');
  const mobile = observations.filter(row => row.width === 320 && row.dialog === dialog);
  if (dialog === 'detail') assert(mobile[1].height < mobile[0].height, 'Small-screen detail controls must become shorter');
}
await writeFile(`${output}/observations.json`, JSON.stringify({ schema: 'wt-image-controls-visual.v1', baseline, sourceDigests, observations, screenshots, limitations: ['Static catalog / Chromium only; no requirement completion or real-device accessibility claim', 'Before swaps only baseline CSS against the same current content'] }, null, 2) + '\n');
