<?php
/**
 * Title: セクション ⑪ 3 ステップ
 * Slug: agent-neo/section-steps
 * Categories: agent-neo
 * Description: 01、02、03 の番号で進め方を示す 3 ステップセクション。サービスの利用手順や導入フローを簡潔に伝える。
 * Keywords: section, steps, process, flow, numbered
 * Viewport Width: 1280
 * Block Types: core/group, core/columns
 * Post Types: wp_template, page
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"align":"full","className":"an-section an-section\u002d\u002dsteps","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|40"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull an-section an-section--steps" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40)"><!-- wp:group {"align":"wide","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide"><!-- wp:paragraph {"textColor":"accent-aa","style":{"typography":{"fontWeight":"700","textTransform":"uppercase"}},"fontSize":"small"} -->
<p class="has-accent-aa-color has-text-color has-small-font-size" style="font-weight:700;text-transform:uppercase"><?php esc_html_e( '進め方', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"style":{"typography":{"fontWeight":"800"}},"fontSize":"xx-large"} -->
<h2 class="wp-block-heading has-xx-large-font-size" style="font-weight:800"><?php esc_html_e( '3 つの動きで、迷いを減らす', 'agent-neo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:columns {"style":{"spacing":{"margin":{"top":"var:preset|spacing|40"},"blockGap":{"left":"var:preset|spacing|40","top":"var:preset|spacing|30"}}}} -->
<div class="wp-block-columns" style="margin-top:var(--wp--preset--spacing--40)"><!-- wp:column {"style":{"border":{"top":{"color":"var(u002du002dwpu002du002dpresetu002du002dcoloru002du002daccent-aa)","width":"1px","style":"solid"}},"spacing":{"padding":{"top":"var:preset|spacing|30"}}}} -->
<div class="wp-block-column" style="border-top-color:var(u002du002dwpu002du002dpresetu002du002dcoloru002du002daccent-aa);border-top-style:solid;border-top-width:1px;padding-top:var(--wp--preset--spacing--30)"><!-- wp:paragraph {"textColor":"accent-aa","style":{"typography":{"fontWeight":"800","lineHeight":"1"}},"fontSize":"xxx-large"} -->
<p class="has-accent-aa-color has-text-color has-xxx-large-font-size" style="font-weight:800;line-height:1">01</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"style":{"typography":{"fontWeight":"700"}},"fontSize":"large"} -->
<h3 class="wp-block-heading has-large-font-size" style="font-weight:700"><?php esc_html_e( '聞く', 'agent-neo' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"textColor":"muted","fontSize":"small"} -->
<p class="has-muted-color has-text-color has-small-font-size"><?php esc_html_e( '目的と現状を短く共有します。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"border":{"top":{"color":"var(u002du002dwpu002du002dpresetu002du002dcoloru002du002daccent-aa)","width":"1px","style":"solid"}},"spacing":{"padding":{"top":"var:preset|spacing|30"}}}} -->
<div class="wp-block-column" style="border-top-color:var(u002du002dwpu002du002dpresetu002du002dcoloru002du002daccent-aa);border-top-style:solid;border-top-width:1px;padding-top:var(--wp--preset--spacing--30)"><!-- wp:paragraph {"textColor":"accent-aa","style":{"typography":{"fontWeight":"800","lineHeight":"1"}},"fontSize":"xxx-large"} -->
<p class="has-accent-aa-color has-text-color has-xxx-large-font-size" style="font-weight:800;line-height:1">02</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"style":{"typography":{"fontWeight":"700"}},"fontSize":"large"} -->
<h3 class="wp-block-heading has-large-font-size" style="font-weight:700"><?php esc_html_e( '整える', 'agent-neo' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"textColor":"muted","fontSize":"small"} -->
<p class="has-muted-color has-text-color has-small-font-size"><?php esc_html_e( '優先順位と次の一手を決めます。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"border":{"top":{"color":"var(u002du002dwpu002du002dpresetu002du002dcoloru002du002daccent-aa)","width":"1px","style":"solid"}},"spacing":{"padding":{"top":"var:preset|spacing|30"}}}} -->
<div class="wp-block-column" style="border-top-color:var(u002du002dwpu002du002dpresetu002du002dcoloru002du002daccent-aa);border-top-style:solid;border-top-width:1px;padding-top:var(--wp--preset--spacing--30)"><!-- wp:paragraph {"textColor":"accent-aa","style":{"typography":{"fontWeight":"800","lineHeight":"1"}},"fontSize":"xxx-large"} -->
<p class="has-accent-aa-color has-text-color has-xxx-large-font-size" style="font-weight:800;line-height:1">03</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"style":{"typography":{"fontWeight":"700"}},"fontSize":"large"} -->
<h3 class="wp-block-heading has-large-font-size" style="font-weight:700"><?php esc_html_e( '続ける', 'agent-neo' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"textColor":"muted","fontSize":"small"} -->
<p class="has-muted-color has-text-color has-small-font-size"><?php esc_html_e( '振り返り、次の改善につなげます。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
