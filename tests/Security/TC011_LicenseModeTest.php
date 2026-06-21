<?php
/**
 * TC-011: ライセンス2モード（invalid / transient）P0 単体テスト。
 *
 * 受入条件（L3-test-plan.md §4 / §014）:
 *
 *   【invalid モード（確定的失効→即時 deny）】
 *   - ライセンスキーが invalid / expired のとき、validate_license() が
 *     WP_Error( 'FEATURE_DISABLED' ) を返すこと（HTTP 403 相当）
 *   - invalid 状態が option に保存されるとき readonly_mode=true / license_mode='invalid' が設定されること
 *   - guard_write_request() が invalid 状態の下で write route を即時 FEATURE_DISABLED（403）で拒否すること
 *   - guard_write_request() が invalid 状態の下で GET（読み取り）を通過させること
 *
 *   【transient モード（サーバ障害→grace→満了後 deny）】
 *   - ライセンスサーバが 502 を返したとき、validate_license() が grace 状態を記録し
 *     license_mode='grace' / readonly_mode=true を option に保存すること
 *   - grace 期間中は guard_write_request() が LICENSE_GRACE_PERIOD（503）で write を拒否すること
 *   - grace 期間中は GET（読み取り）を通過させること
 *   - grace 満了後は guard_write_request() が FEATURE_DISABLED（403）で write を拒否すること
 *   - grace 満了後 validate_license() を再呼び出しすると FEATURE_DISABLED を返すこと
 *
 *   【モード区別】
 *   - invalid（確定失効）と transient（grace 中）が異なるエラーコードで区別されること
 *
 * 実装方針:
 *   - Agent_Neo_Core_License_State を直接インスタンス化。
 *   - WP option API は WpOptionStore trait でインメモリスタブ。
 *   - upstream HTTP 呼び出し（wp_remote_post）は Brain Monkey Functions でスタブ。
 *   - 時刻（time / gmdate）も Brain Monkey Functions\stubs() で制御し grace 満了を再現。
 *   - WP_REST_Request は最小スタブを使用（tests/stubs/wp-stubs.php の実装）。
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
 * TC-011: ライセンス2モードの P0 検証。
 */
class TC011_LicenseModeTest extends TestCase {

	use WpOptionStore;

	/** @var \Agent_Neo_Core_License_State */
	private object $license_state;

	/** @var ReflectionClass */
	private ReflectionClass $ref;

	/**
	 * テスト用の標準 validate params。
	 *
	 * @var array<string, mixed>
	 */
	private array $base_params = array(
		'site_id'      => 'site-test-001',
		'package_id'   => 'personal-monthly',
		'product_tier' => 'personal',
		'license_key'  => 'VALID-KEY-1234',
		'refresh'      => false,
	);

