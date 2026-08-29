<?php
/**
 * Title: ホーム ② Gateway Grid
 * Slug: agent-neo/home-gateway
 * Categories: featured, agent-neo-home
 * Description: home-blueprint 第2セクション。目的別導線カード3列（DP-003 Gateway）。
 * Keywords: home, gateway, grid, navigation
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

<!-- wp:group {"anchor":"home_gateway","className":"an-section an-section--home_gateway","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","wideSize":"1200px"}} -->
<div class="wp-block-group an-section an-section--home_gateway" id="home_gateway" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40)">

	<!-- wp:heading {"level":2,"textAlign":"center","style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--x-large)"},"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
	<h2 class="wp-block-heading has-text-align-center" style="font-weight:700;font-size:var(--wp--preset--font-size--x-large);margin-bottom:var(--wp--preset--spacing--40)"><?php esc_html_e( '目的から選ぶ', 'agent-neo' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":"var:preset|spacing|30"}}} -->
	<div class="wp-block-columns alignwide">

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"an-gateway-card","style":{"border":{"radius":"8px","color":"var(--wp--preset--color--secondary)","width":"1px"},"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}},"color":{"background":"var(--wp--preset--color--background)"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"left"}} -->
			<div class="wp-block-group an-gateway-card has-border-color has-background" style="border-radius:8px;border-color:var(--wp--preset--color--secondary);border-width:1px;background-color:var(--wp--preset--color--background);padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)">

				<!-- wp:heading {"level":3,"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--large)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
				<h3 class="wp-block-heading" style="font-weight:700;font-size:var(--wp--preset--font-size--large);margin-bottom:var(--wp--preset--spacing--20)"><?php esc_html_e( 'アフィリエイト記事', 'agent-neo' ); ?></h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)"},"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium);margin-bottom:var(--wp--preset--spacing--30)"><?php esc_html_e( '収益記事を量産し、検索意図に合わせて自動で最適化。', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph {"style":{"elements":{"link":{"color":{"text":"var(--wp--preset--color--accent-aa)"}}}},"className":"an-cta an-cta--gw_affiliate"} -->
				<p class="an-cta an-cta--gw_affiliate" style=""><a href="#"><?php esc_html_e( '記事運用を見る →', 'agent-neo' ); ?></a></p>
				<!-- /wp:paragraph -->

			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"an-gateway-card","style":{"border":{"radius":"8px","color":"var(--wp--preset--color--secondary)","width":"1px"},"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}},"color":{"background":"var(--wp--preset--color--background)"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"left"}} -->
			<div class="wp-block-group an-gateway-card has-border-color has-background" style="border-radius:8px;border-color:var(--wp--preset--color--secondary);border-width:1px;background-color:var(--wp--preset--color--background);padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)">

				<!-- wp:heading {"level":3,"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--large)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
				<h3 class="wp-block-heading" style="font-weight:700;font-size:var(--wp--preset--font-size--large);margin-bottom:var(--wp--preset--spacing--20)"><?php esc_html_e( '法人LP・HP', 'agent-neo' ); ?></h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)"},"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium);margin-bottom:var(--wp--preset--spacing--30)"><?php esc_html_e( '構造から作り込む、成果が崩れないページ設計。', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph {"style":{"elements":{"link":{"color":{"text":"var(--wp--preset--color--accent-aa)"}}}},"className":"an-cta an-cta--gw_corporate"} -->
				<p class="an-cta an-cta--gw_corporate" style=""><a href="#"><?php esc_html_e( 'LP設計を見る →', 'agent-neo' ); ?></a></p>
				<!-- /wp:paragraph -->

			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"an-gateway-card","style":{"border":{"radius":"8px","color":"var(--wp--preset--color--secondary)","width":"1px"},"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}},"color":{"background":"var(--wp--preset--color--background)"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"left"}} -->
			<div class="wp-block-group an-gateway-card has-border-color has-background" style="border-radius:8px;border-color:var(--wp--preset--color--secondary);border-width:1px;background-color:var(--wp--preset--color--background);padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)">

				<!-- wp:heading {"level":3,"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--large)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
				<h3 class="wp-block-heading" style="font-weight:700;font-size:var(--wp--preset--font-size--large);margin-bottom:var(--wp--preset--spacing--20)"><?php esc_html_e( 'SEO自動運用', 'agent-neo' ); ?></h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)"},"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium);margin-bottom:var(--wp--preset--spacing--30)"><?php esc_html_e( '計測から改善までを、AIが自律的にループ。', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph {"style":{"elements":{"link":{"color":{"text":"var(--wp--preset--color--accent-aa)"}}}},"className":"an-cta an-cta--gw_seo"} -->
				<p class="an-cta an-cta--gw_seo" style=""><a href="#"><?php esc_html_e( '自動運用を見る →', 'agent-neo' ); ?></a></p>
				<!-- /wp:paragraph -->

			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

</div>
<!-- /wp:group -->
