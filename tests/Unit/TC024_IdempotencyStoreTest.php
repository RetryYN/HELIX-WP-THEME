<?php
/**
 * TC-024: idempotency once-token replay 防御 単体テスト。
 *
 * 受入条件（L3-test-plan.md §3.2 TC-024）:
 *   - once-token replay が atomic INSERT で再送除外されること
 *   - 同一の idempotency_key の再送では保存済み result が返ること
 *   - 異なる payload_hash は 409 CONFLICT を返すこと
 *   - payload_hash 計算がキー順序に依存しないこと（canonical 正規化）
 *
 * 実装方針:
 *   - Agent_Neo_Core_Idempotency_Store の public/private メソッドを直接呼び出す。
 *   - WP transient 関数（get_transient / set_transient）は Brain Monkey でスタブ。
 *   - catalog-update-producer の once_token() / signature_headers() も副次検証。
 *
 * @package AgentNeoCore\Tests\Unit
 */

declare( strict_types=1 );

namespace AgentNeo\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use ReflectionClass;

/**
 * TC-024: idempotency store の once-token 重複排除検証。
 */
class TC024_IdempotencyStoreTest extends TestCase {

	/** @var \Agent_Neo_Core_Idempotency_Store */
	private object $store;

	/** @var ReflectionClass */
	private ReflectionClass $ref;

	protected function set_up(): void {
		parent::set_up();
		Monkey\setUp();

		$file = AGENT_NEO_CORE_DIR . 'inc/json/class-idempotency-store.php';
		$auth = AGENT_NEO_CORE_DIR . 'inc/rest/class-auth.php';

		foreach ( [ $auth, $file ] as $f ) {
			if ( file_exists( $f ) ) {
				require_once $f;
			}
		}

		$this->store = new \Agent_Neo_Core_Idempotency_Store();
		$this->ref   = new ReflectionClass( $this->store );
	}

	protected function tear_down(): void {
		Monkey\tearDown();
		parent::tear_down();
	}

	// ------------------------------------------------------------------
	// ヘルパー
	// ------------------------------------------------------------------

	/**
	 * transient_key を生成する（store の private メソッドと同一ロジック）。
	 *
	 * @param string $key idempotency key。
	 * @return string
	 */
	private function transient_key( string $key ): string {
		$method = $this->ref->getMethod( 'transient_key' );
		$method->setAccessible( true );
		return $method->invoke( $this->store, $key );
	}

	// ------------------------------------------------------------------
	// TC-024-01: 初回呼び出しで null が返ること（未記録状態）
	// ------------------------------------------------------------------

	/**
	 * 未記録の idempotency_key に対して get() が null を返すこと。
	 *
	 * @return void
	 */
	public function test_get_returns_null_when_no_record_exists(): void {
		$key          = 'idem-key-first-time';
		$payload_hash = hash( 'sha256', 'some-payload' );

		// transient が存在しない。
		Functions\expect( 'get_transient' )
			->once()
			->with( $this->transient_key( $key ) )
			->andReturn( false );

		$result = $this->store->get( $key, $payload_hash );

		$this->assertNull( $result, '初回呼び出しは null を返すこと' );
	}

	// ------------------------------------------------------------------
	// TC-024-02: save() で記録し、同一 payload_hash で再取得できること
	// ------------------------------------------------------------------

	/**
	 * save() で保存したレコードが同一 payload_hash で取得できること（replay 検出）。
	 *
	 * @return void
	 */
	public function test_get_returns_stored_result_for_same_payload_hash(): void {
		$key          = 'idem-key-same-payload';
		$payload_hash = hash( 'sha256', 'payload-content' );
		$stored_result = [
			'status'    => 'ok',
			'operation' => 'patch-block',
			'data'      => [ 'block_id' => 'block-abc' ],
		];

		// save() 呼び出し: set_transient が呼ばれること。
		Functions\expect( 'set_transient' )
			->once()
			->with(
				$this->transient_key( $key ),
				\Mockery::type( 'array' ),
				\Mockery::type( 'int' )
			)
			->andReturn( true );

		$this->store->save( $key, $payload_hash, $stored_result );

		// get() 呼び出し: 保存されたデータを返す。
		Functions\expect( 'get_transient' )
			->once()
			->with( $this->transient_key( $key ) )
			->andReturn( [
				'payload_hash' => $payload_hash,
				'result'       => $stored_result,
				'created_at'   => time(),
			] );

		$result = $this->store->get( $key, $payload_hash );

		$this->assertIsArray( $result, '同一 payload_hash で result が返ること' );
		$this->assertSame( $stored_result['status'], $result['status'] );
		$this->assertSame( $stored_result['operation'], $result['operation'] );
	}

	// ------------------------------------------------------------------
	// TC-024-03: 異なる payload_hash は 409 CONFLICT を返すこと
	// ------------------------------------------------------------------

