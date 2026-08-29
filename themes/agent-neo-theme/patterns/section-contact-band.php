<?php
/**
 * Title: セクション ⑭ お問い合わせ帯
 * Slug: agent-neo/section-contact-band
 * Categories: agent-neo
 * Description: 相談のきっかけと CTA を横並びにする短いお問い合わせ帯。長い LP の途中や記事末尾に挿入できる。
 * Keywords: section, contact, cta, band, conversion
 * Viewport Width: 1280
 * Block Types: core/group, core/button
 * Post Types: wp_template, page, post
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"anchor":"contact","align":"full","className":"an-section an-section\u002d\u002dcontact-band","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"backgroundColor":"accent-aa","textColor":"background","layout":{"type":"constrained"}} -->
<div id="contact" class="wp-block-group alignfull an-section an-section--contact-band has-background-color has-accent-aa-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--40)"><!-- wp:group {"align":"wide","style":{"spacing":{"blockGap":"var:preset|spacing|40"}},"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap","verticalAlignment":"center"}} -->
<div class="wp-block-group alignwide"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|10"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group"><!-- wp:heading {"textColor":"background","style":{"typography":{"fontWeight":"800"}},"fontSize":"x-large"} -->
<h2 class="wp-block-heading has-background-color has-text-color has-x-large-font-size" style="font-weight:800"><?php esc_html_e( '次の一歩を、一緒に整理しませんか？', 'agent-neo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"textColor":"background","fontSize":"small"} -->
<p class="has-background-color has-text-color has-small-font-size"><?php esc_html_e( 'まだ言葉になっていない課題からお聞かせください。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:button {"backgroundColor":"background","textColor":"accent-aa","style":{"typography":{"fontWeight":"700"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-accent-aa-color has-background-background-color has-text-color has-background wp-element-button" href="/contact/" style="font-weight:700"><?php esc_html_e( 'お問い合わせ', 'agent-neo' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
