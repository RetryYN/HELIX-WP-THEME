import assert from 'node:assert/strict';
import { createHash } from 'node:crypto';
import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';

const repository = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const research = 'docs/research/2026-09-10-current-theme-quality';
const theme = 'docs/research/2026-09-05-design-prototype-03/theme/helix-wt';
const verifier = `${research}/verify.mjs`;

function fixture(t) {
  const root = fs.mkdtempSync(path.join(os.tmpdir(), 'wt-current-theme-quality-'));
  t.after(() => fs.rmSync(root, { recursive: true, force: true }));
  const copy = relative => {
    const source = path.join(repository, relative);
    const target = path.join(root, relative);
    fs.mkdirSync(path.dirname(target), { recursive: true });
    fs.copyFileSync(source, target);
  };
  for (const name of ['before.json', 'after.json', 'after-differences.json', 'before-common.json', 'after-common.json', 'source-digests.json', 'fixtures.json', 'verify.mjs']) copy(`${research}/${name}`);
  for (const name of ['theme.json', 'styles/rules.json', 'styles/mincho.json', 'assets/css/theme.css', 'assets/css/content-faces.css']) copy(`${theme}/${name}`);
  return root;
}

function run(root) {
  return spawnSync(process.execPath, [verifier], { cwd: root, encoding: 'utf8' });
}

test('current-theme verification accepts valid source edits despite historical digest mismatch', t => {
  const root = fixture(t);
  const cssPath = path.join(root, theme, 'assets/css/theme.css');
  fs.appendFileSync(cssPath, '\n/* current regression fixture */\n');

  const result = run(root);
  assert.equal(result.status, 0, result.stderr || result.stdout);
  const report = JSON.parse(fs.readFileSync(path.join(root, research, 'verify.json'), 'utf8'));
  assert.equal(report.pass, true);
  assert.equal(report.failed, 0);
  const actual = createHash('sha256').update(fs.readFileSync(cssPath)).digest('hex');
  assert.equal(report.sourceDigests[`${theme}/assets/css/theme.css`], actual);
  const verifierPath = path.join(root, research, 'verify.mjs');
  const verifierDigest = createHash('sha256').update(fs.readFileSync(verifierPath)).digest('hex');
  assert.equal(report.sourceDigests[`${research}/verify.mjs`], verifierDigest);
});

test('current-theme verification still rejects a broken live CSS invariant', t => {
  const root = fixture(t);
  const cssPath = path.join(root, theme, 'assets/css/theme.css');
  fs.appendFileSync(cssPath, '\n.fixture { color: var(--wp--preset--color--soft); }\n');

  const result = run(root);
  assert.notEqual(result.status, 0);
  const report = JSON.parse(fs.readFileSync(path.join(root, research, 'verify.json'), 'utf8'));
  assert.equal(report.pass, false);
  assert.ok(report.rows.some(row => row.name === 'no undefined soft reference' && !row.pass));
});
