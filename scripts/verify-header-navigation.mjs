import { chromium } from 'playwright';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';import path from 'node:path';import os from 'node:os';
import { fileURLToPath } from 'node:url';import { createHash } from 'node:crypto';
const root=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'..');
const state=process.env.WTCF_STATE_DIR||path.join(os.tmpdir(),'helix-content-lab');
const wp=args=>execFileSync('docker',['run','--rm','--network','helix-content-lab','--env-file',path.join(state,'wp.env'),'--volumes-from','helix-content-wp','--user','33:33','wordpress:cli-php8.3','wp',...args],{encoding:'utf8',stdio:['ignore','pipe','pipe']}).trim();
if(wp(['option','get','blogname'])!=='HELIX Content Lab')throw Error('Dedicated lab required');
const baseline=process.argv.includes('--baseline');const base='http://127.0.0.1:8098';
const out=path.join(root,'docs/research/2026-09-08-content-faces/results/header-navigation');fs.mkdirSync(out,{recursive:true});
const original=wp(['eval',"echo wp_json_encode(get_option('theme_mods_helix-wt',null));"]);
const items=[{label:'記事を読む',url:base+'/library/'},{label:'人を知る',url:base+'/voices/'},{label:'学習・ヘルプ',url:base+'/learn/'},{label:'会社案内',url:base+'/site-company/'}];
const content=links=>links.map(attrs=>'<!-- wp:navigation-link '+JSON.stringify({...attrs,kind:'custom'})+' /-->').join('\n');
const rows=[],shots=[];let id,completed=false;const check=(name,pass)=>rows.push({name,pass:!!pass});
const sources=['docs/research/2026-09-05-design-prototype-03/theme/helix-wt/inc/search.php','scripts/verify-header-navigation.mjs',...['functions.php','inc/content-chrome.php','inc/content-navigation.php','assets/css/content-chrome.css','parts/header.html','parts/header-band.html','patterns/header-sp-extras.php'].map(f=>'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/'+f)];
const getDigests=()=>Object.fromEntries(sources.map(f=>[f,createHash('sha256').update(fs.readFileSync(path.join(root,f))).digest('hex')]));const sourceDigests=getDigests();
const browser=await chromium.launch();
try{
 if(wp(['post','list','--post_type=wp_navigation','--post_status=any','--name=navigation-boundary-fixture','--format=ids']))throw Error('Reserved navigation fixture exists');
 id=Number(wp(['post','create','--post_type=wp_navigation','--post_status=publish','--post_name=navigation-boundary-fixture','--post_title=検証用共通ナビ','--post_content='+content(items),'--porcelain']));
 wp(['theme','mod','set','wt_content_navigation_ref',String(id)]);
 const route='/library/decision-design/?wt=content_chrome:shared,content_paid_head:site';
 for(const [device,width] of [['pc',1440],['sp',375]])for(const js of [true,false]){
  const context=await browser.newContext({viewport:{width,height:900},javaScriptEnabled:js});const page=await context.newPage();
  await page.goto(base+route+',header:band');const label=`${device}:js-${js}`;
  const links=page.locator('.wt-header .wp-block-navigation:not(.wt-header__textnav) .wp-block-navigation-item__content');
  const labels=[...new Set(await links.allTextContents())];const matches=JSON.stringify(labels)===JSON.stringify(items.map(x=>x.label));
  check(`stored-links:${label}`,matches);
  if(matches){
   if(device==='sp'&&js){await page.locator('.wt-header .wp-block-navigation__responsive-container-open').click();await page.locator('.wt-header .is-menu-open').waitFor();}
   check(`visible:${label}`,await links.first().isVisible());
   check(`reflow:${label}`,await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));
   for(const item of items){const response=await page.request.get(item.url);check(`destination:${label}:${item.label}`,response.ok());}
   if(await links.first().isVisible()){
    await links.first().click();check(`click:${label}`,new URL(page.url()).pathname==='/library/');
   }else check(`click:${label}`,false);
  }
  await page.goto(base+route+',header:band');
  const file=`${baseline?'before':'after'}-${device}-js-${js}.jpg`;await page.screenshot({path:path.join(out,file),fullPage:false,type:'jpeg',quality:82});shots.push({file,device,js});
  await context.close();
 }
 wp(['post','update',String(id),'--post_content='+content([items[3],{...items[0],label:'最新の記事へ'}])]);
 const page=await browser.newPage();
 await page.goto(base+route+',header:band');
 const dimensions=()=>page.evaluate(()=>['--wp--style--global--content-size','--wp--style--global--wide-size'].map(key=>getComputedStyle(document.body).getPropertyValue(key).trim()));
 const originalDimensions=await dimensions();
 check('layout-tokens-present',originalDimensions.every(Boolean));
 for(const variant of ['search','nav','cta','announce','center','two-rows','overlay','tel','band']){
  await page.goto(base+route+',header:'+variant);
  check(`layout-tokens:${variant}`,JSON.stringify(await dimensions())===JSON.stringify(originalDimensions));
  check(`updated:${variant}`,JSON.stringify([...new Set(await page.locator('.wt-header .wp-block-navigation:not(.wt-header__textnav) .wp-block-navigation-item__content').allTextContents())])===JSON.stringify(['会社案内','最新の記事へ']));
 }
 for(const status of ['draft','trash']){
  wp(['post','update',String(id),'--post_status='+status]);await page.goto(base+route+',header:band');
  check(`unpublished:${status}`,await page.locator('.wt-header .wp-block-navigation:not(.wt-header__textnav) .wp-block-navigation-item__content').count()===0);
 }
 wp(['post','update',String(id),'--post_status=publish','--post_content=']);
 await page.goto(base+route+',header:band');
 check('empty:no-placeholder-links',await page.locator('.wt-header .wp-block-navigation-item__content').count()===0);
 const wrongType=wp(['post','list','--post_type=wt_paid','--name=decision-design','--format=ids']);
 for(const [label,value] of [['zero','0'],['missing','2147483647'],['wrong-type',wrongType],['malformed','invalid']]){
  wp(['theme','mod','set','wt_content_navigation_ref',value]);await page.goto(base+route+',header:band');
  check(`invalid:${label}`,await page.locator('.wt-header .wp-block-navigation-item__content').count()===0);
 }
 wp(['eval',"set_theme_mod('wt_content_navigation_ref',array(1));"]);await page.goto(base+route+',header:band');
 check('invalid:array',await page.locator('.wt-header .wp-block-navigation-item__content').count()===0);
 wp(['theme','mod','remove','wt_content_navigation_ref']);await page.goto(base+route+',header:band');
 check('unset:no-placeholder-links',await page.locator('.wt-header .wp-block-navigation:not(.wt-header__textnav) .wp-block-navigation-item__content').count()===0);
 await page.close();completed=true;
}finally{
 await browser.close();if(id)wp(['post','delete',String(id),'--force']);
 const encoded=Buffer.from(original).toString('base64');wp(['eval',`$v=json_decode(base64_decode('${encoded}'),true);if(null===$v){delete_option('theme_mods_helix-wt');}else{update_option('theme_mods_helix-wt',$v);}`]);
 check('settings-restored',wp(['eval',"echo wp_json_encode(get_option('theme_mods_helix-wt',null));"])===original);
 check('sources-unchanged',JSON.stringify(sourceDigests)===JSON.stringify(getDigests()));
 fs.writeFileSync(path.join(out,baseline?'baseline.json':'verify.json'),JSON.stringify({completed,sourceDigests,rows,shots},null,2)+'\n');
 console.log(JSON.stringify({checks:rows.length,failed:rows.filter(r=>!r.pass).length}));
}
if(!baseline&&rows.some(r=>!r.pass))process.exitCode=1;
