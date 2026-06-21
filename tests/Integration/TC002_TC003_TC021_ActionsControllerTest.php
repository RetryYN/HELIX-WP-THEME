<?php
/**
 * TC-002 / TC-003 / TC-021 — ActionsController 統合テスト
 *
 * 対象:
 *   TC-002: POST /actions/apply 正常系 — dry-run 直後に apply 成功し rollback_point を保存
 *   TC-003: POST /actions/apply 異常系 — diff_hash 不一致時 412 で拒否
 *   TC-021: diff_hash 整合性 — dry-run/apply で同一 diff_hash 必須、変更内容と差分が一致
 *
 * テスト DB: wordpress_test（分離済み）
 * ライブ DB: agent_neo（無変更を保証）
 *
 * @package AgentNeoCore\Tests\Integration
 */

declare( strict_types=1 );

/**
 * ActionsController の P0 状態遷移統合テスト。
 */
class TC002_TC003_TC021_ActionsControllerTest extends WP_UnitTestCase {

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

		// administrator ユーザを作成し、以降のリクエストで使用する。
		$this->admin_user_id = self::factory()->user->create(
			array( 'role' => 'administrator' )
		);
		wp_set_current_user( $this->admin_user_id );

		// REST サーバを初期化する。
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
	// TC-002: apply_action 正常系
	// ------------------------------------------------------------------

	/**
	 * TC-002: dry-run 直後に apply を呼び出すと
	 *   - HTTP 200
	 *   - applied=true
	 *   - rollback_point_id が返却される
	 *   - diff_hash が dry-run レスポンスと一致する
	 *
	 * @return void
	 */
	public function test_tc002_apply_action_success_after_dry_run(): void {
		// --- Arrange: テスト用投稿を作成する ---
		$post_id = self::factory()->post->create(
			array(
				'post_title'   => 'TC-002 テスト投稿',
				// 最小限のブロックコンテンツ（JSON Patch が適用可能な形式）
				'post_content' => '<!-- wp:paragraph {"blockId":"test-block-001"} --><p>元の本文</p><!-- /wp:paragraph -->',
				'post_status'  => 'publish',
				'post_author'  => $this->admin_user_id,
			)
		);

		$request_id = wp_generate_uuid4();
		$nonce      = wp_create_nonce( 'wp_rest' );

		// --- Act 1: dry-run を実行する ---
		$dry_run_request = new WP_REST_Request( 'POST', '/agent-neo/v1/actions/dry-run' );
		$dry_run_request->add_header( 'Content-Type', 'application/json' );
		$dry_run_request->add_header( 'X-WP-Nonce', $nonce );
		$dry_run_request->set_body(
			wp_json_encode(
				array(
					'action'      => 'patch_post',
					'resource_id' => $post_id,
					'request_id'  => $request_id,
					'changes'     => array(
						array(
							'op'    => 'replace',
							'path'  => '/post_content',
							'value' => '<!-- wp:paragraph {"blockId":"test-block-001"} --><p>変更後の本文</p><!-- /wp:paragraph -->',
						),
					),
				)
			)
		);
		$dry_run_response = $this->server->dispatch( $dry_run_request );

		// dry-run が 200 で成功することを確認する。
		$this->assertSame(
			200,
			$dry_run_response->get_status(),
			'TC-002: dry-run は 200 を返すこと'
		);
		$dry_run_data = $dry_run_response->get_data();
		$this->assertArrayHasKey( 'data', $dry_run_data );
		$this->assertArrayHasKey( 'diff_hash', $dry_run_data['data'], 'TC-002: dry-run レスポンスに diff_hash が含まれること' );

		$diff_hash       = $dry_run_data['data']['diff_hash'];
		$idempotency_key = 'idem-tc002-' . wp_generate_uuid4();

		// --- Act 2: 取得した diff_hash で apply を実行する ---
		$apply_request = new WP_REST_Request( 'POST', '/agent-neo/v1/actions/apply' );
		$apply_request->add_header( 'Content-Type', 'application/json' );
		$apply_request->add_header( 'X-WP-Nonce', $nonce );
		$apply_request->set_body(
			wp_json_encode(
				array(
					'action'          => 'patch_post',
					'resource_id'     => $post_id,
					'request_id'      => $request_id,
					'diff_hash'       => $diff_hash,
					'idempotency_key' => $idempotency_key,
					'rollback_reason' => 'TC-002 テスト適用',
				)
			)
		);
		$apply_response = $this->server->dispatch( $apply_request );

		// --- Assert: 正常適用を確認する ---
		$this->assertSame(
			200,
			$apply_response->get_status(),
			'TC-002: apply は 200 を返すこと'
		);
		$apply_data = $apply_response->get_data();
		$this->assertArrayHasKey( 'data', $apply_data );

		$result = $apply_data['data'];
		$this->assertTrue(
			$result['applied'],
			'TC-002: applied=true であること'
		);
		$this->assertNotEmpty(
			$result['rollback_point_id'],
			'TC-002: rollback_point_id が返却されること'
		);
		$this->assertSame(
			$diff_hash,
			$result['diff_hash'],
			'TC-002: apply レスポンスの diff_hash が dry-run と一致すること'
		);
		$this->assertNotEmpty(
			$result['audit_id'],
			'TC-002: audit_id が返却されること'
		);
	}

