import {chromium} from 'playwright';
import {fileURLToPath} from 'node:url';
import {writeFileSync} from 'node:fs';
const browser=await chromium.launch({args:['--no-sandbox']});const results=[];
try{for(const width of [390,1440]){
 const page=await browser.newPage({viewport:{width,height:900}});
 await page.goto(`${process.env.CATALOG_BASE_URL||'http://127.0.0.1:8099'}/docs/research/2026-09-08-selection-catalog/`);
 await page.locator('.tile-open').first().click();
 const jump=page.locator('#detail [data-requirement-id="WT-FR-SP-01"]');
 await jump.scrollIntoViewIfNeeded();
 await page.screenshot({path:fileURLToPath(new URL(`detail-${width}.png`,import.meta.url))});
 await jump.click();
 await page.screenshot({path:fileURLToPath(new URL(`requirement-${width}.png`,import.meta.url))});
 await page.getByRole('button',{name:'元の候補へ戻る',exact:true}).click();
 results.push({width,focusReturned:await jump.evaluate(e=>e===document.activeElement),overflow:await page.evaluate(()=>document.documentElement.scrollWidth>innerWidth)});
 await page.screenshot({path:fileURLToPath(new URL(`return-${width}.png`,import.meta.url))});
 await page.close();
}}finally{await browser.close();}
writeFileSync(fileURLToPath(new URL('capture.json',import.meta.url)),JSON.stringify(results,null,2)+'\n');console.log(results);
