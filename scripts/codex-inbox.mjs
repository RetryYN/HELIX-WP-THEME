#!/usr/bin/env node
// codex 側の受け口。`.codex/hooks.json` の SessionStart / Stop から呼ぶ。
//   node scripts/codex-inbox.mjs deliver [--exit-code <n>]   未配送を書き出し delivered にする
//   node scripts/codex-inbox.mjs list [--json]              状態表示（配送しない）
// deliver は Claude の claude-memory-wake と同じく、配送があれば stderr へ本文を出して
// --exit-code（既定 2）で終了する。何も無ければ無音で 0。stdin の hook JSON から session_id を拾う。
import { parseArgs } from 'node:util';
import { deliverCodexInbox, listCodexInboxEntries } from './lib/codex-inbox.mjs';

const { values, positionals } = parseArgs({
  allowPositionals: true,
  options: {
    json: { type: 'boolean', default: false },
    'exit-code': { type: 'string', default: '2' },
    'session-id': { type: 'string' },
  },
});
const command = positionals[0] ?? 'deliver';

function readSessionId() {
  if (values['session-id']) return values['session-id'];
  if (process.stdin.isTTY) return 'codex-session';
  try {
    const raw = require('node:fs').readFileSync(0, 'utf8').replace(/^﻿/, '').trim();
    return raw ? (JSON.parse(raw).session_id ?? 'codex-session') : 'codex-session';
  } catch {
    return 'codex-session';
  }
}

try {
  if (command === 'list') {
    const entries = listCodexInboxEntries(process.cwd());
    process.stdout.write(values.json
      ? JSON.stringify({ ok: true, entries }) + '\n'
      : entries.map((e) => `${e.delivered ? 'delivered' : 'pending  '} ${e.id}\n`).join('') || 'codex-inbox: empty\n');
  } else if (command === 'deliver') {
    const result = deliverCodexInbox(process.cwd(), {
      sessionId: readSessionId(),
      write: (message) => process.stderr.write(message),
    });
    if (values.json) process.stdout.write(JSON.stringify({ ok: true, ...result }) + '\n');
    if (result.delivered.length > 0) process.exitCode = Number(values['exit-code']);
  } else {
    process.stderr.write(`codex-inbox: unknown command ${command}\n`);
    process.exit(1);
  }
} catch (error) {
  // hook から呼ばれるため fail-open。通知が読めなくても codex の作業を止めない。
  process.stderr.write(`codex-inbox: ${error instanceof Error ? error.message : String(error)}\n`);
}
