<?php
/**
 * Title: ホーム ① Brand Hero
 * Slug: agent-neo/home-hero
 * Categories: featured, agent-neo-home
 * Description: home-blueprint 第1セクション。ブランドの第一印象を作るメインビジュアル。
 * Keywords: home, hero, brand, mv
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

<!-- wp:group {"align":"full","anchor":"home_hero","className":"an-section an-section--home_hero","backgroundColor":"secondary","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull an-section an-section--home_hero has-secondary-background-color has-background" id="home_hero" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40)">

	<!-- wp:heading {"level":1,"textAlign":"center","style":{"typography":{"fontWeight":"800","fontSize":"var(--wp--preset--font-size--xxx-large)","lineHeight":"1.1"},"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}},"textColor":"primary"} -->
	<h1 class="wp-block-heading has-text-align-center has-primary-color has-text-color" style="font-weight:800;font-size:var(--wp--preset--font-size--xxx-large);line-height:1.1;margin-bottom:var(--wp--preset--spacing--30)"><?php esc_html_e( 'AIが運用しつづける、WordPressテーマ。', 'agent-neo' ); ?></h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"var(--wp--preset--font-size--large)","lineHeight":"1.6"},"spacing":{"margin":{"bottom":"var:preset|spacing|40"}},"color":{"text":"#555555"}}} -->
	<p class="has-text-align-center" style="font-size:var(--wp--preset--font-size--large);line-height:1.6;margin-bottom:var(--wp--preset--spacing--40);color:#555555;max-width:640px;margin-left:auto;margin-right:auto"><?php esc_html_e( '記事生成・SEO最適化・配信まで自動。個人アフィリエイトから法人サイトまで、成果が崩れない情報設計を標準装備しています。', 'agent-neo' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
	<div class="wp-block-buttons">

		<!-- wp:button {"backgroundColor":"accent-aa","textColor":"background","className":"an-cta an-cta--home_hero_primary","style":{"border":{"radius":"6px"},"typography":{"fontWeight":"700","fontSize":"1.0625rem"},"spacing":{"padding":{"top":"1rem","bottom":"1rem","left":"2.5rem","right":"2.5rem"}}}} -->
		<div class="wp-block-button an-cta an-cta--home_hero_primary">
			<a class="wp-block-button__link has-accent-aa-background-color has-background-color has-text-color has-background wp-element-button" href="#" style="border-radius:6px;font-weight:700;font-size:1.0625rem;padding-top:1rem;padding-bottom:1rem;padding-left:2.5rem;padding-right:2.5rem"><?php esc_html_e( '導入をはじめる →', 'agent-neo' ); ?></a>
		</div>
		<!-- /wp:button -->

		<!-- wp:button {"className":"an-cta an-cta--home_hero_secondary is-style-outline","style":{"border":{"radius":"6px","color":"var(--wp--preset--color--accent-aa)","width":"2px"},"color":{"text":"var(--wp--preset--color--accent-aa)"},"typography":{"fontWeight":"700","fontSize":"1.0625rem"},"spacing":{"padding":{"top":"1rem","bottom":"1rem","left":"2.5rem","right":"2.5rem"}}}} -->
		<div class="wp-block-button is-style-outline an-cta an-cta--home_hero_secondary">
			<a class="wp-block-button__link wp-element-button" href="#" style="border-radius:6px;border:2px solid var(--wp--preset--color--accent-aa);color:var(--wp--preset--color--accent-aa);font-weight:700;font-size:1.0625rem;padding-top:1rem;padding-bottom:1rem;padding-left:2.5rem;padding-right:2.5rem"><?php esc_html_e( '機能を見る', 'agent-neo' ); ?></a>
		</div>
		<!-- /wp:button -->

	</div>
	<!-- /wp:buttons -->

</div>
<!-- /wp:group -->
