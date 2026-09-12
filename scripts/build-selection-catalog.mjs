import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { createHash } from 'node:crypto';
import { execFileSync } from 'node:child_process';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const prototype = 'docs/research/2026-09-05-design-prototype-03';
const read = p => JSON.parse(fs.readFileSync(path.join(root, p), 'utf8'));
const index = read(`${prototype}/CATALOG-INDEX.json`);
const glossary = read(`${prototype}/CATALOG-GLOSSARY.json`);
const ir = read('docs/requirements/l3/requirements-ir.json');
execFileSync(process.execPath, [path.join(root, 'scripts/audit-catalog-evidence.mjs')], { cwd: root, stdio: 'inherit' });
const audit = read('docs/research/2026-09-08-selection-catalog/acceptance-audit.json');

// Association is for discovery only. It is never an acceptance or completeness claim.
const families = {
  SEARCH: ['site-search'], HOME: ['home-'], EVENT: ['event-'], FORM: ['form-', 'lp-form', 'event-apply'],
  PARTS: ['header', 'footer-', 'side-', 'chrome-', 'home-hero'],
  LOOK: ['h2', 'h3', 'box', 'cta', 'axis-', 'contrast-guard', 'width', 'graph'],
  VOCAB: ['vocabulary-', 'box', 'cta', 'table', 'toc', 'pr-notice', 'linkcard', 'pros-cons', 'review-bar'],
  LP: ['lp-', 'content-lp'], RECO: ['related', 'category-ranking'], META: ['eyecatch', 'toc', 'share', 'side-'],
  ZONE: ['zone-', 'chrome-', 'side-', 'footer-above'], SP: ['side-sp', 'header', 'table', 'chrome-fix'],
  PAGE: ['page-', 'home-', 'site-'], BANNER: ['footer-above', 'side-set'],
  SNS: ['share', 'article-tail-share', 'footer-extra', 'lp-line'],
  AUTHOR: ['article-tail-author'], TPL: ['404'],
  PAID: ['content-paid'], INTERVIEW: ['content-interview'], BLP: ['content-blp'],
  LEARN: ['content-learning', 'chrome-content-content_learning'],
};
const purpose = part => /form-|event-apply/.test(part) ? '手続きを支える'
  : /cta|fixed|lp-|chrome-fix/.test(part) ? '行動につなげる'
  : /header|footer|side-|category|related|toc|chrome/.test(part) ? '迷わず案内する'
  : /author|review|pros-cons|graph/.test(part) ? '信頼・納得をつくる' : '読みやすく伝える';
