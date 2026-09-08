import { execFileSync } from 'node:child_process';
import { chromium } from 'playwright';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { createHash } from 'node:crypto';
const root=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'..');
const state=process.env.WTCF_STATE_DIR||path.join(os.tmpdir(),'helix-content-lab');
const wp=args=>execFileSync('docker',['run','--rm','--network','helix-content-lab','--env-file',path.join(state,'wp.env'),'--volumes-from','helix-content-wp','--user','33:33','wordpress:cli-php8.3','wp',...args],{encoding:'utf8',stdio:['ignore','pipe','pipe']}).trim();
if(wp(['option','get','blogname'])!=='HELIX Content Lab')throw Error('Dedicated lab required');
const slug='form-flow-fixture';
if(wp(['post','list','--post_type=page','--post_status=any','--name='+slug,'--format=ids']))throw Error('Reserved fixture exists');
const sources=['scripts/verify-form-flow.mjs',...['inc/form.php','inc/event-state.php','assets/js/form.js','inc/footer-navigation.php','parts/footer.html','patterns/footer-sitemap.php','patterns/footer-related.php','functions.php','assets/css/theme.css'].map(f=>'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/'+f)];
const digests=()=>Object.fromEntries(sources.map(f=>[f,createHash('sha256').update(fs.readFileSync(path.join(root,f))).digest('hex')]));
const sourceDigests=digests(),rows=[];const check=(name,pass)=>rows.push({name,pass:!!pass});
const out=path.join(root,'docs/research/2026-09-08-form-flow');fs.mkdirSync(out,{recursive:true});
const browser=await chromium.launch(),base='http://127.0.0.1:8098';let id,completed=false;
const values={name:'検証用の名前',email:'reader@example.com',message:'確認用の本文\n改行も保持する'};
try{
 id=Number(wp(['post','create','--post_type=page','--post_status=publish','--post_name='+slug,'--post_title=Form Flow Fixture','--post_content=<!-- wp:helix-wt/form /-->','--porcelain']));
 for(const [device,width]of[['pc',1440],['sp',375]])for(const js of[true,false])for(const mode of['yes','no','inline-review']){
  const context=await browser.newContext({viewport:{width,height:900},javaScriptEnabled:js});const page=await context.newPage();let externalPosts=0;
  await context.route('**/*',async route=>{const req=route.request();if(req.method()==='POST'&&new URL(req.url()).origin!==base){externalPosts++;await route.abort();}else await route.continue();});
  const label=`${device}:js-${js}:${mode}`,url=base+'/'+slug+'/?wt='+encodeURIComponent(`form_fields:minimal,form_confirm:${mode},form_thanks:inline,form_error:both,form_side:none`);
  await page.goto(url);check('form-present:'+label,await page.locator('.wt-form__form').count()===1);
  await page.locator('.wt-form__form > .wt-form__actions .wt-form__submit').click();
  check('required-errors:'+label,await page.locator('[aria-invalid=true]').count()===4);
  for(const [key,value]of Object.entries(values))await page.locator('#wt-f-'+key).fill(value);
  await page.locator('#wt-f-consent').check();
  await page.locator('.wt-form__form > .wt-form__actions .wt-form__submit').click();
  if(mode!=='no'){
   const inline=js&&mode==='inline-review';const review=page.locator(inline?'.wt-form__inline-review':'.wt-form__confirm');
   check('confirmation-values:'+label,(await review.innerText()).includes(values.name)&&(await review.innerText()).includes(values.email));
   await review.getByRole('button',{name:'修正する',exact:true}).click();
   for(const [key,value]of Object.entries(values))check('edit-preserves:'+key+':'+label,await page.locator('#wt-f-'+key).inputValue()===value);
   await page.locator('#wt-f-message').fill('修正した本文');
   await page.locator('.wt-form__form > .wt-form__actions .wt-form__submit').click();
   check('reconfirmation:'+label,(await review.innerText()).includes('修正した本文'));
   await review.locator('button[type=submit]').last().click();
  }
  await page.waitForSelector('[data-wt-thanks=contact]');
  check('completed:'+label,await page.locator('[data-wt-thanks=contact]').count()===1);
  await page.waitForLoadState('networkidle');
  check('reflow:'+label,await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));
  await page.goto(url);
  const nonce=await page.locator('[name=wt_form_nonce]').inputValue();
  const payload={'wt_form_nonce':nonce,'wt_form[name]':values.name,'wt_form[email]':values.email,'wt_form[message]':values.message,'wt_form[consent]':'1','wt_step':'input'};
  for(const [kind,extra]of [['invalid-nonce',{'wt_form_nonce':'invalid-fixture'}],['unknown-step',{'wt_step':'unexpected'}],['normalized-step',{'wt_step':'con!firm'}],['array-step',{'wt_step[]':'confirm','wt_step':undefined}]]){
   const form={...payload,...extra};for(const key of Object.keys(form))if(form[key]===undefined)delete form[key];
   const response=await context.request.post(url,{form});const html=await response.text();
   check('reject:'+kind+':'+label,response.ok()&&!html.includes('data-wt-thanks=')&&html.includes('role="alert"'));
   check('reject-keeps-values:'+kind+':'+label,html.includes(values.email));
  }
  if(mode==='yes'&&!process.argv.includes('--baseline')){
   await page.goto(url);
   for(const [key,value]of Object.entries(values))await page.locator('#wt-f-'+key).fill(value);
   await page.locator('#wt-f-consent').check();
   await page.locator('[name=wt_form_nonce]').evaluate(n=>{n.value='invalid-fixture';});
   await page.locator('.wt-form__form > .wt-form__actions .wt-form__submit').click();
   await page.waitForSelector('.wt-form__summary');
   check('error-recovery-message:'+label,(await page.locator('.wt-form__summary').innerText()).includes('有効期限'));
   for(const [key,value]of Object.entries(values))check('error-recovery-value:'+key+':'+label,await page.locator('#wt-f-'+key).inputValue()===value);
   if(!js)await page.screenshot({path:path.join(out,`error-${device}.jpg`),fullPage:true});
   await page.locator('.wt-form__form > .wt-form__actions .wt-form__submit').click();
   await page.locator('.wt-form__confirm button[type=submit]').last().click();
   await page.waitForSelector('[data-wt-thanks=contact]');
   check('error-recovery-complete:'+label,await page.locator('[data-wt-thanks=contact]').count()===1);
   if(!js)await page.screenshot({path:path.join(out,`done-${device}.jpg`),fullPage:true});
  }
  check('no-external-post:'+label,externalPosts===0);await context.close();
 }
 completed=true;
}finally{
 await browser.close();if(id)wp(['post','delete',String(id),'--force']);
 check('owned-fixture-removed',wp(['post','list','--post_type=page','--post_status=any','--name='+slug,'--format=ids'])==='');
 check('sources-unchanged',JSON.stringify(sourceDigests)===JSON.stringify(digests()));
 fs.writeFileSync(path.join(out,process.argv.includes('--baseline')?'baseline.json':'verify.json'),JSON.stringify({completed,sourceDigests,rows},null,2)+'\n');
 console.log(JSON.stringify({completed,checks:rows.length,failed:rows.filter(r=>!r.pass).length}));
}
if(rows.some(r=>!r.pass)&&!process.argv.includes('--baseline'))process.exitCode=1;
