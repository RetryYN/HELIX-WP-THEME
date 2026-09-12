import {test} from 'node:test';
import assert from 'node:assert/strict';
import {consumerProjectionFailures} from '../../scripts/lib/consumer-receipt-contract.mjs';
const context={expectedHead:'a'.repeat(40),expectedTree:'b'.repeat(40),doctorPassed:true};
function fixture(){const d={source_head:context.expectedHead,source_tree:context.expectedTree,workspace_attestation:{clean:true,status_entry_count:0},projection_input_mode:'tracked-authority-runtime-logs-excluded',schema_revision:1,replay_schema_revision:1,unexpected_unstable_columns:[]};for(const prefix of ['','replay_']){for(const k of ['projection_digest','checkpoint_digest'])d[prefix+k]='sha256:'+'c'.repeat(64);for(const k of ['stale_count','orphan_count','finding_count'])d[prefix+k]=0;for(const k of ['stale_population_valid','orphan_population_valid'])d[prefix+k]=true;d[prefix+'executed_excluded_projection_steps']=[];d[prefix+'checkpoint_row_counts']={artifact_registry:3,descent_obligations:0,plan_registry:0,review_evidence_registry:0};d[prefix+'checkpoint_tables']=Object.keys(d[prefix+'checkpoint_row_counts']);}return d;}
test('consumer allows absent core-only populations while requiring artifacts',()=>assert.deepEqual(consumerProjectionFailures(fixture(),context),[]));
for(const [name,mutate] of Object.entries({dirty:d=>d.workspace_attestation.clean=false,head:d=>d.source_head='d'.repeat(40),replay:d=>d.replay_projection_digest='sha256:'+'e'.repeat(64),stale:d=>d.stale_count=1,orphan:d=>d.orphan_count=1,finding:d=>d.finding_count=1,empty:d=>d.checkpoint_row_counts.artifact_registry=0,missing:d=>delete d.replay_checkpoint_row_counts,unknownTable:d=>d.checkpoint_tables.push('other'),population:d=>d.stale_population_valid=false,runtime:d=>d.executed_excluded_projection_steps.push('runtime')}))test('reject '+name,()=>{const d=fixture();mutate(d);assert.ok(consumerProjectionFailures(d,context).length);});
test('doctor failure rejects otherwise consistent projection',()=>assert.ok(consumerProjectionFailures(fixture(),{...context,doctorPassed:false}).length));
for (const value of [null, undefined, false, 42, 'receipt', []]) {
  test('reject invalid projection '+String(value), () => {
    assert.deepEqual(consumerProjectionFailures(value, context), ['invalid_projection']);
  });
}
for (const revision of [0, -1, 1.5, Number.MAX_SAFE_INTEGER + 1]) {
  test('reject invalid matching schema revisions '+revision, () => {
    const db = fixture();
    db.schema_revision = db.replay_schema_revision = revision;
    assert.ok(consumerProjectionFailures(db, context).includes('schema_mismatch'));
  });
}
