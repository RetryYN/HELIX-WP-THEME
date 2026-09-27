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
	<h2 class="wp-block-heading has-text-align-center" style="font-weight:700;margin-bottom:var(--wp--preset--spacing--20)"><?php esc_html_e( 'こんなお悩みは', 'agent-neo' ); ?><br><?php esc_html_e( 'ありませんか？', 'agent-neo' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dmedium)"},"spacing":{"margin":{"bottom":"var:preset|spacing|50"}},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dforeground)"}}} -->
	<p class="has-text-align-center" style="font-size:var(--wp--preset--font-size--medium);margin-bottom:var(--wp--preset--spacing--50);color:var(--wp--preset--color--foreground)"><?php esc_html_e( 'WordPress の運用に追われ、本来の業務に集中できていませんか。', 'agent-neo' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:columns {"isStackedOnMobile":true,"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|30","left":"var:preset|spacing|30"}}}} -->
	<div class="wp-block-columns">

		<!-- wp:column {"style":{"border":{"radius":"8px","color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dsecondary)","width":"1px"},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dbackground)"}}} -->
		<div class="wp-block-column has-border-color has-background" style="border-radius:8px;border-color:var(--wp--preset--color--secondary);border-width:1px;background-color:var(--wp--preset--color--background);padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dlarge)"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002daccent-aa)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
			<p style="font-weight:700;font-size:var(--wp--preset--font-size--large);color:var(--wp--preset--color--accent-aa);margin-bottom:var(--wp--preset--spacing--20)"><?php esc_html_e( '記事更新が追いつかない', 'agent-neo' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dmedium)","lineHeight":"1.7"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dforeground)"}}} -->
			<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.7;color:var(--wp--preset--color--foreground)"><?php esc_html_e( 'ライター確保・編集・公開の工程に時間を取られ、更新頻度を上げられない。品質を維持しながら量を増やすことが困難です。', 'agent-neo' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"style":{"border":{"radius":"8px","color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dsecondary)","width":"1px"},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dbackground)"}}} -->
		<div class="wp-block-column has-border-color has-background" style="border-radius:8px;border-color:var(--wp--preset--color--secondary);border-width:1px;background-color:var(--wp--preset--color--background);padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dlarge)"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002daccent-aa)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
			<p style="font-weight:700;font-size:var(--wp--preset--font-size--large);color:var(--wp--preset--color--accent-aa);margin-bottom:var(--wp--preset--spacing--20)"><?php esc_html_e( 'SEO 対策が属人的になっている', 'agent-neo' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dmedium)","lineHeight":"1.7"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dforeground)"}}} -->
			<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.7;color:var(--wp--preset--color--foreground)"><?php esc_html_e( '担当者が変わるたびに施策がリセットされる。キーワード選定・内部リンク・タイトル最適化を一貫して続けられていません。', 'agent-neo' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"style":{"border":{"radius":"8px","color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dsecondary)","width":"1px"},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dbackground)"}}} -->
		<div class="wp-block-column has-border-color has-background" style="border-radius:8px;border-color:var(--wp--preset--color--secondary);border-width:1px;background-color:var(--wp--preset--color--background);padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--40)">
			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dlarge)"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002daccent-aa)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
			<p style="font-weight:700;font-size:var(--wp--preset--font-size--large);color:var(--wp--preset--color--accent-aa);margin-bottom:var(--wp--preset--spacing--20)"><?php esc_html_e( '成果が数値で把握できない', 'agent-neo' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dmedium)","lineHeight":"1.7"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dforeground)"}}} -->
			<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.7;color:var(--wp--preset--color--foreground)"><?php esc_html_e( 'PV・順位・CV がバラバラのツールに散在し、施策の効果を判断するまでに時間がかかります。改善の優先順位を決めにくい状態です。', 'agent-neo' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

</div>
<!-- /wp:group -->
