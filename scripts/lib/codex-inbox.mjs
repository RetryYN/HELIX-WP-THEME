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
const safeName = (id) => id.replace(/[^A-Za-z0-9._-]/gu, '_');

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
  return path.join(sharedCodexWakeRoot(repoRoot), `${safeName(entry.id)}.${suffix}`);
}

// 同じ operationId は 1 回だけ配送する（idempotent）。既存があれば書かずにそのパスを返す。
export function publishCodexInboxEntry(repoRoot, entry) {
  const dir = inboxDir(repoRoot);
  fs.mkdirSync(dir, { recursive: true, mode: 0o700 });
  const target = path.join(dir, `${safeName(entry.id)}.json`);
  if (fs.existsSync(target)) return { path: target, status: 'already_queued' };
  const tmp = `${target}.${process.pid}.tmp`;
  fs.writeFileSync(tmp, JSON.stringify(entry, null, 2) + '\n', { mode: 0o600 });
  fs.renameSync(tmp, target);
  return { path: target, status: 'queued' };
}

export function listCodexInboxEntries(repoRoot) {
  const dir = inboxDir(repoRoot);
  if (!fs.existsSync(dir)) return [];
  return fs.readdirSync(dir)
    .filter((name) => name.endsWith('.json'))
    .sort()
    .map((name) => {
      try {
        const entry = JSON.parse(fs.readFileSync(path.join(dir, name), 'utf8'));
        if (entry?.schemaVersion !== CODEX_INBOX_SCHEMA || typeof entry.id !== 'string') return null;
        return { ...entry, delivered: fs.existsSync(markerPath(repoRoot, entry, 'delivered')) };
      } catch {
        return null; // 壊れた entry は配送しない（fail-close）。削除もしない。
      }
    })
    .filter(Boolean);
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
  const pending = listCodexInboxEntries(repoRoot).filter((entry) => !entry.delivered);
  const delivered = [];
  for (const entry of pending) {
    const message = formatCodexInboxMessage(entry);
    write(message + '\n');
    fs.writeFileSync(markerPath(repoRoot, entry, 'delivered'), JSON.stringify({
      id: entry.id,
      receiverSession: sessionId,
      ackDigest: sha256(message),
      deliveredAt: new Date().toISOString(),
    }, null, 2) + '\n', { mode: 0o600 });
    delivered.push(entry.id);
  }
  return { pending: pending.length, delivered };
}
