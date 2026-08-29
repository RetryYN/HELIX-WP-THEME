<?php
/**
 * Title: サイドバー ④ 追尾（目次代わりの見出しリンク + 小 CTA、スクロール追従）
 * Slug: agent-neo/sidebar-sticky
 * Categories: agent-neo, agent-neo-shared
 * Description: 画面上部に追従する薄いサイドバー。記事内の主要見出しへのリンクと、小さな CTA だけ。長文記事向け。追従はグループの「位置: 固定（sticky）」で指定。
 * Keywords: sidebar, sticky, toc, scroll
 * Viewport Width: 400
 * Block Types: core/template-part/sidebar
 * Post Types: wp_template, wp_template_part
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"className":"an-sidebar an-sidebar\u002d\u002dsticky","style":{"position":{"type":"sticky","top":"0px"},"spacing":{"blockGap":"var:preset|spacing|30","padding":{"top":"var:preset|spacing|30"}}},"layout":{"type":"default"}} -->
<div class="wp-block-group an-sidebar an-sidebar--sticky" style="padding-top:var(--wp--preset--spacing--30)"><!-- wp:group {"className":"an-sidebar-widget an-sidebar-toc","style":{"spacing":{"blockGap":"var:preset|spacing|10","padding":{"left":"var:preset|spacing|20"}},"border":{"left":{"color":"var(--wp--preset--color--secondary)","width":"1px","style":"solid"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group an-sidebar-widget an-sidebar-toc" style="border-left-color:var(--wp--preset--color--secondary);border-left-style:solid;border-left-width:1px;padding-left:var(--wp--preset--spacing--20)"><!-- wp:heading {"style":{"typography":{"fontWeight":"700","textTransform":"uppercase"}},"textColor":"muted","fontSize":"small"} -->
<h2 class="wp-block-heading has-muted-color has-text-color has-small-font-size" style="font-weight:700;text-transform:uppercase"><?php esc_html_e( 'この記事の内容', 'agent-neo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:list {"className":"an-toc-list","style":{"spacing":{"blockGap":"var:preset|spacing|10"}},"fontSize":"small"} -->
<ul class="wp-block-list an-toc-list has-small-font-size"><!-- wp:list-item -->
<li><a href="#sec-1"><?php esc_html_e( '1. 運用を自動化する前に決めること', 'agent-neo' ); ?></a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="#sec-2"><?php esc_html_e( '2. 記事の型と本数', 'agent-neo' ); ?></a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="#sec-3"><?php esc_html_e( '3. 公開前の検査', 'agent-neo' ); ?></a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="#sec-4"><?php esc_html_e( '4. 計測と改善', 'agent-neo' ); ?></a></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"an-sidebar-widget an-sidebar-mini-cta","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|10"}},"backgroundColor":"secondary","layout":{"type":"constrained"}} -->
<div class="wp-block-group an-sidebar-widget an-sidebar-mini-cta has-secondary-background-color has-background" style="padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)"><!-- wp:paragraph {"style":{"typography":{"fontWeight":"700"}},"fontSize":"small"} -->
<p class="has-small-font-size" style="font-weight:700"><?php esc_html_e( 'この記事の内容を、あなたのサイトで', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"accent-aa","textColor":"background","className":"has-custom-width wp-block-button__width-100","style":{"typography":{"fontWeight":"700"}},"fontSize":"small"} -->
<div class="wp-block-button has-custom-width wp-block-button__width-100"><a class="wp-block-button__link has-background-color has-accent-aa-background-color has-text-color has-background has-small-font-size has-custom-font-size wp-element-button" href="/contact/" style="font-weight:700"><?php esc_html_e( '30 分の無料診断 →', 'agent-neo' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"agent-neo/share-buttons"} /--></div>
<!-- /wp:group -->
