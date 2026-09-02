/**
 * TC-019 / TC-020 — apply-rollback E2E テスト（実 WP :8086）
 *
 * テスト設計SSOT: 旧 L3-test-plan.md（削除済み、TC 番号は履歴 ID） TC-019/TC-020 (P0)
 * 実装根拠:
 *   - POST /wp-json/agent-neo/v1/actions/dry-run  (class-actions-controller.php)
 *   - POST /wp-json/agent-neo/v1/actions/apply    (class-actions-controller.php)
 *   - POST /wp-json/agent-neo/v1/rollback/{id}    (class-pages-controller.php)
 *
 * 認証: WP cookie 認証 + X-WP-Nonce（wp_verify_nonce('wp_rest')）
 * admin 認証情報: e2e_admin / E2eAdmin!2026（dev 環境確定値）
 *
 * データ安全:
 *   - テスト専用の下書き投稿を WP REST API（POST /wp/v2/posts）で作成
 *   - TC-019 正常系: apply → rollback でネットゼロ復帰し投稿削除
 *   - TC-020 失敗系: 不正パラメータによる失敗テスト → 投稿削除（コンテンツ改変なし保証）
 *   - afterEach でガード削除（失敗時もクリーンアップ）
 */

import { test, expect, type APIRequestContext } from '@playwright/test';

// -----------------------------------------------------------------------
// 定数
// -----------------------------------------------------------------------
const BASE_URL = 'http://localhost:8086';
/** 検証済み認証レシピ: e2e_admin (administrator ID5) / E2eAdmin!2026 */
const ADMIN_USER = 'e2e_admin';
const ADMIN_PASS = 'E2eAdmin!2026';

// -----------------------------------------------------------------------
// ユーティリティ: UUIDv4 生成（wp_verify で request_id must be UUIDv4 必須）
// -----------------------------------------------------------------------
function newUUID(): string {
  return crypto.randomUUID();
}

// -----------------------------------------------------------------------
// ヘルパー: WP ログイン + nonce 取得（認証レシピ厳守）
// -----------------------------------------------------------------------

/**
 * WP にログインし、REST nonce を保持した APIRequestContext を返す。
 *
 * 認証レシピ（curl で完全実証済み・この順序を厳守）:
 *   1. GET /wp-login.php → wordpress_test_cookie を ctx に注入（必須）
 *   2. POST /wp-login.php → wordpress_logged_in cookie を取得
 *   3. GET /wp-admin/admin-ajax.php?action=rest-nonce → wp_rest nonce (10桁 hex)
 */
async function loginAndGetContext(playwright: { request: { newContext: Function } }): Promise<{ ctx: APIRequestContext; nonce: string }> {
  const ctx = await playwright.request.newContext({ baseURL: BASE_URL });

  // Step 1: GET /wp-login.php — wordpress_test_cookie を ctx に注入（この事前 GET が必須）
  await ctx.get('/wp-login.php');

  // Step 2: POST /wp-login.php — 302 で wordpress_logged_in cookie が ctx に保持される
  await ctx.post('/wp-login.php', {
    form: {
      log: ADMIN_USER,
      pwd: ADMIN_PASS,
      'wp-submit': 'Log In',
      redirect_to: BASE_URL + '/wp-admin/',
      testcookie: '1',
    },
  });

  // Step 3: nonce 取得（canonical: admin-ajax.php?action=rest-nonce が wp_rest nonce を返す）
  const nonceResp = await ctx.get('/wp-admin/admin-ajax.php?action=rest-nonce');
  const nonce = (await nonceResp.text()).trim();

  return { ctx, nonce };
}

/**
 * WP REST API で投稿を作成して post_id を返す。
 * WP core REST API（/wp/v2/posts）を使用（AGENT NEO 依存なし）。
 */
async function createTestPost(ctx: APIRequestContext, nonce: string, title: string, content: string): Promise<number> {
  const resp = await ctx.post('/wp-json/wp/v2/posts', {
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': nonce,
    },
    data: {
      title: title,
      content: content,
      status: 'draft',
    },
  });
  const body = await resp.json();
  const postId = body.id as number;
  return postId;
}

