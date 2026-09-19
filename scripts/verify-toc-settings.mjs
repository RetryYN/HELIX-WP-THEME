import {execFileSync} from 'node:child_process';
import {chromium} from 'playwright';
import fs from 'node:fs';
import {createHash,randomUUID} from 'node:crypto';
const theme='docs/research/2026-09-05-design-prototype-03/theme/helix-wt/',out='docs/research/2026-09-19-toc-settings',state='/tmp/helix-content-lab';
const run=randomUUID(),slug='toc-settings-fixture',lock=state+'/toc-settings-fixture.json';
const wp=args=>execFileSync('docker',['run','--rm','--network','helix-content-lab','--env-file',state+'/wp.env','--volumes-from','helix-content-wp','--user','33:33','wordpress:cli-php8.3','wp',...args],{encoding:'utf8',maxBuffer:16000000}).trim();
const hash=f=>createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const reserved=()=>wp(['post','list','--post_type=any','--post_status=any','--name='+slug,'--format=ids']);
if(wp(['option','get','blogname'])!=='HELIX Content Lab'||reserved()||fs.existsSync(lock))throw Error('Dedicated lab / fixture collision');
fs.mkdirSync(out,{recursive:true});
const sources=['scripts/verify-toc-settings.mjs',...['functions.php','assets/css/theme.css','assets/js/article.js'].map(f=>theme+f)],sourceDigests=Object.fromEntries(sources.map(f=>[f,hash(f)]));
const rows=[],shots=[],ids=[],modes=['box','float','collapsible','none'];
const check=(name,pass,details)=>rows.push({name,pass:!!pass,...details===undefined?{}:{details}});
const saved=JSON.parse(wp(['eval','$out=array();foreach(array("wt_toc","wt_side_layout") as $key){$out[$key]=array("exists"=>array_key_exists($key,get_theme_mods()),"value"=>get_theme_mod($key));}echo wp_json_encode($out);']));
const heading=(text,level=2)=>`<!-- wp:heading {"level":${level}} --><h${level} class="wp-block-heading">${text}</h${level}><!-- /wp:heading -->`;
const paragraph='<!-- wp:paragraph --><p>暮らしの中で使う場面を思い浮かべ、自分に合う選び方を整理します。使いやすさと続けやすさを大切にしましょう。</p><!-- /wp:paragraph -->';
const content=paragraph+heading('いまの使い方を振り返る')+paragraph+heading('場所と時間を整理する',3)+paragraph+heading('選ぶ基準を決める')+paragraph+heading('無理なく続ける工夫')+paragraph;
let browser,completed=false;
fs.writeFileSync(lock,JSON.stringify({run,pid:process.pid}),{flag:'wx'});
const create=type=>{const id=Number(wp(['post','create','--post_type='+type,'--post_status=publish','--post_name='+slug+(ids.length?'-'+ids.length:''),'--post_title=暮らしの道具を選ぶための小さな手順','--post_content='+content,'--meta_input='+JSON.stringify({wt_toc_owner:run}),'--porcelain']));ids.push(id);return id;};
try{
 const post=create('post'),control=create('post'),page=create('page');
 const routes=JSON.parse(wp(['eval',`echo wp_json_encode(array(get_permalink(${post}),get_permalink(${control}),get_permalink(${page})));`]));
 wp(['theme','mod','set','wt_toc','box']);wp(['theme','mod','set','wt_side_layout','right']);browser=await chromium.launch();
 for(const mode of modes){
  wp(['post','meta','update',String(post),'wt_toc',mode]);
  check(mode+':saved-readback',wp(['post','meta','get',String(post),'wt_toc'])===mode);
  for(const[device,width]of[['pc',1440],['sp',390]])for(const js of[true,false]){
   const context=await browser.newContext({viewport:{width,height:1000},javaScriptEnabled:js,reducedMotion:'reduce'}),p=await context.newPage(),prefix=mode+':'+device+':'+(js?'js':'nojs');
   const response=await p.goto(routes[0]);const body=p.locator('.wp-block-post-content').first();await body.waitFor();
   check(prefix+':http200',response.status()===200);check(prefix+':no-overflow',await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));
   const nav=body.locator('.wt-toc');check(prefix+':selected',mode==='none'?await nav.count()===0:await nav.getAttribute('data-wt-toc')===mode);
   if(mode!=='none'){
    check(prefix+':derived-labels',JSON.stringify(await nav.locator('a').allTextContents())===JSON.stringify(['いまの使い方を振り返る','場所と時間を整理する','選ぶ基準を決める','無理なく続ける工夫']));
    check(prefix+':valid-targets',await nav.locator('a').evaluateAll(es=>es.every(e=>document.querySelector(e.getAttribute('href'))?.textContent===e.textContent)));
    check(prefix+':h3-nested',await nav.locator('ol ol a').count()===1);
    check(prefix+':initial-open',await nav.locator('details').evaluate((e,expected)=>e.open===expected,mode!=='collapsible'&&!(mode==='box'&&device==='sp'&&js)));
    check(prefix+':summary-touch-target',await nav.locator('summary').evaluate(e=>e.getBoundingClientRect().height>=44));
    check(prefix+':reduced-motion',await nav.locator('summary').evaluate(e=>[getComputedStyle(e),getComputedStyle(e,'::after')].every(s=>s.animationName==='none'&&s.transitionDuration.split(',').every(v=>parseFloat(v)<=0.01))));
    if(mode==='collapsible'){await nav.locator('summary').focus();await p.keyboard.press('Enter');check(prefix+':keyboard-opens',await nav.locator('details').evaluate(e=>e.open));await p.keyboard.press('Enter');check(prefix+':keyboard-closes',await nav.locator('details').evaluate(e=>!e.open));}
   }
   if(js){const file=mode+'-'+device+'.jpg';await p.screenshot({path:out+'/'+file,type:'jpeg',quality:88,fullPage:true});shots.push({id:mode,device,file,sha256:hash(out+'/'+file)});}
   await p.goto(routes[1]);check(prefix+':control-inherits',await p.locator('.wp-block-post-content .wt-toc').getAttribute('data-wt-toc')==='box');await context.close();
  }
 }
 wp(['post','meta','update',String(post),'wt_toc','float']);
 for(const layout of ['right','left','both','none']){
  wp(['theme','mod','set','wt_side_layout',layout]);
  for(const[device,width]of[['pc',1440],['sp',390]])for(const js of[true,false]){
   const context=await browser.newContext({viewport:{width,height:1000},javaScriptEnabled:js,reducedMotion:'reduce'}),p=await context.newPage();await p.goto(routes[0]);
   const prefix='float-layout:'+layout+':'+device+':'+(js?'js':'nojs');
   const geometry=await p.locator('.wp-block-post-content .wt-toc').evaluate(e=>{const n=e.getBoundingClientRect(),body=e.closest('.wp-block-post-content'),nodes=[...body.querySelectorAll('p,h2,h3')].filter(x=>!e.contains(x));return {position:getComputedStyle(e).position,overlaps:nodes.filter(x=>{const r=x.getBoundingClientRect();return r.width&&r.height&&Math.min(n.right,r.right)>Math.max(n.left,r.left)+1&&Math.min(n.bottom,r.bottom)>Math.max(n.top,r.top)+1;}).length};});
   check(prefix+':no-text-overlap',geometry.overlaps===0,geometry);check(prefix+':placement',geometry.position===(layout==='none'&&device==='pc'?'fixed':'static'),geometry);
   check(prefix+':no-overflow',await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));
   if(js&&device==='pc'){const file='float-'+layout+'-pc.jpg';await p.screenshot({path:out+'/'+file,type:'jpeg',quality:88,fullPage:true});shots.push({id:'float-'+layout,device,file,sha256:hash(out+'/'+file)});}
   await context.close();
  }
 }
 const p=await browser.newPage();wp(['post','meta','delete',String(post),'wt_toc']);wp(['theme','mod','set','wt_toc','collapsible']);await p.goto(routes[0]);check('unset:inherits-site',await p.locator('.wp-block-post-content .wt-toc').getAttribute('data-wt-toc')==='collapsible');
 wp(['post','meta','update',String(post),'wt_toc','box']);
 wp(['post','update',String(post),'--post_content='+content.replace('選ぶ基準を決める','長く使える基準を選ぶ')]);await p.reload();check('edit:headings-follow',await p.locator('.wp-block-post-content .wt-toc').textContent().then(t=>t.includes('長く使える基準を選ぶ')&&!t.includes('選ぶ基準を決める')));
 check('storage:no-generated-toc',!wp(['post','get',String(post),'--field=post_content']).includes('wt-toc'));
 wp(['post','update',String(post),'--post_content='+heading('一つ目')+paragraph+heading('二つ目')+paragraph]);await p.reload();check('threshold:two-h2-hidden',await p.locator('.wp-block-post-content .wt-toc').count()===0);
 await p.goto(routes[2]);check('page:not-inserted',await p.locator('.wp-block-post-content .wt-toc').count()===0);
 completed=true;
}finally{
 await browser?.close();for(const[key,value]of Object.entries(saved)){if(value.exists)wp(['theme','mod','set',key,value.value]);else wp(['theme','mod','remove',key]);}
 for(const id of ids.reverse()){if(wp(['post','meta','get',String(id),'wt_toc_owner'])!==run)throw Error('Fixture owner mismatch');wp(['post','delete',String(id),'--force']);}
 check('fixtures:cleanup',!reserved());check('source-unchanged',sources.every(f=>hash(f)===sourceDigests[f]));fs.unlinkSync(lock);
 fs.writeFileSync(out+'/verification.json',JSON.stringify({schema:'wt-toc-settings-verification.v1',completed,sourceDigests,rows,shots},null,2)+'\n');console.log(JSON.stringify({completed,checks:rows.length,failed:rows.filter(r=>!r.pass)}));
}
if(rows.some(r=>!r.pass))process.exitCode=1;
