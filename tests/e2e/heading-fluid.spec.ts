import {test,expect} from '@playwright/test';
import {execFileSync} from 'node:child_process';
import {randomUUID} from 'node:crypto';

// 専用labの一時ページのみを作成し、共有設定には触れない。
const state='/tmp/helix-content-lab',slug='heading-fluid-e2e',owner=randomUUID();
const wp=(args:string[])=>execFileSync('docker',['run','--rm','--network','helix-content-lab','--env-file',state+'/wp.env','--volumes-from','helix-content-wp','--user','33:33','wordpress:cli-php8.3','wp',...args],{encoding:'utf8'}).trim();
const styles={h2:['plain','2tone','icon','bar','underline','band','numbox','barbg','doubleline','label'],h3:['bar-thin','dotted','num','marker','underline-thin']};
const short='暮らしに合う道具を選ぶ',long='毎日の暮らしで無理なく使い続けられる道具を選ぶために、購入前に確かめたいこと';
let id='';
test.describe.configure({mode:'serial'});
test.beforeAll(()=>{
 expect(wp(['option','get','blogname'])).toBe('HELIX Content Lab');
 expect(wp(['option','get','stylesheet'])).toBe('helix-wt');
 expect(wp(['post','list','--post_type=any','--post_status=any','--name='+slug,'--format=ids'])).toBe('');
 const content='<div id="fluid-check"><div id="hierarchy"><h2>章</h2><h3>節</h3><h4>項</h4></div>'+Object.entries(styles).flatMap(([level,list])=>list.map(style=>`<section data-heading="${level}-${style}"><${level} class="wp-block-heading is-style-wt-${style}">${short}</${level}><p>使う場所と頻度を確かめ、必要な機能から比べます。</p><${level} class="wp-block-heading is-style-wt-${style}">${long}</${level}><p>長い見出しも省略しません。</p></section>`)).join('')+'</div>';
 id=wp(['post','create','--post_type=page','--post_status=publish','--post_name='+slug,'--post_title=見出し拡大検査','--post_content='+content,'--meta_input='+JSON.stringify({wt_heading_owner:owner,_wp_page_template:'page-zone-catalog'}),'--porcelain']);
});
test.afterAll(()=>{
 if(id){expect(wp(['post','meta','get',id,'wt_heading_owner'])).toBe(owner);wp(['post','delete',id,'--force']);}
});
for(const width of[1440,390,320])for(const scale of[1,2])for(const js of[true,false])test(`${width}px / ${scale*100}%文字 / JS ${js}`,async({browser})=>{
 const context=await browser.newContext({viewport:{width,height:1000},javaScriptEnabled:js,reducedMotion:'reduce'});
 try{
  const page=await context.newPage();await page.goto('http://127.0.0.1:8098/?page_id='+id);
  const initial=await page.locator('#hierarchy').evaluate(e=>[...e.children].map(n=>parseFloat(getComputedStyle(n).fontSize)));
  if(scale===2)await page.locator('#fluid-check').evaluate(root=>{
   const nodes=[...root.querySelectorAll<HTMLElement>('h2,h3,h4,p')],sizes=nodes.map(n=>parseFloat(getComputedStyle(n).fontSize));
   nodes.forEach((n,i)=>n.style.fontSize=sizes[i]*2+'px');
  });
  const sizes=await page.locator('#hierarchy').evaluate(e=>[...e.children].map(n=>parseFloat(getComputedStyle(n).fontSize)));
  for(let i=0;i<sizes.length;i++){expect(sizes[i]).toBeCloseTo(initial[i]*scale,1);if(i)expect(sizes[i-1]).toBeGreaterThanOrEqual(sizes[i]);}
  expect(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth)).toBe(true);
  await expect(page.locator('[data-heading]')).toHaveCount(15);
  for(const section of await page.locator('[data-heading]').all()){
   await expect(section.locator('h2,h3').first()).toHaveText(short);await expect(section.locator('h2,h3').last()).toHaveText(long);
   const geometry=await section.locator('h2,h3,p').evaluateAll(ns=>ns.map(n=>{const b=n.getBoundingClientRect(),r=document.createRange();r.selectNodeContents(n);const t=r.getBoundingClientRect();return {top:b.top,bottom:b.bottom,textTop:t.top,textBottom:t.bottom,overflow:n.scrollWidth>n.clientWidth+1};}));
   geometry.forEach((g,i)=>{expect(g.overflow).toBe(false);expect(g.textTop).toBeGreaterThanOrEqual(g.top-1);expect(g.textBottom).toBeLessThanOrEqual(g.bottom+1);if(i)expect(g.top).toBeGreaterThanOrEqual(geometry[i-1].bottom-1);});
  }
 }finally{await context.close();}
});
