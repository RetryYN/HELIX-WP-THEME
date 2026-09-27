<?php
/**
 * Stripe 決済リンク URL バリデータ。
 *
 * REQ-NF-027 準拠: Stripe ホスト型 URL のみを許可し、任意 URL 転送・オープンリダイレクトを防止する。
 * REQ-NF-025 厳守: WP 関数・AI 判定・API 呼び出しを一切含まない純 PHP 実装。
 * テスト: tests/Unit/SP001_PaymentLinkUrlTest.php
 *
 * 注意: ABSPATH ガードを意図的に付けない。
 *       このファイルは PHPUnit から直接 require_once して単体テスト可能にするため。
 *
 * @package AgentNeoCore
 */

if ( ! function_exists( 'agent_neo_core_is_stripe_payment_url' ) ) {
	/**
	 * 指定 URL が Stripe ホスト型決済リンクとして有効かどうかを検証する。
	 *
	 * 許可条件（すべて満たす場合のみ true）:
	 *   1. 空文字でない。
	 *   2. scheme が https（http 等は拒否）。
	 *   3. userinfo（user / pass）が存在しない（`https://buy.stripe.com@evil.com` 対策）。
	 *   4. host を小文字化し完全一致で許可リスト判定（endsWith 禁止: サブドメイン・evil suffix 対策）。
	 *      許可ホスト: buy.stripe.com / checkout.stripe.com
	 *
	 * @param string $url 検証対象の URL 文字列。
	 * @return bool 有効な Stripe 決済 URL であれば true。
	 */
	function agent_neo_core_is_stripe_payment_url( string $url ): bool {
		// 空文字は拒否。
		if ( '' === $url ) {
			return false;
		}

		// parse_url のみ使用（WP 関数不使用）。
		$parts = parse_url( $url );

		// パース失敗は拒否。
		if ( false === $parts || ! is_array( $parts ) ) {
			return false;
		}

		// scheme が https でなければ拒否（http / ftp / javascript 等）。
		if ( ! isset( $parts['scheme'] ) || 'https' !== strtolower( $parts['scheme'] ) ) {
			return false;
		}

		// userinfo（user / pass）が存在したら拒否（`https://buy.stripe.com@evil.com` 対策）。
		if ( isset( $parts['user'] ) || isset( $parts['pass'] ) ) {
			return false;
		}

		// host が存在しなければ拒否。
		if ( ! isset( $parts['host'] ) || '' === $parts['host'] ) {
			return false;
		}

		// host を小文字化して完全一致で判定。
		// endsWith / strpos 禁止: `sub.buy.stripe.com` / `buy.stripe.com.evil.com` を弾く。
		$host = strtolower( $parts['host'] );

		$allowed_hosts = array(
			'buy.stripe.com',
			'checkout.stripe.com',
		);

		return in_array( $host, $allowed_hosts, true );
	}
}
