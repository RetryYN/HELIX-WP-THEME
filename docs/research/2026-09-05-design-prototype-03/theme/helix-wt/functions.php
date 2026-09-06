<?php
/**
 * HELIX WT prototype 03 — 比較媒体の記事面・404・カテゴリ面・LP。選択軸は ?wt= プレビュー → post meta（記事上書き）→ theme_mod（サイト既定）→ 既定値の順で解決する。
 * PoC 証跡。実装時はプレビュー引数を管理者限定にし、選択 UI（サイトエディター / 記事サイドバー）を付ける。
 */

// ---------- 選択軸（キー => [既定, 許容値]） ----------
function wt_axes() {
	return array(
		// 2026-09-06 PO 反応 17 回目 WT-EVT-0270「ヘッダーバリエーション増やそうか」: 台帳 §1 header レイアウトの観察型から +5（Claude 案）
		// center = logo-center-nav-below / two-rows / overlay = transparent-over-hero（eyecatch:hero と併用）/ tel = with-tel / band = テーマ A/B の帯色型
		'header'   => array( 'search', array( 'search', 'nav', 'cta', 'announce', 'center', 'two-rows', 'overlay', 'tel', 'band' ) ),
		'width'    => array( 'default', array( 'narrow', 'default', 'wide' ) ), // 2026-09-05 PO 反応: 本文 / wide / ヘッダー最大幅のプリセット比較（?wt=width:narrow|default|wide）
		'sp'       => array( 'search', array( 'search', 'right', 'left', 'cta', 'text-nav', 'center-logo' ) ), // SP ヘッダー: hamburger+search / hamburger-right / hamburger-left + WT-EVT-0270: hamburger+cta / no-hamburger(text nav) / logo-center（検索左・≡ 右）
		'eyecatch' => array( 'title-image', array( 'title-image', 'image-title', 'hero', 'side', 'none' ) ),
		'toc'      => array( 'box', array( 'box', 'float', 'collapsible', 'none' ) ),
		'related'  => array( 'grid', array( 'grid', 'list', 'rank', 'carousel', 'featured', 'ranking-numbers', 'slider' ) ), // slider: PO 反応 16 回目 WT-EVT-0261
		'share'    => array( 'topbottom', array( 'topbottom', 'float', 'none' ) ),
		'motion'   => array( 'off', array( 'off', 'on' ) ),
		'depth'    => array( '0', array( '0', '1', '2', 'float' ) ), // float: PO 反応 16 回目 WT-EVT-0263（浮遊演出、Claude 案）
		'density'  => array( 'normal', array( 'airy', 'normal', 'compact' ) ),
		'detext'   => array( 'off', array( 'off', 'on' ) ),
		'nf'       => array( 'popular', array( 'popular', 'cta', 'suggest' ) ),    // 404 変種
		'pr'       => array( 'auto', array( 'auto', 'on', 'off' ) ),                // PR 表記の自動挿入。auto は本文先頭の重複表記を検出して抑止する（2026-09-05 PO 反応5回目）
		// 2026-09-06 PO 反応 18 回目 WT-EVT-0283「強化してくれ」（カテゴリ面）。既定と型は台帳 category-recapture 主集計 n=54（Astra レビュー済み）の観察値
		'cat_header'    => array( 'name-count', array( 'name-count', 'name-only', 'name-desc', 'hero' ) ), // name-count 68% / name-only 17% / name-desc 9% / name-desc-image 6%（= hero）
		'cat_lead'      => array( 'none', array( 'none', 'lead-text', 'editorial' ) ), // none 78% / lead-text 13% / editorial-article 9%
		'cat_children'  => array( 'chips', array( 'none', 'chips', 'cards', 'steps', 'sidebar-tree', 'image-banners' ) ), // chips 40% / none 38% / sidebar-tree 15% / cards 6% / image-banners 2%（steps は試作 02 の型）
		'cat_columns'   => array( 'sidebar-right', array( 'sidebar-right', '1col' ) ), // 2col-sidebar-right 69% / 1col 31%
		'cat_sidebar'   => array( 'standard', array( 'standard', 'with-cta', 'full' ) ), // sidebar 内容: categories 67% / popular 57%（standard）+ cta-banner 33%（with-cta）+ archive 20% / search 19% / new-posts 15% / profile 13% / tags 13%（full）
		'cat_list'      => array( 'grid', array( 'grid', 'text-list', 'featured-grid', 'grid-2', 'thumb-list', 'timeline' ) ), // grid-3 43% / text-list 39% / featured-plus-grid 7% / grid-2 6% / thumb-list 4% / timeline 2%
		'cat_card'      => array( 'standard', array( 'standard', 'minimal', 'rich' ) ), // 要素: date 76% / category-chip 63% / excerpt 59% / image 59%（standard）。author / new-badge / tags 各 11%（rich）
		'cat_filter'    => array( 'none', array( 'none', 'tabs', 'year', 'tag', 'sort' ) ), // none 63% / tabs 15% / year-filter 11% / tag-filter 9% / sort-select 2%
		'cat_pagination' => array( 'numbers', array( 'numbers', 'none', 'load-more', 'prev-next' ) ), // numbers 43% / none 39% / load-more 9% / prev-next 9%
		'cat_ranking'   => array( 'none', array( 'none', 'sidebar', 'bottom', 'top' ) ), // sidebar 44% / none 43% / top 13%。既定 none は sidebar の popular（cat_sidebar）が sidebar 型の多数派を担うため
		'cat_pickup'    => array( 'none', array( 'none', 'top-featured', 'editor-pick-box' ) ), // none 52% / top-featured 35% / editor-pick-box 13%
		'cat_cta'       => array( 'none', array( 'none', 'lp-banner', 'newsletter', 'line' ) ), // none 54% / lp-banner 22% / newsletter 11% / line 7%。app-download 6% は第三者ストアのバッジ画像が要るため置かない
		'cat_minihome'  => array( 'on', array( 'off', 'on' ) ), // yes 60%（na 7 除外）。PO 採用済み（WT-EVT-0228 / 0284）→ 既定 on。一覧の下に共存（置き換えない）
		'footer_layout' => array( 'sitemap', array( 'sitemap', 'single-row', 'columns-3' ) ),
		'footer_above'  => array( 'none', array( 'none', 'cta-band', 'banner-row', 'newsletter' ) ),
		'footer_legal'  => array( 'copyright-links', array( 'copyright-links', 'copyright-only' ) ),
		'footer_extra'  => array( 'sns', array( 'none', 'sns', 'sites', 'badges', 'address', 'sns-sites', 'sns-badges', 'sns-address', 'sites-badges', 'sites-address', 'badges-address', 'sns-sites-badges', 'sns-sites-address', 'sns-badges-address', 'sites-badges-address', 'all' ) ),
		'footer_totop'  => array( 'off', array( 'off', 'button' ) ),
		'footer_credit' => array( 'none', array( 'none', 'text' ) ), // PO 反応 16 回目 WT-EVT-0266（テーマ名クレジット、Claude 案・既定 none）
		'tail_order'    => array( 'related-author-share-cta', array( 'related-author-share-cta', 'cta-related-author-share', 'related-cta-author' ) ),
		'tail_share'    => array( 'none', array( 'none', 'icons-row' ) ),
		'tail_author'   => array( 'none', array( 'none', 'avatar-bio', 'avatar-bio-sns', 'supervisor' ) ),
		'tail_prevnext' => array( 'off', array( 'off', 'thumb' ) ),
		'lp_header'    => array( 'minimal', array( 'minimal', 'logo-only', 'none' ) ),
		'lp_hero'      => array( 'split', array( 'split', 'fullbleed', 'product', 'text-only' ) ),
		'lp_hero_cta'  => array( 'single', array( 'single', 'double', 'form-inline' ) ),
		'lp_sections'  => array( 'full', array( 'full', 'short', 'trust', 'extended' ) ), // extended: 段4 の全区間 + PO 反応 16 回目（WT-EVT-0268）の LP パーツ 7 種
		'lp_cta_style' => array( 'solid', array( 'solid', 'outline', 'pill' ) ),
		'lp_fixed'     => array( 'none', array( 'none', 'sp-bottom-bar', 'float-cta', 'line-sticky' ) ),
		// LP パーツ 7 種（WT-EVT-0268、Claude 案）。既定は台帳 lp-recapture の多数派の型
		'lp_interview' => array( 'summary-card', array( 'summary-card', 'link-card', 'logo-only' ) ),
		'lp_review'    => array( 'quote-photo', array( 'quote-photo', 'stars-count', 'satisfaction-number' ) ),
		'lp_rating'    => array( 'certification', array( 'certification', 'client-logos', 'award-badge' ) ),
		'lp_download'  => array( 'button-to-form', array( 'button-to-form', 'form-inline' ) ),
		'lp_form'      => array( 'external', array( 'external', 'inline' ) ),
		'lp_line'      => array( 'button', array( 'button', 'qr' ) ),
		'lp_legal'     => array( 'on', array( 'on', 'off' ) ),
		// 2026-09-06 PO 反応 17 回目 WT-EVT-0277「HP ページは？イベントとかが組めるページは？」（Claude 案）。
		// 既定は台帳 research-r17（HP 39 件 / イベント個別募集ページ 8 件（取得 20 件から page_kind 除外後）、Astra レビュー済み）の最多型。n が小さい区分の型は「選べる型」として置く
		'home_hero'     => array( 'text-only', array( 'text-only', 'slider', 'fullbleed', 'split', 'article-grid', 'video', 'cards-carousel', 'product-shot', 'search-box' ) ), // HP n=39: text-only 33% / slider 26% / fullbleed 21%（台帳 home-event-recapture）。段8（WT-EVT-0287、台帳 home-event-recapture-v2 n=61）: fullbleed 34% / slider 28% / cards-carousel 11% / product-shot 7% / search-box 2% を追加。既定は据え置き（両台帳で最多型が一致しないため）
		'home_hero_cta' => array( 'double', array( 'double', 'single', 'none', 'tel-button', 'search' ) ), // double 62%（CTA 2 つ。用途は home_contact）。段8: search（検索欄。v2 で 2 件）
		'home_sections' => array( 'corporate', array( 'corporate', 'service', 'media', 'shop-school', 'school-org' ) ), // 用途別の区間セット。段8: shop-school（店舗・スクール D）/ school-org（学校法人・団体 E）を v2 の区分別上位区間から追加。段9（WT-EVT-0288「全部追加」）: corporate に greeting（A 65%）、service に logos（B 44%）を追加
		'home_news'     => array( 'list-with-date', array( 'list-with-date', 'tabs', 'cards', 'none' ) ), // list-with-date 46%
		'home_contact'  => array( 'tel-form', array( 'tel-form', 'form-only', 'tel-only', 'line', 'none', 'double-cta' ) ), // tel+form 36%。段8: double-cta（問い合わせ + 資料請求の 2 面。v2 hero_cta=double 44% の受け皿）
		'home_fixed'    => array( 'none', array( 'none', 'float-cta', 'sp-bottom-bar', 'float-tel' ) ), // sticky-header はヘッダー既定で常時
		'event_hero'     => array( 'date-place-block', array( 'key-visual', 'photo-overlay', 'date-place-block', 'text-only' ) ), // 主集計 n=8: key-visual 50% / photo-overlay 25%（参考: 取得全体 n=20 では photo-overlay 55%）。段9（WT-EVT-0288「全部追加」）: 既定を台帳 v2 主集計 n=40 の最多型 date-place-block（62%）へ変更（台帳 §5 規則の例外を PO が承認）
		'event_info'     => array( 'inline-text', array( 'inline-text', 'table', 'icon-list', 'none' ) ), // 主集計 n=8: inline-text 62%
		'event_schedule' => array( 'none', array( 'none', 'table', 'timeline', 'accordion' ) ), // 主集計 n=8: none 38% / table 25% / timeline 12%
		'event_speakers' => array( 'none', array( 'none', 'cards-photo', 'list', 'single-profile' ) ), // 主集計 n=8: none 50% / cards-photo 25%
		'event_sections' => array( 'seminar', array( 'seminar', 'seminar-classic', 'conference', 'festival', 'campaign' ) ), // 段8: 区間セット 5 種。段9（WT-EVT-0288）: seminar（既定）は v2 主集計 A の上位区間構成（対象者・主催あり）、seminar-classic は段 7 までの従来構成。B・C は小標本のため「選べる型」
		'event_apply'    => array( 'inline-form', array( 'inline-form', 'external-form', 'ticket-link', 'closed-notice', 'receipt-upload', 'postcard', 'messaging-app' ) ), // 主集計 n=8: inline-form 38% / external-form 25% / closed-notice 25% / ticket 12%。段8: receipt-upload / postcard / messaging-app（v2 の other:* 3 語、キャンペーン D の応募経路）
		'event_status'   => array( 'open', array( 'open', 'none', 'few-seats', 'ended' ) ), // 主集計 n=8: open 62% / ended 25% / none 12%。few-seats の実例は 0（観測不足）
		'event_map'      => array( 'none', array( 'none', 'static-image', 'text-only', 'embed' ) ), // 主集計 n=8: none 50% / text-only 50%。embed は外部地図の埋め込み（WT-EVT-0284: WT-CAND-SNS の埋め込み方針＝遅延読込・URL は option・鍵はテーマに置かない）
		'event_fixed'    => array( 'none', array( 'none', 'sp-bottom-bar', 'float-apply' ) ), // 主集計 n=8: none 100%（観察に無い型を Claude 案として追加）
		'event_share'    => array( 'none', array( 'none', 'icons', 'add-to-calendar' ) ),
		// 段 10（2026-09-06 PO 反応 21 回目 WT-EVT-0289「進めて」、台帳 research-r21 sidebar n=48）: 記事・固定ページ・HP に共通のサイドバーとサイドナビ。
		// 既定 side_layout=none は据え置き（観察は記事で right 92% だが、記事面の既定変更は PO 判断待ち。README §2.30）
		'side_layout' => array( 'none', array( 'none', 'right', 'left', 'both' ) ), // right 85% / both 10% / left 2% / none 2%
		'side_sticky' => array( 'last-widget', array( 'none', 'whole', 'last-widget', 'toc-only' ) ), // last-widget 48% / toc-only 34% / none 14% / whole 5%
		'side_sp'     => array( 'below-content', array( 'below-content', 'drawer', 'hidden' ) ), // 要約では判定できないため 3 型を持つ（前回台帳 article/sp は none 93%）
		'side_set'    => array( 'media', array( 'media', 'blog', 'owned', 'corporate', 'minimal', 'full' ) ), // 区分別の上位ウィジェット順（C / P / B / 固定ページ・HP / 最小 / 全種）
		'side_nav'    => array( 'none', array( 'none', 'mega-menu', 'fixed-left-nav', 'fixed-right-icons', 'drawer-pc', 'toc-side' ) ), // mega-menu 35% / none 35% / toc-side 23% / 他 2% ずつ // 主集計 n=8: none 62% / icons 38%。add-to-calendar は観察に無い Claude 案
	);
}

