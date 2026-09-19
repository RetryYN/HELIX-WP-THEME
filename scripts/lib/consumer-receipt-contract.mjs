// Local-tool policy candidate. Call only with a freshly rebuilt canonical result.
// This is not a replacement for runtime identity, CI, or receipt digest validation.
export function consumerProjectionFailures(db, { expectedHead, expectedTree, doctorPassed }) {
  if (db === null || typeof db !== 'object' || Array.isArray(db)) return ['invalid_projection'];
  const failures=[];
  if(doctorPassed!==true)failures.push('consumer_doctor_failed');
  if(!/^[a-f0-9]{40}$/.test(expectedHead??'')||db.source_head!==expectedHead)failures.push('head_mismatch');
  if(!/^[a-f0-9]{40}$/.test(expectedTree??'')||db.source_tree!==expectedTree)failures.push('tree_mismatch');
  if(db.workspace_attestation?.clean!==true||db.workspace_attestation?.status_entry_count!==0)failures.push('dirty_workspace');
  if(db.projection_input_mode!=='tracked-authority-runtime-logs-excluded')failures.push('projection_input_mode');
  for(const key of ['projection_digest','checkpoint_digest']) {
    if(!/^sha256:[a-f0-9]{64}$/.test(db[key]??'')||db[key]!==db['replay_'+key])failures.push(key+'_mismatch');
  }
  if(!Number.isSafeInteger(db.schema_revision)||db.schema_revision<1||db.schema_revision!==db.replay_schema_revision)failures.push('schema_mismatch');
  for(const key of ['stale_count','orphan_count','finding_count']) {
    if(db[key]!==0||db['replay_'+key]!==0)failures.push(key);
  }
  for(const key of ['stale_population_valid','orphan_population_valid']) {
    if(db[key]!==true||db['replay_'+key]!==true)failures.push(key);
  }
  for(const key of ['executed_excluded_projection_steps','replay_executed_excluded_projection_steps','unexpected_unstable_columns']) {
    if(!Array.isArray(db[key])||db[key].length!==0)failures.push(key);
  }
  const tables=['artifact_registry','descent_obligations','plan_registry','review_evidence_registry'];
  for(const key of ['checkpoint_row_counts','replay_checkpoint_row_counts']) {
    const counts=db[key];
    if(!counts||Object.keys(counts).sort().join()!==tables.slice().sort().join()||tables.some(t=>!Number.isSafeInteger(counts[t])||counts[t]<0)||counts.artifact_registry<1)failures.push(key);
  }
  for(const t of tables)if(db.checkpoint_row_counts?.[t]!==db.replay_checkpoint_row_counts?.[t])failures.push('row_count_mismatch:'+t);
  if(!Array.isArray(db.checkpoint_tables)||!Array.isArray(db.replay_checkpoint_tables)||db.checkpoint_tables.join()!==db.replay_checkpoint_tables.join()||db.checkpoint_tables.slice().sort().join()!==tables.slice().sort().join())failures.push('checkpoint_tables');
  return failures;
}
