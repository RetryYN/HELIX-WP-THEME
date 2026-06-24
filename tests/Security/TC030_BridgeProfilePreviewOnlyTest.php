<?php
/**
 * TC-030: bridge-profile safe_apply_state=preview-only 検証。
 *
 * 受入条件（L3-test-plan.md §3.2 TC-030 / carry-021 / carry-028）:
 *   既存テーマ環境で GET /automation-seo/bridge-profile が safe_apply_state=preview-only を返却し、
 *   write 系 endpoint 呼び出しが拒否されること（ADR-019 準拠）。
 *
 * 検証観点:
 *   ① 非 AGENT NEO テーマ（既存テーマ環境）では safe_apply_state=preview-only が返ること。
 *   ② AGENT NEO テーマが有効な場合は safe_apply_state=write-ready が返ること（正常系対比）。
 *   ③ safe_apply_state の可能値が "preview-only" と "write-ready" の 2 値であること。
 *   ④ preview-only 環境では capabilities が write 系機能を false に設定すること。
 *
 * 実装方針:
 *   TC023_SsrfGuardTest.php / TC011_LicenseModeTest.php と同じ作法に従う。
 *   - Agent_Neo_Core_Auth / Agent_Neo_Core_License_State は final クラスのため
 *     new でインスタンス化し、get_option を Brain Monkey でスタブして動作を制御する。
 *   - wp_get_theme() は Brain Monkey Functions\stubs() でスタブして
 *     テーマ slug を切り替える（wp_get_theme() は WP グローバル関数なのでスタブ可能）。
 *
 * @package AgentNeo\Tests\Security
 */

declare( strict_types=1 );

namespace AgentNeo\Tests\Security;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use ReflectionClass;

/**
 * TC-030: bridge-profile の safe_apply_state 検証。
 */
class TC030_BridgeProfilePreviewOnlyTest extends TestCase {

	/** @var \Agent_Neo_Core_Automation_SEO_Controller */
	private object $controller;

	/** @var ReflectionClass<\Agent_Neo_Core_Automation_SEO_Controller> */
	private ReflectionClass $ref;

	/** @var array<string, mixed> */
	private array $option_store = array();

