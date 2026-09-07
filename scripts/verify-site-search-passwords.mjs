import { execFileSync } from 'node:child_process';
import { chromium } from 'playwright';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { createHash } from 'node:crypto';
const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const state = process.env.WTCF_STATE_DIR || path.join(os.tmpdir(), 'helix-content-lab');
const wp = args => execFileSync('docker', ['run', '--rm', '--network', 'helix-content-lab', '--env-file', path.join(state, 'wp.env'), '--volumes-from', 'helix-content-wp', '--user', '33:33', 'wordpress:cli-php8.3', 'wp', ...args], { encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] }).trim();
if (wp(['option', 'get', 'blogname']) !== 'HELIX Content Lab') throw Error('Dedicated lab required');
const marker = 'SearchUnlockFixture';
if (wp(['post', 'list', '--post_type=any', '--post_status=any', '--s=' + marker, '--format=ids'])) throw Error('Reserved fixtures exist');
const originalSize = wp(['option', 'get', 'posts_per_page']);
const credentials = JSON.parse(fs.readFileSync(path.join(state, 'credentials.json'), 'utf8'));
const sources = ['scripts/verify-site-search-passwords.mjs', 'docs/research/2026-09-08-content-faces/plugin/search.php', 'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/templates/search.html'];
const digests = () => Object.fromEntries(sources.map(f => [f, createHash('sha256').update(fs.readFileSync(path.join(root, f))).digest('hex')]));
const sourceDigests = digests(), rows = [];
const check = (name, pass) => rows.push({ name, pass: !!pass });
const base = 'http://127.0.0.1:8098', browser = await chromium.launch();
let ids = [], completed = false;
async function unlock(context, password) {
  await context.request.post(base + '/wp-login.php?action=postpass', { form: { post_password: password }, headers: { referer: base + '/?p=' + ids[1] } });
  if (!(await context.cookies()).some(c => c.name.startsWith('wp-postpass_'))) throw Error('Post password cookie missing');
}
async function inspect(page, phase, expected, label) {
  for (const paged of [1, 999]) {
    const response = await page.goto(`${base}/?s=${marker}&paged=${paged}`);
    const total = Number((await page.locator('main .wp-block-query-total').innerText()).match(/\d+/)?.[0]);
    check(`count:${label}:${phase}:${paged}`, total === expected);
    check(`result:${label}:${phase}:${paged}`, await page.locator('main .wp-block-post-title').count() === 1);
    check(`other-password-excluded:${label}:${phase}:${paged}`, !(await page.locator('main .wp-block-post-template').innerText()).includes('DifferentPasswordOnly'));
    check(`no-shared-cache:${label}:${phase}:${paged}`, (response.headers()['cache-control'] || '').includes('no-store'));
  }
  const response = await page.request.get(base + '/?p=' + ids[1]);
  check(`direct-password:${label}:${phase}`, (await response.text()).includes('UnlockedBodyOnly') === (expected === 3));
}
try {
  ids = JSON.parse(wp(['eval', `$ids=array();try{foreach(array('public','first','second','other','draft','private')as $kind){$p=array('post_type'=>'post','post_status'=>in_array($kind,array('draft','private'),true)?$kind:'publish','post_title'=>'${marker} '.$kind,'post_content'=>$kind==='other'?'DifferentPasswordOnly':($kind==='public'?'PublicBodyOnly':'UnlockedBodyOnly'));if($kind!=='public')$p['post_password']=$kind==='other'?'other-fixture':'unlock-fixture';$id=wp_insert_post($p,true);if(is_wp_error($id))throw new Exception('Create failed');$ids[]=$id;}echo wp_json_encode($ids);}catch(Throwable $e){foreach($ids as $id)wp_delete_post($id,true);throw $e;}`]));
  wp(['option', 'update', 'posts_per_page', '1']);
  check('secondary-query-unchanged', wp(['eval', `$u=get_user_by('login','lab_none');wp_set_current_user($u->ID);$q=new WP_Query(array('s'=>'${marker}','posts_per_page'=>20));echo $q->found_posts;`]) === '4');
  // Compare against the same admin query with only this adapter's hooks removed in
  // this isolated CLI process. Admin status defaults differ from public search.
  const adminCounts = JSON.parse(wp(['eval', `require_once ABSPATH.'wp-admin/includes/screen.php';set_current_screen('edit-post');$u=get_user_by('login','lab_none');wp_set_current_user($u->ID);$args=array('s'=>'${marker}','posts_per_page'=>20);$GLOBALS['wp_the_query']=new WP_Query();$GLOBALS['wp_the_query']->query($args);$with=$GLOBALS['wp_the_query']->found_posts;foreach(array('pre_get_posts','posts_search')as $tag){foreach($GLOBALS['wp_filter'][$tag]->callbacks as $priority=>$callbacks){foreach($callbacks as $callback){$fn=$callback['function'];if($fn instanceof Closure&&str_ends_with((new ReflectionFunction($fn))->getFileName(),'/helix-content-faces/search.php'))remove_filter($tag,$fn,$priority);}}}$GLOBALS['wp_the_query']=new WP_Query();$GLOBALS['wp_the_query']->query($args);echo wp_json_encode(array('with'=>$with,'native'=>$GLOBALS['wp_the_query']->found_posts));`]));
  check('admin-query-unchanged', adminCounts.with === adminCounts.native && adminCounts.native > 3);
  for (const role of ['anonymous', 'none']) for (const [device, width] of [['pc', 1440], ['sp', 375]]) for (const js of [true, false]) {
    const context = await browser.newContext({ viewport: { width, height: 900 }, javaScriptEnabled: js });
    if (role === 'none') {
      await context.request.get(base + '/wp-login.php');
      await context.request.post(base + '/wp-login.php', { form: { log: 'lab_none', pwd: credentials.none, redirect_to: base, testcookie: '1' } });
      if (!(await context.cookies()).some(c => c.name.startsWith('wordpress_logged_in_'))) throw Error('Reader login failed');
    }
    const page = await context.newPage(), label = `${role}:${device}:js-${js}`;
    await inspect(page, 'locked', 1, label);
    await unlock(context, 'incorrect-fixture'); await inspect(page, 'wrong', 1, label);
    await unlock(context, 'unlock-fixture'); await inspect(page, 'unlocked', 3, label);
    await page.goto(`${base}/?s=${marker}`);
    await page.locator('main .wp-block-query-pagination-next').click();
    check(`next-page:${label}`, new URL(page.url()).searchParams.get('s') === marker && (await page.locator('main .wp-block-post-template').innerText()).includes('UnlockedBodyOnly'));
    await context.clearCookies({ name: /^wp-postpass_/ });
    await inspect(page, 'cleared', 1, label);
    await context.close();
  }
  const context = await browser.newContext(), page = await context.newPage();
  await unlock(context, 'unlock-fixture'); await inspect(page, 'before-change', 3, 'rotation');
  wp(['eval', `foreach(array(${ids.slice(1, 3).join(',')}) as $id)wp_update_post(array('ID'=>$id,'post_password'=>wp_generate_password(28,true)));`]);
  await inspect(page, 'after-change', 1, 'rotation');
  await context.close();
  completed = true;
} finally {
  await browser.close();
  wp(['option', 'update', 'posts_per_page', originalSize]);
  if (ids.length) wp(['post', 'delete', ...ids.map(String), '--force']);
  check('page-size-restored', wp(['option', 'get', 'posts_per_page']) === originalSize);
  check('owned-fixtures-removed', wp(['post', 'list', '--post_type=any', '--post_status=any', '--s=' + marker, '--format=ids']) === '');
  check('sources-unchanged', JSON.stringify(sourceDigests) === JSON.stringify(digests()));
  fs.writeFileSync(path.join(root, 'docs/research/2026-09-08-site-search/results/passwords.json'), JSON.stringify({ completed, sourceDigests, rows }, null, 2) + '\n');
  console.log(JSON.stringify({ completed, checks: rows.length, failed: rows.filter(r => !r.pass).length }));
}
if (rows.some(r => !r.pass)) process.exitCode = 1;
