import { createHash } from 'node:crypto';
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const output = path.join(root, 'docs/research/2026-09-15-home-completion/source-quality.json');
const budgetPath = path.join(root, 'docs/research/2026-09-15-home-completion/source-quality-budget.json');
const files = [
  'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/functions.php',
  'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/patterns/home-hero.php',
  'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/patterns/home-sections.php',
];

const syntax = files.map(file => {
  const result = spawnSync('php', ['-l', file], { cwd: root, encoding: 'utf8' });
  return { file, pass: result.status === 0, detail: (result.stdout || result.stderr).trim() };
});
const phpcsRun = spawnSync('./vendor/bin/phpcs', ['--standard=WordPress-Core', '--report=json', ...files], {
  cwd: root,
  encoding: 'utf8',
  maxBuffer: 8 * 1024 * 1024,
});
if (![0, 1, 2].includes(phpcsRun.status)) throw new Error(phpcsRun.stderr || `PHPCS exited ${phpcsRun.status}`);
const phpcs = JSON.parse(phpcsRun.stdout);
const current = { errors: phpcs.totals.errors, warnings: phpcs.totals.warnings };
if (!fs.existsSync(output)) throw new Error('PHPCS evidence ledger is missing; refusing to initialize implicitly');
if (!fs.existsSync(budgetPath)) throw new Error('PHPCS budget ledger is missing; refusing to initialize implicitly');
const previous = JSON.parse(fs.readFileSync(output, 'utf8'));
const budget = JSON.parse(fs.readFileSync(budgetPath, 'utf8'));
const previousExpected = previous?.phpcs?.expected;
if (!Number.isInteger(previousExpected?.errors) || !Number.isInteger(previousExpected?.warnings)) {
  throw new Error('PHPCS evidence ledger has no valid expected baseline');
}
if (!Number.isInteger(budget?.errors) || !Number.isInteger(budget?.warnings)) {
  throw new Error('PHPCS budget ledger has no valid baseline');
}
if (previousExpected.errors !== budget.errors || previousExpected.warnings !== budget.warnings) {
  throw new Error('PHPCS evidence and budget ledgers disagree; refusing to rebaseline');
}
const expected = {
  errors: Math.min(budget.errors, current.errors),
  warnings: Math.min(budget.warnings, current.warnings),
};
const baselineTightened = expected.errors < budget.errors || expected.warnings < budget.warnings;
if (baselineTightened) {
  fs.writeFileSync(budgetPath, `${JSON.stringify({ ...budget, ...expected }, null, 2)}\n`);
}
const completed = syntax.every(row => row.pass) && current.errors <= expected.errors && current.warnings <= expected.warnings;
const sources = ['scripts/verify-home-source-quality.mjs', path.relative(root, budgetPath), ...files];
const result = {
  schema: 'wt-home-source-quality.v2',
  completed,
  phpSyntax: { files, passed: syntax.filter(row => row.pass).length, failed: syntax.filter(row => !row.pass).length, rows: syntax },
  phpcs: {
    standard: 'WordPress-Core',
    expected,
    previousExpected,
    baselineTightened,
    current,
    newMessageOrSniffCount: current.errors - expected.errors + current.warnings - expected.warnings,
    note: '既存functions.phpの違反を含む。WPCS全体PASSとは扱わず、記録済み件数からの増加を拒否する。',
  },
  sourceDigests: Object.fromEntries(sources.map(file => [file, createHash('sha256').update(fs.readFileSync(path.join(root, file))).digest('hex')])),
};
fs.writeFileSync(output, `${JSON.stringify(result, null, 2)}\n`);
console.log(JSON.stringify({ completed, syntax: result.phpSyntax, phpcs: result.phpcs }, null, 2));
if (!completed) process.exitCode = 1;
