import fs from 'node:fs';
import path from 'node:path';
import { createHash } from 'node:crypto';
import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const registryPath = 'docs/research/2026-09-08-selection-catalog/acceptance-evidence.json';
const logPath = 'docs/research/2026-09-08-selection-catalog/acceptance-rebind-log.json';
const digest = value => createHash('sha256').update(value).digest('hex');
const bytes = file => fs.readFileSync(path.join(root, file));
const read = file => JSON.parse(bytes(file));
const stable = value => `${JSON.stringify(value, null, 2)}\n`;
const fail = message => { throw new Error(message); };

function validateOracleCommands(commands, proofPaths) {
  const packageDeclared = read('package.json').catalogOracles;
  if (!packageDeclared || typeof packageDeclared !== 'object' || Array.isArray(packageDeclared)) fail('package.json catalogOracles mapping is required');
  const configDeclared = (() => {
    try { return read('config/catalog-admission-oracles.json').commands; }
    catch { return {}; }
  })();
  if (!configDeclared || typeof configDeclared !== 'object' || Array.isArray(configDeclared)) fail('catalog admission oracle mapping is required');
  const expected = new Map();
  for (const proofPath of proofPaths) {
    const packageScript = packageDeclared[proofPath];
    const configCommand = configDeclared[proofPath];
    const packageCommand = typeof packageScript === 'string' && packageScript ? ['npm', 'run', packageScript] : null;
    const validConfig = Array.isArray(configCommand) && configCommand.length && configCommand.every(value => typeof value === 'string' && value);
    if (validConfig && commands.some(command => JSON.stringify(command) === JSON.stringify(configCommand))) expected.set(proofPath, configCommand);
    else if (packageCommand) expected.set(proofPath, packageCommand);
    else if (validConfig) expected.set(proofPath, configCommand);
    else fail(`no declared catalog oracle for proof: ${proofPath}`);
  }
  const invoked = new Set();
  for (const command of commands) {
    const match = [...expected.entries()].find(([, declared]) => JSON.stringify(declared) === JSON.stringify(command));
    if (!match) fail(`oracle command is not declared for the selected proofs: ${JSON.stringify(command)}`);
    const key = match[0];
    if (invoked.has(key)) fail(`oracle command is duplicated: ${JSON.stringify(command)}`);
    invoked.add(key);
  }
  for (const [proofPath, declared] of expected) if (!invoked.has(proofPath)) fail(`declared oracle was not invoked for ${proofPath}: ${JSON.stringify(declared)}`);
}

function git(args) {
  const result = spawnSync('git', args, { cwd: root, encoding: 'utf8' });
  if (result.status !== 0) fail(result.stderr || `git ${args.join(' ')} failed`);
  return result.stdout.trim();
}

function parseArgs(argv) {
  let eventBase = '';
  if (process.env.CI && process.env.GITHUB_EVENT_PATH) {
    try {
      const event = JSON.parse(fs.readFileSync(process.env.GITHUB_EVENT_PATH, 'utf8'));
      eventBase = event.pull_request?.base?.sha || event.before || '';
    } catch { eventBase = ''; }
  }
  const options = {
    cases: [], commands: [], rowRenames: [], apply: false, check: false,
    baseRef: process.env.CATALOG_EVIDENCE_BASE_REF || eventBase || (process.env.CI ? 'HEAD^' : 'HEAD'),
  };
  for (let index = 0; index < argv.length; index += 1) {
    const arg = argv[index];
    if (arg === '--case') options.cases.push(argv[++index]);
    else if (arg === '--command-json') options.commands.push(JSON.parse(argv[++index]));
    else if (arg === '--row-rename-json') options.rowRenames.push(JSON.parse(argv[++index]));
    else if (arg === '--base-ref') options.baseRef = argv[++index];
    else if (arg === '--apply') options.apply = true;
    else if (arg === '--check') options.check = true;
    else fail(`unknown argument: ${arg}`);
  }
  for (const command of options.commands) {
    if (!Array.isArray(command) || !command.length || command.some(value => typeof value !== 'string' || !value)) fail('--command-json must be a non-empty JSON argv array');
  }
  for (const item of options.rowRenames) {
    if (!item || !['case', 'proof', 'from', 'to'].every(key => typeof item[key] === 'string' && item[key])) fail('--row-rename-json requires case, proof, from, and to');
  }
  return options;
}

