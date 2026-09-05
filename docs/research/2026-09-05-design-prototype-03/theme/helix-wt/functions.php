<?php
/**
 * HELIX WT prototype 03 — 比較媒体の記事面・404・カテゴリ面・LP。選択軸は ?wt= プレビュー → post meta（記事上書き）→ theme_mod（サイト既定）→ 既定値の順で解決する。
 * PoC 証跡。実装時はプレビュー引数を管理者限定にし、選択 UI（サイトエディター / 記事サイドバー）を付ける。
 */

// ---------- 選択軸（キー => [既定, 許容値]） ----------
function wt_axes() {
	return array(
		'header'   => array( 'search', array( 'search', 'nav', 'cta', 'announce' ) ),
		'width'    => array( 'default', array( 'narrow', 'default', 'wide' ) ), // 2026-09-05 PO 反応: 本文 / wide / ヘッダー最大幅のプリセット比較（?wt=width:narrow|default|wide）
		'sp'       => array( 'search', array( 'search', 'right', 'left' ) ),           // SP ヘッダー: hamburger+search / hamburger-right / hamburger-left
		'eyecatch' => array( 'title-image', array( 'title-image', 'image-title', 'hero', 'side', 'none' ) ),
		'toc'      => array( 'box', array( 'box', 'float', 'collapsible', 'none' ) ),
		'related'  => array( 'grid', array( 'grid', 'list', 'rank', 'carousel', 'featured', 'ranking-numbers' ) ),
		'share'    => array( 'topbottom', array( 'topbottom', 'float', 'none' ) ),
		'motion'   => array( 'off', array( 'off', 'on' ) ),
		'depth'    => array( '0', array( '0', '1', '2' ) ),
		'density'  => array( 'normal', array( 'airy', 'normal', 'compact' ) ),
		'detext'   => array( 'off', array( 'off', 'on' ) ),
		'nf'       => array( 'popular', array( 'popular', 'cta', 'suggest' ) ),    // 404 変種
		'pr'       => array( 'auto', array( 'auto', 'on', 'off' ) ),                // PR 表記の自動挿入。auto は本文先頭の重複表記を検出して抑止する（2026-09-05 PO 反応5回目）
		'cat_header'    => array( 'name-only', array( 'name-only', 'name-desc', 'hero' ) ),
		'cat_children' => array( 'chips', array( 'none', 'chips', 'cards', 'steps' ) ),
		'cat_list'     => array( 'grid', array( 'grid', 'thumb-list', 'featured-grid' ) ),
		'cat_pagination' => array( 'numbers', array( 'numbers', 'load-more', 'prev-next' ) ),
		'cat_ranking'  => array( 'none', array( 'none', 'sidebar', 'bottom' ) ),
		'cat_minihome' => array( 'off', array( 'off', 'on' ) ),
		'footer_layout' => array( 'sitemap', array( 'sitemap', 'single-row', 'columns-3' ) ),
		'footer_above'  => array( 'none', array( 'none', 'cta-band', 'banner-row', 'newsletter' ) ),
		'footer_legal'  => array( 'copyright-links', array( 'copyright-links', 'copyright-only' ) ),
		'footer_extra'  => array( 'sns', array( 'none', 'sns', 'sites', 'badges', 'address', 'sns-sites', 'sns-badges', 'sns-address', 'sites-badges', 'sites-address', 'badges-address', 'sns-sites-badges', 'sns-sites-address', 'sns-badges-address', 'sites-badges-address', 'all' ) ),
		'footer_totop'  => array( 'off', array( 'off', 'button' ) ),
		'tail_order'    => array( 'related-author-share-cta', array( 'related-author-share-cta', 'cta-related-author-share', 'related-cta-author' ) ),
		'tail_share'    => array( 'none', array( 'none', 'icons-row' ) ),
		'tail_author'   => array( 'none', array( 'none', 'avatar-bio', 'avatar-bio-sns', 'supervisor' ) ),
		'tail_prevnext' => array( 'off', array( 'off', 'thumb' ) ),
		'lp_header'    => array( 'minimal', array( 'minimal', 'logo-only', 'none' ) ),
		'lp_hero'      => array( 'split', array( 'split', 'fullbleed', 'product', 'text-only' ) ),
		'lp_hero_cta'  => array( 'single', array( 'single', 'double', 'form-inline' ) ),
		'lp_sections'  => array( 'full', array( 'full', 'short', 'trust' ) ),
		'lp_cta_style' => array( 'solid', array( 'solid', 'outline', 'pill' ) ),
		'lp_fixed'     => array( 'none', array( 'none', 'sp-bottom-bar', 'float-cta' ) ),
		'lp_legal'     => array( 'on', array( 'on', 'off' ) ),
	);
}

function wt_is_lp_page() {
	// customTemplates は theme.json に登録した slug（拡張子なし）で core に保存される。
	// is_page_template() は保存値との完全一致判定のため、slug 表記・旧 .html 表記の両方を許容する。
	return function_exists( 'is_page_template' ) && is_page_template( array( 'page-lp', 'page-lp.html' ) );
}

