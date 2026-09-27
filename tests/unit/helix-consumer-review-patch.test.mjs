import { test } from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { spawnSync } from 'node:child_process';
const script = fs.readFileSync(new URL('../../scripts/patch-helix-consumer-review.mjs', import.meta.url), 'utf8');
const specs = JSON.parse(script.match(/const specs = (\[[\s\S]*?\]);\nconst pending/)[1]);
function fixture(run, { oldPatchedCli = false } = {}) {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'helix-consumer-patch-'));
  try {
    fs.mkdirSync(path.join(dir, 'scripts'));
    const command = path.join(dir, 'scripts/patch-helix-consumer-review.mjs');
    fs.writeFileSync(command, script);
    const targets = specs.map(spec => {
      let original = fs.readFileSync(new URL('../../node_modules/helix/' + spec.path, import.meta.url), 'utf8');
      const reverse = oldPatchedCli && spec.path === 'src/cli.ts'
        ? spec.replacements.slice(spec.upgradeReplacementStart)
        : spec.replacements;
      for (const [before, after] of [...reverse].reverse()) original = original.replace(after, before);
      const target = path.join(dir, 'node_modules/helix', spec.path);
      fs.mkdirSync(path.dirname(target), { recursive: true });
      fs.writeFileSync(target, original);
      return target;
    });
    run(targets, () => spawnSync(process.execPath, [command], { cwd: os.tmpdir(), encoding: 'utf8' }));
  } finally {
    fs.rmSync(dir, { recursive: true, force: true });
  }
}
test('fresh multi-file patch applies and is idempotent', () => fixture((targets, invoke) => {
  const applied = invoke();
  assert.equal(applied.status, 0, applied.stderr);
  const first = targets.map(p => fs.readFileSync(p, 'utf8'));
  assert.equal(invoke().status, 0);
  assert.deepEqual(targets.map(p => fs.readFileSync(p, 'utf8')), first);
}));
test('known prior CLI patch upgrades to the current digest and remains idempotent', () => fixture((targets, invoke) => {
  const applied = invoke();
  assert.equal(applied.status, 0, applied.stderr);
  const upgraded = targets.map(p => fs.readFileSync(p, 'utf8'));
  assert.equal(invoke().status, 0);
  assert.deepEqual(targets.map(p => fs.readFileSync(p, 'utf8')), upgraded);
}, { oldPatchedCli: true }));
test('unknown final file rejects before changing any earlier file', () => fixture((targets, invoke) => {
  fs.appendFileSync(targets.at(-1), '\n// unknown upstream\n');
  const before = targets.map(p => fs.readFileSync(p, 'utf8'));
  assert.notEqual(invoke().status, 0);
  assert.deepEqual(targets.map(p => fs.readFileSync(p, 'utf8')), before);
}));

test('SessionStart hook uses the local CLI, preserves stdin, and delivers only in isolated git spool', () => {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'helix-wake-hook-'));
  try {
    fs.mkdirSync(path.join(dir, 'node_modules', '.bin'), { recursive: true });
    fs.symlinkSync(new URL('../../node_modules/helix', import.meta.url).pathname, path.join(dir, 'node_modules/helix'), 'dir');
    fs.symlinkSync(new URL('../../node_modules/.bin/tsx', import.meta.url).pathname, path.join(dir, 'node_modules/.bin/tsx'));
    fs.writeFileSync(path.join(dir, 'package.json'), JSON.stringify({ private: true, scripts: { helix: 'tsx node_modules/helix/src/cli.ts' } }));
    assert.equal(spawnSync('git', ['init', '-q'], { cwd: dir }).status, 0);
    const commonDir = spawnSync('git', ['rev-parse', '--path-format=absolute', '--git-common-dir'], { cwd: dir, encoding: 'utf8' }).stdout.trim();
    const spool = path.join(commonDir, 'helix-runtime', 'claude-memory-wake');
    fs.mkdirSync(path.join(spool, 'inbox'), { recursive: true });
    const entry = {
      schemaVersion: 2,
      id: 'harness:claude-inbox:test-session-start:op:integration',
      layer: 'harness',
      key: 'claude-inbox:test-session-start',
      body: 'isolated hook integration fixture',
      type: 'constraint',
      provenance: { planId: null, sessionId: 'test', runtime: 'codex', origin: 'test-fixture' },
      lifecycle: { state: 'active', expiresAt: null, consumedAt: null, consumedBy: null },
      links: [], supersedes: null, createdAt: new Date().toISOString(),
    };
    fs.writeFileSync(path.join(spool, 'inbox', 'fixture.json'), JSON.stringify(entry));
    const result = spawnSync('npm', ['--prefix', dir, 'run', 'helix', '--', 'hook', 'claude-memory-wake', '--event', 'SessionStart'], {
      cwd: dir, encoding: 'utf8', input: JSON.stringify({ hook_event_name: 'SessionStart', session_id: 'session-fixture' }),
      env: { ...process.env, HELIX_CLAUDE_WAKE_POLL_MS: '10', HELIX_CLAUDE_WAKE_MAX_MS: '1000' },
      timeout: 10000,
    });
    assert.equal(result.status, 2, result.stderr);
    assert.match(result.stderr, /\[HELIX_CLAUDE_INBOX\]/u);
    assert.doesNotMatch(result.stdout, /\[HELIX_CLAUDE_INBOX\]/u);
    assert.match(result.stdout, /> helix/u, 'npm must resolve the fixture project script while forwarding stdin');
    const deliveredPath = path.join(spool, `${entry.id.replace(/[^A-Za-z0-9_.-]/gu, '_')}.delivered`);
    const delivered = JSON.parse(fs.readFileSync(deliveredPath, 'utf8'));
    assert.ok(delivered, 'isolated delivery ACK must name the claimed inbox entry');
    assert.equal(delivered.id, entry.id);
    assert.equal(delivered.sessionId, 'session-fixture');
    assert.match(delivered.ackDigest, /^sha256:[0-9a-f]{64}$/u);
    const deliveredBytes = fs.readFileSync(deliveredPath);
    const repeated = spawnSync('npm', ['--prefix', dir, 'run', 'helix', '--', 'hook', 'claude-memory-wake', '--event', 'Stop'], {
      cwd: dir, encoding: 'utf8', input: JSON.stringify({ hook_event_name: 'Stop', session_id: 'session-fixture' }),
      env: { ...process.env, HELIX_CLAUDE_WAKE_POLL_MS: '10', HELIX_CLAUDE_WAKE_MAX_MS: '30' },
      timeout: 10000,
    });
    assert.equal(repeated.status, 0, repeated.stderr);
    assert.doesNotMatch(`${repeated.stdout}\n${repeated.stderr}`, /\[HELIX_CLAUDE_INBOX\]/u);
    assert.deepEqual(fs.readFileSync(deliveredPath), deliveredBytes, 'repeat wait must not rewrite or duplicate the delivery ACK');
  } finally {
    fs.rmSync(dir, { recursive: true, force: true });
  }
});

