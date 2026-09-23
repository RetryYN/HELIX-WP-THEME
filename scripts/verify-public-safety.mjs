import crypto from 'node:crypto';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { execFileSync, spawnSync } from 'node:child_process';

const root = path.resolve(import.meta.dirname, '..');
const guardSource = path.join(root, 'scripts/public-safety-guard.sh');
const evidencePath = path.join(root, 'docs/research/2026-09-09-public-safety/verify.json');
const sha256 = file => crypto.createHash('sha256').update(fs.readFileSync(file)).digest('hex');

function runFixture(name, relativePath, content, expectedPass, env = {}, options = {}) {
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
    if (options.symlink) fs.symlinkSync(content, target);
    else fs.writeFileSync(target, content);
    execFileSync('git', ['add', '--', relativePath], { cwd: fixture, env: { ...process.env, GIT_LITERAL_PATHSPECS: '1' } });
    if (options.gitattributes) {
      fs.writeFileSync(path.join(fixture, '.gitattributes'), options.gitattributes);
      execFileSync('git', ['add', '.gitattributes'], { cwd: fixture });
    }
    if (options.binaryApproval) {
      const digest = sha256(target);
      fs.mkdirSync(path.join(fixture, 'config'), { recursive: true });
      fs.writeFileSync(path.join(fixture, 'config/public-safety-binary-approvals.tsv'),
        `${relativePath}\t${options.binaryApproval === 'matching' ? digest : '0'.repeat(64)}\n`);
      execFileSync('git', ['add', 'config/public-safety-binary-approvals.tsv'], { cwd: fixture });
      if (options.unstagedApproval) {
        fs.writeFileSync(path.join(fixture, 'config/public-safety-binary-approvals.tsv'), `${relativePath}\t${digest}\n`);
      }
    }
    if (options.range) execFileSync('git', ['commit', '-qm', 'candidate'], { cwd: fixture });
    const result = spawnSync('bash', ['scripts/check-public-safety.sh', ...(options.range ? ['--base-ref', 'HEAD^', 'HEAD'] : ['--staged'])], {
      cwd: fixture,
      encoding: 'utf8',
      env: { ...process.env, ...env },
    });
    const actualPass = result.status === 0;
    return {
      name,
      pass: actualPass === expectedPass && (!options.expectedFailure || result.stderr.includes(options.expectedFailure)),
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
  runFixture('negative:private-key-in-Japanese-path-rejected', 'src/日本語の証跡.txt', `${privateKey}\n`, false, {}, { expectedFailure: 'private key material' }),
  runFixture('negative:private-key-in-Japanese-path-range-rejected', 'src/日本語の証跡.txt', `${privateKey}\n`, false, {}, { range: true, expectedFailure: 'private key material' }),
  runFixture('negative:pathspec-exclude-magic-rejected', ':(exclude)*', `${privateKey}\n`, false, {}, { expectedFailure: 'private key material' }),
  runFixture('negative:pathspec-short-exclude-magic-rejected', ':!x', `${privateKey}\n`, false, {}, { expectedFailure: 'private key material' }),
  runFixture('negative:pathspec-wildcard-name-rejected', '*', `${privateKey}\n`, false, {}, { expectedFailure: 'private key material' }),
  runFixture('negative:pathspec-bracket-name-rejected', '[ab]', `${privateKey}\n`, false, {}, { expectedFailure: 'private key material' }),
  runFixture('negative:control-character-path-rejected', 'src/line\nbreak.txt', 'ordinary text\n', false),
  runFixture('negative:access-token-rejected', 'src/token.txt', `${token}\n`, false),
  runFixture('negative:credential-assignment-rejected', 'src/config.txt', `client_${'secret'}=abcdefghijklmnop\n`, false),
  runFixture('negative:personal-path-rejected', 'src/path.txt', `${personalPath}\n`, false),
  runFixture('negative:tracking-url-rejected', 'src/url.txt', `${trackingUrl}\n`, false),
  runFixture('negative:research-requires-private-map', 'docs/research/note.md', 'public observation\n', false),
  runFixture('negative:large-research-requires-private-map', 'docs/research/large-note.md', 'public observation\n'.repeat(25000), false),
  runFixture('positive:research-with-private-map-accepted', 'docs/research/note.md', 'public observation\n', true, { PUBLIC_REDACTION_GUARD_RE: 'private-client-name' }),
  runFixture('negative:custom-private-map-rejected', 'docs/research/note.md', 'private-client-name\n', false, { PUBLIC_REDACTION_GUARD_RE: 'private-client-name' }),
  runFixture('negative:invalid-private-map-regex-rejected', 'src/clean.txt', 'ordinary text\n', false, { PUBLIC_REDACTION_GUARD_RE: '[' }),
  runFixture('negative:binary-research-without-map-rejected', 'docs/research/image.bin', Buffer.from([0, 1, 2, 3]), false),
  runFixture('negative:binary-research-with-map-only-rejected', 'docs/research/image.bin', Buffer.from([0, 1, 2, 3]), false, { PUBLIC_REDACTION_GUARD_RE: 'private-client-name' }),
  runFixture('negative:binary-forced-text-by-gitattributes-rejected', 'src/image.bin', Buffer.from([0, 1, 2, 3]), false, {}, { gitattributes: '*.bin diff\n' }),
  runFixture('negative:binary-with-wrong-digest-rejected', 'src/image.bin', Buffer.from([0, 1, 2, 3]), false, {}, { binaryApproval: 'wrong' }),
  runFixture('negative:unstaged-binary-approval-is-ignored', 'src/image.bin', Buffer.from([0, 1, 2, 3]), false, {}, { binaryApproval: 'wrong', unstagedApproval: true }),
  runFixture('positive:binary-with-reviewed-digest-approval-accepted', 'src/image.bin', Buffer.from([0, 1, 2, 3]), true, {}, { binaryApproval: 'matching' }),
  runFixture('positive:symlink-target-inspected-as-text', 'src/compat-link', 'safe-target', true, {}, { symlink: true }),
  runFixture('negative:symlink-personal-target-rejected', 'src/compat-link', personalPath, false, {}, { symlink: true }),
];

const report = {
  schema: 'wt-public-safety-verification.v1',
  requirements: ['WT-NFR-CRED-01'],
  completed: rows.every(row => row.pass),
  source: 'scripts/public-safety-guard.sh',
  source_sha256: sha256(guardSource),
  sourceDigests: { 'scripts/public-safety-guard.sh': sha256(guardSource) },
  rows,
  failed: rows.filter(row => !row.pass).length,
};
fs.mkdirSync(path.dirname(evidencePath), { recursive: true });
fs.writeFileSync(evidencePath, `${JSON.stringify(report, null, 2)}\n`);
console.log(JSON.stringify(report, null, 2));
if (!report.completed) process.exitCode = 1;
