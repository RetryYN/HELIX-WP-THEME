<?php
/**
 * TC-005 / TC-006 — PagesController 統合テスト
 *
 * 対象:
 *   TC-005: POST /pages/{id}/apply
 *     シナリオ①: preview 昇格経路（from_preview_token 未指定時に拒否）
 *     シナリオ②: 通常 apply（非 preview）で from_preview_token なしでも正常完了
 *
 *   TC-006: POST /pages/{id}/rollback
 *     - rollback_point 不在時 404/410
 *     - 既存履歴の復元成功（apply → rollback で原状復帰）
 *
 * テスト DB: wordpress_test（分離済み）
 * ライブ DB: agent_neo（無変更を保証）
 *
 * 注意: TC-005 シナリオ① は「template_id 付き apply_page で
 *   from_preview_token が必要」という corporate package 判定と
 *   preview_state ロジックの組み合わせを検証する。
 *   Pages controller の check_apply_package_scope により
 *   template_id or from_preview_token が指定された場合は
 *   corporate package が要求される。
 *   テストでは personal package（デフォルト）環境下で
 *   template_id 指定 apply が 403 で拒否されることを確認する。
 *
 * @package AgentNeoCore\Tests\Integration
 */

declare( strict_types=1 );

/**
 * PagesController の P0 状態遷移統合テスト。
 */
class TC005_TC006_PagesControllerTest extends WP_UnitTestCase {

	/**
	 * テスト用 administrator ユーザ ID。
	 *
	 * @var int
	 */
	private int $admin_user_id = 0;

	/**
	 * テスト用 WP_REST_Server インスタンス。
	 *
	 * @var WP_REST_Server
	 */
	private WP_REST_Server $server;

	/**
	 * setUp: administrator ユーザ作成・REST サーバ初期化。
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->admin_user_id = self::factory()->user->create(
			array( 'role' => 'administrator' )
		);
		wp_set_current_user( $this->admin_user_id );

		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;
		do_action( 'rest_api_init' );
	}

	/**
	 * tearDown: 現在ユーザをリセットする。
	 *
	 * @return void
	 */
	public function tearDown(): void {
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	// ------------------------------------------------------------------
	// TC-005 シナリオ①: preview 昇格経路 — template_id あり+from_preview_token なしで拒否
	// ------------------------------------------------------------------

	/**
	 * TC-005-S1: template_id を指定した apply_page は
	 *   corporate package が要求され、personal package では 403 で拒否される。
	 *
	 * 実装詳細:
	 *   check_apply_package_scope は !empty($params['template_id']) || !empty($params['from_preview_token'])
	 *   の場合に corporate を要求する。テスト環境は personal package がデフォルトなので 403 になる。
	 *
	 * @return void
	 */
	public function test_tc005_scenario1_preview_apply_rejected_without_corporate_package(): void {
		// --- Arrange: page 投稿（post_type=page）を作成する ---
		$page_id = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_title'   => 'TC-005-S1 テストページ',
				'post_content' => '<!-- wp:paragraph --><p>元の内容</p><!-- /wp:paragraph -->',
				'post_status'  => 'publish',
				'post_author'  => $this->admin_user_id,
			)
		);

		$nonce = wp_create_nonce( 'wp_rest' );

		// --- Act: template_id 付き apply_page を実行（dry-run なしでも package 判定が先） ---
		$apply_request = new WP_REST_Request( 'POST', "/agent-neo/v1/pages/{$page_id}/apply" );
		$apply_request->add_header( 'Content-Type', 'application/json' );
		$apply_request->add_header( 'X-WP-Nonce', $nonce );
		$apply_request->set_param( 'id', (string) $page_id );
		$apply_request->set_body(
			wp_json_encode(
				array(
					'diff_hash'       => 'some-diff-hash-' . wp_generate_uuid4(),
					'idempotency_key' => 'idem-tc005-s1-' . wp_generate_uuid4(),
					// template_id を指定することで corporate package が要求される。
					'template_id'     => 'lp-blueprint-v1',
				)
			)
		);
		$response = $this->server->dispatch( $apply_request );

