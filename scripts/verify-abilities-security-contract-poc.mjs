import assert from 'node:assert/strict';
import fs from 'node:fs';
import { createHash } from 'node:crypto';
import { fixture, project, validateContract } from '../docs/research/2026-09-20-abilities-security-contract-poc/contract.mjs';

const root = 'docs/research/2026-09-20-abilities-security-contract-poc';
const poc = 'docs/research/2026-09-03-poc-abilities-3face';
const read = file => JSON.parse(fs.readFileSync(file, 'utf8'));
const hash = file => createHash('sha256').update(fs.readFileSync(file)).digest('hex');
const rows = [];
const check = (name, fn) => { try { fn(); rows.push({ name, pass: true }); } catch (error) { rows.push({ name, pass: false, details: error instanceof Error ? error.message : String(error) }); } };
const rejects = (name, fn, message) => check(name, () => assert.throws(fn, message));
const results = read(`${poc}/results/results.json`);
const pack = read(`${poc}/scripts/pack.json`);
const json = file => read(`${poc}/results/${file}`);
const observations = {
  restAnonymousStatus: json('face-b-rest-abilities-anon.json').data.status,
  mcpAnonymousStatus: json('face-c-mcp-wt-pack-tools-list-anon.json').data.status,
  receiptRequiredCode: json('face-b-rest-run-apply-noreceipt-auth.json').code,
  receiptMismatchCode: json('face-b-rest-run-apply-bogus-auth.json').code,
  wrongMethodStatus: json('face-b-rest-run-apply-anon-post.json').data.status,
  mcpNoReceiptError: json('face-c-mcp-wt-pack-call-apply-noreceipt.json').result.isError === true,
  applyAfterReceipt: json('face-b-rest-run-apply-receipt-auth.json').applied === true,
  readAfterHeader: json('face-b-rest-run-read-after.json').header_part,
};
const value = structuredClone(fixture);

check('AC-AGENT-01A binds one pack across CLI, REST, MCP and receipt flow', () => {
  validateContract(value, results, pack, observations);
  assert.equal(results.pack, fixture.pack);
  assert.equal(results.abilities['wt/site-selection-dry-run'].match.input_schema_cli_rest, true);
  assert.equal(results.abilities['wt/site-selection-apply'].match.input_schema_cli_mcp, true);
});
check('AC-AGENT-01B records the known input-schema normalization boundary', () => {
  assert.equal(results.abilities['wt/site-selection-read'].match.input_schema_cli_mcp, false);
  assert.match(fs.readFileSync(`${poc}/README.md`, 'utf8'), /input_schema/);
  assert.equal(observations.wrongMethodStatus, 405);
});
check('AC-NFR-PERM-01A/B proves receipt and anonymous permission fences', () => {
  assert.equal(observations.restAnonymousStatus, 401);
  assert.equal(observations.mcpAnonymousStatus, 401);
  assert.equal(observations.receiptRequiredCode, 'wt_receipt_required');
  assert.equal(observations.receiptMismatchCode, 'wt_receipt_mismatch');
  assert.equal(observations.mcpNoReceiptError, true);
  assert.equal(observations.applyAfterReceipt, true);
});
check('AC-NFR-SEC-01A records the anonymous REST/MCP boundary without claiming full audit', () => {
  assert.equal(observations.restAnonymousStatus, 401);
  assert.equal(observations.mcpAnonymousStatus, 401);
  assert.equal(value.gates.completion, false);
});
rejects('AC-AGENT-01B rejects a pack with an undeclared ability', () => {
  const broken = structuredClone(pack); broken.abilities.push({ name: 'wt/undeclared', meta: {} }); validateContract(value, results, broken, observations);
});
rejects('AC-NFR-PERM-01B rejects receipt-less apply evidence', () => {
  const broken = { ...observations, receiptRequiredCode: 'applied' }; validateContract(value, results, pack, broken);
});
rejects('AC-NFR-SEC-01B rejects an anonymous endpoint that is not forbidden', () => {
  const broken = { ...observations, restAnonymousStatus: 200 }; validateContract(value, results, pack, broken);
});
rejects('AC-AGENT-01B rejects a contract with the wrong schema', () => {
  const broken = { ...value, schema: 'wrong' }; validateContract(broken, results, pack, observations);
}, /Expected values to be strictly equal/u);
rejects('AC-AGENT-01B rejects a contract with the wrong owner', () => {
  const broken = { ...value, owner: 'editor' }; validateContract(broken, results, pack, observations);
}, /Expected values to be strictly equal/u);
rejects('AC-AGENT-01B rejects a pack outside the wt category', () => {
  const broken = structuredClone(pack); broken.category.slug = 'other'; validateContract(value, results, broken, observations);
}, /Expected values to be strictly equal/u);
rejects('AC-AGENT-01B rejects a pack with a missing declared ability', () => {
  const broken = structuredClone(pack); broken.abilities = broken.abilities.slice(0, -1); validateContract(value, results, broken, observations);
}, /Expected values to be strictly deep-equal/u);
rejects('AC-AGENT-01B rejects a result with a missing ability key', () => {
  const broken = structuredClone(results); delete broken.abilities['wt/site-selection-read']; validateContract(value, broken, pack, observations);
}, /Expected values to be strictly deep-equal/u);