function wt_is_event_page() {
	// 固定ページの template meta が空でも、block テーマの階層（page-{slug}.html）で page-event が解決されることがある
	// （ローカル検証台で確認）。その場合 is_page_template() は false になるため、実際に解決した template id も見る。
	global $_wp_current_template_id;
	$resolved = is_string( $_wp_current_template_id ) && str_ends_with( $_wp_current_template_id, '//page-event' );
	return $resolved || ( function_exists( 'is_page_template' ) && is_page_template( array( 'page-event', 'page-event.html' ) ) );
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
	wp_enqueue_style( 'helix-wt-icons', get_theme_file_uri( 'assets/css/icons.css' ), array(), '0.3.16' );
	wp_enqueue_style( 'helix-wt', get_theme_file_uri( 'assets/css/theme.css' ), array( 'helix-wt-icons' ), '0.3.16' );
	$defer = array( 'strategy' => 'defer' );
	wp_enqueue_script( 'helix-wt-reveal', get_theme_file_uri( 'assets/js/reveal.js' ), array(), '0.3.2', $defer );
	wp_enqueue_script( 'helix-wt-header', get_theme_file_uri( 'assets/js/header.js' ), array(), '0.3.2', $defer );
	wp_enqueue_script( 'helix-wt-contrast', get_theme_file_uri( 'assets/js/contrast.js' ), array(), '0.3.2', $defer );
	if ( is_singular() || is_page() ) {
		wp_enqueue_script( 'helix-wt-article', get_theme_file_uri( 'assets/js/article.js' ), array(), '0.3.10', $defer );
	}
	if ( is_front_page() || is_page() ) { // 段 8: 固定ページ用パーツ（カルーセル・カウントダウン）でも使う
		wp_enqueue_script( 'helix-wt-home', get_theme_file_uri( 'assets/js/home.js' ), array(), '0.3.16', $defer );
	}
	if ( is_singular() || is_page() || is_front_page() ) { // 段 10: サイドバー（ドロワー / メガメニュー）
		wp_enqueue_script( 'helix-wt-side', get_theme_file_uri( 'assets/js/side.js' ), array(), '0.3.16', $defer );
	}
	if ( is_404() ) {
		wp_enqueue_script( 'helix-wt-404', get_theme_file_uri( 'assets/js/notfound.js' ), array(), '0.3.2', $defer );
	}
	wp_enqueue_script( 'helix-wt-footer', get_theme_file_uri( 'assets/js/footer.js' ), array(), '0.3.2', $defer );
	wp_enqueue_script( 'helix-wt-qa-modal', get_theme_file_uri( 'assets/js/qa-modal.js' ), array(), '0.3.8', $defer );
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
	if ( is_front_page() ) {
		$classes[] = 'wt-face-home';
	}
	if ( wt_is_event_page() ) {
		$classes[] = 'wt-face-event';
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
	register_block_pattern_category( 'helix-wt-page', array( 'label' => 'HELIX WT 固定ページ用パーツ' ) ); // 段8（WT-EVT-0287「固定ページ継投で使えるパーツ」）: どの固定ページにも挿せる区間パーツ
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
		// 2026-09-05 PO 反応 14 回目（WT-EVT-0256）: Q&A のモーダルウィンドウ型（回答を <dialog> で開く。JS 無効時は本文内に残る）
		array( 'core/group', 'wt-qa-modal', '囲み: Q&A（モーダル）' ),
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
		array( 'core/table', 'wt-compare-rich', '比較表: 画像・アイコン・購入リンク' ), // PO 反応 16 回目 WT-EVT-0264 / 0265
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
	register_block_type( 'helix-wt/category-ranking', array( 'render_callback' => 'wt_render_category_ranking', 'attributes' => array( 'slot' => array( 'type' => 'string', 'default' => 'aside' ) ) ) );
	register_block_type( 'helix-wt/category-count', array( 'render_callback' => 'wt_render_category_count' ) );
	register_block_type( 'helix-wt/category-lead', array( 'render_callback' => 'wt_render_category_lead' ) );
	register_block_type( 'helix-wt/category-filter', array( 'render_callback' => 'wt_render_category_filter' ) );
	register_block_type( 'helix-wt/category-sidebar', array( 'render_callback' => 'wt_render_category_sidebar' ) );
	register_block_type( 'helix-wt/category-pickup', array( 'render_callback' => 'wt_render_category_pickup' ) );
	register_block_type( 'helix-wt/category-cta', array( 'render_callback' => 'wt_render_category_cta' ) );
	register_block_type( 'helix-wt/card-badges', array( 'render_callback' => 'wt_render_card_badges', 'uses_context' => array( 'postId' ) ) );
	register_block_type( 'helix-wt/tail-prevnext', array( 'render_callback' => 'wt_render_tail_prevnext' ) );
	register_block_type( 'helix-wt/tail-author', array( 'render_callback' => 'wt_render_tail_author' ) );
} );

