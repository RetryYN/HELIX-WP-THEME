import fs from 'node:fs';
import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import { fixture, project, update, validateContract } from '../docs/research/2026-09-20-author-contract-poc/contract.mjs';

const root = 'docs/research/2026-09-20-author-contract-poc';
const sourceFiles = [
  'docs/research/2026-09-20-author-contract-poc/contract.mjs',
  'scripts/verify-author-contract-poc.mjs',
  'docs/research/2026-09-20-author-contract-poc/README.md',
  'docs/research/2026-09-20-author-contract-poc/external-observations.md',
  'docs/research/2026-09-05-design-prototype-03/scripts/verify.mjs',
];
const hash = file => createHash('sha256').update(fs.readFileSync(file)).digest('hex');
const rows = [];
const check = (name, fn) => { try { fn(); rows.push({ name, pass: true }); } catch (error) { rows.push({ name, pass: false, details: error instanceof Error ? error.message : String(error) }); } };
const rejects = (name, fn) => check(name, () => assert.throws(fn));

check('AC-AUTHOR-01A projects one canonical update to all visible surfaces', () => {
  validateContract(fixture);
  const changed = update(fixture, { author: { name: '佐藤 綾香（編集長）', credentials: ['編集長'] } });
  const view = project(changed);
  for (const surface of ['authorBox', 'authorArchive']) assert.equal(view[surface].name, '佐藤 綾香（編集長）');
  assert.equal(view.structuredData.Article.author.name, '佐藤 綾香（編集長）');
  assert.equal(view.source, 'config/author-registry.json');
});
check('AC-AUTHOR-01A keeps supervisor data on the same registry projection', () => {
  const view = project(fixture);
  assert.equal(view.supervisorBox.id, fixture.supervisor.id);
  assert.equal(view.structuredData.reviewedBy.url, fixture.supervisor.url);
  assert.equal(view.structuredData.ProfilePage.mainEntity.id, fixture.author.id);
});
rejects('AC-AUTHOR-01B rejects split or incomplete person values', () => {
  const changed = update(fixture, { author: { name: '' } });
  validateContract(changed);
});
rejects('AC-AUTHOR-01B rejects a theme-owned decision or alternate source', () => {
  const changed = { ...fixture, source: 'theme-generated', policy: { ...fixture.policy, themeGeneratesDecision: true } };
  validateContract(changed);
});

check('AC-AUTHOR-02A binds Article.author, reviewedBy, and ProfilePage to the canonical people', () => {
  const view = project(fixture);
  assert.equal(view.structuredData.Article.author.id, fixture.author.id);
  assert.equal(view.structuredData.reviewedBy.id, fixture.supervisor.id);
  assert.equal(view.structuredData.ProfilePage.mainEntity.id, fixture.author.id);
  assert.equal(view.structuredData.ProfilePage.image, fixture.author.image);
});
rejects('AC-AUTHOR-02B rejects mismatched structured-data identity', () => {
  const changed = { ...fixture, supervisor: { ...fixture.supervisor, id: fixture.author.id } };
  project(changed);
});
rejects('AC-AUTHOR-02B rejects an Organization as the review author', () => {
  const changed = { ...fixture, policy: { ...fixture.policy, reviewedByType: 'Organization', reviewAuthorOrganization: true } };
  validateContract(changed);
});
rejects('AC-AUTHOR-02B rejects a profile without representative image', () => {
  const changed = { ...fixture, author: { ...fixture.author, image: '' } };
  project(changed);
});

check('contract has no transport, model, or decision invocation', () => {
  const source = fs.readFileSync('docs/research/2026-09-20-author-contract-poc/contract.mjs', 'utf8');
  for (const forbidden of ['fetch(', 'sendBeacon(', 'XMLHttpRequest', 'openai', 'anthropic', 'model.call', 'wp_remote_']) assert(!source.includes(forbidden), forbidden);
});

