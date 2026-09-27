import { contentLab } from './lib/content-lab-env.mjs';
import {execFileSync} from 'node:child_process';
import {chromium} from 'playwright';
import fs from 'node:fs';
import {createHash,randomUUID} from 'node:crypto';
const theme='docs/research/2026-09-05-design-prototype-03/theme/helix-wt/',out='docs/research/2026-09-19-pricing-cards',state=contentLab.stateDir;
const before=process.argv.includes('--before'),run=randomUUID(),slug='pricing-cards-fixture',lock=state+'/pricing-cards-fixture.json';
const wp=args=>execFileSync('docker',['run','--rm','--network',contentLab.network,'--env-file',state+'/wp.env','--volumes-from',contentLab.wpContainer,'--user','33:33','wordpress:cli-php8.3','wp',...args],{encoding:'utf8',maxBuffer:16000000}).trim();
const hash=f=>createHash('sha256').update(fs.readFileSync(f)).digest('hex'),reserved=()=>wp(['post','list','--post_type=any','--post_status=any','--name='+slug,'--format=ids']);
if(wp(['option','get','blogname'])!=='HELIX Content Lab'||wp(['option','get','stylesheet'])!=='helix-wt'||reserved()||fs.existsSync(lock))throw Error('Dedicated lab / fixture collision');
const sources=['scripts/verify-pricing-cards.mjs',theme+'patterns/pricing.php',theme+'assets/css/theme.css'],sourceDigests=Object.fromEntries(sources.map(f=>[f,hash(f)])),rows=[],shots=[],ids=[];
const check=(name,pass,details)=>rows.push({name,pass:!!pass,...details===undefined?{}:{details}});
let browser,completed=false;
fs.writeFileSync(lock,JSON.stringify({run,pid:process.pid}),{flag:'wx'});
try{
 wp(['eval','wp_get_theme()->delete_pattern_cache();']);
 const raw=wp(['eval',"$p=WP_Block_Patterns_Registry::get_instance()->get_registered('helix-wt/pricing'); echo $p['content'];"]);
 const contact='<!-- wp:heading {"anchor":"contact"} --><h2 class="wp-block-heading" id="contact">相談のご案内</h2><!-- /wp:heading --><!-- wp:paragraph --><p>選んだプランについて相談できます。</p><!-- /wp:paragraph -->';
 for(const mode of ['standard','long-copy']){
  const content=(mode==='standard'?raw:raw.replace('月次レポート','月次レポートと改善の振り返り。公開した記事の読まれ方を確認し、次月に取り組む内容を一緒に整理します。').replace('専任ディレクター','専任ディレクターが企画から公開後の改善まで伴走。複数部門との調整や制作スケジュールの見直しも支援します。'))+contact;
  const id=Number(wp(['post','create','--post_type=page','--post_status=publish','--post_name='+slug+(mode==='standard'?'':'-long'),'--post_title=自分に合う支援を選ぶ','--post_content='+content,'--meta_input='+JSON.stringify({wt_pricing_owner:run,_wp_page_template:'page-zone-catalog'}),'--porcelain']));ids.push(id);
  check(mode+':saved-content-roundtrip',wp(['post','get',String(id),'--field=post_content'])===content);
 }
 browser=await chromium.launch();
 for(const[mode,id]of[['standard',ids[0]],['long-copy',ids[1]]])for(const[device,width]of[['pc',1440],['sp',390]])for(const js of[true,false]){
  const context=await browser.newContext({viewport:{width,height:1000},javaScriptEnabled:js,reducedMotion:'reduce'}),p=await context.newPage(),prefix=mode+':'+device+':'+(js?'js':'nojs');
  const response=await p.goto(`${contentLab.baseUrl}/?page_id=`+id);const section=p.locator('#pricing');await section.waitFor();await section.scrollIntoViewIfNeeded();
  const data=await section.evaluate(e=>{const cards=[...e.querySelectorAll('.wp-block-column > .is-style-wt-card')];return cards.map(c=>{const b=c.getBoundingClientRect(),price=c.querySelector('.wt-price'),unit=price.querySelector('small'),pr=price.getBoundingClientRect(),ur=unit.getBoundingClientRect(),h=c.querySelector('h3'),hb=h.getBoundingClientRect(),a=c.querySelector('.wp-block-button__link'),ab=a.getBoundingClientRect();return {priceFits:price.scrollWidth<=price.clientWidth,unitSameLine:ur.top>=pr.top-1&&ur.bottom<=pr.bottom+1,titleY:hb.y,height:b.height,y:b.y,bottom:b.bottom,buttonBottom:ab.bottom,buttonHeight:ab.height,buttonWidth:ab.width,titleLines:hb.height/parseFloat(getComputedStyle(h).lineHeight),fits:c.scrollWidth<=c.clientWidth&&c.scrollHeight<=c.clientHeight+2,link:a.getAttribute('href')};});});
  check(prefix+':response',response.status()===200);check(prefix+':three-plans',data.length===3);check(prefix+':no-overflow',await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));check(prefix+':text-unclipped',data.every(c=>c.fits));check(prefix+':title-single-line',data.every(c=>c.titleLines<1.1));check(prefix+':touch-target',data.every(c=>c.buttonHeight>=44&&c.buttonWidth>=44));check(prefix+':cta-target',data.every(c=>c.link==='#contact'));check(prefix+':price-unit-single-line',data.every(c=>c.priceFits&&c.unitSameLine));check(prefix+':title-baseline',device==='sp'||Math.max(...data.map(c=>c.titleY))-Math.min(...data.map(c=>c.titleY))<2);
  check(prefix+':equal-row-height',device==='sp'||Math.max(...data.map(c=>c.height))-Math.min(...data.map(c=>c.height))<2,data);
  check(prefix+':cta-baseline',device==='sp'||Math.max(...data.map(c=>c.buttonBottom))-Math.min(...data.map(c=>c.buttonBottom))<2,data.map(c=>c.buttonBottom));
  check(prefix+':sp-stacks',device==='pc'||data.every((c,i)=>!i||c.y>=data[i-1].bottom));
  check(prefix+':reduced-motion',await section.evaluate(e=>[e,...e.querySelectorAll('*')].every(n=>{const s=getComputedStyle(n);return s.animationName==='none'&&s.transitionDuration.split(',').every(v=>parseFloat(v)<=0.01);})));
  const link=section.locator('.wp-block-button__link').first();await link.focus();check(prefix+':focus-visible',await link.evaluate(e=>e===document.activeElement&&getComputedStyle(e).outlineStyle!=='none'));
  if(js){await link.evaluate(e=>e.blur());const file=(before?'before-':'')+mode+'-'+device+'.jpg';await section.screenshot({path:out+'/'+file,type:'jpeg',quality:88});shots.push({id:mode,device,file,sha256:hash(out+'/'+file)});}
  await link.click();check(prefix+':contact-navigation',new URL(p.url()).hash==='#contact');await context.close();
 }
 check('fixture:isolation',wp(['post','get',String(ids[0]),'--field=post_content']).includes('月次レポート</li>'));
 completed=true;
}finally{
 await browser?.close();
 for(const id of ids){if(wp(['post','meta','get',String(id),'wt_pricing_owner'])!==run)throw Error('Fixture owner mismatch');wp(['post','delete',String(id),'--force']);}
 check('fixtures:cleanup',!reserved()&&wp(['post','list','--post_type=any','--post_status=any','--meta_key=wt_pricing_owner','--meta_value='+run,'--format=ids'])==='');check('source-unchanged',sources.every(f=>hash(f)===sourceDigests[f]));fs.unlinkSync(lock);
 fs.writeFileSync(out+'/'+(before?'before':'verification')+'.json',JSON.stringify({schema:'wt-pricing-cards-verification.v1',completed,sourceDigests,rows,shots},null,2)+'\n');console.log(JSON.stringify({completed,checks:rows.length,failed:rows.filter(r=>!r.pass)}));
}
if(rows.some(r=>!r.pass)&&!before)process.exitCode=1;
