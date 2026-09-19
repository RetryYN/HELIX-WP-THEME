import { chromium } from '@playwright/test';
import { readFile, writeFile } from 'node:fs/promises';
import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import assert from 'node:assert/strict';
const root='docs/research/2026-09-08-selection-catalog';
const output=`${root}/visual-quality/mobile-forms`;
const base=process.env.CATALOG_BASE_URL;
assert(base,'CATALOG_BASE_URL required');
const baseline=execFileSync('git',['rev-parse','f11add2'],{encoding:'utf8'}).trim();
const sources=[`${root}/catalog.css`,`${root}/catalog.mjs`,`${root}/index.html`,`${output}/capture.mjs`];
const sourceDigests={};
for(const path of sources){const local=await readFile(path); if(!path.endsWith('capture.mjs')) assert(local.equals(Buffer.from(await (await fetch(`${base}/${path}`)).arrayBuffer())),'Server source mismatch');sourceDigests[path]=createHash('sha256').update(local).digest('hex');}
const browser=await chromium.launch();const observations=[];
try{
for(const stage of ['before','after'])for(const width of [320,390,700,1440]){
 const page=await browser.newPage({viewport:{width,height:900}});
 if(stage==='before')await page.route(`${base}/${root}/catalog.css`,route=>route.fulfill({body:execFileSync('git',['show',`${baseline}:${root}/catalog.css`]),contentType:'text/css'}));
 await page.goto(`${base}/${root}/`);await page.locator('.tile').first().waitFor();
 const measure=async selector=>page.locator(selector).evaluateAll(es=>es.map(e=>({id:e.id,fontSize:parseFloat(getComputedStyle(e).fontSize),height:e.getBoundingClientRect().height})));
 const inputs=await measure('#search,#purpose,#decision');
 await page.locator('.tile-open').first().click();inputs.push(...await measure('.decision-panel textarea'));await page.locator('#detail .close').click();
 await page.locator('[data-face=all]').click();
 await page.locator('#search').fill('料金');await page.locator('#search').scrollIntoViewIfNeeded();
 await page.screenshot({path:`${output}/${stage}-${width}.png`});
 await page.locator('#tab-requirements').click();inputs.push(...await measure('#req-search,#evidence-state'));
 const overflow=await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth);
 if(stage==='after'){assert(!overflow,`overflow ${width}`);if(width<=700)for(const input of inputs){assert(input.fontSize>=16,`${input.id} font`);assert(input.height>=44,`${input.id} height`);}}
 observations.push({stage,width,inputs,overflow});await page.close();
}
}finally{await browser.close();}
await writeFile(`${output}/observations.json`,JSON.stringify({schema:'wt-mobile-forms.v1',baseline,completed:true,sourceDigests,observations,limitations:['Chromium only; real-device browser zoom behavior not measured','Input readability and page overflow only; no whole-site accessibility claim']},null,2)+'\n');
