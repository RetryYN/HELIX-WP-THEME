import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import { createHash } from 'node:crypto';
import { fixture, project, validateContract } from '../docs/research/2026-09-20-gate-contract-poc/contract.mjs';

const root = 'docs/research/2026-09-20-gate-contract-poc';
const read = file => JSON.parse(fs.readFileSync(file, 'utf8'));
const hash = file => createHash('sha256').update(fs.readFileSync(file)).digest('hex');
const rows = [];
const check = (name, fn) => { try { fn(); rows.push({ name, pass: true }); } catch (error) { rows.push({ name, pass: false, details: error instanceof Error ? error.message : String(error) }); } };
const rejects = (name, fn) => check(name, () => assert.throws(fn));

const ge1 = read(fixture.ge1.source);
const staticOutput = execFileSync('bash', ['bin/check-design-consistency.sh'], { encoding: 'utf8' });
const normalizedStaticOutput = staticOutput.replace(/\u001b\[[0-?]*[ -\/]*[@-~]/g, '');
const invalid = Object.values(ge1).reduce((sum, row) => sum + (row.invalid?.length ?? 0), 0);
const value = structuredClone(fixture);
value.ge1.invalid = invalid;

check('AC-NFR-GATE-01A binds all six static gates and G-E1 invalid=0', () => {
  validateContract(value, staticOutput, ge1);
  assert.equal(Object.keys(ge1).length, 71);
  assert.equal(invalid, 0);
});
check('AC-NFR-GATE-01A records the known static baseline and G-E1 scope', () => {
  assert.match(normalizedStaticOutput, /生値検出: patterns=347 parts=42 templates=44 合計=433/);
  assert.match(normalizedStaticOutput, /生値 433 件 ≤ baseline 438/);
  assert.match(normalizedStaticOutput, /^FAIL=0 WARN=1$/m);
  for (const gate of fixture.staticGates) assert.match(normalizedStaticOutput, new RegExp(`^=== ${gate}(?:\\s|=)`, 'm'));
});
check('AC-NFR-GATE-01B keeps completion false for historical G-E1 evidence', () => {
  const view = project(value); assert.equal(view.gates.completion, false); assert.equal(view.gates.exactHeadReceipt, false);
  assert.equal(view.ge1.patterns, 71); assert(view.remaining.length >= 2);
});
rejects('AC-NFR-GATE-01B rejects static PASS with a nonzero FAIL count', () => {
  const broken = structuredClone(value); broken.staticResult.fail = 1; validateContract(broken, staticOutput, ge1);
});
rejects('AC-NFR-GATE-01B rejects an invalid schema', () => {
  const broken = structuredClone(value); broken.schema = 'wrong'; validateContract(broken, staticOutput, ge1);
});
rejects('AC-NFR-GATE-01B rejects an invalid owner', () => {
  const broken = structuredClone(value); broken.owner = 'other'; validateContract(broken, staticOutput, ge1);
});
rejects('AC-NFR-GATE-01B rejects a changed static gate set', () => {
  const broken = structuredClone(value); broken.staticGates = broken.staticGates.slice(0, -1); validateContract(broken, staticOutput, ge1);
});
rejects('AC-NFR-GATE-01B rejects a changed static warning count', () => {
  const broken = structuredClone(value); broken.staticResult.warn = 2; validateContract(broken, staticOutput, ge1);
});
rejects('AC-NFR-GATE-01B rejects a changed raw value count', () => {
  const broken = structuredClone(value); broken.staticResult.rawValues = 434; validateContract(broken, staticOutput, ge1);
});
rejects('AC-NFR-GATE-01B rejects a changed raw baseline', () => {
  const broken = structuredClone(value); broken.staticResult.rawBaseline = 437; validateContract(broken, staticOutput, ge1);
});
rejects('AC-NFR-GATE-01B rejects a changed G-E1 pattern count', () => {
  const broken = structuredClone(value); broken.ge1.patterns = 70; validateContract(broken, staticOutput, ge1);
});
rejects('AC-NFR-GATE-01B rejects completion marked true', () => {
  const broken = structuredClone(value); broken.gates.completion = true; validateContract(broken, staticOutput, ge1);
});
rejects('AC-NFR-GATE-01B rejects an exact-head receipt claim', () => {
  const broken = structuredClone(value); broken.gates.exactHeadReceipt = true; validateContract(broken, staticOutput, ge1);
});
rejects('AC-NFR-GATE-01B rejects a changed static FAIL count', () => {
  validateContract(value, staticOutput.replace('FAIL=0 WARN=1', 'FAIL=03 WARN=1'), ge1);
});
rejects('AC-NFR-GATE-01B rejects a changed static WARN count', () => {
  validateContract(value, staticOutput.replace('FAIL=0 WARN=1', 'FAIL=0 WARN=12'), ge1);
});
rejects('AC-NFR-GATE-01B rejects a missing G-T1 section while G-T1b remains', () => {
  validateContract(value, staticOutput.replace(/G-T1(?!b)/g, 'G-XX'), ge1);
});
for (const gate of fixture.staticGates) {
  rejects(`AC-NFR-GATE-01B rejects a missing ${gate} marker`, () => {
    validateContract(value, staticOutput.replaceAll(gate, ''), ge1);
  });
}
rejects('AC-NFR-GATE-01B rejects a short G-E1 row set', () => {
  const broken = structuredClone(ge1); delete broken[Object.keys(broken)[0]]; validateContract(value, staticOutput, broken);
});
rejects('AC-NFR-GATE-01B rejects a nonzero G-E1 row invalid sum', () => {
  const broken = structuredClone(ge1); broken[Object.keys(broken)[0]].invalid = ['invalid']; validateContract(value, staticOutput, broken);
});
rejects('AC-NFR-GATE-01B rejects a reported count that matches nonzero row invalids', () => {
  const brokenRows = structuredClone(ge1); brokenRows[Object.keys(brokenRows)[0]].invalid = ['invalid'];
  const brokenValue = structuredClone(value); brokenValue.ge1.invalid = 1;
  validateContract(brokenValue, staticOutput, brokenRows);
});
rejects('AC-NFR-GATE-01B rejects G-E1 invalid blocks', () => {
  const broken = structuredClone(value); broken.ge1.invalid = 1; validateContract(broken, staticOutput, ge1);
});
check('contract has no transport, model, or credential material', () => {
  const source = fs.readFileSync(`${root}/contract.mjs`, 'utf8');
  for (const forbidden of ['fetch(', 'sendBeacon(', 'XMLHttpRequest', 'openai', 'anthropic', 'WP_ADMIN_PASS', 'password']) assert(!source.includes(forbidden), forbidden);
});

const sourceFiles = [...fixture.sources, `${root}/contract.mjs`, 'scripts/verify-gate-contract-poc.mjs', `${root}/README.md`];
const report = {
  schema: 'wt-gate-contract-poc-verification.v1',
  completed: rows.every(row => row.pass),
  sourceDigests: Object.fromEntries(sourceFiles.map(file => [file, hash(file)])),
  static: { gates: fixture.staticGates, fail: fixture.staticResult.fail, warn: fixture.staticResult.warn, rawValues: 433, rawBaseline: 438 },
  ge1: { source: fixture.ge1.source, patterns: Object.keys(ge1).length, invalid, wordpress: '7.1' },
  gates: { completion: false, exactHeadReceipt: false },
  rows,
  scope: '静的6ゲートと既存G-E1実機証跡を同じ部分契約へ束ね、静的PASSだけで完了扱いしない。',
  remaining: project(value).remaining,
};
fs.writeFileSync(`${root}/verification.json`, `${JSON.stringify(report, null, 2)}\n`);
fs.writeFileSync(`${root}/acceptance-candidate.json`, `${JSON.stringify({
  schema: 'wt-acceptance-candidate.v1',
  'WT-AC-NFR-GATE-01A': { status: 'partial', scope: '静的6ゲート FAIL=0 と WordPress 7.1 の71パターン G-E1 invalid=0 を再集計する。', remaining: report.remaining, proofs: [{ path: `${root}/verification.json`, row_names: rows.filter(row => row.name.startsWith('AC-NFR-GATE-01A')).map(row => row.name) }] },
  'WT-AC-NFR-GATE-01B': { status: 'partial', scope: '静的PASSのみ、またはG-E1 invalidを含む証跡を拒否し、過去証跡を現行HEAD完了扱いしない。', remaining: report.remaining, proofs: [{ path: `${root}/verification.json`, row_names: rows.filter(row => row.name.startsWith('AC-NFR-GATE-01B')).map(row => row.name) }] },
}, null, 2)}\n`);
fs.writeFileSync(`${root}/catalog-candidates.json`, `${JSON.stringify({
  schema: 'wt-gate-contract-catalog-candidates.v1',
  entries: [{ id: 'gate-contract:static-and-ge1', face: 'system', part: 'quality-gate-contract', label: '品質ゲート：静的6種＋G-E1', variant: 'static-and-ge1', description: '静的6ゲートをFAIL=0で実行し、WordPress 7.1の71パターンをG-E1 invalid=0として再集計する部分契約。現行HEAD receipt未接続を明示する。', purpose: '品質条件を確認する', group: '品質・運用', images: { pc: '../2026-09-05-design-prototype-03/results/h2-plain-pc.jpg', sp: '../2026-09-05-design-prototype-03/results/h2-plain-sp.jpg' }, requirementIds: ['WT-NFR-GATE-01'], referenceId: 'wt-gate-contract.v1', evidence: `${root}/verification.json`, selectionFacts: { '対象と判断': '静的PASSとG-E1 invalid=0を同じ検査で確認し、過去証跡のcurrent HEAD未束縛を残件として表示する。', '実測': '静的6ゲート FAIL=0、生値433/基準438、G-E1 71パターン invalid=0。', '未検証': report.remaining.join(' ') } }],
}, null, 2)}\n`);
console.log(JSON.stringify({ completed: report.completed, tests: rows.length, staticGates: fixture.staticGates.length, ge1: report.ge1, acceptance: '2 partial candidates' }));
if (!report.completed) process.exitCode = 1;
