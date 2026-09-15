import { execFileSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { createL3G3LogicalDbReceipt } from '../../node_modules/helix/src/doctor/l3-g3-logical-db-receipt.ts';
import { nodeDoctorDeps, runConsumerDoctor } from '../../node_modules/helix/src/doctor/index.ts';
import { canonicalJson, sha256Digest } from '../../node_modules/helix/src/runtime/digest.ts';
import { consumerProjectionFailures } from './consumer-receipt-contract.mjs';

export const CONSUMER_REVIEW_PROFILE = 'consumer-review-projection.v1';

// 保存済みJSONは未信頼入力。freshな結果を呼出側から注入せず、自身で再生成する。
export function validateConsumerReviewReceipt(repoRoot: string, candidate: unknown): boolean {
  if (candidate === null || typeof candidate !== 'object' || Array.isArray(candidate)) return false;
  const value = candidate as Record<string, unknown>;
  if (value.schema_version !== 'helix-consumer-review-db-receipt.v1' ||
      value.profile !== CONSUMER_REVIEW_PROFILE || value.converged !== true) return false;
  try {
    const { receipt_digest, ...body } = value;
    if (receipt_digest !== sha256Digest(canonicalJson(body))) return false;
    const current = createConsumerReviewReceipt(repoRoot, CONSUMER_REVIEW_PROFILE);
    return current.converged && canonicalJson(current) === canonicalJson(candidate);
  } catch {
    return false;
  }
}

// DB証拠だけを生成する。reviewer本人性・CI・投稿・ACKの代用にはしない。
// 呼出側はconsumer profileを明示する。本体receipt失敗時の自動fallbackは禁止。
export function createConsumerReviewReceipt(repoRoot: string, profile: string, reviewHead?: string) {
  if (profile !== CONSUMER_REVIEW_PROFILE) throw new Error('unsupported_consumer_review_profile');
  const git = (ref: string) => execFileSync('git', ['rev-parse', ref], { cwd: repoRoot, encoding: 'utf8' }).trim();
  const sourceHead = git('HEAD');
  if (reviewHead !== undefined && (!/^[a-f0-9]{40}$/.test(reviewHead) || reviewHead !== sourceHead)) {
    throw new Error('review_head_db_source_mismatch');
  }
  const sourceTree = git('HEAD^{tree}');
  const doctor = runConsumerDoctor(nodeDoctorDeps(repoRoot));
  const coreReceipt = createL3G3LogicalDbReceipt(repoRoot);
  const failures = consumerProjectionFailures(coreReceipt, {
    expectedHead: sourceHead,
    expectedTree: sourceTree,
    doctorPassed: doctor.ok,
  });
  if (git('HEAD') !== sourceHead || git('HEAD^{tree}') !== sourceTree) failures.push('head_changed_during_generation');
  const body = {
    schema_version: 'helix-consumer-review-db-receipt.v1',
    profile,
    source_head: sourceHead,
    source_tree: sourceTree,
    projection_digest: coreReceipt.projection_digest,
    replay_projection_digest: coreReceipt.replay_projection_digest,
    checkpoint_digest: coreReceipt.checkpoint_digest,
    replay_checkpoint_digest: coreReceipt.replay_checkpoint_digest,
    doctor_passed: doctor.ok,
    verifier_digest: sha256Digest(canonicalJson({
      generator: readFileSync(new URL('./create-consumer-review-receipt.ts', import.meta.url), 'utf8'),
      predicate: readFileSync(new URL('./consumer-receipt-contract.mjs', import.meta.url), 'utf8'),
      doctor: readFileSync(new URL('../../node_modules/helix/src/doctor/index.ts', import.meta.url), 'utf8'),
    })),
    // 本体G3判定をfalseからtrueへ書き換えず、全証拠を保持する。
    core_receipt: coreReceipt,
    failures,
    converged: failures.length === 0,
  };
  return { ...body, receipt_digest: sha256Digest(canonicalJson(body)) };
}
