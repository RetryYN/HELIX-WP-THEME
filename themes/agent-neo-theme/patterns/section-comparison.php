<?php
/**
 * Title: セクション ⑬ 比較表
 * Slug: agent-neo/section-comparison
 * Categories: agent-neo
 * Description: 現状と改善後の違いを表で整理する比較セクション。抽象的な価値を具体的な観点へ置き換えたい LP 向け。
 * Keywords: section, comparison, table, before-after, benefits
 * Viewport Width: 1280
 * Block Types: core/group, core/table
 * Post Types: wp_template, page
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"align":"full","className":"an-section an-section\u002d\u002dcomparison","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|40"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull an-section an-section--comparison" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40)"><!-- wp:group {"align":"wide","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide"><!-- wp:paragraph {"textColor":"accent-aa","style":{"typography":{"fontWeight":"700","textTransform":"uppercase"}},"fontSize":"small"} -->
<p class="has-accent-aa-color has-text-color has-small-font-size" style="font-weight:700;text-transform:uppercase"><?php esc_html_e( '違いを比べる', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"style":{"typography":{"fontWeight":"800"}},"fontSize":"xx-large"} -->
<h2 class="wp-block-heading has-xx-large-font-size" style="font-weight:800"><?php esc_html_e( '変えると、何が変わる？', 'agent-neo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:table {"hasFixedLayout":false,"className":"an-comparison-table","style":{"spacing":{"margin":{"top":"var:preset|spacing|40"}}}} -->
<figure class="wp-block-table an-comparison-table" style="margin-top:var(--wp--preset--spacing--40)"><table><thead><tr><th><?php esc_html_e( '観点', 'agent-neo' ); ?></th><th><?php esc_html_e( 'これまで', 'agent-neo' ); ?></th><th><?php esc_html_e( 'これから', 'agent-neo' ); ?></th></tr></thead><tbody><tr><td><?php esc_html_e( '判断', 'agent-neo' ); ?></td><td><?php esc_html_e( '担当者の経験に頼る', 'agent-neo' ); ?></td><td><?php esc_html_e( '基準を共有する', 'agent-neo' ); ?></td></tr><tr><td><?php esc_html_e( '更新', 'agent-neo' ); ?></td><td><?php esc_html_e( '思いついたときに行う', 'agent-neo' ); ?></td><td><?php esc_html_e( '予定に組み込む', 'agent-neo' ); ?></td></tr><tr><td><?php esc_html_e( '振り返り', 'agent-neo' ); ?></td><td><?php esc_html_e( '感想で終わる', 'agent-neo' ); ?></td><td><?php esc_html_e( '次の一手を決める', 'agent-neo' ); ?></td></tr></tbody></table></figure>
<!-- /wp:table --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
