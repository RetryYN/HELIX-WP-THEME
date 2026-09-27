import { contentLab } from './lib/content-lab-env.mjs';
import {execFileSync} from 'node:child_process';
import {chromium} from 'playwright';
import fs from 'node:fs';import path from 'node:path';import {createHash,randomUUID} from 'node:crypto';
const root=process.cwd(),theme='docs/research/2026-09-05-design-prototype-03/theme/helix-wt/',out='docs/research/2026-09-16-device-vocabulary',state=contentLab.stateDir,slug='device-vocabulary-fixture',run=randomUUID(),lock=state+'/device-vocabulary-fixture.json';
const wp=a=>execFileSync('docker',['run','--rm','--network',contentLab.network,'--env-file',state+'/wp.env','--volumes-from',contentLab.wpContainer,'--user','33:33','wordpress:cli-php8.3','wp',...a],{encoding:'utf8',maxBuffer:16000000}).trim();
const reserved=()=>wp(['post','list','--post_type=any','--post_status=any','--name='+slug,'--format=ids']);
if(wp(['option','get','blogname'])!=='HELIX Content Lab'||reserved()||fs.existsSync(lock))throw Error('Dedicated lab/fixture collision guard');
const hash=f=>createHash('sha256').update(fs.readFileSync(f)).digest('hex'),sources=['scripts/verify-device-vocabulary.mjs',...['functions.php','inc/device-vocabulary.php','config/device-vocabulary.json','assets/css/device-vocabulary.css','assets/js/device-vocabulary.js','patterns/device-read.php','patterns/device-compare.php'].map(f=>theme+f)],sourceDigests=Object.fromEntries(sources.map(f=>[f,hash(f)])),rows=[],shots=[];
const check=(name,pass,details)=>rows.push({name,pass:!!pass,...details===undefined?{}:{details}});let id,browser,completed=false;
fs.writeFileSync(lock,JSON.stringify({run,pid:process.pid}),{flag:'wx'});
try{
 id=Number(wp(['post','create','--post_type=page','--post_status=publish','--post_name='+slug,'--post_title=サービス選びのガイド','--porcelain']));fs.writeFileSync(lock,JSON.stringify({run,pid:process.pid,id}));wp(['post','meta','update',String(id),'wt_device_fixture_owner',run]);wp(['post','meta','update',String(id),'_wp_page_template','page-zone-catalog']);
 browser=await chromium.launch();
 for(const preset of ['compare','read']){
  wp(['post','update',String(id),'--post_content=<!-- wp:pattern {"slug":"helix-wt/device-'+preset+'"} /-->']);
  for(const[device,width]of[['pc',1280],['sp',390]])for(const js of[true,false]){
   const context=await browser.newContext({viewport:{width,height:900},javaScriptEnabled:js,reducedMotion:'reduce'}),p=await context.newPage(),prefix=preset+':'+device+':'+(js?'js':'nojs');
   const response=await p.goto(`${contentLab.baseUrl}/`+slug+'/?wt=page_fix:off');await p.locator('[data-wt-device-preset]').waitFor();await p.locator('.wt-device-gallery img').evaluateAll(imgs=>Promise.all(imgs.map(i=>i.decode())));
   check(prefix+':response',response.status()===200);check(prefix+':no-overflow',await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));
   check(prefix+':core-panels-in-html',(await response.text()).includes('相談後について'));
   check(prefix+':panel-count',await p.locator('.wt-device-tabs__panel').count()===3);
   check(prefix+':contract',JSON.parse(await p.locator('[data-wt-device-contract]').getAttribute('data-wt-device-contract')).sp.table===(preset==='read'?'cards':'scroll'));
   check(prefix+':table-labels',await p.locator('tbody td[data-th]').count()===6);
   check(prefix+':image-loaded',await p.locator('.wt-device-gallery img').evaluateAll(es=>es.every(e=>e.naturalWidth>0)));
   if(!js){check(prefix+':all-content-visible',await p.locator('.wt-device-tabs__panel:visible').count()===3);check(prefix+':no-dead-controls',await p.locator('.wt-device-tabs button:visible').count()===0);check(prefix+':cta-flow',await p.locator('.wt-device-action').evaluate(e=>getComputedStyle(e).position)!=='sticky');}
   else if(device==='pc'){
    const tabs=p.getByRole('tab');check(prefix+':tabs',await tabs.count()===3);await tabs.first().focus();await p.keyboard.press('End');check(prefix+':end-selects-last',await tabs.last().getAttribute('aria-selected')==='true');await p.keyboard.press('Home');check(prefix+':home-selects-first',await tabs.first().getAttribute('aria-selected')==='true');await p.keyboard.press('ArrowRight');check(prefix+':arrow-selects-second',await tabs.nth(1).getAttribute('aria-selected')==='true');
    await p.setViewportSize({width:390,height:900});await p.waitForFunction(()=>document.querySelector('[data-wt-device-active]').dataset.wtDeviceActive==='sp');check(prefix+':resize-to-accordion',await p.locator('.wt-device-tabs__heading button:visible').count()===3);await p.setViewportSize({width,height:900});await p.waitForFunction(()=>document.querySelector('[data-wt-device-active]').dataset.wtDeviceActive==='pc');check(prefix+':resize-back-tabs',await p.getByRole('tab').first().isVisible());
   }else{
    const toggles=p.locator('.wt-device-tabs__heading button');check(prefix+':accordion',await toggles.count()===3);await toggles.first().focus();await p.keyboard.press('Enter');check(prefix+':enter-collapses',await toggles.first().getAttribute('aria-expanded')==='false');await p.keyboard.press('Space');check(prefix+':space-opens',await toggles.first().getAttribute('aria-expanded')==='true');
    check(prefix+':toc-closed',await p.locator('.wt-device-toc').evaluate(e=>!e.open));await p.locator('.wt-device-toc summary').click();check(prefix+':toc-opens',await p.locator('.wt-device-toc').evaluate(e=>e.open));
    await p.getByRole('button',{name:'次の写真'}).click();await p.waitForFunction(()=>document.querySelector('.wt-device-gallery').scrollLeft>0);check(prefix+':gallery-button-scroll',await p.locator('.wt-device-gallery').evaluate(e=>e.scrollLeft>0));
   }
   const tableShape=await p.locator('.wp-block-table').evaluate(e=>({scroll:e.scrollWidth>e.clientWidth,cell:getComputedStyle(e.querySelector('tbody td')).display}));
   check(prefix+':table-device-shape',device==='sp'?(preset==='read'?tableShape.cell==='flex':tableShape.scroll):true,tableShape);
   if(js&&device==='sp'&&preset==='read'){
    await p.locator('.wt-device-action').scrollIntoViewIfNeeded();await p.waitForFunction(()=>document.querySelector('.wt-device-action').classList.contains('wt-device-action--fixed'));
    check(prefix+':cta-reserved',await p.locator('.wt-device-action').evaluate(e=>e.parentElement.getBoundingClientRect().height>=e.getBoundingClientRect().height));
    check(prefix+':cta-inside-viewport',await p.locator('.wt-device-action').evaluate(e=>{const r=e.getBoundingClientRect();return r.bottom<=innerHeight&&r.top>=0;}));
   }
   await p.evaluate(()=>{document.querySelector('.wt-device-gallery').scrollLeft=0;scrollTo(0,0);});
   if(js&&device==='sp'&&preset==='read')await p.waitForFunction(()=>!document.querySelector('.wt-device-action').classList.contains('wt-device-action--fixed'));
   if(js){const file=preset+'-'+device+'.jpg';await p.screenshot({path:path.join(out,file),fullPage:true,type:'jpeg',quality:82});shots.push({id:preset,device,file,sha256:hash(path.join(out,file))});}
   await context.close();
  }
 }
 completed=true;
}finally{
 await browser?.close();if(id){if(wp(['post','meta','get',String(id),'wt_device_fixture_owner'])!==run)throw Error('Owner mismatch');wp(['post','delete',String(id),'--force']);}check('fixture:cleanup',!reserved());check('source-unchanged',sources.every(f=>hash(f)===sourceDigests[f]));fs.unlinkSync(lock);fs.writeFileSync(out+'/verification.json',JSON.stringify({schema:'wt-device-vocabulary-verification.v1',completed,sourceDigests,rows,shots},null,2)+'\n');console.log(JSON.stringify({completed,checks:rows.length,failed:rows.filter(r=>!r.pass)}));
}if(rows.some(r=>!r.pass))process.exitCode=1;
