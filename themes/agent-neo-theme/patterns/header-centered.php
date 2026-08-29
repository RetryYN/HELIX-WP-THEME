<?php
/**
 * Title: ヘッダー ② センター（ロゴ中央・ナビ下段）
 * Slug: agent-neo/header-centered
 * Categories: agent-neo, agent-neo-shared
 * Description: ロゴを中央に置き、ナビゲーションを下段に一列で並べる 2 段ヘッダー。メディア・編集誌向け。CTA なし。
 * Keywords: header, centered, magazine
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

<!-- wp:group {"className":"an-site-header-inner an-header\u002d\u002dcentered","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"0","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|20"},"border":{"bottom":{"color":"var(u002du002dwpu002du002dpresetu002du002dcoloru002du002dsecondary)","width":"1px","style":"solid"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group an-site-header-inner an-header--centered" style="border-bottom-color:var(u002du002dwpu002du002dpresetu002du002dcoloru002du002dsecondary);border-bottom-style:solid;border-bottom-width:1px;padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--40);padding-bottom:0;padding-left:var(--wp--preset--spacing--40)"><!-- wp:group {"className":"an-site-branding","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","justifyContent":"center","verticalAlignment":"center"}} -->
<div class="wp-block-group an-site-branding"><!-- wp:site-logo {"width":36} /-->

<!-- wp:site-title {"level":0,"style":{"typography":{"fontWeight":"700"}},"textColor":"foreground","fontSize":"x-large"} /--></div>
<!-- /wp:group -->

<!-- wp:site-tagline {"style":{"typography":{"textAlign":"center"}},"textColor":"muted","fontSize":"small"} /-->

<!-- wp:navigation {"textColor":"foreground","style":{"spacing":{"blockGap":"var:preset|spacing|40","padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}},"typography":{"fontWeight":"600","textTransform":"uppercase"}},"fontSize":"small","layout":{"type":"flex","justifyContent":"center","flexWrap":"wrap"},"ariaLabel":"グローバルナビゲーション"} -->
<!-- wp:navigation-link {"label":"ホーム","url":"/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"記事","url":"/blog/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"導入事例","url":"/category/case/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"お問い合わせ","url":"/contact/","kind":"custom","isTopLevelLink":true} /-->
<!-- /wp:navigation --></div>
<!-- /wp:group -->
