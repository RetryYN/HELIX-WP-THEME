<?php
/**
 * detect_page_type() エッジケース追加テスト。
 *
 * 既存 TC_TrackingPageTypeTest（5分岐 + 2境界）では未カバーの
 * 境界値・優先順位・ページ種別を補完する。
 *
 * 判定順（修正後）:
 *   1. is_page() かつ LP テンプレート（'page-lp' で始まる）→ 'lp'  ← LP-as-frontpage 対応
 *   2. is_front_page() || is_home()                          → 'home'
 *   3. is_singular('post')                                   → 'post'
 *   4. is_page()（LP テンプレートなし）                      → 'page'
 *   5. 上記以外                                              → 'other'
 *
 * カバー内容:
 *   TC-PT-E01: is_front_page() AND is_page() 同時 true、LP テンプレートなし — 'home' が優先されること
 *   TC-PT-E01b: [新規] LP テンプレ(page-lp-sample 等)をフロントページに設定 — 'lp' が最優先されること（本修正の核心ケース）
 *   TC-PT-E02: is_front_page() AND is_home() 同時 true（is_page=false）— 'home' が返ること
 *   TC-PT-E02b: [新規] 非 LP 固定ページをフロントページに設定（is_front_page=true, is_page=true, template=''）— 'home' が返ること（回帰確認）
 *   TC-PT-E03: is_home() のみ true（blog posts index）— 'home' を返すこと
 *   TC-PT-E04: is_front_page() が true なら is_singular() の手前で確定すること（is_page=false パス）
 *   TC-PT-E05: is_page() true かつ テンプレートスラッグ 'page-lp-foo' — 'lp'
 *   TC-PT-E06: is_page() true かつ テンプレートスラッグ 'page-lp-' — 'lp'
 *   TC-PT-E07: is_page() true かつ テンプレートスラッグ 'page-about-lp' — 'page'
 *   TC-PT-E08: 404 ページ（全条件 false）— 'other'
 *   TC-PT-E09: 検索結果ページ（全条件 false）— 'other'
 *   TC-PT-E10: 添付ページ（全条件 false）— 'other'
 *   TC-PT-E11: is_page() true かつ テンプレートスラッグが空文字 — 'page'
 *   TC-PT-E12: is_singular('post') が false でも is_page() が別途評価されること（独立分岐確認）
 *
 * @package AgentNeo\Tests\Unit
 */

declare( strict_types=1 );

namespace AgentNeo\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * detect_page_type() エッジケース補完テスト。
 */
class TC_TrackingPageTypeEdgeCaseTest extends TestCase {

	/** @var \ReflectionMethod detect_page_type の Reflection オブジェクト */
	private \ReflectionMethod $detect_method;

	/** @var object Agent_Neo_Core_Tracking_Assets のインスタンス（コンストラクタ不使用） */
	private object $assets_instance;

