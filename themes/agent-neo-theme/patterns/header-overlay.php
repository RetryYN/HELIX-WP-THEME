<?php
/**
 * Title: ヘッダー ⑦ 透過オーバーレイ
 * Slug: agent-neo/header-overlay
 * Categories: agent-neo, agent-neo-shared
 * Description: 背景画像の上にロゴとナビゲーションを重ねる透過オーバーレイ型ヘッダー。キャンペーンやブランド訴求の強いトップページ向け。
 * Keywords: header, overlay, transparent, hero
 * Viewport Width: 1280
 * Block Types: core/template-part/header, core/cover
 * Post Types: wp_template, wp_template_part
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$an_cover = esc_url( get_theme_file_uri( 'assets/images/placeholder-cover.jpg' ) );
?>

<!-- wp:cover {"url":"<?php echo $an_cover; ?>","dimRatio":70,"overlayColor":"footer-bg","isUserOverlayColor":true,"minHeight":72,"minHeightUnit":"vh","align":"full","className":"an-site-header-inner an-header\u002d\u002doverlay","layout":{"type":"constrained"}} -->
<div class="wp-block-cover alignfull an-site-header-inner an-header--overlay" style="min-height:72vh"><img class="wp-block-cover__image-background" alt="" src="<?php echo $an_cover; ?>" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-footer-bg-background-color has-background-dim-70 has-background-dim"></span><div class="wp-block-cover__inner-container">
<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|40"}},"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap","verticalAlignment":"center"}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)">
<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group">
<!-- wp:site-logo /-->

<!-- wp:site-title {"level":0,"textColor":"background","style":{"typography":{"fontWeight":"700"}},"fontSize":"large"} /-->
</div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group">
<!-- wp:navigation {"textColor":"background","overlayMenu":"never","style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"fontSize":"small","layout":{"type":"flex","justifyContent":"right"},"ariaLabel":"<?php echo esc_attr__( 'グローバルナビゲーション', 'agent-neo' ); ?>"} -->
<!-- wp:navigation-link {"label":"<?php echo esc_attr__( 'サービス', 'agent-neo' ); ?>","url":"/lp/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"<?php echo esc_attr__( '記事', 'agent-neo' ); ?>","url":"/blog/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"<?php echo esc_attr__( '会社情報', 'agent-neo' ); ?>","url":"/about/","kind":"custom","isTopLevelLink":true} /-->
<!-- /wp:navigation -->

<!-- wp:button {"backgroundColor":"accent-aa","textColor":"background","style":{"typography":{"fontWeight":"700"}},"fontSize":"small"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-background-color has-accent-aa-background-color has-text-color has-background has-small-font-size has-custom-font-size wp-element-button" href="/contact/" style="font-weight:700"><?php esc_html_e( '相談する', 'agent-neo' ); ?></a></div>
<!-- /wp:button -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div></div>
<!-- /wp:cover -->