function wt_opt( $key ) {
	static $preview = null;
	$axes = wt_axes();
	if ( ! isset( $axes[ $key ] ) ) {
		return null;
	}
	list( $default, $allowed ) = $axes[ $key ];
	if ( null === $preview ) {
		$preview = array();
		if ( isset( $_GET['wt'] ) ) { // PoC: プレビュー引数。例 ?wt=header:cta,toc:float
			foreach ( explode( ',', sanitize_text_field( wp_unslash( $_GET['wt'] ) ) ) as $pair ) {
				$kv = explode( ':', $pair, 2 );
				if ( 2 === count( $kv ) ) {
					$preview[ $kv[0] ] = $kv[1];
				}
			}
		}
	}
	$v = $preview[ $key ] ?? null;
	if ( null === $v && is_singular() && in_array( $key, array( 'eyecatch', 'toc', 'pr', 'share' ), true ) ) {
		$m = get_post_meta( get_queried_object_id(), 'wt_' . $key, true ); // 記事単位の上書き（「この記事では目次を隠す」等）
		if ( '' !== $m ) {
			$v = $m;
		}
	}
	if ( null === $v ) {
		// LP は面の性格に合わせて footer の未設定時だけ 1 行型を既定にする。
		// 明示した theme_mod / プレビュー値は通常どおり優先する。
		$site_default = wt_is_lp_page() && 'footer_layout' === $key ? 'single-row' : $default;
		$v = get_theme_mod( 'wt_' . $key, $site_default );
	}
	return in_array( (string) $v, $allowed, true ) ? (string) $v : $default;
}

add_action( 'after_setup_theme', function () {
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_editor_style( array( 'assets/css/icons.css', 'assets/css/theme.css' ) );
	remove_theme_support( 'core-block-patterns' );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'helix-wt-icons', get_theme_file_uri( 'assets/css/icons.css' ), array(), '0.3.2' );
	wp_enqueue_style( 'helix-wt', get_theme_file_uri( 'assets/css/theme.css' ), array( 'helix-wt-icons' ), '0.3.2' );
	$defer = array( 'strategy' => 'defer' );
	wp_enqueue_script( 'helix-wt-reveal', get_theme_file_uri( 'assets/js/reveal.js' ), array(), '0.3.2', $defer );
	wp_enqueue_script( 'helix-wt-header', get_theme_file_uri( 'assets/js/header.js' ), array(), '0.3.2', $defer );
	wp_enqueue_script( 'helix-wt-contrast', get_theme_file_uri( 'assets/js/contrast.js' ), array(), '0.3.2', $defer );
	if ( is_singular() || is_page() ) {
		wp_enqueue_script( 'helix-wt-article', get_theme_file_uri( 'assets/js/article.js' ), array(), '0.3.2', $defer );
	}
	if ( is_404() ) {
		wp_enqueue_script( 'helix-wt-404', get_theme_file_uri( 'assets/js/notfound.js' ), array(), '0.3.2', $defer );
	}
	wp_enqueue_script( 'helix-wt-footer', get_theme_file_uri( 'assets/js/footer.js' ), array(), '0.3.2', $defer );
	if ( is_category() || is_archive() ) {
		wp_enqueue_script( 'helix-wt-category', get_theme_file_uri( 'assets/js/category.js' ), array(), '0.3.2', $defer );
	}
} );

// ---------- body class: 選択軸を class へ ----------
add_filter( 'body_class', function ( $classes ) {
	// LP 面限定の CSS 分岐（to-top 位置など）が非 LP 面へ漏れないよう、面クラスを別枠で付与する。
	if ( wt_is_lp_page() ) {
		$classes[] = 'wt-face-lp';
	}
	foreach ( wt_axes() as $key => $def ) {
		$classes[] = 'wt-' . $key . '-' . wt_opt( $key );
		$class_key = str_replace( '_', '-', $key );
		if ( $class_key !== $key ) {
			$classes[] = 'wt-' . $class_key . '-' . wt_opt( $key );
		}
	}
	$extra = wt_opt( 'footer_extra' );
	foreach ( array( 'sns', 'sites', 'badges', 'address' ) as $slot ) {
		if ( 'all' === $extra || $extra === $slot || str_contains( $extra, $slot . '-' ) || str_contains( $extra, '-' . $slot ) ) {
			$classes[] = 'wt-footer-extra-' . $slot;
		}
	}
	return $classes;
} );

// ---------- ヘッダー template part の差し替え（header → header-<variant>） ----------
add_filter( 'render_block_data', function ( $block ) {
	if ( 'core/template-part' === $block['blockName'] && isset( $block['attrs']['slug'] ) && 'header' === $block['attrs']['slug'] ) {
		$v = wt_opt( 'header' );
		if ( 'search' !== $v && file_exists( get_theme_file_path( 'parts/header-' . $v . '.html' ) ) ) {
			$block['attrs']['slug'] = 'header-' . $v;
		}
	}
	return $block;
} );

