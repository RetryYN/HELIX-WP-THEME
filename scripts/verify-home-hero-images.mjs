import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';
import { createHash } from 'node:crypto';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { chromium } from '@playwright/test';
import { contentLab } from './lib/content-lab-env.mjs';

const root = fileURLToPath(new URL('../', import.meta.url));
const output = path.join(root, 'docs/research/2026-09-27-home-hero-image-integration/verification.json');
const theme = 'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/';
const themeFiles = ['functions.php', 'inc/home-hero-images.php', 'patterns/home-hero.php',
  'assets/css/theme.css', 'assets/css/home-completion.css', 'assets/js/home.js'];
const sources = ['scripts/verify-home-hero-images.mjs', 'scripts/lib/content-lab-env.mjs',
  ...themeFiles.map(file => theme + file)];
const digests = () => Object.fromEntries(sources.map(file =>
  [file, createHash('sha256').update(fs.readFileSync(path.join(root, file))).digest('hex')]));
const report = { schema: 'wt-home-hero-image-integration.v1', completed: false,
  scope: 'HOME image-loading regression; not WT-NFR-PERF-03 performance acceptance',
  sourceDigests: {}, runtime: [], browser: [] };
fs.mkdirSync(path.dirname(output), { recursive: true });
const save = () => fs.writeFileSync(output, JSON.stringify(report, null, 2) + '\n');
save();
const php = (source, env = []) => execFileSync('docker', ['exec', '-i', ...env,
  contentLab.wpContainer, 'php'], { input: source, encoding: 'utf8', maxBuffer: 4 * 1024 * 1024 });
const variants = ['text-only', 'slider', 'fullbleed', 'split', 'article-grid', 'video',
  'cards-carousel', 'product-shot', 'search-box'];
