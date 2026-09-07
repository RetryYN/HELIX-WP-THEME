import { chromium } from 'playwright';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { createHash } from 'node:crypto';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const base = process.env.WTCF_BASE_URL || 'http://127.0.0.1:8098';
if (!['127.0.0.1', 'localhost'].includes(new URL(base).hostname)) throw Error('Dedicated loopback lab required');
const out = path.join(root, 'docs/research/2026-09-08-content-faces/results/learning');
fs.mkdirSync(out, { recursive: true });
const sources = ['scripts/verify-learning-faces.mjs', 'docs/research/2026-09-08-content-faces/plugin/learning.php',
  'docs/research/2026-09-08-content-faces/plugin/manifest.json', 'docs/research/2026-09-08-content-faces/seed-learning.php',
  'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/inc/learning.php',
  'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/assets/css/content-faces.css'];
const sourceDigests = Object.fromEntries(sources.map(p => [p, createHash('sha256').update(fs.readFileSync(path.join(root, p))).digest('hex')]));
const browser = await chromium.launch();
const rows = [], shots = [];
let completed = false;
const check = (name, pass) => { rows.push({ name, pass: Boolean(pass) }); assert.ok(pass, name); };
try {
  for (const dev of ['pc', 'sp']) for (const js of [true, false]) {
    const context = await browser.newContext({ viewport: dev === 'pc' ? { width: 1440, height: 1000 } : { width: 375, height: 812 }, javaScriptEnabled: js });
    const page = await context.newPage();
    const suffix = `${dev}:js-${js}`;
    await page.goto(base + '/learn/');
    check(`archive:count:${suffix}`, (await page.locator('.wtlearn-results').innerText()).includes('7件'));
    check(`archive:first-page:${suffix}`, await page.locator('.wtcf-list article').count() === 6);
    await page.locator('.wtlearn-pagination').getByRole('link', { name: '次のページ' }).click();
    check(`archive:second-page:${suffix}`, await page.locator('.wtcf-list article').count() === 1);
    await page.locator('.wtlearn-pagination').getByRole('link', { name: '前のページ' }).click();
    await page.locator('.wtcf-list').getByRole('link', { name: '伝わるページのつくり方', exact: true }).click();
    const course = new URL(page.url()).pathname;
    const lessons = await page.locator('[aria-label="講座のレッスン"] a').evaluateAll(links => links.map(a => a.getAttribute('href')));
    check(`course:three-lessons:${suffix}`, lessons.length === 3);
    await page.goto(lessons[0]);
    check(`lesson:first-boundary:${suffix}`, await page.locator('.wtlearn-previous').count() === 0 && await page.locator('.wtlearn-next').count() === 1);
    check(`lesson:parent:${suffix}`, await page.locator('.wtlearn-breadcrumbs a').last().getAttribute('href') === base + course);
    check(`lesson:current:${suffix}`, (await page.locator('[aria-label="講座のレッスン"] [aria-current="page"]').innerText()).startsWith('01'));
    await page.locator('.wtlearn-toc a').last().click();
    check(`lesson:toc:${suffix}`, new URL(page.url()).hash === '#learn-section-1' && await page.locator('#learn-section-1').isVisible());
    await page.locator('.wtlearn-next').click();
    check(`lesson:middle:${suffix}`, await page.locator('.wtlearn-previous').count() === 1 && await page.locator('.wtlearn-next').count() === 1);
    await page.locator('.wtlearn-next').click();
    check(`lesson:last-boundary:${suffix}`, await page.locator('.wtlearn-next').count() === 0 && await page.locator('.wtlearn-previous').count() === 1);
    await page.goto(base + '/learn/glossary/');
    await page.locator('.wtlearn-toc').getByRole('link', { name: '導線', exact: true }).click();
    check(`glossary:term-target:${suffix}`, new URL(page.url()).hash === '#learn-section-2');
    await page.goto(base + '/learn/help/');
    const details = page.locator('details').first();
    await details.locator('summary').focus(); await page.keyboard.press('Enter');
    check(`help:open:${suffix}`, await details.evaluate(node => node.open));
    await page.keyboard.press('Space');
    check(`help:close:${suffix}`, !(await details.evaluate(node => node.open)));
    for (const [query, found] of [['読み手', true], ['アクセシビリティ', true], ['存在しない検索語', false], ['購入者向けの検証本文', false], ['未公開レッスンの内部確認語', false], ['no-match"><img src=x onerror=alert(1)>', false]]) {
      await page.locator('#learn-query').fill(query);
      await page.locator('.wtlearn-search button').click();
      check(`search:${query.includes('<img') ? 'escaped' : query}:${suffix}`, found ? await page.locator('.wtcf-list article').count() > 0 : await page.locator('.wtlearn-empty').count() === 1);
      check(`search:noindex:${suffix}:${query.length}`, (await page.locator('meta[name="robots"]').getAttribute('content')).includes('noindex'));
      check(`search:no-injected-image:${suffix}:${query.length}`, await page.locator('.wtlearn img').count() === 0);
    }
    // Recovery must be exercised by following links and submitting the form.
    await page.goto(base + '/learn/?learn_q=not-found&learn_page=999999999');
    check(`recovery:zero-no-pagination:${suffix}`, await page.locator('.wtlearn-empty').count() === 1 && await page.locator('[aria-label="一覧のページ送り"] a').count() === 0);
    await page.locator('.wtlearn-empty a').click();
    check(`recovery:zero-to-list:${suffix}`, new URL(page.url()).search === '' && await page.locator('.wtcf-list article').count() === 6);
    for (const number of ['999999999', '999999999999999999999999999999999999']) {
      await page.goto(base + '/learn/?learn_page=' + number);
      check(`recovery:clamped-count:${number}:${suffix}`, (await page.locator('.wtlearn-results').innerText()).includes('7件') && await page.locator('.wtcf-list article').count() === 1);
      check(`recovery:clamped-last:${number}:${suffix}`, await page.locator('[aria-current="page"]').innerText() === '2 / 2 ページ' && await page.locator('.wtlearn-page-adjustment').count() === 1);
      await page.getByRole('link', { name: '前のページ', exact: true }).click();
      check(`recovery:last-to-first:${number}:${suffix}`, await page.locator('.wtcf-list article').count() === 6);
    }
    for (const invalid of ['0', '-5', 'wrong', '1.5', '1e8', '[]']) {
      const query = invalid === '[]' ? 'learn_page[]=2' : 'learn_page=' + invalid;
      await page.goto(base + '/learn/?' + query);
      check(`recovery:invalid:${invalid}:${suffix}`, await page.locator('.wtcf-list article').count() === 6 && await page.locator('[aria-current="page"]').innerText() === '1 / 2 ページ');
    }
    await page.goto(base + '/learn/?learn_q=' + encodeURIComponent('読み手') + '&learn_page=999999999');
    check(`recovery:preserve-query:${suffix}`, await page.locator('#learn-query').inputValue() === '読み手' && await page.locator('.wtcf-list article').count() > 0 && (await page.locator('.wtlearn-results').innerText()).includes('「読み手」'));
    await page.locator('#learn-query').fill('not-found'); await page.locator('.wtlearn-search button').click();
    check(`recovery:query-resets-page:${suffix}`, !new URL(page.url()).searchParams.has('learn_page') && await page.locator('.wtlearn-empty').count() === 1);
    const examples = [['learning-index', '/learn/'], ['course', course], ['lesson', new URL(lessons[1]).pathname], ['glossary', '/learn/glossary/'], ['help', '/learn/help/'], ['learning-empty', '/learn/?learn_q=not-found'], ['learning-recovered', '/learn/?learn_page=999999999']];
    for (const [face, route] of examples) {
      await page.goto(base + route);
      check(`reflow:${face}:${suffix}`, await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
      check(`heading:${face}:${suffix}`, await page.locator('h1').count() === 1);
      if (js) {
        const file = `${face}-${dev}.jpg`;
        await page.screenshot({ path: path.join(out, file), fullPage: true, type: 'jpeg', quality: 86 });
        shots.push({ file, face: 'learning', part: face, design: 'editorial', role: 'anonymous', dev, route, requirement_ids: ['WT-FR-LEARN-01', 'WT-FR-PAGE-02', 'WT-FR-PARTS-03'] });
      }
    }
    await page.goto(base + '/learn/help/'); await page.keyboard.press('Tab');
    check(`skip-link:${suffix}`, await page.evaluate(() => document.activeElement.getAttribute('href') === '#learning-main'));
    await page.keyboard.press('Enter');
    check(`skip-target-focus:${suffix}`, await page.evaluate(() => document.activeElement.id === 'learning-main'));
    await context.close();
  }
  completed = true;
} finally {
  await browser.close();
  fs.writeFileSync(path.join(out, 'verify.json'), JSON.stringify({ schema: 'wt-learning-verification.v1', completed, sourceDigests,
    scope: 'Learning hierarchy, sequence, glossary, help and public search in the dedicated WordPress PoC',
    pass: rows.filter(r => r.pass).length, fail: rows.filter(r => !r.pass).length, rows, shots }, null, 2) + '\n');
}
console.log(`learning: ${rows.length} checks passed; ${shots.length} screenshots`);
