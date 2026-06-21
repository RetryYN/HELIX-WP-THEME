<?php
/**
 * Title: LP 解決提示（AGENT NEO ができること）
 * Slug: agent-neo/lp-solution
 * Categories: agent-neo
 * Description: LP 解決提示セクション。テキスト + 図版プレースホルダの 2カラム。secondary 背景帯。
 * Keywords: lp, solution, feature, benefit
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

<!-- wp:group {"align":"full","className":"an-lp-solution","backgroundColor":"secondary","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull an-lp-solution has-secondary-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40)">

	<!-- wp:heading {"level":2,"textAlign":"center","style":{"typography":{"fontWeight":"700"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
	<h2 class="wp-block-heading has-text-align-center" style="font-weight:700;margin-bottom:var(--wp--preset--spacing--20)"><?php esc_html_e( 'AGENT NEO が', 'agent-neo' ); ?><br><?php esc_html_e( '運用を丸ごと引き受けます', 'agent-neo' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)"},"spacing":{"margin":{"bottom":"var:preset|spacing|50"}},"color":{"text":"#555555"}}} -->
	<p class="has-text-align-center" style="font-size:var(--wp--preset--font-size--medium);margin-bottom:var(--wp--preset--spacing--50);color:#555555"><?php esc_html_e( 'AI が戦略を立て、コンテンツを生成し、SEO を最適化する。', 'agent-neo' ); ?><br><?php esc_html_e( 'WordPress 運用の全工程をひとつのシステムで完結させます。', 'agent-neo' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:columns {"isStackedOnMobile":true,"verticalAlignment":"center","style":{"spacing":{"blockGap":{"top":"var:preset|spacing|50","left":"var:preset|spacing|50"}}}} -->
	<div class="wp-block-columns is-not-stacked-on-mobile are-vertically-aligned-center" style="gap:var(--wp--preset--spacing--50)">

		<!-- 左カラム: テキスト群 -->
		<!-- wp:column {"verticalAlignment":"center"} -->
		<div class="wp-block-column is-vertically-aligned-center">

			<!-- 機能項目 1 -->
			<!-- wp:group {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|10"}},"layout":{"type":"flex","orientation":"vertical"}} -->
			<div class="wp-block-group" style="margin-bottom:var(--wp--preset--spacing--40);gap:var(--wp--preset--spacing--10)">
				<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--large)"},"color":{"text":"var(--wp--preset--color--accent-aa)"}}} -->
				<p style="font-weight:700;font-size:var(--wp--preset--font-size--large);color:var(--wp--preset--color--accent-aa)"><?php esc_html_e( '01 — 自動コンテンツ生成', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.7"},"color":{"text":"#333333"}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.7;color:#333333"><?php esc_html_e( 'キーワード選定から構成案・本文・タイトル・メタ説明まで AI が一貫して生成。編集者はレビューと承認のみに集中できます。', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

			<!-- 機能項目 2 -->
			<!-- wp:group {"style":{"spacing":{"margin":{"bottom":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|10"}},"layout":{"type":"flex","orientation":"vertical"}} -->
			<div class="wp-block-group" style="margin-bottom:var(--wp--preset--spacing--40);gap:var(--wp--preset--spacing--10)">
				<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--large)"},"color":{"text":"var(--wp--preset--color--accent-aa)"}}} -->
				<p style="font-weight:700;font-size:var(--wp--preset--font-size--large);color:var(--wp--preset--color--accent-aa)"><?php esc_html_e( '02 — 継続的 SEO 最適化', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.7"},"color":{"text":"#333333"}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.7;color:#333333"><?php esc_html_e( '検索順位・クリック率を継続監視し、タイトル改善・内部リンク最適化・古い記事のリライトを自動スケジュールします。', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

			<!-- 機能項目 3 -->
			<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|10"}},"layout":{"type":"flex","orientation":"vertical"}} -->
			<div class="wp-block-group" style="gap:var(--wp--preset--spacing--10)">
				<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--large)"},"color":{"text":"var(--wp--preset--color--accent-aa)"}}} -->
				<p style="font-weight:700;font-size:var(--wp--preset--font-size--large);color:var(--wp--preset--color--accent-aa)"><?php esc_html_e( '03 — 一元管理ダッシュボード', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.7"},"color":{"text":"#333333"}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.7;color:#333333"><?php esc_html_e( 'PV・順位・CV・生成ステータスをひとつの画面で把握。施策の効果を即座に判断し、戦略に反映できます。', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->

		</div>
		<!-- /wp:column -->

		<!-- 右カラム: 図版プレースホルダ -->
		<!-- wp:column {"verticalAlignment":"center"} -->
		<div class="wp-block-column is-vertically-aligned-center">
			<!-- wp:group {"style":{"border":{"radius":"12px"},"dimensions":{"minHeight":"360px"},"color":{"background":"var(--wp--preset--color--primary)"},"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
			<div class="wp-block-group" style="border-radius:12px;min-height:360px;background-color:var(--wp--preset--color--primary);padding:var(--wp--preset--spacing--50);display:flex;flex-direction:column;align-items:center;justify-content:center">
				<!-- wp:paragraph {"align":"center","style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--xx-large)","lineHeight":"1.2"},"color":{"text":"#ffffff"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
				<p class="has-text-align-center" style="font-weight:700;font-size:var(--wp--preset--font-size--xx-large);line-height:1.2;color:#ffffff;margin-bottom:var(--wp--preset--spacing--20)">AGENT NEO</p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.6"},"color":{"text":"rgba(255,255,255,0.75)"}}} -->
				<p class="has-text-align-center" style="font-size:var(--wp--preset--font-size--medium);line-height:1.6;color:rgba(255,255,255,0.75)">Dashboard Preview<br><?php esc_html_e( '（スクリーンショットをここに挿入）', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

</div>
<!-- /wp:group -->
