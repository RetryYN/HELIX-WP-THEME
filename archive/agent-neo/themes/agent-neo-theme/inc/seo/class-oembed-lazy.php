<?php
/**
 * oEmbed iframe への lazy loading 付与。
 *
 * REQ-F-018 / SNS連携基盤 — oEmbed 最小実装担当。
 * embed_oembed_html フィルタで iframe に loading="lazy" を付与するのみ。
 * 外部 fetch・AI 判定は一切行わない（REQ-NF-025 厳守）。
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * oEmbed 出力の iframe に loading="lazy" を付与するクラス。
 */
final class Agent_Neo_Oembed_Lazy {

	/**
	 * フックを登録する。
	 *
	 * @return void
	 */
	public function register(): void {
		// embed_oembed_html フィルタで iframe を加工する。
		add_filter( 'embed_oembed_html', array( $this, 'add_lazy_loading' ), 10, 1 );
	}

	/**
	 * oEmbed HTML の iframe に loading="lazy" 属性を付与する。
	 *
	 * @param string $html oEmbed HTML 文字列。
	 * @return string
	 */
	public function add_lazy_loading( string $html ): string {
		if ( '' === $html ) {
			return $html;
		}

		// すでに loading 属性がある場合は二重付与しない。
		if ( str_contains( $html, 'loading=' ) ) {
			return $html;
		}

		// <iframe で始まる部分に loading="lazy" を挿入する。
		return (string) str_replace( '<iframe ', '<iframe loading="lazy" ', $html );
	}
}
