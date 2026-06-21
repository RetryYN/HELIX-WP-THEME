<?php
/**
 * TC-010: HMAC 署名検証 + nonce 消費ロジック単体テスト。
 *
 * 受入条件（L3-test-plan.md §3.2 TC-010）:
 *   - 不正署名で 401相当（SIGNATURE_INVALID）を返すこと
 *   - 不正 nonce（短すぎ / 形式違反）で拒否されること
 *   - 正規 HMAC 署名で受理されること
 *   - canonical_json 正規化が正しく動作すること（キー順序が逆でも同じ署名）
 *
 * 実装方針:
 *   - Agent_Neo_Core_Tracking_Controller をロードし、
 *     private メソッドを ReflectionMethod で呼び出す。
 *   - WP 関数依存 (get_option / add_option / delete_option) は
 *     Brain Monkey Functions でスタブする。
 *
 * @package AgentNeoCore\Tests\Security
 */

declare( strict_types=1 );

namespace AgentNeo\Tests\Security;

use AgentNeo\Tests\Support\WpOptionStore;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use ReflectionClass;

/**
 * TC-010: tracking controller の verify_signature / consume_nonce 検証。
 */
class TC010_HmacSignatureTest extends TestCase {

	use WpOptionStore;

	/** @var ReflectionClass<\Agent_Neo_Core_Tracking_Controller> */
	private ReflectionClass $ref;

	/** @var \Agent_Neo_Core_Tracking_Controller */
	private object $controller;

	/** テスト用 HMAC キー */
	private string $hmac_key = 'test-hmac-key-secret-1234567890ab';

	/** テスト用サイトトークン */
	private string $site_token = 'test-site-token-xyz';

	protected function set_up(): void {
		parent::set_up();
		Monkey\setUp();

		// WP i18n 関数スタブ（passthrough）。
		// __() 等は Brain Monkey の setUp() 後に stubs() で定義する必要がある。
		Functions\stubs( [
			'__'          => fn( $text, $domain = 'default' ) => $text,
			'esc_html__'  => fn( $text, $domain = 'default' ) => $text,
			'esc_html'    => fn( $text ) => $text,
			'esc_attr'    => fn( $text ) => $text,
			'wp_json_encode' => fn( $data, $options = 0 ) => json_encode( $data, $options ),
		] );

		// WP option API をインメモリストアでスタブ（get_option / add_option /
		// update_option / delete_option）。Brain Monkey の Functions\expect() を
		// 複数回 with() 違いで登録すると2回目が正しく引数マッチしない問題を回避する。
		$this->reset_option_store();
		$this->stub_wp_option_api();

		// 依存クラスを順番にロード。
		$base     = AGENT_NEO_CORE_DIR . 'inc/rest/class-rest-controller-base.php';
		$auth     = AGENT_NEO_CORE_DIR . 'inc/rest/class-auth.php';
		$ctrl     = AGENT_NEO_CORE_DIR . 'inc/rest/class-tracking-controller.php';

		foreach ( [ $base, $auth, $ctrl ] as $file ) {
			if ( file_exists( $file ) && ! $this->class_loaded_from_file( $file ) ) {
				require_once $file;
			}
		}

		// add_action は Brain Monkey で自動スタブ済み。
		$this->controller = new \Agent_Neo_Core_Tracking_Controller();
		$this->ref        = new ReflectionClass( $this->controller );
	}

	protected function tear_down(): void {
		$this->reset_option_store();
		Monkey\tearDown();
		parent::tear_down();
	}

	// ------------------------------------------------------------------
	// ヘルパー
	// ------------------------------------------------------------------

	/**
	 * private メソッドを Reflection で呼ぶ。
	 *
	 * @param string $method_name メソッド名。
	 * @param mixed  ...$args 引数。
	 * @return mixed
	 */
	private function call_private( string $method_name, ...$args ) {
		$method = $this->ref->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invoke( $this->controller, ...$args );
	}

	/**
	 * canonical_json を生成する（controller 内部と同じロジック）。
	 *
	 * @param mixed $value Value。
	 * @return string
	 */
	private function make_canonical_json( $value ): string {
		return $this->call_private( 'canonical_json', $value );
	}