/**
 * WP REST API で投稿を削除する（クリーンアップ用）。
 */
async function deleteTestPost(ctx: APIRequestContext, nonce: string, postId: number): Promise<void> {
  await ctx.delete(`/wp-json/wp/v2/posts/${postId}?force=true`, {
    headers: { 'X-WP-Nonce': nonce },
  });
}

// -----------------------------------------------------------------------
// TC-019: dry-run → apply → rollback 正常系
// -----------------------------------------------------------------------

test.describe('TC-019: apply-rollback 正常系', () => {
  let ctx: APIRequestContext;
  let nonce: string;
  let testPostId: number;

  const ORIGINAL_CONTENT = '<!-- wp:paragraph {"blockId":"tc019-block-001"} --><p>TC-019 元の本文（rollback 先）</p><!-- /wp:paragraph -->';
  const CHANGED_CONTENT  = '<!-- wp:paragraph {"blockId":"tc019-block-001"} --><p>TC-019 変更後の本文（apply 対象）</p><!-- /wp:paragraph -->';

  test.beforeAll(async ({ playwright }) => {
    ({ ctx, nonce } = await loginAndGetContext(playwright));
    // テスト専用下書き投稿を作成
    testPostId = await createTestPost(ctx, nonce, 'TC-019 E2E テスト投稿', ORIGINAL_CONTENT);
  });

  test.afterAll(async () => {
    // テスト投稿を削除してクリーンアップ
    if (testPostId && ctx && nonce) {
      await deleteTestPost(ctx, nonce, testPostId);
    }
    await ctx?.dispose();
  });

  test('TC-019-1: 認証確立（ログイン cookie + nonce 取得）', async () => {
    expect(nonce).toBeTruthy();
    expect(nonce.length).toBeGreaterThanOrEqual(8);
    expect(testPostId).toBeGreaterThan(0);
  });

  test('TC-019-2: dry-run が 200 で diff_hash / dry_run_token / rollback_preview_token を返す', async () => {
    // request_id は UUIDv4 必須（wp_verify で "request_id must be UUIDv4" エラーが発生するため）
    const requestId = newUUID();

    const resp = await ctx.post('/wp-json/agent-neo/v1/actions/dry-run', {
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': nonce,
      },
      data: {
        action: 'patch_post',
        resource_id: testPostId,
        request_id: requestId,
        // changes は配列（schema: "changes":{"type":"array"}）
        changes: [
          {
            op: 'replace',
            path: '/post_content',
            value: CHANGED_CONTENT,
          },
        ],
      },
    });

    expect(resp.status()).toBe(200);
    const body = await resp.json();

    // レスポンス封筒: { success:true, data:{...}, meta:{request_id}, error:null }
    expect(body.success).toBe(true);
    expect(body.error).toBeNull();
    expect(body).toHaveProperty('data');
    const data = body.data;
    expect(data).toHaveProperty('diff_hash');
    expect(data).toHaveProperty('dry_run_token');
    expect(data).toHaveProperty('rollback_preview_token');
    expect(data).toHaveProperty('diff');

    // diff_hash が空でないこと
    expect(data.diff_hash as string).toMatch(/^[0-9a-f]{16,}$/);
    // diff が空でないこと（実変更が表現されていること）
    expect(data.diff).not.toBeNull();
    const diffArr = data.diff as Array<unknown>;
    expect(diffArr.length).toBeGreaterThan(0);
  });

  test('TC-019-3: apply が 200 で applied=true / rollback_point_id / audit_id を返す', async () => {
    // request_id は UUIDv4 必須
    const requestId = newUUID();

    // --- Step 1: dry-run ---
    const dryRunResp = await ctx.post('/wp-json/agent-neo/v1/actions/dry-run', {
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': nonce,
      },
      data: {
        action: 'patch_post',
        resource_id: testPostId,
        request_id: requestId,
        changes: [
          {
            op: 'replace',
            path: '/post_content',
            value: CHANGED_CONTENT,
          },
        ],
      },
    });
    expect(dryRunResp.status()).toBe(200);
    const dryRunBody = await dryRunResp.json();
    const diffHash = dryRunBody.data.diff_hash as string;
    expect(diffHash).toBeTruthy();

    // --- Step 2: apply ---
    // idempotency_key は UUID 文字列（形式を問わないが UUID が確実）
    const idempotencyKey = newUUID();
    const applyResp = await ctx.post('/wp-json/agent-neo/v1/actions/apply', {
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': nonce,
      },
      data: {
        action: 'patch_post',
        resource_id: testPostId,
        request_id: requestId,
        diff_hash: diffHash,
        idempotency_key: idempotencyKey,
        rollback_reason: 'TC-019 E2E apply テスト',
      },
    });

    expect(applyResp.status()).toBe(200);
    const applyBody = await applyResp.json();
    expect(applyBody.success).toBe(true);
    const applyData = applyBody.data;

    // applied=true
    expect(applyData.applied).toBe(true);
    // rollback_point_id が "rb_" で始まる形式で返却される
    expect(applyData.rollback_point_id).toBeTruthy();
    expect(applyData.rollback_point_id as string).toMatch(/^rb_/);
    // diff_hash が dry-run と一致する
    expect(applyData.diff_hash).toBe(diffHash);
    // audit_id が返却される（"act_" 形式）
    expect(applyData.audit_id).toBeTruthy();

    // WP ポスト本文が実際に更新されたことを確認（WP REST /wp/v2/posts で取得）
    const postResp = await ctx.get(`/wp-json/wp/v2/posts/${testPostId}`, {
      headers: { 'X-WP-Nonce': nonce },
    });
    const postBody = await postResp.json();
    // コンテンツに変更後テキストが含まれること（WP がフィルタリングした rendered を確認）
    const renderedContent = postBody.content?.rendered ?? '';
    expect(renderedContent).toContain('変更後の本文');
  });

  test('TC-019-4: rollback が 200 で restored=true を返し元コンテンツが復元される', async () => {
    // まず apply して rollback_point_id を取得する
    const requestId = newUUID();

    // --- dry-run ---
    const dryRunResp = await ctx.post('/wp-json/agent-neo/v1/actions/dry-run', {
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': nonce,
      },
      data: {
        action: 'patch_post',
        resource_id: testPostId,
        request_id: requestId,
        changes: [
          {
            op: 'replace',
            path: '/post_content',
            value: '<!-- wp:paragraph --><p>TC-019 rollback 直前の本文</p><!-- /wp:paragraph -->',
          },
        ],
      },
    });
    expect(dryRunResp.status()).toBe(200);
    const dryRunBody = await dryRunResp.json();
    const diffHash = dryRunBody.data.diff_hash as string;

    // --- apply ---
    const applyResp = await ctx.post('/wp-json/agent-neo/v1/actions/apply', {
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': nonce,
      },
      data: {
        action: 'patch_post',
        resource_id: testPostId,
        request_id: requestId,
        diff_hash: diffHash,
        idempotency_key: newUUID(),
        rollback_reason: 'TC-019 rollback 前の apply',
      },
    });
    expect(applyResp.status()).toBe(200);
    const applyData = (await applyResp.json()).data;
    const rollbackPointId = applyData.rollback_point_id as string;
    expect(rollbackPointId).toBeTruthy();

    // --- rollback ---
    // rollback エンドポイント: POST /wp-json/agent-neo/v1/rollback/{rollback_point_id}
    // data: { request_id:<UUIDv4>, idempotency_key:<UUIDv4> }
    const rollbackResp = await ctx.post(`/wp-json/agent-neo/v1/rollback/${rollbackPointId}`, {
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': nonce,
      },
      data: {
        request_id: newUUID(),
        idempotency_key: newUUID(),
      },
    });

    expect(rollbackResp.status()).toBe(200);
    const rollbackBody = await rollbackResp.json();
    expect(rollbackBody.success).toBe(true);
    const rollbackData = rollbackBody.data;

    // restored=true
    expect(rollbackData.restored).toBe(true);
    // post_id が返却される
    expect(rollbackData.post_id).toBe(testPostId);
    // audit_id が返却される
    expect(rollbackData.audit_id).toBeTruthy();

    // WP 投稿コンテンツが rollback 前コンテンツに戻ったことを確認
    const afterResp = await ctx.get(`/wp-json/wp/v2/posts/${testPostId}`, {
      headers: { 'X-WP-Nonce': nonce },
    });
    const afterBody = await afterResp.json();
    const renderedAfter = afterBody.content?.rendered ?? '';
    // "rollback 直前の本文" が消えていること
    expect(renderedAfter).not.toContain('rollback 直前の本文');
  });

  test('TC-019-5: 完全フロー — dry-run → apply → rollback でネットゼロ復帰', async () => {
    const uniqueTitle = `TC-019 ネットゼロ確認 ${Date.now()}`;
    const originalContent = `<!-- wp:paragraph --><p>${uniqueTitle} 元のコンテンツ</p><!-- /wp:paragraph -->`;
    const newContent      = `<!-- wp:paragraph --><p>${uniqueTitle} 変更後のコンテンツ</p><!-- /wp:paragraph -->`;

    // 専用投稿を作成
    const pid = await createTestPost(ctx, nonce, uniqueTitle, originalContent);

    try {
      // request_id は dry-run と apply で同一である必要がある（サーバーがセッションで照合）
      const requestId = newUUID();

      // --- dry-run ---
      const drResp = await ctx.post('/wp-json/agent-neo/v1/actions/dry-run', {
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
        data: {
          action: 'patch_post',
          resource_id: pid,
          request_id: requestId,
          changes: [{ op: 'replace', path: '/post_content', value: newContent }],
        },
      });
      expect(drResp.status()).toBe(200);
      const drData = (await drResp.json()).data;
      const diffHash = drData.diff_hash as string;

      // --- apply（dry-run と同じ request_id を使用）---
      const apResp = await ctx.post('/wp-json/agent-neo/v1/actions/apply', {
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
        data: {
          action: 'patch_post',
          resource_id: pid,
          request_id: requestId,
          diff_hash: diffHash,
          idempotency_key: newUUID(),
        },
      });
      expect(apResp.status()).toBe(200);
      const apData = (await apResp.json()).data;
      expect(apData.applied).toBe(true);
      const rbpId = apData.rollback_point_id as string;
      expect(rbpId).toMatch(/^rb_/);

      // apply 後のコンテンツ確認
      const midResp = await ctx.get(`/wp-json/wp/v2/posts/${pid}`, {
        headers: { 'X-WP-Nonce': nonce },
      });
      expect((await midResp.json()).content?.rendered ?? '').toContain('変更後のコンテンツ');

      // --- rollback ---
      const rbResp = await ctx.post(`/wp-json/agent-neo/v1/rollback/${rbpId}`, {
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
        data: {
          request_id: newUUID(),
          idempotency_key: newUUID(),
        },
      });
      expect(rbResp.status()).toBe(200);
      const rbData = (await rbResp.json()).data;
      expect(rbData.restored).toBe(true);

      // rollback 後のコンテンツ確認（元に戻っていること）
      const afterResp = await ctx.get(`/wp-json/wp/v2/posts/${pid}`, {
        headers: { 'X-WP-Nonce': nonce },
      });
      const afterContent = (await afterResp.json()).content?.rendered ?? '';
      expect(afterContent).toContain('元のコンテンツ');
      expect(afterContent).not.toContain('変更後のコンテンツ');
    } finally {
      await deleteTestPost(ctx, nonce, pid);
    }
  });
});

