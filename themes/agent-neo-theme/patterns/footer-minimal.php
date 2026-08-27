<?php
/**
 * Title: フッター ② ミニマル（1 行）
 * Slug: agent-neo/footer-minimal
 * Categories: agent-neo, agent-neo-shared
 * Description: サイト名・横並びナビ・コピーライトだけの 1 行フッター。明るい背景で本文と地続き。LP・小規模サイト向け。
 * Keywords: footer, minimal, one-line
 * Viewport Width: 1280
 * Block Types: core/template-part/footer
 * Post Types: wp_template, wp_template_part
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"className":"an-site-footer-inner an-footer--minimal","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|20"},"border":{"top":{"color":"var(--wp--preset--color--secondary)","width":"1px","style":"solid"}}},"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap","verticalAlignment":"center"}} -->
<div class="wp-block-group an-site-footer-inner an-footer--minimal" style="border-top:1px solid var(--wp--preset--color--secondary);padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">

	<!-- wp:site-title {"level":0,"isLink":true,"fontSize":"medium","style":{"typography":{"fontWeight":"700"}},"textColor":"foreground"} /-->

	<!-- wp:navigation {"ariaLabel":"フッターナビゲーション","overlayMenu":"never","layout":{"type":"flex","justifyContent":"center","flexWrap":"wrap"},"fontSize":"small","style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"textColor":"muted"} -->
		<!-- wp:navigation-link {"label":"記事","url":"/blog/","kind":"custom","isTopLevelLink":true} /-->
		<!-- wp:navigation-link {"label":"運営者情報","url":"/owner/","kind":"custom","isTopLevelLink":true} /-->
		<!-- wp:navigation-link {"label":"プライバシーポリシー","url":"/privacy/","kind":"custom","isTopLevelLink":true} /-->
		<!-- wp:navigation-link {"label":"お問い合わせ","url":"/contact/","kind":"custom","isTopLevelLink":true} /-->
	<!-- /wp:navigation -->

	<!-- wp:pattern {"slug":"agent-neo/footer-credit"} /-->

</div>
<!-- /wp:group -->
