import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import fs from 'node:fs/promises';
import path from 'node:path';

const root = process.cwd();
const proofPath = 'docs/research/2026-09-26-news-column-purpose-gap/results/verify.json';
const proofFile = path.join(root, proofPath);
const proof = JSON.parse(await fs.readFile(proofFile, 'utf8'));
const sourcePaths = Object.keys(proof.sourceDigests ?? {});
assert.equal(proof.schema, 'helix-news-column-purpose-poc.v1');
assert.equal(proof.completed, true, 'WordPress browser verification must have completed');
assert.equal(proof.expectedWordPress, '7.1.2');
assert.equal(sourcePaths.length, 8, 'proof must bind the fixture, verifier, and current theme inputs');
for (const source of sourcePaths) {
  const actual = createHash('sha256').update(await fs.readFile(path.join(root, source))).digest('hex');
  assert.equal(proof.sourceDigests[source], actual, `stale proof source: ${source}`);
}
assert.equal(proof.routes.length, 11, 'all purpose, filtered detail, and child archive routes must be recorded');
assert.ok(proof.routes.every(route => route.status === 200), 'all routes must return HTTP 200');
assert.equal(proof.viewports.length, 9, 'all three purpose archives must be checked at three viewport widths');
assert.ok(proof.viewports.every(item => item.documentWidth <= item.viewportWidth), 'no viewport may overflow horizontally');

const requiredRows = [
  'purpose:search-intent-archive-description-and-classification',
  'purpose:news-releases-archive-description-and-classification',
  'purpose:columns-archive-description-and-classification',
  'layout:category-guide-in-primary-before-sidebar',
  'archive:news-releases:2025-filter-to-dated-detail',
  'archive:columns:2025-filter-to-dated-detail',
  'archive:search-intent:2026-filter-to-dated-detail',
  'type:news-releases-purpose-uses-standard-post',
  'type:columns-purpose-uses-standard-post',
  'type:search-intent-purpose-uses-standard-post',
  'topics:news-releases/product-news-child-archive',
  'topics:news-releases/announcements-child-archive',
  'topics:columns/organization-child-archive',
  'topics:columns/data-child-archive',
  'topics:search-intent/guides-child-archive',
  'classification:five-topical-and-notice-child-archives',
  ...['news-releases', 'columns', 'search-intent'].flatMap(slug => [390, 768, 1440].map(width => `responsive:${slug}:${width}-no-horizontal-overflow`)),
];
const rows = new Map(proof.checks.map(row => [row.name, row]));
for (const name of requiredRows) assert.equal(rows.get(name)?.pass, true, `required proof row missing or failed: ${name}`);

// Preserve the original runtime proof bytes. Rebind/admission calls this
// verifier to check the saved run and rewrite the file atomically in-place;
// it must not add a timestamp or otherwise mutate the proof's meaning.
await fs.writeFile(proofFile, `${JSON.stringify(proof, null, 2)}\n`);
process.stdout.write(`${JSON.stringify({ proof: proofPath, checksPassed: requiredRows.length }, null, 2)}\n`);
