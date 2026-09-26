import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import './acceptance-admission.test.mjs';

const hash = value => createHash('sha256').update(value).digest('hex');

function fixture(t) {
  const root = fs.mkdtempSync(path.join(os.tmpdir(), 'wt-rebind-test-'));
  t.after(() => fs.rmSync(root, { recursive: true, force: true }));
  const write = (file, value) => {
    const target = path.join(root, file);
    fs.mkdirSync(path.dirname(target), { recursive: true });
    fs.writeFileSync(target, typeof value === 'string' ? value : `${JSON.stringify(value, null, 2)}\n`);
  };
  write('scripts/rebind-acceptance-evidence.mjs', fs.readFileSync(new URL('../scripts/rebind-acceptance-evidence.mjs', import.meta.url), 'utf8'));
  write('implementation.php', 'source-v1\n');
  const proof = { completed: true, rows: [{ name: 'scenario', pass: true }] };
  proof.sourceDigests = { 'implementation.php': hash('source-v1\n'), 'new-source.txt': hash('new-source\n') };
  write('proof.json', proof);
  write('new-source.txt', 'new-source\n');
  write('docs/research/2026-09-08-selection-catalog/acceptance-evidence.json', {
    schema: 'wt-acceptance-evidence.v1',
    cases: {
      A1: {
        status: 'partial', requirement_digest: 'r1', oracle_sha256: hash('oracle'),
        source_digests: { 'implementation.php': hash('source-v1\n') }, scope: 'scenario', remaining: ['more'],
        proofs: [{ path: 'proof.json', sha256: hash(`${JSON.stringify(proof, null, 2)}\n`), row_names: ['scenario'] }],
      },
    },
  });
  write('docs/research/2026-09-08-selection-catalog/acceptance-rebind-log.json', { schema: 'wt-acceptance-rebind-log.v1', transactions: [] });
  write('rewrite-proof.mjs', "import fs from 'node:fs'; import { createHash } from 'node:crypto'; const p=JSON.parse(fs.readFileSync('proof.json')); p.sourceDigests['implementation.php']=createHash('sha256').update(fs.readFileSync('implementation.php')).digest('hex'); fs.writeFileSync('proof.json', JSON.stringify(p, null, 2)+'\\n');\n");
  write('package.json', {
    scripts: { 'fixture:verify': 'node rewrite-proof.mjs' },
    catalogOracles: { 'proof.json': 'fixture:verify' },
  });
  for (const args of [['init'], ['config', 'user.email', 'test@example.invalid'], ['config', 'user.name', 'Test'], ['add', '.'], ['commit', '-m', 'base']]) {
    assert.equal(spawnSync('git', args, { cwd: root }).status, 0);
  }
  const run = args => spawnSync('node', ['scripts/rebind-acceptance-evidence.mjs', ...args], { cwd: root, encoding: 'utf8', env: { ...process.env, CI: '' } });
  return { root, run, write };
}

