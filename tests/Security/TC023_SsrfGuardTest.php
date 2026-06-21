<?php
/**
 * TC-023a / TC-023b: SSRF 防御 単体テスト。
 *
 * 受入条件（L3-test-plan.md §3.2 TC-023a / TC-023b）:
 *
 * TC-023a（初回検証）:
 *   - private/loopback/link-local/metadata(169.254.169.254) アドレスを拒否する
 *   - redirect は追従しない（endpoint_url() が redirection=0 で wp_remote_post を呼ぶ）
 *
 * TC-023b（再試行時 re-resolve）:
 *   - catalog-update-producer の endpoint_url() は URL を毎回評価するため
 *     再解決先が private/loopback 等であれば拒否する
 *   - automation-seo-controller の validate_url_not_internal() も対象
 *
 * 実装方針:
 *   - Agent_Neo_Core_Automation_SEO_Controller::validate_url_not_internal()
 *     と ip_in_cidr() を Reflection で呼び出し、SSRF ガードを直接検証する。
 *   - Agent_Neo_Core_Catalog_Update_Producer::endpoint_url() も同様に検証する。
 *   - WP 関数は Brain Monkey でスタブする。
 *
 * @package AgentNeoCore\Tests\Security
 */

declare( strict_types=1 );

namespace AgentNeo\Tests\Security;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;
use ReflectionClass;

/**
 * TC-023a / TC-023b: SSRF 防御検証。
 */
class TC023_SsrfGuardTest extends TestCase {

	/** @var \Agent_Neo_Core_Automation_SEO_Controller */
	private object $aseo_ctrl;

	/** @var ReflectionClass */
	private ReflectionClass $aseo_ref;

	/** @var \Agent_Neo_Core_Catalog_Update_Producer */
	private object $producer;

	/** @var ReflectionClass */
	private ReflectionClass $producer_ref;

	protected function set_up(): void {
		parent::set_up();
		Monkey\setUp();

		// WP i18n 関数スタブ（Agent_Neo_Core_Auth::error() 内の __() 等を通過させる）。
		Functions\stubs( [
			'__'         => fn( $text, $domain = 'default' ) => $text,
			'esc_html__' => fn( $text, $domain = 'default' ) => $text,
			'esc_html'   => fn( $text ) => $text,
			'esc_attr'   => fn( $text ) => $text,
		] );

		$this->load_classes();

		// Agent_Neo_Core_Auth は final なので createMock 不可。
		// コンストラクタ引数なし・副作用なしのため実インスタンスを使う。
		$auth = new \Agent_Neo_Core_Auth();

		// Agent_Neo_Core_License_State も final。
		// state() が get_option() を呼ぶため Brain Monkey でスタブ済みの前提で実インスタンス化。
		Functions\stubs( [
			'get_option' => fn( $key, $default = false ) => $default,
		] );
		$license_state = new \Agent_Neo_Core_License_State();

		$this->aseo_ctrl  = new \Agent_Neo_Core_Automation_SEO_Controller( $auth, $license_state );
		$this->aseo_ref   = new ReflectionClass( $this->aseo_ctrl );

		$this->producer     = new \Agent_Neo_Core_Catalog_Update_Producer();
		$this->producer_ref = new ReflectionClass( $this->producer );
	}

	protected function tear_down(): void {
		Monkey\tearDown();
		parent::tear_down();
	}

	// ------------------------------------------------------------------
	// ヘルパー
	// ------------------------------------------------------------------