for (const face of ['cli', 'rest', 'mcp']) {
  rejects(`AC-AGENT-01B rejects an ability missing the ${face} face`, () => {
    const broken = structuredClone(results);
    broken.abilities['wt/site-selection-read'][face].present = false;
    validateContract(value, broken, pack, observations);
  }, /must be present on all faces/u);
}

for (const ability of fixture.abilities.slice(1)) {
  for (const face of ['cli', 'rest', 'mcp']) {
    rejects(`AC-AGENT-01B rejects ${ability} missing the ${face} face`, () => {
      const broken = structuredClone(results);
      broken.abilities[ability][face].present = false;
      validateContract(value, broken, pack, observations);
    }, new RegExp(`${ability} must be present on all faces`, 'u'));
  }
}

for (const [field, message] of [
  ['output_schema_cli_rest', 'output CLI/REST mismatch'],
  ['output_schema_cli_mcp', 'output CLI/MCP mismatch'],
  ['annotations_cli_rest', 'annotations CLI/REST mismatch'],
  ['annotations_cli_mcp', 'annotations CLI/MCP mismatch'],
]) {
  rejects(`AC-AGENT-01B rejects ${field} drift`, () => {
    const broken = structuredClone(results);
    broken.abilities['wt/site-selection-read'].match[field] = false;
    validateContract(value, broken, pack, observations);
  }, new RegExp(message.replace(/[\\/]/gu, '\\$&'), 'u'));
}

for (const ability of fixture.abilities.slice(1)) {
  for (const [field, message] of [
    ['output_schema_cli_rest', 'output CLI/REST mismatch'],
    ['output_schema_cli_mcp', 'output CLI/MCP mismatch'],
    ['annotations_cli_rest', 'annotations CLI/REST mismatch'],
    ['annotations_cli_mcp', 'annotations CLI/MCP mismatch'],
  ]) {
    rejects(`AC-AGENT-01B rejects ${ability} ${field} drift`, () => {
      const broken = structuredClone(results);
      broken.abilities[ability].match[field] = false;
      validateContract(value, broken, pack, observations);
    }, new RegExp(`${ability} ${message.replace(/[\\/]/gu, '\\$&')}`, 'u'));
  }
}

for (const field of ['pack_in_cli', 'pack_in_rest', 'pack_in_mcp_wt_pack', 'pack_in_mcp_default_discover']) {
  rejects(`AC-AGENT-01A rejects a pack set missing ${field}`, () => {
    const broken = structuredClone(results);
    broken.sets[field] = false;
    validateContract(value, broken, pack, observations);
  }, /Expected values to be strictly equal/u);
}

for (const [field, bad, message] of [
  ['mcpAnonymousStatus', 200, 'Expected values to be strictly equal'],
  ['receiptMismatchCode', 'applied', 'Expected values to be strictly equal'],
  ['wrongMethodStatus', 200, 'Expected values to be strictly equal'],
  ['mcpNoReceiptError', false, 'Expected values to be strictly equal'],
  ['applyAfterReceipt', false, 'Expected values to be strictly equal'],
  ['readAfterHeader', 'header-a', 'Expected values to be strictly equal'],
]) {
  rejects(`AC-NFR-SEC-01B rejects ${field} drift`, () => {
    const broken = { ...observations, [field]: bad };
    validateContract(value, results, pack, broken);
  }, new RegExp(message, 'u'));
}

for (const field of ['completion', 'liveCurrentHead']) {
  rejects(`AC-NFR-SEC-01B rejects a completed ${field} gate`, () => {
    const broken = structuredClone(value);
    broken.gates[field] = true;
    validateContract(broken, results, pack, observations);
  }, /Expected values to be strictly equal/u);
}
check('contract has no transport, model, or credential material', () => {
  const source = fs.readFileSync(`${root}/contract.mjs`, 'utf8');
  for (const forbidden of ['fetch(', 'sendBeacon(', 'XMLHttpRequest', 'openai', 'anthropic', 'WP_APP_PASS', 'password']) assert(!source.includes(forbidden), forbidden);
});

