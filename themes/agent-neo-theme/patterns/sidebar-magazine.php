<?php
/**
 * Title: サイドバー ① マガジン（プロフィール + 新着 + カテゴリー）
 * Slug: agent-neo/sidebar-magazine
 * Categories: agent-neo, agent-neo-shared
 * Description: 運営者プロフィール、アイキャッチ付き新着記事、カテゴリー、検索の順。回遊と信頼を両立するメディア向け。
 * Keywords: sidebar, magazine, profile, latest
 * Viewport Width: 400
 * Block Types: core/template-part/sidebar
 * Post Types: wp_template, wp_template_part
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"className":"an-sidebar an-sidebar\u002d\u002dmagazine","style":{"spacing":{"blockGap":"var:preset|spacing|40"}},"layout":{"type":"default"}} -->
<div class="wp-block-group an-sidebar an-sidebar--magazine">
<!-- wp:group {"className":"an-sidebar-widget an-sidebar-profile","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|20"}},"backgroundColor":"secondary","layout":{"type":"constrained"}} -->
<div class="wp-block-group an-sidebar-widget an-sidebar-profile has-secondary-background-color has-background" style="padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)">
<!-- wp:avatar {"size":72,"align":"center","style":{"border":{"radius":"var:preset|spacing|60"}}} /-->

<!-- wp:heading {"style":{"typography":{"fontWeight":"700","textAlign":"center"}},"fontSize":"medium"} -->
<h2 class="wp-block-heading has-text-align-center has-medium-font-size" style="font-weight:700"><?php esc_html_e( '運営者', 'agent-neo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"typography":{"textAlign":"center"}},"textColor":"muted","fontSize":"small"} -->
<p class="has-text-align-center has-muted-color has-text-color has-small-font-size"><?php esc_html_e( 'AI 運用と WordPress の実務を、失敗談込みで書いています。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:pattern {"slug":"agent-neo/share-buttons"} /-->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"an-sidebar-widget","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group an-sidebar-widget">
<!-- wp:heading {"style":{"typography":{"fontWeight":"700","textTransform":"uppercase"},"border":{"bottom":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dprimary)","width":"1px","style":"solid"}},"spacing":{"padding":{"bottom":"var:preset|spacing|10"}}},"textColor":"muted","fontSize":"small"} -->
<h2 class="wp-block-heading has-muted-color has-text-color has-small-font-size" style="border-bottom-color:var(--wp--preset--color--primary);border-bottom-style:solid;border-bottom-width:1px;padding-bottom:var(--wp--preset--spacing--10);font-weight:700;text-transform:uppercase"><?php esc_html_e( '新着記事', 'agent-neo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:latest-posts {"postsToShow":4,"displayPostDate":true,"displayFeaturedImage":true,"featuredImageAlign":"left","featuredImageSizeWidth":72,"featuredImageSizeHeight":72,"addLinkToFeaturedImage":true,"className":"an-sidebar-latest","fontSize":"small"} /-->
</div>
<!-- /wp:group -->

<!-- wp:group {"className":"an-sidebar-widget","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group an-sidebar-widget">
<!-- wp:heading {"style":{"typography":{"fontWeight":"700","textTransform":"uppercase"},"border":{"bottom":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dprimary)","width":"1px","style":"solid"}},"spacing":{"padding":{"bottom":"var:preset|spacing|10"}}},"textColor":"muted","fontSize":"small"} -->
<h2 class="wp-block-heading has-muted-color has-text-color has-small-font-size" style="border-bottom-color:var(--wp--preset--color--primary);border-bottom-style:solid;border-bottom-width:1px;padding-bottom:var(--wp--preset--spacing--10);font-weight:700;text-transform:uppercase"><?php esc_html_e( 'カテゴリー', 'agent-neo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:categories {"showPostCounts":true,"className":"an-sidebar-categories","fontSize":"small"} /-->
</div>
<!-- /wp:group -->

<!-- wp:search {"label":"検索","showLabel":false,"placeholder":"記事を探す","buttonText":"検索","buttonUseIcon":true,"fontSize":"small"} /-->
</div>
<!-- /wp:group -->
