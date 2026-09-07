import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { createHash } from 'node:crypto';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const digest = bytes => createHash('sha256').update(bytes).digest('hex');
const bytes = name => {
  const resolved = path.resolve(root, name);
  if (!resolved.startsWith(root + path.sep)) throw Error('Evidence must stay inside the repository');
  return fs.readFileSync(resolved);
};
const read = name => JSON.parse(bytes(name));
const ir = read('docs/requirements/l3/requirements-ir.json');
const ac = read('docs/requirements/l3/acceptance-cases.json');
const registry = read('docs/research/2026-09-08-selection-catalog/acceptance-evidence.json');
const known = new Map(ac.cases.map(c => [c.id, c]));
if (!ir.requirements.length || !ac.cases.length || known.size !== ac.cases.length) throw Error('Empty or duplicate acceptance scope');
const declared = new Set();
for (const requirement of ir.requirements) {
  for (const id of requirement.acceptance_ids) {
    if (declared.has(id) || known.get(id)?.requirement_id !== requirement.id) throw Error(`Missing or mismatched acceptance: ${id}`);
    declared.add(id);
  }
}
if (declared.size !== known.size) throw Error('Acceptance registry differs from the requirement scope');
for (const id of Object.keys(registry.cases)) if (!known.has(id)) throw Error(`Unknown acceptance ID: ${id}`);
const proofCache = new Map();
const proof = ref => {
  if (!proofCache.has(ref)) proofCache.set(ref, { raw: bytes(ref), value: read(ref) });
  return proofCache.get(ref);
};
const rows = ac.cases.map(c => {
  const requirement = ir.requirements.find(r => r.id === c.requirement_id);
  const evidence = registry.cases[c.id];
  const row = { id: c.id, requirement_id: c.requirement_id, oracle: c.oracle, polarity: c.polarity,
    status: 'missing', scope: '', remaining: ['再現手順・操作デモ・実測行と、この受入条件全体の対応付けが必要'], evidence: [] };
  if (!evidence) return row;
  const failures = [];
  if (evidence.requirement_digest !== requirement.semantic_digest || evidence.oracle_sha256 !== digest(c.oracle)) failures.push('要求または受入条件が記録後に変更された');
  for (const [ref, expected] of Object.entries(evidence.source_digests || {})) {
    if (!fs.existsSync(path.join(root, ref)) || digest(bytes(ref)) !== expected) failures.push(`実装・検証コードの変更: ${ref}`);
  }
  if (!Object.keys(evidence.source_digests || {}).length) failures.push('実装・検証コードのdigestがない');
  for (const ref of evidence.proofs || []) {
    try {
      const { raw, value } = proof(ref.path);
      if (digest(raw) !== ref.sha256 || value.completed !== true) failures.push(`未完了または証跡変更: ${ref.path}`);
      const actualRows = value.rows || value.checks || [];
      if (!ref.row_names?.length) failures.push(`検証行の指定なし: ${ref.path}`);
      for (const name of ref.row_names || []) {
        const matches = actualRows.filter(r => r.name === name);
        if (!matches.length || matches.some(r => r.pass !== true)) failures.push(`成功行がない: ${ref.path} / ${name}`);
      }
    } catch { failures.push(`証跡を取得できない: ${ref.path}`); }
  }
  if (!evidence.proofs?.length) failures.push('証跡の指定なし');
  if (!['partial', 'verified_in_poc'].includes(evidence.status)) failures.push('無効な証拠状態');
  if (!evidence.scope || !Array.isArray(evidence.remaining)) failures.push('確認範囲・残りが未定義');
  if (evidence.status === 'verified_in_poc' && evidence.remaining?.length) failures.push('未確認事項があるため完了扱い不可');
  return { ...row, status: failures.length ? 'stale' : evidence.status, scope: evidence.scope,
    remaining: [...(evidence.remaining || []), ...failures], evidence: evidence.proofs || [] };
});
const counts = Object.fromEntries(['missing', 'partial', 'verified_in_poc', 'stale'].map(s => [s, rows.filter(r => r.status === s).length]));
const report = { schema: 'wt-catalog-acceptance-audit.v1', requirementCount: ir.requirements.length, acceptanceCount: rows.length,
  counts, complete: rows.every(r => r.status === 'verified_in_poc'), rows };
fs.writeFileSync(path.join(root, 'docs/research/2026-09-08-selection-catalog/acceptance-audit.json'), JSON.stringify(report, null, 2) + '\n');
console.log(JSON.stringify({ requirements: report.requirementCount, acceptance: report.acceptanceCount, ...counts, complete: report.complete }));
if (counts.stale) process.exitCode = 1;