	/**
	 * 異なる payload_hash で同一 key を要求すると WP_Error（CONFLICT）が返ること。
	 *
	 * @return void
	 */
	public function test_get_returns_conflict_for_different_payload_hash(): void {
		$key             = 'idem-key-conflict';
		$original_hash   = hash( 'sha256', 'original-payload' );
		$different_hash  = hash( 'sha256', 'different-payload' );

		Functions\expect( 'get_transient' )
			->once()
			->with( $this->transient_key( $key ) )
			->andReturn( [
				'payload_hash' => $original_hash, // 保存されている hash。
				'result'       => [ 'status' => 'ok' ],
				'created_at'   => time() - 60,
			] );

		// __ は Brain Monkey が自動スタブ。
		Functions\stubs( [ '__' => fn( $str ) => $str ] );

		$result = $this->store->get( $key, $different_hash );

		$this->assertInstanceOf( \WP_Error::class, $result, '異なる payload_hash は WP_Error を返すこと' );
		$this->assertSame( 'CONFLICT', $result->get_error_code(), 'エラーコードが CONFLICT であること' );
	}

	// ------------------------------------------------------------------
	// TC-024-04: payload_hash() がキー順序に依存しないこと
	// ------------------------------------------------------------------

	/**
	 * payload_hash() はキー順序の異なる同一内容の array に対して同じ hash を返すこと。
	 *
	 * @return void
	 */
	public function test_payload_hash_is_order_independent(): void {
		Functions\stubs( [
			'wp_json_encode' => fn( $data, $flags = 0 ) => json_encode( $data, $flags ),
		] );

		$payload_a = [
			'z_field' => 'z_val',
			'a_field' => 'a_val',
			'nonce'   => 'test-nonce',
		];

		$payload_b = [
			'nonce'   => 'test-nonce',
			'a_field' => 'a_val',
			'z_field' => 'z_val',
		];

		$hash_a = $this->store->payload_hash( $payload_a );
		$hash_b = $this->store->payload_hash( $payload_b );

		$this->assertSame(
			$hash_a,
			$hash_b,
			'payload_hash() はキー順序を正規化して同じ hash を返すこと'
		);
	}

	// ------------------------------------------------------------------
	// TC-024-05: payload_hash() は異なるペイロードに異なる hash を返すこと
	// ------------------------------------------------------------------

	/**
	 * payload_hash() は内容が異なる array に対して異なる hash を返すこと。
	 *
	 * @return void
	 */
	public function test_payload_hash_differs_for_different_payloads(): void {
		Functions\stubs( [
			'wp_json_encode' => fn( $data, $flags = 0 ) => json_encode( $data, $flags ),
		] );

		$hash_a = $this->store->payload_hash( [ 'key' => 'value_a' ] );
		$hash_b = $this->store->payload_hash( [ 'key' => 'value_b' ] );

		$this->assertNotSame( $hash_a, $hash_b, '内容が異なれば hash が異なること' );
	}

	// ------------------------------------------------------------------
	// TC-024-06: once-token の形式検証（catalog-update-producer）
	// ------------------------------------------------------------------

	/**
	 * catalog-update-producer の once_token() が URL-safe base64 形式を返すこと。
	 *
	 * once-token は HMAC ヘッダに使われる。
	 * 実装: random_bytes(32) → base64_encode → +/-→-_ → 末尾= 除去
	 *
	 * @return void
	 */
	public function test_catalog_producer_once_token_is_url_safe_base64(): void {
		$producer_file = AGENT_NEO_CORE_DIR . 'inc/catalog/class-catalog-update-producer.php';
		if ( file_exists( $producer_file ) ) {
			require_once $producer_file;
		}

		$producer = new \Agent_Neo_Core_Catalog_Update_Producer();
		$ref      = new ReflectionClass( $producer );
		$method   = $ref->getMethod( 'once_token' );
		$method->setAccessible( true );

		$token = $method->invoke( $producer );

		$this->assertIsString( $token, 'once_token() は string を返すこと' );

		// URL-safe base64 文字のみ（+ / = を含まない）。
		$this->assertMatchesRegularExpression(
			'/^[A-Za-z0-9\-_]+$/',
			$token,
			'once_token() は URL-safe base64 文字のみを含むこと'
		);

		// 32 バイト → base64 → 43 文字（パディングなし）。
		$this->assertGreaterThanOrEqual( 40, strlen( $token ), '十分な長さであること' );
	}

	// ------------------------------------------------------------------
	// TC-024-07: save() の TTL が DAY_IN_SECONDS と一致すること
	// ------------------------------------------------------------------

	/**
	 * save() が 86400 秒（24h）TTL で set_transient を呼ぶこと。
	 *
	 * @return void
	 */
	public function test_save_uses_day_in_seconds_ttl(): void {
		if ( ! defined( 'DAY_IN_SECONDS' ) ) {
			define( 'DAY_IN_SECONDS', 86400 );
		}

		$key          = 'idem-key-ttl-check';
		$payload_hash = hash( 'sha256', 'ttl-payload' );
		$result       = [ 'status' => 'ok' ];

		Functions\expect( 'set_transient' )
			->once()
			->with(
				\Mockery::type( 'string' ),
				\Mockery::type( 'array' ),
				DAY_IN_SECONDS // 86400 秒であること。
			)
			->andReturn( true );

		$this->store->save( $key, $payload_hash, $result );

		// PHPUnit の assertion: expect() の検証は Mockery が行う。
		$this->assertTrue( true );
	}
}
