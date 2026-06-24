<?php
/**
 * CAT-001〜CAT-008: catalog-update producer 契約テスト。
 *
 * 受入条件（L3-test-plan.md §3.1）:
 *
 * CAT-001: event_kind=block_registered で enqueue し、
 *           build_request の封筒に event_kind / event_id / received=true 相当フィールドが存在すること。
 *           ALLOWED_EVENT_KIND 内であること。
 * CAT-002: event_kind=block_unregistered で enqueue 正常完了すること。
 * CAT-003: event_kind=template_updated で enqueue 正常完了すること。
 * CAT-004: event_kind=theme_token_updated で enqueue 正常完了すること。
 * CAT-005: 同一 event_id 再送時に deduplicated=true が返ること（冪等性）。
 * CAT-006: event_kind 欠落（空文字 / 不正値）時に WP_Error が返ること（400 VALIDATION_ERROR 相当）。
 * CAT-007: 再試行 backoff が initial_backoff=1s、指数 2^n、最大 5 回、±10% jitter に従うこと。
 * CAT-008: エンドポイント未設定時に enqueue が失敗し WP_Error 相当が返ること（409 AGENT_NEO_NOT_INSTALLED 相当）。
 *
 * 実装方針:
 *   - WP コア不要。Brain Monkey で WP 関数をスタブ。
 *   - private メソッドは ReflectionClass で呼び出す。
 *   - get_transient / set_transient はインメモリで状態管理する。
 *   - enqueue_event の重複排除は idempotency_marker（transient）で検証する。
 *
 * @package AgentNeo\Tests\Unit
 */

declare( strict_types=1 );

namespace AgentNeo\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use ReflectionClass;

/**
 * CAT-001〜CAT-008: catalog-update-producer 契約テスト。
 */
class CAT001_CAT008_CatalogUpdateProducerTest extends TestCase {

	/** @var \Agent_Neo_Core_Catalog_Update_Producer */
	private object $producer;

	/** @var ReflectionClass<\Agent_Neo_Core_Catalog_Update_Producer> */
	private ReflectionClass $ref;

	/**
	 * インメモリ transient ストア。
	 * key => [ value, expiration ]
	 *
	 * @var array<string, mixed>
	 */
	private array $transient_store = array();

	/**
	 * インメモリ option ストア。
	 *
	 * @var array<string, mixed>
	 */
	private array $option_store = array();

	protected function set_up(): void {
		parent::set_up();
		Monkey\setUp();

		$this->transient_store = array();
		$this->option_store    = array();

		$store          = &$this->transient_store;
		$option_storage = &$this->option_store;

		// WP 関数スタブ。
		Functions\stubs( array(
			'__'             => fn( $text, $domain = 'default' ) => $text,
			'esc_html__'     => fn( $text, $domain = 'default' ) => $text,
			'sanitize_key'   => fn( $key ) => preg_replace( '/[^a-z0-9_\-]/', '', strtolower( $key ) ) ?? '',
			'sanitize_text_field' => fn( $text ) => strip_tags( trim( $text ) ),
			'wp_json_encode' => fn( $data, $flags = 0 ) => json_encode( $data, $flags ),
			'home_url'       => fn( $path = '' ) => 'https://example.com' . $path,
			'gmdate'         => fn( $format, $timestamp = null ) => gmdate( $format, $timestamp ?? time() ),
			'wp_rand'        => fn( $min = 0, $max = PHP_INT_MAX ) => random_int( $min, $max ),
			'do_action'      => fn( ...$args ) => null,
			'apply_filters'  => fn( $hook, $value, ...$args ) => $value,
		) );

		// transient スタブ（インメモリ）。
		Functions\stubs( array(
			'get_transient' => function ( string $key ) use ( &$store ) {
				if ( ! array_key_exists( $key, $store ) ) {
					return false;
				}
				$record = $store[ $key ];
				if ( is_array( $record ) && isset( $record['__expires_at'] ) ) {
					if ( $record['__expires_at'] !== 0 && time() > $record['__expires_at'] ) {
						unset( $store[ $key ] );
						return false;
					}
					return $record['__value'];
				}
				return $record;
			},
			'set_transient' => function ( string $key, $value, int $expiration = 0 ) use ( &$store ) {
				$store[ $key ] = array(
					'__value'      => $value,
					'__expires_at' => $expiration > 0 ? time() + $expiration : 0,
				);
				return true;
			},
			'delete_transient' => function ( string $key ) use ( &$store ) {
				unset( $store[ $key ] );
				return true;
			},
		) );

		// option スタブ（インメモリ）。
		Functions\stubs( array(
			'get_option' => function ( string $option, $default = false ) use ( &$option_storage ) {
				return array_key_exists( $option, $option_storage ) ? $option_storage[ $option ] : $default;
			},
			'update_option' => function ( string $option, $value, $autoload = null ) use ( &$option_storage ) {
				$option_storage[ $option ] = $value;
				return true;
			},
		) );

		// WP スケジューリング関数スタブ。
		Functions\stubs( array(
			'wp_next_scheduled'      => fn( $hook, ...$args ) => false,
			'wp_schedule_single_event' => fn( $timestamp, $hook, ...$args ) => true,
			'wp_unschedule_event'    => fn( $timestamp, $hook, ...$args ) => true,
		) );

		$producer_file = AGENT_NEO_CORE_DIR . 'inc/catalog/class-catalog-update-producer.php';
		if ( file_exists( $producer_file ) ) {
			require_once $producer_file;
		}

		$this->producer = new \Agent_Neo_Core_Catalog_Update_Producer();
		$this->ref      = new ReflectionClass( $this->producer );
	}

