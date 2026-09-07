import { chromium } from 'playwright';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import assert from 'node:assert/strict';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const out = path.join(root, 'docs/research/2026-09-08-content-faces/results');
const base = process.env.WTCF_BASE_URL || 'http://127.0.0.1:8098';
if (!['127.0.0.1', 'localhost'].includes(new URL(base).hostname)) throw Error('Dedicated loopback lab required');
if (!process.env.WTCF_LAB_CREDENTIALS) throw Error('WTCF_LAB_CREDENTIALS must point to the external lab credentials file');
const credentials = JSON.parse(fs.readFileSync(process.env.WTCF_LAB_CREDENTIALS, 'utf8'));
fs.mkdirSync(out, { recursive: true });
const browser = await chromium.launch({ headless: true });
const rows = [], shots = [];
let completed = false;
const protectedText = 'この段落は購入者向けの検証本文です';
const routes = { oneoff: '/library/decision-design/', subscription: '/library/monthly-notes/', interview: '/voices/making-room/', blp: '/guides/before-redesign/', lp: '/start/editorial-session/' };
const check = (name, condition, detail = {}) => { rows.push({ name, pass: Boolean(condition), ...detail }); assert.ok(condition, name); };
async function contextFor(role, options = {}) {
  const context = await browser.newContext(options);
  if (role !== 'anonymous') {
    await context.request.get(`${base}/wp-login.php`);
    const response = await context.request.post(`${base}/wp-login.php`, { form: { log: `lab_${role}`, pwd: credentials[role], 'wp-submit': 'Log In', redirect_to: base, testcookie: '1' } });
    check(`login:${role}`, response.ok() && (await context.cookies()).some(c => c.name.startsWith('wordpress_logged_in_')));
  }
  return context;
}
try {
  for (const role of ['anonymous', 'none', 'oneoff', 'subscription', 'expired', 'other_product']) {
    const context = await contextFor(role);
    for (const kind of ['oneoff', 'subscription']) {
      const expected = role === kind;
      for (const view of ['sales', 'preview', 'body']) {
        const response = await context.request.get(`${base}${routes[kind]}?view=${view}`);
        const html = await response.text();
        check(`access:${role}:${kind}:${view}`, response.status() === 200 && html.includes(protectedText) === (expected && view !== 'sales'));
        check(`cache:${role}:${kind}:${view}`, (response.headers()['cache-control'] || '').includes('no-store'));
      }
    }
    if (role === 'anonymous') {
      for (const route of ['/wp-json/wp/v2/wt_paid', '/?s=実践編', '/?feed=rss2&post_type=wt_paid', '/library/', `${routes.oneoff}?view=body&wtcf_access=granted&ownership=off`]) {
        const response = await context.request.get(base + route);
        check(`public-surface:${route}`, response.ok() && !(await response.text()).includes(protectedText));
      }
      const pending = await context.request.get(`${base}/voices/pending-confirmation/`);
      check('unconfirmed-interview:not-public', pending.status() === 404);
      const page = await context.newPage();
      await page.goto(base + '/voices-in-context/');
      check('interview:embedded-reference', await page.locator('.wtcf-reference a').getAttribute('href') === base + routes.interview);
      await page.locator('.wtcf-reference a').click();
      check('interview:people-speech', await page.locator('[data-person]').count() === 2 && await page.locator('[data-speaker]').count() === 3);
      check('interview:confirmation', await page.locator('.wtcf-confirmed').count() === 1);
      await page.goto(base + routes.blp);
      check('blp:reasoning', await page.locator('.wtcf-story-section').count() === 3 && (await page.locator('main').innerText()).includes('向かない場合'));
      await page.locator('.wtcf-next a').click();
      check('blp:lp-target', new URL(page.url()).pathname === routes.lp && await page.locator('#apply form').count() === 1);
      for (const ownership of ['inherit', 'own', 'off']) {
        await page.goto(`${base}${routes.blp}?ownership=${ownership}`);
        check(`blp:ownership:${ownership}`, await page.locator('.wtcf-header').count() === (ownership === 'off' ? 0 : 1) && (ownership !== 'own' || (await page.locator('.wtcf-brand').innerText()).includes('FIELD NOTES')));
      }
    }
    await context.close();
  }
  for (const dev of ['pc', 'sp']) {
    for (const js of [true, false]) {
      const context = await contextFor('anonymous', { viewport: dev === 'pc' ? { width: 1440, height: 1000 } : { width: 375, height: 812 }, javaScriptEnabled: js });
      const page = await context.newPage();
      for (const [face, route] of Object.entries(routes)) {
        for (const design of ['standard', 'editorial']) {
          await page.goto(`${base}${route}?design=${design}`);
          check(`reflow:${face}:${design}:${dev}:js-${js}`, await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth));
          check(`heading:${face}:${design}:${dev}:js-${js}`, await page.locator('h1').count() === 1);
          if (js) {
            const file = `${face}-${design}-${dev}.jpg`;
            await page.screenshot({ path: path.join(out, file), fullPage: true, type: 'jpeg', quality: 86 });
            shots.push({ file, face, design, dev, role: 'anonymous', route, requirement_ids: [face === 'interview' ? 'WT-FR-INTERVIEW-01' : face === 'blp' ? 'WT-FR-BLP-01' : face === 'lp' ? 'WT-FR-LP-01' : 'WT-FR-PAID-01'] });
          }
        }
      }
      await context.close();
    }
    const auxiliary = await contextFor('anonymous', { viewport: dev === 'pc' ? { width: 1440, height: 1000 } : { width: 375, height: 812 } });
    const auxiliaryPage = await auxiliary.newPage();
    for (const [face, view, route] of [['paid', 'list', '/library/'], ['oneoff', 'sales', routes.oneoff + '?view=sales'], ['subscription', 'sales', routes.subscription + '?view=sales']]) {
      await auxiliaryPage.goto(base + route);
      check(`display-purpose:${face}:${view}:${dev}`, await auxiliaryPage.locator('h1').count() === 1 && !(await auxiliaryPage.locator('main').innerText()).includes(protectedText));
      const file = `${face}-${view}-${dev}.jpg`;
      await auxiliaryPage.screenshot({ path: path.join(out, file), fullPage: true, type: 'jpeg', quality: 86 });
      shots.push({ file, face, view, design: 'editorial', dev, role: 'anonymous', route, requirement_ids: ['WT-FR-PAID-01'] });
    }
    await auxiliary.close();
    for (const role of ['oneoff', 'subscription']) {
      const context = await contextFor(role, { viewport: dev === 'pc' ? { width: 1440, height: 1000 } : { width: 375, height: 812 } });
      const page = await context.newPage(); await page.goto(`${base}${routes[role]}?view=body`);
      check(`granted-render:${role}:${dev}`, await page.locator('.wtcf-fulltext').count() === 1);
      const file = `${role}-granted-${dev}.jpg`; await page.screenshot({ path: path.join(out, file), fullPage: true, type: 'jpeg', quality: 86 });
      shots.push({ file, face: role, design: 'editorial', dev, role, route: routes[role] + '?view=body', requirement_ids: ['WT-FR-PAID-01'] });
      await context.close();
    }
  }
  completed = true;
} finally {
  await browser.close();
  fs.writeFileSync(path.join(out, 'verify.json'), JSON.stringify({ schema: 'wt-content-faces-verification.v1', completed, scope: 'Dedicated WordPress PoC with local entitlement fixtures; not production service integration', pass: rows.filter(r => r.pass).length, fail: rows.filter(r => !r.pass).length, rows, shots }, null, 2) + '\n');
}
console.log(`content faces: ${rows.length} checks passed, ${shots.length} screenshots`);
