import { chromium } from 'playwright';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import assert from 'node:assert/strict';
import { fileURLToPath } from 'node:url';
import { createHash } from 'node:crypto';
const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const state = process.env.WTCF_STATE_DIR || path.join(os.tmpdir(), 'helix-content-lab');
const cli = ['run', '--rm', '--network', 'helix-content-lab', '--env-file', path.join(state, 'wp.env'), '--volumes-from', 'helix-content-wp', '--user', '33:33', 'wordpress:cli-php8.3', 'wp'];
const wp = args => execFileSync('docker', [...cli, ...args], { encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] }).trim();
assert.equal(wp(['option', 'get', 'blogname']), 'HELIX Content Lab');
const base = 'http://127.0.0.1:8098';
const manifest = JSON.parse(fs.readFileSync(path.join(root, 'docs/research/2026-09-08-content-faces/plugin/site-pages.json')));
const out = path.join(root, 'docs/research/2026-09-08-content-faces/results/site-pages');fs.mkdirSync(out, { recursive: true });
const sources = ['scripts/verify-site-pages.mjs','docs/research/2026-09-08-content-faces/plugin/site-pages.json','docs/research/2026-09-08-content-faces/plugin/site-pages.php','docs/research/2026-09-08-content-faces/seed-site-pages.php','docs/research/2026-09-05-design-prototype-03/theme/helix-wt/inc/site-pages.php','docs/research/2026-09-05-design-prototype-03/theme/helix-wt/assets/css/site-pages.css','docs/research/2026-09-05-design-prototype-03/theme/helix-wt/templates/page-site-guide.html','docs/research/2026-09-08-content-faces/provider/index.html','docs/research/2026-09-08-content-faces/provider/complete.html'];
sources.push(...['functions.php','inc/footer-navigation.php','parts/footer.html','patterns/footer-sitemap.php','patterns/footer-related.php'].map(p=>'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/'+p));
const sourceDigests = Object.fromEntries(sources.map(f => [f,createHash('sha256').update(fs.readFileSync(path.join(root,f))).digest('hex')]));
const rows = [], shots = [];let completed=false;const check=(name,pass)=>{rows.push({name,pass:Boolean(pass)});assert.ok(pass,name);};
const browser=await chromium.launch();
try {
  const declared=JSON.parse(wp(['eval', `$out=array();foreach(wtcf_site_manifest()['pages'] as $key=>$page){$pattern=WP_Block_Patterns_Registry::get_instance()->get_registered('helix-wt/site-'.$key);$post=get_post(get_option('wtcf_site_page_ids')[$key]);$out[$key]=array('registered'=>(bool)$pattern,'matches'=>$pattern && $pattern['content']===$post->post_content,'type'=>$post->post_type,'id'=>$post->ID);}echo wp_json_encode($out);`]));
  for(const key of Object.keys(manifest.pages))check('pattern-and-independent-page:'+key,declared[key]?.registered&&declared[key].matches&&declared[key].type==='page');
  for(const dev of ['pc','sp'])for(const js of [true,false]){
    const context=await browser.newContext({viewport:dev==='pc'?{width:1440,height:1000}:{width:375,height:812},javaScriptEnabled:js});const page=await context.newPage();const suffix=`${dev}:js-${js}`;
    for(const [key,definition] of Object.entries(manifest.pages)){
      const response=await page.goto(`${base}/${definition.slug}/`);
      check(`page:${key}:${suffix}`,response.status()===200&&await page.locator('.wtsite').getAttribute('data-site-page')===key);
      check(`heading:${key}:${suffix}`,await page.locator('h1').count()===1&&await page.locator('h1').innerText()===definition.title);
      check(`reflow:${key}:${suffix}`,await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));
      check(`shared-business:${key}:${suffix}`,(await page.locator('.wtsite-footer').innerText()).includes(manifest.business.name));
      check(`no-owned-form:${key}:${suffix}`,await page.locator('form').count()===0);
      const content=await page.locator('.wtsite article').innerText();
      check(`purpose-copy:${key}:${suffix}`,definition.sections.every(section=>content.includes(section[0])&&content.includes(section[1])));
      if(definition.plans)check(`pricing:comparable-scope:${suffix}`,await page.locator('.wtsite-plans>section').count()===3&&definition.plans.every(plan=>[plan.name,plan.price,plan.unit,plan.scope,plan.limit].every(value=>content.includes(value))));
      if(definition.plans && dev==='pc') {
        const headings=await page.locator('.wtsite-plans>section').evaluateAll(cards=>cards.map(card=>[...card.querySelectorAll('h3')].map(h=>h.getBoundingClientRect().top)));
        check(`pricing:aligned-comparison:${suffix}`,headings.every(pair=>pair.every((top,i)=>Math.abs(top-headings[0][i])<1)));
      }
      const target=page.locator('[aria-label="このページの目次"] a').last();await target.click();
      check(`toc:${key}:${suffix}`,new URL(page.url()).hash===`#site-section-${definition.sections.length-1}`&&await page.locator(new URL(page.url()).hash).isVisible());
      const links=await page.locator('.wtsite-related a').evaluateAll(nodes=>nodes.map(a=>a.href));
      check(`related:${key}:${suffix}`,links.length===definition.links.length&&links.every((link,i)=>new URL(link).pathname===`/${manifest.pages[definition.links[i]].slug}/`));
      if(js){await page.goto(`${base}/${definition.slug}/`);const file=`${key}-${dev}.jpg`;await page.screenshot({path:path.join(out,file),fullPage:true,type:'jpeg',quality:86});shots.push({file,dev,key,label:definition.label,route:`/${definition.slug}/`});}
    }
    await page.goto(base+'/site-contact/');let posts=0;page.on('request',r=>{if(r.method()==='POST')posts++;});
    await page.locator('.wtsite-handoff a').click();
    check(`handoff:other-origin:${suffix}`,new URL(page.url()).origin==='http://127.0.0.1:8099'&&await page.locator('form').count()===1);
    await page.getByLabel('情報の整理').check();await page.getByRole('button',{name:'選択して次へ'}).click();
    check(`handoff:complete:${suffix}`,new URL(page.url()).pathname.endsWith('/provider/complete.html')&&new URL(page.url()).searchParams.get('topic')==='structure'&&posts===0);
    await page.getByRole('link',{name:'問い合わせ案内へ戻る'}).click();check(`handoff:return:${suffix}`,new URL(page.url()).pathname==='/site-contact/');
    await context.close();
  }
  const protectedId=declared.company.id;
  assert.ok(wp(['post','get',String(protectedId),'--field=post_password'])==='','Expected unprotected dedicated page');
  try {
    wp(['eval',`wp_update_post(array('ID'=>${protectedId},'post_password'=>wp_generate_password(28,true)));`]);
    const page=await browser.newPage();await page.goto(base+'/site-company/');
    check('protected-page:body-hidden',await page.locator('input[name="post_password"]').count()===1&&await page.locator('.wtsite').count()===0);
    const response=await page.request.get(base+'/wp-json/wp/v2/pages/'+protectedId);const value=await response.json();
    check('protected-page:rest-hidden',response.ok()&&value.content?.protected===true&&!value.content?.rendered.includes('よい説明を'));
    await page.close();
  } finally { wp(['post','update',String(protectedId),'--post_password=']); }
  const before=wp(['eval',"echo wp_json_encode(get_option('wtcf_site_settings',null));"]);
  try{
    wp(['eval',`update_option('wtcf_site_settings',array('business'=>array('name'=>'設定変更の検証組織 <img src=x onerror=alert(1)>'),'destinations'=>array(array('name'=>'変更後の接続例','purpose'=>'設定追従','data'=>'分類','condition'=>'自動送信なし'))));`]);
    const page=await browser.newPage();
    for(const [key,definition] of Object.entries(manifest.pages)){await page.goto(`${base}/${definition.slug}/`);check('central-update:'+key,(await page.locator('.wtsite-footer').innerText()).includes('設定変更の検証組織')&&!(await page.locator('.wtsite-footer').innerText()).includes(manifest.business.name)&&await page.locator('.wtsite img').count()===0);}
    await page.goto(base+'/site-transmissions/');check('destinations:config-update',(await page.locator('.wtsite-destinations').innerText()).includes('変更後の接続例')&&!(await page.locator('.wtsite-destinations').innerText()).includes(manifest.destinations[0].name));
    wp(['eval',"update_option('wtcf_site_settings',array('destinations'=>array()));"]);await page.reload();check('destinations:empty',(await page.locator('.wtsite-destinations').innerText()).includes('登録された外部送信先はありません'));
    await page.close();
  }finally{const encoded=Buffer.from(before).toString('base64');wp(['eval',`$value=json_decode(base64_decode('${encoded}'),true);if($value===null){delete_option('wtcf_site_settings');}else{update_option('wtcf_site_settings',$value);}`]);}
  check('central-settings-restored',wp(['eval',"echo wp_json_encode(get_option('wtcf_site_settings',null));"])===before);
  completed=true;
}finally{await browser.close();fs.writeFileSync(path.join(out,'verify.json'),JSON.stringify({schema:'wt-site-pages-evidence.v1',completed,sourceDigests,rows,shots},null,2)+'\n');}
console.log(`Site pages: ${rows.length} checks passed, ${shots.length} screenshots`);
