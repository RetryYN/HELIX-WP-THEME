<?php
/**
 * CTA / バナー計装 — render_block フィルタで data 属性を付与する。
 *
 * WP_HTML_Tag_Processor（WP6.2+）を使い、出力 HTML に
 * data-agent-neo-affiliate / data-agent-neo-ad を付与する。
 * 既存属性がある場合は二重付与しない。対象なし・HTML 破損時は元文字列を返す（fail-safe）。
 *
 * REQ-NF-025 厳守: AI ロジック・モデル呼び出し・統計判定を含まない。
 * 純粋な文字列操作のみ。
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CTA / バナー計装クラス。
 */
final class Agent_Neo_Core_CTA_Instrumenter {

	/**
	 * render_block フィルタを登録する。
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'render_block', array( $this, 'instrument_block' ), 10, 2 );
	}

	/**
	 * render_block フィルタ本体。
	 *
	 * - `an-cta--<id>` クラスを持つ `<a>` に affiliate 属性を付与する。
	 * - banner 系 div（an-article-cta / an-*-final-cta / an-cta-banner）に ad 属性を付与する。
	 *
	 * @param string              $block_content ブロック出力 HTML。
	 * @param array<string,mixed> $parsed_block  パース済みブロック情報。
	 * @return string
	 */
	public function instrument_block( string $block_content, array $parsed_block ): string {
		// 高速 bail-out: 計装対象キーワードを持たない HTML はそのまま返す。
		if (
			false === strpos( $block_content, 'an-cta--' ) &&
			false === strpos( $block_content, 'an-article-cta' ) &&
			false === strpos( $block_content, 'an-cta-banner' ) &&
			! preg_match( '/\ban-[a-z]+-final-cta\b/', $block_content )
		) {
			return $block_content;
		}

		// WP6.2+ の WP_HTML_Tag_Processor が必須。
		if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
			return $block_content;
		}

		// --- アフィリエイトリンク (<a> に an-cta--<id>) ---
		$block_content = $this->instrument_affiliate_links( $block_content );

		// --- バナー系 (<div> の最外要素に an-article-cta 等) ---
		$block_content = $this->instrument_banner_divs( $block_content );

		return $block_content;
	}

	/**
	 * `an-cta--<id>` クラスを持つ `<a>` に affiliate data 属性を付与する。
	 *
	 * @param string $html 入力 HTML。
	 * @return string
	 */
	private function instrument_affiliate_links( string $html ): string {
		$processor = new WP_HTML_Tag_Processor( $html );

		while ( $processor->next_tag( 'A' ) ) {
			$class_attr = (string) ( $processor->get_attribute( 'class' ) ?? '' );
			$cta_id     = self::extract_cta_id_from_class( $class_attr );

			if ( '' === $cta_id ) {
				continue;
			}

			// 二重付与防止: 既に data-agent-neo-affiliate が付いていればスキップ。
			if ( null !== $processor->get_attribute( 'data-agent-neo-affiliate' ) ) {
				continue;
			}

			$processor->set_attribute( 'data-agent-neo-affiliate', '' );
			$processor->set_attribute( 'data-cta-id', $cta_id );
			$processor->set_attribute( 'data-variant-id', 'default' );
		}

		return $processor->get_updated_html();
	}

	/**
	 * banner 系グループ `<div>` に ad data 属性を付与する。
	 *
	 * `an-article-cta` / `an-*-final-cta` / `an-cta-banner` を持つ最初の `<div>` が対象。
	 *
	 * @param string $html 入力 HTML。
	 * @return string
	 */
	private function instrument_banner_divs( string $html ): string {
		$processor = new WP_HTML_Tag_Processor( $html );

		while ( $processor->next_tag( 'DIV' ) ) {
			$class_attr = (string) ( $processor->get_attribute( 'class' ) ?? '' );

			if ( ! $this->is_banner_class( $class_attr ) ) {
				continue;
			}

			// 二重付与防止: 既に data-agent-neo-ad が付いていればスキップ。
			if ( null !== $processor->get_attribute( 'data-agent-neo-ad' ) ) {
				continue;
			}

			// CTA ID: an-cta--<id> があれば使い、なければ banner クラス名から生成する。
			$cta_id = self::extract_cta_id_from_class( $class_attr );
			if ( '' === $cta_id ) {
				$cta_id = $this->banner_section_id( $class_attr );
			}

			$processor->set_attribute( 'data-agent-neo-ad', '' );
			$processor->set_attribute( 'data-cta-id', $cta_id );
			$processor->set_attribute( 'data-ad-type', 'cta' );

			// バナーは最外 div（最初のヒット）1つだけ計装する。
			break;
		}

		return $processor->get_updated_html();
	}

	/**
	 * class 文字列が banner グループに該当するか判定する。
	 *
	 * 対象: an-article-cta / an-*-final-cta / an-cta-banner。
	 *
	 * @param string $class_attr class 属性値。
	 * @return bool
	 */
	private function is_banner_class( string $class_attr ): bool {
		if ( false !== strpos( $class_attr, 'an-article-cta' ) ) {
			return true;
		}
		if ( false !== strpos( $class_attr, 'an-cta-banner' ) ) {
			return true;
		}
		// an-*-final-cta パターン（an-lp-final-cta / an-home-final-cta 等）。
		if ( preg_match( '/\ban-[a-z]+-final-cta\b/', $class_attr ) ) {
			return true;
		}
		return false;
	}

	/**
	 * banner クラス名から section 識別子を生成する。
	 *
	 * an-article-cta → article_cta、an-lp-final-cta → lp_final_cta 等。
	 *
	 * @param string $class_attr class 属性値。
	 * @return string
	 */
	private function banner_section_id( string $class_attr ): string {
		// an-article-cta を優先。
		if ( preg_match( '/\ban-(article-cta)\b/', $class_attr, $m ) ) {
			return str_replace( '-', '_', $m[1] );
		}
		// an-*-final-cta。
		if ( preg_match( '/\ban-([a-z]+-final-cta)\b/', $class_attr, $m ) ) {
			return str_replace( '-', '_', $m[1] );
		}
		// an-cta-banner。
		if ( false !== strpos( $class_attr, 'an-cta-banner' ) ) {
			return 'cta_banner';
		}
		return 'ad';
	}
}

/**
 * `an-cta--<id>` クラスから CTA ID を抽出する純 helper 関数。
 *
 * WP 非依存（unit テスト可）。
 * `an-cta--<id>` の `<id>` は `[A-Za-z0-9_-]+` のみ有効とする。
 * 対象なし・不正形式の場合は '' を返す。
 *
 * @param string $class_attr class 属性値。
 * @return string
 */
function agent_neo_core_extract_cta_id_from_class( string $class_attr ): string {
	if ( '' === $class_attr ) {
		return '';
	}

	if ( preg_match( '/\ban-cta--([A-Za-z0-9_-]+)\b/', $class_attr, $matches ) ) {
		return $matches[1];
	}

	return '';
}
