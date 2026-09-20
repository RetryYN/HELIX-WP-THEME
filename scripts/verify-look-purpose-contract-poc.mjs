import fs from 'node:fs';
import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import { fixture, project, validateContract } from '../docs/research/2026-09-20-look-purpose-contract-poc/contract.mjs';

const root = 'docs/research/2026-09-20-look-purpose-contract-poc';
const prototype = 'docs/research/2026-09-05-design-prototype-03';
const sourceFiles = [
  'docs/research/2026-09-20-look-purpose-contract-poc/contract.mjs',
  'scripts/verify-look-purpose-contract-poc.mjs',
  'docs/research/2026-09-20-look-purpose-contract-poc/README.md',
  'docs/research/2026-09-20-look-purpose-contract-poc/external-observations.md',
  'docs/research/2026-09-05-parts-pattern-taxonomy/by-purpose.md',
  'docs/research/2026-09-05-design-prototype-03/CATALOG-INDEX.json',
  'docs/research/2026-09-05-design-prototype-03/results/verify.json',
  'docs/research/2026-09-05-design-prototype-03/scripts/verify.mjs',
];
const hash = file => createHash('sha256').update(fs.readFileSync(file)).digest('hex');
const read = file => JSON.parse(fs.readFileSync(file, 'utf8'));
const rows = [];
const check = (name, fn) => { try { fn(); rows.push({ name, pass: true }); } catch (error) { rows.push({ name, pass: false, details: error instanceof Error ? error.message : String(error) }); } };
const rejects = (name, fn) => check(name, () => assert.throws(fn));
const result = read(`${prototype}/results/verify.json`);
const index = read(`${prototype}/CATALOG-INDEX.json`);
const parts = new Set(index.map(row => row.part));

check('AC-LOOK-01E binds the purpose ledger and all required surface families', () => {
  validateContract(fixture);
  const text = fs.readFileSync(fixture.ledger, 'utf8');
  for (const marker of ['企業 HP', 'サービス / SaaS LP', '比較・アフィリエイト媒体', '## 2. 用途 × 目的']) assert(text.includes(marker), marker);
  for (const part of ['h2', 'h3', 'box', 'cta', 'table', 'graph', 'linkcard', 'related']) assert(parts.has(part), part);
});
check('AC-LOOK-01E projects PC/SP, JS-off, reduced-motion, and contrast gates', () => {
  const view = project(fixture);
  assert.equal(view.gates.pc, true); assert.equal(view.gates.sp, true); assert.equal(view.gates.noJs, true); assert.equal(view.gates.reducedMotion, true); assert(view.gates.contrastRatio >= 4.5);
  const checks = result.summary.checks;
  for (const name of ['noJs', 'reducedMotion', 'contrast', 'contrastGuard', 'table', 'relatedQuality', 'graphs', 'detextVisualDiff', 'depthFloat', 'relatedSlider', 'metricsSp', 'prAutoFixtures']) assert.equal(checks[name], true, name);
});
check('AC-LOOK-01E keeps the measured prototype complete', () => {
  assert.equal(result.summary.fail, 0);
  assert(result.summary.pass >= 90);
  assert.deepEqual(result.summary.skipped, []);
  assert.equal(result.summary.checks.pc ?? true, true);
});
check('AC-LOOK-01E has candidate-facing evidence for both widths', () => {
  for (const file of ['h2-plain-pc.jpg', 'h2-plain-sp.jpg', 'axis-motion-off-pc.jpg', 'axis-motion-off-sp.jpg']) {
    assert(fs.existsSync(`${prototype}/results/${file}`), file);
    assert(fs.statSync(`${prototype}/results/${file}`).size > 0, file);
  }
});
rejects('AC-LOOK-01E rejects a missing purpose surface', () => {
  const value = structuredClone(fixture); value.surfaces = value.surfaces.filter(name => name !== 'graph'); validateContract(value);
});
rejects('AC-LOOK-01E rejects an omitted reduced-motion gate', () => {
  const value = structuredClone(fixture); value.gates.reducedMotion = false; validateContract(value);
});
check('contract has no transport, model, or decision invocation', () => {
  const source = fs.readFileSync(`${root}/contract.mjs`, 'utf8');
  for (const forbidden of ['fetch(', 'sendBeacon(', 'XMLHttpRequest', 'openai', 'anthropic', 'model.call']) assert(!source.includes(forbidden), forbidden);
});

