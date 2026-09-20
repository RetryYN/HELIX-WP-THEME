import {test,expect} from '@playwright/test';
import path from 'node:path';
import {startUtilityServer} from '../../scripts/utility-poc-server.mjs';
import {defaults,project,schema} from '../../docs/research/2026-09-20-admin-poc/contract.mjs';
let service:Awaited<ReturnType<typeof startUtilityServer>>;
test.beforeAll(async()=>{service=await startUtilityServer();});
test.afterAll(async()=>{await service.close();});
const url=(layer:string)=>`${service.base}/docs/research/2026-09-20-admin-poc/${layer}.html`;
for(const layer of ['site','parts','article'])for(const width of [1440,390,320])for(const js of [true,false]){
 test(`AC-C ${layer} ${width}px ${js?'js':'no-js'} layer and reflow`,async({browser},info)=>{
  const context=await browser.newContext({viewport:{width,height:1000},javaScriptEnabled:js});const page=await context.newPage();await page.goto(url(layer));
  await expect(page.locator('h1')).toBeVisible();await expect(page.locator('[aria-current=page]')).toHaveAttribute('href',`${layer}.html`);
  expect(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth)).toBe(true);
  await expect(page.getByText('全P01–P33、WP 7.2実機',{exact:false})).toBeVisible();
  if(js){await expect(page.locator('#state')).toHaveText('未変更');await expect(page.locator('#projection')).toContainText('postMetaPreview');}
  else{await expect(page.locator('.diagnostic')).toContainText('JavaScriptが無効');await page.getByRole('button',{name:'一時設定へ適用'}).click();expect(page.url()).toBe(url(layer));}
  if(js&&width!==320){await page.locator('header').click();const name=`${layer}-${width===1440?'pc':'sp'}.jpg`;await page.screenshot({path:process.env.ADMIN_CAPTURE_DIR?path.join(process.env.ADMIN_CAPTURE_DIR,name):info.outputPath(name),fullPage:true,type:'jpeg',quality:85});}
  await context.close();
 });
}
test('AC-A apply export import and manifest projection use same document',async({page})=>{
 await page.goto(url('site'));await page.getByLabel('サービス紹介',{exact:false}).check();await page.getByLabel('セットの管理名').fill('比較用');await page.getByRole('button',{name:'一時設定へ適用'}).click();await page.locator('#transfer > summary').click();await page.locator('#export').click();const exported=JSON.parse(await page.locator('#json').inputValue());expect(exported['site.bundle']).toBe('service');expect(JSON.parse((await page.locator('#projection').textContent())!)).toEqual(project(exported));
 await page.reload();await page.locator('#transfer > summary').click();await page.locator('#json').fill(JSON.stringify(exported));await page.locator('#import').click();expect(JSON.parse(await page.locator('#json').inputValue())).toEqual(exported);expect(JSON.parse((await page.locator('#projection').textContent())!)).toEqual(project(exported));await expect(page.getByLabel('セットの管理名')).toHaveValue('比較用');expect(await page.evaluate(()=>localStorage.length+sessionStorage.length)).toBe(0);
});
for(const [name,change]of Object.entries({unknown:{credential:'example'},pattern:{'site.label':'<bad>'},length:{'site.label':'x'.repeat(25)},range:{'parts.threshold':9},fraction:{'parts.threshold':3.5},missing:{'site.label':null},version:{schema:'other'},enum:{'parts.toc':'invalid'}}))test(`AC-B rejected ${name} preserves applied projection`,async({page})=>{await page.goto(url('site'));const before=(await page.locator('#projection').textContent())!;await page.locator('#transfer > summary').click();await page.locator('#json').fill(JSON.stringify({...defaults(),...change}));await page.locator('#import').click();await expect(page.locator('#diagnostic')).toBeFocused();await expect(page.locator('#state')).toHaveAttribute('data-state','invalid');expect((await page.locator('#projection').textContent())!).toBe(before);});
test('AC-B invalid syntax has readable diagnostic',async({page})=>{await page.goto(url('site'));await page.locator('#transfer > summary').click();await page.locator('#json').fill('{');await page.locator('#import').click();await expect(page.locator('#errors')).toContainText('JSONオブジェクト');});
test('AC-E schema boundaries and hierarchy bulk input states',async({page})=>{
 await page.goto(url('article'));expect(schema.hierarchy.map(x=>x.parent)).toEqual([null,'site','parts']);await expect(page.locator('#bulk')).toHaveAttribute('aria-disabled','true');await page.locator('#bulk').click({force:true});await expect(page.locator('#bulk-help')).toContainText('先に対象');await page.getByLabel('この記事では非表示',{exact:false}).check();await expect(page.locator('#state')).toHaveAttribute('data-state','dirty');await page.getByRole('button',{name:'一時設定へ適用'}).click();await expect(page.locator('#effective')).toContainText('非表示 / 記事：上書き');await page.locator('#bulk-select').check();await page.locator('#bulk').click();await expect(page.locator('#effective')).toContainText('継承');await expect(page.locator('#state')).toHaveAttribute('data-state','applied');
 await page.locator('#transfer > summary').click();for(const v of [3,8]){await page.locator('#json').fill(JSON.stringify({...defaults(),'parts.threshold':v,'site.label':'x'.repeat(v===3?1:24)}));await page.locator('#import').click();await expect(page.locator('#diagnostic')).toBeHidden();}
});
test('AC-D fixed guards ownership C note and keyboard at 200 percent root text',async({page})=>{
 await page.setViewportSize({width:390,height:900});await page.goto(url('parts'));await page.keyboard.press('Tab');await expect(page.locator('.skip')).toBeFocused();await page.keyboard.press('Enter');await page.getByLabel('見出しが３つ以上',{exact:false}).focus();await page.keyboard.press('ArrowRight');await expect(page.getByLabel('表示しない',{exact:false})).toBeChecked();await page.getByRole('button',{name:'一時設定へ適用'}).focus();await page.keyboard.press('Enter');await expect(page.locator('#effective')).toContainText('非表示');await page.addStyleTag({content:':root{font-size:32px}'});expect(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth)).toBe(true);await expect(page.locator('.limits')).toContainText('要自社検証');await expect(page.locator('.limits')).toContainText('変更できないガード');expect(await page.locator('button.primary').evaluate(e=>e.getBoundingClientRect().height)).toBeGreaterThanOrEqual(44);
});
