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

<!-- wp:group {"align":"full","className":"an-lp-final-cta","backgroundColor":"primary","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull an-lp-final-cta has-primary-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40)">

	<!-- wp:heading {"level":2,"textAlign":"center","style":{"typography":{"fontWeight":"800","fontSize":"var(--wp--preset--font-size--xx-large)","lineHeight":"1.2"},"spacing":{"margin":{"bottom":"var:preset|spacing|30"}},"color":{"text":"#ffffff"}}} -->
	<h2 class="wp-block-heading has-text-align-center" style="font-weight:800;font-size:var(--wp--preset--font-size--xx-large);line-height:1.2;color:#ffffff;margin-bottom:var(--wp--preset--spacing--30)"><?php esc_html_e( 'WordPress 運用の課題、', 'agent-neo' ); ?><br><?php esc_html_e( '今すぐ解決しませんか。', 'agent-neo' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"var(--wp--preset--font-size--large)","lineHeight":"1.7"},"spacing":{"margin":{"bottom":"var:preset|spacing|50"}},"color":{"text":"rgba(255,255,255,0.80)"}}} -->
	<p class="has-text-align-center" style="font-size:var(--wp--preset--font-size--large);line-height:1.7;color:rgba(255,255,255,0.80);margin-bottom:var(--wp--preset--spacing--50)"><?php esc_html_e( '初期設定は最短1日。インストールだけで、あとはシステムが自動で動き始めます。', 'agent-neo' ); ?><br><?php esc_html_e( 'まずは無料トライアルでその効果を体感してください。', 'agent-neo' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"blockGap":"var:preset|spacing|20"}}} -->
	<div class="wp-block-buttons">
		<!-- wp:button {"backgroundColor":"accent","textColor":"background","style":{"border":{"radius":"6px"},"typography":{"fontWeight":"800","fontSize":"1.125rem"},"spacing":{"padding":{"top":"1.25rem","bottom":"1.25rem","left":"3rem","right":"3rem"}}}} -->
		<div class="wp-block-button">
			<a class="wp-block-button__link has-accent-background-color has-background-color has-text-color has-background wp-element-button" href="#" style="border-radius:6px;font-weight:800;font-size:1.125rem;padding-top:1.25rem;padding-bottom:1.25rem;padding-left:3rem;padding-right:3rem"><?php esc_html_e( '14日間 無料で試す →', 'agent-neo' ); ?></a>
		</div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->

	<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"var(--wp--preset--font-size--small)"},"spacing":{"margin":{"top":"var:preset|spacing|30"}},"color":{"text":"rgba(255,255,255,0.50)"}}} -->
	<p class="has-text-align-center" style="font-size:var(--wp--preset--font-size--small);margin-top:var(--wp--preset--spacing--30);color:rgba(255,255,255,0.50)"><?php esc_html_e( 'クレジットカード不要・いつでも解約可能・導入サポートあり', 'agent-neo' ); ?></p>
	<!-- /wp:paragraph -->

</div>
<!-- /wp:group -->
