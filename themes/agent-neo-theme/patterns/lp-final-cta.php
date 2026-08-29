<?php
/**
 * Title: LP 最終 CTA（締め）
 * Slug: agent-neo/lp-final-cta
 * Categories: agent-neo
 * Description: LP 最終 CTA セクション。濃インク帯・白見出し・オレンジ CTA ボタン（大）。コントラスト強め。
 * Keywords: lp, cta, contact, close, footer-cta, final
 * Viewport Width: 1280
 * Block Types: core/group
 * Post Types: page, wp_template
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"align":"full","className":"an-lp-final-cta","backgroundColor":"footer-bg","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} --><div class="wp-block-group alignfull an-lp-final-cta has-footer-bg-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40)"><!-- wp:heading {"style":{"typography":{"fontWeight":"800","fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dxx-large)","lineHeight":"1.2","textAlign":"center"},"spacing":{"margin":{"bottom":"var:preset|spacing|30"}},"color":{"text":"#ffffff"}}} -->
<h2 class="wp-block-heading has-text-align-center has-text-color" style="color:#ffffff;margin-bottom:var(--wp--preset--spacing--30);font-size:var(--wp--preset--font-size--xx-large);font-weight:800;line-height:1.2"><?php esc_html_e( 'WordPress 運用の課題、', 'agent-neo' ); ?><br><?php esc_html_e( '今すぐ解決しませんか。', 'agent-neo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dlarge)","lineHeight":"1.7"},"spacing":{"margin":{"bottom":"var:preset|spacing|50"}},"color":{"text":"rgba(255,255,255,0.80)"}}} -->
<p class="has-text-color" style="color:rgba(255,255,255,0.80);margin-bottom:var(--wp--preset--spacing--50);font-size:var(--wp--preset--font-size--large);line-height:1.7"><?php esc_html_e( '初期設定は最短1日。インストールだけで、あとはシステムが自動で動き始めます。', 'agent-neo' ); ?><br><?php esc_html_e( 'まずは無料トライアルでその効果を体感してください。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"accent-aa","textColor":"background","style":{"border":{"radius":"6px"},"typography":{"fontWeight":"800","fontSize":"1.125rem"},"spacing":{"padding":{"top":"1.25rem","bottom":"1.25rem","left":"3rem","right":"3rem"}}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-background-color has-accent-aa-background-color has-text-color has-background has-custom-font-size wp-element-button" href="#" style="border-radius:6px;padding-top:1.25rem;padding-right:3rem;padding-bottom:1.25rem;padding-left:3rem;font-size:1.125rem;font-weight:800"><?php esc_html_e( '14日間 無料で試す →', 'agent-neo' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dsmall)"},"spacing":{"margin":{"top":"var:preset|spacing|30"}},"color":{"text":"rgba(255,255,255,0.50)"}}} -->
<p class="has-text-color" style="color:rgba(255,255,255,0.50);margin-top:var(--wp--preset--spacing--30);font-size:var(--wp--preset--font-size--small)"><?php esc_html_e( 'クレジットカード不要・いつでも解約可能・導入サポートあり', 'agent-neo' ); ?></p>
<!-- /wp:paragraph --></div><!-- /wp:group -->
