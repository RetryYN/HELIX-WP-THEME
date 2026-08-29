<?php
/**
 * Title: フッター ⑧ サイトマップ
 * Slug: agent-neo/footer-sitemap
 * Categories: agent-neo, agent-neo-shared
 * Description: サービス、読みもの、会社情報、サポートを 4 列のサイトマップとして並べるフッター。回遊を最優先するサイト向け。
 * Keywords: footer, sitemap, navigation, columns
 * Viewport Width: 1280
 * Block Types: core/template-part/footer, core/columns
 * Post Types: wp_template, wp_template_part
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"className":"an-site-footer-inner an-footer\u002d\u002dsitemap","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|50"}},"backgroundColor":"secondary","layout":{"type":"constrained"}} -->
<div class="wp-block-group an-site-footer-inner an-footer--sitemap has-secondary-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)"><!-- wp:group {"align":"wide","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap","verticalAlignment":"bottom"}} -->
<div class="wp-block-group alignwide"><!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group"><!-- wp:site-title {"level":0,"style":{"typography":{"fontWeight":"800"}},"fontSize":"x-large"} /-->

<!-- wp:site-tagline {"textColor":"muted","fontSize":"small"} /--></div>
<!-- /wp:group -->

<!-- wp:paragraph {"textColor":"muted","fontSize":"small"} -->
<p class="has-muted-color has-text-color has-small-font-size"><?php esc_html_e( '迷ったときは、目的からお選びください。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|40","top":"var:preset|spacing|30"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"textColor":"muted","style":{"typography":{"fontWeight":"700","textTransform":"uppercase"}},"fontSize":"small"} -->
<h2 class="wp-block-heading has-muted-color has-text-color has-small-font-size" style="font-weight:700;text-transform:uppercase"><?php esc_html_e( 'サービス', 'agent-neo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:navigation {"overlayMenu":"never","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"fontSize":"small","layout":{"type":"flex","orientation":"vertical"},"ariaLabel":"<?php echo esc_attr__( 'サービス一覧', 'agent-neo' ); ?>"} -->
<!-- wp:navigation-link {"label":"<?php echo esc_attr__( '概要', 'agent-neo' ); ?>","url":"/lp/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"<?php echo esc_attr__( '料金', 'agent-neo' ); ?>","url":"/lp/#pricing","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"<?php echo esc_attr__( '導入事例', 'agent-neo' ); ?>","url":"/category/case/","kind":"custom","isTopLevelLink":true} /-->
<!-- /wp:navigation --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"textColor":"muted","style":{"typography":{"fontWeight":"700","textTransform":"uppercase"}},"fontSize":"small"} -->
<h2 class="wp-block-heading has-muted-color has-text-color has-small-font-size" style="font-weight:700;text-transform:uppercase"><?php esc_html_e( '読みもの', 'agent-neo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:navigation {"overlayMenu":"never","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"fontSize":"small","layout":{"type":"flex","orientation":"vertical"},"ariaLabel":"<?php echo esc_attr__( '読みもの一覧', 'agent-neo' ); ?>"} -->
<!-- wp:navigation-link {"label":"<?php echo esc_attr__( '記事一覧', 'agent-neo' ); ?>","url":"/blog/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"<?php echo esc_attr__( 'カテゴリー', 'agent-neo' ); ?>","url":"/categories/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"<?php echo esc_attr__( '検索', 'agent-neo' ); ?>","url":"/search/","kind":"custom","isTopLevelLink":true} /-->
<!-- /wp:navigation --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"textColor":"muted","style":{"typography":{"fontWeight":"700","textTransform":"uppercase"}},"fontSize":"small"} -->
<h2 class="wp-block-heading has-muted-color has-text-color has-small-font-size" style="font-weight:700;text-transform:uppercase"><?php esc_html_e( '会社情報', 'agent-neo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:navigation {"overlayMenu":"never","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"fontSize":"small","layout":{"type":"flex","orientation":"vertical"},"ariaLabel":"<?php echo esc_attr__( '会社情報一覧', 'agent-neo' ); ?>"} -->
<!-- wp:navigation-link {"label":"<?php echo esc_attr__( '運営者情報', 'agent-neo' ); ?>","url":"/owner/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"<?php echo esc_attr__( 'お知らせ', 'agent-neo' ); ?>","url":"/news/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"<?php echo esc_attr__( 'お問い合わせ', 'agent-neo' ); ?>","url":"/contact/","kind":"custom","isTopLevelLink":true} /-->
<!-- /wp:navigation --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"textColor":"muted","style":{"typography":{"fontWeight":"700","textTransform":"uppercase"}},"fontSize":"small"} -->
<h2 class="wp-block-heading has-muted-color has-text-color has-small-font-size" style="font-weight:700;text-transform:uppercase"><?php esc_html_e( 'サポート', 'agent-neo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:navigation {"overlayMenu":"never","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"fontSize":"small","layout":{"type":"flex","orientation":"vertical"},"ariaLabel":"<?php echo esc_attr__( 'サポート一覧', 'agent-neo' ); ?>"} -->
<!-- wp:navigation-link {"label":"<?php echo esc_attr__( 'よくある質問', 'agent-neo' ); ?>","url":"/lp/#faq","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"<?php echo esc_attr__( '利用規約', 'agent-neo' ); ?>","url":"/terms/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"<?php echo esc_attr__( 'プライバシー', 'agent-neo' ); ?>","url":"/privacy/","kind":"custom","isTopLevelLink":true} /-->
<!-- /wp:navigation --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:paragraph {"align":"wide","textColor":"muted","fontSize":"small"} -->
<p class="alignwide has-muted-color has-text-color has-small-font-size"><?php esc_html_e( '© サイト運営者', 'agent-neo' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
