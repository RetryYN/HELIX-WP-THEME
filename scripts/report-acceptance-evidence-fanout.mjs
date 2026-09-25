import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const registryPath = 'docs/research/2026-09-08-selection-catalog/acceptance-evidence.json';

export function buildFanout(registry) {
  const cases = registry?.cases;
  if (!cases || typeof cases !== 'object' || Array.isArray(cases)) {
    throw new TypeError('acceptance evidence registry must contain a cases object');
  }
  const paths = new Map();
  for (const [caseId, evidence] of Object.entries(cases)) {
    if (!evidence || typeof evidence !== 'object' || Array.isArray(evidence)) {
      throw new TypeError(`acceptance evidence case must be an object: ${caseId}`);
    }
    const sources = evidence.source_digests ?? {};
    if (!sources || typeof sources !== 'object' || Array.isArray(sources)) {
      throw new TypeError(`source_digests must be an object: ${caseId}`);
    }
    for (const source of Object.keys(sources)) {
      if (!paths.has(source)) paths.set(source, new Set());
      paths.get(source).add(caseId);
    }
  }
  const bindings = [...paths]
    .map(([path, caseIds]) => ({ path, case_count: caseIds.size, case_ids: [...caseIds].sort() }))
    .sort((left, right) => right.case_count - left.case_count || left.path.localeCompare(right.path));
  return {
    schema: 'wt-acceptance-evidence-declared-fanout.v1',
    interpretation: 'Counts declared source_digests bindings only; it does not claim runtime or behavioral impact.',
    case_count: Object.keys(cases).length,
    source_count: bindings.length,
    bindings,
  };
}

if (process.argv[1] && path.resolve(process.argv[1]) === fileURLToPath(import.meta.url)) {
  const registry = JSON.parse(fs.readFileSync(path.join(root, registryPath), 'utf8'));
  process.stdout.write(`${JSON.stringify(buildFanout(registry), null, 2)}\n`);
}
