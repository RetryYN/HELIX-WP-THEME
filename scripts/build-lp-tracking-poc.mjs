import fs from 'node:fs';
import { fixture, validateTrackingContract } from '../docs/research/2026-09-20-lp-tracking-poc/model.mjs';

const root = 'docs/research/2026-09-20-lp-tracking-poc';
const esc = value => String(value).replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;');
validateTrackingContract();
fs.mkdirSync(root, { recursive: true });
fs.writeFileSync(root + '/manifest.json', JSON.stringify({ schema: 'wt-lp-tracking-manifest.v1', lp: fixture.lp, patterns: fixture.lp.patternIds }, null, 2) + '\n');
fs.writeFileSync(root + '/form-fixture.json', JSON.stringify({ schema: 'wt-lp-form-slot.v1', formSlots: fixture.formSlots }, null, 2) + '\n');
fs.writeFileSync(root + '/tracking-contract.json', JSON.stringify({ schema: 'wt-data-layer-contract.v1', tracking: fixture.tracking }, null, 2) + '\n');
const fields = fixture.formSlots[0].fields.map(field => '<label>' + esc(field.id) + '<input name="' + esc(field.id) + '" type="' + esc(field.type) + '" ' + (field.required ? 'required' : '') + '></label>').join('');
const eventList = fixture.tracking.events.map(name => '<li><code>' + esc(name) + '</code></li>').join('');
const page = [
  '<!doctype html><html lang="ja"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow">',
  '<title>', esc(fixture.lp.title), '｜計測契約PoC</title><link rel="stylesheet" href="style.css"><body><main>',
  '<p class="eyebrow">HELIX / LP TRACKING CONTRACT</p><h1>', esc(fixture.lp.title), '</h1>',
  '<p class="lead">LP専用のフォーム配置と、表示・スクロール・CTA・送信を同じversion付きデータ層契約へ束ねる。</p>',
  '<p class="boundary">静的ローカルPoC。入力は保存せず、外部送信・最適化・判定ロジック・WordPress 7.2実接続は行わない。</p>',
  '<section class="hero" data-pattern="lp-hero"><p>LP ID <code>', esc(fixture.lp.id), '</code> / variation <code>', esc(fixture.lp.variationId), '</code> / variant <code>', esc(fixture.lp.variantId), '</code></p><a class="cta" href="#consultation-form" data-helix-cta>フォームへ進む</a></section>',
  '<section data-pattern="lp-proof"><h2>計測対象を確認する</h2><p>表示・スクロール・CTAクリック・フォーム送信の全イベントは、目標CV IDとvariant IDを必須属性としてローカルdata layerへ記録する。</p><ul>', eventList, '</ul></section>',
  '<section data-pattern="lp-form" id="consultation-form"><h2>相談フォーム</h2><p>フォームslot <code>', esc(fixture.formSlots[0].id), '</code> / 配置 <code>', esc(fixture.formSlots[0].placement), '</code></p>',
  '<form data-lp-form="', esc(fixture.formSlots[0].id), '" action="#form-result" method="post">', fields, '<button type="submit">送信イベントを記録</button></form><p id="form-result" role="status">まだ送信していません。</p></section>',
  '<section><h2>ローカルイベント</h2><pre id="event-log" aria-live="polite">[]</pre></section>',
  '<footer>version <code>', esc(fixture.tracking.version), '</code> / destination <code>', esc(fixture.tracking.destination), '</code> / optimization owner <code>', esc(fixture.tracking.optimizationOwner), '</code></footer>',
  '</main><script type="module" src="tracking.mjs"></script></body></html>\n',
].join('');
fs.writeFileSync(root + '/index.html', page);
fs.writeFileSync(root + '/style.css', ':root{color-scheme:light;--ink:#18242b;--muted:#587078;--paper:#f7f5ef;--panel:#fff;--line:#d4e0dc;--accent:#176b61;--soft:#e6f1ed}*{box-sizing:border-box}body{margin:0;background:var(--paper);color:var(--ink);font:16px/1.7 system-ui,sans-serif}main{max-width:880px;margin:auto;padding:clamp(1.25rem,4vw,4rem)}h1{font-size:clamp(2.1rem,5vw,4.4rem);line-height:1.08;margin:.25rem 0 1rem}.eyebrow{font-size:.75rem;letter-spacing:.16em;color:var(--accent);font-weight:700}.lead{font-size:clamp(1.1rem,2vw,1.4rem);max-width:48rem;color:#304c53}.boundary{padding:1rem 1.2rem;border-left:4px solid var(--accent);background:var(--soft);color:#304c53}section{margin-top:clamp(2rem,5vw,4rem);padding:1.25rem;background:var(--panel);border:1px solid var(--line)}.hero{background:#eaf1f5;border-color:#cfdee5}.cta,button{display:inline-flex;align-items:center;min-height:44px;padding:.65rem 1rem;border:2px solid var(--accent);border-radius:.35rem;background:var(--accent);color:#fff;font-weight:700;text-decoration:none;cursor:pointer}label{display:grid;gap:.25rem;margin:.8rem 0;font-weight:650}input{width:100%;max-width:100%;min-height:44px;border:1px solid #8da5a2;border-radius:.25rem;padding:.55rem;font:inherit}pre{min-height:7rem;max-height:18rem;overflow:auto;padding:1rem;background:#16242b;color:#e6f1ed;white-space:pre-wrap}code{font-family:ui-monospace,SFMono-Regular,monospace;font-size:.9em;color:#173c49}footer{margin-top:3rem;color:var(--muted);font-size:.85rem}@media(max-width:620px){main{padding:1.25rem}section{padding:1rem}}\n');
