import { chromium } from 'playwright';
import { fileURLToPath } from 'node:url';
const browser = await chromium.launch({ headless: true, args: ['--no-sandbox'] });
const results = [];
try {
  for (const width of [390, 1440]) {
    const page = await browser.newPage({ viewport: { width, height: 900 } });
    await page.goto(`${process.env.CATALOG_BASE_URL || 'http://127.0.0.1:8099'}/docs/research/2026-09-08-selection-catalog/`);
    await page.locator('.tile-open').first().waitFor();
    await page.evaluate(async () => { await document.fonts.ready; await Promise.all([...document.images].slice(0, 6).map(img => img.decode().catch(() => {}))); });
    results.push({ width, ...await page.evaluate(() => ({ overflow: document.documentElement.scrollWidth > innerWidth, galleryTop: document.querySelector('#gallery').getBoundingClientRect().top, firstCompareBottom: document.querySelector('.compare-pick').getBoundingClientRect().bottom })) });
    await page.screenshot({ path: fileURLToPath(new URL(`gallery-${width}.png`, import.meta.url)) });
    for (let i = 0; i < 3; i++) await page.locator('.compare-pick input').nth(i).check();
    await page.locator('#open-compare').click();
    if (width === 390) await page.locator('.compare-switcher button').nth(1).click();
    await page.screenshot({ path: fileURLToPath(new URL(`compare-${width}.png`, import.meta.url)) });
    await page.close();
  }
  console.log(JSON.stringify(results, null, 2));
} finally { await browser.close(); }