const report = {
  schema: 'wt-author-contract-poc-verification.v1',
  completed: rows.every(row => row.pass),
  sourceDigests: Object.fromEntries(sourceFiles.map(file => [file, hash(file)])),
  rows,
  shots: [
    { id: 'avatar-bio', device: 'pc', file: '../2026-09-05-design-prototype-03/results/tail-author-avatar-bio-pc.jpg', sha256: hash('docs/research/2026-09-05-design-prototype-03/results/tail-author-avatar-bio-pc.jpg') },
    { id: 'avatar-bio', device: 'sp', file: '../2026-09-05-design-prototype-03/results/tail-author-avatar-bio-sp.jpg', sha256: hash('docs/research/2026-09-05-design-prototype-03/results/tail-author-avatar-bio-sp.jpg') },
    { id: 'supervisor', device: 'pc', file: '../2026-09-05-design-prototype-03/results/tail-author-supervisor-pc.jpg', sha256: hash('docs/research/2026-09-05-design-prototype-03/results/tail-author-supervisor-pc.jpg') },
    { id: 'supervisor', device: 'sp', file: '../2026-09-05-design-prototype-03/results/tail-author-supervisor-sp.jpg', sha256: hash('docs/research/2026-09-05-design-prototype-03/results/tail-author-supervisor-sp.jpg') },
  ],
  scope: '著者・監修者正本から表示欄、アーカイブ、Article.author、reviewedBy、ProfilePageへ投影する静的契約。',
  remaining: ['WordPress 7.2 管理画面保存、REST/MCP 往復、実サイト構造化データ出力、検索適格性と全ブラウザ検証は未接続。'],
};
fs.writeFileSync(`${root}/verification.json`, `${JSON.stringify(report, null, 2)}\n`);
const names = rows.map(row => row.name);
fs.writeFileSync(`${root}/acceptance-candidate.json`, `${JSON.stringify({
  schema: 'wt-acceptance-candidate.v1',
  'WT-AC-AUTHOR-01A': { status: 'partial', scope: '著者・監修者の正本を一度更新すると、表示欄・アーカイブ・構造化データへ同じ値を投影する静的契約を検証する。', remaining: report.remaining, proofs: [{ path: `${root}/verification.json`, row_names: names.filter(name => name.startsWith('AC-AUTHOR-01A')) }] },
  'WT-AC-AUTHOR-01B': { status: 'partial', scope: '値の分散、未反映、テーマ側の判定生成を負例として拒否する。', remaining: report.remaining, proofs: [{ path: `${root}/verification.json`, row_names: names.filter(name => name.startsWith('AC-AUTHOR-01B')) }] },
  'WT-AC-AUTHOR-02A': { status: 'partial', scope: 'Article.author、reviewedBy、ProfilePage が表示欄と同じ著者・監修者正本を参照し、代表画像を保持する。', remaining: report.remaining, proofs: [{ path: `${root}/verification.json`, row_names: names.filter(name => name.startsWith('AC-AUTHOR-02A')) }] },
  'WT-AC-AUTHOR-02B': { status: 'partial', scope: '構造化データの不一致、Organization の監修者化、代表画像欠落を負例として拒否する。', remaining: report.remaining, proofs: [{ path: `${root}/verification.json`, row_names: names.filter(name => name.startsWith('AC-AUTHOR-02B')) }] },
}, null, 2)}\n`);
fs.writeFileSync(`${root}/catalog-candidates.json`, `${JSON.stringify({
  schema: 'wt-author-contract-catalog-candidates.v1',
  entries: [
    { id: 'author-contract:canonical-person', face: 'article', part: 'article-tail-author', label: '著者欄：正本と表示の一致', variant: 'avatar-bio', description: '著者の名前・経歴・資格・sameAs・画像を単一正本から記事末・アーカイブ・構造化データへ投影する静的契約PoC。', purpose: '信頼・納得をつくる', group: 'ページ・本文', images: { pc: '../2026-09-05-design-prototype-03/results/tail-author-avatar-bio-pc.jpg', sp: '../2026-09-05-design-prototype-03/results/tail-author-avatar-bio-sp.jpg' }, requirementIds: ['WT-FR-AUTHOR-01', 'WT-FR-AUTHOR-02'], referenceId: 'wt-author-contract.v1', evidence: '../2026-09-20-author-contract-poc/verification.json', selectionFacts: { '対象と判断': '同じ人物正本を表示欄・アーカイブ・構造化データで共有する。', '実測': '名前・経歴・資格・url・sameAs・代表画像を一回の更新から投影。', '未検証': report.remaining.join(' ') } },
    { id: 'author-contract:supervisor', face: 'article', part: 'article-tail-author', label: '監修者欄：reviewedByの正本', variant: 'supervisor', description: '監修者の人物正本を記事表示とreviewedByへ投影し、著者正本と混同しない静的契約PoC。', purpose: '信頼・納得をつくる', group: 'ページ・本文', images: { pc: '../2026-09-05-design-prototype-03/results/tail-author-supervisor-pc.jpg', sp: '../2026-09-05-design-prototype-03/results/tail-author-supervisor-sp.jpg' }, requirementIds: ['WT-FR-AUTHOR-01', 'WT-FR-AUTHOR-02'], referenceId: 'wt-author-contract.v1', evidence: '../2026-09-20-author-contract-poc/verification.json', selectionFacts: { '対象と判断': '著者と監修者を別人物正本として表示する。', '実測': 'reviewedByの人物型、著者正本とのID分離、Organization混入拒否。', '未検証': report.remaining.join(' ') } },
  ],
}, null, 2)}\n`);
console.log(JSON.stringify({ completed: report.completed, tests: rows.length, screenshots: report.shots.length, acceptance: '4 partial candidates' }));
if (!report.completed) process.exitCode = 1;
