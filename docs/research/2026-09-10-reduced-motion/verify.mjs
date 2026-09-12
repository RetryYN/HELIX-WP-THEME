import { chromium } from '@playwright/test';
import { readFileSync, writeFileSync, readdirSync } from 'node:fs';
import { createHash } from 'node:crypto';
const prototype = 'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/assets/';
const cssFiles = [prototype + 'css/theme.css'];
const sourceFiles = [...cssFiles, ...['article','side','footer','home','reveal'].map(name=>prototype+'js/'+name+'.js'), 'docs/research/2026-09-10-reduced-motion/verify.mjs', 'package.json', '.github/workflows/test.yml'];
const auditRoots = ['docs/research/2026-09-05-design-prototype-03/theme/helix-wt'];
function ownedFiles(root) {
  return readdirSync(root, {withFileTypes:true}).flatMap(entry=>entry.isDirectory() ? ownedFiles(root+'/'+entry.name) : /\.(?:js|php|html)$/.test(entry.name) ? [root+'/'+entry.name] : []).sort();
}
const motionAuditSources = auditRoots.flatMap(ownedFiles).sort();
// 既存timerは全文digest固定で監査済みの内容更新・resize・計測に限定。
// timer追加やcallback変更を暗黙に許可しない。変更時は用途を再監査する。
const reviewedTimerSources = {
  "docs/research/2026-09-05-design-prototype-03/theme/helix-wt/assets/js/article.js": "95bc2214aa9c118959753688966016a3bbe3ea6fc62cbfae9c64dcbc4787ee79",
  "docs/research/2026-09-05-design-prototype-03/theme/helix-wt/assets/js/home.js": "e70778368d99e8fdbd69c7890f4d1068b6cb6f675876ea34c35f534c40efcbc5"
};
sourceFiles.push(...motionAuditSources.filter(file=>!sourceFiles.includes(file)));
const sourceDigests = Object.fromEntries(sourceFiles.map(file=>[file, createHash('sha256').update(readFileSync(file)).digest('hex')]));
const rows = [];
let browser;
function check(name, pass) { rows.push({name, pass: Boolean(pass)}); }
function gate(sample) {
  return sample.animation === 'none' && sample.transition === '0s' && sample.scroll === 'auto' && sample.visible && sample.manual && sample.autoplay === false;
}
try {
  for (const root of auditRoots) {
    const sources = motionAuditSources.filter(file=>file.startsWith(root+'/'));
    check('source:no-autoplay:'+root, sources.every(file=>!/(?:autoplay|\.play\s*\()/i.test(readFileSync(file,'utf8'))));
    check('source:no-automatic-scroll-timer:'+root, sources.every(file=>{
      const source = readFileSync(file,'utf8');
      return !/\b(?:setInterval|setTimeout)\s*\(/.test(source) || reviewedTimerSources[file]===sourceDigests[file];
    }));
  }
  browser = await chromium.launch({headless:true});
  const page = await browser.newPage({reducedMotion:'reduce'});
  // 正負fixtureは本体と同じ判定関数を直接通す。
  const good = {animation:'none',transition:'0s',scroll:'auto',visible:true,manual:true,autoplay:false};
  check('fixture:positive', gate(good));
  for (const [key,value] of Object.entries({animation:'spin',transition:'1s',scroll:'smooth',visible:false,manual:false,autoplay:true})) check('fixture:negative:'+key, !gate({...good,[key]:value}));
  for (const file of cssFiles) {
    await page.setContent('<button id="sample" class="wt-reveal">操作可能な内容</button>');
    await page.addStyleTag({content:'@keyframes probe{to{opacity:.5}} #sample{animation:probe 5s infinite;transition:transform 1s;scroll-behavior:smooth}'});
    await page.addStyleTag({content:readFileSync(file,'utf8')});
    const sample = await page.locator('#sample').evaluate(el=>{const s=getComputedStyle(el);return {animation:s.animationName,transition:s.transitionDuration,scroll:s.scrollBehavior,visible:s.display!=='none'&&s.visibility!=='hidden'&&s.opacity!=='0',manual:!el.disabled,autoplay:document.querySelector('[autoplay]')!==null};});
    check('css:'+file, gate(sample));
  }
  await page.setContent(`<body class="wt-motion-on wt-related-slider"><div class="wt-reveal">全内容</div><span class="wt-count" data-to="1234.5">1234.5</span><button class="wt-totop" data-wt-totop>先頭</button><div class="wt-related"><ul class="wp-block-post-template" style="width:100px;display:flex;overflow:auto"><li style="min-width:100px">一</li><li style="min-width:100px">二</li></ul></div><div class="wt-home-hero--slider"><div class="wt-home-slider__track" style="width:100px;display:flex;overflow:auto"><div style="min-width:100px">一</div><div style="min-width:100px">二</div></div><div class="wt-home-slider__nav"><button data-wt-slide="prev">前</button><div class="wt-home-slider__dots"></div><button data-wt-slide="next">次</button></div></div><div data-wt-carousel><div class="wt-hcar__track" style="display:flex;gap:16px"><div>一</div><div>二</div></div><div class="wt-hcar__nav"><button data-wt-slide="next">次</button></div></div></body>`);
  await page.evaluate(()=>{
    window.calls=[];window.frames=0;
    window.scrollTo=(options)=>window.calls.push(options);
    Element.prototype.scrollTo=function(options){window.calls.push(options);};
    Element.prototype.scrollBy=function(options){window.calls.push(options);};
    const raf=window.requestAnimationFrame;window.requestAnimationFrame=cb=>{window.frames++;return raf(cb);};
  });
  for (const file of ['article','side','footer','home','reveal']) await page.addScriptTag({content:readFileSync(prototype+'js/'+file+'.js','utf8')});
  check('counter:initial-final-value', await page.locator('.wt-count').textContent()==='1,234.5');
  check('counter:initial-no-frame', await page.evaluate(()=>window.frames)===0);
  check('reveal:immediate-content', await page.locator('.wt-reveal').evaluate(el=>el.classList.contains('is-in')));
  const buttons=['.wt-totop','.wt-carousel__nav > button:last-child','.wt-slider__dots button:last-child','.wt-home-slider__nav [data-wt-slide="next"]','.wt-home-slider__dots button:last-child','.wt-hcar__nav button'];
  for (const [index, preference] of ['reduce','no-preference','reduce'].entries()) {
    await page.emulateMedia({reducedMotion:preference});
    await page.evaluate(()=>{window.calls=[];});
    for (const selector of buttons) await page.locator(selector).click();
    const calls=await page.evaluate(()=>window.calls);
    check('manual:all-controls:'+index+':'+preference, calls.length>=buttons.length);
    check('manual:scroll-behavior:'+index+':'+preference, calls.every(call=>call.behavior===(preference==='reduce'?'auto':'smooth')));
  }
  // 実行中のcounterに設定変更を適用し、次のframeを待たず最終値を確定する。
  await page.emulateMedia({reducedMotion:'no-preference'});
  await page.setContent('<body class="wt-motion-on"><span class="wt-count" data-to="9876.5">0</span></body>');
  await page.evaluate(()=>{window.requestAnimationFrame=()=>1;});
  await page.addScriptTag({content:readFileSync(prototype+'js/article.js','utf8')});
  await page.emulateMedia({reducedMotion:'reduce'});
  await page.waitForFunction(()=>document.querySelector('.wt-count').textContent==='9,876.5');
  check('counter:live-preference-final-value', true);
} catch(error) { check('runtime:verification-completed', false); console.error(error); }
finally {
  if (browser) await browser.close();
  const failed = rows.filter(row=>!row.pass).length;
  const report = {schema:'wt-reduced-motion-verification.v1', requirements:['WT-NFR-A11Y-02'], completed:failed===0, motionAuditSources, reviewedTimerSources, sourceDigests, rows, failed};
  writeFileSync('docs/research/2026-09-10-reduced-motion/verify.json', JSON.stringify(report,null,2)+'\n');
  console.log(JSON.stringify(report,null,2));
  if (failed) process.exitCode=1;
}
