import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { createHash } from 'node:crypto';
import { chromium } from 'playwright';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const state = process.env.WTCF_STATE_DIR || path.join(os.tmpdir(), 'helix-content-lab');
const base = 'http://127.0.0.1:8098';
const marker = 'SearchEntitlementFixture';
const protectedBody = 'SearchEntitlementProtectedBody';
const editorLogin = 'lab_search_editor';
const editorPassword = ['search', 'fixture', 'pass'].join('-');
const wp = args => execFileSync('docker', [
  'run', '--rm', '--network', 'helix-content-lab', '--env-file', path.join(state, 'wp.env'),
  '--volumes-from', 'helix-content-wp', '--user', '33:33', 'wordpress:cli-php8.3', 'wp', ...args,
], { encoding: 'utf8', maxBuffer: 16 * 1024 * 1024, stdio: ['ignore', 'pipe', 'pipe'] }).trim();
const php = code => wp(['eval', code]);
const digest = file => createHash('sha256').update(fs.readFileSync(path.join(root, file))).digest('hex');
const sources = [
  'scripts/verify-site-search-entitlements.mjs',
  'scripts/verify-site-search-access.mjs',
  'docs/research/2026-09-08-content-faces/plugin/content-faces.php',
  'docs/research/2026-09-08-content-faces/plugin/search.php',
  'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/templates/search.html',
  'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/inc/search.php',
];
const sourceDigests = Object.fromEntries(sources.map(file => [file, digest(file)]));
const rows = [];
const check = (name, pass, detail = {}) => rows.push({ name, pass: Boolean(pass), ...detail });
const credentials = JSON.parse(fs.readFileSync(path.join(state, 'credentials.json'), 'utf8'));
const browser = await chromium.launch();
let fixtureIds = {};
let editorId = 0;
let editorCreated = false;
let originalEntitlements = {};
let completed = false;

function setEntitlement(login, value) {
  const encoded = Buffer.from(JSON.stringify(value)).toString('base64');
  php(`$u=get_user_by('login','${login}');if(!$u)throw new Exception('missing user');update_user_meta($u->ID,'_wtcf_entitlement',json_decode(base64_decode('${encoded}'),true));`);
}

function restoreEntitlements() {
  for (const [login, value] of Object.entries(originalEntitlements)) {
    const encoded = Buffer.from(JSON.stringify(value)).toString('base64');
    php(`$u=get_user_by('login','${login}');if($u){$v=json_decode(base64_decode('${encoded}'),true);if($v===null)delete_user_meta($u->ID,'_wtcf_entitlement');else update_user_meta($u->ID,'_wtcf_entitlement',$v);}`);
  }
}

async function login(context, loginName, password) {
  await context.request.get(base + '/wp-login.php');
  await context.request.post(base + '/wp-login.php', {
    form: { log: loginName, pwd: password, 'wp-submit': 'Log In', redirect_to: base, testcookie: '1' },
    timeout: 15000,
  });
  check(`login:${loginName}`, (await context.cookies()).some(cookie => cookie.name.startsWith('wordpress_logged_in_')));
}

function feedItems(xml) {
  return [...xml.matchAll(/<item[\s\S]*?<\/item>/g)].map(match => match[0]);
}

