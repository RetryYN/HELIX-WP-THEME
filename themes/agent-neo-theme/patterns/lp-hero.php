<?php
/**
 * Title: LP ヒーロー（ファーストビュー）
 * Slug: agent-neo/lp-hero
 * Categories: agent-neo
 * Description: LP ファーストビュー。大見出し・リード文・オレンジ CTA ボタン。secondary 背景帯。
 * Keywords: lp, hero, cta, firstview
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

<!-- wp:group {"align":"full","className":"an-lp-hero","backgroundColor":"secondary","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull an-lp-hero has-secondary-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40)">

	<!-- wp:heading {"level":1,"textAlign":"center","style":{"typography":{"fontWeight":"800","fontSize":"var(--wp--preset--font-size--xxx-large)","lineHeight":"1.1"},"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}},"textColor":"primary"} -->
	<h1 class="wp-block-heading has-text-align-center has-primary-color has-text-color" style="font-weight:800;font-size:var(--wp--preset--font-size--xxx-large);line-height:1.1;margin-bottom:var(--wp--preset--spacing--30)"><?php esc_html_e( 'WordPress 運用を、AI に任せる時代へ。', 'agent-neo' ); ?></h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"var(--wp--preset--font-size--large)","lineHeight":"1.7"},"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
	<p class="has-text-align-center" style="font-size:var(--wp--preset--font-size--large);line-height:1.7;margin-bottom:var(--wp--preset--spacing--40)"><?php esc_html_e( '記事生成から SEO 最適化・公開・分析まで、AGENT NEO がすべて自動で動き続けます。', 'agent-neo' ); ?><br><?php esc_html_e( '担当者の手間を省き、コンテンツ品質を継続的に高めます。', 'agent-neo' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"blockGap":"var:preset|spacing|20"}}} -->
	<div class="wp-block-buttons">
		<!-- wp:button {"backgroundColor":"accent-aa","textColor":"background","style":{"border":{"radius":"6px"},"typography":{"fontWeight":"700","fontSize":"1.0625rem"},"spacing":{"padding":{"top":"1rem","bottom":"1rem","left":"2.5rem","right":"2.5rem"}}}} -->
		<div class="wp-block-button">
			<a class="wp-block-button__link has-accent-aa-background-color has-background-color has-text-color has-background wp-element-button" href="#" style="border-radius:6px;font-weight:700;font-size:1.0625rem;padding-top:1rem;padding-bottom:1rem;padding-left:2.5rem;padding-right:2.5rem"><?php esc_html_e( '無料で試してみる →', 'agent-neo' ); ?></a>
		</div>
		<!-- /wp:button -->
		<!-- wp:button {"className":"is-style-outline","style":{"border":{"radius":"6px","color":"var(--wp--preset--color--primary)","width":"2px"},"typography":{"fontWeight":"600","fontSize":"1rem"},"spacing":{"padding":{"top":"1rem","bottom":"1rem","left":"2rem","right":"2rem"}},"color":{"text":"var(--wp--preset--color--primary)"}}} -->
		<div class="wp-block-button is-style-outline">
			<a class="wp-block-button__link wp-element-button" href="#" style="border-radius:6px;border:2px solid var(--wp--preset--color--primary);font-weight:600;font-size:1rem;padding-top:1rem;padding-bottom:1rem;padding-left:2rem;padding-right:2rem;color:var(--wp--preset--color--primary)"><?php esc_html_e( '導入事例を見る', 'agent-neo' ); ?></a>
		</div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->

</div>
<!-- /wp:group -->
