import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import { readFileSync, writeFileSync } from 'node:fs';

const root = 'docs/research/2026-09-08-selection-catalog';
const suites = ['tests/e2e/selection-catalog.spec.ts', 'tests/e2e/selection-catalog-filters.spec.ts'];
const sources = [`${root}/catalog.mjs`, `${root}/catalog.css`, `${root}/index.html`, ...suites, `${root}/visual-quality/filter-context/verify.mjs`];
const digest = file => createHash('sha256').update(readFileSync(file)).digest('hex');
const sourceDigests = Object.fromEntries(sources.map(file => [file, digest(file)]));
const command = ['npx', 'playwright', 'test', ...suites, '--workers=1', '--reporter=json'];
const result = JSON.parse(execFileSync(command[0], command.slice(1), { encoding: 'utf8', maxBuffer: 16 * 1024 * 1024 }));
if (result.stats.unexpected || result.stats.flaky || result.stats.skipped || !result.stats.expected) throw Error('Every catalog case must pass');
if (sources.some(file => digest(file) !== sourceDigests[file])) throw Error('Source changed during verification');
const artifact = {
  schema: 'wt-catalog-filter-verification.v1',
  scope: 'catalog selection and filter browser regression only',
  completed: true,
  command,
  playwright: { passed: result.stats.expected, failed: result.stats.unexpected, skipped: result.stats.skipped, flaky: result.stats.flaky },
  sourceDigests,
  limitations: ['Does not assert npm test or public safety; those checks run separately.', 'Existing filter screenshots are earlier visual evidence.'],
};
writeFileSync(`${root}/visual-quality/filter-context/verification.json`, JSON.stringify(artifact, null, 2) + '\n');
console.log(`catalog browser verification: ${result.stats.expected} passed`);
