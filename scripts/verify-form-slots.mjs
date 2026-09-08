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
const slug='form-slots-fixture';
if(wp(['post','list','--post_type=page','--post_status=any','--name='+slug,'--format=ids']))throw Error('Reserved fixture exists');
const sources=['scripts/verify-form-slots.mjs',...['inc/form.php','inc/event-state.php','assets/js/form.js','inc/footer-navigation.php','parts/footer.html','patterns/footer-sitemap.php','patterns/footer-related.php','functions.php','assets/css/theme.css','patterns/lp.php','patterns/event.php','templates/page-lp.html','templates/page-event.html'].map(f=>'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/'+f)];
const digests=()=>Object.fromEntries(sources.map(f=>[f,createHash('sha256').update(fs.readFileSync(path.join(root,f))).digest('hex')]));
const sourceDigests=digests(),rows=[];const check=(name,pass)=>rows.push({name,pass:!!pass});
const out=path.join(root,'docs/research/2026-09-08-form-flow');
const browser=await chromium.launch(),base='http://127.0.0.1:8098';let id,thanksId,completed=false;
try{
 const existingThanks=wp(['post','list','--post_type=page','--post_status=any','--name=thanks','--format=ids']);
 if(!existingThanks)thanksId=Number(wp(['post','create','--post_type=page','--post_status=publish','--post_name=thanks','--post_title=Thanks Fixture','--post_content=<!-- wp:helix-wt/form-thanks /-->','--porcelain']));
 id=Number(wp(['post','create','--post_type=page','--post_status=publish','--post_name='+slug,'--post_title=Form Slots Fixture','--post_content=<!-- wp:helix-wt/form /-->','--porcelain']));
 for(const [device,width]of[['pc',1440],['sp',375]])for(const js of[true,false])for(const face of ['lp','event']){
  const kind=face==='event'?'apply':'contact',actual=kind;
  wp(['post','meta','update',String(id),'_wp_page_template','page-'+face]);
  const context=await browser.newContext({viewport:{width,height:900},javaScriptEnabled:js});const page=await context.newPage();
  const label=`${device}:js-${js}:${face}`;
  const route=base+'/'+slug+'/';
  await page.goto(route);check('default-no-form:'+label,await page.locator('.wt-form__form').count()===0);
  const selected=face==='lp'?'lp_form:block,lp_sections:extended':'event_apply:block-form';
  const url=route+'?wt='+encodeURIComponent(`${selected},form_kind:contact,form_fields:by-kind,form_confirm:yes,form_thanks:separate,form_error:both,form_side:none,form_captcha:question`);
  await page.goto(url);check('kind:'+label,await page.locator(`[data-wt-form="${actual}"]`).count()===1);
  await page.locator('.wt-form__form').scrollIntoViewIfNeeded();
  const expected=kind==='apply'?['name','email','tel','subject-radio','people-count','message','captcha','consent']:['name','name-kana','company','email','tel','subject-select','message','captcha','consent'];
  check('kind-fields:'+label,JSON.stringify(expected)===JSON.stringify(await page.locator('.wt-form__row').evaluateAll(es=>es.map(e=>e.dataset.wtField))));
  await page.locator('.wt-form__form > .wt-form__actions .wt-form__submit').click();
  check('empty-rejected:'+label,await page.locator('[aria-invalid=true]').count()>0);
  const controls=page.locator('.wt-form__form input:not([type=hidden]):not([type=file]):not(#wt-hp),.wt-form__form select,.wt-form__form textarea');
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
  check('attachment-no-name:'+label,await page.locator('input[type=file][name]').count()===0);
  const before=await page.locator('.wt-form__form').evaluate(form=>Array.from(new FormData(form).entries()).filter(([k])=>k.startsWith('wt_form[')));
  await page.locator('.wt-form__form > .wt-form__actions .wt-form__submit').click();
  const review=page.locator('.wt-form__confirm');
  const confirmed=await review.count()===1;check('confirmation:'+label,confirmed);
  if(!confirmed){await context.close();continue;}
  await review.getByRole('button',{name:'修正する',exact:true}).click();
  const after=await page.locator('.wt-form__form').evaluate(form=>Array.from(new FormData(form).entries()).filter(([k])=>k.startsWith('wt_form[')));
  check('all-values-retained:'+label,JSON.stringify(before)===JSON.stringify(after));
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
 fs.writeFileSync(path.join(out,'slots-verify.json'),JSON.stringify({completed,sourceDigests,rows},null,2)+'\n');
 console.log(JSON.stringify({completed,checks:rows.length,failed:rows.filter(r=>!r.pass)}));
}
if(rows.some(r=>!r.pass))process.exitCode=1;
