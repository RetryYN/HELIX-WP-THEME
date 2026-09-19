import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
import { createHash } from 'node:crypto';

const hash = value => createHash('sha256').update(value).digest('hex');
const tool = new URL('../scripts/admit-acceptance-evidence.mjs', import.meta.url);

function fixture(t) {
  const root = fs.mkdtempSync(path.join(os.tmpdir(), 'wt-admission-test-'));
  t.after(() => fs.rmSync(root, { recursive: true, force: true }));
  const write = (file, value) => {
    const target = path.join(root, file);
    fs.mkdirSync(path.dirname(target), { recursive: true });
    fs.writeFileSync(target, typeof value === 'string' ? value : `${JSON.stringify(value, null, 2)}\n`);
  };
  const source = 'source-v1\n';
  const proof = {
    schema: 'fixture-proof.v1', completed: true,
    sourceDigests: { 'source.txt': hash(source) },
    rows: [{ name: 'scenario', pass: true }],
  };
  write('source.txt', source);
  write('proof.json', proof);
  write('docs/requirements/l3/requirements-ir.json', { requirements: [{ id: 'R1', semantic_digest: 'requirement-v1' }] });
  write('docs/requirements/l3/acceptance-cases.json', { cases: [{ id: 'A1', requirement_id: 'R1', oracle: 'Expected behavior', polarity: 'positive' }] });
  write('docs/research/2026-09-08-selection-catalog/acceptance-evidence.json', { schema: 'wt-acceptance-evidence.v1', cases: {} });
  write('docs/research/2026-09-08-selection-catalog/acceptance-rebind-log.json', { schema: 'wt-acceptance-rebind-log.v1', transactions: [] });
  write('config/catalog-admission-oracles.json', { schema: 'wt-catalog-admission-oracles.v1', commands: { 'proof.json': ['node', 'rewrite-proof.mjs'] } });
  write('rewrite-proof.mjs', "import fs from 'node:fs'; const file='proof.json'; const value=JSON.parse(fs.readFileSync(file)); fs.writeFileSync(file, JSON.stringify(value, null, 2)+'\\n');\n");
  write('candidate.json', {
    schema: 'wt-acceptance-candidate.v1',
    A1: {
      status: 'partial', scope: 'fixture scenario', remaining: ['additional scenarios'],
      proofs: [{ path: 'proof.json', row_names: ['scenario'] }],
    },
  });
  const run = args => spawnSync(process.execPath, [tool.pathname, ...args], {
    cwd: root, env: { ...process.env, HELIX_ACCEPTANCE_ROOT: root }, encoding: 'utf8',
  });
  return { root, run };
}

test('admission dry-run is side-effect free and apply records a bound transaction', t => {
  const f = fixture(t);
  const dry = f.run(['--candidate', 'candidate.json']);
  assert.equal(dry.status, 0, dry.stderr);
  assert.match(dry.stdout, /"mode": "dry-run"/u);
  const logPath = path.join(f.root, 'docs/research/2026-09-08-selection-catalog/acceptance-rebind-log.json');
  const before = fs.readFileSync(logPath, { encoding: 'utf8' });
  const applied = f.run(['--candidate', 'candidate.json', '--command-json', '["node","rewrite-proof.mjs"]', '--apply']);
  assert.equal(applied.status, 0, applied.stderr);
  const registry = JSON.parse(fs.readFileSync(path.join(f.root, 'docs/research/2026-09-08-selection-catalog/acceptance-evidence.json')));
  assert.equal(registry.cases.A1.status, 'partial');
  assert.equal(registry.cases.A1.proofs[0].sha256, hash(fs.readFileSync(path.join(f.root, 'proof.json'))));
  const log = JSON.parse(fs.readFileSync(logPath));
  assert.equal(log.transactions.length, 1);
  assert.equal(log.transactions[0].kind, 'admit');
  assert.notEqual(fs.readFileSync(path.join(f.root, 'docs/research/2026-09-08-selection-catalog/acceptance-rebind-log.json'), 'utf8'), before);
});

test('admission rejects unknown or duplicate cases before running an oracle', t => {
  const f = fixture(t);
  const candidate = JSON.parse(fs.readFileSync(path.join(f.root, 'candidate.json')));
  candidate.A2 = candidate.A1;
  fs.writeFileSync(path.join(f.root, 'candidate.json'), `${JSON.stringify(candidate, null, 2)}\n`);
  const result = f.run(['--candidate', 'candidate.json']);
  assert.equal(result.status, 1);
  assert.match(result.stderr, /unknown acceptance ID: A2/u);
  assert.deepEqual(JSON.parse(fs.readFileSync(path.join(f.root, 'docs/research/2026-09-08-selection-catalog/acceptance-evidence.json'))).cases, {});
});
