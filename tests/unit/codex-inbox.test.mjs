import { test } from 'node:test';
import assert from 'node:assert/strict';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import {
  buildCodexInboxEntry,
  deliverCodexInbox,
  listCodexInboxEntries,
  publishCodexInboxEntry,
  sharedCodexWakeRoot,
  CODEX_INBOX_BOUNDARY,
  CODEX_WAKE_BODY_MAX_CHARS,
} from '../../scripts/lib/codex-inbox.mjs';

function tempRepo() {
  const root = fs.mkdtempSync(path.join(os.tmpdir(), 'codex-inbox-'));
  const git = (...args) => execFileSync('git', args, { cwd: root, encoding: 'utf8' }).trim();
  git('init', '-q', '-b', 'main');
  git('-c', 'user.name=t', '-c', 'user.email=t@example.invalid', 'commit', '-q', '--allow-empty', '-m', 'init');
  return { root, git };
}

const baseEntry = { key: 'review:pr:owner/repo#1', body: 'reviewed', operationId: 'op-1', runtime: 'claude' };

test('entry keys are prefixed and ids are derived from key and operation id', () => {
  const entry = buildCodexInboxEntry(baseEntry);
  assert.equal(entry.key, 'codex-inbox:review:pr:owner/repo#1');
  assert.equal(entry.id, 'harness:codex-inbox:review:pr:owner/repo#1:op:op-1');
  assert.match(entry.bodyDigest, /^sha256:[a-f0-9]{64}$/);
});

test('codex cannot notify itself and invalid input is rejected', () => {
  assert.throws(() => buildCodexInboxEntry({ ...baseEntry, runtime: 'codex' }), /codex_cannot_notify_itself/);
  assert.throws(() => buildCodexInboxEntry({ ...baseEntry, runtime: 'robot' }), /invalid_runtime/);
  assert.throws(() => buildCodexInboxEntry({ ...baseEntry, body: '  ' }), /empty_body/);
  assert.throws(() => buildCodexInboxEntry({ ...baseEntry, body: 'x'.repeat(CODEX_WAKE_BODY_MAX_CHARS + 1) }), /body_too_long/);
  assert.throws(() => buildCodexInboxEntry({ ...baseEntry, key: '../escape' }), /invalid_key/);
  assert.throws(() => buildCodexInboxEntry({ ...baseEntry, operationId: 'a/b' }), /invalid_operation_id/);
});

test('publish is idempotent per operation id and lands in the git common dir', () => {
  const { root } = tempRepo();
  const entry = buildCodexInboxEntry(baseEntry);
  const first = publishCodexInboxEntry(root, entry);
  const second = publishCodexInboxEntry(root, entry);
  assert.equal(first.status, 'queued');
  assert.equal(second.status, 'already_queued');
  assert.equal(first.path, second.path);
  assert.ok(first.path.startsWith(path.join(root, '.git', 'helix-runtime', 'codex-memory-wake')));
  assert.equal(listCodexInboxEntries(root).length, 1);
});

test('entries published from one worktree are visible and deliverable from another', () => {
  const { root, git } = tempRepo();
  const other = path.join(root, '..', path.basename(root) + '-wt');
  git('worktree', 'add', '-q', '--detach', other);
  publishCodexInboxEntry(root, buildCodexInboxEntry(baseEntry));
  assert.equal(sharedCodexWakeRoot(other), sharedCodexWakeRoot(root));
  const written = [];
  const result = deliverCodexInbox(other, { sessionId: 's1', write: (m) => written.push(m) });
  assert.deepEqual(result, { pending: 1, delivered: ['harness:codex-inbox:review:pr:owner/repo#1:op:op-1'] });
  assert.match(written[0], new RegExp(`^\\[${CODEX_INBOX_BOUNDARY}\\]`));
  assert.match(written[0], /"origin_runtime":"claude"/);
  // 2 回目は配送しない（delivered マーカーが common dir にある）
  assert.deepEqual(deliverCodexInbox(root, { sessionId: 's2', write: () => {} }), { pending: 0, delivered: [] });
  assert.equal(listCodexInboxEntries(root)[0].delivered, true);
});

test('delivery marks an entry only after the write succeeded', () => {
  const { root } = tempRepo();
  publishCodexInboxEntry(root, buildCodexInboxEntry(baseEntry));
  assert.throws(() => deliverCodexInbox(root, { sessionId: 's', write: () => { throw new Error('stdout closed'); } }));
  assert.equal(listCodexInboxEntries(root)[0].delivered, false);
});

test('malformed spool files are ignored without deleting them', () => {
  const { root } = tempRepo();
  const dir = path.join(sharedCodexWakeRoot(root), 'inbox');
  fs.mkdirSync(dir, { recursive: true });
  fs.writeFileSync(path.join(dir, 'broken.json'), '{not json');
  fs.writeFileSync(path.join(dir, 'foreign.json'), JSON.stringify({ schemaVersion: 'other' }));
  assert.deepEqual(listCodexInboxEntries(root), []);
  assert.equal(fs.readdirSync(dir).length, 2);
});
