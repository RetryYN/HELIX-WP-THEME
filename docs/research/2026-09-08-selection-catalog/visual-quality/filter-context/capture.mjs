import { chromium } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { mkdir, readFile, writeFile } from 'node:fs/promises';

const base = process.env.CATALOG_BASE_URL || 'http://127.0.0.1:8099';
const catalog = 'docs/research/2026-09-08-selection-catalog';
const output = process.env.CATALOG_CAPTURE_OUTPUT || `${catalog}/visual-quality/filter-context`;
const baseline = process.env.CATALOG_BASELINE_REF;
if (!baseline) throw new Error('CATALOG_BASELINE_REF is required; refusing to capture HEAD as before-state');
await mkdir(output, { recursive: true });
const browser = await chromium.launch({ headless: true });
const observations = [];
for (const stage of ['before', 'after']) {
  for (const width of [1440, 390]) {
    const page = await browser.newPage({ viewport: { width, height: 900 } });
    if (stage === 'before') {
      for (const file of ['index.html', 'catalog.css', 'catalog.mjs']) {
        const source = execFileSync('git', ['show', `${baseline}:${catalog}/${file}`], { encoding: 'utf8' });
        await page.route(file === 'index.html' ? `${base}/${catalog}/` : `${base}/${catalog}/${file}`, route => route.fulfill({ body: source, contentType: file.endsWith('.css') ? 'text/css' : file.endsWith('.mjs') ? 'text/javascript' : 'text/html' }));
      }
    }
    await page.goto(`${base}/${catalog}/`);
    await page.locator('[data-face=all]').click();
    await page.locator('#search').fill('料金');
    await page.locator('#decision').selectOption('unreviewed');
    await page.locator('.results-head').scrollIntoViewIfNeeded();
    await page.locator('#search').blur();
    await page.screenshot({ path: `${output}/${stage}-${width}.png` });
    const visibleConditions = await page.locator('#filter-chips button').count();
    await page.locator('#tab-requirements').click();
    observations.push({ stage, width, visibleConditions, requirements: await page.locator('.req-row').count(), requirementSummary: await page.locator('#requirements-count').textContent(), horizontalOverflow: await page.evaluate(() => document.documentElement.scrollWidth > innerWidth) });
    await page.close();
  }
}
await browser.close();
for (const width of [1440, 390]) {
  const before = await readFile(`${output}/before-${width}.png`);
  const after = await readFile(`${output}/after-${width}.png`);
  if (before.equals(after)) throw new Error(`before/after screenshots are byte-identical at ${width}px`);
}
await writeFile(`${output}/observations.json`, JSON.stringify({ baseline: execFileSync('git', ['rev-parse', baseline], { encoding: 'utf8' }).trim(), observations }, null, 2) + '\n');
