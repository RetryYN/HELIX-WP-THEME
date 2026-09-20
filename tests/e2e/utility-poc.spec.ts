import {test,expect,Page} from '@playwright/test';
import {startUtilityServer} from '../../scripts/utility-poc-server.mjs';
import {definitions} from '../../docs/research/2026-09-20-utility-poc/definitions.mjs';
import path from 'node:path';
let service:Awaited<ReturnType<typeof startUtilityServer>>;
test.beforeAll(async()=>{service=await startUtilityServer();});
test.afterAll(async()=>{await service.close();});
const route='/docs/research/2026-09-20-utility-poc/';
async function fill(page:Page,kind:string){if(kind==='calculator'){await page.locator('#people').fill('12');await page.locator('#cost').fill('2500');}else if(kind==='grader'){await page.locator('#purpose').check();await page.locator('#audience').check();}else await page.locator('#topic').fill('暮らしの道具');}
for(const d of definitions){
 for(const [device,width]of [['pc',1440],['sp',390],['narrow',320]] as const)for(const scale of [1,2])for(const js of [true,false]){
  test(`AC-A-D ${d.kind} ${device} ${scale}x ${js?'js':'nojs'} contract and reflow`,async({browser},info)=>{
   const context=await browser.newContext({viewport:{width,height:1000},javaScriptEnabled:js,reducedMotion:'reduce'}),page=await context.newPage();
   try{
    await page.goto(`${service.base}${route}${d.kind}.html`);
    if(scale===2)await page.locator('html').evaluate(e=>e.style.fontSize='32px');
    expect(await page.locator('html').evaluate(e=>parseFloat(getComputedStyle(e).fontSize))).toBe(16*scale);
    await expect(page.locator('h1')).toHaveText(d.title);
    await expect(page.locator('.privacy')).toContainText('入力は保存しません');
    for(const f of d.fields)await expect(page.getByLabel(f.label,{exact:true})).toBeVisible();
    await expect(page.locator('#manual')).toContainText(d.manual);
    if(js){
     await fill(page,d.kind);await page.locator('form input').last().focus();await page.keyboard.press('Tab');await expect(page.locator('#submit')).toBeFocused();await page.keyboard.press('Enter');
     await expect(page.locator('#result')).toBeVisible();await expect(page.locator('#result-title')).toBeFocused();
     await expect(page.locator('#status')).toContainText('結果を更新');await expect(page.locator('#status')).toHaveAttribute('role','status');
     await expect(page.locator('#basis')).toContainText(d.method);await expect(page.locator('#basis')).toContainText(d.exclusions);await expect(page.locator('#basis')).toContainText('2026-09-20');await expect(page.locator('#basis')).toContainText('丸め');
     const expected=d.kind==='calculator'?'30,000 円':d.kind==='grader'?'2 / 3 項目を確認':'３つの見出しのたたき台';await expect(page.locator('#value')).toHaveText(expected);
     if(scale===1&&device!=='narrow'){await page.evaluate(()=>document.fonts.ready);await page.screenshot({path:process.env.UTILITY_CAPTURE_DIR?path.join(process.env.UTILITY_CAPTURE_DIR,`${d.kind}-${device}.jpg`):info.outputPath(`${d.kind}-${device}.jpg`),type:'jpeg',quality:88,fullPage:true});}
     await page.locator('#again').click();await expect(page.locator('form input').first()).toBeFocused();await expect(page.locator('#result')).toBeHidden();
    }else{await expect(page.locator('.nojs')).toContainText('JavaScriptが無効');await expect(page.locator('#submit')).toBeDisabled();await page.getByRole('link',{name:'手元で確かめる方法 ↓'}).click();await expect(page).toHaveURL(/#manual$/);}
    expect(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth)).toBe(true);
    const boxes=await page.locator('.input-panel,.result-panel,#manual').evaluateAll(es=>es.map(e=>({top:e.getBoundingClientRect().top,bottom:e.getBoundingClientRect().bottom,left:e.getBoundingClientRect().left,right:e.getBoundingClientRect().right})));
    expect(boxes[2].top).toBeGreaterThanOrEqual(Math.max(boxes[0].bottom,boxes[1].bottom));if(width<700)expect(boxes[1].top).toBeGreaterThanOrEqual(boxes[0].bottom);
    expect(await page.locator('input,button:not([hidden]),nav a').evaluateAll(es=>es.every(e=>{const r=e.getBoundingClientRect();return r.right<=innerWidth&&r.left>=0&&(e instanceof HTMLInputElement||e.scrollWidth<=e.clientWidth+1);}))).toBe(true);
   }finally{await context.close();}
  });
 }
 test(`AC-B ${d.kind} transient input and separate processor`,async({page})=>{
  const writes:string[]=[];await page.exposeFunction('recordWrite',(key:string)=>writes.push(key));
  await page.addInitScript(()=>{Storage.prototype.setItem=function(k:string){void (window as unknown as {recordWrite:(k:string)=>void}).recordWrite(k);};});
  const requests:string[]=[];page.on('request',r=>{if(r.method()==='POST')requests.push(r.url());});
  await page.goto(`${service.base}${route}${d.kind}.html`);await fill(page,d.kind);await page.locator('#submit').click();await expect(page.locator('#result')).toBeVisible();
  expect(requests).toEqual([`${service.base}/utility/execute`]);expect(writes).toEqual([]);expect(await page.context().cookies()).toEqual([]);
  await page.reload();for(const f of d.fields){if(f.type==='checkbox')await expect(page.locator('#'+f.id)).not.toBeChecked();else await expect(page.locator('#'+f.id)).toHaveValue('');}await expect(page.locator('#result')).toBeHidden();
 });
 test(`AC-C ${d.kind} failure retry delay cancellation and stale result`,async({page})=>{
  await page.goto(`${service.base}${route}${d.kind}.html`);await fill(page,d.kind);await page.locator('#submit').click();await expect(page.locator('#result')).toBeVisible();
  await page.route('**/utility/execute',r=>r.fulfill({status:503,contentType:'application/json',body:'{}'}));await page.locator('#submit').click();await expect(page.locator('#error')).toContainText('入力はそのまま');await expect(page.locator('#result')).toBeHidden();
  if(d.kind==='calculator')await expect(page.locator('#people')).toHaveValue('12');if(d.kind==='generator')await expect(page.locator('#topic')).toHaveValue('暮らしの道具');if(d.kind==='grader')await expect(page.locator('#purpose')).toBeChecked();
  await page.unroute('**/utility/execute');await page.locator('#submit').click();await expect(page.locator('#result')).toBeVisible();
  let release:()=>void=()=>{};const held=new Promise<void>(r=>release=r);await page.route('**/utility/execute',async r=>{await held;await r.fulfill({status:200,contentType:'application/json',body:JSON.stringify({result:{schema:'wt-utility-result.v1',kind:d.kind,value:'古い結果',items:[],used:'old',method:'old',updated:'old',rounding:'old',exclusions:'old'}})}).catch(()=>{});});
  await page.locator('#submit').click();await expect(page.locator('#status')).toContainText('処理しています');await expect(page.locator('#result')).toBeHidden();await expect(page.locator('form')).toHaveAttribute('aria-busy','true');
  await page.locator('#cancel').click();release();await expect(page.locator('#status')).toContainText('中止');await expect(page.locator('#submit')).toBeEnabled();await expect(page.locator('#result')).toBeHidden();
  await page.unroute('**/utility/execute');await page.locator('#submit').click();await expect(page.locator('#result')).toBeVisible();await expect(page.locator('#value')).not.toHaveText('古い結果');
  const first=page.locator('form input').first();if(d.kind==='grader')await first.uncheck();else await first.fill('1');await expect(page.locator('#result')).toBeHidden();
 });
}
test('AC-C calculator empty minimum maximum and out of range',async({page,request})=>{
 await page.goto(`${service.base}${route}calculator.html`);await page.locator('#submit').click();await expect(page.locator('#people')).toHaveAttribute('aria-invalid','true');await expect(page.locator('#people')).toBeFocused();
 for(const [people,cost,result]of [['0','0','0 円'],['1000','100000','100,000,000 円']]){await page.locator('#people').fill(people);await page.locator('#cost').fill(cost);await page.locator('#submit').click();await expect(page.locator('#value')).toHaveText(result);}
 for(const value of ['-1','1001','1.5']){await page.locator('#people').fill(value);await page.locator('#submit').click();await expect(page.locator('#people')).toHaveAttribute('aria-invalid','true');await expect(page.locator('#result')).toBeHidden();await expect(page.locator('#people')).toHaveValue(value);}
 const response=await request.post(service.base+'/utility/execute',{data:{kind:'calculator',input:{people:'1001',cost:'1'}}});expect(response.status()).toBe(422);
});
test('AC-C grader zero and all criteria',async({page})=>{
 await page.goto(`${service.base}${route}grader.html`);await page.locator('#submit').click();await expect(page.locator('#value')).toHaveText('0 / 3 項目を確認');await expect(page.locator('#items li')).toHaveCount(3);
 for(const input of await page.locator('input').all())await input.check();await page.locator('#submit').click();await expect(page.locator('#value')).toHaveText('3 / 3 項目を確認');
});
test('AC-C generator blank multilingual maximum long text and safe text output',async({page})=>{
 await page.goto(`${service.base}${route}generator.html`);
 for(const value of ['', '   ', '長'.repeat(81)]){await page.locator('#topic').fill(value);await page.locator('#submit').click();await expect(page.locator('#topic')).toHaveAttribute('aria-invalid','true');await expect(page.locator('#topic')).toHaveValue(value);await expect(page.locator('#result')).toBeHidden();}
 for(const value of ['あ','界'.repeat(80),'暮らし café مرحبا 🌱','<img src=x onerror=alert(1)>']){await page.locator('#topic').fill(value);await page.locator('#submit').click();await expect(page.locator('#result')).toBeVisible();await expect(page.locator('#items li').first()).toHaveText(`${value} はじめてガイド`);await expect(page.locator('#result img')).toHaveCount(0);}
});
test('AC-C processing timeout leaves inputs available for retry',async({page})=>{
 await page.goto(`${service.base}${route}generator.html`);await fill(page,'generator');await page.route('**/utility/execute',()=>{});await page.locator('#submit').click();await expect(page.locator('#error')).toContainText('入力はそのまま',{timeout:7000});await expect(page.locator('#topic')).toHaveValue('暮らしの道具');await expect(page.locator('#result')).toBeHidden();await expect(page.locator('#submit')).toBeEnabled();
});
