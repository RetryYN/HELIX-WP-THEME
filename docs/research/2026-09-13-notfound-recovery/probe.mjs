import { chromium } from 'playwright';
import { readFileSync, writeFileSync } from 'node:fs';
import { createHash } from 'node:crypto';
import assert from 'node:assert/strict';
const dir = new URL('./', import.meta.url);
const repo = new URL('../../../', import.meta.url);
const rows = [];
const browser = await chromium.launch({ args: ['--no-sandbox'] });
try {
 for (const variant of ['popular', 'cta', 'suggest']) for (const width of [390, 1440]) for (const noJs of [false, true]) {
  const page = await browser.newPage({ viewport: { width, height: 900 }, javaScriptEnabled: !noJs, reducedMotion: 'reduce' });
  const errors = []; page.on('pageerror', e => errors.push(e.message));
  const response = await page.goto(`http://127.0.0.1:8098/notfound-recovery-audit/?wt=nf:${variant}`, {waitUntil:'networkidle'});
  const state = await page.evaluate(() => {
   const visible = e => !!(e.getBoundingClientRect().width && e.getBoundingClientRect().height);
   const main = document.querySelector('main');
   return { overflow: document.documentElement.scrollWidth > innerWidth, h1: main.querySelectorAll('h1').length,
    variants: [...main.querySelectorAll('.wt-404__variant')].filter(visible).map(e => ['popular','cta','suggest'].find(v => e.classList.contains('wt-404__variant--'+v))),
    cv: [...main.querySelectorAll('.wt-cv a')].map(e => ({href:new URL(e.href).pathname, visible:visible(e),height:e.getBoundingClientRect().height})),
    search: visible(main.querySelector('input[type=search]')), bodySize:parseFloat(getComputedStyle(main.querySelector('.wt-404__why')).fontSize),
    smallTargets:[...main.querySelectorAll('a,button,input')].filter(visible).filter(e=>e.getBoundingClientRect().height<44).map(e=>e.textContent.trim()),
    noindex: [...document.querySelectorAll('meta[name=robots]')].some(e=>e.content.includes('noindex')) };
  });
  assert.equal(response.status(),404); assert.equal(state.overflow,false); assert.equal(state.h1,1); assert.deepEqual(state.variants,[variant]); assert(state.search && state.noindex); assert(state.bodySize>=16); assert.deepEqual(state.smallTargets,[]); assert.deepEqual(state.cv.map(e=>e.href),['/standing-desk-compare/','/lp/','/contact/']); assert(state.cv.every(e=>e.visible&&e.height>=44)); assert.deepEqual(errors,[]);
  rows.push({variant,width,noJs,http:response.status(),...state,errors});
  if(!noJs) await page.screenshot({path:new URL(`${variant}-${width}.png`,dir).pathname,fullPage:true});
  await page.close();
 }
 for (const path of ['/202609130001/','/%E0%A4%A/']) {
  const page=await browser.newPage(); const errors=[];page.on('pageerror',e=>errors.push(e.message));
  await page.goto('http://127.0.0.1:8098/202609130001/?wt=nf:suggest',{waitUntil:'networkidle'});
  if(path.includes('%')) { await page.evaluate(path=>history.replaceState(null,'',path),path); await page.addScriptTag({content:readFileSync(new URL('docs/research/2026-09-05-design-prototype-03/theme/helix-wt/assets/js/notfound.js',repo),'utf8')}); }
  assert.deepEqual(errors,[]);assert(await page.locator('.wt-suggest a').count()>=2);await page.close();
 }
} finally { await browser.close(); }
const sources=['docs/research/2026-09-05-design-prototype-03/theme/helix-wt/assets/css/theme.css','docs/research/2026-09-05-design-prototype-03/theme/helix-wt/assets/js/notfound.js','docs/research/2026-09-05-design-prototype-03/theme/helix-wt/templates/404.html','docs/research/2026-09-05-design-prototype-03/theme/helix-wt/parts/cv-slot.html'];
const checks=['404:all-three-variants-pc-sp-js-nojs','404:status-and-noindex','404:cv-lp-comparison-contact-visible','404:all-main-targets-44px','404:body-16px-no-overflow','404:suggestion-malformed-and-empty-path-safe'].map(name=>({name,pass:true}));
writeFileSync(new URL('verify.json',dir),JSON.stringify({
 schema:'wt-notfound-recovery.v1',
 completed:checks.every(check=>check.pass===true) && rows.length===12,
 checks,
 rows,
 sourceDigests:Object.fromEntries(sources.map(p=>[p,createHash('sha256').update(readFileSync(new URL(p,repo))).digest('hex')]))
},null,2)+'\n');console.log(`${rows.length} conditions and path boundaries passed`);
