<?php
/**
 * HELIX WT prototype 03 — 比較媒体の記事面・404・カテゴリ面。選択軸は ?wt= プレビュー → post meta（記事上書き）→ theme_mod（サイト既定）→ 既定値の順で解決する。
 * PoC 証跡。実装時はプレビュー引数を管理者限定にし、選択 UI（サイトエディター / 記事サイドバー）を付ける。
 */

// ---------- 選択軸（キー => [既定, 許容値]） ----------
function wt_axes() {
	return array(
		'header'   => array( 'search', array( 'search', 'nav', 'cta', 'announce' ) ),
		'sp'       => array( 'search', array( 'search', 'right', 'left' ) ),           // SP ヘッダー: hamburger+search / hamburger-right / hamburger-left
		'eyecatch' => array( 'title-image', array( 'title-image', 'image-title', 'hero', 'side', 'none' ) ),
		'toc'      => array( 'box', array( 'box', 'float', 'collapsible', 'none' ) ),
		'related'  => array( 'grid', array( 'grid', 'list', 'rank', 'carousel' ) ),
		'share'    => array( 'topbottom', array( 'topbottom', 'float', 'none' ) ),
		'motion'   => array( 'off', array( 'off', 'on' ) ),
		'depth'    => array( '0', array( '0', '1', '2' ) ),
		'density'  => array( 'normal', array( 'airy', 'normal', 'compact' ) ),
		'detext'   => array( 'off', array( 'off', 'on' ) ),
		'nf'       => array( 'popular', array( 'popular', 'cta', 'suggest' ) ),    // 404 変種
		'pr'       => array( 'on', array( 'on', 'off' ) ),                          // PR 表記の自動挿入
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
	);
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
		$v = get_theme_mod( 'wt_' . $key, $default );
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
	wp_enqueue_style( 'helix-wt-icons', get_theme_file_uri( 'assets/css/icons.css' ), array(), '0.3.0' );
	wp_enqueue_style( 'helix-wt', get_theme_file_uri( 'assets/css/theme.css' ), array( 'helix-wt-icons' ), '0.3.0' );
	$defer = array( 'strategy' => 'defer' );
	wp_enqueue_script( 'helix-wt-reveal', get_theme_file_uri( 'assets/js/reveal.js' ), array(), '0.3.0', $defer );
	wp_enqueue_script( 'helix-wt-header', get_theme_file_uri( 'assets/js/header.js' ), array(), '0.3.0', $defer );
	wp_enqueue_script( 'helix-wt-contrast', get_theme_file_uri( 'assets/js/contrast.js' ), array(), '0.3.0', $defer );
	if ( is_singular() || is_page() ) {
		wp_enqueue_script( 'helix-wt-article', get_theme_file_uri( 'assets/js/article.js' ), array(), '0.3.0', $defer );
	}
	if ( is_404() ) {
		wp_enqueue_script( 'helix-wt-404', get_theme_file_uri( 'assets/js/notfound.js' ), array(), '0.3.0', $defer );
	}
	wp_enqueue_script( 'helix-wt-footer', get_theme_file_uri( 'assets/js/footer.js' ), array(), '0.3.0', $defer );
	if ( is_category() || is_archive() ) {
		wp_enqueue_script( 'helix-wt-category', get_theme_file_uri( 'assets/js/category.js' ), array(), '0.3.0', $defer );
	}
} );

// ---------- body class: 選択軸を class へ ----------
add_filter( 'body_class', function ( $classes ) {
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
		// h3 向けの控えめな型
		array( 'core/heading', 'wt-bar-thin', '細い左バー（h3）' ),
		array( 'core/heading', 'wt-dotted', '点線下線（h3）' ),
		array( 'core/heading', 'wt-num', '番号前置（h3）' ),
		// 囲み（観察: plain-border / tinted / band-title / tab-title / label-title / shadow-card / check-list）
		array( 'core/group', 'wt-plain-border', '囲み: 罫線' ),
		array( 'core/group', 'wt-tinted', '囲み: 淡塗り' ),
		array( 'core/group', 'wt-band-title', '囲み: 帯タイトル' ),
		array( 'core/group', 'wt-tab-title', '囲み: タブタイトル' ),
		array( 'core/group', 'wt-label-title', '囲み: ラベルタイトル' ),
		array( 'core/group', 'wt-card-shadow', '囲み: 影カード' ),
		array( 'core/group', 'wt-note', '注記（囲み）' ),
		array( 'core/group', 'wt-point', 'ポイント（囲み）' ),
		array( 'core/group', 'wt-warn', '注意（囲み）' ),
		array( 'core/group', 'wt-card', 'カード（罫線）' ),
		array( 'core/group', 'wt-linkcard', 'リンクカード（内部）' ),
		array( 'core/group', 'wt-product', '商品カード束' ),
		array( 'core/group', 'wt-cta-box', 'CTA ボックス（コピー付き）' ),
		array( 'core/group', 'wt-pr', 'PR 表記（控えめ 1 行）' ),
		// リスト
		array( 'core/list', 'wt-check', 'チェックリスト' ),
		array( 'core/list', 'wt-badge-list', '番号バッジリスト' ),
		array( 'core/list', 'wt-icon-list', 'アイコンリスト' ),
		array( 'core/list', 'wt-pros', 'メリット（○）' ),
		array( 'core/list', 'wt-cons', 'デメリット（×）' ),
		// ボタン
		array( 'core/button', 'wt-pill', 'ピル' ),
		array( 'core/button', 'wt-raised', '立体（raised）' ),
		array( 'core/button', 'wt-ghost', 'ゴースト' ),
		// 表
		array( 'core/table', 'wt-compare', '比較表（先頭列固定・SP カード）' ),
		array( 'core/table', 'wt-compare-scroll', '比較表（先頭列固定・SP も横スクロール）' ),
		// 画像・カバー
		array( 'core/image', 'wt-banner', 'バナー画像 CTA' ),
		array( 'core/cover', 'wt-scrim', '自動コントラスト（スクリム）' ),
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
function wt_insert_pr( $content ) {
	if ( 'on' !== wt_opt( 'pr' ) || str_contains( $content, 'class="wt-pr ' ) ) {
		return $content;
	}
	return '<p class="wt-pr is-style-wt-pr"><span class="wt-pr__tag">PR</span>本記事にはアフィリエイト広告を含みます。評価・掲載順は報酬額で決めていません。</p>' . $content;
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