// ---------- 目次: h2/h3 機械導出、h2 ≥ 3 で挿入、記事上書き wt_toc=none で非表示 ----------
// 段 10: 見出しへの id 付与と木構造の生成を helper に分け、サイドバーの目次ウィジェット（本文と同じ id を指す）でも使う
function wt_toc_assign_ids( $content ) {
	$n = 0;
	return preg_replace_callback( '/<h([23])([^>]*)>(.*?)<\/h\1>/su', function ( $m ) use ( &$n ) {
		if ( preg_match( '/\sid=/', $m[2] ) ) {
			return $m[0];
		}
		$n++;
		return '<h' . $m[1] . $m[2] . ' id="h-' . $n . '">' . $m[3] . '</h' . $m[1] . '>';
	}, $content );
}
function wt_toc_items( $content ) { // id 付与済みの本文から、h2 数と項目 HTML を返す（h2 が無ければ 0 と空文字）
	if ( ! preg_match_all( '/<h([23])[^>]*\sid="([^"]+)"[^>]*>(.*?)<\/h\1>/su', $content, $ms, PREG_SET_ORDER ) ) {
		return array( 0, '' );
	}
	$h2 = 0; $tree = array();
	foreach ( $ms as $m ) {
		$node = array( 'id' => $m[2], 'label' => wp_strip_all_tags( $m[3] ), 'children' => array() );
		if ( '2' === $m[1] ) {
			$h2++; $tree[] = $node;
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
	return array( $h2, $items );
}
add_filter( 'the_content', function ( $content ) {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$content = wt_toc_assign_ids( $content );
	$variant = wt_opt( 'toc' );
	if ( 'none' === $variant ) {
		return wt_insert_pr( $content );
	}
	list( $h2, $items ) = wt_toc_items( $content );
	if ( $h2 < 3 ) { // しきい値（P19 / R44）
		return wt_insert_pr( $content );
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
	return '<p class="wt-pr is-style-wt-pr"><span class="wt-pr__tag">PR</span>本記事にはプロモーションが含まれます。</p>' . $content;
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
	$verb     = '/含まれ(ます|る|て)?|含み(ます)?|含む|掲載|表記/u'; // 「含まれます」（PO 決定の既定文言）も述語に含める（Astra 是正）
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
	if ( 'none' === $variant || 'sidebar-tree' === $variant ) { // sidebar-tree は wt_render_category_sidebar() 側（右カラム）で描く
		return '<nav class="wt-cat-children wt-cat-children--none" aria-label="子カテゴリ"></nav>';
	}
	$out = '<nav class="wt-cat-children wt-cat-children--' . esc_attr( $variant ) . '" aria-label="子カテゴリ"><ul>';
	$img = get_theme_file_uri( 'assets/img' );
	foreach ( $children as $i => $child ) {
		$url   = get_term_link( $child );
		$count = number_format_i18n( (int) $child->count );
		if ( is_wp_error( $url ) ) {
			continue;
		}
		$label = '<span class="wt-cat-child__name">' . esc_html( $child->name ) . '</span><span class="wt-cat-child__count">' . esc_html( $count ) . '件</span>';
		if ( 'image-banners' === $variant ) { // PoC: 子カテゴリの画像は生成写真を順に割り当てる（実運用はタームのメタ画像）
			$label = '<img src="' . esc_url( $img . '/media-pickup-' . ( ( $i % 6 ) + 1 ) . '.jpg' ) . '" alt="" width="600" height="338" loading="lazy" decoding="async">' . $label;
		}
		$out  .= '<li><a href="' . esc_url( $url ) . '">' . $label . '</a>';
		if ( 'cards' === $variant ) {
			$out .= '<span class="wt-cat-child__desc">' . esc_html( wp_trim_words( $child->description, 12, '…' ) ) . '</span>';
		}
		$out .= '</li>';
	}
	return $out . '</ul></nav>';
}

function wt_render_category_ranking( $attributes = array() ) {
	$term = wt_current_category_term();
	if ( ! $term ) {
		return '';
	}
	// 置き場所（top / bottom / aside）ごとに template に 1 つずつ置き、選択された cat_ranking と一致する slot だけ描く
	$slot   = isset( $attributes['slot'] ) ? (string) $attributes['slot'] : 'aside';
	$choice = wt_opt( 'cat_ranking' );
	$want   = 'sidebar' === $choice ? 'aside' : $choice;
	if ( $want !== $slot ) {
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
	$out = '<aside class="wt-cat-ranking wt-cat-ranking--' . esc_attr( $slot ) . '" aria-labelledby="wt-cat-ranking-title"><h2 id="wt-cat-ranking-title">このカテゴリのランキング</h2><ol>';
	foreach ( $posts as $index => $post ) {
		$out .= '<li><a href="' . esc_url( get_permalink( $post ) ) . '"><span class="wt-cat-ranking__num">' . esc_html( (string) ( $index + 1 ) ) . '</span><span>' . esc_html( get_the_title( $post ) ) . '</span></a></li>';
	}
	return $out . '</ol></aside>';
}

// 段 6: カテゴリ面の年フィルタ（?year=YYYY）。redirect_canonical は年付きのカテゴリ一覧を年アーカイブへ飛ばしカテゴリを落とすため、カテゴリ面でだけ止める
add_filter( 'redirect_canonical', function ( $redirect ) {
	if ( is_category() && get_query_var( 'year' ) ) {
		return false;
	}
	return $redirect;
} );
// ---------- 段 7: 地図の外部埋め込み（event_map:embed、WT-EVT-0284） ----------
// 埋め込み先 URL は option（wp option / 設定 JSON 側）に持ち、テーマ・公開リポには第三者サービスのドメインも鍵も書かない。
// URL が未設定なら埋め込まず、その旨を枠に表示する（外部へ勝手に接続しない）。iframe は loading="lazy"・title 付き・referrerpolicy 指定。
function wt_event_map_embed_url() {
	$url = get_option( 'helix_wt_event_map_embed_url', '' );
	$url = is_string( $url ) ? trim( $url ) : '';
	return ( '' !== $url && in_array( wp_parse_url( $url, PHP_URL_SCHEME ), array( 'http', 'https' ), true ) ) ? $url : '';
}
function wt_render_event_map_embed() {
	$url = wt_event_map_embed_url();
	$out = '<div class="wt-event-map wt-event-map--embed" data-wt-embed="' . ( $url ? 'set' : 'unset' ) . '">';
	if ( $url ) {
		$out .= '<iframe class="wt-event-map__frame" src="' . esc_url( $url ) . '" title="会場周辺の地図（外部の地図サービス）" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen width="1200" height="480"></iframe>';
	} else {
		$out .= '<p class="wt-event-map__unset">地図の埋め込み URL が未設定です（option <code>helix_wt_event_map_embed_url</code>）。設定するまで外部の地図サービスへは接続しません。</p>';
	}
	return $out . '<address><b>サンプルホール 3F</b><br>設定された所在地<br>最寄り駅から徒歩 5 分（PoC 用の文言）</address></div>';
}
register_block_type( 'helix-wt/event-map-embed', array( 'render_callback' => 'wt_render_event_map_embed' ) );

// ---------- 段 8: SNS フィードの外部埋め込み（固定ページ用パーツ helix-wt-page/sns-feed。地図埋め込みと同じ方針: URL は option、未設定なら外部へ接続しない、鍵はテーマに置かない） ----------
function wt_sns_feed_embed_url() {
	$url = get_option( 'helix_wt_sns_feed_embed_url', '' );
	$url = is_string( $url ) ? trim( $url ) : '';
	return ( '' !== $url && in_array( wp_parse_url( $url, PHP_URL_SCHEME ), array( 'http', 'https' ), true ) ) ? $url : '';
}
function wt_render_sns_feed_embed() {
	$url = wt_sns_feed_embed_url();
	$out = '<div class="wt-part-sns__feed" data-wt-embed="' . ( $url ? 'set' : 'unset' ) . '">';
	if ( $url ) {
		$out .= '<iframe class="wt-part-sns__frame" src="' . esc_url( $url ) . '" title="SNS の最新投稿（外部サービスの埋め込み）" loading="lazy" referrerpolicy="no-referrer-when-downgrade" width="1200" height="480"></iframe>';
	} else {
		$out .= '<p class="wt-part-sns__unset">SNS フィードの埋め込み URL が未設定です（option <code>helix_wt_sns_feed_embed_url</code>）。設定するまで外部の SNS サービスへは接続しません。</p>';
	}
	return $out . '</div>';
}
register_block_type( 'helix-wt/sns-feed-embed', array( 'render_callback' => 'wt_render_sns_feed_embed' ) ); // パターンを保存した後も描画時に option を読む

// ---------- 段 10: 共通サイドバー（記事 / 固定ページ / HP。WT-EVT-0289、台帳 research-r21 sidebar n=48） ----------
// ウィジェット 18 種は観察の全種（WT-EVT-0288「最大数」）。セットは区分別の上位順。カテゴリ面の cat_sidebar 3 型は段 6 のまま残す（統合は次段）
function wt_side_sets() {
	return array(
		'media'     => array( 'search', 'categories', 'popular-ranking', 'toc-sticky', 'cta-banner', 'ad', 'related-posts' ), // C 比較メディア n=18 の上位
		'blog'      => array( 'search', 'profile', 'categories', 'popular-ranking', 'new-posts', 'archive', 'tags', 'sns-follow' ), // P 個人 / ポータル n=23
		'owned'     => array( 'search', 'categories', 'popular-ranking', 'new-posts', 'tags', 'newsletter', 'recruit', 'cta-banner' ), // B オウンドメディア n=7
		'corporate' => array( 'contact-box', 'tel-box', 'new-posts', 'event-list', 'banner-stack' ), // 固定ページ / HP 向け（観察の corporate 系は少数。Claude 案）
		'minimal'   => array( 'popular-ranking', 'related-posts', 'banner-stack' ), // ポータル記事の最小構成
		'full'      => array( 'search', 'profile', 'categories', 'popular-ranking', 'new-posts', 'tags', 'cta-banner', 'toc-sticky', 'newsletter', 'sns-follow', 'archive', 'calendar', 'ad', 'related-posts', 'event-list', 'contact-box', 'tel-box', 'banner-stack', 'recruit' ), // 全種
	);
}
function wt_side_widget( $key, $sfx = '' ) { // $sfx: 左カラム用の id 接尾辞（右と同じウィジェットを出すときの id 重複を避ける）
	$posts = get_posts( array( 'posts_per_page' => 5, 'post_status' => 'publish', 'orderby' => 'date', 'order' => 'DESC' ) );
	$u     = get_theme_file_uri( 'assets/img' );
	$h     = function ( $title, $small = '' ) use ( $key, $sfx ) { return '<h2 id="wt-side-h-' . esc_attr( $key . $sfx ) . '">' . esc_html( $title ) . ( $small ? '<small>' . esc_html( $small ) . '</small>' : '' ) . '</h2>'; };
	$o     = '<section class="wt-side-widget wt-side-widget--' . esc_attr( $key ) . '" aria-labelledby="wt-side-h-' . esc_attr( $key . $sfx ) . '" data-wt-widget="' . esc_attr( $key ) . '">';
	switch ( $key ) {
		case 'search':
			$o .= $h( '検索' ) . '<form role="search" method="get" action="' . esc_url( home_url( '/' ) ) . '"><label class="screen-reader-text" for="wt-side-s' . esc_attr( $sfx ) . '">キーワード</label><input id="wt-side-s' . esc_attr( $sfx ) . '" type="search" name="s" placeholder="キーワードで検索"><button type="submit" aria-label="検索"><i class="wt-i wt-i--s wt-i--search" aria-hidden="true"></i></button></form>';
			break;
		case 'profile':
			$o .= $h( 'この媒体について' ) . '<div class="wt-side-profile"><img src="' . esc_url( $u ) . '/avatar.png" alt="" width="64" height="64" loading="lazy"><p><b>編集部</b><br>比較と選び方を、実測と一次情報で。PoC 用の架空プロフィール。</p></div>';
			break;
		case 'categories':
			$cats = get_terms( array( 'taxonomy' => 'category', 'parent' => 0, 'hide_empty' => false, 'orderby' => 'term_id', 'order' => 'ASC', 'number' => 8 ) );
			$o   .= $h( 'カテゴリ' ) . '<ul>';
			foreach ( is_wp_error( $cats ) ? array() : $cats as $c ) { $l = get_term_link( $c ); if ( ! is_wp_error( $l ) ) { $o .= '<li><a href="' . esc_url( $l ) . '">' . esc_html( $c->name ) . '<span class="wt-side-count">' . esc_html( number_format_i18n( (int) $c->count ) ) . '</span></a></li>'; } }
			$o .= '</ul>';
			break;
		case 'popular-ranking':
			$o .= $h( '人気記事', '（PoC: 日付順）' ) . '<ol class="wt-side-rank">';
			foreach ( $posts as $i => $p ) { $o .= '<li><a href="' . esc_url( get_permalink( $p ) ) . '"><b>' . esc_html( (string) ( $i + 1 ) ) . '</b><span>' . esc_html( get_the_title( $p ) ) . '</span></a></li>'; }
			$o .= '</ol>';
			break;
		case 'new-posts':
			$o .= $h( '新着記事' ) . '<ul>';
			foreach ( array_slice( $posts, 0, 3 ) as $p ) { $o .= '<li><a href="' . esc_url( get_permalink( $p ) ) . '"><time datetime="' . esc_attr( get_the_date( 'c', $p ) ) . '">' . esc_html( get_the_date( 'Y.m.d', $p ) ) . '</time><span>' . esc_html( get_the_title( $p ) ) . '</span></a></li>'; }
			$o .= '</ul>';
			break;
		case 'tags':
			$tags = get_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => true, 'number' => 12 ) );
			$o   .= $h( 'タグ' ) . '<ul class="wt-side-tags">';
			foreach ( is_wp_error( $tags ) ? array() : $tags as $t ) { $l = get_term_link( $t ); if ( ! is_wp_error( $l ) ) { $o .= '<li><a href="' . esc_url( $l ) . '">#' . esc_html( $t->name ) . '</a></li>'; } }
			$o .= '</ul>';
			break;
		case 'cta-banner':
			$lp = get_page_by_path( 'lp' );
			$o .= $h( '案内' ) . '<a class="wt-side-banner" href="' . esc_url( $lp ? get_permalink( $lp ) : home_url( '/' ) ) . '"><span class="wt-eyebrow">GUIDE</span><b>はじめての方へ</b><span>選び方の全体像を 1 ページで</span></a>';
			break;
		case 'toc-sticky':
			$items = ''; $h2 = 0;
			if ( is_singular( 'post' ) ) { list( $h2, $items ) = wt_toc_items( wt_toc_assign_ids( do_blocks( get_post_field( 'post_content', get_queried_object_id() ) ) ) ); } // 本文は pattern 参照のことがあるので block を展開してから（the_content の filter は do_blocks（9）→ 目次（12）の順で、id の付与順は同じ）
			if ( $h2 < 1 ) { return ''; } // 見出しが無い面では出さない（届かない導線を作らない）
			$o .= $h( '目次' ) . '<nav class="wt-side-toc" aria-label="目次（サイド）"><ol>' . $items . '</ol></nav>';
			break;
		case 'newsletter':
			$o .= $h( 'ニュースレター' ) . '<div class="wt-side-newsletter" role="group" aria-labelledby="wt-side-h-newsletter"><p>週 1 回、新着と比較の更新をお届け。</p><label class="screen-reader-text" for="wt-side-nl">メールアドレス</label><input id="wt-side-nl" type="email" name="email" placeholder="email@example.invalid" autocomplete="email"><button type="button" class="wt-lp-cta-action" aria-describedby="wt-side-nl-note">登録する</button><p id="wt-side-nl-note" class="wt-lp-form__note">PoC のため送信しない（form 要素を使わない）。</p></div>';
			break;
		case 'sns-follow':
			$o .= $h( 'フォローする' ) . '<ul class="wt-side-sns" aria-label="公式アカウント（PoC のダミー導線）"><li><a class="wt-sns" href="#wt-side-h-sns-follow" rel="nofollow" aria-label="公式アカウント 1"><i class="wt-i wt-i--sns-x" aria-hidden="true"></i></a></li><li><a class="wt-sns" href="#wt-side-h-sns-follow" rel="nofollow" aria-label="公式アカウント 2"><i class="wt-i wt-i--sns-ig" aria-hidden="true"></i></a></li><li><a class="wt-sns" href="#wt-side-h-sns-follow" rel="nofollow" aria-label="公式アカウント 3"><i class="wt-i wt-i--sns-yt" aria-hidden="true"></i></a></li></ul>';
			break;
		case 'archive':
			$months = array();
			foreach ( get_posts( array( 'posts_per_page' => -1, 'post_status' => 'publish' ) ) as $p ) { $months[ get_the_date( 'Y-m', $p ) ] = array( (int) get_the_date( 'Y', $p ), (int) get_the_date( 'n', $p ) ); }
			krsort( $months );
			$o .= $h( '月別' ) . '<ul>';
			foreach ( array_slice( $months, 0, 6, true ) as $pair ) { $o .= '<li><a href="' . esc_url( get_month_link( $pair[0], $pair[1] ) ) . '">' . esc_html( $pair[0] . '年' . $pair[1] . '月' ) . '</a></li>'; }
			$o .= '</ul>';
			break;
		case 'calendar':
			$o .= $h( 'カレンダー' ) . '<div class="wt-side-calendar">' . get_calendar( true, false ) . '</div>';
			break;
		case 'ad':
			$o .= $h( '広告枠', '（PoC のダミー）' ) . '<div class="wt-side-ad" role="img" aria-label="広告枠のダミー（300×250 相当。実運用では広告タグを置く）"><span>AD 300×250</span></div>';
			break;
		case 'related-posts':
			$o .= $h( '関連記事' ) . '<ul class="wt-side-related">';
			foreach ( array_slice( $posts, 0, 3 ) as $p ) { $o .= '<li><a href="' . esc_url( get_permalink( $p ) ) . '">' . get_the_post_thumbnail( $p, 'thumbnail', array( 'loading' => 'lazy', 'alt' => '' ) ) . '<span>' . esc_html( get_the_title( $p ) ) . '</span></a></li>'; }
			$o .= '</ul>';
			break;
		case 'event-list':
			$o .= $h( 'イベント' ) . '<ul class="wt-side-events"><li><a href="/event/"><span class="wt-side-events__date"><b>10/15</b><small>木</small></span><span>業務改善セミナー 2026 秋</span></a></li><li><a href="/event/"><span class="wt-side-events__date"><b>11/02</b><small>日</small></span><span>オープンキャンパス（体験授業）</span></a></li></ul>';
			break;
		case 'contact-box':
			$o .= $h( 'お問い合わせ' ) . '<div class="wt-side-contact"><p>導入のご相談・資料請求はこちらから。</p><a class="wt-lp-cta-action" href="/lp/">相談する</a></div>';
			break;
		case 'tel-box':
			$o .= $h( 'お電話' ) . '<a class="wt-side-tel" href="tel:0000000000"><i class="wt-i wt-i--phone" aria-hidden="true"></i><span><b>000-000-0000</b><small>平日 9:00-18:00（PoC 用のダミー番号）</small></span></a>';
			break;
		case 'banner-stack':
			$o .= $h( 'バナー' ) . '<ul class="wt-side-banners"><li><a href="/lp/"><span class="wt-eyebrow">GUIDE</span><b>3 分の無料診断</b></a></li><li><a href="/event/"><span class="wt-eyebrow">EVENT</span><b>秋の無料相談会</b></a></li><li><a href="/parts/"><span class="wt-eyebrow">PARTS</span><b>固定ページ用パーツ</b></a></li></ul>';
			break;
		case 'recruit':
			$o .= $h( '採用情報' ) . '<a class="wt-side-banner wt-side-banner--recruit" href="/lp/"><img src="' . esc_url( $u ) . '/media-pickup-6.jpg" alt="" width="640" height="400" loading="lazy" decoding="async"><b>一緒に働く仲間を募集</b></a>';
			break;
		default:
			return '';
	}
	return $o . '</section>';
}
function wt_render_sidebar( $attrs = array() ) {
	$sets = wt_side_sets();
	$set  = wt_opt( 'side_set' );
	$keys = isset( $sets[ $set ] ) ? $sets[ $set ] : $sets['media'];
	$out  = '';
	foreach ( $keys as $k ) { $out .= wt_side_widget( $k ); }
	// left 側 / both の左は「ナビ寄り」（カテゴリ + 検索）に固定し、右に本セットを置く
	$left = wt_side_widget( 'categories', '-l' ) . wt_side_widget( 'search', '-l' );
	return '<aside class="wt-side wt-side--right" data-wt-side="right" aria-label="サイドバー">' . $out . '</aside>'
		. '<aside class="wt-side wt-side--left" data-wt-side="left" aria-label="サイドナビ（左）">' . $left . '</aside>'
		. '<button class="wt-side-drawer__open" type="button" hidden aria-controls="wt-side-drawer" aria-expanded="false"><i class="wt-i wt-i--menu" aria-hidden="true"></i>メニュー</button>'
		. '<div class="wt-side-drawer" id="wt-side-drawer" hidden><div class="wt-side-drawer__panel" role="dialog" aria-modal="true" aria-label="サイドバー（ドロワー）"><button class="wt-side-drawer__close" type="button" aria-label="閉じる"><i class="wt-i wt-i--close" aria-hidden="true"></i></button><div class="wt-side-drawer__body"></div></div></div>';
}
register_block_type( 'helix-wt/sidebar', array( 'render_callback' => 'wt_render_sidebar' ) );
// サイドナビ 5 型（fixed-left-nav / fixed-right-icons / mega-menu / drawer-pc / toc-side）。mega-menu はヘッダーのナビにパネルを足す（JS で aria）
function wt_render_side_nav() {
	$cats = get_terms( array( 'taxonomy' => 'category', 'parent' => 0, 'hide_empty' => false, 'orderby' => 'term_id', 'order' => 'ASC', 'number' => 8 ) );
	$li   = '';
	foreach ( is_wp_error( $cats ) ? array() : $cats as $c ) { $l = get_term_link( $c ); if ( ! is_wp_error( $l ) ) { $li .= '<li><a href="' . esc_url( $l ) . '">' . esc_html( $c->name ) . '</a></li>'; } }
	$out  = '<nav class="wt-sidenav wt-sidenav--fixed-left" aria-label="サイドナビ（固定・左）"><p class="wt-eyebrow">MENU</p><ul>' . $li . '</ul></nav>';
	$out .= '<nav class="wt-sidenav wt-sidenav--fixed-right" aria-label="サイドナビ（固定・右アイコン）"><a href="/lp/" aria-label="資料請求"><i class="wt-i wt-i--download" aria-hidden="true"></i><span>資料</span></a><a href="tel:0000000000" aria-label="電話（PoC 用のダミー番号）"><i class="wt-i wt-i--phone" aria-hidden="true"></i><span>電話</span></a><a href="/lp/" aria-label="お問い合わせ（相談ページへ）"><i class="wt-i wt-i--mail" aria-hidden="true"></i><span>相談</span></a><button type="button" data-wt-totop aria-label="ページ上部へ"><i class="wt-i wt-i--chevron-down" aria-hidden="true"></i><span>TOP</span></button></nav>';
	$out .= '<div class="wt-megamenu" id="wt-megamenu" hidden><div class="wt-megamenu__inner"><div><p class="wt-eyebrow">CATEGORY</p><ul>' . $li . '</ul></div><div><p class="wt-eyebrow">PICKUP</p><ul><li><a href="/lp/">はじめての方へ</a></li><li><a href="/event/">イベント</a></li><li><a href="/parts/">固定ページ用パーツ</a></li></ul></div><div><p class="wt-eyebrow">ABOUT</p><ul><li><a href="/">この媒体について</a></li><li><a href="/category/topic-index/">カテゴリ一覧</a></li></ul></div></div></div>';
	return $out;
}
register_block_type( 'helix-wt/side-nav', array( 'render_callback' => 'wt_render_side_nav' ) );

// ---------- 段 6: カテゴリ面の強化（WT-EVT-0283、台帳 category-recapture n=54） ----------
function wt_category_posts( $term, $n, $offset = 0 ) {
	return get_posts( array( 'category' => (int) $term->term_id, 'posts_per_page' => $n, 'offset' => $offset, 'post_status' => 'publish', 'orderby' => 'date', 'order' => 'DESC' ) );
}

function wt_render_category_count() {
	$term = wt_current_category_term();
	if ( ! $term ) {
		return '';
	}
	$total = isset( $GLOBALS['wp_query'] ) ? (int) $GLOBALS['wp_query']->found_posts : 0; // 子カテゴリを含む一覧の件数（見出しの件数は一覧と同じ分母）
	return '<p class="wt-cat-head__count"><b>' . esc_html( number_format_i18n( $total ) ) . '</b> 件</p>';
}

function wt_render_category_lead() {
	$term = wt_current_category_term();
	if ( ! $term ) {
		return '';
	}
	$desc = $term->description ? $term->description : 'このカテゴリの記事は、読む順番に迷わないよう基礎から順に並べています。';
	$out  = '<p class="wt-cat-lead wt-cat-lead--lead-text">' . esc_html( $desc ) . '</p>';
	$out .= '<div class="wt-cat-lead wt-cat-lead--editorial"><p class="wt-eyebrow">EDITOR\'S NOTE</p><h2>' . esc_html( $term->name ) . ' の歩き方</h2>';
	$out .= '<p>' . esc_html( $desc ) . '</p><p>まず「基礎」の記事で全体像をつかみ、次に「比較」で選び方の軸を決め、最後に「運用」で日々の使い方を確認する流れを勧めています。迷ったら一覧の先頭から順に読んでください。</p>';
	$out .= '<a class="wt-cat-lead__link" href="#list">記事一覧へ</a></div>';
	return $out;
}

function wt_render_category_filter() {
	$term = wt_current_category_term();
	if ( ! $term ) {
		return '';
	}
	$base = get_term_link( $term );
	if ( is_wp_error( $base ) ) {
		return '';
	}
	$variant = wt_opt( 'cat_filter' );
	if ( 'none' === $variant ) {
		return '';
	}
	$out = '<div class="wt-cat-filter wt-cat-filter--' . esc_attr( $variant ) . '">';
	if ( 'tabs' === $variant ) {
		$children = get_terms( array( 'taxonomy' => 'category', 'parent' => (int) $term->term_id, 'hide_empty' => false, 'orderby' => 'term_id', 'order' => 'ASC' ) );
		$out     .= '<nav aria-label="絞り込み"><ul><li><a href="' . esc_url( $base ) . '" aria-current="page">すべて</a></li>';
		foreach ( is_wp_error( $children ) ? array() : $children as $child ) {
			$link = get_term_link( $child );
			if ( ! is_wp_error( $link ) ) {
				$out .= '<li><a href="' . esc_url( $link ) . '">' . esc_html( $child->name ) . '</a></li>';
			}
		}
		$out .= '</ul></nav>';
	} elseif ( 'year' === $variant ) {
		$years = array();
		foreach ( wt_category_posts( $term, -1 ) as $p ) {
			$years[ get_the_date( 'Y', $p ) ] = true;
		}
		krsort( $years );
		$current = get_query_var( 'year' );
		$out    .= '<nav aria-label="年で絞り込み"><ul><li><a href="' . esc_url( $base ) . '"' . ( $current ? '' : ' aria-current="page"' ) . '>すべて</a></li>';
		foreach ( array_keys( $years ) as $y ) {
			$out .= '<li><a href="' . esc_url( add_query_arg( 'year', $y, $base ) ) . '"' . ( (string) $current === (string) $y ? ' aria-current="page"' : '' ) . '>' . esc_html( $y ) . '年</a></li>';
		}
		$out .= '</ul></nav>';
	} elseif ( 'tag' === $variant ) {
		$tags    = get_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => true, 'number' => 12 ) );
		$current = get_query_var( 'tag' );
		$out    .= '<nav aria-label="タグで絞り込み"><ul><li><a href="' . esc_url( $base ) . '"' . ( $current ? '' : ' aria-current="page"' ) . '>すべて</a></li>';
		foreach ( is_wp_error( $tags ) ? array() : $tags as $t ) {
			$out .= '<li><a href="' . esc_url( add_query_arg( 'tag', $t->slug, $base ) ) . '"' . ( $current === $t->slug ? ' aria-current="page"' : '' ) . '>#' . esc_html( $t->name ) . '</a></li>';
		}
		$out .= '</ul></nav>';
	} else { // sort: 同一サイト内の一覧の並べ替え（WP の公開 query var orderby / order）。問い合わせフォームではないので送信する
		$orderby = (string) get_query_var( 'orderby' );
		$order   = strtolower( (string) get_query_var( 'order' ) );
		$opts    = array( 'date' => '新しい順', 'modified' => '更新順', 'title' => 'タイトル順' );
		$out    .= '<form class="wt-cat-sort" method="get" action="' . esc_url( $base ) . '"><label for="wt-cat-orderby">並べ替え</label><select id="wt-cat-orderby" name="orderby">';
		foreach ( $opts as $k => $label ) {
			$out .= '<option value="' . esc_attr( $k ) . '"' . selected( $orderby ?: 'date', $k, false ) . '>' . esc_html( $label ) . '</option>';
		}
		$out .= '</select><input type="hidden" name="order" value="' . esc_attr( 'asc' === $order ? 'asc' : 'desc' ) . '"><button type="submit">適用</button></form>';
	}
	return $out . '</div>';
}

function wt_render_category_sidebar() {
	$term = wt_current_category_term();
	if ( ! $term ) {
		return '';
	}
	$out = '<div class="wt-cat-side">';
	if ( 'sidebar-tree' === wt_opt( 'cat_children' ) ) { // 子カテゴリの木（右カラム型）
		$children = get_terms( array( 'taxonomy' => 'category', 'parent' => (int) $term->term_id, 'hide_empty' => false, 'orderby' => 'term_id', 'order' => 'ASC' ) );
		$out     .= '<nav class="wt-cat-widget wt-cat-widget--tree wt-cat-children--sidebar-tree" aria-labelledby="wt-cat-tree-title"><h2 id="wt-cat-tree-title">' . esc_html( $term->name ) . '</h2><ul>';
		foreach ( is_wp_error( $children ) ? array() : $children as $child ) {
			$link = get_term_link( $child );
			if ( ! is_wp_error( $link ) ) {
				$out .= '<li><a href="' . esc_url( $link ) . '"><span class="wt-cat-child__name">' . esc_html( $child->name ) . '</span><span class="wt-cat-child__count">' . esc_html( number_format_i18n( (int) $child->count ) ) . '件</span></a></li>';
			}
		}
		$out .= '</ul></nav>';
	}
	// categories（67%）
	$cats = get_terms( array( 'taxonomy' => 'category', 'parent' => 0, 'hide_empty' => false, 'orderby' => 'term_id', 'order' => 'ASC', 'number' => 8 ) );
	$out .= '<section class="wt-cat-widget wt-cat-widget--categories" aria-labelledby="wt-cat-w-categories"><h2 id="wt-cat-w-categories">カテゴリ</h2><ul>';
	foreach ( is_wp_error( $cats ) ? array() : $cats as $c ) {
		$link = get_term_link( $c );
		if ( ! is_wp_error( $link ) ) {
			$out .= '<li><a href="' . esc_url( $link ) . '"' . ( (int) $c->term_id === (int) $term->term_id ? ' aria-current="page"' : '' ) . '>' . esc_html( $c->name ) . '<span class="wt-cat-child__count">' . esc_html( number_format_i18n( (int) $c->count ) ) . '</span></a></li>';
		}
	}
	$out .= '</ul></section>';
	// popular-ranking（57%）。PoC は閲覧数を持たないため日付順の上位 5 件を「人気」の代わりに並べる（見出しに明記）
	$out .= '<section class="wt-cat-widget wt-cat-widget--popular" aria-labelledby="wt-cat-w-popular"><h2 id="wt-cat-w-popular">人気記事<small>（PoC: 日付順）</small></h2><ol>';
	foreach ( wt_category_posts( $term, 5 ) as $i => $p ) {
		$out .= '<li><a href="' . esc_url( get_permalink( $p ) ) . '"><b>' . esc_html( (string) ( $i + 1 ) ) . '</b><span>' . esc_html( get_the_title( $p ) ) . '</span></a></li>';
	}
	$out .= '</ol></section>';
	// cta-banner（33%）
	$lp   = get_page_by_path( 'lp' );
	$out .= '<section class="wt-cat-widget wt-cat-widget--cta" aria-label="案内"><a class="wt-cat-widget__banner" href="' . esc_url( $lp ? get_permalink( $lp ) : home_url( '/' ) ) . '"><span class="wt-eyebrow">GUIDE</span><b>はじめての方へ</b><span>選び方の全体像を 1 ページで</span></a></section>';
	// full: search / archive-month / new-posts / profile / tags
	$out .= '<section class="wt-cat-widget wt-cat-widget--search" aria-labelledby="wt-cat-w-search"><h2 id="wt-cat-w-search">検索</h2><form role="search" method="get" action="' . esc_url( home_url( '/' ) ) . '"><label class="screen-reader-text" for="wt-cat-s">キーワード</label><input id="wt-cat-s" type="search" name="s" placeholder="キーワード"><button type="submit" aria-label="検索"><i class="wt-i wt-i--search" aria-hidden="true"></i></button></form></section>';
	$months = array();
	foreach ( wt_category_posts( $term, -1 ) as $p ) {
		$months[ get_the_date( 'Y-m', $p ) ] = array( (int) get_the_date( 'Y', $p ), (int) get_the_date( 'n', $p ) );
	}
	krsort( $months );
	$out .= '<section class="wt-cat-widget wt-cat-widget--archive" aria-labelledby="wt-cat-w-archive"><h2 id="wt-cat-w-archive">月別</h2><ul>';
	foreach ( array_slice( $months, 0, 6, true ) as $ym => $pair ) {
		$out .= '<li><a href="' . esc_url( get_month_link( $pair[0], $pair[1] ) ) . '">' . esc_html( $pair[0] . '年' . $pair[1] . '月' ) . '</a></li>';
	}
	$out .= '</ul></section>';
	$out .= '<section class="wt-cat-widget wt-cat-widget--new" aria-labelledby="wt-cat-w-new"><h2 id="wt-cat-w-new">新着記事</h2><ul>';
	foreach ( wt_category_posts( $term, 3 ) as $p ) {
		$out .= '<li><a href="' . esc_url( get_permalink( $p ) ) . '"><time datetime="' . esc_attr( get_the_date( 'c', $p ) ) . '">' . esc_html( get_the_date( 'Y.m.d', $p ) ) . '</time><span>' . esc_html( get_the_title( $p ) ) . '</span></a></li>';
	}
	$out .= '</ul></section>';
	$out .= '<section class="wt-cat-widget wt-cat-widget--profile" aria-labelledby="wt-cat-w-profile"><h2 id="wt-cat-w-profile">この媒体について</h2><div class="wt-cat-widget__profile"><img src="' . esc_url( get_theme_file_uri( 'assets/img/avatar.png' ) ) . '" alt="" width="64" height="64" loading="lazy"><p><b>編集部</b><br>選び方を数字で比べる比較媒体。</p></div></section>';
	$tags = get_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => true, 'number' => 12 ) );
	$out .= '<section class="wt-cat-widget wt-cat-widget--tags" aria-labelledby="wt-cat-w-tags"><h2 id="wt-cat-w-tags">タグ</h2><ul>';
	foreach ( is_wp_error( $tags ) ? array() : $tags as $t ) {
		$link = get_term_link( $t );
		if ( ! is_wp_error( $link ) ) {
			$out .= '<li><a href="' . esc_url( $link ) . '">#' . esc_html( $t->name ) . '</a></li>';
		}
	}
	return $out . '</ul></section></div>';
}

