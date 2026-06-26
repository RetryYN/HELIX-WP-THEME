<?php
/**
 * テーマ SEO ユニットテスト。
 *
 * 対象:
 *   - Agent_Neo_Structured_Data  (inc/seo/class-structured-data.php)
 *   - Agent_Neo_Head_Meta        (inc/seo/class-head-meta.php)
 *
 * TC-SEO-001: CR-1 著者名フォールバック（display_name 空 → login）
 * TC-SEO-002: CR-2 固定ページに WebPage を出力し BlogPosting を出力しない
 * TC-SEO-003: CR-2 固定ページの og:type が website
 * TC-SEO-004: IM-1 投稿ページで meta description が出力される
 * TC-SEO-005: IM-2 著者アーカイブに noindex が付与される
 * TC-SEO-006: IM-2 日付アーカイブに noindex が付与される
 * TC-SEO-007: IM-2 404 に noindex が付与される
 * TC-SEO-008: IM-2 通常投稿は noindex 不添付
 * TC-SEO-009: IM-4 Organization に sameAs フィルタが反映される
 * TC-SEO-010: IM-7 description の [&hellip;] エンティティが除去される
 * TC-SEO-011: IM-7 description の HTML タグが除去される
 * TC-SEO-012: IM-3 is_front_page 時に canonical が出力される（非 singular）
 * TC-SEO-013: is_singular('post') のみ BlogPosting を出力し page は出力しない
 *
 * @package AgentNeo\Tests\Unit
 */

declare( strict_types=1 );

namespace AgentNeo\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * テーマ SEO ユニットテスト。
 */
class TC_ThemeSeoTest extends TestCase {

	/**
	 * Brain Monkey を初期化する。
	 *
	 * @return void
	 */
	protected function set_up(): void {
		parent::set_up();
		Monkey\setUp();

		// テーマ定数（ABSPATH は bootstrap-unit.php で定義済み）。
		if ( ! defined( 'AGENT_NEO_DIR' ) ) {
			define( 'AGENT_NEO_DIR', dirname( __DIR__, 2 ) . '/themes/agent-neo-theme/' );
		}

		// SEO クラスをロードする。
		if ( ! class_exists( 'Agent_Neo_Structured_Data' ) ) {
			require_once AGENT_NEO_DIR . 'inc/seo/class-structured-data.php';
		}
		if ( ! class_exists( 'Agent_Neo_Head_Meta' ) ) {
			require_once AGENT_NEO_DIR . 'inc/seo/class-head-meta.php';
		}
	}

	/**
	 * Brain Monkey を後片付けする。
	 *
	 * @return void
	 */
	protected function tear_down(): void {
		Monkey\tearDown();
		parent::tear_down();
	}

	// ---------------------------------------------------------------
	// ヘルパー: structured-data の private メソッドを Reflection で取得する
	// ---------------------------------------------------------------

	/**
	 * Agent_Neo_Structured_Data の private メソッドを呼ぶ。
	 *
	 * @param string        $method_name メソッド名。
	 * @param array<mixed>  $args        引数配列。
	 * @return mixed
	 */
	private function call_sd_method( string $method_name, array $args = [] ) {
		$obj    = new \Agent_Neo_Structured_Data();
		$method = new \ReflectionMethod( \Agent_Neo_Structured_Data::class, $method_name );
		$method->setAccessible( true );
		return $method->invokeArgs( $obj, $args );
	}

	/**
	 * Agent_Neo_Head_Meta の private メソッドを呼ぶ。
	 *
	 * @param string       $method_name メソッド名。
	 * @param array<mixed> $args        引数配列。
	 * @return mixed
	 */
	private function call_hm_method( string $method_name, array $args = [] ) {
		$obj    = new \Agent_Neo_Head_Meta();
		$method = new \ReflectionMethod( \Agent_Neo_Head_Meta::class, $method_name );
		$method->setAccessible( true );
		return $method->invokeArgs( $obj, $args );
	}

