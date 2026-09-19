import {chromium} from 'playwright';
import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
import {createHash} from 'node:crypto';
const root=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'..');
const out=path.join(root,'docs/research/2026-09-15-home-completion');
const browser=await chromium.launch();const measurements=[];
try{
 for(const [device,width]of [['pc',1440],['sp',375]])for(const version of ['before','after']){
  const context=await browser.newContext({viewport:{width,height:900},reducedMotion:'reduce'});
  if(version==='before')await context.route('**/assets/css/home-completion.css*',r=>r.fulfill({contentType:'text/css',body:''}));
  const page=await context.newPage();await page.goto('http://127.0.0.1:8098/?wt=home_hero:split,home_sections:service,home_side_layout:right,side_from:below-hero,home_fixed:sp-bottom-bar,home_fix:own,home_contact:double-cta');
  const row=await page.evaluate(()=>{const hero=document.querySelector('.wt-home-hero-slot').getBoundingClientRect(),main=document.querySelector('.wt-home .wt-side-main').getBoundingClientRect(),side=document.querySelector('.wt-home .wt-side').getBoundingClientRect();return {heroToBodyGap:main.top-hero.bottom,sidebarTop:side.top,heroBottom:hero.bottom};});
  await page.evaluate(async()=>{for(let y=0;y<document.documentElement.scrollHeight;y+=700){scrollTo(0,y);await new Promise(r=>setTimeout(r,20));}scrollTo(0,document.documentElement.scrollHeight);});
  const footer=await page.evaluate(()=>{const fixed=document.querySelector('.wt-home-fixed--sp-bottom-bar');const links=[...document.querySelectorAll('.wt-footer a')].filter(e=>e.getClientRects().length);return {fixedVisible:fixed.getClientRects().length>0,fixedTop:fixed.getBoundingClientRect().top,lastFooterLinkBottom:links.at(-1).getBoundingClientRect().bottom};});
  if(width===375)await page.screenshot({path:path.join(out,`fixed-footer-${version}.png`)});
  measurements.push({device,version,...row,...footer});await context.close();
 }
}finally{await browser.close();}
const files=['scripts/measure-home-presentation.mjs','docs/research/2026-09-05-design-prototype-03/theme/helix-wt/assets/css/home-completion.css','docs/research/2026-09-05-design-prototype-03/theme/helix-wt/functions.php'];
const sourceDigests=Object.fromEntries(files.map(f=>[f,createHash('sha256').update(fs.readFileSync(path.join(root,f))).digest('hex')]));
const rows=[];for(const device of ['pc','sp']){const before=measurements.find(r=>r.device===device&&r.version==='before'),after=measurements.find(r=>r.device===device&&r.version==='after');rows.push({name:`${device}:hero-body-gap-reduced`,pass:after.heroToBodyGap<before.heroToBodyGap});if(device==='pc')rows.push({name:'pc:sidebar-below-hero',pass:after.sidebarTop>=after.heroBottom});else rows.push({name:'sp:footer-overlap-reproduced-and-fixed',pass:before.fixedVisible&&before.lastFooterLinkBottom>before.fixedTop&&after.fixedVisible&&after.lastFooterLinkBottom<=after.fixedTop});}
const result={completed:true,sourceDigests,measurements,rows};fs.writeFileSync(path.join(out,'presentation-measurements.json'),JSON.stringify(result,null,2)+'\n');console.log(JSON.stringify(result));if(rows.some(r=>!r.pass))process.exitCode=1;