function wt_render_category_pickup() {
	$term = wt_current_category_term();
	if ( ! $term ) {
		return '';
	}
	$variant = wt_opt( 'cat_pickup' );
	if ( 'none' === $variant ) {
		return '';
	}
	if ( 'top-featured' === $variant ) {
		$posts = wt_category_posts( $term, 1 );
		if ( ! $posts ) {
			return '';
		}
		$p    = $posts[0];
		$img  = get_the_post_thumbnail( $p, 'large', array( 'loading' => 'eager', 'decoding' => 'async' ) );
		$cats = get_the_category( $p );
		$out  = '<section class="wt-cat-pickup wt-cat-pickup--top-featured" aria-labelledby="wt-cat-pickup-title"><a class="wt-cat-pickup__media" href="' . esc_url( get_permalink( $p ) ) . '" tabindex="-1" aria-hidden="true">' . $img . '</a><div class="wt-cat-pickup__body"><p class="wt-eyebrow">FEATURED</p>';
		if ( $cats ) {
			$out .= '<a class="wt-data-card__chip" href="' . esc_url( get_category_link( $cats[0] ) ) . '">' . esc_html( $cats[0]->name ) . '</a>';
		}
		$out .= '<h2 id="wt-cat-pickup-title"><a href="' . esc_url( get_permalink( $p ) ) . '">' . esc_html( get_the_title( $p ) ) . '</a></h2><time datetime="' . esc_attr( get_the_date( 'c', $p ) ) . '">' . esc_html( get_the_date( 'Y.m.d', $p ) ) . '</time><p>' . esc_html( wp_trim_words( get_the_excerpt( $p ), 40, '…' ) ) . '</p></div></section>';
		return $out;
	}
	$out = '<section class="wt-cat-pickup wt-cat-pickup--editor-pick-box" aria-labelledby="wt-cat-pickup-title"><h2 id="wt-cat-pickup-title">編集部のおすすめ</h2><ol>';
	foreach ( wt_category_posts( $term, 3, 1 ) as $p ) {
		$out .= '<li><a href="' . esc_url( get_permalink( $p ) ) . '">' . get_the_post_thumbnail( $p, 'medium', array( 'loading' => 'lazy', 'decoding' => 'async' ) ) . '<span>' . esc_html( get_the_title( $p ) ) . '</span></a></li>';
	}
	return $out . '</ol></section>';
}