function fixtureWithSecondaryProof(t) {
  const f = fixture(t);
  const registryPath = path.join(f.root, 'docs/research/2026-09-08-selection-catalog/acceptance-evidence.json');
  const registry = JSON.parse(fs.readFileSync(registryPath, 'utf8'));
  const secondary = { completed: true, rows: [{ name: 'secondary scenario', pass: true }], sourceDigests: { 'unrelated.txt': hash('unrelated-v1\n') } };
  f.write('unrelated.txt', 'unrelated-v1\n');
  f.write('secondary-proof.json', secondary);
  f.write('rewrite-secondary-proof.mjs', "import fs from 'node:fs'; import { createHash } from 'node:crypto'; const proof=JSON.parse(fs.readFileSync('secondary-proof.json')); proof.refresh='rerun'; proof.sourceDigests['unrelated.txt']=createHash('sha256').update(fs.readFileSync('unrelated.txt')).digest('hex'); fs.writeFileSync('secondary-proof.json', JSON.stringify(proof, null, 2)+'\\n');\n");
  registry.cases.A1.source_digests['new-source.txt'] = hash('new-source\n');
  registry.cases.A1.source_digests['unrelated.txt'] = hash('unrelated-v1\n');
  registry.cases.A1.proofs.push({ path: 'secondary-proof.json', sha256: hash(`${JSON.stringify(secondary, null, 2)}\n`), row_names: ['secondary scenario'] });
  f.write('docs/research/2026-09-08-selection-catalog/acceptance-evidence.json', registry);
  const packageJson = JSON.parse(fs.readFileSync(path.join(f.root, 'package.json'), 'utf8'));
  packageJson.scripts['fixture:secondary'] = 'node rewrite-secondary-proof.mjs';
  packageJson.catalogOracles['secondary-proof.json'] = 'fixture:secondary';
  f.write('package.json', packageJson);
  assert.equal(spawnSync('git', ['add', '.'], { cwd: f.root }).status, 0);
  assert.equal(spawnSync('git', ['commit', '-m', 'add independent proof fixture'], { cwd: f.root }).status, 0);
  return f;
}

function fixtureWithUnboundCaseSource(t) {
  const f = fixture(t);
  f.write('orphan.txt', 'orphan-v1\n');
  const registryPath = path.join(f.root, 'docs/research/2026-09-08-selection-catalog/acceptance-evidence.json');
  const registry = JSON.parse(fs.readFileSync(registryPath, 'utf8'));
  registry.cases.A1.source_digests['orphan.txt'] = hash('orphan-v1\n');
  f.write('docs/research/2026-09-08-selection-catalog/acceptance-evidence.json', registry);
  assert.equal(spawnSync('git', ['add', '.'], { cwd: f.root }).status, 0);
  assert.equal(spawnSync('git', ['commit', '-m', 'record unbound source fixture'], { cwd: f.root }).status, 0);
  return f;
}

test('dry-run reports exact stale source and leaves registry and proof unchanged', t => {
  const f = fixture(t);
  f.write('implementation.php', 'source-v2\n');
  const registry = fs.readFileSync(path.join(f.root, 'docs/research/2026-09-08-selection-catalog/acceptance-evidence.json'));
  const proof = fs.statSync(path.join(f.root, 'proof.json')).mtimeMs;
  const result = f.run(['--case', 'A1']);
  assert.equal(result.status, 0, result.stderr);
  assert.match(result.stdout, /"mode": "dry-run"/u);
  assert.match(result.stdout, /implementation\.php/u);
  assert.deepEqual(fs.readFileSync(path.join(f.root, 'docs/research/2026-09-08-selection-catalog/acceptance-evidence.json')), registry);
  assert.equal(fs.statSync(path.join(f.root, 'proof.json')).mtimeMs, proof);
});

test('selected-proof rebind refreshes one proof and preserves unchanged sibling proof bindings', async t => {
  const f = fixtureWithSecondaryProof(t);
  f.write('implementation.php', 'source-v2\n');
  await new Promise(resolve => setTimeout(resolve, 5));
  const result = f.run(['--case', 'A1', '--proof', 'proof.json', '--command-json', '["npm","run","fixture:verify"]', '--apply']);
  assert.equal(result.status, 0, result.stderr);
  const registry = JSON.parse(fs.readFileSync(path.join(f.root, 'docs/research/2026-09-08-selection-catalog/acceptance-evidence.json'), 'utf8'));
  assert.equal(registry.cases.A1.source_digests['implementation.php'], hash('source-v2\n'));
  assert.equal(registry.cases.A1.source_digests['unrelated.txt'], hash('unrelated-v1\n'));
  const log = JSON.parse(fs.readFileSync(path.join(f.root, 'docs/research/2026-09-08-selection-catalog/acceptance-rebind-log.json'), 'utf8'));
  assert.deepEqual(log.transactions[0].proof_writes.map(write => write.path), ['proof.json']);
  const checked = f.run(['--check', '--base-ref', 'HEAD']);
  assert.equal(checked.status, 0, checked.stderr || checked.stdout);
});

