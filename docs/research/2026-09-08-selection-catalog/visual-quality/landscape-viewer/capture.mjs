import { chromium } from '@playwright/test';
import { createHash } from 'node:crypto';
import { readFile, writeFile, mkdir } from 'node:fs/promises';
import { execFileSync } from 'node:child_process';
import assert from 'node:assert/strict';
const catalog = 'docs/research/2026-09-08-selection-catalog';
const evidence = `${catalog}/visual-quality/landscape-viewer`;
const output = process.env.CATALOG_CAPTURE_OUTPUT || evidence;
const base = process.env.CATALOG_BASE_URL;
if (!base) throw new Error('CATALOG_BASE_URL is required');
const baseline = execFileSync('git', ['rev-parse', process.env.CATALOG_BASELINE_REF || '68668c2'], { encoding: 'utf8' }).trim();
const css = execFileSync('git', ['show', `${baseline}:${catalog}/catalog.css`]);
const hash = value => createHash('sha256').update(value).digest('hex');
assert.notEqual(hash(css), hash(await readFile(`${catalog}/catalog.css`)));
const sourceDigests = {};
for (const file of ['index.html', 'catalog.css', 'catalog.mjs', 'catalog-data.json']) {
  const path = `${catalog}/${file}`, bytes = await readFile(path), response = await fetch(`${base}/${path}`);
  assert(response.ok); assert.equal(hash(Buffer.from(await response.arrayBuffer())), hash(bytes), `Server mismatch: ${file}`);
  sourceDigests[path] = hash(bytes);
}
for (const path of [`${evidence}/capture.mjs`, 'tests/e2e/landscape-viewer-selection-catalog.spec.ts']) sourceDigests[path] = hash(await readFile(path));
await mkdir(output, { recursive: true });
const browser = await chromium.launch(), records = [];
try {
  for (const stage of ['before', 'after']) for (const [width, height] of [[844, 320], [844, 390], [390, 844], [1024, 600], [1440, 900]]) for (const [index, mode] of ['overview', 'width', 'native'].entries()) {
    const page = await browser.newPage({ viewport: { width, height } });
    if (stage === 'before') await page.route(`${base}/${catalog}/catalog.css`, route => route.fulfill({ body: css, contentType: 'text/css' }));
    await page.goto(`${base}/${catalog}/`);
    await page.locator('[data-face=lp]').click(); await page.locator('#search').fill('standard');
    await page.locator('.tile-open').click(); await page.locator('#detail .image-controls button').nth(index).click();
    await page.locator('#detail img').evaluate(image => image.decode()); await page.evaluate(() => document.fonts.ready);
    if (width > 700) await page.locator('#detail').evaluate(dialog => { dialog.scrollTop = 500; });
    else await page.locator('#detail .image-viewer').scrollIntoViewIfNeeded();
    if (mode !== 'overview') await page.locator('#detail .image-viewer').evaluate(viewer => { viewer.scrollTop = viewer.scrollHeight; });
    const values = await page.locator('#detail').evaluate(dialog => {
      const viewer = dialog.querySelector('.image-viewer'), image = viewer.querySelector('img');
      const v = viewer.getBoundingClientRect(), d = dialog.getBoundingClientRect(), h = dialog.querySelector('.dialog-head').getBoundingClientRect(), i = image.getBoundingClientRect();
      return { viewerWidth: v.width, viewerHeight: v.height, viewerTop: v.top, viewerBottom: v.bottom, dialogBottom: d.bottom, headerBottom: h.bottom, clipping: Math.max(0, v.bottom - d.bottom), imageBottom: i.bottom, endDistance: viewer.scrollHeight - viewer.scrollTop - viewer.clientHeight, scrollTop: viewer.scrollTop, content: dialog.querySelector('.detail-copy').textContent, image: image.getAttribute('src') };
    });
    const contentDigest = hash(JSON.stringify({ content: values.content, image: values.image }));
    delete values.content; delete values.image;
    const record = { stage, width, height, mode, contentDigest, ...values };
    if (width === 844 && mode === 'width') {
      const screenshot = `${stage}-${width}x${height}.png`, bytes = await page.screenshot({ path: `${output}/${screenshot}` });
      record.screenshot = screenshot; record.screenshotDigest = hash(bytes);
    }
    if (stage === 'after') {
      const prior = records.find(r => r.stage === 'before' && r.width === width && r.height === height && r.mode === mode);
      assert.equal(contentDigest, prior.contentDigest);
      if (width > 700) {
        assert.equal(record.clipping, 0); assert(record.viewerTop >= record.headerBottom);
        if (mode === 'overview') assert(record.imageBottom <= record.viewerBottom);
        if (mode !== 'overview') { assert(record.imageBottom < record.dialogBottom); assert(record.endDistance <= 1); assert(record.scrollTop > 0); }
      }
      if (width === 844) { assert(prior.clipping > 0); assert(record.viewerHeight < prior.viewerHeight); }
      else { assert.equal(record.viewerWidth, prior.viewerWidth); assert.equal(record.viewerHeight, prior.viewerHeight); }
    }
    records.push(record); await page.close();
  }
  await writeFile(`${output}/observations.json`, JSON.stringify({ baseline, sourceDigests, limitations: ['Chromium only; representative LP standard image', 'Sticky detail viewport after 500px dialog scroll; not all scroll positions or browser chrome configurations', 'No requirement or acceptance completion claim'], records }, null, 2) + '\n');
} finally { await browser.close(); }
