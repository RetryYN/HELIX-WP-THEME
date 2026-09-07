import { createHash } from 'node:crypto';
import { chromium } from 'playwright';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';import os from 'node:os';import path from 'node:path';import assert from 'node:assert/strict';import { fileURLToPath } from 'node:url';
const root=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'..');const state=process.env.WTCF_STATE_DIR||path.join(os.tmpdir(),'helix-content-lab');
const cli=['run','--rm','--network','helix-content-lab','--env-file',path.join(state,'wp.env'),'--volumes-from','helix-content-wp','--user','33:33','wordpress:cli-php8.3','wp'];
const wp=args=>execFileSync('docker',[...cli,...args],{encoding:'utf8',stdio:['ignore','pipe','pipe']}).trim();assert.equal(wp(['option','get','blogname']),'HELIX Content Lab');
const manifest=JSON.parse(fs.readFileSync(path.join(root,'docs/research/2026-09-08-content-faces/plugin/site-pages.json')));
const sourceFiles=['scripts/verify-site-page-editor.mjs','docs/research/2026-09-08-content-faces/plugin/site-pages.json','docs/research/2026-09-08-content-faces/plugin/site-pages.php',...['inc/site-pages.php','theme.json','assets/css/site-pages.css','blocks/site-page/block.json','blocks/site-page/editor.js','blocks/site-page/editor.css','templates/page-site-guide.html'].map(f=>'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/'+f)];
const digests=()=>Object.fromEntries(sourceFiles.map(f=>[f,createHash('sha256').update(fs.readFileSync(path.join(root,f))).digest('hex')]));
const sourceDigests=digests();
const credentials=JSON.parse(fs.readFileSync(path.join(state,'credentials.json')));const base='http://127.0.0.1:8098';const rows=[];let id,completed=false;
const check=(name,pass)=>{rows.push({name,pass:Boolean(pass)});assert.ok(pass,name);};
const browser=await chromium.launch();const page=await browser.newPage({viewport:{width:1440,height:1000}});const errors=[];page.on('pageerror',e=>errors.push(e.message));
try{
 assert.equal(wp(['post','list','--post_type=page','--name=editor-boundary-fixture','--post_status=any','--format=ids']),'','Reserved fixture exists; inspect before retrying');
 id=Number(wp(['post','create','--post_type=page','--post_status=publish','--post_name=editor-boundary-fixture','--post_title=編集経路の検証','--porcelain']));assert.ok(Number.isInteger(id)&&id>0);
 await page.goto(base+'/wp-login.php');await page.locator('#user_login').fill('lab_admin');await page.locator('#user_pass').fill(credentials.admin);await page.locator('#wp-submit').click();await page.waitForURL('**/wp-admin/**');
 await page.goto(`${base}/wp-admin/post.php?post=${id}&action=edit`);await page.waitForFunction(()=>!!window.wp?.blocks?.getBlockType('helix-wt/site-page'));
 const close=page.getByRole('dialog').getByRole('button',{name:'Close',exact:true});
 if(await close.waitFor({state:'visible',timeout:3000}).then(()=>true,()=>false))await close.click();
 // The insertion path is exercised through the actual block inserter, not a direct store write.
 await page.getByRole('button',{name:'Block Inserter',exact:true}).click();
 await page.getByRole('tab',{name:'Patterns',exact:true}).click();
 await page.getByText('常設案内・規約',{exact:true}).click();
 await page.getByRole('option',{name:'会社案内',exact:true}).click();
 await page.waitForFunction(()=>wp.data.select('core/block-editor').getBlocks().some(b=>b.name==='helix-wt/site-page'));
 check('pattern:ui-inserted',await page.evaluate(()=>wp.data.select('core/block-editor').getBlocks().filter(b=>b.name==='helix-wt/site-page').length===1));
 const canvas=page.frameLocator('iframe[name="editor-canvas"]');
 await canvas.getByRole('button',{name:'ページ全体用テンプレートを適用'}).click();
 check('template:explicit-apply',await page.evaluate(()=>wp.data.select('core/editor').getEditedPostAttribute('template')==='page-site-guide'));
 await canvas.getByLabel('ページの種類',{exact:true}).selectOption('pricing');
 await canvas.locator('.wtsite[data-site-page="pricing"]').waitFor();
 check('preview:pricing',await canvas.locator('.wtsite-plans>section').count()===3);
 await page.getByRole('button',{name:'Save',exact:true}).click();
 await page.waitForFunction(()=>!wp.data.select('core/editor').isSavingPost()&&!wp.data.select('core/editor').isEditedPostDirty());
 await page.reload();await page.waitForFunction(()=>wp.data.select('core/block-editor').getBlocks().some(b=>b.name==='helix-wt/site-page'));
 check('saved:reload',await page.evaluate(()=>{const b=wp.data.select('core/block-editor').getBlocks().find(b=>b.name==='helix-wt/site-page');return b.attributes.pageKey==='pricing'&&b.isValid&&wp.data.select('core/editor').getEditedPostAttribute('template')==='page-site-guide';}));
 const response=await page.request.get(base+'/?p='+id);const html=await response.text();check('saved:public-view',response.ok()&&html.includes('data-site-page="pricing"')&&html.includes('165,000円'));
 await canvas.getByLabel('ページの種類',{exact:true}).selectOption('privacy');await canvas.locator('.wtsite[data-site-page="privacy"]').waitFor();
 await page.getByRole('button',{name:'Save',exact:true}).click();await page.waitForFunction(()=>!wp.data.select('core/editor').isSavingPost()&&!wp.data.select('core/editor').isEditedPostDirty());
 const changed=await page.request.get(base+'/?p='+id);check('changed:public-view',(await changed.text()).includes('data-site-page="privacy"'));
 check('editor:all-kind-options',JSON.stringify(await canvas.getByLabel('ページの種類',{exact:true}).locator('option').evaluateAll(es=>es.map(e=>e.value)))===JSON.stringify(Object.keys(manifest.pages)));
 for(const [key,definition] of Object.entries(manifest.pages)){
  await canvas.getByLabel('ページの種類',{exact:true}).selectOption(key);
  const preview=canvas.locator(`.wtsite[data-site-page="${key}"]`);await preview.waitFor();
  check(`preview:${key}:heading`,await preview.locator('h1').innerText()===definition.title);
  await page.getByRole('button',{name:'Save',exact:true}).click();
  await page.waitForFunction(()=>!wp.data.select('core/editor').isSavingPost()&&!wp.data.select('core/editor').isEditedPostDirty());
  await page.reload();await page.waitForFunction(()=>wp.data.select('core/block-editor').getBlocks().some(b=>b.name==='helix-wt/site-page'));
  check(`saved:${key}:reload`,await page.evaluate(key=>{const b=wp.data.select('core/block-editor').getBlocks().find(b=>b.name==='helix-wt/site-page');return b.attributes.pageKey===key&&b.isValid;},key));
  const publicView=await page.request.get(base+'/?p='+id);
  check(`saved:${key}:public`,publicView.ok()&&(await publicView.text()).includes(`data-site-page="${key}"`));
 }
 check('editor:sources-unchanged',JSON.stringify(sourceDigests)===JSON.stringify(digests()));
 check('editor:no-runtime-error',errors.length===0);completed=true;
}catch(error){await page.screenshot({path:path.join(os.tmpdir(),'wt-editor-failure.png')});console.error('Editor verification failed:',error.message);console.error('Visible buttons:',await page.getByRole('button').evaluateAll(bs=>bs.filter(b=>b.getBoundingClientRect().width).map(b=>b.getAttribute('aria-label')||b.textContent)));throw error;
}finally{
 await browser.close();if(id)wp(['post','delete',String(id),'--force']);
 fs.writeFileSync(path.join(root,'docs/research/2026-09-08-content-faces/results/site-pages/editor.json'),JSON.stringify({schema:'wt-site-page-editor.v1',completed,sourceDigests,rows},null,2)+'\n');
}
console.log(`Site page editor: ${rows.length} checks passed; owned page removed`);
