import fs from 'node:fs';import {chromium} from 'playwright';import {createHash} from 'node:crypto';
const out='docs/research/2026-09-14-header-navigation-editing';fs.mkdirSync(out,{recursive:true});
const rows=[],conditions=[];const headers=['search','nav','cta','announce','center','two-rows','overlay','tel','band'];
const browser=await chromium.launch({args:['--no-sandbox']});
try{for(const js of [true,false])for(const width of [390,1440]){
 const page=await browser.newPage({viewport:{width,height:900},javaScriptEnabled:js});
 for(const kind of ['native','shared'])for(const h of headers){
  const name=[kind,h,width,js].join(':');conditions.push(name);
  const route=kind==='native'?'/?p=1&wt=':'/library/decision-design/?wt=';
  await page.goto('http://127.0.0.1:8098'+route+'content_chrome:shared,content_paid_head:site,header:'+h+',sp:text-nav',{waitUntil:'load'});
  rows.push({name:name+':editor-ui-absent',pass:await page.locator('.wt-header-editor-sp-navigation,.wt-header-navigation-pending').count()===0});
  rows.push({name:name+':editor-script-not-enqueued',pass:await page.locator('script[src*="header-navigation-editor.js"]').count()===0});
 }
 await page.close();
}}finally{await browser.close();}
const files=['scripts/verify-header-navigation-editor-isolation.mjs','docs/research/2026-09-05-design-prototype-03/theme/helix-wt/assets/js/header-navigation-editor.js','docs/research/2026-09-05-design-prototype-03/theme/helix-wt/inc/header-navigation-settings.php'];const sourceDigests=Object.fromEntries(files.map(f=>[f,createHash('sha256').update(fs.readFileSync(f)).digest('hex')]));
const completed=rows.every(r=>r.pass);fs.writeFileSync(out+'/public-isolation.json',JSON.stringify({completed,conditionCount:conditions.length,assertionCount:rows.length,conditions,sourceDigests,rows},null,2)+'\n');console.log('public editor isolation',conditions.length,rows.length,rows.filter(r=>!r.pass));if(!completed)process.exitCode=1;