test('project hooks use the package-local CLI for both supported events', () => {
  const settings = JSON.parse(fs.readFileSync(new URL('../../.claude/settings.json', import.meta.url), 'utf8'));
  for (const [event, group] of [['SessionStart', settings.hooks.SessionStart], ['Stop', settings.hooks.Stop]]) {
    const wake = group.find(item => item.hooks?.some(hook => hook.command?.includes('claude-memory-wake')))?.hooks[0];
    assert.equal(wake.asyncRewake, true);
    assert.equal(wake.command, `npm --prefix "$CLAUDE_PROJECT_DIR" run helix -- hook claude-memory-wake --event ${event}`);
  }
});

test('consumer doctor contract and setup template both retain the dual wake hooks', async () => {
  const { BUILTIN_GITHUB_TEMPLATES } = await import('../../node_modules/helix/src/setup/templates.ts');
  const { consumerClaudeHookSettingsMatchContract } = await import('../../node_modules/helix/src/setup/index.ts');
  const generated = BUILTIN_GITHUB_TEMPLATES['adapter/.claude/settings.json'];
  assert.doesNotThrow(() => JSON.parse(generated));
  assert.equal(consumerClaudeHookSettingsMatchContract(generated), true);
  const settings = fs.readFileSync(new URL('../../.claude/settings.json', import.meta.url), 'utf8');
  assert.equal(consumerClaudeHookSettingsMatchContract(settings), true);
});

test('wake hook rejects missing session IDs and expected-event mismatches without exit 2', () => {
  const cli = new URL('../../node_modules/helix/src/cli.ts', import.meta.url);
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'helix-wake-invalid-'));
  const run = input => spawnSync(process.execPath, [new URL('../../node_modules/.bin/tsx', import.meta.url).pathname, cli.pathname,
    'hook', 'claude-memory-wake', '--event', 'SessionStart'], { encoding: 'utf8', input, cwd: dir, timeout: 10000 });
  try {
    for (const input of [
      JSON.stringify({ hook_event_name: 'SessionStart' }),
      JSON.stringify({ hook_event_name: 'Stop', session_id: 'session-mismatch' }),
    ]) {
      const result = run(input);
      assert.equal(result.status, 1, result.stderr);
      assert.doesNotMatch(result.stderr, /\[HELIX_CLAUDE_INBOX\]/u);
    }
    assert.equal(fs.existsSync(path.join(dir, '.helix')), false);
  } finally {
    fs.rmSync(dir, { recursive: true, force: true });
  }
});

test('a newer waiter for the same session supersedes its older generation', async () => {
  const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'helix-wake-generation-'));
  try {
    const { waitForClaudeMemory } = await import('../../node_modules/helix/src/runtime/claude-memory-wake.ts');
    let signalSleeping;
    let releaseOldSleep;
    const oldSleeping = new Promise(resolve => { signalSleeping = resolve; });
    const oldWaiter = waitForClaudeMemory({
      repoRoot: dir, sessionId: 'same-session', pollIntervalMs: 10, maxWaitMs: 60000,
      sleep: () => new Promise(resolve => { releaseOldSleep = resolve; signalSleeping(); }),
    });
    await oldSleeping;
    const newWaiter = waitForClaudeMemory({
      repoRoot: dir, sessionId: 'same-session', pollIntervalMs: 10, maxWaitMs: 100,
      sleep: ms => new Promise(resolve => setTimeout(resolve, ms)),
    });
    releaseOldSleep();
    assert.equal((await newWaiter).kind, 'timeout');
    assert.equal((await oldWaiter).kind, 'superseded');
  } finally {
    fs.rmSync(dir, { recursive: true, force: true });
  }
});
