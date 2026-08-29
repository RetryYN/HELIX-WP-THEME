<?php
/**
 * Title: サイドバー ② CTA 追従（相談ボックス + 目次代わりの人気記事）
 * Slug: agent-neo/sidebar-cta
 * Categories: agent-neo, agent-neo-shared
 * Description: 濃色の相談ボックスを最上段に置き、続けて人気（新着）記事とタグ。スクロールしても CTA が残るよう追従用クラスを付与。営業サイト向け。
 * Keywords: sidebar, cta, sticky, conversion
 * Viewport Width: 400
 * Block Types: core/template-part/sidebar
 * Post Types: wp_template, wp_template_part
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"className":"an-sidebar an-sidebar\u002d\u002dcta","style":{"spacing":{"blockGap":"var:preset|spacing|40"}},"layout":{"type":"default"}} -->
<div class="wp-block-group an-sidebar an-sidebar--cta"><!-- wp:group {"className":"an-sidebar-widget an-sidebar-cta-box","style":{"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"blockGap":"var:preset|spacing|20"}},"backgroundColor":"primary","textColor":"background","layout":{"type":"constrained"}} -->
<div class="wp-block-group an-sidebar-widget an-sidebar-cta-box has-background-color has-primary-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--30)"><!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","textTransform":"uppercase"}},"textColor":"accent","fontSize":"small"} -->
<p class="has-accent-color has-text-color has-small-font-size" style="font-weight:700;text-transform:uppercase"><?php esc_html_e( 'Free consultation', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"style":{"typography":{"fontWeight":"800","lineHeight":"1.3"}},"textColor":"background","fontSize":"large"} -->
<h2 class="wp-block-heading has-background-color has-text-color has-large-font-size" style="font-weight:800;line-height:1.3"><?php esc_html_e( '運用の自動化、どこから始めるか 30 分で整理します', 'agent-neo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"textColor":"background","fontSize":"small"} -->
<p class="has-background-color has-text-color has-small-font-size"><?php esc_html_e( '現状のサイトを画面共有しながら、優先順位と概算をその場でお出しします。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"style":{"spacing":{"blockGap":"var:preset|spacing|10"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"accent-aa","textColor":"background","style":{"typography":{"fontWeight":"700"},"dimensions":{"width":"100%"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-background-color has-accent-aa-background-color has-text-color has-background wp-element-button" href="/contact/" style="font-weight:700"><?php esc_html_e( '無料で相談する →', 'agent-neo' ); ?></a></div>
<!-- /wp:button -->

<!-- wp:button {"textColor":"background","className":"has-custom-width wp-block-button__width-100 is-style-outline","fontSize":"small"} -->
<div class="wp-block-button has-custom-width wp-block-button__width-100 is-style-outline"><a class="wp-block-button__link has-background-color has-text-color has-small-font-size has-custom-font-size wp-element-button" href="/download/"><?php esc_html_e( '資料をダウンロード', 'agent-neo' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"an-sidebar-widget","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group an-sidebar-widget"><!-- wp:heading {"style":{"typography":{"fontWeight":"700"}},"fontSize":"medium"} -->
<h2 class="wp-block-heading has-medium-font-size" style="font-weight:700"><?php esc_html_e( 'よく読まれている記事', 'agent-neo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:latest-posts {"className":"an-sidebar-ranking","fontSize":"small"} /--></div>
<!-- /wp:group -->

<!-- wp:group {"className":"an-sidebar-widget","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group an-sidebar-widget"><!-- wp:heading {"style":{"typography":{"fontWeight":"700"}},"fontSize":"medium"} -->
<h2 class="wp-block-heading has-medium-font-size" style="font-weight:700"><?php esc_html_e( 'タグ', 'agent-neo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:tag-cloud {"numberOfTags":12,"smallestFontSize":"0.8125rem","largestFontSize":"0.8125rem","className":"an-sidebar-tags"} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
