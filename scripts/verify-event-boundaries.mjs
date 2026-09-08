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
const slug='event-boundary-fixture';
if(wp(['post','list','--post_type=page','--post_status=any','--name='+slug,'--format=ids']))throw Error('Reserved fixture exists');
const out=path.join(root,'docs/research/2026-09-08-event-state');fs.mkdirSync(out,{recursive:true});
const sources=['scripts/verify-event-boundaries.mjs',...['patterns/event.php','templates/page-event.html','functions.php','assets/css/theme.css','inc/event-state.php','inc/form.php'].map(f=>'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/'+f)];
const sourceDigests=Object.fromEntries(sources.map(f=>[f,createHash('sha256').update(fs.readFileSync(path.join(root,f))).digest('hex')]));
const defaults={opens_at:'2026-10-01T09:00:00Z',closes_at:'2026-10-14T09:00:00Z',observed_at:'2026-10-02T09:00:00Z',capacity:50,registered:0};
const fixtures=[
 {name:'japan-start',observed_at:'2026-10-01T18:00:00+09:00',expected:'受付中'},
 {name:'west-before-start',observed_at:'2026-10-01T03:59:59-05:00',expected:'受付前'},
 {name:'japan-deadline',observed_at:'2026-10-14T18:00:00+09:00',expected:'受付終了'},
 {name:'impossible-date',opens_at:'2026-02-30T09:00:00Z'},
 {name:'no-zone',opens_at:'2026-10-01T09:00:00'},
 {name:'invalid-offset-hour',opens_at:'2026-10-01T09:00:00+99:00'},
 {name:'invalid-offset-minute',opens_at:'2026-10-01T09:00:00+09:99'},
 {name:'missing-observed',observed_at:null},
 {name:'equal-period',closes_at:'2026-10-01T09:00:00Z'},
 {name:'reversed-period',closes_at:'2026-09-30T09:00:00Z'},
 {name:'negative-capacity',capacity:-1},
 {name:'fraction-capacity',capacity:1.5},
 {name:'string-capacity',capacity:'50'},
 {name:'negative-registered',registered:-1},
 {name:'boolean-registered',registered:true},
 {name:'zero-capacity',capacity:0,expected:'満席'},
 {name:'over-capacity',registered:51,expected:'満席'}
].map(f=>({...defaults,expected:'受付情報を確認中',...f}));
const browser=await chromium.launch();let id,completed=false;const rows=[];
try{
 id=Number(wp(['post','create','--post_type=page','--post_status=publish','--post_name='+slug,'--post_title=Event Boundary Fixture','--porcelain']));
 wp(['post','meta','update',String(id),'_wp_page_template','page-event']);
 const nonce=wp(['eval','echo wp_create_nonce("wt_form");']);
 for(const fixture of fixtures){
  wp(['post','meta','update',String(id),'_wtcf_event_fixture',JSON.stringify(fixture)]);
  for(const [device,width]of[['pc',1440],['sp',375]])for(const js of [true,false]){
   const context=await browser.newContext({viewport:{width,height:900},javaScriptEnabled:js});const page=await context.newPage();
   const url='http://127.0.0.1:8098/'+slug+'/?wt=event_apply:block-form,form_fields:minimal,form_thanks:inline';
   await page.goto(url);
   const label=fixture.name+':'+device+':js-'+js,open=fixture.expected==='受付中';
   const visible=await page.locator('.wt-event-status').evaluateAll(es=>es.filter(e=>e.getBoundingClientRect().height>0).map(e=>e.textContent.trim()));
   rows.push({name:'state:'+label,expected:fixture.expected,visible,pass:visible.length===1&&visible[0]===fixture.expected});
   rows.push({name:'form-availability:'+label,pass:await page.locator('.wt-form__form').count()===(open?1:0)});
   rows.push({name:'explanation:'+label,pass:open||await page.locator('.wt-event-availability').count()===1});
   const link=page.locator('.wt-event-hero:visible a[href="#apply"]').first();
   await link.click();
   rows.push({name:'availability-route:'+label,pass:new URL(page.url()).hash==='#apply'&&await page.locator('#apply').count()===1});
   const response=await context.request.post(url,{form:{wt_form_nonce:nonce,wt_step:'confirm','wt_form[name]':'検証用の名前','wt_form[email]':'reader@example.com','wt_form[message]':'検証用本文','wt_form[consent]':'1'}});
   const html=await response.text();
   rows.push({name:'post-availability:'+label,pass:response.ok()&&html.includes('data-wt-thanks="apply"')===open});
   await context.close();
  }
 }
 completed=true;
}finally{
 await browser.close();if(id)wp(['post','delete',String(id),'--force']);
 rows.push({name:'owned-fixture-removed',pass:wp(['post','list','--post_type=page','--post_status=any','--name='+slug,'--format=ids'])===''});
 fs.writeFileSync(path.join(out,process.argv.includes('--strict')?'boundaries-verify.json':'boundaries-baseline.json'),JSON.stringify({completed,sourceDigests,fixtures,rows,limitation:'時刻・残席の専用fixture入力。観測時刻・残席による表示とPOST可否を照合。業務予約・定員更新・実送信は対象外。'},null,2)+'\n');
 console.log(JSON.stringify({completed,checks:rows.length,failed:rows.filter(r=>!r.pass).length}));
}
if(process.argv.includes('--strict')&&rows.some(r=>!r.pass))process.exitCode=1;