// ---------- block style ----------
add_action( 'init', function () {
	register_block_pattern_category( 'helix-wt', array( 'label' => 'HELIX WT' ) );
	$styles = array(
		// 見出し h2（観察: plain-bold / bottom-border-2tone / icon-prefix / bar-left / underline / band-fill）
		array( 'core/heading', 'wt-plain', '無装飾（太字）' ),
		array( 'core/heading', 'wt-2tone', '下線 2 色' ),
		array( 'core/heading', 'wt-icon', 'アイコン前置' ),
		array( 'core/heading', 'wt-bar', '左バー' ),
		array( 'core/heading', 'wt-underline', '下線' ),
		array( 'core/heading', 'wt-band', '帯（塗り）' ),
		// 2026-09-05 PO 反応2回目: h2 バリエーション強化（+4）。台帳 parts-pattern-taxonomy README §1 の観察型から選定
		array( 'core/heading', 'wt-numbox', '番号ボックス' ),
		array( 'core/heading', 'wt-barbg', '左太罫 + 背景淡色' ),
		array( 'core/heading', 'wt-doubleline', '上下二重線' ),
		array( 'core/heading', 'wt-label', '英字ラベル付き' ),
		// h3 向けの控えめな型
		array( 'core/heading', 'wt-bar-thin', '細い左バー（h3）' ),
		array( 'core/heading', 'wt-dotted', '点線下線（h3）' ),
		array( 'core/heading', 'wt-num', '番号前置（h3）' ),
		// 2026-09-05 PO 反応2回目: h3 バリエーション強化（+2）
		array( 'core/heading', 'wt-marker', '左マーカー（h3）' ),
		array( 'core/heading', 'wt-underline-thin', '下線 細（h3）' ),
		// 囲み（観察: plain-border / tinted / band-title / tab-title / label-title / shadow-card / check-list）
		array( 'core/group', 'wt-plain-border', '囲み: 罫線' ),
		array( 'core/group', 'wt-tinted', '囲み: 淡塗り' ),
		array( 'core/group', 'wt-band-title', '囲み: 帯タイトル' ),
		array( 'core/group', 'wt-tab-title', '囲み: タブタイトル' ),
		array( 'core/group', 'wt-label-title', '囲み: ラベルタイトル' ),
		array( 'core/group', 'wt-card-shadow', '囲み: 影カード' ),
		// 2026-09-05 PO 反応4回目: 囲みバリエーション強化（+5）。台帳 parts-pattern-taxonomy README §1「囲み」の観察型（引用・タブ・チェック等）と
		// Claude 案（Q&A ボックス・番号手順ボックス・warn の強弱2段。PO 指示は「バリエーション追加」まで）から選定。既存7型は変更していない
		array( 'core/group', 'wt-quote', '囲み: 引用風' ),
		array( 'core/group', 'wt-dashed', '囲み: 破線' ),
		array( 'core/group', 'wt-steps', '囲み: 番号手順' ),
		array( 'core/group', 'wt-qa', '囲み: Q&A' ),
		array( 'core/group', 'wt-warn-soft', '囲み: 注意（弱）' ),
		array( 'core/group', 'wt-note', '注記（囲み）' ),
		array( 'core/group', 'wt-point', 'ポイント（囲み）' ),
		array( 'core/group', 'wt-warn', '注意（囲み）' ),
		array( 'core/group', 'wt-card', 'カード（罫線）' ),
		array( 'core/group', 'wt-linkcard', 'ブログカード（内部）' ),
		array( 'core/group', 'wt-blogcard-top', 'ブログカード: 画像上' ),
		array( 'core/group', 'wt-blogcard-band', 'ブログカード: テキスト帯' ),
		array( 'core/group', 'wt-blogcard-ogp', 'ブログカード: 外部 OGP 風' ),
		array( 'core/group', 'wt-product', '商品カード束' ),
		array( 'core/group', 'wt-cta-box', 'CTA ボックス（コピー付き）' ),
		array( 'core/group', 'wt-pr', 'PR 表記（控えめ 1 行）' ),
		array( 'core/group', 'wt-pr-intro', 'PR 表記: 記事上部ラベル' ),
		array( 'core/group', 'wt-pr-inline', 'PR 表記: 見出し横ラベル' ),
		array( 'core/group', 'wt-pr-double', 'PR 表記: 上下 2 箇所' ),
		array( 'core/group', 'wt-pr-band', 'PR 表記: アイコン帯' ),
		// リスト
		array( 'core/list', 'wt-check', 'チェックリスト' ),
		array( 'core/list', 'wt-badge-list', '番号バッジリスト' ),
		array( 'core/list', 'wt-icon-list', 'アイコンリスト' ),
		array( 'core/list', 'wt-pros', 'メリット（○）' ),
		array( 'core/list', 'wt-cons', 'デメリット（×）' ),
		array( 'core/group', 'wt-pros-contrast', 'メリデメ: 2 カラム対比' ),
		array( 'core/group', 'wt-pros-icons', 'メリデメ: ○×アイコン' ),
		array( 'core/group', 'wt-pros-band', 'メリデメ: 帯タイトル箱' ),
		array( 'core/group', 'wt-review-stars', 'レビューバー: 星 + 数値' ),
		array( 'core/group', 'wt-review-bars', 'レビューバー: 項目別 5 本' ),
		array( 'core/group', 'wt-review-score', 'レビューバー: 総合スコア円' ),
		array( 'core/group', 'wt-detext-takeaways', 'detext: 要点 3 カード' ),
		array( 'core/group', 'wt-detext-metrics', 'detext: 数字強調' ),
		array( 'core/group', 'wt-detext-diagram', 'detext: 図解プレースホルダ' ),
		array( 'core/group', 'wt-detext-quote', 'detext: 引用大文字' ),
		// ボタン
		array( 'core/button', 'wt-pill', 'ピル' ),
		array( 'core/button', 'wt-raised', '立体（raised）' ),
		array( 'core/button', 'wt-ghost', 'ゴースト' ),
		// 表
		array( 'core/table', 'wt-compare', '比較表（先頭列固定・SP カード）' ),
		array( 'core/table', 'wt-compare-scroll', '比較表（先頭列固定・SP も横スクロール）' ),
		array( 'core/table', 'wt-compare-striped', '比較表: シンプル縞' ),
		array( 'core/table', 'wt-compare-evaluation', '比較表: 評価セル強調' ),
		array( 'core/table', 'wt-compare-price', '比較表: 価格行ハイライト' ),
		array( 'core/table', 'wt-compare-showdown', '比較表: 2 製品対決' ),
		// 画像・カバー
		array( 'core/image', 'wt-banner', 'バナー画像 CTA' ),
		array( 'core/cover', 'wt-scrim', '自動コントラスト（スクリム）' ),
		array( 'core/cover', 'wt-contrast-white-fade', 'contrast-guard: 白フェード' ),
		array( 'core/cover', 'wt-contrast-overlay-warm', 'contrast-guard: 暖色オーバーレイ' ),
		array( 'core/cover', 'wt-contrast-overlay-cool', 'contrast-guard: 寒色オーバーレイ' ),
		array( 'core/cover', 'wt-contrast-overlay-brand', 'contrast-guard: ブランド色オーバーレイ' ),
		array( 'core/cover', 'wt-contrast-bottom-gradient', 'contrast-guard: 下部グラデーション' ),
		array( 'core/cover', 'wt-contrast-blur-bright', 'contrast-guard: ぼかし + 明度調整' ),
		array( 'core/cover', 'wt-contrast-duotone', 'contrast-guard: デュオトーン風' ),
		array( 'core/quote', 'wt-quote-mark', '引用符つき' ),
	);
	foreach ( $styles as $s ) {
		register_block_style( $s[0], array( 'name' => $s[1], 'label' => $s[2] ) );
	}
	// 記事単位の上書き meta: 許容値は wt_axes() と同じ。allowlist 外は既定値へ丸め、REST schema に enum を出す
	foreach ( array( 'eyecatch', 'toc', 'pr', 'share' ) as $key ) {
		list( $default, $allowed ) = wt_axes()[ $key ];
		register_post_meta( 'post', 'wt_' . $key, array(
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'sanitize_callback' => function ( $value ) use ( $default, $allowed ) {
				if ( '' === $value || null === $value ) {
					return ''; // 元入力が厳密に空 = サイト設定（theme_mod）を継承
				}
				// 非空の入力は sanitize_key 後に allowlist 照合。記号・空白・非 ASCII のみで空になった値も既定値へ丸める（継承にしない）
				$value = is_string( $value ) ? sanitize_key( $value ) : '';
				return in_array( $value, $allowed, true ) ? $value : $default;
			},
			'auth_callback'     => function () { return current_user_can( 'edit_posts' ); },
			'show_in_rest'      => array( 'schema' => array( 'type' => 'string', 'enum' => array_merge( array( '' ), $allowed ), 'default' => '' ) ),
		) );
	}
	register_block_type( 'helix-wt/category-children', array( 'render_callback' => 'wt_render_category_children' ) );
	register_block_type( 'helix-wt/category-minihome', array( 'render_callback' => 'wt_render_category_minihome' ) );
	register_block_type( 'helix-wt/category-ranking', array( 'render_callback' => 'wt_render_category_ranking' ) );
	register_block_type( 'helix-wt/tail-prevnext', array( 'render_callback' => 'wt_render_tail_prevnext' ) );
	register_block_type( 'helix-wt/tail-author', array( 'render_callback' => 'wt_render_tail_author' ) );
} );

