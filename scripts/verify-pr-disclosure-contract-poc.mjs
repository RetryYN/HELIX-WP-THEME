import fs from 'node:fs';
import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import { copy, fixture, hasCommercialSignal, hasExistingDisclosure, readBack, resolveNotice, validateContract } from '../docs/research/2026-09-20-pr-disclosure-contract-poc/contract.mjs';

const root = 'docs/research/2026-09-20-pr-disclosure-contract-poc';
const sourceFiles = [
  'docs/research/2026-09-20-pr-disclosure-contract-poc/contract.mjs',
  'scripts/verify-pr-disclosure-contract-poc.mjs',
  'docs/research/2026-09-20-pr-disclosure-contract-poc/README.md',
  'docs/research/2026-09-20-pr-disclosure-contract-poc/external-observations.md',
  'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/functions.php',
  'docs/research/2026-09-05-design-prototype-03/scripts/verify.mjs',
];
const hash = file => createHash('sha256').update(fs.readFileSync(file)).digest('hex');
const rows = [];
const check = (name, action) => {
  try { action(); rows.push({ name, pass: true }); }
  catch (error) { rows.push({ name, pass: false, details: error instanceof Error ? error.message : String(error) }); }
};
const rejects = (name, action) => check(name, () => assert.throws(action));

check('AC-VOCAB-03A accepts automatic commercial signal detection', () => {
  validateContract(fixture);
  for (const signal of ['affiliate-link', 'product-link', 'ad-creative']) assert.equal(hasCommercialSignal({ signals: [signal] }), true);
  assert.equal(resolveNotice({ signals: ['affiliate-link'], body: '<p>比較記事です。</p>' }).text, fixture.defaultText);
});
check('AC-VOCAB-03A places one subtle notice in the article first view', () => {
  const notice = resolveNotice({ signals: ['product-link'], body: '<p>商品を比較します。</p>' });
  assert.deepEqual(notice, { text: fixture.defaultText, location: 'article-top', count: 1, automatic: true });
  assert.equal(fixture.style.fontSize, 'theme-minimum');
  assert(fixture.style.contrastRatio >= 4.5);
  assert.equal(fixture.placement.adjacentTo.length, 0);
});
check('AC-VOCAB-03A keeps display design and page/post controls selectable', () => {
  assert.equal(fixture.style.editorSelectable, true);
  assert.equal(fixture.style.aiSelectable, true);
  assert.equal(fixture.controls.pageScope, true);
  assert.equal(fixture.controls.postScope, true);
});

check('AC-VOCAB-03B omits notices from articles with no commercial signal', () => {
  assert.equal(resolveNotice({ signals: [], body: '<p>広告のない製品を比較します。</p>' }), null);
});
rejects('AC-VOCAB-03B rejects signal removal from the automatic contract', () => {
  const value = copy(fixture); value.triggers = ['unrelated']; validateContract(value);
});
rejects('AC-VOCAB-03B rejects below-minimum font size or contrast', () => {
  const value = copy(fixture); value.style.fontSize = 'tiny'; value.style.contrastRatio = 4.49; validateContract(value);
});
rejects('AC-VOCAB-03B rejects CTA/banner adjacency', () => {
  const value = copy(fixture); value.placement.adjacentTo = ['cta', 'banner']; validateContract(value);
});
rejects('AC-VOCAB-03B rejects body-edit removal', () => {
  const value = copy(fixture); value.controls.bodyCanRemove = true; validateContract(value);
});

check('AC-VOCAB-03C keeps the PO default wording and avoids component badges', () => {
  assert.equal(fixture.defaultText, '本記事にはプロモーションが含まれます。');
  assert.deepEqual(fixture.placement.adjacentTo, []);
  assert.equal(resolveNotice({ signals: ['affiliate-block'], body: '<p>本文</p>' }).text, fixture.defaultText);
});
check('AC-VOCAB-03C suppresses duplicate positive disclosure in the first three paragraphs', () => {
  const body = '<p>本記事にはプロモーションが含まれます。</p><p>本文です。</p>';
  assert.equal(hasExistingDisclosure(body), true);
  assert.equal(resolveNotice({ signals: ['affiliate-link'], body }), null);
});
check('AC-VOCAB-03C preserves the known pending boundary for late or heading-only disclosure', () => {
  const late = '<p>前段</p><p>次段</p><p>三段</p><p>本記事にはプロモーションが含まれます。</p>';
  const heading = '<h2>本記事にはプロモーションが含まれます。</h2><p>本文</p>';
  assert.equal(hasExistingDisclosure(late), false);
  assert.equal(hasExistingDisclosure(heading), false);
});

check('contract has no external transport or model invocation', () => {
  const source = fs.readFileSync('docs/research/2026-09-20-pr-disclosure-contract-poc/contract.mjs', 'utf8');
  for (const forbidden of ['fetch(', 'sendBeacon(', 'XMLHttpRequest', 'openai', 'anthropic', 'model.call']) assert(!source.includes(forbidden), forbidden);
});