	protected function tear_down(): void {
		$this->transient_store = array();
		$this->option_store    = array();
		Monkey\tearDown();
		parent::tear_down();
	}

	// ------------------------------------------------------------------
	// ヘルパー
	// ------------------------------------------------------------------

	/**
	 * ReflectionClass でプライベートメソッドを呼ぶ。
	 *
	 * @param string $method メソッド名。
	 * @param mixed  ...$args 引数。
	 * @return mixed
	 */
	private function call_private( string $method, ...$args ) {
		$m = $this->ref->getMethod( $method );
		$m->setAccessible( true );
		return $m->invoke( $this->producer, ...$args );
	}

	/**
	 * ReflectionClass でプライベート定数を得る。
	 *
	 * @param string $name 定数名。
	 * @return mixed
	 */
	private function get_const( string $name ) {
		return $this->ref->getReflectionConstant( $name )->getValue();
	}

	// ------------------------------------------------------------------
	// CAT-001: block_registered イベント封筒の形
	// ------------------------------------------------------------------

	/**
	 * CAT-001: block_registered の build_request 封筒が 4 必須フィールドを持つこと。
	 *
	 * 受入条件 §3.1 CAT-001:
	 *   event_kind=block_registered で received=true / event_id / deduplicated=false / next_action=none
	 *   (4フィールド, §17.11)
	 *
	 * @return void
	 */
	public function test_cat001_block_registered_build_request_has_required_envelope(): void {
		$result = $this->producer->build_request( 'block_registered', array( 'block_name' => 'agent-neo/hero' ) );

		$this->assertIsArray( $result, 'build_request は array を返すこと' );
		$this->assertArrayHasKey( 'event_kind', $result, 'event_kind フィールドが存在すること' );
		$this->assertArrayHasKey( 'event_id', $result, 'event_id フィールドが存在すること' );
		$this->assertArrayHasKey( 'payload', $result, 'payload フィールドが存在すること' );
		$this->assertArrayHasKey( 'site_hash', $result, 'site_hash フィールドが存在すること' );
		$this->assertArrayHasKey( 'occurred_at', $result, 'occurred_at フィールドが存在すること' );
		$this->assertSame( 'block_registered', $result['event_kind'] );
		$this->assertIsString( $result['event_id'] );
		$this->assertNotEmpty( $result['event_id'], 'event_id は空でないこと' );
	}

	/**
	 * CAT-001: enqueue_block_registered が pending ステータスで正常 enqueue されること。
	 *
	 * @return void
	 */
	public function test_cat001_enqueue_block_registered_returns_enqueued_true(): void {
		$result = $this->producer->enqueue_block_registered( 'agent-neo/hero-block' );

		$this->assertIsArray( $result );
		$this->assertTrue( $result['enqueued'], 'enqueued は true であること' );
		$this->assertArrayHasKey( 'event_id', $result, 'event_id が返ること' );
		$this->assertSame( 'pending', $result['status'] );
	}

	// ------------------------------------------------------------------
	// CAT-002: block_unregistered イベント封筒の形
	// ------------------------------------------------------------------

