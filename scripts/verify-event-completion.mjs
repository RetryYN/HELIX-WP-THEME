import{execFileSync}from'node:child_process';import{chromium}from'playwright';import fs from'node:fs';import os from'node:os';import path from'node:path';import{fileURLToPath}from'node:url';import{createHash,randomUUID}from'node:crypto';
const root=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'..'),out=path.join(root,'docs/research/2026-09-15-event-completion'),state=process.env.WTCF_STATE_DIR||path.join(os.tmpdir(),'helix-content-lab');
const baseline=process.argv.includes('--baseline'),finishedOnly=process.argv.includes('--finished-only'),base='http://127.0.0.1:8098',slug='event-completion-fixture',run=randomUUID();
const wp=args=>execFileSync('docker',['run','--rm','--network','helix-content-lab','--env-file',path.join(state,'wp.env'),'--volumes-from','helix-content-wp','--user','33:33','wordpress:cli-php8.3','wp',...args],{encoding:'utf8',stdio:['ignore','pipe','pipe']}).trim();
if(wp(['option','get','blogname'])!=='HELIX Content Lab')throw Error('Dedicated lab required');
const reserved=()=>wp(['post','list','--post_type=page','--post_status=any','--name='+slug,'--format=ids']);if(reserved())throw Error('Reserved fixture exists');
const lock=path.join(state,'event-completion-fixture.json');if(fs.existsSync(lock))throw Error('Previous run requires ownership recovery');fs.writeFileSync(lock,JSON.stringify({run,pid:process.pid}),{flag:'wx'});
const choices=JSON.parse(fs.readFileSync(path.join(out,'choices.json'))).choices,rows=[],shots=[];
const check=(name,pass,details)=>rows.push({name,pass:!!pass,...(details===undefined?{}:{details})});
const theme='docs/research/2026-09-05-design-prototype-03/theme/helix-wt/';const files=['scripts/verify-event-completion.mjs','docs/research/2026-09-15-event-completion/choices.json',...['functions.php','patterns/event.php','templates/page-event.html','assets/css/theme.css','assets/css/event-completion.css','assets/css/event-state.css','inc/event-state.php','inc/form.php'].map(f=>theme+f)];
const digest=()=>Object.fromEntries(files.map(f=>[f,createHash('sha256').update(fs.readFileSync(path.join(root,f))).digest('hex')]));const sourceDigests=digest();
let id,completed=false,browser,optionSnapshot;
try{
 optionSnapshot=JSON.parse(wp(['eval',`$s=new stdClass();$v=get_option('wtcf_event_fixture_mode',$s);echo wp_json_encode(array('exists'=>$v!==$s,'value'=>$v===$s?null:$v));`]));
 id=Number(wp(['post','create','--post_type=page','--post_status=publish','--post_name='+slug,'--post_title=Event Completion Fixture','--porcelain']));if(!Number.isInteger(id)||id<=0)throw Error('Invalid fixture id');
 wp(['post','meta','update',String(id),'wt_event_completion_owner',run]);wp(['post','meta','update',String(id),'_wp_page_template','page-event']);
 fs.writeFileSync(lock,JSON.stringify({run,pid:process.pid,id,optionSnapshot}));
 browser=await chromium.launch();
 const declared=JSON.parse(wp(['eval','echo wp_json_encode(wt_axes());']));
 const axes=Object.fromEntries(Object.entries(declared).filter(([k])=>/^event_(hero|info|schedule|speakers|sections|apply|status|map|fixed|share|side)$/.test(k)).map(([k,v])=>[k,v[1]]));
 const scenarios=choices.map(c=>({id:c.id,values:c.values,finished:true,sectionIds:c.sectionIds}));
 if(!finishedOnly)for(const [key,values]of Object.entries(axes))for(const value of values)scenarios.push({id:`${key}-${value}`,values:{...choices[0].values,[key]:value}});
 for(const[device,width]of[['pc',1440],['sp',375]])for(const js of[true,false]){
  const context=await browser.newContext({viewport:{width,height:900},javaScriptEnabled:js,reducedMotion:'reduce'});if(baseline)await context.route('**/assets/css/event-completion.css*',r=>r.fulfill({contentType:'text/css',body:''}));
  const page=await context.newPage(),errors=[];page.on('pageerror',e=>errors.push(e.message));
  for(const s of scenarios){
   const route='/'+slug+'/?wt='+encodeURIComponent(Object.entries(s.values).map(([k,v])=>`${k}:${v}`).join(',')),label=`${device}-${js?'js':'nojs'}:${s.id}`;
   const requests=[];page.on('request',record);function record(r){if(!r.url().startsWith('data:')&&new URL(r.url()).origin!==base)requests.push(new URL(r.url()).origin);}
   const response=await page.goto(base+route);await page.evaluate(()=>document.fonts.ready);page.off('request',record);
   const actual=await page.evaluate(()=>{const visible=e=>e.getClientRects().length&&getComputedStyle(e).visibility!=='hidden';const es=[...document.querySelectorAll('.wt-event__section')].filter(visible).sort((a,b)=>a.getBoundingClientRect().top-b.getBoundingClientRect().top);return{sections:es.map(e=>e.id),heroes:[...document.querySelectorAll('.wt-event-hero')].filter(visible).map(e=>e.className),overflow:document.documentElement.scrollWidth>innerWidth,forms:[...document.querySelectorAll('.wt-event-apply')].filter(visible).map(e=>e.className),maps:[...document.querySelectorAll('.wt-event-map')].filter(visible).map(e=>e.className),body:document.body.className,h1:[...document.querySelectorAll('h1')].filter(visible).length,frames:document.querySelectorAll('.wt-event iframe').length,side:[...document.querySelectorAll('.wt-event .wt-side')].filter(visible).length};});
   check(label+':response',response.ok());check(label+':one-hero',actual.heroes.length===1&&actual.heroes[0].includes('--'+s.values.event_hero),actual.heroes);check(label+':one-heading',actual.h1===1);check(label+':no-overflow',!actual.overflow);check(label+':no-external-request',requests.length===0,requests);
   if(s.finished)check(label+':metadata-section-order',JSON.stringify(actual.sections)===JSON.stringify(s.sectionIds),actual.sections);
   check(label+':selected-application',actual.forms.length===1&&actual.forms[0].includes('--'+s.values.event_apply),actual.forms);
   check(label+':selected-map',s.values.event_map==='none'||!actual.sections.includes('access')?actual.maps.length===0:actual.maps.length===1&&actual.maps[0].includes('--'+s.values.event_map),actual.maps);
   if(s.values.event_map!=='embed')check(label+':unselected-map-no-iframe',actual.frames===0);
   if(s.values.event_side==='off')check(label+':sidebar-off',actual.side===0);
   if(s.finished&&js){await page.evaluate(async()=>{for(let y=0;y<document.documentElement.scrollHeight;y+=700){scrollTo(0,y);await new Promise(r=>setTimeout(r,25));}await Promise.all([...document.images].filter(e=>e.getClientRects().length).map(e=>e.decode().catch(()=>{})));scrollTo(0,0);});
    const file=`${baseline?'before':'after'}-${s.id}-${device}.jpg`;await page.screenshot({path:path.join(out,file),fullPage:true,type:'jpeg',quality:78});shots.push({file,device,purpose:s.id,route,sha256:createHash('sha256').update(fs.readFileSync(path.join(out,file))).digest('hex')});
   }
   if(!baseline&&s.finished){
    const quality=await page.evaluate(()=>{const vis=e=>e.getClientRects().length&&getComputedStyle(e).visibility!=='hidden';const title=[...document.querySelectorAll('.wt-event-hero h1')].find(vis);title.textContent='自分に合った参加方法を選ぶための長いイベント名と、開催内容を最後まで確認するための案内';for(const img of document.querySelectorAll('.wt-event-speakers img'))img.remove();const card=document.querySelector('.wt-event-speakers--cards-photo li span');if(card)card.textContent='長い紹介文でも内容を省略せず確認できます。'.repeat(12);scrollTo(0,document.documentElement.scrollHeight);const fixed=[...document.querySelectorAll('.wt-fixed,.wt-event-fixed')].filter(vis).filter(e=>getComputedStyle(e).position==='fixed'),footer=document.querySelector('.wt-footer'),links=footer?[...footer.querySelectorAll('a')].filter(vis):[],last=links.at(-1);const photo=document.querySelector('.wt-event-hero--photo-overlay .wt-event-hero__inner'),cards=[...document.querySelectorAll('.wt-event-speakers--cards-photo li')].filter(vis).map(e=>e.getBoundingClientRect());return{overflow:document.documentElement.scrollWidth>innerWidth,titleClipped:(title.scrollHeight>title.clientHeight+1&&['hidden','clip'].includes(getComputedStyle(title).overflowY))||title.getBoundingClientRect().bottom>title.closest('.wt-event-hero').getBoundingClientRect().bottom+1,footerClear:!last||fixed.every(e=>last.getBoundingClientRect().bottom<=e.getBoundingClientRect().top+1),cardRows:cards.every((a,i)=>cards.every((b,j)=>i===j||Math.abs(a.top-b.top)>1||Math.abs(a.height-b.height)<1)),photoBackground:photo?getComputedStyle(photo).backgroundColor:null};});
    check(label+':long-text-no-overflow',!quality.overflow);check(label+':long-title-readable',!quality.titleClipped);check(label+':image-absence-card-rows',quality.cardRows);if(width<600)check(label+':fixed-footer-clear',quality.footerClear);if(s.values.event_hero==='photo-overlay')check(label+':opaque-photo-panel',quality.photoBackground&&!quality.photoBackground.startsWith('rgba('),quality.photoBackground);
   }
  }
  check(`${device}-${js?'js':'nojs'}:runtime-errors`,errors.length===0,errors);await context.close();
 }
 if(!baseline&&!finishedOnly){
  wp(['option','update','wtcf_event_fixture_mode','1']);
  for(const fixture of[{name:'before',time:'2026-10-01T08:59:59Z',registered:0,label:'受付前'},{name:'open',time:'2026-10-01T09:00:00Z',registered:0,label:'受付中'},{name:'full',time:'2026-10-02T09:00:00Z',registered:50,label:'満席'},{name:'ended',time:'2026-10-14T09:00:00Z',registered:0,label:'受付終了'}]){
   wp(['post','meta','update',String(id),'_wtcf_event_fixture',JSON.stringify({opens_at:'2026-10-01T09:00:00Z',closes_at:'2026-10-14T09:00:00Z',observed_at:fixture.time,capacity:50,registered:fixture.registered})]);
   for(const[device,width]of[['pc',1440],['sp',375]])for(const js of[true,false]){const c=await browser.newContext({viewport:{width,height:900},javaScriptEnabled:js,reducedMotion:'reduce'}),p=await c.newPage();await p.goto(base+'/'+slug+'/?wt=event_apply:block-form,event_fixed:sp-bottom-bar,event_fix:own');const name=`state:${fixture.name}:${device}-${js?'js':'nojs'}`;check(name+':label',await p.locator('[data-wt-event-state]:visible').count()===1&&await p.locator('[data-wt-event-state]:visible').textContent()===fixture.label);check(name+':form',await p.locator('.wt-form form:visible').count()===(fixture.name==='open'?1:0));check(name+':anchor',await p.locator('#apply:visible').count()===1);if(js){const file=`state-${fixture.name}-${device}.jpg`;await p.screenshot({path:path.join(out,file),fullPage:true,type:'jpeg',quality:78});}await c.close();}
  }
 }
 completed=true;
}finally{
 await browser?.close();
 if(id){const owner=wp(['post','meta','get',String(id),'wt_event_completion_owner']);if(owner!==run)throw Error('Fixture ownership mismatch');wp(['post','delete',String(id),'--force']);}
 if(optionSnapshot){if(optionSnapshot.exists)wp(['option','update','wtcf_event_fixture_mode',JSON.stringify(optionSnapshot.value),'--format=json']);else wp(['option','delete','wtcf_event_fixture_mode']);}
 check('fixtures:cleanup',reserved()==='');check('source-unchanged',JSON.stringify(digest())===JSON.stringify(sourceDigests));
 fs.unlinkSync(lock);
 fs.writeFileSync(path.join(out,baseline?'baseline.json':'verification.json'),JSON.stringify({schema:'wt-event-completion.v1',completed,baselinePresentationDisabled:baseline,finishedOnly,sourceDigests,shots,rows},null,2)+'\n');console.log(JSON.stringify({completed,checks:rows.length,failed:rows.filter(r=>!r.pass)}));
}if(rows.some(r=>!r.pass))process.exitCode=1;
