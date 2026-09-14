import{chromium}from'playwright';import{execFileSync}from'node:child_process';import fs from'node:fs';
const out='docs/research/2026-09-13-header-navigation-complete';const theme='docs/research/2026-09-05-design-prototype-03/theme/helix-wt';
const php=c=>execFileSync('docker',['exec','helix-content-wp','php','-r',`require '/var/www/html/wp-load.php';${c}`],{encoding:'utf8'}).trim();const wp=c=>JSON.parse(php(c));if(php('echo get_option("blogname");')!=='HELIX Content Lab')throw Error('lab');
const original=php('echo wp_json_encode(get_option("theme_mods_helix-wt",null));');const rows=[],conditions=[],created=[];const check=(name,pass,detail)=>rows.push({name,pass:!!pass,...(!pass?{detail}:{})});const set=ref=>php(`set_theme_mod('wt_content_navigation_ref',json_decode(base64_decode('${Buffer.from(JSON.stringify(ref)).toString('base64')}'),true));`);
const insert=(type,slug,content)=>{const encoded=Buffer.from(JSON.stringify({post_type:type,post_name:slug,post_status:'publish',post_title:slug,post_content:content})).toString('base64');const id=Number(php(`if(get_page_by_path('${slug}',OBJECT,'${type}'))throw new Exception('collision');echo wp_insert_post(wp_slash(json_decode(base64_decode('${encoded}'),true)));`));created.push(id);return id;};
const headers=['search','nav','cta','announce','center','two-rows','overlay','tel','band'],axes=['search','right','left','cta','text-nav','center-logo'];
const browser=await chromium.launch({args:['--no-sandbox']});
try{
const content='<!-- wp:navigation-submenu {"label":"読みもの","url":"/library/"} --><!-- wp:navigation-link {"label":"階層の案内","url":"/learn/"} /--><!-- /wp:navigation-submenu --><!-- wp:navigation-link {"label":"長い日本語の案内を確認するための項目","url":"/site-company/"} /-->';
const nav=insert('wp_navigation','header-boundary-nav',content), other=insert('wp_navigation','header-boundary-other','<!-- wp:navigation-link {"label":"本文専用の案内","url":"/learn/"} /-->');
const native=insert('post','header-boundary-native','<!-- wp:navigation {"ref":'+other+',"overlayMenu":"never"} /-->');
const nativePath=wp(`echo wp_json_encode(wp_parse_url(get_permalink(${native}),PHP_URL_PATH));`);
const url=(kind,h,sp='cta')=>'http://127.0.0.1:8098'+(kind==='native'?nativePath:'/library/decision-design/')+'?wt=content_chrome:shared,content_paid_head:site,header:'+h+',sp:'+sp;
// 不正参照は全型・両経路で確認。JS無効でも固定リンクや暗黙一覧が出ない。
const page=await browser.newPage({viewport:{width:390,height:900},javaScriptEnabled:false});
for(const [state,ref]of [['unset',null],['zero',0],['missing',2147483647],['wrong-type',native],['malformed','abc'],['array',[nav]],['draft',nav],['private',nav],['trash',nav],['empty',nav]]){
 if(state==='unset')php('remove_theme_mod("wt_content_navigation_ref");');else set(ref);
 if(['draft','private','trash'].includes(state))php(`wp_update_post(['ID'=>${nav},'post_status'=>'${state}']);`);
 if(state==='empty')php(`wp_update_post(['ID'=>${nav},'post_status'=>'publish','post_content'=>'']);`);
 for(const kind of['native','shared'])for(const h of headers){const name=`invalid:${state}:${kind}:${h}`;conditions.push(name);await page.goto(url(kind,h),{waitUntil:'load'});check(name+':navigation-empty',await page.locator('.wt-header .wp-block-navigation-item__content').count()===0);check(name+':cta-independent',await page.locator('.wt-header__spcta:visible').count()===1);}
}
const encoded=Buffer.from(content).toString('base64');php(`wp_update_post(wp_slash(['ID'=>${nav},'post_status'=>'publish','post_content'=>base64_decode('${encoded}')]));`);set(nav);await page.close();
for(const js of[true,false]){
 const p=await browser.newPage({viewport:{width:390,height:900},javaScriptEnabled:js});p.setDefaultTimeout(2500);
 for(const kind of['native','shared'])for(const h of headers)for(const sp of axes){const name=`hierarchy:${kind}:${h}:${sp}:${js}`;conditions.push(name);try{
  await p.goto(url(kind,h,sp),{waitUntil:'load'});const toggle=p.locator('.wt-header .wp-block-navigation__responsive-container-open:visible');if(js&&sp!=='text-nav'&&await toggle.count())await toggle.first().click();
  if(js){const submenu=p.locator('.wt-header .wp-block-navigation-submenu__toggle:visible').first();if(await submenu.count())await submenu.click();}
  const child=p.locator('.wt-header a').filter({hasText:'階層の案内'}).filter({visible:true});
  check(name+':child-visible',await child.count()>0);check(name+':no-overflow',await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));
  if(await child.count()){await child.first().click();check(name+':child-navigation',new URL(p.url()).pathname==='/learn/');}
  if(h==='band'&&kind==='native'){await p.goto(url(kind,h,sp),{waitUntil:'load'});await p.screenshot({path:`${out}/hierarchy-${sp}-${js}.png`});}
 }catch(e){check(name+':interaction',false,e.message.slice(0,120));}}
 await p.close();console.log('hierarchy',js,rows.filter(x=>!x.pass).length,'failures');
}
const p=await browser.newPage({viewport:{width:1440,height:900}});
const styles=['default',...fs.readdirSync(theme+'/styles').filter(x=>x.endsWith('.json')).map(x=>x.replace('.json',''))];
for(const style of styles)for(const h of headers){const name=`scale:${style}:${h}`;conditions.push(name);await p.goto(url('native',h,'search'),{waitUntil:'load'});
 if(style!=='default'){const css=php(`$t=new WP_Theme_JSON(json_decode(file_get_contents(get_stylesheet_directory().'/theme.json'),true));$t->merge(new WP_Theme_JSON(json_decode(file_get_contents(get_stylesheet_directory().'/styles/${style}.json'),true)));echo $t->get_stylesheet();`);await p.addStyleTag({content:css});}
 await p.addStyleTag({content:'body{--wp--style--global--content-size:620px;--wp--style--global--wide-size:840px;--wt-header-max:900px}'});
 const sizes=await p.locator('.wt-header__row').evaluateAll(es=>es.map(e=>({max:getComputedStyle(e).maxWidth,width:e.getBoundingClientRect().width,content:getComputedStyle(e).getPropertyValue('--wp--style--global--content-size').trim(),wide:getComputedStyle(e).getPropertyValue('--wp--style--global--wide-size').trim()})));
 check(name+':parent-propagates',sizes.every(x=>x.max==='900px'&&x.content==='620px'&&x.wide==='840px'&&x.width<=901),sizes);
}
for(const width of[320,599,600,844])for(const h of headers){const name=`boundary-width:${width}:${h}`;conditions.push(name);await p.setViewportSize({width,height:900});await p.goto(url('native',h,'center-logo'),{waitUntil:'load'});check(name+':overflow',await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));}
await p.goto(url('native','band'),{waitUntil:'load'});check('scope:body-navigation-unchanged',await p.locator('main').getByText('本文専用の案内',{exact:true}).count()===1);check('scope:header-does-not-use-body-navigation',await p.locator('.wt-header').getByText('本文専用の案内',{exact:true}).count()===0);await p.close();
}finally{
await browser.close();for(const id of created){php(`wp_delete_post(${id},true);`);check('cleanup:'+id,php(`echo (int)(get_post(${id})===null);`)==='1');}const e=Buffer.from(original).toString('base64');php(`$v=json_decode(base64_decode('${e}'),true);if(null===$v)delete_option('theme_mods_helix-wt');else update_option('theme_mods_helix-wt',$v);`);check('settings-restored',php('echo wp_json_encode(get_option("theme_mods_helix-wt",null));')===original);fs.writeFileSync(out+'/boundaries.json',JSON.stringify({conditionCount:conditions.length,assertionCount:rows.length,conditions,rows},null,2)+'\n');console.log('boundaries',conditions.length,rows.length,rows.filter(x=>!x.pass));}
if(rows.some(x=>!x.pass))process.exitCode=1;
