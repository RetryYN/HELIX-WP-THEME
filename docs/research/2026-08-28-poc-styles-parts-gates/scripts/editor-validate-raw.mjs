import { chromium } from 'playwright'; import fs from 'fs'; import { execSync } from 'child_process';
const B = process.env.POC_URL; // 例: https://poc.example const KEY = '';
const SSH = process.env.POC_SSH; // 例: ssh -o BatchMode=yes -i ~/.ssh/<key> -p <port> <user>@<host>
const WP = process.env.POC_WP; // 例: cd <wp-root> && php wp-cli.phar
const sh = (c, input) => execSync(c, { input, stdio: ['pipe', 'pipe', 'pipe'], maxBuffer: 64e6 }).toString();
const env = Object.fromEntries(fs.readFileSync('process.env.POC_ENV', 'utf8').split('\n').filter(l => l.includes('=')).map(l => [l.slice(0, l.indexOf('=')), l.slice(l.indexOf('=') + 1).trim()]));
const FILES = process.env.FILES.split(','); const ids = {};
for (const f of FILES) { const id = sh(`${SSH} '${WP} post create --post_type=page --post_status=draft --post_title=conv-$(basename ${f}) --porcelain -'`, fs.readFileSync(f)).trim(); ids[f] = id; }
const b = await chromium.launch(); const ctx = await b.newContext({ viewport: { width: 1400, height: 900 } }); const p = await ctx.newPage();
await p.goto(B + '/wp-login.php'); await p.fill('#user_login', env.WP_ADMIN_USER); await p.fill('#user_pass', env.WP_ADMIN_PASS); await p.click('#wp-submit'); await p.waitForLoadState('domcontentloaded');
const out = {};
for (const [f, id] of Object.entries(ids)) {
  await p.goto(`${B}/wp-admin/post.php?post=${id}&action=edit`, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await p.waitForFunction(() => window.wp && wp.data && wp.data.select('core/block-editor') && wp.data.select('core/block-editor').getBlocks().length > 0, null, { timeout: 60000 });
  await p.waitForTimeout(1500);
  out[f] = await p.evaluate(() => { const sel = wp.data.select('core/block-editor'); const bad = []; let n = 0;
    const walk = (bs) => { for (const b of bs) { n++; if (b.isValid === false) bad.push(b.name); walk(b.innerBlocks || []); } };
    walk(sel.getBlocks()); return { blocks: n, invalid: bad }; });
  console.log(f.split('/').pop(), JSON.stringify(out[f]));
}
await b.close();
for (const id of Object.values(ids)) sh(`${SSH} '${WP} post delete ${id} --force >/dev/null'`);
fs.writeFileSync(process.env.OUT, JSON.stringify(out, null, 1));
