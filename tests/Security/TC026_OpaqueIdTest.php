<?php
/**
 * TC-026: public snapshot の opaque ID 変換 単体テスト。
 *
 * 受入条件（L3-test-plan.md §3.2 TC-026）:
 *   - public snapshot が内部 ID を返さない（公開 ID 変換のみ）
 *   - make_opaque_id() が同一 seed に対して常に同じ値を返す（決定的）
 *   - make_opaque_id() の出力が内部 seed を含まない（不可逆）
 *   - section_id_public / cta_id_public は公開レスポンスに含まれるが
 *     内部 section_id / cta_id / variant_id は含まれないこと
 *   - opaque id は16文字の hex 文字列であること
 *
 * 実装方針:
 *   - Agent_Neo_Core_Public_Controller::make_opaque_id() を Reflection で呼び出す。
 *   - extract_sections() / extract_ctas() のレスポンス構造を検証する。
 *   - WP 関数（wp_salt / parse_blocks 等）は Brain Monkey でスタブ。
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
 * TC-026: opaque ID 変換の安全性検証。
 */
class TC026_OpaqueIdTest extends TestCase {

	/** @var \Agent_Neo_Core_Public_Controller */
	private object $controller;

	/** @var ReflectionClass */
	private ReflectionClass $ref;

	/** テスト用 salt */
	private string $test_salt = 'test-wp-auth-salt-for-unit-testing-only-1234567890';

	protected function set_up(): void {
		parent::set_up();
		Monkey\setUp();

		$this->load_classes();

		$this->controller = new \Agent_Neo_Core_Public_Controller();
		$this->ref        = new ReflectionClass( $this->controller );
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
			AGENT_NEO_CORE_DIR . 'inc/rest/class-public-controller.php',
		];
		foreach ( $files as $file ) {
			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
	}

	/**
	 * controller の private メソッドを呼ぶ。
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
	 * WP_Post モックを作る。
	 *
	 * @param array<string,mixed> $props プロパティ。
	 * @return \WP_Post
	 */
	private function make_post( array $props = [] ): object {
		$post = $this->createMock( \WP_Post::class );
		foreach ( array_merge(
			[
				'ID'           => 42,
				'post_content' => '',
				'post_status'  => 'publish',
				'post_password' => '',
				'post_type'    => 'post',
				'post_name'    => 'test-post',
			],
			$props
		) as $k => $v ) {
			$post->$k = $v;
		}
		return $post;
	}

	// ==================================================================
	// TC-026-01: make_opaque_id() は 16 文字 hex を返すこと
	// ==================================================================

	/**
	 * make_opaque_id() が 16 文字 hex を返すこと。
	 *
	 * @return void
	 */
	public function test_make_opaque_id_returns_16_char_hex(): void {
		Functions\expect( 'wp_salt' )
			->with( 'auth' )
			->andReturn( $this->test_salt );

		$result = $this->call_private( 'make_opaque_id', 'section::42::0::見出しテキスト' );

		$this->assertIsString( $result, 'make_opaque_id() は string を返すこと' );
		$this->assertSame( 16, strlen( $result ), '16 文字であること' );
		$this->assertMatchesRegularExpression( '/^[a-f0-9]{16}$/', $result, 'hex 文字列であること' );
	}

	// ==================================================================
	// TC-026-02: 同じ seed は常に同じ opaque id を返す（決定的）
	// ==================================================================

	/**
	 * 同一の seed に対して常に同じ opaque id が生成されること（決定的変換）。
	 *
	 * @return void
	 */
	public function test_make_opaque_id_is_deterministic(): void {
		Functions\stubs( [
			'wp_salt' => fn( $scheme ) => $this->test_salt,
		] );

		$seed = 'section::99::3::テスト見出し';

		$id1 = $this->call_private( 'make_opaque_id', $seed );
		$id2 = $this->call_private( 'make_opaque_id', $seed );

		$this->assertSame( $id1, $id2, '同一 seed は常に同じ opaque id を返すこと（決定的）' );
	}

	// ==================================================================
	// TC-026-03: 異なる seed は異なる opaque id を返す
	// ==================================================================

