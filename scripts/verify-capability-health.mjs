import { execFileSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';
import os from 'node:os';

const root = path.resolve(import.meta.dirname, '..');
const state = process.env.WTCF_STATE_DIR || path.join(os.tmpdir(), 'helix-content-lab');
const outputPath = path.join(root, 'docs/research/2026-09-09-capability-manifest/runtime.json');
const wp = args => execFileSync('docker', ['run', '--rm', '--network', 'helix-content-lab', '--env-file', path.join(state, 'wp.env'), '--volumes-from', 'helix-content-wp', '--user', '33:33', 'wordpress:cli-php8.3', 'wp', ...args], { encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'], maxBuffer: 16 * 1024 * 1024 }).trim();

const raw = wp(['eval', `
$before = helix_wt_capability_health();
$removed = $before['capability_manifest']['patterns'][0];
unregister_block_pattern( $removed );
$after = helix_wt_capability_health();
echo wp_json_encode( array(
  'wordpress_version' => get_bloginfo( 'version' ),
  'php_version' => PHP_VERSION,
  'active_theme' => get_stylesheet(),
  'removed_for_negative_fixture' => $removed,
  'before' => $before,
  'after_unregister' => $after,
) );
`]);
const data = JSON.parse(raw.slice(raw.indexOf('{')));
const declared = data.before.capability_manifest;
const registered = data.before.registered_capabilities;
const noDrift = Object.values(data.before.capability_drift).every(kind => kind.missing.length === 0 && kind.undeclared.length === 0);
const rows = [
  { name: 'health:loaded', pass: data.before.loaded === true },
  { name: 'health:active-current-theme', pass: data.active_theme === 'helix-wt', detail: { active_theme: data.active_theme } },
  { name: 'health:declared-patterns-registered', pass: declared.patterns.every(pattern => registered.patterns.includes(pattern)), detail: { extensions: data.before.extension_patterns } },
  { name: 'health:declared-custom-blocks-registered', pass: declared.custom_blocks.every(block => registered.custom_blocks.includes(block)), detail: { extensions: data.before.extension_blocks } },
  { name: 'health:block-styles-match-wordpress-registry', pass: JSON.stringify(declared.block_styles) === JSON.stringify(registered.block_styles) },
  { name: 'health:no-runtime-drift', pass: noDrift, detail: data.before.capability_drift },
  { name: 'negative:unregistered-pattern-reported', pass: JSON.stringify(data.after_unregister.capability_drift.patterns.missing) === JSON.stringify([data.removed_for_negative_fixture]) },
  { name: 'health:digest-present', pass: /^[a-f0-9]{64}$/.test(data.before.capability_digest) },
];
const result = {
  schema: 'wt-capability-health-runtime.v2',
  completed: true,
  requirements: ['WT-TR-CORE-02'],
  environment: { wordpress: data.wordpress_version, php: data.php_version, active_theme: data.active_theme },
  counts: { declared_patterns: declared.patterns.length, registered_patterns: registered.patterns.length, declared_custom_blocks: declared.custom_blocks.length, registered_custom_blocks: registered.custom_blocks.length, declared_block_styles: declared.block_styles.length, registered_block_styles: registered.block_styles.length },
  negative_fixture: { removed_pattern: data.removed_for_negative_fixture, reported_missing: data.after_unregister.capability_drift.patterns.missing },
  rows,
  failed: rows.filter(row => !row.pass).length,
};
fs.mkdirSync(path.dirname(outputPath), { recursive: true });
fs.writeFileSync(outputPath, `${JSON.stringify(result, null, 2)}\n`);
console.log(JSON.stringify(result, null, 2));
if (result.failed) process.exitCode = 1;
