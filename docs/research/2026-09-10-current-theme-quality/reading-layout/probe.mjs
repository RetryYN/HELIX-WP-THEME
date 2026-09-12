import {chromium} from 'playwright';
import {execFileSync} from 'node:child_process';
import {readFileSync,writeFileSync} from 'node:fs';
import {createHash} from 'node:crypto';
import assert from 'node:assert/strict';
import {fileURLToPath} from 'node:url';
const mode=process.argv[2]||'after';
const wp=code=>JSON.parse(execFileSync('docker',['exec','helix-content-wp','php','-r',"require '/var/www/html/wp-load.php';"+code],{encoding:'utf8'}));
const created=[],results=[],tocNone=[];const browser=await chromium.launch({args:['--no-sandbox']});
try{
for(const [face,type,template,content] of [['article','post','','<!-- wp:pattern {"slug":"helix-wt/article-kit"} /-->'],['lp','page','page-lp',''],['event','page','page-event','']]){
 const slug='reading-layout-audit-'+face;
 const fixture=wp(`if(get_page_by_path('${slug}',OBJECT,'${type}'))throw new Exception('collision');$id=wp_insert_post(['post_type'=>'${type}','post_name'=>'${slug}','post_title'=>'読みやすさの確認','post_status'=>'publish','post_content'=>'${content}'],true);if(is_wp_error($id))throw new Exception('create failed');if('${template}')update_post_meta($id,'_wp_page_template','${template}');echo wp_json_encode(['id'=>$id,'slug'=>'${slug}']);`);
 created.push(fixture);
 for(const width of [390,1440])for(const noJs of [false,true]){
 const page=await browser.newPage({viewport:{width,height:900},javaScriptEnabled:!noJs});
 const response=await page.goto(`http://127.0.0.1:8098/${slug}/?wt=toc:box,event_info:inline-text`,{waitUntil:'networkidle'});
 const state=await page.evaluate(()=>({overflow:document.documentElement.scrollWidth>innerWidth,toc:document.querySelectorAll('main .wt-toc').length,tocLinks:[...document.querySelectorAll('main .wt-toc a[href^="#"]')].every(a=>document.getElementById(a.hash.slice(1))),eventRows:document.querySelectorAll('.wt-event-info--inline-text>div').length,h1:document.querySelector('main h1')?.textContent,phrases:[...document.querySelectorAll('.wt-lp-phrase')].map(e=>({width:e.getBoundingClientRect().width,height:e.getBoundingClientRect().height,lineHeight:parseFloat(getComputedStyle(e).lineHeight)}))}));
 results.push({face,width,noJs,http:response.status(),...state});
 if(mode==='after'){assert.equal(response.status(),200);assert.equal(state.overflow,false);if(face==='article'){assert.equal(state.toc,1);assert.equal(state.tocLinks,true);}if(face==='event')assert.equal(state.eventRows,5);if(face==='lp'){assert.equal(state.phrases.length,3);assert(state.phrases.every(p=>p.height<=p.lineHeight+1));}}
 if(!noJs&&face==='event')await page.locator('.wt-event-info--inline-text').screenshot({path:fileURLToPath(new URL(`${mode}-event-info-${width}.png`,import.meta.url))});
 if(!noJs)await page.screenshot({path:fileURLToPath(new URL(`${mode}-${face}-${width}.png`,import.meta.url))});
 if(mode==='after'&&face==='article'){await page.goto(`http://127.0.0.1:8098/${slug}/?wt=toc:none`,{waitUntil:'networkidle'});const count=await page.locator('main .wt-toc').count();tocNone.push({width,noJs,count});assert.equal(count,0);}
 await page.close();
 }
}
}finally{
 await browser.close();
 for(const row of created)Object.assign(row,wp(`$p=get_post(${row.id});if(!$p||$p->post_name!=='${row.slug}')throw new Exception('identity mismatch');wp_delete_post(${row.id},true);echo wp_json_encode(['absent'=>get_post(${row.id})===null]);`));
 const payload={results,fixtures:created};
 writeFileSync(fileURLToPath(new URL(`${mode}.json`,import.meta.url)),JSON.stringify(payload,null,2)+'\n');
 if(mode==='after'){
  const relative=['patterns/article-kit.php','patterns/lp.php','patterns/event.php','assets/css/theme.css'];
  const theme=fileURLToPath(new URL('../../2026-09-05-design-prototype-03/theme/helix-wt/',import.meta.url));
  const sourceDigests=Object.fromEntries(relative.map(file=>[`docs/research/2026-09-05-design-prototype-03/theme/helix-wt/${file}`,createHash('sha256').update(readFileSync(theme+file)).digest('hex')]));
  const article=results.filter(row=>row.face==='article'),lp=results.filter(row=>row.face==='lp'),event=results.filter(row=>row.face==='event');
  const rows=[
   {name:'reading-layout:all-widths-reflow',pass:results.length===12&&results.every(row=>row.http===200&&!row.overflow)},
   {name:'reading-layout:single-derived-article-toc',pass:article.length===4&&article.every(row=>row.toc===1&&row.tocLinks)},
   {name:'reading-layout:toc-none-removes-derived-toc',pass:tocNone.length===4&&tocNone.every(row=>row.count===0)},
   {name:'reading-layout:lp-phrase-boundaries',pass:lp.length===4&&lp.every(row=>row.phrases.length===3&&row.phrases.every(phrase=>phrase.height<=phrase.lineHeight+1))},
   {name:'reading-layout:event-label-value-rows',pass:event.length===4&&event.every(row=>row.eventRows===5)},
   {name:'reading-layout:owned-fixtures-removed',pass:created.length===3&&created.every(row=>row.absent===true)},
  ];
  writeFileSync(fileURLToPath(new URL('verify.json',import.meta.url)),JSON.stringify({schema:'wt-reading-layout-verification.v1',completed:rows.every(row=>row.pass),sourceDigests,rows},null,2)+'\n');
 }
}
console.log(mode,JSON.stringify(results));
