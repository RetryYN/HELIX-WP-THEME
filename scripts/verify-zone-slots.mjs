import {execFileSync} from 'node:child_process';
import {chromium} from 'playwright';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import {createHash} from 'node:crypto';
const root=process.cwd(),out=path.join(root,'docs/research/2026-09-13-zone-slots');
fs.mkdirSync(out,{recursive:true});
const wp=args=>execFileSync('docker',['run','--rm','--network','helix-content-lab','--env-file',path.join(process.env.WTCF_STATE_DIR||path.join(os.tmpdir(),'helix-content-lab'),'wp.env'),'--volumes-from','helix-content-wp','--user','33:33','wordpress:cli-php8.3','wp',...args],{encoding:'utf8',stdio:['ignore','pipe','pipe']}).trim();
if(wp(['option','get','blogname'])!=='HELIX Content Lab'||wp(['option','get','stylesheet'])!=='helix-wt')throw Error('Dedicated current-theme lab required');
const slug='zone-selection-fixture';
if(wp(['post','list','--post_type=page','--post_status=any','--name='+slug,'--format=ids']))throw Error('Reserved fixture exists');
const ids=['header-inner','page-top','before-content','before-related','after-related','sticky-sidebar','page-bottom','sp-bottom'];
const card=(title,label='FIELD NOTES',text='日々の選択を、少し確かなものに。編集部がまとめたガイドをお届けします。')=>({title,label,text,url:'#zone-guide',action:'ガイドを読む'});
const declarations=Object.fromEntries(ids.map(id=>[id,{slot:id,common:[card('暮らしを整える、小さなヒント。')]}]));
declarations['header-inner'].common=[card('今月の特集：心地よい暮らしのつくり方','NEW ISSUE','道具、空間、時間。自分に合う整え方を見つけよう。')];
declarations['page-top'].common=[card('よく読まれた、３つのガイド','START HERE','はじめての方へ。いま役立つ読みものを選びました。')];
declarations['before-related'].common=[card('整える習慣を、続けるために','NEXT STEP','１日５分から始める、片づけのヒント。')];
declarations['after-related'].common=[card('季節と暮らす、道具の選び方','SEASONAL GUIDE','長く愛用できるものを、納得して選ぶ。')];
declarations['sticky-sidebar'].common=[card('編集部のおすすめ','BOOKMARK','あとで読みたいガイドをまとめました。')];
declarations['page-bottom'].common=[card('次の週末、何から始めよう。','YOUR NEXT WEEKEND','無理のないペースで、自分らしい暮らしへ。')];
declarations['before-content'].pc=[card('道具をじっくり比較する','DESKTOP GUIDE'),card('選び方から読み始める','EDITOR’S PICK')];
declarations['before-content'].sp=[card('手のひらで、選び方をチェック','MOBILE GUIDE')];
declarations['sticky-sidebar'].sp=[];
declarations['sp-bottom'].sp=[card('気になるテーマを、もうひとつ。','CONTINUE READING','')];
const slot=id=>'<!-- wp:helix-wt/zone-slot '+JSON.stringify(declarations[id])+' /-->';
const section=(title,text)=>'<!-- wp:heading --><h2 class="wp-block-heading">'+title+'</h2><!-- /wp:heading --><!-- wp:paragraph --><p>'+text+'</p><!-- /wp:paragraph -->';
const content=slot('header-inner')+slot('page-top')+'<!-- wp:group {"anchor":"zone-guide"} --><div id="zone-guide" class="wp-block-group">'+section('余白のある毎日をつくる','読みものとガイドを、自分のペースで。暮らしの小さな工夫を集めました。')+slot('before-content')+section('まずは、ひとつの場所から。','よく使うものを手に取りやすい場所へ。大きく変える必要はありません。毎日続けられる、小さな一歩を見つけます。')+slot('before-related')+section('あわせて読みたい','道具の選び方と、長く付き合うための手入れ。次のヒントはこちらから。')+slot('after-related')+slot('sticky-sidebar')+slot('page-bottom')+slot('sp-bottom')+'<!-- /wp:group -->';
fs.writeFileSync(path.join(out,'fixture.json'),JSON.stringify({schema:'wt-zone-slot-poc.v1',families:{'before-content':['before-content'],related:['before-related','after-related'],page:['page-top','page-bottom'],'header-inner':['header-inner'],'sp-bottom':['sp-bottom'],'sticky-sidebar':['sticky-sidebar']},declarations},null,2)+'\n');
const browser=await chromium.launch(),checks=[],screenshots=[];let id;
const check=(name,pass)=>checks.push({name,pass:!!pass});
try {
id=wp(['post','create','--post_type=page','--post_status=publish','--post_name='+slug,'--post_title=日々を整える、読みものとガイド','--post_content='+content,'--porcelain']);
wp(['post','meta','update',id,'_wp_page_template','page-zone-catalog']);
for(const [device,width]of[['pc',1440],['sp',390]])for(const js of[true,false]) {
const context=await browser.newContext({viewport:{width,height:960},javaScriptEnabled:js,isMobile:device==='sp',extraHTTPHeaders:{'Sec-CH-UA-Mobile':device==='sp'?'?1':'?0'},userAgent:device==='sp'?'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Mobile/15E148':'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 Chrome/130.0.0.0 Safari/537.36'});
const page=await context.newPage();await page.goto('http://127.0.0.1:8098/'+slug+'/?wt=page_side:none,content_chrome:shared');await page.evaluate(()=>document.fonts.ready);
const name=device+'-'+(js?'js':'nojs');
const expected=ids.filter(x=>device==='pc'?x!=='sp-bottom':x!=='sticky-sidebar');
check(name+':placement-order',JSON.stringify(await page.locator('[data-wt-zone]').evaluateAll(es=>es.map(e=>e.dataset.wtZone)))===JSON.stringify(expected));
check(name+':device-difference',await page.locator('[data-wt-zone="before-content"] article').count()===(device==='pc'?2:1));
check(name+':opposite-heavy-absent',!(await page.content()).includes(device==='pc'?'手のひらで、選び方をチェック':'道具をじっくり比較する'));
check(name+':no-overflow',await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));
check(name+':44px-targets',await page.locator('.wt-zone__link').evaluateAll(es=>es.every(e=>e.getBoundingClientRect().height>=44&&e.getBoundingClientRect().width>=44)));
check(name+':no-overlap',await page.locator('[data-wt-zone]').evaluateAll(es=>{const r=es.map(e=>e.getBoundingClientRect());return r.every((a,i)=>r.every((b,j)=>i===j||a.bottom<=b.top||b.bottom<=a.top||a.right<=b.left||b.right<=a.left))}));
check(name+':no-hidden-zone',await page.locator('[data-wt-zone]').evaluateAll(es=>es.every(e=>getComputedStyle(e).display!=='none'&&!e.hidden)));
check(name+':AA-text',await page.locator('.wt-zone__link,.wt-zone__eyebrow,.wt-zone__card>p:not(.wt-zone__eyebrow)').evaluateAll(es=>{const rgb=s=>s.match(/[\d.]+/g).slice(0,3).map(Number);const lum=s=>rgb(s).map(v=>{v/=255;return v<=.04045?v/12.92:((v+.055)/1.055)**2.4}).reduce((a,v,i)=>a+v*[.2126,.7152,.0722][i],0);return es.every(e=>{const c=getComputedStyle(e);let b=e;while(b&&getComputedStyle(b).backgroundColor==='rgba(0, 0, 0, 0)')b=b.parentElement;const l=[lum(c.color),lum(b?getComputedStyle(b).backgroundColor:'rgb(255,255,255)')].sort((a,b)=>b-a);return (l[0]+.05)/(l[1]+.05)>=4.5})}));
const image=name+'.png';await page.screenshot({path:path.join(out,image),fullPage:true});screenshots.push(image);await context.close();
}
// 同じ実機面を空宣言に置換。見出し・ラッパー・カード全体が返らないことを確認。
const empty=ids.map(slot=>'<!-- wp:helix-wt/zone-slot '+JSON.stringify({slot,common:[]})+' /-->').join('');
wp(['post','update',id,'--post_content='+empty]);
for(const mobile of[false,true]) {const context=await browser.newContext({javaScriptEnabled:false,userAgent:mobile?'Mobile iPhone':'Desktop'});const page=await context.newPage();await page.goto('http://127.0.0.1:8098/'+slug+'/');check('empty-dom-'+mobile,await page.locator('[data-wt-zone],.wt-zone__card,.wt-zone__eyebrow').count()===0);await context.close();}
} finally {if(id){wp(['post','delete',id,'--force']);check('fixture-cleanup',!wp(['post','list','--post_type=page','--post_status=any','--name='+slug,'--format=ids']));}await browser.close();}
const sources=['scripts/verify-zone-slots.mjs','docs/research/2026-09-05-design-prototype-03/theme/helix-wt/templates/page-zone-catalog.html','docs/research/2026-09-05-design-prototype-03/theme/helix-wt/inc/zone-slots.php','docs/research/2026-09-05-design-prototype-03/theme/helix-wt/assets/css/zone-slots.css'];
const sourceDigests=Object.fromEntries(sources.map(p=>[p,createHash('sha256').update(fs.readFileSync(p)).digest('hex')]));
fs.writeFileSync(path.join(out,'verification.json'),JSON.stringify({schema:'wt-zone-slots-verification.v1',acceptance:['WT-AC-ZONE-01A','WT-AC-ZONE-01B'],scope:'Dedicated local WordPress / current helix-wt / server-rendering PoC',completed:checks.every(c=>c.pass),passed:checks.every(c=>c.pass),checks,screenshots,sourceDigests,limitations:['Dedicated placement fixture; production templates and Site Editor controls not connected','Device selection uses wp_is_mobile; viewport-only resize and cache segmentation are not verified']},null,2)+'\n');
console.log(JSON.stringify({passed:checks.every(c=>c.pass),checks:checks.length,failed:checks.filter(c=>!c.pass)}));
if(checks.some(c=>!c.pass))process.exitCode=1;
