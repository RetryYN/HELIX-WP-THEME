import { execFileSync } from 'node:child_process';
import { chromium } from 'playwright';
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
const marker = 'LearningPublicationFixture';
assert.equal(wp(['post', 'list', '--post_type=wt_learning', '--post_status=any', '--s=' + marker, '--format=ids']), '', 'Reserved fixture exists; inspect before retrying');
const rows = []; let ids = [], completed = false;
const check = (name, pass) => { rows.push({ name, pass: Boolean(pass) }); assert.ok(pass, name); };
const browser = await chromium.launch();
const base = 'http://127.0.0.1:8098';
try {
  ids = JSON.parse(wp(['eval', `$ids=array(); for($i=1;$i<=7;$i++){ $ids[]=wp_insert_post(array('post_type'=>'wt_learning','post_status'=>'publish','post_title'=>'${marker} '.$i,'post_content'=>'${marker}','menu_order'=>$i)); } echo wp_json_encode($ids);`]));
  assert.ok(ids.length === 7 && ids.every(id => Number.isInteger(id) && id > 0));
  const contexts = [];
  for (const dev of ['pc', 'sp']) {
    const context = await browser.newContext({ javaScriptEnabled: false, viewport: dev === 'pc' ? { width: 1440, height: 1000 } : { width: 375, height: 812 } });
    const page = await context.newPage(); contexts.push({ dev, page });
    await page.goto(`${base}/learn/?learn_q=${marker}&learn_page=2`);
    check('published:last-page:' + dev, await page.locator('.wtcf-list article').count() === 1 && (await page.locator('.wtlearn-results').innerText()).includes('7件'));
    check('published:last-record:' + dev, await page.locator('.wtcf-list article').innerText() === marker + ' 7');
  }
  wp(['post', 'update', String(ids[6]), '--post_status=draft']);
  for (const { dev, page } of contexts) {
    await page.reload();
    check('unpublished:count-and-recovery:' + dev, (await page.locator('.wtlearn-results').innerText()).includes('6件') && await page.locator('.wtcf-list article').count() === 6 && await page.locator('[aria-current="page"]').innerText() === '1 / 1 ページ');
    check('unpublished:record-absent:' + dev, !(await page.locator('.wtcf-list').innerText()).includes(marker + ' 7'));
    check('unpublished:query-preserved:' + dev, await page.locator('#learn-query').inputValue() === marker);
  }
  wp(['eval', `foreach(array(${ids.join(',')}) as $id){ wp_update_post(array('ID'=>$id,'post_status'=>'draft')); }`]);
  for (const { dev, page } of contexts) {
    await page.reload();
    check('all-unpublished:zero:' + dev, await page.locator('.wtlearn-empty').count() === 1 && await page.locator('.wtcf-list article').count() === 0 && await page.locator('[aria-label="一覧のページ送り"] a').count() === 0);
    await page.locator('.wtlearn-empty a').click();
    check('all-unpublished:back-to-public-list:' + dev, (await page.locator('.wtlearn-results').innerText()).includes('7件') && !(await page.locator('.wtcf-list').innerText()).includes(marker));
  }
  completed = true;
} finally {
  await browser.close();
  if (ids.length) wp(['post', 'delete', ...ids.map(String), '--force']);
  check('owned-fixtures-removed', wp(['post', 'list', '--post_type=wt_learning', '--post_status=any', '--s=' + marker, '--format=ids']) === '');
  fs.writeFileSync(path.join(root, 'docs/research/2026-09-08-content-faces/results/learning/publication.json'), JSON.stringify({ schema: 'wt-learning-publication.v1', completed, rows }, null, 2) + '\n');
}
console.log(`Learning publication changes: ${rows.length} checks passed; owned fixtures removed`);
