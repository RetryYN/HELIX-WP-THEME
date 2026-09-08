import fs from 'node:fs';
import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import { chromium } from 'playwright';
const root=new URL('../',import.meta.url);
const php=code=>execFileSync('docker',['exec','helix-content-wp','php','-r','require "/var/www/html/wp-load.php"; '+code],{encoding:'utf8'}).trim();
if(php('echo get_option("blogname");')!=='HELIX Content Lab')throw Error('Dedicated lab required');
php('wp_get_theme()->delete_pattern_cache();');
if(php('echo count(get_posts(array("post_type"=>"wp_template_part","name"=>"footer","post_status"=>"any")));')!=='0')throw Error('Existing footer override');
const slug='footer-render-navigation-fixture';
if(php(`echo count(get_posts(array('post_type'=>'wp_navigation','name'=>'${slug}','post_status'=>'any')));`)!=='0')throw Error('Existing navigation fixture');
const browser=await chromium.launch();let nav,part,completed=false;const rows=[];
const check=(name,pass)=>rows.push({name,pass:!!pass});
try{
 nav=Number(php(`echo wp_insert_post(array('post_type'=>'wp_navigation','post_status'=>'publish','post_name'=>'${slug}','post_title'=>'Footer Render Fixture'));`));
 if(!nav)throw Error('Navigation create failed');
 part=Number(php(`$blocks=resolve_pattern_blocks(parse_blocks(file_get_contents(get_theme_file_path('parts/footer.html'))));function bind_footer(&$blocks){foreach($blocks as &$b){if('core/navigation'===$b['blockName']&&str_contains($b['attrs']['className']??'','wt-footer-data-navigation')){$b['attrs']['ref']=${nav};}if(!empty($b['innerBlocks']))bind_footer($b['innerBlocks']);}}bind_footer($blocks);$id=wp_insert_post(wp_slash(array('post_type'=>'wp_template_part','post_status'=>'publish','post_name'=>'footer','post_title'=>'Footer Render Fixture','post_content'=>serialize_blocks($blocks))));wp_set_object_terms($id,'helix-wt','wp_theme');wp_set_object_terms($id,'footer','wp_template_part_area');echo $id;`));
 if(!part)throw Error('Template part create failed');
 const links=[{label:'会社案内',url:'/site-company/'},{label:'読む・学ぶ',url:'/learn/'}];
 for(const state of ['empty','filled','emptied']){
  const content=state==='filled'?links.map(x=>'<!-- wp:navigation-link '+JSON.stringify({...x,kind:'custom'})+' /-->').join('\n'):'';
  const payload=Buffer.from(JSON.stringify({ID:nav,post_content:content})).toString('base64');
  php(`wp_update_post(wp_slash(json_decode(base64_decode('${payload}'),true)));`);
  for(const width of [1440,375])for(const js of [true,false]){
   const context=await browser.newContext({viewport:{width,height:900},javaScriptEnabled:js});const page=await context.newPage();
   await page.goto('http://127.0.0.1:8098/?wt=footer_extra:sites,footer_layout:sitemap');
   const label=`${state}:${width}:js-${js}`;
   const related=page.locator('.wt-footer-extra-slot--sites');
   const groups=page.locator('.wt-footer__sitemap details');
   check('related-group:'+label,await related.count()===(state==='filled'?1:0));
   check('sitemap-groups:'+label,await groups.count()===(state==='filled'?4:0));
   check('viewport:'+label,await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));
   if(state==='empty'&&js)await page.locator('.wt-footer').screenshot({path:new URL(`docs/research/2026-09-09-footer-data/empty-${width}.png`,root).pathname});
   if(state==='filled'){
    check('stored-links:'+label,JSON.stringify(await related.locator('a').evaluateAll(es=>es.map(e=>({label:e.textContent.trim(),url:e.getAttribute('href')}))))===JSON.stringify(links));
    if(width===1440){const tops=await groups.locator('summary').evaluateAll(es=>es.map(e=>e.getBoundingClientRect().top));check('aligned-headings:'+label,Math.max(...tops)-Math.min(...tops)<=1);}
    if(js)await page.locator('.wt-footer').screenshot({path:new URL(`docs/research/2026-09-09-footer-data/filled-${width}.png`,root).pathname});
    const summary=groups.first().locator('summary');await summary.focus();const before=await groups.first().getAttribute('open');await page.keyboard.press('Enter');check('keyboard-toggle:'+label,(await groups.first().getAttribute('open'))!==before);
    await page.goto('http://127.0.0.1:8098/?wt=footer_extra:none,footer_layout:sitemap');check('off-omits-related:'+label,await page.locator('.wt-footer-extra-slot--sites').count()===0);
   }
   await context.close();
  }
 }
 completed=true;
}finally{
 await browser.close();if(part)php(`wp_delete_post(${part},true);`);if(nav)php(`wp_delete_post(${nav},true);`);
 check('owned-footer-removed',php('echo count(get_posts(array("post_type"=>"wp_template_part","name"=>"footer","post_status"=>"any")));')==='0');
 check('owned-navigation-removed',php(`echo count(get_posts(array('post_type'=>'wp_navigation','name'=>'${slug}','post_status'=>'any')));`)==='0');
 const sources=['scripts/verify-footer-rendering.mjs',...['functions.php','parts/footer.html','patterns/footer-sitemap.php','patterns/footer-related.php','inc/footer-navigation.php','assets/css/theme.css','assets/js/footer.js'].map(f=>'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/'+f)];
 fs.writeFileSync(new URL('docs/research/2026-09-09-footer-data/rendering.json',root),JSON.stringify({completed,sourceDigests:Object.fromEntries(sources.map(p=>[p,createHash('sha256').update(fs.readFileSync(new URL(p,root))).digest('hex')])),rows,limitation:'標準保存モデルwp_navigation/wp_template_partを使う。Site Editorブラウザー操作、全継承面、リンク先本文は未検証。'},null,2)+'\n');
}
console.log(JSON.stringify({completed,checks:rows.length,failed:rows.filter(r=>!r.pass).length}));
if(rows.some(r=>!r.pass))process.exitCode=1;