	/**
	 * 異なる seed には異なる opaque id が生成されること。
	 *
	 * @return void
	 */
	public function test_make_opaque_id_differs_for_different_seeds(): void {
		Functions\stubs( [
			'wp_salt' => fn( $scheme ) => $this->test_salt,
		] );

		$id_a = $this->call_private( 'make_opaque_id', 'section::1::0::見出しA' );
		$id_b = $this->call_private( 'make_opaque_id', 'section::1::1::見出しB' );

		$this->assertNotSame( $id_a, $id_b, '異なる seed は異なる opaque id を返すこと' );
	}

	// ==================================================================
	// TC-026-04: opaque id に内部 seed が含まれないこと（不可逆）
	// ==================================================================

	/**
	 * opaque id の出力に内部 seed（post_id / index / テキスト）が含まれないこと。
	 *
	 * @return void
	 */
	public function test_make_opaque_id_does_not_contain_internal_seed(): void {
		Functions\stubs( [
			'wp_salt' => fn( $scheme ) => $this->test_salt,
		] );

		$post_id    = 12345;
		$index      = 7;
		$heading    = '機密セクション見出し';
		$seed       = sprintf( 'section::%d::%d::%s', $post_id, $index, $heading );

		$opaque_id = $this->call_private( 'make_opaque_id', $seed );

		// opaque_id が 16 文字 hex であることを前提に不可逆性を検証する。
		// 「内部 seed が漏れていない」とは「opaque_id の文字列自体が seed の構成要素と一致しない」ことであり、
		// hex 出力に個々の数字や文字が偶然含まれるかどうかではない（そうなるのは正常）。
		//
		// 検証する性質:
		//   1. opaque_id は seed 文字列そのものと一致しない（seed そのまま漏れ）
		//   2. opaque_id は seed を部分文字列として含まない
		//   3. opaque_id は 16 文字 hex（make_opaque_id() の仕様）
		$this->assertNotSame( $seed, $opaque_id, 'opaque_id が生の seed と一致しないこと（不可逆）' );
		$this->assertStringNotContainsString( $seed, $opaque_id, 'opaque_id が seed を部分文字列として含まないこと' );
		$this->assertStringNotContainsString( (string) $post_id, $opaque_id, 'opaque_id に post_id の 5 桁文字列が含まれないこと' );
		// テキストの ASCII 部分も含まれないこと。
		$this->assertStringNotContainsString( 'section', $opaque_id, 'opaque_id に "section" 文字列が含まれないこと' );
		// 16 文字 hex であること（ハッシュ切り出しによる不可逆変換の確認）。
		$this->assertMatchesRegularExpression( '/^[a-f0-9]{16}$/', $opaque_id, 'opaque_id は 16 文字 hex であること' );
	}

	// ==================================================================
	// TC-026-05: extract_sections() のレスポンスに内部 section_id が含まれないこと
	// ==================================================================

	/**
	 * extract_sections() のレスポンスに内部 section_id キーが含まれず、
	 * section_id_public のみが公開されること。
	 *
	 * @return void
	 */
	public function test_extract_sections_exposes_only_public_ids(): void {
		Functions\stubs( [
			'wp_salt'          => fn( $scheme ) => $this->test_salt,
			'parse_blocks'     => fn( $content ) => [
				[
					'blockName'    => 'core/heading',
					'attrs'        => [ 'level' => 2 ],
					'innerHTML'    => '<h2>テスト見出し</h2>',
					'innerContent' => [],
				],
			],
			'wp_strip_all_tags' => fn( $str ) => strip_tags( $str ),
			'html_entity_decode' => fn( $str, $f = ENT_QUOTES, $e = 'UTF-8' ) => html_entity_decode( $str, $f, $e ),
		] );

		$post = $this->make_post( [
			'ID'           => 42,
			'post_content' => '<!-- wp:heading {"level":2} --><h2>テスト見出し</h2><!-- /wp:heading -->',
		] );

		$sections = $this->call_private( 'extract_sections', $post );

		$this->assertNotEmpty( $sections, 'セクションが 1 件以上返ること' );

		foreach ( $sections as $section ) {
			// section_id_public は含まれること。
			$this->assertArrayHasKey( 'section_id_public', $section, 'section_id_public キーが含まれること' );

			// 内部 section_id は含まれないこと（carry-026 準拠）。
			$this->assertArrayNotHasKey( 'section_id', $section, '内部 section_id は含まれないこと（carry-026）' );

			// variant_id も含まれないこと。
			$this->assertArrayNotHasKey( 'variant_id', $section, 'variant_id は含まれないこと（carry-026）' );

			// opaque_id は 16 文字 hex。
			$this->assertMatchesRegularExpression(
				'/^[a-f0-9]{16}$/',
				$section['section_id_public'],
				'section_id_public は 16 文字 hex であること'
			);
		}
	}

