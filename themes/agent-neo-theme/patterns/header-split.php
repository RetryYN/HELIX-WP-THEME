<?php
/**
 * Title: ヘッダー ⑦ スプリット（ナビ左右にロゴ中央）
 * Slug: agent-neo/header-split
 * Categories: agent-neo, agent-neo-shared
 * Description: ナビゲーションを左右 2 つに分け、その中央にロゴを置く対称レイアウト。ブランド・ファッション・食のサイト向け。細い上罫線で締める。
 * Keywords: header, split, symmetric, brand
 * Viewport Width: 1280
 * Block Types: core/template-part/header
 * Post Types: wp_template, wp_template_part
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"className":"an-site-header-inner an-header\u002d\u002dsplit","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"border":{"top":{"color":"var(u002du002dwpu002du002dpresetu002du002dcoloru002du002dprimary)","width":"1px","style":"solid"},"bottom":{"color":"var(u002du002dwpu002du002dpresetu002du002dcoloru002du002dsecondary)","width":"1px","style":"solid"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group an-site-header-inner an-header--split" style="border-top-color:var(u002du002dwpu002du002dpresetu002du002dcoloru002du002dprimary);border-top-style:solid;border-top-width:1px;border-bottom-color:var(u002du002dwpu002du002dpresetu002du002dcoloru002du002dsecondary);border-bottom-style:solid;border-bottom-width:1px;padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--40)"><!-- wp:columns {"verticalAlignment":"center","isStackedOnMobile":false,"align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|30"}}}} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center is-not-stacked-on-mobile"><!-- wp:column {"verticalAlignment":"center","width":"35%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:35%"><!-- wp:navigation {"textColor":"foreground","overlayMenu":"never","style":{"spacing":{"blockGap":"var:preset|spacing|40"},"typography":{"fontWeight":"600","textTransform":"uppercase"}},"fontSize":"small","layout":{"type":"flex","justifyContent":"left","flexWrap":"nowrap"},"ariaLabel":"ナビゲーション 左"} -->
<!-- wp:navigation-link {"label":"記事","url":"/blog/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"導入事例","url":"/category/case/","kind":"custom","isTopLevelLink":true} /-->
<!-- /wp:navigation --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"30%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:30%"><!-- wp:site-title {"level":0,"style":{"typography":{"fontWeight":"700","textTransform":"uppercase","textAlign":"center"}},"textColor":"foreground","fontSize":"medium"} /--></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"35%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:35%"><!-- wp:navigation {"textColor":"foreground","style":{"spacing":{"blockGap":"var:preset|spacing|40"},"typography":{"fontWeight":"600","textTransform":"uppercase"}},"fontSize":"small","layout":{"type":"flex","justifyContent":"right","flexWrap":"nowrap"},"ariaLabel":"ナビゲーション 右"} -->
<!-- wp:navigation-link {"label":"サービス","url":"/lp/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"お問い合わせ","url":"/contact/","kind":"custom","isTopLevelLink":true} /-->
<!-- /wp:navigation --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