const sourceFiles = [...fixture.sources, `${root}/contract.mjs`, 'scripts/verify-abilities-security-contract-poc.mjs'];
const report = {
  schema: 'wt-abilities-security-contract-poc-verification.v1',
  completed: rows.every(row => row.pass),
  sourceDigests: Object.fromEntries(sourceFiles.map(file => [file, hash(file)])),
  pack: fixture.pack,
  abilities: fixture.abilities,
  observations,
  gates: { completion: false, liveCurrentHead: false },
  rows,
  scope: '既存のAbilities 3面PoCから、manifest・権限・dry-run receiptの比較可能な部分証跡を作る。',
  remaining: project(value).remaining,
};
fs.writeFileSync(`${root}/verification.json`, `${JSON.stringify(report, null, 2)}\n`);
fs.writeFileSync(`${root}/acceptance-candidate.json`, `${JSON.stringify({
  schema: 'wt-acceptance-candidate.v1',
  'WT-AC-AGENT-01A': { status: 'partial', scope: 'wt-site-selection packの3 abilityをCLI/REST/MCPで列挙し、dry-run receipt後applyまで突合する。', remaining: report.remaining, proofs: [{ path: `${root}/verification.json`, row_names: rows.filter(row => row.name.startsWith('AC-AGENT-01A')).map(row => row.name) }] },
  'WT-AC-AGENT-01B': { status: 'partial', scope: '未宣言ability、入力schema境界、誤HTTPメソッドを拒否する。', remaining: report.remaining, proofs: [{ path: `${root}/verification.json`, row_names: rows.filter(row => row.name.startsWith('AC-AGENT-01B')).map(row => row.name) }] },
  'WT-AC-NFR-PERM-01A': { status: 'partial', scope: '匿名REST/MCPとreceiptなし・偽receiptのapplyを拒否する。', remaining: report.remaining, proofs: [{ path: `${root}/verification.json`, row_names: rows.filter(row => row.name.startsWith('AC-NFR-PERM-01')).map(row => row.name) }] },
  'WT-AC-NFR-PERM-01B': { status: 'partial', scope: '権限・receipt境界の迂回を負例で拒否する。', remaining: report.remaining, proofs: [{ path: `${root}/verification.json`, row_names: rows.filter(row => row.name.startsWith('AC-NFR-PERM-01')).map(row => row.name) }] },
  'WT-AC-NFR-SEC-01A': { status: 'partial', scope: '匿名REST/MCP露出を401として記録するが、全体SSRF/Warning監査は残件とする。', remaining: report.remaining, proofs: [{ path: `${root}/verification.json`, row_names: rows.filter(row => row.name.startsWith('AC-NFR-SEC-01A')).map(row => row.name) }] },
  'WT-AC-NFR-SEC-01B': { status: 'partial', scope: '匿名公開を200扱いする改変を負例で拒否する。', remaining: report.remaining, proofs: [{ path: `${root}/verification.json`, row_names: rows.filter(row => row.name.startsWith('AC-NFR-SEC-01B')).map(row => row.name) }] },
}, null, 2)}\n`);
fs.writeFileSync(`${root}/catalog-candidates.json`, `${JSON.stringify({
  schema: 'wt-abilities-security-contract-catalog-candidates.v1',
  entries: [{ id: 'agent-pack:security-and-receipt', face: 'system', part: 'agent-ability-pack', label: 'エージェント接点：3面能力・権限・receipt', variant: 'security-and-receipt', description: 'wt-site-selectionの3 abilityをCLI/REST/MCPへ同じ宣言から公開し、匿名拒否とdry-run receipt必須を検証した部分契約。', purpose: '安全に操作を委譲する', group: '品質・運用', images: { pc: '../2026-09-05-design-prototype-03/results/h2-plain-pc.jpg', sp: '../2026-09-05-design-prototype-03/results/h2-plain-sp.jpg' }, requirementIds: ['WT-FR-AGENT-01', 'WT-NFR-SEC-01', 'WT-NFR-PERM-01'], referenceId: 'wt-abilities-security-contract.v1', evidence: `${root}/verification.json`, selectionFacts: { '対象と判断': 'manifest・認証・receiptの境界を1候補に束ね、能力の選択時に安全条件と残件を同時表示する。', '実測': '3 ability、CLI/REST/MCP突合、匿名401、receiptなし400、偽receipt409、apply後read反映。', '未検証': report.remaining.join(' ') } }],
}, null, 2)}\n`);
console.log(JSON.stringify({ completed: report.completed, tests: rows.length, abilities: fixture.abilities.length, acceptance: '6 partial candidates' }));
if (!report.completed) process.exitCode = 1;
