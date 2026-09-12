import { chromium } from 'playwright';
import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import { readFileSync, writeFileSync } from 'node:fs';
import assert from 'node:assert/strict';

const base = 'http://127.0.0.1:8098';
const dir = new URL('./', import.meta.url);
const repo = new URL('../../../', import.meta.url);
const wp = code => JSON.parse(execFileSync('docker', ['exec', 'helix-content-wp', 'php', '-r', `require '/var/www/html/wp-load.php';${code}`], { encoding: 'utf8' }));
const suffix = 'learning-nav-ownership-audit';
const fixtures = wp(`
$slugs=['${suffix}-course','${suffix}-first','${suffix}-middle','${suffix}-last','${suffix}-draft','${suffix}-protected'];
foreach($slugs as $slug){if(get_page_by_path($slug,OBJECT,'wt_learning'))throw new Exception('fixture collision');}
$make=function($slug,$title,$status='publish',$parent=0,$order=0,$password=''){return wp_insert_post(['post_type'=>'wt_learning','post_name'=>$slug,'post_title'=>$title,'post_excerpt'=>'公開要約','post_content'=>'<!-- wp:heading {"level":2} --><h2>公開セクション</h2><!-- /wp:heading --><!-- wp:paragraph --><p>PUBLIC-CONTENT</p><!-- /wp:paragraph -->','post_status'=>$status,'post_parent'=>$parent,'menu_order'=>$order,'post_password'=>$password,'meta_input'=>['_wtcf_document'=>['kind'=>$parent?'lesson':'course']]],true);};
$course=$make($slugs[0],'検証講座');$first=$make($slugs[1],'01 最初','publish',$course,1);$middle=$make($slugs[2],'02 中央','publish',$course,2);$last=$make($slugs[3],'03 最後','publish',$course,3);$draft=$make($slugs[4],'DRAFT-SECRET','draft',$course,4);$protected=$make($slugs[5],'保護レッスン','publish',$course,5,'audit-pass');
foreach(compact('course','first','middle','last','draft','protected') as $name=>$id){if(is_wp_error($id))throw new Exception($name);$rows[$name]=['id'=>$id,'slug'=>get_post($id)->post_name,'url'=>get_permalink($id)];}echo wp_json_encode($rows);`);

