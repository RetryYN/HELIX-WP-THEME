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
const marker='SearchBoundaryFixture';
if(wp(['post','list','--post_type=any','--post_status=any','--s='+marker,'--format=ids']))throw Error('Reserved fixtures exist');
const size=Number(wp(['option','get','posts_per_page']));if(!Number.isInteger(size)||size<1||size>30)throw Error('Unexpected lab page size');
const sources=['scripts/verify-site-search-boundaries.mjs','docs/research/2026-09-08-content-faces/plugin/search.php','docs/research/2026-09-05-design-prototype-03/theme/helix-wt/assets/css/theme.css','docs/research/2026-09-05-design-prototype-03/theme/helix-wt/templates/search.html','docs/research/2026-09-08-content-faces/plugin/content-faces.php'];
const digests=()=>Object.fromEntries(sources.map(f=>[f,createHash('sha256').update(fs.readFileSync(path.join(root,f))).digest('hex')]));
const sourceDigests=digests(), rows=[];const check=(name,pass)=>rows.push({name,pass:!!pass});
const base='http://127.0.0.1:8098';let ids=[],completed=false;
const browser=await chromium.launch();
try {
 ids=JSON.parse(wp(['eval',`$ids=array();try{for($i=0;$i<${size+1};$i++){$p=array('post_type'=>'post','post_status'=>'publish','post_title'=>'${marker} public '.$i,'post_content'=>'PublicSearchFixtureText');$id=wp_insert_post($p,true);if(is_wp_error($id))throw new Exception('Create failed');$ids[]=$id;}foreach(array('draft','private','protected','paid')as $state){$p=array('post_type'=>$state==='paid'?'wt_paid':'post','post_status'=>in_array($state,array('draft','private'),true)?$state:'publish','post_title'=>'${marker} '.$state,'post_content'=>$state==='paid'?'PublicPreviewFixture':'HiddenSearchFixtureText');if($state==='protected')$p['post_password']='fixture-only';if($state==='paid')$p['meta_input']=array('_wtcf_document'=>array('billing'=>'oneoff','body'=>'PurchaseBodyOnlyFixture'));$id=wp_insert_post($p,true);if(is_wp_error($id))throw new Exception('Create failed');$ids[]=$id;}echo wp_json_encode($ids);}catch(Throwable $e){foreach($ids as $id)wp_delete_post($id,true);throw $e;}`]));
 const total=size+2;
 for(const [device,width]of[['pc',1440],['sp',375]])for(const js of[true,false]){
  const context=await browser.newContext({viewport:{width,height:900},javaScriptEnabled:js});const page=await context.newPage();const label=`${device}:js-${js}`;
  const count=async()=>Number((await page.locator('main .wp-block-query-total').innerText()).match(/\d+/)?.[0]);
  await page.goto(base+'/?s='+marker);
  check(`public-count:${label}`,await count()===total);
  check(`first-page:${label}`,await page.locator('main .wp-block-post-title').count()===size);
  const html=await page.content();check(`private-excluded:${label}`,!html.includes(marker+' draft')&&!html.includes(marker+' private')&&!html.includes(marker+' protected')&&!html.includes('HiddenSearchFixtureText')&&!html.includes('PurchaseBodyOnlyFixture'));
  const next=page.locator('main .wp-block-query-pagination-next');check(`next-link:${label}`,await next.count()===1);
  if(await next.count()){
   await next.click();check(`query-preserved:${label}`,new URL(page.url()).searchParams.get('s')===marker);
   check(`last-page:${label}`,await count()===total&&await page.locator('main .wp-block-post-title').count()===2);
   const content=await page.content();check(`last-page-private-excluded:${label}`,!content.includes('HiddenSearchFixtureText')&&!content.includes('PurchaseBodyOnlyFixture'));
   await page.locator('main .wp-block-query-pagination-previous').click();check(`previous:${label}`,await page.locator('main .wp-block-post-title').count()===size);
  }
  for(const term of['HiddenSearchFixtureText','PurchaseBodyOnlyFixture']){
   await page.goto(base+'/?s='+term);check(`private-body-query:${term}:${label}`,await count()===0&&await page.locator('main .wp-block-post-title').count()===0);
  }
  await page.goto(base+'/?s='+marker+'&paged=999');
  const feedback=page.locator('main .wp-block-query-total');
  check(`out-of-range-count:${label}`,await feedback.count()===1&&await count()===total);
  check(`out-of-range-recovery:${label}`,await page.locator('main .wp-block-post-title').count()===2);
  await context.close();
 }
 wp(['post','update',...ids.slice(0,2).map(String),'--post_status=draft']);
 for(const [device,width]of[['pc',1440],['sp',375]]){
  const context=await browser.newContext({viewport:{width,height:900},javaScriptEnabled:false});const page=await context.newPage();
  await page.goto(base+'/?s='+marker+'&paged=2');
  check(`publication-recovery:${device}`,Number((await page.locator('main .wp-block-query-total').innerText()).match(/\d+/)?.[0])===size&&await page.locator('main .wp-block-post-title').count()===size);
  await page.locator('main input[type=search]').fill('PurchaseBodyOnlyFixture');await page.locator('main').getByRole('button',{name:'検索する'}).click();
  check(`new-query-clears-page:${device}`,!new URL(page.url()).searchParams.has('paged')&&new URL(page.url()).pathname==='/'&&await page.locator('main .wp-block-post-title').count()===0);
  await context.close();
 }
 completed=true;
}finally{
 await browser.close();if(ids.length)wp(['post','delete',...ids.map(String),'--force']);
 check('owned-fixtures-removed',wp(['post','list','--post_type=any','--post_status=any','--s='+marker,'--format=ids'])==='');
 check('sources-unchanged',JSON.stringify(sourceDigests)===JSON.stringify(digests()));
 const out=path.join(root,'docs/research/2026-09-08-site-search/results');
 fs.writeFileSync(path.join(out,process.argv.includes('--baseline')?'boundaries-baseline.json':'boundaries.json'),JSON.stringify({completed,sourceDigests,rows},null,2)+'\n');
 console.log(JSON.stringify({checks:rows.length,failed:rows.filter(r=>!r.pass).length}));
}
if(!process.argv.includes('--baseline')&&rows.some(r=>!r.pass))process.exitCode=1;
