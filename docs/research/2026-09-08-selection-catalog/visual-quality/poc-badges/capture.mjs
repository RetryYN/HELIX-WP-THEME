import { chromium } from '@playwright/test';
import { readFile, writeFile } from 'node:fs/promises';
import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import assert from 'node:assert/strict';
const root = 'docs/research/2026-09-08-selection-catalog';
const output = `${root}/visual-quality/poc-badges`;
const base = process.env.CATALOG_BASE_URL;
assert(base, 'CATALOG_BASE_URL required');
const baseline = execFileSync('git', ['rev-parse', '1d97c11'], { encoding: 'utf8' }).trim();
const sources = [`${root}/catalog.css`, `${root}/catalog.mjs`, `${root}/index.html`, `${output}/capture.mjs`, 'tests/e2e/poc-badges-selection-catalog.spec.ts'];
const sourceDigests = {};
for (const file of sources) {
  const bytes = await readFile(file);
  if (file.startsWith(root) && !file.endsWith('capture.mjs')) assert(bytes.equals(Buffer.from(await (await fetch(`${base}/${file}`)).arrayBuffer())), 'Server source mismatch');
  sourceDigests[file] = createHash('sha256').update(bytes).digest('hex');
}
const browser = await chromium.launch();
const screenshots = [];
try {
  for (const width of [390, 1440]) for (const stage of ['before', 'after']) {
    const page = await browser.newPage({ viewport: { width, height: 900 } });
    if (stage === 'before') for (const file of ['catalog.css', 'catalog.mjs']) await page.route(`${base}/${root}/${file}`, route => route.fulfill({ body: execFileSync('git', ['show', `${baseline}:${root}/${file}`]), contentType: file.endsWith('css') ? 'text/css' : 'application/javascript' }));
    await page.goto(`${base}/${root}/`);
    await page.locator('#tab-requirements').click();
    await page.locator('#requirements').scrollIntoViewIfNeeded();
    const file = `${stage}-${width}.png`;
    await page.screenshot({ path: `${output}/${file}` });
    screenshots.push({ file, sha256: createHash('sha256').update(await readFile(`${output}/${file}`)).digest('hex') });
    await page.close();
  }
  for (const width of [390, 1440]) assert.notEqual(screenshots.find(s => s.file === `before-${width}.png`).sha256, screenshots.find(s => s.file === `after-${width}.png`).sha256, 'Before/after must differ');
} finally { await browser.close(); }
await writeFile(`${output}/observations.json`, JSON.stringify({ schema: 'wt-poc-badge-visual.v1', baseline, sourceDigests, screenshots, limitations: ['Static catalog only; no WordPress runtime or requirement completion claim', 'Screenshots show initial requirement rows; browser spec additionally checks partial counts and keyboard expansion'] }, null, 2) + '\n');
