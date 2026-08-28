// PoC の管理画面でパターンを開き、Block validation（isValid=false）を数える。
// 手順: 各パターンの登録済み content を 1 ページに 1 つずつ投入 → 編集画面を開く → getBlocks() を再帰して isValid を集計 → ページ削除
import { chromium } from 'playwright'; import fs from 'fs'; import { execSync } from 'child_process';
const S = process.env.S; const B = process.env.POC_URL; // 例: https://poc.example
const KEY = '';
const SSH = process.env.POC_SSH; // 例: ssh -o BatchMode=yes -i ~/.ssh/<key> -p <port> <user>@<host>
const WP = process.env.POC_WP; // 例: cd <wp-root> && php wp-cli.phar
const sh = (c) => execSync(c, { stdio: ['ignore', 'pipe', 'pipe'], maxBuffer: 64e6 }).toString();
const env = Object.fromEntries(fs.readFileSync('process.env.POC_ENV', 'utf8').split('\n').filter(l => l.includes('=')).map(l => [l.slice(0, l.indexOf('=')), l.slice(l.indexOf('=') + 1).replace(/^"|"$/g, '')]));
const SLUGS = JSON.parse(process.env.SLUGS);
// 1 ページに 1 パターン（content は登録済みの PHP 実行後の文字列）
const ids = {};
for (const s of SLUGS) {
  const id = sh(`${SSH} '${WP} eval "\\$r = WP_Block_Patterns_Registry::get_instance()->get_registered(\\"agent-neo/${s}\\"); \\$id = wp_insert_post([\\"post_type\\"=>\\"page\\",\\"post_status\\"=>\\"draft\\",\\"post_title\\"=>\\"validate-${s}\\",\\"post_content\\"=>\\$r[\\"content\\"]]); echo \\$id;"'`).trim();
  ids[s] = id;
}
const b = await chromium.launch(); const ctx = await b.newContext({ viewport: { width: 1400, height: 900 } }); const p = await ctx.newPage();
await p.goto(B + '/wp-login.php'); await p.fill('#user_login', env.WP_ADMIN_USER); await p.fill('#user_pass', env.WP_ADMIN_PASS); await p.click('#wp-submit'); await p.waitForLoadState('networkidle');
const out = {};
for (const [s, id] of Object.entries(ids)) {
  await p.goto(`${B}/wp-admin/post.php?post=${id}&action=edit`, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await p.waitForFunction(() => window.wp && wp.data && wp.data.select('core/block-editor') && wp.data.select('core/block-editor').getBlocks().length > 0, null, { timeout: 30000 }).catch(() => {});
  await p.waitForTimeout(1500);
  out[s] = await p.evaluate(() => { const sel = wp.data.select('core/block-editor'); const bad = []; const diffs = [];
    const fresh = (b) => wp.blocks.createBlock(b.name, b.attributes, (b.innerBlocks || []).map(fresh));
    const walk = (bs) => { for (const b of bs) { if (b.isValid === false) { bad.push(b.name); const exp = wp.blocks.getSaveContent(b.name, b.attributes, (b.innerBlocks||[]).map(fresh)); diffs.push({ name: b.name, expected: exp.slice(0, 700), original: (b.originalContent || '').slice(0, 700) }); } walk(b.innerBlocks || []); } };
    walk(sel.getBlocks());
    const canonical = wp.blocks.serialize(sel.getBlocks().map(fresh));
    return { blocks: sel.getBlocks().length, invalid: bad, diffs, canonical }; });
  fs.writeFileSync(`${S}/parts/canonical-${s}.html`, out[s].canonical); delete out[s].canonical;
  if (out[s].invalid.length) await p.screenshot({ path: `${S}/parts/invalid-${s}.jpg`, type: 'jpeg', quality: 60, fullPage: false });
  console.error(s, JSON.stringify(out[s].invalid));
}
await b.close();
for (const id of Object.values(ids)) sh(`${SSH} '${WP} post delete ${id} --force >/dev/null'`);
fs.writeFileSync(`${S}/parts/editor-validate.json`, JSON.stringify(out, null, 1));
const total = Object.values(out).reduce((a, o) => a + o.invalid.length, 0); console.error('TOTAL invalid:', total);
