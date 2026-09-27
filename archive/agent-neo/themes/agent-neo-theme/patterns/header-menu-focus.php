<?php
/**
 * Title: ヘッダー ⑨ メニュー主体
 * Slug: agent-neo/header-menu-focus
 * Categories: agent-neo, agent-neo-shared
 * Description: ブランド名と大きなメニューボタンを主役にしたハンバーガー主体のヘッダー。モバイル優先のサイトや作品紹介に向く。
 * Keywords: header, menu, hamburger, mobile
 * Viewport Width: 1280
 * Block Types: core/template-part/header, core/navigation
 * Post Types: wp_template, wp_template_part
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"className":"an-site-header-inner an-header\u002d\u002dmenu-focus","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group an-site-header-inner an-header--menu-focus" style="padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--40)">
<!-- wp:group {"align":"wide","style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group alignwide">
<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group">
<!-- wp:site-logo /-->

<!-- wp:site-title {"level":0,"style":{"typography":{"fontWeight":"800"}},"fontSize":"large"} /-->
</div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group">
<!-- wp:paragraph {"textColor":"muted","fontSize":"small"} -->
<p class="has-muted-color has-text-color has-small-font-size"><?php esc_html_e( 'ページを選ぶ', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:navigation {"overlayMenu":"always","showSubmenuIcon":true,"className":"an-menu-focus-navigation","style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"flex","justifyContent":"right"},"ariaLabel":"<?php echo esc_attr__( 'メニューを開く', 'agent-neo' ); ?>"} -->
<!-- wp:navigation-link {"label":"<?php echo esc_attr__( 'ホーム', 'agent-neo' ); ?>","url":"/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"<?php echo esc_attr__( 'サービス', 'agent-neo' ); ?>","url":"/lp/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"<?php echo esc_attr__( '記事', 'agent-neo' ); ?>","url":"/blog/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"<?php echo esc_attr__( 'お問い合わせ', 'agent-neo' ); ?>","url":"/contact/","kind":"custom","isTopLevelLink":true} /-->
<!-- /wp:navigation -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