const report = {
  schema: 'wt-look-purpose-contract-poc-verification.v1',
  completed: rows.every(row => row.pass),
  sourceDigests: Object.fromEntries(sourceFiles.map(file => [file, hash(file)])),
  rows,
  shots: [
    { id: 'heading', device: 'pc', file: '../2026-09-05-design-prototype-03/results/h2-plain-pc.jpg', sha256: hash(`${prototype}/results/h2-plain-pc.jpg`) },
    { id: 'heading', device: 'sp', file: '../2026-09-05-design-prototype-03/results/h2-plain-sp.jpg', sha256: hash(`${prototype}/results/h2-plain-sp.jpg`) },
    { id: 'motion-off', device: 'pc', file: '../2026-09-05-design-prototype-03/results/axis-motion-off-pc.jpg', sha256: hash(`${prototype}/results/axis-motion-off-pc.jpg`) },
    { id: 'motion-off', device: 'sp', file: '../2026-09-05-design-prototype-03/results/axis-motion-off-sp.jpg', sha256: hash(`${prototype}/results/axis-motion-off-sp.jpg`) },
  ],
  scope: '用途台帳と既存見た目検査を、表面 family、4軸、PC/SP、JS無効、reduced-motion、コントラストの選択契約へ束ねる。',
  remaining: ['全style variation・Site Editor・ブラウザzoom/root font組合せ、用途台帳のPO保留行、全候補の再撮影は未接続。'],
};
fs.writeFileSync(`${root}/verification.json`, `${JSON.stringify(report, null, 2)}\n`);
const names = rows.map(row => row.name);
fs.writeFileSync(`${root}/acceptance-candidate.json`, `${JSON.stringify({
  schema: 'wt-acceptance-candidate.v1',
  'WT-AC-LOOK-01E': { status: 'partial', scope: '用途台帳と既存実機検査を、見出し・囲み・CTA・比較表・グラフ・ブログカード・関連一覧・画像処理・4軸、およびPC/SP・JS無効・reduced-motion・コントラストへ対応付ける。', remaining: report.remaining, proofs: [{ path: `${root}/verification.json`, row_names: names.filter(name => name.startsWith('AC-LOOK-01E')) }] },
}, null, 2)}\n`);
fs.writeFileSync(`${root}/catalog-candidates.json`, `${JSON.stringify({
  schema: 'wt-look-purpose-contract-catalog-candidates.v1',
  entries: [{ id: 'look-contract:purpose-quality', face: 'article', part: 'look-contract', label: '見た目：用途台帳と品質ゲート', variant: 'purpose-quality', description: '用途台帳から見出し・囲み・CTA・比較表・グラフ・ブログカード・関連一覧・画像処理・4軸を束ね、既存のPC/SP・JS無効・reduced-motion・コントラスト検査へ接続する静的契約PoC。', purpose: '読みやすく伝える', group: 'ページ・本文', images: { pc: '../2026-09-05-design-prototype-03/results/h2-plain-pc.jpg', sp: '../2026-09-05-design-prototype-03/results/h2-plain-sp.jpg' }, requirementIds: ['WT-FR-LOOK-01'], referenceId: 'wt-look-purpose-contract.v1', evidence: '../2026-09-20-look-purpose-contract-poc/verification.json', selectionFacts: { '対象と判断': '用途別台帳から必要な型を選び、同じ品質ゲートでPC/SPを検査する。', '実測': '既存results/verify.json 90 pass / 0 fail、JS無効・reduced-motion・contrast・4軸、代表PC/SP画像。', '未検証': report.remaining.join(' ') } }],
}, null, 2)}\n`);
console.log(JSON.stringify({ completed: report.completed, tests: rows.length, screenshots: report.shots.length, acceptance: '1 partial candidate' }));
if (!report.completed) process.exitCode = 1;