		// --- Assert: corporate package 不足で 403 になること ---
		$this->assertSame(
			403,
			$response->get_status(),
			'TC-005-S1: template_id 付き apply_page は personal package では 403 で拒否されること'
		);
	}

	// ------------------------------------------------------------------
	// TC-005 シナリオ②: 通常 apply（非 preview）— from_preview_token なしでも正常完了
	// ------------------------------------------------------------------

	/**
	 * TC-005-S2: template_id / from_preview_token を指定しない通常 apply_page は
	 *   dry-run → apply で 200 が返り、rollback_point_id が生成される。
	 *
	 * @return void
	 */
	public function test_tc005_scenario2_normal_apply_page_without_preview_token(): void {
		// --- Arrange: page 投稿を作成する ---
		$original_content = '<!-- wp:paragraph --><p>TC-005-S2 元の内容</p><!-- /wp:paragraph -->';
		$page_id          = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_title'   => 'TC-005-S2 テストページ',
				'post_content' => $original_content,
				'post_status'  => 'publish',
				'post_author'  => $this->admin_user_id,
			)
		);

		$request_id = wp_generate_uuid4();
		$nonce      = wp_create_nonce( 'wp_rest' );
		$new_content = '<!-- wp:paragraph --><p>TC-005-S2 変更後の内容</p><!-- /wp:paragraph -->';

		// --- Act 1: actions/dry-run で page コンテンツ変更の dry-run を実行する ---
		// Pages controller は actions/dry-run を使って dry-run 結果を作成し、
		// pages/{id}/apply が diff_hash でそれを参照する。
		$dry_run_request = new WP_REST_Request( 'POST', '/agent-neo/v1/actions/dry-run' );
		$dry_run_request->add_header( 'Content-Type', 'application/json' );
		$dry_run_request->add_header( 'X-WP-Nonce', $nonce );
		$dry_run_request->set_body(
			wp_json_encode(
				array(
					'action'      => 'apply_page',
					'resource_id' => $page_id,
					'request_id'  => $request_id,
					'changes'     => array(
						array(
							'op'    => 'replace',
							'path'  => '/post_content',
							'value' => $new_content,
						),
					),
				)
			)
		);
		$dry_run_response = $this->server->dispatch( $dry_run_request );
		$this->assertSame( 200, $dry_run_response->get_status(), 'TC-005-S2 前提: dry-run が 200' );

		$dry_run_data    = $dry_run_response->get_data();
		$diff_hash       = $dry_run_data['data']['diff_hash'];
		$idempotency_key = 'idem-tc005-s2-' . wp_generate_uuid4();

		// --- Act 2: pages/{page_id}/apply を from_preview_token なしで実行する ---
		$apply_request = new WP_REST_Request( 'POST', "/agent-neo/v1/pages/{$page_id}/apply" );
		$apply_request->add_header( 'Content-Type', 'application/json' );
		$apply_request->add_header( 'X-WP-Nonce', $nonce );
		$apply_request->set_param( 'id', (string) $page_id );
		$apply_request->set_body(
			wp_json_encode(
				array(
					'diff_hash'       => $diff_hash,
					'idempotency_key' => $idempotency_key,
					'request_id'      => $request_id,
					// from_preview_token は指定しない（通常 apply 経路）。
				)
			)
		);
		$apply_response = $this->server->dispatch( $apply_request );

		// --- Assert: 正常完了 ---
		$this->assertSame(
			200,
			$apply_response->get_status(),
			'TC-005-S2: 通常 apply_page は 200 を返すこと'
		);
		$apply_data = $apply_response->get_data();
		$this->assertTrue(
			$apply_data['data']['applied'],
			'TC-005-S2: applied=true であること'
		);
		$this->assertNotEmpty(
			$apply_data['data']['rollback_point_id'],
			'TC-005-S2: rollback_point_id が返却されること'
		);
		// preview_state は from_preview_token を指定しなかったため "ignored"。
		$this->assertSame(
			'ignored',
			$apply_data['data']['preview_state'],
			'TC-005-S2: from_preview_token なしの場合 preview_state=ignored であること'
		);
	}

	// ------------------------------------------------------------------
	// TC-006: rollback_page — rollback_point 不在時 NOT_FOUND / 既存履歴の復元
	// ------------------------------------------------------------------

	/**
	 * TC-006-A: rollback_point_id が存在しない場合に 404 で拒否される。
	 *
	 * @return void
	 */
	public function test_tc006a_rollback_page_returns_404_when_rollback_point_not_found(): void {
		// --- Arrange ---
		$page_id = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_title'   => 'TC-006-A テストページ',
				'post_content' => '<!-- wp:paragraph --><p>元の内容</p><!-- /wp:paragraph -->',
				'post_status'  => 'publish',
				'post_author'  => $this->admin_user_id,
			)
		);

		$nonce = wp_create_nonce( 'wp_rest' );

		// --- Act: 存在しない rollback_point_id で rollback を実行する ---
		$rollback_request = new WP_REST_Request( 'POST', "/agent-neo/v1/pages/{$page_id}/rollback" );
		$rollback_request->add_header( 'Content-Type', 'application/json' );
		$rollback_request->add_header( 'X-WP-Nonce', $nonce );
		$rollback_request->set_param( 'id', (string) $page_id );
		$rollback_request->set_body(
			wp_json_encode(
				array(
					'rollback_point_id' => 'rb_nonexistent-' . wp_generate_uuid4(),
					'idempotency_key'   => 'idem-tc006-a-' . wp_generate_uuid4(),
				)
			)
		);
		$response = $this->server->dispatch( $rollback_request );

		// --- Assert: 404 または 410 で拒否されること ---
		$status = $response->get_status();
		$this->assertContains(
			$status,
			array( 404, 410 ),
			'TC-006-A: rollback_point 不在時は 404 または 410 が返ること'
		);
	}

	/**
	 * TC-006-B: apply → rollback で原状復帰する往復フロー。
	 *
	 * 検証手順:
	 *   1. page を作成
	 *   2. dry-run → apply で内容を変更（rollback_point_id を取得）
	 *   3. rollback を実行
	 *   4. 投稿コンテンツが元に戻っていることを DB から確認
	 *
	 * @return void
	 */
	public function test_tc006b_rollback_page_restores_original_content(): void {
		// --- Arrange ---
		$original_content = '<!-- wp:paragraph --><p>TC-006-B 元の内容（復元確認用）</p><!-- /wp:paragraph -->';
		$page_id          = self::factory()->post->create(
			array(
				'post_type'    => 'page',
				'post_title'   => 'TC-006-B テストページ',
				'post_content' => $original_content,
				'post_status'  => 'publish',
				'post_author'  => $this->admin_user_id,
			)
		);

		$request_id  = wp_generate_uuid4();
		$nonce       = wp_create_nonce( 'wp_rest' );
		$new_content = '<!-- wp:paragraph --><p>TC-006-B 変更後の内容</p><!-- /wp:paragraph -->';

		// Step 1: dry-run を実行する。
		$dry_run_request = new WP_REST_Request( 'POST', '/agent-neo/v1/actions/dry-run' );
		$dry_run_request->add_header( 'Content-Type', 'application/json' );
		$dry_run_request->add_header( 'X-WP-Nonce', $nonce );
		$dry_run_request->set_body(
			wp_json_encode(
				array(
					'action'      => 'apply_page',
					'resource_id' => $page_id,
					'request_id'  => $request_id,
					'changes'     => array(
						array(
							'op'    => 'replace',
							'path'  => '/post_content',
							'value' => $new_content,
						),
					),
				)
			)
		);
		$dry_run_response = $this->server->dispatch( $dry_run_request );
		$this->assertSame( 200, $dry_run_response->get_status(), 'TC-006-B 前提: dry-run が 200' );

		$diff_hash = $dry_run_response->get_data()['data']['diff_hash'];

		// Step 2: apply を実行して rollback_point_id を取得する。
		$apply_idem_key = 'idem-tc006-b-apply-' . wp_generate_uuid4();
		$apply_request  = new WP_REST_Request( 'POST', "/agent-neo/v1/pages/{$page_id}/apply" );
		$apply_request->add_header( 'Content-Type', 'application/json' );
		$apply_request->add_header( 'X-WP-Nonce', $nonce );
		$apply_request->set_param( 'id', (string) $page_id );
		$apply_request->set_body(
			wp_json_encode(
				array(
					'diff_hash'       => $diff_hash,
					'idempotency_key' => $apply_idem_key,
					'request_id'      => $request_id,
				)
			)
		);
		$apply_response = $this->server->dispatch( $apply_request );
		$this->assertSame( 200, $apply_response->get_status(), 'TC-006-B 前提: apply が 200' );

		$rollback_point_id = $apply_response->get_data()['data']['rollback_point_id'];
		$this->assertNotEmpty( $rollback_point_id, 'TC-006-B 前提: rollback_point_id が返却されること' );

		// 変更が実際に適用されたことを確認する。
		$changed_post = get_post( $page_id );
		$this->assertStringContainsString(
			'TC-006-B 変更後の内容',
			$changed_post->post_content,
			'TC-006-B 前提: 変更後の内容が投稿に反映されていること'
		);

		// Step 3: rollback を実行する。
		$rollback_idem_key = 'idem-tc006-b-rollback-' . wp_generate_uuid4();
		$rollback_request  = new WP_REST_Request( 'POST', "/agent-neo/v1/pages/{$page_id}/rollback" );
		$rollback_request->add_header( 'Content-Type', 'application/json' );
		$rollback_request->add_header( 'X-WP-Nonce', $nonce );
		$rollback_request->set_param( 'id', (string) $page_id );
		$rollback_request->set_body(
			wp_json_encode(
				array(
					'rollback_point_id' => $rollback_point_id,
					'idempotency_key'   => $rollback_idem_key,
					'reason'            => 'TC-006-B テスト rollback',
				)
			)
		);
		$rollback_response = $this->server->dispatch( $rollback_request );

		// --- Assert: rollback が成功し原状復帰する ---
		$this->assertSame(
			200,
			$rollback_response->get_status(),
			'TC-006-B: rollback は 200 を返すこと'
		);
		$rollback_data = $rollback_response->get_data();
		$this->assertTrue(
			$rollback_data['data']['restored'],
			'TC-006-B: restored=true であること'
		);
		$this->assertNotEmpty(
			$rollback_data['data']['audit_id'],
			'TC-006-B: audit_id が返却されること'
		);

		// DB から原状復帰を確認する。
		$restored_post = get_post( $page_id );
		$this->assertInstanceOf( WP_Post::class, $restored_post );
		$this->assertStringContainsString(
			'TC-006-B 元の内容（復元確認用）',
			$restored_post->post_content,
			'TC-006-B: rollback 後に元の内容が復元されること'
		);
		$this->assertStringNotContainsString(
			'TC-006-B 変更後の内容',
			$restored_post->post_content,
			'TC-006-B: rollback 後に変更後の内容が消えていること'
		);
	}
}
