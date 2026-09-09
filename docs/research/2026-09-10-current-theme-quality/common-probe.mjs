import { chromium } from '@playwright/test';
import { readFileSync,writeFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
const dir='docs/research/2026-09-10-current-theme-quality/';
const mode=process.argv[2]??'before';
const rows=[];const browser=await chromium.launch();
const cases=[['article','width:wide,depth:float,motion:on'],['lp','lp_sections:full,motion:on'],['event','event_status:open,motion:on'],['form','form_captcha:question,form_side:tel'],['form','form_captcha:external-slot,form_side:email']];
const variants={default:''};
for(const name of ['rules','mincho']){
 const php=`require '/var/www/html/wp-load.php';$root=get_stylesheet_directory();$t=new WP_Theme_JSON(json_decode(file_get_contents($root.'/theme.json'),true));$t->merge(new WP_Theme_JSON(json_decode(file_get_contents($root.'/styles/${name}.json'),true)));echo $t->get_stylesheet();`;
 variants[name]=execFileSync('docker',['exec','helix-content-wp','php','-r',php],{encoding:'utf8',maxBuffer:10e6});
}
try{
 for(const [face,axes]of cases)for(const width of [390,1440])for(const [variation,css]of Object.entries(variants)){
  const page=await browser.newPage({viewport:{width,height:900},reducedMotion:'reduce'});
  const response=await page.goto('http://127.0.0.1:8098/quality-audit-'+face+'/?wt='+axes,{waitUntil:'networkidle'});
  if(css)await page.addStyleTag({content:css});
  const data=await page.evaluate(()=>{
   const pick=selector=>[...document.querySelectorAll(selector)].map(e=>{const s=getComputedStyle(e);return {selector,background:s.backgroundColor,color:s.color,font:s.fontSize,width:e.getBoundingClientRect().width,animation:s.animationName,transition:s.transitionDuration}});
   return {overflow:document.documentElement.scrollWidth>innerWidth,h1:document.querySelectorAll('main h1').length,text:document.querySelector('main')?.textContent.trim().length??0,palette:Object.fromEntries(['surface','ok','warn-soft','ok-soft','soft'].map(x=>[x,getComputedStyle(document.body).getPropertyValue('--wp--preset--color--'+x).trim()])),parts:pick('.wt-form__captcha-q,.wt-form__captcha-slot,.wt-form__side,.wt-header__row,.wt-reveal,.wt-rcard')};
  });
  rows.push({face,axes,width,variation,http:response.status(),...data});
  if(variation==='default' || (face==='form'&&axes.includes('question'))){
   const subject=face==='form'?page.locator('.wt-form').first():null;
   const path=dir+mode+'-'+face+'-'+(axes.includes('external-slot')?'external-':'')+variation+'-'+width+'.png';
   if(subject&&await subject.count())await subject.screenshot({path});else await page.screenshot({path});
  }
  await page.close();
 }
 // JavaScript無効で各代表面が描画されることを確認する。
 for(const [face,axes]of cases){const page=await browser.newPage({viewport:{width:390,height:900},javaScriptEnabled:false});const response=await page.goto('http://127.0.0.1:8098/quality-audit-'+face+'/?wt='+axes,{waitUntil:'load'});rows.push({face,noJs:true,http:response.status(),...await page.evaluate(()=>({overflow:document.documentElement.scrollWidth>innerWidth,text:document.querySelector('main')?.textContent.trim().length??0}))});await page.close();}
 writeFileSync(dir+mode+'-common.json',JSON.stringify(rows,null,2)+'\n');
 console.log(mode,'common',rows.length,'bad',rows.filter(x=>x.http!==200||x.overflow||x.text<20).map(x=>[x.face,x.axes,x.width,x.variation,x.http,x.overflow,x.text]));
}finally{await browser.close();}
