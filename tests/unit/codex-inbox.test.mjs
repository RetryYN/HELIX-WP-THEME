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
  scanCodexInbox,
  sharedCodexWakeRoot,
  spoolBaseName,
  validateCodexInboxEntry,
  isDeliveredMarkerValid,
  CODEX_INBOX_BOUNDARY,
  CODEX_INBOX_SCHEMA,
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
  assert.deepEqual(result, { pending: 1, delivered: ['harness:codex-inbox:review:pr:owner/repo#1:op:op-1'], rejected: [] });
  assert.match(written[0], new RegExp(`^\\[${CODEX_INBOX_BOUNDARY}\\]`));
  assert.match(written[0], /"origin_runtime":"claude"/);
  // 2 回目は配送しない（delivered マーカーが common dir にある）
  assert.deepEqual(deliverCodexInbox(root, { sessionId: 's2', write: () => {} }), { pending: 0, delivered: [], rejected: [] });
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
  assert.deepEqual(scanCodexInbox(root).rejected.map((r) => r.reason), ['unparseable', 'schema_mismatch']);
  assert.equal(fs.readdirSync(dir).length, 2);
});

test('ids that collapse to the same readable name get distinct spool paths', () => {
  const { root } = tempRepo();
  const a = publishCodexInboxEntry(root, buildCodexInboxEntry({ ...baseEntry, key: 'review:a/b' }));
  const b = publishCodexInboxEntry(root, buildCodexInboxEntry({ ...baseEntry, key: 'review:a:b' }));
  assert.equal(a.status, 'queued');
  assert.equal(b.status, 'queued');
  assert.notEqual(a.path, b.path);
  assert.equal(listCodexInboxEntries(root).length, 2);
  assert.match(spoolBaseName('harness:x:op:1'), /^harness_x_op_1\.[a-f0-9]{64}$/);
});

test('a partially valid entry is quarantined and does not block later entries', () => {
  const { root } = tempRepo();
  const dir = path.join(sharedCodexWakeRoot(root), 'inbox');
  fs.mkdirSync(dir, { recursive: true });
  // 先頭（sort 順で最初）に schema と id だけ持つ entry を置く
  fs.writeFileSync(path.join(dir, '0-partial.json'), JSON.stringify({ schemaVersion: CODEX_INBOX_SCHEMA, id: 'valid-id' }));
  // 本文改竄（digest 不一致）と、ファイル名が id と一致しない entry
  const tampered = { ...buildCodexInboxEntry({ ...baseEntry, operationId: 'op-t' }), body: 'changed' };
  fs.writeFileSync(path.join(dir, `${spoolBaseName(tampered.id)}.json`), JSON.stringify(tampered));
  const moved = buildCodexInboxEntry({ ...baseEntry, operationId: 'op-m' });
  fs.writeFileSync(path.join(dir, 'renamed.json'), JSON.stringify(moved));
  // 正常 entry
  publishCodexInboxEntry(root, buildCodexInboxEntry(baseEntry));

  const written = [];
  const result = deliverCodexInbox(root, { sessionId: 's', write: (m) => written.push(m) });
  assert.deepEqual(result.delivered, ['harness:codex-inbox:review:pr:owner/repo#1:op:op-1']);
  assert.equal(written.length, 1);
  assert.deepEqual(result.rejected.map((r) => r.reason).sort(),
    ['body_digest_mismatch', 'filename_identity_mismatch', 'missing_id_or_key'].sort());
  assert.equal(fs.readdirSync(dir).length, 4, 'rejected entries are left in the spool');
  assert.equal(validateCodexInboxEntry({ ...buildCodexInboxEntry(baseEntry), provenance: { runtime: 'codex', origin: 'x', sessionId: 'y' } }).ok, false);
});

test('deliver CLI reads session_id from hook stdin JSON (ESM)', () => {
  const { root } = tempRepo();
  publishCodexInboxEntry(root, buildCodexInboxEntry(baseEntry));
  const cli = path.resolve('scripts/codex-inbox.mjs');
  const run = (input) => {
    try {
      return execFileSync(process.execPath, [cli, 'deliver', '--json', '--exit-code', '0'], { cwd: root, input, encoding: 'utf8' });
    } catch (e) { throw new Error(`cli failed: ${e.stderr}`); }
  };
  const out = JSON.parse(run(JSON.stringify({ session_id: 'session-from-hook' })));
  assert.equal(out.delivered.length, 1);
  const marker = fs.readdirSync(sharedCodexWakeRoot(root)).find((n) => n.endsWith('.delivered'));
  const record = JSON.parse(fs.readFileSync(path.join(sharedCodexWakeRoot(root), marker), 'utf8'));
  assert.equal(record.receiverSession, 'session-from-hook');
  // 2 回目: 配送なし、exit 0
  assert.deepEqual(JSON.parse(run('')).delivered, []);
});

test('empty, corrupt or foreign delivered markers are treated as not delivered', () => {
  const { root } = tempRepo();
  const entry = buildCodexInboxEntry(baseEntry);
  publishCodexInboxEntry(root, entry);
  const marker = path.join(sharedCodexWakeRoot(root), `${spoolBaseName(entry.id)}.delivered`);
  for (const content of [
    '',
    '{not json',
    JSON.stringify({ id: 'harness:codex-inbox:other:op:x', receiverSession: 's', ackDigest: 'sha256:' + 'a'.repeat(64), deliveredAt: new Date().toISOString() }),
    JSON.stringify({ id: entry.id }),
  ]) {
    fs.writeFileSync(marker, content);
    assert.equal(listCodexInboxEntries(root)[0].delivered, false, `marker ${JSON.stringify(content).slice(0, 30)}`);
    assert.equal(isDeliveredMarkerValid(marker, entry.id), false);
  }
  // 破損 marker があっても再配送され、atomic に完全な marker へ置き換わる
  const written = [];
  const result = deliverCodexInbox(root, { sessionId: 's-retry', write: (m) => written.push(m) });
  assert.deepEqual(result.delivered, [entry.id]);
  assert.equal(isDeliveredMarkerValid(marker, entry.id), true);
  assert.equal(JSON.parse(fs.readFileSync(marker, 'utf8')).receiverSession, 's-retry');
  assert.equal(listCodexInboxEntries(root)[0].delivered, true);
  assert.equal(fs.readdirSync(sharedCodexWakeRoot(root)).filter((n) => n.endsWith('.tmp')).length, 0);
});
