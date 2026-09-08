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
const slug='form-boundaries-fixture';
if(wp(['post','list','--post_type=page','--post_status=any','--name='+slug,'--format=ids']))throw Error('Reserved fixture exists');
const sources=['scripts/verify-form-boundaries.mjs',...['inc/form.php','inc/event-state.php','assets/js/form.js','inc/footer-navigation.php','parts/footer.html','patterns/footer-sitemap.php','patterns/footer-related.php','functions.php','assets/css/theme.css'].map(f=>'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/'+f)];
const digests=()=>Object.fromEntries(sources.map(f=>[f,createHash('sha256').update(fs.readFileSync(path.join(root,f))).digest('hex')]));
const sourceDigests=digests(),rows=[];const check=(name,pass)=>rows.push({name,pass:!!pass});
const out=path.join(root,'docs/research/2026-09-08-form-flow');
const browser=await chromium.launch(),base='http://127.0.0.1:8098';let id,completed=false;
const kinds=['contact','apply','download','reservation','newsletter','recruit','quote','trial','diagnosis'];
try{
 id=Number(wp(['post','create','--post_type=page','--post_status=publish','--post_name='+slug,'--post_title=Form Boundaries Fixture','--post_content=<!-- wp:helix-wt/form /-->','--porcelain']));
 for(const [device,width]of[['pc',1440],['sp',375]])for(const js of[true,false])for(const kind of ['full']){
  const actual=kind==='full'?'contact':kind;
  const context=await browser.newContext({viewport:{width,height:900},javaScriptEnabled:js});const page=await context.newPage();
  const label=`${device}:js-${js}:${kind}`;
  const url=base+'/'+slug+'/?wt='+encodeURIComponent(`form_kind:${actual},form_fields:${kind==='full'?'full':'by-kind'},form_confirm:yes,form_thanks:inline,form_error:both,form_side:none,form_captcha:question`);
  await page.goto(url);check('kind:'+label,await page.locator(`[data-wt-form="${actual}"]`).count()===1);
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
  const nonce=await page.locator('[name=wt_form_nonce]').inputValue();
  const validPayload=new URLSearchParams(before);validPayload.set('wt_form_nonce',nonce);validPayload.set('wt_step','input');
  const validResponse=await context.request.post(url,{data:validPayload.toString(),headers:{'Content-Type':'application/x-www-form-urlencoded'}});
  check('valid-control:'+label,validResponse.ok()&&(await validResponse.text()).includes('class="wt-form__confirm"'));
  const invalidCases=[
   ['birthdate','2026-02-30','impossible-date'],['birthdate','2026-13-01','invalid-date-month'],
   ['founded_date','2026-13','invalid-month'],['founded_date','0000-01','zero-year'],
   ['date_pref[]','not-a-date','invalid-preference'],['date_pref[]','2026-02-30','impossible-preference'],
   ['people_count','1.5','fractional-people'],['people_count','0','zero-people'],
   ['subject_select','unlisted','unknown-select'],['subject_radio','unlisted','unknown-radio'],
   ['option_questions[]','unlisted','unknown-checks'],['eligibility_questions[0]','unknown','unknown-yesno'],
   ['email','invalid','invalid-email'],['email_confirm','other@example.com','email-mismatch'],
   ['tel','abcdefghi','invalid-phone'],['postal','123','invalid-postal'],
   ['name_kana','Latin','invalid-kana'],['url','https://','missing-url-host'],
   ['consent','no','invalid-consent'],['captcha','8','wrong-captcha']
  ];
  for(const [key,value,tag]of invalidCases){
   const payload=new URLSearchParams(before);payload.set('wt_form_nonce',nonce);payload.set('wt_step','input');
   const field=key.match(/^([^\[]+)(.*)$/);payload.set(`wt_form[${field[1]}]${field[2]}`,value);
   const response=await context.request.post(url,{data:payload.toString(),headers:{'Content-Type':'application/x-www-form-urlencoded'}});
   const html=await response.text();
   check('reject:'+tag+':'+label,response.ok()&&html.includes('role="alert"')&&!html.includes('class="wt-form__confirm"')&&!html.includes('data-wt-thanks='));
  }
  for(const [key,value]of [['birthdate','2024-02-29'],['birthdate','2000-02-29'],['founded_date','2026-12']]){
   const payload=new URLSearchParams(validPayload);payload.set(`wt_form[${key}]`,value);
   const response=await context.request.post(url,{data:payload.toString(),headers:{'Content-Type':'application/x-www-form-urlencoded'}});
   check('accept-valid:'+key+':'+value+':'+label,response.ok()&&(await response.text()).includes('class="wt-form__confirm"'));
  }
  if(js){
   let browserPosts=0;page.on('request',request=>{if(request.method()==='POST')browserPosts++;});
   await page.locator('#wt-f-people-count').fill('1.5');
   await page.locator('.wt-form__form > .wt-form__actions .wt-form__submit').click();
   check('client-fraction-rejected:'+label,await page.locator('#wt-f-people-count').getAttribute('aria-invalid')==='true'&&browserPosts===0);
  }
  await context.close();
 }
 completed=true;
}finally{
 await browser.close();if(id)wp(['post','delete',String(id),'--force']);
 check('owned-fixture-removed',wp(['post','list','--post_type=page','--post_status=any','--name='+slug,'--format=ids'])==='');
 check('sources-unchanged',JSON.stringify(sourceDigests)===JSON.stringify(digests()));
 fs.writeFileSync(path.join(out,process.argv.includes('--baseline')?'boundaries-baseline.json':'boundaries-verify.json'),JSON.stringify({completed,sourceDigests,rows},null,2)+'\n');
 console.log(JSON.stringify({completed,checks:rows.length,failed:rows.filter(r=>!r.pass)}));
}
if(rows.some(r=>!r.pass)&&!process.argv.includes('--baseline'))process.exitCode=1;