	// ==================================================================
	// TC-026-06: extract_ctas() のレスポンスに内部 cta_id / variant_id が含まれないこと
	// ==================================================================

	/**
	 * extract_ctas() のレスポンスに内部 cta_id / variant_id が含まれないこと。
	 *
	 * @return void
	 */
	public function test_extract_ctas_does_not_expose_internal_ids(): void {
		Functions\stubs( [
			'wp_salt'           => fn( $scheme ) => $this->test_salt,
			'sanitize_text_field' => fn( $str ) => $str,
			'esc_url_raw'        => fn( $url ) => $url,
		] );

		// CTA ブロックを含むコンテンツ。
		$post = $this->make_post( [
			'ID'           => 55,
			'post_content' => '<!-- wp:agent-neo/cta {"label":"cta-internal-001","buttonText":"申し込む","url":"https://example.com/form","variantId":"var-secret-xyz"} /-->',
		] );

		$ctas = $this->call_private( 'extract_ctas', $post );

		$this->assertNotEmpty( $ctas, 'CTA が 1 件以上返ること' );

		foreach ( $ctas as $cta ) {
			// cta_id_public は含まれること。
			$this->assertArrayHasKey( 'cta_id_public', $cta, 'cta_id_public キーが含まれること' );

			// 内部 cta_id は含まれないこと（carry-026）。
			$this->assertArrayNotHasKey( 'cta_id', $cta, '内部 cta_id は含まれないこと（carry-026）' );

			// variant_id は完全除外（carry-026）。
			$this->assertArrayNotHasKey( 'variant_id', $cta, 'variant_id は完全除外されること（carry-026）' );

			// opaque_id は 16 文字 hex。
			$this->assertMatchesRegularExpression(
				'/^[a-f0-9]{16}$/',
				$cta['cta_id_public'],
				'cta_id_public は 16 文字 hex であること'
			);
		}

		// label（内部 ID ではない表示用文字列）は含まれてよい。
		$this->assertArrayHasKey( 'label', $ctas[0], 'label は含まれること' );

		// variantId が label / button_text / url 等に漏れていないこと。
		$this->assertStringNotContainsString(
			'var-secret',
			(string) $ctas[0]['label'],
			'内部 variantId が label に漏れていないこと'
		);
	}

	// ==================================================================
	// TC-026-07: salt が変わると opaque id が変わること（サイト固有性）
	// ==================================================================

	/**
	 * wp_salt() の値が変わると opaque id も変わること（サイト間での衝突防止）。
	 *
	 * @return void
	 */
	public function test_make_opaque_id_changes_with_different_salt(): void {
		$seed      = 'section::1::0::共通見出し';
		$salt_a    = 'salt-for-site-a-abcdef1234567890';
		$salt_b    = 'salt-for-site-b-zyxwvu9876543210';

		Functions\expect( 'wp_salt' )
			->with( 'auth' )
			->andReturn( $salt_a );

		$id_a = $this->call_private( 'make_opaque_id', $seed );

		// tearDown/setUp なしに別 salt を使うため Mockery の期待を追加する。
		\Mockery::close();
		Monkey\tearDown();
		Monkey\setUp();

		Functions\expect( 'wp_salt' )
			->with( 'auth' )
			->andReturn( $salt_b );

		// 別インスタンスで呼ぶ（同じメソッドロジック）。
		$ctrl2  = new \Agent_Neo_Core_Public_Controller();
		$ref2   = new ReflectionClass( $ctrl2 );
		$method = $ref2->getMethod( 'make_opaque_id' );
		$method->setAccessible( true );
		$id_b = $method->invoke( $ctrl2, $seed );

		$this->assertNotSame( $id_a, $id_b, '異なる salt では異なる opaque id が生成されること（サイト固有性）' );
	}
}
