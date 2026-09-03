/**
 * デザイン試作スクリーンショット生成。
 * 前提: 子テーマ wt-proto が有効、set-variation.php が wt-proto 直下にある、Playwright が NODE_PATH 経由で解決できる。
 * 環境変数:
 *   WT_THEME_DIR : docker compose を実行するディレクトリ (既定: このスクリプトの 3 階層上 = テーマリポ root)
 *   WT_OUT_DIR   : 出力先 (既定: ./out)
 *   WT_BASE_URL  : WP の URL (既定: http://localhost:8086)
 *   WT_VARIATIONS: カンマ区切りで対象 variation を絞る (任意)
 */
const { chromium } = require('playwright');
const { execFileSync } = require('child_process');
const fs = require('fs');
const path = require('path');

const THEME_DIR = process.env.WT_THEME_DIR || path.resolve(__dirname, '../../..');
const OUT = process.env.WT_OUT_DIR || path.resolve(process.cwd(), 'out');
const BASE = process.env.WT_BASE_URL || 'http://localhost:8086';
const PAGES = {
  article: '/design-proto-article/',
  lp: '/design-proto-lp/',
  home: '/',
};
const WIDTHS = { sp: 390, pc: 1280 };
const EXISTING = ['light', 'dark', 'business', 'depth', 'editorial', 'mono', 'night-contrast', 'vivid', 'warm'];
const PROTO = ['compare', 'corporate', 'service-lp'];
let VARIATIONS = [...EXISTING, ...PROTO];
if (process.env.WT_VARIATIONS) VARIATIONS = process.env.WT_VARIATIONS.split(',');

// 語彙ごとの部分スクリーンショット (記事ページ, SP 幅, 1 variation)
const VOCAB_VARIATION = process.env.WT_VOCAB_VARIATION || 'compare';
const VOCAB = {
  box: '.is-style-wt-box-info',
  'box-warn': '.is-style-wt-box-warn',
  'box-point': '.is-style-wt-box-point',
  buttons: '.wt-buttons-3',
  'link-card': '.is-style-wt-link-card',
  steps: '.wt-steps',
  table: '.wt-compare-table',
  faq: '.wt-faq',
  review: '.is-style-wt-review',
  'product-card': '.wp-block-columns',
  cta: '.is-style-wt-cta-bundle',
  toc: '.is-style-wt-toc',
  'pr-notice': '.wt-pr-notice',
};


// テーマ同梱の Cookie 同意バナー (#agent-neo-consent-banner) は固定表示でスクリーンショットを覆うため、
// 「拒否する」を押して閉じる (実ユーザー操作と同じ経路。context 内では localStorage で保持される)。
async function dismissConsent(page) {
  const banner = page.locator('#agent-neo-consent-banner');
  if (await banner.count() && await banner.isVisible()) {
    const btn = banner.getByRole('button', { name: /拒否/ });
    if (await btn.count()) { await btn.first().click(); await banner.waitFor({ state: 'hidden', timeout: 3000 }).catch(() => {}); }
  }
}

function setVariation(slug) {
  const out = execFileSync('docker', ['compose', 'run', '--rm', '-T', 'wpcli', '--user=admin', 'eval-file',
    '/var/www/html/wp-content/themes/wt-proto/set-variation.php', slug], { cwd: THEME_DIR, stdio: ['ignore', 'pipe', 'ignore'] }).toString();
  const line = out.trim().split('\n').pop();
  return JSON.parse(line);
}

function normHex(c) {
  // rgb(a, b, c) -> #aabbcc
  const m = c.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
  if (!m) return c.trim().toLowerCase();
  return '#' + [m[1], m[2], m[3]].map((n) => Number(n).toString(16).padStart(2, '0')).join('');
}

