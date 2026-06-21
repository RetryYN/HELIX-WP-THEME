<?php
/**
 * Title: LP 活用シーン（業種・用途別ユースケース）
 * Slug: agent-neo/lp-use-case
 * Categories: agent-neo
 * Description: LP 活用シーンセクション。業種・用途カード3例・Before→After 形式。secondary 背景帯。
 * Keywords: lp, use-case, usecase, example, case, scene
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

<!-- wp:group {"align":"full","className":"an-lp-use-case","backgroundColor":"secondary","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull an-lp-use-case has-secondary-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40)">

	<!-- wp:heading {"level":2,"textAlign":"center","style":{"typography":{"fontWeight":"700"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
	<h2 class="wp-block-heading has-text-align-center" style="font-weight:700;margin-bottom:var(--wp--preset--spacing--20)"><?php esc_html_e( 'こんな現場で使われています', 'agent-neo' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)"},"spacing":{"margin":{"bottom":"var:preset|spacing|50"}},"color":{"text":"#555555"}}} -->
	<p class="has-text-align-center" style="font-size:var(--wp--preset--font-size--medium);margin-bottom:var(--wp--preset--spacing--50);color:#555555"><?php esc_html_e( '業種・規模を問わず、WordPress を運用するあらゆる組織で導入が進んでいます。', 'agent-neo' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:columns {"isStackedOnMobile":true,"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|30","left":"var:preset|spacing|30"}}}} -->
	<div class="wp-block-columns is-not-stacked-on-mobile" style="gap:var(--wp--preset--spacing--30)">

		<!-- ユースケース 1: EC・通販 -->
		<!-- wp:column {"style":{"border":{"radius":"8px","color":"var(--wp--preset--color--background)","width":"1px"},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"#ffffff"}}} -->
		<div class="wp-block-column" style="border-radius:8px;border:1px solid var(--wp--preset--color--background);padding:var(--wp--preset--spacing--40);background-color:#ffffff">

			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--large)"},"color":{"text":"var(--wp--preset--color--accent)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
			<p style="font-weight:700;font-size:var(--wp--preset--font-size--large);color:var(--wp--preset--color--accent);margin-bottom:var(--wp--preset--spacing--20)"><?php esc_html_e( 'EC・通販サイト', 'agent-neo' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:group {"style":{"border":{"radius":"6px"},"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}},"color":{"background":"#fff3e6"}},"layout":{"type":"flex","orientation":"vertical"}} -->
			<div class="wp-block-group" style="border-radius:6px;padding:var(--wp--preset--spacing--20) var(--wp--preset--spacing--30);background-color:#fff3e6;margin-bottom:var(--wp--preset--spacing--20)">
				<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--small)"},"color":{"text":"#888888"}}} -->
				<p style="font-weight:700;font-size:var(--wp--preset--font-size--small);color:#888888">BEFORE</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.7"},"color":{"text":"#555555"}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.7;color:#555555"><?php esc_html_e( '商品レビュー・使い方コラムをライターに外注。月4〜5本が限界で、商品数に対してコンテンツが追いついていなかった。', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

			<!-- wp:group {"style":{"border":{"radius":"6px","left":{"color":"var(--wp--preset--color--accent)","width":"3px"}},"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}},"color":{"background":"#ffffff"}},"layout":{"type":"flex","orientation":"vertical"}} -->
			<div class="wp-block-group" style="border-radius:6px;border-left:3px solid var(--wp--preset--color--accent);padding:var(--wp--preset--spacing--20) var(--wp--preset--spacing--30);background-color:#ffffff">
				<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--small)"},"color":{"text":"var(--wp--preset--color--accent)"}}} -->
				<p style="font-weight:700;font-size:var(--wp--preset--font-size--small);color:var(--wp--preset--color--accent)">AFTER</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.7"},"color":{"text":"#333333"}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.7;color:#333333"><?php esc_html_e( '月60本以上の商品コンテンツを自動生成・公開。オーガニック流入が6ヶ月で2.4倍に増加し、広告費を30%削減。', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

		</div>
		<!-- /wp:column -->

		<!-- ユースケース 2: 専門メディア -->
		<!-- wp:column {"style":{"border":{"radius":"8px","color":"var(--wp--preset--color--background)","width":"1px"},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"#ffffff"}}} -->
		<div class="wp-block-column" style="border-radius:8px;border:1px solid var(--wp--preset--color--background);padding:var(--wp--preset--spacing--40);background-color:#ffffff">

			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--large)"},"color":{"text":"var(--wp--preset--color--accent)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
			<p style="font-weight:700;font-size:var(--wp--preset--font-size--large);color:var(--wp--preset--color--accent);margin-bottom:var(--wp--preset--spacing--20)"><?php esc_html_e( '専門メディア・情報サイト', 'agent-neo' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:group {"style":{"border":{"radius":"6px"},"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}},"color":{"background":"#fff3e6"}},"layout":{"type":"flex","orientation":"vertical"}} -->
			<div class="wp-block-group" style="border-radius:6px;padding:var(--wp--preset--spacing--20) var(--wp--preset--spacing--30);background-color:#fff3e6;margin-bottom:var(--wp--preset--spacing--20)">
				<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--small)"},"color":{"text":"#888888"}}} -->
				<p style="font-weight:700;font-size:var(--wp--preset--font-size--small);color:#888888">BEFORE</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.7"},"color":{"text":"#555555"}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.7;color:#555555"><?php esc_html_e( '編集部2名が記事制作を担当。更新が週2〜3本に限られ、競合メディアに検索順位を奪われ続けていた。', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

			<!-- wp:group {"style":{"border":{"radius":"6px","left":{"color":"var(--wp--preset--color--accent)","width":"3px"}},"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}},"color":{"background":"#ffffff"}},"layout":{"type":"flex","orientation":"vertical"}} -->
			<div class="wp-block-group" style="border-radius:6px;border-left:3px solid var(--wp--preset--color--accent);padding:var(--wp--preset--spacing--20) var(--wp--preset--spacing--30);background-color:#ffffff">
				<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--small)"},"color":{"text":"var(--wp--preset--color--accent)"}}} -->
				<p style="font-weight:700;font-size:var(--wp--preset--font-size--small);color:var(--wp--preset--color--accent)">AFTER</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.7"},"color":{"text":"#333333"}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.7;color:#333333"><?php esc_html_e( '同じ体制で週20本超の更新を実現。編集部は企画・監修に集中でき、総PVが4ヶ月で1.8倍に回復。', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

		</div>
		<!-- /wp:column -->

		<!-- ユースケース 3: BtoB SaaS -->
		<!-- wp:column {"style":{"border":{"radius":"8px","color":"var(--wp--preset--color--background)","width":"1px"},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"#ffffff"}}} -->
		<div class="wp-block-column" style="border-radius:8px;border:1px solid var(--wp--preset--color--background);padding:var(--wp--preset--spacing--40);background-color:#ffffff">

			<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--large)"},"color":{"text":"var(--wp--preset--color--accent)"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
			<p style="font-weight:700;font-size:var(--wp--preset--font-size--large);color:var(--wp--preset--color--accent);margin-bottom:var(--wp--preset--spacing--20)"><?php esc_html_e( 'BtoB SaaS・コーポレートサイト', 'agent-neo' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:group {"style":{"border":{"radius":"6px"},"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}},"color":{"background":"#fff3e6"}},"layout":{"type":"flex","orientation":"vertical"}} -->
			<div class="wp-block-group" style="border-radius:6px;padding:var(--wp--preset--spacing--20) var(--wp--preset--spacing--30);background-color:#fff3e6;margin-bottom:var(--wp--preset--spacing--20)">
				<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--small)"},"color":{"text":"#888888"}}} -->
				<p style="font-weight:700;font-size:var(--wp--preset--font-size--small);color:#888888">BEFORE</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.7"},"color":{"text":"#555555"}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.7;color:#555555"><?php esc_html_e( 'マーケ担当1名が兼任でブログを運営。月1〜2本しか公開できず、SEOによるリード獲得がほぼ機能していなかった。', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

			<!-- wp:group {"style":{"border":{"radius":"6px","left":{"color":"var(--wp--preset--color--accent)","width":"3px"}},"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}},"color":{"background":"#ffffff"}},"layout":{"type":"flex","orientation":"vertical"}} -->
			<div class="wp-block-group" style="border-radius:6px;border-left:3px solid var(--wp--preset--color--accent);padding:var(--wp--preset--spacing--20) var(--wp--preset--spacing--30);background-color:#ffffff">
				<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--small)"},"color":{"text":"var(--wp--preset--color--accent)"}}} -->
				<p style="font-weight:700;font-size:var(--wp--preset--font-size--small);color:var(--wp--preset--color--accent)">AFTER</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.7"},"color":{"text":"#333333"}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.7;color:#333333"><?php esc_html_e( '月15本のコラム公開を自動化。担当者は戦略立案に集中でき、問い合わせ経路のSEO比率が8ヶ月で12%→38%に上昇。', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

</div>
<!-- /wp:group -->
