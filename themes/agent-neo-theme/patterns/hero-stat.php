<?php
/**
 * Title: ヒーロー ⑤ 数値強調
 * Slug: agent-neo/hero-stat
 * Categories: agent-neo, agent-neo-home
 * Description: メッセージの直下に 3 つの数値を並べ、価値を一目で伝える数値強調型ヒーロー。実績紹介やサービス概要の入口向け。
 * Keywords: hero, statistics, metrics, numbers, proof
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

<!-- wp:group {"align":"full","className":"an-section an-section\u002d\u002dhero an-hero\u002d\u002dstat","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"backgroundColor":"primary","textColor":"background","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull an-section an-section--hero an-hero--stat has-background-color has-primary-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40)"><!-- wp:group {"align":"wide","style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group alignwide"><!-- wp:paragraph {"align":"center","textColor":"accent","style":{"typography":{"fontWeight":"700","textTransform":"uppercase"}},"fontSize":"small"} -->
<p class="has-text-align-center has-accent-color has-text-color has-small-font-size" style="font-weight:700;text-transform:uppercase"><?php esc_html_e( '成果を見える化する', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","level":1,"textColor":"background","style":{"typography":{"fontWeight":"800","lineHeight":"1.08"}},"fontSize":"xxx-large"} -->
<h1 class="wp-block-heading has-text-align-center has-background-color has-text-color has-xxx-large-font-size" style="font-weight:800;line-height:1.08"><?php esc_html_e( '変化は、数字の前にある。', 'agent-neo' ); ?></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","textColor":"background","fontSize":"large"} -->
<p class="has-text-align-center has-background-color has-text-color has-large-font-size"><?php esc_html_e( '日々の小さな改善を、振り返れる指標に置き換えます。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:columns {"align":"wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|50"},"blockGap":{"left":"var:preset|spacing|30","top":"var:preset|spacing|30"}}}} -->
<div class="wp-block-columns alignwide" style="margin-top:var(--wp--preset--spacing--50)"><!-- wp:column {"style":{"border":{"top":{"color":"var(--wp--preset--color--accent)","width":"1px","style":"solid"}},"spacing":{"padding":{"top":"var:preset|spacing|30"}}}} -->
<div class="wp-block-column" style="border-top-color:var(--wp--preset--color--accent);border-top-style:solid;border-top-width:1px;padding-top:var(--wp--preset--spacing--30)"><!-- wp:paragraph {"textColor":"accent","style":{"typography":{"fontWeight":"800","lineHeight":"1"}},"fontSize":"xxx-large"} -->
<p class="has-accent-color has-text-color has-xxx-large-font-size" style="font-weight:800;line-height:1">01</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"textColor":"background","style":{"typography":{"fontWeight":"700"}},"fontSize":"medium"} -->
<p class="has-background-color has-text-color has-medium-font-size" style="font-weight:700"><?php esc_html_e( '整理する', 'agent-neo' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"border":{"top":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002daccent)","width":"1px","style":"solid"}},"spacing":{"padding":{"top":"var:preset|spacing|30"}}}} -->
<div class="wp-block-column" style="border-top-color:var(--wp--preset--color--accent);border-top-style:solid;border-top-width:1px;padding-top:var(--wp--preset--spacing--30)"><!-- wp:paragraph {"textColor":"accent","style":{"typography":{"fontWeight":"800","lineHeight":"1"}},"fontSize":"xxx-large"} -->
<p class="has-accent-color has-text-color has-xxx-large-font-size" style="font-weight:800;line-height:1">02</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"textColor":"background","style":{"typography":{"fontWeight":"700"}},"fontSize":"medium"} -->
<p class="has-background-color has-text-color has-medium-font-size" style="font-weight:700"><?php esc_html_e( '伝える', 'agent-neo' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"border":{"top":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002daccent)","width":"1px","style":"solid"}},"spacing":{"padding":{"top":"var:preset|spacing|30"}}}} -->
<div class="wp-block-column" style="border-top-color:var(--wp--preset--color--accent);border-top-style:solid;border-top-width:1px;padding-top:var(--wp--preset--spacing--30)"><!-- wp:paragraph {"textColor":"accent","style":{"typography":{"fontWeight":"800","lineHeight":"1"}},"fontSize":"xxx-large"} -->
<p class="has-accent-color has-text-color has-xxx-large-font-size" style="font-weight:800;line-height:1">03</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"textColor":"background","style":{"typography":{"fontWeight":"700"}},"fontSize":"medium"} -->
<p class="has-background-color has-text-color has-medium-font-size" style="font-weight:700"><?php esc_html_e( '続ける', 'agent-neo' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
