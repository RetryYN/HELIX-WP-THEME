import { contentLab } from './lib/content-lab-env.mjs';
import { chromium } from 'playwright';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { createHash } from 'node:crypto';
const root=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'..');
const out=path.join(root,'docs/research/2026-09-15-home-completion');
const base=`${contentLab.baseUrl}`;
const rows=[];const check=(name,pass,details)=>rows.push({name,pass:!!pass,...(details===undefined?{}:{details})});let completed=false;
const theme='docs/research/2026-09-05-design-prototype-03/theme/helix-wt/';
const sourceFiles=['scripts/verify-home-isolation.mjs',...['functions.php','inc/content-chrome.php','inc/content-faces.php','assets/css/home-completion.css','assets/css/theme.css','assets/css/content-chrome.css'].map(f=>theme+f)];
const digest=()=>Object.fromEntries(sourceFiles.map(f=>[f,createHash('sha256').update(fs.readFileSync(path.join(root,f))).digest('hex')]));const sourceDigests=digest();
const browser=await chromium.launch();
try{
 for(const [device,width]of [['pc',1440],['sp',375]])for(const js of [true,false]){
  const context=await browser.newContext({viewport:{width,height:900},javaScriptEnabled:js,reducedMotion:'reduce'});const page=await context.newPage();
  for(const mode of ['site','own','off']){
   const wt=`home_head:${mode},home_foot:${mode},home_fix:${mode},header:band,own_home_header:center,footer_layout:single-row,own_home_footer_layout:columns-3,fixed:float-cta,home_fixed:float-tel,home_side_layout:right,home_side_set:minimal,side_from:below-hero`;
   await page.goto(base+'/?wt='+encodeURIComponent(wt));const label=`${device}-${js?'js':'nojs'}:home:${mode}`;
   check(`${label}:header-dom`,await page.locator('.wt-header').count()===(mode==='off'?0:1));
   check(`${label}:footer-dom`,await page.locator('.wt-footer').count()===(mode==='off'?0:1));
   check(`${label}:fixed-dom`,await page.locator('.wt-fixed').count()===(mode==='site'?1:0));
   if(mode!=='off'){
    check(`${label}:header-source`,await page.locator(mode==='site'?'.wt-header--band':'.wt-header--center').count()===1);
    check(`${label}:footer-source`,await page.locator(mode==='site'?'.wt-footer__layout--single-row':'.wt-footer__layout--columns-3').isVisible());
    check(`${label}:fixed-source`,await page.locator(mode==='site'?'.wt-fixed--float-cta':'.wt-home-fixed--float-tel').count()===1);
   }
   check(`${label}:inactive-owned-fixed-hidden`,mode==='own'||await page.locator('.wt-home-fixed:visible').count()===0);
   check(`${label}:sidebar-home-bundle`,await page.locator('body').evaluate(e=>e.classList.contains('wt-side-bundle-home')));
   check(`${label}:no-overflow`,await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));
  }
  for(const route of ['/library/decision-design/','/voices/making-room/','/learn/page-design/','/site-company/']){
   const common='content_chrome:shared,header:band,footer_layout:single-row';
   const snapshot=async(extra)=>{
    await page.goto(base+route+'?wt='+encodeURIComponent(common+extra));
    return page.evaluate(()=>({homeCss:!!document.querySelector('#helix-wt-home-completion-css'),home:!!document.querySelector('main.wt-home'),chrome:[...document.querySelectorAll('.wt-header,.wt-footer,.wt-side,.wt-fixed')].map(e=>({className:e.className,text:e.textContent.replace(/\s+/g,' ').trim(),width:Math.round(e.getBoundingClientRect().width),x:Math.round(e.getBoundingClientRect().x)}))}));
   };
   const initial=await snapshot('');const changed=await snapshot(',home_hero:slider,home_sections:media,home_head:off,home_foot:off,home_fix:own,home_fixed:sp-bottom-bar,home_side_layout:both,home_side_set:full,side_from:top');
   check(`${device}-${js?'js':'nojs'}:${route}:home-axis-isolation`,JSON.stringify(initial)===JSON.stringify(changed));
   check(`${device}-${js?'js':'nojs'}:${route}:scoped-home-css-has-no-home-dom`,changed.homeCss&&!changed.home);
  }
  if(width===375){
   await page.goto(base+'/?wt=home_fixed:sp-bottom-bar,home_fix:own,home_contact:double-cta');
   await page.evaluate(()=>scrollTo(0,document.documentElement.scrollHeight));
   check(`${device}-${js?'js':'nojs'}:fixed-footer-clearance`,await page.evaluate(()=>{const fixed=document.querySelector('.wt-home-fixed--sp-bottom-bar').getBoundingClientRect();const links=[...document.querySelectorAll('.wt-footer a')].filter(e=>e.getClientRects().length);return links.length>0&&links.at(-1).getBoundingClientRect().bottom<=fixed.top;}));
  }
  await context.close();
 }
 completed=true;
}finally{await browser.close();check('sources-unchanged',JSON.stringify(sourceDigests)===JSON.stringify(digest()));fs.writeFileSync(path.join(out,'isolation.json'),JSON.stringify({completed,sourceDigests,rows},null,2)+'\n');console.log(JSON.stringify({completed,checks:rows.length,failed:rows.filter(r=>!r.pass).length,failures:rows.filter(r=>!r.pass)}));}
if(rows.some(r=>!r.pass))process.exitCode=1;
