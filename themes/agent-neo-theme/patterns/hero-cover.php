<?php
/**
 * Title: ヒーロー ③ 背景画像（カバー + オーバーレイ + 視差）
 * Slug: agent-neo/hero-cover
 * Categories: agent-neo, agent-neo-home
 * Description: 背景画像の上に overlay-dark グラデーションを重ねたカバー型ヒーロー。画像は置き換え、暗さはオーバーレイの不透明度、動きは「固定背景」で制御。
 * Keywords: hero, cover, image, parallax, mv
 * Viewport Width: 1280
 * Block Types: core/cover
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

<!-- wp:cover {"url":"<?php echo $an_cover; ?>","hasParallax":true,"dimRatio":50,"isUserOverlayColor":true,"minHeight":70,"minHeightUnit":"vh","customGradient":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dgradient\u002d\u002doverlay-dark)","contentPosition":"center left","align":"full","className":"an-section an-section\u002d\u002dhero an-hero\u002d\u002dcover","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","justifyContent":"left"}} -->
<div class="wp-block-cover alignfull has-parallax has-custom-content-position is-position-center-left an-section an-section--hero an-hero--cover" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40);min-height:70vh"><div class="wp-block-cover__image-background has-parallax" style="background-position:50% 50%;background-image:url(<?php echo $an_cover; ?>)"></div><span aria-hidden="true" class="wp-block-cover__background has-background-dim wp-block-cover__gradient-background has-background-gradient" style="background:var(--wp--preset--gradient--overlay-dark)"></span><div class="wp-block-cover__inner-container"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"constrained","justifyContent":"left","contentSize":"640px"}} -->
<div class="wp-block-group"><!-- wp:heading {"level":1,"style":{"typography":{"fontWeight":"800","lineHeight":"1.05"}},"textColor":"background","fontSize":"xxx-large"} -->
<h1 class="wp-block-heading has-background-color has-text-color has-xxx-large-font-size" style="font-weight:800;line-height:1.05"><?php esc_html_e( '止まらない運用を、標準装備に。', 'agent-neo' ); ?></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"textColor":"background","fontSize":"large"} -->
<p class="has-background-color has-text-color has-large-font-size"><?php esc_html_e( '写真は差し替え、暗さはオーバーレイ、動きは固定背景。3 つのつまみだけで印象が変わります。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}}} -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"accent","textColor":"primary","style":{"typography":{"fontWeight":"700"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-primary-color has-accent-background-color has-text-color has-background wp-element-button" href="/contact/" style="font-weight:700"><?php esc_html_e( '無料で相談する', 'agent-neo' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover -->
