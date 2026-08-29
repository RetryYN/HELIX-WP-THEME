<?php
/**
 * Title: サイドバー ⑦ タグクラウド
 * Slug: agent-neo/sidebar-tag-cloud
 * Categories: agent-neo, agent-neo-shared
 * Description: タグクラウドを中心にカテゴリーと検索を添えた探索型サイドバー。テーマ横断で記事を見つけてもらいたいメディア向け。
 * Keywords: sidebar, tags, tag-cloud, search, archive
 * Viewport Width: 400
 * Block Types: core/template-part/sidebar, core/tag-cloud
 * Post Types: wp_template, wp_template_part
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"className":"an-sidebar an-sidebar\u002d\u002dtag-cloud","style":{"spacing":{"blockGap":"var:preset|spacing|40"}},"layout":{"type":"default"}} -->
<div class="wp-block-group an-sidebar an-sidebar--tag-cloud"><!-- wp:group {"className":"an-sidebar-widget","backgroundColor":"secondary","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|20"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group an-sidebar-widget has-secondary-background-color has-background" style="padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)"><!-- wp:heading {"style":{"typography":{"fontWeight":"700"}},"fontSize":"medium"} -->
<h2 class="wp-block-heading has-medium-font-size" style="font-weight:700"><?php esc_html_e( 'テーマから探す', 'agent-neo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:tag-cloud {"numberOfTags":12,"showTagCounts":true,"taxonomy":"post_tag","className":"an-sidebar-tag-cloud","fontSize":"small"} /--></div>
<!-- /wp:group -->

<!-- wp:group {"className":"an-sidebar-widget","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group an-sidebar-widget"><!-- wp:heading {"textColor":"muted","style":{"typography":{"fontWeight":"700","textTransform":"uppercase"}},"fontSize":"small"} -->
<h2 class="wp-block-heading has-muted-color has-text-color has-small-font-size" style="font-weight:700;text-transform:uppercase"><?php esc_html_e( 'カテゴリー', 'agent-neo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:categories {"showPostCounts":true,"fontSize":"small"} /--></div>
<!-- /wp:group -->

<!-- wp:search {"label":"<?php echo esc_attr__( 'サイト内検索', 'agent-neo' ); ?>","showLabel":false,"placeholder":"<?php echo esc_attr__( 'キーワードを入力', 'agent-neo' ); ?>","buttonText":"<?php echo esc_attr__( '検索', 'agent-neo' ); ?>","buttonUseIcon":true,"fontSize":"small"} /--></div>
<!-- /wp:group -->
