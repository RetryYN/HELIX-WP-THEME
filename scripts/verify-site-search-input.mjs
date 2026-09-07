import { chromium } from 'playwright';
import fs from 'node:fs';import path from 'node:path';import { fileURLToPath } from 'node:url';import { createHash } from 'node:crypto';
const root=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'..');
const sourceFiles=['docs/research/2026-09-05-design-prototype-03/theme/helix-wt/inc/search.php','scripts/verify-site-search-input.mjs','docs/research/2026-09-08-content-faces/plugin/search.php','docs/research/2026-09-05-design-prototype-03/theme/helix-wt/templates/search.html','docs/research/2026-09-05-design-prototype-03/theme/helix-wt/assets/css/theme.css'];
const digests=()=>Object.fromEntries(sourceFiles.map(f=>[f,createHash('sha256').update(fs.readFileSync(path.join(root,f))).digest('hex')]));
const sourceDigests=digests(), rows=[];const check=(name,pass)=>rows.push({name,pass:!!pass});
const query='判断', marker='<img src=x onerror="window.searchInjected=true">';
const cases=[['query-array','s%5B%5D=test'],['page-array','s='+encodeURIComponent(query)+'&paged%5B%5D=999'],['page-negative','s='+encodeURIComponent(query)+'&paged=-4'],['page-text','s='+encodeURIComponent(query)+'&paged=invalid'],['page-huge','s='+encodeURIComponent(query)+'&paged=999999999999999999999999999999'],['html','s='+encodeURIComponent(marker)],['long-word','s='+('longsearch'.repeat(80))]];
const browser=await chromium.launch();let completed=false;
try {
 for(const [device,width]of[['pc',1440],['sp',375]])for(const js of[true,false]){
  const context=await browser.newContext({viewport:{width,height:900},javaScriptEnabled:js});const page=await context.newPage();
  await page.goto('http://127.0.0.1:8098/?s='+encodeURIComponent(query));
  const expectedCount=await page.locator('main .wp-block-query-total').innerText();
  for(const [name,params]of cases){
   const label=`${name}:${device}:js-${js}`;const response=await page.goto('http://127.0.0.1:8098/?'+params);
   check(`response:${label}`,response.status()===200);
   const input=page.locator('main input[type=search]');check(`search-available:${label}`,await input.count()===1);
   check(`reflow:${label}`,await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));
   check(`no-injection:${label}`,await page.evaluate(()=>!window.searchInjected)&&await page.locator('main img[onerror]').count()===0);
   if(name.startsWith('page-'))check(`page-count-preserved:${label}`,await page.locator('main .wp-block-query-total').count()===1&&await page.locator('main .wp-block-query-total').innerText()===expectedCount&&await page.locator('main .wp-block-post-title a').count()>0);
   if(await input.count()===1){
    let focused=false;for(let step=0;step<30;step++){await page.keyboard.press('Tab');focused=await input.evaluate(e=>e===document.activeElement);if(focused)break;}
    check(`keyboard-input-reachable:${label}`,focused);if(!focused)continue;
    check(`keyboard-focus-visible:${label}`,await input.evaluate(e=>e.matches(':focus-visible')&&parseFloat(getComputedStyle(e).outlineWidth)>0));await page.keyboard.press('ControlOrMeta+A');await page.keyboard.type(query);
    await page.keyboard.press('Tab');check(`keyboard-submit-focus:${label}`,await page.locator('main button[type=submit]').evaluate(e=>e===document.activeElement));
    await page.keyboard.press('Enter');await page.waitForURL(url=>url.pathname==='/'&&url.searchParams.get('s')===query&&[...url.searchParams].length===1);await page.locator('main .wp-block-post-title a').first().waitFor();check(`keyboard-recovery:${label}`,new URL(page.url()).searchParams.get('s')===query&&await page.locator('main .wp-block-post-title a').count()>0);
    const result=page.locator('main .wp-block-post-title a').first();let resultFocused=false;for(let step=0;step<40;step++){await page.keyboard.press('Tab');resultFocused=await result.evaluate(e=>e===document.activeElement);if(resultFocused)break;}
    check(`keyboard-result-focus:${label}`,resultFocused);if(resultFocused){const href=await result.getAttribute('href');await page.keyboard.press('Enter');await page.waitForURL(href);check(`keyboard-result-navigation:${label}`,page.url()===href);}
   }
  }
  await context.close();
 }
 completed=true;
}finally{
 await browser.close();check('sources-unchanged',JSON.stringify(digests())===JSON.stringify(sourceDigests));
 const output=path.join(root,'docs/research/2026-09-08-site-search/results',process.argv.includes('--baseline')?'input-baseline.json':'input.json');
 fs.writeFileSync(output,JSON.stringify({completed,sourceDigests,rows},null,2)+'\n');console.log(JSON.stringify({checks:rows.length,failed:rows.filter(r=>!r.pass).length}));
}
if(!process.argv.includes('--baseline')&&rows.some(r=>!r.pass))process.exitCode=1;
