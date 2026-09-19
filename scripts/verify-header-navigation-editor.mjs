import {chromium}from'playwright';import {execFileSync}from'node:child_process';import fs from'node:fs';import path from'node:path';import os from'node:os';import {randomBytes}from'node:crypto';
const out='docs/research/2026-09-13-header-navigation-complete/editor';fs.mkdirSync(out,{recursive:true});
const php=code=>execFileSync('docker',['exec','helix-content-wp','php','-r',`require '/var/www/html/wp-load.php'; ${code}`],{encoding:'utf8'}).trim();const wp=c=>JSON.parse(php(c));
if(php('echo get_option("blogname");')!=='HELIX Content Lab')throw Error('dedicated lab required');
const original=php('echo wp_json_encode(get_option("theme_mods_helix-wt",null));');const originals=wp('echo wp_json_encode(get_posts(["post_type"=>"wp_template_part","post_status"=>"any","numberposts"=>-1]));');
const variants=['header','header-nav','header-cta','header-announce','header-center','header-two-rows','header-overlay','header-tel','header-band'];
const checks=[],conditions=[],created=[];let lowId;const check=(name,pass)=>{checks.push({name,pass:!!pass});if(!pass)throw Error(name);};
const browser=await chromium.launch({args:['--no-sandbox']});const page=await browser.newPage({viewport:{width:1440,height:1000}});
page.setDefaultTimeout(10000);
const credentials=JSON.parse(fs.readFileSync(path.join(process.env.WTCF_STATE_DIR||path.join(os.tmpdir(),'helix-content-lab'),'credentials.json')));
const login=async(p,user,password)=>{await p.goto('http://127.0.0.1:8098/wp-login.php');await p.locator('#user_login').fill(user);await p.locator('#user_pass').fill(password);await p.locator('#wp-submit').click();await p.waitForURL('**/wp-admin/**');};
const openPart=async slug=>{await page.goto(`http://127.0.0.1:8098/wp-admin/site-editor.php?postId=helix-wt%2F%2F${slug}&postType=wp_template_part&canvas=edit`);await page.waitForFunction(()=>window.wp?.data?.select('core/block-editor')?.getBlocks()?.length>0);const start=page.getByRole('button',{name:'Get started',exact:true});if(await start.isVisible())await start.click();const exit=page.getByRole('button',{name:'Exit code editor',exact:true});if(await exit.isVisible())await exit.click();await page.locator('iframe[name="editor-canvas"]').waitFor();};
const savePart=async()=>{await page.getByRole('button',{name:'Save',exact:true}).first().click();const confirm=page.getByRole('dialog').getByRole('button',{name:'Save',exact:true});if(await confirm.waitFor({timeout:1000}).then(()=>true,()=>false))await confirm.click();await page.waitForFunction(()=>!wp.data.select('core/editor').isSavingPost()&&!wp.data.select('core/editor').isEditedPostDirty());};
try{
 for(const letter of ['A','B']){const content='<!-- wp:navigation-link '+JSON.stringify({label:'編集ナビ'+letter,url:'http://127.0.0.1:8098/library/',kind:'custom'})+' /-->';const encoded=Buffer.from(content).toString('base64');const slug='header-editor-nav-'+letter.toLowerCase();const id=Number(php(`if(get_page_by_path('${slug}',OBJECT,'wp_navigation'))throw new Exception('collision');echo wp_insert_post(wp_slash(['post_type'=>'wp_navigation','post_status'=>'publish','post_name'=>'${slug}','post_title'=>'編集ナビ${letter}','post_content'=>base64_decode('${encoded}')]));`));created.push(id);}
 await login(page,'lab_admin',credentials.admin);
 await openPart('header');await page.getByRole('button',{name:'共通ヘッダーナビ',exact:true}).click();
 for(const [i,ref]of created.entries()){
  await page.getByLabel('参照するナビゲーション',{exact:true}).selectOption(String(ref));
  await page.getByRole('button',{name:'参照先を保存',exact:true}).click();await page.locator('.components-notice__content').filter({hasText:'共通ヘッダーナビを保存しました。'}).waitFor();check('UI:save-reference-'+i,Number(php('echo get_theme_mod("wt_content_navigation_ref");'))===ref);
  await page.reload();await page.getByRole('button',{name:'共通ヘッダーナビ',exact:true}).click();await page.getByLabel('参照するナビゲーション',{exact:true}).waitFor();check('UI:reenter-reference-'+i,await page.getByLabel('参照するナビゲーション',{exact:true}).inputValue()===String(ref));
  await page.frameLocator('iframe[name="editor-canvas"]').getByText('編集ナビ'+['A','B'][i],{exact:true}).first().waitFor();
  check('UI:canvas-reference-'+i,await page.frameLocator('iframe[name="editor-canvas"]').getByText('編集ナビ'+['A','B'][i],{exact:true}).count()>0);
 }
 await page.screenshot({path:out+'/reference-selection.png'});
 // 標準Navigation編集のラベルを実際に編集し、同じentityへ保存する。
 await page.getByRole('button',{name:'共通ヘッダーナビ',exact:true}).click();
 const canvas=page.frameLocator('iframe[name="editor-canvas"]');await canvas.locator('[data-type="core/navigation"]:visible').first().click();const navText=canvas.getByText('編集ナビB',{exact:true}).filter({visible:true}).first();await navText.click();
 const editable=canvas.locator('[contenteditable="true"]').filter({hasText:'編集ナビB'}).first();
 await editable.fill('保存した共通案内');await savePart();
 check('UI:standard-navigation-entity-save',php(`echo get_post(${created[1]})->post_content;`).includes('保存した共通案内'));
 await page.reload();await page.frameLocator('iframe[name="editor-canvas"]').getByText('保存した共通案内',{exact:true}).filter({visible:true}).first().waitFor();check('UI:standard-navigation-reenter',true);
 for(const slug of variants){
  await openPart(slug);
  await page.getByRole('button',{name:'Options',exact:true}).click();await page.getByText('Code editor',{exact:true}).click();
  const source=fs.readFileSync(`docs/research/2026-09-05-design-prototype-03/theme/helix-wt/parts/${slug}.html`,'utf8').replace('<!-- wp:group {','<!-- wp:group {"metadata":{"name":"検証ヘッダー"},');
  await page.locator('.editor-post-text-editor').fill(source);await page.locator('.editor-post-text-editor').blur();await savePart();
  await page.reload();await page.locator('.editor-post-text-editor').waitFor();
  console.log('saved part',slug);check('UI:'+slug+':saved-reentered',await page.evaluate(()=>wp.data.select('core/editor').getEditedPostContent().includes('検証ヘッダー')));
  await page.getByRole('button',{name:'Exit code editor',exact:true}).click();
  check('UI:'+slug+':valid-blocks',await page.evaluate(()=>(() => { const valid = blocks => blocks.every(b => b.isValid !== false && valid(b.innerBlocks || [])); return valid(wp.data.select('core/block-editor').getBlocks()); })()));
  if(slug==='header-center')await page.screenshot({path:out+'/saved-header-center.png'});
 }
 // Site Editor保存後のDB partをnative/sharedの公開ブラウザで照合する。
 for(const js of [true,false])for(const width of [390,1440]){
  const publicPage=await browser.newPage({viewport:{width,height:900},javaScriptEnabled:js});
  for(const slug of variants)for(const kind of ['native','shared']){
   const h=slug==='header'?'search':slug.replace('header-','');const name=`saved-db:${slug}:${kind}:${width}:${js}`;conditions.push(name);
   const route=kind==='native'?'/?p=1&wt=':'/library/decision-design/?wt=';
   await publicPage.goto('http://127.0.0.1:8098'+route+'content_chrome:shared,content_paid_head:site,header:'+h+',sp:search',{waitUntil:'load'});
   check(name+':saved-navigation',await publicPage.locator('.wt-header').getByText('保存した共通案内',{exact:true}).count()>0);
   const toggles=publicPage.locator('.wt-header .wp-block-navigation__responsive-container-open:visible');if(js&&await toggles.count())await toggles.first().click();
   check(name+':visible-navigation',await publicPage.locator('.wt-header').getByText('保存した共通案内',{exact:true}).filter({visible:true}).count()>0);
   check(name+':overflow',await publicPage.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));
  }
  await publicPage.close();
 }
 const before=php('echo get_theme_mod("wt_content_navigation_ref");');
 for(const value of [-1,1.5,'12',[],{},2147483647]){const result=await page.evaluate(async ref=>{try{await wp.apiFetch({path:'/helix-wt/v1/header-navigation',method:'POST',data:{ref}});return 200;}catch(e){return e.data?.status;}},value);check('REST:invalid-'+JSON.stringify(value),result===400);check('REST:unchanged-'+JSON.stringify(value),php('echo get_theme_mod("wt_content_navigation_ref");')===before);}
 for(const nonce of ['', 'invalid']){const response=await page.request.post('http://127.0.0.1:8098/wp-json/helix-wt/v1/header-navigation',{headers:nonce?{'X-WP-Nonce':nonce}:{},data:{ref:0}});check('REST:nonce-'+(nonce||'missing'),[401,403].includes(response.status()));check('REST:nonce-unchanged',php('echo get_theme_mod("wt_content_navigation_ref");')===before);}
 const password=randomBytes(24).toString('hex');lowId=Number(php(`if(username_exists('header-low-fixture'))throw new Exception('user collision');$id=wp_create_user('header-low-fixture','${password}');if(is_wp_error($id))throw new Exception('user create');(new WP_User($id))->set_role('subscriber');echo $id;`));
 const low=await browser.newPage();await login(low,'header-low-fixture',password);const lowResponse=await low.evaluate(async()=>{const nonce=await (await fetch('/wp-admin/admin-ajax.php?action=rest-nonce')).text();const r=await fetch('/wp-json/helix-wt/v1/header-navigation',{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':nonce},body:'{"ref":0}'});return {status:r.status,body:await r.json()};});check('REST:low-permission',lowResponse.status===403&&lowResponse.body.code==='rest_forbidden');check('REST:low-unchanged',php('echo get_theme_mod("wt_content_navigation_ref");')===before);await low.close();
 await openPart('header');await page.getByRole('button',{name:'共通ヘッダーナビ',exact:true}).click();await page.getByLabel('参照するナビゲーション',{exact:true}).selectOption('0');await page.getByRole('button',{name:'参照先を保存',exact:true}).click();await page.locator('.components-notice__content').filter({hasText:'共通ヘッダーナビを保存しました。'}).waitFor();check('UI:none',php('echo get_theme_mod("wt_content_navigation_ref");')==='0');
 console.log('editor checks',checks.length);
}catch(e){await page.screenshot({path:path.join(os.tmpdir(),'helix-header-editor-failure.png')});console.error(e.message);throw e;}
finally{
 await browser.close();
 const now=wp('echo wp_json_encode(get_posts(["post_type"=>"wp_template_part","post_status"=>"any","numberposts"=>-1]));');
 for(const p of now.filter(p=>variants.includes(p.post_name))){const old=originals.find(o=>o.ID===p.ID);if(old){const encoded=Buffer.from(JSON.stringify(old)).toString('base64');php(`wp_update_post(wp_slash(json_decode(base64_decode('${encoded}'),true)));`);}else php(`wp_delete_post(${p.ID},true);`);}
 for(const id of created)php(`wp_delete_post(${id},true);`);if(lowId)php(`require_once ABSPATH.'wp-admin/includes/user.php';wp_delete_user(${lowId});`);
 const encoded=Buffer.from(original).toString('base64');php(`$v=json_decode(base64_decode('${encoded}'),true);if(null===$v)delete_option('theme_mods_helix-wt');else update_option('theme_mods_helix-wt',$v);`);
 fs.writeFileSync(out+'/verify.json',JSON.stringify({checks,conditions,conditionCount:conditions.length,assertionCount:checks.length,cleanup:true},null,2)+'\n');
}
