<?php
/**
 * Title: フッター ④ メガ（4 列 + 最新記事）
 * Slug: agent-neo/footer-mega
 * Categories: agent-neo, agent-neo-shared
 * Description: ブランド / サービス / 記事カテゴリー / 最新記事 の 4 列を持つ明色フッター。回遊を重視するメディア・情報サイト向け。
 * Keywords: footer, mega, sitemap, latest
 * Viewport Width: 1280
 * Block Types: core/template-part/footer
 * Post Types: wp_template, wp_template_part
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"className":"an-site-footer-inner an-footer--mega","backgroundColor":"secondary","textColor":"foreground","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|50"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group an-site-footer-inner an-footer--mega has-foreground-color has-secondary-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">

	<!-- wp:columns {"align":"wide","isStackedOnMobile":true,"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50","top":"var:preset|spacing|40"}}}} -->
	<div class="wp-block-columns alignwide">

		<!-- wp:column {"width":"34%"} -->
		<div class="wp-block-column" style="flex-basis:34%">
			<!-- wp:site-title {"level":0,"isLink":true,"fontSize":"large","style":{"typography":{"fontWeight":"700"}},"textColor":"foreground"} /-->
			<!-- wp:site-tagline {"fontSize":"small","textColor":"muted"} /-->
			<!-- wp:paragraph {"fontSize":"small","textColor":"muted"} -->
			<p class="has-muted-color has-text-color has-small-font-size"><?php esc_html_e( '記事生成から SEO・配信までを AI が担う WordPress テーマ。人は方針と最終判断だけに集中できます。', 'agent-neo' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:pattern {"slug":"agent-neo/share-buttons"} /-->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"22%"} -->
		<div class="wp-block-column an-footer-col" style="flex-basis:22%">
			<!-- wp:heading {"level":2,"className":"an-footer-col-heading","fontSize":"small","textColor":"muted","style":{"typography":{"fontWeight":"700","textTransform":"uppercase"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
			<h2 class="wp-block-heading an-footer-col-heading has-muted-color has-text-color has-small-font-size" style="margin-bottom:var(--wp--preset--spacing--20);font-weight:700;text-transform:uppercase"><?php esc_html_e( 'サービス', 'agent-neo' ); ?></h2>
			<!-- /wp:heading -->
			<!-- wp:navigation {"ariaLabel":"サービス","overlayMenu":"never","layout":{"type":"flex","orientation":"vertical","justifyContent":"left"},"fontSize":"small","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"textColor":"foreground"} -->
				<!-- wp:navigation-link {"label":"AI 運用の仕組み","url":"/lp/","kind":"custom","isTopLevelLink":true} /-->
				<!-- wp:navigation-link {"label":"料金","url":"/lp/#pricing","kind":"custom","isTopLevelLink":true} /-->
				<!-- wp:navigation-link {"label":"導入事例","url":"/category/case/","kind":"custom","isTopLevelLink":true} /-->
				<!-- wp:navigation-link {"label":"よくある質問","url":"/lp/#faq","kind":"custom","isTopLevelLink":true} /-->
			<!-- /wp:navigation -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"22%"} -->
		<div class="wp-block-column an-footer-col" style="flex-basis:22%">
			<!-- wp:heading {"level":2,"className":"an-footer-col-heading","fontSize":"small","textColor":"muted","style":{"typography":{"fontWeight":"700","textTransform":"uppercase"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
			<h2 class="wp-block-heading an-footer-col-heading has-muted-color has-text-color has-small-font-size" style="margin-bottom:var(--wp--preset--spacing--20);font-weight:700;text-transform:uppercase"><?php esc_html_e( 'カテゴリー', 'agent-neo' ); ?></h2>
			<!-- /wp:heading -->
			<!-- wp:categories {"showPostCounts":true,"fontSize":"small","className":"an-footer-categories"} /-->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"width":"22%"} -->
		<div class="wp-block-column an-footer-col" style="flex-basis:22%">
			<!-- wp:heading {"level":2,"className":"an-footer-col-heading","fontSize":"small","textColor":"muted","style":{"typography":{"fontWeight":"700","textTransform":"uppercase"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
			<h2 class="wp-block-heading an-footer-col-heading has-muted-color has-text-color has-small-font-size" style="margin-bottom:var(--wp--preset--spacing--20);font-weight:700;text-transform:uppercase"><?php esc_html_e( '最新記事', 'agent-neo' ); ?></h2>
			<!-- /wp:heading -->
			<!-- wp:latest-posts {"postsToShow":4,"displayPostDate":true,"fontSize":"small","className":"an-footer-latest"} /-->
		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

	<!-- wp:group {"align":"wide","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"},"style":{"border":{"top":{"color":"var(--wp--preset--color--muted)","width":"1px","style":"solid"}},"spacing":{"padding":{"top":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|30"}}} -->
	<div class="wp-block-group alignwide" style="border-top:1px solid var(--wp--preset--color--muted);padding-top:var(--wp--preset--spacing--30)">
		<!-- wp:pattern {"slug":"agent-neo/footer-credit"} /-->
		<!-- wp:navigation {"ariaLabel":"規約","overlayMenu":"never","layout":{"type":"flex","justifyContent":"right"},"fontSize":"small","style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"textColor":"muted"} -->
			<!-- wp:navigation-link {"label":"プライバシーポリシー","url":"/privacy/","kind":"custom","isTopLevelLink":true} /-->
			<!-- wp:navigation-link {"label":"運営者情報","url":"/owner/","kind":"custom","isTopLevelLink":true} /-->
		<!-- /wp:navigation -->
	</div>
	<!-- /wp:group -->

</div>
<!-- /wp:group -->
