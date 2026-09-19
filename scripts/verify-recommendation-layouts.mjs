import {execFileSync} from 'node:child_process';
import {chromium} from 'playwright';
import fs from 'node:fs';
import path from 'node:path';
import {createHash, randomUUID} from 'node:crypto';
const theme='docs/research/2026-09-05-design-prototype-03/theme/helix-wt/',out='docs/research/2026-09-19-recommendation-layouts',state='/tmp/helix-content-lab';
const run=randomUUID(),slug='recommendation-layout-fixture',lock=state+'/recommendation-layout-fixture.json';
const wp=args=>execFileSync('docker',['run','--rm','--network','helix-content-lab','--env-file',state+'/wp.env','--volumes-from','helix-content-wp','--user','33:33','wordpress:cli-php8.3','wp',...args],{encoding:'utf8',maxBuffer:16000000}).trim();
const reserved=()=>wp(['post','list','--post_type=any','--post_status=any','--name='+slug,'--format=ids']);
if(wp(['option','get','blogname'])!=='HELIX Content Lab'||reserved()||fs.existsSync(lock))throw Error('Dedicated lab / fixture collision guard');
const hash=f=>createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const sources=['scripts/verify-recommendation-layouts.mjs',...['patterns/recommendation-cards.php','patterns/recommendation-list.php','theme.json','style.css'].map(f=>theme+f)];
const sourceDigests=Object.fromEntries(sources.map(f=>[f,hash(f)])),rows=[],shots=[],ids=[],attachments=[];
const check=(name,pass,details)=>rows.push({name,pass:!!pass,...details===undefined?{}:{details}});
let category,pageId,browser,completed=false;
const create=(args)=>{const id=Number(wp(['post','create',...args,'--meta_input='+JSON.stringify({wt_recommendation_owner:run}),'--porcelain']));ids.push(id);return id;};
fs.writeFileSync(lock,JSON.stringify({run,pid:process.pid}),{flag:'wx'});
try{
 // 新規ファイル追加後のテーマ限定patternキャッシュを更新する。
 wp(['eval','wp_get_theme()->delete_pattern_cache();']);
 category=Number(wp(['term','create','category','記事一覧検証 '+run,'--slug=wt-recommendation-'+run,'--porcelain']));
 wp(['term','meta','update',String(category),'wt_recommendation_owner',run]);
 const titles=['小さな余白から、毎日の仕事を整える','選ぶ前に考えたい、道具と暮らしのちょうどよい関係','はじめての見直しで迷わないために、今の暮らしから優先順位を一つずつ整理する方法'];
 const posts=[];
 for(let i=0;i<3;i++){
  const id=create(['--post_type=post','--post_status=publish','--post_title='+titles[i],'--post_name=wt-recommendation-'+run+'-'+i,'--post_date=2026-08-0'+(3-i)+' 12:00:00','--post_category='+category,'--post_excerpt=いまの習慣を振り返り、自分に合う進め方を見つけるための読みものです。無理なく続けられる工夫を、身近な場面から紹介します。','--post_content=架空の検証用記事です。']);posts.push(id);
  if(i<2){const attachment=Number(wp(['media','import','/var/www/html/wp-content/themes/helix-wt/assets/img/media-pickup-'+(i+1)+'.jpg','--post_id='+id,'--title=記事一覧の検証画像','--porcelain']));attachments.push(attachment);wp(['post','meta','update',String(attachment),'wt_recommendation_owner',run]);wp(['post','meta','update',String(id),'_thumbnail_id',String(attachment)]);}
 }
 const hidden=create(['--post_type=post','--post_status=private','--post_title=非公開の記事一覧検証','--post_category='+category,'--post_date=2026-08-04 12:00:00']);
 pageId=create(['--post_type=page','--post_status=publish','--post_name='+slug,'--post_title=次の読みものを見つける']);
 wp(['post','meta','update',String(pageId),'_wp_page_template','page-zone-catalog']);
 const contracts=[];browser=await chromium.launch();
 for(const mode of ['cards','list']){
  const raw=wp(['eval',`$p=WP_Block_Patterns_Registry::get_instance()->get_registered('helix-wt/recommendation-${mode}'); if(!$p){throw new Exception('Pattern missing');} $b=parse_blocks($p['content']); $query=null; $visit=function(&$blocks) use (&$visit,&$query){foreach($blocks as &$block){if($block['blockName']==='core/query'){$query=$block['attrs']['query'];$block['attrs']['query']['taxQuery']=array('category'=>array(${category}));} $visit($block['innerBlocks']);}}; $visit($b); echo wp_json_encode(array('query'=>$query,'content'=>serialize_blocks($b)));`]);
  const data=JSON.parse(raw);contracts.push(data.query);check(mode+':registered',!!data.content);wp(['post','update',String(pageId),'--post_content='+data.content]);
  for(const[device,width]of[['pc',1440],['sp',390]])for(const js of[true,false]){
   const context=await browser.newContext({viewport:{width,height:1000},javaScriptEnabled:js,reducedMotion:'reduce'}),p=await context.newPage(),prefix=mode+':'+device+':'+(js?'js':'nojs');
   const response=await p.goto('http://127.0.0.1:8098/'+slug+'/?wt=page_fix:off');await p.locator('.wt-recommendation-query').waitFor();await p.locator('.wt-recommendation-query img').evaluateAll(imgs=>Promise.all(imgs.map(i=>i.decode())));
   const actual=await p.locator('.wt-recommendation-query .wp-block-post').evaluateAll(es=>es.map(e=>Number([...e.classList].find(c=>/^post-\d+$/.test(c)).slice(5))));
   check(prefix+':same-public-order-count',JSON.stringify(actual)===JSON.stringify(posts),{actual,expected:posts});check(prefix+':private-excluded',!actual.includes(hidden));
   check(prefix+':response',response.status()===200);check(prefix+':no-overflow',await p.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));
   check(prefix+':images-loaded',await p.locator('.wt-recommendation-query img').evaluateAll(es=>es.length===2&&es.every(e=>e.naturalWidth>0)));
   check(prefix+':image-absence-no-empty-frame',await p.locator('.wt-recommendation-query .post-'+posts[2]+' .wp-block-post-featured-image').count()===0);
   check(prefix+':titles-readable',await p.locator('.wt-recommendation-query .wp-block-post-title').evaluateAll(es=>es.every(e=>e.scrollWidth<=e.clientWidth&&e.getBoundingClientRect().height>0)));
   const boxes=await p.locator('.wt-recommendation-query .wp-block-post').evaluateAll(es=>es.map(e=>({x:e.getBoundingClientRect().x,y:e.getBoundingClientRect().y})));
   check(prefix+':layout',mode==='cards'&&device==='pc'?Math.abs(boxes[0].y-boxes[1].y)<2:boxes[1].y>boxes[0].y,boxes);
   if(mode==='list')check(prefix+':horizontal-media-row',await p.locator('.wt-recommendation-query .wp-block-post').first().evaluate(e=>{const image=e.querySelector('.wp-block-post-featured-image').getBoundingClientRect(),title=e.querySelector('.wp-block-post-title').getBoundingClientRect();return title.left>=image.right&&title.width>=180;}));
   if(mode==='list')check(prefix+':image-absence-full-width',await p.locator('.wt-recommendation-query .post-'+posts[2]+' .wt-recommendation-row').evaluate(e=>{const row=e.getBoundingClientRect(),text=e.querySelector('.wt-recommendation-copy').getBoundingClientRect();return Math.abs(row.left-text.left)<2&&text.width>=row.width-2;}));
   if(mode==='list')check(prefix+':image-fills-media-slot',await p.locator('.wt-recommendation-query .wp-block-post-featured-image').evaluateAll(es=>es.every(e=>{const slot=e.getBoundingClientRect(),image=e.querySelector('img').getBoundingClientRect();return image.width>=slot.width-2&&image.width>=80;})));
   const link=p.locator('.wt-recommendation-query .wp-block-post-title a').first();await link.focus();check(prefix+':keyboard-focus',await link.evaluate(e=>e===document.activeElement));
   if(js){await p.evaluate(()=>{document.activeElement?.blur();scrollTo(0,0);});const file=mode+'-'+device+'.jpg';await p.screenshot({path:path.join(out,file),fullPage:true,type:'jpeg',quality:85});shots.push({id:mode,device,file,sha256:hash(path.join(out,file))});}
   await link.click();check(prefix+':article-link',await p.locator('h1').filter({hasText:titles[0]}).count()>0);await context.close();
  }
 }
 check('contract:query-invariant',JSON.stringify(contracts[0])===JSON.stringify(contracts[1]));check('contract:newest-three-not-popularity',contracts.every(q=>q.perPage===3&&q.orderBy==='date'&&q.order==='desc'&&q.postType==='post'));
 completed=true;
}finally{
 await browser?.close();
 for(const id of [...attachments,...ids].reverse()){if(wp(['post','meta','get',String(id),'wt_recommendation_owner'])!==run)throw Error('Fixture owner mismatch');wp(['post','delete',String(id),'--force']);}
 if(category){if(wp(['term','meta','get',String(category),'wt_recommendation_owner'])!==run)throw Error('Category owner mismatch');wp(['term','delete','category',String(category)]);}
 check('fixtures:cleanup',!reserved()&&wp(['post','list','--post_type=any','--post_status=any','--meta_key=wt_recommendation_owner','--meta_value='+run,'--format=ids'])==='');
 check('source-unchanged',sources.every(f=>hash(f)===sourceDigests[f]));fs.unlinkSync(lock);
 fs.writeFileSync(out+'/verification.json',JSON.stringify({schema:'wt-recommendation-layout-verification.v1',completed,sourceDigests,rows,shots},null,2)+'\n');console.log(JSON.stringify({completed,checks:rows.length,failed:rows.filter(r=>!r.pass)}));
}
if(rows.some(r=>!r.pass))process.exitCode=1;
