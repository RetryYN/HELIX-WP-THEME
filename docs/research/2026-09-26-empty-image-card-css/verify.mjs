import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { createHash } from 'node:crypto';
import { chromium } from 'playwright';

const base = process.env.HELIX_ARTICLE_BASE_URL || 'http://127.0.0.1:18133';
const directory = 'docs/research/2026-09-26-empty-image-card-css';
const evidence = 'local-evidence/issue345';
const theme = 'docs/research/2026-09-05-design-prototype-03/theme/helix-wt';
const sources = [`${theme}/assets/css/theme.css`, `${theme}/templates/category.html`, `${theme}/templates/archive.html`, `${directory}/verify.mjs`, `${directory}/fixture.php`];
const rows = [];
const browser = await chromium.launch();
fs.mkdirSync(evidence, { recursive: true });
try {
  for (const width of [390, 599, 600, 768, 1440]) {
    const page = await browser.newPage({ viewport: { width, height: 1000 } });
    for (const [name, route] of Object.entries({category:'/category/news-releases/',tag:'/tag/card-fixture/',date:'/2026/',author:'/author/local-review/'})) {
      const response = await page.goto(base + route, { waitUntil: 'networkidle' });
      assert.equal(response.status(), 200);
      assert.equal(await page.locator('meta[name="generator"]').getAttribute('content'), 'WordPress 7.1.2');
      const cssUrl = await page.locator('link[href*="/helix-wt/assets/css/theme.css"]').getAttribute('href');
      const servedCss = await page.request.get(cssUrl);
      assert.equal(servedCss.status(), 200);
      assert.equal(createHash('sha256').update(await servedCss.body()).digest('hex'), createHash('sha256').update(fs.readFileSync(`${theme}/assets/css/theme.css`)).digest('hex'), 'WordPress must serve this checkout stylesheet');
      const facts = await page.evaluate(() => ({
        overflow: document.documentElement.scrollWidth > innerWidth,
        cards: [...document.querySelectorAll('.wt-cat-primary-list .wt-cat-card')].map(card => {
          const style = getComputedStyle(card), title = card.querySelector('.wp-block-post-title'), date = card.querySelector('.wp-block-post-date');
          return { image: !!card.querySelector(':scope > .wp-block-post-featured-image'), columns: style.gridTemplateColumns, width: card.getBoundingClientRect().width, titleWidth: title.getBoundingClientRect().width, directDate: date.parentElement === card, dateArea: getComputedStyle(date).gridArea, titleArea: getComputedStyle(title).gridArea };
        }),
      }));
      assert.equal(facts.overflow, false);
      assert(facts.cards.some(card => card.image), 'positive image control required');
      assert(facts.cards.some(card => !card.image), 'empty image fixture required');
      for (const card of facts.cards) {
        if (width <= 599 && !card.image) {
          assert.equal(card.columns.trim().split(/\s+/).length, 1);
          assert(Math.abs(card.width - card.titleWidth - 26) < 1, 'title spans card minus padding and border');
          if (card.directDate) assert.equal(card.dateArea.split(' / ')[0], 'meta');
        } else if (width <= 599) {
          assert.equal(card.columns.split(' ')[0], '104px');
        } else {
          assert.equal(card.columns, 'none');
        }
      }
      if ([390,768,1440].includes(width)) await page.screenshot({path:path.join(evidence, `verified-${name}-${width}.png`)});
      rows.push({ name: `${name}:${width}:image-and-empty-card-layout`, pass: true, viewport: { width, height: 1000 }, overflow: facts.overflow, imageCards: facts.cards.filter(card => card.image).length, emptyCards: facts.cards.filter(card => !card.image).length, emptyTitleWidths: [...new Set(facts.cards.filter(card => !card.image).map(card => card.titleWidth))] });
    }
    await page.close();
  }
} finally {
  await browser.close();
}
assert(!fs.readFileSync(`${theme}/templates/category.html`, 'utf8').includes('<style>'));
rows.push({name:'category-template-has-no-inline-style',pass:true});
const sourceDigests = Object.fromEntries(sources.map(file=>[file,createHash('sha256').update(fs.readFileSync(file)).digest('hex')]));
fs.writeFileSync(`${directory}/verification.json`, JSON.stringify({schema:'wt-empty-image-card-css.v1',completed:true,sourceDigests,rows},null,2)+'\n');
console.log(`${rows.length} checks passed`);