	protected function set_up(): void {
		parent::set_up();
		Monkey\setUp();

		// WP i18n 関数スタブ。
		Functions\stubs( array(
			'__'          => fn( $text, $domain = 'default' ) => $text,
			'esc_html__'  => fn( $text, $domain = 'default' ) => $text,
			'sanitize_key'  => fn( $text ) => strtolower( preg_replace( '/[^a-z0-9_\-]/', '', (string) $text ) ),
			'sanitize_text_field' => fn( $text ) => (string) $text,
			'wp_json_encode'  => fn( $data, $flags = 0 ) => json_encode( $data, $flags ),
			// apply_filters は2引数目の値をそのまま返す（フィルタなし）。
			'apply_filters'   => fn( $tag, $value ) => $value,
		) );

		// WP option API をインメモリストアでスタブ。
		$this->reset_option_store();
		$this->stub_wp_option_api();

		// 依存クラスをロード。
		$auth_file    = AGENT_NEO_CORE_DIR . 'inc/rest/class-auth.php';
		$state_file   = AGENT_NEO_CORE_DIR . 'inc/license/class-license-state.php';

		if ( file_exists( $auth_file ) && ! class_exists( 'Agent_Neo_Core_Auth' ) ) {
			require_once $auth_file;
		}
		if ( file_exists( $state_file ) && ! class_exists( 'Agent_Neo_Core_License_State' ) ) {
			require_once $state_file;
		}

		$this->license_state = new \Agent_Neo_Core_License_State();
		$this->ref           = new ReflectionClass( $this->license_state );
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
	private function call_private( string $method_name, ...$args ): mixed {
		$method = $this->ref->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invoke( $this->license_state, ...$args );
	}

	/**
	 * option ストアに license state を直接投入する。
	 *
	 * @param array<string, mixed> $state 投入する state。
	 * @return void
	 */
	private function seed_state( array $state ): void {
		$this->option_store['agent_neo_license_state'] = $state;
	}

	/**
	 * option ストアから現在の保存済み license state を取得する。
	 *
	 * @return array<string, mixed>
	 */
	private function saved_state(): array {
		return $this->option_store['agent_neo_license_state'] ?? array();
	}

	/**
	 * upstream が invalid を返す設定のスタブを登録する。
	 *
	 * @return void
	 */
	private function stub_upstream_invalid(): void {
		Functions\stubs( array(
			'wp_remote_post' => fn( ...$args ) => array(
				'response' => array( 'code' => 401, 'message' => 'Unauthorized' ),
				'body'     => json_encode( array( 'valid' => false, 'status' => 'invalid' ) ),
			),
			'wp_remote_retrieve_response_code' => fn( $r ) => (int) ( $r['response']['code'] ?? 200 ),
			'wp_remote_retrieve_body' => fn( $r ) => $r['body'] ?? '',
			'is_wp_error' => fn( $v ) => $v instanceof \WP_Error,
		) );
		// license endpoint が設定されている前提。
		$this->option_store['agent_neo_license_endpoint'] = 'https://license.example.com/verify';
	}

	/**
	 * upstream が 502（サーバ障害）を返す設定のスタブを登録する。
	 *
	 * @return void
	 */
	private function stub_upstream_502(): void {
		Functions\stubs( array(
			'wp_remote_post' => fn( ...$args ) => array(
				'response' => array( 'code' => 502, 'message' => 'Bad Gateway' ),
				'body'     => '',
			),
			'wp_remote_retrieve_response_code' => fn( $r ) => (int) ( $r['response']['code'] ?? 200 ),
			'wp_remote_retrieve_body' => fn( $r ) => $r['body'] ?? '',
			'is_wp_error' => fn( $v ) => $v instanceof \WP_Error,
		) );
		$this->option_store['agent_neo_license_endpoint'] = 'https://license.example.com/verify';
	}

	/**
	 * write route 用の WP_REST_Request スタブを作る。
	 *
	 * wp-stubs.php の WP_REST_Request はシンプルな実クラスのため createMock は使わず
	 * 直接インスタンス化して route プロパティを公開スロットに注入する。
	 * patchwork が get_route を final/不在として mock 拒否するため、
	 * WP_REST_Request のサブクラスとして匿名クラスで route を返す。
	 *
	 * @param string $method HTTP メソッド。
	 * @param string $route  ルート文字列。
	 * @return \WP_REST_Request
	 */
	private function make_write_request( string $method = 'POST', string $route = '/agent-neo/v1/actions/apply' ): \WP_REST_Request {
		return new class( $method, array(), array() ) extends \WP_REST_Request {
			private string $_route;
			private string $_method_override;

			public function init( string $method, string $route ): static {
				$this->_method_override = $method;
				$this->_route           = $route;
				return $this;
			}

			public function get_method(): string {
				return $this->_method_override;
			}

			public function get_route(): string {
				return $this->_route;
			}
		};
	}

	/**
	 * make_write_request のファクトリラッパー（method+route を受け取る）。
	 *
	 * @param string $method HTTP メソッド。
	 * @param string $route  ルート文字列。
	 * @return \WP_REST_Request
	 */
	private function build_request( string $method, string $route ): \WP_REST_Request {
		$req = new class( $method, array(), array() ) extends \WP_REST_Request {
			public string $_route          = '';
			public string $_method_override = '';

			public function get_method(): string { return $this->_method_override; }
			public function get_route(): string  { return $this->_route; }
		};
		$req->_method_override = $method;
		$req->_route           = $route;
		return $req;
	}

	/**
	 * 現在時刻を固定する。
	 *
	 * @param int $now Unix timestamp。
	 * @return void
	 */
	private function freeze_time( int $now ): void {
		Functions\stubs( array(
			'time'   => fn() => $now,
			'gmdate' => fn( $f, $t = null ) => gmdate( $f, $t ?? $now ),
		) );
	}

	// ==================================================================
	// ■ INVALID モード（確定的失効→即時 deny）
	// ==================================================================

	/**
	 * TC-011-01: upstream が invalid を返したとき validate_license() が
	 * FEATURE_DISABLED WP_Error を返すこと。
	 *
	 * @return void
	 */
	public function test_validate_license_returns_feature_disabled_on_invalid(): void {
		$now = 1700000000;
		$this->freeze_time( $now );
		$this->stub_upstream_invalid();

		$result = $this->license_state->validate_license( $this->base_params );

		$this->assertInstanceOf( \WP_Error::class, $result,
			'invalid ライセンスは WP_Error を返すこと' );
		$this->assertSame( 'FEATURE_DISABLED', $result->get_error_code(),
			'エラーコードが FEATURE_DISABLED であること' );

		// HTTP status が 403 に対応すること。
		$data = $result->get_error_data();
		$this->assertSame( 403, $data['status'],
			'FEATURE_DISABLED は HTTP 403 に対応すること' );
	}

	/**
	 * TC-011-02: invalid 状態が option に正しく保存されること
	 * （license_mode='invalid', readonly_mode=true）。
	 *
	 * @return void
	 */
	public function test_invalid_license_persists_correct_state(): void {
		$now = 1700000000;
		$this->freeze_time( $now );
		$this->stub_upstream_invalid();

		// validate_license() を呼ぶことで record_invalid_license() が発火する。
		$this->license_state->validate_license( $this->base_params );

		$state = $this->saved_state();

		$this->assertNotEmpty( $state,
			'license state が option に保存されること' );
		$this->assertSame( 'invalid', $state['license_mode'],
			'license_mode が "invalid" であること' );
		$this->assertTrue( (bool) $state['readonly_mode'],
			'readonly_mode が true であること' );
		$this->assertSame( 'personal', $state['package'],
			'invalid 後は personal package に縮退すること' );
	}

	/**
	 * TC-011-03: invalid 状態のとき guard_write_request() が write route を
	 * FEATURE_DISABLED（403）で即時拒否すること。
	 *
	 * @return void
	 */
	public function test_guard_write_request_denies_on_invalid_state(): void {
		$now = 1700000000;
		$this->freeze_time( $now );

		// invalid 状態を直接 option ストアに投入する。
		$this->seed_state( array(
			'license_mode'  => 'invalid',
			'readonly_mode' => true,
			'reason'        => 'license_invalid',
			'grace_started_at' => null,
			'grace_expires_at' => null,
		) );

		$request  = $this->build_request( 'POST', '/agent-neo/v1/actions/apply' );
		$handler  = array();

		$result = $this->license_state->guard_write_request( null, $handler, $request );

		$this->assertInstanceOf( \WP_Error::class, $result,
			'invalid 状態では write route が WP_Error を返すこと' );
		$this->assertSame( 'FEATURE_DISABLED', $result->get_error_code(),
			'エラーコードが FEATURE_DISABLED（403）であること' );
	}

	/**
	 * TC-011-04: invalid 状態でも GET（読み取り）リクエストは通過すること。
	 *
	 * guard_write_request は write method のみ対象。GET は null を返し通過する。
	 *
	 * @return void
	 */
	public function test_guard_write_request_passes_get_on_invalid_state(): void {
		$now = 1700000000;
		$this->freeze_time( $now );

		$this->seed_state( array(
			'license_mode'  => 'invalid',
			'readonly_mode' => true,
			'reason'        => 'license_invalid',
			'grace_started_at' => null,
			'grace_expires_at' => null,
		) );

		// GET リクエストは guard 対象外。
		$request = $this->build_request( 'GET', '/agent-neo/v1/pages/list' );
		$handler = array();

		$result = $this->license_state->guard_write_request( null, $handler, $request );

		$this->assertNull( $result,
			'GET リクエストは guard を通過して null が返ること（= 次のハンドラに委譲）' );
	}

	// ==================================================================
	// ■ TRANSIENT モード（サーバ障害→grace→満了後 deny）
	// ==================================================================

	/**
	 * TC-011-05: upstream が 502 を返したとき grace 状態が記録されること
	 * （license_mode='grace', readonly_mode=true）。
	 *
	 * 初回失敗（先行する valid 記録なし）は LICENSE_GATEWAY_ERROR を返し、
	 * grace state を option に保存すること。
	 *
	 * @return void
	 */
	public function test_transient_502_records_grace_state(): void {
		$now = 1700000000;
		$this->freeze_time( $now );
		$this->stub_upstream_502();

		// 初回 502 呼び出し（先行 valid record なし）。
		$this->license_state->validate_license( $this->base_params );

		$state = $this->saved_state();

		$this->assertNotEmpty( $state,
			'grace state が option に保存されること' );
		$this->assertSame( 'grace', $state['license_mode'],
			'license_mode が "grace" であること' );
		$this->assertTrue( (bool) $state['readonly_mode'],
			'readonly_mode が true であること' );
		$this->assertNotNull( $state['grace_expires_at'],
			'grace_expires_at が設定されること' );

		// grace 開始直後なので grace_expires_at = now + 48h であること。
		$grace_expires = strtotime( $state['grace_expires_at'] );
		$expected      = $now + ( 48 * HOUR_IN_SECONDS );
		$this->assertSame( $expected, $grace_expires,
			'grace_expires_at が現在 + 48h であること' );
	}

	/**
	 * TC-011-06: grace 期間中に guard_write_request() が write route を
	 * LICENSE_GRACE_PERIOD（503）で拒否すること。
	 *
	 * @return void
	 */
	public function test_guard_write_request_returns_503_during_grace(): void {
		$now = 1700000000;
		$this->freeze_time( $now );

		// grace 期間中の状態を直接投入。
		$this->seed_state( array(
			'license_mode'    => 'grace',
			'readonly_mode'   => true,
			'reason'          => 'license_unreachable',
			'grace_started_at' => gmdate( DATE_ATOM, $now - 3600 ),
			'grace_expires_at' => gmdate( DATE_ATOM, $now + ( 47 * HOUR_IN_SECONDS ) ),
		) );

		$request = $this->build_request( 'POST', '/agent-neo/v1/actions/apply' );
		$handler = array();

		$result = $this->license_state->guard_write_request( null, $handler, $request );

		$this->assertInstanceOf( \WP_Error::class, $result,
			'grace 中は write route が WP_Error を返すこと' );
		$this->assertSame( 'LICENSE_GRACE_PERIOD', $result->get_error_code(),
			'エラーコードが LICENSE_GRACE_PERIOD（503）であること（transient モード固有）' );

		// HTTP status 503 に対応すること。
		$data = $result->get_error_data();
		$this->assertSame( 503, $data['status'],
			'LICENSE_GRACE_PERIOD は HTTP 503 に対応すること' );

		// grace_remaining_hours が data に含まれること。
		$this->assertArrayHasKey( 'grace_remaining_hours', $data['error']['details'],
			'grace_remaining_hours が details に含まれること' );
		$this->assertGreaterThan( 0, $data['error']['details']['grace_remaining_hours'],
			'grace_remaining_hours が正の整数であること' );
	}

	/**
	 * TC-011-07: grace 期間中は GET（読み取り）リクエストが通過すること。
	 *
	 * @return void
	 */
	public function test_guard_write_request_passes_get_during_grace(): void {
		$now = 1700000000;
		$this->freeze_time( $now );

		$this->seed_state( array(
			'license_mode'    => 'grace',
			'readonly_mode'   => true,
			'reason'          => 'license_unreachable',
			'grace_started_at' => gmdate( DATE_ATOM, $now - 3600 ),
			'grace_expires_at' => gmdate( DATE_ATOM, $now + ( 47 * HOUR_IN_SECONDS ) ),
		) );

		$request = $this->build_request( 'GET', '/agent-neo/v1/pages/list' );
		$handler = array();

		$result = $this->license_state->guard_write_request( null, $handler, $request );

		$this->assertNull( $result,
			'grace 中でも GET は通過して null が返ること' );
	}

	/**
	 * TC-011-08: grace 満了後に guard_write_request() が
	 * FEATURE_DISABLED（403）で write を拒否すること。
	 *
	 * grace 満了後は is_license_denied() が true を返すため
	 * LICENSE_GRACE_PERIOD ではなく FEATURE_DISABLED になること。
	 *
	 * @return void
	 */
	public function test_guard_write_request_returns_403_after_grace_expires(): void {
		$now = 1700000000;

		// grace が満了した状態（grace_expires_at < now）を投入。
		// ただし license_mode はまだ 'grace' のまま（満了を validate して更新していない段階）。
		$this->seed_state( array(
			'license_mode'    => 'grace',
			'readonly_mode'   => true,
			'reason'          => 'license_unreachable',
			'grace_started_at' => gmdate( DATE_ATOM, $now - ( 49 * HOUR_IN_SECONDS ) ),
			'grace_expires_at' => gmdate( DATE_ATOM, $now - HOUR_IN_SECONDS ), // 1h 前に満了。
		) );

		// 時刻を grace 満了後に固定。
		$this->freeze_time( $now );

		$request = $this->build_request( 'POST', '/agent-neo/v1/actions/apply' );
		$handler = array();

		$result = $this->license_state->guard_write_request( null, $handler, $request );

		$this->assertInstanceOf( \WP_Error::class, $result,
			'grace 満了後は write route が WP_Error を返すこと' );
		$this->assertSame( 'FEATURE_DISABLED', $result->get_error_code(),
			'grace 満了後は FEATURE_DISABLED（403）になること（503 ではない）' );

		$data = $result->get_error_data();
		$this->assertSame( 403, $data['status'],
			'grace 満了後の HTTP status は 403 であること' );
	}

	/**
	 * TC-011-09: grace 満了後に validate_license() を呼ぶと
	 * FEATURE_DISABLED を返し license_mode が 'invalid' に移行すること。
	 *
	 * @return void
	 */
	public function test_validate_license_after_grace_expires_returns_feature_disabled(): void {
		$now = 1700000000;
		$this->freeze_time( $now );
		$this->stub_upstream_502();

		// grace が満了済みの状態を事前投入（grace_started_at は 49h 前）。
		$this->seed_state( array(
			'license_mode'    => 'grace',
			'readonly_mode'   => true,
			'reason'          => 'license_unreachable',
			'failure_count'   => 3,
			'last_valid_at'   => gmdate( DATE_ATOM, $now - ( 50 * HOUR_IN_SECONDS ) ),
			'grace_started_at' => gmdate( DATE_ATOM, $now - ( 49 * HOUR_IN_SECONDS ) ),
			'grace_expires_at' => gmdate( DATE_ATOM, $now - HOUR_IN_SECONDS ), // 満了済み。
		) );

		// 再度 upstream 失敗 → grace 満了なので record_transient_failure が
		// invalid へ遷移させ FEATURE_DISABLED を返すこと。
		$result = $this->license_state->validate_license( $this->base_params );

		$this->assertInstanceOf( \WP_Error::class, $result,
			'grace 満了後の validate_license は WP_Error を返すこと' );
		$this->assertSame( 'FEATURE_DISABLED', $result->get_error_code(),
			'grace 満了後は FEATURE_DISABLED であること（LICENSE_GATEWAY_ERROR ではない）' );

		// option が 'invalid' mode に更新されていること。
		$state = $this->saved_state();
		$this->assertSame( 'invalid', $state['license_mode'],
			'grace 満了後は license_mode が "invalid" に移行すること' );
		$this->assertSame( 'grace_expired', $state['reason'],
			'理由が grace_expired であること' );
	}

	// ==================================================================
	// ■ モード区別（invalid vs transient の違いを明示）
	// ==================================================================

	/**
	 * TC-011-10: invalid モードと transient（grace 中）モードは
	 * 異なるエラーコードで区別されること。
	 *
	 * invalid → FEATURE_DISABLED（403）
	 * grace 中 → LICENSE_GRACE_PERIOD（503）
	 *
	 * @return void
	 */
	public function test_invalid_and_grace_modes_produce_different_error_codes(): void {
		$now     = 1700000000;
		$handler = array();

		$this->freeze_time( $now );

		// --- invalid 状態での応答 ---
		$this->seed_state( array(
			'license_mode'    => 'invalid',
			'readonly_mode'   => true,
			'reason'          => 'license_invalid',
			'grace_started_at' => null,
			'grace_expires_at' => null,
		) );

		$request        = $this->build_request( 'POST', '/agent-neo/v1/actions/apply' );
		$invalid_result = $this->license_state->guard_write_request( null, $handler, $request );

		// --- grace 中の状態での応答 ---
		$this->seed_state( array(
			'license_mode'    => 'grace',
			'readonly_mode'   => true,
			'reason'          => 'license_unreachable',
			'grace_started_at' => gmdate( DATE_ATOM, $now - 3600 ),
			'grace_expires_at' => gmdate( DATE_ATOM, $now + ( 47 * HOUR_IN_SECONDS ) ),
		) );

		$grace_result = $this->license_state->guard_write_request( null, $handler, $request );

		$this->assertInstanceOf( \WP_Error::class, $invalid_result,
			'invalid 状態は WP_Error を返すこと' );
		$this->assertInstanceOf( \WP_Error::class, $grace_result,
			'grace 状態は WP_Error を返すこと' );

		$this->assertNotSame(
			$invalid_result->get_error_code(),
			$grace_result->get_error_code(),
			'invalid と grace は異なるエラーコードであること（モード区別）'
		);

		$this->assertSame( 'FEATURE_DISABLED', $invalid_result->get_error_code(),
			'invalid は FEATURE_DISABLED' );
		$this->assertSame( 'LICENSE_GRACE_PERIOD', $grace_result->get_error_code(),
			'grace は LICENSE_GRACE_PERIOD' );

		// HTTP status も別であること。
		$invalid_data = $invalid_result->get_error_data();
		$grace_data   = $grace_result->get_error_data();
		$this->assertSame( 403, $invalid_data['status'], 'invalid は HTTP 403' );
		$this->assertSame( 503, $grace_data['status'], 'grace は HTTP 503' );
	}
}