// ---------- 目次: h2/h3 機械導出、h2 ≥ 3 で挿入、記事上書き wt_toc=none で非表示 ----------
add_filter( 'the_content', function ( $content ) {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	// 見出しに id を付与
	$n = 0;
	$content = preg_replace_callback( '/<h([23])([^>]*)>(.*?)<\/h\1>/su', function ( $m ) use ( &$n ) {
		if ( preg_match( '/\sid=/', $m[2] ) ) {
			return $m[0];
		}
		$n++;
		return '<h' . $m[1] . $m[2] . ' id="h-' . $n . '">' . $m[3] . '</h' . $m[1] . '>';
	}, $content );

	$variant = wt_opt( 'toc' );
	if ( 'none' === $variant ) {
		return wt_insert_pr( $content );
	}
	if ( ! preg_match_all( '/<h([23])[^>]*\sid="([^"]+)"[^>]*>(.*?)<\/h\1>/su', $content, $ms, PREG_SET_ORDER ) ) {
		return wt_insert_pr( $content );
	}
	$h2 = 0;
	foreach ( $ms as $m ) {
		if ( '2' === $m[1] ) {
			$h2++;
		}
	}
	if ( $h2 < 3 ) { // しきい値（P19 / R44）
		return wt_insert_pr( $content );
	}
	// 木構造へ（h2 の下に h3）
	$tree = array();
	foreach ( $ms as $m ) {
		$node = array( 'id' => $m[2], 'label' => wp_strip_all_tags( $m[3] ), 'children' => array() );
		if ( '2' === $m[1] ) {
			$tree[] = $node;
		} elseif ( $tree ) {
			$tree[ count( $tree ) - 1 ]['children'][] = $node;
		}
	}
	$items = '';
	foreach ( $tree as $t ) {
		$items .= '<li><a href="#' . esc_attr( $t['id'] ) . '">' . esc_html( $t['label'] ) . '</a>';
		if ( $t['children'] ) {
			$items .= '<ol>';
			foreach ( $t['children'] as $c ) {
				$items .= '<li><a href="#' . esc_attr( $c['id'] ) . '">' . esc_html( $c['label'] ) . '</a></li>';
			}
			$items .= '</ol>';
		}
		$items .= '</li>';
	}
	$is_open = in_array( $variant, array( 'box', 'float' ), true ) ? ' open' : '';
	$toc = '<nav class="wt-toc wt-toc--' . esc_attr( $variant ) . '" aria-label="目次" data-wt-toc="' . esc_attr( $variant ) . '">'
		. '<details class="wt-toc__d"' . $is_open . '><summary class="wt-toc__s"><i class="wt-i wt-i--s wt-i--file" aria-hidden="true"></i>この記事の内容<span class="wt-toc__count">' . $h2 . ' 章</span></summary>'
		. '<ol class="wt-toc__list">' . $items . '</ol></details></nav>';
	// 挿入位置: 最初の h2 の直前（リード文の後）
	$pos = strpos( $content, '<h2' );
	$content = false === $pos ? $toc . $content : substr( $content, 0, $pos ) . $toc . substr( $content, $pos );
	return wt_insert_pr( $content );
}, 12 );