const fixture = String.raw`<?php
$_GET['wt']='home_hero:'.getenv('HERO_VARIANT');
require '/var/www/html/wp-load.php';
$selected=wt_opt('home_hero');
$variants=wt_axes()['home_hero'][1];
$rows=[];
function check_hero($label,$pass) {global $rows; $rows[]=array('id'=>$label,'pass'=>$pass);if(!$pass)throw new Exception($label);}
check_hero('filter-registered',has_filter('render_block_core/html','wt_defer_inactive_home_hero_images')!==false);
$unrelated='<section><img loading="eager" fetchpriority="high" src="unrelated.jpg"></section>';
check_hero('unrelated-unchanged',wt_defer_inactive_home_hero_images($unrelated)===$unrelated);
$html='<div class="wt-home-hero-slot__html">';
foreach($variants as $v){$html.='<section class="wt-home-hero wt-home-hero--'.$v.'"><section><img data-variant="'.$v.'" loading="eager" fetchpriority="high" src="'.$v.'.jpg"></section></section>';}
$html.='<img data-variant="outside" loading="eager" fetchpriority="high" src="outside.jpg"></div>';
$actual=render_block(array('blockName'=>'core/html','attrs'=>array(),'innerBlocks'=>array(),'innerHTML'=>$html,'innerContent'=>array($html)));
$p=new WP_HTML_Tag_Processor($actual);$n=0;
while($p->next_tag('IMG')){$v=$p->get_attribute('data-variant');$active=$v===$selected||$v==='outside';check_hero($v.':loading',$p->get_attribute('loading')===($active?'eager':'lazy'));check_hero($v.':priority',$p->get_attribute('fetchpriority')===($active?'high':null));$n++;}
check_hero('all-images-preserved',$n===10);
$other=$selected==='text-only'?'split':'text-only';
$nested='<div class="wt-home-hero-slot__html"><section class="wt-home-hero wt-home-hero--'.$other.'"><section class="wt-home-hero wt-home-hero--'.$selected.'"><img loading="eager" fetchpriority="high" src="nested.jpg"></section></section></div>';
$p=new WP_HTML_Tag_Processor(wt_defer_inactive_home_hero_images($nested));$p->next_tag('IMG');
check_hero('hidden-parent-wins',$p->get_attribute('loading')==='lazy'&&$p->get_attribute('fetchpriority')===null);
echo json_encode(array('variant'=>$selected,'wordpress'=>get_bloginfo('version'),'rows'=>$rows));
`;
let browser;
try {
  report.sourceDigests = digests();
  const info = JSON.parse(php(`<?php require '/var/www/html/wp-load.php';
    $files=json_decode('${JSON.stringify(themeFiles)}',true);$hashes=array();
    foreach($files as $file){$hashes[$file]=hash_file('sha256',get_theme_file_path($file));}
    echo json_encode(array('version'=>get_bloginfo('version'),'theme'=>get_stylesheet(),
      'url'=>home_url('/'),'hashes'=>$hashes));`));
  assert.equal(info.theme, 'helix-wt');
  assert.equal(info.url.replace(/\/$/, ''), contentLab.baseUrl);
  for (const file of themeFiles) assert.equal(info.hashes[file], report.sourceDigests[theme + file], file);
  report.wordpressVersion = info.version;
  report.runtimeOrigin = new URL(info.url).origin;
  for (const variant of variants) {
    const row = JSON.parse(php(fixture, ['-e', `HERO_VARIANT=${variant}`]));
    assert.equal(row.variant, variant);
    assert.equal(row.rows.length, 24);
    assert.ok(row.rows.every(check => check.pass));
    report.runtime.push(row);
  }
  browser = await chromium.launch({ headless: true });
  report.browserVersion = browser.version();
  const retiredOrigin = 'http://127.0.0.1:18253';
  for (const width of [390, 1440]) for (const variant of variants) {
    const page = await browser.newPage({ viewport: { width, height: 900 } });
    let retiredOriginRequests = 0;
    page.on('request', request => {
      try {
        if (new URL(request.url()).origin === retiredOrigin) retiredOriginRequests++;
      } catch {}
    });
    const response = await page.goto(`${contentLab.baseUrl}/?wt=home_hero:${variant}`, { waitUntil: 'networkidle' });
    assert.equal(response.status(), 200);
    assert.equal(new URL(page.url()).origin, contentLab.baseUrl);
    const heroes = page.locator('.wt-home-hero');
    const visible = await heroes.evaluateAll(es => es.filter(e => e.checkVisibility()).map(e => e.className));
    assert.equal(visible.length, 1);
    assert.ok(visible[0].split(/\s+/).includes(`wt-home-hero--${variant}`));
    const inactive = await heroes.evaluateAll((es, v) => es.filter(e => !e.classList.contains(`wt-home-hero--${v}`))
      .flatMap(e => [...e.querySelectorAll('img')].map(i => ({ loading: i.loading, priority: i.getAttribute('fetchpriority') }))), variant);
    assert.ok(inactive.length > 0);
    assert.ok(inactive.every(i => i.loading === 'lazy' && i.priority === null));
    let loadedImages = 0;
    for (const img of await page.locator(`.wt-home-hero--${variant} img`).elementHandles()) {
      if (!await img.evaluate(e => e.checkVisibility())) continue;
      await img.scrollIntoViewIfNeeded();
      await page.waitForFunction(e => e.complete && e.naturalWidth > 0, img, { timeout: 10000 });
      loadedImages++;
    }
    assert.equal(retiredOriginRequests, 0, 'browser requested retired lab origin');
    report.browser.push({ width, variant, selectedHeroOnly: true, inactiveImagesDeferred: inactive.length, loadedImages, retiredOriginRequests });
    await page.close();
  }
  assert.deepEqual(digests(), report.sourceDigests, 'sources unchanged');
  report.completed = true;
} finally {
  await browser?.close();
  save();
}
console.log(JSON.stringify({ completed: report.completed,
  runtimeChecks: report.runtime.reduce((n, row) => n + row.rows.length, 0), browserCases: report.browser.length }));
