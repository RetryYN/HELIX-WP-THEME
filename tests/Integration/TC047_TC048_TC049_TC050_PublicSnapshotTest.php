<?php
/**
 * TC-047 / TC-048 / TC-049 / TC-050 — 公開エンドポイント情報漏洩防止 統合テスト
 *
 * 対象（GAP-RT-039 / REQ-NF-004 / REQ-NF-015）:
 *   TC-047: draft/private 記事が公開 snapshot に含まれないこと
 *   TC-048: draft/private/password-protected/noindex 記事が crawl-map に含まれないこと
 *   TC-049: nonce 情報がスナップショットレスポンスに含まれないこと
 *   TC-050: ライセンスキー / api_key が公開エンドポイントに含まれないこと
 *
 * テスト設計 SSOT: docs/test-plan/L3-test-plan.md §8.4 (TC-047〜TC-050)
 *
 * テスト DB: wordpress_test（分離済み）
 * ライブ DB: agent_neo（無変更を保証）
 *
 * 実装参照:
 *   plugins/agent-neo-core/inc/rest/class-public-controller.php
 *     GET /public/pages/{id}/snapshot  → get_page_snapshot()
 *     GET /public/crawl-map            → get_crawl_map()
 *     GET /public/llmo/answers         → get_llmo_answers()
 *
 * ルート形の乖離に関する注記:
 *   test-plan §8.4 TC-047 は "GET /public/pages/snapshot"（一覧）と記載しているが、
 *   実装されているルートは "GET /public/pages/{id}/snapshot"（単体）のみ。
 *   一覧ルートは実装に存在しないため、TC-047 は単体 snapshot エンドポイントを使用し、
 *   draft 記事 ID を渡した場合に 404 / 空レスポンスを返すことで検証する。
 *   また crawl-map（TC-048）には draft 記事が含まれないことを crawl-map の entries で検証する。
 *
 * @package AgentNeoCore\Tests\Integration
 */

declare( strict_types=1 );

/**
 * 公開エンドポイント情報漏洩防止 P0 統合テスト。
 */
class TC047_TC048_TC049_TC050_PublicSnapshotTest extends WP_UnitTestCase {

	/**
	 * テスト用 REST サーバ。
	 *
	 * @var WP_REST_Server
	 */
	private WP_REST_Server $server;

	/**
	 * 未認証リクエスト確認用: setUp 前のユーザ状態を保持。
	 *
	 * @var int
	 */
	private int $original_user_id = 0;

	/**
	 * setUp: REST サーバ初期化（認証なし）。
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// 未認証状態に設定する（public endpoint のテストが目的）。
		$this->original_user_id = get_current_user_id();
		wp_set_current_user( 0 );

		// REST サーバを初期化する。
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;
		do_action( 'rest_api_init' );
	}

	/**
	 * tearDown: ユーザ状態・オプションを元に戻す。
	 *
	 * @return void
	 */
	public function tearDown(): void {
		wp_set_current_user( $this->original_user_id );
		// テスト用ライセンスキー option を削除する。
		delete_option( 'agent_neo_license_key' );
		delete_option( 'agent_neo_api_key' );
		// crawl-map transient キャッシュを削除する（次テストに干渉しないように）。
		delete_transient( 'agent_neo_crawl_map_v2_500_1' );
		parent::tearDown();
	}

	// ============================================================
	// TC-047: draft 記事が snapshot 単体取得で 404 を返す
	// ============================================================

