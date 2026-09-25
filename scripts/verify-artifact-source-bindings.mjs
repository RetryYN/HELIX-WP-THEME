import { createHash } from 'node:crypto';
import { existsSync, readFileSync, readdirSync } from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const researchRoot = path.join(root, 'docs/research');

// Historical artifacts are pinned by their own bytes. Their sourceDigests keep
// recording what each comparison measured, while live artifacts remain bound
// to current source bytes below.
const historyManifestPath = 'docs/research/2026-09-08-selection-catalog/historical-artifact-snapshots.json';
const historyManifest = JSON.parse(readFileSync(path.join(root, historyManifestPath), 'utf8'));
if (historyManifest.schema !== 'wt-historical-artifact-snapshots.v1' || !Array.isArray(historyManifest.snapshots)) {
  throw new Error(`invalid historical artifact manifest: ${historyManifestPath}`);
}
const historicalSnapshots = new Map();
for (const snapshot of historyManifest.snapshots) {
  if (!snapshot || typeof snapshot.path !== 'string' || !snapshot.path.startsWith('docs/research/') || !snapshot.path.endsWith('.json')
    || typeof snapshot.reason !== 'string' || snapshot.reason.length === 0
    || typeof snapshot.artifactSha256 !== 'string' || !/^[a-f0-9]{64}$/u.test(snapshot.artifactSha256)
    || historicalSnapshots.has(snapshot.path)) {
    throw new Error(`invalid or duplicate historical artifact entry: ${JSON.stringify(snapshot)}`);
  }
  historicalSnapshots.set(snapshot.path, snapshot);
}

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
    const historical = historicalSnapshots.get(artifactPath);
    const actualArtifactDigest = digest(absolute);
    if (actualArtifactDigest !== historical.artifactSha256) {
      findings.push({
        artifact: artifactPath,
        reason: 'historical-artifact-digest',
        expected: historical.artifactSha256,
        actual: actualArtifactDigest,
      });
    }
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
      reason: historical.reason,
      bindings: bindings.length,
      mismatches,
    });
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
