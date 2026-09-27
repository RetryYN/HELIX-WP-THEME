import { contentLab } from './lib/content-lab-env.mjs';
import {execFileSync} from 'node:child_process';
import {chromium} from 'playwright';
import fs from 'node:fs';
import {createHash,randomUUID} from 'node:crypto';
const theme='docs/research/2026-09-05-design-prototype-03/theme/helix-wt/',out='docs/research/2026-09-19-eyecatch-meta',state=contentLab.stateDir;
const run=randomUUID(),lock=state+'/eyecatch-meta-fixture.json',slug='eyecatch-meta-fixture';
const wp=args=>execFileSync('docker',['run','--rm','--network',contentLab.network,'--env-file',state+'/wp.env','--volumes-from',contentLab.wpContainer,'--user','33:33','wordpress:cli-php8.3','wp',...args],{encoding:'utf8',maxBuffer:16000000}).trim();
const hash=f=>createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const reserved=()=>wp(['post','list','--post_type=any','--post_status=any','--name='+slug,'--format=ids']);
if(wp(['option','get','blogname'])!=='HELIX Content Lab'||reserved()||fs.existsSync(lock))throw Error('Dedicated lab / fixture collision');
const sources=['scripts/verify-eyecatch-meta.mjs',...['functions.php','templates/single.html','assets/css/theme.css'].map(f=>theme+f)],sourceDigests=Object.fromEntries(sources.map(f=>[f,hash(f)]));
const rows=[],shots=[],ids=[],modes=['title-image','image-title','hero','side','none'];
const check=(name,pass,details)=>rows.push({name,pass:!!pass,...details===undefined?{}:{details}});
const saved=JSON.parse(wp(['eval','echo wp_json_encode(array("exists"=>array_key_exists("wt_eyecatch",get_theme_mods()),"value"=>get_theme_mod("wt_eyecatch")));']));
let browser,completed=false;
fs.writeFileSync(lock,JSON.stringify({run,pid:process.pid}),{flag:'wx'});
const create=args=>{const id=Number(wp(['post','create',...args,'--meta_input='+JSON.stringify({wt_eyecatch_owner:run}),'--porcelain']));ids.push(id);return id;};
try{
 const title='道具を見直して、暮らしと仕事の余白をつくる';
 const post=create(['--post_type=post','--post_status=publish','--post_name='+slug,'--post_title='+title,'--post_content=<!-- wp:paragraph --><p>使う場所と時間を振り返り、自分に合う道具を選ぶための読みものです。</p><!-- /wp:paragraph -->']);
 const control=create(['--post_type=post','--post_status=publish','--post_name='+slug+'-control','--post_title=設定を継承する対照記事','--post_content=対照記事']);
 const image=Number(wp(['media','import','/var/www/html/wp-content/themes/helix-wt/assets/img/media-pickup-1.jpg','--post_id='+post,'--title=道具と暮らしの検証写真','--porcelain']));ids.push(image);wp(['post','meta','update',String(image),'wt_eyecatch_owner',run]);
 wp(['post','meta','update',String(post),'_thumbnail_id',String(image)]);wp(['post','meta','update',String(control),'_thumbnail_id',String(image)]);
 const routes=JSON.parse(wp(['eval',`echo wp_json_encode(array(get_permalink(${post}),get_permalink(${control})));`]));
 wp(['theme','mod','set','wt_eyecatch','image-title']);
 browser=await chromium.launch();
 for(const mode of modes){
  wp(['post','meta','update',String(post),'wt_eyecatch',mode]);
  for(const[device,width]of[['pc',1440],['sp',390]])for(const js of[true,false]){
   const context=await browser.newContext({viewport:{width,height:1000},javaScriptEnabled:js,reducedMotion:'reduce'}),p=await context.newPage(),prefix=mode+':'+device+':'+(js?'js':'nojs');
   const response=await p.goto(routes[0]);await p.locator('.wt-posthead').waitFor();
   await p.locator('.wt-posthead img').evaluateAll(es=>Promise.all(es.map(e=>e.decode())));
   const geometry=await p.locator('.wt-posthead').evaluate(e=>{const t=e.querySelector('.wt-posthead__text'),i=e.querySelector('.wt-posthead__img'),a=t.getBoundingClientRect(),b=i.getBoundingClientRect();return {tx:a.x,ty:a.y,tr:a.right,tb:a.bottom,ix:b.x,iy:b.y,ir:b.right,ib:b.bottom,visible:getComputedStyle(i).display!=='none',textVisible:a.height>0,textFits:t.scrollWidth<=t.clientWidth};});
   check(prefix+':http200',response.status()===200);check(prefix+':post-meta-selected',await p.locator('body').evaluate((e,m)=>e.classList.contains('wt-eyecatch-'+m),mode));
   check(prefix+':no-overflow',await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));check(prefix+':title-readable',geometry.textVisible&&geometry.textFits);
   let layout=mode==='none'?!geometry.visible:geometry.visible;
   if(mode==='title-image'||(mode==='side'&&device==='sp'))layout&&=geometry.iy>=geometry.tb-2;
   if(mode==='image-title')layout&&=geometry.ty>=geometry.ib-2;
   if(mode==='side'&&device==='pc')layout&&=geometry.ix>=geometry.tr-2;
   if(mode==='hero')layout&&=geometry.ty>=geometry.iy&&geometry.tb<=geometry.ib+2;
   check(prefix+':visual-order',layout,geometry);
   check(prefix+':reduced-motion',await p.locator('.wt-posthead').evaluate(e=>[e,...e.querySelectorAll('*')].every(n=>{const s=getComputedStyle(n);return s.animationName==='none'&&s.transitionDuration.split(',').every(v=>parseFloat(v)<=0.01);})));
   if(js){const file=mode+'-'+device+'.jpg';await p.locator('.wt-posthead').screenshot({path:out+'/'+file,type:'jpeg',quality:88});shots.push({id:mode,device,file,sha256:hash(out+'/'+file)});}
   await p.goto(routes[1]);check(prefix+':control-inherits-site',await p.locator('body').evaluate(e=>e.classList.contains('wt-eyecatch-image-title')));
   await context.close();
  }
 }
 wp(['post','meta','delete',String(post),'wt_eyecatch']);
 const p=await browser.newPage();await p.goto(routes[0]);check('unset:inherits-site',await p.locator('body').evaluate(e=>e.classList.contains('wt-eyecatch-image-title')));
 wp(['theme','mod','set','wt_eyecatch','none']);await p.reload();check('unset:follows-changed-site',await p.locator('.wt-posthead__img').isHidden());
 wp(['post','meta','update',String(post),'wt_eyecatch','title-image']);await p.reload();check('explicit:overrides-changed-site',await p.locator('.wt-posthead__img').isVisible());
 completed=true;
}finally{
 await browser?.close();
 if(saved.exists)wp(['theme','mod','set','wt_eyecatch',saved.value]);else wp(['theme','mod','remove','wt_eyecatch']);
 for(const id of ids.reverse()){if(wp(['post','meta','get',String(id),'wt_eyecatch_owner'])!==run)throw Error('Fixture owner mismatch');wp(['post','delete',String(id),'--force']);}
 check('fixtures:cleanup',!reserved());check('source-unchanged',sources.every(f=>hash(f)===sourceDigests[f]));fs.unlinkSync(lock);
 fs.writeFileSync(out+'/verification.json',JSON.stringify({schema:'wt-eyecatch-meta-verification.v1',completed,sourceDigests,rows,shots},null,2)+'\n');console.log(JSON.stringify({completed,checks:rows.length,failed:rows.filter(r=>!r.pass)}));
}
if(rows.some(r=>!r.pass))process.exitCode=1;
