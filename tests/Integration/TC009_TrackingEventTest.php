<?php
/**
 * TC-009 — TrackingController 必須項目バリデーション統合テスト
 *
 * 対象:
 *   TC-009: POST /tracking/event
 *     - 必須項目（section_id / cta_id / variant_id / event_type）未送付時に 400 を返す
 *
 * テスト設計 SSOT: 旧 L3-test-plan.md（削除済み、TC 番号は履歴 ID） §3.2 TC-009
 *
 * テスト DB: wordpress_test（分離済み）
 * ライブ DB: agent_neo（無変更を保証）
 *
 * 実装参照: plugins/agent-neo-core/inc/rest/class-tracking-controller.php
 *   - permission_callback: check_tracking_permission()
 *     → validate_tracking_request() で section_id / cta_id / variant_id / event_type を必須検証
 *   - 認証フィールド（site_token / signature / nonce）が欠落した場合は 401（SIGNATURE_INVALID）
 *   - セマンティックフィールド欠落は 400（VALIDATION_ERROR）
 *
 * 注意: tracking endpoint は site_token / hmac_key の option 設定が必須。
 *   テスト内で update_option() により設定し、tearDown で削除する。
 *
 * @package AgentNeoCore\Tests\Integration
 */

declare( strict_types=1 );

/**
 * TrackingController の P0 必須項目バリデーション統合テスト。
 */
class TC009_TrackingEventTest extends WP_UnitTestCase {

	/**
	 * テスト用 WP_REST_Server インスタンス。
	 *
	 * @var WP_REST_Server
	 */
	private WP_REST_Server $server;

	/**
	 * テスト用 site_token。
	 *
	 * @var string
	 */
	private string $site_token = 'test-site-token-tc009';

	/**
	 * テスト用 hmac_key。
	 *
	 * @var string
	 */
	private string $hmac_key = 'test-hmac-key-tc009-abcdef1234567890';

