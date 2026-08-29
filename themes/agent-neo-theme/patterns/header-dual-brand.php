<?php
/**
 * Title: ヘッダー ⑧ 二段ロゴ + CTA
 * Slug: agent-neo/header-dual-brand
 * Categories: agent-neo, agent-neo-shared
 * Description: 上段にロゴとブランド情報、下段にカテゴリーナビゲーションと CTA をまとめる二段構成。情報量の多い企業サイト向け。
 * Keywords: header, brand, two-row, cta
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

<!-- wp:group {"className":"an-site-header-inner an-header\u002d\u002ddual-brand","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group an-site-header-inner an-header--dual-brand"><!-- wp:group {"align":"wide","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30"}}},"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap","verticalAlignment":"center"}} -->
<div class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30)"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group"><!-- wp:site-logo /-->

<!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group"><!-- wp:site-title {"level":0,"style":{"typography":{"fontWeight":"800"}},"fontSize":"large"} /-->

<!-- wp:site-tagline {"textColor":"muted","fontSize":"small"} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:buttons {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","justifyContent":"right"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline","textColor":"accent-aa","style":{"typography":{"fontWeight":"700"}},"fontSize":"small"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-accent-aa-color has-text-color has-small-font-size has-custom-font-size wp-element-button" href="/download/"><?php esc_html_e( '資料を見る', 'agent-neo' ); ?></a></div>
<!-- /wp:button -->

<!-- wp:button {"backgroundColor":"accent-aa","textColor":"background","style":{"typography":{"fontWeight":"700"}},"fontSize":"small"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-background-color has-accent-aa-background-color has-text-color has-background has-small-font-size has-custom-font-size wp-element-button" href="/contact/"><?php esc_html_e( 'お問い合わせ', 'agent-neo' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:group {"backgroundColor":"primary","textColor":"background","style":{"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group has-background-color has-primary-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20)"><!-- wp:navigation {"textColor":"background","overlayMenu":"never","style":{"spacing":{"blockGap":"var:preset|spacing|40"},"typography":{"fontWeight":"600"}},"fontSize":"small","layout":{"type":"flex","justifyContent":"center","flexWrap":"wrap"},"ariaLabel":"<?php echo esc_attr__( '主要ナビゲーション', 'agent-neo' ); ?>"} -->
<!-- wp:navigation-link {"label":"<?php echo esc_attr__( 'サービス', 'agent-neo' ); ?>","url":"/lp/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"<?php echo esc_attr__( '導入事例', 'agent-neo' ); ?>","url":"/category/case/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"<?php echo esc_attr__( '読みもの', 'agent-neo' ); ?>","url":"/blog/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"<?php echo esc_attr__( 'よくある質問', 'agent-neo' ); ?>","url":"/lp/#faq","kind":"custom","isTopLevelLink":true} /-->
<!-- /wp:navigation --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
