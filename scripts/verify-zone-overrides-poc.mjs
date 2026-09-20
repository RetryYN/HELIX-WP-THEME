import fs from 'node:fs';
import { createHash } from 'node:crypto';
import assert from 'node:assert/strict';
import { cases, fixture } from '../docs/research/2026-09-20-zone-overrides-poc/contract.mjs';

const root = 'docs/research/2026-09-20-zone-overrides-poc';
const sourceFiles = ['scripts/verify-zone-overrides-poc.mjs', `${root}/contract.mjs`];
const digest = file => createHash('sha256').update(fs.readFileSync(file)).digest('hex');
const rows = [];
for (const [name, check] of cases) {
  let pass = false;
  try { pass = Boolean(check()); } catch { pass = false; }
  rows.push({ name, pass });
  assert.equal(pass, true, name);
}
const report = {
  schema: 'wt-zone-overrides-poc-verification.v1', completed: rows.every(row => row.pass),
  sourceDigests: Object.fromEntries(sourceFiles.map(file => [file, digest(file)])),
  rows, fixture: { schema: fixture.schema, declaredZoneCount: 23, testedZoneIds: fixture.zones.map(zone => zone.id) },
  scope: 'Pure local schema and first-match resolver contract; no WordPress REST, CPT, editor, persistence, permission, or production creative rendering.',
  remaining: ['実WP 7.2のREST保存・権限・管理UI・MCP/CLI接続、全23面の実登録、creative実体の公開描画、同時更新競合は未接続。'],
};
fs.writeFileSync(`${root}/verification.json`, `${JSON.stringify(report, null, 2)}\n`);
console.log(JSON.stringify({ completed: report.completed, tests: rows.length, failed: rows.filter(row => !row.pass) }));
