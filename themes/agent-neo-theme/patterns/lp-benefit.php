<?php
/**
 * Title: LP 提供価値（選ばれる理由）
 * Slug: agent-neo/lp-benefit
 * Categories: agent-neo
 * Description: LP 価値・特徴セクション。4カラム・オレンジ見出し＋説明テキスト。白背景帯。
 * Keywords: lp, benefit, feature, value, reason
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

<!-- wp:group {"align":"full","className":"an-lp-benefit","backgroundColor":"background","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull an-lp-benefit has-background-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40)">

	<!-- wp:heading {"level":2,"textAlign":"center","style":{"typography":{"fontWeight":"700"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
	<h2 class="wp-block-heading has-text-align-center" style="font-weight:700;margin-bottom:var(--wp--preset--spacing--20)">AGENT NEO が選ばれる理由</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)"},"spacing":{"margin":{"bottom":"var:preset|spacing|50"}},"color":{"text":"#555555"}}} -->
	<p class="has-text-align-center" style="font-size:var(--wp--preset--font-size--medium);margin-bottom:var(--wp--preset--spacing--50);color:#555555">他のツールでは実現できない、4つの核心価値をご紹介します。</p>
	<!-- /wp:paragraph -->

	<!-- wp:columns {"isStackedOnMobile":true,"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|30","left":"var:preset|spacing|30"}}}} -->
	<div class="wp-block-columns" style="gap:var(--wp--preset--spacing--30)">

		<!-- 価値 1 -->
		<!-- wp:column {"style":{"border":{"radius":"8px","color":"var(--wp--preset--color--secondary)","width":"1px"},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"#ffffff"}}} -->
		<div class="wp-block-column" style="border-radius:8px;border:1px solid var(--wp--preset--color--secondary);padding:var(--wp--preset--spacing--40);background-color:#ffffff">
			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"800","fontSize":"2rem","lineHeight":"1.0"},"color":{"text":"var(--wp--preset--color--accent)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
			<p style="font-weight:800;font-size:2rem;line-height:1.0;color:var(--wp--preset--color--accent);margin-bottom:var(--wp--preset--spacing--20)">01</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--large)"},"color":{"text":"var(--wp--preset--color--primary)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
			<p style="font-weight:700;font-size:var(--wp--preset--font-size--large);color:var(--wp--preset--color--primary);margin-bottom:var(--wp--preset--spacing--20)">完全自動・介在不要</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.7"},"color":{"text":"#444444"}}} -->
			<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.7;color:#444444">キーワード調査から公開・分析まで、担当者がいなくてもシステムが自律的に動き続けます。休日・深夜も止まりません。</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- 価値 2 -->
		<!-- wp:column {"style":{"border":{"radius":"8px","color":"var(--wp--preset--color--secondary)","width":"1px"},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"#ffffff"}}} -->
		<div class="wp-block-column" style="border-radius:8px;border:1px solid var(--wp--preset--color--secondary);padding:var(--wp--preset--spacing--40);background-color:#ffffff">
			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"800","fontSize":"2rem","lineHeight":"1.0"},"color":{"text":"var(--wp--preset--color--accent)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
			<p style="font-weight:800;font-size:2rem;line-height:1.0;color:var(--wp--preset--color--accent);margin-bottom:var(--wp--preset--spacing--20)">02</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--large)"},"color":{"text":"var(--wp--preset--color--primary)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
			<p style="font-weight:700;font-size:var(--wp--preset--font-size--large);color:var(--wp--preset--color--primary);margin-bottom:var(--wp--preset--spacing--20)">品質と量を両立</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.7"},"color":{"text":"#444444"}}} -->
			<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.7;color:#444444">AI による品質チェックと自動修正で、ライターへの発注コストを抑えながら月100本超の更新を維持できます。</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- 価値 3 -->
		<!-- wp:column {"style":{"border":{"radius":"8px","color":"var(--wp--preset--color--secondary)","width":"1px"},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"#ffffff"}}} -->
		<div class="wp-block-column" style="border-radius:8px;border:1px solid var(--wp--preset--color--secondary);padding:var(--wp--preset--spacing--40);background-color:#ffffff">
			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"800","fontSize":"2rem","lineHeight":"1.0"},"color":{"text":"var(--wp--preset--color--accent)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
			<p style="font-weight:800;font-size:2rem;line-height:1.0;color:var(--wp--preset--color--accent);margin-bottom:var(--wp--preset--spacing--20)">03</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--large)"},"color":{"text":"var(--wp--preset--color--primary)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
			<p style="font-weight:700;font-size:var(--wp--preset--font-size--large);color:var(--wp--preset--color--primary);margin-bottom:var(--wp--preset--spacing--20)">既存 WP にそのまま導入</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.7"},"color":{"text":"#444444"}}} -->
			<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.7;color:#444444">プラグインをインストールするだけで連携完了。テーマやカスタマイズを変える必要はなく、既存サイト資産を活かせます。</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- 価値 4 -->
		<!-- wp:column {"style":{"border":{"radius":"8px","color":"var(--wp--preset--color--secondary)","width":"1px"},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"#ffffff"}}} -->
		<div class="wp-block-column" style="border-radius:8px;border:1px solid var(--wp--preset--color--secondary);padding:var(--wp--preset--spacing--40);background-color:#ffffff">
			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"800","fontSize":"2rem","lineHeight":"1.0"},"color":{"text":"var(--wp--preset--color--accent)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
			<p style="font-weight:800;font-size:2rem;line-height:1.0;color:var(--wp--preset--color--accent);margin-bottom:var(--wp--preset--spacing--20)">04</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--large)"},"color":{"text":"var(--wp--preset--color--primary)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
			<p style="font-weight:700;font-size:var(--wp--preset--font-size--large);color:var(--wp--preset--color--primary);margin-bottom:var(--wp--preset--spacing--20)">成果をデータで証明</p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.7"},"color":{"text":"#444444"}}} -->
			<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.7;color:#444444">順位変動・流入数・CV 率をリアルタイムで計測。どの施策が効いているかを可視化し、戦略の改善に直結させます。</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

</div>
<!-- /wp:group -->
