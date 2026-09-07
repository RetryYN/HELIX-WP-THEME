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
  HOME: ['home-'], EVENT: ['event-'], FORM: ['form-', 'lp-form', 'event-apply'],
  PARTS: ['header', 'footer-', 'side-', 'chrome-', 'home-hero'],
  LOOK: ['h2', 'h3', 'box', 'cta', 'axis-', 'contrast-guard', 'width', 'graph'],
  VOCAB: ['box', 'cta', 'table', 'toc', 'pr-notice', 'linkcard', 'pros-cons', 'review-bar'],
  LP: ['lp-', 'content-lp'], RECO: ['related', 'category-ranking'], META: ['eyecatch', 'toc', 'share', 'side-'],
  ZONE: ['chrome-', 'side-', 'footer-above'], SP: ['side-sp', 'header', 'table', 'chrome-fix'],
  PAGE: ['page-', 'home-'], BANNER: ['footer-above', 'side-set'],
  SNS: ['share', 'article-tail-share', 'footer-extra', 'lp-line'],
  AUTHOR: ['article-tail-author'], TPL: ['404'],
  PAID: ['content-paid'], INTERVIEW: ['content-interview'], BLP: ['content-blp'],
  LEARN: ['content-learning'],
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
    const label = { 'learning-index': '学習・ヘルプの入口', course: '講座の全体像', lesson: 'レッスンと階層ナビ', glossary: '用語集と索引', help: 'FAQ・ヘルプ', 'learning-empty': '検索のゼロ件案内' }[shot.part];
    const entry = entries.get(id) || { id, face: 'learning', part: 'content-learning', label, variant: shot.part,
      purpose: '学びを支える', group: 'ページ・本文', images: {}, requirementIds: [], demoRoute: shot.route,
      description: 'WordPressの公開学習投稿を表示。階層・前後のレッスン・ページ内目次を分け、用語索引・FAQ・検索とゼロ件からの案内を確認します。',
      evidence: '../2026-09-08-content-faces/results/learning/verify.json' };
    entry.images[shot.dev] = `../2026-09-08-content-faces/results/learning/${shot.file}`;
    entries.set(id, entry);
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
  screenshotCount: index.length + (contentEvidence?.shots.length || 0) + (learningEvidence?.shots.length || 0), faces: glossary.faces, entries: [...entries.values()], requirements, acceptanceAudit: audit.counts,
  evidenceNote: '関連画像は探すための手掛かりです。全受入条件の再現完了を表しません。' };
const out = path.join(root, 'docs/research/2026-09-08-selection-catalog/catalog-data.json');
fs.mkdirSync(path.dirname(out), { recursive: true });
fs.writeFileSync(out, JSON.stringify(result, null, 2) + '\n');
console.log(`catalog: ${entries.size} candidates / ${result.screenshotCount} screenshots / ${requirements.length} requirements`);
