import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import test from 'node:test';
import { fileURLToPath } from 'node:url';
import { runProbeProcess } from '../scripts/performance-gate/probe-execution.mjs';
import { collectThemeInputDigests } from '../scripts/performance-gate/theme-input-manifest.mjs';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const runner = fs.readFileSync(path.join(root, 'scripts/run-performance-gate.mjs'), 'utf8');
const workflow = fs.readFileSync(path.join(root, '.github/workflows/theme-quality-gate.yml'), 'utf8');

test('real child execution rejects stale report, child failure, and incomplete fresh report', () => {
  const tempDir = fs.mkdtempSync(path.join(os.tmpdir(), 'helix-probe-runner-'));
  const reportPath = path.join(tempDir, 'probe.json');
  const invoke = code => runProbeProcess({
    command: process.execPath,
    args: ['-e', code],
    cwd: tempDir,
    env: process.env,
    reportPath,
  });
  try {
    fs.writeFileSync(reportPath, JSON.stringify({ completed: true, marker: 'stale-success' }));
    const failedChild = invoke('process.exit(7)');
    assert.equal(failedChild.exitCode, 7);
    assert.equal(failedChild.reportCreated, false);
    assert.equal(failedChild.report, null);
    assert.equal(failedChild.completed, false);

    fs.writeFileSync(reportPath, JSON.stringify({ completed: true, marker: 'stale-success' }));
    const noReport = invoke('process.exit(0)');
    assert.equal(noReport.exitCode, 0);
    assert.equal(noReport.reportCreated, false);
    assert.equal(noReport.completed, false);

    const incomplete = invoke(`require('node:fs').writeFileSync(${JSON.stringify(reportPath)}, JSON.stringify({completed:false}))`);
    assert.equal(incomplete.report?.completed, false);
    assert.equal(incomplete.completed, false);

    const fresh = invoke(`require('node:fs').writeFileSync(${JSON.stringify(reportPath)}, JSON.stringify({completed:true, marker:'fresh'}))`);
    assert.equal(fresh.exitCode, 0);
    assert.equal(fresh.report?.completed, true);
    assert.equal(fresh.report?.marker, 'fresh');
    assert.equal(fresh.completed, true);
  } finally {
    fs.rmSync(tempDir, { recursive: true, force: true });
  }
});

test('the runner adopts parsed completion and propagates any gate failure to process exit', () => {
  assert.match(runner, /const result = runProbeProcess\(/u);
  assert.match(runner, /failures\.length === 0 && validation\.accepted/u);
  assert.match(runner, /\.\.\.failures\.map\(message => \(\{ path: 'runner', message \}\)\)/u);
  assert.doesNotMatch(runner, /\|\|\s*true/u);
});

test('LP INP probe measures the existing page-lp contact anchor without submitting', () => {
  const probe = fs.readFileSync(path.join(root, 'scripts/performance-gate/remaining-surfaces-inp-probe.mjs'), 'utf8');
  const lpPattern = fs.readFileSync(path.join(root, 'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/patterns/lp.php'), 'utf8');
  assert.match(lpPattern, /href="#contact"/u);
  assert.match(lpPattern, /id="contact"/u);
  assert.match(probe, /a\[href="#contact"\]:visible/u);
  assert.match(probe, /location\.hash === '#contact' && !!document\.querySelector\('#contact'\)/u);
  assert.match(probe, /lp-contact-anchor/u);
  assert.match(runner, /row\.hash === '#contact' && row\.targetVisible === true && row\.submitted === false/u);
  assert.doesNotMatch(probe, /#apply/u);
});

test('the source manifest includes all current theme render inputs, including HOME/LP templates, CSS, and patterns', () => {
  const manifest = collectThemeInputDigests(root, 'docs/research/2026-09-05-design-prototype-03/theme/helix-wt');
  for (const file of [
    'theme.json', 'style.css', 'assets/css/theme.css', 'assets/css/home-completion.css',
    'templates/front-page.html', 'patterns/home-hero.php', 'patterns/home-sections.php',
    'patterns/lp.php', 'templates/page-lp.html', 'inc/site-pages.php', 'functions.php',
  ]) {
    assert.match(manifest[`docs/research/2026-09-05-design-prototype-03/theme/helix-wt/${file}`] ?? '', /^[a-f0-9]{64}$/u,
      `theme input ${file} is hashed`);
  }
  const temporaryRoot = fs.mkdtempSync(path.join(os.tmpdir(), 'helix-theme-inputs-'));
  try {
    const cssPath = path.join(temporaryRoot, 'theme/assets/css/theme.css');
    fs.mkdirSync(path.dirname(cssPath), { recursive: true });
    fs.writeFileSync(cssPath, 'body { color: black; }');
    const before = collectThemeInputDigests(temporaryRoot, 'theme');
    fs.writeFileSync(cssPath, 'body { color: white; }');
    const after = collectThemeInputDigests(temporaryRoot, 'theme');
    assert.notEqual(before['theme/assets/css/theme.css'], after['theme/assets/css/theme.css'],
      'theme source changes alter the manifest digest');
  } finally {
    fs.rmSync(temporaryRoot, { recursive: true, force: true });
  }
  for (const probe of ['listing-loadmore-inp-probe.mjs', 'remaining-surfaces-inp-probe.mjs', 'compare-pattern-inp-probe.mjs']) {
    const source = fs.readFileSync(path.join(root, 'scripts/performance-gate', probe), 'utf8');
    assert.match(source, /reducedMotion: 'no-preference'/u, `${probe} measures with normal motion preference`);
    assert.match(source, /deviceScaleFactor/u, `${probe} sets explicit SP/PC device scale`);
  }
});

test('the performance job uses repository Node pin and keeps the HOME regression measurement separate', () => {
  const job = workflow.slice(workflow.indexOf('  performance-gate:'));
  assert.match(job, /node-version-file: \.node-version/u);
  assert.equal(fs.readFileSync(path.join(root, '.node-version'), 'utf8').trim(), '24.19.0');
  const config = JSON.parse(fs.readFileSync(path.join(root, '.lighthouserc.json'), 'utf8'));
  assert.deepEqual(config.homeRegression.renderBlockingInsight, { maxLength: 0 });
  assert.match(runner, /renderBlockingInsight: \{ maxLength: 0 \}/u);
  assert.match(runner, /renderBlockingInsightMaxLength: config\.homeRegression\.renderBlockingInsight\.maxLength/u);
  assert.match(runner, /coverageId: 'home-regression', device: 'home'/u);
  assert.match(runner, /purpose: 'separate-regression-only; excluded from the eight required page\/device conditions'/u);
});