// PR 表記（1 行・控えめ）を本文先頭へ。記事 meta wt_pr=off で抑止
// 2026-09-05 PO 反応5回目:「記事本文にすでに PR 表記が入っている場合、自動挿入が重複する」への是正。
// pr:auto（既定）は本文の開示文らしき記述を検出したら自動挿入を抑止する。
// pr:on は検出をせず常に挿入（旧既定の挙動）、pr:off は常に挿入しない。
// 注: 「本文の語を機械判定してよいか」自体は要求 VOCAB-03 の解釈に関わるため、本実装は PoC の是正であり、
// 正本の判定方式（語検出の是非・対象語・範囲）を確定させる決定ではない。
function wt_insert_pr( $content ) {
	$mode = wt_opt( 'pr' );
	if ( 'off' === $mode || str_contains( $content, 'class="wt-pr ' ) ) {
		return $content;
	}
	if ( 'auto' === $mode && wt_content_has_pr_disclosure( $content ) ) {
		return $content;
	}
	return '<p class="wt-pr is-style-wt-pr"><span class="wt-pr__tag">PR</span>本記事にはアフィリエイト広告を含みます。評価・掲載順は報酬額で決めていません。</p>' . $content;
}

// 2026-09-05 PO反応5回目 Astraレビュー是正: 「PR」「広告」等の単純な部分一致だと
// 「広告のない製品」「PROモデル」等に誤検出し（false positive）、逆に本文201字目以降の
// 実際の開示文は見逃す（false negative）。
// 本関数は (1) 開示の話題語（PR / 広告 / アフィリエイト / プロモーション。"PR" は前後が英字でない
// 独立した2文字のときだけ一致させ "PRO" 等を除外）と (2) 開示の述語（含む・含みます・掲載・表記）が
// 同一文（。！？または改行で区切った1文）内に共起する場合だけを開示文とみなす。
// 走査範囲は本文先頭の段落（<p> タグ）を先頭から最大3つ、かつ合計600字までとし、200字の固定長では
// 拾えない201字目以降の開示文にも対応する（それ以降・見出し内の記述は対象外＝既知の限界）。
// 2026-09-05 Astra 再レビュー是正（重大）: 上記 (1)(2) の共起だけでは「広告のない製品を掲載しています」
// 「本記事には広告を含みません」のような**否定文**も開示文として誤検出していた。同一文内に否定語
// （ない・なし・ません・ありません・ございません 等。「含みません」のように動詞へ直結する否定形も
// 部分一致で拾う）があれば、その文は開示文とみなさない（文単位のヒューリスティックのため、
// 無関係な否定表現が同じ文に混在する場合は見逃す方向に倒れる＝既知の限界）。
function wt_content_has_pr_disclosure( $content ) {
	preg_match_all( '/<p[^>]*>(.*?)<\/p>/su', $content, $m );
	$paragraphs = array_slice( $m[1], 0, 3 );
	$plain = trim( wp_strip_all_tags( implode( "\n", $paragraphs ) ) );
	if ( '' === $plain ) {
		// p タグを持たない本文（wp:html 等）へのフォールバック
		$plain = wp_strip_all_tags( $content );
	}
	$plain = mb_substr( $plain, 0, 600 );
	$sentences = preg_split( '/(?<=[。！？])|\n+/u', $plain, -1, PREG_SPLIT_NO_EMPTY );
	$topic    = '/(?<![A-Za-z])PR(?![A-Za-z])|広告|アフィリエイト|プロモーション/u';
	$verb     = '/含み(ます)?|含む|掲載|表記/u';
	$negation = '/ない|なし|ません|ありません|ございません/u';
	foreach ( $sentences as $s ) {
		if ( preg_match( $topic, $s ) && preg_match( $verb, $s ) && ! preg_match( $negation, $s ) ) {
			return true;
		}
	}
	return false;
}

