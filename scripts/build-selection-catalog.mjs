import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const prototype = 'docs/research/2026-09-05-design-prototype-03';
const read = p => JSON.parse(fs.readFileSync(path.join(root, p), 'utf8'));
const index = read(`${prototype}/CATALOG-INDEX.json`);
const glossary = read(`${prototype}/CATALOG-GLOSSARY.json`);
const ir = read('docs/requirements/l3/requirements-ir.json');
const ac = read('docs/requirements/l3/acceptance-cases.json');

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
const requirements = ir.requirements.map(r => {
  const family = r.id.split('-').at(-2);
  const prefixes = families[family] || [];
  const related = [...entries.values()].filter(e => prefixes.some(p => e.part.startsWith(p)));
  related.forEach(e => e.requirementIds.push(r.id));
  return { id: r.id, family, statement: r.statement, priority: r.priority, revision: r.revision,
    acceptance: ac.cases.filter(a => a.requirement_id === r.id),
    status: contentEvidence && ['PAID', 'INTERVIEW', 'BLP'].includes(family) ? 'partial_poc' : 'not_verified', relatedEntryIds: related.map(e => e.id),
    evidence: contentEvidence && ['PAID', 'INTERVIEW', 'BLP'].includes(family) ? '../2026-09-08-content-faces/results/verify.json' : null,
    pending: r.pending_resolution || [],
    next: related.length ? '関連画像を起点に全受入条件の再現・実測を確認する' : '操作・状態・契約を含む再現デモと証跡を追加する' };
});
const result = { schema: 'wt-selection-catalog.v1', source: prototype, requirementCount: requirements.length,
  screenshotCount: index.length + (contentEvidence?.shots.length || 0), faces: glossary.faces, entries: [...entries.values()], requirements,
  evidenceNote: '関連画像は探すための手掛かりです。全受入条件の再現完了を表しません。' };
const out = path.join(root, 'docs/research/2026-09-08-selection-catalog/catalog-data.json');
fs.mkdirSync(path.dirname(out), { recursive: true });
fs.writeFileSync(out, JSON.stringify(result, null, 2) + '\n');
console.log(`catalog: ${entries.size} candidates / ${result.screenshotCount} screenshots / ${requirements.length} requirements`);
