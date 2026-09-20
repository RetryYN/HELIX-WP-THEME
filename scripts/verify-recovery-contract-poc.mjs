import fs from 'node:fs';
import path from 'node:path';
import os from 'node:os';
import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import assert from 'node:assert/strict';
import { fixture, patches, dryRun, apply, rollback, digest, tokenProjectionFixture, projectTokenLayer } from '../docs/research/2026-09-20-recovery-contract-poc/contract.mjs';

const root = 'docs/research/2026-09-20-recovery-contract-poc';
const sourceFiles = ['scripts/build-recovery-contract-poc.mjs', 'scripts/recovery-contract-renderer.mjs', 'scripts/recovery-contract-server.mjs', 'scripts/verify-recovery-contract-poc.mjs', 'tests/e2e/recovery-contract-poc-selection-catalog.spec.ts', `${root}/contract.mjs`, `${root}/view.mjs`, `${root}/index.html`];
const hash = file => createHash('sha256').update(fs.readFileSync(file)).digest('hex');
const save = (file, value) => fs.writeFileSync(file, `${JSON.stringify(value, null, 2)}\n`);
const before = digest(fixture);
for (const kind of Object.keys(patches)) { const receipt = dryRun(fixture, patches[kind], `verify-${kind}`); const applied = apply(fixture, patches[kind], receipt); assert.notEqual(receipt.targetDigest, before); assert.equal(rollback(applied.state, applied.rollbackPoint).restoredDigest, before); }

const projectedTokens = projectTokenLayer(tokenProjectionFixture.parent, tokenProjectionFixture.bridge);
assert.equal(projectedTokens.typography.slugs.length, 6);
assert.equal(projectedTokens.spacing.slugs.length, 6);
assert.deepEqual(projectedTokens.widths.slugs, ['content', 'wide']);
const removedScale = structuredClone(tokenProjectionFixture.bridge); removedScale.spacing.slugs.pop();
assert.throws(() => projectTokenLayer(tokenProjectionFixture.parent, removedScale), /spacing projection changes slugs/);
const renamedScale = structuredClone(tokenProjectionFixture.bridge); renamedScale.typography.slugs[0] = 'tiny';
assert.throws(() => projectTokenLayer(tokenProjectionFixture.parent, renamedScale), /typography projection changes slugs/);
const settingsOverride = structuredClone(tokenProjectionFixture.bridge); settingsOverride.settings = { typography: { fontSizes: [] } };
assert.throws(() => projectTokenLayer(tokenProjectionFixture.parent, settingsOverride), /settings override is forbidden/);
const addedSafeValue = structuredClone(tokenProjectionFixture.bridge); addedSafeValue.safeValues.extraPreset = { slug: 'x', value: '1rem' };
assert.throws(() => projectTokenLayer(tokenProjectionFixture.parent, addedSafeValue), /safe value dimensions cannot change/);
const removedSafeValue = structuredClone(tokenProjectionFixture.bridge); delete removedSafeValue.safeValues.minWidth;
assert.throws(() => projectTokenLayer(tokenProjectionFixture.parent, removedSafeValue), /safe value dimensions cannot change/);

