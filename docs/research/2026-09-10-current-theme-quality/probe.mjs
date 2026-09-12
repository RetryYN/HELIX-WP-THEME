import { chromium } from '@playwright/test';
import { readFileSync,writeFileSync } from 'node:fs';
import { createHash } from 'node:crypto';
const dir='docs/research/2026-09-10-current-theme-quality/';
const theme='docs/research/2026-09-05-design-prototype-03/theme/helix-wt/';
const mode=process.argv[2]??'before';
const base='http://127.0.0.1:8098';
const urls=['/library/decision-design/','/voices/making-room/','/guides/before-redesign/','/start/editorial-session/','/library/','/learn/'];
const rows=[];const browser=await chromium.launch();
try{
 for(const route of urls)for(const width of [390,1440])for(const design of ['editorial','standard']){
  const page=await browser.newPage({viewport:{width,height:900},reducedMotion:'reduce'});
  if(mode==='trial')await page.route('**/content-faces.css*',async route=>{const response=await route.fetch();await route.fulfill({response,body:(await response.text()).replace(/!important/g,'')});});
  const response=await page.goto(base+route+'?design='+design,{waitUntil:'networkidle'});
  const data=await page.evaluate(()=>{
   const props=['font-family','font-size','font-weight','line-height','letter-spacing','color','background-color','border-width','border-radius','box-shadow','padding','margin','display','grid-template-columns'];
   const root=document.querySelector('.wtcf');
   return {found:!!root,status:document.title,overflow:document.documentElement.scrollWidth>innerWidth,styles:root?[...root.querySelectorAll('*')].map(e=>({tag:e.tagName,class:e.className,text:e.textContent.slice(0,40),styles:Object.fromEntries(props.map(p=>[p,getComputedStyle(e).getPropertyValue(p)]))})):[]};
  });
  rows.push({route,width,design,http:response.status(),...data});
  if((mode==='before'||mode==='after')&&design==='editorial'&&['/library/decision-design/','/voices/making-room/'].includes(route))await page.screenshot({path:dir+mode+'-'+route.split('/')[1]+'-'+width+'.png',fullPage:true});
  await page.close();
 }
 const source=readFileSync(theme+'assets/css/content-faces.css','utf8');
 writeFileSync(dir+mode+'.json',JSON.stringify({sourceDigest:createHash('sha256').update(source).digest('hex'),rows},null,2)+'\n');
 console.log(mode,rows.length,'rows',rows.filter(r=>r.overflow||!r.found||r.http!==200).map(r=>[r.route,r.width,r.http,r.found,r.overflow]));
 if(mode!=='before'){
  const before=JSON.parse(readFileSync(dir+'before.json','utf8')).rows;
  const differences=rows.flatMap((row,i)=>row.styles.flatMap((s,j)=>JSON.stringify(s)===JSON.stringify(before[i].styles[j])?[]:[{route:row.route,width:row.width,design:row.design,index:j,before:before[i].styles[j],after:s}]));
  writeFileSync(dir+mode+'-differences.json',JSON.stringify(differences,null,2)+'\n');console.log('computed differences',differences.length);
 }
}finally{await browser.close();}
