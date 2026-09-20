import assert from 'node:assert/strict';

export const fixture = {
  schema: 'wt-abilities-security-contract.v1',
  owner: 'helix',
  pack: 'wt-site-selection',
  abilities: ['wt/site-selection-read', 'wt/site-selection-dry-run', 'wt/site-selection-apply'],
  security: {
    restAnonymousStatus: 401,
    mcpAnonymousStatus: 401,
    receiptRequiredCode: 'wt_receipt_required',
    receiptMismatchCode: 'wt_receipt_mismatch',
    wrongMethodStatus: 405,
  },
  sources: [
    'docs/research/2026-09-03-poc-abilities-3face/README.md',
    'docs/research/2026-09-03-poc-abilities-3face/results/results.json',
    'docs/research/2026-09-03-poc-abilities-3face/scripts/pack.json',
    'docs/research/2026-09-03-poc-abilities-3face/scripts/compare-faces.py',
    'docs/requirements/l3/requirements-ir.json',
    'docs/requirements/l3/acceptance-cases.json',
  ],
  gates: { completion: false, liveCurrentHead: false },
};

export function validateContract(value, results, pack, observations) {
  assert.equal(value.schema, 'wt-abilities-security-contract.v1');
  assert.equal(value.owner, 'helix');
  assert.equal(pack.category.slug, 'wt');
  assert.deepEqual(pack.abilities.map(item => item.name).sort(), fixture.abilities.slice().sort());
  assert.deepEqual(Object.keys(results.abilities).sort(), fixture.abilities.slice().sort());
  for (const name of fixture.abilities) {
    const item = results.abilities[name];
    assert(item.cli.present && item.rest.present && item.mcp.present, `${name} must be present on all faces`);
    assert.equal(item.match.output_schema_cli_rest, true, `${name} output CLI/REST mismatch`);
    assert.equal(item.match.output_schema_cli_mcp, true, `${name} output CLI/MCP mismatch`);
    assert.equal(item.match.annotations_cli_rest, true, `${name} annotations CLI/REST mismatch`);
    assert.equal(item.match.annotations_cli_mcp, true, `${name} annotations CLI/MCP mismatch`);
  }
  assert.equal(results.sets.pack_in_cli, true);
  assert.equal(results.sets.pack_in_rest, true);
  assert.equal(results.sets.pack_in_mcp_wt_pack, true);
  assert.equal(results.sets.pack_in_mcp_default_discover, true);
  assert.equal(observations.restAnonymousStatus, 401);
  assert.equal(observations.mcpAnonymousStatus, 401);
  assert.equal(observations.receiptRequiredCode, 'wt_receipt_required');
  assert.equal(observations.receiptMismatchCode, 'wt_receipt_mismatch');
  assert.equal(observations.wrongMethodStatus, 405);
  assert.equal(observations.mcpNoReceiptError, true);
  assert.equal(observations.applyAfterReceipt, true);
  assert.equal(observations.readAfterHeader, 'header-b');
  assert.equal(value.gates.completion, false);
  assert.equal(value.gates.liveCurrentHead, false);
}

export function project(value) {
  return {
    schema: value.schema,
    pack: value.pack,
    abilities: [...value.abilities],
    gates: { ...value.gates },
    remaining: [
      'PoCはWordPress 7.1のローカル実行で、現行HEADへ再実行したreceiptではない。',
      'REST/MCPの全匿名メソッド、実装全体のSSRF/Warning静的監査、WP 7.2実機は未接続。',
    ],
  };
}
