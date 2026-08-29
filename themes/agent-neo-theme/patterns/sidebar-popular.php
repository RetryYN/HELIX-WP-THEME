<?php
/**
 * Title: サイドバー ⑥ 人気記事ランキング
 * Slug: agent-neo/sidebar-popular
 * Categories: agent-neo, agent-neo-shared
 * Description: 1 位から 3 位までの人気記事を順位付きで見せるサイドバー。新着記事とは異なる回遊導線をつくりたいメディア向け。
 * Keywords: sidebar, popular, ranking, posts
 * Viewport Width: 400
 * Block Types: core/template-part/sidebar, core/list
 * Post Types: wp_template, wp_template_part
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"className":"an-sidebar an-sidebar\u002d\u002dpopular","style":{"spacing":{"blockGap":"var:preset|spacing|40"}},"layout":{"type":"default"}} -->
<div class="wp-block-group an-sidebar an-sidebar--popular"><!-- wp:group {"className":"an-sidebar-widget","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group an-sidebar-widget"><!-- wp:heading {"style":{"typography":{"fontWeight":"700"},"border":{"bottom":{"color":"var(u002du002dwpu002du002dpresetu002du002dcoloru002du002dprimary)","width":"1px","style":"solid"}},"spacing":{"padding":{"bottom":"var:preset|spacing|10"}}},"fontSize":"medium"} -->
<h2 class="wp-block-heading has-medium-font-size" style="border-bottom-color:var(u002du002dwpu002du002dpresetu002du002dcoloru002du002dprimary);border-bottom-style:solid;border-bottom-width:1px;padding-bottom:var(--wp--preset--spacing--10);font-weight:700"><?php esc_html_e( '人気記事', 'agent-neo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:list {"ordered":true,"className":"an-sidebar-ranking","style":{"spacing":{"blockGap":"var:preset|spacing|20"}}} -->
<ol class="wp-block-list an-sidebar-ranking"><!-- wp:list-item -->
<li><a href="/blog/first-step/"><?php esc_html_e( '最初に整えるべき運用の基本', 'agent-neo' ); ?></a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="/blog/content-plan/"><?php esc_html_e( '続けられるコンテンツ計画のつくり方', 'agent-neo' ); ?></a></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><a href="/blog/review-checklist/"><?php esc_html_e( '公開前に確認したいチェック項目', 'agent-neo' ); ?></a></li>
<!-- /wp:list-item --></ol>
<!-- /wp:list --></div>
<!-- /wp:group -->

<!-- wp:search {"label":"<?php echo esc_attr__( 'サイト内検索', 'agent-neo' ); ?>","showLabel":false,"placeholder":"<?php echo esc_attr__( '記事を探す', 'agent-neo' ); ?>","buttonText":"<?php echo esc_attr__( '検索', 'agent-neo' ); ?>","buttonUseIcon":true,"fontSize":"small"} /--></div>
<!-- /wp:group -->
