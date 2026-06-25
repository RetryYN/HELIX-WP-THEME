<?php
/**
 * SP001: agent_neo_core_is_stripe_payment_url() 単体テスト。
 *
 * 受入条件（stripe-payment-design-spec.md §3 D-ACC / SP-ACC-3）:
 *   - `https://buy.stripe.com/...` と `https://checkout.stripe.com/...`（test_ 含む）は許可。
 *   - `http://`・非 Stripe ドメイン・`buy.stripe.com.evil.com`・`https://buy.stripe.com@evil.com`
 *     ・`javascript:`・空文字 は拒否（ボタン非出力）。
 *   - ユニットテストで全ケース検証。
 *
 * 実装方針:
 *   - inc/payment-link.php を直接 require_once し、純 PHP 関数を呼ぶ。
 *   - WP 関数・Brain Monkey スタブ不要（純関数）。
 *
 * @package AgentNeo\Tests\Unit
 */

declare( strict_types=1 );

namespace AgentNeo\Tests\Unit;

use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * SP001: URL バリデータ網羅テスト。
 */
class SP001_PaymentLinkUrlTest extends TestCase {

	/**
	 * テスト対象関数を require する。
	 *
	 * AGENT_NEO_CORE_DIR は tests/bootstrap-unit.php で定義済み。
	 *
	 * @return void
	 */
	protected function set_up(): void {
		parent::set_up();

		require_once AGENT_NEO_CORE_DIR . 'inc/payment-link.php';
	}

	// ------------------------------------------------------------------
	// 許可ケース（true を期待）
	// ------------------------------------------------------------------

	/**
	 * buy.stripe.com の有効な Payment Link URL は true。
	 *
	 * @return void
	 */
	public function test_buy_stripe_com_valid_returns_true(): void {
		$this->assertTrue(
			agent_neo_core_is_stripe_payment_url( 'https://buy.stripe.com/aEU3cd0000' ),
			'buy.stripe.com の有効 URL は許可されること'
		);
	}

	/**
	 * buy.stripe.com の test_ プレフィックス URL は true。
	 *
	 * @return void
	 */
	public function test_buy_stripe_com_test_prefix_returns_true(): void {
		$this->assertTrue(
			agent_neo_core_is_stripe_payment_url( 'https://buy.stripe.com/test_8wM00abc' ),
			'buy.stripe.com の test_ URL は許可されること'
		);
	}

	/**
	 * checkout.stripe.com の有効な Checkout Session URL は true。
	 *
	 * @return void
	 */
	public function test_checkout_stripe_com_valid_returns_true(): void {
		$this->assertTrue(
			agent_neo_core_is_stripe_payment_url( 'https://checkout.stripe.com/c/pay/cs_test_x' ),
			'checkout.stripe.com の有効 URL は許可されること'
		);
	}

	// ------------------------------------------------------------------
	// 拒否ケース（false を期待）
	// ------------------------------------------------------------------

	/**
	 * 空文字は false。
	 *
	 * @return void
	 */
	public function test_empty_string_returns_false(): void {
		$this->assertFalse(
			agent_neo_core_is_stripe_payment_url( '' ),
			'空文字は拒否されること'
		);
	}

	/**
	 * http:// スキームは false（https 必須）。
	 *
	 * @return void
	 */
	public function test_http_scheme_returns_false(): void {
		$this->assertFalse(
			agent_neo_core_is_stripe_payment_url( 'http://buy.stripe.com/x' ),
			'http スキームは拒否されること'
		);
	}

	/**
	 * 非 Stripe ドメインは false。
	 *
	 * @return void
	 */
	public function test_non_stripe_domain_returns_false(): void {
		$this->assertFalse(
			agent_neo_core_is_stripe_payment_url( 'https://evil.com/x' ),
			'非 Stripe ドメインは拒否されること'
		);
	}

	/**
	 * evil suffix ドメイン（`buy.stripe.com.evil.com`）は false。
	 *
	 * endsWith での判定バグを防ぐ重要ケース。
	 *
	 * @return void
	 */
	public function test_evil_suffix_domain_returns_false(): void {
		$this->assertFalse(
			agent_neo_core_is_stripe_payment_url( 'https://buy.stripe.com.evil.com/x' ),
			'buy.stripe.com.evil.com は拒否されること（endsWith バグ防止）'
		);
	}

	/**
	 * userinfo 付き URL（`https://buy.stripe.com@evil.com/x`）は false。
	 *
	 * parse_url は evil.com をホストとして解釈し、user フィールドに buy.stripe.com が入る。
	 *
	 * @return void
	 */
	public function test_userinfo_url_returns_false(): void {
		$this->assertFalse(
			agent_neo_core_is_stripe_payment_url( 'https://buy.stripe.com@evil.com/x' ),
			'userinfo 付き URL（@evil.com）は拒否されること'
		);
	}

	/**
	 * サブドメイン（`sub.buy.stripe.com`）は false（完全一致のみ許可）。
	 *
	 * @return void
	 */
	public function test_subdomain_returns_false(): void {
		$this->assertFalse(
			agent_neo_core_is_stripe_payment_url( 'https://sub.buy.stripe.com/x' ),
			'サブドメイン (sub.buy.stripe.com) は拒否されること'
		);
	}

	/**
	 * javascript: スキームは false（XSS 対策）。
	 *
	 * @return void
	 */
	public function test_javascript_scheme_returns_false(): void {
		$this->assertFalse(
			agent_neo_core_is_stripe_payment_url( 'javascript:alert(1)' ),
			'javascript: スキームは拒否されること'
		);
	}

	/**
	 * ftp:// スキームは false。
	 *
	 * @return void
	 */
	public function test_ftp_scheme_returns_false(): void {
		$this->assertFalse(
			agent_neo_core_is_stripe_payment_url( 'ftp://buy.stripe.com/x' ),
			'ftp スキームは拒否されること'
		);
	}
}
