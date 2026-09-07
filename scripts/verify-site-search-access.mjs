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
const credentials = JSON.parse(fs.readFileSync(path.join(state, 'credentials.json'), 'utf8'));
const marker = 'SearchAccessFixture', hidden = 'SearchAccessHiddenBody';
if (wp(['post', 'list', '--post_type=any', '--post_status=any', '--s=' + marker, '--format=ids'])) throw Error('Reserved fixtures exist');
const sources = ['scripts/verify-site-search-access.mjs', 'docs/research/2026-09-08-content-faces/plugin/search.php', 'docs/research/2026-09-08-content-faces/plugin/content-faces.php', 'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/templates/search.html', 'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/inc/search.php', 'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/inc/content-faces.php'];
const digests = () => Object.fromEntries(sources.map(f => [f, createHash('sha256').update(fs.readFileSync(path.join(root, f))).digest('hex')]));
const sourceDigests = digests(), rows = [];
const check = (name, pass, detail = {}) => rows.push({ name, pass: !!pass, ...detail });
const base = 'http://127.0.0.1:8098', protectedText = 'この段落は購入者向けの検証本文です';
const browser = await chromium.launch();
let ids = [], completed = false;
async function login(context, role) {
  await context.request.get(base + '/wp-login.php');
  await context.request.post(base + '/wp-login.php', { form: { log: 'lab_' + role, pwd: credentials[role], 'wp-submit': 'Log In', redirect_to: base, testcookie: '1' } });
  const authenticated = (await context.cookies()).some(c => c.name.startsWith('wordpress_logged_in_'));
  check('login:' + role, authenticated);
  if (!authenticated) throw Error('Fixture login failed');
}
async function search(page, term, expected, label) {
  const response = await page.goto(base + '/?s=' + encodeURIComponent(term));
  const total = Number((await page.locator('main .wp-block-query-total').innerText()).match(/\d+/)?.[0]);
  check('count:' + label, response.status() === 200 && total === expected, { expected, actual: total });
  check('results:' + label, await page.locator('main .wp-block-post-title').count() === expected);
  // The query itself is reflected in the form/title. Inspect result articles, not that reflection.
  const articles = await page.locator('main .wp-block-post-template').allTextContents();
  check('protected-excerpt:' + label, !articles.join('').includes(hidden) && !articles.join('').includes(protectedText));
}
try {
  ids = JSON.parse(wp(['eval', `$ids=array();try{foreach(array('publish','draft','private','protected')as $state){$p=array('post_type'=>'post','post_status'=>$state==='protected'?'publish':$state,'post_title'=>'${marker} '.$state,'post_content'=>$state==='publish'?'Public search sample':'${hidden}');if($state==='protected')$p['post_password']='fixture-only';$id=wp_insert_post($p,true);if(is_wp_error($id))throw new Exception('Create failed');$ids[]=$id;}echo wp_json_encode($ids);}catch(Throwable $e){foreach($ids as $id)wp_delete_post($id,true);throw $e;}`]));
  for (const role of ['anonymous', 'none', 'oneoff', 'subscription', 'expired', 'other_product']) {
    // The reader matrix must not accidentally use an editorial account that can read private posts.
    if (role !== 'anonymous') check('reader-capability:' + role, wp(['eval', `$u=get_user_by('login','lab_${role}');echo $u&&!user_can($u,'read_private_posts')?'reader':'unexpected';`]) === 'reader');
    for (const [device, width] of [['pc', 1440], ['sp', 375]]) for (const js of [true, false]) {
      const context = await browser.newContext({ viewport: { width, height: 900 }, javaScriptEnabled: js });
      if (role !== 'anonymous') await login(context, role);
      const page = await context.newPage(), label = `${role}:${device}:js-${js}`;
      await search(page, marker, 1, label + ':public');
      await search(page, hidden, 0, label + ':hidden-body');
      await search(page, protectedText, 0, label + ':purchase-body');
      for (const [kind, route] of [['oneoff', '/library/decision-design/'], ['subscription', '/library/monthly-notes/']]) {
        const response = await context.request.get(base + route + '?view=body');
        check('direct-access:' + label + ':' + kind, response.ok() && (await response.text()).includes(protectedText) === (role === kind));
      }
      await context.close();
    }
  }
  for (const role of ['oneoff', 'subscription']) {
    const context = await browser.newContext(), page = await context.newPage();
    for (const phase of ['before-login', 'after-login', 'after-logout']) {
      if (phase === 'after-login') await login(context, role);
      if (phase === 'after-logout') await context.clearCookies();
      await search(page, hidden, 0, `${role}:${phase}:hidden-body`);
      await search(page, protectedText, 0, `${role}:${phase}:purchase-body`);
      const route = role === 'oneoff' ? '/library/decision-design/' : '/library/monthly-notes/';
      const response = await context.request.get(base + route + '?view=body');
      check(`transition-body:${role}:${phase}`, response.ok() && (await response.text()).includes(protectedText) === (phase === 'after-login'));
    }
    await context.close();
  }
  completed = true;
} finally {
  await browser.close();
  if (ids.length) wp(['post', 'delete', ...ids.map(String), '--force']);
  check('owned-fixtures-removed', wp(['post', 'list', '--post_type=any', '--post_status=any', '--s=' + marker, '--format=ids']) === '');
  check('sources-unchanged', JSON.stringify(sourceDigests) === JSON.stringify(digests()));
  const name = process.argv.includes('--baseline') ? 'access-baseline.json' : 'access.json';
  fs.writeFileSync(path.join(root, 'docs/research/2026-09-08-site-search/results', name), JSON.stringify({ completed, sourceDigests, rows }, null, 2) + '\n');
  console.log(JSON.stringify({ checks: rows.length, failed: rows.filter(r => !r.pass).length, completed }));
}
if (rows.some(r => !r.pass) && !process.argv.includes('--baseline')) process.exitCode = 1;
