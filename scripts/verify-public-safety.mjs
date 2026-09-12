import crypto from 'node:crypto';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { execFileSync, spawnSync } from 'node:child_process';

const root = path.resolve(import.meta.dirname, '..');
const guardSource = path.join(root, 'scripts/public-safety-guard.sh');
const evidencePath = path.join(root, 'docs/research/2026-09-09-public-safety/verify.json');
const sha256 = file => crypto.createHash('sha256').update(fs.readFileSync(file)).digest('hex');

function runFixture(name, relativePath, content, expectedPass, env = {}) {
  const fixture = fs.mkdtempSync(path.join(os.tmpdir(), 'wt-public-safety-'));
  try {
    fs.mkdirSync(path.join(fixture, 'scripts'), { recursive: true });
    fs.copyFileSync(guardSource, path.join(fixture, 'scripts/check-public-safety.sh'));
    execFileSync('git', ['init', '-q'], { cwd: fixture });
    execFileSync('git', ['config', 'user.email', 'fixture@example.invalid'], { cwd: fixture });
    execFileSync('git', ['config', 'user.name', 'Fixture'], { cwd: fixture });
    fs.writeFileSync(path.join(fixture, 'baseline.txt'), 'baseline\n');
    execFileSync('git', ['add', '.'], { cwd: fixture });
    execFileSync('git', ['commit', '-qm', 'baseline'], { cwd: fixture });
    const target = path.join(fixture, relativePath);
    fs.mkdirSync(path.dirname(target), { recursive: true });
    fs.writeFileSync(target, content);
    execFileSync('git', ['add', relativePath], { cwd: fixture });
    const result = spawnSync('bash', ['scripts/check-public-safety.sh', '--staged'], {
      cwd: fixture,
      encoding: 'utf8',
      env: { ...process.env, ...env },
    });
    const actualPass = result.status === 0;
    return {
      name,
      pass: actualPass === expectedPass,
      expected: expectedPass ? 'accept' : 'reject',
      actual: actualPass ? 'accept' : 'reject',
      exit_code: result.status,
    };
  } finally {
    fs.rmSync(fixture, { recursive: true, force: true });
  }
}

const token = ['gh', 'p_', 'a'.repeat(24)].join('');
const privateKey = ['-----BEGIN ', 'TEST PRIVATE KEY-----'].join('');
const personalPath = ['/', 'home', '/fixture-user/private.txt'].join('');
const trackingUrl = ['https://example.invalid/path?', 'a8', 'mat=value'].join('');
const rows = [
  runFixture('positive:clean-source-accepted', 'src/clean.php', '<?php echo "safe";\n', true),
  runFixture('negative:private-key-rejected', 'src/key.txt', `${privateKey}\n`, false),
  runFixture('negative:access-token-rejected', 'src/token.txt', `${token}\n`, false),
  runFixture('negative:credential-assignment-rejected', 'src/config.txt', `client_${'secret'}=abcdefghijklmnop\n`, false),
  runFixture('negative:personal-path-rejected', 'src/path.txt', `${personalPath}\n`, false),
  runFixture('negative:tracking-url-rejected', 'src/url.txt', `${trackingUrl}\n`, false),
  runFixture('negative:research-requires-private-map', 'docs/research/note.md', 'public observation\n', false),
  runFixture('positive:research-with-private-map-accepted', 'docs/research/note.md', 'public observation\n', true, { PUBLIC_REDACTION_GUARD_RE: 'private-client-name' }),
  runFixture('negative:custom-private-map-rejected', 'docs/research/note.md', 'private-client-name\n', false, { PUBLIC_REDACTION_GUARD_RE: 'private-client-name' }),
];

const report = {
  schema: 'wt-public-safety-verification.v1',
  requirements: ['WT-NFR-CRED-01'],
  completed: rows.every(row => row.pass),
  source: 'scripts/public-safety-guard.sh',
  source_sha256: sha256(guardSource),
  rows,
  failed: rows.filter(row => !row.pass).length,
};
fs.mkdirSync(path.dirname(evidencePath), { recursive: true });
fs.writeFileSync(evidencePath, `${JSON.stringify(report, null, 2)}\n`);
console.log(JSON.stringify(report, null, 2));
if (!report.completed) process.exitCode = 1;
