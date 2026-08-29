<?php
/**
 * Title: フッター ⑦ SNS 更新帯
 * Slug: agent-neo/footer-social-strip
 * Categories: agent-neo, agent-neo-shared
 * Description: SNS やニュースの更新先をひとつの帯に集約するフッター。複数の情報発信先へ自然に誘導したいサイト向け。
 * Keywords: footer, social, sns, updates, strip
 * Viewport Width: 1280
 * Block Types: core/template-part/footer, core/navigation
 * Post Types: wp_template, wp_template_part
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"className":"an-site-footer-inner an-footer\u002d\u002dsocial-strip","style":{"spacing":{"blockGap":"var:preset|spacing|40"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group an-site-footer-inner an-footer--social-strip"><!-- wp:group {"backgroundColor":"primary","textColor":"background","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-background-color has-primary-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)"><!-- wp:group {"align":"wide","style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap","verticalAlignment":"center"}} -->
<div class="wp-block-group alignwide"><!-- wp:heading {"textColor":"background","style":{"typography":{"fontWeight":"700"}},"fontSize":"large"} -->
<h2 class="wp-block-heading has-background-color has-text-color has-large-font-size" style="font-weight:700"><?php esc_html_e( '最新情報を受け取る', 'agent-neo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:navigation {"textColor":"background","overlayMenu":"never","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"fontSize":"small","layout":{"type":"flex","justifyContent":"right","flexWrap":"wrap"},"ariaLabel":"<?php echo esc_attr__( '更新情報へのリンク', 'agent-neo' ); ?>"} -->
<!-- wp:navigation-link {"label":"<?php echo esc_attr__( '公式アカウント', 'agent-neo' ); ?>","url":"/updates/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"<?php echo esc_attr__( 'ニュース', 'agent-neo' ); ?>","url":"/news/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"<?php echo esc_attr__( 'コミュニティ', 'agent-neo' ); ?>","url":"/community/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"<?php echo esc_attr__( '更新通知', 'agent-neo' ); ?>","url":"/feed/","kind":"custom","isTopLevelLink":true} /-->
<!-- /wp:navigation --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
<div class="wp-block-group alignwide" style="padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)"><!-- wp:site-title {"level":0,"fontSize":"large","style":{"typography":{"fontWeight":"700"}}} /-->

<!-- wp:paragraph {"textColor":"muted","fontSize":"small"} -->
<p class="has-muted-color has-text-color has-small-font-size"><?php esc_html_e( '発信先を選んで、必要な情報だけ。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
