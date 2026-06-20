<?php
/**
 * Title: Hero セクション
 * Slug: agent-neo/hero
 * Categories: featured, banner
 * Description: ページ上部に配置するヒーローセクション。見出し・説明文・CTAボタンで構成。secondary 背景・インク色テキスト・オレンジ CTA。
 * Keywords: hero, banner, cta, top
 * Viewport Width: 1280
 * Block Types: core/group
 * Post Types: page, wp_template
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"align":"full","className":"an-hero-section","backgroundColor":"secondary","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull an-hero-section has-secondary-background-color has-background" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">

	<!-- wp:heading {"level":1,"textAlign":"center","fontSize":"xx-large","style":{"typography":{"fontWeight":"700"}}} -->
	<h1 class="wp-block-heading has-text-align-center has-xx-large-font-size" style="font-weight:700">
		<?php esc_html_e( 'AI が運用する WordPress', 'agent-neo' ); ?>
	</h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","fontSize":"large"} -->
	<p class="has-text-align-center has-large-font-size">
		<?php esc_html_e( 'コンテンツ生成・SEO最適化・配信まで、すべて自動で動き続けます。', 'agent-neo' ); ?>
	</p>
	<!-- /wp:paragraph -->

	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
	<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--40)">
		<!-- wp:button {"backgroundColor":"accent","textColor":"background","fontSize":"medium","style":{"border":{"radius":"4px"},"typography":{"fontWeight":"700"}}} -->
		<div class="wp-block-button">
			<a class="wp-block-button__link has-accent-background-color has-background-color has-text-color has-background has-medium-font-size wp-element-button" href="#" style="border-radius:4px;font-weight:700">
				<?php esc_html_e( 'はじめる', 'agent-neo' ); ?>
			</a>
		</div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->

</div>
<!-- /wp:group -->