function wt_render_category_cta() {
	$term = wt_current_category_term();
	if ( ! $term ) {
		return '';
	}
	$variant = wt_opt( 'cat_cta' );
	if ( 'none' === $variant ) {
		return '';
	}
	if ( 'lp-banner' === $variant ) {
		$lp = get_page_by_path( 'lp' );
		return '<aside class="wt-cat-cta wt-cat-cta--lp-banner" aria-label="案内"><a href="' . esc_url( $lp ? get_permalink( $lp ) : home_url( '/' ) ) . '"><span class="wt-eyebrow">GUIDE</span><b>' . esc_html( $term->name ) . ' の選び方ガイド</b><span>比較の軸と失敗しない順番を 1 ページにまとめました</span><span class="wt-lp-cta-action">ガイドを読む</span></a></aside>';
	}
	if ( 'newsletter' === $variant ) { // PoC: 送信しない（外部の配信サービスへ接続しない）。form 要素を使わない: 文字入力 1 つの form は送信ボタンが無くても Enter で暗黙送信される
		return '<aside class="wt-cat-cta wt-cat-cta--newsletter" id="cat-newsletter" aria-labelledby="wt-cat-nl-title"><h2 id="wt-cat-nl-title">新着記事をメールで受け取る</h2><div class="wt-cat-cta__fields" role="group" aria-labelledby="wt-cat-nl-title" data-wt-poc-form="no-submit"><label for="wt-cat-nl-email">メールアドレス</label><input id="wt-cat-nl-email" type="email" name="email" autocomplete="email" placeholder="you@example.com"><button type="button">登録する（PoC: 送信しない）</button></div></aside>';
	}
	return '<aside class="wt-cat-cta wt-cat-cta--line" id="cat-line" aria-label="LINE 案内"><p>新着と限定情報を LINE でお届け</p><a class="wt-lp-cta-action" href="#cat-line" aria-label="LINE で友だち追加"><i class="wt-i wt-i--bubble" aria-hidden="true"></i>LINE で友だち追加</a></aside>';
}

