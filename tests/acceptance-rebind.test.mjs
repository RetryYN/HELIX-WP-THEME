import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { createHash } from 'node:crypto';

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
  write('proof.json', proof);
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
  write('rewrite-proof.mjs', "import fs from 'node:fs'; const p=JSON.parse(fs.readFileSync('proof.json')); fs.writeFileSync('proof.json', JSON.stringify(p, null, 2)+'\\n');\n");
  write('package.json', {
    scripts: { 'fixture:verify': 'node rewrite-proof.mjs' },
    catalogOracles: { 'proof.json': 'fixture:verify' },
  });
  for (const args of [['init'], ['config', 'user.email', 'test@example.invalid'], ['config', 'user.name', 'Test'], ['add', '.'], ['commit', '-m', 'base']]) {
    assert.equal(spawnSync('git', args, { cwd: root }).status, 0);
  }
  const run = args => spawnSync('node', ['scripts/rebind-acceptance-evidence.mjs', ...args], { cwd: root, encoding: 'utf8' });
  return { root, run, write };
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
  assert.match(checked.stdout, /1 digest change/u);
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
