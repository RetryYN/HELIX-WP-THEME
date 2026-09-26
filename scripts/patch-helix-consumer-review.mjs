// 同梱パッケージ専用。全対象の版を確認してから書き込む。
import fs from 'node:fs';
import { createHash } from 'node:crypto';
const hash = value => createHash('sha256').update(value).digest('hex');
const specs = [
  {
    "path": "src/cli.ts",
    "before": "fad552ff1413707e9ec40083e1ff1ec1888b8edc3668576c36a80dd2cd81866a",
    "after": "eedbd0e6c900bece5e61282eb8655ad92dc7a670ce79840a11937d2bdce4069d",
    "upgradeFrom": "636ca538a0f698f1c7eba74e94cdf56bf2060760a7986f1d1847df5bab8db984",
    "upgradeReplacementStart": 4,
    "replacements": [
      [
        "import { createL3G3LogicalDbReceipt } from \"./doctor/l3-g3-logical-db-receipt\";",
        "import { createL3G3LogicalDbReceipt } from \"./doctor/l3-g3-logical-db-receipt\";\nimport { createConsumerReviewReceipt, CONSUMER_REVIEW_PROFILE } from \"../../../scripts/lib/create-consumer-review-receipt.ts\";"
      ],
      [
        ".description(\"record a Claude Code current-HEAD convergence review receipt\")",
        ".description(\"record a Claude Code current-HEAD convergence review receipt\")\n  .option(\"--db-profile <profile>\", \"explicit DB profile: core or consumer-review-projection.v1\", \"core\")"
      ],
      [
        ".action((opts: { inputJson: string; apply?: boolean; json?: boolean }) => {\n    try {\n      assertNodeEngineRuntimeAuthority(process.cwd());",
        ".action((opts: { inputJson: string; apply?: boolean; json?: boolean; dbProfile: string }) => {\n    if (opts.dbProfile !== \"core\" && opts.dbProfile !== CONSUMER_REVIEW_PROFILE) throw new Error(\"unsupported_review_db_profile\");\n    try {\n      assertNodeEngineRuntimeAuthority(process.cwd());"
      ],
      [
        "input = bindCanonicalLogicalDbReceipt(input, createL3G3LogicalDbReceipt(process.cwd()));",
        "input = bindCanonicalLogicalDbReceipt(input, opts.dbProfile === CONSUMER_REVIEW_PROFILE\n        ? createConsumerReviewReceipt(process.cwd(), opts.dbProfile, input.headSha)\n        : createL3G3LogicalDbReceipt(process.cwd()));"
      ],
      [
        "function readStrictHookInput(): AgentGuardInput | null {",
        "type ClaudeMemoryWakeHookEvent = \"SessionStart\" | \"Stop\";\n\nfunction readClaudeMemoryWakeHookInput(expectedEvent?: string):\n  | { hook_event_name: ClaudeMemoryWakeHookEvent; session_id: string }\n  | null {\n  const raw = process.stdin.isTTY ? \"\" : readStdin();\n  const normalized = raw.replace(/^\\uFEFF/, \"\").trim();\n  if (!normalized) return null;\n  let parsed: unknown;\n  try {\n    parsed = JSON.parse(normalized) as unknown;\n  } catch {\n    return null;\n  }\n  if (typeof parsed !== \"object\" || parsed === null || Array.isArray(parsed)) return null;\n  const input = parsed as Record<string, unknown>;\n  const event = input.hook_event_name;\n  const sessionId = input.session_id;\n  if (\n    (event !== \"SessionStart\" && event !== \"Stop\") ||\n    (expectedEvent !== undefined && event !== expectedEvent) ||\n    typeof sessionId !== \"string\" ||\n    sessionId.trim() === \"\"\n  ) {\n    return null;\n  }\n  return { hook_event_name: event, session_id: sessionId.trim() };\n}\n\nfunction readStrictHookInput(): AgentGuardInput | null {"
      ],
      [
        ".description(\"wait for an addressed harness-memory event and rewake an idle Claude session\")\n  .action(async () => {\n    const input = readHookInput(\"Stop\");",
        ".description(\"wait for an addressed harness-memory event and rewake an idle Claude session\")\n  .option(\"--event <event>\", \"expected Claude hook event (SessionStart or Stop)\")\n  .action(async (opts: { event?: string }) => {\n    if (opts.event !== undefined && opts.event !== \"SessionStart\" && opts.event !== \"Stop\") {\n      process.stderr.write(\"claude-memory-wake: unsupported hook event\\n\");\n      process.exitCode = 1;\n      return;\n    }\n    const input = readClaudeMemoryWakeHookInput(opts.event);\n    if (!input) {\n      process.stderr.write(\"claude-memory-wake: valid hook event and session_id required\\n\");\n      process.exitCode = 1;\n      return;\n    }"
      ],
      [
        "repoRoot: process.cwd(),\n      sessionId: input.session_id ?? \"claude-session\",\n      pollIntervalMs:",
        "repoRoot: process.cwd(),\n      sessionId: input.session_id,\n      pollIntervalMs:"
      ],
      [
        "repoRoot: process.cwd(),\n        entry: result.entry,\n        sessionId: input.session_id ?? \"claude-session\",\n        ackDigest:",
        "repoRoot: process.cwd(),\n        entry: result.entry,\n        sessionId: input.session_id,\n        ackDigest:"
      ]
    ]
  },
  {
    "path": "src/runtime/claude-pr-convergence.ts",
    "before": "ebd88558eb98dd9cba9ad9a3bfc97192b8dbba3afef7cf05adb40d791cd4a4f4",
    "after": "ecd454bc7bf00de6681f268801406be8c366bbaba908f00ae89f4d4477b8e04a",
    "replacements": [
      [
        "input.dbReceiptSchemaVersion !== \"helix-l3-g3-logical-db-bootstrap-receipt.v2\" ||",
        "(input.dbReceiptSchemaVersion !== \"helix-l3-g3-logical-db-bootstrap-receipt.v2\" &&\n        input.dbReceiptSchemaVersion !== \"helix-consumer-review-db-receipt.v1\") ||"
      ]
    ]
  },
  {
    "path": "src/runtime/github-cross-review-admission.ts",
    "before": "ec0f527d7f9a9a4ef15fe5de011a35002e0a181f261eab3401bc5af09c681d42",
    "after": "e956f3d784d2f5fcb77acbf40be961187fbebbf4fecfb61f6f591ddb8d5cd052",
    "replacements": [
      [
        "export function canonicalLogicalDbReceiptValid(",
        "import { validateConsumerReviewReceipt } from \"../../../../scripts/lib/create-consumer-review-receipt.ts\";\n\nexport function canonicalLogicalDbReceiptValid("
      ],
      [
        "  const keys = Object.keys(db).sort();",
        "  if (db.schema_version === \"helix-consumer-review-db-receipt.v1\") {\n    return db.source_head === candidateHead && validateConsumerReviewReceipt(process.cwd(), db);\n  }\n  const keys = Object.keys(db).sort();"
      ]
    ]
  },
  {
    "path": "src/setup/index.ts",
    "before": "b219a7c2e4e662498048f863db0f1c8048798716c21cac4c8bcb20409d78559d",
    "after": "f3b026f9ebea3faf16b5222a1ccb9466d1b8ee7efe10d18bb37ff839bfb15443",
    "replacements": [
      [
        "  { event: \"Stop\", command: \"helix hook claude-memory-wake\", asyncRewake: true },",
        "  {\n    event: \"SessionStart\",\n    command: 'npm --prefix \"$CLAUDE_PROJECT_DIR\" run helix -- hook claude-memory-wake --event SessionStart',\n    asyncRewake: true,\n    minTimeout: 7230,\n  },\n  {\n    event: \"Stop\",\n    command: 'npm --prefix \"$CLAUDE_PROJECT_DIR\" run helix -- hook claude-memory-wake --event Stop',\n    asyncRewake: true,\n    minTimeout: 7230,\n  },"
      ]
    ]
  },
  {
    "path": "src/setup/templates.ts",
    "before": "2c087a5c2dce565ca2c887aee8c43b5b3fd0021749422fa89cfee284a3acb37b",
    "after": "53dc185c2e917c3f6cabf647f77610f1ecd1433576ec4872e62a1b32364ef7ba",
    "replacements": [
      [
        "    '            \"command\": \"helix session start\",',\n    HELIX_HOOK_TIMEOUT_15_LINE,\n    '            \"statusMessage\": \"session-log: session start (fail-open)\"',\n    \"          }\",\n    \"        ]\",\n    \"      }\",\n    \"    ],\",\n    '    \"PostToolUse\": [',",
        "    '            \"command\": \"helix session start\",',\n    HELIX_HOOK_TIMEOUT_15_LINE,\n    '            \"statusMessage\": \"session-log: session start (fail-open)\"',\n    \"          }\",\n    \"        ]\",\n    \"      },\",\n    \"      {\",\n    '        \"hooks\": [',\n    \"          {\",\n    HELIX_HOOK_COMMAND_TYPE_LINE,\n    '            \"command\": \"npm --prefix \\\\\\\"$CLAUDE_PROJECT_DIR\\\\\\\" run helix -- hook claude-memory-wake --event SessionStart\",',\n    '            \"timeout\": 7230,',\n    '            \"asyncRewake\": true,',\n    '            \"statusMessage\": \"harness-memory: Claude宛て通知を待機\"',\n    \"          }\",\n    \"        ]\",\n    \"      }\",\n    \"    ],\",\n    '    \"PostToolUse\": [',"
      ],
      [
        "    '            \"command\": \"helix hook claude-memory-wake\",',",
        "    '            \"command\": \"npm --prefix \\\\\\\"$CLAUDE_PROJECT_DIR\\\\\\\" run helix -- hook claude-memory-wake --event Stop\",',"
      ]
    ]
  }
];
const pending = specs.map(spec => {
  const target = new URL('../node_modules/helix/' + spec.path, import.meta.url);
  const original = fs.readFileSync(target, 'utf8');
  if (hash(original) === spec.after) return null;
  const upgrade = spec.upgradeFrom === hash(original);
  if (!upgrade && hash(original) !== spec.before) throw Error('Unknown HELIX source: ' + spec.path);
  let patched = original;
  const replacements = upgrade
    ? spec.replacements.slice(spec.upgradeReplacementStart)
    : spec.replacements;
  for (const [before, after] of replacements) {
    if (patched.split(before).length !== 2) throw Error('Patch anchor mismatch: ' + spec.path);
    patched = patched.replace(before, after);
  }
  if (hash(patched) !== spec.after) throw Error('Patch digest mismatch: ' + spec.path);
  return { target, patched };
});
for (const change of pending) if (change) fs.writeFileSync(change.target, change.patched);
console.log('HELIX consumer review patch: verified');
