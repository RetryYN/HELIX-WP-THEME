import fs from 'node:fs';
import { createHash } from 'node:crypto';
const target = new URL('../node_modules/helix/src/doctor/l3-g3-logical-db-receipt.ts', import.meta.url);
const original = fs.readFileSync(target, 'utf8');
const hash = value => createHash('sha256').update(value).digest('hex');
const before = 'af17ac70783c1bd58b0099953bdb750b8d5cfdbf3225f9ed7d8bcb69a0dc30d9';
const after = '9817545fdedfe8b122399c209e88fdb81d522971f90c8018188d1fc0d99f0569';
if (hash(original) === after) {
  console.log('HELIX receipt package paths: already patched');
} else {
  if (hash(original) !== before) throw Error('HELIX receipt source changed; review patch before applying');
  const patched = original
    .replace('readFileSync(join(repoRoot, POLICY_PATH), "utf8")', 'readFileSync(new URL("../../" + POLICY_PATH, import.meta.url), "utf8")')
    .replace('readFileSync(join(repoRoot, SCRIPT_PATH))', 'readFileSync(new URL("../../" + SCRIPT_PATH, import.meta.url))');
  if (hash(patched) !== after) throw Error('HELIX receipt patch mismatch');
  fs.writeFileSync(target, patched);
  console.log('HELIX receipt package paths: patched');
}
