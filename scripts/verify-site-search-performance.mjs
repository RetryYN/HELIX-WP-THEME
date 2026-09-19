import { execFileSync } from 'node:child_process';
import { chromium } from 'playwright';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { createHash } from 'node:crypto';
import { performance } from 'node:perf_hooks';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const state = process.env.WTCF_STATE_DIR || path.join(os.tmpdir(), 'helix-content-lab');
const wp = args => execFileSync('docker', ['run', '--rm', '--network', 'helix-content-lab', '--env-file', path.join(state, 'wp.env'), '--volumes-from', 'helix-content-wp', '--user', '33:33', 'wordpress:cli-php8.3', 'wp', ...args], { encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] }).trim();
if (wp(['option', 'get', 'blogname']) !== 'HELIX Content Lab') throw Error('Dedicated lab required');

const marker = 'SearchPerfFixture';
const base = 'http://127.0.0.1:8098';
const sources = ['scripts/verify-site-search-performance.mjs', 'docs/research/2026-09-08-content-faces/plugin/search.php'];
const digests = () => Object.fromEntries(sources.map(file => [file, createHash('sha256').update(fs.readFileSync(path.join(root, file))).digest('hex')]));
const sourceDigests = digests();
const rows = [], measurements = [];
const check = (name, pass, actual) => rows.push({ name, pass: Boolean(pass), actual });
const median = values => [...values].sort((a, b) => a - b)[Math.floor(values.length / 2)];
const browser = await chromium.launch();
let completed = false;

function clearFixtures() {
  wp(['eval', `global $wpdb;$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->posts} WHERE post_title LIKE %s",'${marker}%'));wtcf_search_bump_password_cache_version();`]);
}
function seed(count) {
  return JSON.parse(wp(['eval', `$ids=array();try{for($i=0;$i<${count};$i++){$id=wp_insert_post(array('post_type'=>'post','post_status'=>'publish','post_title'=>'${marker} '.($i<2?'Visible ':'Hidden ').$i,'post_content'=>'Performance fixture','post_password'=>$i<2?'perf-unlock':'other-'.$i),true);if(is_wp_error($id))throw new Exception($id->get_error_message());$ids[]=$id;}$public=wp_insert_post(array('post_type'=>'post','post_status'=>'publish','post_title'=>'${marker} Visible public','post_content'=>'Performance fixture'),true);if(is_wp_error($public))throw new Exception($public->get_error_message());echo wp_json_encode(array('protected'=>$ids,'public'=>(int)$public));}catch(Throwable $e){foreach($ids as $id)wp_delete_post($id,true);throw $e;}`]));
}
function profile(count) {
  return JSON.parse(wp(['eval', `global $wpdb;require_once ABSPATH.WPINC.'/class-phpass.php';$hasher=new PasswordHash(8,true);$_COOKIE['wp-postpass_'.COOKIEHASH]=$hasher->HashPassword('perf-unlock');$key='wtcf_search_unlocked_'.hash('sha256',wp_unslash($_COOKIE['wp-postpass_'.COOKIEHASH]).'|'.wtcf_search_password_cache_version());delete_transient($key);$GLOBALS['wtcf_perf_checks']=0;add_filter('post_password_required',function($required){$GLOBALS['wtcf_perf_checks']++;return $required;},PHP_INT_MAX);$before=$wpdb->num_queries;$start=microtime(true);$cold=wtcf_search_unlocked_posts();$cold_ms=(microtime(true)-$start)*1000;$cold_queries=$wpdb->num_queries-$before;$cold_checks=$GLOBALS['wtcf_perf_checks'];$GLOBALS['wtcf_perf_checks']=0;$before=$wpdb->num_queries;$start=microtime(true);$warm=wtcf_search_unlocked_posts();$warm_ms=(microtime(true)-$start)*1000;$warm_queries=$wpdb->num_queries-$before;echo wp_json_encode(compact('cold','warm','cold_ms','warm_ms','cold_queries','warm_queries','cold_checks')+array('warm_checks'=>$GLOBALS['wtcf_perf_checks']));`]));
}

try {
  clearFixtures();
  for (const count of [50, 400]) {
    const fixture = seed(count);
    const direct = profile(count);
    check(`cold-scans-protected:${count}`, direct.cold_checks >= count, direct.cold_checks);
    check(`warm-skips-password-checks:${count}`, direct.warm_checks === 0, direct.warm_checks);
    check(`cache-preserves-unlocked-ids:${count}`, direct.cold.length === 2 && JSON.stringify(direct.cold) === JSON.stringify(direct.warm), direct.warm.length);
    check(`warm-uses-bounded-queries:${count}`, direct.warm_queries <= 2 && direct.warm_queries < direct.cold_queries, { cold: direct.cold_queries, warm: direct.warm_queries });

    const context = await browser.newContext();
    await context.request.post(base + '/wp-login.php?action=postpass', { form: { post_password: 'perf-unlock' }, headers: { referer: base + '/' } });
    check(`postpass-cookie:${count}`, (await context.cookies()).some(cookie => cookie.name.startsWith('wp-postpass_')));
    const page = await context.newPage();
    const elapsed = async () => { const start = performance.now(); await page.goto(base + '/?s=' + marker + '+Visible'); return performance.now() - start; };
    const coldResponseMs = await elapsed();
    const warmResponseMs = [];
    for (let run = 0; run < 5; run++) warmResponseMs.push(await elapsed());
    const resultCount = Number((await page.locator('main .wp-block-query-total').innerText()).match(/\d+/)?.[0]);
    check(`search-result-contract:${count}`, resultCount === 3, resultCount);
    check(`warm-response-faster-than-cold:${count}`, median(warmResponseMs) < coldResponseMs, { coldResponseMs, warmMedianMs: median(warmResponseMs) });
    measurements.push({ protectedPosts: count, direct, coldResponseMs, warmResponseMs, warmMedianMs: median(warmResponseMs) });
    await context.close();
    clearFixtures();
  }
  const [small, large] = measurements;
  check('warm-response-not-linear-with-protected-count', large.warmMedianMs < small.warmMedianMs * 4, { countRatio: 8, responseRatio: large.warmMedianMs / small.warmMedianMs });
  completed = true;
} finally {
  await browser.close();
  clearFixtures();
  check('owned-fixtures-removed', wp(['post', 'list', '--post_type=any', '--post_status=any', '--s=' + marker, '--format=ids']) === '');
  check('sources-unchanged', JSON.stringify(sourceDigests) === JSON.stringify(digests()));
  const out = path.join(root, 'docs/research/2026-09-08-site-search/results/performance.json');
  fs.writeFileSync(out, JSON.stringify({ completed, sourceDigests, measurements, rows, limitation: 'Cold cache scans protected posts once per cookie/version. Warm searches reuse a server-side transient. Timing is local lab evidence, not a production SLA.' }, null, 2) + '\n');
  console.log(JSON.stringify({ completed, checks: rows.length, failed: rows.filter(row => !row.pass).length }));
}
if (rows.some(row => !row.pass)) process.exitCode = 1;
