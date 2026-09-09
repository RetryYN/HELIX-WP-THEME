import { spawnSync } from 'node:child_process';
import fs from 'node:fs';
import path from 'node:path';
import process from 'node:process';

const root = path.resolve(import.meta.dirname, '..');
const outputPath = path.join(root, 'docs/research/2026-09-09-capability-manifest/runtime.json');

function wp(...args) {
  const result = spawnSync('docker', ['compose', 'run', '--rm', 'wpcli', ...args], {
    cwd: root,
    encoding: 'utf8',
    maxBuffer: 16 * 1024 * 1024,
  });
  if (result.status !== 0) throw new Error(result.stderr || result.stdout || `wpcli exited ${result.status}`);
  return result.stdout.trim();
}

const previousTheme = wp('theme', 'list', '--status=active', '--field=name').split('\n').at(-1);
let restored = false;
try {
  wp('theme', 'activate', 'agent-neo-themes/agent-neo-theme');
  const raw = wp('eval', `
    $before = agent_neo_health();
    $removed = $before['capability_manifest']['patterns'][0];
    unregister_block_pattern( $removed );
    $after = agent_neo_health();
    echo wp_json_encode( array(
      'wordpress_version' => get_bloginfo( 'version' ),
      'php_version' => PHP_VERSION,
      'active_theme' => get_stylesheet(),
      'removed_for_negative_fixture' => $removed,
      'before' => $before,
      'after_unregister' => $after,
    ) );
  `);
  const data = JSON.parse(raw.slice(raw.indexOf('{')));
  const beforeDrift = data.before.capability_drift.patterns;
  const afterDrift = data.after_unregister.capability_drift.patterns;
  const declared = data.before.capability_manifest.patterns;
  const registered = data.before.registered_capabilities.patterns;
  const rows = [
    { name: 'health:loaded', pass: data.before.loaded === true },
    { name: 'health:config-valid', pass: data.before.config_valid === true },
    { name: 'health:boundary-valid', pass: data.before.boundary_valid === true },
    { name: 'health:manifest-present', pass: Boolean(data.before.capability_manifest) },
    { name: 'health:patterns-match-wordpress-registry', pass: JSON.stringify(declared) === JSON.stringify(registered), detail: { declared: declared.length, registered: registered.length } },
    { name: 'health:no-pattern-drift', pass: beforeDrift.missing.length === 0 && beforeDrift.undeclared.length === 0 },
    { name: 'negative:unregistered-pattern-reported', pass: JSON.stringify(afterDrift.missing) === JSON.stringify([data.removed_for_negative_fixture]) },
    { name: 'health:digest-present', pass: /^[a-f0-9]{64}$/.test(data.before.capability_digest) },
    { name: 'health:active-theme-observed', pass: data.active_theme === 'agent-neo-themes/agent-neo-theme' },
  ];
  const result = {
    schema: 'wt-capability-health-runtime.v1',
    completed: true,
    requirements: ['WT-TR-CORE-02'],
    environment: {
      wordpress: data.wordpress_version,
      php: data.php_version,
      active_theme: data.active_theme,
    },
    counts: {
      declared_patterns: declared.length,
      registered_patterns: registered.length,
      declared_hooks: data.before.capability_manifest.hooks.length,
      registered_hooks: data.before.registered_capabilities.hooks.filter(hook => hook.registered).length,
    },
    negative_fixture: {
      removed_pattern: data.removed_for_negative_fixture,
      reported_missing: afterDrift.missing,
    },
    rows,
    failed: rows.filter(row => !row.pass).length,
  };
  fs.mkdirSync(path.dirname(outputPath), { recursive: true });
  fs.writeFileSync(outputPath, `${JSON.stringify(result, null, 2)}\n`);
  console.log(JSON.stringify(result, null, 2));
  if (result.failed) process.exitCode = 1;
} finally {
  wp('theme', 'activate', previousTheme);
  restored = true;
}

if (!restored) process.exitCode = 1;