	/**
	 * 正規 HMAC 署名を生成する。
	 *
	 * @param array<string,mixed> $params パラメータ（signature フィールドを除く）。
	 * @param string              $hmac_key HMAC キー。
	 * @param string              $method HTTP メソッド。
	 * @return string 64 文字 hex 署名。
	 */
	private function make_valid_signature( array $params, string $hmac_key, string $method = 'POST' ): string {
		// signature フィールドを除外した canonical_json を生成する。
		$body = $params;
		unset( $body['signature'] );
		$canonical = $this->make_canonical_json( $body );

		$payload = implode(
			'|',
			[
				$method,
				'/agent-neo/v1/tracking/event', // canonical パス固定。
				(string) $params['nonce'],
				hash( 'sha256', $canonical ),
			]
		);

		return hash_hmac( 'sha256', $payload, $hmac_key );
	}

	/**
	 * WP_REST_Request モックを作成する。
	 *
	 * @param array<string,mixed> $params JSON パラメータ。
	 * @param string              $method HTTP メソッド。
	 * @return \WP_REST_Request
	 */
	private function make_request( array $params, string $method = 'POST' ): object {
		$request = $this->createMock( \WP_REST_Request::class );
		$request->method( 'get_json_params' )->willReturn( $params );
		$request->method( 'get_method' )->willReturn( $method );
		$request->method( 'get_header' )->willReturn( '' );
		return $request;
	}

	/**
	 * ファイルがすでにロード済みかどうかを簡易判定する。
	 *
	 * @param string $file ファイルパス。
	 * @return bool
	 */
	private function class_loaded_from_file( string $file ): bool {
		// クラス名はファイル名から推測する。
		$class = 'Agent_Neo_Core_' . implode(
			'_',
			array_map( 'ucfirst', explode( '-', basename( $file, '.php' ) ) )
		);
		return class_exists( $class );
	}

	// ------------------------------------------------------------------
	// TC-010-01: 正規署名で受理されること
	// ------------------------------------------------------------------

	/**
	 * 正規の HMAC 署名（hex 形式）でエラーが起きないこと。
	 *
	 * @return void
	 */
	public function test_valid_hex_signature_is_accepted(): void {
		$nonce = 'valid-nonce-abc123';

		$params = [
			'site_token' => $this->site_token,
			'nonce'      => $nonce,
			'event_type' => 'impression',
			'section_id' => 'sec-001',
			'cta_id'     => 'cta-001',
			'variant_id' => 'var-001',
		];

		$sig           = $this->make_valid_signature( $params, $this->hmac_key );
		$params['signature'] = $sig;

		$request = $this->make_request( $params );

		$result = $this->call_private( 'verify_signature', $request, $params, $this->hmac_key );

		// WP_Error でなく string（正規署名値）が返ること。
		$this->assertIsString( $result, 'verify_signature は正規署名時に string を返すこと' );
		$this->assertSame( 64, strlen( $result ), '返される署名は SHA-256 hex（64 文字）であること' );
	}

	// ------------------------------------------------------------------
	// TC-010-02: 不正署名で SIGNATURE_INVALID エラーが返ること
	// ------------------------------------------------------------------

	/**
	 * 改ざんされた署名で WP_Error（SIGNATURE_INVALID）が返ること。
	 *
	 * @return void
	 */
	public function test_invalid_signature_returns_wp_error(): void {
		$params = [
			'site_token' => $this->site_token,
			'nonce'      => 'valid-nonce-xyz789',
			'event_type' => 'click',
			'section_id' => 'sec-002',
			'cta_id'     => 'cta-002',
			'variant_id' => 'var-002',
			'signature'  => str_repeat( 'a', 64 ), // 意図的に不正な署名。
		];

		$request = $this->make_request( $params );

		$result = $this->call_private( 'verify_signature', $request, $params, $this->hmac_key );

		$this->assertInstanceOf( \WP_Error::class, $result, '不正署名は WP_Error を返すこと' );
		$this->assertSame( 'SIGNATURE_INVALID', $result->get_error_code(), 'エラーコードが SIGNATURE_INVALID であること' );
	}

	// ------------------------------------------------------------------
	// TC-010-03: base64 形式の署名も受理されること
	// ------------------------------------------------------------------

