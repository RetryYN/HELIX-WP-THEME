<?php
/**
 * Title: ヘッダー ③ トップバー（連絡帯 + メイン行）
 * Slug: agent-neo/header-topbar
 * Categories: agent-neo, agent-neo-shared
 * Description: 上に濃色の細い帯（営業時間・連絡先・補助リンク）、下にロゴ・ナビ・CTA のメイン行を持つ 2 段ヘッダー。法人サイト向け。
 * Keywords: header, topbar, corporate
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

<!-- wp:group {"className":"an-site-header-inner an-header\u002d\u002dtopbar","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
<div class="wp-block-group an-site-header-inner an-header--topbar"><!-- wp:group {"className":"an-header-topbar","style":{"spacing":{"padding":{"top":"var:preset|spacing|10","bottom":"var:preset|spacing|10","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"backgroundColor":"primary","textColor":"background","fontSize":"small","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
<div class="wp-block-group an-header-topbar has-background-color has-primary-background-color has-text-color has-background has-small-font-size" style="padding-top:var(--wp--preset--spacing--10);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--10);padding-left:var(--wp--preset--spacing--40)"><!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size"><?php esc_html_e( '平日 10:00–18:00 受付 ｜ 初回相談は無料です', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:navigation {"textColor":"background","overlayMenu":"never","style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"fontSize":"small","layout":{"type":"flex","justifyContent":"right"},"ariaLabel":"補助ナビゲーション"} -->
<!-- wp:navigation-link {"label":"運営者情報","url":"/owner/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"採用","url":"/recruit/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"お問い合わせ","url":"/contact/","kind":"custom","isTopLevelLink":true} /-->
<!-- /wp:navigation --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"an-header-main","style":{"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"border":{"bottom":{"color":"var(u002du002dwpu002du002dpresetu002du002dcoloru002du002dsecondary)","width":"1px","style":"solid"}}},"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
<div class="wp-block-group an-header-main" style="border-bottom-color:var(u002du002dwpu002du002dpresetu002du002dcoloru002du002dsecondary);border-bottom-style:solid;border-bottom-width:1px;padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--40)"><!-- wp:group {"className":"an-site-branding","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group an-site-branding"><!-- wp:site-logo {"width":40} /-->

<!-- wp:group {"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group"><!-- wp:site-title {"level":0,"style":{"typography":{"fontWeight":"700"}},"textColor":"foreground","fontSize":"large"} /-->

<!-- wp:site-tagline {"textColor":"muted","fontSize":"small"} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|40"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center","justifyContent":"right"}} -->
<div class="wp-block-group"><!-- wp:navigation {"textColor":"foreground","style":{"spacing":{"blockGap":"var:preset|spacing|30"},"typography":{"fontWeight":"600"}},"fontSize":"small","layout":{"type":"flex","justifyContent":"right","flexWrap":"nowrap"},"ariaLabel":"グローバルナビゲーション"} -->
<!-- wp:navigation-link {"label":"サービス","url":"/lp/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"導入事例","url":"/category/case/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"記事","url":"/blog/","kind":"custom","isTopLevelLink":true} /-->

<!-- wp:navigation-link {"label":"会社情報","url":"/about/","kind":"custom","isTopLevelLink":true} /-->
<!-- /wp:navigation -->

<!-- wp:buttons {"style":{"spacing":{"blockGap":"var:preset|spacing|10"}},"layout":{"type":"flex","justifyContent":"right","flexWrap":"nowrap"}} -->
<div class="wp-block-buttons"><!-- wp:button {"textColor":"accent-aa","className":"is-style-outline an-header-cta-secondary","style":{"typography":{"fontWeight":"700"}},"fontSize":"small"} -->
<div class="wp-block-button is-style-outline an-header-cta-secondary"><a class="wp-block-button__link has-accent-aa-color has-text-color has-small-font-size has-custom-font-size wp-element-button" href="/download/" style="font-weight:700"><?php esc_html_e( '資料請求', 'agent-neo' ); ?></a></div>
<!-- /wp:button -->

<!-- wp:button {"backgroundColor":"accent-aa","textColor":"background","className":"an-header-cta","style":{"typography":{"fontWeight":"700"}},"fontSize":"small"} -->
<div class="wp-block-button an-header-cta"><a class="wp-block-button__link has-background-color has-accent-aa-background-color has-text-color has-background has-small-font-size has-custom-font-size wp-element-button" href="/contact/" style="font-weight:700"><?php esc_html_e( '無料で相談', 'agent-neo' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
