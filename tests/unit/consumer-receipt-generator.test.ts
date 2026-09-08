import { test } from 'node:test';
import assert from 'node:assert/strict';
import { fileURLToPath } from 'node:url';
import { createConsumerReviewReceipt, validateConsumerReviewReceipt } from '../../scripts/lib/create-consumer-review-receipt.ts';

test('unknown profiles reject before reading repository or rebuilding DB', () => {
  for (const profile of ['', 'core', 'consumer', 'consumer-review-projection.v2']) {
    assert.throws(() => createConsumerReviewReceipt('nonexistent-repository', profile), /unsupported_consumer_review_profile/);
  }
});

test('review HEAD mismatch rejects before doctor or DB generation', () => {
  const root = fileURLToPath(new URL('../../', import.meta.url));
  for (const head of ['', 'invalid', '0'.repeat(40)]) {
    assert.throws(() => createConsumerReviewReceipt(root, 'consumer-review-projection.v1', head), /review_head_db_source_mismatch/);
  }
});

test('untrusted receipts reject malformed input, wrong profile and false convergence', () => {
  for (const candidate of [null, undefined, [], {}, {
    schema_version: 'helix-consumer-review-db-receipt.v1',
    profile: 'consumer-review-projection.v1',
    converged: false,
  }, {
    schema_version: 'helix-consumer-review-db-receipt.v1',
    profile: 'consumer-review-projection.v1',
    converged: true,
    receipt_digest: 'sha256:' + '0'.repeat(64),
  }]) assert.equal(validateConsumerReviewReceipt('nonexistent-repository', candidate), false);
});
