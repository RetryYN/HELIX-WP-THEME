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

<!-- wp:group {"className":"an-site-footer-inner an-footer\u002d\u002dmega","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|50"}},"backgroundColor":"secondary","textColor":"foreground","layout":{"type":"constrained"}} -->
<div class="wp-block-group an-site-footer-inner an-footer--mega has-foreground-color has-secondary-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50","top":"var:preset|spacing|40"}}}} -->
<div class="wp-block-columns alignwide">
<!-- wp:column {"width":"34%"} -->
<div class="wp-block-column" style="flex-basis:34%">
<!-- wp:site-title {"level":0,"style":{"typography":{"fontWeight":"700"}},"textColor":"foreground","fontSize":"large"} /-->

<!-- wp:site-tagline {"textColor":"muted","fontSize":"small"} /-->

<!-- wp:paragraph {"textColor":"muted","fontSize":"small"} -->
<p class="has-muted-color has-text-color has-small-font-size"><?php esc_html_e( '記事生成から SEO・配信までを AI が担う WordPress テーマ。人は方針と最終判断だけに集中できます。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:pattern {"slug":"agent-neo/share-buttons"} /-->
</div>
<!-- /wp:column -->

<!-- wp:column {"width":"22%","className":"an-footer-col"} -->
<div class="wp-block-column an-footer-col" style="flex-basis:22%">
<!-- wp:heading {"className":"an-footer-col-heading","style":{"typography":{"fontWeight":"700","textTransform":"uppercase"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}},"textColor":"muted","fontSize":"small"} -->
<h2 class="wp-block-heading an-footer-col-heading has-muted-color has-text-color has-small-font-size" style="margin-bottom:var(--wp--preset--spacing--20);font-weight:700;text-transform:uppercase"><?php esc_html_e( 'サービス', 'agent-neo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:navigation {"textColor":"foreground","overlayMenu":"never","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"fontSize":"small","layout":{"type":"flex","orientation":"vertical","justifyContent":"left"},"ariaLabel":"サービス"} -->
<!-- wp:navigation-link {"label":"AI 運用の仕組み","url":"/lp/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"料金","url":"/lp/#pricing","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"導入事例","url":"/category/case/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"よくある質問","url":"/lp/#faq","kind":"custom","isTopLevelLink":true} /-->
<!-- /wp:navigation -->
</div>
<!-- /wp:column -->

<!-- wp:column {"width":"22%","className":"an-footer-col"} -->
<div class="wp-block-column an-footer-col" style="flex-basis:22%">
<!-- wp:heading {"className":"an-footer-col-heading","style":{"typography":{"fontWeight":"700","textTransform":"uppercase"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}},"textColor":"muted","fontSize":"small"} -->
<h2 class="wp-block-heading an-footer-col-heading has-muted-color has-text-color has-small-font-size" style="margin-bottom:var(--wp--preset--spacing--20);font-weight:700;text-transform:uppercase"><?php esc_html_e( 'カテゴリー', 'agent-neo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:categories {"showPostCounts":true,"className":"an-footer-categories","fontSize":"small"} /-->
</div>
<!-- /wp:column -->

<!-- wp:column {"width":"22%","className":"an-footer-col"} -->
<div class="wp-block-column an-footer-col" style="flex-basis:22%">
<!-- wp:heading {"className":"an-footer-col-heading","style":{"typography":{"fontWeight":"700","textTransform":"uppercase"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}},"textColor":"muted","fontSize":"small"} -->
<h2 class="wp-block-heading an-footer-col-heading has-muted-color has-text-color has-small-font-size" style="margin-bottom:var(--wp--preset--spacing--20);font-weight:700;text-transform:uppercase"><?php esc_html_e( '最新記事', 'agent-neo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:latest-posts {"postsToShow":4,"displayPostDate":true,"className":"an-footer-latest","fontSize":"small"} /-->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->

<!-- wp:group {"align":"wide","style":{"border":{"top":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dmuted)","width":"1px","style":"solid"}},"spacing":{"padding":{"top":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|30"}},"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
<div class="wp-block-group alignwide" style="border-top-color:var(--wp--preset--color--muted);border-top-style:solid;border-top-width:1px;padding-top:var(--wp--preset--spacing--30)">
<!-- wp:pattern {"slug":"agent-neo/footer-credit"} /-->

<!-- wp:navigation {"textColor":"muted","overlayMenu":"never","style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"fontSize":"small","layout":{"type":"flex","justifyContent":"right"},"ariaLabel":"規約"} -->
<!-- wp:navigation-link {"label":"プライバシーポリシー","url":"/privacy/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"運営者情報","url":"/owner/","kind":"custom","isTopLevelLink":true} /-->
<!-- /wp:navigation -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
