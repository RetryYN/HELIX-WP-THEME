<?php
/**
 * Title: ヘッダー ⑤ 画像一体（背景画像 + 透過ヘッダー + キャッチ）
 * Slug: agent-neo/header-image
 * Categories: agent-neo, agent-neo-shared
 * Description: 背景画像（カバー）の上にヘッダー行を重ね、下にサイトのキャッチを置く「ヘッダー一体型」。画像はカバーブロックの置き換えで差し替え、暗さは overlay-dark グラデーションで制御。
 * Keywords: header, image, cover, hero, unified
 * Viewport Width: 1280
 * Block Types: core/template-part/header
 * Post Types: wp_template, wp_template_part
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$an_header_bg = esc_url( get_theme_file_uri( 'assets/images/placeholder-cover.jpg' ) );
?>

<!-- wp:cover {"url":"<?php echo $an_header_bg; ?>","dimRatio":60,"isUserOverlayColor":true,"minHeight":52,"minHeightUnit":"vh","customGradient":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dgradient\u002d\u002doverlay-dark)","contentPosition":"top center","align":"full","className":"an-site-header-inner an-header\u002d\u002dimage","style":{"spacing":{"padding":{"top":"0","bottom":"var:preset|spacing|60","left":"0","right":"0"}}},"layout":{"type":"default"}} -->
<div class="wp-block-cover alignfull has-custom-content-position is-position-top-center an-site-header-inner an-header--image" style="padding-top:0;padding-right:0;padding-bottom:var(--wp--preset--spacing--60);padding-left:0;min-height:52vh"><img class="wp-block-cover__image-background" alt="" src="<?php echo $an_header_bg; ?>" data-object-fit="cover"/><span aria-hidden="true" class="wp-block-cover__background has-background-dim-60 has-background-dim wp-block-cover__gradient-background has-background-gradient" style="background:var(--wp--preset--gradient--overlay-dark)"></span><div class="wp-block-cover__inner-container">
<!-- wp:group {"className":"an-header-main","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"textColor":"background","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
<div class="wp-block-group an-header-main has-background-color has-text-color" style="padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--40)">
<!-- wp:site-title {"level":0,"style":{"typography":{"fontWeight":"700"}},"textColor":"background","fontSize":"large"} /-->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group">
<!-- wp:navigation {"textColor":"background","overlayBackgroundColor":"primary","overlayTextColor":"background","style":{"spacing":{"blockGap":"var:preset|spacing|30"},"typography":{"fontWeight":"600"}},"fontSize":"small","layout":{"type":"flex","justifyContent":"right","flexWrap":"nowrap"},"ariaLabel":"グローバルナビゲーション"} -->
<!-- wp:navigation-link {"label":"ホーム","url":"/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"記事","url":"/blog/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"お問い合わせ","url":"/contact/","kind":"custom","isTopLevelLink":true} /-->
<!-- /wp:navigation -->

<!-- wp:buttons -->
<div class="wp-block-buttons">
<!-- wp:button {"backgroundColor":"accent","textColor":"primary","style":{"typography":{"fontWeight":"700"}},"fontSize":"small"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-primary-color has-accent-background-color has-text-color has-background has-small-font-size has-custom-font-size wp-element-button" href="/contact/" style="font-weight:700"><?php esc_html_e( '無料で相談', 'agent-neo' ); ?></a></div>
<!-- /wp:button -->
</div>
<!-- /wp:buttons -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"an-header-catch","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|20"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group an-header-catch" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
<!-- wp:heading {"style":{"typography":{"fontWeight":"800","lineHeight":"1.1","textAlign":"center"}},"textColor":"background","fontSize":"xxx-large"} -->
<h2 class="wp-block-heading has-text-align-center has-background-color has-text-color has-xxx-large-font-size" style="font-weight:800;line-height:1.1"><?php esc_html_e( '運用を、設計で終わらせない。', 'agent-neo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:site-tagline {"style":{"typography":{"textAlign":"center"}},"textColor":"background","fontSize":"medium"} /-->
</div>
<!-- /wp:group -->
</div></div>
<!-- /wp:cover -->