test('selected-proof rebind rejects a stale source owned by an unselected sibling proof', t => {
  const f = fixtureWithSecondaryProof(t);
  f.write('unrelated.txt', 'unrelated-v2\n');
  const result = f.run(['--case', 'A1', '--proof', 'proof.json', '--command-json', '["npm","run","fixture:verify"]', '--apply']);
  assert.equal(result.status, 1);
  assert.match(result.stderr, /stale source in unselected proof; select it for revalidation: secondary-proof\.json \/ unrelated\.txt/u);
});

test('rebind rejects dropping a stale registry source that no proof accounts for', async t => {
  const f = fixtureWithUnboundCaseSource(t);
  f.write('orphan.txt', 'orphan-v2\n');
  await new Promise(resolve => setTimeout(resolve, 5));
  const result = f.run(['--case', 'A1', '--proof', 'proof.json', '--command-json', '["npm","run","fixture:verify"]', '--apply']);
  assert.equal(result.status, 1);
  assert.match(result.stderr, /stale source is not accounted for by a registered proof: A1 \/ orphan\.txt/u);
  const registry = JSON.parse(fs.readFileSync(path.join(f.root, 'docs/research/2026-09-08-selection-catalog/acceptance-evidence.json'), 'utf8'));
  assert.equal(registry.cases.A1.source_digests['orphan.txt'], hash('orphan-v1\n'));
});

test('explicitly detaching an obsolete case source records a reason and is provenance checked', async t => {
  const f = fixtureWithUnboundCaseSource(t);
  f.write('orphan.txt', 'orphan-v2\n');
  await new Promise(resolve => setTimeout(resolve, 5));
  const detachment = JSON.stringify({ case: 'A1', source: 'orphan.txt', reason: 'The source was incorrectly attached to this proof and is not read by its registered oracle.' });
  const result = f.run(['--case', 'A1', '--proof', 'proof.json', '--detach-source-json', detachment, '--command-json', '["npm","run","fixture:verify"]', '--apply']);
  assert.equal(result.status, 0, result.stderr);
  const registryPath = path.join(f.root, 'docs/research/2026-09-08-selection-catalog/acceptance-evidence.json');
  const registry = JSON.parse(fs.readFileSync(registryPath, 'utf8'));
  assert.equal(registry.cases.A1.source_digests['orphan.txt'], undefined);
  const log = JSON.parse(fs.readFileSync(path.join(f.root, 'docs/research/2026-09-08-selection-catalog/acceptance-rebind-log.json'), 'utf8'));
  assert.deepEqual(log.transactions[0].source_detachments, [JSON.parse(detachment)]);
  assert.ok(log.transactions[0].changes.some(change => change.kind === 'source' && change.path === 'orphan.txt' && change.after === null));
  const checked = f.run(['--check', '--base-ref', 'HEAD']);
  assert.equal(checked.status, 0, checked.stderr || checked.stdout);
});

test('source detachment rejects a source still declared by the regenerated proof', async t => {
  const f = fixture(t);
  f.write('implementation.php', 'source-v2\n');
  await new Promise(resolve => setTimeout(resolve, 5));
  const detachment = JSON.stringify({ case: 'A1', source: 'implementation.php', reason: 'test invalid detach' });
  const result = f.run(['--case', 'A1', '--detach-source-json', detachment, '--command-json', '["npm","run","fixture:verify"]', '--apply']);
  assert.equal(result.status, 1);
  assert.match(result.stderr, /cannot detach source still declared by a registered proof/u);
});

