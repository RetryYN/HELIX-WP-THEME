<?php
/**
 * Title: フッター ⑥ 連絡先 3 カラム
 * Slug: agent-neo/footer-contact-grid
 * Categories: agent-neo, agent-neo-shared
 * Description: ブランド、連絡先、受付時間を 3 カラムで見せるフッター。問い合わせ導線を常に分かりやすくしたいサイト向け。
 * Keywords: footer, contact, address, columns
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

<!-- wp:group {"className":"an-site-footer-inner an-footer\u002d\u002dcontact-grid","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|50"}},"backgroundColor":"footer-bg","textColor":"background","layout":{"type":"constrained"}} -->
<div class="wp-block-group an-site-footer-inner an-footer--contact-grid has-background-color has-footer-bg-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)"><!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50","top":"var:preset|spacing|40"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"40%"} -->
<div class="wp-block-column" style="flex-basis:40%"><!-- wp:site-title {"level":0,"textColor":"background","style":{"typography":{"fontWeight":"800"}},"fontSize":"x-large"} /-->

<!-- wp:site-tagline {"textColor":"muted","fontSize":"small"} /-->

<!-- wp:paragraph {"textColor":"muted","fontSize":"small"} -->
<p class="has-muted-color has-text-color has-small-font-size"><?php esc_html_e( '考える人とつくる人のための、静かな運用基盤です。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"30%"} -->
<div class="wp-block-column" style="flex-basis:30%"><!-- wp:heading {"textColor":"background","style":{"typography":{"fontWeight":"700"}},"fontSize":"medium"} -->
<h2 class="wp-block-heading has-background-color has-text-color has-medium-font-size" style="font-weight:700"><?php esc_html_e( '連絡先', 'agent-neo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"textColor":"muted","fontSize":"small"} -->
<p class="has-muted-color has-text-color has-small-font-size"><?php esc_html_e( 'お問い合わせフォーム', 'agent-neo' ); ?><br><?php esc_html_e( '平日 10:00–18:00 受付', 'agent-neo' ); ?><br><?php esc_html_e( '通常 2 営業日以内に返信', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:button {"backgroundColor":"accent","textColor":"primary","style":{"typography":{"fontWeight":"700"}},"fontSize":"small"} -->
<div class="wp-block-button"><a class="wp-block-button__link has-primary-color has-accent-background-color has-text-color has-background has-small-font-size has-custom-font-size wp-element-button" href="/contact/" style="font-weight:700"><?php esc_html_e( 'フォームを開く', 'agent-neo' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"30%"} -->
<div class="wp-block-column" style="flex-basis:30%"><!-- wp:heading {"textColor":"background","style":{"typography":{"fontWeight":"700"}},"fontSize":"medium"} -->
<h2 class="wp-block-heading has-background-color has-text-color has-medium-font-size" style="font-weight:700"><?php esc_html_e( 'ご案内', 'agent-neo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:navigation {"textColor":"background","overlayMenu":"never","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"fontSize":"small","layout":{"type":"flex","orientation":"vertical"},"ariaLabel":"<?php echo esc_attr__( 'ご案内メニュー', 'agent-neo' ); ?>"} -->
<!-- wp:navigation-link {"label":"<?php echo esc_attr__( 'サービス', 'agent-neo' ); ?>","url":"/lp/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"<?php echo esc_attr__( '記事一覧', 'agent-neo' ); ?>","url":"/blog/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"<?php echo esc_attr__( '運営者情報', 'agent-neo' ); ?>","url":"/owner/","kind":"custom","isTopLevelLink":true} /-->
<!-- /wp:navigation --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:group {"align":"wide","style":{"border":{"top":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dmuted)","width":"1px","style":"solid"}},"spacing":{"padding":{"top":"var:preset|spacing|30"}}},"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
<div class="wp-block-group alignwide" style="border-top-color:var(--wp--preset--color--muted);border-top-style:solid;border-top-width:1px;padding-top:var(--wp--preset--spacing--30)"><!-- wp:paragraph {"textColor":"muted","fontSize":"small"} -->
<p class="has-muted-color has-text-color has-small-font-size"><?php esc_html_e( '© サイト運営者', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:navigation {"textColor":"muted","overlayMenu":"never","style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"fontSize":"small","layout":{"type":"flex","justifyContent":"right"},"ariaLabel":"<?php echo esc_attr__( 'フッター補助ナビゲーション', 'agent-neo' ); ?>"} -->
<!-- wp:navigation-link {"label":"<?php echo esc_attr__( 'プライバシーポリシー', 'agent-neo' ); ?>","url":"/privacy/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"<?php echo esc_attr__( '利用規約', 'agent-neo' ); ?>","url":"/terms/","kind":"custom","isTopLevelLink":true} /-->
<!-- /wp:navigation --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
