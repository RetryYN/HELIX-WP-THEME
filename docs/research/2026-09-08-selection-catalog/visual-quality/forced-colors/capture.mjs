import { chromium } from '@playwright/test';
import { readFile, writeFile, mkdir } from 'node:fs/promises';
import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import assert from 'node:assert/strict';
const root = 'docs/research/2026-09-08-selection-catalog';
const captureRoot = `${root}/visual-quality/forced-colors`;
const output = process.env.CATALOG_CAPTURE_OUTPUT || captureRoot;
const base = process.env.CATALOG_BASE_URL;
assert(base && process.env.CATALOG_BASELINE_REF, 'CATALOG_BASE_URL and CATALOG_BASELINE_REF required');
const baseline = execFileSync('git', ['rev-parse', '--verify', `${process.env.CATALOG_BASELINE_REF}^{commit}`], { encoding: 'utf8' }).trim();
const baselineCss = execFileSync('git', ['show', `${baseline}:${root}/catalog.css`]);
assert(!baselineCss.equals(await readFile(`${root}/catalog.css`)), 'Baseline CSS must differ');
const digest = bytes => createHash('sha256').update(bytes).digest('hex');
const sourceDigests = {};
for (const path of [`${root}/catalog.css`, `${root}/catalog.mjs`, `${root}/index.html`, `${root}/catalog-data.json`, `${captureRoot}/capture.mjs`, 'tests/e2e/forced-colors-selection-catalog.spec.ts']) {
  const bytes = await readFile(path);
  if (path.startsWith(root) && !path.endsWith('capture.mjs')) {
    const response = await fetch(`${base}/${path}`);
    assert(response.ok && bytes.equals(Buffer.from(await response.arrayBuffer())), `Server source mismatch: ${path}`);
  }
  sourceDigests[path] = digest(bytes);
}
await mkdir(output, { recursive: true });
const browser = await chromium.launch();
const observations = [];
const screenshots = [];
try {
  for (const width of [390, 1440]) for (const forcedColors of ['active', 'none']) for (const colorScheme of ['light', 'dark']) for (const stage of ['before', 'after']) {
    const page = await browser.newPage({ viewport: { width, height: 900 }, forcedColors, colorScheme, reducedMotion: 'reduce' });
    if (stage === 'before') await page.route(`${base}/${root}/catalog.css`, route => route.fulfill({ body: baselineCss, contentType: 'text/css' }));
    await page.goto(`${base}/${root}/`);
    await page.locator('.tile img').evaluateAll(images => Promise.all(images.slice(0, 4).map(image => { image.loading = 'eager'; return image.decode(); })));
    await page.evaluate(() => document.fonts.ready);
    for (const scenario of ['gallery', 'detail']) {
      if (scenario === 'detail') await page.locator('.tile-open').first().click();
      await page.locator(scenario === 'detail' ? '#detail img' : '.tile img').first().evaluate(image => image.decode());
      const selector = scenario === 'detail' ? '#detail .image-controls button' : '.device button';
      const controls = await page.locator(selector).evaluateAll(elements => elements.map(e => ({
        label: e.textContent, selected: e.getAttribute('aria-pressed') === 'true',
        decoration: getComputedStyle(e).textDecorationLine,
        thickness: getComputedStyle(e).textDecorationThickness,
        width: e.getBoundingClientRect().width, height: e.getBoundingClientRect().height,
      })));
      if (stage === 'after') for (const control of controls) assert.equal(control.decoration, forcedColors === 'active' && control.selected ? 'underline' : 'none');
      observations.push({ stage, width, forcedColors, colorScheme, scenario, controls });
      const bytes = forcedColors === 'none'
        ? await page.locator(scenario === 'detail' ? '#detail .image-controls' : '.device').screenshot()
        : await page.screenshot();
      const file = `${stage}-${colorScheme}-${scenario}-${width}.png`;
      if (forcedColors === 'active' && width === 390) await writeFile(`${output}/${file}`, bytes);
      screenshots.push({ stage, width, forcedColors, colorScheme, scenario, scope: forcedColors === 'none' ? 'controls' : 'viewport', ...(forcedColors === 'active' && width === 390 ? { file } : {}), sha256: digest(bytes) });
    }
    await page.close();
  }
} finally { await browser.close(); }
for (const width of [390, 1440]) for (const forcedColors of ['active', 'none']) for (const colorScheme of ['light', 'dark']) for (const scenario of ['gallery', 'detail']) {
  const pair = screenshots.filter(row => row.width === width && row.forcedColors === forcedColors && row.colorScheme === colorScheme && row.scenario === scenario);
  if (forcedColors === 'none') assert.equal(pair[0].sha256, pair[1].sha256, `Normal control rendering must remain byte-identical: ${width}/${colorScheme}/${scenario}`);
  else assert.notEqual(pair[0].sha256, pair[1].sha256, 'Forced colors must gain visible selection');
  const geometry = observations.filter(row => row.width === width && row.forcedColors === forcedColors && row.colorScheme === colorScheme && row.scenario === scenario).map(row => row.controls.map(({ decoration, thickness, ...control }) => control));
  assert.deepEqual(geometry[0], geometry[1], 'Control labels, selection and geometry must stay unchanged');
}
await writeFile(`${output}/observations.json`, JSON.stringify({ schema: 'wt-forced-colors-selection.v1', baseline, sourceDigests, observations, screenshots, limitations: ['Chromium forced-colors emulation with light/dark palettes; not all OS themes or assistive technology', 'Before swaps baseline CSS against the same current content'] }, null, 2) + '\n');
