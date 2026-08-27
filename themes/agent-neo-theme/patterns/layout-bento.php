<?php
/**
 * Title: レイアウト ① ベント（グリッド 4 列・大小混在）
 * Slug: agent-neo/layout-bento
 * Categories: agent-neo
 * Description: core/grid で 4 列を切り、1 枚目を 2 列 × 2 行、他を 1 マスにした大小混在のグリッド。面積の差で優先順位を見せる。カードは背景色とパディングのみ。
 * Keywords: layout, grid, bento, columns
 * Viewport Width: 1280
 * Block Types: core/group
 * Post Types: wp_template, page
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$an_cover = esc_url( get_theme_file_uri( 'assets/images/placeholder-cover.jpg' ) );
?>

<!-- wp:group {"align":"full","className":"an-section an-section--bento","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|40"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull an-section an-section--bento" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--40)">

	<!-- wp:heading {"level":2,"fontSize":"xx-large","style":{"typography":{"fontWeight":"800"}}} -->
	<h2 class="wp-block-heading has-xx-large-font-size" style="font-weight:800"><?php esc_html_e( 'できること', 'agent-neo' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:group {"align":"wide","className":"an-bento","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"grid","columnCount":4,"minimumColumnWidth":null}} -->
	<div class="wp-block-group alignwide an-bento">

		<!-- wp:cover {"url":"<?php echo $an_cover; ?>","dimRatio":60,"overlayColor":"primary","isUserOverlayColor":true,"minHeight":100,"minHeightUnit":"%","contentPosition":"bottom left","className":"an-bento-cell an-bento-cell--hero","style":{"layout":{"columnSpan":2,"rowSpan":2},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}}} -->
		<div class="wp-block-cover an-bento-cell an-bento-cell--hero has-custom-content-position is-position-bottom-left" style="padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40);min-height:100%"><span aria-hidden="true" class="wp-block-cover__background has-primary-background-color has-background-dim-60 has-background-dim"></span><img class="wp-block-cover__image-background" alt="" src="<?php echo $an_cover; ?>" data-object-fit="cover"/><div class="wp-block-cover__inner-container">
			<!-- wp:heading {"level":3,"fontSize":"x-large","textColor":"background","style":{"typography":{"fontWeight":"800"}}} -->
			<h3 class="wp-block-heading has-background-color has-text-color has-x-large-font-size" style="font-weight:800"><?php esc_html_e( '記事を書きつづける', 'agent-neo' ); ?></h3>
			<!-- /wp:heading -->
			<!-- wp:paragraph {"fontSize":"small","textColor":"background"} -->
			<p class="has-background-color has-text-color has-small-font-size"><?php esc_html_e( 'キーワードから構成・本文・図版まで。公開後も検索順位を見て自動で書き直します。', 'agent-neo' ); ?></p>
			<!-- /wp:paragraph -->
		</div></div>
		<!-- /wp:cover -->

		<!-- wp:group {"className":"an-bento-cell","backgroundColor":"secondary","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|10"}},"layout":{"type":"constrained"}} -->
		<div class="wp-block-group an-bento-cell has-secondary-background-color has-background" style="padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)">
			<!-- wp:heading {"level":3,"fontSize":"large","style":{"typography":{"fontWeight":"700"}}} --><h3 class="wp-block-heading has-large-font-size" style="font-weight:700"><?php esc_html_e( 'SEO 監査', 'agent-neo' ); ?></h3><!-- /wp:heading -->
			<!-- wp:paragraph {"fontSize":"small","textColor":"muted"} --><p class="has-muted-color has-text-color has-small-font-size"><?php esc_html_e( '内部リンク・重複・鮮度を毎週点検。', 'agent-neo' ); ?></p><!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"an-bento-cell","backgroundColor":"primary","textColor":"background","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|10"}},"layout":{"type":"constrained"}} -->
		<div class="wp-block-group an-bento-cell has-background-color has-primary-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)">
			<!-- wp:heading {"level":3,"fontSize":"xxx-large","textColor":"accent","style":{"typography":{"fontWeight":"800","lineHeight":"1"}}} --><h3 class="wp-block-heading has-accent-color has-text-color has-xxx-large-font-size" style="font-weight:800;line-height:1">24h</h3><!-- /wp:heading -->
			<!-- wp:paragraph {"fontSize":"small","textColor":"background"} --><p class="has-background-color has-text-color has-small-font-size"><?php esc_html_e( '止まらない運用サイクル', 'agent-neo' ); ?></p><!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"an-bento-cell","gradient":"secondary-fade","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|10"},"border":{"width":"1px","color":"var(--wp--preset--color--secondary)","style":"solid"}},"layout":{"type":"constrained"}} -->
		<div class="wp-block-group an-bento-cell has-secondary-fade-gradient-background has-background" style="border:1px solid var(--wp--preset--color--secondary);padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)">
			<!-- wp:heading {"level":3,"fontSize":"large","style":{"typography":{"fontWeight":"700"}}} --><h3 class="wp-block-heading has-large-font-size" style="font-weight:700"><?php esc_html_e( '配信', 'agent-neo' ); ?></h3><!-- /wp:heading -->
			<!-- wp:paragraph {"fontSize":"small","textColor":"muted"} --><p class="has-muted-color has-text-color has-small-font-size"><?php esc_html_e( 'X・メール・RSS へ同時に。', 'agent-neo' ); ?></p><!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"an-bento-cell","backgroundColor":"secondary","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|10"}},"layout":{"type":"constrained"}} -->
		<div class="wp-block-group an-bento-cell has-secondary-background-color has-background" style="padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)">
			<!-- wp:heading {"level":3,"fontSize":"large","style":{"typography":{"fontWeight":"700"}}} --><h3 class="wp-block-heading has-large-font-size" style="font-weight:700"><?php esc_html_e( '導入事例', 'agent-neo' ); ?></h3><!-- /wp:heading -->
			<!-- wp:paragraph {"fontSize":"small","style":{"typography":{"fontWeight":"700"}}} --><p class="has-small-font-size" style="font-weight:700"><a href="/category/case/"><?php esc_html_e( '3 社の 6 か月を見る →', 'agent-neo' ); ?></a></p><!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"an-bento-cell","backgroundColor":"accent","textColor":"primary","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|10"},"layout":{"columnSpan":2}},"layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"center","flexWrap":"wrap"}} -->
		<div class="wp-block-group an-bento-cell has-primary-color has-accent-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)">
			<!-- wp:heading {"level":3,"fontSize":"large","textColor":"primary","style":{"typography":{"fontWeight":"800"}}} --><h3 class="wp-block-heading has-primary-color has-text-color has-large-font-size" style="font-weight:800"><?php esc_html_e( '30 分で現状診断', 'agent-neo' ); ?></h3><!-- /wp:heading -->
			<!-- wp:buttons --><div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"primary","textColor":"background","fontSize":"small","style":{"typography":{"fontWeight":"700"}}} --><div class="wp-block-button"><a class="wp-block-button__link has-background-color has-primary-background-color has-text-color has-background has-small-font-size wp-element-button" href="/contact/" style="font-weight:700"><?php esc_html_e( '予約する →', 'agent-neo' ); ?></a></div><!-- /wp:button --></div><!-- /wp:buttons -->
		</div>
		<!-- /wp:group -->

	</div>
	<!-- /wp:group -->

</div>
<!-- /wp:group -->
