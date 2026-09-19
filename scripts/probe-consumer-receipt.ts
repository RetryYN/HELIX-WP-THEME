// 診断専用。承認receiptの生成・投稿・ACKは行わない。
import { execFileSync } from 'node:child_process';
import { createL3G3LogicalDbReceipt } from '../node_modules/helix/src/doctor/l3-g3-logical-db-receipt.ts';
import { nodeDoctorDeps, runConsumerDoctor } from '../node_modules/helix/src/doctor/index.ts';
import { consumerProjectionFailures } from './lib/consumer-receipt-contract.mjs';

const repoRoot = process.cwd();
const git = (ref: string) => execFileSync('git', ['rev-parse', ref], { cwd: repoRoot, encoding: 'utf8' }).trim();
const expectedHead = git('HEAD');
const expectedTree = git('HEAD^{tree}');
const doctor = runConsumerDoctor(nodeDoctorDeps(repoRoot));
const db = createL3G3LogicalDbReceipt(repoRoot);
const failures = consumerProjectionFailures(db, { expectedHead, expectedTree, doctorPassed: doctor.ok });
if (git('HEAD') !== expectedHead || git('HEAD^{tree}') !== expectedTree) failures.push('head_changed_during_probe');
process.stdout.write(JSON.stringify({
  schema_version: 'helix-consumer-receipt-diagnostic.v1',
  approval_receipt: false,
  source_head: expectedHead,
  source_tree: expectedTree,
  doctor_passed: doctor.ok,
  core_converged: db.converged,
  consumer_projection_failures: failures,
  checkpoint_row_counts: db.checkpoint_row_counts,
  replay_checkpoint_row_counts: db.replay_checkpoint_row_counts,
  core_checkpoint_population_valid: db.checkpoint_population_valid,
  workspace_attestation: db.workspace_attestation,
}, null, 2) + '\n');
if (failures.length > 0) process.exitCode = 1;
