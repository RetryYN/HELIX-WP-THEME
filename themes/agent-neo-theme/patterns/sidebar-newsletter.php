<?php
/**
 * Title: サイドバー ⑧ ニュースレター
 * Slug: agent-neo/sidebar-newsletter
 * Categories: agent-neo, agent-neo-shared
 * Description: 更新通知の価値を短く伝え、登録ページへ送るニュースレター用サイドバー。記事の読了後に継続接点をつくりたいサイト向け。
 * Keywords: sidebar, newsletter, subscribe, updates
 * Viewport Width: 400
 * Block Types: core/template-part/sidebar, core/button
 * Post Types: wp_template, wp_template_part
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"className":"an-sidebar an-sidebar\u002d\u002dnewsletter","style":{"spacing":{"blockGap":"var:preset|spacing|40"}},"layout":{"type":"default"}} -->
<div class="wp-block-group an-sidebar an-sidebar--newsletter"><!-- wp:group {"className":"an-sidebar-widget an-sidebar-newsletter-card","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|20"}},"backgroundColor":"primary","textColor":"background","layout":{"type":"constrained"}} -->
<div class="wp-block-group an-sidebar-widget an-sidebar-newsletter-card has-background-color has-primary-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--30)"><!-- wp:heading {"textColor":"background","style":{"typography":{"fontWeight":"700"}},"fontSize":"large"} -->
<h2 class="wp-block-heading has-background-color has-text-color has-large-font-size" style="font-weight:700"><?php esc_html_e( '更新を受け取る', 'agent-neo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"textColor":"background","fontSize":"small"} -->
<p class="has-background-color has-text-color has-small-font-size"><?php esc_html_e( '新しい記事と運用のヒントを、無理のない頻度でお届けします。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:button {"backgroundColor":"accent","textColor":"primary","width":100,"style":{"typography":{"fontWeight":"700"}},"fontSize":"small"} -->
<div class="wp-block-button has-custom-width wp-block-button__width-100"><a class="wp-block-button__link has-primary-color has-accent-background-color has-text-color has-background has-small-font-size has-custom-font-size wp-element-button" href="/subscribe/" style="font-weight:700"><?php esc_html_e( '登録ページへ', 'agent-neo' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:group -->

<!-- wp:paragraph {"textColor":"muted","fontSize":"small"} -->
<p class="has-muted-color has-text-color has-small-font-size"><?php esc_html_e( '登録や配信停止はいつでも管理できます。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
