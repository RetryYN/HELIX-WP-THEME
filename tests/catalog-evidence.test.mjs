import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import { fileURLToPath } from 'node:url';
import { writeGenerated } from '../scripts/lib/generated-output.mjs';
import { buildFanout } from '../scripts/report-acceptance-evidence-fanout.mjs';

const hash = value => createHash('sha256').update(value).digest('hex');
function fixture(t) {
  const root = fs.mkdtempSync(path.join(os.tmpdir(), 'wt-evidence-test-'));
  t.after(() => fs.rmSync(root, { recursive: true, force: true }));
  function write(file, value) { const target = path.join(root, file); fs.mkdirSync(path.dirname(target), { recursive: true }); fs.writeFileSync(target, typeof value === 'string' ? value : JSON.stringify(value)); }
  write('scripts/audit-catalog-evidence.mjs', fs.readFileSync(new URL('../scripts/audit-catalog-evidence.mjs', import.meta.url), 'utf8'));
  write('scripts/lib/generated-output.mjs', fs.readFileSync(new URL('../scripts/lib/generated-output.mjs', import.meta.url), 'utf8'));
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
  function run(args = []) {
    const process = spawnSync('node', [path.join(root, 'scripts/audit-catalog-evidence.mjs'), ...args], { encoding: 'utf8' });
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
test('report-only audit mode keeps stale evidence visible without blocking catalog projection', t => {
  const f = fixture(t); f.write('implementation.php', 'source-v2'); const result = f.run(['--allow-stale']);
  assert.equal(result.status, 0); assert.equal(result.report.counts.stale, 1); assert.equal(result.report.complete, false);
});

test('current-theme quality gate runs strict evidence audit for verifier changes on push and pull requests', () => {
  const workflow = fs.readFileSync(new URL('../.github/workflows/theme-quality-gate.yml', import.meta.url), 'utf8');
  const packageJson = JSON.parse(fs.readFileSync(new URL('../package.json', import.meta.url), 'utf8'));
  const oracleConfig = JSON.parse(fs.readFileSync(new URL('../config/catalog-admission-oracles.json', import.meta.url), 'utf8'));
  const push = workflow.match(/^  push:\n([\s\S]*?)^  pull_request:/mu)?.[1];
  const pullRequest = workflow.match(/^  pull_request:\n([\s\S]*?)^  workflow_dispatch:/mu)?.[1];

  assert.ok(push, 'missing push path filter');
  assert.ok(pullRequest, 'missing pull_request path filter');
  for (const [event, paths] of [['push', push], ['pull_request', pullRequest]]) {
    assert.match(paths, /^      - 'scripts\/\*\*\/\*\.mjs'$/mu, `${event} must cover script oracles`);
    assert.match(paths, /^      - 'docs\/research\/\*\*\/\*\.mjs'$/mu, `${event} must cover research script oracles`);
  }

  const oracleSources = new Set();
  const addSource = (source, context) => {
    assert.match(source, /^(?:scripts|docs\/research)\/.+\.(?:mjs|js|cjs)$/u,
      `${context} oracle source needs a covered scripts/ or docs/research/ path: ${source}`);
    oracleSources.add(source);
  };
  const collectScriptSources = (script, context) => {
    const sources = [...script.matchAll(/(?:^|\s)([^\s"'`]+\.(?:mjs|js|cjs))(?=\s|$)/gu)].map(match => match[1]);
    assert.ok(sources.length > 0, `${context} must resolve to a JavaScript oracle source`);
    for (const source of sources) addSource(source, context);
  };
  const collectCommandSources = (command, context) => {
    if (command?.[0] === 'node') {
      const sources = command.slice(1).filter(argument => /\.(?:mjs|js|cjs)$/u.test(argument));
      assert.ok(sources.length > 0, `${context} node command must identify a JavaScript oracle source`);
      for (const source of sources) addSource(source, context);
      return;
    }
    if (command?.[0] === 'npm' && command[1] === 'run') {
      const script = packageJson.scripts[command[2]];
      assert.ok(script, `${context} references missing package script ${command[2]}`);
      collectScriptSources(script, context);
      return;
    }
    assert.fail(`${context} uses an unsupported catalog oracle command: ${JSON.stringify(command)}`);
  };
  for (const [proof, command] of Object.entries(oracleConfig.commands)) collectCommandSources(command, proof);
  for (const [proof, scriptName] of Object.entries(packageJson.catalogOracles || {})) {
    const script = packageJson.scripts[scriptName];
    assert.ok(script, `${proof} references missing package script ${scriptName}`);
    collectScriptSources(script, proof);
  }
  assert.ok(oracleSources.size > 0, 'no catalog oracle source scripts discovered');
  for (const source of oracleSources) {
    const extension = path.extname(source).slice(1);
    const root = source.startsWith('scripts/') ? 'scripts' : 'docs/research';
    const glob = `${root}/**/*.${extension}`;
    for (const [event, paths] of [['push', push], ['pull_request', pullRequest]]) {
      assert.ok(paths.includes(`      - '${glob}'`), `${event} does not cover ${source} with ${glob}`);
    }
  }

  assert.match(workflow, /^          node scripts\/audit-catalog-evidence\.mjs$/mu, 'CI must run the fail-closed audit');
  assert.doesNotMatch(workflow, /^          node scripts\/audit-catalog-evidence\.mjs --allow-stale$/mu);
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

test('theme CSS impact inventory stays aligned with source-bound acceptance proofs', () => {
  const root = new URL('../', import.meta.url);
  const evidence = JSON.parse(fs.readFileSync(new URL('docs/research/2026-09-08-selection-catalog/acceptance-evidence.json', root), 'utf8'));
  const impact = JSON.parse(fs.readFileSync(new URL('docs/research/2026-09-19-toc-settings/revalidation-impact.json', root), 'utf8'));
  const packageJson = JSON.parse(fs.readFileSync(new URL('package.json', root), 'utf8'));
  const configuredCommands = JSON.parse(fs.readFileSync(new URL('config/catalog-admission-oracles.json', root), 'utf8')).commands;
  const source = impact.changedSource;
  const affected = Object.entries(evidence.cases)
    .filter(([, record]) => Object.hasOwn(record.source_digests || {}, source))
    .map(([ac, record]) => ({ ac, proofs: [...new Set(record.proofs.map(proof => proof.path))] }));
  const proofPaths = [...new Set(affected.flatMap(record => record.proofs))].sort();
  const recordedAffected = [...impact.affected]
    .map(record => ({ ac: record.ac, proofs: [...new Set(record.proofs)].sort() }))
    .sort((a, b) => a.ac.localeCompare(b.ac));
  const expectedAffected = affected
    .map(record => ({ ac: record.ac, proofs: record.proofs.sort() }))
    .sort((a, b) => a.ac.localeCompare(b.ac));
  const recordedProofs = impact.proofVerifiers.map(record => record.proof).sort();

  assert.equal(affected.length, 30);
  assert.equal(proofPaths.length, 31);
  assert.deepEqual(recordedAffected, expectedAffected);
  assert.deepEqual(recordedProofs, proofPaths);
  for (const record of impact.proofVerifiers) {
    const configuredCommand = configuredCommands[record.proof];
    const packageOracleName = packageJson.catalogOracles?.[record.proof];
    assert.ok(configuredCommand || packageOracleName, `missing catalog oracle declaration for ${record.proof}`);

    for (const sourceFile of record.verifierSources) {
      assert.ok(fs.existsSync(new URL(sourceFile, root)), `missing proof verifier source ${sourceFile}`);
    }

    if (configuredCommand) {
      const sourceArguments = configuredCommand.filter(argument => /\.(?:mjs|cjs|js)$/u.test(argument));
      assert.ok(
        sourceArguments.some(sourceFile => record.verifierSources.includes(sourceFile)),
        `configured oracle does not match verifier sources for ${record.proof}`,
      );
    }

    if (packageOracleName) {
      const packageCommand = packageJson.scripts[packageOracleName];
      assert.ok(packageCommand, `missing package oracle script ${packageOracleName}`);
      const sourceArguments = packageCommand.split(/\s+/u).filter(argument => /\.(?:mjs|cjs|js)$/u.test(argument));
      assert.ok(
        sourceArguments.some(sourceFile => record.verifierSources.includes(sourceFile)),
        `package oracle does not match verifier sources for ${record.proof}`,
      );
    }
  }
});

test('the generated catalog matches current sources without rewriting tracked outputs', () => {
  const repoRoot = fileURLToPath(new URL('../', import.meta.url));
  const files = ['acceptance-audit.json', 'catalog-data.json', 'selection-index.json', 'completion-backlog.json', 'completion-backlog.md', 'README.md']
    .map(name => path.join(repoRoot, 'docs/research/2026-09-08-selection-catalog', name));
  const before = files.map(file => hash(fs.readFileSync(file)));
  const run = spawnSync(process.execPath, ['scripts/build-selection-catalog.mjs'], {
    cwd: repoRoot,
    encoding: 'utf8',
    env: { ...process.env, CATALOG_GENERATED_CHECK: '1' },
  });
  assert.equal(run.status, 0, run.stderr || run.stdout);
  assert.deepEqual(files.map(file => hash(fs.readFileSync(file))), before);
});

test('generated-output check rejects stale bytes without overwriting them', t => {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'wt-generated-check-'));
  t.after(() => fs.rmSync(dir, { recursive: true, force: true }));
  const file = path.join(dir, 'catalog-data.json');
  fs.writeFileSync(file, 'old\n');
  assert.throws(() => writeGenerated(file, 'new\n', { check: true }), /out of date/u);
  assert.equal(fs.readFileSync(file, 'utf8'), 'old\n');
});

test('declared acceptance fanout counts each case once per source and sorts deterministically', () => {
  const report = buildFanout({ cases: {
    B: { source_digests: { 'theme.css': 'one', 'functions.php': 'two' } },
    A: { source_digests: { 'theme.css': 'three' } },
    C: { source_digests: { 'functions.php': 'four' } },
  } });
  assert.equal(report.case_count, 3);
  assert.deepEqual(report.bindings, [
    { path: 'functions.php', case_count: 2, case_ids: ['B', 'C'] },
    { path: 'theme.css', case_count: 2, case_ids: ['A', 'B'] },
  ]);
  assert.match(report.interpretation, /does not claim runtime or behavioral impact/u);
});

test('current acceptance fanout baseline makes the largest recorded dependencies visible', () => {
  const registry = JSON.parse(fs.readFileSync(new URL('../docs/research/2026-09-08-selection-catalog/acceptance-evidence.json', import.meta.url), 'utf8'));
  const report = buildFanout(registry);
  assert.equal(report.case_count, 137);
  assert.equal(report.bindings[0].path, 'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/functions.php');
  assert.equal(report.bindings[0].case_count, 64);
  assert.equal(report.bindings.find(item => item.path.endsWith('/assets/css/theme.css'))?.case_count, 30);
});

test('acceptance fanout rejects malformed source bindings', () => {
  assert.throws(() => buildFanout({ cases: { A: { source_digests: [] } } }), /source_digests must be an object/u);
});