	/**
	 * base64 形式（Automation SEO 送信形式）の HMAC 署名でも受理されること。
	 *
	 * @return void
	 */
	public function test_valid_base64_signature_is_accepted(): void {
		$nonce = 'base64-nonce-test12';

		$params = [
			'site_token' => $this->site_token,
			'nonce'      => $nonce,
			'event_type' => 'impression',
			'section_id' => 'sec-003',
			'cta_id'     => 'cta-003',
			'variant_id' => 'var-003',
		];

		// body から signature を除外して canonical を作り base64 形式の署名を生成する。
		$body      = $params;
		$canonical = $this->make_canonical_json( $body );
		$payload   = implode(
			'|',
			[
				'POST',
				'/agent-neo/v1/tracking/event',
				$nonce,
				hash( 'sha256', $canonical ),
			]
		);

		$raw_sig             = hash_hmac( 'sha256', $payload, $this->hmac_key, true );
		$params['signature'] = base64_encode( $raw_sig );

		$request = $this->make_request( $params );

		$result = $this->call_private( 'verify_signature', $request, $params, $this->hmac_key );

		$this->assertIsString( $result, 'base64 署名でも verify_signature は string を返すこと' );
	}

	// ------------------------------------------------------------------
	// TC-010-04: canonical_json のキー順序正規化
	// ------------------------------------------------------------------

	/**
	 * canonical_json はキー順序が異なる入力に対して同じ出力を返すこと。
	 *
	 * @return void
	 */
	public function test_canonical_json_normalizes_key_order(): void {
		$params_a = [
			'z_key'  => 'z_val',
			'a_key'  => 'a_val',
			'nonce'  => 'nonce-123',
		];

		$params_b = [
			'nonce'  => 'nonce-123',
			'a_key'  => 'a_val',
			'z_key'  => 'z_val',
		];

		$json_a = $this->make_canonical_json( $params_a );
		$json_b = $this->make_canonical_json( $params_b );

		$this->assertSame(
			$json_a,
			$json_b,
			'canonical_json はキー順序を正規化して同じ JSON を生成すること'
		);
	}

	// ------------------------------------------------------------------
	// TC-010-05: consume_nonce — 短すぎる nonce を拒否すること
	// ------------------------------------------------------------------

	/**
	 * 8文字未満の nonce は SIGNATURE_INVALID で拒否されること。
	 *
	 * @return void
	 */
	public function test_consume_nonce_rejects_too_short_nonce(): void {
		// option API は set_up() で stub_wp_option_api() により登録済み。
		// time / gmdate のみ追加スタブ。
		Functions\stubs( [
			'time'   => fn() => 1700000000,
			'gmdate' => fn( $f, $t = null ) => date( $f, $t ?? 1700000000 ),
		] );

		$result = $this->call_private( 'consume_nonce', $this->site_token, 'short', 'sig' );

		$this->assertInstanceOf( \WP_Error::class, $result, '短い nonce は WP_Error を返すこと' );
		$this->assertSame( 'SIGNATURE_INVALID', $result->get_error_code() );
	}

	// ------------------------------------------------------------------
	// TC-010-06: consume_nonce — 不正文字を含む nonce を拒否すること
	// ------------------------------------------------------------------

	/**
	 * 許可外文字（スペース等）を含む nonce は SIGNATURE_INVALID で拒否されること。
	 *
	 * @return void
	 */
	public function test_consume_nonce_rejects_nonce_with_invalid_chars(): void {
		// option API は set_up() で stub_wp_option_api() により登録済み。
		Functions\stubs( [
			'time'   => fn() => 1700000000,
			'gmdate' => fn( $f, $t = null ) => date( $f, $t ?? 1700000000 ),
		] );

		// スペースを含む不正 nonce（8文字以上だが文字種違反）。
		$invalid_nonce = 'invalid nonce!!';

		$result = $this->call_private( 'consume_nonce', $this->site_token, $invalid_nonce, 'sig' );

		$this->assertInstanceOf( \WP_Error::class, $result, '不正文字 nonce は WP_Error を返すこと' );
		$this->assertSame( 'SIGNATURE_INVALID', $result->get_error_code() );
	}

	// ------------------------------------------------------------------
	// TC-010-07: consume_nonce — 正規 nonce が初回受理されること
	// ------------------------------------------------------------------

