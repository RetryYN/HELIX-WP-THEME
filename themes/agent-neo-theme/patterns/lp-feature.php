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
	<h2 class="wp-block-heading has-text-align-center" style="font-weight:700;margin-bottom:var(--wp--preset--spacing--20)">AGENT NEO の主要機能</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)"},"spacing":{"margin":{"bottom":"var:preset|spacing|50"}},"color":{"text":"#555555"}}} -->
	<p class="has-text-align-center" style="font-size:var(--wp--preset--font-size--medium);margin-bottom:var(--wp--preset--spacing--50);color:#555555">WordPress 運用に必要なすべての工程を、ひとつのシステムでカバーします。</p>
	<!-- /wp:paragraph -->

	<!-- wp:columns {"isStackedOnMobile":true,"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|30","left":"var:preset|spacing|30"}}}} -->
	<div class="wp-block-columns is-not-stacked-on-mobile" style="gap:var(--wp--preset--spacing--30)">

		<!-- 機能 1 -->
		<!-- wp:column {"style":{"border":{"radius":"8px","top":{"color":"var(--wp--preset--color--accent)","width":"3px"},"bottom":{"color":"var(--wp--preset--color--secondary)","width":"1px"},"left":{"color":"var(--wp--preset--color--secondary)","width":"1px"},"right":{"color":"var(--wp--preset--color--secondary)","width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"#ffffff"}}} -->
		<div class="wp-block-column" style="border-radius:8px;border-top:3px solid var(--wp--preset--color--accent);border-bottom:1px solid var(--wp--preset--color--secondary);border-left:1px solid var(--wp--preset--color--secondary);border-right:1px solid var(--wp--preset--color--secondary);padding:var(--wp--preset--spacing--40);background-color:#ffffff">
			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"800","fontSize":"2rem","lineHeight":"1.0"},"color":{"text":"var(--wp--preset--color--accent)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
			<p style="font-weight:800;font-size:2rem;line-height:1.0;color:var(--wp--preset--color--accent);margin-bottom:var(--wp--preset--spacing--20)">F1</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--large)"},"color":{"text":"var(--wp--preset--color--primary)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
			<p style="font-weight:700;font-size:var(--wp--preset--font-size--large);color:var(--wp--preset--color--primary);margin-bottom:var(--wp--preset--spacing--20)">AI コンテンツ生成</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.7"},"color":{"text":"#444444"}}} -->
			<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.7;color:#444444">キーワード選定・構成・本文・タイトル・メタ説明を一気通貫で生成。独自の品質スコアで基準を下回る記事は自動修正します。</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- 機能 2 -->
		<!-- wp:column {"style":{"border":{"radius":"8px","top":{"color":"var(--wp--preset--color--accent)","width":"3px"},"bottom":{"color":"var(--wp--preset--color--secondary)","width":"1px"},"left":{"color":"var(--wp--preset--color--secondary)","width":"1px"},"right":{"color":"var(--wp--preset--color--secondary)","width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"#ffffff"}}} -->
		<div class="wp-block-column" style="border-radius:8px;border-top:3px solid var(--wp--preset--color--accent);border-bottom:1px solid var(--wp--preset--color--secondary);border-left:1px solid var(--wp--preset--color--secondary);border-right:1px solid var(--wp--preset--color--secondary);padding:var(--wp--preset--spacing--40);background-color:#ffffff">
			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"800","fontSize":"2rem","lineHeight":"1.0"},"color":{"text":"var(--wp--preset--color--accent)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
			<p style="font-weight:800;font-size:2rem;line-height:1.0;color:var(--wp--preset--color--accent);margin-bottom:var(--wp--preset--spacing--20)">F2</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--large)"},"color":{"text":"var(--wp--preset--color--primary)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
			<p style="font-weight:700;font-size:var(--wp--preset--font-size--large);color:var(--wp--preset--color--primary);margin-bottom:var(--wp--preset--spacing--20)">継続 SEO 最適化</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.7"},"color":{"text":"#444444"}}} -->
			<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.7;color:#444444">検索順位・CTR を24時間監視。タイトル A/B テスト・内部リンク補強・古記事のリライトを自動スケジュールして継続的に改善します。</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- 機能 3 -->
		<!-- wp:column {"style":{"border":{"radius":"8px","top":{"color":"var(--wp--preset--color--accent)","width":"3px"},"bottom":{"color":"var(--wp--preset--color--secondary)","width":"1px"},"left":{"color":"var(--wp--preset--color--secondary)","width":"1px"},"right":{"color":"var(--wp--preset--color--secondary)","width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"#ffffff"}}} -->
		<div class="wp-block-column" style="border-radius:8px;border-top:3px solid var(--wp--preset--color--accent);border-bottom:1px solid var(--wp--preset--color--secondary);border-left:1px solid var(--wp--preset--color--secondary);border-right:1px solid var(--wp--preset--color--secondary);padding:var(--wp--preset--spacing--40);background-color:#ffffff">
			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"800","fontSize":"2rem","lineHeight":"1.0"},"color":{"text":"var(--wp--preset--color--accent)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
			<p style="font-weight:800;font-size:2rem;line-height:1.0;color:var(--wp--preset--color--accent);margin-bottom:var(--wp--preset--spacing--20)">F3</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--large)"},"color":{"text":"var(--wp--preset--color--primary)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
			<p style="font-weight:700;font-size:var(--wp--preset--font-size--large);color:var(--wp--preset--color--primary);margin-bottom:var(--wp--preset--spacing--20)">統合分析ダッシュボード</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.7"},"color":{"text":"#444444"}}} -->
			<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.7;color:#444444">PV・順位・CV・生成ステータスをひとつの画面に集約。ツールを横断する手間なく、施策の効果を即座に判断できます。</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- 機能 4 -->
		<!-- wp:column {"style":{"border":{"radius":"8px","top":{"color":"var(--wp--preset--color--accent)","width":"3px"},"bottom":{"color":"var(--wp--preset--color--secondary)","width":"1px"},"left":{"color":"var(--wp--preset--color--secondary)","width":"1px"},"right":{"color":"var(--wp--preset--color--secondary)","width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"#ffffff"}}} -->
		<div class="wp-block-column" style="border-radius:8px;border-top:3px solid var(--wp--preset--color--accent);border-bottom:1px solid var(--wp--preset--color--secondary);border-left:1px solid var(--wp--preset--color--secondary);border-right:1px solid var(--wp--preset--color--secondary);padding:var(--wp--preset--spacing--40);background-color:#ffffff">
			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"800","fontSize":"2rem","lineHeight":"1.0"},"color":{"text":"var(--wp--preset--color--accent)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
			<p style="font-weight:800;font-size:2rem;line-height:1.0;color:var(--wp--preset--color--accent);margin-bottom:var(--wp--preset--spacing--20)">F4</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--large)"},"color":{"text":"var(--wp--preset--color--primary)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
			<p style="font-weight:700;font-size:var(--wp--preset--font-size--large);color:var(--wp--preset--color--primary);margin-bottom:var(--wp--preset--spacing--20)">WP シームレス連携</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.7"},"color":{"text":"#444444"}}} -->
			<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.7;color:#444444">専用プラグインをインストールするだけで接続完了。既存テーマ・プラグインへの影響ゼロで、REST API 経由で安全に連携します。</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

</div>
<!-- /wp:group -->
