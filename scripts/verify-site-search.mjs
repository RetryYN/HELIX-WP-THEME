import { chromium } from 'playwright';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { createHash } from 'node:crypto';
const root=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'..');
const out=path.join(root,'docs/research/2026-09-08-site-search/results');
fs.mkdirSync(out,{recursive:true});
const sources=['scripts/verify-site-search.mjs','docs/research/2026-09-05-design-prototype-03/theme/helix-wt/inc/search.php','docs/research/2026-09-08-content-faces/plugin/search.php','docs/research/2026-09-05-design-prototype-03/theme/helix-wt/assets/css/theme.css','docs/research/2026-09-05-design-prototype-03/theme/helix-wt/templates/search.html'];
const digests=()=>Object.fromEntries(sources.map(f=>[f,createHash('sha256').update(fs.readFileSync(path.join(root,f))).digest('hex')]));
const sourceDigests=digests(), rows=[], shots=[];
const check=(name,pass)=>rows.push({name,pass:!!pass});
const browser=await chromium.launch();let completed=false;
try {
 for(const [device,width] of [['pc',1440],['sp',375]])for(const js of [true,false]){
  const context=await browser.newContext({viewport:{width,height:900},javaScriptEnabled:js});const page=await context.newPage();
  for(const [state,q] of [['results','判断'],['empty','no-match-search-fixture-20260908'],['blank',''],['whitespace','　 ']]){
   const response=await page.goto('http://127.0.0.1:8098/?s='+encodeURIComponent(q));const label=`${state}:${device}:js-${js}`;
   check(`response:${label}`,response.status()===200);
   check(`search-heading:${label}`,await page.locator('main h1').count()===1&&(await page.locator('main h1').innerText())==='サイト内検索');
   const input=page.locator('main input[type=search]');check(`editable-query:${label}`,await input.count()===1&&await input.inputValue()===q);
   check(`reflow:${label}`,await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));
   if(!['blank','whitespace'].includes(state)){
    const total=page.locator('main .wp-block-query-total');check(`count:${label}`,await total.count()===1&&(Number((await total.innerText()).match(/\d+/)?.[0])>0)===(state==='results'));
    check(`state:${label}`,state==='results'?await page.locator('main .wp-block-post-title a').count()>0:await page.getByRole('heading',{name:'該当する情報が見つかりませんでした。'}).isVisible());
   }
   if(['blank','whitespace'].includes(state)){check(`unentered:${label}`,await page.locator('main .wt-search-start').count()===1&&await page.locator('main .wp-block-post-title, main .wp-block-query-total').count()===0);check(`not-zero:${label}`,await page.locator('main .wp-block-query-no-results').count()===0);}
   if(!js&&state!=='whitespace'){const file=`after-${state}-${device}.jpg`;await page.screenshot({path:path.join(out,file),type:'jpeg',quality:80,fullPage:true});shots.push({file,device,state,query:q});}
  }
  await page.goto('http://127.0.0.1:8098/?s=no-match-search-fixture-20260908');
  await page.locator('main input[type=search]').fill('判断');await page.locator('main').getByRole('button',{name:'検索する'}).click();
  check(`recover:${device}:js-${js}`,new URL(page.url()).searchParams.get('s')==='判断'&&await page.locator('main .wp-block-post-title a').count()>0);
  const link=page.locator('main .wp-block-post-title a').first();const href=await link.getAttribute('href');await link.click();
  check(`result-navigation:${device}:js-${js}`,page.url()===href);
  await context.close();
 }
 completed=true;
}finally{
 await browser.close();check('sources-unchanged',JSON.stringify(digests())===JSON.stringify(sourceDigests));
 fs.writeFileSync(path.join(out,'verify.json'),JSON.stringify({completed,sourceDigests,rows,shots},null,2)+'\n');
 console.log(JSON.stringify({checks:rows.length,failed:rows.filter(x=>!x.pass).length}));
}
if(!completed||rows.some(x=>!x.pass))process.exitCode=1;
