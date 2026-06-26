<?php
/**
 * detect_page_type() ユニットテスト。
 *
 * TC-PT-001〜TC-PT-005: Agent_Neo_Core_Tracking_Assets::detect_page_type() の
 * 5 分岐（home / post / lp / page / other）をカバーする。
 *
 * テスト戦略:
 *   - WP 条件関数（is_front_page / is_home / is_singular / is_page）は Brain Monkey でスタブ。
 *   - get_page_template_slug() も Brain Monkey でスタブ。
 *   - Reflection で private メソッドにアクセスし、各分岐の戻り値を検証する。
 *
 * @package AgentNeo\Tests\Unit
 */

declare( strict_types=1 );

namespace AgentNeo\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * detect_page_type() 分岐テスト。
 */
class TC_TrackingPageTypeTest extends TestCase {

	/** @var \ReflectionMethod detect_page_type の Reflection オブジェクト */
	private \ReflectionMethod $detect_method;

	/** @var object Agent_Neo_Core_Tracking_Assets のインスタンス（コンストラクタ不使用） */
	private object $assets_instance;

	/**
	 * Brain Monkey 初期化 + クラスロード + Reflection セットアップ。
	 *
	 * @return void
	 */
	protected function set_up(): void {
		parent::set_up();
		Monkey\setUp();

		// class-tracking-assets.php をロードする（ABSPATH は bootstrap-unit.php で定義済み）。
		if ( ! class_exists( 'Agent_Neo_Core_Tracking_Assets' ) ) {
			require_once AGENT_NEO_CORE_DIR . 'inc/tracking/class-tracking-assets.php';
		}

		// private メソッドへのアクセスを Reflection で取得する。
		$ref                  = new \ReflectionClass( 'Agent_Neo_Core_Tracking_Assets' );
		$this->detect_method  = $ref->getMethod( 'detect_page_type' );
		$this->detect_method->setAccessible( true );
		$this->assets_instance = $ref->newInstanceWithoutConstructor();
	}

	/**
	 * Brain Monkey 後片付け。
	 *
	 * @return void
	 */
	protected function tear_down(): void {
		Monkey\tearDown();
		parent::tear_down();
	}

	// ------------------------------------------------------------------
	// TC-PT-001: is_front_page() === true → 'home' を返す
	// ------------------------------------------------------------------

	/**
	 * is_front_page() が true の場合、'home' が返ること。
	 *
	 * @return void
	 */
	public function test_detect_page_type_front_page_returns_home(): void {
		Functions\expect( 'is_front_page' )->once()->andReturn( true );
		// is_home は呼ばれない（短絡評価）ため expect しない。

		$result = $this->detect_method->invoke( $this->assets_instance );

		$this->assertSame( 'home', $result, 'is_front_page() true → home' );
	}

	// ------------------------------------------------------------------
	// TC-PT-002: is_front_page() === false、is_home() === true → 'home' を返す
	// ------------------------------------------------------------------

	/**
	 * is_home() が true の場合、'home' が返ること。
	 *
	 * @return void
	 */
	public function test_detect_page_type_home_returns_home(): void {
		Functions\expect( 'is_front_page' )->once()->andReturn( false );
		Functions\expect( 'is_home' )->once()->andReturn( true );

		$result = $this->detect_method->invoke( $this->assets_instance );

		$this->assertSame( 'home', $result, 'is_home() true → home' );
	}

	// ------------------------------------------------------------------
	// TC-PT-003: is_singular('post') === true → 'post' を返す
	// ------------------------------------------------------------------

	/**
	 * is_singular('post') が true の場合、'post' が返ること。
	 *
	 * @return void
	 */
	public function test_detect_page_type_singular_post_returns_post(): void {
		Functions\expect( 'is_front_page' )->once()->andReturn( false );
		Functions\expect( 'is_home' )->once()->andReturn( false );
		Functions\expect( 'is_singular' )
			->once()
			->with( 'post' )
			->andReturn( true );

		$result = $this->detect_method->invoke( $this->assets_instance );

		$this->assertSame( 'post', $result, 'is_singular(post) true → post' );
	}

	// ------------------------------------------------------------------
	// TC-PT-004: is_page() === true かつ get_page_template_slug() が 'page-lp' で始まる → 'lp'
	// ------------------------------------------------------------------