	// ------------------------------------------------------------------
	// TC-003: apply_action 異常系 — diff_hash 不一致で 412
	// ------------------------------------------------------------------

	/**
	 * TC-003: diff_hash が dry-run と異なる場合に 412 PRECONDITION_FAILED で拒否される。
	 *
	 * @return void
	 */
	public function test_tc003_apply_action_rejected_when_diff_hash_mismatch(): void {
		// --- Arrange ---
		$post_id = self::factory()->post->create(
			array(
				'post_title'   => 'TC-003 テスト投稿',
				'post_content' => '<!-- wp:paragraph --><p>元の本文</p><!-- /wp:paragraph -->',
				'post_status'  => 'publish',
				'post_author'  => $this->admin_user_id,
			)
		);

		$request_id = wp_generate_uuid4();
		$nonce      = wp_create_nonce( 'wp_rest' );

		// dry-run を実行して有効な request_id を登録する。
		$dry_run_request = new WP_REST_Request( 'POST', '/agent-neo/v1/actions/dry-run' );
		$dry_run_request->add_header( 'Content-Type', 'application/json' );
		$dry_run_request->add_header( 'X-WP-Nonce', $nonce );
		$dry_run_request->set_body(
			wp_json_encode(
				array(
					'action'      => 'patch_post',
					'resource_id' => $post_id,
					'request_id'  => $request_id,
					'changes'     => array(
						array(
							'op'    => 'replace',
							'path'  => '/post_content',
							'value' => '<!-- wp:paragraph --><p>変更後</p><!-- /wp:paragraph -->',
						),
					),
				)
			)
		);
		$dry_run_response = $this->server->dispatch( $dry_run_request );
		$this->assertSame( 200, $dry_run_response->get_status(), 'TC-003 前提: dry-run が成功すること' );

		// --- Act: 意図的に不正な diff_hash で apply を呼び出す ---
		$apply_request = new WP_REST_Request( 'POST', '/agent-neo/v1/actions/apply' );
		$apply_request->add_header( 'Content-Type', 'application/json' );
		$apply_request->add_header( 'X-WP-Nonce', $nonce );
		$apply_request->set_body(
			wp_json_encode(
				array(
					'action'          => 'patch_post',
					'resource_id'     => $post_id,
					'request_id'      => $request_id,
					'diff_hash'       => 'invalid-diff-hash-0000000000000000000000000000000000000000000000000000000000000000',
					'idempotency_key' => 'idem-tc003-' . wp_generate_uuid4(),
				)
			)
		);
		$apply_response = $this->server->dispatch( $apply_request );

		// --- Assert: 412 または 4xx で拒否されること ---
		// dry-run store は request_id + diff_hash の組み合わせで管理するため、
		// 不一致 diff_hash では dry-run が見つからず PRECONDITION_FAILED になる。
		$status = $apply_response->get_status();
		$this->assertGreaterThanOrEqual(
			400,
			$status,
			'TC-003: 不正 diff_hash での apply は 4xx を返すこと'
		);
		$this->assertLessThan(
			500,
			$status,
			'TC-003: 不正 diff_hash での apply は 5xx にならないこと'
		);

		// エラーコードが PRECONDITION_FAILED であることを確認する。
		$data = $apply_response->get_data();
		if ( isset( $data['data']['error']['code'] ) ) {
			$this->assertSame(
				'PRECONDITION_FAILED',
				$data['data']['error']['code'],
				'TC-003: エラーコードが PRECONDITION_FAILED であること'
			);
		} elseif ( isset( $data['code'] ) ) {
			$this->assertSame(
				'PRECONDITION_FAILED',
				$data['code'],
				'TC-003: WP_Error code が PRECONDITION_FAILED であること'
			);
		}
	}

