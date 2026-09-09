import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import { spawnSync } from 'node:child_process';

const root = path.resolve(import.meta.dirname, '..');
const roots = ['themes/agent-neo-theme', 'plugins/agent-neo-core'];
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
const manifest = JSON.parse(fs.readFileSync(path.join(root, 'themes/agent-neo-theme/config/theme-manifest.json'), 'utf8'));
const boundary = manifest.boundary?.ai_decision_logic;
rows.push({
  name: 'boundary:manifest-denies-local-ai-logic',
  pass: boundary?.owner === 'helix' && boundary?.theme_allowed === false && boundary?.core_plugin_allowed === false,
  detail: boundary,
});
const guardPath = path.join(root, 'themes/agent-neo-theme/inc/setup/class-boundary-guard.php');
const guardProbe = spawnSync('php', ['-r', `
  define( 'ABSPATH', '${root.replaceAll("'", "\\'")}/' );
  require '${guardPath.replaceAll("'", "\\'")}';
  $manifest = json_decode( file_get_contents( '${path.join(root, 'themes/agent-neo-theme/config/theme-manifest.json').replaceAll("'", "\\'")}' ), true );
  $positive = new Agent_Neo_Boundary_Guard();
  $positive->validate( $manifest );
  $manifest['boundary']['ai_decision_logic']['theme_allowed'] = true;
  $negative = new Agent_Neo_Boundary_Guard();
  $negative->validate( $manifest );
  echo json_encode( array( 'positive' => $positive->get_errors(), 'negative' => $negative->get_errors() ) );
`], { encoding: 'utf8' });
const guard = guardProbe.status === 0 ? JSON.parse(guardProbe.stdout) : { positive: ['probe_failed'], negative: [] };
rows.push({ name: 'boundary:guard-accepts-current-manifest', pass: guard.positive.length === 0, detail: guard.positive });
rows.push({ name: 'negative:boundary-guard-rejects-theme-ai', pass: guard.negative.some(error => error.includes('theme_allowed must be false')), detail: guard.negative });
const result = {
  schema: 'wt-ai-boundary-verification.v1',
  completed: true,
  requirements: ['WT-TR-CORE-03'],
  scannedRoots: roots,
  rules: rules.map(rule => rule.id),
  rows,
  failed: rows.filter(row => !row.pass).length,
};
fs.mkdirSync(path.dirname(outputPath), { recursive: true });
fs.writeFileSync(outputPath, `${JSON.stringify(result, null, 2)}\n`);
console.log(JSON.stringify(result, null, 2));
if (result.failed) process.exitCode = 1;
