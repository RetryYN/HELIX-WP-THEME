import { chromium } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { mkdir, writeFile, readFile } from 'node:fs/promises';
import { createHash } from 'node:crypto';

const base = process.env.CATALOG_BASE_URL || 'http://127.0.0.1:8099';
const root = 'docs/research/2026-09-08-selection-catalog';
const output = process.env.CATALOG_CAPTURE_OUTPUT || `${root}/visual-quality/detail-sequence`;
const baseline = process.env.CATALOG_BASELINE_REF;
if (!baseline) throw Error('CATALOG_BASELINE_REF is required');
const commit = execFileSync('git', ['rev-parse', baseline], { encoding: 'utf8' }).trim();
const sourceDigests = {};
for (const file of ['index.html', 'catalog.css', 'catalog.mjs']) {
  const source = await readFile(`${root}/${file}`);
  const served = Buffer.from(await (await fetch(`${base}/${root}/${file}`)).arrayBuffer());
  if (!source.equals(served)) throw Error(`Server source mismatch: ${file}`);
  sourceDigests[`${root}/${file}`] = createHash('sha256').update(source).digest('hex');
}
await mkdir(output, { recursive: true });
const browser = await chromium.launch();
const observations = [];
try {
  for (const width of [1440, 390]) {
    for (const stage of ['before', 'after']) {
      const page = await browser.newPage({ viewport: { width, height: 900 }, reducedMotion: 'reduce' });
      if (stage === 'before') for (const file of ['catalog.css', 'catalog.mjs']) {
        const body = execFileSync('git', ['show', `${commit}:${root}/${file}`], { encoding: 'utf8' });
        await page.route(`**/${file}`, route => route.fulfill({ body, contentType: file.endsWith('css') ? 'text/css' : 'text/javascript' }));
      }
      await page.goto(`${base}/${root}/`);
      await page.locator('.tile-open').first().click();
      await page.screenshot({ path: `${output}/${stage}-${width}.png` });
      observations.push({ width, stage, title: await page.locator('#detail .detail-copy h2').textContent(), sequenceControls: await page.locator('.detail-sequence button').count(), horizontalOverflow: await page.locator('#detail').evaluate(d => d.scrollWidth > d.clientWidth) });
      await page.close();
    }
    if ((await readFile(`${output}/before-${width}.png`)).equals(await readFile(`${output}/after-${width}.png`))) throw Error('before/after must differ');
  }
  await writeFile(`${output}/observations.json`, JSON.stringify({ baseline: commit, sourceDigests, reducedMotion: 'reduce', observations }, null, 2) + '\n');
} finally { await browser.close(); }
