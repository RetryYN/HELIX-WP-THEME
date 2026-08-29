<?php
/**
 * Title: ホーム ⑥ Trust
 * Slug: agent-neo/home-trust
 * Categories: featured, agent-neo-home
 * Description: home-blueprint 第6セクション。AGENT NEO が選ばれる理由を数値4列で表示。
 * Keywords: home, trust, stats, numbers, reasons
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

<!-- wp:group {"anchor":"home_trust","className":"an-section an-section--home_trust","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","wideSize":"1200px"}} -->
<div class="wp-block-group an-section an-section--home_trust" id="home_trust" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40)">

	<!-- wp:heading {"level":2,"textAlign":"center","style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--x-large)"},"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
	<h2 class="wp-block-heading has-text-align-center" style="font-weight:700;font-size:var(--wp--preset--font-size--x-large);margin-bottom:var(--wp--preset--spacing--40)"><?php esc_html_e( 'AGENT NEO が選ばれる理由', 'agent-neo' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":"var:preset|spacing|30"}}} -->
	<div class="wp-block-columns alignwide">

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|20","right":"var:preset|spacing|20"}}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
			<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--20)">

				<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"var(--wp--preset--font-size--xxx-large)","fontWeight":"800"},"color":{"text":"var(--wp--preset--color--accent-aa)"},"spacing":{"margin":{"bottom":"var:preset|spacing|10"}}}} -->
				<p class="has-text-align-center" style="font-size:var(--wp--preset--font-size--xxx-large);font-weight:800;color:var(--wp--preset--color--accent-aa);margin-bottom:var(--wp--preset--spacing--10)"><?php esc_html_e( '24領域', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph {"align":"center","fontSize":"small","style":{"color":{"text":"var(--wp--preset--color--muted)"}}} -->
				<p class="has-text-align-center has-small-font-size" style="color:var(--wp--preset--color--muted)"><?php esc_html_e( '自動化対象の業務領域', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->

			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|20","right":"var:preset|spacing|20"}}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
			<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--20)">

				<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"var(--wp--preset--font-size--xxx-large)","fontWeight":"800"},"color":{"text":"var(--wp--preset--color--accent-aa)"},"spacing":{"margin":{"bottom":"var:preset|spacing|10"}}}} -->
				<p class="has-text-align-center" style="font-size:var(--wp--preset--font-size--xxx-large);font-weight:800;color:var(--wp--preset--color--accent-aa);margin-bottom:var(--wp--preset--spacing--10)"><?php esc_html_e( '3モデル', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph {"align":"center","fontSize":"small","style":{"color":{"text":"var(--wp--preset--color--muted)"}}} -->
				<p class="has-text-align-center has-small-font-size" style="color:var(--wp--preset--color--muted)"><?php esc_html_e( 'マルチLLM運用（Claude / GPT / Grok）', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->

			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|20","right":"var:preset|spacing|20"}}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
			<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--20)">

				<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"var(--wp--preset--font-size--xxx-large)","fontWeight":"800"},"color":{"text":"var(--wp--preset--color--accent-aa)"},"spacing":{"margin":{"bottom":"var:preset|spacing|10"}}}} -->
				<p class="has-text-align-center" style="font-size:var(--wp--preset--font-size--xxx-large);font-weight:800;color:var(--wp--preset--color--accent-aa);margin-bottom:var(--wp--preset--spacing--10)"><?php esc_html_e( 'SEO標準', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph {"align":"center","fontSize":"small","style":{"color":{"text":"var(--wp--preset--color--muted)"}}} -->
				<p class="has-text-align-center has-small-font-size" style="color:var(--wp--preset--color--muted)"><?php esc_html_e( 'JSON-LD・OGP を標準出力', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->

			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|20","right":"var:preset|spacing|20"}}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
			<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--20)">

				<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"var(--wp--preset--font-size--xxx-large)","fontWeight":"800"},"color":{"text":"var(--wp--preset--color--accent-aa)"},"spacing":{"margin":{"bottom":"var:preset|spacing|10"}}}} -->
				<p class="has-text-align-center" style="font-size:var(--wp--preset--font-size--xxx-large);font-weight:800;color:var(--wp--preset--color--accent-aa);margin-bottom:var(--wp--preset--spacing--10)"><?php esc_html_e( '100%自動', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph {"align":"center","fontSize":"small","style":{"color":{"text":"var(--wp--preset--color--muted)"}}} -->
				<p class="has-text-align-center has-small-font-size" style="color:var(--wp--preset--color--muted)"><?php esc_html_e( '初期設定後の運用', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->

			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

	<!-- wp:paragraph {"align":"center","fontSize":"medium","style":{"color":{"text":"var(--wp--preset--color--muted)"},"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
	<p class="has-text-align-center has-medium-font-size" style="color:var(--wp--preset--color--muted);margin-top:var(--wp--preset--spacing--40)"><?php esc_html_e( 'AIロジックは Automation SEO 側に集約し、テーマはレンダリングと計測に専念します。', 'agent-neo' ); ?></p>
	<!-- /wp:paragraph -->

</div>
<!-- /wp:group -->
