import {chromium} from 'playwright';
import AxeBuilder from '@axe-core/playwright';
import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
import {createHash} from 'node:crypto';
import {contentLab} from './lib/content-lab-env.mjs';

// 実配信CSSを検査する。レスポンス差替え・DB変更は行わない。
const root=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'..');
const out=path.join(root,'local-evidence/sidebar-specificity/mounted');
const cssPath='docs/research/2026-09-05-design-prototype-03/theme/helix-wt/assets/css/theme.css';
const hash=value=>createHash('sha256').update(value).digest('hex');
const siteCSSPath=cssPath.replace('theme.css','site-pages.css');
const themeJsonPath='docs/research/2026-09-05-design-prototype-03/theme/helix-wt/theme.json';
const themeJsonSource=fs.readFileSync(path.join(root,themeJsonPath));
const themeJson=JSON.parse(themeJsonSource.toString('utf8'));
const expectedThemeJson=hash(themeJsonSource);
const accentHex=themeJson.settings?.color?.palette?.find(token=>token.slug==='accent')?.color;
const accentValid=/^#[0-9a-f]{6}$/iu.test(accentHex??'');
let accentColor=null;
const expectedSiteCSS=hash(fs.readFileSync(path.join(root,siteCSSPath)));
const expectedCSS=hash(fs.readFileSync(path.join(root,cssPath)));
const route='/?wt=home_hero:split,home_sections:service,home_side_layout:right,home_side_set:full,side_from:below-hero,home_fixed:sp-bottom-bar,home_fix:own,home_contact:double-cta';
const scenarios=[{name:'home',route},...['own','site'].map(mode=>({name:'site-'+mode,route:'/site-company/?wt='+encodeURIComponent(`content_chrome:shared,content_site_head:${mode},content_site_foot:${mode},content_site_fix:${mode},content_site_side:${mode==='site'?'article':'own'},side_layout:right,side_set:full,own_content_site_side_layout:left,own_content_site_side_set:full`)}))].filter(s=>!process.argv.includes('--site-only')||s.name!=='home');
const parts={banner1:'.wt-side-banners li:nth-child(1) a',banner2:'.wt-side-banners li:nth-child(2) a',banner3:'.wt-side-banners li:nth-child(3) a',cta:'.wt-side-contact .wt-lp-cta-action',sns:'.wt-side-sns a',news:'.wt-side-widget--new-posts a',rank:'.wt-side-rank a',related:'.wt-side-related a',events:'.wt-side-events a'};
const rows=[],bindings=[];const check=(name,pass,details)=>rows.push({name,pass,...details===undefined?{}:{details}});
const luminance=rgb=>rgb.map(v=>{v/=255;return v<=.04045?v/12.92:((v+.055)/1.055)**2.4;}).reduce((sum,v,i)=>sum+v*[.2126,.7152,.0722][i],0);
const contrast=(a,b)=>(Math.max(luminance(a),luminance(b))+.05)/(Math.min(luminance(a),luminance(b))+.05);
let browser,completed=false;const errors=[];
const save=()=>fs.writeFileSync(path.join(out,'verification.json'),JSON.stringify({kind:'mounted-css-sidebar-regression',completed,errors,sourceDigests:{[cssPath]:expectedCSS,[siteCSSPath]:expectedSiteCSS,[themeJsonPath]:expectedThemeJson,'scripts/verify-sidebar-specificity.mjs':hash(fs.readFileSync(fileURLToPath(import.meta.url)))},bindings,rows,limitations:'Scoped axe only; review incomplete items and screenshots. Numeric banner contrast supplements gradient incomplete results. Not whole-theme accessibility certification.'},null,2)+'\n');
fs.mkdirSync(out,{recursive:true});save();
try{
 if(!accentValid)throw Error('theme.json accent palette token missing or invalid');
 accentColor=`rgb(${[1,3,5].map(index=>Number.parseInt(accentHex.slice(index,index+2),16)).join(', ')})`;
 browser=await chromium.launch();
 for(const [device,width] of [['pc',1440],['sp',390]])for(const scenario of scenarios){
  const screen=scenario.name+'-'+device;
  const context=await browser.newContext({viewport:{width,height:900},reducedMotion:'reduce'});
  const page=await context.newPage();
  const waitForStylesheet=(filename)=>page.waitForResponse(r=>new URL(r.url()).pathname.endsWith(`/assets/css/${filename}`),{timeout:30000}).catch(error=>{throw new Error(`${screen}: expected ${filename} response was not received: ${error.message}`);});
  const cssResponse=waitForStylesheet('theme.css');
  const siteResponse=scenario.name==='home'?null:waitForStylesheet('site-pages.css');
  const pending=[page.goto(contentLab.baseUrl+scenario.route),cssResponse];
  if(siteResponse)pending.push(siteResponse);
  const [response,served,servedSite]=await Promise.all(pending);
  const servedCSS=hash(await served.body());
  const servedSiteCSS=servedSite?hash(await servedSite.body()):null;
  bindings.push({device,scenario:scenario.name,route:scenario.route,servedSiteCSSSHA256:servedSiteCSS,htmlSHA256:hash(await response.body()),servedCSSSHA256:servedCSS,expectedCSSSHA256:expectedCSS});
  if(servedCSS!==expectedCSS)throw Error('Mounted CSS differs from checked source');
  if(siteResponse&&servedSiteCSS!==expectedSiteCSS)throw Error('Mounted site CSS differs from checked source');
  if(!await page.getByText('HELIX Content Lab',{exact:true}).count())throw Error('Dedicated lab required');
  check(`${screen}:response`,response.status()===200);
  for(const [part,selector] of Object.entries(parts)){
   const loc=page.locator(selector).first();await loc.scrollIntoViewIfNeeded();
   for(const state of ['normal','hover','focus']){
    await page.mouse.move(0,0);await page.evaluate(()=>document.activeElement?.blur());
    if(state==='hover')await loc.hover();
    if(state==='focus'){await loc.focus();await page.keyboard.press('Tab');await page.keyboard.press('Shift+Tab');}
    await page.waitForTimeout(200);
    const style=await loc.evaluate(e=>{const s=getComputedStyle(e);return {color:s.color,background:s.backgroundColor,gradient:s.backgroundImage,align:s.alignItems,justify:s.justifyContent,minHeight:s.minHeight,display:s.display,width:e.getBoundingClientRect().width,parentWidth:e.parentElement.getBoundingClientRect().width,outline:s.outline,focusVisible:e.matches(':focus-visible'),headingColor:e.querySelector('b')?getComputedStyle(e.querySelector('b')).color:null};});
    const label=`${screen}:${part}:${state}`;
    if(part.startsWith('banner'))check(label+':white-left',style.color==='rgb(255, 255, 255)'&&style.align==='flex-start'&&style.justify==='center'&&style.minHeight==='72px',style);
    else if(part==='cta')check(label+':cta-colors',style.color==='rgb(255, 255, 255)'&&style.background==='rgb(194, 65, 12)'&&style.minHeight==='48px',style);
    else if(part==='sns')check(label+':sns-colors',style.color==='rgb(255, 255, 255)'&&style.background==='rgb(23, 28, 34)'&&style.justify==='center',style);
    else check(label+':widget-layout',part==='news'?style.align==='flex-start':style.justify==='flex-start',style);
    if(state==='hover'&&['news','rank','related','events'].includes(part))check(label+':hover-accent',style.color===accentColor,{...style,expectedAccent:accentColor,accentToken:accentHex});
    if(part.startsWith('banner')){
     check(label+':full-width-flex',style.display==='flex'&&Math.abs(style.width-style.parentWidth)<=1,style);
     // 現行の不透明2色gradientに限定。全RGB成分が明端以下なので白文字の下限を明端で算定できる。
     const knownGradient=style.gradient==='linear-gradient(135deg, rgb(29, 78, 216), rgb(23, 28, 34))';
     const white=style.color==='rgb(255, 255, 255)'&&style.headingColor==='rgb(255, 255, 255)';
     const minimum=knownGradient&&white?contrast([255,255,255],[29,78,216]):null;
     check(label+':heading-contrast',minimum!==null&&minimum>=4.5,{minimum,threshold:4.5,knownGradient,headingColor:style.headingColor,lightEndpoint:[29,78,216],darkEndpoint:[23,28,34]});
    }
    if(state==='focus')check(label+':visible-focus',style.focusVisible&&style.outline.includes('solid'),style);
    if(part.startsWith('banner')||['cta','sns'].includes(part)){
     const axe=await new AxeBuilder({page}).include(selector).withTags(['wcag2a','wcag2aa','wcag21aa']).analyze();
     check(label+':axe',axe.violations.length===0,{violations:axe.violations,incomplete:axe.incomplete});
     await loc.locator('xpath=ancestor::*[contains(@class,"wt-side-widget")][1]').screenshot({path:path.join(out,`${screen}-${part}-${state}.png`)});
    }
   }
  }
  if(scenario.name!=='home'){
   const taps=await page.locator('#site-main a').evaluateAll(es=>es.filter(e=>e.getClientRects().length).map(e=>({width:e.getBoundingClientRect().width,height:e.getBoundingClientRect().height,min:parseFloat(getComputedStyle(e).getPropertyValue('--wp--custom--tap-min'))})));
   check(`${screen}:body-tap-min`,taps.length>0&&taps.every(t=>t.width>=t.min&&t.height>=t.min),taps);
  }
  await page.mouse.move(0,0);
  const imageState=await page.evaluate(async()=>{
   document.activeElement?.blur();
   const imgs=[...document.images].filter(e=>e.getClientRects().length&&getComputedStyle(e).visibility!=='hidden');
   for(const img of imgs){img.scrollIntoView({block:'center'});await img.decode();}
   scrollTo(0,document.documentElement.scrollHeight);
   await new Promise(resolve=>requestAnimationFrame(()=>requestAnimationFrame(resolve)));
   return {count:imgs.length,unloaded:imgs.filter(e=>!e.complete||e.naturalWidth===0).length};
  });
  check(`${screen}:footer-images-loaded`,imageState.unloaded===0,imageState);
  await page.screenshot({path:path.join(out,`fixed-footer-${screen}.png`)});
  await context.close();
 }
 completed=rows.length>0&&rows.every(r=>r.pass);
}catch(error){
 errors.push({stage:'verification',message:error.message});completed=false;
}finally{
 try{await browser?.close();}catch(error){errors.push({stage:'browser-close',message:error.message});completed=false;}
 save();
}
console.log(JSON.stringify({completed,checks:rows.length,failed:rows.filter(r=>!r.pass).length,errors:errors.length}));
if(!completed)process.exitCode=1;