async function inspectSearch(role, loginName, password, expectedPrivate) {
  const context = await browser.newContext({ viewport: { width: 1440, height: 900 }, javaScriptEnabled: false });
  if (loginName) await login(context, loginName, password);
  const html = await context.request.get(`${base}/?s=${marker}`, { timeout: 15000 });
  const htmlBody = await html.text();
  const resultText = htmlBody.match(/<main[\s\S]*?<\/main>/i)?.[0] || '';
  check(`html:${role}:status`, html.status() === 200);
  check(`html:${role}:public`, resultText.includes(`${marker} public`));
  check(`html:${role}:private-boundary`, resultText.includes(`${marker} private`) === expectedPrivate);
  check(`html:${role}:draft-excluded`, !resultText.includes(`${marker} draft`));
  check(`html:${role}:protected-excluded`, !resultText.includes(`${marker} protected`) && !resultText.includes(protectedBody));
  check(`html:${role}:no-store`, (html?.headers()['cache-control'] || '').includes('no-store'));

  const rest = await context.request.get(`${base}/wp-json/wp/v2/search?search=${marker}`, { timeout: 15000 });
  let restItems = [];
  try { restItems = await rest.json(); } catch { restItems = []; }
  const restText = JSON.stringify(restItems);
  check(`rest:${role}:status`, rest.status() === 200);
  check(`rest:${role}:public`, restText.includes(`${marker} public`));
  check(`rest:${role}:private-excluded`, !restText.includes(`${marker} private`));
  check(`rest:${role}:draft-excluded`, !restText.includes(`${marker} draft`));
  check(`rest:${role}:protected-excluded`, !restText.includes(`${marker} protected`) && !restText.includes(protectedBody));

  const feed = await context.request.get(`${base}/feed/?s=${marker}`, { timeout: 15000 });
  const items = feedItems(await feed.text());
  const feedText = items.join('\n');
  check(`feed:${role}:status`, feed.status() === 200);
  check(`feed:${role}:public`, feedText.includes(`${marker} public`));
  check(`feed:${role}:private-boundary`, feedText.includes(`${marker} private`) === expectedPrivate);
  check(`feed:${role}:draft-excluded`, !feedText.includes(`${marker} draft`));
  check(`feed:${role}:protected-excluded`, !feedText.includes(`${marker} protected`) && !feedText.includes(protectedBody));
  await context.close();
}

async function inspectPaid(role, loginName, password, pathName, expectedBody) {
  const context = await browser.newContext();
  if (loginName) await login(context, loginName, password);
  const response = await context.request.get(`${base}${pathName}?view=body`, { timeout: 15000 });
  const body = await response.text();
  check(`paid:${role}:${pathName}:status`, response.status() === 200);
  check(`paid:${role}:${pathName}:entitlement`, body.includes('購入者向けの検証本文です') === expectedBody);
  check(`paid:${role}:${pathName}:no-store`, (response.headers()['cache-control'] || '').includes('no-store'));
  const rest = await context.request.get(`${base}/wp-json/wp/v2/wt_paid/${pathName.includes('decision-design') ? fixtureIds.oneoff : fixtureIds.subscription}`, { timeout: 15000 });
  check(`paid:${role}:${pathName}:rest-protected-body`, !(await rest.text()).includes('購入者向けの検証本文です'));
  const feed = await context.request.get(`${base}/?post_type=wt_paid&feed=rss2`, { timeout: 15000 });
  check(`paid:${role}:${pathName}:feed-protected-body`, !(await feed.text()).includes('購入者向けの検証本文です'));
  await context.close();
}

