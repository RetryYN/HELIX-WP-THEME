import { chromium } from 'playwright';
import fs from 'node:fs';
import path from 'node:path';
import { createHash } from 'node:crypto';
import { fileURLToPath } from 'node:url';
const root=path.resolve(path.dirname(fileURLToPath(import.meta.url)),'..');
const baseline=process.argv.includes('--baseline');
const out=path.join(root,'docs/research/2026-09-08-content-faces/results/site-quality');
fs.mkdirSync(out,{recursive:true});
const sourceFiles=['scripts/verify-site-page-quality.mjs','docs/research/2026-09-08-content-faces/plugin/site-pages.json',...['theme.json','inc/site-pages.php','assets/css/site-pages.css'].map(f=>'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/'+f)];
sourceFiles.push(...['functions.php','inc/footer-navigation.php','parts/footer.html','patterns/footer-sitemap.php','patterns/footer-related.php'].map(p=>'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/'+p));
const digest=f=>createHash('sha256').update(fs.readFileSync(path.join(root,f))).digest('hex');
sourceFiles.push('docs/research/2026-09-05-design-prototype-03/theme/helix-wt/inc/content-chrome.php');
const sourceDigests=Object.fromEntries(sourceFiles.map(f=>[f,digest(f)]));
const manifest=JSON.parse(fs.readFileSync(path.join(root,sourceFiles[1])));
const rows=[],measurements=[],shots=[];let completed=false;
const browser=await chromium.launch();
const check=(name,pass)=>rows.push({name,pass:!!pass});
try{
 for(const [device,width] of [['pc',1440],['sp',375]])for(const js of [true,false]){
  const context=await browser.newContext({viewport:{width,height:900},javaScriptEnabled:js,reducedMotion:'reduce'});
  const page=await context.newPage();
  for(const key of Object.keys(manifest.pages)){
   const response=await page.goto(`http://127.0.0.1:8098/site-${key}/`,{waitUntil:'load'});
   const label=`${key}:${device}:js-${js}`;
   check(`response:${label}`,response.ok());
   const result=await page.evaluate(()=>{
    const links=[...document.querySelectorAll('.wtsite a:not(.wtsite-skip)')].map(e=>{const r=e.getBoundingClientRect();return {text:e.textContent.trim(),width:r.width,height:r.height};});
    const body=[...document.querySelectorAll('.wtsite article p,.wtsite article dd')].map(e=>parseFloat(getComputedStyle(e).fontSize));
    return {links,body,overflow:document.documentElement.scrollWidth-innerWidth};
   });
   measurements.push({label,...result});
   check(`target-size:${label}`,result.links.length>0&&result.links.every(r=>r.width>=44&&r.height>=44));
   check(`body-size:${label}`,result.body.length>0&&result.body.every(n=>n>=16));
   check(`reflow:${label}`,result.overflow===0);
   await page.keyboard.press('Tab');
   const focus=await page.evaluate(()=>{const e=document.activeElement,r=e.getBoundingClientRect(),s=getComputedStyle(e);return {skip:e.tagName==='A'&&e.hash==='#site-main',width:r.width,height:r.height,inside:r.left>=0&&r.top>=0&&r.right<=innerWidth&&r.bottom<=innerHeight,outline:s.outlineStyle!=='none'&&parseFloat(s.outlineWidth)>0};});
   measurements.at(-1).focus=focus;
   check(`skip-focus:${label}`,focus.skip&&focus.inside&&focus.outline&&focus.width>=44&&focus.height>=44);
   await page.keyboard.press('Enter');
   check(`skip-target:${label}`,await page.evaluate(()=>document.activeElement.id==='site-main'));
   if(js&&['company','pricing'].includes(key)){
    await page.goto(`http://127.0.0.1:8098/site-${key}/`);
    const file=`${baseline?'before':'after'}-${key}-${device}.jpg`;
    await page.screenshot({path:path.join(out,file),fullPage:true,type:'jpeg',quality:82});shots.push({file,key,device,width});
   }
  }
  await context.close();
 }
 check('sources-unchanged',sourceFiles.every(f=>digest(f)===sourceDigests[f]));
 completed=true;
}finally{
 await browser.close();
 const summary={checks:rows.length,failed:rows.filter(r=>!r.pass).length,undersizedTargets:measurements.reduce((n,m)=>n+m.links.filter(r=>r.width<44||r.height<44).length,0)};
 fs.writeFileSync(path.join(out,baseline?'baseline.json':'verify.json'),JSON.stringify({schema:'wt-site-quality.v1',completed,sourceDigests,summary,rows,measurements,shots},null,2)+'\n');
 console.log(JSON.stringify(summary));
}
if(!baseline&&rows.some(r=>!r.pass))process.exitCode=1;
