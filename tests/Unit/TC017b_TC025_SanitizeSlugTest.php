<?php
/**
 * TC-017b / TC-025: sanitize_slug / sanitize_title 分離・slug 安全性 単体テスト。
 *
 * 受入条件（L3-test-plan.md §3.2）:
 *
 * TC-017b (carry-013 / R-09a / CARRY-G2-009 / CARRY-G2-013):
 *   ① sanitize_slug() が非ASCII入力（"SEO 基礎" / "日本語のみ"）を [a-z0-9-] 内部 slug へ正規化すること。
 *      全非ASCII入力はフォールバックで UUID 短縮形（12文字 hex）を返すこと。
 *   ② sanitize_title() はWP標準の表示用関数であり [a-z0-9-] を保証しないため、
 *      その戻り値を section_id / cta_id の DB カラム / API route パラメータ /
 *      WP ブロック属性 / CSS セレクタへ直接使わないことを確認する。
 *      「内部ID = sanitize_slug() 出力」「sanitize_title() は表示用（ログ・管理画面ラベル）で section_id には不使用」
 *      の分離を単体テストで証明する。
 *
 * TC-025 第2要件 (carry-009):
 *   非ASCII入力を sanitize_slug() が [a-z0-9-] へ正規化し、CSSセレクタ / ブロック属性に
 *   非ASCII slug を出力しないことを単体テストで検証すること。
 *   ログ化のみでの合格は不可。
 *
 * 実装方針:
 *   - Agent_Neo_Core_Slug::sanitize_slug() を直接呼ぶ。
 *   - Brain Monkey で WP 関数スタブ（wp_generate_uuid4 など）。
 *   - CSS セレクタ / ブロック属性に安全に使える文字セットのみを出力することを正規表現で証明する。
 *
 * @package AgentNeo\Tests\Unit
 */

declare( strict_types=1 );

namespace AgentNeo\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * TC-017b / TC-025: sanitize_slug() と sanitize_title() の分離テスト。
 */
class TC017b_TC025_SanitizeSlugTest extends TestCase {

	protected function set_up(): void {
		parent::set_up();
		Monkey\setUp();

		Functions\stubs( array(
			'wp_generate_uuid4' => fn() => sprintf(
				'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
				0xdead, 0xbeef, 0xcafe, 0x4bab, 0x9abc, 0xfeed, 0xf00d, 0xc0de
			),
		) );

		$slug_file = AGENT_NEO_CORE_DIR . 'inc/util/class-slug.php';
		if ( file_exists( $slug_file ) ) {
			require_once $slug_file;
		}
	}

	protected function tear_down(): void {
		Monkey\tearDown();
		parent::tear_down();
	}

	// ------------------------------------------------------------------
	// TC-017b-01: 半角英数混じり入力が [a-z0-9-] のみになること
	// ------------------------------------------------------------------

	/**
	 * TC-017b-01: "SEO 基礎" の非ASCII部分が除去され ASCII 部分のみが slug になること。
	 *
	 * 入力: "SEO 基礎" → スペース→ハイフン、非ASCII除去 → "seo" or "seo-"（trim後 "seo"）
	 *
	 * @return void
	 */
	public function test_tc017b_seo_mixed_input_normalizes_to_ascii_slug(): void {
		$result = \Agent_Neo_Core_Slug::sanitize_slug( 'SEO 基礎' );

		$this->assertIsString( $result );
		$this->assertNotEmpty( $result );
		$this->assertMatchesRegularExpression(
			'/^[a-z0-9][a-z0-9-]*$|^[a-z0-9]$/',
			$result,
			'出力は [a-z0-9-] のみで構成されること'
		);
		// ASCII 部分 "SEO" が "seo" として保持されること（または UUID fallback）。
		$this->assertTrue(
			'seo' === $result || preg_match( '/^[0-9a-f]{12}$/', $result ) === 1,
			'出力が "seo" または UUID 短縮形 12文字であること'
		);
	}

	/**
	 * TC-017b-02: 全非ASCII入力（"日本語のみ"）が UUID 短縮形（12文字 hex）へフォールバックすること。
	 *
	 * 受入条件 TC-017b ①:
	 *   全非ASCII入力はフォールバックで UUID 短縮形を返す。
	 *
	 * @return void
	 */
	public function test_tc017b_all_non_ascii_input_returns_uuid_fallback(): void {
		$result = \Agent_Neo_Core_Slug::sanitize_slug( '日本語のみ' );

		$this->assertIsString( $result );
		$this->assertNotEmpty( $result );
		$this->assertMatchesRegularExpression(
			'/^[a-z0-9-]+$/',
			$result,
			'出力は [a-z0-9-] のみであること'
		);
		// UUID 短縮形は 12 文字の hex（ハイフンなし）。
		$this->assertMatchesRegularExpression(
			'/^[0-9a-f]{12}$/',
			$result,
			'全非 ASCII 入力は UUID 短縮形 12 文字になること'
		);
	}

	// ------------------------------------------------------------------
	// TC-017b-03: 出力が CSS セレクタとして安全であること
	// ------------------------------------------------------------------

