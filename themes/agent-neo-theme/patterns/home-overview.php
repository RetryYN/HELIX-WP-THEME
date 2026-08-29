<?php
/**
 * Title: ホーム ③ Product Overview
 * Slug: agent-neo/home-overview
 * Categories: featured, agent-neo-home
 * Description: home-blueprint 第3セクション。AGENT NEO の主要機能を3列で紹介。
 * Keywords: home, overview, features, product
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

<!-- wp:group {"anchor":"home_overview","className":"an-section an-section--home_overview","backgroundColor":"secondary","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained","wideSize":"1200px"}} -->
<div class="wp-block-group an-section an-section--home_overview has-secondary-background-color has-background" id="home_overview" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40)">

	<!-- wp:heading {"level":2,"textAlign":"center","style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--x-large)"},"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
	<h2 class="wp-block-heading has-text-align-center" style="font-weight:700;font-size:var(--wp--preset--font-size--x-large);margin-bottom:var(--wp--preset--spacing--40)"><?php esc_html_e( 'AGENT NEO でできること', 'agent-neo' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":"var:preset|spacing|30"}}} -->
	<div class="wp-block-columns alignwide">

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"style":{"border":{"radius":"8px"},"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}},"color":{"background":"var(--wp--preset--color--background)"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"left"}} -->
			<div class="wp-block-group has-background" style="border-radius:8px;background-color:var(--wp--preset--color--background);padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)">

				<!-- wp:heading {"level":3,"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--large)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
				<h3 class="wp-block-heading" style="font-weight:700;font-size:var(--wp--preset--font-size--large);margin-bottom:var(--wp--preset--spacing--20)"><?php esc_html_e( 'コンテンツ生成', 'agent-neo' ); ?></h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)"}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium)"><?php esc_html_e( 'ブランドの文脈を踏まえ、記事・固定ページの下書きをAIが生成。', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->

			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"style":{"border":{"radius":"8px"},"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}},"color":{"background":"var(--wp--preset--color--background)"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"left"}} -->
			<div class="wp-block-group has-background" style="border-radius:8px;background-color:var(--wp--preset--color--background);padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)">

				<!-- wp:heading {"level":3,"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--large)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
				<h3 class="wp-block-heading" style="font-weight:700;font-size:var(--wp--preset--font-size--large);margin-bottom:var(--wp--preset--spacing--20)"><?php esc_html_e( 'SEO最適化', 'agent-neo' ); ?></h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)"}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium)"><?php esc_html_e( '構造化データ・内部リンク・リライトを自動で組み立て。', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->

			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column -->
		<div class="wp-block-column">
			<!-- wp:group {"style":{"border":{"radius":"8px"},"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}},"color":{"background":"var(--wp--preset--color--background)"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"left"}} -->
			<div class="wp-block-group has-background" style="border-radius:8px;background-color:var(--wp--preset--color--background);padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)">

				<!-- wp:heading {"level":3,"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--large)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
				<h3 class="wp-block-heading" style="font-weight:700;font-size:var(--wp--preset--font-size--large);margin-bottom:var(--wp--preset--spacing--20)"><?php esc_html_e( '自動配信', 'agent-neo' ); ?></h3>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)"}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium)"><?php esc_html_e( '公開・更新・計測まで、初期設定後は止まらず運用。', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->

			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

</div>
<!-- /wp:group -->