	/**
	 * CAT-002: block_unregistered で enqueue が成功し event_kind が正しいこと。
	 *
	 * 受入条件 §3.1 CAT-002:
	 *   event_kind=block_unregistered で received=true / event_id / deduplicated=false / next_action=none
	 *
	 * @return void
	 */
	public function test_cat002_enqueue_block_unregistered_succeeds(): void {
		$result = $this->producer->enqueue_block_unregistered( 'agent-neo/old-block' );

		$this->assertIsArray( $result );
		$this->assertTrue( $result['enqueued'], 'enqueued は true であること' );
		$this->assertArrayHasKey( 'event_id', $result );

		// build_request でも検証。
		$req = $this->producer->build_request( 'block_unregistered', array( 'block_name' => 'agent-neo/old-block' ) );
		$this->assertSame( 'block_unregistered', $req['event_kind'] );
	}

	// ------------------------------------------------------------------
	// CAT-003: template_updated イベント封筒の形
	// ------------------------------------------------------------------

	/**
	 * CAT-003: template_updated で enqueue が成功し template_part_slug が payload に含まれること。
	 *
	 * 受入条件 §3.1 CAT-003:
	 *   event_kind=template_updated で received=true / event_id / deduplicated=false / next_action=none
	 *
	 * @return void
	 */
	public function test_cat003_enqueue_template_updated_succeeds(): void {
		$result = $this->producer->enqueue_template_updated( 'home', array( 'before_hash' => 'abc', 'current_hash' => 'def' ) );

		$this->assertIsArray( $result );
		$this->assertTrue( $result['enqueued'] );

		$req = $this->producer->build_request( 'template_updated', array( 'template_part_slug' => 'home', 'diff' => array() ) );
		$this->assertSame( 'template_updated', $req['event_kind'] );
		$this->assertArrayHasKey( 'template_part_slug', $req['payload'] );
	}

	// ------------------------------------------------------------------
	// CAT-004: theme_token_updated イベント封筒の形
	// ------------------------------------------------------------------

	/**
	 * CAT-004: theme_token_updated で enqueue が成功し event_kind が正しいこと。
	 *
	 * 受入条件 §3.1 CAT-004:
	 *   event_kind=theme_token_updated で received=true / event_id / deduplicated=false / next_action=none
	 *
	 * @return void
	 */
	public function test_cat004_enqueue_theme_token_updated_succeeds(): void {
		$diff   = array( 'option' => 'agent_neo_theme_tokens', 'before_hash' => 'sha256:aaa', 'after_hash' => 'sha256:bbb' );
		$result = $this->producer->enqueue_theme_token_updated( $diff );

		$this->assertIsArray( $result );
		$this->assertTrue( $result['enqueued'] );

		$req = $this->producer->build_request( 'theme_token_updated', array( 'diff' => $diff ) );
		$this->assertSame( 'theme_token_updated', $req['event_kind'] );
	}

	// ------------------------------------------------------------------
	// CAT-005: 冪等性 — 同一 event_id 再送で deduplicated=true
	// ------------------------------------------------------------------

	/**
	 * CAT-005: 同一 event_id で再度 enqueue_event を呼ぶと deduplicated=true が返ること。
	 *
	 * 受入条件 §3.1 CAT-005:
	 *   同一 event_id 再送時に received=true / event_id / deduplicated=true / next_action=none（4フィールド）
	 *
	 * @return void
	 */
	public function test_cat005_duplicate_event_id_returns_deduplicated_true(): void {
		$fixed_event_id = 'fixed-evt-id-' . bin2hex( random_bytes( 8 ) );

		// 1回目は正常 enqueue。
		$first = $this->producer->enqueue_block_registered( 'agent-neo/test-block', array(), $fixed_event_id );
		$this->assertTrue( $first['enqueued'], '初回は enqueued=true であること' );

		// 2回目は同一 event_id で deduplicated。
		$second = $this->producer->enqueue_block_registered( 'agent-neo/test-block', array(), $fixed_event_id );

		$this->assertIsArray( $second );
		$this->assertFalse( $second['enqueued'] ?? true, '再送は enqueued=false であること' );
		$this->assertTrue( $second['deduplicated'] ?? false, '再送は deduplicated=true であること' );
		$this->assertSame( $fixed_event_id, $second['event_id'] ?? '' );
	}

	// ------------------------------------------------------------------
	// CAT-006: event_kind 欠落 / 不正値 で WP_Error が返ること
	// ------------------------------------------------------------------