	private function load_classes(): void {
		$files = [
			AGENT_NEO_CORE_DIR . 'inc/rest/class-auth.php',
			AGENT_NEO_CORE_DIR . 'inc/rest/class-rest-controller-base.php',
			AGENT_NEO_CORE_DIR . 'inc/license/class-license-state.php',
			AGENT_NEO_CORE_DIR . 'inc/rest/class-automation-seo-controller.php',
			AGENT_NEO_CORE_DIR . 'inc/catalog/class-catalog-update-producer.php',
		];

		foreach ( $files as $file ) {
			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
	}

	/**
	 * aseo_ctrl の private メソッドを呼ぶ。
	 *
	 * @param string $method_name メソッド名。
	 * @param mixed  ...$args 引数。
	 * @return mixed
	 */
	private function call_aseo( string $method_name, ...$args ) {
		$method = $this->aseo_ref->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invoke( $this->aseo_ctrl, ...$args );
	}

	/**
	 * producer の private メソッドを呼ぶ。
	 *
	 * @param string $method_name メソッド名。
	 * @param mixed  ...$args 引数。
	 * @return mixed
	 */
	private function call_producer( string $method_name, ...$args ) {
		$method = $this->producer_ref->getMethod( $method_name );
		$method->setAccessible( true );
		return $method->invoke( $this->producer, ...$args );
	}

	// ===================================================================
	// TC-023a: automation-seo-controller の SSRF ガード（初回検証）
	// ===================================================================

	/**
	 * localhost を拒否すること。
	 *
	 * @return void
	 */
	public function test_aseo_ssrf_rejects_localhost(): void {
		Functions\stubs( [ 'wp_parse_url' => fn( $url ) => parse_url( $url ) ] );

		$result = $this->call_aseo( 'validate_url_not_internal', 'http://localhost/api' );

		$this->assertInstanceOf( \WP_Error::class, $result, 'localhost は拒否されること' );
	}

	/**
	 * 127.0.0.1（IPv4 ループバック）を拒否すること。
	 *
	 * @return void
	 */
	public function test_aseo_ssrf_rejects_ipv4_loopback(): void {
		Functions\stubs( [ 'wp_parse_url' => fn( $url ) => parse_url( $url ) ] );

		$result = $this->call_aseo( 'validate_url_not_internal', 'http://127.0.0.1:8000/api' );

		$this->assertInstanceOf( \WP_Error::class, $result, '127.0.0.1 は拒否されること' );
	}

	/**
	 * 127.x.x.x 全体（127.0.0.0/8）を拒否すること。
	 *
	 * @return void
	 */
	public function test_aseo_ssrf_rejects_ipv4_loopback_subnet(): void {
		Functions\stubs( [ 'wp_parse_url' => fn( $url ) => parse_url( $url ) ] );

		$result = $this->call_aseo( 'validate_url_not_internal', 'http://127.0.0.100/api' );

		$this->assertInstanceOf( \WP_Error::class, $result, '127.0.0.100 は拒否されること（127.0.0.0/8）' );
	}

	/**
	 * プライベートアドレス 10.0.0.0/8 を拒否すること。
	 *
	 * @return void
	 */
	public function test_aseo_ssrf_rejects_private_10_block(): void {
		Functions\stubs( [ 'wp_parse_url' => fn( $url ) => parse_url( $url ) ] );

		$result = $this->call_aseo( 'validate_url_not_internal', 'http://10.1.2.3/api' );

		$this->assertInstanceOf( \WP_Error::class, $result, '10.x.x.x は拒否されること' );
	}

	/**
	 * プライベートアドレス 172.16.0.0/12 を拒否すること。
	 *
	 * @return void
	 */
	public function test_aseo_ssrf_rejects_private_172_block(): void {
		Functions\stubs( [ 'wp_parse_url' => fn( $url ) => parse_url( $url ) ] );

		$result = $this->call_aseo( 'validate_url_not_internal', 'http://172.20.0.1/api' );

		$this->assertInstanceOf( \WP_Error::class, $result, '172.20.0.1 は拒否されること（172.16.0.0/12）' );
	}

	/**
	 * プライベートアドレス 192.168.0.0/16 を拒否すること。
	 *
	 * @return void
	 */
	public function test_aseo_ssrf_rejects_private_192_168_block(): void {
		Functions\stubs( [ 'wp_parse_url' => fn( $url ) => parse_url( $url ) ] );

		$result = $this->call_aseo( 'validate_url_not_internal', 'http://192.168.1.100/api' );

		$this->assertInstanceOf( \WP_Error::class, $result, '192.168.1.100 は拒否されること' );
	}

	/**
	 * リンクローカルアドレス 169.254.0.0/16（AWS metadata 等）を拒否すること。
	 *
	 * @return void
	 */
	public function test_aseo_ssrf_rejects_link_local_metadata(): void {
		Functions\stubs( [ 'wp_parse_url' => fn( $url ) => parse_url( $url ) ] );

		$result = $this->call_aseo( 'validate_url_not_internal', 'http://169.254.169.254/latest/meta-data/' );

		$this->assertInstanceOf( \WP_Error::class, $result, '169.254.169.254 は拒否されること（AWS メタデータエンドポイント）' );
	}

	/**
	 * IPv6 ループバック ::1 を拒否すること。
	 *
	 * @return void
	 */
	public function test_aseo_ssrf_rejects_ipv6_loopback(): void {
		Functions\stubs( [ 'wp_parse_url' => fn( $url ) => parse_url( $url ) ] );

		$result = $this->call_aseo( 'validate_url_not_internal', 'http://[::1]/api' );

		$this->assertInstanceOf( \WP_Error::class, $result, '::1 は拒否されること' );
	}

	/**
	 * 公開外部ドメインは受理されること（正常系）。
	 *
	 * @return void
	 */
	public function test_aseo_ssrf_allows_public_url(): void {
		Functions\stubs( [ 'wp_parse_url' => fn( $url ) => parse_url( $url ) ] );

		$result = $this->call_aseo( 'validate_url_not_internal', 'https://automation-seo.example.com/api' );

		$this->assertTrue( $result, '公開外部 URL は受理されること' );
	}

	// ===================================================================
	// TC-023a: ip_in_cidr の直接検証（境界値）
	// ===================================================================

	/**
	 * ip_in_cidr: 正確に /8 境界のアドレスが含まれること。
	 *
	 * @return void
	 */
	public function test_ip_in_cidr_ipv4_boundary(): void {
		// 10.0.0.0/8 の境界: 10.0.0.0 は含まれる / 11.0.0.0 は含まれない。
		$this->assertTrue(
			$this->call_aseo( 'ip_in_cidr', '10.0.0.0', '10.0.0.0/8' ),
			'10.0.0.0 は 10.0.0.0/8 に含まれること'
		);

		$this->assertFalse(
			$this->call_aseo( 'ip_in_cidr', '11.0.0.0', '10.0.0.0/8' ),
			'11.0.0.0 は 10.0.0.0/8 に含まれないこと'
		);
	}

	/**
	 * ip_in_cidr: IPv6 ::1/128 が正確に判定されること。
	 *
	 * @return void
	 */
	public function test_ip_in_cidr_ipv6_loopback(): void {
		$this->assertTrue(
			$this->call_aseo( 'ip_in_cidr', '::1', '::1/128' ),
			'::1 は ::1/128 に含まれること'
		);

		$this->assertFalse(
			$this->call_aseo( 'ip_in_cidr', '::2', '::1/128' ),
			'::2 は ::1/128 に含まれないこと'
		);
	}

	// ===================================================================
	// TC-023b: catalog-update-producer の endpoint_url() SSRF ガード
	// ===================================================================

	/**
	 * endpoint_url() は HTTP（非 HTTPS）エンドポイントを拒否すること。
	 *
	 * @return void
	 */
	public function test_producer_endpoint_url_rejects_http(): void {
		Functions\stubs( [
			'get_option'     => '',
			'apply_filters'  => fn( $filter, $val ) => $val,
			'esc_url_raw'    => fn( $url ) => $url,
			'wp_parse_url'   => fn( $url ) => parse_url( $url ),
		] );

		// HTTP の endpoint を環境変数で注入する。
		putenv( 'AGENT_NEO_CATALOG_UPDATE_ENDPOINT=http://evil.example.com/api' );

		$result = $this->call_producer( 'endpoint_url' );

		putenv( 'AGENT_NEO_CATALOG_UPDATE_ENDPOINT=' );

		$this->assertInstanceOf( \WP_Error::class, $result, 'HTTP エンドポイントは WP_Error を返すこと' );
		$this->assertSame( 'ENDPOINT_NOT_ALLOWED', $result->get_error_code() );
	}

	/**
	 * endpoint_url() は未設定時に ENDPOINT_NOT_CONFIGURED を返すこと。
	 *
	 * @return void
	 */
	public function test_producer_endpoint_url_returns_error_when_not_configured(): void {
		// env も option も空。
		Functions\stubs( [
			'get_option'    => '',
			'apply_filters' => fn( $filter, $val ) => $val,
			'home_url'      => fn( $path = '/' ) => 'http://localhost' . $path,
		] );

		// 環境変数を明示的にクリア。
		putenv( 'AGENT_NEO_CATALOG_UPDATE_ENDPOINT=' );
		putenv( 'AGENT_NEO_ASEO_CATALOG_UPDATE_ENDPOINT=' );
		putenv( 'AGENT_NEO_ASEO_BASE_URL=' );

		$result = $this->call_producer( 'endpoint_url' );

		$this->assertInstanceOf( \WP_Error::class, $result, '未設定は WP_Error を返すこと' );
		$this->assertSame( 'ENDPOINT_NOT_CONFIGURED', $result->get_error_code() );
	}

	/**
	 * endpoint_url() は allowlist 外ホストを ENDPOINT_NOT_ALLOWED で拒否すること。
	 *
	 * TC-023b: 再試行時に URL が再評価されるため、allowlist チェックが毎回実行される。
	 *
	 * @return void
	 */
	public function test_producer_endpoint_url_rejects_non_allowlisted_host(): void {
		Functions\stubs( [
			'get_option'    => '',
			'apply_filters' => fn( $filter, $val ) => $val,
			'esc_url_raw'   => fn( $url ) => $url,
			'wp_parse_url'  => fn( $url ) => parse_url( $url ),
		] );

		// HTTPS だが allowlist にないホスト。
		putenv( 'AGENT_NEO_CATALOG_UPDATE_ENDPOINT=https://not-allowlisted.example.com/api' );
		// allowlist は空のまま。
		putenv( 'AGENT_NEO_ASEO_ALLOWED_HOSTS=' );

		$result = $this->call_producer( 'endpoint_url' );

		putenv( 'AGENT_NEO_CATALOG_UPDATE_ENDPOINT=' );

		$this->assertInstanceOf( \WP_Error::class, $result, 'allowlist 外ホストは WP_Error を返すこと' );
		$this->assertSame( 'ENDPOINT_NOT_ALLOWED', $result->get_error_code() );
	}
}
