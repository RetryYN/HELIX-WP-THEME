import { chromium } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { mkdir, mkdtemp, readFile, rename, rm, writeFile } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import { createHash } from 'node:crypto';

const base = process.env.CATALOG_BASE_URL || 'http://127.0.0.1:8099';
const catalog = 'docs/research/2026-09-08-selection-catalog';
const output = process.env.CATALOG_CAPTURE_OUTPUT || `${catalog}/visual-quality/decision-panel`;
const baseline = process.env.CATALOG_BASELINE_REF;
if (!baseline) throw new Error('CATALOG_BASELINE_REF is required; refusing to capture HEAD as before-state');
const baselineCommit = execFileSync('git', ['rev-parse', baseline], { encoding: 'utf8' }).trim();
await mkdir(dirname(output), { recursive: true });
const staging = await mkdtemp(join(dirname(output), '.decision-panel-capture-'));
let browser;
try {
  browser = await chromium.launch({ headless: true });
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
      await page.locator('.tile-open').first().click();
      await page.locator('#detail').getByRole('button', { name: '採用候補', exact: true }).click();
      await page.locator('#detail textarea').fill('余白と情報量を確認して決める');
      await page.locator('#detail .choices').scrollIntoViewIfNeeded();
      await page.locator('#detail textarea').blur();
      await page.screenshot({ path: join(staging, `${stage}-${width}.png`) });
      observations.push({ stage, width, buttons: await page.locator('#detail .choices button').evaluateAll(buttons => buttons.map(button => ({ label: button.getAttribute('aria-label') || button.textContent, pressed: button.getAttribute('aria-pressed'), height: button.getBoundingClientRect().height }))), horizontalOverflow: await page.locator('#detail').evaluate(dialog => dialog.scrollWidth > dialog.clientWidth) });
      await page.close();
    }
  }
  await browser.close();
  browser = undefined;
  for (const width of [1440, 390]) {
    const before = await readFile(join(staging, `before-${width}.png`));
    const after = await readFile(join(staging, `after-${width}.png`));
    if (before.equals(after)) throw new Error(`before/after screenshots are byte-identical at ${width}px`);
  }
  const sourceDigests = {};
  for (const file of ['index.html', 'catalog.css', 'catalog.mjs']) sourceDigests[`${catalog}/${file}`] = createHash('sha256').update(await readFile(`${catalog}/${file}`)).digest('hex');
  await writeFile(join(staging, 'observations.json'), JSON.stringify({ baseline: baselineCommit, sourceDigests, observations }, null, 2) + '\n');
  await mkdir(output, { recursive: true });
  for (const file of ['before-1440.png', 'before-390.png', 'after-1440.png', 'after-390.png', 'observations.json']) {
    await rename(join(staging, file), join(output, file));
  }
} finally {
  if (browser) await browser.close();
  await rm(staging, { recursive: true, force: true });
}
