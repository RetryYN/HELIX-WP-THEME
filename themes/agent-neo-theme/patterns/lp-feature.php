<?php
/**
 * Title: LP 機能一覧（主要機能）
 * Slug: agent-neo/lp-feature
 * Categories: agent-neo
 * Description: LP 機能セクション。4カラム・オレンジアイコン番号＋見出し＋説明テキスト。白背景帯。
 * Keywords: lp, feature, function, spec, capability
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

<!-- wp:group {"align":"full","className":"an-lp-feature","backgroundColor":"background","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull an-lp-feature has-background-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40)">

	<!-- wp:heading {"level":2,"textAlign":"center","style":{"typography":{"fontWeight":"700"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
	<h2 class="wp-block-heading has-text-align-center" style="font-weight:700;margin-bottom:var(--wp--preset--spacing--20)"><?php esc_html_e( 'AGENT NEO の主要機能', 'agent-neo' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)"},"spacing":{"margin":{"bottom":"var:preset|spacing|50"}},"color":{"text":"var(--wp--preset--color--foreground)"}}} -->
	<p class="has-text-align-center" style="font-size:var(--wp--preset--font-size--medium);margin-bottom:var(--wp--preset--spacing--50);color:var(--wp--preset--color--foreground)"><?php esc_html_e( 'WordPress 運用に必要なすべての工程を、ひとつのシステムでカバーします。', 'agent-neo' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:columns {"isStackedOnMobile":true,"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|30","left":"var:preset|spacing|30"}}}} --><div class="wp-block-columns"><!-- wp:column {"style":{"border":{"radius":"8px","top":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002daccent)","width":"3px"},"bottom":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dsecondary)","width":"1px"},"left":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dsecondary)","width":"1px"},"right":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dsecondary)","width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dbackground)"}}} -->
<div class="wp-block-column has-background" style="border-radius:8px;border-top-color:var(--wp--preset--color--accent);border-top-width:3px;border-right-color:var(--wp--preset--color--secondary);border-right-width:1px;border-bottom-color:var(--wp--preset--color--secondary);border-bottom-width:1px;border-left-color:var(--wp--preset--color--secondary);border-left-width:1px;background-color:var(--wp--preset--color--background);padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)"><!-- wp:paragraph {"style":{"typography":{"fontWeight":"800","fontSize":"2rem","lineHeight":"1.0"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002daccent-aa)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--accent-aa);margin-bottom:var(--wp--preset--spacing--20);font-size:2rem;font-weight:800;line-height:1.0">F1</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dlarge)"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dprimary)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--primary);margin-bottom:var(--wp--preset--spacing--20);font-size:var(--wp--preset--font-size--large);font-weight:700"><?php esc_html_e( 'AI コンテンツ生成', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dmedium)","lineHeight":"1.7"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dforeground)"}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--foreground);font-size:var(--wp--preset--font-size--medium);line-height:1.7"><?php esc_html_e( 'キーワード選定・構成・本文・タイトル・メタ説明を一気通貫で生成。独自の品質スコアで基準を下回る記事は自動修正します。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"border":{"radius":"8px","top":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002daccent)","width":"3px"},"bottom":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dsecondary)","width":"1px"},"left":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dsecondary)","width":"1px"},"right":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dsecondary)","width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dbackground)"}}} -->
<div class="wp-block-column has-background" style="border-radius:8px;border-top-color:var(--wp--preset--color--accent);border-top-width:3px;border-right-color:var(--wp--preset--color--secondary);border-right-width:1px;border-bottom-color:var(--wp--preset--color--secondary);border-bottom-width:1px;border-left-color:var(--wp--preset--color--secondary);border-left-width:1px;background-color:var(--wp--preset--color--background);padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)"><!-- wp:paragraph {"style":{"typography":{"fontWeight":"800","fontSize":"2rem","lineHeight":"1.0"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002daccent-aa)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--accent-aa);margin-bottom:var(--wp--preset--spacing--20);font-size:2rem;font-weight:800;line-height:1.0">F2</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dlarge)"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dprimary)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--primary);margin-bottom:var(--wp--preset--spacing--20);font-size:var(--wp--preset--font-size--large);font-weight:700"><?php esc_html_e( '継続 SEO 最適化', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dmedium)","lineHeight":"1.7"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dforeground)"}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--foreground);font-size:var(--wp--preset--font-size--medium);line-height:1.7"><?php esc_html_e( '検索順位・CTR を24時間監視。タイトル A/B テスト・内部リンク補強・古記事のリライトを自動スケジュールして継続的に改善します。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"border":{"radius":"8px","top":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002daccent)","width":"3px"},"bottom":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dsecondary)","width":"1px"},"left":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dsecondary)","width":"1px"},"right":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dsecondary)","width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dbackground)"}}} -->
<div class="wp-block-column has-background" style="border-radius:8px;border-top-color:var(--wp--preset--color--accent);border-top-width:3px;border-right-color:var(--wp--preset--color--secondary);border-right-width:1px;border-bottom-color:var(--wp--preset--color--secondary);border-bottom-width:1px;border-left-color:var(--wp--preset--color--secondary);border-left-width:1px;background-color:var(--wp--preset--color--background);padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)"><!-- wp:paragraph {"style":{"typography":{"fontWeight":"800","fontSize":"2rem","lineHeight":"1.0"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002daccent-aa)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--accent-aa);margin-bottom:var(--wp--preset--spacing--20);font-size:2rem;font-weight:800;line-height:1.0">F3</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dlarge)"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dprimary)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--primary);margin-bottom:var(--wp--preset--spacing--20);font-size:var(--wp--preset--font-size--large);font-weight:700"><?php esc_html_e( '統合分析ダッシュボード', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dmedium)","lineHeight":"1.7"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dforeground)"}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--foreground);font-size:var(--wp--preset--font-size--medium);line-height:1.7"><?php esc_html_e( 'PV・順位・CV・生成ステータスをひとつの画面に集約。ツールを横断する手間なく、施策の効果を即座に判断できます。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"border":{"radius":"8px","top":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002daccent)","width":"3px"},"bottom":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dsecondary)","width":"1px"},"left":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dsecondary)","width":"1px"},"right":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dsecondary)","width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dbackground)"}}} -->
<div class="wp-block-column has-background" style="border-radius:8px;border-top-color:var(--wp--preset--color--accent);border-top-width:3px;border-right-color:var(--wp--preset--color--secondary);border-right-width:1px;border-bottom-color:var(--wp--preset--color--secondary);border-bottom-width:1px;border-left-color:var(--wp--preset--color--secondary);border-left-width:1px;background-color:var(--wp--preset--color--background);padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)"><!-- wp:paragraph {"style":{"typography":{"fontWeight":"800","fontSize":"2rem","lineHeight":"1.0"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002daccent-aa)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--accent-aa);margin-bottom:var(--wp--preset--spacing--20);font-size:2rem;font-weight:800;line-height:1.0">F4</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dlarge)"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dprimary)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--primary);margin-bottom:var(--wp--preset--spacing--20);font-size:var(--wp--preset--font-size--large);font-weight:700"><?php esc_html_e( 'WP シームレス連携', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dmedium)","lineHeight":"1.7"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dforeground)"}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--foreground);font-size:var(--wp--preset--font-size--medium);line-height:1.7"><?php esc_html_e( '専用プラグインをインストールするだけで接続完了。既存テーマ・プラグインへの影響ゼロで、REST API 経由で安全に連携します。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div><!-- /wp:columns -->

</div>
<!-- /wp:group -->
