import { execFileSync } from 'node:child_process';
import os from 'node:os';
import { chromium } from 'playwright';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { createHash } from 'node:crypto';
const root=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'..');
const out=path.join(root,'docs/research/2026-09-08-content-faces/results/inheritance');fs.mkdirSync(out,{recursive:true});
const baseline=process.argv.includes('--baseline');
const faces={content_paid:'/library/decision-design/',content_interview:'/voices/making-room/',content_blp:'/guides/before-redesign/',content_lp:'/start/editorial-session/',content_learning:'/learn/page-design/',content_site:'/site-company/'};
const rows=[],shots=[];const check=(name,pass)=>rows.push({name,pass:!!pass});let completed=false;
const state=process.env.WTCF_STATE_DIR||path.join(os.tmpdir(),'helix-content-lab');
const wp=args=>execFileSync('docker',['run','--rm','--network','helix-content-lab','--env-file',path.join(state,'wp.env'),'--volumes-from','helix-content-wp','--user','33:33','wordpress:cli-php8.3','wp',...args],{encoding:'utf8',stdio:['ignore','pipe','pipe']}).trim();
if(wp(['option','get','blogname'])!=='HELIX Content Lab')throw Error('Dedicated lab required');
const original=wp(['eval',"echo wp_json_encode(get_option('theme_mods_helix-wt',null));"]);let settingsChanged=false;
const trackedSources=['scripts/verify-content-inheritance.mjs','docs/research/2026-09-08-content-faces/plugin/search.php',...['functions.php','inc/content-faces.php','inc/learning.php','inc/site-pages.php','inc/content-chrome.php','inc/content-navigation.php','config/content-chrome.json','theme.json','assets/css/theme.css','assets/css/content-faces.css','assets/css/site-pages.css','assets/css/content-chrome.css','assets/js/header.js','assets/js/footer.js','assets/js/side.js','parts/header-band.html','parts/header-center.html','parts/header-two-rows.html','parts/footer.html'].map(f=>'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/'+f),'docs/research/2026-09-08-content-faces/plugin/content-faces.php','docs/research/2026-09-08-content-faces/plugin/manifest.json'];
const getDigests=()=>Object.fromEntries(trackedSources.map(f=>[f,createHash('sha256').update(fs.readFileSync(path.join(root,f))).digest('hex')]));
const sourceDigests=getDigests();
const browser=await chromium.launch();
try{
 for(const [device,width] of [['pc',1440],['sp',375]]){
  const context=await browser.newContext({viewport:{width,height:900},javaScriptEnabled:false});const page=await context.newPage();
  for(const [face,route] of Object.entries(faces))for(const mode of ['site','own','off']){
   const wt=['content_chrome:shared',`${face}_head:${mode}`,`${face}_foot:${mode}`,`${face}_fix:${mode}`,`${face}_side:${mode==='site'?'article':mode}`,'header:band',`own_${face}_header:center`,'footer_layout:single-row',`own_${face}_footer_layout:columns-3`,'fixed:float-cta',`own_${face}_fixed:float-tel`,'side_layout:right','side_set:minimal',`own_${face}_side_layout:left`, `own_${face}_side_set:minimal`].join(',');
   const response=await page.goto('http://127.0.0.1:8098'+route+'?wt='+encodeURIComponent(wt));const label=`${face}:${mode}:${device}`;
   check(`response:${label}`,response.ok());
   check(`face:${label}`,await page.locator('body').evaluate((e,face)=>e.classList.contains('wt-face-'+face),face));
   check(`header:${label}`,mode==='off'?await page.locator('.wt-header').count()===0:await page.locator('.wt-header').count()===1);
   check(`footer:${label}`,mode==='off'?await page.locator('.wt-footer').count()===0:await page.locator('.wt-footer').count()===1);
   if(mode!=='off'){
    check(`header-variant:${label}`,await page.locator(mode==='site'?'.wt-header--band':'.wt-header--center').count()===1);
    check(`footer-variant:${label}`,await page.locator(mode==='site'?'.wt-footer__layout--single-row':'.wt-footer__layout--columns-3').isVisible());
   }
   check(`fixed:${label}`,mode==='off'?await page.locator('.wt-fixed').count()===0:await page.locator(mode==='site'?'.wt-fixed--float-cta':'.wt-fixed--float-tel').count()===1);
   check(`sidebar:${label}`,mode==='off'?await page.locator('.wt-side').count()===0:await page.locator('.wt-side--right').isVisible());
   if(mode!=='off')check(`sidebar-position:${label}`,await page.evaluate(({mode,device})=>{const side=document.querySelector('.wt-side--right').getBoundingClientRect(),main=document.querySelector('.wt-side-main').getBoundingClientRect();return device==='sp'?side.top>=main.bottom-1:mode==='own'?side.right<=main.left+1:side.left>=main.right-1;},{mode,device}));
   if(mode!=='off')check(`sidebar-banner-contrast:${label}`,await page.locator('.wt-side--right .wt-side-banners a').evaluateAll(es=>{
    const rgb=s=>(s.match(/[\d.]+/g)||[]).slice(0,3).map(Number);
    const lum=v=>v.map(n=>n/255).map(n=>n<=.04045?n/12.92:((n+.055)/1.055)**2.4).reduce((sum,n,i)=>sum+n*[.2126,.7152,.0722][i],0);
    return es.length>0&&es.every(e=>{const colors=(getComputedStyle(e).backgroundImage.match(/rgb\([^)]*\)/g)||[]).map(rgb);if(colors.length<2)return false;const brightest=[0,1,2].map(i=>Math.max(...colors.map(v=>v[i])));const text=lum(rgb(getComputedStyle(e.querySelector('b')).color));const back=lum(brightest);return (Math.max(text,back)+.05)/(Math.min(text,back)+.05)>=4.5;});
   }));
   if(mode!=='off')check(`navigation-labels:${label}`,JSON.stringify([...new Set(await page.locator('.wt-header .wp-block-navigation-item__content').allTextContents())])===JSON.stringify(['記事を読む','人を知る','学習・ヘルプ','会社案内']));
   check(`native-removed:${label}`,await page.locator('.wtcf-header,.wtcf-footer,.wtsite-header,.wtsite-footer').count()===0);
   check(`reflow:${label}`,await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));
   if(face==='content_paid')check(`access:${label}`,!(await page.content()).includes('この段落は購入者向けの検証本文です'));
   const file=`${face}-${mode}-${device}.jpg`;
   if(!baseline){await page.screenshot({path:path.join(out,file),fullPage:true,type:'jpeg',quality:75});shots.push({file,face,mode,device,route:route+'?wt='+encodeURIComponent(wt)});}
  }
  await context.close();
 }
 if(!baseline){
  settingsChanged=true;
  wp(['eval',"set_theme_mod('wt_content_chrome','shared');set_theme_mod('wt_header','band');set_theme_mod('wt_content_interview_head','own');set_theme_mod('wt_own_content_interview_header','center');"]);
  const page=await browser.newPage();
  for(const [face,route] of Object.entries(faces)){
   await page.goto('http://127.0.0.1:8098'+route);
   check(`saved-settings:${face}`,await page.locator(face==='content_interview'?'.wt-header--center':'.wt-header--band').count()===1);
  }
  wp(['eval',"set_theme_mod('wt_header','two-rows');"]);
  for(const [face,route] of Object.entries(faces)){
   await page.goto('http://127.0.0.1:8098'+route);
   check(`shared-update-own-isolation:${face}`,await page.locator(face==='content_interview'?'.wt-header--center':'.wt-header--two-rows').count()===1);
  }
  await page.close();
 }
 completed=true;
}finally{
 await browser.close();
 if(settingsChanged){
  const encoded=Buffer.from(original).toString('base64');
  wp(['eval',`$v=json_decode(base64_decode('${encoded}'),true);if(null===$v){delete_option('theme_mods_helix-wt');}else{update_option('theme_mods_helix-wt',$v);}`]);
  check('settings-restored',wp(['eval',"echo wp_json_encode(get_option('theme_mods_helix-wt',null));"])===original);
 }
 check('sources-unchanged',JSON.stringify(sourceDigests)===JSON.stringify(getDigests()));
 fs.writeFileSync(path.join(out,baseline?'baseline.json':'verify.json'),JSON.stringify({completed,sourceDigests,rows,shots},null,2)+'\n');
 console.log(JSON.stringify({checks:rows.length,failed:rows.filter(r=>!r.pass).length}));
}
if(!baseline&&rows.some(r=>!r.pass))process.exitCode=1;
