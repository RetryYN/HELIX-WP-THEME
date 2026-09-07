import { execFileSync } from 'node:child_process';import { chromium } from 'playwright';import fs from 'node:fs';import os from 'node:os';import path from 'node:path';import { fileURLToPath } from 'node:url';import { createHash } from 'node:crypto';
const root=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'..');const state=process.env.WTCF_STATE_DIR||path.join(os.tmpdir(),'helix-content-lab');
const wp=args=>execFileSync('docker',['run','--rm','--network','helix-content-lab','--env-file',path.join(state,'wp.env'),'--volumes-from','helix-content-wp','--user','33:33','wordpress:cli-php8.3','wp',...args],{encoding:'utf8',stdio:['ignore','pipe','pipe']}).trim();
if(wp(['option','get','blogname'])!=='HELIX Content Lab')throw Error('Dedicated lab required');
wp(['language','core','is-installed','ja']);
const original=wp(['eval',"echo wp_json_encode(get_option('WPLANG',null));"]);
const sourceFiles=['scripts/verify-site-search-locale.mjs','docs/research/2026-09-05-design-prototype-03/theme/helix-wt/templates/search.html','docs/research/2026-09-05-design-prototype-03/theme/helix-wt/assets/css/theme.css'];
const digests=()=>Object.fromEntries(sourceFiles.map(f=>[f,createHash('sha256').update(fs.readFileSync(path.join(root,f))).digest('hex')]));const sourceDigests=digests();
const languageDigest=wp(['eval',"echo hash_file('sha256',WP_LANG_DIR.'/ja.mo');"]);
const out=path.join(root,'docs/research/2026-09-08-site-search/results');const rows=[],shots=[];const check=(name,pass)=>rows.push({name,pass:!!pass});let completed=false;
const browser=await chromium.launch();
try{
 wp(['option','update','WPLANG','ja']);
 for(const [device,width]of[['pc',1440],['sp',375]])for(const js of[true,false]){
  const context=await browser.newContext({viewport:{width,height:900},javaScriptEnabled:js});const page=await context.newPage();
  for(const [state,q]of[['results','判断'],['empty','no-match-search-fixture-20260908']]){
   await page.goto('http://127.0.0.1:8098/?s='+encodeURIComponent(q));const label=`${state}:${device}:js-${js}`;
   check(`locale:${label}`,await page.locator('html').getAttribute('lang')==='ja');
   const title=await page.locator('main .wp-block-query-title').innerText();const total=await page.locator('main .wp-block-query-total').innerText();
   check(`translated-title:${label}`,title.includes('検索結果')&&title.includes(q));
   check(`translated-total:${label}`,total.includes('件'));
   check(`no-english-core-copy:${label}`,!/(Search results|results? found)/.test(await page.locator('main').innerText()));
   check(`query:${label}`,await page.locator('main input[type=search]').inputValue()===q);
   check(`reflow:${label}`,await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));
   if(!js){const file=`ja-${state}-${device}.jpg`;await page.screenshot({path:path.join(out,file),type:'jpeg',quality:80,fullPage:true});shots.push({file,device,state,query:q});}
  }
  await context.close();
 }
 completed=true;
}finally{
 await browser.close();const encoded=Buffer.from(original).toString('base64');wp(['eval',`$v=json_decode(base64_decode('${encoded}'),true);if(null===$v){delete_option('WPLANG');}else{update_option('WPLANG',$v);}`]);
 check('locale-restored',wp(['eval',"echo wp_json_encode(get_option('WPLANG',null));"])===original);
 check('sources-unchanged',JSON.stringify(digests())===JSON.stringify(sourceDigests));
 fs.writeFileSync(path.join(out,'locale.json'),JSON.stringify({completed,locale:'ja',languageDigest,sourceDigests,rows,shots},null,2)+'\n');console.log(JSON.stringify({checks:rows.length,failed:rows.filter(r=>!r.pass).length}));
}
if(rows.some(r=>!r.pass))process.exitCode=1;