test('an explicit base ref permits rebind after the source commit', async t => {
  const f = fixture(t);
  f.write('implementation.php', 'source-v2\n');
  assert.equal(spawnSync('git', ['add', 'implementation.php'], { cwd: f.root }).status, 0);
  assert.equal(spawnSync('git', ['commit', '-m', 'source change'], { cwd: f.root }).status, 0);
  const withoutBase = f.run(['--case', 'A1']);
  assert.equal(withoutBase.status, 1);
  assert.match(withoutBase.stderr, /stale source is not an actual working-tree change/u);
  await new Promise(resolve => setTimeout(resolve, 5));
  const result = f.run(['--base-ref', 'HEAD^', '--case', 'A1', '--command-json', '["npm","run","fixture:verify"]', '--apply']);
  assert.equal(result.status, 0, result.stderr);
  assert.equal(f.run(['--check', '--base-ref', 'HEAD^']).status, 0);
});

test('apply requires same-execution proof rewrite, updates only stale digest, and passes provenance check', async t => {
  const f = fixture(t);
  f.write('implementation.php', 'source-v2\n');
  await new Promise(resolve => setTimeout(resolve, 5));
  const result = f.run(['--case', 'A1', '--command-json', '["npm","run","fixture:verify"]', '--apply']);
  assert.equal(result.status, 0, result.stderr);
  const registry = JSON.parse(fs.readFileSync(path.join(f.root, 'docs/research/2026-09-08-selection-catalog/acceptance-evidence.json')));
  assert.equal(registry.cases.A1.source_digests['implementation.php'], hash('source-v2\n'));
  const diff = spawnSync('git', ['diff', '--unified=0', '--', 'docs/research/2026-09-08-selection-catalog/acceptance-evidence.json'], { cwd: f.root, encoding: 'utf8' }).stdout;
  assert.equal((diff.match(/^[-+]\s+"implementation\.php"/gmu) || []).length, 2);
  const checked = f.run(['--check', '--base-ref', 'HEAD']);
  assert.equal(checked.status, 0, checked.stderr);
  assert.match(checked.stdout, /3 digest change/u);
});

test('apply imports newly declared proof source digests into the acceptance registry', async t => {
  const f = fixture(t);
  f.write('implementation.php', 'source-v2\n');
  await new Promise(resolve => setTimeout(resolve, 5));
  const result = f.run(['--case', 'A1', '--command-json', '["npm","run","fixture:verify"]', '--apply']);
  assert.equal(result.status, 0, result.stderr);
  const registry = JSON.parse(fs.readFileSync(path.join(f.root, 'docs/research/2026-09-08-selection-catalog/acceptance-evidence.json')));
  assert.equal(registry.cases.A1.source_digests['new-source.txt'], hash('new-source\n'));
});

test('a refreshed proof can be rebound when source digests are unchanged', async t => {
  const f = fixture(t);
  const proofPath = path.join(f.root, 'proof.json');
  const proof = JSON.parse(fs.readFileSync(proofPath, 'utf8'));
  proof.refresh = 'runtime rerun';
  f.write('proof.json', proof);
  const preview = f.run(['--case', 'A1']);
  assert.equal(preview.status, 0, preview.stderr);
  assert.match(preview.stdout, /proof\.json/u);
  await new Promise(resolve => setTimeout(resolve, 5));
  const applied = f.run(['--case', 'A1', '--command-json', '["npm","run","fixture:verify"]', '--apply']);
  assert.equal(applied.status, 0, applied.stderr);
  assert.match(applied.stdout, /proof_digest_updates/u);
  const registry = JSON.parse(fs.readFileSync(path.join(f.root, 'docs/research/2026-09-08-selection-catalog/acceptance-evidence.json'), 'utf8'));
  assert.equal(registry.cases.A1.proofs[0].sha256, hash(fs.readFileSync(proofPath)));
  const checked = f.run(['--check', '--base-ref', 'HEAD']);
  assert.equal(checked.status, 0, checked.stderr || checked.stdout);
});

