import { contentLab } from './lib/content-lab-env.mjs';
import {execFileSync} from 'node:child_process';
import {chromium} from 'playwright';
import fs from 'node:fs';
import {createHash,randomUUID} from 'node:crypto';
const theme='docs/research/2026-09-05-design-prototype-03/theme/helix-wt/',out='docs/research/2026-09-20-heading-comparison',state=contentLab.stateDir;
const before=process.argv.includes('--before'),run=randomUUID(),slug='heading-comparison-fixture',lock=state+'/'+slug+'.json';
const wp=args=>execFileSync('docker',['run','--rm','--network',contentLab.network,'--env-file',state+'/wp.env','--volumes-from',contentLab.wpContainer,'--user','33:33','wordpress:cli-php8.3','wp',...args],{encoding:'utf8',maxBuffer:16000000}).trim();
const hash=f=>createHash('sha256').update(fs.readFileSync(f)).digest('hex'),reserved=()=>wp(['post','list','--post_type=any','--post_status=any','--name='+slug,'--format=ids']);
if(wp(['option','get','blogname'])!=='HELIX Content Lab'||wp(['option','get','stylesheet'])!=='helix-wt'||reserved()||fs.existsSync(lock))throw Error('Dedicated lab / fixture collision');
const styles={h2:['plain','2tone','icon','bar','underline','band','numbox','barbg','doubleline','label'],h3:['bar-thin','dotted','num','marker','underline-thin']};
const sources=['scripts/verify-heading-comparison.mjs',theme+'functions.php',theme+'theme.json',theme+'assets/css/theme.css'];
const sourceDigests=Object.fromEntries(sources.map(f=>[f,hash(f)])),rows=[],shots=[];
const check=(name,pass,details)=>rows.push({name,pass:!!pass,...details===undefined?{}:{details}});
const heading=(level,style,text)=>`<!-- wp:heading ${JSON.stringify({level:Number(level.slice(1)),className:'is-style-wt-'+style})} --><${level} class="wp-block-heading is-style-wt-${style}">${text}</${level}><!-- /wp:heading -->`;
const paragraph=text=>`<!-- wp:paragraph --><p>${text}</p><!-- /wp:paragraph -->`;
const sections=Object.entries(styles).flatMap(([level,list])=>list.map(style=>({id:level+'-'+style,level,style})));
let browser,id,completed=false;
fs.writeFileSync(lock,JSON.stringify({run,pid:process.pid}),{flag:'wx'});
try{
 const registry=JSON.parse(wp(['eval',"echo wp_json_encode(WP_Block_Styles_Registry::get_instance()->get_registered_styles_for_block('core/heading'));"]));
 check('registry:15-existing-styles',sections.every(s=>registry['wt-'+s.style]?.name==='wt-'+s.style));
 const content='<!-- wp:group {"anchor":"scale"} --><div id="scale" class="wp-block-group">'+[2,3,4].map(n=>`<!-- wp:heading {"level":${n}} --><h${n} class="wp-block-heading">見出し階層 H${n}</h${n}><!-- /wp:heading -->`).join('')+'</div><!-- /wp:group -->'+sections.map(s=>`<!-- wp:group ${JSON.stringify({anchor:s.id})} --><div id="${s.id}" class="wp-block-group">`+paragraph(s.level.toUpperCase()+' / '+s.style+' — 同じ本文で比較')+heading(s.level,s.style,'暮らしに合う道具を選ぶ')+paragraph('使う場所と頻度を確かめ、必要な機能から比べます。装飾の強さと本文へのつながりを見てください。')+heading(s.level,s.style,'毎日の暮らしで無理なく使い続けられる道具を選ぶために、購入前に確かめたいこと')+paragraph('長い見出しも省略しません。文字が複数行になったときの記号の位置、行間、本文までの余白を比較します。')+'</div><!-- /wp:group -->').join('');
 id=Number(wp(['post','create','--post_type=page','--post_status=publish','--post_name='+slug,'--post_title=見出しを同じ本文で比べる','--post_content='+content,'--meta_input='+JSON.stringify({wt_heading_owner:run,_wp_page_template:'page-zone-catalog'}),'--porcelain']));
 check('content:roundtrip',wp(['post','get',String(id),'--field=post_content'])===content);
 browser=await chromium.launch();
 for(const[device,width]of[['pc',1440],['sp',390],['narrow',320]])for(const js of[true,false]){
  const context=await browser.newContext({viewport:{width,height:1000},javaScriptEnabled:js,reducedMotion:'reduce'}),page=await context.newPage(),prefix=device+':'+(js?'js':'nojs');
  const response=await page.goto(`${contentLab.baseUrl}/?page_id=`+id);await page.locator('#scale').waitFor();
  check(prefix+':response',response.status()===200);check(prefix+':no-page-overflow',await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));
  const sizes=await page.locator('#scale').evaluate(e=>[...e.querySelectorAll('h2,h3,h4')].map(n=>parseFloat(getComputedStyle(n).fontSize)));
  check(prefix+':scale-descends',sizes.length===3&&sizes.every((n,i)=>n>0&&(!i||sizes[i-1]>=n)),sizes);
  for(const s of sections){
   const section=page.locator('#'+s.id);await section.scrollIntoViewIfNeeded();
   const data=await section.locator(s.level).evaluateAll(nodes=>nodes.map(n=>{const c=getComputedStyle(n),p=getComputedStyle(n,'::before'),range=document.createRange();range.selectNodeContents(n);const textBox=range.getBoundingClientRect(),box=n.getBoundingClientRect();return {tag:n.tagName,text:n.textContent,align:c.alignItems,overflow:n.scrollWidth>n.clientWidth+1||textBox.top<box.top-1||textBox.bottom>box.bottom+1,font:parseFloat(c.fontSize),content:p.content,whiteSpace:p.whiteSpace,animation:c.animationName};}));
   check(prefix+':'+s.id+':semantics',data.length===2&&data.every(n=>n.tag===s.level.toUpperCase()));
   check(prefix+':'+s.id+':full-copy',data[1]?.text==='毎日の暮らしで無理なく使い続けられる道具を選ぶために、購入前に確かめたいこと');
   check(prefix+':'+s.id+':no-clipping',data.every(n=>!n.overflow));
   check(prefix+':'+s.id+':level-size',data.every(n=>n.font===sizes[s.level==='h2'?0:1]));
   check(prefix+':'+s.id+':no-motion',data.every(n=>n.animation==='none'));
   if(['icon','numbox','marker'].includes(s.style))check(prefix+':'+s.id+':first-line-decoration',data.every(n=>['baseline','flex-start','start'].includes(n.align)),data.map(n=>n.align));
   if(['numbox','num'].includes(s.style))check(prefix+':'+s.id+':counter-retained',data.every(n=>n.content.includes('counter(')&&n.whiteSpace==='nowrap'));
   if(js&&device!=='narrow'){const file=(before?'before-':'')+s.id+'-'+device+'.jpg';await section.screenshot({path:out+'/'+file,type:'jpeg',quality:88});shots.push({id:s.id,device,file,sha256:hash(out+'/'+file)});}
  }
  await context.close();
 }
 completed=true;
}finally{
 await browser?.close();
 if(id){if(wp(['post','meta','get',String(id),'wt_heading_owner'])!==run)throw Error('Fixture owner mismatch');wp(['post','delete',String(id),'--force']);}
 check('fixtures:cleanup',!reserved());check('source-unchanged',sources.every(f=>hash(f)===sourceDigests[f]));fs.unlinkSync(lock);
 fs.writeFileSync(out+'/'+(before?'before':'verification')+'.json',JSON.stringify({schema:'wt-heading-comparison.v1',completed,sourceDigests,rows,shots},null,2)+'\n');console.log(JSON.stringify({completed,checks:rows.length,failed:rows.filter(r=>!r.pass)}));
}
if(rows.some(r=>!r.pass)&&!before)process.exitCode=1;
