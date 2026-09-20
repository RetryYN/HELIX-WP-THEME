import fs from 'node:fs';
import path from 'node:path';
import os from 'node:os';
import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import assert from 'node:assert/strict';

const root = 'docs/research/2026-09-20-lp-tracking-poc';
const sourceFiles = ['scripts/build-lp-tracking-poc.mjs', 'scripts/verify-lp-tracking-poc.mjs', 'tests/e2e/lp-tracking-poc-selection-catalog.spec.ts', root + '/model.mjs', root + '/tracking.mjs', root + '/style.css'];
const hash = file => createHash('sha256').update(fs.readFileSync(file)).digest('hex');
const save = (file, value) => fs.writeFileSync(file, JSON.stringify(value, null, 2) + '\n');
execFileSync(process.execPath, ['scripts/build-lp-tracking-poc.mjs'], { stdio: 'inherit' });
const trackingSource = fs.readFileSync(root + '/tracking.mjs', 'utf8');
for (const forbidden of ['fetch(', 'sendBeacon(', 'XMLHttpRequest', 'navigator.sendBeacon']) assert(!trackingSource.includes(forbidden), 'tracking fixture must not send externally');
const staging = fs.mkdtempSync(path.join(os.tmpdir(), 'lp-tracking-capture-'));
try {
  const args = ['playwright', 'test', 'tests/e2e/lp-tracking-poc-selection-catalog.spec.ts', '--workers=1', '--reporter=json'];
  const report = JSON.parse(execFileSync('npx', args, { encoding: 'utf8', maxBuffer: 16 * 1024 * 1024, env: { ...process.env, LP_TRACKING_CAPTURE_DIR: staging } }));
  const flatten = suites => suites.flatMap(suite => [...(suite.specs || []), ...flatten(suite.suites || [])]);
  const rows = flatten(report.suites).map(spec => ({ name: spec.title, pass: spec.ok && spec.tests.every(test => test.results.length === 1 && test.results[0].status === 'passed') }));
  assert(rows.length === 8 && rows.every(row => row.pass));
  const shots = ['pc', 'sp'].map(device => {
    const file = 'lp-tracking-' + device + '.jpg';
    assert(fs.statSync(path.join(staging, file)).size > 1000);
    fs.copyFileSync(path.join(staging, file), path.join(root, file));
    return { id: 'tracking-contract', device, file, sha256: hash(path.join(root, file)) };
  });
  const sourceDigests = Object.fromEntries(sourceFiles.map(file => [file, hash(file)]));
  save(root + '/verification.json', {
    schema: 'wt-lp-tracking-poc-verification.v1',
    completed: true,
    sourceDigests,
    rows,
    shots,
    scope: 'Static/local LP fixture. Form placement, LP variation/pattern IDs, versioned HELIX data-layer events for view/scroll/CTA/submit, required IDs, no-JS rendering, and theme-side optimization boundary are verified.',
    remaining: ['WordPress 7.2 persistence, real REST/MCP registration, permissions, and editor UI are unconnected.', 'External tracking destination, consent policy, deduplication, retries, real form transport, real-device accessibility, and all LP variations are unverified.', 'Optimization and decision logic remain external and are not implemented by this fixture.'],
  });
  const names = rows.map(row => row.name);
  const remaining = ['WordPress 7.2 persistence, real REST/MCP registration, permissions, and editor UI are unconnected.', 'External tracking, consent, deduplication, retries, transport, real-device accessibility, and all LP variations remain unverified.'];
  save(root + '/acceptance-candidate.json', {
    schema: 'wt-acceptance-candidate.v1',
    'WT-AC-LP-02A': { status: 'partial', scope: '静的LP fixtureでform slot・LP専用variation/patternと、表示・スクロール・CTA・送信のversion付きdata layerイベントを必須ID付きで記録する。', remaining, proofs: [{ path: root + '/verification.json', row_names: names.filter(name => name.startsWith('AC-LP-02A')) }] },
    'WT-AC-LP-02B': { status: 'partial', scope: '必須ID欠落、イベント集合不足、テーマ内最適化、外部送信を負例・静的境界で拒否する。', remaining, proofs: [{ path: root + '/verification.json', row_names: names.filter(name => name === 'AC-LP-02B rejects missing IDs, incomplete events, and theme-side optimization') }] },
  });
  const images = { pc: '../2026-09-20-lp-tracking-poc/lp-tracking-pc.jpg', sp: '../2026-09-20-lp-tracking-poc/lp-tracking-sp.jpg' };
  const evidence = '../2026-09-20-lp-tracking-poc/verification.json';
  save(root + '/catalog-candidates.json', {
    schema: 'wt-lp-tracking-catalog-candidates.v1',
    entries: [
      { id: 'lp-tracking:form-slot', face: 'lp', part: 'lp-form-tracking', label: 'LPフォーム：slotと必須ID付きイベント', variant: 'form-slot', description: 'LP専用form slotをJSONで宣言し、4種類の行動イベントへ同じLP/目標CV/variant IDを渡す静的PoC。', purpose: '行動につなげる', group: 'ページ・本文', images, requirementIds: ['WT-FR-LP-02'], referenceId: 'wt-lp-tracking-poc.v1', evidence, selectionFacts: { '対象と判断': 'フォーム配置とデータ層契約を同じLP正本から選ぶ。', '実測': 'form slot、variation/pattern、view/scroll/CTA/submit、必須ID、JS無効。', '未検証': remaining.join(' ') } },
      { id: 'lp-tracking:boundary', face: 'lp', part: 'lp-form-tracking', label: 'LP計測：外部送信・最適化の境界', variant: 'boundary', description: 'テーマ内に判定ロジックや外部送信を置かず、契約外イベントと必須ID欠落を拒否する静的PoC。', purpose: '信頼・納得をつくる', group: 'ページ・本文', images, requirementIds: ['WT-FR-LP-02'], referenceId: 'wt-lp-tracking-poc.v1', evidence, selectionFacts: { '対象と判断': 'テーマがCV判定や最適化を所有しない境界を選ぶ。', '実測': 'ID欠落、イベント集合不足、optimizationOwner改変、送信API不在。', '未検証': remaining.join(' ') } },
    ],
  });
  console.log(JSON.stringify({ completed: true, tests: rows.length, screenshots: shots.length, acceptance: '2 partial candidates' }));
} finally {
  fs.rmSync(staging, { recursive: true, force: true });
}