function validateProof(proof) {
  const value = read(proof.path);
  if (value.completed !== true) fail(`proof is incomplete: ${proof.path}`);
  const rows = [...(Array.isArray(value.rows) ? value.rows : []), ...(Array.isArray(value.checks) ? value.checks : [])];
  for (const name of proof.row_names || []) {
    const matches = rows.filter(row => row.name === name);
    if (!matches.length || matches.some(row => row.pass !== true)) fail(`proof row is not passing: ${proof.path} / ${name}`);
  }
  return value;
}

function entryDiff(before, after, { allowAdditions = false } = {}) {
  const changes = [];
  const ids = new Set([...Object.keys(before.cases || {}), ...Object.keys(after.cases || {})]);
  for (const id of ids) {
    const left = before.cases?.[id];
    const right = after.cases?.[id];
    if (!left || !right) {
      if (allowAdditions && !left && right) {
        changes.push({ kind: 'admit', case_id: id, path: 'registry.cases', before: null, after: 'present' });
        continue;
      }
      fail(`case addition/removal is not a rebind: ${id}`);
    }
    const leftCopy = structuredClone(left);
    const rightCopy = structuredClone(right);
    const sourceKeys = new Set([...Object.keys(left.source_digests || {}), ...Object.keys(right.source_digests || {})]);
    for (const source of sourceKeys) {
      if (left.source_digests?.[source] !== right.source_digests?.[source]) changes.push({ kind: 'source', case_id: id, path: source, before: left.source_digests?.[source], after: right.source_digests?.[source] });
    }
    const proofKeys = new Set([...(left.proofs || []).map(item => item.path), ...(right.proofs || []).map(item => item.path)]);
    for (const proofPath of proofKeys) {
      const leftProof = (left.proofs || []).find(item => item.path === proofPath);
      const rightProof = (right.proofs || []).find(item => item.path === proofPath);
      if (!leftProof || !rightProof) fail(`proof addition/removal is not a rebind: ${id} / ${proofPath}`);
      if (leftProof.sha256 !== rightProof.sha256) changes.push({ kind: 'proof', case_id: id, path: proofPath, before: leftProof.sha256, after: rightProof.sha256 });
      if (JSON.stringify(leftProof.row_names || []) !== JSON.stringify(rightProof.row_names || [])) {
        changes.push({ kind: 'row_names', case_id: id, path: proofPath, before: leftProof.row_names || [], after: rightProof.row_names || [] });
      }
      leftProof.sha256 = rightProof.sha256;
    }
    leftCopy.source_digests = rightCopy.source_digests;
    leftCopy.proofs = rightCopy.proofs;
    if (JSON.stringify(leftCopy) !== JSON.stringify(rightCopy)) fail(`non-digest field changed outside the rebind tool: ${id}`);
  }
  return changes;
}

