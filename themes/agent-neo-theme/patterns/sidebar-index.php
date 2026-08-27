<?php
/**
 * Title: サイドバー ③ 索引（検索 + カテゴリー + アーカイブ、装飾なし）
 * Slug: agent-neo/sidebar-index
 * Categories: agent-neo, agent-neo-shared
 * Description: 検索・カテゴリー・月別アーカイブ・新着タイトルだけを罫線で区切った索引型。ドキュメント・技術ブログ向け。背景色なし。
 * Keywords: sidebar, index, archive, docs
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

<!-- wp:group {"className":"an-sidebar an-sidebar--index","style":{"spacing":{"blockGap":"var:preset|spacing|30"},"border":{"left":{"color":"var(--wp--preset--color--secondary)","width":"1px","style":"solid"}},"spacing":{"padding":{"left":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|30"}},"layout":{"type":"default"}} -->
<div class="wp-block-group an-sidebar an-sidebar--index" style="border-left:1px solid var(--wp--preset--color--secondary);padding-left:var(--wp--preset--spacing--30)">

	<!-- wp:search {"label":"検索","showLabel":false,"placeholder":"ドキュメントを検索","buttonText":"検索","buttonPosition":"button-inside","buttonUseIcon":true,"fontSize":"small"} /-->

	<!-- wp:group {"className":"an-sidebar-widget","style":{"spacing":{"blockGap":"var:preset|spacing|10"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group an-sidebar-widget">
		<!-- wp:heading {"level":2,"fontSize":"small","textColor":"muted","style":{"typography":{"fontWeight":"700"}}} -->
		<h2 class="wp-block-heading has-muted-color has-text-color has-small-font-size" style="font-weight:700"><?php esc_html_e( 'カテゴリー', 'agent-neo' ); ?></h2>
		<!-- /wp:heading -->
		<!-- wp:categories {"showHierarchy":true,"showPostCounts":true,"fontSize":"small","className":"an-sidebar-categories"} /-->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"an-sidebar-widget","style":{"spacing":{"blockGap":"var:preset|spacing|10"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group an-sidebar-widget">
		<!-- wp:heading {"level":2,"fontSize":"small","textColor":"muted","style":{"typography":{"fontWeight":"700"}}} -->
		<h2 class="wp-block-heading has-muted-color has-text-color has-small-font-size" style="font-weight:700"><?php esc_html_e( '新着', 'agent-neo' ); ?></h2>
		<!-- /wp:heading -->
		<!-- wp:latest-posts {"postsToShow":6,"displayPostDate":true,"fontSize":"small","className":"an-sidebar-latest-plain"} /-->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"className":"an-sidebar-widget","style":{"spacing":{"blockGap":"var:preset|spacing|10"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group an-sidebar-widget">
		<!-- wp:heading {"level":2,"fontSize":"small","textColor":"muted","style":{"typography":{"fontWeight":"700"}}} -->
		<h2 class="wp-block-heading has-muted-color has-text-color has-small-font-size" style="font-weight:700"><?php esc_html_e( 'アーカイブ', 'agent-neo' ); ?></h2>
		<!-- /wp:heading -->
		<!-- wp:archives {"displayAsDropdown":false,"showPostCounts":true,"fontSize":"small","className":"an-sidebar-archives"} /-->
	</div>
	<!-- /wp:group -->

</div>
<!-- /wp:group -->
