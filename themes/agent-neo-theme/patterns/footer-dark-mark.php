<?php
/**
 * Title: フッター ⑨ ダーク大型ロゴ
 * Slug: agent-neo/footer-dark-mark
 * Categories: agent-neo, agent-neo-shared
 * Description: ダークな背景に大型のサイトタイトルと短いメッセージを置く余白重視のフッター。ブランドの余韻を残したいサイト向け。
 * Keywords: footer, dark, logo, brand, minimal
 * Viewport Width: 1280
 * Block Types: core/template-part/footer, core/site-title
 * Post Types: wp_template, wp_template_part
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"className":"an-site-footer-inner an-footer\u002d\u002ddark-mark","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|50"}},"backgroundColor":"footer-bg","textColor":"background","layout":{"type":"constrained"}} -->
<div class="wp-block-group an-site-footer-inner an-footer--dark-mark has-background-color has-footer-bg-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)"><!-- wp:group {"align":"wide","style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide"><!-- wp:site-title {"level":0,"textColor":"background","style":{"typography":{"fontWeight":"800"}},"fontSize":"xxx-large"} /-->

<!-- wp:paragraph {"textColor":"muted","fontSize":"large"} -->
<p class="has-muted-color has-text-color has-large-font-size"><?php esc_html_e( '余白のある運用で、次の一歩をつくる。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|30"}}}} -->
<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--30)"><!-- wp:button {"backgroundColor":"accent","textColor":"primary","style":{"typography":{"fontWeight":"700"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-primary-color has-accent-background-color has-text-color has-background wp-element-button" href="/contact/" style="font-weight:700"><?php esc_html_e( '話を聞いてみる', 'agent-neo' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:group {"align":"wide","style":{"border":{"top":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dmuted)","width":"1px","style":"solid"}},"spacing":{"padding":{"top":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|30"}},"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
<div class="wp-block-group alignwide" style="border-top-color:var(--wp--preset--color--muted);border-top-style:solid;border-top-width:1px;padding-top:var(--wp--preset--spacing--30)"><!-- wp:navigation {"textColor":"background","overlayMenu":"never","style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"fontSize":"small","layout":{"type":"flex","justifyContent":"right","flexWrap":"wrap"},"ariaLabel":"<?php echo esc_attr__( 'フッターナビゲーション', 'agent-neo' ); ?>"} -->
<!-- wp:navigation-link {"label":"<?php echo esc_attr__( 'サービス', 'agent-neo' ); ?>","url":"/lp/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"<?php echo esc_attr__( '記事', 'agent-neo' ); ?>","url":"/blog/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"<?php echo esc_attr__( 'お問い合わせ', 'agent-neo' ); ?>","url":"/contact/","kind":"custom","isTopLevelLink":true} /-->
<!-- /wp:navigation -->

<!-- wp:paragraph {"textColor":"muted","fontSize":"small"} -->
<p class="has-muted-color has-text-color has-small-font-size"><?php esc_html_e( '© サイト運営者', 'agent-neo' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