	/**
	 * is_page() が true で LP テンプレートスラッグの場合、'lp' が返ること。
	 *
	 * @return void
	 */
	public function test_detect_page_type_lp_template_returns_lp(): void {
		Functions\expect( 'is_front_page' )->once()->andReturn( false );
		Functions\expect( 'is_home' )->once()->andReturn( false );
		Functions\expect( 'is_singular' )->once()->with( 'post' )->andReturn( false );
		Functions\expect( 'is_page' )->once()->andReturn( true );
		// LP テンプレートスラッグ（'page-lp' で始まる）。
		Functions\expect( 'get_page_template_slug' )->once()->andReturn( 'page-lp-service' );

		$result = $this->detect_method->invoke( $this->assets_instance );

		$this->assertSame( 'lp', $result, 'get_page_template_slug が page-lp 始まり → lp' );
	}

	// ------------------------------------------------------------------
	// TC-PT-004b: スラッグが 'page-lp' ちょうどの場合も 'lp' を返す
	// ------------------------------------------------------------------

	/**
	 * get_page_template_slug() が 'page-lp' ちょうどの場合も 'lp' が返ること。
	 *
	 * @return void
	 */
	public function test_detect_page_type_lp_template_exact_slug_returns_lp(): void {
		Functions\expect( 'is_front_page' )->once()->andReturn( false );
		Functions\expect( 'is_home' )->once()->andReturn( false );
		Functions\expect( 'is_singular' )->once()->with( 'post' )->andReturn( false );
		Functions\expect( 'is_page' )->once()->andReturn( true );
		Functions\expect( 'get_page_template_slug' )->once()->andReturn( 'page-lp' );

		$result = $this->detect_method->invoke( $this->assets_instance );

		$this->assertSame( 'lp', $result, 'get_page_template_slug が page-lp ちょうど → lp' );
	}

	// ------------------------------------------------------------------
	// TC-PT-005: is_page() === true かつ LP テンプレートでない → 'page' を返す
	// ------------------------------------------------------------------

	/**
	 * is_page() が true で LP テンプレート以外の場合、'page' が返ること。
	 *
	 * @return void
	 */
	public function test_detect_page_type_normal_page_returns_page(): void {
		Functions\expect( 'is_front_page' )->once()->andReturn( false );
		Functions\expect( 'is_home' )->once()->andReturn( false );
		Functions\expect( 'is_singular' )->once()->with( 'post' )->andReturn( false );
		Functions\expect( 'is_page' )->once()->andReturn( true );
		// 通常固定ページ（テンプレートなし）。
		Functions\expect( 'get_page_template_slug' )->once()->andReturn( '' );

		$result = $this->detect_method->invoke( $this->assets_instance );

		$this->assertSame( 'page', $result, 'is_page() true でテンプレートなし → page' );
	}

	// ------------------------------------------------------------------
	// TC-PT-005b: テンプレートスラッグが 'page-lp' で始まらない → 'page'
	// ------------------------------------------------------------------

	/**
	 * get_page_template_slug() が 'page-about' の場合、'page' が返ること。
	 *
	 * @return void
	 */
	public function test_detect_page_type_non_lp_template_returns_page(): void {
		Functions\expect( 'is_front_page' )->once()->andReturn( false );
		Functions\expect( 'is_home' )->once()->andReturn( false );
		Functions\expect( 'is_singular' )->once()->with( 'post' )->andReturn( false );
		Functions\expect( 'is_page' )->once()->andReturn( true );
		Functions\expect( 'get_page_template_slug' )->once()->andReturn( 'page-about' );

		$result = $this->detect_method->invoke( $this->assets_instance );

		$this->assertSame( 'page', $result, 'テンプレートが page-lp 始まり以外 → page' );
	}

	// ------------------------------------------------------------------
	// TC-PT-006: アーカイブページ → 'other' を返す
	// ------------------------------------------------------------------

	/**
	 * is_front_page / is_home / is_singular / is_page が全て false の場合、'other' が返ること。
	 *
	 * @return void
	 */
	public function test_detect_page_type_archive_returns_other(): void {
		Functions\expect( 'is_front_page' )->once()->andReturn( false );
		Functions\expect( 'is_home' )->once()->andReturn( false );
		Functions\expect( 'is_singular' )->once()->with( 'post' )->andReturn( false );
		Functions\expect( 'is_page' )->once()->andReturn( false );

		$result = $this->detect_method->invoke( $this->assets_instance );

		$this->assertSame( 'other', $result, '全条件 false（アーカイブ等）→ other' );
	}
}