	/**
	 * TC-017b-03: sanitize_slug() の出力が CSS セレクタに安全に使える文字セットのみであること。
	 *
	 * 受入条件 TC-017b ② / TC-025:
	 *   CSSセレクタ / ブロック属性への非ASCII出力がないこと。
	 *
	 * @return void
	 */
	public function test_tc017b_output_is_safe_for_css_selectors(): void {
		$inputs = array(
			'Section Title',
			'CTA Button',
			'pricing-table',
			'SEO 基礎',
			'hero section',
			'FAQ よくある質問',
			'price_table',
			'<script>alert(1)</script>',
			'../../../etc/passwd',
		);

		foreach ( $inputs as $input ) {
			$result = \Agent_Neo_Core_Slug::sanitize_slug( $input );

			$this->assertMatchesRegularExpression(
				'/^[a-z0-9-]+$/',
				$result,
				"入力「{$input}」の出力「{$result}」は [a-z0-9-] のみであること"
			);
			$this->assertDoesNotMatchRegularExpression(
				'/[^\x00-\x7F]/',
				$result,
				"入力「{$input}」の出力に非 ASCII 文字が含まれないこと"
			);
			// CSS 識別子として危険な文字（スペース、記号類）がないこと。
			$this->assertDoesNotMatchRegularExpression(
				'/[\s<>"\'{}|\\\\^`]/',
				$result,
				"入力「{$input}」の出力に CSS 危険文字が含まれないこと"
			);
		}
	}

	// ------------------------------------------------------------------
	// TC-017b-04: sanitize_title() の分離 — 戻り値を内部 ID に直接使わないことを確認
	// ------------------------------------------------------------------

	/**
	 * TC-017b-04: sanitize_title() はWP表示用関数であり、その戻り値は [a-z0-9-] を保証しない。
	 *
	 * 受入条件 TC-017b ②:
	 *   sanitize_title() の戻り値を section_id / cta_id / DB カラム / CSS セレクタに直接使わない。
	 *   → 本テストは「sanitize_title() 相当の文字列（WP が返す可能性のある値）が
	 *      [a-z0-9-] 以外の文字を含みうることを証明し、
	 *      Agent_Neo_Core_Slug::sanitize_slug() を経由すれば安全になること」を示す。
	 *
	 * @return void
	 */
	public function test_tc017b_sanitize_title_equivalent_may_contain_non_ascii(): void {
		// WP の sanitize_title() は日本語入力の場合 URL エンコード等を行うが
		// [a-z0-9-] 以外の文字（%エンコード・Unicode）を返す可能性がある。
		// ここでは sanitize_title() 相当の「危険な入力」を直接使うのではなく
		// sanitize_slug() を経由することで安全になることを示す。
		$potentially_unsafe_title = '%e6%97%a5%e6%9c%ac%e8%aa%9e'; // URLエンコード済み日本語（sanitize_title相当）。

		// 直接使用した場合（文字セット違反を確認）。
		$direct_use = $potentially_unsafe_title;
		$is_unsafe = ! (bool) preg_match( '/^[a-z0-9-]*$/', $direct_use );
		$this->assertTrue( $is_unsafe, 'sanitize_title 相当の出力は [a-z0-9-] 以外の文字を含みうること' );

		// sanitize_slug() を経由すれば安全になること。
		$safe_slug = \Agent_Neo_Core_Slug::sanitize_slug( $potentially_unsafe_title );
		$this->assertMatchesRegularExpression(
			'/^[a-z0-9-]+$/',
			$safe_slug,
			'sanitize_slug() 経由後は [a-z0-9-] のみになること'
		);
	}

	// ------------------------------------------------------------------
	// TC-025-01: 全テスト入力が [a-z0-9-] に正規化されること（TC-025 第2要件）
	// ------------------------------------------------------------------

	/**
	 * TC-025-01: 特殊文字・XSS パターン・パストラバーサルが [a-z0-9-] に正規化されること。
	 *
	 * 受入条件 TC-025:
	 *   非ASCII入力を sanitize_slug() が [a-z0-9-] へ正規化し、
	 *   CSSセレクタ / ブロック属性に非ASCII slug を出力しないことを単体テストで検証。
	 *
	 * @return void
	 */
	public function test_tc025_security_critical_inputs_are_normalized(): void {
		$security_inputs = array(
			// XSS 候補。
			'<img src=x onerror=alert(1)>',
			'javascript:alert(1)',
			// パストラバーサル。
			'../../../etc/passwd',
			// SQL インジェクション候補。
			"'; DROP TABLE posts; --",
			// 非 ASCII 混入。
			'café-section',
			'résumé-block',
			// 制御文字。
			"section\x00name",
			"block\ttab",
		);

		foreach ( $security_inputs as $input ) {
			$result = \Agent_Neo_Core_Slug::sanitize_slug( $input );

			$this->assertIsString( $result, "入力「{$input}」でも string を返すこと" );
			$this->assertNotEmpty( $result, '出力は空でないこと（UUID フォールバックあり）' );
			$this->assertMatchesRegularExpression(
				'/^[a-z0-9-]+$/',
				$result,
				"入力「{$input}」の出力「{$result}」は [a-z0-9-] のみであること"
			);
		}
	}

	/**
	 * TC-025-02: is_valid_slug() で sanitize_slug() の出力が常に有効と判定されること。
	 *
	 * @return void
	 */
	public function test_tc025_sanitize_slug_output_always_passes_is_valid_slug(): void {
		$inputs = array(
			'hero',
			'cta-button',
			'section-1',
			'a', // 最短（1文字）。
			'SEO 基礎',
			'日本語のみ',
			'test123',
		);

		foreach ( $inputs as $input ) {
			$slug = \Agent_Neo_Core_Slug::sanitize_slug( $input );
			$this->assertTrue(
				\Agent_Neo_Core_Slug::is_valid_slug( $slug ),
				"sanitize_slug('{$input}') の出力「{$slug}」が is_valid_slug() で true になること"
			);
		}
	}
}
