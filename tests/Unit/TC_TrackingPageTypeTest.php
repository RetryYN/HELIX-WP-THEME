<?php
/**
 * detect_page_type() ユニットテスト。
 *
 * TC-PT-001〜TC-PT-005: Agent_Neo_Core_Tracking_Assets::detect_page_type() の
 * 5 分岐（home / post / lp / page / other）をカバーする。
 *
 * 判定順（修正後）:
 *   1. is_page() かつ LP テンプレート（'page-lp' で始まる）→ 'lp'  ← LP-as-frontpage 対応
 *   2. is_front_page() || is_home()                          → 'home'
 *   3. is_singular('post')                                   → 'post'
 *   4. is_page()（LP テンプレートなし）                      → 'page'
 *   5. 上記以外                                              → 'other'
 *
 * テスト戦略:
 *   - WP 条件関数（is_front_page / is_home / is_singular / is_page）は Brain Monkey でスタブ。
 *   - get_page_template_slug() / get_queried_object_id() も Brain Monkey でスタブ。
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
	// TC-PT-001: is_front_page() === true（固定ページではないトップ）→ 'home' を返す
	//
	// 判定順変更後: is_page() が先に評価される。
	// ブログ投稿インデックスをトップに設定した場合は is_page()=false のため
	// LP テンプレート判定をスキップし、is_front_page()=true で 'home' を返す。
	// ------------------------------------------------------------------

	/**
	 * is_front_page() が true かつ is_page() が false の場合、'home' が返ること。
	 *
	 * @return void
	 */
	public function test_detect_page_type_front_page_returns_home(): void {
		// 修正後: is_page() を最初に評価する。is_page=false のため LP チェックをスキップ。
		Functions\expect( 'is_page' )->once()->andReturn( false );
		Functions\expect( 'is_front_page' )->once()->andReturn( true );
		// is_home は短絡評価でスキップされる。

		$result = $this->detect_method->invoke( $this->assets_instance );

		$this->assertSame( 'home', $result, 'is_front_page() true（is_page=false）→ home' );
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
		// 修正後: is_page() を最初に評価する。is_page=false のため LP チェックをスキップ。
		Functions\expect( 'is_page' )->once()->andReturn( false );
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
		// 修正後: is_page() を最初に評価する。is_page=false のため LP チェックをスキップ。
		Functions\expect( 'is_page' )->once()->andReturn( false );
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
		// 修正後: is_page()=true → get_queried_object_id() → get_page_template_slug(id) で LP 判定。
		Functions\expect( 'is_page' )->once()->andReturn( true );
		Functions\expect( 'get_queried_object_id' )->once()->andReturn( 42 );
		// LP テンプレートスラッグ（'page-lp' で始まる）。
		Functions\expect( 'get_page_template_slug' )->once()->with( 42 )->andReturn( 'page-lp-service' );

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
		Functions\expect( 'is_page' )->once()->andReturn( true );
		Functions\expect( 'get_queried_object_id' )->once()->andReturn( 1 );
		Functions\expect( 'get_page_template_slug' )->once()->with( 1 )->andReturn( 'page-lp' );

		$result = $this->detect_method->invoke( $this->assets_instance );

		$this->assertSame( 'lp', $result, 'get_page_template_slug が page-lp ちょうど → lp' );
	}

	// ------------------------------------------------------------------
	// TC-PT-005: is_page() === true かつ LP テンプレートでない → 'page' を返す
	//
	// 修正後: is_page()=true, template='' → is_front_page/is_home/is_singular を経由して
	// 2 度目の is_page()=true で 'page' を返す。
	// ------------------------------------------------------------------

	/**
	 * is_page() が true で LP テンプレート以外の場合、'page' が返ること。
	 *
	 * @return void
	 */
	public function test_detect_page_type_normal_page_returns_page(): void {
		// 1回目の is_page(): LP テンプレート判定のため呼ばれる。
		// 2回目の is_page(): 'page' 返却のため呼ばれる。
		Functions\expect( 'is_page' )->twice()->andReturn( true );
		Functions\expect( 'get_queried_object_id' )->once()->andReturn( 5 );
		// 通常固定ページ（テンプレートなし）。
		Functions\expect( 'get_page_template_slug' )->once()->with( 5 )->andReturn( '' );
		// LP 非該当のため is_front_page / is_home / is_singular を経由する。
		Functions\expect( 'is_front_page' )->once()->andReturn( false );
		Functions\expect( 'is_home' )->once()->andReturn( false );
		Functions\expect( 'is_singular' )->once()->with( 'post' )->andReturn( false );

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
		Functions\expect( 'is_page' )->twice()->andReturn( true );
		Functions\expect( 'get_queried_object_id' )->once()->andReturn( 7 );
		Functions\expect( 'get_page_template_slug' )->once()->with( 7 )->andReturn( 'page-about' );
		Functions\expect( 'is_front_page' )->once()->andReturn( false );
		Functions\expect( 'is_home' )->once()->andReturn( false );
		Functions\expect( 'is_singular' )->once()->with( 'post' )->andReturn( false );

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
		// 修正後の判定順で is_page() が 2 回呼ばれる:
		//   1回目: LP テンプレート判定（先頭）→ false でスキップ
		//   2回目: 'page' 返却判定（is_front_page / is_home / is_singular の後）→ false → 'other'
		Functions\expect( 'is_page' )->twice()->andReturn( false );
		Functions\expect( 'is_front_page' )->once()->andReturn( false );
		Functions\expect( 'is_home' )->once()->andReturn( false );
		Functions\expect( 'is_singular' )->once()->with( 'post' )->andReturn( false );

		$result = $this->detect_method->invoke( $this->assets_instance );

		$this->assertSame( 'other', $result, '全条件 false（アーカイブ等）→ other' );
	}
}
