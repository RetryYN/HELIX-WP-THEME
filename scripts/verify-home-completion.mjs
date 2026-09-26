import { contentLab } from './lib/content-lab-env.mjs';
import { execFileSync } from 'node:child_process';
import os from 'node:os';
import { chromium } from 'playwright';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { createHash, randomUUID } from 'node:crypto';
const root=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'..');
const out=path.join(root,'docs/research/2026-09-15-home-completion');
const baseline=process.argv.includes('--baseline');
const choices=JSON.parse(fs.readFileSync(path.join(out,'choices.json'))).choices;
const base=`${contentLab.baseUrl}`;
const rows=[],shots=[];let completed=false;
const check=(name,pass,details)=>rows.push({name,pass:!!pass,...(details===undefined?{}:{details})});
const theme='docs/research/2026-09-05-design-prototype-03/theme/helix-wt/';
const files=['scripts/verify-home-completion.mjs','docs/research/2026-09-15-home-completion/choices.json',...['functions.php','patterns/home-hero.php','patterns/home-sections.php','templates/front-page.html','assets/css/theme.css','assets/js/home.js','assets/css/home-completion.css'].filter(f=>fs.existsSync(path.join(root,theme,f))).map(f=>theme+f)];
const digests=()=>Object.fromEntries(files.map(f=>[f,createHash('sha256').update(fs.readFileSync(path.join(root,f))).digest('hex')]));
const sourceDigests=digests();
const axes={home_hero:['text-only','slider','fullbleed','split','article-grid','video','cards-carousel','product-shot','search-box'],home_hero_cta:['double','single','none','tel-button','search'],home_sections:choices.map(c=>c.id),home_news:['list-with-date','tabs','cards','none'],home_contact:['tel-form','form-only','tel-only','line','none','double-cta'],home_fixed:['none','float-cta','sp-bottom-bar','float-tel'],home_side_layout:['none','right','left','both'],home_side_sticky:['none','whole','last-widget','toc-only'],home_side_sp:['below-content','drawer','hidden'],home_side_set:['media','blog','owned','corporate','minimal','full'],home_side_nav:['none','mega-menu','fixed-left-nav','fixed-right-icons','drawer-pc','toc-side'],side_from:['below-hero','top']};
const choiceAxes=c=>({home_hero:c.hero,home_hero_cta:c.cta,home_sections:c.id,home_news:c.news,home_contact:c.contact,home_fixed:c.fixed,home_side_layout:c.sidebar,home_side_set:c.set,side_from:'below-hero',home_head:'site',home_foot:'site',home_fix:'own'});
const scenarios=choices.map(c=>({id:c.id,values:choiceAxes(c),finished:true}));
for(const [key,values]of Object.entries(axes))for(const value of values)scenarios.push({id:`${key}-${value}`,values:{...choiceAxes(choices[1]),[key]:value}});
scenarios.push({id:'risk-both-top',values:{...choiceAxes(choices[1]),home_side_layout:'both',side_from:'top',home_hero:'split'}},{id:'risk-fullbleed-search',values:{...choiceAxes(choices[0]),home_hero:'fullbleed',home_hero_cta:'search'}},{id:'risk-slider-tel',values:{...choiceAxes(choices[0]),home_hero:'slider',home_hero_cta:'tel-button'}});
const state=contentLab.stateDir;
const wp=args=>execFileSync('docker',['run','--rm','--network',contentLab.network,'--env-file',path.join(state,'wp.env'),'--volumes-from',contentLab.wpContainer,'--user','33:33','wordpress:cli-php8.3','wp',...args],{encoding:'utf8',stdio:['ignore','pipe','pipe']}).trim();
if(wp(['option','get','blogname'])!=='HELIX Content Lab')throw Error('Dedicated lab required');
const fixtureIds=[];
const recoveryPath=path.join(state,'home-completion-fixtures.json');
const fixtureQuery=run=>`get_posts(array('post_type'=>array('post','attachment'),'post_status'=>array('publish','inherit','draft','trash'),'numberposts'=>-1,'fields'=>'ids','meta_key'=>'wt_home_catalog_fixture','meta_value'=>'${run}'))`;
const ownedIds=run=>JSON.parse(wp(['eval',`echo wp_json_encode(${fixtureQuery(run)});`]));
if(process.argv.includes('--recover')){
 if(!fs.existsSync(recoveryPath)){console.log('No HOME fixtures to recover');process.exit(0);}
 const recovery=JSON.parse(fs.readFileSync(recoveryPath));
 if(!/^[a-f0-9-]{36}$/.test(recovery.run)||!Number.isInteger(recovery.pid))throw Error('Invalid recovery marker');
 let alive=false;try{process.kill(recovery.pid,0);alive=true;}catch{}
 if(alive)throw Error('Recorded HOME verifier process is still alive');
 const ids=ownedIds(recovery.run);if(ids.length)wp(['post','delete',...ids.map(String),'--force']);
 if(ownedIds(recovery.run).length)throw Error('HOME fixture recovery failed');
 fs.unlinkSync(recoveryPath);console.log(JSON.stringify({recovered:ids.length}));process.exit(0);
}
if(fs.existsSync(recoveryPath))throw Error('Prior HOME run needs explicit --recover after checking its process');
const fixtureRun=randomUUID();
fs.writeFileSync(recoveryPath,JSON.stringify({run:fixtureRun,pid:process.pid}));
const browser=await chromium.launch();
const baselineRoute=async context=>{if(baseline)await context.route('**/assets/css/home-completion.css*',route=>route.fulfill({contentType:'text/css',body:'/* Dedicated HOME presentation disabled; same content fixture. */'}));};
try{
const fixturePhp=`require_once ABSPATH.'wp-admin/includes/file.php';require_once ABSPATH.'wp-admin/includes/media.php';require_once ABSPATH.'wp-admin/includes/image.php';$titles=array('働き方を見直すために、最初に整理したいこと','小さく始める業務改善。現場の声から考える','比較で迷ったときに確認する5つのポイント','数字と事例で見る、道具の選び方','相談する前にまとめておきたいこと','使い続けられる仕組みを育てる');$images=array('case-factory.jpg','case-tax.jpg','case-clinic.jpg');$ids=array();foreach($titles as $i=>$title){$source=get_theme_file_path('assets/img/'.$images[$i%3]);$tmp=wp_tempnam($images[$i%3]);copy($source,$tmp);$aid=media_handle_sideload(array('name'=>$images[$i%3],'tmp_name'=>$tmp),0,'HOME catalog fixture image',array('meta_input'=>array('wt_home_catalog_fixture'=>'${fixtureRun}')));if(is_wp_error($aid)){throw new Exception('attachment');}$ids[]=$aid;$id=wp_insert_post(array('post_type'=>'post','post_status'=>'publish','post_title'=>$title,'post_name'=>'home-catalog-fixture-'.$i,'meta_input'=>array('wt_home_catalog_fixture'=>'${fixtureRun}'),'post_excerpt'=>'構成比較用の架空記事です。読み手の判断を支える情報を整理します。','post_content'=>'<!-- wp:paragraph --><p>構成比較用の架空記事です。</p><!-- /wp:paragraph -->'));if(is_wp_error($id)){throw new Exception('post');}$ids[]=$id;set_post_thumbnail($id,$aid);}echo wp_json_encode($ids);`;
fixtureIds.push(...JSON.parse(wp(['eval',fixturePhp])));
const declared=JSON.parse(wp(['eval','echo wp_json_encode(wt_axes());']));
for(const [key,values] of Object.entries(axes))check(`coverage:${key}:declaration`,JSON.stringify(values)===JSON.stringify(declared[key][1]));
check('fixtures:owned-posts-and-images',fixtureIds.length===12);
for(const [device,width]of [['pc',1440],['sp',375]])for(const js of [true,false]){
 const context=await browser.newContext({viewport:{width,height:900},javaScriptEnabled:js,reducedMotion:'reduce'});await baselineRoute(context);const page=await context.newPage();const errors=[];page.on('pageerror',e=>errors.push(e.message));
 for(const scenario of scenarios.filter(s=>!process.argv.includes('--finished-only')||s.finished)){
  const route='/?wt='+encodeURIComponent(Object.entries(scenario.values).map(([k,v])=>`${k}:${v}`).join(','));const label=`${device}-${js?'js':'nojs'}:${scenario.id}`;
  const response=await page.goto(base+route);await page.evaluate(()=>document.fonts.ready);
  check(`${label}:response`,response.ok());check(`${label}:home`,await page.locator('main.wt-home').count()===1);
  const actual=await page.evaluate(()=>{
   const visible=e=>e.getClientRects().length&&getComputedStyle(e).visibility!=='hidden';
   const box=e=>{const r=e.getBoundingClientRect();return {x:r.x,y:r.y,width:r.width,height:r.height,bottom:r.bottom,right:r.right}};
   const heroes=[...document.querySelectorAll('.wt-home-hero')].filter(visible),sections=[...document.querySelectorAll('.wt-home__section')].filter(visible).sort((a,b)=>a.getBoundingClientRect().top-b.getBoundingClientRect().top);
   const side=[...document.querySelectorAll('.wt-home .wt-side')].filter(visible),hero=document.querySelector('.wt-home-hero-slot');
   const fixed=[...document.querySelectorAll('.wt-fixed,.wt-home-fixed')].filter(visible).filter(e=>getComputedStyle(e).position==='fixed');
   return {news:[...document.querySelectorAll('.wt-home-news')].filter(visible).map(e=>e.className),contact:[...document.querySelectorAll('.wt-home-contact')].filter(visible).map(e=>e.className),cta:[...document.querySelectorAll('.wt-home-cta')].filter(visible).map(e=>e.className),body:document.body.className,heroes:heroes.map(e=>e.className),sections:sections.map(e=>({id:e.id,classes:e.className,box:box(e)})),side:side.map(box),hero:hero?box(hero):null,fixed:fixed.map(box),overflow:document.documentElement.scrollWidth>innerWidth};
  });
  check(`${label}:one-selected-hero`,actual.heroes.length===1&&actual.heroes[0].includes('--'+scenario.values.home_hero),actual.heroes);
  const expectedSections=[...choices.find(c=>c.id===scenario.values.home_sections).sectionIds];
  // The purpose metadata follows its chosen news/contact settings; recover enabled slots before applying this case.
  const sectionDefaults={corporate:['news','greeting','service','features','numbers','cases','company','access','contact'],service:['service','features','numbers','cases','logos','price','faq','cta','contact'],media:['latest','categories','ranking','banners','news','cta','contact'],'shop-school':['news','service','features','stores','events','price','faq','recruit','banners','access','contact'],'school-org':['greeting','news','features','service','events','gallery','sns','history','logos','banners','cta','contact']};
  const expected=sectionDefaults[scenario.values.home_sections].filter(id=>(id!=='news'||scenario.values.home_news!=='none')&&(id!=='contact'||scenario.values.home_contact!=='none'));
  check(`${label}:section-order`,JSON.stringify(actual.sections.map(s=>s.id))===JSON.stringify(expected),actual.sections.map(s=>s.id));
  if(scenario.finished)check(`${label}:metadata-section-order`,JSON.stringify(actual.sections.map(s=>s.id))===JSON.stringify(expectedSections));
  check(`${label}:selected-news`,scenario.values.home_news==='none'||!expected.includes('news')?actual.news.length===0:actual.news.length===1&&actual.news[0].includes('--'+scenario.values.home_news));
  check(`${label}:selected-contact`,scenario.values.home_contact==='none'?actual.contact.length===0:actual.contact.length===1&&actual.contact[0].includes('--'+scenario.values.home_contact));
  check(`${label}:sidebar-choice-resolved`,actual.body.split(' ').includes('wt-side-layout-'+scenario.values.home_side_layout));
  check(`${label}:sidebar-disabled`,scenario.values.home_side_layout!=='none'||actual.side.length===0);
  if(scenario.values.home_hero!=='article-grid')check(`${label}:selected-hero-cta`,scenario.values.home_hero_cta==='none'?actual.cta.length===0:actual.cta.length>=1&&actual.cta.every(c=>c.includes('--'+scenario.values.home_hero_cta)));
  check(`${label}:no-horizontal-overflow`,!actual.overflow);
  if(width>=1024&&scenario.values.side_from==='below-hero')check(`${label}:sidebar-below-hero`,actual.side.every(s=>s.y>=actual.hero.bottom-1));
  if(width<600)check(`${label}:fixed-occupancy`,actual.fixed.every(s=>s.height<=900*.2),actual.fixed);
  check(`${label}:hero-width`,actual.hero.width<=width+1);
  if(scenario.finished&&js){
   await page.evaluate(async()=>{for(let y=0;y<document.documentElement.scrollHeight;y+=700){scrollTo(0,y);await new Promise(r=>setTimeout(r,35));}await Promise.all([...document.images].filter(i=>i.getClientRects().length).map(i=>i.decode().catch(()=>{})));scrollTo(0,0);});
   check(`${label}:visible-images-loaded`,await page.locator('main img:visible').evaluateAll(es=>es.every(e=>e.complete&&e.naturalWidth>0)));
   if(scenario.id==='media')check(`${label}:article-thumbnails`,await page.locator('.wt-home__section--article-grid .wp-block-post-featured-image img').count()===6);
   const file=`${baseline?'before':'after'}-${scenario.id}-${device}.jpg`;await page.screenshot({path:path.join(out,file),fullPage:true,type:'jpeg',quality:78});shots.push({file,device,purpose:scenario.id,route,sectionOrder:actual.sections.map(s=>s.id)});
  }
 }
 check(`${device}-${js?'js':'nojs'}:runtime-errors`,errors.length===0,errors);
 await context.close();
}
for(const [device,width]of [['pc',1440],['sp',375]]){
 const context=await browser.newContext({viewport:{width,height:900},reducedMotion:'reduce'});await baselineRoute(context);const page=await context.newPage();
 const route='/?wt='+encodeURIComponent('home_hero:split,home_sections:service,home_news:tabs,home_side_layout:right,side_from:below-hero');
 await page.goto(base+route);
 const layout=await page.evaluate(()=>{
  const hero=document.querySelector('.wt-home-hero--split');const head=hero.querySelector('h1');head.textContent='現場の困りごとを一緒に整理して、無理なく続けられる仕組みを考え、改善を積み重ねるために。';
  hero.querySelectorAll('img').forEach(e=>e.remove());
  const cards=[...document.querySelectorAll('.wt-home-cards--3 a')].filter(e=>e.getClientRects().length);cards.forEach((e,i)=>{e.querySelector('b').textContent=i===0?'相談':'業務の流れを整理して次の改善につなげる';e.querySelector('span').textContent=i===0?'小さく始める。':'担当者と一緒に課題を整理し、日々の作業を確認しながら、無理なく続けられる改善の順番を考えます。'.repeat(i+1);});
  const rects=cards.map(e=>{const r=e.getBoundingClientRect();return {y:r.y,height:r.height}});
  return {overflow:document.documentElement.scrollWidth>innerWidth,headingVisible:head.getBoundingClientRect().height>0,cards:rects,animation:[...document.querySelectorAll('.wt-home .wt-home-cards a')].filter(e=>e.getClientRects().length).some(e=>getComputedStyle(e).transitionDuration.split(',').some(t=>parseFloat(t)>0))};
 });
 check(`${device}:stress:no-image-long-copy-reflow`,!layout.overflow&&layout.headingVisible);
 check(`${device}:stress:same-row-card-height`,layout.cards.every(a=>layout.cards.filter(b=>Math.abs(a.y-b.y)<1).every(b=>Math.abs(a.height-b.height)<1)),layout.cards);
 check(`${device}:stress:reduced-motion`,!layout.animation);
 const file=`stress-${device}.jpg`;await page.screenshot({path:path.join(out,file),fullPage:true,type:'jpeg',quality:75});
 await page.goto(base+'/?wt=home_sections:corporate,home_news:tabs');
 const tabs=page.locator('.wt-home-tabs [role=tab]');await tabs.first().focus();await page.keyboard.press('ArrowRight');check(`${device}:tabs:keyboard`,await tabs.nth(1).getAttribute('aria-selected')==='true'&&await page.locator('#home-news-tab-news').isVisible());await page.keyboard.press('End');check(`${device}:tabs:last`,await tabs.last().getAttribute('aria-selected')==='true');
 await page.goto(base+'/?wt=home_hero:slider,home_hero_cta:double');
 const track=page.locator('.wt-home-slider__track');await track.focus();await page.keyboard.press('ArrowRight');check(`${device}:slider:keyboard-reduced-motion`,await track.evaluate(e=>e.scrollLeft>0));
 for(const type of ['fullbleed','slider']){
  await page.goto(base+'/?wt='+encodeURIComponent(`home_hero:${type},home_hero_cta:tel-button`));
  const box=page.locator(type==='fullbleed'?'.wt-home-hero--fullbleed .wt-home-hero__inner':'.wt-home-slider__caption').first();
  const contrast=await box.evaluate(e=>{const lum=c=>{const a=(c.match(/[\d.]+/g)||[]).slice(0,3).map(Number).map(v=>v/255).map(v=>v<=.04045?v/12.92:((v+.055)/1.055)**2.4);return a.reduce((n,v,i)=>n+v*[.2126,.7152,.0722][i],0);};const background=getComputedStyle(e).backgroundColor;const channels=background.match(/[\d.]+/g)||[];if(channels.length>3&&Number(channels[3])<1)return false;const bg=lum(background);return [...e.querySelectorAll('h1,h2,p,small')].filter(x=>x.getClientRects().length).every(x=>{const fg=lum(getComputedStyle(x).color);return (Math.max(bg,fg)+.05)/(Math.min(bg,fg)+.05)>=4.5;});});
  check(`${device}:${type}:opaque-text-panel-AA`,contrast);
 }
 await context.close();
 for(const js of [true,false]){
  const c=await browser.newContext({viewport:{width,height:900},javaScriptEnabled:js});await baselineRoute(c);const p=await c.newPage();
  for(const axes of ['home_hero:search-box,home_hero_cta:none','home_hero:text-only,home_hero_cta:search']){
   await p.goto(base+'/?wt='+encodeURIComponent(axes));const form=p.locator('main form[role=search]:visible');await form.locator('input[name=s]').fill('改善');await Promise.all([p.waitForURL(u=>u.searchParams.get('s')==='改善'),form.locator('button[type=submit]').click()]);check(`${device}-${js?'js':'nojs'}:${axes}:native-search`,new URL(p.url()).searchParams.get('s')==='改善');
  }
  await c.close();
 }
}
completed=true;
}finally{await browser.close();const remainingIds=ownedIds(fixtureRun);if(remainingIds.length)wp(['post','delete',...remainingIds.map(String),'--force']);const cleaned=ownedIds(fixtureRun).length===0;check('fixtures:cleanup',cleaned);if(cleaned)fs.unlinkSync(recoveryPath);check('source-unchanged',JSON.stringify(sourceDigests)===JSON.stringify(digests()));fs.writeFileSync(path.join(out,baseline?'baseline.json':'verification.json'),JSON.stringify({schema:'wt-home-completion.v1',completed,baselinePresentationDisabled:baseline,sourceDigests,axes,scenarioCount:process.argv.includes('--finished-only')?choices.length:scenarios.length,declaredScenarioCount:scenarios.length,rows,shots:shots.map(shot=>({...shot,sha256:createHash('sha256').update(fs.readFileSync(path.join(out,shot.file))).digest('hex')}))},null,2)+'\n');console.log(JSON.stringify({completed,scenarios:process.argv.includes('--finished-only')?choices.length:scenarios.length,checks:rows.length,failed:rows.filter(r=>!r.pass).length,failures:rows.filter(r=>!r.pass).slice(0,25)}));}
if(!baseline&&rows.some(r=>!r.pass))process.exitCode=1;
