<?php
/**
 * Title: 記事末 CTA
 * Slug: agent-neo/article-cta
 * Categories: featured, agent-neo-home
 * Description: 記事ページ末尾に表示する AGENT NEO 導入 CTA バナー。
 * Keywords: cta, article, conversion
 * Viewport Width: 1280
 * Block Types: core/group
 * Post Types: wp_template, post
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"className":"an-article-cta","style":{"border":{"radius":"10px"},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dsecondary)"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group an-article-cta has-background" style="border-radius:10px;background-color:var(--wp--preset--color--secondary);padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">

	<!-- wp:heading {"level":2,"textAlign":"center","style":{"typography":{"fontWeight":"700","fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dx-large)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
	<h2 class="wp-block-heading has-text-align-center" style="font-weight:700;font-size:var(--wp--preset--font-size--x-large);margin-bottom:var(--wp--preset--spacing--20)"><?php esc_html_e( 'AGENT NEO で、記事を自動で生み出す。', 'agent-neo' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","fontSize":"medium","textColor":"foreground","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} -->
	<p class="has-text-align-center has-medium-font-size has-foreground-color has-text-color" style="margin-bottom:var(--wp--preset--spacing--30)"><?php esc_html_e( '初期設定だけで、生成・SEO最適化・配信まで動き続けます。', 'agent-neo' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
	<div class="wp-block-buttons">
		<!-- wp:button {"backgroundColor":"accent-aa","textColor":"background","className":"an-cta an-cta--article_cta","style":{"border":{"radius":"6px"},"typography":{"fontWeight":"700","fontSize":"1.0625rem"},"spacing":{"padding":{"top":"1rem","bottom":"1rem","left":"2.5rem","right":"2.5rem"}}}} -->
		<div class="wp-block-button an-cta an-cta--article_cta">
			<a class="wp-block-button__link has-background-color has-accent-aa-background-color has-text-color has-background has-custom-font-size wp-element-button" href="#" style="border-radius:6px;font-weight:700;font-size:1.0625rem;padding-top:1rem;padding-bottom:1rem;padding-left:2.5rem;padding-right:2.5rem"><?php esc_html_e( '導入をはじめる →', 'agent-neo' ); ?></a>
		</div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->

</div>
<!-- /wp:group -->
