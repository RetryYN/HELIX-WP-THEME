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
	 * `an-cta--<id>` クラスを持つ要素を起点に `<a>` へ affiliate data 属性を付与する。
	 *
	 * WP 標準の wp:button 構造では an-cta--<id> クラスはラッパ <div> に付き、
	 * 内側の <a>（wp-block-button__link）には付かない。そのため 2 パス方式で対応する:
	 *
	 * パス A（<a> 直付け / 後方互換）:
	 *   an-cta--<id> が <a> 自身に付いている場合 → その <a> を計装。
	 *
	 * パス B（div ラッパ → 内側 <a>）:
	 *   ① <div> スキャンで an-cta--<id> を持つラッパの cta_id を収集する。
	 *   ② 各ラッパに対して対応する内側 <a> を正規表現で特定し、
	 *      WP_HTML_Tag_Processor で計装する。
	 *
	 * パス B の "対応する内側 <a>" の解決:
	 *   an-cta--<id> を持つ <div> の直後の <a> を探す。実装では、
	 *   PHP preg_match で div_open + 内側最初の a_open を抽出し、
	 *   cta_id をマップとして保持する（div[class=an-cta--X] → a[id=Y] の対応）。
	 *   その後 <a> スキャンで href 一致を使って計装する。
	 *
	 * ただし、この preg_match ベースの a 特定は HTML 構造の複雑さに依存する。
	 * よりシンプルかつ堅牢な実装として:
	 *   <div> スキャンで an-cta--<id> の直後に来る href を持つ <a> の href 値を
	 *   preg_match で抽出し、href をキーにした pending マップを作成する。
	 *   次の <a> スキャンで pending マップの href と一致したものを計装する。
	 *
	 * ただし WP_HTML_Tag_Processor は線形でタグ種別を混在スキャンできるため、
	 * 本番実装（実WP）は next_tag() 引数なし走査が可能。
	 * テストスタブは next_tag(string) のみサポートするため、
	 * 2パス（DIV スキャン → A スキャン）方式で互換を保つ。
	 *
	 * REQ-NF-025 厳守: 純粋な文字列操作のみ。AI ロジック不使用。
	 *
	 * @param string $html 入力 HTML。
	 * @return string
	 */
	private function instrument_affiliate_links( string $html ): string {
		// --- パス A: <a> 自身に an-cta--<id> が付いているケース（テキストリンク型 / 後方互換） ---
		$html = $this->instrument_direct_cta_links( $html );

		// --- パス B: div ラッパの an-cta--<id> を起点に内側 <a> を計装 ---
		$html = $this->instrument_wrapper_div_cta_links( $html );

		return $html;
	}

	/**
	 * <a> 自身に an-cta--<id> が付いているケースを計装する（後方互換パス）。
	 *
	 * @param string $html 入力 HTML。
	 * @return string
	 */
	private function instrument_direct_cta_links( string $html ): string {
		$processor = new WP_HTML_Tag_Processor( $html );

		while ( $processor->next_tag( 'A' ) ) {
			$class_attr = (string) ( $processor->get_attribute( 'class' ) ?? '' );
			$cta_id     = agent_neo_core_extract_cta_id_from_class( $class_attr );

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
	 * div ラッパに an-cta--<id> が付いているケースを計装する（WP ボタン構造対応パス）。
	 *
	 * 処理手順:
	 *   1. <div> スキャンで an-cta--<id> を持つラッパを検出する。
	 *   2. ラッパ直後の <a> の href を正規表現で抽出し、href → cta_id の pending マップを作成する。
	 *   3. <a> スキャンで pending マップの href と一致する要素を計装する。
	 *      href が空またはマッチしない場合は、ラッパ直後の最初の <a> を対象とするため、
	 *      div 開始位置インデックスベースの順序マップを使う。
	 *
	 * シンプル実装として: div ラッパ + 内側 <a> を正規表現で一括捕捉し、
	 * <a> タグに data 属性を直接文字列置換する。HTML の入れ子が単純な場合（WP ボタン構造）
	 * は正規表現で安全に処理できる。複雑な入れ子は WP_HTML_Tag_Processor で対応。
	 *
	 * @param string $html 入力 HTML。
	 * @return string
	 */
	private function instrument_wrapper_div_cta_links( string $html ): string {
		// <div> スキャンで an-cta--<id> を持つラッパを検出し、
		// その直後の最初の <a> に data 属性を付与する。
		// WP_HTML_Tag_Processor でスキャン: div → cta_id 収集、a → 照合・計装。

		// --- ステップ 1: div スキャンで an-cta--<id> を持つラッパの cta_id リストを作成 ---
		$div_proc  = new WP_HTML_Tag_Processor( $html );
		$div_ctaids = array(); // cta_id の順序リスト（出現順）

		while ( $div_proc->next_tag( 'DIV' ) ) {
			$class_attr = (string) ( $div_proc->get_attribute( 'class' ) ?? '' );
			$cta_id     = agent_neo_core_extract_cta_id_from_class( $class_attr );
			if ( '' !== $cta_id ) {
				$div_ctaids[] = $cta_id;
			}
		}

		if ( empty( $div_ctaids ) ) {
			// ラッパ <div> に an-cta-- が無い場合はそのまま返す。
			return $html;
		}

		// --- ステップ 2: 各ラッパの直後の <a> を正規表現で特定して計装 ---
		// 正規表現パターン: <div ...class="...an-cta--<id>..."...><a ... />
		// WP ボタン構造は div > a の 1 段入れ子なので、div 開き直後の最初の <a> を対象とする。
		// preg_replace_callback でマッチした <a> に data 属性を追加する。
		foreach ( $div_ctaids as $cta_id ) {
			// --- ラッパ div + 内側最初の <a> を捕捉するパターン ---
			// パターン: div タグ（class に an-cta--{cta_id} を含む）の直後の最初の <a> タグ
			// - an-cta--{cta_id} の直後の <a> のみを対象（div 閉じタグ前）
			// - 既に data-agent-neo-affiliate が付いている <a> はスキップ（二重付与防止）
			$escaped_id = preg_quote( $cta_id, '/' );
			$pattern    = '/(<div\s[^>]*\ban-cta--' . $escaped_id . '\b[^>]*>)((?:(?!<a[\s>]).)*?)(<a\s)([^>]*>)/s';

			$html = preg_replace_callback(
				$pattern,
				static function ( array $m ) use ( $cta_id ): string {
					// $m[1] = <div ...>
					// $m[2] = div と <a> の間のテキスト（空白等）
					// $m[3] = '<a '
					// $m[4] = <a> の残り属性 + '>'

					// <a> が既に data-agent-neo-affiliate を持つ場合はスキップ。
					if ( false !== strpos( $m[4], 'data-agent-neo-affiliate' ) ) {
						return $m[0];
					}

					// data 属性を <a> の末尾（>の直前）に挿入する。
					// $m[4] は '>'' で終わるので、'>' の直前に挿入する。
					// cta_id は agent_neo_core_extract_cta_id_from_class が [A-Za-z0-9_-]+ のみ
					// 許可するためエスケープ不要（REQ-NF-025 / XSS リスクなし）。
					$a_attrs_with_data = rtrim( $m[4], '>' )
						. ' data-agent-neo-affiliate=""'
						. ' data-cta-id="' . $cta_id . '"'
						. ' data-variant-id="default">';

					return $m[1] . $m[2] . $m[3] . $a_attrs_with_data;
				},
				$html,
				1 // 最初のマッチのみ（ラッパ 1 つにつき 1 回）
			) ?? $html; // preg_replace_callback 失敗時は元文字列を返す（fail-safe）
		}

		return $html;
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
			$cta_id = agent_neo_core_extract_cta_id_from_class( $class_attr );
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
