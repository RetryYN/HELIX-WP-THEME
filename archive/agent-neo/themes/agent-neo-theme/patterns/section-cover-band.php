<?php
/**
 * Title: セクション 帯（背景画像 + 半透明 + 全幅、中に 1 文と CTA）
 * Slug: agent-neo/section-cover-band
 * Categories: agent-neo
 * Description: 本文の途中に差し込む全幅の画像帯。低い高さ、固定背景、primary のオーバーレイで文字を確保。ページに奥行きの層を足す。
 * Keywords: section, cover, band, full, parallax
 * Viewport Width: 1280
 * Block Types: core/cover
 * Post Types: wp_template, page, post
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$an_cover = esc_url( get_theme_file_uri( 'assets/images/placeholder-cover.jpg' ) );
?>

<!-- wp:cover {"url":"<?php echo $an_cover; ?>","hasParallax":true,"dimRatio":70,"overlayColor":"primary","minHeight":36,"minHeightUnit":"vh","align":"full","className":"an-section an-section\u002d\u002dband","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-cover alignfull has-parallax an-section an-section--band" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--40);min-height:36vh"><div class="wp-block-cover__image-background has-parallax" style="background-position:50% 50%;background-image:url(<?php echo $an_cover; ?>)"></div><span aria-hidden="true" class="wp-block-cover__background has-primary-background-color has-background-dim-70 has-background-dim"></span><div class="wp-block-cover__inner-container">
<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap","verticalAlignment":"center"}} -->
<div class="wp-block-group">
<!-- wp:heading {"style":{"typography":{"fontWeight":"700","lineHeight":"1.3"}},"textColor":"background","fontSize":"x-large"} -->
<h2 class="wp-block-heading has-background-color has-text-color has-x-large-font-size" style="font-weight:700;line-height:1.3"><?php esc_html_e( '同じ記事を、来月も、再来月も更新しつづける。', 'agent-neo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:buttons -->
<div class="wp-block-buttons">
<!-- wp:button {"backgroundColor":"accent","textColor":"primary","style":{"typography":{"fontWeight":"700"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-primary-color has-accent-background-color has-text-color has-background wp-element-button" href="/lp/" style="font-weight:700"><?php esc_html_e( '仕組みを見る →', 'agent-neo' ); ?></a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
</div></div>
<!-- /wp:cover -->
