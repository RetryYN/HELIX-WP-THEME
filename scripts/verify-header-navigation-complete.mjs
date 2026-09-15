import { chromium } from 'playwright';
import {execFileSync} from 'node:child_process';
import fs from 'node:fs';import path from 'node:path';import {createHash} from 'node:crypto';
const out='docs/research/2026-09-13-header-navigation-complete';fs.mkdirSync(out,{recursive:true});
const theme='docs/research/2026-09-05-design-prototype-03/theme/helix-wt';
const php=code=>execFileSync('docker',['exec','helix-content-wp','php','-r',`require '/var/www/html/wp-load.php'; ${code}`],{encoding:'utf8'}).trim();
const wp=code=>JSON.parse(php(code));
if(php('echo get_option("blogname");')!=='HELIX Content Lab')throw Error('dedicated lab required');
const variants=['search','nav','cta','announce','center','two-rows','overlay','tel','band'];
const axes=['search','right','left','cta','text-nav','center-logo'];
const checks=[],conditions=[],created=[];const check=(name,pass,detail)=>checks.push({name,pass:!!pass,...(pass||detail===undefined?{}:{detail})});
const original=php('echo wp_json_encode(get_option("theme_mods_helix-wt",null));');
const setRef=ref=>php(`set_theme_mod('wt_content_navigation_ref',${JSON.stringify(ref)});`);
const insert=(type,slug,content)=>{const data=Buffer.from(JSON.stringify({post_type:type,post_status:'publish',post_name:slug,post_title:'ヘッダーナビ検証 '+slug,post_content:content})).toString('base64');const row=wp(`if(get_page_by_path('${slug}',OBJECT,'${type}'))throw new Exception('fixture collision');$id=wp_insert_post(wp_slash(json_decode(base64_decode('${data}'),true)),true);if(is_wp_error($id))throw new Exception('fixture create');echo wp_json_encode(['id'=>$id,'slug'=>'${slug}','type'=>'${type}']);`);created.push(row);return row.id;};
const updateNav=(id,version)=>{const labels=version==='A'?['読む','学ぶ','会社案内']:['新しい案内','学びを探す','読む順番'];const links=labels.map((label,i)=>({label,url:'http://127.0.0.1:8098/'+['library/','learn/','site-company/'][i],kind:'custom'}));const content=links.map(a=>'<!-- wp:navigation-link '+JSON.stringify(a)+' /-->').join('');const encoded=Buffer.from(content).toString('base64');php(`wp_update_post(wp_slash(['ID'=>${id},'post_content'=>base64_decode('${encoded}')]));`);return labels;};
const sources=Object.fromEntries([...fs.readdirSync(theme+'/parts').filter(x=>x.startsWith('header')).map(x=>'parts/'+x),'patterns/header-sp-extras.php','inc/content-navigation.php','inc/header-navigation-settings.php','assets/js/header-navigation-editor.js','assets/css/theme.css'].map(x=>[x,createHash('sha256').update(fs.readFileSync(theme+'/'+x)).digest('hex')]));
const browser=await chromium.launch({args:['--no-sandbox']});let completed=false;
try{
const native=insert('post','header-complete-native','<!-- wp:heading --><h2 class="wp-block-heading" id="ranking">ナビの確認</h2><!-- /wp:heading --><!-- wp:paragraph --><p>本文の独立した案内を確認します。</p><!-- /wp:paragraph -->');
const nav=insert('wp_navigation','header-complete-navigation','');
// 独立CTAの遷移先は存在しない場合だけ専用labへ一時作成する。
if(!php('echo (int)!!get_page_by_path("lp");'))insert('page','lp','<!-- wp:paragraph --><p>相談の案内</p><!-- /wp:paragraph -->');
const nativePath=wp(`echo wp_json_encode(wp_parse_url(get_permalink(${native}),PHP_URL_PATH));`);
setRef(nav);
for(const version of ['A','B']){
 const expected=updateNav(nav,version);
 for(const js of [true,false]){
 const context=await browser.newContext({javaScriptEnabled:js,viewport:{width:1440,height:900}});const page=await context.newPage();
 for(const kind of ['native','shared'])for(const variant of variants)for(const sp of ['pc',...axes]){
  const width=sp==='pc'?1440:390;await page.setViewportSize({width,height:900});
  const url='http://127.0.0.1:8098'+(kind==='native'?nativePath:'/library/decision-design/')+'?wt=content_chrome:shared,content_paid_head:site,header:'+variant+',sp:'+(sp==='pc'?'search':sp);
  const name=[version,kind,variant,sp,js?'js':'nojs'].join(':');conditions.push({name,width});
  try{
   const response=await page.goto(url,{waitUntil:'load'});check(name+':http',response.status()===200);
   const header=page.locator('.wt-header');
   check(name+':one-header',await header.count()===1);
   const labels=[...new Set(await header.locator('.wp-block-navigation-item__label').allTextContents())];check(name+':stored-labels',JSON.stringify(labels)===JSON.stringify(expected),labels);
   check(name+':overflow',await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));
   let opened=false;
   const toggles=header.locator('.wp-block-navigation__responsive-container-open:visible');
   if(js&&((sp!=='pc'&&sp!=='text-nav')||(sp==='pc'&&variant==='cta'))){
    check(name+':open-button',await toggles.count()===1);
    if(await toggles.count()){await toggles.first().focus();await page.keyboard.press('Enter');await page.waitForTimeout(25);opened=true;check(name+':opened',await header.locator('.is-menu-open').count()===1);await page.keyboard.press('Escape');check(name+':closed',await header.locator('.is-menu-open').count()===0);check(name+':focus-return',await toggles.first().evaluate(e=>e===document.activeElement));await toggles.first().click();}
   }
   const visibleLinks=header.locator('.wp-block-navigation-item__content:visible');
   check(name+':links-visible',await visibleLinks.count()>=expected.length);
   if(await visibleLinks.count()){const href=await visibleLinks.first().getAttribute('href');await visibleLinks.first().click();check(name+':actual-navigation',new URL(page.url()).pathname===new URL(href,'http://127.0.0.1:8098').pathname);await page.goto(url,{waitUntil:'load'});}
   if(sp==='cta'){const cta=header.locator('.wt-header__spcta:visible');check(name+':independent-sp-cta',await cta.count()===1);if(await cta.count()){await cta.click();check(name+':sp-cta-navigation',new URL(page.url()).pathname==='/lp/');await page.goto(url,{waitUntil:'load'});}}
   if(sp==='pc'&&variant==='tel')check(name+':telephone-uri',await header.locator('.wt-header__tel').getAttribute('href')==='tel:0000000000');
   if(variant==='announce'){const close=header.locator('.wt-announce__close:visible');if(js&&await close.count()){await close.click();check(name+':announcement-dismissed',!(await header.locator('.wt-announce').isVisible()));}}
   if(sp==='pc'&&['cta','tel','two-rows'].includes(variant)){const cta=header.locator('.wt-header__cta a:visible');check(name+':independent-pc-cta',await cta.count()===1);if(await cta.count()){await cta.click();check(name+':cta-navigation',new URL(page.url()).pathname==='/lp/');await page.goto(url,{waitUntil:'load'});}}
   if(version==='B'&&js&&['pc','cta','text-nav'].includes(sp)&&kind==='native')await page.screenshot({path:path.join(out,`${kind}-${variant}-${sp}.png`)});
  }catch(e){check(name+':runtime',false,e.message.slice(0,200));}
 }
 await context.close();console.log(version,js?'JS':'noJS',conditions.length,'conditions',checks.filter(x=>!x.pass).length,'failures');
 }
}
for(const file of fs.readdirSync(theme+'/parts').filter(x=>x.startsWith('header'))){const source=fs.readFileSync(theme+'/parts/'+file,'utf8');check('static:'+file+':no-fixed-navigation',!source.includes('wp:navigation-link'));check('static:'+file+':no-layer1-layout',!/(contentSize|wideSize)/.test(source));}
const negative=source=>!/(contentSize|wideSize)|wp:navigation-link/.test(source);check('static:negative-navigation',!negative('<!-- wp:navigation-link {} /-->'));check('static:negative-wide',!negative('{"wideSize":"1440px"}'));
completed=true;
}finally{
 await browser.close();for(const row of created){php(`$p=get_post(${row.id});if(!$p||$p->post_name!=='${row.slug}')throw new Exception('identity mismatch');wp_delete_post(${row.id},true);`);check('cleanup:'+row.slug,php(`echo (int)(get_post(${row.id})===null);`)==='1');}
 const encoded=Buffer.from(original).toString('base64');php(`$v=json_decode(base64_decode('${encoded}'),true);if(null===$v)delete_option('theme_mods_helix-wt');else update_option('theme_mods_helix-wt',$v);`);
 check('settings-restored',php('echo wp_json_encode(get_option("theme_mods_helix-wt",null));')===original);
 fs.writeFileSync(out+'/matrix.json',JSON.stringify({completed,conditionCount:conditions.length,assertionCount:checks.length,conditions,checks,sourceDigests:sources},null,2)+'\n');console.log('FINAL',checks.length,checks.filter(x=>!x.pass));
}
if(checks.some(x=>!x.pass))process.exitCode=1;