// ---------- 比較表: SP カード化のための data-th を各セルへ付与 ----------
add_filter( 'render_block_core/table', function ( $html, $block ) {
	$cls = $block['attrs']['className'] ?? '';
	if ( ! str_contains( $cls, 'is-style-wt-compare' ) ) {
		return $html;
	}
	if ( ! preg_match( '/<thead>.*?<tr>(.*?)<\/tr>.*?<\/thead>/s', $html, $th ) ) {
		return $html;
	}
	preg_match_all( '/<th[^>]*>(.*?)<\/th>/s', $th[1], $ths );
	$heads = array_map( 'wp_strip_all_tags', $ths[1] );
	// thead 内の th だけに scope="col"（タグ境界を限定し <thead> に誤一致しない。件数制限なし）
	$html  = preg_replace_callback( '/<thead>.*?<\/thead>/s', function ( $thead ) {
		return preg_replace( '/<th(?=[\s>])(?![^>]*\sscope\s*=)([^>]*)>/i', '<th scope="col"$1>', $thead[0] );
	}, $html, 1 );
	// 行見出し変換は <tbody> 内に限定（tfoot は対象外。SP カード CSS も tbody th のみを扱う）
	$html  = preg_replace_callback( '/<tbody>.*?<\/tbody>/s', function ( $tbody ) use ( $heads ) {
		return preg_replace_callback( '/<tr>(.*?)<\/tr>/s', function ( $row ) use ( $heads ) {
			if ( str_contains( $row[1], '<th' ) ) {
				return $row[0];
			}
			$i = 0;
			// 先頭列は行見出し <th scope="row">（開始・終了タグとも、data-th なし）。データセル td だけが data-th で列見出しを持つ
			$r = preg_replace_callback( '/<td([^>]*)>(.*?)<\/td>/s', function ( $td ) use ( &$i, $heads ) {
				$label = $heads[ $i ] ?? '';
				$out   = 0 === $i
					? '<th' . $td[1] . ' scope="row">' . $td[2] . '</th>'
					: '<td' . $td[1] . ' data-th="' . esc_attr( $label ) . '">' . $td[2] . '</td>';
				$i++;
				return $out;
			}, $row[1] );
			return '<tr>' . $r . '</tr>';
		}, $tbody[0] );
	}, $html, 1 );
	return $html;
}, 10, 2 );

// ---------- 関連記事: queryId 901 = 次に読む（同カテゴリ優先）、902 = 関連、903 = 404 人気。現在の記事を除外 ----------
add_filter( 'query_loop_block_query_vars', function ( $query, $block ) {
	$qid = (int) ( $block->context['queryId'] ?? 0 );
	if ( ! in_array( $qid, array( 901, 902, 903 ), true ) ) {
		return $query;
	}
	if ( is_singular() ) {
		$id                    = get_queried_object_id();
		$query['post__not_in'] = array( $id );
		if ( 901 === $qid ) {
			$cats = wp_get_post_categories( $id );
			if ( $cats ) {
				$query['category__in'] = $cats;
			}
		}
	}
	if ( 903 === $qid ) { // 人気: PoC では新着順で代替（集計方式は Issue #110）
		$query['orderby'] = 'date';
	}
	return $query;
}, 10, 2 );

// ---------- 404: HTTP 404 は WP 既定（template 404.html）。noindex を明示 ----------
add_action( 'wp_head', function () {
	if ( is_404() ) {
		echo '<meta name="robots" content="noindex">' . "\n";
	}
} );

// ---------- 見出し: scroll-margin-top は CSS。アイコン前置の既定アイコンはデータ属性で切替可 ----------
add_filter( 'render_block_core/heading', function ( $html, $block ) {
	$cls = $block['attrs']['className'] ?? '';
	if ( str_contains( $cls, 'is-style-wt-icon' ) && ! str_contains( $html, 'data-wt-icon' ) ) {
		$html = preg_replace( '/^<h(\d)/', '<h$1 data-wt-icon="check-circle"', $html, 1 );
	}
	return $html;
}, 10, 2 );

// ---------- 画像: alt 未設定の装飾画像は alt="" を保証、eyecatch は fetchpriority ----------
add_filter( 'wp_get_attachment_image_attributes', function ( $attr ) {
	if ( ! isset( $attr['alt'] ) ) {
		$attr['alt'] = '';
	}
	return $attr;
} );

// ---------- 段 3 の表示用動的ブロック（取得・表示のみ。判定や外部 API は持たない） ----------
function wt_current_category_term() {
	$term = get_queried_object();
	return ( $term instanceof WP_Term && 'category' === $term->taxonomy ) ? $term : null;
}

function wt_category_card_image( $post, $size = 'medium_large' ) {
	$image = get_the_post_thumbnail( $post, $size, array( 'class' => 'wt-data-card__image' ) );
	return $image ? wp_kses_post( $image ) : '<span class="wt-data-card__image wt-data-card__image--empty" aria-hidden="true">画像</span>';
}