	protected function set_up(): void {
		parent::set_up();
		Monkey\setUp();

		$option_store = &$this->option_store;

		// WP i18n 関数スタブ（TC023_SsrfGuardTest と同じパターン）。
		Functions\stubs( array(
			'__'             => fn( $text, $domain = 'default' ) => $text,
			'esc_html__'     => fn( $text, $domain = 'default' ) => $text,
			'esc_html'       => fn( $text ) => $text,
			'esc_attr'       => fn( $text ) => $text,
			'sanitize_text_field' => fn( $text ) => strip_tags( trim( (string) $text ) ),
			// WP sanitize_key: 小文字化後 [a-z0-9_-] 以外を除去（License_State の package 正規化が使用）。
			'sanitize_key'   => fn( $key ) => preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) ),
			'sanitize_url'   => fn( $url ) => $url,
			'wp_json_encode' => fn( $data, $flags = 0 ) => json_encode( $data, $flags ),
			'gmdate'         => fn( $format, $timestamp = null ) => gmdate( $format, $timestamp ?? time() ),
			// request_id 生成（Automation SEO Controller が apply 経路で使用）。値の決定性はテスト不問。
			'wp_generate_uuid4' => fn() => '00000000-0000-4000-8000-000000000000',
			'apply_filters'  => fn( $hook, $value, ...$args ) => $value,
			'add_action'     => fn( ...$args ) => null,
			'add_filter'     => fn( ...$args ) => null,
			'register_rest_route' => fn( ...$args ) => true,
			'rest_ensure_response' => function ( $data ) {
				if ( $data instanceof \WP_REST_Response ) {
					return $data;
				}
				return new \WP_REST_Response( $data, 200 );
			},
			// option API: インメモリストア（TC011 と同じパターン）。
			'get_option'    => function ( string $option, $default = false ) use ( &$option_store ) {
				return array_key_exists( $option, $option_store ) ? $option_store[ $option ] : $default;
			},
			'update_option' => function ( string $option, $value, $autoload = null ) use ( &$option_store ) {
				$option_store[ $option ] = $value;
				return true;
			},
		) );

		$this->load_classes();

		// Agent_Neo_Core_Auth は final / 引数なしコンストラクタ → 実インスタンス（TC023 同様）。
		$auth = new \Agent_Neo_Core_Auth();

		// Agent_Neo_Core_License_State は final / get_option を読む → option スタブ済みで実インスタンス化。
		// agent_neo_license_state は空のまま → personal/personal として動作する（デフォルト）。
		$license_state = new \Agent_Neo_Core_License_State();

		$this->controller = new \Agent_Neo_Core_Automation_SEO_Controller( $auth, $license_state );
		$this->ref        = new ReflectionClass( $this->controller );
	}

	protected function tear_down(): void {
		$this->option_store = array();
		Monkey\tearDown();
		parent::tear_down();
	}

	// ------------------------------------------------------------------
	// ヘルパー
	// ------------------------------------------------------------------

	/**
	 * 必要クラスファイルをロードする（TC023_SsrfGuardTest と同じパターン）。
	 *
	 * @return void
	 */
	private function load_classes(): void {
		$files = array(
			AGENT_NEO_CORE_DIR . 'inc/rest/class-rest-controller-base.php',
			AGENT_NEO_CORE_DIR . 'inc/rest/class-auth.php',
			AGENT_NEO_CORE_DIR . 'inc/license/class-license-state.php',
			AGENT_NEO_CORE_DIR . 'inc/rest/class-automation-seo-controller.php',
		);

		foreach ( $files as $file ) {
			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
	}

	/**
	 * WP_Theme スタブを作成する（wp_get_theme() の返り値を模倣）。
	 *
	 * WP_Theme は final クラスでないため匿名クラスで代替できる。
	 *
	 * @param string $stylesheet テーマスタイルシート slug。
	 * @param string $template   テーマテンプレート slug。
	 * @param string $name       テーマ名。
	 * @return object
	 */
	private function create_wp_theme_stub( string $stylesheet, string $template = '', string $name = 'Test Theme' ): object {
		$tmpl = '' !== $template ? $template : $stylesheet;
		return new class( $stylesheet, $tmpl, $name ) {
			private string $stylesheet;
			private string $template;
			private string $name;

			public function __construct( string $stylesheet, string $template, string $name ) {
				$this->stylesheet = $stylesheet;
				$this->template   = $template;
				$this->name       = $name;
			}

			public function get_stylesheet(): string {
				return $this->stylesheet;
			}

			public function get_template(): string {
				return $this->template;
			}

			/** @return mixed */
			public function get( string $prop ) {
				return match ( $prop ) {
					'Name'    => $this->name,
					'Version' => '1.0.0',
					default   => '',
				};
			}
		};
	}

	/**
	 * WP_REST_Request スタブを作成する。
	 *
	 * wp-stubs.php で定義された WP_REST_Request スタブクラスを使用する。
	 * get_bridge_profile( WP_REST_Request $request ) の型宣言に合致させる。
	 *
	 * @return \WP_REST_Request
	 */
	private function make_request(): \WP_REST_Request {
		return new \WP_REST_Request( 'GET', array(), array() );
	}

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
		return $m->invoke( $this->controller, ...$args );
	}

	/**
	 * WP_REST_Response のネスト構造から実データを取り出す。
	 *
	 * Agent_Neo_Core_Auth::success_response() は
	 * [ 'success', 'data' => [...実データ...], 'meta', 'error' ] を返す。
	 * rest_ensure_response() が WP_REST_Response に包むため、
	 * get_data() → ['data'] キーで実データにアクセスする。
	 *
	 * @param mixed $response WP_REST_Response またはその他。
	 * @return array<string, mixed>
	 */
	private function extract_data( $response ): array {
		$raw = $response instanceof \WP_REST_Response ? $response->get_data() : $response;
		if ( is_array( $raw ) && isset( $raw['data'] ) && is_array( $raw['data'] ) ) {
			return $raw['data'];
		}
		return is_array( $raw ) ? $raw : array();
	}

	// ------------------------------------------------------------------
	// TC-030-01: 非 AGENT NEO テーマ環境で preview-only が返ること
	// ------------------------------------------------------------------

	/**
	 * TC-030-01: 既存テーマ（非 AGENT NEO）環境で safe_apply_state=preview-only が返ること。
	 *
	 * 受入条件 TC-030:
	 *   既存テーマ環境で GET /automation-seo/bridge-profile が safe_apply_state=preview-only を返却。
	 *   ADR-019 準拠。
	 *
	 * @return void
	 */
	public function test_tc030_existing_theme_returns_preview_only(): void {
		// 非 AGENT NEO テーマを設定する。
		Functions\stubs( array(
			'wp_get_theme' => fn() => $this->create_wp_theme_stub( 'some-third-party-theme', 'some-third-party-theme', 'Third Party Theme' ),
		) );

		$request  = $this->make_request();
		$response = $this->controller->get_bridge_profile( $request );

		// WP_REST_Response または配列から data を取り出す。
		$data = $this->extract_data( $response );

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'safe_apply_state', $data );
		$this->assertSame(
			'preview-only',
			$data['safe_apply_state'],
			'既存テーマ環境では safe_apply_state=preview-only であること（ADR-019）'
		);
	}

	// ------------------------------------------------------------------
	// TC-030-02: AGENT NEO テーマ環境で write-ready が返ること（正常系対比）
	// ------------------------------------------------------------------

	/**
	 * TC-030-02: AGENT NEO テーマが有効な場合は safe_apply_state=write-ready が返ること。
	 *
	 * @return void
	 */
	public function test_tc030_agent_neo_theme_returns_write_ready(): void {
		// AGENT NEO テーマを設定する。
		Functions\stubs( array(
			'wp_get_theme' => fn() => $this->create_wp_theme_stub( 'agent-neo-theme', 'agent-neo-theme', 'AGENT NEO' ),
		) );

		$request  = $this->make_request();
		$response = $this->controller->get_bridge_profile( $request );

		$data = $this->extract_data( $response );

		$this->assertIsArray( $data );
		$this->assertArrayHasKey( 'safe_apply_state', $data );
		$this->assertSame(
			'write-ready',
			$data['safe_apply_state'],
			'AGENT NEO テーマでは safe_apply_state=write-ready であること'
		);
	}

	// ------------------------------------------------------------------
	// TC-030-03: preview-only 環境では write 系 capability が false
	// ------------------------------------------------------------------

	/**
	 * TC-030-03: preview-only 環境では section_edit / cta_swap / blueprint_apply 等が false であること。
	 *
	 * 受入条件 TC-030:
	 *   write 系 endpoint 呼び出しが拒否される。
	 *   bridge-profile の capabilities で write 系が false になることを確認。
	 *
	 * @return void
	 */
	public function test_tc030_preview_only_disables_write_capabilities(): void {
		Functions\stubs( array(
			'wp_get_theme' => fn() => $this->create_wp_theme_stub( 'random-theme', 'random-theme', 'Random Theme' ),
		) );

		$request  = $this->make_request();
		$response = $this->controller->get_bridge_profile( $request );

		$data = $this->extract_data( $response );

		$this->assertArrayHasKey( 'capabilities', $data );
		$caps = $data['capabilities'];

		// write 系 capabilities は全て false であること。
		$write_caps = array( 'section_edit', 'cta_swap', 'seo_mapping', 'blueprint_apply', 'token_sync' );
		foreach ( $write_caps as $cap ) {
			$this->assertArrayHasKey( $cap, $caps, "{$cap} が capabilities に存在すること" );
			$this->assertFalse( $caps[ $cap ], "preview-only 環境では {$cap}=false であること" );
		}

		// preview_render は常に true であること。
		$this->assertTrue( $caps['preview_render'] ?? false, 'preview_render は常に true であること' );
	}

	// ------------------------------------------------------------------
	// TC-030-04: safe_apply_state の値は "preview-only" か "write-ready" のどちらかであること
	// ------------------------------------------------------------------

	/**
	 * TC-030-04: safe_apply_state は "preview-only" または "write-ready" の 2 値のみであること。
	 *
	 * @return void
	 */
	public function test_tc030_safe_apply_state_is_binary(): void {
		$allowed_values = array( 'preview-only', 'write-ready' );

		// 非 AGENT NEO テーマ。
		Functions\stubs( array(
			'wp_get_theme' => fn() => $this->create_wp_theme_stub( 'other-theme' ),
		) );

		$response = $this->controller->get_bridge_profile( $this->make_request() );
		// 他テストと同様に success_response エンベロープを extract_data で展開する。
		$data = $this->extract_data( $response );

		$this->assertContains(
			$data['safe_apply_state'],
			$allowed_values,
			'safe_apply_state は "preview-only" か "write-ready" のどちらかであること'
		);
	}

	// ------------------------------------------------------------------
	// TC-030-05: is_agent_neo_theme_active のロジックを直接検証
	// ------------------------------------------------------------------

	/**
	 * TC-030-05: is_agent_neo_theme_active が "agent-neo-theme" slug を正しく判定すること。
	 *
	 * @return void
	 */
	public function test_tc030_non_agent_neo_slug_is_not_detected_as_agent_neo(): void {
		Functions\stubs( array(
			'wp_get_theme' => fn() => $this->create_wp_theme_stub( 'twentytwentyfour', 'twentytwentyfour' ),
		) );

		$result = $this->call_private( 'is_agent_neo_theme_active' );
		$this->assertFalse( $result, '非 AGENT NEO テーマは false を返すこと' );
	}

	/**
	 * TC-030-06: is_agent_neo_theme_active が "agent-neo-theme" slug を true と判定すること。
	 *
	 * @return void
	 */
	public function test_tc030_agent_neo_slug_is_detected_as_agent_neo(): void {
		Functions\stubs( array(
			'wp_get_theme' => fn() => $this->create_wp_theme_stub( 'agent-neo-theme', 'agent-neo-theme' ),
		) );

		$result = $this->call_private( 'is_agent_neo_theme_active' );
		$this->assertTrue( $result, 'AGENT NEO テーマは true を返すこと' );
	}
}