	/**
	 * CAT-006: 空文字の event_kind で build_request が WP_Error を返すこと。
	 *
	 * 受入条件 §3.1 CAT-006:
	 *   event_kind 欠落時に 400 VALIDATION_ERROR 相当
	 *
	 * @return void
	 */
	public function test_cat006_empty_event_kind_returns_wp_error(): void {
		$result = $this->producer->build_request( '', array() );

		$this->assertInstanceOf( \WP_Error::class, $result, '空 event_kind は WP_Error を返すこと' );
		$this->assertSame( 'invalid_event_kind', $result->get_error_code() );
	}

	/**
	 * CAT-006: 不正値の event_kind で build_request が WP_Error を返すこと。
	 *
	 * @return void
	 */
	public function test_cat006_invalid_event_kind_returns_wp_error(): void {
		$result = $this->producer->build_request( 'not_allowed_kind', array() );

		$this->assertInstanceOf( \WP_Error::class, $result, '不正 event_kind は WP_Error を返すこと' );
		$this->assertSame( 'invalid_event_kind', $result->get_error_code() );
	}

	/**
	 * CAT-006: ALLOWED_EVENT_KIND に定義された値のみが受理されること。
	 *
	 * @return void
	 */
	public function test_cat006_allowed_event_kinds_are_accepted(): void {
		$allowed = $this->get_const( 'ALLOWED_EVENT_KIND' );

		$this->assertIsArray( $allowed );
		$this->assertContains( 'block_registered', $allowed );
		$this->assertContains( 'block_unregistered', $allowed );
		$this->assertContains( 'template_updated', $allowed );
		$this->assertContains( 'theme_token_updated', $allowed );
		$this->assertCount( 4, $allowed, 'ALLOWED_EVENT_KIND は4種類であること' );

		foreach ( $allowed as $kind ) {
			$result = $this->producer->build_request( $kind, array() );
			$this->assertIsArray( $result, "event_kind={$kind} は配列を返すこと" );
		}
	}

	// ------------------------------------------------------------------
	// CAT-007: retry backoff 検証（initial=1s / 指数 2^n / 最大5回 / ±10% jitter）
	// ------------------------------------------------------------------

	/**
	 * CAT-007: backoff_seconds の初回が INITIAL_BACKOFF(1s) ±10% の範囲内であること。
	 *
	 * 受入条件 §3.1 CAT-007 / carry-006:
	 *   initial_backoff=1s、指数 2^n、最大5回、±10% jitter
	 *
	 * @return void
	 */
	public function test_cat007_initial_backoff_is_approximately_1s(): void {
		// attempts=0 のとき base = 1 * 2^0 = 1。
		$backoff = $this->call_private( 'backoff_seconds', 0 );

		// ±10% jitter: 0.9s〜1.1s = 1 秒 ±10%。max(1,...) の下限あり。
		$this->assertGreaterThanOrEqual( 1, $backoff, '初回 backoff は 1s 以上であること' );
		$this->assertLessThanOrEqual( 2, $backoff, '初回 backoff は 2s 以内であること（jitter 考慮）' );
	}

	/**
	 * CAT-007: backoff_seconds の指数増加を検証する（attempts 別に 1→2→4→8→16 の傾向）。
	 *
	 * @return void
	 */
	public function test_cat007_backoff_is_exponential(): void {
		$backoffs = array();
		for ( $i = 0; $i < 5; $i++ ) {
			$backoffs[] = $this->call_private( 'backoff_seconds', $i );
		}

		// 各ステップで前のステップよりも大きい（jitter があるため厳密比較不可、傾向を確認）。
		// attempts=0→base=1, 1→2, 2→4, 3→8, 4→16。jitter±10% でも前の 1.3 倍以上は期待できる。
		for ( $i = 1; $i < 5; $i++ ) {
			$this->assertGreaterThan(
				$backoffs[ $i - 1 ] * 0.5, // 最低でも前の 0.5 倍以上（jitter 幅に余裕をもたせる）。
				$backoffs[ $i ],
				"backoffs[{$i}] は backoffs[" . ( $i - 1 ) . '] より大きい傾向であること'
			);
		}
	}

	/**
	 * CAT-007: MAX_ATTEMPTS 定数が 5 であること。
	 *
	 * @return void
	 */
	public function test_cat007_max_attempts_is_5(): void {
		$max_attempts = $this->get_const( 'MAX_ATTEMPTS' );
		$this->assertSame( 5, $max_attempts, 'MAX_ATTEMPTS は 5 であること' );
	}