(async () => {
  fs.mkdirSync(OUT, { recursive: true });
  const browser = await chromium.launch();
  const index = { generated_at: new Date().toISOString(), base_url: BASE, shots: [], problems: [] };

  for (const v of VARIATIONS) {
    const info = setVariation(v);
    const pal = info.palette;
    console.log('variation', v, JSON.stringify(pal));
    for (const [wname, width] of Object.entries(WIDTHS)) {
      const ctx = await browser.newContext({ viewport: { width, height: 900 }, deviceScaleFactor: 1, locale: 'ja-JP' });
      const page = await ctx.newPage();
      const consoleErrors = [];
      page.on('pageerror', (e) => consoleErrors.push(String(e)));
      for (const [pname, url] of Object.entries(PAGES)) {
        await page.goto(BASE + url, { waitUntil: 'networkidle' });
        await dismissConsent(page);
        // variation が効いているかを計算済み CSS 変数で確認
        const computed = await page.evaluate(() => {
          const cs = getComputedStyle(document.documentElement);
          const body = getComputedStyle(document.body);
          const h = document.querySelector('h1, h2');
          return {
            accent: cs.getPropertyValue('--wp--preset--color--accent').trim(),
            primary: cs.getPropertyValue('--wp--preset--color--primary').trim(),
            background: cs.getPropertyValue('--wp--preset--color--background').trim(),
            bodyBg: body.backgroundColor,
            bodyFont: body.fontFamily,
            headingFont: h ? getComputedStyle(h).fontFamily : null,
            headingColor: h ? getComputedStyle(h).color : null,
          };
        });
        const ok = pal.accent && computed.accent.toLowerCase() === pal.accent.toLowerCase()
          && computed.primary.toLowerCase() === pal.primary.toLowerCase();
        if (!ok) {
          index.problems.push({ page: pname, variation: v, width, reason: 'palette mismatch', expected: pal, computed });
          console.log('  MISMATCH', pname, wname, computed.accent, pal.accent);
        }
        const file = `${pname}-${v}-${wname}.png`;
        await page.screenshot({ path: path.join(OUT, file), fullPage: true });
        index.shots.push({ page: pname, variation: v, width, path: file, verified: ok,
          colors: { primary: pal.primary, secondary: pal.secondary, accent: pal.accent, 'accent-aa': pal['accent-aa'], background: pal.background, foreground: pal.foreground, 'footer-bg': pal['footer-bg'], muted: pal.muted },
          computed: { body_bg: normHex(computed.bodyBg), body_font: computed.bodyFont, heading_font: computed.headingFont, heading_color: normHex(computed.headingColor || '') } });
        console.log('  shot', file, ok ? 'ok' : 'NG');
      }
      if (consoleErrors.length) index.problems.push({ variation: v, width, reason: 'pageerror', errors: consoleErrors });
      await ctx.close();
    }
  }

  // 語彙ごとの部分スクリーンショット
  setVariation(VOCAB_VARIATION);
  const ctx = await browser.newContext({ viewport: { width: 390, height: 900 }, deviceScaleFactor: 2, locale: 'ja-JP' });
  const page = await ctx.newPage();
  await page.goto(BASE + PAGES.article, { waitUntil: 'networkidle' });
  await dismissConsent(page);
  for (const [name, sel] of Object.entries(VOCAB)) {
    const el = page.locator(sel).first();
    if ((await el.count()) === 0) { index.problems.push({ vocab: name, reason: 'selector not found', sel }); continue; }
    if (name === 'faq') { for (const d of await page.locator('details.wt-faq').all()) await d.evaluate((n) => (n.open = true)); }
    await el.scrollIntoViewIfNeeded();
    const file = `vocab-${name}-sp.png`;
    await el.screenshot({ path: path.join(OUT, file) });
    index.shots.push({ page: 'article', variation: VOCAB_VARIATION, width: 390, path: file, vocab: name, selector: sel });
    console.log('  vocab', file);
  }
  await ctx.close();
  await browser.close();
  fs.writeFileSync(path.join(OUT, 'index.json'), JSON.stringify(index, null, 2));
  console.log('done', index.shots.length, 'shots;', index.problems.length, 'problems');
})().catch((e) => { console.error(e); process.exit(1); });
