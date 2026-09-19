#!/usr/bin/env node
// Claude から codex へ wake を送る。`helix memory notify-claude` の対称版（consumer 実装）。
//   node scripts/notify-codex.mjs <key> <body> --operation-id <id> [--runtime claude] [--origin <label>]
//                                 [--session-id <id>] [--plan-id <id>] [--json]
import { parseArgs } from 'node:util';
import { buildCodexInboxEntry, publishCodexInboxEntry } from './lib/codex-inbox.mjs';

const { values, positionals } = parseArgs({
  allowPositionals: true,
  options: {
    'operation-id': { type: 'string' },
    runtime: { type: 'string', default: 'claude' },
    origin: { type: 'string', default: 'helix-codex-notify' },
    'session-id': { type: 'string', default: 'cli-codex-notify' },
    'plan-id': { type: 'string' },
    json: { type: 'boolean', default: false },
  },
});
const [key, body] = positionals;
if (!key || !body || !values['operation-id']) {
  process.stderr.write('usage: notify-codex <key> <body> --operation-id <id> [--runtime claude] [--json]\n');
  process.exit(1);
}
try {
  const entry = buildCodexInboxEntry({
    key, body,
    operationId: values['operation-id'],
    runtime: values.runtime,
    origin: values.origin,
    sessionId: values['session-id'],
    planId: values['plan-id'],
  });
  const result = publishCodexInboxEntry(process.cwd(), entry);
  process.stdout.write(values.json
    ? JSON.stringify({ ok: true, ...result, entry }) + '\n'
    : `notify-codex: ${result.status} id=${entry.id}\n`);
} catch (error) {
  process.stderr.write(`notify-codex rejected: ${error instanceof Error ? error.message : String(error)}\n`);
  process.exit(1);
}
