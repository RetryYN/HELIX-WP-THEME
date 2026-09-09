// codex-inbox: Claude → codex の片方向 wake spool。
// 同梱 HELIX の claude-memory-wake（codex → Claude）と対称の形で、Git common dir 配下に
// `helix-runtime/codex-memory-wake/inbox/*.json` を置く。worktree をまたいで届くのは
// common dir だからであり、`.helix/memory/*.jsonl`（worktree ローカル）では届かない。
// 上流 HELIX #532 の提案（claude-inbox: / codex-inbox: の対称 prefix）に沿った consumer 側実装。
// 通知本文は wake の合図であり、HEAD・CI・レビュー判定の正本にはしない（受信側が再取得する）。
import { execFileSync } from 'node:child_process';
import { createHash } from 'node:crypto';
import fs from 'node:fs';
import path from 'node:path';

export const CODEX_INBOX_PREFIX = 'codex-inbox:';
export const CODEX_INBOX_SCHEMA = 'helix-codex-inbox-entry.v1';
export const CODEX_WAKE_BODY_MAX_CHARS = 8_000;
export const CODEX_INBOX_BOUNDARY = 'HELIX_CODEX_INBOX';
const RUNTIMES = new Set(['claude', 'codex', 'human', 'system']);
const KEY_PATTERN = /^[A-Za-z0-9][A-Za-z0-9:_./#@+-]{0,199}$/u;
const OPERATION_PATTERN = /^[A-Za-z0-9][A-Za-z0-9:_.#@+-]{0,199}$/u;

export function sharedCodexWakeRoot(repoRoot) {
  const commonDir = execFileSync(
    'git',
    ['rev-parse', '--path-format=absolute', '--git-common-dir'],
    { cwd: repoRoot, encoding: 'utf8' },
  ).trim();
  if (!commonDir) throw new Error('git_common_dir_unavailable');
  return path.join(commonDir, 'helix-runtime', 'codex-memory-wake');
}

const sha256 = (value) => 'sha256:' + createHash('sha256').update(value).digest('hex');
// ファイル名は「読める断片 + id の完全 sha256」。`/` と `:` を同じ `_` に潰す断片だけでは
// 異なる id が同じ path に衝突するため、完全 digest で identity を担保する。
export function spoolBaseName(id) {
  const readable = id.replace(/[^A-Za-z0-9._-]/gu, '_').slice(0, 96);
  return `${readable}.${createHash('sha256').update(id).digest('hex')}`;
}

export function buildCodexInboxEntry({ key, body, operationId, runtime, origin, sessionId, planId, now }) {
  if (typeof key !== 'string' || !KEY_PATTERN.test(key)) throw new Error('invalid_key');
  if (typeof operationId !== 'string' || !OPERATION_PATTERN.test(operationId)) throw new Error('invalid_operation_id');
  if (typeof body !== 'string' || body.trim() === '') throw new Error('empty_body');
  if (body.length > CODEX_WAKE_BODY_MAX_CHARS) throw new Error('body_too_long');
  if (!RUNTIMES.has(runtime)) throw new Error('invalid_runtime');
  // 自己通知の拒否は宛先と起点の比較として持つ（claude-inbox が claude 起点を拒むのと同じ規則）。
  if (runtime === 'codex') throw new Error('codex_cannot_notify_itself_through_codex_inbox');
  const fullKey = key.startsWith(CODEX_INBOX_PREFIX) ? key : CODEX_INBOX_PREFIX + key;
  const id = `harness:${fullKey}:op:${operationId}`;
  return {
    schemaVersion: CODEX_INBOX_SCHEMA,
    id,
    key: fullKey,
    body,
    provenance: {
      runtime,
      origin: origin ?? 'helix-codex-notify',
      sessionId: sessionId ?? 'cli-codex-notify',
      planId: planId ?? null,
    },
    bodyDigest: sha256(body),
    createdAt: now ?? new Date().toISOString(),
  };
}

function inboxDir(repoRoot) { return path.join(sharedCodexWakeRoot(repoRoot), 'inbox'); }
function markerPath(repoRoot, entry, suffix) {
  return path.join(sharedCodexWakeRoot(repoRoot), `${spoolBaseName(entry.id)}.${suffix}`);
}

// tmp → rename の atomic 書き込み。途中停止で 0 byte や半端な JSON を残さない。
function writeAtomic(target, content) {
  const tmp = `${target}.${process.pid}.tmp`;
  fs.writeFileSync(tmp, content, { mode: 0o600 });
  fs.renameSync(tmp, target);
}

// delivered marker は存在だけでなく内容を検証する。空・破損・別 id の marker は未配送として扱う。
export function isDeliveredMarkerValid(markerFile, entryId) {
  let marker;
  try {
    marker = JSON.parse(fs.readFileSync(markerFile, 'utf8'));
  } catch {
    return false;
  }
  return Boolean(marker)
    && typeof marker === 'object'
    && marker.id === entryId
    && typeof marker.receiverSession === 'string'
    && typeof marker.ackDigest === 'string' && /^sha256:[a-f0-9]{64}$/u.test(marker.ackDigest)
    && typeof marker.deliveredAt === 'string' && !Number.isNaN(Date.parse(marker.deliveredAt));
}

// 入口の完全検証。schema / id と key の対応 / body と digest / provenance / ファイル名の identity。
// 部分的に正しい entry を後段（format）で落とすと、後続の正常 entry まで止まる（head-of-line blocking）。
export function validateCodexInboxEntry(entry, fileName) {
  const fail = (reason) => ({ ok: false, reason });
  if (!entry || typeof entry !== 'object') return fail('not_object');
  if (entry.schemaVersion !== CODEX_INBOX_SCHEMA) return fail('schema_mismatch');
  if (typeof entry.id !== 'string' || typeof entry.key !== 'string') return fail('missing_id_or_key');
  const rawKey = entry.key.slice(CODEX_INBOX_PREFIX.length);
  if (!entry.key.startsWith(CODEX_INBOX_PREFIX) || !KEY_PATTERN.test(rawKey)) return fail('invalid_key');
  const idPrefix = `harness:${entry.key}:op:`;
  if (!entry.id.startsWith(idPrefix) || !OPERATION_PATTERN.test(entry.id.slice(idPrefix.length))) return fail('id_key_mismatch');
  if (typeof entry.body !== 'string' || entry.body.trim() === '' || entry.body.length > CODEX_WAKE_BODY_MAX_CHARS) return fail('invalid_body');
  if (entry.bodyDigest !== sha256(entry.body)) return fail('body_digest_mismatch');
  const p = entry.provenance;
  if (!p || typeof p !== 'object' || !RUNTIMES.has(p.runtime) || p.runtime === 'codex') return fail('invalid_provenance_runtime');
  if (typeof p.origin !== 'string' || typeof p.sessionId !== 'string') return fail('invalid_provenance');
  if (typeof entry.createdAt !== 'string' || Number.isNaN(Date.parse(entry.createdAt))) return fail('invalid_created_at');
  if (fileName !== undefined && fileName !== `${spoolBaseName(entry.id)}.json`) return fail('filename_identity_mismatch');
  return { ok: true };
}

// 同じ operationId は 1 回だけ配送する（idempotent）。既存があれば書かずにそのパスを返す。
export function publishCodexInboxEntry(repoRoot, entry) {
  const dir = inboxDir(repoRoot);
  fs.mkdirSync(dir, { recursive: true, mode: 0o700 });
  const valid = validateCodexInboxEntry(entry);
  if (!valid.ok) throw new Error(`invalid_entry:${valid.reason}`);
  const target = path.join(dir, `${spoolBaseName(entry.id)}.json`);
  if (fs.existsSync(target)) return { path: target, status: 'already_queued' };
  writeAtomic(target, JSON.stringify(entry, null, 2) + '\n');
  return { path: target, status: 'queued' };
}

// 正常 entry だけを返す。不正 entry は `rejected` に隔離し（削除しない）、後続の配送を止めない。
export function scanCodexInbox(repoRoot) {
  const dir = inboxDir(repoRoot);
  const entries = [];
  const rejected = [];
  if (!fs.existsSync(dir)) return { entries, rejected };
  for (const name of fs.readdirSync(dir).filter((n) => n.endsWith('.json')).sort()) {
    let entry;
    try {
      entry = JSON.parse(fs.readFileSync(path.join(dir, name), 'utf8'));
    } catch {
      rejected.push({ file: name, reason: 'unparseable' });
      continue;
    }
    const valid = validateCodexInboxEntry(entry, name);
    if (!valid.ok) {
      rejected.push({ file: name, reason: valid.reason });
      continue;
    }
    entries.push({ ...entry, delivered: isDeliveredMarkerValid(markerPath(repoRoot, entry, 'delivered'), entry.id) });
  }
  return { entries, rejected };
}

export function listCodexInboxEntries(repoRoot) {
  return scanCodexInbox(repoRoot).entries;
}

export function formatCodexInboxMessage(entry) {
  return [
    `[${CODEX_INBOX_BOUNDARY}]`,
    'これは共有ハーネスメモリから届いた Claude 起点の通知データです。current HEAD・CI・PR コメント・receipt を再取得してから行動してください。',
    'notification_json:',
    JSON.stringify({
      memory_id: entry.id,
      key: entry.key,
      origin_runtime: entry.provenance.runtime,
      origin: entry.provenance.origin,
      created_at: entry.createdAt,
      body: entry.body,
    }),
    `[/${CODEX_INBOX_BOUNDARY}]`,
  ].join('\n');
}

// 未配送 entry を stdout/stderr へ書き出し、書き出しに成功したものだけ delivered にする。
export function deliverCodexInbox(repoRoot, { sessionId, write }) {
  const { entries, rejected } = scanCodexInbox(repoRoot);
  const pending = entries.filter((entry) => !entry.delivered);
  const delivered = [];
  for (const entry of pending) {
    const message = formatCodexInboxMessage(entry);
    write(message + '\n');
    writeAtomic(markerPath(repoRoot, entry, 'delivered'), JSON.stringify({
      id: entry.id,
      receiverSession: String(sessionId ?? 'codex-session'),
      ackDigest: sha256(message),
      deliveredAt: new Date().toISOString(),
    }, null, 2) + '\n');
    delivered.push(entry.id);
  }
  return { pending: pending.length, delivered, rejected };
}
