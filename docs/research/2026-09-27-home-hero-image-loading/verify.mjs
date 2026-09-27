import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import crypto from 'node:crypto';
import { fileURLToPath } from 'node:url';
import { execFile } from 'node:child_process';
import { promisify } from 'node:util';
import { chromium } from '@playwright/test';

// この専用 Compose ラボだけを変更する。既存の mu-plugin は上書きしない。
const root = fileURLToPath(new URL('../../../', import.meta.url));
const here = fileURLToPath(new URL('./', import.meta.url));
const project = process.env.HELIX_PERF_PROJECT;
const envFile = process.env.HELIX_PERF_ENV_FILE;
const base = process.env.HELIX_PERF_BASE_URL;
const chrome = process.env.CHROME_PATH;
const lighthouse = process.env.HELIX_PERF_LIGHTHOUSE_BIN;
assert.match(project ?? '', /^helix-perf352-[a-z0-9-]+$/);
assert.ok(envFile && base && chrome && lighthouse, 'Set the lab environment, URL and pinned browser/Lighthouse paths');
assert.ok(['localhost', '127.0.0.1'].includes(new URL(base).hostname), 'local lab only');
const output = path.resolve(root, process.env.HELIX_PERF_OUTPUT ?? 'poc-wp/perf352');
fs.mkdirSync(output, { recursive: true });
const startedAt = new Date().toISOString();
const summaryPath = path.join(output, 'verification.json');
// 失敗時に、前回の completed:true が今回の結果として残らないようにする。
fs.writeFileSync(summaryPath, JSON.stringify({ completed: false, startedAt }) + '\n');
const run = promisify(execFile);
const compose = ['compose', '-p', project, '--env-file', path.resolve(envFile), '-f',
  path.join(root, 'docs/research/2026-09-26-news-column-purpose-gap/compose.yaml')];
const docker = args => run('docker', [...compose, ...args], { cwd: root, maxBuffer: 4 * 1024 * 1024 });
const target = '/var/www/html/wp-content/mu-plugins/helix-perf352-image-loading.php';
const source = path.join(here, 'hidden-hero-loading.php');
const hash = bytes => crypto.createHash('sha256').update(bytes).digest('hex');
const variants = ['text-only', 'slider', 'fullbleed', 'split', 'article-grid', 'video', 'cards-carousel', 'product-shot', 'search-box'];
const rows = [];
const performance = [];
const themePath = 'docs/research/2026-09-05-design-prototype-03/theme/helix-wt';
const { stdout: dirtyTheme } = await run('git', ['status', '--porcelain', '--', themePath], { cwd: root });
assert.equal(dirtyTheme.trim(), '', 'Theme inputs must match the recorded Git tree');
const { stdout: themeTree } = await run('git', ['rev-parse', `HEAD:${themePath}`], { cwd: root });
const { stdout: chromeVersion } = await run(chrome, ['--version']);
assert.match(chromeVersion, /\b156\.0\.8075\.0\b/);
const { stdout: wpInfo } = await docker(['exec', '-T', 'wordpress', 'php', '-r',
  'require "/var/www/html/wp-load.php"; echo json_encode(array("version"=>get_bloginfo("version"),"url"=>home_url("/"),"theme"=>get_stylesheet()));']);
