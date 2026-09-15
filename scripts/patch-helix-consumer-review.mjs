// 同梱パッケージ専用。全対象の版を確認してから書き込む。
import fs from 'node:fs';
import { createHash } from 'node:crypto';
const hash = value => createHash('sha256').update(value).digest('hex');
const specs = [
  {
    "path": "src/cli.ts",
    "before": "fad552ff1413707e9ec40083e1ff1ec1888b8edc3668576c36a80dd2cd81866a",
    "after": "636ca538a0f698f1c7eba74e94cdf56bf2060760a7986f1d1847df5bab8db984",
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
  }
];
const pending = specs.map(spec => {
  const target = new URL('../node_modules/helix/' + spec.path, import.meta.url);
  const original = fs.readFileSync(target, 'utf8');
  if (hash(original) === spec.after) return null;
  if (hash(original) !== spec.before) throw Error('Unknown HELIX source: ' + spec.path);
  let patched = original;
  for (const [before, after] of spec.replacements) {
    if (patched.split(before).length !== 2) throw Error('Patch anchor mismatch: ' + spec.path);
    patched = patched.replace(before, after);
  }
  if (hash(patched) !== spec.after) throw Error('Patch digest mismatch: ' + spec.path);
  return { target, patched };
});
for (const change of pending) if (change) fs.writeFileSync(change.target, change.patched);
console.log('HELIX consumer review patch: verified');
