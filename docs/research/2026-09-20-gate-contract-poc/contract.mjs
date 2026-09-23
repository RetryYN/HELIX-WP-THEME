import assert from 'node:assert/strict';

export const fixture = {
  schema: 'wt-gate-contract.v1',
  owner: 'helix',
  staticGates: ['G-T1', 'G-T1b', 'G-T2', 'G-T3', 'G-S1', 'G-S2'],
  staticResult: { fail: 0, warn: 1, rawValues: 433, rawBaseline: 438 },
  ge1: {
    source: 'docs/research/2026-08-29-ge1-local/editor-validate-2026-08-29.json',
    patterns: 71,
    invalid: 0,
  },
  sources: [
    'bin/check-design-consistency.sh',
    'docs/research/2026-08-29-ge1-local/README.md',
    'docs/research/2026-08-29-ge1-local/editor-validate-2026-08-29.json',
    'docs/requirements/l3/requirements-ir.json',
    'docs/requirements/l3/acceptance-cases.json',
  ],
  gates: { completion: false, exactHeadReceipt: false },
};

export function validateContract(value, staticOutput, ge1) {
  assert.equal(value.schema, 'wt-gate-contract.v1');
  assert.equal(value.owner, 'helix');
  assert.deepEqual(value.staticGates, fixture.staticGates);
  assert.equal(value.staticResult.fail, 0);
  assert.equal(value.staticResult.warn, 1);
  assert.equal(value.staticResult.rawValues, 433);
  assert.equal(value.staticResult.rawBaseline, 438);
  assert.equal(value.ge1.patterns, 71);
  assert.equal(value.gates.completion, false);
  assert.equal(value.gates.exactHeadReceipt, false);
  assert.match(staticOutput, /FAIL=0/);
  assert.match(staticOutput, /WARN=1/);
  for (const gate of fixture.staticGates) assert.match(staticOutput, new RegExp(gate));
  const rows = Object.values(ge1);
  assert.equal(rows.length, fixture.ge1.patterns);
  assert.equal(rows.reduce((sum, row) => sum + (row.invalid?.length ?? 0), 0), 0);
  assert.equal(value.ge1.invalid, rows.reduce((sum, row) => sum + (row.invalid?.length ?? 0), 0));
}

export function project(value) {
  return {
    schema: value.schema,
    staticGates: [...value.staticGates],
    ge1: { ...value.ge1 },
    gates: { ...value.gates },
    remaining: [
      '実機 G-E1 は 2026-08-29 のローカル WordPress 7.1 証跡であり、現行HEADの再実行 receipt ではない。',
      'required check と独立レビュー receipt を同一 current HEAD へ束縛する運用は未接続。',
    ],
  };
}