const wordpress = JSON.parse(wpInfo);
assert.equal(wordpress.version, '7.1.2');
assert.equal(wordpress.theme, 'helix-wt');
assert.equal(wordpress.url.replace(/\/$/, ''), base.replace(/\/$/, ''), 'URL must be the selected Compose lab');
let installed = false;
await docker(['exec', '-T', 'wordpress', 'test', '!', '-e', target]);
try {
  for (const mode of ['baseline', 'patched']) {
    if (mode === 'patched') {
      await docker(['exec', '-T', 'wordpress', 'mkdir', '-p', path.posix.dirname(target)]);
      installed = true;
      await docker(['cp', source, `wordpress:${target}`]);
    }
    const browser = await chromium.launch({ executablePath: chrome, headless: true, args: ['--no-sandbox'] });
    try {
      for (const width of [390, 1440]) for (const variant of variants) {
        const page = await browser.newPage({ viewport: { width, height: 900 } });
        const requests = [];
        page.on('request', r => { if (r.resourceType() === 'image') requests.push(new URL(r.url()).pathname); });
        const url = new URL(base);
        url.searchParams.set('wt', `home_hero:${variant}`);
        await page.goto(url.href, { waitUntil: 'networkidle' });
        await page.evaluate(() => document.fonts.ready);
        const visible = await page.locator('.wt-home-hero').evaluateAll(els =>
          els.filter(e => e.checkVisibility()).map(e => e.className));
        assert.equal(visible.length, 1, `${mode} ${width} ${variant}: one selected hero`);
        assert.ok(visible[0].split(/\s+/).includes(`wt-home-hero--${variant}`));
        const screenshot = await page.screenshot({ path: path.join(output, `${mode}-${width}-${variant}.png`) });
        const row = { mode, width, variant, screenshotSha256: hash(screenshot), imageRequests: [...new Set(requests)] };
        // CSSで表示中でも横スクロールの画面外にあるlazy画像は未ロードで正常。
        // 実際にスクロールで露出させてから、読込成功を確認する。
        for (const img of await page.locator(`.wt-home-hero--${variant} img`).elementHandles()) {
          if (!await img.evaluate(e => e.checkVisibility())) continue;
          await img.scrollIntoViewIfNeeded();
          await page.waitForFunction(e => e.complete && e.naturalWidth > 0, img, { timeout: 10000 });
        }
        rows.push(row);
        await page.close();
      }
    } finally { await browser.close(); }
    console.log(`${mode}: 18 variant/viewport captures completed`);
    for (let trial = 1; trial <= 3; trial++) {
      const reportPath = path.join(output, `${mode}-${trial}.json`);
      await run(lighthouse, [base, '--chrome-flags=--headless --no-sandbox --disable-dev-shm-usage',
        '--only-categories=performance', '--output=json', `--output-path=${reportPath}`, '--quiet'],
      { cwd: root, env: process.env, maxBuffer: 4 * 1024 * 1024 });
      const report = JSON.parse(fs.readFileSync(reportPath));
      assert.equal(report.lighthouseVersion, '13.5.0');
      assert.match(report.environment.hostUserAgent, /HeadlessChrome\/156\./);
      assert.equal(report.runtimeError, undefined);
      assert.deepEqual(Object.entries(report.audits).filter(([, a]) => a.errorMessage), []);
      for (const metric of ['largest-contentful-paint', 'cumulative-layout-shift', 'total-blocking-time', 'total-byte-weight']) {
        assert.ok(Number.isFinite(report.audits[metric]?.numericValue), `${metric}: finite measurement required`);
      }
      performance.push({ mode, trial, lighthouseVersion: report.lighthouseVersion,
        browserVersion: report.environment.hostUserAgent.match(/HeadlessChrome\/[\d.]+/)[0],
        throttlingMethod: report.configSettings.throttlingMethod,
        lcpMs: report.audits['largest-contentful-paint'].numericValue,
        observedLcpMs: report.audits.metrics.details.items[0].observedLargestContentfulPaint,
        cls: report.audits['cumulative-layout-shift'].numericValue,
        tbtMs: report.audits['total-blocking-time'].numericValue,
        transferBytes: report.audits['total-byte-weight'].numericValue });
      console.log(`${mode}: Lighthouse trial ${trial} completed`);
    }
  }
  const comparisons = rows.filter(r => r.mode === 'patched').map(r => {
    const before = rows.find(b => b.mode === 'baseline' && b.width === r.width && b.variant === r.variant);
    assert.equal(r.screenshotSha256, before.screenshotSha256, `${r.width} ${r.variant}: screenshot unchanged`);
    return { width: r.width, variant: r.variant, screenshotUnchanged: true,
      screenshotSha256: r.screenshotSha256, requestsBefore: before.imageRequests.length, requestsAfter: r.imageRequests.length };
  });
  const report = { schema: 'wt-home-hero-image-loading-experiment.v1', completed: true,
    startedAt, finishedAt: new Date().toISOString(),
    scope: 'isolated WordPress HOME experiment; not acceptance of WT-NFR-PERF-03',
    wordpressVersion: wordpress.version, chromeVersion: chromeVersion.trim(), themeTree: themeTree.trim(),
    sourceDigests: Object.fromEntries(['hidden-hero-loading.php', 'verify.mjs', 'fixture.php'].map(p =>
      [path.relative(root, path.join(here, p)), hash(fs.readFileSync(path.join(here, p)))])),
    comparisons, performance };
  fs.writeFileSync(summaryPath, JSON.stringify(report, null, 2) + '\n');
  console.log(JSON.stringify({ comparisons: comparisons.length, performance }, null, 2));
} finally {
  if (installed) await docker(['exec', '-T', 'wordpress', 'rm', target]);
}
