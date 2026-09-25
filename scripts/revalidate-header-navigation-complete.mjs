import { spawnSync } from 'node:child_process';

const scripts = [
  'scripts/verify-header-navigation-complete.mjs',
  'scripts/verify-header-navigation-boundaries.mjs',
  'scripts/verify-header-navigation-editor.mjs',
  'scripts/verify-header-navigation-static.mjs',
  'scripts/summarize-header-navigation-complete.mjs',
];

for (const script of scripts) {
  const result = spawnSync(process.execPath, [script], { stdio: 'inherit' });
  if (result.status !== 0) {
    throw new Error(`header navigation revalidation failed: ${script} (exit ${result.status ?? 'signal'})`);
  }
}
