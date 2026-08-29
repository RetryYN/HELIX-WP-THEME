<?php
/**
 * Title: ヒーロー ⑥ 動画風ダーク
 * Slug: agent-neo/hero-video-dark
 * Categories: agent-neo, agent-neo-home
 * Description: 暗い背景画像、短いコピー、再生イメージの CTA を組み合わせた動画風ヒーロー。映像やデモへの入口に向く静的パターン。
 * Keywords: hero, video, dark, play, demo
 * Viewport Width: 1280
 * Block Types: core/cover, core/button
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

<!-- wp:cover {"url":"<?php echo $an_cover; ?>","dimRatio":80,"overlayColor":"footer-bg","isUserOverlayColor":true,"minHeight":64,"minHeightUnit":"vh","align":"full","className":"an-section an-section\u002d\u002dhero an-hero\u002d\u002dvideo-dark","layout":{"type":"constrained"}} -->
<div class="wp-block-cover alignfull an-section an-section--hero an-hero--video-dark" style="min-height:64vh"><div class="wp-block-cover__image-background" style="background-position:50% 50%;background-image:url(<?php echo $an_cover; ?>)"></div><span aria-hidden="true" class="wp-block-cover__background has-footer-bg-background-color has-background-dim-80 has-background-dim"></span><div class="wp-block-cover__inner-container"><!-- wp:group {"align":"wide","style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group alignwide"><!-- wp:paragraph {"align":"center","textColor":"accent","style":{"typography":{"fontWeight":"700","textTransform":"uppercase"}},"fontSize":"small"} -->
<p class="has-text-align-center has-accent-color has-text-color has-small-font-size" style="font-weight:700;text-transform:uppercase"><?php esc_html_e( '見ることでわかる', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","level":1,"textColor":"background","style":{"typography":{"fontWeight":"800","lineHeight":"1.08"}},"fontSize":"xxx-large"} -->
<h1 class="wp-block-heading has-text-align-center has-background-color has-text-color has-xxx-large-font-size" style="font-weight:800;line-height:1.08"><?php esc_html_e( '仕組みを、短いデモで。', 'agent-neo' ); ?></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","textColor":"background","fontSize":"large"} -->
<p class="has-text-align-center has-background-color has-text-color has-large-font-size"><?php esc_html_e( '流れを知れば、次に決めることが見えてきます。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"accent","textColor":"primary","className":"an-video-play-button","style":{"typography":{"fontWeight":"700"}}} -->
<div class="wp-block-button an-video-play-button"><a class="wp-block-button__link has-primary-color has-accent-background-color has-text-color has-background wp-element-button" href="/video/"><?php esc_html_e( '▶ 再生イメージを見る', 'agent-neo' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover -->