const groupName = part => /^(header|footer-|chrome-|side-|width)/.test(part) ? '共通設定・部品' : 'ページ・本文';
const entries = new Map();
for (const shot of index) {
  if (!/^[a-zA-Z0-9_.-]+\.jpg$/.test(shot.file) || !['pc', 'sp'].includes(shot.dev)) throw Error(`Invalid screenshot ${shot.file}`);
  if (!fs.existsSync(path.join(root, prototype, 'results', shot.file))) throw Error(`Missing image ${shot.file}`);
  const id = `${shot.face}:${shot.file.replace(/-(?:pc|sp)\.jpg$/, '')}`;
  const label = glossary.parts[shot.part]?.label || shot.part;
  const desc = glossary.parts[shot.part]?.desc || '同じ目的の候補と並べ、構成・情報量・導線の違いを確認してください。';
  const item = entries.get(id) || { id, face: shot.face, part: shot.part, label, variant: shot.variant, description: desc,
    purpose: purpose(shot.part), group: groupName(shot.part), images: {}, requirementIds: [] };
  if (item.images[shot.dev]) throw Error(`Duplicate device in ${id}`);
  item.images[shot.dev] = `../2026-09-05-design-prototype-03/results/${shot.file}`;
  entries.set(id, item);
}
const contentEvidencePath = 'docs/research/2026-09-08-content-faces/results/verify.json';
let contentEvidence = null;
if (fs.existsSync(path.join(root, contentEvidencePath))) {
  contentEvidence = read(contentEvidencePath);
  if (!contentEvidence.completed || contentEvidence.fail || !contentEvidence.shots.length) throw Error('Content face evidence is incomplete or failed');
  for (const shot of contentEvidence.shots) {
    if (!/^[a-z0-9-]+\.jpg$/.test(shot.file) || !['pc', 'sp'].includes(shot.dev)) throw Error('Invalid content screenshot');
    if (!fs.existsSync(path.join(root, 'docs/research/2026-09-08-content-faces/results', shot.file))) throw Error('Missing content screenshot');
    const face = ['oneoff', 'subscription'].includes(shot.face) ? 'paid' : shot.face;
    const id = `content:${shot.file.replace(/-(?:pc|sp)\.jpg$/, '')}`;
    const label = { paid: '有料記事', interview: 'インタビュー', blp: '理解から相談へ進むBLP', lp: '相談を受け付けるLP' }[face];
    const item = entries.get(id) || { id, face, part: `content-${face}`, label,
      variant: `${shot.face} / ${shot.design} / ${shot.view || (shot.role === 'anonymous' ? 'preview' : 'body')} / ${shot.role}`, purpose: face === 'lp' ? '行動につなげる' : '信頼・納得をつくる', group: 'ページ・本文',
      description: '独立したWordPress検証環境で描画した代表モック。standardとeditorialは同じ本文・画面幅で比較します。購入者本文は対象権限のある検証アカウントでのみ表示されます。',
      images: {}, requirementIds: [], demoRoute: shot.route + (shot.route.includes('?') ? '&' : '?') + 'design=' + shot.design, evidence: '../2026-09-08-content-faces/results/verify.json' };
    item.images[shot.dev] = `../2026-09-08-content-faces/results/${shot.file}`;
    entries.set(id, item);
  }
}
const learningPath = 'docs/research/2026-09-08-content-faces/results/learning/verify.json';
let learningEvidence = null;
if (fs.existsSync(path.join(root, learningPath))) {
  learningEvidence = read(learningPath);
  if (!learningEvidence.completed || learningEvidence.fail) throw Error('Learning verification is incomplete');
  for (const [file, hash] of Object.entries(learningEvidence.sourceDigests)) {
    if (createHash('sha256').update(fs.readFileSync(path.join(root, file))).digest('hex') !== hash) throw Error(`Stale learning evidence: ${file}`);
  }
  for (const shot of learningEvidence.shots) {
    if (!/^[a-z0-9-]+\.jpg$/.test(shot.file) || !['pc', 'sp'].includes(shot.dev)) throw Error('Invalid learning screenshot');
    if (!fs.existsSync(path.join(root, path.dirname(learningPath), shot.file))) throw Error('Missing learning screenshot');
    const id = `learning:${shot.file.replace(/-(?:pc|sp)\.jpg$/, '')}`;
    const label = { 'learning-index': '学習・ヘルプの入口', course: '講座の全体像', lesson: 'レッスンと階層ナビ', glossary: '用語集と索引', help: 'FAQ・ヘルプ', 'learning-empty': '検索のゼロ件案内', 'learning-recovered': '存在しないページからの復帰' }[shot.part];
    const entry = entries.get(id) || { id, face: 'learning', part: 'content-learning', label, variant: shot.part,
      purpose: '学びを支える', group: 'ページ・本文', images: {}, requirementIds: [], demoRoute: shot.route,
      description: 'WordPressの公開学習投稿を表示。階層・前後のレッスン・ページ内目次を分け、用語索引・FAQ・検索とゼロ件からの案内を確認します。',
      evidence: '../2026-09-08-content-faces/results/learning/verify.json' };
    entry.images[shot.dev] = `../2026-09-08-content-faces/results/learning/${shot.file}`;
    entries.set(id, entry);
  }
}
const sitePath = 'docs/research/2026-09-08-content-faces/results/site-pages/verify.json';
let siteEvidence = null;
if (fs.existsSync(path.join(root, sitePath))) {
  siteEvidence = read(sitePath);
  if (!siteEvidence.completed || siteEvidence.rows.some(r => !r.pass)) throw Error('Site page evidence incomplete');
  for (const [file, hash] of Object.entries(siteEvidence.sourceDigests)) {
    if (createHash('sha256').update(fs.readFileSync(path.join(root, file))).digest('hex') !== hash) throw Error(`Stale site evidence: ${file}`);
  }
  for (const shot of siteEvidence.shots) {
    if (!/^[a-z0-9-]+\.jpg$/.test(shot.file) || !['pc', 'sp'].includes(shot.dev)) throw Error('Invalid site screenshot');
    if (!fs.existsSync(path.join(root, path.dirname(sitePath), shot.file))) throw Error('Missing site screenshot');
    const id = `site:${shot.key}`;
    const entry = entries.get(id) || { id, face: 'site', part: `site-${shot.key}`, label: shot.label, variant: shot.key,
      purpose: '事業と利用条件を伝える', group: 'ページ・本文', images: {}, requirementIds: [], demoRoute: shot.route,
      description: '常設案内と規約の代表面。共通事業者設定、目的別の説明、料金の条件、外部受付への引き渡しを確認します。',
      evidence: '../2026-09-08-content-faces/results/site-pages/verify.json' };
    entry.images[shot.dev] = `../2026-09-08-content-faces/results/site-pages/${shot.file}`;
    entries.set(id, entry);
  }
}
const inheritancePath = 'docs/research/2026-09-08-content-faces/results/inheritance/verify.json';
if (fs.existsSync(path.join(root, inheritancePath))) {
  const evidence = read(inheritancePath);
  if (!evidence.completed || evidence.rows.some(r => !r.pass)) throw Error('Content inheritance evidence incomplete');
  for (const [file, hash] of Object.entries(evidence.sourceDigests)) {
    if (createHash('sha256').update(fs.readFileSync(path.join(root, file))).digest('hex') !== hash) throw Error(`Stale inheritance evidence: ${file}`);
  }
  const labels = { content_paid: '有料記事', content_interview: 'インタビュー', content_blp: 'BLP', content_lp: 'LP', content_learning: '学習', content_site: '常設案内' };
  const modes = { site: '共通設定', own: '独自設定', off: '非表示' };
  for (const shot of evidence.shots) {
    if (!labels[shot.face] || !modes[shot.mode] || !['pc', 'sp'].includes(shot.device) || !/^[a-z0-9_-]+\.jpg$/.test(shot.file)) throw Error('Invalid inheritance screenshot');
    if (!fs.existsSync(path.join(root, path.dirname(inheritancePath), shot.file))) throw Error('Missing inheritance screenshot');
    const id = `inheritance:${shot.face}:${shot.mode}`;
    const entry = entries.get(id) || { id, face: 'inheritance', part: `chrome-content-${shot.face}`, label: `${labels[shot.face]} / ${modes[shot.mode]}`, variant: shot.mode,
      purpose: '共通設定と個別設定を選ぶ', group: '共通設定・部品', images: {}, requirementIds: [], demoRoute: shot.route,
      description: '既存の共通ヘッダー・フッター・固定CTA・サイドバーへ接続した代表表示です。共通は右、独自は左のサイドバーを使用し、SPでは本文の後へ置きます。導線の実在性・固定要素の被覆・全状態の同意と認可は未完了です。',
      evidence: '../2026-09-08-content-faces/results/inheritance/verify.json' };
    entry.images[shot.device] = `../2026-09-08-content-faces/results/inheritance/${shot.file}`;
    entries.set(id, entry);
  }
}
const searchPath = 'docs/research/2026-09-08-site-search/results/verify.json';
if (fs.existsSync(path.join(root, searchPath))) {
  const evidence = read(searchPath);
  if (!evidence.completed || evidence.rows.some(r => !r.pass)) throw Error('Site search evidence incomplete');
  for (const [file, hash] of Object.entries(evidence.sourceDigests)) {
    if (createHash('sha256').update(fs.readFileSync(path.join(root, file))).digest('hex') !== hash) throw Error(`Stale search evidence: ${file}`);
  }
  const labels = { results: '検索結果', empty: 'ゼロ件と再検索', blank: '検索を始める' };
  for (const shot of evidence.shots) {
    if (!labels[shot.state] || !['pc', 'sp'].includes(shot.device) || !/^[a-z0-9-]+\.jpg$/.test(shot.file)) throw Error('Invalid search screenshot');
    if (!fs.existsSync(path.join(root, path.dirname(searchPath), shot.file))) throw Error('Missing search screenshot');
    const id = `search:${shot.state}`;
    const entry = entries.get(id) || { id, face: 'search', part: 'site-search', label: labels[shot.state], variant: shot.state,
      purpose: '情報を探し直す', group: 'ページ・本文', images: {}, requirementIds: [], demoRoute: '/?s=' + encodeURIComponent(shot.query),
      description: '標準Query Loopの検索結果・再検索・結果移動の代表再現。公開範囲の全権限行列、絞り込みは未完了。',
      evidence: '../2026-09-08-site-search/results/verify.json' };
    entry.images[shot.device] = `../2026-09-08-site-search/results/${shot.file}`;
    entries.set(id, entry);
  }
}
const searchLocalePath = 'docs/research/2026-09-08-site-search/results/locale.json';
if (fs.existsSync(path.join(root, searchLocalePath))) {
  const evidence = read(searchLocalePath);
  if (!evidence.completed || evidence.locale !== 'ja' || evidence.rows.some(r => !r.pass)) throw Error('Japanese search evidence incomplete');
  for (const [file, hash] of Object.entries(evidence.sourceDigests)) {
    if (createHash('sha256').update(fs.readFileSync(path.join(root, file))).digest('hex') !== hash) throw Error(`Stale localized search evidence: ${file}`);
  }
  for (const shot of evidence.shots) {
    if (!['results', 'empty', 'blank'].includes(shot.state) || !['pc', 'sp'].includes(shot.device) || !/^ja-[a-z-]+\.jpg$/.test(shot.file)) throw Error('Invalid localized search screenshot');
    if (!fs.existsSync(path.join(root, path.dirname(searchLocalePath), shot.file))) throw Error('Missing localized search screenshot');
    const entry = entries.get(`search:${shot.state}`);
    if (!entry) throw Error('Missing source search candidate');
    entry.images[shot.device] = `../2026-09-08-site-search/results/${shot.file}`;
    entry.description = '日本語設定で撮影したサイト検索。結果・ゼロ件から再検索し、キーボードでも移動できます。全権限行列、絞り込みは未完了。実機リンク先の言語は検証環境の現在設定に従います。';
  }
}
const eventStatePath = 'docs/research/2026-09-08-event-state/verify.json';
if (fs.existsSync(path.join(root, eventStatePath))) {
  const evidence = read(eventStatePath);
  if (!evidence.completed || evidence.rows.some(r => !r.pass)) throw Error('Event state evidence incomplete');
  for (const [file, hash] of Object.entries(evidence.sourceDigests)) {
    if (createHash('sha256').update(fs.readFileSync(path.join(root, file))).digest('hex') !== hash) throw Error(`Stale event state evidence: ${file}`);
  }
  const labels = {'before-open':'受付前の案内','opening-boundary':'受付開始と申込','full':'満席と受付状況','deadline-boundary':'締切後の案内'};
  for (const [state, label] of Object.entries(labels)) {
    const id = `event-state:${state}`, images = {};
    for (const device of ['pc','sp']) {
      const file = `${state}-${device}.jpg`;
      if (!fs.existsSync(path.join(root, path.dirname(eventStatePath), file))) throw Error('Missing event state image');
      images[device] = `../2026-09-08-event-state/${file}`;
    }
    entries.set(id, {id, face:'event', part:'event-state-fixture',label,variant:state,images,requirementIds:[],
      purpose:'受付状況と次の行動を伝える',group:'ページ・本文',
      description:'専用fixtureの開始・締切・残席から算出した受付状態。受付不能時はフォームを描画せずPOSTも拒否し、状態確認の導線を残す。業務予約・定員更新・実送信は未実装。',
      evidence:'../2026-09-08-event-state/verify.json'});
  }
}
const footerDataPath = 'docs/research/2026-09-09-footer-data/rendering.json';
if (fs.existsSync(path.join(root, footerDataPath))) {
  const evidence = read(footerDataPath);
  if (!evidence.completed || !evidence.rows.length || evidence.rows.some(r => !r.pass)) throw Error('Footer data evidence incomplete');
  for (const [file, hash] of Object.entries(evidence.sourceDigests)) {
    if (createHash('sha256').update(fs.readFileSync(path.join(root, file))).digest('hex') !== hash) throw Error(`Stale footer data evidence: ${file}`);
  }
  for (const [state, label] of Object.entries({empty:'未登録の案内を省略',filled:'登録済みの案内を表示'})) {
    const images = {};
    for (const [device,width] of Object.entries({pc:1440,sp:375})) {
      const file = `${state}-${width}.png`;
      if (!fs.existsSync(path.join(root,path.dirname(footerDataPath),file))) throw Error('Missing footer data image');
      images[device] = `../2026-09-09-footer-data/${file}`;
    }
    const id = `footer-data:${state}`;
    entries.set(id,{id,face:'article',part:'footer-data-navigation',label,variant:state,images,requirementIds:[],
      purpose:'迷わず案内する',group:'共通設定・部品',
      description:state==='empty'?'保存済みメニューが空のとき、サイトマップのグループと関連サイト欄を省略。リンク登録後の候補と比較できます。':'保存済みメニューを4列のサイトマップと関連サイト欄に表示。スマートフォンではグループを開閉できます。画像は共通の検証用リンクで、グループ別メニューの編集操作は未検証です。',
      evidence:'../2026-09-09-footer-data/rendering.json'});
  }
}
const formProgressPath = 'docs/research/2026-09-12-current-theme-form-progress/verify.json';
if (fs.existsSync(path.join(root, formProgressPath))) {
  const evidence = read(formProgressPath);
  if (!evidence.completed || !Array.isArray(evidence.results) || evidence.results.length !== 4
    || evidence.results.some(r => r.http !== 200 || r.overflow || r.stepCount !== 3 || r.currentCount !== 1
      || r.stepWidth > r.formWidth || r.markerContrast < 4.5 || r.undersizedTargets?.length)) {
    throw Error('Form progress evidence incomplete');
  }
  for (const [file, hash] of Object.entries(evidence.sourceDigests || {})) {
    if (createHash('sha256').update(fs.readFileSync(path.join(root, file))).digest('hex') !== hash) throw Error(`Stale form progress evidence: ${file}`);
  }
  const images = {};
  for (const [device, width] of Object.entries({ pc: 1440, sp: 390 })) {
    const file = `after-${width}-js.png`;
    if (!fs.existsSync(path.join(root, path.dirname(formProgressPath), file))) throw Error('Missing form progress image');
    images[device] = `../2026-09-12-current-theme-form-progress/${file}`;
  }
  entries.set('form-progress:steps', {
    id: 'form-progress:steps', face: 'form', part: 'form-progress', label: '入力ステップの現在位置', variant: 'numbered-rail', images,
    requirementIds: [], purpose: '手続きを支える', group: 'ページ・本文',
    description: '3段階の現在位置を番号と接続線で示す代表表示。PC/SP、JavaScript有効/無効で横溢れ、44px操作寸法、現在位置、コントラストを検証しています。全9種別・全style variation・支援技術実機は未検証です。',
    evidence: '../2026-09-12-current-theme-form-progress/verify.json',
  });
}
const learningOwnershipPath = 'docs/research/2026-09-13-learning-navigation-ownership/verify.json';
if (fs.existsSync(path.join(root, learningOwnershipPath))) {
  const evidence = read(learningOwnershipPath);
  const requiredChecks = [
    'navigation:all-owner-combinations', 'navigation:site-own-style-separated',
    'navigation:off-removes-only-selected-nav', 'navigation:current-and-neighbours',
    'navigation:pc-sp-js-nojs-no-overflow', 'boundary:draft-not-public',
    'boundary:password-protected-not-public', 'boundary:data-provider-denies-nonpublic',
    'owned-fixtures-removed',
  ];
  if (!evidence.completed || evidence.rows?.length !== 44 || requiredChecks.some(name => !evidence.checks?.some(check => check.name === name && check.pass === true))) {
    throw Error('Learning navigation ownership evidence incomplete');
  }
  for (const [file, hash] of Object.entries(evidence.sourceDigests || {})) {
    if (createHash('sha256').update(fs.readFileSync(path.join(root, file))).digest('hex') !== hash) throw Error(`Stale learning navigation ownership evidence: ${file}`);
  }
  const variants = {
    site: ['共通設定の階層・前後ナビ', 'panel / cards'],
    own: ['学習面独自の階層・前後ナビ', 'trail / split'],
    off: ['階層・前後ナビを非表示', 'off'],
  };
  for (const [mode, [label, variant]] of Object.entries(variants)) {
    const images = {};
    for (const [device, width] of Object.entries({ pc: 1440, sp: 390 })) {
      const file = `${mode}-${width}.png`;
      if (!fs.existsSync(path.join(root, path.dirname(learningOwnershipPath), file))) throw Error(`Missing learning ownership image: ${file}`);
      images[device] = `../2026-09-13-learning-navigation-ownership/${file}`;
    }
    entries.set(`learning-navigation:${mode}`, {
      id: `learning-navigation:${mode}`, face: 'learning', part: 'content-learning-navigation', label, variant, images,
      requirementIds: [], purpose: '学びの現在位置と次の行動を示す', group: '共通設定・部品',
      description: mode === 'off'
        ? '階層と前後ナビだけを除き、学習本文と講座内レッスン一覧を維持する代表表示。公開・下書き・パスワード保護の境界も別途実測しています。'
        : `${mode === 'site' ? 'サイト共通' : '学習面独自'}の所有権と見た目を組み合わせた代表表示。階層と前後ナビを個別に切り替え、PC/SP・JavaScript有効/無効で検証しています。`,
      evidence: '../2026-09-13-learning-navigation-ownership/verify.json',
    });
  }
}
const notfoundRecoveryPath = 'docs/research/2026-09-13-notfound-recovery/verify.json';
if (fs.existsSync(path.join(root, notfoundRecoveryPath))) {
  const evidence = read(notfoundRecoveryPath);
  const requiredChecks = [
    '404:all-three-variants-pc-sp-js-nojs', '404:status-and-noindex',
    '404:cv-lp-comparison-contact-visible', '404:all-main-targets-44px',
    '404:body-16px-no-overflow', '404:suggestion-malformed-and-empty-path-safe',
  ];
  if (!evidence.completed || evidence.rows?.length !== 12
    || requiredChecks.some(name => !evidence.checks?.some(check => check.name === name && check.pass === true))) {
    throw Error('404 recovery evidence incomplete');
  }
  for (const [file, hash] of Object.entries(evidence.sourceDigests || {})) {
    if (createHash('sha256').update(fs.readFileSync(path.join(root, file))).digest('hex') !== hash) throw Error(`Stale 404 recovery evidence: ${file}`);
  }
  const variants = {
    popular: ['人気記事から探し直す404', '人気記事'],
    cta: ['目的別の入口から戻る404', 'CTA'],
    suggest: ['URLから候補を提案する404', '検索語提案'],
  };
  for (const [variant, [label, variantLabel]] of Object.entries(variants)) {
    const images = {};
    for (const [device, width] of Object.entries({ pc: 1440, sp: 390 })) {
      const file = `${variant}-${width}.png`;
      if (!fs.existsSync(path.join(root, path.dirname(notfoundRecoveryPath), file))) throw Error(`Missing 404 recovery image: ${file}`);
      images[device] = `../2026-09-13-notfound-recovery/${file}`;
    }
    entries.set(`notfound-recovery:${variant}`, {
      id: `notfound-recovery:${variant}`, face: '404', part: '404-recovery', label, variant: variantLabel, images,
      requirementIds: [], purpose: '迷わず探し直せるようにする', group: 'ページ・本文',
      description: 'HTTP 404とnoindexを保ちながら、検索、カテゴリ、比較記事・LP・問い合わせへの共通導線を示す代表表示。PC/SP・JavaScript有効/無効で実測しています。',
      evidence: '../2026-09-13-notfound-recovery/verify.json',
    });
  }
}
const zoneSlotsPath = 'docs/research/2026-09-13-zone-slots/verification.json';
if (fs.existsSync(path.join(root, zoneSlotsPath))) {
  const evidence = read(zoneSlotsPath);
  const requiredChecks = [
    'pc-js:placement-order', 'pc-js:device-difference', 'pc-js:opposite-heavy-absent',
    'sp-js:placement-order', 'sp-js:device-difference', 'sp-js:opposite-heavy-absent',
    'empty-dom-false', 'empty-dom-true', 'fixture-cleanup',
  ];
  if (!evidence.completed || evidence.checks?.length !== 35
    || requiredChecks.some(name => !evidence.checks?.some(check => check.name === name && check.pass === true))) {
    throw Error('ZONE slot evidence incomplete');
  }
  for (const [file, hash] of Object.entries(evidence.sourceDigests || {})) {
    if (createHash('sha256').update(fs.readFileSync(path.join(root, file))).digest('hex') !== hash) throw Error(`Stale ZONE slot evidence: ${file}`);
  }
  entries.set('zone-slots:device-selection', {
    id: 'zone-slots:device-selection', face: 'zone', part: 'zone-slot-selection',
    label: '共通slotとPC/SP差分', variant: 'server-rendered',
    images: { pc: '../2026-09-13-zone-slots/pc-js.png', sp: '../2026-09-13-zone-slots/sp-js.png' },
    requirementIds: [], purpose: '配置する案内を端末ごとに選ぶ', group: '共通設定・部品',
    description: '共通宣言を端末差分で置換し、非選択の重い面と空slotをHTMLへ残さない専用カタログPoC。通常テンプレ、Site Editor、設定schema、キャッシュ分離は未完了です。',
    evidence: '../2026-09-13-zone-slots/verification.json',
  });
}
const partsDeclarationPath = 'docs/research/2026-09-13-parts-declaration/verification.json';
if (fs.existsSync(path.join(root, partsDeclarationPath))) {
  const evidence = read(partsDeclarationPath);
  const requiredChecks = [
    'initial:saved-declaration-roundtrip', 'updated:saved-declaration-roundtrip',
    'initial-pc-js:selected-reference-visible', 'initial-sp-js:selected-reference-visible',
    'updated-pc-js:selected-reference-visible', 'updated-sp-js:selected-reference-visible',
    'missingReference:validator-rejects', 'missingReference:public-rejects',
    'missingDevice:validator-rejects', 'invalidType:validator-rejects',
    'traversal:validator-rejects', 'extraKey:validator-rejects', 'fixture-cleanup',
  ];
  if (!evidence.completed || evidence.checks?.length !== 77
    || requiredChecks.some(name => !evidence.checks?.some(check => check.name === name && check.pass === true))) {
    throw Error('PARTS declaration evidence incomplete');
  }
  for (const [file, hash] of Object.entries(evidence.sourceDigests || {})) {
    if (createHash('sha256').update(fs.readFileSync(path.join(root, file))).digest('hex') !== hash) throw Error(`Stale PARTS declaration evidence: ${file}`);
  }
  const variants = {
    initial: ['共通ヘッダー＋SP中央型', 'PCは共通 / SP差分'],
    updated: ['共通中央型＋PC帯型', 'PC差分 / SPは共通'],
  };
  for (const [variant, [label, variantLabel]] of Object.entries(variants)) {
    entries.set(`parts-declaration:${variant}`, {
      id: `parts-declaration:${variant}`, face: 'parts', part: 'template-part-device-selection',
      label, variant: variantLabel,
      images: { pc: `../2026-09-13-parts-declaration/${variant}-pc-js.png`, sp: `../2026-09-13-parts-declaration/${variant}-sp-js.png` },
      requirementIds: [], purpose: '共通部品と端末差分を選ぶ', group: '共通設定・部品',
      description: 'core/template-partの共通参照をPC/SP差分で置換する保存・公開PoC。不正宣言は表示前に拒否します。Site Editor操作、テンプレート全体切替、キャッシュ分離は未完了です。',
      evidence: '../2026-09-13-parts-declaration/verification.json',
    });
  }
}
const vocabularyCatalogPath = 'docs/research/2026-09-13-vocabulary-catalog/verification.json';
if (fs.existsSync(path.join(root, vocabularyCatalogPath))) {
  const evidence = read(vocabularyCatalogPath);
  const requiredChecks = [
    'mapping-fourteen-one-receiver', 'six-proposed-plus-one-reserved', 'eighth-slot-rejected',
    'missing-receiver-rejected', 'comparison-conflation-rejected',
    'pc-js:fourteen-visible', 'pc-js:four-sales-visible', 'sp-js:fourteen-visible', 'sp-js:four-sales-visible',
  ];
  if (!evidence.completed || evidence.checks?.length !== 42
    || requiredChecks.some(name => !evidence.checks?.some(check => check.name === name && check.pass === true))) {
    throw Error('Vocabulary catalog evidence incomplete');
  }
  for (const [file, hash] of Object.entries(evidence.sourceDigests || {})) {
    if (createHash('sha256').update(fs.readFileSync(path.join(root, file))).digest('hex') !== hash) throw Error(`Stale vocabulary catalog evidence: ${file}`);
  }
  const variants = {
    overview: ['14語彙と販売系4種', '14語彙・販売4種の全景'],
    box: ['囲みの読み比べ', '上位語彙の表示密度'],
    'product-card': ['商品カード', '販売系の情報と導線'],
    mapping: ['語彙と受け皿の対応表', 'core・style・新規block案'],
  };
  for (const [variant, [label, variantLabel]] of Object.entries(variants)) {
    const suffix = variant === 'overview' ? '' : `-${variant}`;
    entries.set(`vocabulary-catalog:${variant}`, {
      id: `vocabulary-catalog:${variant}`, face: 'article', part: `vocabulary-${variant}`,
      label, variant: variantLabel,
      images: { pc: `../2026-09-13-vocabulary-catalog/pc-js${suffix}.png`, sp: `../2026-09-13-vocabulary-catalog/sp-js${suffix}.png` },
      requirementIds: [], purpose: '記事表現の受け皿を選ぶ', group: 'ページ・本文',
      description: '14語彙の受け皿案と販売系4種を同じ紙面で比較する静的PoC。WordPress登録、編集保存、商品正本、製品全体のblock上限は未完了です。',
      evidence: '../2026-09-13-vocabulary-catalog/verification.json',
    });
  }
}
const vocabularyMediaPath = 'docs/research/2026-09-13-vocabulary-catalog/media/verification.json';
if (fs.existsSync(path.join(root, vocabularyMediaPath))) {
  const evidence = read(vocabularyMediaPath);
  const modes = { icon: '自前SVGアイコン', upload: 'アップロード画像', photo: '写真', number: '番号', none: 'メディアなし' };
  const requiredChecks = Object.keys(modes).flatMap(mode => [
    `pc-js-${mode}:same-body-dom`, `pc-js-${mode}:only-selected-media`,
    `sp-nojs-${mode}:same-body-dom`, `sp-nojs-${mode}:only-selected-media`,
  ]);
  if (!evidence.completed || evidence.checks?.length !== 220
    || requiredChecks.some(name => !evidence.checks?.some(check => check.name === name && check.pass === true))) {
    throw Error('Vocabulary media evidence incomplete');
  }
  for (const [file, hash] of Object.entries(evidence.sourceDigests || {})) {
    if (createHash('sha256').update(fs.readFileSync(path.join(root, file))).digest('hex') !== hash) throw Error(`Stale vocabulary media evidence: ${file}`);
  }
  for (const [mode, label] of Object.entries(modes)) {
    entries.set(`vocabulary-media:${mode}`, {
      id: `vocabulary-media:${mode}`, face: 'article', part: `vocabulary-media-${mode}`,
      label: `メディア枠：${label}`, variant: mode,
      images: { pc: `../2026-09-13-vocabulary-catalog/media/pc-js-${mode}.png`, sp: `../2026-09-13-vocabulary-catalog/media/sp-js-${mode}.png` },
      requirementIds: [], purpose: '本文を変えず伝え方を選ぶ', group: 'ページ・本文',
      description: 'カード・箇条書き・手順の本文構造を保ったまま、メディア枠だけを切り替える静的PoC。WordPressの編集保存と実記事への適用は未完了です。',
      evidence: '../2026-09-13-vocabulary-catalog/media/verification.json',
    });
  }
}
const requirements = ir.requirements.map(r => {
  const family = r.id.split('-').at(-2);
  const prefixes = families[family] || [];
  const related = [...entries.values()].filter(e => prefixes.some(p => e.part.startsWith(p)));
  related.forEach(e => e.requirementIds.push(r.id));
  return { id: r.id, family, statement: r.statement, priority: r.priority, revision: r.revision,
    acceptance: audit.rows.filter(a => a.requirement_id === r.id),
    status: audit.rows.some(a => a.requirement_id === r.id && ['partial', 'verified_in_poc'].includes(a.status)) ? 'partial_poc' : 'not_verified', relatedEntryIds: related.map(e => e.id),
    evidence: '../2026-09-08-selection-catalog/acceptance-audit.json',
    pending: r.pending_resolution || [],
    next: related.length ? '関連画像を起点に全受入条件の再現・実測を確認する' : '操作・状態・契約を含む再現デモと証跡を追加する' };
});
const result = { schema: 'wt-selection-catalog.v1', source: prototype, requirementCount: requirements.length,
  screenshotCount: [...entries.values()].reduce((sum, entry) => sum + Object.keys(entry.images).length, 0), faces: glossary.faces, entries: [...entries.values()], requirements, acceptanceAudit: audit.counts,
  evidenceNote: '関連画像は探すための手掛かりです。全受入条件の再現完了を表しません。' };