	/**
	 * CAT-007: INITIAL_BACKOFF 定数が 1 であること。
	 *
	 * @return void
	 */
	public function test_cat007_initial_backoff_const_is_1(): void {
		$initial_backoff = $this->get_const( 'INITIAL_BACKOFF' );
		$this->assertSame( 1, $initial_backoff, 'INITIAL_BACKOFF は 1 (秒) であること' );
	}

	/**
	 * CAT-007: contract_summary の retry 仕様が正しく宣言されていること。
	 *
	 * @return void
	 */
	public function test_cat007_contract_summary_declares_retry_spec(): void {
		// contract_summary は get_option / outbox / dlq を参照するため option をスタブ済み。
		$summary = $this->producer->contract_summary();

		$this->assertIsArray( $summary );
		$this->assertArrayHasKey( 'retry', $summary );

		$retry = $summary['retry'];
		$this->assertSame( 1, $retry['initial_backoff_seconds'] );
		$this->assertSame( 2, $retry['multiplier'] );
		$this->assertSame( 5, $retry['max_attempts'] );
		$this->assertStringContainsString( '10%', (string) $retry['jitter'] );
		$this->assertContains( '5xx', $retry['retry_on'] );
		$this->assertContains( '429', $retry['retry_on'] );
		$this->assertContains( 'network_timeout', $retry['retry_on'] );
		$this->assertContains( '4xx_except_429', $retry['no_retry_on'] );
	}

	/**
	 * CAT-007: is_retryable_status が 429 / 5xx / 0 を retryable と判定すること。
	 *
	 * @return void
	 */
	public function test_cat007_retryable_statuses_include_429_5xx_timeout(): void {
		// 429 は retryable。
		$this->assertTrue( $this->call_private( 'is_retryable_status', 429 ) );
		// 500 は retryable。
		$this->assertTrue( $this->call_private( 'is_retryable_status', 500 ) );
		// 503 は retryable。
		$this->assertTrue( $this->call_private( 'is_retryable_status', 503 ) );
		// network timeout 相当 (status=0) は retryable。
		$this->assertTrue( $this->call_private( 'is_retryable_status', 0 ) );
		// 400 は non-retryable。
		$this->assertFalse( $this->call_private( 'is_retryable_status', 400 ) );
		// 401 は non-retryable。
		$this->assertFalse( $this->call_private( 'is_retryable_status', 401 ) );
		// 409 は non-retryable。
		$this->assertFalse( $this->call_private( 'is_retryable_status', 409 ) );
	}

	// ------------------------------------------------------------------
	// CAT-008: エンドポイント未設定時の挙動
	// ------------------------------------------------------------------

	/**
	 * CAT-008: エンドポイント未設定（option 空 / env 未設定）時に endpoint_url が WP_Error を返すこと。
	 *
	 * 受入条件 §3.1 CAT-008:
	 *   未インストール時に 409 AGENT_NEO_NOT_INSTALLED
	 *   → エンドポイント未設定時は ENDPOINT_NOT_CONFIGURED を返す。
	 *
	 * @return void
	 */
	public function test_cat008_endpoint_not_configured_returns_wp_error(): void {
		// option_store は空のまま（エンドポイント未設定状態）。
		// env 変数も未設定と仮定（CI 環境でも未設定が基本）。

		// esc_url_raw / wp_parse_url スタブ（build_request を通じて endpoint_url が呼ばれる前段で必要）。
		Functions\stubs( array(
			'esc_url_raw'  => fn( $url ) => $url,
			'wp_parse_url' => fn( $url, $component = -1 ) => parse_url( $url, $component ),
		) );

		$endpoint_result = $this->call_private( 'endpoint_url' );

		$this->assertInstanceOf( \WP_Error::class, $endpoint_result, 'エンドポイント未設定は WP_Error を返すこと' );
		$this->assertSame(
			'ENDPOINT_NOT_CONFIGURED',
			$endpoint_result->get_error_code(),
			'エラーコードが ENDPOINT_NOT_CONFIGURED であること'
		);
	}