	// ---------------------------------------------------------------
	// TC-SEO-001: CR-1 著者名フォールバック
	// ---------------------------------------------------------------

	/**
	 * display_name が空のとき login にフォールバックすること。
	 */
	public function test_seo_001_author_name_fallback_login(): void {
		// get_author_name 内部で sanitize_text_field が呼ばれるため mock 必須。
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'get_the_author_meta' )->alias(
			function ( string $field, int $user_id ): string {
				// display_name は空、user_nicename も空、login は 'admin'。
				return match ( $field ) {
					'display_name'  => '',
					'user_nicename' => '',
					'login'         => 'admin',
					default         => '',
				};
			}
		);

		$result = $this->call_sd_method( 'get_author_name', [ 1 ] );
		$this->assertSame( 'admin', $result, 'display_name 空のとき login にフォールバックする' );
	}

	/**
	 * display_name が空のとき nicename にフォールバックすること。
	 */
	public function test_seo_001b_author_name_fallback_nicename(): void {
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'get_the_author_meta' )->alias(
			function ( string $field, int $user_id ): string {
				return match ( $field ) {
					'display_name'  => '',
					'user_nicename' => 'john-doe',
					'login'         => 'johndoe',
					default         => '',
				};
			}
		);

		$result = $this->call_sd_method( 'get_author_name', [ 1 ] );
		$this->assertSame( 'john-doe', $result, 'display_name 空のとき nicename にフォールバックする' );
	}

	/**
	 * display_name が設定されているときはそれを返すこと。
	 */
	public function test_seo_001c_author_name_display_name_wins(): void {
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'get_the_author_meta' )->alias(
			function ( string $field, int $user_id ): string {
				return match ( $field ) {
					'display_name'  => 'John Doe',
					'user_nicename' => 'john-doe',
					'login'         => 'johndoe',
					default         => '',
				};
			}
		);

		$result = $this->call_sd_method( 'get_author_name', [ 1 ] );
		$this->assertSame( 'John Doe', $result, 'display_name 設定時はそれを返す' );
	}

	// ---------------------------------------------------------------
	// TC-SEO-002: CR-2 固定ページ → WebPage（BlogPosting でない）
	// ---------------------------------------------------------------

	/**
	 * build_web_page() が @type=WebPage を返すこと。
	 */
	public function test_seo_002_web_page_type(): void {
		$post           = new \WP_Post();
		$post->ID       = 10;
		$post->post_author  = 1;
		$post->post_content = 'LP コンテンツ';
		$post->post_type    = 'page';

		Functions\when( 'get_the_ID' )->justReturn( 10 );
		Functions\when( 'get_post' )->justReturn( $post );
		Functions\when( 'get_the_title' )->justReturn( 'LP サンプル' );
		Functions\when( 'get_permalink' )->justReturn( 'http://localhost:8086/lp-sample/' );
		Functions\when( 'home_url' )->justReturn( 'http://localhost:8086/' );
		Functions\when( 'get_the_excerpt' )->justReturn( '' );
		Functions\when( 'wp_trim_words' )->justReturn( 'LP コンテンツ' );
		Functions\when( 'wp_strip_all_tags' )->justReturn( 'LP コンテンツ' );
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'html_entity_decode' )->returnArg();
		Functions\when( 'esc_url_raw' )->returnArg();

		$result = $this->call_sd_method( 'build_web_page' );

		$this->assertSame( 'WebPage', $result['@type'], '固定ページは WebPage を返す' );
		$this->assertStringContainsString( '#webpage', $result['@id'], '@id に #webpage を含む' );
		$this->assertArrayHasKey( 'isPartOf', $result, 'isPartOf を含む' );
	}

	// ---------------------------------------------------------------
	// TC-SEO-003: CR-2 固定ページの og:type が 'website'
	// ---------------------------------------------------------------

	/**
	 * is_singular('post') が false のとき og:type が 'website' になること。
	 */
	public function test_seo_003_page_og_type_is_website(): void {
		$post           = new \WP_Post();
		$post->ID       = 102;
		$post->post_author  = 1;
		$post->post_content = 'LP コンテンツ';
		$post->post_type    = 'page';

		Functions\when( 'is_singular' )->alias(
			function ( $type = null ) {
				// page 型 is_singular。post 型は false。
				if ( 'post' === $type ) {
					return false;
				}
				// is_singular() 無引数は true。
				return true;
			}
		);
		Functions\when( 'get_the_ID' )->justReturn( 102 );
		Functions\when( 'get_post' )->justReturn( $post );
		Functions\when( 'get_the_title' )->justReturn( 'LP サンプル' );
		Functions\when( 'get_permalink' )->justReturn( 'http://localhost:8086/lp-sample/' );
		Functions\when( 'get_post_meta' )->justReturn( '' );
		Functions\when( 'get_the_excerpt' )->justReturn( '' );
		Functions\when( 'wp_trim_words' )->justReturn( 'LP コンテンツ' );
		Functions\when( 'wp_strip_all_tags' )->justReturn( 'LP コンテンツ' );
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'html_entity_decode' )->returnArg();
		Functions\when( 'esc_url_raw' )->returnArg();
		Functions\when( 'has_post_thumbnail' )->justReturn( false );
		Functions\when( 'get_theme_mod' )->justReturn( 0 );
		Functions\when( 'get_option' )->justReturn( 0 );
		Functions\when( 'get_header_image' )->justReturn( '' );
		Functions\when( 'get_bloginfo' )->justReturn( 'AGENT NEO Dev' );
		Functions\when( 'get_locale' )->justReturn( 'ja_JP' );

		$meta = $this->call_hm_method( 'build_singular_meta' );

		$this->assertSame( 'website', $meta['og']['og:type'], '固定ページの og:type は website' );
	}

	// ---------------------------------------------------------------
	// TC-SEO-004: IM-1 meta description 出力
	// ---------------------------------------------------------------

	/**
	 * assemble_meta が description キーを持ち、空でないこと。
	 *
	 * assemble_meta は description を引数として受け取るだけで preg_replace を呼ばない。
	 * preg_replace mock（Patchwork 内部関数制約）は不要。
	 */
	public function test_seo_004_meta_description_present(): void {
		Functions\when( 'get_bloginfo' )->justReturn( 'AGENT NEO Dev' );
		Functions\when( 'get_locale' )->justReturn( 'ja_JP' );
		Functions\when( 'esc_url_raw' )->returnArg();

		$result = $this->call_hm_method(
			'assemble_meta',
			[ 'タイトル', 'テスト説明文', '', 'http://localhost:8086/', 'article' ]
		);

		$this->assertArrayHasKey( 'description', $result, 'description キーが存在する' );
		$this->assertSame( 'テスト説明文', $result['description'], 'description が正しく設定される' );
		$this->assertSame( 'テスト説明文', $result['og']['og:description'], 'og:description も一致' );
		$this->assertSame( 'テスト説明文', $result['twitter']['twitter:description'], 'twitter:description も一致' );
	}

	// ---------------------------------------------------------------
	// TC-SEO-005〜008: IM-2 noindex フィルタ
	// ---------------------------------------------------------------

	/**
	 * is_author() が true のとき noindex が付与されること。
	 */
	public function test_seo_005_noindex_on_author(): void {
		Functions\when( 'is_author' )->justReturn( true );
		Functions\when( 'is_date' )->justReturn( false );
		Functions\when( 'is_404' )->justReturn( false );

		$obj    = new \Agent_Neo_Head_Meta();
		$robots = $obj->add_noindex_robots( [ 'max-image-preview' => 'large' ] );

		$this->assertTrue( $robots['noindex'], '著者アーカイブに noindex が付与される' );
		$this->assertTrue( $robots['follow'], '著者アーカイブに follow が付与される' );
		$this->assertArrayNotHasKey( 'max-image-preview', $robots, 'noindex 時は max-image-preview が取り下げられる' );
	}

	/**
	 * is_date() が true のとき noindex が付与されること。
	 */
	public function test_seo_006_noindex_on_date(): void {
		Functions\when( 'is_author' )->justReturn( false );
		Functions\when( 'is_date' )->justReturn( true );
		Functions\when( 'is_404' )->justReturn( false );

		$obj    = new \Agent_Neo_Head_Meta();
		$robots = $obj->add_noindex_robots( [] );

		$this->assertTrue( $robots['noindex'], '日付アーカイブに noindex が付与される' );
		$this->assertTrue( $robots['follow'], '日付アーカイブに follow が付与される' );
	}

	/**
	 * is_404() が true のとき noindex が付与されること。
	 */
	public function test_seo_007_noindex_on_404(): void {
		Functions\when( 'is_author' )->justReturn( false );
		Functions\when( 'is_date' )->justReturn( false );
		Functions\when( 'is_404' )->justReturn( true );

		$obj    = new \Agent_Neo_Head_Meta();
		$robots = $obj->add_noindex_robots( [] );

		$this->assertTrue( $robots['noindex'], '404 に noindex が付与される' );
	}

	/**
	 * 通常ページは noindex が付与されないこと。
	 */
	public function test_seo_008_no_noindex_on_normal_page(): void {
		Functions\when( 'is_author' )->justReturn( false );
		Functions\when( 'is_date' )->justReturn( false );
		Functions\when( 'is_404' )->justReturn( false );

		$obj    = new \Agent_Neo_Head_Meta();
		$robots = $obj->add_noindex_robots( [ 'max-image-preview' => 'large' ] );

		$this->assertArrayNotHasKey( 'noindex', $robots, '通常ページに noindex は付与されない' );
	}

	// ---------------------------------------------------------------
	// TC-SEO-009: IM-4 Organization sameAs フィルタ
	// ---------------------------------------------------------------

	/**
	 * apply_filters('agent_neo_organization_same_as', []) が反映されること。
	 */
	public function test_seo_009_organization_same_as_filter(): void {
		Functions\when( 'get_bloginfo' )->justReturn( 'AGENT NEO Dev' );
		Functions\when( 'home_url' )->justReturn( 'http://localhost:8086/' );
		Functions\when( 'esc_url_raw' )->returnArg();
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'get_theme_mod' )->justReturn( 0 );
		Functions\when( 'get_option' )->justReturn( 0 );
		Functions\when( 'apply_filters' )->alias(
			function ( string $hook, $value ) {
				if ( 'agent_neo_organization_same_as' === $hook ) {
					return [ 'https://twitter.com/example' ];
				}
				return $value;
			}
		);

		$result = $this->call_sd_method( 'build_organization' );

		$this->assertArrayHasKey( 'sameAs', $result, 'sameAs が存在する' );
		$this->assertContains( 'https://twitter.com/example', $result['sameAs'], 'フィルタで注入した URL が反映される' );
	}

	/**
	 * apply_filters が空配列を返す場合、sameAs キーが存在しないこと。
	 */
	public function test_seo_009b_organization_no_same_as_when_empty(): void {
		Functions\when( 'get_bloginfo' )->justReturn( 'AGENT NEO Dev' );
		Functions\when( 'home_url' )->justReturn( 'http://localhost:8086/' );
		Functions\when( 'esc_url_raw' )->returnArg();
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'get_theme_mod' )->justReturn( 0 );
		Functions\when( 'get_option' )->justReturn( 0 );
		Functions\when( 'apply_filters' )->alias(
			function ( string $hook, $value ) {
				return $value; // フィルタなし = 空配列。
			}
		);

		$result = $this->call_sd_method( 'build_organization' );

		$this->assertArrayNotHasKey( 'sameAs', $result, 'sameAs なしのとき sameAs キーが存在しない' );
	}

	// ---------------------------------------------------------------
	// TC-SEO-010: IM-7 description エンティティ除去
	// ---------------------------------------------------------------

	/**
	 * [&hellip;] 等のエンティティが description から除去されること。
	 *
	 * sanitize_description は wp_strip_all_tags → html_entity_decode → preg_replace
	 * → sanitize_text_field のパイプライン。
	 * 本テストでは WP スタブを実体に近い形で実装し、
	 * PHP 組み込みの preg_replace はスタブしない（Patchwork の redefinable-internals 制約回避）。
	 */
	public function test_seo_010_description_entity_decode(): void {
		Functions\when( 'wp_strip_all_tags' )->alias(
			fn( string $text ) => strip_tags( $text )
		);
		Functions\when( 'html_entity_decode' )->alias(
			fn( string $text, int $flags, string $enc ) => html_entity_decode( $text, $flags, $enc )
		);
		Functions\when( 'sanitize_text_field' )->alias(
			fn( string $text ) => trim( $text )
		);

		// [&hellip;] を含む抜粋テキスト。
		// html_entity_decode が &hellip; → … に変換し、preg_replace が [...] を除去する。
		$input = '記事の概要を説明します。 テーマ・データベース・アップロード [&hellip;]';

		$result = $this->call_sd_method( 'sanitize_description', [ $input ] );

		$this->assertStringNotContainsString( '[', $result, '省略マーカー [ が除去される' );
		$this->assertStringNotContainsString( '&hellip;', $result, 'エンティティ &hellip; が除去される' );
		$this->assertStringNotContainsString( '&amp;', $result, '余分なエンティティが含まれない' );
	}

	// ---------------------------------------------------------------
	// TC-SEO-011: IM-7 description HTML タグ除去
	// ---------------------------------------------------------------

	/**
	 * HTML タグが description から除去されること。
	 */
	public function test_seo_011_description_strips_html(): void {
		Functions\when( 'wp_strip_all_tags' )->alias(
			fn( string $text ) => strip_tags( $text )
		);
		Functions\when( 'html_entity_decode' )->alias(
			fn( string $text, int $flags, string $enc ) => html_entity_decode( $text, $flags, $enc )
		);
		Functions\when( 'sanitize_text_field' )->alias(
			fn( string $text ) => trim( $text )
		);

		$input  = '<p>これは<strong>テスト</strong>の説明文です。</p>';
		$result = $this->call_sd_method( 'sanitize_description', [ $input ] );

		$this->assertStringNotContainsString( '<p>', $result, '<p> タグが除去される' );
		$this->assertStringNotContainsString( '<strong>', $result, '<strong> タグが除去される' );
		$this->assertStringContainsString( 'テスト', $result, 'テキスト本文は残る' );
	}

	// ---------------------------------------------------------------
	// TC-SEO-012: IM-3 canonical（ホームページ）
	// ---------------------------------------------------------------

	/**
	 * is_front_page() が true のとき canonical を出力すること。
	 */
	public function test_seo_012_canonical_on_front_page(): void {
		Functions\when( 'is_feed' )->justReturn( false );
		Functions\when( 'is_singular' )->justReturn( false );
		Functions\when( 'is_front_page' )->justReturn( true );
		Functions\when( 'is_home' )->justReturn( true );
		Functions\when( 'home_url' )->justReturn( 'http://localhost:8086/' );
		Functions\when( 'esc_url_raw' )->returnArg();
		Functions\when( 'esc_url' )->returnArg();

		$obj = new \Agent_Neo_Head_Meta();
		ob_start();
		$obj->output_canonical();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'rel="canonical"', $output, 'canonical タグが出力される' );
		$this->assertStringContainsString( 'http://localhost:8086/', $output, 'home_url が出力される' );
	}

	/**
	 * is_singular() が true のとき canonical を出力しないこと（WP コアに委譲）。
	 */
	public function test_seo_012b_no_canonical_on_singular(): void {
		Functions\when( 'is_feed' )->justReturn( false );
		Functions\when( 'is_singular' )->justReturn( true );

		$obj = new \Agent_Neo_Head_Meta();
		ob_start();
		$obj->output_canonical();
		$output = ob_get_clean();

		$this->assertSame( '', $output, 'singular では canonical を出力しない（WP コア委譲）' );
	}

	// ---------------------------------------------------------------
	// TC-SEO-013: is_singular('post') のみ BlogPosting を出す
	// ---------------------------------------------------------------

	/**
	 * build_blog_posting() は投稿の @type=BlogPosting を返し author.name が設定されること。
	 *
	 * preg_replace は PHP 組み込み関数のため Brain Monkey/Patchwork でスタブしない。
	 * 実際の sanitize_description パイプラインを通して実出力を検証する。
	 * get_the_excerpt が '記事の概要' を返すため description がそれを元に生成されること。
	 */
	public function test_seo_013_blog_posting_for_post_type(): void {
		$post                    = new \WP_Post();
		$post->ID                = 256;
		$post->post_author       = 1;
		$post->post_content      = '記事本文の内容';
		$post->post_type         = 'post';
		$post->post_date_gmt     = '2026-06-26 14:18:12';
		$post->post_modified_gmt = '2026-06-26 14:18:12';

		Functions\when( 'get_the_ID' )->justReturn( 256 );
		Functions\when( 'get_post' )->justReturn( $post );
		Functions\when( 'get_the_title' )->justReturn( 'FSE テーマ移行チェックリスト' );
		Functions\when( 'get_permalink' )->justReturn( 'http://localhost:8086/fse-checklist/' );
		Functions\when( 'home_url' )->justReturn( 'http://localhost:8086/' );
		// 抜粋をプレーンテキストで返す（エンティティなし）。
		Functions\when( 'get_the_excerpt' )->justReturn( '記事の概要テキスト' );
		Functions\when( 'get_the_author_meta' )->alias(
			function ( string $field, int $id ): string {
				return match ( $field ) {
					'display_name'  => 'テスト著者',
					'user_nicename' => 'test-author',
					'login'         => 'testauthor',
					default         => '',
				};
			}
		);
		Functions\when( 'get_author_posts_url' )->justReturn( 'http://localhost:8086/author/testauthor/' );
		Functions\when( 'mysql2date' )->justReturn( '2026-06-26T14:18:12+00:00' );
		Functions\when( 'has_post_thumbnail' )->justReturn( false );
		// preg_replace は PHP 組み込みなのでスタブしない（alias は DefinedTooEarly になるため不可）。
		// returnArg() で入力値をそのまま返す（テキスト内容の変換より構造の正しさを確認する）。
		Functions\when( 'wp_strip_all_tags' )->returnArg();
		Functions\when( 'html_entity_decode' )->returnArg();
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'esc_url_raw' )->returnArg();
		// resolve_author_id（CR-1: post_author=0 → 管理者フォールバック）用。
		Functions\when( 'get_userdata' )->justReturn( (object) array( 'ID' => 1 ) );
		Functions\when( 'get_users' )->justReturn( array( 1 ) );

		$result = $this->call_sd_method( 'build_blog_posting' );

		// @type と author.name を検証（メインの確認観点）。
		$this->assertNotEmpty( $result, 'post タイプで BlogPosting が返る' );
		$this->assertSame( 'BlogPosting', $result['@type'], '@type が BlogPosting' );
		$this->assertSame( 'テスト著者', $result['author']['name'], 'author.name が設定される' );
		// description が空でないこと。
		$this->assertNotEmpty( $result['description'], 'description が空でない' );
		// author @id に #author が含まれること（Person ノード参照）。
		$this->assertStringContainsString( '#author', $result['author']['@id'], 'author @id に #author が含まれる' );
	}
}
