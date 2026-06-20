<?php
/**
 * Title: LP 課題提起（こんなお悩みありませんか）
 * Slug: agent-neo/lp-problem
 * Categories: agent-neo
 * Description: LP 課題提起セクション。3カラムの悩み一覧。白背景帯。
 * Keywords: lp, problem, pain, trouble
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

<!-- wp:group {"align":"full","className":"an-lp-problem","backgroundColor":"background","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull an-lp-problem has-background-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40)">

	<!-- wp:heading {"level":2,"textAlign":"center","style":{"typography":{"fontWeight":"700"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
	<h2 class="wp-block-heading has-text-align-center" style="font-weight:700;margin-bottom:var(--wp--preset--spacing--20)">こんなお悩みは<br>ありませんか？</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)"},"spacing":{"margin":{"bottom":"var:preset|spacing|50"}},"color":{"text":"#555555"}}} -->
	<p class="has-text-align-center" style="font-size:var(--wp--preset--font-size--medium);margin-bottom:var(--wp--preset--spacing--50);color:#555555">WordPress の運用に追われ、本来の業務に集中できていませんか。</p>
	<!-- /wp:paragraph -->

	<!-- wp:columns {"isStackedOnMobile":true,"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|30","left":"var:preset|spacing|30"}}}} -->
	<div class="wp-block-columns is-not-stacked-on-mobile" style="gap:var(--wp--preset--spacing--30)">

		<!-- wp:column {"style":{"border":{"radius":"8px","color":"var(--wp--preset--color--secondary)","width":"1px"},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"#ffffff"}}} -->
		<div class="wp-block-column" style="border-radius:8px;border:1px solid var(--wp--preset--color--secondary);padding:var(--wp--preset--spacing--40);background-color:#ffffff">
			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--large)"},"color":{"text":"var(--wp--preset--color--accent)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
			<p style="font-weight:700;font-size:var(--wp--preset--font-size--large);color:var(--wp--preset--color--accent);margin-bottom:var(--wp--preset--spacing--20)">記事更新が追いつかない</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.7"},"color":{"text":"#444444"}}} -->
			<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.7;color:#444444">ライター確保・編集・公開の工程に時間を取られ、更新頻度を上げられない。品質を維持しながら量を増やすことが困難です。</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"style":{"border":{"radius":"8px","color":"var(--wp--preset--color--secondary)","width":"1px"},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"#ffffff"}}} -->
		<div class="wp-block-column" style="border-radius:8px;border:1px solid var(--wp--preset--color--secondary);padding:var(--wp--preset--spacing--40);background-color:#ffffff">
			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--large)"},"color":{"text":"var(--wp--preset--color--accent)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
			<p style="font-weight:700;font-size:var(--wp--preset--font-size--large);color:var(--wp--preset--color--accent);margin-bottom:var(--wp--preset--spacing--20)">SEO 対策が属人的になっている</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.7"},"color":{"text":"#444444"}}} -->
			<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.7;color:#444444">担当者が変わるたびに施策がリセットされる。キーワード選定・内部リンク・タイトル最適化を一貫して続けられていません。</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"style":{"border":{"radius":"8px","color":"var(--wp--preset--color--secondary)","width":"1px"},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"#ffffff"}}} -->
		<div class="wp-block-column" style="border-radius:8px;border:1px solid var(--wp--preset--color--secondary);padding:var(--wp--preset--spacing--40);background-color:#ffffff">
			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--large)"},"color":{"text":"var(--wp--preset--color--accent)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
			<p style="font-weight:700;font-size:var(--wp--preset--font-size--large);color:var(--wp--preset--color--accent);margin-bottom:var(--wp--preset--spacing--20)">成果が数値で把握できない</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.7"},"color":{"text":"#444444"}}} -->
			<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.7;color:#444444">PV・順位・CV がバラバラのツールに散在し、施策の効果を判断するまでに時間がかかります。改善の優先順位を決めにくい状態です。</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

</div>
<!-- /wp:group -->
