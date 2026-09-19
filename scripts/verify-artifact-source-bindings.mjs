import { createHash } from 'node:crypto';
import { existsSync, readFileSync, readdirSync } from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const researchRoot = path.join(root, 'docs/research');
const artifactNames = new Set(['verify.json', 'verification.json']);

function walk(directory) {
  return readdirSync(directory, { withFileTypes: true }).flatMap(entry => {
    const absolute = path.join(directory, entry.name);
    if (entry.isDirectory()) return walk(absolute);
    return artifactNames.has(entry.name) ? [absolute] : [];
  });
}

function digest(file) {
  return createHash('sha256').update(readFileSync(file)).digest('hex');
}

const discovered = walk(researchRoot).sort();
const artifacts = [];
const findings = [];

for (const absolute of discovered) {
  const artifact = JSON.parse(readFileSync(absolute, 'utf8'));
  if (artifact.completed !== true || !artifact.sourceDigests || Array.isArray(artifact.sourceDigests)) continue;
  const artifactPath = path.relative(root, absolute);
  const bindings = Object.entries(artifact.sourceDigests);
  artifacts.push({ path: artifactPath, bindings: bindings.length });
  for (const [sourcePath, expected] of bindings) {
    const source = path.join(root, sourcePath);
    if (!existsSync(source)) {
      findings.push({ artifact: artifactPath, source: sourcePath, reason: 'missing' });
      continue;
    }
    const actual = digest(source);
    if (actual !== expected) findings.push({ artifact: artifactPath, source: sourcePath, reason: 'digest', expected, actual });
  }
}

const report = {
  schema: 'wt-artifact-source-binding-verification.v1',
  completed: findings.length === 0,
  discovered: discovered.length,
  artifacts: artifacts.length,
  bindings: artifacts.reduce((sum, artifact) => sum + artifact.bindings, 0),
  findings,
};

console.log(JSON.stringify(report, null, 2));
if (findings.length) process.exitCode = 1;
