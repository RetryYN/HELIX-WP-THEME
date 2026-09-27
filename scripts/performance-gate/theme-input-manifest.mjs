import { createHash } from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';

export function collectThemeInputDigests(root, themeDir) {
  const themeRoot = path.join(root, themeDir);
  const digests = {};
  const visit = directory => {
    for (const entry of fs.readdirSync(directory, { withFileTypes: true })) {
      if (entry.isSymbolicLink()) continue;
      const absolutePath = path.join(directory, entry.name);
      if (entry.isDirectory()) visit(absolutePath);
      else if (entry.isFile()) {
        const relativePath = path.relative(root, absolutePath).split(path.sep).join('/');
        digests[relativePath] = createHash('sha256').update(fs.readFileSync(absolutePath)).digest('hex');
      }
    }
  };
  visit(themeRoot);
  return Object.fromEntries(Object.entries(digests).sort(([left], [right]) => left.localeCompare(right)));
}

export function compareMountedSourceHashes(runtimeHashes, localSourceDigests, themeDir, sourceFiles) {
  const errors = [];
  const isRecord = value => value !== null && typeof value === 'object' && !Array.isArray(value);
  if (!isRecord(runtimeHashes)) return { matches: false, errors: ['runtime sourceHashes is missing or invalid'] };
  if (!isRecord(localSourceDigests)) return { matches: false, errors: ['local sourceDigests is missing or invalid'] };
  if (!Array.isArray(sourceFiles) || sourceFiles.length === 0 || sourceFiles.some(file => typeof file !== 'string' || !file)) {
    return { matches: false, errors: ['theme source file list is missing or invalid'] };
  }

  const expected = Object.fromEntries(sourceFiles.map(file => [file, localSourceDigests[`${themeDir}/${file}`]]));
  const runtimeKeys = Object.keys(runtimeHashes).sort();
  const expectedKeys = Object.keys(expected).sort();
  for (const key of expectedKeys) if (!Object.hasOwn(runtimeHashes, key)) errors.push(`runtime source hash missing: ${key}`);
  for (const key of runtimeKeys) if (!Object.hasOwn(expected, key)) errors.push(`unexpected runtime source hash: ${key}`);
  for (const key of expectedKeys) {
    const expectedDigest = expected[key];
    const runtimeDigest = runtimeHashes[key];
    if (typeof expectedDigest !== 'string' || !/^[a-f0-9]{64}$/u.test(expectedDigest)) {
      errors.push(`local source digest missing or invalid: ${key}`);
    } else if (typeof runtimeDigest !== 'string' || !/^[a-f0-9]{64}$/u.test(runtimeDigest)) {
      errors.push(`runtime source digest missing or invalid: ${key}`);
    } else if (runtimeDigest !== expectedDigest) {
      errors.push(`source digest mismatch: ${key}`);
    }
  }
  return { matches: errors.length === 0, expectedFileCount: expectedKeys.length, runtimeFileCount: runtimeKeys.length, errors };
}