const owners = ['site', 'own', 'off'];
const rows = [];
let directBoundary = null;
const browser = await chromium.launch({ args: ['--no-sandbox'] });
try {
  for (const hierarchy of owners) for (const sequence of owners) for (const width of [390, 1440]) for (const noJs of [false, true]) {
    const page = await browser.newPage({ viewport: { width, height: 900 }, javaScriptEnabled: !noJs });
    const axes = [
      `content_learning_hierarchy:${hierarchy}`,
      `content_learning_sequence:${sequence}`,
      'learning_hierarchy_style:panel',
      'learning_sequence_style:cards',
      'own_content_learning_hierarchy_style:trail',
      'own_content_learning_sequence_style:split',
    ].join(',');
    const response = await page.goto(`${fixtures.middle.url}?wt=${axes}`, { waitUntil: noJs ? 'load' : 'networkidle' });
    const state = await page.evaluate(() => ({
      overflow: document.documentElement.scrollWidth > innerWidth,
      hierarchy: document.querySelector('[aria-label="階層"]')?.dataset.wtOwner ?? null,
      hierarchyStyle: document.querySelector('[aria-label="階層"]')?.className ?? null,
      current: document.querySelectorAll('[aria-label="階層"] [aria-current="page"]').length,
      sequence: document.querySelector('[aria-label="前後のレッスン"]')?.dataset.wtOwner ?? null,
      sequenceStyle: document.querySelector('[aria-label="前後のレッスン"]')?.className ?? null,
      previous: document.querySelectorAll('.wtlearn-previous').length,
      next: document.querySelectorAll('.wtlearn-next').length,
      lessonLinks: [...document.querySelectorAll('[aria-label="講座のレッスン"] a')].map(a => a.textContent.trim()),
      publicContent: document.querySelector('main')?.textContent.includes('PUBLIC-CONTENT') ?? false,
    }));
    assert.equal(response.status(), 200);
    assert.equal(state.overflow, false);
    assert.equal(state.publicContent, true);
    assert.equal(state.hierarchy, hierarchy === 'off' ? null : hierarchy);
    assert.equal(state.sequence, sequence === 'off' ? null : sequence);
    assert.equal(state.current, hierarchy === 'off' ? 0 : 1);
    assert.equal(state.previous, sequence === 'off' ? 0 : 1);
    assert.equal(state.next, sequence === 'off' ? 0 : 1);
    assert(state.lessonLinks.every(label => !label.includes('DRAFT-SECRET')));
    if (hierarchy === 'site') assert(state.hierarchyStyle.includes('--panel'));
    if (hierarchy === 'own') assert(state.hierarchyStyle.includes('--trail'));
    if (sequence === 'site') assert(state.sequenceStyle.includes('--cards'));
    if (sequence === 'own') assert(state.sequenceStyle.includes('--split'));
    rows.push({ hierarchy, sequence, width, noJs, http: response.status(), ...state });
    if (hierarchy === sequence && !noJs) await page.screenshot({ path: new URL(`${hierarchy}-${width}.png`, dir).pathname, fullPage: true });
    await page.close();
  }
  for (const width of [390, 1440]) for (const noJs of [false, true]) {
    const page = await browser.newPage({ viewport: { width, height: 900 }, javaScriptEnabled: !noJs });
    const draft = await page.goto(fixtures.draft.url, { waitUntil: noJs ? 'load' : 'networkidle' });
    const draftState = { width, noJs, kind: 'draft', http: draft.status(), secret: await page.getByText('DRAFT-SECRET', { exact: false }).count(), publicContent: await page.getByText('PUBLIC-CONTENT', { exact: false }).count() };
    assert.equal(draftState.http, 404); assert.equal(draftState.secret, 0); assert.equal(draftState.publicContent, 0); rows.push(draftState);
    const protectedResponse = await page.goto(fixtures.protected.url, { waitUntil: noJs ? 'load' : 'networkidle' });
    const protectedState = { width, noJs, kind: 'protected', http: protectedResponse.status(), passwordForm: await page.locator('form.post-password-form').count(), publicContent: await page.getByText('PUBLIC-CONTENT', { exact: false }).count() };
    assert.equal(protectedState.http, 200); assert.equal(protectedState.passwordForm, 1); assert.equal(protectedState.publicContent, 0); rows.push(protectedState);
    await page.close();
  }
  directBoundary = wp(`echo wp_json_encode(['draft'=>wtcf_learning_display(${fixtures.draft.id})===null,'protected'=>wtcf_learning_display(${fixtures.protected.id})===null]);`);
} finally {
  await browser.close();
  for (const fixture of Object.values(fixtures).reverse()) Object.assign(fixture, wp(`$p=get_post(${fixture.id});if($p&&$p->post_name!=='${fixture.slug}')throw new Exception('identity mismatch');if($p)wp_delete_post(${fixture.id},true);echo wp_json_encode(['absent'=>get_post(${fixture.id})===null]);`));
}
const sources = [
  'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/functions.php',
  'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/inc/learning.php',
  'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/assets/css/content-faces.css',
  'docs/research/2026-09-08-content-faces/plugin/learning.php',
];
const sourceDigests = Object.fromEntries(sources.map(file => [file, createHash('sha256').update(readFileSync(new URL(file, repo))).digest('hex')]));
const completed = rows.length === 44 && rows.every(row => row.http === (row.kind === 'draft' ? 404 : 200)) && Object.values(fixtures).every(row => row.absent) && directBoundary.draft && directBoundary.protected;
const navigationRows = rows.filter(row => !row.kind);
const boundaryRows = rows.filter(row => row.kind);
const checks = [
  { name: 'navigation:all-owner-combinations', pass: navigationRows.length === 36 && owners.every(hierarchy => owners.every(sequence => navigationRows.filter(row => row.hierarchy === (hierarchy === 'off' ? null : hierarchy) && row.sequence === (sequence === 'off' ? null : sequence)).length === 4)) },
  { name: 'navigation:site-own-style-separated', pass: navigationRows.every(row => (row.hierarchy !== 'site' || row.hierarchyStyle.includes('--panel')) && (row.hierarchy !== 'own' || row.hierarchyStyle.includes('--trail')) && (row.sequence !== 'site' || row.sequenceStyle.includes('--cards')) && (row.sequence !== 'own' || row.sequenceStyle.includes('--split'))) },
  { name: 'navigation:off-removes-only-selected-nav', pass: navigationRows.every(row => (row.hierarchy === null) === (row.current === 0) && (row.sequence === null) === (row.previous === 0 && row.next === 0) && row.publicContent) },
  { name: 'navigation:current-and-neighbours', pass: navigationRows.every(row => (row.hierarchy === null || row.current === 1) && (row.sequence === null || (row.previous === 1 && row.next === 1))) },
  { name: 'navigation:pc-sp-js-nojs-no-overflow', pass: navigationRows.every(row => !row.overflow) && [390, 1440].every(width => [false, true].every(noJs => navigationRows.some(row => row.width === width && row.noJs === noJs))) },
  { name: 'boundary:draft-not-public', pass: boundaryRows.filter(row => row.kind === 'draft').length === 4 && boundaryRows.filter(row => row.kind === 'draft').every(row => row.http === 404 && row.secret === 0 && row.publicContent === 0) },
  { name: 'boundary:password-protected-not-public', pass: boundaryRows.filter(row => row.kind === 'protected').length === 4 && boundaryRows.filter(row => row.kind === 'protected').every(row => row.http === 200 && row.passwordForm === 1 && row.publicContent === 0) },
  { name: 'boundary:data-provider-denies-nonpublic', pass: directBoundary.draft === true && directBoundary.protected === true },
  { name: 'owned-fixtures-removed', pass: Object.values(fixtures).every(row => row.absent === true) },
];
writeFileSync(new URL('verify.json', dir), `${JSON.stringify({ schema: 'wt-learning-navigation-ownership-verification.v1', completed: completed && checks.every(check => check.pass), fixtures, directBoundary, sourceDigests, checks, rows }, null, 2)}\n`);
assert.equal(completed, true);
console.log(`learning navigation ownership: ${rows.length} cases passed`);
