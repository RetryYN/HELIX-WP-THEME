<?php
/**
 * Title: ヘッダー ④ ミニマル（サイト名 + オーバーレイメニュー）
 * Slug: agent-neo/header-minimal
 * Categories: agent-neo, agent-neo-shared
 * Description: サイト名と常時オーバーレイのメニューボタンだけの最小ヘッダー。LP・ポートフォリオ向け。右端に控えめな CTA テキストリンク。
 * Keywords: header, minimal, overlay, lp
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

<!-- wp:group {"className":"an-site-header-inner an-header\u002d\u002dminimal","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group an-site-header-inner an-header--minimal" style="padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--40)"><!-- wp:site-title {"level":0,"style":{"typography":{"fontWeight":"700","textTransform":"uppercase"}},"textColor":"foreground","fontSize":"medium"} /-->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"style":{"typography":{"fontWeight":"600"}},"fontSize":"small"} -->
<p class="has-small-font-size" style="font-weight:600"><a href="/contact/"><?php esc_html_e( '相談する →', 'agent-neo' ); ?></a></p>
<!-- /wp:paragraph -->

<!-- wp:navigation {"textColor":"foreground","overlayMenu":"always","icon":"menu","overlayBackgroundColor":"primary","overlayTextColor":"background","style":{"typography":{"fontWeight":"700"}},"fontSize":"x-large","layout":{"type":"flex","justifyContent":"right"},"ariaLabel":"メニュー"} -->
<!-- wp:navigation-link {"label":"ホーム","url":"/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"サービス","url":"/lp/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"記事","url":"/blog/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"お問い合わせ","url":"/contact/","kind":"custom","isTopLevelLink":true} /-->
<!-- /wp:navigation --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