test('apply removes obsolete proof source bindings and records the removal', async t => {
  const f = fixture(t);
  const registryPath = path.join(f.root, 'docs/research/2026-09-08-selection-catalog/acceptance-evidence.json');
  const registry = JSON.parse(fs.readFileSync(registryPath, 'utf8'));
  registry.cases.A1.source_digests['new-source.txt'] = hash('new-source\n');
  fs.writeFileSync(registryPath, `${JSON.stringify(registry, null, 2)}\n`);
  assert.equal(spawnSync('git', ['add', 'docs/research/2026-09-08-selection-catalog/acceptance-evidence.json'], { cwd: f.root }).status, 0);
  assert.equal(spawnSync('git', ['commit', '-m', 'record prior proof source set'], { cwd: f.root }).status, 0);
  f.write('implementation.php', 'source-v2\n');
  f.write('rewrite-proof.mjs', "import fs from 'node:fs'; import { createHash } from 'node:crypto'; const p=JSON.parse(fs.readFileSync('proof.json')); p.sourceDigests['implementation.php']=createHash('sha256').update(fs.readFileSync('implementation.php')).digest('hex'); delete p.sourceDigests['new-source.txt']; fs.writeFileSync('proof.json', JSON.stringify(p, null, 2)+'\\n');\n");
  await new Promise(resolve => setTimeout(resolve, 5));
  const result = f.run(['--case', 'A1', '--command-json', '["npm","run","fixture:verify"]', '--apply']);
  assert.equal(result.status, 0, result.stderr);
  const after = JSON.parse(fs.readFileSync(registryPath, 'utf8'));
  assert.equal(after.cases.A1.source_digests['new-source.txt'], undefined);
  const log = JSON.parse(fs.readFileSync(path.join(f.root, 'docs/research/2026-09-08-selection-catalog/acceptance-rebind-log.json'), 'utf8'));
  assert.ok(log.transactions[0].changes.some(change => change.kind === 'source' && change.path === 'new-source.txt' && change.after === null));
  const checked = f.run(['--check', '--base-ref', 'HEAD']);
  assert.equal(checked.status, 0, checked.stderr || checked.stdout);
});

test('apply rejects a proof source digest mismatch after the oracle rewrites the proof', async t => {
  const f = fixture(t);
  f.write('implementation.php', 'source-v2\n');
  f.write('new-source.txt', 'new-source-mutated\n');
  await new Promise(resolve => setTimeout(resolve, 5));
  const result = f.run(['--case', 'A1', '--command-json', '["npm","run","fixture:verify"]', '--apply']);
  assert.equal(result.status, 1);
  assert.match(result.stderr, /proof source digest mismatch: proof\.json \/ new-source\.txt/u);
});

test('apply rejects a rewritten proof that lacks sourceDigests', async t => {
  const f = fixture(t);
  f.write('implementation.php', 'source-v2\n');
  f.write('missing-source-digests.mjs', "import fs from 'node:fs'; const p=JSON.parse(fs.readFileSync('proof.json')); delete p.sourceDigests; fs.writeFileSync('proof.json', JSON.stringify(p, null, 2)+'\\n');\n");
  const packageJson = JSON.parse(fs.readFileSync(path.join(f.root, 'package.json')));
  packageJson.scripts['fixture:missing-source-digests'] = 'node missing-source-digests.mjs';
  packageJson.catalogOracles['proof.json'] = 'fixture:missing-source-digests';
  f.write('package.json', packageJson);
  await new Promise(resolve => setTimeout(resolve, 5));
  const result = f.run(['--case', 'A1', '--command-json', '["npm","run","fixture:missing-source-digests"]', '--apply']);
  assert.equal(result.status, 1);
  assert.match(result.stderr, /proof lacks sourceDigests: proof\.json/u);
});

test('apply rejects an arbitrary command even if it rewrites every referenced proof', t => {
  const f = fixture(t);
  f.write('implementation.php', 'source-v2\n');
  const result = f.run(['--case', 'A1', '--command-json', '["touch","proof.json"]', '--apply']);
  assert.equal(result.status, 1);
  assert.match(result.stderr, /oracle command is not declared/u);
});

