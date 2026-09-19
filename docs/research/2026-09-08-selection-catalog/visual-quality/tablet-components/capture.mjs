import { chromium } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { writeFile } from 'node:fs/promises';
const catalog = 'docs/research/2026-09-08-selection-catalog';
const output = `${catalog}/visual-quality/tablet-components`;
const base = process.env.CATALOG_BASE_URL;
if (!base) throw new Error('CATALOG_BASE_URL is required');
const baseline = 'ecca471';
const browser = await chromium.launch();
const records = [];
try {
  for (const stage of ['before', 'after']) for (const width of [390, 768, 1440]) {
    const page = await browser.newPage({ viewport: { width, height: 900 } });
    if (stage === 'before') await page.route(`${base}/${catalog}/catalog.css`, route => route.fulfill({ body: execFileSync('git', ['show', `${baseline}:${catalog}/catalog.css`], { encoding: 'utf8' }), contentType: 'text/css' }));
    await page.goto(`${base}/${catalog}/`);
    await page.waitForLoadState('networkidle');
    await page.locator('.component-preview img').first().evaluate(img => img.decode());
    await page.screenshot({ path: `${output}/${stage}-${width}.png` });
    records.push({ stage, width, ...await page.evaluate(() => ({ imageWidth: document.querySelector('.component-preview img').getBoundingClientRect().width, columns: getComputedStyle(document.querySelector('.gallery')).gridTemplateColumns, overflow: document.documentElement.scrollWidth > innerWidth })) });
    await page.close();
  }
  await writeFile(`${output}/observations.json`, JSON.stringify({ baseline: execFileSync('git', ['rev-parse', baseline], { encoding: 'utf8' }).trim(), records }, null, 2) + '\n');
} finally { await browser.close(); }
