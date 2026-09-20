import { chromium } from '@playwright/test';
import { createHash } from 'node:crypto';
import { readFile, writeFile, mkdir } from 'node:fs/promises';
import { execFileSync } from 'node:child_process';
import assert from 'node:assert/strict';

const catalog = 'docs/research/2026-09-08-selection-catalog';
const evidence = `${catalog}/visual-quality/tablet-compare`;
const output = process.env.CATALOG_CAPTURE_OUTPUT || evidence;
const base = process.env.CATALOG_BASE_URL;
if (!base) throw new Error('CATALOG_BASE_URL is required');
const baseline = execFileSync('git', ['rev-parse', process.env.CATALOG_BASELINE_REF || '26074c4'], { encoding: 'utf8' }).trim();
const beforeCss = execFileSync('git', ['show', `${baseline}:${catalog}/catalog.css`]);
const hash = bytes => createHash('sha256').update(bytes).digest('hex');
assert.notEqual(hash(beforeCss), hash(await readFile(`${catalog}/catalog.css`)));
const sourceDigests = {};
for (const file of ['index.html', 'catalog.css', 'catalog.mjs', 'catalog-data.json']) {
  const path = `${catalog}/${file}`;
  const bytes = await readFile(path);
  const response = await fetch(`${base}/${path}`);
  assert.equal(response.ok, true);
  assert.equal(hash(Buffer.from(await response.arrayBuffer())), hash(bytes), `server source mismatch: ${file}`);
  sourceDigests[path] = hash(bytes);
}
for (const path of [`${evidence}/capture.mjs`, 'tests/e2e/tablet-compare-selection-catalog.spec.ts']) sourceDigests[path] = hash(await readFile(path));
await mkdir(output, { recursive: true });
const browser = await chromium.launch();
const records = [];
try {
  for (const stage of ['before', 'after']) for (const width of [390, 700, 701, 768, 1000, 1001, 1440]) for (const count of [2, 3]) {
    const page = await browser.newPage({ viewport: { width, height: 900 } });
    if (stage === 'before') await page.route(`${base}/${catalog}/catalog.css`, route => route.fulfill({ body: beforeCss, contentType: 'text/css' }));
    await page.goto(`${base}/${catalog}/`);
    for (let i = 0; i < count; i++) await page.locator('.compare-pick input').nth(i).check();
    await page.locator('#open-compare').click();
    // 同じ操作で表を閉じ、候補画像・説明の実表示を撮影する。
    if (await page.locator('.comparison-summary').getAttribute('open') !== null) await page.locator('.comparison-summary summary').click();
    await page.locator('#compare img').evaluateAll(images => Promise.all(images.map(img => img.decode())));
    await page.evaluate(() => document.fonts.ready);
    const measurement = await page.locator('#compare').evaluate(dialog => {
      const columns = [...dialog.querySelectorAll('.compare-grid>section')];
      return {
        dialogOverflow: dialog.scrollWidth - dialog.clientWidth,
        switcherVisible: getComputedStyle(dialog.querySelector('.compare-switcher')).display !== 'none',
        columns: columns.map(column => ({
          visible: getComputedStyle(column).display !== 'none',
          width: column.getBoundingClientRect().width,
          imageWidth: column.querySelector('img').getBoundingClientRect().width,
          copyWidth: column.querySelector('.detail-copy').clientWidth,
          copyOverflow: column.querySelector('.detail-copy').scrollWidth - column.querySelector('.detail-copy').clientWidth,
          text: column.textContent,
          image: column.querySelector('img').getAttribute('src'),
        })),
        tableText: dialog.querySelector('.comparison-facts').textContent,
      };
    });
    const contentDigest = hash(JSON.stringify({ columns: measurement.columns.map(c => ({ text: c.text, image: c.image })), table: measurement.tableText }));
    for (const column of measurement.columns) { delete column.text; delete column.image; }
    delete measurement.tableText;
    const record = { stage, width, count, contentDigest, ...measurement };
    if ([768, 1000].includes(width) && count === 3) {
      const screenshot = `${stage}-${width}.png`;
      const bytes = await page.screenshot({ path: `${output}/${screenshot}` });
      record.screenshot = screenshot; record.screenshotDigest = hash(bytes);
    }
    if (stage === 'after') {
      const prior = records.find(r => r.stage === 'before' && r.width === width && r.count === count);
      assert.equal(contentDigest, prior.contentDigest);
      assert.equal(record.dialogOverflow, 0);
      assert.equal(record.columns.filter(c => c.visible).length, width <= 1000 ? 1 : count);
      if (width > 700 && width <= 1000) {
        assert.ok(record.columns[0].imageWidth > prior.columns[0].imageWidth * 1.9);
        assert.equal(record.columns[0].copyOverflow, 0);
      } else assert.deepEqual(record.columns, prior.columns);
    }
    records.push(record);
    await page.close();
  }
  await writeFile(`${output}/observations.json`, JSON.stringify({ baseline, sourceDigests, limitations: ['Chromium only; representative first 2/3 catalog candidates', 'Comparison table initial disclosure behavior remains unchanged', 'No claim of whole-program acceptance completion'], records }, null, 2) + '\n');
} finally { await browser.close(); }
