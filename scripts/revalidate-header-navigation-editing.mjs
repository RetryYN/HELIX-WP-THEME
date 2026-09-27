import { spawnSync } from 'node:child_process';

const invoke = script => {
  const result = spawnSync(process.execPath, [script], { stdio: 'inherit' });
  if (result.status !== 0) {
    throw new Error(`header navigation editing revalidation failed: ${script} (exit ${result.status ?? 'signal'})`);
  }
};

// The editing summary includes the established header regression set as its baseline.
invoke('scripts/revalidate-header-navigation-complete.mjs');
invoke('scripts/verify-header-navigation-scope.mjs');
invoke('scripts/verify-header-navigation-recovery.mjs');

let editingError;
try {
  invoke('scripts/verify-header-navigation-editing.mjs');
} catch (error) {
  editingError = error;
} finally {
  // The verifier normally restores in its own finally block. This second call is
  // intentionally idempotent and covers interrupted child-process exits.
  const recovery = spawnSync(process.execPath, ['scripts/recover-header-navigation-editing.mjs'], { stdio: 'inherit' });
  if (recovery.status !== 0) {
    const recoveryError = new Error(`header navigation recovery failed (exit ${recovery.status ?? 'signal'})`);
    editingError = editingError
      ? new AggregateError([editingError, recoveryError], 'editing revalidation and recovery both failed')
      : recoveryError;
  }
}
if (editingError) throw editingError;

invoke('scripts/verify-header-navigation-editor-isolation.mjs');
invoke('scripts/summarize-header-navigation-editing.mjs');
