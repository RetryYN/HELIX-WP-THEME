import fs from 'node:fs';
import path from 'node:path';
import { createHash } from 'node:crypto';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(process.env.HELIX_ACCEPTANCE_ROOT ?? path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..'));
const registryPath = 'docs/research/2026-09-08-selection-catalog/acceptance-evidence.json';
const logPath = 'docs/research/2026-09-08-selection-catalog/acceptance-rebind-log.json';
const acceptancePath = 'docs/requirements/l3/acceptance-cases.json';
const requirementsPath = 'docs/requirements/l3/requirements-ir.json';
const oracleConfigPath = 'config/catalog-admission-oracles.json';
const digest = value => createHash('sha256').update(value).digest('hex');
const bytes = file => {
  const resolved = path.resolve(root, file);
  if (!resolved.startsWith(`${root}${path.sep}`)) throw new Error(`path escapes repository: ${file}`);
  return fs.readFileSync(resolved);
};
const read = file => JSON.parse(bytes(file));
const stable = value => `${JSON.stringify(value, null, 2)}\n`;
const fail = message => { throw new Error(message); };

function parseArgs(argv) {
  const options = { candidates: [], apply: false, commands: [] };
  for (let index = 0; index < argv.length; index += 1) {
    const arg = argv[index];
    if (arg === '--candidate') options.candidates.push(argv[++index]);
    else if (arg === '--command-json') options.commands.push(JSON.parse(argv[++index]));
    else if (arg === '--apply') options.apply = true;
    else fail(`unknown argument: ${arg}`);
  }
  if (!options.candidates.length) fail('at least one --candidate is required');
  if (!options.apply) return options;
  for (const command of options.commands) {
    if (!Array.isArray(command) || !command.length) {
      fail('--command-json must be a non-empty argv array');
    }
    if (command.some(value => typeof value !== 'string' || !value)) fail('--command-json contains an empty value');
  }
  return options;
}

function proofRefs(candidate) {
  const refs = candidate.proofs ?? candidate.evidence;
  if (!Array.isArray(refs) || !refs.length) fail('candidate must declare proofs or evidence');
  return refs.map(ref => ({ ...ref, row_names: ref.row_names ?? ref.rowNames }));
}

function sourceDigestsFromProofs(proofs) {
  const sources = {};
  for (const proof of proofs) {
    const value = read(proof.path);
    const declared = value.sourceDigests ?? value.source_digests;
    if (!declared || typeof declared !== 'object' || Array.isArray(declared)) fail(`proof lacks sourceDigests: ${proof.path}`);
    for (const [source, expected] of Object.entries(declared)) {
      if (sources[source] && sources[source] !== expected) fail(`conflicting source digest: ${source}`);
      sources[source] = expected;
    }
  }
  return sources;
}

function validateProof(proof) {
  const raw = bytes(proof.path);
  const value = JSON.parse(raw);
  if (value.completed !== true) fail(`proof is incomplete: ${proof.path}`);
  const rows = [...(Array.isArray(value.rows) ? value.rows : []), ...(Array.isArray(value.checks) ? value.checks : [])];
  if (!Array.isArray(proof.row_names) || !proof.row_names.length) fail(`proof has no row_names: ${proof.path}`);
  for (const name of proof.row_names) {
    const matches = rows.filter(row => row.name === name);
    if (!matches.length || matches.some(row => row.pass !== true)) fail(`proof row is not passing: ${proof.path} / ${name}`);
  }
  return { sha256: digest(raw), value };
}

function loadCandidateFile(candidatePath) {
  const value = read(candidatePath);
  if (value.schema && value.schema !== 'wt-acceptance-candidate.v1') fail(`unsupported candidate schema: ${candidatePath}`);
  if (Array.isArray(value.rows)) return value.rows.map(row => ({ ...row, __candidatePath: candidatePath }));
  if (!value || typeof value !== 'object' || Array.isArray(value)) fail(`candidate must be an object: ${candidatePath}`);
  return Object.entries(value)
    .filter(([key]) => key !== 'schema')
    .map(([id, row]) => ({ ...row, id, __candidatePath: candidatePath }));
}

function normalizeCandidate(candidate, registry, acceptanceById, requirementById) {
  const id = candidate.id;
  if (typeof id !== 'string' || !id) fail('candidate acceptance id is required');
  if (registry.cases?.[id]) fail(`acceptance already admitted: ${id}`);
  const acceptance = acceptanceById.get(id);
  if (!acceptance) fail(`unknown acceptance ID: ${id}`);
  const requirement = requirementById.get(acceptance.requirement_id);
  if (!requirement) fail(`unknown requirement for acceptance: ${id}`);
  if (!['partial', 'verified_in_poc'].includes(candidate.status)) fail(`invalid candidate status: ${id}`);
  if (typeof candidate.scope !== 'string' || !candidate.scope.trim()) fail(`candidate scope is required: ${id}`);
  if (!Array.isArray(candidate.remaining)) fail(`candidate remaining must be an array: ${id}`);
  if (candidate.status === 'verified_in_poc' && candidate.remaining.length) fail(`verified candidate has remaining work: ${id}`);
  const proofs = proofRefs(candidate);
  const sourceDigests = candidate.source_digests ?? candidate.sourceDigests ?? sourceDigestsFromProofs(proofs);
  if (!sourceDigests || typeof sourceDigests !== 'object' || Array.isArray(sourceDigests) || !Object.keys(sourceDigests).length) fail(`source digests are required: ${id}`);
  for (const [source, expected] of Object.entries(sourceDigests)) {
    if (!fs.existsSync(path.join(root, source)) || digest(bytes(source)) !== expected) fail(`source digest mismatch: ${id} / ${source}`);
  }
  const requirementDigest = candidate.requirement_digest ?? requirement.semantic_digest;
  if (requirementDigest !== requirement.semantic_digest) fail(`requirement digest mismatch: ${id}`);
  const oracleSha = candidate.oracle_sha256 ?? digest(acceptance.oracle);
  if (oracleSha !== digest(acceptance.oracle)) fail(`oracle digest mismatch: ${id}`);
  for (const proof of proofs) validateProof(proof);
  return {
    status: candidate.status,
    requirement_digest: requirementDigest,
    oracle_sha256: oracleSha,
    source_digests: { ...sourceDigests },
    scope: candidate.scope,
    remaining: [...candidate.remaining],
    proofs: proofs.map(proof => ({ path: proof.path, sha256: proof.sha256, row_names: [...proof.row_names] })),
  };
}

function declaredOracleCommands(proofs, explicit) {
  const declared = read(oracleConfigPath).commands;
  if (!declared || typeof declared !== 'object' || Array.isArray(declared)) fail('catalog admission oracle mapping is required');
  const paths = [...new Set(proofs.map(proof => proof.path))].sort();
  if (paths.some(proofPath => !Array.isArray(declared[proofPath]) || !declared[proofPath].length)) fail('every candidate proof must have a declared catalog oracle');
  const commands = explicit.length ? explicit : paths.map(proofPath => declared[proofPath]);
  const expected = paths.map(proofPath => JSON.stringify(declared[proofPath]));
  const actual = commands.map(command => JSON.stringify(command));
  if (JSON.stringify(actual) !== JSON.stringify(expected)) fail(`oracle commands must exactly match declared proofs: expected ${expected.join(', ')}`);
  if (new Set(actual).size !== actual.length) fail('oracle command is duplicated');
  return commands;
}

function runCommands(commands) {
  for (const command of commands) {
    const result = spawnSync(command[0], command.slice(1), { cwd: root, stdio: 'inherit' });
    if (result.status !== 0) fail(`oracle command failed: ${JSON.stringify(command)}`);
  }
}

function entryDiff(before, after) {
  const ids = new Set([...Object.keys(before.cases || {}), ...Object.keys(after.cases || {})]);
  return [...ids].sort().flatMap(id => {
    if (!after.cases?.[id]) fail(`admission transaction removed case: ${id}`);
    if (before.cases?.[id]) return [];
    return [{ kind: 'admit', case_id: id, path: 'registry.cases', before: null, after: 'present' }];
  });
}

function main() {
  const options = parseArgs(process.argv.slice(2));
  const registryRaw = bytes(registryPath);
  const registry = JSON.parse(registryRaw);
  const acceptance = read(acceptancePath).cases;
  const requirements = read(requirementsPath).requirements;
  const acceptanceById = new Map(acceptance.map(item => [item.id, item]));
  const requirementById = new Map(requirements.map(item => [item.id, item]));
  const candidates = options.candidates.flatMap(loadCandidateFile);
  const seen = new Set();
  for (const candidate of candidates) {
    if (seen.has(candidate.id)) fail(`candidate acceptance is duplicated: ${candidate.id}`);
    seen.add(candidate.id);
  }
  const candidateProofs = candidates.flatMap(proofRefs);
  const commands = declaredOracleCommands(candidateProofs, options.commands);
  const candidateDigests = Object.fromEntries([...new Set(candidates.map(candidate => candidate.__candidatePath))].map(file => [file, digest(bytes(file))]));
  const normalized = candidates.map(candidate => [candidate.id, normalizeCandidate(candidate, registry, acceptanceById, requirementById)]);
  if (!options.apply) {
    console.log(JSON.stringify({ mode: 'dry-run', candidates: normalized.map(([id]) => id), commands, candidateDigests }, null, 2));
    return;
  }
  const snapshots = new Map([...new Set(candidateProofs.map(proof => proof.path))].map(proofPath => {
    const stat = fs.statSync(path.join(root, proofPath), { bigint: true });
    return [proofPath, { sha256: digest(bytes(proofPath)), mtimeNs: stat.mtimeNs, ctimeNs: stat.ctimeNs }];
  }));
  runCommands(commands);
  const proofWrites = [];
  const finalProofs = new Map();
  for (const proof of candidateProofs) {
    if (finalProofs.has(proof.path)) continue;
    const stat = fs.statSync(path.join(root, proof.path), { bigint: true });
    const before = snapshots.get(proof.path);
    if (!(stat.mtimeNs > before.mtimeNs || stat.ctimeNs > before.ctimeNs)) fail(`oracle did not rewrite referenced proof: ${proof.path}`);
    const checked = validateProof(proof);
    finalProofs.set(proof.path, checked.sha256);
    proofWrites.push({ path: proof.path, before_sha256: before.sha256, after_sha256: checked.sha256, rewritten: true });
  }
  const records = new Map();
  for (const candidate of candidates) {
    const record = normalizeCandidate(candidate, registry, acceptanceById, requirementById);
    record.proofs = record.proofs.map(proof => ({ ...proof, sha256: finalProofs.get(proof.path) }));
    records.set(candidate.id, record);
  }
  registry.cases ??= {};
  for (const [id, record] of records) registry.cases[id] = record;
  const afterRaw = Buffer.from(stable(registry));
  const changes = entryDiff(JSON.parse(registryRaw), registry);
  const transaction = {
    schema: 'wt-acceptance-rebind-transaction.v1', kind: 'admit', cases: [...records.keys()],
    candidate_paths: [...new Set(candidates.map(candidate => candidate.__candidatePath))], candidate_digests: candidateDigests,
    commands, changes, proof_writes: proofWrites,
    registry_before_sha256: digest(registryRaw), registry_after_sha256: digest(afterRaw),
  };
  const log = fs.existsSync(path.join(root, logPath)) ? read(logPath) : { schema: 'wt-acceptance-rebind-log.v1', transactions: [] };
  if (log.schema !== 'wt-acceptance-rebind-log.v1' || !Array.isArray(log.transactions)) fail('invalid acceptance rebind log');
  log.transactions.push(transaction);
  fs.writeFileSync(path.join(root, registryPath), afterRaw);
  fs.writeFileSync(path.join(root, logPath), stable(log));
  console.log(JSON.stringify({ mode: 'applied', candidates: [...records.keys()], commands, registry_after_sha256: transaction.registry_after_sha256 }, null, 2));
}

try { main(); } catch (error) { console.error(`acceptance admission: ${error instanceof Error ? error.message : String(error)}`); process.exitCode = 1; }
