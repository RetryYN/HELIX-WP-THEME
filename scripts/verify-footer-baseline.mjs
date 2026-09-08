import { chromium } from 'playwright';
import fs from 'node:fs';
import { createHash } from 'node:crypto';
import { execFileSync } from 'node:child_process';
const root = new URL('../', import.meta.url);
const blog = execFileSync('docker', ['exec', 'helix-content-wp', 'php', '-r', 'require "/var/www/html/wp-load.php"; echo get_option("blogname");'], { encoding: 'utf8' });
if (blog !== 'HELIX Content Lab') throw Error('Dedicated lab required');
const sources = ['scripts/verify-footer-baseline.mjs', ...['parts/footer.html', 'functions.php', 'assets/js/footer.js', 'assets/css/theme.css'].map(p => 'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/' + p)];
const sourceDigests = Object.fromEntries(sources.map(p => [p, createHash('sha256').update(fs.readFileSync(new URL(p, root))).digest('hex')]));
const browser = await chromium.launch();
const rows = []; let completed = false;
try {
  for (const width of [1440, 375]) for (const js of [true, false]) for (const extra of ['none', 'sites']) {
    const context = await browser.newContext({ viewport: { width, height: 900 }, javaScriptEnabled: js });
    const page = await context.newPage();
    await page.goto('http://127.0.0.1:8098/?wt=footer_extra:' + extra + ',footer_layout:sitemap');
    const actual = await page.locator('.wt-footer').evaluate(footer => {
      const sites = footer.querySelector('.wt-footer-extra-slot--sites');
      return { sitesNodes: sites ? 1 : 0, sitesVisible: !!sites?.getBoundingClientRect().height,
        sitesLinks: [...(sites?.querySelectorAll('a') || [])].map(a => ({ text: a.textContent.trim(), href: a.getAttribute('href') })),
        extraHeight: footer.querySelector('.wt-footer__extra')?.getBoundingClientRect().height,
        groups: footer.querySelectorAll('.wt-footer__sitemap details').length };
    });
    const label = `${width}:js-${js}:extra-${extra}`;
    rows.push({ name: 'selected-visibility:' + label, pass: actual.sitesVisible === (extra === 'sites'), actual });
    if (extra === 'none') rows.push({ name: 'disabled-sites-omitted:' + label, pass: actual.sitesNodes === 0 });
    if (extra === 'sites') rows.push({ name: 'related-links-distinct-destinations:' + label, pass: new Set(actual.sitesLinks.map(a => a.href)).size === actual.sitesLinks.length });
    await context.close();
  }
  completed = true;
} finally {
  await browser.close();
  fs.writeFileSync(new URL('docs/research/2026-09-09-footer-data/baseline.json', root), JSON.stringify({ completed, sourceDigests, rows, limitation: '現行fixtureのoff/onとリンク先を観測。空データ保存の受入試験ではない。専用labへのGETのみで保存操作なし。' }, null, 2) + '\n');
}
console.log(JSON.stringify({ completed, checks: rows.length, failed: rows.filter(r => !r.pass).length }));