function check(baseRef) {
  let baseRaw;
  try { baseRaw = git(['show', `${baseRef}:${registryPath}`]); }
  catch {
    const fetched = spawnSync('git', ['fetch', '--no-tags', '--depth=1', 'origin', baseRef], { cwd: root, encoding: 'utf8' });
    if (fetched.status !== 0) fail(`base registry is unavailable: ${baseRef}`);
    try { baseRaw = git(['show', `${baseRef}:${registryPath}`]); }
    catch { fail(`base registry is unavailable after fetch: ${baseRef}`); }
  }
  const currentRaw = bytes(registryPath).toString();
  const base = JSON.parse(baseRaw);
  const current = JSON.parse(currentRaw);
  const changes = entryDiff(base, current, { allowAdditions: true });
  const baseLogRaw = (() => { try { return git(['show', `${baseRef}:${logPath}`]); } catch { return ''; } })();
  const currentLog = fs.existsSync(path.join(root, logPath)) ? read(logPath) : { schema: 'wt-acceptance-rebind-log.v1', transactions: [] };
  const baseTransactions = baseLogRaw ? JSON.parse(baseLogRaw).transactions || [] : [];
  const added = (currentLog.transactions || []).slice(baseTransactions.length);
  if (!changes.length) {
    if (added.length) fail('rebind log changed without acceptance digest changes');
    console.log('acceptance rebind check: OK (no registry changes)');
    return;
  }
  if (!added.length) fail('acceptance-evidence.json changed without a rebind transaction');
  let expected = digest(Buffer.from(`${baseRaw}\n`.replace(/\n\n$/u, '\n')));
  const covered = [];
  const latestProofs = new Map();
  for (const transaction of added) {
    if (transaction.registry_before_sha256 !== expected) fail('rebind transaction chain does not start at the base registry');
    expected = transaction.registry_after_sha256;
    covered.push(...(transaction.changes || []));
    if (!transaction.commands?.length || !transaction.proof_writes?.length) fail('rebind transaction lacks oracle execution evidence');
    if (transaction.kind && !['rebind', 'admit'].includes(transaction.kind)) fail(`unknown acceptance transaction kind: ${transaction.kind}`);
    if ((transaction.kind ?? 'rebind') === 'admit') {
      if (!Array.isArray(transaction.candidate_paths) || !transaction.candidate_paths.length) fail('admission transaction lacks candidate paths');
      for (const [candidatePath, expected] of Object.entries(transaction.candidate_digests || {})) {
        if (!fs.existsSync(path.join(root, candidatePath)) || digest(bytes(candidatePath)) !== expected) fail(`admission candidate drift: ${candidatePath}`);
      }
      if (!(transaction.changes || []).every(change => change.kind === 'admit')) fail('admission transaction contains non-admission changes');
    }
    validateOracleCommands(transaction.commands, transaction.proof_writes.map(proof => proof.path));
    for (const proof of transaction.proof_writes) {
      if (proof.rewritten !== true) fail(`proof rewrite was not observed: ${proof.path}`);
      latestProofs.set(proof.path, proof.after_sha256);
    }
  }
  for (const [proofPath, expectedProof] of latestProofs) if (digest(bytes(proofPath)) !== expectedProof) fail(`rebind proof drift: ${proofPath}`);
  if (expected !== digest(Buffer.from(currentRaw))) fail('rebind transaction chain does not reach the current registry');
  const normalize = list => list.map(item => `${item.kind}:${item.case_id}:${item.path}:${item.before}->${item.after}`).sort();
  const collapsed = new Map();
  for (const item of covered) {
    const key = `${item.kind}:${item.case_id}:${item.path}`;
    const previous = collapsed.get(key);
    if (previous && previous.after !== item.before) fail(`rebind digest chain is discontinuous: ${key}`);
    collapsed.set(key, previous ? { ...previous, after: item.after } : item);
  }
  if (JSON.stringify(normalize(changes)) !== JSON.stringify(normalize([...collapsed.values()]))) fail('rebind transaction does not exactly cover registry digest changes');
  console.log(`acceptance rebind check: OK (${changes.length} digest change(s), ${added.length} transaction(s))`);
}

