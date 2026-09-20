import fs from 'node:fs';
import path from 'node:path';
import os from 'node:os';
import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import assert from 'node:assert/strict';

const root = 'docs/research/2026-09-20-lp-routing-poc';
const sourceFiles = ['scripts/build-lp-routing-poc.mjs', 'scripts/verify-lp-routing-poc.mjs', 'tests/e2e/lp-routing-poc-selection-catalog.spec.ts', `${root}/model.mjs`, `${root}/style.css`];
const hash = file => createHash('sha256').update(fs.readFileSync(file)).digest('hex');
const save = (file, value) => fs.writeFileSync(file, `${JSON.stringify(value, null, 2)}\n`);
execFileSync(process.execPath, ['scripts/build-lp-routing-poc.mjs'], { stdio: 'inherit' });
const staging = fs.mkdtempSync(path.join(os.tmpdir(), 'lp-routing-capture-'));
try {
  const args = ['playwright', 'test', 'tests/e2e/lp-routing-poc-selection-catalog.spec.ts', '--workers=1', '--reporter=json'];
  const report = JSON.parse(execFileSync('npx', args, { encoding: 'utf8', maxBuffer: 16 * 1024 * 1024, env: { ...process.env, LP_ROUTING_CAPTURE_DIR: staging } }));
  const flatten = suites => suites.flatMap(s => [...(s.specs || []), ...flatten(s.suites || [])]);
  const rows = flatten(report.suites).map(spec => ({ name: spec.title, pass: spec.ok && spec.tests.every(test => test.results.length === 1 && test.results[0].status === 'passed') }));
  assert(rows.length === 8 && rows.every(row => row.pass));
  const shots = ['pc', 'sp'].map(device => { const file = `lp-routing-${device}.jpg`; const source = path.join(staging, file); assert(fs.statSync(source).size > 1000); fs.copyFileSync(source, path.join(root, file)); return { id: 'routing', device, file, sha256: hash(path.join(root, file)) }; });
  const sourceDigests = Object.fromEntries(sourceFiles.map(file => [file, hash(file)]));
  save(`${root}/verification.json`, { schema: 'wt-lp-routing-poc-verification.v1', completed: true, sourceDigests, rows, shots, scope: 'Static/local routing contract. Manifest, REST-shaped projection, flat slugs, independent event/comparison kinds, BLP→LP purpose relation, and negative route guards are verified. No WP 7.2 persistence, real REST, permissions, form submission, or external CV.', remaining: ['実WP 7.2でのCPT登録・保存・show_in_rest公開・リライトルール・権限を未接続。','実フォームの表示/送信イベント、A/B variant、CV計測IDのデータ層連携はWT-FR-LP-02で別途検証する。'] });
  const names = rows.map(row => row.name);
  const proofs = [{ path: `${root}/verification.json`, row_names: names }];
  const remaining = ['実WP 7.2のCPT/REST/保存/権限/リライトルールは未接続。','静的JSONの契約を実運用の投稿編集画面や公開APIの成功とは扱わない。'];
  save(`${root}/acceptance-candidate.json`, { schema: 'wt-acceptance-candidate.v1', 'WT-AC-LP-01A': { status: 'partial', scope: 'LP・イベント・比較特設・BLPのmanifestとREST形投影を作り、LP系の非階層スラッグ、固定ページの階層URL、PC/SP・JS無効の表示を検査。', remaining, proofs }, 'WT-AC-LP-01B': { status: 'partial', scope: '階層化LP、階層URL、イベント/比較種別のLPへの畳み込みを負例として拒否。', remaining, proofs }, 'WT-AC-LP-01C': { status: 'partial', scope: 'BLPは判断材料と送客、LPはCV目的、イベント/比較は独立種別としてmanifestで識別できることを検査。', remaining, proofs } });
  const catalogRoot = '../2026-09-20-lp-routing-poc';
  const images = { pc: `${catalogRoot}/lp-routing-pc.jpg`, sp: `${catalogRoot}/lp-routing-sp.jpg` };
  const evidence = `${catalogRoot}/verification.json`;
  save(`${root}/catalog-candidates.json`, { schema: 'wt-lp-routing-catalog-candidates.v1', entries: [{ id: 'lp-routing:normal', face: 'lp', part: 'content-lp-routing', label: '通常LP：非階層URLとCV目的', variant: 'normal', description: '通常LPを非階層スラッグで管理し、BLPから別目的のCV面へ送る静的契約PoC。', purpose: '行動につなげる', group: 'ページ・本文', images, requirementIds: ['WT-FR-LP-01'], referenceId: 'wt-lp-routing-poc.v1', evidence, selectionFacts: { '対象と判断': 'LPを固定ページ階層から分離し、CV目的を持たせる。', '実測': 'manifest、REST投影、非階層URL、BLP→LP、PC/SP、JS無効、負例。', '未検証': remaining.join(' ') } }, { id: 'lp-routing:event', face: 'event', part: 'content-lp-routing', label: 'イベント：独立管理種別', variant: 'event', description: 'イベントをLPへ一律に畳み込まず、独立した種別として列挙する静的契約PoC。', purpose: '行動につなげる', group: 'ページ・本文', images, requirementIds: ['WT-FR-LP-01'], referenceId: 'wt-lp-routing-poc.v1', evidence, selectionFacts: { '対象と判断': '開催情報と申込の管理目的をLPと識別する。', '実測': 'wt_eventのmanifest/REST種別、非階層URL、畳み込み拒否。', '未検証': remaining.join(' ') } }, { id: 'lp-routing:comparison', face: 'comparison', part: 'content-lp-routing', label: '比較特設：独立管理種別', variant: 'comparison', description: '比較特設を独立種別として管理し、BLP・通常LPの目的差を選べる静的契約PoC。', purpose: '信頼・納得をつくる', group: 'ページ・本文', images, requirementIds: ['WT-FR-LP-01'], referenceId: 'wt-lp-routing-poc.v1', evidence, selectionFacts: { '対象と判断': '比較検討のための特設案内を通常LPと分ける。', '実測': 'wt_comparisonのmanifest/REST種別、非階層URL、BLP/LP目的差。', '未検証': remaining.join(' ') } }] });
  console.log(JSON.stringify({ completed: true, tests: rows.length, acceptance: '3 partial candidates' }));
} finally { fs.rmSync(staging, { recursive: true, force: true }); }
