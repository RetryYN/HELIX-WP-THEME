import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import { createHash } from 'node:crypto';
import { fixture, project, validateContract } from '../docs/research/2026-09-20-look-pattern-contract-poc/contract.mjs';

const root = 'docs/research/2026-09-20-look-pattern-contract-poc';
const survey = 'docs/research/2026-09-04-site-survey';
const taxonomy = 'docs/research/2026-09-05-parts-pattern-taxonomy';
const prototype = 'docs/research/2026-09-05-design-prototype-03';
const sourceFiles = [
  'docs/research/2026-09-20-look-pattern-contract-poc/contract.mjs',
  'scripts/verify-look-pattern-contract-poc.mjs',
  'docs/research/2026-09-20-look-pattern-contract-poc/README.md',
  'docs/research/2026-09-20-look-pattern-contract-poc/external-observations.md',
  'docs/research/2026-09-04-site-survey/sites-index.json',
  'docs/research/2026-09-04-site-survey/results/analysis.json',
  'docs/research/2026-09-05-parts-pattern-taxonomy/aggregate.json',
  'docs/research/2026-09-03-design-prototype-01/index.json',
  'bin/check-design-consistency.sh',
  'themes/agent-neo-theme/theme.json',
  'docs/requirements/l3/requirements-ir.json',
];
const read = file => JSON.parse(fs.readFileSync(file, 'utf8'));
const hash = file => createHash('sha256').update(fs.readFileSync(file)).digest('hex');
const rows = [];
const check = (name, fn) => { try { fn(); rows.push({ name, pass: true }); } catch (error) { rows.push({ name, pass: false, details: error instanceof Error ? error.message : String(error) }); } };
const rejects = (name, fn) => check(name, () => assert.throws(fn));

const index = read(`${survey}/sites-index.json`);
const analysis = read(`${survey}/results/analysis.json`);
const parts = read(`${taxonomy}/aggregate.json`);
const styleFiles = fs.readdirSync('themes/agent-neo-theme/styles').filter(file => file.endsWith('.json')).sort();
const observed = [...new Set(index.map(item => item.pattern))].sort();
const expectedCounts = { brand: 35, compare: 66, corporate: 35, motion: 35, portal: 64, service: 34 };

check('AC-LOOK-03A binds the survey vocabulary and pattern distribution', () => {
  validateContract(fixture, observed);
  assert.equal(index.length, 269);
  assert.deepEqual(Object.fromEntries(Object.entries(index.reduce((out, item) => { out[item.pattern] = (out[item.pattern] || 0) + 1; return out; }, {})).sort()), expectedCounts);
  for (const group of ['corporate/top', 'service/top', 'brand/top', 'portal/top', 'compare/top', 'motion/top']) assert(analysis.groups[group]?.sp?.n > 0, group);
  assert.equal(parts.n, 730);
  for (const family of ['header.layout', 'hero.type', 'section.types', 'card.style', 'footer.layout', 'heading.h2', 'box.types', 'link.card', 'related.layout']) assert(parts.by_part[family], family);
});
check('AC-LOOK-03A derives the nine current variations and passes G-T1b/G-T3', () => {
  assert.deepEqual(styleFiles, ['business.json', 'dark.json', 'depth.json', 'editorial.json', 'light.json', 'mono.json', 'night-contrast.json', 'vivid.json', 'warm.json']);
  const output = execFileSync('bash', ['bin/check-design-consistency.sh'], { encoding: 'utf8' });
  assert.match(output, /G-T1b/); assert.match(output, /G-T3/); assert.match(output, /FAIL=0/);
  for (const styles of Object.values(fixture.variationMap)) for (const style of styles) assert(fs.existsSync(`themes/agent-neo-theme/styles/${style}.json`), style);
});
check('AC-LOOK-03C keeps an open index and refuses completion while it has unobserved patterns', () => {
  const view = project(fixture); assert.equal(view.gates.completion, false); assert(view.openPatterns.length >= 4);
  assert(view.openPatterns.every(item => item.status === 'unobserved' && !observed.includes(item.id)));
});
rejects('AC-LOOK-03B rejects a variation sourced from an unobserved pattern', () => {
  const value = structuredClone(fixture); value.variationMap.commerce = ['vivid']; validateContract(value, observed);
});
rejects('AC-LOOK-03B rejects a pattern distribution that is not in the survey', () => {
  const value = structuredClone(fixture); value.variationMap.motion = []; validateContract(value, observed);
});
rejects('AC-LOOK-03C rejects an open pattern that is already observed', () => {
  const value = structuredClone(fixture); value.openPatterns[0].id = 'corporate'; validateContract(value, observed);
});
check('contract has no transport, model, or decision invocation', () => {
  const source = fs.readFileSync(`${root}/contract.mjs`, 'utf8');
  for (const forbidden of ['fetch(', 'sendBeacon(', 'XMLHttpRequest', 'openai', 'anthropic', 'model.call']) assert(!source.includes(forbidden), forbidden);
});