const out = path.join(root, 'docs/research/2026-09-08-selection-catalog/catalog-data.json');
fs.mkdirSync(path.dirname(out), { recursive: true });
fs.writeFileSync(out, JSON.stringify(result, null, 2) + '\n');
const readmePath = path.join(root, 'docs/research/2026-09-08-selection-catalog/README.md');
const readme = fs.readFileSync(readmePath, 'utf8');
const currentStart = '<!-- catalog-current:start -->';
const currentEnd = '<!-- catalog-current:end -->';
const currentPattern = new RegExp(`${currentStart}[\\s\\S]*?${currentEnd}`);
if (!currentPattern.test(readme)) throw Error('Missing generated catalog-current block in selection catalog README');
const current = `${currentStart}\n現在の生成結果: ${entries.size}候補 / ${result.screenshotCount}画像 / ${requirements.length}要求 / ${audit.acceptanceCount}受入条件。PoC確認${audit.counts.verified_in_poc}・部分確認${audit.counts.partial}・証跡未対応${audit.counts.missing}・再検証${audit.counts.stale}。全要求完了ではない。\n${currentEnd}`;
fs.writeFileSync(readmePath, readme.replace(currentPattern, current));
console.log(`catalog: ${entries.size} candidates / ${result.screenshotCount} screenshots / ${requirements.length} requirements`);
