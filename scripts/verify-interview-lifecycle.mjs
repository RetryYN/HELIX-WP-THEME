import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import assert from 'node:assert/strict';
const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const state = process.env.WTCF_STATE_DIR || path.join(os.tmpdir(), 'helix-content-lab');
const cli = ['run', '--rm', '--network', 'helix-content-lab', '--env-file', path.join(state, 'wp.env'), '--volumes-from', 'helix-content-wp', '--user', '33:33', 'wordpress:cli-php8.3', 'wp'];
const wp = args => execFileSync('docker', [...cli, ...args], { encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] }).trim();
assert.equal(wp(['option', 'get', 'blogname']), 'HELIX Content Lab');
const base = 'http://127.0.0.1:8098';
const marker = 'INTERVIEW_LIFECYCLE_PRIVATE_SENTINEL';
const rows = []; let completed = false; let id;
const check = (name, pass) => rows.push({ name, pass: Boolean(pass) });
async function inspect(label, published) {
  check(label + ':status', wp(['post', 'get', String(id), '--field=post_status']) === (published ? 'publish' : 'draft'));
  const detail = await fetch(base + '/?p=' + id); const html = await detail.text();
  check(label + ':detail', detail.status === (published ? 200 : 404) && html.includes(marker) === published);
  const rest = await fetch(base + '/wp-json/wp/v2/wt_interview/' + id); const data = await rest.json();
  check(label + ':rest', (published ? rest.status === 200 : [401, 403, 404].includes(rest.status)) && JSON.stringify(data).includes(marker) === published);
  for (const [route, name] of [['/wp-json/wp/v2/wt_interview?per_page=100', 'collection'], ['/?s=' + marker, 'search'], ['/?feed=rss2&post_type=wt_interview', 'feed'], ['/voices/', 'archive']]) {
    const response = await fetch(base + route); const body = await response.text();
    // Search echoes its query even with no results; inspect actual result links instead.
    const found = name === 'search' ? body.includes('boundary-interview-check') : body.includes(marker);
    check(label + ':' + name, response.ok && found === published);
  }
  const card = wp(['eval', `echo do_shortcode('[wtcf_interview_card slug="boundary-interview-check"]');`]);
  check(label + ':card', card.includes(marker) === published);
  const projection = wp(['eval', `echo wp_json_encode(wtcf_display(${id}));`]);
  check(label + ':projection', published ? projection.includes(marker) : projection === 'null');
}
try {
  const existing = wp(['post', 'list', '--post_type=wt_interview', '--name=boundary-interview-check', '--post_status=any', '--format=ids']);
  assert.equal(existing, '', 'Reserved fixture exists; inspect it before retrying');
  id = Number(wp(['eval', `$source=get_option('wtcf_fixture_ids')['interview']; $doc=wtcf_document($source); $doc['confirmed']=true; echo wp_insert_post(array('post_type'=>'wt_interview','post_status'=>'publish','post_name'=>'boundary-interview-check','post_title'=>'掲載状態の検証','post_content'=>'${marker}','post_excerpt'=>'${marker}','meta_input'=>array('_wtcf_document'=>$doc)));`]));
  assert.ok(Number.isInteger(id) && id > 0);
  await inspect('confirmed', true);
  wp(['eval', `$doc=wtcf_document(${id}); $doc['confirmed']=false; update_post_meta(${id},'_wtcf_document',$doc);`]);
  await inspect('confirmation-revoked', false);
  wp(['eval', `$doc=wtcf_document(${id}); $doc['confirmed']=true; update_post_meta(${id},'_wtcf_document',$doc);`]);
  await inspect('reconfirmed-awaits-publish', false);
  wp(['post', 'update', String(id), '--post_status=publish']);
  await inspect('explicitly-republished', true);
  wp(['eval', `$doc=wtcf_document(${id}); $doc['exchanges'][0]['speaker']='unknown-person'; update_post_meta(${id},'_wtcf_document',$doc);`]);
  await inspect('broken-speaker-reference', false);
  wp(['post', 'update', String(id), '--post_status=publish']);
  await inspect('invalid-publish-rejected', false);
  wp(['eval', `$doc=wtcf_document(get_option('wtcf_fixture_ids')['interview']); update_post_meta(${id},'_wtcf_document',$doc); wp_update_post(array('ID'=>${id},'post_status'=>'publish'));`]);
  await inspect('repaired-and-republished', true);
  wp(['eval', `delete_post_meta(${id},'_wtcf_document');`]);
  await inspect('missing-document', false);
  completed = true;
} finally {
  if (id) { wp(['post', 'delete', String(id), '--force']); check('owned-fixture-removed', wp(['post', 'list', '--post_type=wt_interview', '--name=boundary-interview-check', '--post_status=any', '--format=ids']) === ''); }
  fs.writeFileSync(path.join(root, 'docs/research/2026-09-08-content-faces/results/interview-lifecycle.json'), JSON.stringify({ schema: 'wt-interview-lifecycle.v1', completed, rows }, null, 2) + '\n');
}
console.log(`Interview lifecycle: ${rows.filter(r => r.pass).length}/${rows.length} checks passed; owned fixture removed`);
if (rows.some(r => !r.pass)) process.exitCode = 1;
