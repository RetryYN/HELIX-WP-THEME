<?php
/**
 * Title: ホーム ⑦ Final CTA
 * Slug: agent-neo/home-final-cta
 * Categories: featured, agent-neo-home
 * Description: home-blueprint 第7セクション。ページ末尾の導入促進 CTA。accent-aa 背景に白文字。
 * Keywords: home, cta, conversion, final, action
 * Viewport Width: 1280
 * Block Types: core/group
 * Post Types: wp_template, page
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"align":"full","anchor":"home_final_cta","className":"an-section an-section--home_final_cta","style":{"color":{"background":"var(--wp--preset--color--accent-aa)"},"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull an-section an-section--home_final_cta has-background" id="home_final_cta" style="background-color:var(--wp--preset--color--accent-aa);padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40)">

	<!-- wp:heading {"level":2,"textAlign":"center","textColor":"background","style":{"typography":{"fontWeight":"800","fontSize":"var(--wp--preset--font-size--xx-large)","lineHeight":"1.1"},"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} -->
	<h2 class="wp-block-heading has-text-align-center has-background-color has-text-color" style="font-weight:800;font-size:var(--wp--preset--font-size--xx-large);line-height:1.1;margin-bottom:var(--wp--preset--spacing--30)"><?php esc_html_e( 'サイト運用を、AIに任せる。', 'agent-neo' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","textColor":"background","style":{"typography":{"fontSize":"var(--wp--preset--font-size--large)","lineHeight":"1.6"},"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
	<p class="has-text-align-center has-background-color has-text-color" style="font-size:var(--wp--preset--font-size--large);line-height:1.6;margin-bottom:var(--wp--preset--spacing--40)"><?php esc_html_e( '初期設定だけで、生成・最適化・配信が動き続けます。', 'agent-neo' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
	<div class="wp-block-buttons">

		<!-- wp:button {"textColor":"accent-aa","className":"an-cta an-cta--final_primary","style":{"border":{"radius":"6px"},"color":{"background":"var(--wp--preset--color--background)"},"typography":{"fontWeight":"700","fontSize":"1.0625rem"},"spacing":{"padding":{"top":"1rem","bottom":"1rem","left":"2.5rem","right":"2.5rem"}}}} --><div class="wp-block-button an-cta an-cta--final_primary"><a class="wp-block-button__link has-accent-aa-color has-text-color has-background has-custom-font-size wp-element-button" href="#" style="border-radius:6px;background-color:var(--wp--preset--color--background);padding-top:1rem;padding-right:2.5rem;padding-bottom:1rem;padding-left:2.5rem;font-size:1.0625rem;font-weight:700"><?php esc_html_e( '導入をはじめる →', 'agent-neo' ); ?></a></div><!-- /wp:button -->

		<!-- wp:button {"textColor":"background","className":"an-cta an-cta--final_pricing is-style-outline","style":{"border":{"radius":"6px","color":"var:preset|color|background","width":"2px"},"typography":{"fontWeight":"700","fontSize":"1.0625rem"},"spacing":{"padding":{"top":"1rem","bottom":"1rem","left":"2.5rem","right":"2.5rem"}}}} --><div class="wp-block-button is-style-outline an-cta an-cta--final_pricing"><a class="wp-block-button__link has-background-color has-text-color has-border-color has-custom-font-size wp-element-button" href="#" style="border-color:var(--wp--preset--color--background);border-width:2px;border-radius:6px;padding-top:1rem;padding-right:2.5rem;padding-bottom:1rem;padding-left:2.5rem;font-size:1.0625rem;font-weight:700"><?php esc_html_e( '料金を見る', 'agent-neo' ); ?></a></div><!-- /wp:button -->

	</div>
	<!-- /wp:buttons -->

</div>
<!-- /wp:group -->
