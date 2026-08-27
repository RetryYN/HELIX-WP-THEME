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

<!-- wp:group {"className":"an-site-header-inner an-header--centered","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"0","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|20"},"border":{"bottom":{"color":"var(--wp--preset--color--secondary)","width":"1px","style":"solid"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group an-site-header-inner an-header--centered" style="border-bottom:1px solid var(--wp--preset--color--secondary);padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--40);padding-bottom:0;padding-left:var(--wp--preset--spacing--40)">

	<!-- wp:group {"className":"an-site-branding","layout":{"type":"flex","justifyContent":"center","verticalAlignment":"center"},"style":{"spacing":{"blockGap":"var:preset|spacing|20"}}} -->
	<div class="wp-block-group an-site-branding" style="gap:var(--wp--preset--spacing--20)">
		<!-- wp:site-logo {"width":36} /-->
		<!-- wp:site-title {"level":0,"isLink":true,"fontSize":"x-large","style":{"typography":{"fontWeight":"700"}},"textColor":"foreground"} /-->
	</div>
	<!-- /wp:group -->

	<!-- wp:site-tagline {"textAlign":"center","fontSize":"small","textColor":"muted"} /-->

	<!-- wp:navigation {"ariaLabel":"グローバルナビゲーション","overlayMenu":"mobile","layout":{"type":"flex","justifyContent":"center","flexWrap":"wrap"},"fontSize":"small","style":{"spacing":{"blockGap":"var:preset|spacing|40","padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}},"typography":{"fontWeight":"600","textTransform":"uppercase"}},"textColor":"foreground"} -->
		<!-- wp:navigation-link {"label":"ホーム","url":"/","kind":"custom","isTopLevelLink":true} /-->
		<!-- wp:navigation-link {"label":"記事","url":"/blog/","kind":"custom","isTopLevelLink":true} /-->
		<!-- wp:navigation-link {"label":"導入事例","url":"/category/case/","kind":"custom","isTopLevelLink":true} /-->
		<!-- wp:navigation-link {"label":"お問い合わせ","url":"/contact/","kind":"custom","isTopLevelLink":true} /-->
	<!-- /wp:navigation -->

</div>
<!-- /wp:group -->
