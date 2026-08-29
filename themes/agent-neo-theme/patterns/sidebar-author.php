<?php
/**
 * Title: サイドバー ⑤ 著者プロフィール
 * Slug: agent-neo/sidebar-author
 * Categories: agent-neo, agent-neo-shared
 * Description: アバター、著者名、役割、プロフィールリンクを縦にまとめたコンパクトな著者カード。記事一覧の信頼感を補強したいサイト向け。
 * Keywords: sidebar, author, profile, avatar
 * Viewport Width: 400
 * Block Types: core/template-part/sidebar, core/avatar
 * Post Types: wp_template, wp_template_part
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"className":"an-sidebar an-sidebar\u002d\u002dauthor","style":{"spacing":{"blockGap":"var:preset|spacing|40"}},"layout":{"type":"default"}} -->
<div class="wp-block-group an-sidebar an-sidebar--author"><!-- wp:group {"className":"an-sidebar-widget an-sidebar-author-card","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|20"}},"backgroundColor":"secondary","layout":{"type":"constrained"}} -->
<div class="wp-block-group an-sidebar-widget an-sidebar-author-card has-secondary-background-color has-background" style="padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)"><!-- wp:avatar {"size":72,"align":"center","style":{"border":{"radius":"var:preset|spacing|60"}}} /-->

<!-- wp:paragraph {"align":"center","textColor":"muted","fontSize":"small"} -->
<p class="has-text-align-center has-muted-color has-text-color has-small-font-size"><?php esc_html_e( 'このサイトの編集者', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:post-author-name {"textAlign":"center","style":{"typography":{"fontWeight":"700"}},"fontSize":"medium"} /-->

<!-- wp:paragraph {"align":"center","textColor":"muted","fontSize":"small"} -->
<p class="has-text-align-center has-muted-color has-text-color has-small-font-size"><?php esc_html_e( '運用の工夫と学びを、実例とともに紹介しています。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:button {"width":100,"className":"is-style-outline","textColor":"accent-aa","fontSize":"small"} -->
<div class="wp-block-button has-custom-width wp-block-button__width-100 is-style-outline"><a class="wp-block-button__link has-accent-aa-color has-text-color has-small-font-size has-custom-font-size wp-element-button" href="/author/"><?php esc_html_e( 'プロフィールを見る', 'agent-neo' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"an-sidebar-widget","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group an-sidebar-widget"><!-- wp:heading {"style":{"typography":{"fontWeight":"700","textTransform":"uppercase"},"border":{"bottom":{"color":"var(u002du002dwpu002du002dpresetu002du002dcoloru002du002dprimary)","width":"1px","style":"solid"}},"spacing":{"padding":{"bottom":"var:preset|spacing|10"}}},"textColor":"muted","fontSize":"small"} -->
<h2 class="wp-block-heading has-muted-color has-text-color has-small-font-size" style="border-bottom-color:var(u002du002dwpu002du002dpresetu002du002dcoloru002du002dprimary);border-bottom-style:solid;border-bottom-width:1px;padding-bottom:var(--wp--preset--spacing--10);font-weight:700;text-transform:uppercase"><?php esc_html_e( 'プロフィール', 'agent-neo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"textColor":"muted","fontSize":"small"} -->
<p class="has-muted-color has-text-color has-small-font-size"><?php esc_html_e( '記事の背景や更新のお知らせをまとめています。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