	/**
	 * 正規の nonce が初回に受理され replay=false が返ること。
	 *
	 * @return void
	 */
	public function test_consume_nonce_accepts_valid_nonce_first_time(): void {
		$nonce     = 'valid-nonce-abcdef01';
		$signature = str_repeat( 'f', 64 ); // 64 文字 hex。
		$now       = 1700000000;

		// インメモリ option ストアは set_up() でリセット済み（空）。
		// 初回呼び出し: timeout_key / value_key ともに存在しない状態が自然に表現される。

		Functions\stubs( [
			'time'   => fn() => $now,
			'gmdate' => fn( $f, $t = null ) => date( $f, $t ?? $now ),
		] );

		$result = $this->call_private( 'consume_nonce', $this->site_token, $nonce, $signature );

		$this->assertIsArray( $result, '正規 nonce の初回受理は array を返すこと' );
		$this->assertFalse( $result['replay'], '初回は replay=false であること' );
		$this->assertArrayHasKey( 'event_id', $result );
		$this->assertStringStartsWith( 'evt_', $result['event_id'] );

		// インメモリストアに nonce 記録が保存されたことを確認する（状態保持検証）。
		$key       = 'agent_neo_tracking_nonce_' . hash( 'sha256', $this->site_token . '|' . $nonce );
		$value_key = '_transient_' . $key;
		$this->assertArrayHasKey( $value_key, $this->option_store, 'consume_nonce が option ストアに記録を保存したこと' );
	}

	// ------------------------------------------------------------------
	// TC-010-08: consume_nonce — 同一 nonce の再送が replay=true を返すこと
	// ------------------------------------------------------------------

	/**
	 * 同一 nonce の再送時に replay=true が返ること（二重送信検出）。
	 *
	 * @return void
	 */
	public function test_consume_nonce_detects_replay(): void {
		$nonce     = 'replay-nonce-xyz1234';
		$signature = hash( 'sha256', 'test-signature-seed' );
		$now       = 1700000000;

		$key         = 'agent_neo_tracking_nonce_' . hash( 'sha256', $this->site_token . '|' . $nonce );
		$value_key   = '_transient_' . $key;
		$timeout_key = '_transient_timeout_' . $key;

		// 既存レコード（同一 signature）をインメモリストアに事前投入する。
		// これにより「1回目の consume_nonce が既に呼ばれた後の状態」を再現する。
		$stored_event_id = 'evt_' . str_repeat( 'a', 32 );
		$this->option_store[ $timeout_key ] = $now + 600; // TTL 内。
		$this->option_store[ $value_key ]   = [
			'signature'   => $signature,
			'event_id'    => $stored_event_id,
			'accepted_at' => date( 'c', $now - 10 ),
		];

		Functions\stubs( [
			'time'   => fn() => $now,
			'gmdate' => fn( $f, $t = null ) => date( $f, $t ?? $now ),
		] );

		$result = $this->call_private( 'consume_nonce', $this->site_token, $nonce, $signature );

		$this->assertIsArray( $result, 'replay 検出時も array を返すこと' );
		$this->assertTrue( $result['replay'], '再送は replay=true であること' );
		$this->assertSame( $stored_event_id, $result['event_id'], '既存 event_id が返ること' );
	}

	// ------------------------------------------------------------------
	// TC-010-09: consume_nonce — 異なる署名の再送を拒否すること
	// ------------------------------------------------------------------

	/**
	 * 既存 nonce に対して異なる署名で送信した場合は SIGNATURE_INVALID を返すこと。
	 *
	 * @return void
	 */
	public function test_consume_nonce_rejects_replay_with_different_signature(): void {
		$nonce         = 'conflict-nonce-456xy';
		$original_sig  = hash( 'sha256', 'original-sig' );
		$different_sig = hash( 'sha256', 'different-sig' );
		$now           = 1700000000;

		$key         = 'agent_neo_tracking_nonce_' . hash( 'sha256', $this->site_token . '|' . $nonce );
		$value_key   = '_transient_' . $key;
		$timeout_key = '_transient_timeout_' . $key;

		// インメモリストアに「別署名で登録済み」の状態を事前投入する。
		$this->option_store[ $timeout_key ] = $now + 600;
		$this->option_store[ $value_key ]   = [
			'signature'   => $original_sig, // 元のリクエストとは異なる署名が保存されている。
			'event_id'    => 'evt_' . str_repeat( 'b', 32 ),
			'accepted_at' => date( 'c', $now - 5 ),
		];

		Functions\stubs( [
			'time'   => fn() => $now,
			'gmdate' => fn( $f, $t = null ) => date( $f, $t ?? $now ),
		] );

		$result = $this->call_private( 'consume_nonce', $this->site_token, $nonce, $different_sig );

		$this->assertInstanceOf( \WP_Error::class, $result, '署名不一致の replay は WP_Error を返すこと' );
		$this->assertSame( 'SIGNATURE_INVALID', $result->get_error_code() );
	}
}
