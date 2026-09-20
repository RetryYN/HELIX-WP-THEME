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
const discoveryProjection = read('docs/requirements/discovery/candidate-projection.json');
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
const partsEditorPath = 'docs/research/2026-09-13-parts-declaration/editor/verification.json';
if (fs.existsSync(path.join(root, partsEditorPath))) {
  const evidence = read(partsEditorPath);
  const requiredChecks = [
    'client:attribute-registered', 'inspector:store-updated', 'editor:no-invalid-preview',
    'save:database', 'reload:declaration-valid', 'pc-js:public-reference', 'sp-js:public-reference',
    'missingReference:rest-rejected', 'missingDevice:rest-rejected', 'invalidType:rest-rejected',
    'editor:no-runtime-errors', 'cleanup:owned-fixtures',
  ];
  if (!evidence.completed || evidence.checks?.length !== 25
    || requiredChecks.some(name => !evidence.checks?.some(check => check.name === name && check.pass === true))) {
    throw Error('PARTS editor evidence incomplete');
  }
  for (const [file, hash] of Object.entries(evidence.sourceDigests || {})) {
    if (createHash('sha256').update(fs.readFileSync(path.join(root, file))).digest('hex') !== hash) throw Error(`Stale PARTS editor evidence: ${file}`);
  }
  entries.set('parts-declaration:site-editor', {
    id: 'parts-declaration:site-editor', face: 'parts', part: 'header-device-editor',
    label: 'Site Editorで共通・PC・SPを選択', variant: 'Inspector / save / reload',
    images: { pc: '../2026-09-13-parts-declaration/editor/inspector.png' },
    requirementIds: [], purpose: '共通部品と端末差分を編集する', group: '共通設定・部品',
    description: 'core/template-partのInspectorで共通・PC・SP参照を選び、実Save、再読込、公開反映を検証した編集UI。REST保存境界は不正宣言を400で拒否し、既存DB内容を維持します。',
    evidence: '../2026-09-13-parts-declaration/editor/verification.json',
  });
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
const homePath = 'docs/research/2026-09-15-home-completion/verification.json';
if (fs.existsSync(path.join(root, homePath))) {
  const evidence = read(homePath);
  const homeChoices = read('docs/research/2026-09-15-home-completion/choices.json').choices;
  const required = ['fixtures:cleanup', 'source-unchanged', ...homeChoices.flatMap(choice => ['pc-js', 'pc-nojs', 'sp-js', 'sp-nojs'].map(mode => `${mode}:${choice.id}:metadata-section-order`))];
  if (!evidence.completed || evidence.baselinePresentationDisabled || evidence.scenarioCount !== evidence.declaredScenarioCount || evidence.rows.some(row => !row.pass) || required.some(name => !evidence.rows.some(row => row.name === name && row.pass))) throw Error('HOME completion evidence failed or partial');
  for (const [file, hash] of Object.entries(evidence.sourceDigests)) {
    if (createHash('sha256').update(fs.readFileSync(path.join(root, file))).digest('hex') !== hash) throw Error(`Stale HOME evidence: ${file}`);
  }
  for (const choice of homeChoices) {
    const images = {};
    for (const device of ['pc', 'sp']) {
      const shot = evidence.shots.find(s => s.purpose === choice.id && s.device === device);
      if (!shot || !fs.existsSync(path.join(root, 'docs/research/2026-09-15-home-completion', shot.file))) throw Error(`Missing finished HOME ${choice.id}/${device}`);
      if (createHash('sha256').update(fs.readFileSync(path.join(root, 'docs/research/2026-09-15-home-completion', shot.file))).digest('hex') !== shot.sha256) throw Error(`Changed HOME screenshot ${shot.file}`);
      images[device] = `../2026-09-15-home-completion/${shot.file}`;
    }
    entries.set(`home-finished:${choice.id}`, {
      id: `home-finished:${choice.id}`, face: 'home', part: 'home-finished', finished: true,
      label: `完成HOME：${choice.label}`, variant: `${choice.hero} / ${choice.id}`,
      purpose: choice.purpose, description: '既存の目的別構成をheroからfooterまで同じ条件で比較する完成画面PoC。文言・記事・数値は架空。選択メモは本番への適用ではありません。',
      group: 'ページ・本文', images, requirementIds: ['WT-FR-LOOK-01', 'WT-FR-PARTS-03'],
      selectionFacts: { '入口の導線': choice.openingRoute, '情報量': choice.density, '区間の順序': choice.sectionOrder, 'サイドバー開始': choice.sidebarStart, '共通部品の所属': choice.ownership },
      demoRoute: evidence.shots.find(s => s.purpose === choice.id).route,
      evidence: '../2026-09-15-home-completion/verification.json',
    });
  }
}
const eventPath = 'docs/research/2026-09-15-event-completion/verification.json';
if (fs.existsSync(path.join(root, eventPath))) {
  const evidence = read(eventPath);
  const choices = read('docs/research/2026-09-15-event-completion/choices.json').choices;
  const required = ['fixtures:cleanup', 'source-unchanged', ...choices.flatMap(c => ['pc-js', 'pc-nojs', 'sp-js', 'sp-nojs'].map(mode => `${mode}:${c.id}:metadata-section-order`))];
  if (!evidence.completed || evidence.baselinePresentationDisabled || evidence.finishedOnly || evidence.rows.some(r => !r.pass) || required.some(name => !evidence.rows.some(r => r.name === name && r.pass))) throw Error('EVENT completion evidence failed or partial');
  for (const [file, hash] of Object.entries(evidence.sourceDigests)) if (createHash('sha256').update(fs.readFileSync(path.join(root, file))).digest('hex') !== hash) throw Error(`Stale EVENT evidence: ${file}`);
  for (const choice of choices) {
    const images = {};
    for (const device of ['pc', 'sp']) {
      const shot = evidence.shots.find(s => s.purpose === choice.id && s.device === device);
      if (!shot || createHash('sha256').update(fs.readFileSync(path.join(root, 'docs/research/2026-09-15-event-completion', shot.file))).digest('hex') !== shot.sha256) throw Error(`Missing or changed EVENT screenshot ${choice.id}/${device}`);
      images[device] = `../2026-09-15-event-completion/${shot.file}`;
    }
    entries.set(`event-finished:${choice.id}`, {
      id: `event-finished:${choice.id}`, face: 'event', part: 'event-finished', finished: true,
      label: `完成EVENT：${choice.label}`, variant: `${choice.values.event_hero} / ${choice.id}`,
      purpose: choice.purpose, description: '開催情報から申込までの完成構成PoC。人物・会場・本文は架空。外部送信・実予約は含みません。受付前・満席・締切の操作証拠は別fixtureです。',
      group: 'ページ・本文', images, requirementIds: ['WT-FR-EVENT-01', 'WT-FR-LOOK-01', 'WT-FR-PARTS-03', 'WT-FR-FORM-01'],
      selectionFacts: choice.selectionFacts,
      demoRoute: evidence.shots.find(s => s.purpose === choice.id).route,
      evidence: '../2026-09-15-event-completion/verification.json',
    });
  }
}
const bannerPath = 'docs/research/2026-09-16-banner-zone-completion/verification.json';
if (fs.existsSync(path.join(root, bannerPath))) {
  const evidence = read(bannerPath);
  if (!evidence.completed || evidence.rows.some(r => !r.pass) || !evidence.rows.some(r => r.name === 'fixtures:cleanup' && r.pass)) throw Error('Banner zone evidence incomplete');
  for (const [file, hash] of Object.entries(evidence.sourceDigests)) if (createHash('sha256').update(fs.readFileSync(path.join(root, file))).digest('hex') !== hash) throw Error(`Stale banner evidence: ${file}`);
  const facts = {
    notice: ['必要な案内だけを短く伝える', 'ヘッダー直下', '固定', '同じ端末で閉状態を保持'],
    guide: ['読む前に判断材料を案内する', '本文前', '固定', '本文の流れに配置'],
    product: ['商品情報と表示の食い違いを防ぐ', '本文後', '商品IDから派生', '商品側の画像とリンクを共用'],
    advertisement: ['広告の提供元を曖昧にしない', '本文前', '固定', '画像・リンクの直前に広告表示'],
    rotation: ['複数の案内を切り替える', '本文前', '訪問リクエスト単位で切替', '表示中のバナーごとに広告表示'],
    stack: ['補助操作を本文CTAに重ねない', 'ヘッダー直下＋下部', '固定', '設定へのリンク→メニュー→共有'],
  };
  for (const [id, fact] of Object.entries(facts)) {
    const images = {};
    for (const device of ['pc', 'sp']) {
      const shot = evidence.shots.find(s => s.id === id && s.device === device);
      if (!shot || createHash('sha256').update(fs.readFileSync(path.join(root, 'docs/research/2026-09-16-banner-zone-completion', shot.file))).digest('hex') !== shot.sha256) throw Error(`Missing banner screenshot ${id}/${device}`);
      images[device] = `../2026-09-16-banner-zone-completion/${shot.file}`;
    }
    entries.set(`banner-finished:${id}`, { id: `banner-finished:${id}`, face: 'zone', part: 'banner-finished', finished: true, label: `バナー完成比較：${evidence.shots.find(s => s.id === id).label}`, variant: id, images, requirementIds: ['WT-FR-BANNER-01', 'WT-FR-ZONE-03'], purpose: fact[0], group: 'ページ・本文', description: '架空のバナー正本と配置の比較PoC。実配信・同意取得・推奨面積の確定を示すものではありません。', selectionFacts: { '対象と判断': fact[0], '配置': fact[1], '表示方法': fact[2], '状態と帰属': fact[3], '予算': '画像150KB以内・viewport面積60%以内の試験宣言（推奨値未確定）' }, evidence: '../2026-09-16-banner-zone-completion/verification.json' });
  }
}
const devicePath = 'docs/research/2026-09-16-device-vocabulary/verification.json';
if (fs.existsSync(path.join(root, devicePath))) {
  const evidence = read(devicePath);
  if (!evidence.completed || evidence.rows.some(r => !r.pass) || !evidence.rows.some(r => r.name === 'fixture:cleanup' && r.pass)) throw Error('Device vocabulary evidence incomplete');
  for (const [file, hash] of Object.entries(evidence.sourceDigests)) if (createHash('sha256').update(fs.readFileSync(path.join(root, file))).digest('hex') !== hash) throw Error(`Stale device evidence: ${file}`);
  for (const [id, title] of [['compare', '横に比べて、条件を確かめる'], ['read', '一つずつ読んで、相談を決める']]) {
    const images = {};
    for (const device of ['pc', 'sp']) {
      const shot = evidence.shots.find(s => s.id === id && s.device === device);
      if (!shot || createHash('sha256').update(fs.readFileSync(path.join(root, 'docs/research/2026-09-16-device-vocabulary', shot.file))).digest('hex') !== shot.sha256) throw Error(`Missing device screenshot ${id}/${device}`);
      images[device] = `../2026-09-16-device-vocabulary/${shot.file}`;
    }
    entries.set(`device-finished:${id}`, { id: `device-finished:${id}`, face: 'article', part: 'device-finished', finished: true, label: `端末別完成比較：${title}`, variant: id, images, requirementIds: ['WT-FR-SP-03', 'WT-FR-VOCAB-01', 'WT-FR-LOOK-01'], purpose: title, group: 'ページ・本文', description: '同じ架空本文を端末別の読み方で比較するPoC。管理画面・MCPのプレビュー一致は未実証。', selectionFacts: { '対象と判断': title, '比較表': id === 'read' ? 'PC横表・SP項目カード' : 'PC/SPとも横比較', '内容の切替': 'PCタブ・SP見出し開閉', '写真と目次': 'SP横送り・目次開閉、PC一覧', '行動導線': id === 'read' ? 'SPで到達後に固定' : '本文末の全幅ボタン', 'JSなし': '全本文・写真横スクロール・通常フローCTA' }, evidence: '../2026-09-16-device-vocabulary/verification.json' });
  }
}
const recommendationPath = 'docs/research/2026-09-19-recommendation-layouts/verification.json';
if (fs.existsSync(path.join(root, recommendationPath))) {
  const evidence = read(recommendationPath);
  if (!evidence.completed || evidence.rows.some(r => !r.pass) || !evidence.rows.some(r => r.name === 'fixtures:cleanup' && r.pass)) throw Error('Recommendation layout evidence incomplete');
  for (const [file, hash] of Object.entries(evidence.sourceDigests)) if (createHash('sha256').update(fs.readFileSync(path.join(root, file))).digest('hex') !== hash) throw Error(`Stale recommendation evidence: ${file}`);
  for (const [id, label] of [['cards', '写真から次の記事を選ぶ'], ['list', '内容を確かめて次の記事を選ぶ']]) {
    const images = {};
    for (const device of ['pc', 'sp']) {
      const shot = evidence.shots.find(s => s.id === id && s.device === device);
      if (!shot || createHash('sha256').update(fs.readFileSync(path.join(root, 'docs/research/2026-09-19-recommendation-layouts', shot.file))).digest('hex') !== shot.sha256) throw Error(`Missing recommendation screenshot ${id}/${device}`);
      images[device] = `../2026-09-19-recommendation-layouts/${shot.file}`;
    }
    entries.set(`recommendation-layout:${id}`, { id: `recommendation-layout:${id}`, face: 'article', part: 'related-layout', finished: true, label: `記事一覧比較：${label}`, variant: id, images, requirementIds: ['WT-FR-RECO-01'], purpose: label, group: 'ページ・本文', description: '同じ新着記事3件を表示型だけ変えて比較。人気集計・関連記事抽出・管理画面での型切替は未確認。', selectionFacts: { '対象と判断': label, '選択と順序': '公開記事・新着順・3件（両型共通）', 'PC': id === 'cards' ? '2列カード・写真を上に配置' : '写真と説明の横並びリスト', 'SP': id === 'cards' ? '1列・写真の後に本文' : '横メディア行・写真100px／残りに本文', '画像なし': id === 'list' ? '本文が行の全幅を使用' : '空の画像枠を省略', '参照ID': `helix-wt/recommendation-${id}` }, evidence: '../2026-09-19-recommendation-layouts/verification.json' });
  }
}
const eyecatchPath = 'docs/research/2026-09-19-eyecatch-meta/verification.json';
if (fs.existsSync(path.join(root, eyecatchPath))) {
  const evidence = read(eyecatchPath);
  if (!evidence.completed || evidence.rows.some(r => !r.pass) || !evidence.rows.some(r => r.name === 'fixtures:cleanup' && r.pass)) throw Error('Eyecatch meta evidence incomplete');
  for (const [file, hash] of Object.entries(evidence.sourceDigests)) if (createHash('sha256').update(fs.readFileSync(path.join(root, file))).digest('hex') !== hash) throw Error(`Stale eyecatch evidence: ${file}`);
  for (const [id, label] of [['title-image', '題名の後に写真'], ['image-title', '写真の後に題名'], ['hero', '写真に題名を重ねる'], ['side', '題名の横に写真を添える'], ['none', '写真を表示しない']]) {
    const images = {};
    for (const device of ['pc', 'sp']) {
      const shot = evidence.shots.find(s => s.id === id && s.device === device);
      if (!shot || createHash('sha256').update(fs.readFileSync(path.join(root, 'docs/research/2026-09-19-eyecatch-meta', shot.file))).digest('hex') !== shot.sha256) throw Error(`Missing eyecatch screenshot ${id}/${device}`);
      images[device] = `../2026-09-19-eyecatch-meta/${shot.file}`;
    }
    entries.set(`eyecatch-meta:${id}`, { id: `eyecatch-meta:${id}`, face: 'article', part: 'eyecatch-meta', finished: true, label: `記事設定比較：${label}`, variant: id, images, requirementIds: ['WT-FR-META-01'], purpose: '題名と写真の優先順を選ぶ', group: 'ページ・本文', description: '同じ記事・写真で投稿メタから5型を選択。対照記事の不変、未設定時のサイト既定継承、PC/SP・JS有無を実測。管理画面とREST/MCP往復、写真欠損は未確認。', selectionFacts: { '対象と判断': label, '同じ内容': '題名・本文・写真は全型共通', '保存先': `投稿メタ wt_eyecatch = ${id}`, '未設定': 'サイト既定を継承', '他の記事': '対照記事の表示は変わらない', 'SP': id === 'side' ? '題名の後に写真を積む' : label, 'JSなし': '位置と表示を維持' }, evidence: '../2026-09-19-eyecatch-meta/verification.json' });
  }
}
// 既存目次候補を保存設定からの実機画像へ更新し、重複候補を増やさない。
const tocPath = 'docs/research/2026-09-19-toc-settings/verification.json';
if (fs.existsSync(path.join(root, tocPath))) {
  const report = JSON.parse(fs.readFileSync(path.join(root, tocPath), 'utf8'));
  if (!report.completed || report.rows.some(r => !r.pass)) throw Error('TOC settings verification failed');
  for (const [source, digest] of Object.entries(report.sourceDigests)) if (createHash('sha256').update(fs.readFileSync(path.join(root, source))).digest('hex') !== digest) throw Error(`Stale TOC source ${source}`);
  for (const [id, purpose] of [['box', '本文の前に構成を見せる'], ['float', 'サイドバーなしは横レール、ありは本文内'], ['collapsible', '必要なときに目次を開く'], ['none', '本文へ直接読み進める']]) {
    const entry = entries.get(`article:toc-${id}`);
    if (!entry) throw Error(`Missing existing TOC candidate ${id}`);
    for (const device of ['pc', 'sp']) {
      const shot = report.shots.find(s => s.id === id && s.device === device);
      if (!shot || createHash('sha256').update(fs.readFileSync(path.join(root, 'docs/research/2026-09-19-toc-settings', shot.file))).digest('hex') !== shot.sha256) throw Error(`Missing TOC screenshot ${id}/${device}`);
      entry.images[device] = `../2026-09-19-toc-settings/${shot.file}`;
    }
    entry.purpose = purpose;
    entry.label = '目次設定比較：' + ({box:'本文前',float:'フロート／本文内',collapsible:'開閉',none:'非表示'})[id];
    entry.description = '同じ記事の投稿メタに保存した4型を比較。見出し編集への追従、サイト既定継承、対照記事、PC/SP・JS有無・キーボード開閉を実測。ページ種別設定UIとREST/MCPは未確認。';
    entry.selectionFacts = {'対象と判断': purpose, '保存先': `投稿メタ wt_toc = ${id}`, '未設定': 'サイト既定を継承', '内容': 'H2/H3から描画時に導出。H2が3個未満では省略', 'JSなし': '目次の表示と開閉を維持', '他の記事': '対照記事の表示は変わらない'};
    entry.evidence = '../2026-09-19-toc-settings/verification.json';
  }
}
const pricingPath = 'docs/research/2026-09-19-pricing-cards/verification.json';
if (fs.existsSync(path.join(root, pricingPath))) {
  const report = read(pricingPath);
  const candidates = read('docs/research/2026-09-19-pricing-cards/catalog-candidates.json');
  if (!report.completed || report.rows.some(r => !r.pass) || !report.rows.some(r => r.name === 'fixtures:cleanup' && r.pass)) throw Error('Pricing card evidence incomplete');
  for (const [source, expected] of Object.entries(report.sourceDigests)) if (createHash('sha256').update(fs.readFileSync(path.join(root, source))).digest('hex') !== expected) throw Error(`Stale pricing source ${source}`);
  for (const candidate of candidates.entries) {
    const id = candidate.variant;
    for (const device of ['pc', 'sp']) {
      const shot = report.shots.find(item => item.id === id && item.device === device);
      if (!shot || createHash('sha256').update(fs.readFileSync(path.join(root, 'docs/research/2026-09-19-pricing-cards', shot.file))).digest('hex') !== shot.sha256) throw Error(`Missing pricing screenshot ${id}/${device}`);
    }
    entries.set(candidate.id, {...candidate, finished:true, selectionFacts:{'対象と判断':id === 'standard' ? '通常量の3プランを比較' : '説明が長い3プランの境界確認','同じ型':'通常／長文は同じ既存1型。型数を増やさない','PC':'同一行の外枠・見出し・末尾CTAを整列','SP':'1列で全文を省略せず表示','保存先':'固定ページ本文のcore blocks','参照ID':'helix-wt/pricing'}});
  }
}
const headingPath = 'docs/research/2026-09-20-heading-fluid/verification.json';
if (fs.existsSync(path.join(root, headingPath))) {
  const report = read(headingPath);
  const candidates = read('docs/research/2026-09-20-heading-fluid/catalog-candidates.json');
  if (!report.completed || report.rows.some(r => !r.pass) || !report.rows.some(r => r.name === 'fixtures:cleanup' && r.pass)) throw Error('Heading comparison evidence incomplete');
  for (const [source, expected] of Object.entries(report.sourceDigests)) if (createHash('sha256').update(fs.readFileSync(path.join(root, source))).digest('hex') !== expected) throw Error(`Stale heading source ${source}`);
  for (const candidate of candidates.entries) {
    const entry = entries.get(candidate.id);
    if (!entry || !['h2', 'h3'].includes(entry.part)) throw Error(`Unknown heading candidate ${candidate.id}`);
    for (const device of ['pc', 'sp']) {
      const shot = report.shots.find(item => item.id === candidate.id.slice('article:'.length) && item.device === device && item.scale === 1);
      if (!shot || createHash('sha256').update(fs.readFileSync(path.join(root, 'docs/research/2026-09-20-heading-fluid', shot.file))).digest('hex') !== shot.sha256) throw Error(`Missing heading screenshot ${candidate.id}/${device}`);
    }
    Object.assign(entry, candidate);
  }
}
const utilityPath = 'docs/research/2026-09-20-utility-poc/verification.json';
if (fs.existsSync(path.join(root, utilityPath))) {
  const report = read(utilityPath);
  const candidates = read('docs/research/2026-09-20-utility-poc/catalog-candidates.json');
  if (!report.completed || !report.rows.length || report.rows.some(r => !r.pass)) throw Error('Utility PoC evidence incomplete');
  for (const [source, expected] of Object.entries(report.sourceDigests)) if (createHash('sha256').update(fs.readFileSync(path.join(root, source))).digest('hex') !== expected) throw Error(`Stale utility source ${source}`);
  if (JSON.stringify(candidates.entries.map(e => e.id).sort()) !== JSON.stringify(['utility:calculator', 'utility:generator', 'utility:grader'])) throw Error('Utility candidates must cover all three uses');
  for (const candidate of candidates.entries) {
    if (JSON.stringify(candidate.requirementIds) !== JSON.stringify(['WT-FR-UTILITY-01'])) throw Error('Utility requirement mapping mismatch');
    for (const device of ['pc', 'sp']) {
      const shot = report.shots.find(s => s.id === candidate.variant && s.device === device);
      if (!shot || candidate.images[device] !== `../2026-09-20-utility-poc/${shot.file}` || createHash('sha256').update(fs.readFileSync(path.join(root, 'docs/research/2026-09-20-utility-poc', shot.file))).digest('hex') !== shot.sha256) throw Error(`Missing utility screenshot ${candidate.id}/${device}`);
    }
    entries.set(candidate.id, candidate);
  }
}
const adminPath = 'docs/research/2026-09-20-admin-poc/verification.json';
if (fs.existsSync(path.join(root, adminPath))) {
  const report = read(adminPath);
  const candidates = read('docs/research/2026-09-20-admin-poc/catalog-candidates.json');
  if (!report.completed || !report.rows.length || report.rows.some(r => !r.pass)) throw Error('Admin PoC evidence incomplete');
  for (const [source, expected] of Object.entries(report.sourceDigests)) if (createHash('sha256').update(fs.readFileSync(path.join(root, source))).digest('hex') !== expected) throw Error(`Stale admin source ${source}`);
  if (JSON.stringify(candidates.entries.map(e => e.id).sort()) !== JSON.stringify(['admin:article', 'admin:parts', 'admin:site'])) throw Error('Admin candidates must cover all three layers');
  for (const candidate of candidates.entries) {
    if (JSON.stringify(candidate.requirementIds) !== JSON.stringify(['WT-FR-ADMIN-01'])) throw Error('Admin requirement mapping mismatch');
    for (const device of ['pc', 'sp']) {
      const shot = report.shots.find(s => s.id === candidate.variant && s.device === device);
      if (!shot || candidate.images[device] !== `../2026-09-20-admin-poc/${shot.file}` || createHash('sha256').update(fs.readFileSync(path.join(root, 'docs/research/2026-09-20-admin-poc', shot.file))).digest('hex') !== shot.sha256) throw Error(`Missing admin screenshot ${candidate.id}/${device}`);
    }
    entries.set(candidate.id, candidate);
  }
}
const adminChangesPath = 'docs/research/2026-09-20-admin-changes-poc/verification.json';
if (fs.existsSync(path.join(root, adminChangesPath))) {
  const report = read(adminChangesPath);
  const candidates = read('docs/research/2026-09-20-admin-changes-poc/catalog-candidates.json');
  if (!report.completed || !report.rows.length || report.rows.some(r => !r.pass)) throw Error('Admin changes PoC evidence incomplete');
  for (const [source, expected] of Object.entries(report.sourceDigests)) if (createHash('sha256').update(fs.readFileSync(path.join(root, source))).digest('hex') !== expected) throw Error(`Stale admin source ${source}`);
  if (JSON.stringify(candidates.entries.map(e => e.id).sort()) !== JSON.stringify(['admin-changes:blocked', 'admin-changes:recovery', 'admin-changes:review'])) throw Error('Admin changes candidates must cover all three scenarios');
  for (const candidate of candidates.entries) {
    if (JSON.stringify(candidate.requirementIds) !== JSON.stringify(['WT-FR-ADMIN-03'])) throw Error('Admin changes requirement mapping mismatch');
    for (const device of ['pc', 'sp']) {
      const shot = report.shots.find(s => s.id === candidate.variant && s.device === device);
      if (!shot || candidate.images[device] !== `../2026-09-20-admin-changes-poc/${shot.file}` || createHash('sha256').update(fs.readFileSync(path.join(root, 'docs/research/2026-09-20-admin-changes-poc', shot.file))).digest('hex') !== shot.sha256) throw Error(`Missing admin screenshot ${candidate.id}/${device}`);
    }
    entries.set(candidate.id, candidate);
  }
}
const adminKeysPath = 'docs/research/2026-09-20-admin-keys-poc/verification.json';
if (fs.existsSync(path.join(root, adminKeysPath))) {
  const report = read(adminKeysPath);
  const candidates = read('docs/research/2026-09-20-admin-keys-poc/catalog-candidates.json');
  if (!report.completed || !report.rows.length || report.rows.some(r => !r.pass)) throw Error('Admin keys PoC evidence incomplete');
  for (const [source, expected] of Object.entries(report.sourceDigests)) if (createHash('sha256').update(fs.readFileSync(path.join(root, source))).digest('hex') !== expected) throw Error(`Stale admin source ${source}`);
  if (JSON.stringify(candidates.entries.map(e => e.id).sort()) !== JSON.stringify(['admin-keys:readonly', 'admin-keys:revoked', 'admin-keys:writer'])) throw Error('Admin keys candidates must cover all three scenarios');
  for (const candidate of candidates.entries) {
    if (JSON.stringify(candidate.requirementIds) !== JSON.stringify(['WT-FR-ADMIN-04'])) throw Error('Admin keys requirement mapping mismatch');
    for (const device of ['pc', 'sp']) {
      const shot = report.shots.find(s => s.id === candidate.variant && s.device === device);
      if (!shot || candidate.images[device] !== `../2026-09-20-admin-keys-poc/${shot.file}` || createHash('sha256').update(fs.readFileSync(path.join(root, 'docs/research/2026-09-20-admin-keys-poc', shot.file))).digest('hex') !== shot.sha256) throw Error(`Missing admin screenshot ${candidate.id}/${device}`);
    }
    entries.set(candidate.id, candidate);
  }
}
const productSurfacesPath = 'docs/research/2026-09-20-product-surfaces-poc/verification.json';
if (fs.existsSync(path.join(root, productSurfacesPath))) {
  const report = read(productSurfacesPath);
  const candidates = read('docs/research/2026-09-20-product-surfaces-poc/catalog-candidates.json');
  if (!report.completed || !report.rows.length || report.rows.some(r => !r.pass)) throw Error('Product surfaces PoC evidence incomplete');
  for (const [source, expected] of Object.entries(report.sourceDigests)) if (createHash('sha256').update(fs.readFileSync(path.join(root, source))).digest('hex') !== expected) throw Error(`Stale product source ${source}`);
  if (JSON.stringify(candidates.entries.map(e => e.id).sort()) !== JSON.stringify(['product-surfaces:card', 'product-surfaces:comparison', 'product-surfaces:cta', 'product-surfaces:ranking', 'product-surfaces:review'])) throw Error('Product surfaces candidates must cover all five surfaces');
  for (const candidate of candidates.entries) {
    if (JSON.stringify(candidate.requirementIds) !== JSON.stringify(['WT-FR-SELL-02'])) throw Error('Product surfaces requirement mapping mismatch');
    for (const device of ['pc', 'sp']) {
      const shot = report.shots.find(s => s.id === candidate.variant && s.device === device);
      if (!shot || candidate.images[device] !== `../2026-09-20-product-surfaces-poc/${shot.file}` || createHash('sha256').update(fs.readFileSync(path.join(root, 'docs/research/2026-09-20-product-surfaces-poc', shot.file))).digest('hex') !== shot.sha256) throw Error(`Missing product screenshot ${candidate.id}/${device}`);
    }
    entries.set(candidate.id, candidate);
  }
}
const requirements = ir.requirements.map(r => {
  const family = r.id.split('-').at(-2);
  const prefixes = families[family] || [];
  const related = [...entries.values()].filter(e => e.requirementIds.includes(r.id) || prefixes.some(p => e.part.startsWith(p)));
  related.forEach(e => { if (!e.requirementIds.includes(r.id)) e.requirementIds.push(r.id); });
  return { id: r.id, family, statement: r.statement, priority: r.priority, revision: r.revision,
    acceptance: audit.rows.filter(a => a.requirement_id === r.id),
    status: audit.rows.some(a => a.requirement_id === r.id && ['partial', 'verified_in_poc'].includes(a.status)) ? 'partial_poc' : 'not_verified', relatedEntryIds: related.map(e => e.id),
    evidence: '../2026-09-08-selection-catalog/acceptance-audit.json',
    pending: r.pending_resolution || [],
    next: related.length ? '関連画像を起点に全受入条件の再現・実測を確認する' : '操作・状態・契約を含む再現デモと証跡を追加する' };
});

// Requirement rows need a selector-facing state as well as acceptance status.
// A requirement can have verified rows and still have no visual candidate, or
// have candidates while missing evidence. Keep those cases distinct so the
// catalog can prioritize the next useful action without implying completion.
const coverageForRequirement = requirement => {
  const counts = Object.fromEntries(['verified_in_poc', 'partial', 'missing', 'stale'].map(status => [status, 0]));
  for (const row of requirement.acceptance) counts[row.status] = (counts[row.status] || 0) + 1;
  const openAcceptanceIds = requirement.acceptance
    .filter(row => ['missing', 'stale'].includes(row.status))
    .map(row => row.id)
    .sort();
  const selectionState = !requirement.relatedEntryIds.length
    ? 'no_candidate'
    : openAcceptanceIds.length
      ? 'candidate_with_open_acceptance'
      : counts.partial
        ? 'candidate_with_partial_evidence'
        : 'candidate_verified';
  return {
    candidateCount: requirement.relatedEntryIds.length,
    acceptanceCount: requirement.acceptance.length,
    counts,
    openAcceptanceIds,
    selectionState,
  };
};
for (const requirement of requirements) requirement.coverage = coverageForRequirement(requirement);
// Candidates are selected by people and agents, so expose the evidence boundary
// next to every candidate.  The related requirement list alone cannot tell a
// selector whether a candidate is merely photographed, partially verified, or
// ready for a PoC decision.  Keep this derived metadata separate from the
// acceptance registry: it is a navigation aid, never an acceptance claim.
const auditByRequirement = new Map();
for (const row of audit.rows) {
  const rows = auditByRequirement.get(row.requirement_id) || [];
  rows.push(row);
  auditByRequirement.set(row.requirement_id, rows);
}
const coverageFor = entry => {
  const rows = entry.requirementIds.flatMap(id => auditByRequirement.get(id) || []);
  const counts = Object.fromEntries(['verified_in_poc', 'partial', 'missing', 'stale'].map(status => [status, 0]));
  for (const row of rows) counts[row.status] = (counts[row.status] || 0) + 1;
  const status = counts.stale ? 'stale' : counts.missing ? (counts.verified_in_poc || counts.partial ? 'partial' : 'missing')
    : counts.partial ? 'partial' : counts.verified_in_poc ? 'verified_in_poc' : 'unmapped';
  const openAcceptanceIds = rows.filter(row => ['missing', 'stale'].includes(row.status)).map(row => row.id).sort();
  const next = entry.requirementIds.map(id => requirements.find(requirement => requirement.id === id)?.next).find(Boolean)
    || (status === 'unmapped' ? '要求との対応付けを確認する' : '受入条件の検証範囲を確認する');
  return {
    status,
    acceptanceCount: rows.length,
    counts,
    openAcceptanceIds,
    next,
  };
};
const result = { schema: 'wt-selection-catalog.v1', source: prototype, requirementCount: requirements.length,
  screenshotCount: [...entries.values()].reduce((sum, entry) => sum + Object.keys(entry.images).length, 0), faces: { ...glossary.faces, utility: '対話型ユーティリティ面: 入力から派生結果と根拠を確かめ、再入力する面。ローカルPoC。' }, entries: [...entries.values()], requirements, acceptanceAudit: audit.counts,
  evidenceNote: '関連画像は探すための手掛かりです。全受入条件の再現完了を表しません。' };
const out = path.join(root, 'docs/research/2026-09-08-selection-catalog/catalog-data.json');
fs.mkdirSync(path.dirname(out), { recursive: true });
fs.writeFileSync(out, JSON.stringify(result, null, 2) + '\n');
const selectionIndex = {
  schema: 'wt-selection-index.v1',
  source: 'catalog-data.json',
  generatedAtEventHead: discoveryProjection.event_head,
  candidateCount: entries.size,
  requirements: requirements.map(requirement => ({
    id: requirement.id,
    priority: requirement.priority,
    status: requirement.status,
    coverage: requirement.coverage,
    acceptance: requirement.acceptance.map(row => ({ id: row.id, status: row.status })),
    relatedEntryIds: requirement.relatedEntryIds,
  })),
  entries: [...entries.values()].map(entry => ({
    id: entry.id,
    face: entry.face,
    part: entry.part,
    label: entry.label,
    variant: entry.variant,
    purpose: entry.purpose,
    group: entry.group,
    requirementIds: entry.requirementIds,
    selectionCoverage: coverageFor(entry),
    evidence: entry.evidence || null,
    devices: Object.keys(entry.images).sort(),
  })),
};
fs.writeFileSync(path.join(path.dirname(out), 'selection-index.json'), JSON.stringify(selectionIndex, null, 2) + '\n');
const readmePath = path.join(root, 'docs/research/2026-09-08-selection-catalog/README.md');
const readme = fs.readFileSync(readmePath, 'utf8');
const currentStart = '<!-- catalog-current:start -->';
const currentEnd = '<!-- catalog-current:end -->';
const currentPattern = new RegExp(`${currentStart}[\\s\\S]*?${currentEnd}`);
if (!currentPattern.test(readme)) throw Error('Missing generated catalog-current block in selection catalog README');
const current = `${currentStart}\n現在の生成結果: ${entries.size}候補 / ${result.screenshotCount}画像 / ${requirements.length}要求 / ${audit.acceptanceCount}受入条件。PoC確認${audit.counts.verified_in_poc}・部分確認${audit.counts.partial}・証跡未対応${audit.counts.missing}・再検証${audit.counts.stale}。全要求完了ではない。\n${currentEnd}`;
fs.writeFileSync(readmePath, readme.replace(currentPattern, current));
console.log(`catalog: ${entries.size} candidates / ${result.screenshotCount} screenshots / ${requirements.length} requirements`);
// catalog-data を書き出した後に、同じ時点の要求・証跡・候補から完遂backlogも再生成する。
await import('./build-catalog-completion-backlog.mjs');
