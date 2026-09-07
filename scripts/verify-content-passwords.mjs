import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import assert from 'node:assert/strict';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const state = process.env.WTCF_STATE_DIR || path.join(os.tmpdir(), 'helix-content-lab');
const cli = ['run', '--rm', '--network', 'helix-content-lab', '--env-file', path.join(state, 'wp.env'),
  '--volumes-from', 'helix-content-wp', '--user', '33:33', 'wordpress:cli-php8.3', 'wp'];
const wp = args => execFileSync('docker', [...cli, ...args], { encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'] }).trim();
assert.equal(wp(['option', 'get', 'blogname']), 'HELIX Content Lab');
const ids = JSON.parse(wp(['eval', 'echo wp_json_encode(array_merge(get_option("wtcf_fixture_ids"),get_option("wtcf_learning_ids")));']));
const samples = [['oneoff', '最初に揃えるのは'], ['interview', '良いページは'], ['blp', '新しいデザインを選ぶ前に'], ['lp', '相談内容を入力する'], ['course', 'この講座で学ぶこと']];
const checks = []; let completed = false;
try {
  for (const [key, marker] of samples) {
    const id = ids[key]; assert.ok(Number.isInteger(id));
    // Only known, initially unprotected lab fixtures are changed; the original state is restored.
    assert.ok(wp(['post', 'get', String(id), '--field=post_password']) === '', 'Expected an unprotected lab fixture');
    try {
      wp(['eval', `wp_update_post(array('ID'=>${id},'post_password'=>wp_generate_password(28,true)));`]);
      const response = await fetch(`http://127.0.0.1:8098/?p=${id}`);
      const html = await response.text();
      const pass = response.ok && html.includes('name="post_password"') && !html.includes(marker);
      checks.push({ name: `${key}:password-boundary`, pass }); assert.ok(pass, key);
      const type = wp(['post', 'get', String(id), '--field=post_type']);
      const restResponse = await fetch(`http://127.0.0.1:8098/wp-json/wp/v2/${type}/${id}`);
      const rest = await restResponse.json();
      const restPass = restResponse.ok && rest.content?.protected === true && !JSON.stringify(rest).includes(marker); checks.push({ name: `${key}:rest-password-boundary`, pass: restPass }); assert.ok(restPass, key + ':REST');
    } finally { wp(['post', 'update', String(id), '--post_password=']); }
  }
  completed = true;
} finally {
  fs.writeFileSync(path.join(root, 'docs/research/2026-09-08-content-faces/results/passwords.json'), JSON.stringify({ schema: 'wt-content-password-boundary.v1', completed, checks }, null, 2) + '\n');
}
console.log(`WordPress password boundaries: ${checks.length} checks passed; fixture passwords restored`);