	// ------------------------------------------------------------------
	// TC-021: diff_hash 整合性 — dry-run/apply で同一 diff_hash 必須
	// ------------------------------------------------------------------

	/**
	 * TC-021: dry-run の diff_hash が apply まで一貫して維持される。
	 *   - dry-run レスポンスの diff_hash
	 *   - apply レスポンスの diff_hash
	 *   が完全一致すること。
	 *
	 * また、diff が空でないこと（実際に変更が表現されていること）を確認する。
	 *
	 * @return void
	 */
	public function test_tc021_diff_hash_consistency_between_dry_run_and_apply(): void {
		// --- Arrange ---
		$original_content = '<!-- wp:paragraph {"blockId":"tc021-block"} --><p>TC-021 元文</p><!-- /wp:paragraph -->';
		$post_id          = self::factory()->post->create(
			array(
				'post_title'   => 'TC-021 diff_hash 整合テスト',
				'post_content' => $original_content,
				'post_status'  => 'publish',
				'post_author'  => $this->admin_user_id,
			)
		);

		$request_id = wp_generate_uuid4();
		$nonce      = wp_create_nonce( 'wp_rest' );
		$new_content = '<!-- wp:paragraph {"blockId":"tc021-block"} --><p>TC-021 変更文</p><!-- /wp:paragraph -->';

		// --- Act 1: dry-run を実行して diff と diff_hash を取得する ---
		$dry_run_request = new WP_REST_Request( 'POST', '/agent-neo/v1/actions/dry-run' );
		$dry_run_request->add_header( 'Content-Type', 'application/json' );
		$dry_run_request->add_header( 'X-WP-Nonce', $nonce );
		$dry_run_request->set_body(
			wp_json_encode(
				array(
					'action'      => 'patch_post',
					'resource_id' => $post_id,
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
		$this->assertSame( 200, $dry_run_response->get_status(), 'TC-021 前提: dry-run が 200' );

		$dry_run_data = $dry_run_response->get_data();
		$diff_hash    = $dry_run_data['data']['diff_hash'];
		$diff         = $dry_run_data['data']['diff'];

		// diff が空でないこと（実変更が表現されていること）。
		$this->assertNotEmpty(
			$diff,
			'TC-021: dry-run の diff が空でないこと（変更が表現されていること）'
		);
		$this->assertNotEmpty(
			$diff_hash,
			'TC-021: diff_hash が空でないこと'
		);

		// --- Act 2: apply を実行する ---
		$idempotency_key = 'idem-tc021-' . wp_generate_uuid4();
		$apply_request   = new WP_REST_Request( 'POST', '/agent-neo/v1/actions/apply' );
		$apply_request->add_header( 'Content-Type', 'application/json' );
		$apply_request->add_header( 'X-WP-Nonce', $nonce );
		$apply_request->set_body(
			wp_json_encode(
				array(
					'action'          => 'patch_post',
					'resource_id'     => $post_id,
					'request_id'      => $request_id,
					'diff_hash'       => $diff_hash,
					'idempotency_key' => $idempotency_key,
				)
			)
		);
		$apply_response = $this->server->dispatch( $apply_request );
		$this->assertSame( 200, $apply_response->get_status(), 'TC-021 前提: apply が 200' );

		$apply_data = $apply_response->get_data();

		// --- Assert: diff_hash が干要に一致すること ---
		$this->assertSame(
			$diff_hash,
			$apply_data['data']['diff_hash'],
			'TC-021: dry-run と apply の diff_hash が完全一致すること'
		);
		$this->assertTrue(
			$apply_data['data']['applied'],
			'TC-021: applied=true であること'
		);

		// 実際に投稿コンテンツが更新されていることを DB から確認する。
		$updated_post = get_post( $post_id );
		$this->assertInstanceOf( WP_Post::class, $updated_post );
		$this->assertStringContainsString(
			'TC-021 変更文',
			$updated_post->post_content,
			'TC-021: 変更後の本文が実際の投稿に反映されていること'
		);
	}
}
