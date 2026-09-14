import {chromium}from'playwright';import {execFileSync}from'node:child_process';import fs from'node:fs';import path from'node:path';import os from'node:os';import {randomBytes,createHash}from'node:crypto';
const out='docs/research/2026-09-14-header-navigation-editing';fs.mkdirSync(out,{recursive:true});
const php=code=>execFileSync('docker',['exec','helix-content-wp','php','-r',`require '/var/www/html/wp-load.php'; ${code}`],{encoding:'utf8'}).trim();const wp=c=>JSON.parse(php(c));
if(php('echo get_option("blogname");')!=='HELIX Content Lab')throw Error('dedicated lab required');
const original=php('echo wp_json_encode(get_option("theme_mods_helix-wt",null));');const originals=wp('echo wp_json_encode(get_posts(["post_type"=>"wp_template_part","post_status"=>"any","numberposts"=>-1]));');
const recovery=path.join(os.tmpdir(),'helix-header-editing-restore.json');if(fs.existsSync(recovery))throw Error('Pending lab recovery; run scripts/recover-header-navigation-editing.mjs first');
const variants=['header','header-nav','header-cta','header-announce','header-center','header-two-rows','header-overlay','header-tel','header-band'];
const checks=[],conditions=[],created=[],createdPartIds=[];let lowId;const check=(name,pass)=>{checks.push({name,pass:!!pass});if(!pass)throw Error(name);};
let completed=false,cleanupDone=false;
const snapshot=()=>fs.writeFileSync(recovery,JSON.stringify({mods:JSON.parse(original),originals,created,createdPartIds,lowId}));snapshot();
const recover=()=>{if(cleanupDone)return;execFileSync(process.execPath,['scripts/recover-header-navigation-editing.mjs'],{stdio:'pipe'});cleanupDone=true;};
let browser;
for(const signal of ['SIGINT','SIGTERM'])process.once(signal,async()=>{try{await browser?.close();recover();}finally{process.exit(128+(signal==='SIGINT'?2:15));}});
for(const event of ['uncaughtException','unhandledRejection'])process.once(event,async error=>{console.error(String(error));try{await browser?.close();recover();}finally{process.exit(1);}});
browser=await chromium.launch({args:['--no-sandbox']});const page=await browser.newPage({viewport:{width:1440,height:1000}});
page.setDefaultTimeout(10000);
const credentials=JSON.parse(fs.readFileSync(path.join(process.env.WTCF_STATE_DIR||path.join(os.tmpdir(),'helix-content-lab'),'credentials.json')));
const login=async(p,user,password)=>{await p.goto('http://127.0.0.1:8098/wp-login.php');await p.locator('#user_login').fill(user);await p.locator('#user_pass').fill(password);await p.locator('#wp-submit').click();await p.waitForURL('**/wp-admin/**');};
const openPart=async slug=>{await page.goto(`http://127.0.0.1:8098/wp-admin/site-editor.php?postId=helix-wt%2F%2F${slug}&postType=wp_template_part&canvas=edit`);await page.waitForFunction(()=>window.wp?.data?.select('core/block-editor')?.getBlocks()?.length>0);const start=page.getByRole('button',{name:'Get started',exact:true});if(await start.isVisible())await start.click();const exit=page.getByRole('button',{name:'Exit code editor',exact:true});if(await exit.isVisible())await exit.click();await page.locator('iframe[name="editor-canvas"]').waitFor();};
const savePart=async()=>{await page.getByRole('button',{name:'Save',exact:true}).first().click();const confirm=page.getByRole('dialog').getByRole('button',{name:'Save',exact:true});if(await confirm.waitFor({timeout:1000}).then(()=>true,()=>false))await confirm.click();await page.waitForFunction(()=>!wp.data.select('core/editor').isSavingPost()&&!wp.data.select('core/editor').isEditedPostDirty());for(const id of wp('echo wp_json_encode(wp_list_pluck(get_posts(["post_type"=>"wp_template_part","post_status"=>"any","numberposts"=>-1]),"ID"));'))if(!originals.some(p=>p.ID===id)&&!createdPartIds.includes(id))createdPartIds.push(id);snapshot();};
try{
 for(const letter of ['A','B']){const content='<!-- wp:navigation-link '+JSON.stringify({label:'編集ナビ'+letter,url:'http://127.0.0.1:8098/library/',kind:'custom'})+' /-->';const encoded=Buffer.from(content).toString('base64');const slug='header-editor-nav-'+letter.toLowerCase();const id=Number(php(`if(get_page_by_path('${slug}',OBJECT,'wp_navigation'))throw new Exception('collision');echo wp_insert_post(wp_slash(['post_type'=>'wp_navigation','post_status'=>'publish','post_name'=>'${slug}','post_title'=>'編集ナビ${letter}','post_content'=>base64_decode('${encoded}')]));`));created.push(id);snapshot();}
 if(process.argv.includes('--test-signal-cleanup')){process.kill(process.pid,'SIGTERM');await new Promise(()=>{});}
 if(process.argv.includes('--test-rejection-cleanup')){Promise.reject(new Error('synthetic cleanup rejection'));await new Promise(()=>{});}
 if(process.argv.includes('--test-exception-cleanup')){setTimeout(()=>{throw Error('synthetic cleanup exception');},0);await new Promise(()=>{});}
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
 const canvas=page.frameLocator('iframe[name="editor-canvas"]');await canvas.locator('[data-type="core/navigation"]:visible').first().click();await page.getByRole('button',{name:'Settings',exact:true}).click();
 const choose=async(name,current)=>{await page.getByRole('tabpanel',{name:'List View',exact:true}).getByRole('button',{name:current,exact:true}).click();await page.getByRole('menuitemradio',{name:new RegExp(name)}).click();};
 const pending=page.locator('.wt-header-navigation-pending');
 let posts=0;page.on('request',r=>{if(r.method()==='POST'&&r.url().includes('/helix-wt/v1/header-navigation'))posts++;});
 await choose('編集ナビA','編集ナビB');await pending.waitFor();
 check('stage:no-global-post',posts===0);check('stage:saved-ref-remains-B',Number(php('echo get_theme_mod("wt_content_navigation_ref");'))===created[1]);
 check('stage:previous-and-next-visible',(await pending.innerText()).includes('編集ナビB → 変更後の参照先: 編集ナビA'));
 check('device:SP-label',await canvas.getByText(/SP専用テキストナビ/).count()>0);
 await page.screenshot({path:out+'/pending-reference.png'});
 await page.getByRole('button',{name:'Undo',exact:true}).click();await pending.waitFor({state:'hidden'});check('stage:undo-no-post',posts===0);
 await page.getByRole('button',{name:'Redo',exact:true}).click();await pending.waitFor();check('stage:redo-no-post',posts===0);
 page.on('dialog',d=>d.accept());await page.goto('http://127.0.0.1:8098/wp-admin/index.php');await openPart('header');
 check('stage:leave-without-global-save',Number(php('echo get_theme_mod("wt_content_navigation_ref");'))===created[1]);check('stage:reenter-no-pending',await pending.count()===0);
 await canvas.locator('[data-type="core/navigation"]:visible').first().click();
 if(!await page.getByRole('button',{name:'編集ナビB',exact:true}).isVisible())await page.getByRole('button',{name:'Settings',exact:true}).click();
 await choose('編集ナビA','編集ナビB');await pending.waitFor();
 await page.route('**/helix-wt/v1/header-navigation**',r=>r.fulfill({status:500,contentType:'application/json',body:JSON.stringify({code:'test_failure',message:'検証用の保存失敗',data:{status:500}})}));
 await pending.getByRole('button',{name:'全ヘッダーに適用',exact:true}).click();await page.locator('.components-notice__content').filter({hasText:'検証用の保存失敗'}).waitFor();
 check('failure:saved-ref-remains-B',Number(php('echo get_theme_mod("wt_content_navigation_ref");'))===created[1]);check('failure:pending-recoverable',await pending.count()===1);
 await page.screenshot({path:out+'/rest-failure.png'});await page.unroute('**/helix-wt/v1/header-navigation**');
 const beforeRetry=posts;await pending.getByRole('button',{name:'全ヘッダーに適用',exact:true}).evaluate(e=>{e.click();e.click();});await pending.waitFor({state:'hidden'});
 check('retry:one-post-for-double-click',posts===beforeRetry+1);check('retry:saved-A',Number(php('echo get_theme_mod("wt_content_navigation_ref");'))===created[0]);
 await page.getByRole('button',{name:'Undo',exact:true}).click();await pending.waitFor();check('undo-after-apply:global-remains-A',Number(php('echo get_theme_mod("wt_content_navigation_ref");'))===created[0]);
 await pending.getByRole('button',{name:'変更を取り消す',exact:true}).click();await pending.waitFor({state:'hidden'});check('cancel-after-apply:restores-current-A',Number(php('echo get_theme_mod("wt_content_navigation_ref");'))===created[0]);
 let release;let requestArrived;const arrived=new Promise(resolve=>requestArrived=resolve);const delayed=new Promise(resolve=>release=resolve);
 await page.route('**/helix-wt/v1/header-navigation**',async r=>{requestArrived();await delayed;await r.continue();});
 const beforeRapid=posts;await choose('編集ナビB','編集ナビA');await pending.waitFor();await pending.getByRole('button',{name:'全ヘッダーに適用',exact:true}).click();await arrived;
 await choose('編集ナビA','編集ナビB');release();await pending.waitFor();
 await page.waitForFunction(ref=>Number(helixWTNavigation.ref)===ref,created[1]);await page.unroute('**/helix-wt/v1/header-navigation**');
 check('rapid-change:only-confirmed-B-posted',posts===beforeRapid+1&&Number(php('echo get_theme_mod("wt_content_navigation_ref");'))===created[1]);
 check('rapid-change:latest-A-remains-pending',(await pending.innerText()).includes('変更後の参照先: 編集ナビA'));
 await pending.getByRole('button',{name:'変更を取り消す',exact:true}).click();await pending.waitFor({state:'hidden'});check('rapid-change:cancel-restores-B',Number(php('echo get_theme_mod("wt_content_navigation_ref");'))===created[1]);

 await page.getByRole('button',{name:'Settings',exact:true}).click();
 const navText=canvas.getByText('編集ナビB',{exact:true}).filter({visible:true}).first();await navText.click();
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
 const savedHeaderId=Number(php("echo get_page_by_path('header',OBJECT,'wp_template_part')->ID;"));check('db-backed:exists',savedHeaderId>0);
 const direct=await page.goto('http://127.0.0.1:8098/wp-admin/post.php?post='+savedHeaderId+'&action=edit');check('db-backed:numeric-post-php-rejected-by-core',direct.status()===403);
 await openPart('header');await page.frameLocator('iframe[name="editor-canvas"]').getByText('SP専用テキストナビ',{exact:true}).waitFor();
 check('db-backed:site-editor-saved-part',await page.evaluate(()=>wp.data.select('core/editor').getEditedPostContent().includes('検証ヘッダー')));
 const identity=await page.evaluate(()=>({id:wp.data.select('core/editor').getCurrentPostId(),slug:wp.data.select('core/editor').getEditedPostAttribute('slug')}));check('db-backed:slug-available',identity.slug==='header');
 // Runtime state adapter: WP's supported route uses theme//slug; simulate a numeric ID while retaining its real slug.
 await page.evaluate(id=>{const editor=wp.data.select('core/editor');window.__headerOriginalId=editor.getCurrentPostId;editor.getCurrentPostId=()=>id;wp.data.dispatch('core/block-editor').clearSelectedBlock();},savedHeaderId);
 await page.frameLocator('iframe[name="editor-canvas"]').locator('[data-type="core/navigation"]:visible').first().click();await page.waitForTimeout(100);check('db-backed:numeric-state-id',await page.evaluate(()=>typeof wp.data.select('core/editor').getCurrentPostId()==='number'));check('db-backed:numeric-state-SP-label',await page.frameLocator('iframe[name="editor-canvas"]').getByText('SP専用テキストナビ',{exact:true}).count()>0);
 await page.evaluate(()=>{wp.data.select('core/editor').getCurrentPostId=window.__headerOriginalId;delete window.__headerOriginalId;});
 await page.screenshot({path:out+'/db-backed-header.png'});
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
 const bodyContent='<!-- wp:navigation '+JSON.stringify({ref:created[0],className:'wt-header__textnav wt-header-navigation'})+' /-->';
 for(const kind of ['post','wp_template_part']){
  const slug=kind==='post'?'header-editor-body':'footer-header-editor-proof';const encoded=Buffer.from(bodyContent).toString('base64');
  const id=Number(php(`if(get_page_by_path('${slug}',OBJECT,'${kind}'))throw new Exception('collision');$id=wp_insert_post(wp_slash(['post_type'=>'${kind}','post_status'=>'publish','post_name'=>'${slug}','post_title'=>'独立ナビ検証','post_content'=>base64_decode('${encoded}')]));if('${kind}'==='wp_template_part')wp_set_object_terms($id,'helix-wt','wp_theme');echo $id;`));created.push(id);snapshot();
  const route=kind==='post'?`post.php?post=${id}&action=edit`:`site-editor.php?postId=helix-wt%2F%2F${slug}&postType=wp_template_part&canvas=edit`;
  await page.goto('http://127.0.0.1:8098/wp-admin/'+route);await page.waitForFunction(()=>window.wp?.data?.select('core/block-editor')?.getBlocks()?.length>0);
  const exit=page.getByRole('button',{name:'Exit code editor',exact:true});if(await exit.isVisible())await exit.click();
  const frame=page.frameLocator('iframe[name="editor-canvas"]');await frame.getByText('編集ナビA',{exact:true}).first().waitFor();
  check('outside-editor:'+kind+':independent-A',await frame.getByText('保存した共通案内',{exact:true}).count()===0);
  check('outside-editor:'+kind+':no-SP-binding',await frame.locator('.wt-header-editor-sp-navigation').count()===0);
  check('outside-editor:'+kind+':no-global-pending',await page.locator('.wt-header-navigation-pending').count()===0);
  await page.screenshot({path:out+'/'+kind+'-independent.png'});
 }
 await openPart('header');
 const before=php('echo get_theme_mod("wt_content_navigation_ref");');
 for(const value of [-1,1.5,'12',[],{},2147483647]){const result=await page.evaluate(async ref=>{try{await wp.apiFetch({path:'/helix-wt/v1/header-navigation',method:'POST',data:{ref}});return 200;}catch(e){return e.data?.status;}},value);check('REST:invalid-'+JSON.stringify(value),result===400);check('REST:unchanged-'+JSON.stringify(value),php('echo get_theme_mod("wt_content_navigation_ref");')===before);}
 for(const nonce of ['', 'invalid']){const response=await page.request.post('http://127.0.0.1:8098/wp-json/helix-wt/v1/header-navigation',{headers:nonce?{'X-WP-Nonce':nonce}:{},data:{ref:0}});check('REST:nonce-'+(nonce||'missing'),[401,403].includes(response.status()));check('REST:nonce-unchanged',php('echo get_theme_mod("wt_content_navigation_ref");')===before);}
 const password=randomBytes(24).toString('hex');lowId=Number(php(`if(username_exists('header-low-fixture'))throw new Exception('user collision');$id=wp_create_user('header-low-fixture','${password}');if(is_wp_error($id))throw new Exception('user create');(new WP_User($id))->set_role('subscriber');echo $id;`));
 snapshot();const low=await browser.newPage();await login(low,'header-low-fixture',password);const lowResponse=await low.evaluate(async()=>{const nonce=await (await fetch('/wp-admin/admin-ajax.php?action=rest-nonce')).text();const r=await fetch('/wp-json/helix-wt/v1/header-navigation',{method:'POST',headers:{'Content-Type':'application/json','X-WP-Nonce':nonce},body:'{"ref":0}'});return {status:r.status,body:await r.json()};});check('REST:low-permission',lowResponse.status===403&&lowResponse.body.code==='rest_forbidden');check('REST:low-unchanged',php('echo get_theme_mod("wt_content_navigation_ref");')===before);await low.close();
 await openPart('header');await page.getByRole('button',{name:'共通ヘッダーナビ',exact:true}).click();await page.getByLabel('参照するナビゲーション',{exact:true}).selectOption('0');await page.getByRole('button',{name:'参照先を保存',exact:true}).click();await page.locator('.components-notice__content').filter({hasText:'共通ヘッダーナビを保存しました。'}).waitFor();check('UI:none',php('echo get_theme_mod("wt_content_navigation_ref");')==='0');
 completed=true;console.log('editor checks',checks.length);
}catch(e){await page.screenshot({path:path.join(os.tmpdir(),'helix-header-editor-failure.png')});console.error(e.message);throw e;}
finally{
 await browser.close();
 recover();
 const files=['assets/js/header-navigation-editor.js','inc/content-navigation.php','inc/header-navigation-settings.php'].map(f=>'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/'+f).concat(['scripts/verify-header-navigation-editing.mjs','scripts/recover-header-navigation-editing.mjs']);const sourceDigests=Object.fromEntries(files.map(f=>[f,createHash('sha256').update(fs.readFileSync(f)).digest('hex')]));
 fs.writeFileSync(out+'/verify.json',JSON.stringify({completed,sourceDigests,checks,conditions,conditionCount:conditions.length,assertionCount:checks.length,cleanup:true},null,2)+'\n');
}
