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

test('the npm catalog evidence test command includes admission coverage', () => {
  const root = new URL('../', import.meta.url);
  const packageJson = JSON.parse(fs.readFileSync(new URL('package.json', root), 'utf8'));
  assert.match(packageJson.scripts['catalog-evidence:rebind-test'], /tests\/acceptance-rebind\.test\.mjs/u);
  const rebindTest = fs.readFileSync(new URL('tests/acceptance-rebind.test.mjs', root), 'utf8');
  assert.match(rebindTest, /\.\/acceptance-admission\.test\.mjs/u);
});

test('selection index exposes evidence boundaries for every catalog candidate', () => {
  const root = new URL('../', import.meta.url);
  const catalog = JSON.parse(fs.readFileSync(new URL('docs/research/2026-09-08-selection-catalog/catalog-data.json', root), 'utf8'));
  const index = JSON.parse(fs.readFileSync(new URL('docs/research/2026-09-08-selection-catalog/selection-index.json', root), 'utf8'));
  const projection = JSON.parse(fs.readFileSync(new URL('docs/requirements/discovery/candidate-projection.json', root), 'utf8'));
  assert.equal(index.schema, 'wt-selection-index.v1');
  assert.equal(index.generatedAtEventHead, projection.event_head);
  assert.equal(index.candidateCount, catalog.entries.length);
  assert.equal(index.entries.length, catalog.entries.length);
  const catalogIds = new Set(catalog.entries.map(entry => entry.id));
  const indexIds = new Set(index.entries.map(entry => entry.id));
  assert.equal(indexIds.size, index.entries.length);
  assert.deepEqual([...indexIds].sort(), [...catalogIds].sort());
  for (const entry of index.entries) {
    const coverage = entry.selectionCoverage;
    assert.ok(['unmapped', 'missing', 'partial', 'stale', 'verified_in_poc'].includes(coverage.status));
    assert.equal(Object.values(coverage.counts).reduce((sum, count) => sum + count, 0), coverage.acceptanceCount);
    assert.deepEqual(coverage.openAcceptanceIds, [...coverage.openAcceptanceIds].sort());
    assert.ok(entry.devices.every(device => ['pc', 'sp'].includes(device)));
  }
  const requirementIds = new Set(index.requirements.map(requirement => requirement.id));
  assert.equal(requirementIds.size, catalog.requirements.length);
  for (const requirement of index.requirements) {
    const catalogRequirement = catalog.requirements.find(candidate => candidate.id === requirement.id);
    assert.ok(catalogRequirement);
    assert.deepEqual(requirement.coverage, catalogRequirement.coverage);
    assert.equal(requirement.coverage.candidateCount, requirement.relatedEntryIds.length);
    const openAcceptanceIds = catalogRequirement.acceptance
      .filter(row => ['missing', 'stale'].includes(row.status))
      .map(row => row.id)
      .sort();
    const partialCount = catalogRequirement.acceptance
      .filter(row => row.status === 'partial').length;
    const expectedSelectionState = !catalogRequirement.relatedEntryIds.length
      ? 'no_candidate'
      : openAcceptanceIds.length
        ? 'candidate_with_open_acceptance'
        : partialCount
          ? 'candidate_with_partial_evidence'
          : 'candidate_verified';
    assert.deepEqual(requirement.coverage.openAcceptanceIds, openAcceptanceIds);
    assert.equal(requirement.coverage.selectionState, expectedSelectionState);
    assert.equal(Object.values(requirement.coverage.counts).reduce((sum, count) => sum + count, 0), requirement.coverage.acceptanceCount);
    assert.ok(requirement.relatedEntryIds.every(id => catalogIds.has(id)));
    assert.ok(requirement.acceptance.every(row => ['missing', 'partial', 'stale', 'verified_in_poc'].includes(row.status)));
  }
});

test('catalog data stays aligned with canonical requirement and acceptance counts', () => {
  const root = new URL('../', import.meta.url);
  const ir = JSON.parse(fs.readFileSync(new URL('docs/requirements/l3/requirements-ir.json', root), 'utf8'));
  const cases = JSON.parse(fs.readFileSync(new URL('docs/requirements/l3/acceptance-cases.json', root), 'utf8'));
  const catalog = JSON.parse(fs.readFileSync(new URL('docs/research/2026-09-08-selection-catalog/catalog-data.json', root), 'utf8'));
  const catalogAcceptanceRows = catalog.requirements.flatMap(requirement => requirement.acceptance);
  const auditAcceptanceCount = Object.values(catalog.acceptanceAudit).reduce((sum, count) => sum + count, 0);

  assert.equal(catalog.requirementCount, ir.requirements.length);
  assert.equal(catalog.requirements.length, ir.requirements.length);
  assert.equal(catalogAcceptanceRows.length, cases.cases.length);
  assert.equal(auditAcceptanceCount, cases.cases.length);
  assert.deepEqual(
    [...new Set(catalog.requirements.map(requirement => requirement.id))].sort(),
    [...new Set(ir.requirements.map(requirement => requirement.id))].sort(),
  );
  assert.deepEqual(
    [...new Set(catalogAcceptanceRows.map(acceptance => acceptance.id))].sort(),
    [...new Set(cases.cases.map(acceptance => acceptance.id))].sort(),
  );
});