const report = {
  schema: 'wt-look-pattern-contract-poc-verification.v1',
  completed: rows.every(row => row.pass),
  sourceDigests: Object.fromEntries(sourceFiles.map(file => [file, hash(file)])),
  survey: { entries: index.length, patternCounts: expectedCounts, analysisGroups: Object.keys(analysis.groups).length, taxonomyObservations: parts.n, taxonomyFamilies: Object.keys(parts.by_part).length },
  variationMap: fixture.variationMap,
  openPatterns: fixture.openPatterns,
  gates: { gT1b: true, gT3: true, completion: false },
  rows,
  shots: [
    { id: 'pattern-contract', device: 'pc', file: '../2026-09-05-design-prototype-03/results/h2-plain-pc.jpg', sha256: hash(`${prototype}/results/h2-plain-pc.jpg`) },
    { id: 'pattern-contract', device: 'sp', file: '../2026-09-05-design-prototype-03/results/h2-plain-sp.jpg', sha256: hash(`${prototype}/results/h2-plain-sp.jpg`) },
  ],
  scope: '実サイト調査のパターン分布を現行variationへ対応付け、未観察系統を開いたままカタログ選択へ渡す。',
  remaining: ['全style variationの編集画面保存・REST/MCP経路、未観察系統の実サイト再調査、常時アニメーションの資産層は未接続。'],
};
fs.writeFileSync(`${root}/verification.json`, `${JSON.stringify(report, null, 2)}\n`);
fs.writeFileSync(`${root}/acceptance-candidate.json`, `${JSON.stringify({
  schema: 'wt-acceptance-candidate.v1',
  'WT-AC-LOOK-03A': { status: 'partial', scope: '269件のサイト調査、6系統の分布、730件25 familyの部品集計、および9 variationの導出をG-T1b/G-T3へ束ねる。', remaining: report.remaining, proofs: [{ path: `${root}/verification.json`, row_names: rows.filter(row => row.name.startsWith('AC-LOOK-03A')).map(row => row.name) }] },
  'WT-AC-LOOK-03B': { status: 'partial', scope: '調査証跡のないパターンをvariationMapへ入れない負例を固定する。', remaining: report.remaining, proofs: [{ path: `${root}/verification.json`, row_names: rows.filter(row => row.name.startsWith('AC-LOOK-03B')).map(row => row.name) }] },
  'WT-AC-LOOK-03C': { status: 'partial', scope: '未観察パターンをopen indexへ保持し、completion=falseを固定する。', remaining: report.remaining, proofs: [{ path: `${root}/verification.json`, row_names: rows.filter(row => row.name.startsWith('AC-LOOK-03C')).map(row => row.name) }] },
}, null, 2)}\n`);
fs.writeFileSync(`${root}/catalog-candidates.json`, `${JSON.stringify({
  schema: 'wt-look-pattern-contract-catalog-candidates.v1',
  entries: [{ id: 'look-pattern:survey-derived', face: 'article', part: 'look-pattern-contract', label: '見た目：サイトパターン分布からの導出', variant: 'survey-derived', description: '269件のサイト調査と730件の部品語彙集計から6系統を観察済みとして9 variationへ対応付け、未観察系統を開いた索引に残す静的契約PoC。', purpose: '用途に合う型を選ぶ', group: 'ページ・本文', images: { pc: '../2026-09-05-design-prototype-03/results/h2-plain-pc.jpg', sp: '../2026-09-05-design-prototype-03/results/h2-plain-sp.jpg' }, requirementIds: ['WT-FR-LOOK-03'], referenceId: 'wt-look-pattern-contract.v1', evidence: '../2026-09-20-look-pattern-contract-poc/verification.json', selectionFacts: { '対象と判断': '観察済み分布に根拠があるvariationだけを候補にし、未観察系統は索引へ残す。', '実測': '調査269件、部品集計730件25 family、G-T1b/G-T3 FAIL=0。', '未検証': report.remaining.join(' ') } }],
}, null, 2)}\n`);
console.log(JSON.stringify({ completed: report.completed, tests: rows.length, survey: report.survey, screenshots: report.shots.length, acceptance: '3 partial candidates' }));
if (!report.completed) process.exitCode = 1;