function wt_render_card_badges( $attributes = array(), $content = '', $block = null ) {
	$post_id = ( $block && isset( $block->context['postId'] ) ) ? (int) $block->context['postId'] : get_the_ID();
	if ( ! $post_id ) {
		return '';
	}
	$out = '<div class="wt-cat-card__badges">';
	if ( ( time() - get_post_time( 'U', true, $post_id ) ) < 30 * DAY_IN_SECONDS ) {
		$out .= '<span class="wt-cat-card__new">NEW</span>';
	}
	$tags = get_the_tags( $post_id );
	if ( $tags ) {
		$out .= '<ul class="wt-cat-card__tags">';
		foreach ( array_slice( $tags, 0, 3 ) as $t ) {
			$link = get_term_link( $t );
			if ( ! is_wp_error( $link ) ) {
				$out .= '<li><a href="' . esc_url( $link ) . '">#' . esc_html( $t->name ) . '</a></li>';
			}
		}
		$out .= '</ul>';
	}
	return $out . '</div>';
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

// 2026-09-06 PO 反応 17 回目 WT-EVT-0275「off の SP 版がバグってね？」（Claude 解釈）: 記事末尾の関連 Query Loop（901 / 902）が
// 既定カテゴリ（Uncategorized）の fixture 投稿と表示中の記事自身を拾い、SP のグリッド 1 行目に本文と無関係なカードが入っていた。
// 既定カテゴリの投稿と自記事を除外する（表示件数は perPage のまま。該当が足りない小規模サイトでは件数が減るだけで崩れない）。
add_filter( 'query_loop_block_query_vars', function ( $query, $block ) {
	$query_id = ( $block instanceof WP_Block && isset( $block->context['queryId'] ) ) ? (int) $block->context['queryId'] : 0;
	if ( ! in_array( $query_id, array( 901, 902 ), true ) ) {
		return $query;
	}
	$default_cat = (int) get_option( 'default_category' );
	if ( $default_cat > 0 ) {
		$query['category__not_in'] = array_values( array_unique( array_merge( (array) ( $query['category__not_in'] ?? array() ), array( $default_cat ) ) ) );
	}
	$self = (int) get_queried_object_id();
	if ( $self > 0 ) {
		$query['post__not_in'] = array_values( array_unique( array_merge( (array) ( $query['post__not_in'] ?? array() ), array( $self ) ) ) );
	}
	return $query;
}, 10, 2 );

// PO 反応7（related 再設計、Claude 案）: アイキャッチ未設定の投稿でも関連カードの 16:9 サムネ枠を崩さないよう、
// 記事末尾の関連 Query Loop（parts/article-tail.html の queryId 901 / 902）の post-featured-image ブロックが空を返したときだけ
// 既定画像（同梱の無文字グラデーション lum-mid.jpg）を figure で返す。get_the_post_thumbnail() 自体には作用させない
// （カテゴリカード wt_category_card_image() の $attr・空プレースホルダはそのまま）。Astra レビュー是正。
// core/post-template は各投稿の子ブロックを context なしで再生成するため queryId が post-featured-image まで届かない。
// post-template 自身の context（queryId）を描画中だけ退避し、子の post-featured-image の context へ引き継ぐ。
add_filter( 'render_block_context', function ( $context, $parsed_block ) {
	$name = isset( $parsed_block['blockName'] ) ? $parsed_block['blockName'] : '';
	if ( 'core/post-template' === $name ) {
		$GLOBALS['wt_rendering_query_id'] = isset( $context['queryId'] ) ? (int) $context['queryId'] : 0;
	} elseif ( 'core/post-featured-image' === $name && ! isset( $context['queryId'] ) && ! empty( $GLOBALS['wt_rendering_query_id'] ) ) {
		$context['queryId'] = (int) $GLOBALS['wt_rendering_query_id'];
	}
	return $context;
}, 10, 2 );
add_filter( 'render_block_core/post-template', function ( $html ) {
	$GLOBALS['wt_rendering_query_id'] = 0;
	return $html;
} );
add_filter( 'render_block_core/post-featured-image', function ( $html, $block, $instance ) {
	if ( '' !== trim( (string) $html ) || is_admin() ) {
		return $html;
	}
	$query_id = ( $instance instanceof WP_Block && isset( $instance->context['queryId'] ) ) ? (int) $instance->context['queryId'] : 0;
	if ( ! in_array( $query_id, array( 901, 902 ), true ) ) {
		return $html;
	}
	$src = get_theme_file_uri( 'assets/img/lum-mid.jpg' );
	return '<figure class="wp-block-post-featured-image"><img class="wt-thumb-fallback" src="' . esc_url( $src ) . '" alt="" width="1600" height="900" loading="lazy" decoding="async" /></figure>';
}, 10, 3 );