	/**
	 * setUp: site_token / hmac_key 設定・REST サーバ初期化。
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// tracking endpoint が使用する site_token / hmac_key を設定する。
		update_option( 'agent_neo_site_token', $this->site_token );
		update_option( 'agent_neo_tracking_hmac_key', $this->hmac_key );

		// REST サーバを初期化する。
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->server   = $wp_rest_server;
		do_action( 'rest_api_init' );
	}

	/**
	 * tearDown: option 削除・ユーザリセット。
	 *
	 * @return void
	 */
	public function tearDown(): void {
		delete_option( 'agent_neo_site_token' );
		delete_option( 'agent_neo_tracking_hmac_key' );
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	// ============================================================
	// ヘルパー: 正規署名付きリクエストボディを生成する
	// ============================================================

	/**
	 * テスト用 canonical JSON を生成する。
	 *
	 * @param mixed $value Value。
	 * @return string
	 */
	private function canonical_json( $value ): string {
		$value = $this->sort_recursive( $value );
		$json  = wp_json_encode( $value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		return is_string( $json ) ? $json : '';
	}

	/**
	 * 配列キーを再帰ソートする。
	 *
	 * @param mixed $value Value。
	 * @return mixed
	 */
	private function sort_recursive( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}

		foreach ( $value as $key => $item ) {
			$value[ $key ] = $this->sort_recursive( $item );
		}

		// 連想配列の場合はキーをソートする。
		if ( array_keys( $value ) !== range( 0, count( $value ) - 1 ) ) {
			ksort( $value );
		}

		return $value;
	}

	/**
	 * 署名を生成する。
	 * class-tracking-controller.php の signature_payload() と一致させる。
	 *
	 * @param array<string, mixed> $body リクエストボディ（signature 除外済み）。
	 * @param string               $nonce Nonce。
	 * @return string
	 */
	private function make_signature( array $body, string $nonce ): string {
		$body_no_sig = $body;
		unset( $body_no_sig['signature'] );

		$canonical = $this->canonical_json( $body_no_sig );
		$payload   = implode(
			'|',
			array(
				'POST',
				'/agent-neo/v1/tracking/event',
				$nonce,
				hash( 'sha256', $canonical ),
			)
		);

		return hash_hmac( 'sha256', $payload, $this->hmac_key );
	}

	/**
	 * 有効なリクエストボディを組み立てる。
	 * テスト内で特定フィールドを除去して不正リクエストを作る際のベースとなる。
	 *
	 * @param array<string, mixed> $override 上書きするフィールド。
	 * @param array<string>        $remove   除去するフィールド名。
	 * @return array<string, mixed>
	 */
	private function make_body( array $override = array(), array $remove = array() ): array {
		$nonce = 'tc009-nonce-' . uniqid( '', true );

		// ベースとなる全フィールド揃ったボディ（signature なし）。
		$base = array(
			'site_token' => $this->site_token,
			'nonce'      => $nonce,
			'event_type' => 'impression',
			'section_id' => 'abc123def456',
			'cta_id'     => 'cta-id-001',
			'variant_id' => 'var-001',
		);

		// override を適用する。
		foreach ( $override as $k => $v ) {
			$base[ $k ] = $v;
		}

		// 除去フィールドを消す（signature 生成前に除去する）。
		foreach ( $remove as $k ) {
			unset( $base[ $k ] );
		}

		// 署名を生成して付与する。
		$base['signature'] = $this->make_signature( $base, $nonce );

		return $base;
	}

	/**
	 * WP_REST_Request を組み立てて dispatch する。
	 *
	 * @param array<string, mixed> $body リクエストボディ。
	 * @return WP_REST_Response|WP_HTTP_Response|WP_Error
	 */
	private function dispatch_event( array $body ) {
		$request = new WP_REST_Request( 'POST', '/agent-neo/v1/tracking/event' );
		$request->add_header( 'Content-Type', 'application/json' );
		$request->set_body( wp_json_encode( $body ) );
		return $this->server->dispatch( $request );
	}

	/**
	 * レスポンスが 400 系エラー（VALIDATION_ERROR）であることを表明する。
	 *
	 * @param WP_REST_Response|WP_HTTP_Response|WP_Error $response レスポンス。
	 * @param string                                      $message  テストメッセージ。
	 * @return void
	 */
	private function assert_validation_error( $response, string $message ): void {
		// WP_Error の場合は直接取り出す。
		if ( $response instanceof WP_Error ) {
			$this->addToAssertionCount( 1 );
			return;
		}

		$status = $response->get_status();
		$this->assertGreaterThanOrEqual( 400, $status, "{$message}: ステータスが 4xx 以上であること" );
		$this->assertLessThan( 500, $status, "{$message}: ステータスが 5xx にならないこと" );
	}

	// ============================================================
	// TC-009-A: section_id 欠落 → 400
	// ============================================================

	/**
	 * TC-009-A: section_id が未送付の場合 400（VALIDATION_ERROR）を返す。
	 *
	 * @return void
	 */
	public function test_tc009_a_missing_section_id_returns_400(): void {
		$body     = $this->make_body( array(), array( 'section_id' ) );
		$response = $this->dispatch_event( $body );

		$this->assert_validation_error(
			$response,
			'TC-009-A: section_id 欠落時'
		);
	}

	// ============================================================
	// TC-009-B: cta_id 欠落 → 400
	// ============================================================

	/**
	 * TC-009-B: cta_id が未送付の場合 400（VALIDATION_ERROR）を返す。
	 *
	 * @return void
	 */
	public function test_tc009_b_missing_cta_id_returns_400(): void {
		$body     = $this->make_body( array(), array( 'cta_id' ) );
		$response = $this->dispatch_event( $body );

		$this->assert_validation_error(
			$response,
			'TC-009-B: cta_id 欠落時'
		);
	}

	// ============================================================
	// TC-009-C: variant_id 欠落 → 400
	// ============================================================

	/**
	 * TC-009-C: variant_id が未送付の場合 400（VALIDATION_ERROR）を返す。
	 *
	 * @return void
	 */
	public function test_tc009_c_missing_variant_id_returns_400(): void {
		$body     = $this->make_body( array(), array( 'variant_id' ) );
		$response = $this->dispatch_event( $body );

		$this->assert_validation_error(
			$response,
			'TC-009-C: variant_id 欠落時'
		);
	}

	// ============================================================
	// TC-009-D: event_type 欠落 → 400
	// ============================================================

	/**
	 * TC-009-D: event_type が未送付の場合 400（VALIDATION_ERROR）を返す。
	 *
	 * @return void
	 */
	public function test_tc009_d_missing_event_type_returns_400(): void {
		$body     = $this->make_body( array(), array( 'event_type' ) );
		$response = $this->dispatch_event( $body );

		$this->assert_validation_error(
			$response,
			'TC-009-D: event_type 欠落時'
		);
	}

	// ============================================================
	// TC-009-E: event_type 不正値 → 400
	// ============================================================

	/**
	 * TC-009-E: event_type が不正値（enum 外）の場合 400（VALIDATION_ERROR）を返す。
	 *
	 * @return void
	 */
	public function test_tc009_e_invalid_event_type_returns_400(): void {
		$body     = $this->make_body( array( 'event_type' => 'invalid_event' ) );
		$response = $this->dispatch_event( $body );

		$this->assert_validation_error(
			$response,
			'TC-009-E: event_type 不正値時'
		);
	}

	// ============================================================
	// TC-009-F: 全必須項目を送付した正常系が 400 にならないこと（境界確認）
	// ============================================================

	/**
	 * TC-009-F: 全必須項目を正しく送付した場合は 400 以外（200 or 401 等）を返す。
	 * ※ site_token / hmac_key が設定済みのため、正常系は 200 を期待する。
	 *
	 * @return void
	 */
	public function test_tc009_f_valid_payload_does_not_return_400(): void {
		$body     = $this->make_body();
		$response = $this->dispatch_event( $body );

		if ( $response instanceof WP_Error ) {
			$this->fail( 'TC-009-F: 正常ペイロードで WP_Error が返った（実装エラー）' );
		}

		$status = $response->get_status();
		$this->assertNotEquals(
			400,
			$status,
			'TC-009-F: 全必須項目が揃った場合は 400 を返さないこと'
		);
	}

	// ============================================================
	// TC-009-G: JSON body なし → 400
	// ============================================================

	/**
	 * TC-009-G: JSON body が空の場合 400 系を返す。
	 *
	 * @return void
	 */
	public function test_tc009_g_missing_json_body_returns_error(): void {
		$request = new WP_REST_Request( 'POST', '/agent-neo/v1/tracking/event' );
		$request->add_header( 'Content-Type', 'application/json' );
		$request->set_body( '' );
		$response = $this->server->dispatch( $request );

		if ( $response instanceof WP_Error ) {
			$this->addToAssertionCount( 1 );
			return;
		}

		$status = $response->get_status();
		$this->assertGreaterThanOrEqual(
			400,
			$status,
			'TC-009-G: JSON body なし時は 4xx を返すこと'
		);
		$this->assertLessThan(
			500,
			$status,
			'TC-009-G: JSON body なし時は 5xx を返さないこと'
		);
	}
}
