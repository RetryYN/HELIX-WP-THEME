import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { createHash } from 'node:crypto';

const root = path.resolve(import.meta.dirname, '..');
const roots = [
  'docs/research/2026-09-05-design-prototype-03/theme/helix-wt',
  'docs/research/2026-09-08-content-faces/plugin',
];
const outputPath = path.join(root, 'docs/research/2026-09-09-ai-boundary/verify.json');
const extensions = new Set(['.php', '.js', '.mjs', '.cjs', '.ts', '.tsx', '.json']);
const rules = [
  { id: 'sdk-import-js', pattern: /(?:from\s*|require\s*\(\s*)['"](?:openai|@anthropic-ai\/sdk|@xai-sdk\/client)['"]/u },
  { id: 'sdk-import-php', pattern: /(?:use|new)\s+(?:OpenAI|Anthropic|XAI)\\/u },
  { id: 'sdk-package-php', pattern: /"(?:openai-php\/client|anthropic-ai\/sdk|xai-php\/client)"\s*:/u },
  { id: 'model-endpoint', pattern: /api\.(?:openai\.com\/v1\/(?:responses|chat\/completions)|anthropic\.com\/v1\/messages|x\.ai\/v1\/(?:responses|chat\/completions))/u },
  { id: 'variant-decision', pattern: /(?:function\s+|->|::)(?:generate|select|choose|rank)_?(?:content_?)?variant\s*\(/iu },
  { id: 'risk-score-decision', pattern: /(?:function\s+|->|::)(?:calculate|compute|predict|rank)_?risk_?score\s*\(/iu },
  { id: 'statistical-decision', pattern: /(?:function\s+|->|::)(?:select|choose)_?(?:ab_?)?(?:winner|significance)\s*\(/iu },
];

function files(directory) {
  const result = [];
  for (const entry of fs.readdirSync(directory, { withFileTypes: true })) {
    const absolute = path.join(directory, entry.name);
    if (entry.isDirectory()) result.push(...files(absolute));
    else if (extensions.has(path.extname(entry.name))) result.push(absolute);
  }
  return result;
}

function findingsFor(source, file = '<fixture>') {
  return rules.flatMap(rule => {
    const match = source.match(rule.pattern);
    if (!match) return [];
    return [{ rule: rule.id, file, line: source.slice(0, match.index).split('\n').length }];
  });
}

const findings = roots.flatMap(relativeRoot => files(path.join(root, relativeRoot)).flatMap(file =>
  findingsFor(fs.readFileSync(file, 'utf8'), path.relative(root, file).split(path.sep).join('/'))));
const scannedFiles = roots.flatMap(relativeRoot => files(path.join(root, relativeRoot))).sort();
const sourceDigests = Object.fromEntries(scannedFiles.map(file => [
  path.relative(root, file).split(path.sep).join('/'),
  createHash('sha256').update(fs.readFileSync(file)).digest('hex'),
]));
const fixtures = {
  'sdk-import-js': `import OpenAI from 'openai';`,
  'sdk-import-php': `<?php use OpenAI\\Client;`,
  'sdk-package-php': `{"openai-php/client":"^1"}`,
  'model-endpoint': `https://api.anthropic.com/v1/messages`,
  'variant-decision': `function select_variant() {}`,
  'risk-score-decision': `function calculate_risk_score() {}`,
  'statistical-decision': `function choose_ab_winner() {}`,
};
const rows = [
  { name: 'boundary:production-findings-zero', pass: findings.length === 0, detail: { findings } },
  ...Object.entries(fixtures).map(([rule, source]) => ({
    name: `negative:${rule}-fails`,
    pass: findingsFor(source).some(finding => finding.rule === rule),
  })),
];
const manifest = JSON.parse(fs.readFileSync(path.join(root, 'docs/research/2026-09-05-design-prototype-03/theme/helix-wt/config/capability-manifest.json'), 'utf8'));
const boundary = manifest.boundary?.ai_decision_logic;
rows.push({
  name: 'boundary:manifest-denies-local-ai-logic',
  pass: boundary?.owner === 'helix' && boundary?.theme_allowed === false && boundary?.content_fixture_plugin_allowed === false,
  detail: boundary,
});
const boundaryValid = value => value?.owner === 'helix' && value?.theme_allowed === false && value?.content_fixture_plugin_allowed === false;
rows.push({ name: 'negative:boundary-policy-rejects-theme-ai', pass: !boundaryValid({ ...boundary, theme_allowed: true }) });
const result = {
  schema: 'wt-ai-boundary-verification.v1',
  completed: true,
  requirements: ['WT-TR-CORE-03'],
  scannedRoots: roots,
  sourceDigests,
  rules: rules.map(rule => rule.id),
  rows,
  failed: rows.filter(row => !row.pass).length,
};
fs.mkdirSync(path.dirname(outputPath), { recursive: true });
fs.writeFileSync(outputPath, `${JSON.stringify(result, null, 2)}\n`);
console.log(JSON.stringify(result, null, 2));
if (result.failed) process.exitCode = 1;