try {
  if (wp(['option', 'get', 'blogname']) !== 'HELIX Content Lab') throw new Error('Dedicated lab required');
  if (wp(['post', 'list', '--post_type=any', '--post_status=any', '--s=' + marker, '--format=ids'])) throw new Error('Reserved fixtures exist');
  const original = JSON.parse(php(`$out=[];foreach(['lab_oneoff','lab_subscription','lab_expired','lab_other_product','lab_none']as $login){$u=get_user_by('login',$login);$out[$login]=$u?get_user_meta($u->ID,'_wtcf_entitlement',true):null;}echo wp_json_encode($out);`));
  originalEntitlements = original;
  const created = JSON.parse(php(`$ids=[];foreach(['public','private','draft','protected']as $kind){$status=$kind==='private'?'private':($kind==='draft'?'draft':'publish');$id=wp_insert_post(['post_type'=>'post','post_status'=>$status,'post_title'=>'${marker} '.$kind,'post_content'=>$kind==='protected'?'${protectedBody}':'Search entitlement fixture body','post_password'=>$kind==='protected'?'search-fixture-pass':'']);if(is_wp_error($id))throw new Exception('fixture create');$ids[$kind]=(int)$id;}echo wp_json_encode($ids);`));
  fixtureIds = created;
  const editor = JSON.parse(php(`$u=get_user_by('login','${editorLogin}');$created=false;if(!$u){$id=wp_insert_user(['user_login'=>'${editorLogin}','user_pass'=>'${editorPassword}','user_email'=>'${editorLogin}@example.invalid','role'=>'editor']);if(is_wp_error($id))throw new Exception('editor create');$u=get_user_by('id',$id);$created=true;}echo wp_json_encode(['id'=>(int)$u->ID,'created'=>$created]);`));
  editorId = editor.id;
  editorCreated = editor.created;
  const fixture = await php(`echo wp_json_encode(get_option('wtcf_fixture_ids'));`);
  fixtureIds = { ...fixtureIds, ...JSON.parse(fixture) };
  const roles = [
    ['anonymous', '', ''],
    ['none', 'lab_none', credentials.none],
    ['expired', 'lab_expired', credentials.expired],
    ['other_product', 'lab_other_product', credentials.other_product],
    ['oneoff', 'lab_oneoff', credentials.oneoff],
    ['subscription', 'lab_subscription', credentials.subscription],
    ['editor', editorLogin, editorPassword],
    ['admin', 'lab_admin', credentials.admin],
  ];
  for (const [role, loginName, password] of roles) await inspectSearch(role, loginName, password, role === 'editor' || role === 'admin');
  for (const [role, loginName, password, pathName, expected] of [
    ['anonymous', '', '', '/library/decision-design/', false],
    ['none', 'lab_none', credentials.none, '/library/decision-design/', false],
    ['oneoff', 'lab_oneoff', credentials.oneoff, '/library/decision-design/', true],
    ['subscription', 'lab_subscription', credentials.subscription, '/library/monthly-notes/', true],
    ['editor', editorLogin, editorPassword, '/library/decision-design/', false],
  ]) await inspectPaid(role, loginName, password, pathName, expected);

  setEntitlement('lab_oneoff', { state: 'oneoff', posts: [] });
  await inspectPaid('oneoff-revoked', 'lab_oneoff', credentials.oneoff, '/library/decision-design/', false);
  setEntitlement('lab_oneoff', { state: 'oneoff', posts: [Number(fixtureIds.oneoff)] });
  await inspectPaid('oneoff-granted', 'lab_oneoff', credentials.oneoff, '/library/decision-design/', true);
  setEntitlement('lab_oneoff', { state: 'oneoff', posts: [] });
  await inspectPaid('oneoff-revoked-again', 'lab_oneoff', credentials.oneoff, '/library/decision-design/', false);
  setEntitlement('lab_subscription', { state: 'subscription', expires: Math.floor(Date.now() / 1000) - 1 });
  await inspectPaid('subscription-expired', 'lab_subscription', credentials.subscription, '/library/monthly-notes/', false);
  setEntitlement('lab_subscription', { state: 'subscription', expires: Math.floor(Date.now() / 1000) + 3600 });
  await inspectPaid('subscription-granted', 'lab_subscription', credentials.subscription, '/library/monthly-notes/', true);
  setEntitlement('lab_subscription', { state: 'subscription', expires: Math.floor(Date.now() / 1000) - 1 });
  await inspectPaid('subscription-expired-again', 'lab_subscription', credentials.subscription, '/library/monthly-notes/', false);
  completed = true;
} finally {
  await browser.close();
  try { restoreEntitlements(); } catch (error) { check('entitlement-restore', false, { detail: String(error) }); }
  if (fixtureIds.public || fixtureIds.private || fixtureIds.draft || fixtureIds.protected) {
    try { wp(['post', 'delete', ...[fixtureIds.public, fixtureIds.private, fixtureIds.draft, fixtureIds.protected].filter(Boolean).map(String), '--force']); } catch (error) { check('fixtures-removed', false, { detail: String(error) }); }
  }
  if (editorCreated && editorId) {
    try { wp(['user', 'delete', String(editorId), '--yes']); } catch (error) { check('editor-removed', false, { detail: String(error) }); }
  }
  check('owned-fixtures-removed', wp(['post', 'list', '--post_type=any', '--post_status=any', '--s=' + marker, '--format=ids']) === '');
  check('sources-unchanged', JSON.stringify(sourceDigests) === JSON.stringify(Object.fromEntries(sources.map(file => [file, digest(file)]))));
  const out = path.join(root, 'docs/research/2026-09-08-site-search/results/entitlements.json');
  fs.writeFileSync(out, JSON.stringify({ schema: 'wt-site-search-entitlements.v1', completed, sourceDigests, rows }, null, 2) + '\n');
  console.log(JSON.stringify({ completed, checks: rows.length, failed: rows.filter(row => !row.pass).length }));
}
if (rows.some(row => !row.pass)) process.exitCode = 1;