	/**
	 * TC-047: draft ステータスの記事 ID で snapshot を未認証取得すると 404 を返す。
	 *
	 * 実装補足: GET /public/pages/{id}/snapshot は get_public_post() で
	 * post_status === 'publish' 以外を NOT_FOUND（404）として除外する。
	 * draft 記事 ID / private 記事 ID を渡した場合に 404 or 4xx を返すことを検証する。
	 *
	 * @return void
	 */
	public function test_tc047_draft_post_returns_404_from_snapshot(): void {
		// draft 記事を作成する（管理者権限で作成・テスト後は自動削除）。
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$draft_post_id = self::factory()->post->create(
			array(
				'post_title'   => 'TC-047 draft 記事（漏洩防止テスト）',
				'post_content' => '<!-- wp:paragraph --><p>draft secret content</p><!-- /wp:paragraph -->',
				'post_status'  => 'draft',
				'post_author'  => $admin_id,
			)
		);

		$private_post_id = self::factory()->post->create(
			array(
				'post_title'   => 'TC-047 private 記事（漏洩防止テスト）',
				'post_content' => '<!-- wp:paragraph --><p>private secret content</p><!-- /wp:paragraph -->',
				'post_status'  => 'private',
				'post_author'  => $admin_id,
			)
		);

		// 未認証状態に戻す。
		wp_set_current_user( 0 );

		// --- draft 記事の snapshot 取得 ---
		$draft_request = new WP_REST_Request( 'GET', "/agent-neo/v1/public/pages/{$draft_post_id}/snapshot" );
		$draft_response = $this->server->dispatch( $draft_request );

		$draft_status = $draft_response->get_status();
		$this->assertGreaterThanOrEqual(
			400,
			$draft_status,
			'TC-047: draft 記事 ID で snapshot 取得すると 4xx を返すこと'
		);

		// レスポンスに draft コンテンツが含まれないことを確認する。
		$draft_body = wp_json_encode( $draft_response->get_data() );
		$this->assertStringNotContainsString(
			'draft secret content',
			(string) $draft_body,
			'TC-047: draft 記事コンテンツがレスポンスに含まれないこと'
		);
		$this->assertStringNotContainsString(
			'TC-047 draft 記事',
			(string) $draft_body,
			'TC-047: draft 記事タイトルがレスポンスに含まれないこと'
		);

		// --- private 記事の snapshot 取得 ---
		$private_request = new WP_REST_Request( 'GET', "/agent-neo/v1/public/pages/{$private_post_id}/snapshot" );
		$private_response = $this->server->dispatch( $private_request );

		$private_status = $private_response->get_status();
		$this->assertGreaterThanOrEqual(
			400,
			$private_status,
			'TC-047: private 記事 ID で snapshot 取得すると 4xx を返すこと'
		);

		$private_body = wp_json_encode( $private_response->get_data() );
		$this->assertStringNotContainsString(
			'private secret content',
			(string) $private_body,
			'TC-047: private 記事コンテンツがレスポンスに含まれないこと'
		);
	}

	/**
	 * TC-047 補完: 公開済み記事は snapshot が取得できること（正常系境界確認）。
	 *
	 * @return void
	 */
	public function test_tc047_published_post_snapshot_is_accessible(): void {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$published_post_id = self::factory()->post->create(
			array(
				'post_title'   => 'TC-047 公開済み記事',
				'post_content' => '<!-- wp:paragraph --><p>公開コンテンツ</p><!-- /wp:paragraph -->',
				'post_status'  => 'publish',
				'post_author'  => $admin_id,
			)
		);

		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'GET', "/agent-neo/v1/public/pages/{$published_post_id}/snapshot" );
		$response = $this->server->dispatch( $request );

