<?php
/**
 * Title: ホーム ⑤ Resources
 * Slug: agent-neo/home-resources
 * Categories: featured, agent-neo-home
 * Description: home-blueprint 第5セクション。最新記事・ガイドを3件グリッド表示。
 * Keywords: home, resources, articles, posts, blog
 * Viewport Width: 1280
 * Block Types: core/group, core/query
 * Post Types: wp_template, page
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"anchor":"home_resources","className":"an-section an-section--home_resources","backgroundColor":"secondary","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","wideSize":"1200px"}} -->
<div class="wp-block-group an-section an-section--home_resources has-secondary-background-color has-background" id="home_resources" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40)">

	<!-- wp:heading {"level":2,"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--x-large)"},"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
	<h2 class="wp-block-heading" style="font-weight:700;font-size:var(--wp--preset--font-size--x-large);margin-bottom:var(--wp--preset--spacing--40)"><?php esc_html_e( '最新の記事・ガイド', 'agent-neo' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:query {"queryId":2,"query":{"perPage":3,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":false},"align":"wide","layout":{"type":"default"}} -->
	<div class="wp-block-query alignwide">

		<!-- wp:post-template {"layout":{"type":"grid","columnCount":3},"style":{"spacing":{"blockGap":"var:preset|spacing|30"}}} -->

			<!-- wp:group {"className":"an-card","style":{"border":{"radius":"8px","color":"var(--wp--preset--color--secondary)","width":"1px"},"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"}},"color":{"background":"var(--wp--preset--color--background)"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"left"}} -->
			<div class="wp-block-group an-card has-background" style="border-radius:8px;border:1px solid var(--wp--preset--color--secondary);overflow:hidden;background-color:var(--wp--preset--color--background)">

				<!-- wp:group {"className":"an-card-image-wrap","style":{"dimensions":{"aspectRatio":"16/9"},"color":{"background":"var(--wp--preset--color--secondary)"},"spacing":{"padding":{"top":"0","bottom":"0","left":"0","right":"0"}}},"layout":{"type":"default"}} -->
				<div class="wp-block-group an-card-image-wrap has-background" style="aspect-ratio:16/9;background-color:var(--wp--preset--color--secondary);overflow:hidden;display:flex;align-items:center;justify-content:center">

					<!-- wp:post-featured-image {"isLink":true,"width":"100%","height":"100%","style":{"layout":{"selfStretch":"fill"}}} /-->

				</div>
				<!-- /wp:group -->

				<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|10"}},"layout":{"type":"flex","orientation":"vertical"}} -->
				<div class="wp-block-group" style="padding:var(--wp--preset--spacing--30);gap:var(--wp--preset--spacing--10)">

					<!-- wp:post-terms {"term":"category","fontSize":"small","style":{"elements":{"link":{"color":{"text":"var(--wp--preset--color--background)"},"typography":{"fontWeight":"700"}}},"color":{"background":"var(--wp--preset--color--accent-aa)","text":"var(--wp--preset--color--background)"},"spacing":{"padding":{"top":"2px","bottom":"2px","left":"8px","right":"8px"}},"border":{"radius":"12px"}},"className":"an-card-category"} /-->

					<!-- wp:post-title {"isLink":true,"level":3,"style":{"typography":{"fontWeight":"700","fontSize":"1.0625rem","lineHeight":"1.3"},"elements":{"link":{"color":{"text":"var(--wp--preset--color--foreground)"},":hover":{"color":{"text":"var(--wp--preset--color--accent)"}}}}}} /-->

					<!-- wp:post-date {"fontSize":"small","style":{"color":{"text":"var(--wp--preset--color--muted)"}}} /-->

				</div>
				<!-- /wp:group -->

			</div>
			<!-- /wp:group -->

		<!-- /wp:post-template -->

		<!-- wp:query-no-results -->
			<!-- wp:paragraph -->
			<p><?php esc_html_e( '記事がまだありません。', 'agent-neo' ); ?></p>
			<!-- /wp:paragraph -->
		<!-- /wp:query-no-results -->

	</div>
	<!-- /wp:query -->

	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
	<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--40)">

		<!-- wp:button {"className":"an-cta an-cta--resources_all is-style-outline","style":{"border":{"radius":"6px","color":"var(--wp--preset--color--accent-aa)","width":"2px"},"color":{"text":"#8a3d00"},"typography":{"fontWeight":"700","fontSize":"1.0625rem"},"spacing":{"padding":{"top":"0.875rem","bottom":"0.875rem","left":"2rem","right":"2rem"}}}} -->
		<div class="wp-block-button is-style-outline an-cta an-cta--resources_all">
			<a class="wp-block-button__link wp-element-button" href="/" style="border-radius:6px;border:2px solid var(--wp--preset--color--accent-aa);color:#8a3d00;font-weight:700;font-size:1.0625rem;padding-top:0.875rem;padding-bottom:0.875rem;padding-left:2rem;padding-right:2rem"><?php esc_html_e( '記事一覧へ →', 'agent-neo' ); ?></a>
		</div>
		<!-- /wp:button -->

	</div>
	<!-- /wp:buttons -->

</div>
<!-- /wp:group -->