	/**
	 * Brain Monkey 初期化 + Reflection セットアップ。
	 *
	 * @return void
	 */
	protected function set_up(): void {
		parent::set_up();
		Monkey\setUp();

		if ( ! class_exists( 'Agent_Neo_Core_Tracking_Assets' ) ) {
			require_once AGENT_NEO_CORE_DIR . 'inc/tracking/class-tracking-assets.php';
		}

		$ref                   = new \ReflectionClass( 'Agent_Neo_Core_Tracking_Assets' );
		$this->detect_method   = $ref->getMethod( 'detect_page_type' );
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
	// TC-PT-E01: is_front_page() AND is_page() 同時 true、LP テンプレートなし → 'home' 優先
	//
	// 非 LP 固定ページをフロントに設定した場合。
	// 修正後: is_page()=true で LP テンプレート判定を先に行うが、
	// template='' のため LP 判定を通過 → is_front_page()=true → 'home'。
	// ------------------------------------------------------------------

	/**
	 * TC-PT-E01: 静的フロントページ（is_front_page=true, is_page=true, LP テンプレートなし）→ 'home' が返ること。
	 *
	 * @return void
	 */
	public function test_detect_page_type_static_front_page_no_lp_template_home_wins(): void {
		// 修正後の判定順: is_page() → get_queried_object_id() → get_page_template_slug(id) → LP非該当
		// → is_front_page()=true → 'home'
		Functions\expect( 'is_page' )->once()->andReturn( true );
		Functions\expect( 'get_queried_object_id' )->once()->andReturn( 10 );
		// LP テンプレートではない（空文字 = テンプレートなし）。
		Functions\expect( 'get_page_template_slug' )->once()->with( 10 )->andReturn( '' );
		Functions\expect( 'is_front_page' )->once()->andReturn( true );
		// is_home は短絡評価でスキップされる。

		$result = $this->detect_method->invoke( $this->assets_instance );

		$this->assertSame( 'home', $result, '非 LP 固定ページをフロントに設定（LP テンプレートなし）→ home が優先されること' );
	}

	// ------------------------------------------------------------------
	// TC-PT-E01b: [新規] LP テンプレをフロントページに設定 → 'lp' が最優先
	//
	// 本修正（確定欠陥 #2）の核心ケース。
	// SaaS では LP をドメインルートに設定するケースが多い。
	// is_front_page()=true であっても LP テンプレートが先に判定され 'lp' を返すこと。
	// ------------------------------------------------------------------

	/**
	 * TC-PT-E01b: LP テンプレを持つ固定ページをフロントに設定（is_front_page=true, is_page=true, template='page-lp-sample'）→ 'lp' が最優先されること。
	 *
	 * @return void
	 */
	public function test_detect_page_type_lp_as_front_page_returns_lp(): void {
		// 修正後: is_page()=true → LP テンプレート検出 → 'lp' を即返却。
		// is_front_page() には到達しない。
		Functions\expect( 'is_page' )->once()->andReturn( true );
		Functions\expect( 'get_queried_object_id' )->once()->andReturn( 1 );
		Functions\expect( 'get_page_template_slug' )->once()->with( 1 )->andReturn( 'page-lp-sample' );
		// is_front_page / is_home / is_singular は呼ばれないことを確認する
		// （Brain Monkey は expect なし = unexpected call をエラーとして検出する）。

		$result = $this->detect_method->invoke( $this->assets_instance );

		$this->assertSame( 'lp', $result, 'LP テンプレをフロントページに設定しても lp が最優先されること（確定欠陥 #2 修正核心）' );
	}

	// ------------------------------------------------------------------
	// TC-PT-E02: is_front_page() AND is_home() 同時 true（is_page=false）→ 'home'
	//
	// 「最新の投稿」をフロントページに設定した場合、両関数が true になる。
	// is_page()=false のため LP テンプレート判定をスキップして 'home'。
	// ------------------------------------------------------------------

	/**
	 * TC-PT-E02: is_front_page AND is_home 同時 true（is_page=false）→ 'home' が返ること。
	 *
	 * @return void
	 */
	public function test_detect_page_type_blog_homepage_front_and_home_both_true(): void {
		// 修正後: is_page()=false → LP テンプレート判定をスキップ。
		Functions\expect( 'is_page' )->once()->andReturn( false );
		// is_front_page()=true → 短絡評価で is_home() は呼ばれない。
		Functions\expect( 'is_front_page' )->once()->andReturn( true );

		$result = $this->detect_method->invoke( $this->assets_instance );

		$this->assertSame( 'home', $result, 'is_front_page=true AND is_home=true（is_page=false）の場合 home が返ること' );
	}

	// ------------------------------------------------------------------
	// TC-PT-E02b: [新規] 非 LP 固定ページをフロントページに設定 → 'home'（回帰確認）
	//
	// is_front_page=true, is_page=true, template='' → LP テンプレート非該当
	// → is_front_page()=true → 'home'（TC-PT-E01 と実質同等だが回帰確認として明示）
	// ------------------------------------------------------------------

	/**
	 * TC-PT-E02b: 非 LP 固定ページをフロントページに設定（is_front_page=true, is_page=true, template=''）→ 'home'（回帰確認）。
	 *
	 * @return void
	 */
	public function test_detect_page_type_non_lp_page_as_front_returns_home_regression(): void {
		Functions\expect( 'is_page' )->once()->andReturn( true );
		Functions\expect( 'get_queried_object_id' )->once()->andReturn( 2 );
		Functions\expect( 'get_page_template_slug' )->once()->with( 2 )->andReturn( '' );
		Functions\expect( 'is_front_page' )->once()->andReturn( true );

		$result = $this->detect_method->invoke( $this->assets_instance );

		$this->assertSame( 'home', $result, '非 LP 固定ページをフロントに設定（LP テンプレートなし）→ home（回帰確認）' );
	}

	// ------------------------------------------------------------------
	// TC-PT-E03: is_home() のみ true（ブログ投稿インデックス）→ 'home'
	//
	// 静的フロントページを設定し別URLをブログトップにした場合、
	// ブログトップでは is_front_page()=false、is_home()=true。
	// is_page()=false のため LP テンプレート判定をスキップ。
	// ------------------------------------------------------------------

	/**
	 * TC-PT-E03: blog posts index（is_front_page=false、is_home=true、is_page=false）→ 'home' が返ること。
	 *
	 * @return void
	 */
	public function test_detect_page_type_blog_posts_index_returns_home(): void {
		// 修正後: is_page()=false → LP テンプレート判定をスキップ。
		Functions\expect( 'is_page' )->once()->andReturn( false );
		Functions\expect( 'is_front_page' )->once()->andReturn( false );
		Functions\expect( 'is_home' )->once()->andReturn( true );

		$result = $this->detect_method->invoke( $this->assets_instance );

		$this->assertSame( 'home', $result, 'blog posts index（is_home=true）→ home' );
	}

	// ------------------------------------------------------------------
	// TC-PT-E04: is_front_page() が true かつ is_page()=false → is_singular() の前で確定
	//
	// 「最新の投稿」をフロントページにした場合、is_page()=false のため
	// LP テンプレート判定をスキップし、is_front_page()=true で home 確定。
	// ------------------------------------------------------------------

	/**
	 * TC-PT-E04: is_front_page=true かつ is_page=false の場合 is_singular() は評価されず 'home' が返ること（優先順位確認）。
	 *
	 * @return void
	 */
	public function test_detect_page_type_front_page_evaluated_before_singular(): void {
		// 修正後: is_page()=false → LP スキップ → is_front_page()=true → home 確定。
		// is_singular が呼ばれないことを Brain Monkey が検出する（expect なし）。
		Functions\expect( 'is_page' )->once()->andReturn( false );
		Functions\expect( 'is_front_page' )->once()->andReturn( true );

		$result = $this->detect_method->invoke( $this->assets_instance );

		$this->assertSame( 'home', $result, 'is_front_page=true（is_page=false）なら is_singular() の前で home 確定すること' );
	}

	// ------------------------------------------------------------------
	// TC-PT-E05: page-lp-foo（page-lp プレフィックス + サフィックス）→ 'lp'
	// ------------------------------------------------------------------

	/**
	 * TC-PT-E05: テンプレートスラッグ 'page-lp-foo' → 'lp' が返ること（LP サフィックス境界）。
	 *
	 * @return void
	 */
	public function test_detect_page_type_lp_template_slug_with_arbitrary_suffix_returns_lp(): void {
		Functions\expect( 'is_page' )->once()->andReturn( true );
		Functions\expect( 'get_queried_object_id' )->once()->andReturn( 20 );
		Functions\expect( 'get_page_template_slug' )->once()->with( 20 )->andReturn( 'page-lp-foo' );

		$result = $this->detect_method->invoke( $this->assets_instance );

		$this->assertSame( 'lp', $result, 'page-lp-foo は page-lp で始まるため lp になること' );
	}

	// ------------------------------------------------------------------
	// TC-PT-E06: page-lp- （末尾ハイフンで終わる）→ 'lp'
	// ------------------------------------------------------------------

	/**
	 * TC-PT-E06: テンプレートスラッグ 'page-lp-' → 'lp' が返ること（末尾ハイフン境界）。
	 *
	 * @return void
	 */
	public function test_detect_page_type_lp_template_slug_trailing_hyphen_returns_lp(): void {
		Functions\expect( 'is_page' )->once()->andReturn( true );
		Functions\expect( 'get_queried_object_id' )->once()->andReturn( 21 );
		Functions\expect( 'get_page_template_slug' )->once()->with( 21 )->andReturn( 'page-lp-' );

		$result = $this->detect_method->invoke( $this->assets_instance );

		$this->assertSame( 'lp', $result, 'page-lp- は page-lp で始まるため lp になること' );
	}

	// ------------------------------------------------------------------
	// TC-PT-E07: page-about-lp （page-lp で始まらない）→ 'page'
	// ------------------------------------------------------------------

	/**
	 * TC-PT-E07: テンプレートスラッグ 'page-about-lp' → 'page' が返ること（LP 非先頭境界）。
	 *
	 * @return void
	 */
	public function test_detect_page_type_slug_containing_lp_but_not_starting_with_page_lp_returns_page(): void {
		Functions\expect( 'is_page' )->twice()->andReturn( true );
		Functions\expect( 'get_queried_object_id' )->once()->andReturn( 22 );
		Functions\expect( 'get_page_template_slug' )->once()->with( 22 )->andReturn( 'page-about-lp' );
		Functions\expect( 'is_front_page' )->once()->andReturn( false );
		Functions\expect( 'is_home' )->once()->andReturn( false );
		Functions\expect( 'is_singular' )->once()->with( 'post' )->andReturn( false );

		$result = $this->detect_method->invoke( $this->assets_instance );

		$this->assertSame( 'page', $result, 'page-about-lp は page-lp で始まらないため page になること' );
	}

	// ------------------------------------------------------------------
	// TC-PT-E08: 404 ページ（全条件 false）→ 'other'
	// ------------------------------------------------------------------

	/**
	 * TC-PT-E08: 404 ページ（全 WP 条件 false）→ 'other' が返ること。
	 *
	 * @return void
	 */
	public function test_detect_page_type_404_returns_other(): void {
		// is_page() が 2 回呼ばれる:
		//   1回目: LP テンプレート判定（先頭）→ false でスキップ
		//   2回目: 'page' 返却判定（is_front_page / is_home / is_singular の後）→ false → 'other'
		Functions\expect( 'is_page' )->twice()->andReturn( false );
		Functions\expect( 'is_front_page' )->once()->andReturn( false );
		Functions\expect( 'is_home' )->once()->andReturn( false );
		Functions\expect( 'is_singular' )->once()->with( 'post' )->andReturn( false );

		$result = $this->detect_method->invoke( $this->assets_instance );

		$this->assertSame( 'other', $result, '404 ページ → other' );
	}

	// ------------------------------------------------------------------
	// TC-PT-E09: 検索結果ページ（全条件 false）→ 'other'
	// ------------------------------------------------------------------

	/**
	 * TC-PT-E09: 検索結果ページ（is_search=true だが detect_page_type は評価しない）→ 'other'。
	 *
	 * @return void
	 */
	public function test_detect_page_type_search_results_returns_other(): void {
		// is_page() が 2 回呼ばれる（先頭判定 + page 返却判定）。
		Functions\expect( 'is_page' )->twice()->andReturn( false );
		Functions\expect( 'is_front_page' )->once()->andReturn( false );
		Functions\expect( 'is_home' )->once()->andReturn( false );
		Functions\expect( 'is_singular' )->once()->with( 'post' )->andReturn( false );

		$result = $this->detect_method->invoke( $this->assets_instance );

		$this->assertSame( 'other', $result, '検索結果ページ → other に集約されること' );
	}

	// ------------------------------------------------------------------
	// TC-PT-E10: 添付ページ（attachment — 全条件 false）→ 'other'
	// ------------------------------------------------------------------

	/**
	 * TC-PT-E10: 添付ページ（is_attachment=true だが detect_page_type は評価しない）→ 'other'。
	 *
	 * @return void
	 */
	public function test_detect_page_type_attachment_page_returns_other(): void {
		// is_page() が 2 回呼ばれる（先頭判定 + page 返却判定）。
		Functions\expect( 'is_page' )->twice()->andReturn( false );
		Functions\expect( 'is_front_page' )->once()->andReturn( false );
		Functions\expect( 'is_home' )->once()->andReturn( false );
		// 添付ページ: is_singular('attachment') だが is_singular('post') は false。
		Functions\expect( 'is_singular' )->once()->with( 'post' )->andReturn( false );

		$result = $this->detect_method->invoke( $this->assets_instance );

		$this->assertSame( 'other', $result, '添付ページ（attachment）→ other に集約されること' );
	}

	// ------------------------------------------------------------------
	// TC-PT-E11: is_page() true かつ get_page_template_slug() が空文字 → 'page'
	// ------------------------------------------------------------------

	/**
	 * TC-PT-E11: is_page()=true でテンプレートスラッグ空文字 → 'page' が返ること（テンプレートなし固定ページ）。
	 *
	 * @return void
	 */
	public function test_detect_page_type_page_with_no_template_returns_page(): void {
		// 1回目: LP テンプレート判定、2回目: 'page' 返却。
		Functions\expect( 'is_page' )->twice()->andReturn( true );
		Functions\expect( 'get_queried_object_id' )->once()->andReturn( 30 );
		// テンプレートなし: get_page_template_slug() は '' を返す。
		Functions\expect( 'get_page_template_slug' )->once()->with( 30 )->andReturn( '' );
		// LP 非該当のため is_front_page / is_home / is_singular を経由する。
		Functions\expect( 'is_front_page' )->once()->andReturn( false );
		Functions\expect( 'is_home' )->once()->andReturn( false );
		Functions\expect( 'is_singular' )->once()->with( 'post' )->andReturn( false );

		$result = $this->detect_method->invoke( $this->assets_instance );

		$this->assertSame( 'page', $result, 'テンプレートなし固定ページ → page' );
	}

	// ------------------------------------------------------------------
	// TC-PT-E12: is_singular('post')=false → is_page() が独立して評価されること
	// ------------------------------------------------------------------

	/**
	 * TC-PT-E12: is_singular('post')=false のとき is_page()=true が独立して評価され 'lp' になること。
	 *
	 * @return void
	 */
	public function test_detect_page_type_page_evaluated_independently_from_singular_post(): void {
		// 修正後: is_page() を最初に評価する。is_page=true, template='page-lp' → 即 'lp'。
		// is_singular は呼ばれない。
		Functions\expect( 'is_page' )->once()->andReturn( true );
		Functions\expect( 'get_queried_object_id' )->once()->andReturn( 40 );
		Functions\expect( 'get_page_template_slug' )->once()->with( 40 )->andReturn( 'page-lp' );

		$result = $this->detect_method->invoke( $this->assets_instance );

		$this->assertSame( 'lp', $result, 'is_singular(post)=false でも is_page()=true で LP テンプレートなら lp が返ること' );
	}
}