const report = {
  schema: 'wt-pr-disclosure-contract-poc-verification.v1',
  completed: rows.every(row => row.pass),
  sourceDigests: Object.fromEntries(sourceFiles.map(file => [file, hash(file)])),
  rows,
  shots: [
    { id: 'default', device: 'pc', file: '../2026-09-05-design-prototype-03/results/pr-notice-one-line-pc.jpg', sha256: hash('docs/research/2026-09-05-design-prototype-03/results/pr-notice-one-line-pc.jpg') },
    { id: 'default', device: 'sp', file: '../2026-09-05-design-prototype-03/results/pr-notice-one-line-sp.jpg', sha256: hash('docs/research/2026-09-05-design-prototype-03/results/pr-notice-one-line-sp.jpg') },
  ],
  scope: '静的PR表記契約。広告シグナルの自動判定、記事上部一箇所、first view、最小文字サイズ、AAコントラスト、本文編集削除拒否、既定文言、重複抑止の境界を検証する。',
  remaining: ['WordPress 7.2 の管理画面保存、REST/MCP実接続、全表示型の実機検証、本文表記との重複抑止と欠落防止の最終両立は未接続または要求pending_resolution。'],
};
fs.writeFileSync(`${root}/verification.json`, `${JSON.stringify(report, null, 2)}\n`);
const names = rows.map(row => row.name);
fs.writeFileSync(`${root}/acceptance-candidate.json`, `${JSON.stringify({
  schema: 'wt-acceptance-candidate.v1',
  'WT-AC-VOCAB-03A': { status: 'partial', scope: '広告シグナルがある記事だけへ、既定の控えめなPR表記を記事上部一箇所・first view・最小文字サイズ・AAコントラストで自動表示し、表示範囲を選択できる静的契約を検証する。', remaining: report.remaining, proofs: [{ path: `${root}/verification.json`, row_names: names.filter(name => name.startsWith('AC-VOCAB-03A')) }] },
  'WT-AC-VOCAB-03B': { status: 'partial', scope: '欠落、対象外への誤表示、サイズ/コントラスト不足、CTA/バナー隣接、本文編集による削除を負例として拒否する。', remaining: report.remaining, proofs: [{ path: `${root}/verification.json`, row_names: names.filter(name => name.startsWith('AC-VOCAB-03B')) }] },
  'WT-AC-VOCAB-03C': { status: 'partial', scope: 'PO既定文言、不要なCTA/商品カード束バッジ抑止、先頭範囲の重複抑止と既知のpending境界を記録する。', remaining: report.remaining, proofs: [{ path: `${root}/verification.json`, row_names: names.filter(name => name.startsWith('AC-VOCAB-03C')) }] },
}, null, 2)}\n`);
fs.writeFileSync(`${root}/catalog-candidates.json`, `${JSON.stringify({
  schema: 'wt-pr-disclosure-contract-catalog-candidates.v1',
  entries: [
    { id: 'pr-disclosure:automatic-boundary', face: 'article', part: 'pr-disclosure', label: 'PR表記：広告シグナルの自動境界', variant: 'default', description: '広告パーツ・アフィリエイトリンク・商品リンクを起点に、対象記事だけへ自動挿入する静的契約PoC。', purpose: '信頼・納得をつくる', group: 'ページ・本文', images: { pc: '../2026-09-05-design-prototype-03/results/pr-notice-one-line-pc.jpg', sp: '../2026-09-05-design-prototype-03/results/pr-notice-one-line-sp.jpg' }, requirementIds: ['WT-FR-VOCAB-03'], referenceId: 'wt-pr-disclosure-contract.v1', evidence: '../2026-09-20-pr-disclosure-contract-poc/verification.json', selectionFacts: { '対象と判断': '広告シグナルと表示要否を同一契約から選ぶ。', '実測': '4種以上のシグナル、対象/対象外、先頭3段落・600字境界、否定文。', '未検証': report.remaining.join(' ') } },
    { id: 'pr-disclosure:placement', face: 'article', part: 'pr-disclosure', label: 'PR表記：記事上部・first view・AA', variant: 'default', description: '控えめな既定文言を記事上部に一箇所だけ置き、最小文字サイズとAAコントラストを契約化する静的PoC。', purpose: '信頼・納得をつくる', group: 'ページ・本文', images: { pc: '../2026-09-05-design-prototype-03/results/pr-notice-one-line-pc.jpg', sp: '../2026-09-05-design-prototype-03/results/pr-notice-one-line-sp.jpg' }, requirementIds: ['WT-FR-VOCAB-03'], referenceId: 'wt-pr-disclosure-contract.v1', evidence: '../2026-09-20-pr-disclosure-contract-poc/verification.json', selectionFacts: { '対象と判断': '記事を読み始める前に広告性を確認できる位置を選ぶ。', '実測': 'article-top、first view、count 1、CTA/バナー隣接なし、4.5:1以上。', '未検証': report.remaining.join(' ') } },
    { id: 'pr-disclosure:wording-boundary', face: 'article', part: 'pr-disclosure', label: 'PR表記：既定文言と重複抑止', variant: 'default', description: 'PO決定文言を正本に束ね、本文先頭の既存表記との重複を抑止し、pending境界を明示する静的PoC。', purpose: '信頼・納得をつくる', group: 'ページ・本文', images: { pc: '../2026-09-05-design-prototype-03/results/pr-notice-one-line-pc.jpg', sp: '../2026-09-05-design-prototype-03/results/pr-notice-one-line-sp.jpg' }, requirementIds: ['WT-FR-VOCAB-03'], referenceId: 'wt-pr-disclosure-contract.v1', evidence: '../2026-09-20-pr-disclosure-contract-poc/verification.json', selectionFacts: { '対象と判断': '文言の統一と二重表示の抑止を選ぶ。', '実測': '本記事にはプロモーションが含まれます。、先頭3段落/600字、CTA・商品カード束バッジなし。', '未検証': report.remaining.join(' ') } },
  ],
}, null, 2)}\n`);
console.log(JSON.stringify({ completed: report.completed, tests: rows.length, screenshots: report.shots.length, acceptance: '3 partial candidates' }));
if (!report.completed) process.exitCode = 1;
