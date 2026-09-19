import { createHash } from 'node:crypto';
import { existsSync, readFileSync, readdirSync } from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const researchRoot = path.join(root, 'docs/research');

// Immutable before-state captures. Refreshing these to current bytes would erase
// comparison evidence. Exact paths keep every newly named artifact gated by default.
const historicalSnapshots = new Map([
  ['docs/research/2026-09-08-content-faces/results/header-navigation/baseline.json', 'pre-fix baseline'],
  ['docs/research/2026-09-08-content-faces/results/inheritance/baseline.json', 'pre-fix baseline'],
  ['docs/research/2026-09-08-content-faces/results/site-quality/baseline.json', 'pre-fix baseline'],
  ['docs/research/2026-09-08-form-flow/baseline.json', 'pre-fix baseline'],
  ['docs/research/2026-09-08-form-flow/boundaries-baseline.json', 'pre-fix boundary baseline'],
  ['docs/research/2026-09-08-form-flow/initial-baseline.json', 'pre-fix initial baseline'],
  ['docs/research/2026-09-08-form-flow/navigation-baseline.json', 'pre-fix navigation baseline'],
  ['docs/research/2026-09-08-site-search/results/access-baseline.json', 'pre-fix access baseline'],
  ['docs/research/2026-09-08-site-search/results/boundaries-baseline.json', 'pre-fix boundary baseline'],
  ['docs/research/2026-09-08-site-search/results/input-baseline.json', 'pre-fix input baseline'],
  ['docs/research/2026-09-08-site-search/results/out-of-range-before.json', 'pre-fix range capture'],
  ['docs/research/2026-09-09-chrome-config/baseline.json', 'pre-fix baseline'],
  ['docs/research/2026-09-09-footer-data/baseline.json', 'pre-fix baseline'],
  ['docs/research/2026-09-09-footer-data/navigation-input-baseline.json', 'pre-fix navigation baseline'],
  ['docs/research/2026-09-15-event-completion/baseline.json', 'pre-fix baseline'],
  ['docs/research/2026-09-15-home-completion/baseline.json', 'pre-fix baseline'],
  ['docs/research/2026-09-19-pricing-cards/before.json', 'before-state visual evidence'],
  ['docs/research/2026-09-19-toc-settings/baseline.json', 'pre-fix baseline'],
  ['docs/research/2026-09-20-heading-comparison/before.json', 'before-state visual evidence'],
  ['docs/research/2026-09-20-heading-fluid/before.json', 'before-state visual evidence'],
]);

function walk(directory) {
  return readdirSync(directory, { withFileTypes: true }).flatMap(entry => {
    const absolute = path.join(directory, entry.name);
    if (entry.isDirectory()) return walk(absolute);
    return entry.name.endsWith('.json') ? [absolute] : [];
  });
}

function digest(file) {
  return createHash('sha256').update(readFileSync(file)).digest('hex');
}

const jsonFiles = walk(researchRoot).sort();
const artifacts = [];
const excluded = [];
const findings = [];

for (const absolute of jsonFiles) {
  const artifactPath = path.relative(root, absolute);
  let artifact;
  try {
    artifact = JSON.parse(readFileSync(absolute, 'utf8'));
  } catch (error) {
    findings.push({ artifact: artifactPath, reason: 'parse-error', detail: error.message });
    continue;
  }
  for (const [key, value] of Object.entries(artifact)) {
    if (key === 'sourceDigests' || !/^source.*(?:digest|sha|hash)/i.test(key)) continue;
    if (!value || Array.isArray(value) || typeof value !== 'object') continue;
    const candidateBindings = Object.entries(value);
    if (candidateBindings.length && candidateBindings.every(([sourcePath, hash]) =>
      (sourcePath.includes('/') || sourcePath.includes('\\')) && typeof hash === 'string' && /^[a-f0-9]{64}$/i.test(hash))) {
      findings.push({ artifact: artifactPath, reason: 'nonstandard-binding-key', key });
    }
  }
  if (!artifact.sourceDigests || Array.isArray(artifact.sourceDigests) || typeof artifact.sourceDigests !== 'object') continue;

  if (historicalSnapshots.has(artifactPath)) {
    const bindings = Object.entries(artifact.sourceDigests);
    let mismatches = 0;
    for (const [sourcePath, expected] of bindings) {
      const source = path.resolve(root, sourcePath);
      if (source !== root && !source.startsWith(`${root}${path.sep}`)) {
        findings.push({ artifact: artifactPath, source: sourcePath, reason: 'outside-root' });
        continue;
      }
      if (!existsSync(source) || digest(source) !== expected) mismatches += 1;
    }
    excluded.push({
      path: artifactPath,
      reason: historicalSnapshots.get(artifactPath),
      bindings: bindings.length,
      mismatches,
    });
    if (mismatches === 0) findings.push({ artifact: artifactPath, reason: 'exclusion-not-needed' });
    continue;
  }

  const bindings = Object.entries(artifact.sourceDigests);
  artifacts.push({
    path: artifactPath,
    completionState: artifact.completed === true ? 'complete' : artifact.completed === false ? 'incomplete' : 'unspecified',
    bindings: bindings.length,
  });

  for (const [sourcePath, expected] of bindings) {
    const source = path.resolve(root, sourcePath);
    if (source !== root && !source.startsWith(`${root}${path.sep}`)) {
      findings.push({ artifact: artifactPath, source: sourcePath, reason: 'outside-root' });
      continue;
    }
    if (!existsSync(source)) {
      findings.push({ artifact: artifactPath, source: sourcePath, reason: 'missing' });
      continue;
    }
    const actual = digest(source);
    if (actual !== expected) findings.push({ artifact: artifactPath, source: sourcePath, reason: 'digest', expected, actual });
  }
}

for (const excludedPath of historicalSnapshots.keys()) {
  if (!excluded.some(row => row.path === excludedPath)) findings.push({ artifact: excludedPath, reason: 'unused-exclusion' });
}

const report = {
  schema: 'wt-artifact-source-binding-verification.v2',
  completed: findings.length === 0,
  discoveredJson: jsonFiles.length,
  sourceBoundArtifacts: artifacts.length + excluded.length,
  artifacts: artifacts.length,
  excludedHistoricalSnapshots: excluded.length,
  bindings: artifacts.reduce((sum, artifact) => sum + artifact.bindings, 0),
  completionStates: Object.fromEntries(['complete', 'incomplete', 'unspecified'].map(state => [state, artifacts.filter(row => row.completionState === state).length])),
  excluded,
  findings,
};

console.log(JSON.stringify(report, null, 2));
if (findings.length) process.exitCode = 1;
