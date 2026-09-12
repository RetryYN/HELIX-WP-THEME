import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { createHash } from 'node:crypto';

const hash = value => createHash('sha256').update(value).digest('hex');
function fixture(t) {
  const root = fs.mkdtempSync(path.join(os.tmpdir(), 'wt-evidence-test-'));
  t.after(() => fs.rmSync(root, { recursive: true, force: true }));
  function write(file, value) { const target = path.join(root, file); fs.mkdirSync(path.dirname(target), { recursive: true }); fs.writeFileSync(target, typeof value === 'string' ? value : JSON.stringify(value)); }
  write('scripts/audit-catalog-evidence.mjs', fs.readFileSync(new URL('../scripts/audit-catalog-evidence.mjs', import.meta.url), 'utf8'));
  write('docs/requirements/l3/requirements-ir.json', { requirements: [{ id: 'R1', semantic_digest: 'revision-1', acceptance_ids: ['A1'] }] });
  write('docs/requirements/l3/acceptance-cases.json', { cases: [{ id: 'A1', requirement_id: 'R1', oracle: 'Expected behavior', polarity: 'positive' }] });
  write('implementation.php', 'source-v1');
  const proof = { completed: true, rows: [{ name: 'scenario', pass: true }] };
  write('proof.json', proof);
  const record = { status: 'partial', requirement_digest: 'revision-1', oracle_sha256: hash('Expected behavior'),
    source_digests: { 'implementation.php': hash('source-v1') }, scope: 'One scenario', remaining: ['Other conditions'],
    proofs: [{ path: 'proof.json', sha256: hash(JSON.stringify(proof)), row_names: ['scenario'] }] };
  const save = data => write('docs/research/2026-09-08-selection-catalog/acceptance-evidence.json', { cases: data });
  save({ A1: record });
  function run() {
    const process = spawnSync('node', [path.join(root, 'scripts/audit-catalog-evidence.mjs')], { encoding: 'utf8' });
    const report = JSON.parse(fs.readFileSync(path.join(root, 'docs/research/2026-09-08-selection-catalog/acceptance-audit.json'), 'utf8'));
    return { status: process.status, report };
  }
  return { write, record, save, run };
}

test('successful partial evidence cannot claim overall completion; missing cases stay visible', t => {
  const f = fixture(t); let result = f.run();
  assert.equal(result.status, 0); assert.equal(result.report.counts.partial, 1); assert.equal(result.report.complete, false);
  f.save({}); result = f.run(); assert.equal(result.report.counts.missing, 1); assert.equal(result.report.complete, false);
});
test('source changes invalidate otherwise successful proof', t => {
  const f = fixture(t); f.write('implementation.php', 'source-v2'); const result = f.run();
  assert.equal(result.status, 1); assert.equal(result.report.counts.stale, 1);
});
test('changed acceptance wording requires reinspection', t => {
  const f = fixture(t); f.write('docs/requirements/l3/acceptance-cases.json', { cases: [{ id: 'A1', requirement_id: 'R1', oracle: 'Stronger behavior', polarity: 'positive' }] });
  assert.equal(f.run().report.counts.stale, 1);
});
test('an interrupted run remains invalid even when all recorded rows pass', t => {
  const f = fixture(t); const proof = { completed: false, rows: [{ name: 'scenario', pass: true }] };
  f.write('proof.json', proof); f.record.proofs[0].sha256 = hash(JSON.stringify(proof)); f.save({ A1: f.record });
  assert.equal(f.run().report.counts.stale, 1);
});
test('a referenced failing row and unresolved conditions cannot be promoted', t => {
  const f = fixture(t); const proof = { completed: true, rows: [{ name: 'scenario', pass: false }] };
  f.write('proof.json', proof); f.record.proofs[0].sha256 = hash(JSON.stringify(proof)); f.record.status = 'verified_in_poc'; f.save({ A1: f.record });
  const result = f.run(); assert.equal(result.status, 1); assert.equal(result.report.complete, false);
});

test('remaining conditions prevent completion even with unchanged passing proof', t => {
  const f = fixture(t); f.record.status = 'verified_in_poc'; f.save({ A1: f.record });
  const result = f.run(); assert.equal(result.report.counts.stale, 1); assert.equal(result.report.complete, false);
});