execFileSync(process.execPath, ['scripts/build-recovery-contract-poc.mjs']);
const staging = fs.mkdtempSync(path.join(os.tmpdir(), 'recovery-contract-capture-'));
try {
  const args = ['playwright', 'test', 'tests/e2e/recovery-contract-poc-selection-catalog.spec.ts', '--workers=1', '--reporter=json'];
  const env = { ...process.env, RECOVERY_CAPTURE_DIR: staging };
  const report = JSON.parse(execFileSync('npx', args, { encoding: 'utf8', maxBuffer: 16 * 1024 * 1024, env }));
  const flatten = suites => suites.flatMap(s => [...(s.specs || []), ...flatten(s.suites || [])]);
  const rows = flatten(report.suites).map(spec => ({ name: spec.title, pass: spec.ok && spec.tests.every(t => t.results.length === 1 && t.results[0].status === 'passed') }));
  assert(rows.length > 0 && rows.every(row => row.pass));
  const shots = Object.keys(patches).flatMap(id => ['pc', 'sp'].map(device => { const file = `${id}-${device}.jpg`; assert(fs.statSync(path.join(staging, file)).size > 1000); fs.copyFileSync(path.join(staging, file), path.join(root, file)); return { id, device, file, sha256: hash(path.join(root, file)) }; }));
  const sourceDigests = Object.fromEntries(sourceFiles.map(file => [file, hash(file)]));
  save(`${root}/verification.json`, { schema: 'wt-recovery-contract-poc-verification.v1', completed: true, command: ['npx', ...args], sourceDigests, rows, shots, scope: 'Static/local contract PoC. Four resource classes share dry-run/apply/rollback receipt and digest restoration; token projection preserves six parent scales and rejects structural/settings changes. No real WP/API/storage write.', remaining: ['実WP 7.2、実ユーザー権限、永続DB、MCP/CLI、実運用の構造/スタイル/値/ゾーン全体、theme.json/bridge/子テーマ/user global stylesの投影接続は未接続。'] });
  const allRows = rows.map(row => row.name);
  save(`${root}/acceptance-candidate.json`, { 'WT-AC-NFR-REC-01A': { status: 'partial', scope: '構造・スタイル・値・ゾーンの4種のローカルfixtureを、同一receipt契約でdry-run→apply→rollbackし、復元digestを照合。', remaining: ['実WPの保存/API、実権限、実運用の全リソース種別、WP 7.2実機は未接続。'], proofs: [{ path: `${root}/verification.json`, row_names: allRows.filter(name => name.startsWith('AC-A')) }] }, 'WT-AC-NFR-REC-01B': { status: 'partial', scope: 'stale receipt、patch mismatch、stale rollback、未知path、空patchを拒否。', remaining: ['実WP/APIの失敗境界と保存競合は未接続。'], proofs: [{ path: `${root}/verification.json`, row_names: allRows.filter(name => name.startsWith('AC-B')) }] }, 'WT-AC-NFR-VALUE-03A': { status: 'partial', scope: '親の6段フォント・余白と2段幅を維持したまま、bridge投影が値だけを差し替える契約を検査。dimension preset、minWidth、background.gradientも同じslugの安全値として保持。', remaining: ['実theme.json・bridge・子テーマ・user global styles・WP 7.2の投影接続は未検証。'], proofs: [{ path: `${root}/verification.json`, row_names: allRows.filter(name => name.startsWith('AC-VALUE-03A')) }] }, 'WT-AC-NFR-VALUE-03B': { status: 'partial', scope: '段の増減、slug変更、settings上書きを拒否し、親の尺度を維持する負例を検査。', remaining: ['実theme.json・bridge・子テーマ・user global stylesの実更新境界は未接続。'], proofs: [{ path: `${root}/verification.json`, row_names: allRows.filter(name => name.startsWith('AC-VALUE-03B')) }] } });
  save(`${root}/value-acceptance-candidate.json`, { schema: 'wt-acceptance-candidate.v1', 'WT-AC-NFR-VALUE-03A': { status: 'partial', scope: '親の6段フォント・余白と2段幅を維持したまま、bridge投影が値だけを差し替える契約を検査。dimension preset、minWidth、background.gradientも同じslugの安全値として保持。', remaining: ['実theme.json・bridge・子テーマ・user global styles・WP 7.2の投影接続は未検証。'], proofs: [{ path: `${root}/verification.json`, row_names: allRows.filter(name => name.startsWith('AC-VALUE-03A')) }] }, 'WT-AC-NFR-VALUE-03B': { status: 'partial', scope: '段の増減、slug変更、settings上書きを拒否し、親の尺度を維持する負例を検査。', remaining: ['実theme.json・bridge・子テーマ・user global stylesの実更新境界は未接続。'], proofs: [{ path: `${root}/verification.json`, row_names: allRows.filter(name => name.startsWith('AC-VALUE-03B')) }] } });
  save(`${root}/catalog-candidates.json`, { schema: 'wt-recovery-contract-catalog-candidates.v1', entries: [{ id: 'recovery:transaction', face: 'admin', part: 'recovery-transaction', label: '変更回復：digest付き取引', variant: 'structure', description: '構造・スタイル・値・ゾーンを同じreceiptで変更し、rollbackで変更前digestへ戻す無装飾の契約PoC。2受入条件は部分確認。', purpose: '変更の影響を確かめて安全に戻す', group: '共通設定・部品', images: { pc: '../2026-09-20-recovery-contract-poc/structure-pc.jpg', sp: '../2026-09-20-recovery-contract-poc/structure-sp.jpg' }, requirementIds: ['WT-NFR-REC-01'], referenceId: 'wt-recovery-contract-poc.v1', evidence: '../2026-09-20-recovery-contract-poc/verification.json', selectionFacts: { '対象と判断': '4種のリソースを同一契約でdry-run/apply/rollbackできるか選ぶ候補。', '実測': '構造・スタイル・値・ゾーン各1 patch、before/after/restored digest、stale receiptと未知pathの拒否、1440/390px。', '対象外': '視覚デザインの採用、実WP保存、DB/API、権限、MCP/CLI、AI、credential、外部送信。', '未検証': '実WP 7.2での永続化・同時更新競合・全リソース横断のrollback。' } }, { id: 'recovery:token-projection', face: 'admin', part: 'token-projection', label: '尺度投影：6段の値差し替え', variant: 'value', description: 'bridge投影が親の6段フォント・余白、2段幅、安全値を維持し、段追加・削除・slug変更・settings上書きを拒否する契約PoC。', purpose: '尺度の所有権と投影境界を確かめる', group: '共通設定・部品', images: { pc: '../2026-09-20-recovery-contract-poc/value-pc.jpg', sp: '../2026-09-20-recovery-contract-poc/value-sp.jpg' }, requirementIds: ['WT-NFR-VALUE-03'], referenceId: 'wt-token-projection.v1', evidence: '../2026-09-20-recovery-contract-poc/verification.json', selectionFacts: { '対象と判断': '親の段数・slugを壊さず、bridgeが値だけを差し替えられるか選ぶ候補。', '実測': '6段フォント・6段余白・2段幅、dimension preset/minWidth/background.gradientのslug維持、段削除・slug変更・settings上書き拒否。', '対象外': '実theme.json・bridge・子テーマ・user global styles・WP 7.2接続、視覚デザインの採用。', '未検証': '実際の投影API・保存・複数正本の優先順・権限。' } }] });
  console.log(JSON.stringify({ completed: true, tests: rows.length, screenshots: shots.length, acceptance: '4 partial candidates' }));
} finally { fs.rmSync(staging, { recursive: true, force: true }); }