function plan(options) {
  if (!options.cases.length) fail('at least one --case is required');
  const registryRaw = bytes(registryPath);
  const registry = JSON.parse(registryRaw);
  const selectedIds = new Set(options.cases);
  const changedPaths = new Set(git(['diff', '--name-only', options.baseRef, '--']).split('\n').filter(Boolean));
  const selected = options.cases.map(id => {
    const evidence = registry.cases[id];
    if (!evidence) fail(`unknown or missing evidence case: ${id}`);
    const staleSources = Object.entries(evidence.source_digests || {}).filter(([source, expected]) => digest(bytes(source)) !== expected);
    for (const [source] of staleSources) if (!changedPaths.has(source)) fail(`stale source is not an actual working-tree change: ${id} / ${source}`);
    return { id, evidence, staleSources };
  });
  const staleCount = selected.reduce((sum, item) => sum + item.staleSources.length, 0);
  for (const rename of options.rowRenames) {
    if (!selectedIds.has(rename.case)) fail(`row rename case is not selected: ${rename.case}`);
    const proof = registry.cases[rename.case].proofs?.find(item => item.path === rename.proof);
    if (!proof) fail(`row rename proof is not registered: ${rename.proof}`);
    if ((proof.row_names || []).filter(name => name === rename.from).length !== 1 || proof.row_names.includes(rename.to)) fail(`row rename is not one-to-one: ${rename.case}`);
  }
  for (const [id, evidence] of Object.entries(registry.cases || {})) {
    if (selectedIds.has(id)) continue;
    const stale = Object.entries(evidence.source_digests || {}).find(([source, expected]) => digest(bytes(source)) !== expected);
    if (stale) fail(`all cases affected by a changed source must be selected: ${id} / ${stale[0]}`);
  }
  const proofs = new Map();
  for (const { evidence } of selected) for (const proof of evidence.proofs || []) proofs.set(proof.path, proof);
  const proofUpdates = [...proofs].filter(([proofPath, proof]) => digest(bytes(proofPath)) !== proof.sha256);
  for (const [proofPath] of proofUpdates) {
    if (!changedPaths.has(proofPath)) fail(`stale proof is not an actual working-tree change: ${proofPath}`);
  }
  if (!staleCount && !options.rowRenames.length && !proofUpdates.length) {
    fail('selected cases have no changed source digests, proof files, or proof rows to rebind');
  }
  for (const [id, evidence] of Object.entries(registry.cases || {})) {
    if (selectedIds.has(id)) continue;
    const shared = (evidence.proofs || []).find(proof => proofs.has(proof.path));
    if (shared) fail(`all cases sharing a regenerated proof must be selected: ${id} / ${shared.path}`);
  }
  const summary = { cases: selected.map(item => item.id), source_updates: selected.flatMap(item => item.staleSources.map(([source, before]) => ({ case_id: item.id, path: source, before, after: digest(bytes(source)) }))), proof_updates: proofUpdates.map(([path, proof]) => ({ path, before: proof.sha256, observed: digest(bytes(path)) })), row_renames: options.rowRenames, proofs: [...proofs.keys()], commands: options.commands };
  if (!options.apply) {
    console.log(JSON.stringify({ mode: 'dry-run', ...summary }, null, 2));
    return;
  }
  if (!options.commands.length) fail('--apply requires at least one --command-json oracle command');
  validateOracleCommands(options.commands, proofs.keys());
  const snapshots = new Map([...proofs.keys()].map(proofPath => {
    const stat = fs.statSync(path.join(root, proofPath), { bigint: true });
    return [proofPath, { sha256: digest(bytes(proofPath)), mtimeNs: stat.mtimeNs, ctimeNs: stat.ctimeNs }];
  }));
  for (const command of options.commands) {
    const result = spawnSync(command[0], command.slice(1), { cwd: root, stdio: 'inherit' });
    if (result.status !== 0) fail(`oracle command failed: ${JSON.stringify(command)}`);
  }
  for (const rename of options.rowRenames) {
    const proof = registry.cases[rename.case].proofs.find(item => item.path === rename.proof);
    proof.row_names = proof.row_names.map(name => name === rename.from ? rename.to : name);
  }
  const proofWrites = [];
  for (const [proofPath, proof] of proofs) {
    const stat = fs.statSync(path.join(root, proofPath), { bigint: true });
    const before = snapshots.get(proofPath);
    const rewritten = stat.mtimeNs > before.mtimeNs || stat.ctimeNs > before.ctimeNs;
    if (!rewritten) fail(`oracle did not rewrite referenced proof in this execution: ${proofPath}`);
    const value = validateProof(proof);
    const declared = value.sourceDigests ?? value.source_digests;
    if (!declared || typeof declared !== 'object' || Array.isArray(declared)) fail(`proof lacks sourceDigests: ${proofPath}`);
    for (const [source, expected] of Object.entries(declared)) {
      if (!fs.existsSync(path.join(root, source)) || digest(bytes(source)) !== expected) fail(`proof source digest mismatch: ${proofPath} / ${source}`);
      for (const { evidence } of selected) {
        if ((evidence.proofs || []).some(item => item.path === proofPath)) evidence.source_digests[source] = expected;
      }
    }
    const after = digest(bytes(proofPath));
    proofWrites.push({ path: proofPath, before_sha256: before.sha256, after_sha256: after, rewritten: true });
    for (const { evidence } of selected) for (const item of evidence.proofs || []) if (item.path === proofPath) item.sha256 = after;
  }
  for (const item of selected) for (const [source] of item.staleSources) item.evidence.source_digests[source] = digest(bytes(source));
  const afterRaw = Buffer.from(stable(registry));
  const changes = entryDiff(JSON.parse(registryRaw), registry);
  const transaction = {
    schema: 'wt-acceptance-rebind-transaction.v1',
    cases: selected.map(item => item.id),
    commands: options.commands,
    changes,
    proof_writes: proofWrites,
    registry_before_sha256: digest(registryRaw),
    registry_after_sha256: digest(afterRaw),
  };
  const log = fs.existsSync(path.join(root, logPath)) ? read(logPath) : { schema: 'wt-acceptance-rebind-log.v1', transactions: [] };
  if (log.schema !== 'wt-acceptance-rebind-log.v1' || !Array.isArray(log.transactions)) fail('invalid acceptance rebind log');
  log.transactions.push(transaction);
  fs.writeFileSync(path.join(root, registryPath), afterRaw);
  fs.writeFileSync(path.join(root, logPath), stable(log));
  console.log(JSON.stringify({ mode: 'applied', ...summary, registry_after_sha256: transaction.registry_after_sha256 }, null, 2));
}

try {
  const options = parseArgs(process.argv.slice(2));
  if (options.check) check(options.baseRef);
  else plan(options);
} catch (error) {
  console.error(`acceptance rebind: ${error instanceof Error ? error.message : String(error)}`);
  process.exitCode = 1;
}