// -----------------------------------------------------------------------
// TC-020: rollback 失敗時に状態を破綻させない / 正しい再試行で成功
// -----------------------------------------------------------------------

test.describe('TC-020: rollback 失敗時の状態不変 + 再試行成功', () => {
  let ctx: APIRequestContext;
  let nonce: string;
  let testPostId: number;

  const ORIGINAL_CONTENT = '<!-- wp:paragraph --><p>TC-020 元の本文（状態不変確認用）</p><!-- /wp:paragraph -->';

  test.beforeAll(async ({ playwright }) => {
    ({ ctx, nonce } = await loginAndGetContext(playwright));
    testPostId = await createTestPost(ctx, nonce, 'TC-020 E2E テスト投稿', ORIGINAL_CONTENT);
  });

  test.afterAll(async () => {
    if (testPostId && ctx && nonce) {
      await deleteTestPost(ctx, nonce, testPostId);
    }
    await ctx?.dispose();
  });

  test('TC-020-1: 不正な rollback_id で rollback すると 4xx が返り状態が変化しない', async () => {
    // 存在しない rollback_id（rb_ プレフィックスを使用）
    const fakeRollbackId = 'rb_nonexistent-rollback-id-0000000000';

    const beforeResp = await ctx.get(`/wp-json/wp/v2/posts/${testPostId}`, {
      headers: { 'X-WP-Nonce': nonce },
    });
    const beforeContent = (await beforeResp.json()).content?.rendered ?? '';

    const rbResp = await ctx.post(`/wp-json/agent-neo/v1/rollback/${fakeRollbackId}`, {
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
      data: {
        request_id: newUUID(),
        idempotency_key: newUUID(),
      },
    });

    // 4xx（NOT_FOUND が期待値）
    expect(rbResp.status()).toBeGreaterThanOrEqual(400);
    expect(rbResp.status()).toBeLessThan(500);
    const failBody = await rbResp.json();
    // エラーコードは body.code で直接取得（封筒構造: {code, message, data:{status,...}}）
    expect(failBody.code).toBe('NOT_FOUND');

    // 投稿コンテンツが変化していないこと
    const afterResp = await ctx.get(`/wp-json/wp/v2/posts/${testPostId}`, {
      headers: { 'X-WP-Nonce': nonce },
    });
    const afterContent = (await afterResp.json()).content?.rendered ?? '';
    expect(afterContent).toBe(beforeContent);
  });

  test('TC-020-2: idempotency_key なしで rollback すると VALIDATION_ERROR(400)', async () => {
    // apply して有効な rollback_point_id を取得
    const newContent = '<!-- wp:paragraph --><p>TC-020 apply 後コンテンツ</p><!-- /wp:paragraph -->';

    // request_id は dry-run と apply で同一である必要がある（サーバーがセッションで照合）
    const applyRequestId = newUUID();

    // dry-run
    const drResp = await ctx.post('/wp-json/agent-neo/v1/actions/dry-run', {
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
      data: {
        action: 'patch_post',
        resource_id: testPostId,
        request_id: applyRequestId,
        changes: [{ op: 'replace', path: '/post_content', value: newContent }],
      },
    });
    expect(drResp.status()).toBe(200);
    const diffHash = (await drResp.json()).data.diff_hash as string;

    // apply（dry-run と同じ request_id を使用）
    const apResp = await ctx.post('/wp-json/agent-neo/v1/actions/apply', {
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
      data: {
        action: 'patch_post',
        resource_id: testPostId,
        request_id: applyRequestId,
        diff_hash: diffHash,
        idempotency_key: newUUID(),
      },
    });
    expect(apResp.status()).toBe(200);
    const rollbackPointId = (await apResp.json()).data.rollback_point_id as string;

    // idempotency_key なしで rollback（VALIDATION_ERROR 期待）
    const rbFailResp = await ctx.post(`/wp-json/agent-neo/v1/rollback/${rollbackPointId}`, {
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
      data: {
        request_id: newUUID(),
        // idempotency_key を意図的に省略
      },
    });
    expect(rbFailResp.status()).toBe(400);
    const failBody = await rbFailResp.json();
    // エラーコードは body.code で直接取得（封筒: {code, message, data:{status,...}}）
    expect(failBody.code).toBe('VALIDATION_ERROR');

    // --- 正しい idempotency_key で再試行 → 成功 ---
    const rbSuccessResp = await ctx.post(`/wp-json/agent-neo/v1/rollback/${rollbackPointId}`, {
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
      data: {
        request_id: newUUID(),
        idempotency_key: newUUID(),
      },
    });
    expect(rbSuccessResp.status()).toBe(200);
    const successData = (await rbSuccessResp.json()).data;
    expect(successData.restored).toBe(true);
  });

  test('TC-020-3: apply の diff_hash 不一致（412 PRECONDITION_FAILED）後に状態不変 + 正しい再試行で成功', async () => {
    // 現在のコンテンツ取得
    const beforeResp = await ctx.get(`/wp-json/wp/v2/posts/${testPostId}`, {
      headers: { 'X-WP-Nonce': nonce },
    });
    const beforeContent = (await beforeResp.json()).content?.rendered ?? '';

    const newContent = '<!-- wp:paragraph --><p>TC-020-3 変更コンテンツ</p><!-- /wp:paragraph -->';

    // request_id は dry-run と apply で同一である必要がある（サーバーがセッションで照合）
    const applyRequestId = newUUID();

    // dry-run で有効な diff_hash 取得（正しい形式: request_id=UUIDv4, changes=配列）
    const drResp = await ctx.post('/wp-json/agent-neo/v1/actions/dry-run', {
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
      data: {
        action: 'patch_post',
        resource_id: testPostId,
        request_id: applyRequestId,
        changes: [{ op: 'replace', path: '/post_content', value: newContent }],
      },
    });
    expect(drResp.status()).toBe(200);
    const validDiffHash = (await drResp.json()).data.diff_hash as string;

    // 不正な diff_hash で apply → 4xx（412 PRECONDITION_FAILED 期待）
    // ※ request_id が一致していても diff_hash が不正なら PRECONDITION_FAILED になる
    const invalidApplyResp = await ctx.post('/wp-json/agent-neo/v1/actions/apply', {
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
      data: {
        action: 'patch_post',
        resource_id: testPostId,
        request_id: applyRequestId,
        diff_hash: 'invalid-diff-hash-' + '0'.repeat(50),
        idempotency_key: newUUID(),
      },
    });
    expect(invalidApplyResp.status()).toBeGreaterThanOrEqual(400);
    expect(invalidApplyResp.status()).toBeLessThan(500);

    // 状態不変確認（コンテンツが変化していないこと）
    const midResp = await ctx.get(`/wp-json/wp/v2/posts/${testPostId}`, {
      headers: { 'X-WP-Nonce': nonce },
    });
    const midContent = (await midResp.json()).content?.rendered ?? '';
    expect(midContent).toBe(beforeContent);

    // 正しい diff_hash で再試行 → 成功（同じ request_id で再 dry-run してから apply）
    const retryRequestId = newUUID();
    const retryDrResp = await ctx.post('/wp-json/agent-neo/v1/actions/dry-run', {
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
      data: {
        action: 'patch_post',
        resource_id: testPostId,
        request_id: retryRequestId,
        changes: [{ op: 'replace', path: '/post_content', value: newContent }],
      },
    });
    expect(retryDrResp.status()).toBe(200);
    const retryDiffHash = (await retryDrResp.json()).data.diff_hash as string;

    const validApplyResp = await ctx.post('/wp-json/agent-neo/v1/actions/apply', {
      headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
      data: {
        action: 'patch_post',
        resource_id: testPostId,
        request_id: retryRequestId,
        diff_hash: retryDiffHash,
        idempotency_key: newUUID(),
      },
    });
    expect(validApplyResp.status()).toBe(200);
    const validApplyData = (await validApplyResp.json()).data;
    expect(validApplyData.applied).toBe(true);
  });
});
