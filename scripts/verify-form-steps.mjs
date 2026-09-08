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
wp(['option','update','wtcf_event_fixture_mode','1']);
const slug='form-steps-fixture';
if(wp(['post','list','--post_type=page','--post_status=any','--name='+slug,'--format=ids']))throw Error('Reserved fixture exists');
const sources=['scripts/verify-form-steps.mjs',...['inc/form.php','inc/event-state.php','assets/js/form.js','inc/footer-navigation.php','parts/footer.html','patterns/footer-sitemap.php','patterns/footer-related.php','functions.php','assets/css/theme.css','assets/css/event-state.css'].map(f=>'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/'+f)];
const digests=()=>Object.fromEntries(sources.map(f=>[f,createHash('sha256').update(fs.readFileSync(path.join(root,f))).digest('hex')]));
const sourceDigests=digests(),rows=[];const check=(name,pass)=>rows.push({name,pass:!!pass});
const out=path.join(root,'docs/research/2026-09-08-form-flow');
const browser=await chromium.launch(),base='http://127.0.0.1:8098';let id,thanksId,completed=false;
const kinds=['contact','apply','download','reservation','newsletter','recruit','quote','trial','diagnosis'];
try{
 const existingThanks=wp(['post','list','--post_type=page','--post_status=any','--name=thanks','--format=ids']);
 if(!existingThanks)thanksId=Number(wp(['post','create','--post_type=page','--post_status=publish','--post_name=thanks','--post_title=Thanks Fixture','--post_content=<!-- wp:helix-wt/form-thanks /-->','--porcelain']));
 id=Number(wp(['post','create','--post_type=page','--post_status=publish','--post_name='+slug,'--post_title=Form Steps Fixture','--post_content=<!-- wp:helix-wt/form /-->','--porcelain']));
 for(const [device,width]of[['pc',1440],['sp',375]])for(const js of[true,false])for(const kind of [...kinds,'full']){
  const actual=kind==='full'?'contact':kind;
  const context=await browser.newContext({viewport:{width,height:900},javaScriptEnabled:js});const page=await context.newPage();
  const label=`${device}:js-${js}:${kind}`;
  const url=base+'/'+slug+'/?wt='+encodeURIComponent(`form_kind:${actual},form_fields:${kind==='full'?'full':'by-kind'},form_confirm:yes,form_thanks:separate,form_layout:steps,form_error:both,form_side:none,form_captcha:question`);
  await page.goto(url);check('kind:'+label,await page.locator(`[data-wt-form="${actual}"]`).count()===1);
  await page.locator(js?'.wt-form__next':'.wt-form__form > .wt-form__actions .wt-form__submit').click();
  check('empty-rejected:'+label,await page.locator('[aria-invalid=true]').count()>0);
  const stepCount=js?await page.locator('.wt-form__step').count():1;
  for(let step=0;step<stepCount;step++){
  const scope=js?page.locator('.wt-form__step').nth(step):page.locator('.wt-form__form');
  const controls=scope.locator('input:not([type=hidden]):not([type=file]):not(#wt-hp),select,textarea');
  const seen=new Set();
  for(let i=0;i<await controls.count();i++){
   const control=controls.nth(i),type=await control.getAttribute('type'),name=await control.getAttribute('name'),cid=await control.getAttribute('id');
   if(type==='radio'){if(!seen.has(name)){await control.check();seen.add(name);}continue;}
   if(type==='checkbox'){await control.check();continue;}
   if(await control.evaluate(e=>e.tagName)==='SELECT'){await control.selectOption({index:1});continue;}
   let value='検証用サンプル';
   if(cid?.includes('kana'))value='けんしょう';
   else if(cid?.includes('email'))value='reader@example.com';
   else if(type==='tel')value='0312345678';
   else if(cid?.includes('postal'))value='1000001';
   else if(type==='url')value='https://example.com/';
   else if(type==='date')value='2026-10-15';
   else if(type==='month')value='2020-01';
   else if(type==='number')value='2';
   else if(cid==='wt-f-captcha')value='7';
   await control.fill(value);
  }
  if(js&&step<stepCount-1){
   const values=await scope.locator('input,select,textarea').evaluateAll(es=>es.map(e=>[e.id,e.value,e.checked]));
   await page.locator('.wt-form__next').click();
   check('next-step:'+step+':'+label,await page.locator('.wt-form__step').nth(step+1).isVisible());
   await page.locator('.wt-form__prev').click();
   check('back-values:'+step+':'+label,JSON.stringify(values)===JSON.stringify(await scope.locator('input,select,textarea').evaluateAll(es=>es.map(e=>[e.id,e.value,e.checked]))));
   await page.locator('.wt-form__next').click();
  }
  }
  check('attachment-no-name:'+label,await page.locator('input[type=file][name]').count()===0);
  const before=await page.locator('.wt-form__form').evaluate(form=>Array.from(new FormData(form).entries()).filter(([k])=>k.startsWith('wt_form[')));
  await page.locator('.wt-form__form > .wt-form__actions .wt-form__submit').click();
  const review=page.locator('.wt-form__confirm');
  const confirmed=await review.count()===1;check('confirmation:'+label,confirmed);
  if(!confirmed){await context.close();continue;}
  await review.getByRole('button',{name:'修正する',exact:true}).click();
  const after=await page.locator('.wt-form__form').evaluate(form=>Array.from(new FormData(form).entries()).filter(([k])=>k.startsWith('wt_form[')));
  check('all-values-retained:'+label,JSON.stringify(before)===JSON.stringify(after));
  if(js)for(let step=1;step<stepCount;step++)await page.locator('.wt-form__next').click();
  await page.locator('.wt-form__form > .wt-form__actions .wt-form__submit').click();
  await review.locator('button[type=submit]').last().click();
  await page.waitForSelector(`[data-wt-thanks="${actual}"]`);
  check('separate-route:'+label,new URL(page.url()).pathname==='/thanks/');
  check('kind-completed:'+label,await page.locator(`[data-wt-thanks="${actual}"]`).count()===1);
  await page.waitForLoadState('networkidle');check('reflow:'+label,await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));
  await context.close();
 }
 completed=true;
}finally{
 await browser.close();if(thanksId)wp(['post','delete',String(thanksId),'--force']);if(id)wp(['post','delete',String(id),'--force']);
 check('owned-fixture-removed',wp(['post','list','--post_type=page','--post_status=any','--name='+slug,'--format=ids'])==='');
 if(thanksId)check('owned-thanks-removed',wp(['post','list','--post_type=page','--post_status=any','--name=thanks','--format=ids'])==='');
 check('sources-unchanged',JSON.stringify(sourceDigests)===JSON.stringify(digests()));
 fs.writeFileSync(path.join(out,'steps-verify.json'),JSON.stringify({completed,sourceDigests,rows},null,2)+'\n');
 console.log(JSON.stringify({completed,checks:rows.length,failed:rows.filter(r=>!r.pass)}));
}
if(rows.some(r=>!r.pass))process.exitCode=1;
