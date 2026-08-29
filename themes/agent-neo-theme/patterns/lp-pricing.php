<?php
/**
 * Title: LP 料金プラン
 * Slug: agent-neo/lp-pricing
 * Categories: agent-neo
 * Description: LP 料金プランセクション。3プランカード（推奨プランをオレンジ枠強調・価格大きく・特徴リスト・CTAボタン）。白背景帯。
 * Keywords: lp, pricing, plan, price, cost
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

<!-- wp:group {"align":"full","className":"an-lp-pricing","backgroundColor":"background","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull an-lp-pricing has-background-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40)">

	<!-- wp:heading {"level":2,"textAlign":"center","style":{"typography":{"fontWeight":"700"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
	<h2 class="wp-block-heading has-text-align-center" style="font-weight:700;margin-bottom:var(--wp--preset--spacing--20)"><?php esc_html_e( 'シンプルな料金プラン', 'agent-neo' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)"},"spacing":{"margin":{"bottom":"var:preset|spacing|50"}},"color":{"text":"var(--wp--preset--color--foreground)"}}} -->
	<p class="has-text-align-center" style="font-size:var(--wp--preset--font-size--medium);margin-bottom:var(--wp--preset--spacing--50);color:var(--wp--preset--color--foreground)"><?php esc_html_e( 'すべてのプランで14日間の無料トライアルが利用できます。クレジットカード登録は不要です。', 'agent-neo' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:columns {"isStackedOnMobile":true,"verticalAlignment":"top","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|30","left":"var:preset|spacing|30"}}}} --><div class="wp-block-columns are-vertically-aligned-top"><!-- wp:column {"verticalAlignment":"top","style":{"border":{"radius":"8px","color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dsecondary)","width":"2px"},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dsecondary)"}}} -->
<div class="wp-block-column is-vertically-aligned-top has-border-color has-background" style="border-color:var(--wp--preset--color--secondary);border-width:2px;border-radius:8px;background-color:var(--wp--preset--color--secondary);padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)"><!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dlarge)"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dprimary)"},"spacing":{"margin":{"bottom":"var:preset|spacing|10"}}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--primary);margin-bottom:var(--wp--preset--spacing--10);font-size:var(--wp--preset--font-size--large);font-weight:700"><?php esc_html_e( 'スターター', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dsmall)"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dmuted)"},"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--muted);margin-bottom:var(--wp--preset--spacing--30);font-size:var(--wp--preset--font-size--small)"><?php esc_html_e( '小規模サイト・個人メディア向け', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"fontSize":"xxx-large","style":{"typography":{"fontWeight":"800","lineHeight":"1.0"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dprimary)"},"spacing":{"margin":{"bottom":"var:preset|spacing|10"}}}} -->
<p class="has-xxx-large-font-size has-text-color" style="color:var(--wp--preset--color--primary);margin-bottom:var(--wp--preset--spacing--10);font-weight:800;line-height:1.0">¥29,800<span style="font-size:1rem;font-weight:400"><?php esc_html_e( '/月', 'agent-neo' ); ?></span></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dsmall)"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dmuted)"},"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--muted);margin-bottom:var(--wp--preset--spacing--40);font-size:var(--wp--preset--font-size--small)"><?php esc_html_e( '（税抜）', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20","margin":{"bottom":"var:preset|spacing|40"}}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group" style="margin-bottom:var(--wp--preset--spacing--40)"><!-- wp:paragraph {"style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dmedium)","lineHeight":"1.5"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dforeground)"}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--foreground);font-size:var(--wp--preset--font-size--medium);line-height:1.5">✓  <?php esc_html_e( 'AI 記事生成：月20本まで', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dmedium)","lineHeight":"1.5"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dforeground)"}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--foreground);font-size:var(--wp--preset--font-size--medium);line-height:1.5">✓  <?php esc_html_e( '1 WordPressサイト接続', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dmedium)","lineHeight":"1.5"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dforeground)"}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--foreground);font-size:var(--wp--preset--font-size--medium);line-height:1.5">✓  <?php esc_html_e( '基本 SEO 最適化', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dmedium)","lineHeight":"1.5"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dforeground)"}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--foreground);font-size:var(--wp--preset--font-size--medium);line-height:1.5">✓  <?php esc_html_e( '分析ダッシュボード', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dmedium)","lineHeight":"1.5"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dmuted)"}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--muted);font-size:var(--wp--preset--font-size--medium);line-height:1.5">—  <?php esc_html_e( 'マルチサイト管理', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dmedium)","lineHeight":"1.5"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dmuted)"}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--muted);font-size:var(--wp--preset--font-size--medium);line-height:1.5">—  <?php esc_html_e( '専任サポート', 'agent-neo' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"width":100,"className":"is-style-outline an-cta an-cta\u002d\u002dlp_pricing_starter","style":{"border":{"radius":"6px","color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dprimary)","width":"2px"},"typography":{"fontWeight":"600"},"spacing":{"padding":{"top":"0.875rem","bottom":"0.875rem"}},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dprimary)"}}} -->
<div class="wp-block-button has-custom-width wp-block-button__width-100 is-style-outline an-cta an-cta--lp_pricing_starter"><a class="wp-block-button__link has-text-color has-border-color wp-element-button" href="#" style="border-color:var(--wp--preset--color--primary);border-width:2px;border-radius:6px;color:var(--wp--preset--color--primary);padding-top:0.875rem;padding-bottom:0.875rem;font-weight:600"><?php esc_html_e( '無料で試す', 'agent-neo' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"top","style":{"border":{"radius":"8px","color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002daccent)","width":"2px"},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dbackground)"}}} -->
<div class="wp-block-column is-vertically-aligned-top has-border-color has-background" style="border-color:var(--wp--preset--color--accent);border-width:2px;border-radius:8px;background-color:var(--wp--preset--color--background);padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)"><!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|10","margin":{"bottom":"var:preset|spacing|10"}}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group" style="margin-bottom:var(--wp--preset--spacing--10)"><!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dlarge)"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dprimary)"}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--primary);font-size:var(--wp--preset--font-size--large);font-weight:700"><?php esc_html_e( 'プロ', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"border":{"radius":"4px"},"typography":{"fontWeight":"700","fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dsmall)"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dbackground)","background":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002daccent-aa)"},"spacing":{"padding":{"top":"0.2rem","bottom":"0.2rem","left":"0.6rem","right":"0.6rem"}}}} -->
<p class="has-text-color has-background" style="border-radius:4px;color:var(--wp--preset--color--background);background-color:var(--wp--preset--color--accent-aa);padding-top:0.2rem;padding-right:0.6rem;padding-bottom:0.2rem;padding-left:0.6rem;font-size:var(--wp--preset--font-size--small);font-weight:700"><?php esc_html_e( 'おすすめ', 'agent-neo' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dsmall)"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dmuted)"},"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--muted);margin-bottom:var(--wp--preset--spacing--30);font-size:var(--wp--preset--font-size--small)"><?php esc_html_e( '成長フェーズの企業・メディア向け', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"fontSize":"xxx-large","style":{"typography":{"fontWeight":"800","lineHeight":"1.0"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002daccent-aa)"},"spacing":{"margin":{"bottom":"var:preset|spacing|10"}}}} -->
<p class="has-xxx-large-font-size has-text-color" style="color:var(--wp--preset--color--accent-aa);margin-bottom:var(--wp--preset--spacing--10);font-weight:800;line-height:1.0">¥89,800<span style="font-size:1rem;font-weight:400;color:var(--wp--preset--color--foreground)"><?php esc_html_e( '/月', 'agent-neo' ); ?></span></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dsmall)"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dmuted)"},"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--muted);margin-bottom:var(--wp--preset--spacing--40);font-size:var(--wp--preset--font-size--small)"><?php esc_html_e( '（税抜）', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20","margin":{"bottom":"var:preset|spacing|40"}}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group" style="margin-bottom:var(--wp--preset--spacing--40)"><!-- wp:paragraph {"style":{"typography":{"fontWeight":"600","fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dmedium)","lineHeight":"1.5"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dforeground)"}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--foreground);font-size:var(--wp--preset--font-size--medium);font-weight:600;line-height:1.5">✓  <?php esc_html_e( 'AI 記事生成：月100本まで', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontWeight":"600","fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dmedium)","lineHeight":"1.5"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dforeground)"}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--foreground);font-size:var(--wp--preset--font-size--medium);font-weight:600;line-height:1.5">✓  <?php esc_html_e( '5 WordPressサイト接続', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontWeight":"600","fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dmedium)","lineHeight":"1.5"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dforeground)"}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--foreground);font-size:var(--wp--preset--font-size--medium);font-weight:600;line-height:1.5">✓  <?php esc_html_e( '高度 SEO 最適化 + A/B テスト', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontWeight":"600","fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dmedium)","lineHeight":"1.5"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dforeground)"}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--foreground);font-size:var(--wp--preset--font-size--medium);font-weight:600;line-height:1.5">✓  <?php esc_html_e( '詳細分析ダッシュボード', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontWeight":"600","fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dmedium)","lineHeight":"1.5"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dforeground)"}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--foreground);font-size:var(--wp--preset--font-size--medium);font-weight:600;line-height:1.5">✓  <?php esc_html_e( 'マルチサイト一元管理', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontWeight":"600","fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dmedium)","lineHeight":"1.5"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dmuted)"}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--muted);font-size:var(--wp--preset--font-size--medium);font-weight:600;line-height:1.5">—  <?php esc_html_e( '専任サポート', 'agent-neo' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"width":100,"backgroundColor":"accent-aa","textColor":"background","className":"an-cta an-cta\u002d\u002dlp_pricing_pro","style":{"border":{"radius":"6px"},"typography":{"fontWeight":"700"},"spacing":{"padding":{"top":"0.875rem","bottom":"0.875rem"}}}} -->
<div class="wp-block-button has-custom-width wp-block-button__width-100 an-cta an-cta--lp_pricing_pro"><a class="wp-block-button__link has-background-color has-accent-aa-background-color has-text-color has-background wp-element-button" href="#" style="border-radius:6px;padding-top:0.875rem;padding-bottom:0.875rem;font-weight:700"><?php esc_html_e( '無料で試す →', 'agent-neo' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"top","style":{"border":{"radius":"8px","color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dsecondary)","width":"2px"},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dsecondary)"}}} -->
<div class="wp-block-column is-vertically-aligned-top has-border-color has-background" style="border-color:var(--wp--preset--color--secondary);border-width:2px;border-radius:8px;background-color:var(--wp--preset--color--secondary);padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)"><!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dlarge)"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dprimary)"},"spacing":{"margin":{"bottom":"var:preset|spacing|10"}}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--primary);margin-bottom:var(--wp--preset--spacing--10);font-size:var(--wp--preset--font-size--large);font-weight:700"><?php esc_html_e( 'エンタープライズ', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dsmall)"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dmuted)"},"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--muted);margin-bottom:var(--wp--preset--spacing--30);font-size:var(--wp--preset--font-size--small)"><?php esc_html_e( '大規模メディア・複数ブランド運営向け', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"fontSize":"xx-large","style":{"typography":{"fontWeight":"800","lineHeight":"1.0"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dprimary)"},"spacing":{"margin":{"bottom":"var:preset|spacing|10"}}}} -->
<p class="has-xx-large-font-size has-text-color" style="color:var(--wp--preset--color--primary);margin-bottom:var(--wp--preset--spacing--10);font-weight:800;line-height:1.0"><?php esc_html_e( '要お問い合わせ', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dsmall)"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dmuted)"},"spacing":{"margin":{"bottom":"var:preset|spacing|40"}}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--muted);margin-bottom:var(--wp--preset--spacing--40);font-size:var(--wp--preset--font-size--small)"><?php esc_html_e( '規模・要件に応じた個別見積もり', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20","margin":{"bottom":"var:preset|spacing|40"}}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group" style="margin-bottom:var(--wp--preset--spacing--40)"><!-- wp:paragraph {"style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dmedium)","lineHeight":"1.5"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dforeground)"}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--foreground);font-size:var(--wp--preset--font-size--medium);line-height:1.5">✓  <?php esc_html_e( 'AI 記事生成：無制限', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dmedium)","lineHeight":"1.5"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dforeground)"}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--foreground);font-size:var(--wp--preset--font-size--medium);line-height:1.5">✓  <?php esc_html_e( '無制限サイト接続', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dmedium)","lineHeight":"1.5"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dforeground)"}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--foreground);font-size:var(--wp--preset--font-size--medium);line-height:1.5">✓  <?php esc_html_e( 'カスタム SEO 戦略設定', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dmedium)","lineHeight":"1.5"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dforeground)"}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--foreground);font-size:var(--wp--preset--font-size--medium);line-height:1.5">✓  <?php esc_html_e( 'BI 連携・カスタムレポート', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dmedium)","lineHeight":"1.5"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dforeground)"}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--foreground);font-size:var(--wp--preset--font-size--medium);line-height:1.5">✓  <?php esc_html_e( 'マルチサイト一元管理', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dmedium)","lineHeight":"1.5"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dforeground)"}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--foreground);font-size:var(--wp--preset--font-size--medium);line-height:1.5">✓  <?php esc_html_e( '専任カスタマーサクセス', 'agent-neo' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"width":100,"className":"is-style-outline an-cta an-cta\u002d\u002dlp_pricing_business","style":{"border":{"radius":"6px","color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dprimary)","width":"2px"},"typography":{"fontWeight":"600"},"spacing":{"padding":{"top":"0.875rem","bottom":"0.875rem"}},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dprimary)"}}} -->
<div class="wp-block-button has-custom-width wp-block-button__width-100 is-style-outline an-cta an-cta--lp_pricing_business"><a class="wp-block-button__link has-text-color has-border-color wp-element-button" href="#" style="border-color:var(--wp--preset--color--primary);border-width:2px;border-radius:6px;color:var(--wp--preset--color--primary);padding-top:0.875rem;padding-bottom:0.875rem;font-weight:600"><?php esc_html_e( 'お問い合わせ', 'agent-neo' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column --></div><!-- /wp:columns -->

</div>
<!-- /wp:group -->