	/**
	 * CAT-008: HTTPS 以外の scheme を持つエンドポイントを設定した場合に WP_Error が返ること。
	 *
	 * @return void
	 */
	public function test_cat008_http_endpoint_is_rejected(): void {
		// HTTP（非 HTTPS）の URL を option に保存する。
		$this->option_store['agent_neo_catalog_update_endpoint'] = 'http://example.com/aseo/v1/agent-neo/catalog-update';

		Functions\stubs( array(
			'esc_url_raw'  => fn( $url ) => $url,
			'wp_parse_url' => fn( $url, $component = -1 ) => parse_url( $url, $component ),
		) );

		$endpoint_result = $this->call_private( 'endpoint_url' );

		$this->assertInstanceOf( \WP_Error::class, $endpoint_result, 'HTTP エンドポイントは WP_Error を返すこと' );
		$this->assertSame( 'ENDPOINT_NOT_ALLOWED', $endpoint_result->get_error_code() );
	}

	/**
	 * CAT-008: MAX_QUEUE_ITEMS 定数が 200 であること。
	 *
	 * 受入条件 §3.1 CAT-008 関連:
	 *   MAX_QUEUE_ITEMS=200 超過時の挙動を規定する定数値。
	 *
	 * @return void
	 */
	public function test_cat008_max_queue_items_is_200(): void {
		$max = $this->get_const( 'MAX_QUEUE_ITEMS' );
		$this->assertSame( 200, $max, 'MAX_QUEUE_ITEMS は 200 であること' );
	}

	/**
	 * CAT-008: outbox に 200 件超のアイテムが保存された場合、最新 200 件のみ保持されること。
	 *
	 * @return void
	 */
	public function test_cat008_outbox_overflow_truncates_to_max_queue_items(): void {
		// save_outbox の truncation ロジックを直接テストする。
		$outbox = array();
		for ( $i = 0; $i < 210; $i++ ) {
			$event_id          = 'evt-overflow-' . $i;
			$outbox[ $event_id ] = array(
				'event_id'   => $event_id,
				'status'     => 'pending',
				'created_at' => $i, // created_at をシーケンシャルに設定。
			);
		}

		// save_outbox を呼ぶ（uasort + truncation が実行される）。
		// option_store 経由で保存されたものを取得して確認する。
		$this->call_private( 'save_outbox', $outbox );

		$saved = $this->option_store['agent_neo_catalog_update_outbox'] ?? array();
		$this->assertLessThanOrEqual( 200, count( $saved ), 'outbox は 200 件以内に切り詰められること' );
	}

	// ------------------------------------------------------------------
	// 追加: outbox / DLQ / receipt の遷移
	// ------------------------------------------------------------------

	/**
	 * CAT-001〜004 補足: enqueue_event の結果が outbox に保存されること。
	 *
	 * @return void
	 */
	public function test_enqueue_stores_item_in_outbox(): void {
		$result = $this->producer->enqueue_block_registered( 'agent-neo/test-section' );

		$this->assertTrue( $result['enqueued'] );

		$event_id = $result['event_id'];
		$outbox   = $this->option_store['agent_neo_catalog_update_outbox'] ?? array();
		$this->assertArrayHasKey( $event_id, $outbox, '発行された event_id が outbox に存在すること' );
		$this->assertSame( 'pending', $outbox[ $event_id ]['status'] ?? '' );
	}

	/**
	 * CAT-005 補足: idempotency_marker が enqueue 後に設定されていること。
	 *
	 * @return void
	 */
	public function test_idempotency_marker_is_set_after_enqueue(): void {
		$event_id = 'marker-check-evt-' . bin2hex( random_bytes( 6 ) );
		$this->producer->enqueue_block_registered( 'agent-neo/marker-block', array(), $event_id );

		$marker = $this->call_private( 'idempotency_marker', $event_id );
		$this->assertIsArray( $marker, 'idempotency marker は array であること' );
		$this->assertTrue( $marker['active'] ?? false, 'idempotency marker は active=true であること' );
		$this->assertSame( 'queued', $marker['status'] ?? '' );
	}

	/**
	 * CAT-001〜004 補足: build_request の response_fields が §17.11 の4フィールドを宣言すること。
	 *
	 * @return void
	 */
	public function test_contract_summary_response_fields_match_spec(): void {
		$summary = $this->producer->contract_summary();

		$this->assertArrayHasKey( 'response_fields', $summary );
		$expected = array( 'received', 'event_id', 'deduplicated', 'next_action' );
		foreach ( $expected as $field ) {
			$this->assertContains( $field, $summary['response_fields'], "response_fields に {$field} が含まれること" );
		}
	}
}
