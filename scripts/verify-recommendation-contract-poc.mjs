import fs from 'node:fs';
import path from 'node:path';
import { createHash } from 'node:crypto';
import assert from 'node:assert/strict';
import { aggregateDaily, copy, fixture, projectDisplay, readBack, validateRecommendationContract } from '../docs/research/2026-09-20-recommendation-contract-poc/contract.mjs';

const root = 'docs/research/2026-09-20-recommendation-contract-poc';
const sourceFiles = [
  'docs/research/2026-09-20-recommendation-contract-poc/contract.mjs',
  'scripts/verify-recommendation-contract-poc.mjs',
  'docs/research/2026-09-20-recommendation-contract-poc/README.md',
];
const hash = file => createHash('sha256').update(fs.readFileSync(file)).digest('hex');
const rows = [];
const check = (name, action) => {
  try { action(); rows.push({ name, pass: true }); }
  catch (error) { rows.push({ name, pass: false, details: error instanceof Error ? error.message : String(error) }); }
};
const rejects = (name, action) => check(name, () => assert.throws(action));

check('AC-RECO-01A accepts three selectable methods and popular settings', () => {
  validateRecommendationContract(fixture);
  assert.deepEqual(Object.keys(fixture.methods).sort(), ['manual', 'popular', 'related']);
  assert.equal(fixture.methods.popular.period, '7d');
  assert.equal(fixture.methods.popular.externalReadback.enabled, true);
  assert.deepEqual(fixture.methods.manual.postIds, ['post-103', 'post-101', 'post-102']);
});
check('AC-RECO-01A config JSON readback is identical', () => {
  assert.deepEqual(readBack(fixture), fixture);
});
check('AC-RECO-01A self aggregation is daily and contains no IP', () => {
  const output = aggregateDaily([
    { postId: 'post-101', day: '2026-09-20', count: 2 },
    { postId: 'post-101', day: '2026-09-20', count: 3, bot: true },
    { postId: 'post-101', day: '2026-09-20', count: 1, admin: true },
    { postId: 'post-102', day: '2026-09-20', count: 4 },
  ]);
  assert.deepEqual(output, [
    { postId: 'post-101', day: '2026-09-20', count: 2 },
    { postId: 'post-102', day: '2026-09-20', count: 4 },
  ]);
  assert(output.every(row => Object.keys(row).sort().join(',') === 'count,day,postId'));
});

rejects('AC-RECO-01B rejects a fixed popular method and missing manual order', () => {
  const value = copy(fixture);
  value.methods.popular.method = 'fixed';
  value.methods.manual.postIds = [];
  validateRecommendationContract(value);
});
rejects('AC-RECO-01B rejects IP/raw-view persistence', () => {
  const value = copy(fixture);
  value.methods.popular.storage.fields = ['postId', 'day', 'count', 'ip'];
  validateRecommendationContract(value);
});
rejects('AC-RECO-01B rejects theme-owned AI ranking', () => {
  const value = copy(fixture);
  value.methods.popular.rankingOwner = 'theme-ai';
  validateRecommendationContract(value);
});
rejects('AC-RECO-01B rejects raw visitor fields at aggregation input', () => {
  aggregateDaily([{ postId: 'post-101', day: '2026-09-20', count: 1, ip: '192.0.2.1' }]);
});

check('AC-RECO-01C display type changes preserve method, count, and selected IDs', () => {
  const result = { method: 'popular', postIds: ['post-101', 'post-102', 'post-103'] };
  const projections = fixture.displayTypes.map(type => projectDisplay(result, type));
  assert(projections.every(item => item.method === 'popular' && item.count === 3 && item.postIds.join(',') === result.postIds.join(',')));
  assert(projections.every(item => item.referenceId.startsWith('helix-wt/recommendation-')));
});
check('AC-RECO-01C exposes distinct purpose-bound reference IDs', () => {
  const refs = fixture.displayTypes.map(type => fixture.displayBindings[type].referenceId);
  assert.equal(new Set(refs).size, fixture.displayTypes.length);
});
check('contract has no external transport or model invocation', () => {
  const source = fs.readFileSync('docs/research/2026-09-20-recommendation-contract-poc/contract.mjs', 'utf8');
  for (const forbidden of ['fetch(', 'sendBeacon(', 'XMLHttpRequest', 'openai', 'anthropic', 'model.call']) assert(!source.includes(forbidden), forbidden);
});

