import { chromium } from 'playwright';
import { writeFile, readFile, mkdir } from 'node:fs/promises';
import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
const stage = process.argv[2];
if (!['before', 'after'].includes(stage)) throw Error('Use before or after');
const root = 'docs/research/2026-09-08-selection-catalog';
const base = process.env.CATALOG_BASE_URL || 'http://127.0.0.1:8099';
const output = process.env.CATALOG_CAPTURE_OUTPUT || `${root}/visual-quality/dialog-focus`;
if (!process.env.CATALOG_BASELINE_REF) throw Error('CATALOG_BASELINE_REF is required');
const baseline = execFileSync('git', ['rev-parse', '--verify', `${process.env.CATALOG_BASELINE_REF}^{commit}`], { encoding: 'utf8' }).trim();
const sourceDigests = {};
const baselineSources = {};
let changed = false;
for (const file of ['index.html', 'catalog.css', 'catalog.mjs']) {
  const path = `${root}/${file}`;
  const current = await readFile(path);
  const response = await fetch(`${base}/${path}`);
  if (!response.ok || !current.equals(Buffer.from(await response.arrayBuffer()))) throw Error(`Server source mismatch: ${file}`);
  const historical = execFileSync('git', ['show', `${baseline}:${path}`]);
  baselineSources[file] = historical;
  changed ||= !historical.equals(current);
  sourceDigests[path] = createHash('sha256').update(stage === 'before' ? historical : current).digest('hex');
}
if (!changed) throw Error('Baseline and current sources must differ');
sourceDigests[`${root}/visual-quality/dialog-focus/capture.mjs`] = createHash('sha256').update(await readFile(new URL(import.meta.url))).digest('hex');
await mkdir(output, { recursive: true });
const browser = await chromium.launch({ args: ['--no-sandbox'] });
const rows = [];
try {
  for (const width of [320, 390, 768, 1440]) {
    const page = await browser.newPage({ viewport: { width, height: 844 } });
    if (stage === 'before') {
      for (const [file, body] of Object.entries(baselineSources)) {
        const url = file === 'index.html' ? `${base}/${root}/` : `${base}/${root}/${file}`;
        await page.route(url, route => route.fulfill({ body, contentType: file.endsWith('.html') ? 'text/html' : file.endsWith('.css') ? 'text/css' : 'text/javascript' }));
      }
    }
    await page.goto(`${base}/${root}/`);
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
      if (width === 390 && step === 2) await page.screenshot({ path: `${output}/${stage}-390.png` });
    }
    if (!Number.isFinite(minClearance)) throw Error(`No focus measurement at ${width}px`);
    if (stage === 'after' && minClearance < 6) throw Error(`Focus obscured at ${width}px: ${minClearance}`);
    rows.push({ width, height: 844, minFocusClearance: minClearance, passed: minClearance >= 6 });
    await page.close();
  }
  await writeFile(`${output}/${stage}.json`, JSON.stringify({ baseline, sourceDigests, scenario: 'detail last related requirement then reverse Tab to header', rows }, null, 2) + '\n');
  console.log(rows);
} finally { await browser.close(); }