function wt_category_card_markup( $post, $term_name = '' ) {
	$title = get_the_title( $post );
	$link  = get_permalink( $post );
	$date  = get_the_date( 'Y.m.d', $post );
	$terms = get_the_category( $post );
	$chip  = $term_name;
	if ( '' === $chip && $terms ) {
		$chip = $terms[0]->name;
	}
	$chip_link = $terms ? get_category_link( $terms[0]->term_id ) : '#';
	if ( is_wp_error( $chip_link ) ) {
		$chip_link = '#';
	}
	$out = '<article class="wt-data-card">';
	$out .= '<a class="wt-data-card__media" href="' . esc_url( $link ) . '">' . wt_category_card_image( $post ) . '</a>';
	$out .= '<div class="wt-data-card__body">';
	if ( '' !== $chip ) {
		$out .= '<a class="wt-data-card__chip" href="' . esc_url( $chip_link ) . '">' . esc_html( $chip ) . '</a>';
	}
	$out .= '<h3 class="wt-data-card__title"><a href="' . esc_url( $link ) . '">' . esc_html( $title ) . '</a></h3>';
	$out .= '<time class="wt-data-card__date" datetime="' . esc_attr( get_the_date( 'c', $post ) ) . '">' . esc_html( $date ) . '</time>';
	$out .= '<p class="wt-data-card__excerpt">' . esc_html( wp_trim_words( get_the_excerpt( $post ), 28, '…' ) ) . '</p>';
	$out .= '</div></article>';
	return $out;
}

function wt_render_category_children() {
	$term = wt_current_category_term();
	if ( ! $term ) {
		return '';
	}
	$children = get_terms( array(
		'taxonomy'   => 'category',
		'parent'     => (int) $term->term_id,
		'hide_empty' => false,
		'orderby'    => 'term_id',
		'order'      => 'ASC',
	) );
	if ( is_wp_error( $children ) || ! $children ) {
		return '<nav class="wt-cat-children wt-cat-children--none" aria-label="子カテゴリ"></nav>';
	}
	$variant = wt_opt( 'cat_children' );
	if ( 'none' === $variant ) {
		return '<nav class="wt-cat-children wt-cat-children--none" aria-label="子カテゴリ"></nav>';
	}
	$out = '<nav class="wt-cat-children wt-cat-children--' . esc_attr( $variant ) . '" aria-label="子カテゴリ"><ul>';
	foreach ( $children as $child ) {
		$url   = get_term_link( $child );
		$count = number_format_i18n( (int) $child->count );
		if ( is_wp_error( $url ) ) {
			continue;
		}
		$label = '<span class="wt-cat-child__name">' . esc_html( $child->name ) . '</span><span class="wt-cat-child__count">' . esc_html( $count ) . '件</span>';
		$out  .= '<li><a href="' . esc_url( $url ) . '">' . $label . '</a>';
		if ( 'cards' === $variant ) {
			$out .= '<span class="wt-cat-child__desc">' . esc_html( wp_trim_words( $child->description, 12, '…' ) ) . '</span>';
		}
		$out .= '</li>';
	}
	return $out . '</ul></nav>';
}

