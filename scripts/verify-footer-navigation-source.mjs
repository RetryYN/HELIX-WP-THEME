import fs from 'node:fs';
import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
const root = new URL('../', import.meta.url);
const php = code => execFileSync('docker', ['exec', 'helix-content-wp', 'php', '-r', 'require "/var/www/html/wp-load.php"; ' + code], { encoding: 'utf8' }).trim();
if (php('echo get_option("blogname");') !== 'HELIX Content Lab') throw Error('Dedicated lab required');
const slug = 'footer-navigation-source-fixture';
if (php(`echo count(get_posts(array('post_type'=>'wp_navigation','post_status'=>'any','name'=>'${slug}')));`) !== '0') throw Error('Reserved fixture exists');
const encode = value => Buffer.from(JSON.stringify(value)).toString('base64');
const items = [{ label: '会社案内', url: '/site-company/' }, { label: '読む・学ぶ', url: '/learn/' }];
const content = links => links.map(attrs => '<!-- wp:navigation-link ' + JSON.stringify({ ...attrs, kind: 'custom' }) + ' /-->').join('\n');
const guarded = process.argv.includes('--guarded');
const rows = []; let id, completed = false;
try {
 id = Number(php(`$id=wp_insert_post(array('post_type'=>'wp_navigation','post_status'=>'publish','post_name'=>'${slug}','post_title'=>'Footer Navigation Source Fixture'),true); if(is_wp_error($id)){fwrite(STDERR,'create failed');exit(1);} echo $id;`));
 if (!Number.isSafeInteger(id) || id < 1) throw Error('Invalid fixture ID');
 for (const [name, status, links, wanted] of [['empty','publish',[]],['one','publish',items.slice(0,1)],['multiple','publish',items],['draft','draft',items],['private','private',items],['emptied','publish',[]],['blank-label','publish',[{label:'',url:'/learn/'}],[]],['spaces-label','publish',[{label:'   ',url:'/learn/'}],[]],['blank-url','publish',[{label:'案内',url:''}],[]],['script-url','publish',[{label:'案内',url:'javascript:alert(1)'}],[]],['mixed','publish',[items[0],{label:'',url:'/learn/'}],[items[0]]]]) {
  const payload = encode({ ID: id, post_status: status, post_content: content(links) });
  php(`$r=wp_update_post(wp_slash(json_decode(base64_decode('${payload}'),true)),true);if(is_wp_error($r)){exit(1);}`);
  const render = guarded ? `require get_theme_file_path('inc/footer-navigation.php'); $html=wt_footer_navigation(${id},'フッター案内');` : `$html=do_blocks('<!-- wp:navigation {"ref":${id},"overlayMenu":"never"} /-->');`;
  const actual = JSON.parse(php(`${render} $d=new DOMDocument(); @$d->loadHTML('<?xml encoding="UTF-8">'.$html);$links=array();foreach($d->getElementsByTagName('a') as $a){$links[]=array('label'=>trim($a->textContent),'url'=>$a->getAttribute('href'));}echo wp_json_encode(array('links'=>$links,'nav_count'=>$d->getElementsByTagName('nav')->length));`));
  const expected = status === 'publish' ? (wanted ?? links) : [];
  rows.push({ name: 'stored-links:' + name, pass: JSON.stringify(actual.links) === JSON.stringify(expected), actual, expected });
  if (!expected.length) rows.push({ name: 'empty-wrapper-omitted:' + name, pass: actual.nav_count === 0 });
 }
 completed = true;
} finally {
 if (id) php(`if(!wp_delete_post(${id},true)){exit(1);}`);
 rows.push({ name:'owned-fixture-removed',pass:php(`echo count(get_posts(array('post_type'=>'wp_navigation','post_status'=>'any','name'=>'${slug}')));`)==='0' });
 const file = 'scripts/verify-footer-navigation-source.mjs';
 const result = { completed, wordpress:php('echo get_bloginfo("version");'), sourceDigests:Object.fromEntries([file,...(guarded ? ['docs/research/2026-09-05-design-prototype-03/theme/helix-wt/inc/footer-navigation.php'] : [])].map(p=>[p,createHash('sha256').update(fs.readFileSync(new URL(p,root))).digest('hex')])), rows, limitation:'Navigation標準ブロックのサーバー描画。Site Editor UI、フッター組込み、端末別レイアウトの検証ではない。' };
 fs.writeFileSync(new URL('docs/research/2026-09-09-footer-data/'+(process.argv.includes('--baseline') ? 'navigation-input-baseline.json' : (guarded?'navigation-guarded.json':'navigation-source.json')),root), JSON.stringify(result,null,2)+'\n');
}
console.log(JSON.stringify({completed,checks:rows.length,failed:rows.filter(r=>!r.pass).length}));

if (guarded && rows.some(r=>!r.pass)) process.exitCode=1;