test('check rejects a hand-edited digest without a transaction', t => {
  const f = fixture(t);
  const file = path.join(f.root, 'docs/research/2026-09-08-selection-catalog/acceptance-evidence.json');
  const registry = JSON.parse(fs.readFileSync(file));
  registry.cases.A1.source_digests['implementation.php'] = hash('forged');
  fs.writeFileSync(file, `${JSON.stringify(registry, null, 2)}\n`);
  const result = f.run(['--check', '--base-ref', 'HEAD']);
  assert.equal(result.status, 1);
  assert.match(result.stderr, /without a rebind transaction/u);
});

test('check accepts a chained second rebind and validates the latest proof digest', async t => {
  const f = fixture(t);
  f.write('implementation.php', 'source-v2\n');
  await new Promise(resolve => setTimeout(resolve, 5));
  assert.equal(f.run(['--case', 'A1', '--command-json', '["npm","run","fixture:verify"]', '--apply']).status, 0);
  f.write('implementation.php', 'source-v3\n');
  await new Promise(resolve => setTimeout(resolve, 5));
  assert.equal(f.run(['--case', 'A1', '--command-json', '["npm","run","fixture:verify"]', '--apply']).status, 0);
  const checked = f.run(['--check', '--base-ref', 'HEAD']);
  assert.equal(checked.status, 0, checked.stderr);
  assert.match(checked.stdout, /2 transaction/u);
});

test('a proof row rename requires a passing new row and a logged oracle run', async t => {
  const f = fixture(t);
  const proofPath = path.join(f.root, 'proof.json');
  const proof = JSON.parse(fs.readFileSync(proofPath));
  proof.rows[0].name = 'renamed scenario';
  f.write('proof.json', proof);
  const rename = JSON.stringify({ case: 'A1', proof: 'proof.json', from: 'scenario', to: 'renamed scenario' });
  await new Promise(resolve => setTimeout(resolve, 5));
  const result = f.run(['--case', 'A1', '--row-rename-json', rename, '--command-json', '["npm","run","fixture:verify"]', '--apply']);
  assert.equal(result.status, 0, result.stderr);
  const registry = JSON.parse(fs.readFileSync(path.join(f.root, 'docs/research/2026-09-08-selection-catalog/acceptance-evidence.json')));
  assert.deepEqual(registry.cases.A1.proofs[0].row_names, ['renamed scenario']);
  assert.equal(f.run(['--check', '--base-ref', 'HEAD']).status, 0);
});

test('a proof row rename rejects a missing new row', async t => {
  const f = fixture(t);
  const rename = JSON.stringify({ case: 'A1', proof: 'proof.json', from: 'scenario', to: 'absent scenario' });
  await new Promise(resolve => setTimeout(resolve, 5));
  const result = f.run(['--case', 'A1', '--row-rename-json', rename, '--command-json', '["npm","run","fixture:verify"]', '--apply']);
  assert.equal(result.status, 1);
  assert.match(result.stderr, /proof row is not passing/u);
});

test('apply rejects a partial case set when another case shares the changed source', t => {
  const f = fixture(t);
  const file = path.join(f.root, 'docs/research/2026-09-08-selection-catalog/acceptance-evidence.json');
  const registry = JSON.parse(fs.readFileSync(file));
  registry.cases.A2 = structuredClone(registry.cases.A1);
  fs.writeFileSync(file, `${JSON.stringify(registry, null, 2)}\n`);
  spawnSync('git', ['add', '.'], { cwd: f.root });
  spawnSync('git', ['commit', '-m', 'shared case'], { cwd: f.root });
  f.write('implementation.php', 'source-v2\n');
  const result = f.run(['--case', 'A1']);
  assert.equal(result.status, 1);
  assert.match(result.stderr, /all cases affected.*A2/u);
});