function wt_render_category_ranking() {
	$term = wt_current_category_term();
	if ( ! $term ) {
		return '';
	}
	$posts = get_posts( array(
		'category'       => (int) $term->term_id,
		'posts_per_page' => 3,
		'post_status'    => 'publish',
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );
	if ( ! $posts ) {
		return '';
	}
	$out = '<aside class="wt-cat-ranking" aria-labelledby="wt-cat-ranking-title"><h2 id="wt-cat-ranking-title">このカテゴリのランキング</h2><ol>';
	foreach ( $posts as $index => $post ) {
		$out .= '<li><a href="' . esc_url( get_permalink( $post ) ) . '"><span class="wt-cat-ranking__num">' . esc_html( (string) ( $index + 1 ) ) . '</span><span>' . esc_html( get_the_title( $post ) ) . '</span></a></li>';
	}
	return $out . '</ol></aside>';
}

function wt_render_category_minihome() {
	$term = wt_current_category_term();
	if ( ! $term ) {
		return '';
	}
	$children = get_terms( array(
		'taxonomy'   => 'category',
		'parent'     => (int) $term->term_id,
		'hide_empty' => false,
		'orderby'    => 'term_id',
		'order'      => 'ASC',
	) );
	if ( is_wp_error( $children ) || ! $children ) {
		return '<section class="wt-cat-minihome" aria-label="カテゴリ ミニ HOME"><p>子カテゴリの一覧は準備中です。</p></section>';
	}
	$out = '<section class="wt-cat-minihome" aria-labelledby="wt-cat-minihome-title"><div class="wt-cat-minihome__intro"><p class="wt-eyebrow">CATEGORY GUIDE</p><h2 id="wt-cat-minihome-title">読む順番</h2><ol class="wt-cat-reading-order">';
	foreach ( $children as $child ) {
		$url = get_term_link( $child );
		if ( is_wp_error( $url ) ) {
			continue;
		}
		$out .= '<li><a href="' . esc_url( $url ) . '">' . esc_html( $child->name ) . '</a></li>';
	}
	$out .= '</ol></div><div class="wt-cat-minihome__sections">';
	foreach ( $children as $child ) {
		$url   = get_term_link( $child );
		$posts = get_posts( array(
			'category'       => (int) $child->term_id,
			'posts_per_page' => 4,
			'post_status'    => 'publish',
			'orderby'        => 'date',
			'order'          => 'DESC',
		) );
		if ( is_wp_error( $url ) || ! $posts ) {
			continue;
		}
		$out .= '<section class="wt-cat-mini-section"><header><div><p class="wt-eyebrow">SECTION</p><h2>' . esc_html( $child->name ) . '</h2></div><a class="wt-cat-mini-section__more" href="' . esc_url( $url ) . '">一覧へ</a></header><div class="wt-cat-mini-grid">';
		foreach ( $posts as $post ) {
			$out .= wt_category_card_markup( $post, $child->name );
		}
		$out .= '</div></section>';
	}
	$out .= '</div><div class="wt-cat-minihome__rank"><h2>このカテゴリのランキング</h2><ol>';
	$rank = get_posts( array(
		'category'       => (int) $term->term_id,
		'posts_per_page' => 3,
		'post_status'    => 'publish',
		'orderby'        => 'date',
		'order'          => 'DESC',
	) );
	foreach ( $rank as $index => $post ) {
		$out .= '<li><a href="' . esc_url( get_permalink( $post ) ) . '"><b>' . esc_html( (string) ( $index + 1 ) ) . '</b>' . esc_html( get_the_title( $post ) ) . '</a></li>';
	}
	return $out . '</ol></div></section>';
}

function wt_render_tail_prevnext() {
	$previous = get_previous_post();
	$next     = get_next_post();
	if ( ! $previous && ! $next ) {
		return '';
	}
	$out = '<nav class="wt-tail__prevnext" aria-label="前後の記事">';
	foreach ( array( 'previous' => $previous, 'next' => $next ) as $direction => $post ) {
		if ( ! $post ) {
			continue;
		}
		$label = 'previous' === $direction ? '前の記事' : '次の記事';
		$out  .= '<a class="wt-tail__prevnext-link wt-tail__prevnext-link--' . esc_attr( $direction ) . '" href="' . esc_url( get_permalink( $post ) ) . '"><span class="wt-tail__prevnext-label">' . esc_html( $label ) . '</span>' . wt_category_card_image( $post, 'thumbnail' ) . '<strong>' . esc_html( get_the_title( $post ) ) . '</strong></a>';
	}
	return $out . '</nav>';
}

// 著者ボックス 3 型（avatar-bio / avatar-bio-sns / supervisor）。template part 内では core の post-author 系ブロックが postId context を持たず空になるため、
// 記事 ID から PHP で描く。アバターは外部サービスへ問い合わせず、表示名の頭文字を丸く出す（第三者ロゴ・外部 API なし）。
function wt_render_tail_author() {
	$post_id = get_queried_object_id();
	if ( ! $post_id || 'post' !== get_post_type( $post_id ) ) {
		return '';
	}
	$author_id = (int) get_post_field( 'post_author', $post_id );
	$name      = get_the_author_meta( 'display_name', $author_id );
	$bio       = get_the_author_meta( 'description', $author_id );
	$initial   = function_exists( 'mb_substr' ) ? mb_substr( $name, 0, 1 ) : substr( $name, 0, 1 );
	$avatar    = '<div class="wt-author-box__avatar"><span class="wt-author-box__initial" aria-hidden="true">' . esc_html( $initial ) . '</span></div>';
	$name_html = '<p class="wt-author-box__name">' . esc_html( $name ) . '</p>';
	$bio_html  = $bio ? '<p class="wt-author-box__bio">' . esc_html( $bio ) . '</p>' : '';
	$sns       = '<div class="wt-author-sns" aria-label="著者の共有先"><a href="#" aria-label="共有先 1">1</a><a href="#" aria-label="共有先 2">2</a><a href="#" aria-label="共有先 3">3</a></div>';
	$variants  = array(
		'avatar-bio'     => $avatar . '<div><p class="wt-author-box__label">この記事を書いた人</p>' . $name_html . $bio_html . '</div>',
		'avatar-bio-sns' => $avatar . '<div><p class="wt-author-box__label">この記事を書いた人</p>' . $name_html . $bio_html . $sns . '</div>',
		'supervisor'     => $avatar . '<div><p class="wt-author-box__label">監修者</p>' . $name_html . '<p class="wt-author-box__bio">内容を確認した担当者の紹介です。</p></div>',
	);
	$out = '';
	foreach ( $variants as $key => $inner ) {
		$out .= '<div class="wt-author-variant wt-author-variant--' . esc_attr( $key ) . '"><div class="wt-author-box">' . $inner . '</div></div>';
	}
	return $out;
}

// PO 反応7（related 再設計、Claude 案）: アイキャッチ未設定の投稿でもカードの 16:9 サムネ枠を崩さないよう、
// フロントの投稿カードだけ既定画像（同梱の無文字グラデーション lum-mid.jpg）を返す。管理画面・添付ページは対象外。
add_filter( 'post_thumbnail_html', function ( $html, $post_id, $thumbnail_id ) {
	if ( '' !== $html || $thumbnail_id || is_admin() || 'post' !== get_post_type( $post_id ) ) {
		return $html;
	}
	$src = get_theme_file_uri( 'assets/img/lum-mid.jpg' );
	return '<img class="wt-thumb-fallback" src="' . esc_url( $src ) . '" alt="" width="1600" height="900" loading="lazy" decoding="async" />';
}, 10, 3 );