const sourceDigests = Object.fromEntries(sourceFiles.map(file => [file, hash(file)]));
const report = {
  schema: 'wt-recommendation-contract-poc-verification.v1',
  completed: rows.every(row => row.pass),
  sourceDigests,
  rows,
  shots: [
    { id: 'methods', device: 'pc', file: '../2026-09-19-recommendation-layouts/cards-pc.jpg', sha256: hash('docs/research/2026-09-19-recommendation-layouts/cards-pc.jpg') },
    { id: 'methods', device: 'sp', file: '../2026-09-19-recommendation-layouts/cards-sp.jpg', sha256: hash('docs/research/2026-09-19-recommendation-layouts/cards-sp.jpg') },
    { id: 'boundary', device: 'pc', file: '../2026-09-19-recommendation-layouts/list-pc.jpg', sha256: hash('docs/research/2026-09-19-recommendation-layouts/list-pc.jpg') },
    { id: 'boundary', device: 'sp', file: '../2026-09-19-recommendation-layouts/list-sp.jpg', sha256: hash('docs/research/2026-09-19-recommendation-layouts/list-sp.jpg') },
  ],
  scope: '静的推薦設定契約。関連・人気・手動の3方式、人気の方式/期間、bot・管理者除外、IP・生訪問データ非保存、外部読み戻し、テーマ外の順位判定、表示型と参照IDの不変条件を検証する。',
  remaining: ['WordPress 7.2 の管理画面保存、REST/MCP 実接続、外部集計サービスの実通信、実デバイス支援技術検証は未接続。'],
};
fs.writeFileSync(`${root}/verification.json`, `${JSON.stringify(report, null, 2)}\n`);
fs.writeFileSync(`${root}/acceptance-candidate.json`, `${JSON.stringify({
  schema: 'wt-acceptance-candidate.v1',
  'WT-AC-RECO-01A': { status: 'partial', scope: '3方式・人気の方式/期間・手動順・読み戻し・日次集計の静的契約を検証する。', remaining: report.remaining, proofs: [{ path: `${root}/verification.json`, row_names: rows.filter(row => row.name.startsWith('AC-RECO-01A')).map(row => row.name) }] },
  'WT-AC-RECO-01B': { status: 'partial', scope: '固定人気、手動順欠落、IP/生訪問保存、テーマ所有のAI順位判定を負例として拒否する。', remaining: report.remaining, proofs: [{ path: `${root}/verification.json`, row_names: rows.filter(row => row.name.startsWith('AC-RECO-01B')).map(row => row.name) }] },
}, null, 2)}\n`);
fs.writeFileSync(`${root}/catalog-candidates.json`, `${JSON.stringify({
  schema: 'wt-recommendation-contract-catalog-candidates.v1',
  entries: [
    { id: 'recommendation-contract:methods', face: 'article', part: 'recommendation-contract', label: '記事選定：関連・人気・手動の3方式', variant: 'methods', description: '3方式、人気の方式/期間、手動順を同じ設定JSONから比較する静的契約PoC。', purpose: '迷わず案内する', group: 'ページ・本文', images: { pc: '../2026-09-19-recommendation-layouts/cards-pc.jpg', sp: '../2026-09-19-recommendation-layouts/cards-sp.jpg' }, requirementIds: ['WT-FR-RECO-01'], referenceId: 'wt-recommendation-contract.v1', evidence: '../2026-09-20-recommendation-contract-poc/verification.json', selectionFacts: { '対象と判断': '方式と運用設定を一つの契約から選ぶ。', '実測': 'related/popular/manual、人気方式/期間、手動順、JSON読み戻し、日次集計。', '未検証': report.remaining.join(' ') } },
    { id: 'recommendation-contract:boundary', face: 'article', part: 'recommendation-contract', label: '記事選定：個人情報・順位判定の境界', variant: 'boundary', description: 'bot/管理者除外、IP・生訪問データ非保存、テーマ外の順位判定を負例付きで確認する静的PoC。', purpose: '信頼・納得をつくる', group: 'ページ・本文', images: { pc: '../2026-09-19-recommendation-layouts/list-pc.jpg', sp: '../2026-09-19-recommendation-layouts/list-sp.jpg' }, requirementIds: ['WT-FR-RECO-01'], referenceId: 'wt-recommendation-contract.v1', evidence: '../2026-09-20-recommendation-contract-poc/verification.json', selectionFacts: { '対象と判断': 'テーマが個人情報やAI順位判定を所有しない境界を選ぶ。', '実測': '固定方式/手動欠落/IP・生訪問/テーマAI順位を拒否。', '未検証': report.remaining.join(' ') } },
    { id: 'recommendation-contract:display-binding', face: 'article', part: 'recommendation-contract', label: '記事一覧：表示型と選定結果の不変条件', variant: 'display-binding', description: 'カード・リスト・ランキング・サムネ大小・横スクロールの表示型を用途別参照IDへ束ね、方式・件数・記事IDを保持する静的PoC。', purpose: '読みやすく伝える', group: 'ページ・本文', images: { pc: '../2026-09-19-recommendation-layouts/cards-pc.jpg', sp: '../2026-09-19-recommendation-layouts/cards-sp.jpg' }, requirementIds: ['WT-FR-RECO-01'], referenceId: 'wt-recommendation-contract.v1', evidence: '../2026-09-20-recommendation-contract-poc/verification.json', selectionFacts: { '対象と判断': '表示型だけを変え、記事の選定結果は変えない。', '実測': '5表示型、全型の参照ID、同じ方式・件数・記事ID。', '未検証': report.remaining.join(' ') } },
  ],
}, null, 2)}\n`);
console.log(JSON.stringify({ completed: report.completed, tests: rows.length, screenshots: report.shots.length, acceptance: '2 partial candidates; AC-RECO-01C already admitted from layout evidence' }));
if (!report.completed) process.exitCode = 1;
