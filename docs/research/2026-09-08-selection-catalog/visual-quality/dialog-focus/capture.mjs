import { chromium } from 'playwright';
import { writeFile } from 'node:fs/promises';
const stage = process.argv[2];
if (!['before', 'after'].includes(stage)) throw Error('Use before or after');
const browser = await chromium.launch({ args: ['--no-sandbox'] });
const rows = [];
try {
  for (const width of [320, 390, 768, 1440]) {
    const page = await browser.newPage({ viewport: { width, height: 844 } });
    await page.goto(`${process.env.CATALOG_BASE_URL || 'http://127.0.0.1:8099'}/docs/research/2026-09-08-selection-catalog/`);
    await page.locator('.tile-open').first().click();
    await page.locator('#detail .related-requirement button').last().focus();
    let minClearance = Infinity;
    for (let step = 0; step < 16; step++) {
      await page.keyboard.press('Shift+Tab');
      const position = await page.evaluate(() => {
        const focus = document.activeElement;
        const header = document.querySelector('#detail .dialog-head');
        return { inHeader: header.contains(focus), clearance: focus.getBoundingClientRect().top - header.getBoundingClientRect().bottom };
      });
      if (position.inHeader) break;
      minClearance = Math.min(minClearance, position.clearance);
      if (width === 390 && step === 2) await page.screenshot({ path: new URL(`${stage}-390.png`, import.meta.url).pathname });
    }
    rows.push({ width, height: 844, minFocusClearance: minClearance, passed: minClearance >= 6 });
    await page.close();
  }
  await writeFile(new URL(`${stage}.json`, import.meta.url), JSON.stringify({ baseline: '5961bde', scenario: 'detail last related requirement then reverse Tab to header', rows }, null, 2) + '\n');
  console.log(rows);
} finally { await browser.close(); }