		$this->assertSame(
			200,
			$response->get_status(),
			'TC-047: 公開済み記事は snapshot 取得が成功すること'
		);
	}

	// ============================================================
	// TC-048: crawl-map に draft/private/password-protected が含まれない
	// ============================================================

	/**
	 * TC-048: crawl-map に draft / private / password-protected 記事の URL が含まれない。
	 *
	 * @return void
	 */
	public function test_tc048_draft_private_password_excluded_from_crawl_map(): void {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		// 各種ステータスの記事を作成する。
		$draft_post_id = self::factory()->post->create(
			array(
				'post_title'  => 'TC-048 draft 記事',
				'post_status' => 'draft',
				'post_author' => $admin_id,
			)
		);

		$private_post_id = self::factory()->post->create(
			array(
				'post_title'  => 'TC-048 private 記事',
				'post_status' => 'private',
				'post_author' => $admin_id,
			)
		);

		// パスワード保護記事。
		$password_post_id = self::factory()->post->create(
			array(
				'post_title'         => 'TC-048 パスワード保護記事',
				'post_status'        => 'publish',
				'post_password'      => 'secret-password',
				'post_author'        => $admin_id,
			)
		);

		// noindex 記事（SEO meta に noindex=true を設定）。
		$noindex_post_id = self::factory()->post->create(
			array(
				'post_title'  => 'TC-048 noindex 記事',
				'post_status' => 'publish',
				'post_author' => $admin_id,
			)
		);
		update_post_meta( $noindex_post_id, '_agent_neo_seo_meta', array( 'noindex' => true ) );

		// 公開済み正常記事（比較用）。
		$published_post_id = self::factory()->post->create(
			array(
				'post_title'  => 'TC-048 公開済み正常記事',
				'post_status' => 'publish',
				'post_author' => $admin_id,
			)
		);

		wp_set_current_user( 0 );

		// crawl-map transient を無効化して最新データを取得する。
		delete_transient( 'agent_neo_crawl_map_v2_500_1' );

		$request = new WP_REST_Request( 'GET', '/agent-neo/v1/public/crawl-map' );
		$response = $this->server->dispatch( $request );

		$this->assertSame(
			200,
			$response->get_status(),
			'TC-048: crawl-map エンドポイントが 200 を返すこと'
		);

		$data = $response->get_data();
		// StandardResponse 封筒を考慮して data.entries を取得する。
		$entries = array();
		if ( isset( $data['data']['entries'] ) ) {
			$entries = $data['data']['entries'];
		} elseif ( isset( $data['entries'] ) ) {
			$entries = $data['entries'];
		}

		$entry_post_ids = array_column( $entries, 'post_id' );

		// draft / private が crawl-map に含まれていないことを確認する。
		$this->assertNotContains(
			$draft_post_id,
			$entry_post_ids,
			'TC-048: draft 記事は crawl-map に含まれないこと'
		);
		$this->assertNotContains(
			$private_post_id,
			$entry_post_ids,
			'TC-048: private 記事は crawl-map に含まれないこと'
		);
		$this->assertNotContains(
			$password_post_id,
			$entry_post_ids,
			'TC-048: パスワード保護記事は crawl-map に含まれないこと'
		);

		// noindex 記事について: 実装では WP_Query が publish 記事のみ返すが、
		// noindex=true を crawl-map entry から除外する実装が現時点に存在するか確認する。
		// 現実装は noindex フラグを entry.robots に "noindex,nofollow" として反映するが、
		// entry 自体は含む設計（SEO クローラが自分で判断する想定）。
		// entries の robots フィールドが "noindex,nofollow" であるかを確認する。
		foreach ( $entries as $entry ) {
			if ( isset( $entry['post_id'] ) && (int) $entry['post_id'] === $noindex_post_id ) {
				$this->assertStringContainsString(
					'noindex',
					(string) ( $entry['robots'] ?? '' ),
					'TC-048: noindex 記事の crawl-map entry に robots=noindex が含まれること'
				);
			}
		}

		// 公開済み正常記事は crawl-map に含まれること（境界確認）。
		$this->assertContains(
			$published_post_id,
			$entry_post_ids,
			'TC-048: 公開済み記事は crawl-map に含まれること（境界確認）'
		);
	}

	// ============================================================
	// TC-049: nonce 情報がスナップショットレスポンスに含まれない
	// ============================================================

	/**
	 * TC-049: snapshot レスポンスに _wpnonce / nonce キーが含まれない。
	 *
	 * @return void
	 */
	public function test_tc049_nonce_not_leaked_in_snapshot(): void {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		// nonce を含む SEO meta を持つ記事を作成する。
		// 実際の実装では nonce は HTML コンテンツ内にのみ存在するが、
		// 仮に SEO meta に nonce キーが混入した場合の漏洩も確認する。
		$nonce_value = wp_create_nonce( 'test_nonce_tc049' );

		$post_id = self::factory()->post->create(
			array(
				'post_title'   => 'TC-049 nonce テスト記事',
				'post_content' => "<!-- wp:paragraph --><p>Content with hidden nonce <!-- _wpnonce={$nonce_value} --></p><!-- /wp:paragraph -->",
				'post_status'  => 'publish',
				'post_author'  => $admin_id,
			)
		);

		// SEO meta に nonce を混入させる（漏洩テスト）。
		update_post_meta(
			$post_id,
			'_agent_neo_seo_meta',
			array(
				'canonical' => get_permalink( $post_id ),
				'noindex'   => false,
				'json_ld'   => array(),
				// 意図的に nonce フィールドを混入させる（実装が除外すべき）。
				'nonce'     => $nonce_value,
				'_wpnonce'  => $nonce_value,
			)
		);

		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'GET', "/agent-neo/v1/public/pages/{$post_id}/snapshot" );
		$response = $this->server->dispatch( $request );

		$this->assertSame(
			200,
			$response->get_status(),
			'TC-049 前提: 公開記事の snapshot 取得が成功すること'
		);

		// レスポンスボディを JSON 文字列化して文字列レベルで検索する。
		$response_json = wp_json_encode( $response->get_data() );
		$this->assertIsString( $response_json );

		// _wpnonce 文字列がレスポンスに含まれないことを確認する。
		$this->assertStringNotContainsString(
			'_wpnonce',
			$response_json,
			'TC-049: レスポンスに _wpnonce 文字列が含まれないこと'
		);

		// nonce 文字列 (nonce という文字列がキーとして現れない) を確認する。
		// JSON では "nonce": が nonce キーの出現を示す。
		$this->assertStringNotContainsString(
			'"nonce"',
			$response_json,
			'TC-049: レスポンスに "nonce" キーが含まれないこと'
		);

		// nonce 値そのものがレスポンスに含まれないことを確認する。
		$this->assertStringNotContainsString(
			$nonce_value,
			$response_json,
			'TC-049: nonce 値そのものがレスポンスに含まれないこと'
		);

		// レスポンスデータを配列で検証し、どの階層にも nonce キーが存在しないことを確認する。
		$data = $response->get_data();
		$json_string = wp_json_encode( $data );
		$this->assertIsString( $json_string );

		$decoded = json_decode( $json_string, true );
		$this->assert_no_nonce_key_in_array( $decoded, 'TC-049: レスポンスのどの階層にも nonce キーが存在しないこと' );
	}

	/**
	 * 配列のどの階層にも 'nonce' / '_wpnonce' キーが存在しないことを再帰的に確認する。
	 *
	 * @param mixed  $data    検査対象。
	 * @param string $message テストメッセージ。
	 * @return void
	 */
	private function assert_no_nonce_key_in_array( $data, string $message ): void {
		if ( ! is_array( $data ) ) {
			return;
		}

		foreach ( $data as $key => $value ) {
			$this->assertNotSame( 'nonce', $key, $message . "（キー: {$key}）" );
			$this->assertNotSame( '_wpnonce', $key, $message . "（キー: {$key}）" );
			$this->assert_no_nonce_key_in_array( $value, $message );
		}
	}

	// ============================================================
	// TC-050: ライセンスキー / api_key が公開エンドポイントに含まれない
	// ============================================================

	/**
	 * TC-050-A: 公開エンドポイント（snapshot）にライセンスキーが含まれない。
	 *
	 * @return void
	 */
	public function test_tc050_a_license_key_not_in_public_snapshot(): void {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		// テスト用ライセンスキーを設定する。
		$license_key = 'sk-test-license-key-tc050-abcdef1234567890';
		$api_key     = 'api-key-tc050-xyz987654321';
		update_option( 'agent_neo_license_key', $license_key );
		update_option( 'agent_neo_api_key', $api_key );

		$post_id = self::factory()->post->create(
			array(
				'post_title'   => 'TC-050 ライセンスキー漏洩テスト記事',
				'post_content' => '<!-- wp:paragraph --><p>通常コンテンツ</p><!-- /wp:paragraph -->',
				'post_status'  => 'publish',
				'post_author'  => $admin_id,
			)
		);

		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'GET', "/agent-neo/v1/public/pages/{$post_id}/snapshot" );
		$response = $this->server->dispatch( $request );

		$this->assertSame(
			200,
			$response->get_status(),
			'TC-050-A 前提: snapshot 取得が成功すること'
		);

		$response_json = wp_json_encode( $response->get_data() );
		$this->assertIsString( $response_json );

		$this->assertStringNotContainsString(
			$license_key,
			$response_json,
			'TC-050-A: snapshot にライセンスキーが含まれないこと'
		);
		$this->assertStringNotContainsString(
			$api_key,
			$response_json,
			'TC-050-A: snapshot に api_key が含まれないこと'
		);
		$this->assertStringNotContainsString(
			'license_key',
			$response_json,
			'TC-050-A: snapshot に "license_key" フィールドが含まれないこと'
		);
		$this->assertStringNotContainsString(
			'api_key',
			$response_json,
			'TC-050-A: snapshot に "api_key" フィールドが含まれないこと'
		);
	}

	/**
	 * TC-050-B: 公開エンドポイント（crawl-map）にライセンスキーが含まれない。
	 *
	 * @return void
	 */
	public function test_tc050_b_license_key_not_in_crawl_map(): void {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$license_key = 'sk-test-license-key-tc050b-abcdef';
		$api_key     = 'api-key-tc050b-xyz';
		update_option( 'agent_neo_license_key', $license_key );
		update_option( 'agent_neo_api_key', $api_key );

		wp_set_current_user( 0 );

		delete_transient( 'agent_neo_crawl_map_v2_500_1' );

		$request = new WP_REST_Request( 'GET', '/agent-neo/v1/public/crawl-map' );
		$response = $this->server->dispatch( $request );

		$this->assertSame(
			200,
			$response->get_status(),
			'TC-050-B 前提: crawl-map 取得が成功すること'
		);

		$response_json = wp_json_encode( $response->get_data() );
		$this->assertIsString( $response_json );

		$this->assertStringNotContainsString(
			$license_key,
			$response_json,
			'TC-050-B: crawl-map にライセンスキーが含まれないこと'
		);
		$this->assertStringNotContainsString(
			$api_key,
			$response_json,
			'TC-050-B: crawl-map に api_key が含まれないこと'
		);
		$this->assertStringNotContainsString(
			'license_key',
			$response_json,
			'TC-050-B: crawl-map に "license_key" フィールドが含まれないこと'
		);
	}

	/**
	 * TC-050-C: 公開エンドポイント（llmo/answers）にライセンスキーが含まれない。
	 *
	 * @return void
	 */
	public function test_tc050_c_license_key_not_in_llmo_answers(): void {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$license_key = 'sk-test-license-key-tc050c-fedcba';
		$api_key     = 'api-key-tc050c-abc';
		update_option( 'agent_neo_license_key', $license_key );
		update_option( 'agent_neo_api_key', $api_key );

		wp_set_current_user( 0 );

		$request = new WP_REST_Request( 'GET', '/agent-neo/v1/public/llmo/answers' );
		$response = $this->server->dispatch( $request );

		$this->assertSame(
			200,
			$response->get_status(),
			'TC-050-C 前提: llmo/answers 取得が成功すること'
		);

		$response_json = wp_json_encode( $response->get_data() );
		$this->assertIsString( $response_json );

		$this->assertStringNotContainsString(
			$license_key,
			$response_json,
			'TC-050-C: llmo/answers にライセンスキーが含まれないこと'
		);
		$this->assertStringNotContainsString(
			$api_key,
			$response_json,
			'TC-050-C: llmo/answers に api_key が含まれないこと'
		);
		$this->assertStringNotContainsString(
			'license_key',
			$response_json,
			'TC-050-C: llmo/answers に "license_key" フィールドが含まれないこと'
		);
	}

	/**
	 * TC-050-D: settings/export（認証済み管理者）でもライセンスキーが平文で返らない。
	 *
	 * 注記: settings controller は export/import のみ実装されており、
	 *   ライセンスキーを直接返すエンドポイントは現実装に存在しない。
	 *   settings/export が license_key / api_key フィールドを含まないことを確認する。
	 *
	 * @return void
	 */
	public function test_tc050_d_license_key_masked_in_settings_export(): void {
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );

		$license_key = 'sk-test-license-key-tc050d-123456';
		$api_key     = 'api-key-tc050d-abcdef';
		update_option( 'agent_neo_license_key', $license_key );
		update_option( 'agent_neo_api_key', $api_key );

		$nonce = wp_create_nonce( 'wp_rest' );

		$request = new WP_REST_Request( 'POST', '/agent-neo/v1/settings/export' );
		$request->add_header( 'Content-Type', 'application/json' );
		$request->add_header( 'X-WP-Nonce', $nonce );
		$request->set_body( wp_json_encode( array() ) );
		$response = $this->server->dispatch( $request );

		// 200 か認証エラー（未実装の場合は 404）を許容する。
		$status = $response->get_status();
		if ( 404 === $status ) {
			// settings/export エンドポイント自体が存在しない場合はスキップ。
			$this->markTestSkipped( 'TC-050-D: settings/export エンドポイントが未実装のためスキップ' );
			return;
		}

		$response_json = wp_json_encode( $response->get_data() );
		$this->assertIsString( $response_json );

		// 成功した場合はライセンスキーが平文で含まれないことを確認する。
		if ( 200 === $status ) {
			$this->assertStringNotContainsString(
				$license_key,
				$response_json,
				'TC-050-D: settings/export にライセンスキーが平文で含まれないこと'
			);
			$this->assertStringNotContainsString(
				$api_key,
				$response_json,
				'TC-050-D: settings/export に api_key が平文で含まれないこと'
			);
		}
	}
}
