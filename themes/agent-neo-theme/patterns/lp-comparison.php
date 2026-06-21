<?php
/**
 * Title: LP 比較（従来 vs AGENT NEO）
 * Slug: agent-neo/lp-comparison
 * Categories: agent-neo
 * Description: LP 比較セクション。従来の手法と AGENT NEO の 2カラム対比。AGENT NEO 側をオレンジ強調。白背景帯。
 * Keywords: lp, comparison, versus, before, after
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

<!-- wp:group {"align":"full","className":"an-lp-comparison","backgroundColor":"background","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull an-lp-comparison has-background-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40)">

	<!-- wp:heading {"level":2,"textAlign":"center","style":{"typography":{"fontWeight":"700"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
	<h2 class="wp-block-heading has-text-align-center" style="font-weight:700;margin-bottom:var(--wp--preset--spacing--20)"><?php esc_html_e( '従来の運用と何が違うのか', 'agent-neo' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)"},"spacing":{"margin":{"bottom":"var:preset|spacing|50"}},"color":{"text":"#555555"}}} -->
	<p class="has-text-align-center" style="font-size:var(--wp--preset--font-size--medium);margin-bottom:var(--wp--preset--spacing--50);color:#555555"><?php esc_html_e( '人手に依存した旧来の運用スタイルから、AI による自律運用へ。その差は一目瞭然です。', 'agent-neo' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:columns {"isStackedOnMobile":true,"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|30","left":"var:preset|spacing|30"}}}} -->
	<div class="wp-block-columns is-not-stacked-on-mobile" style="gap:var(--wp--preset--spacing--30)">

		<!-- 従来の運用 -->
		<!-- wp:column {"style":{"border":{"radius":"8px","color":"var(--wp--preset--color--secondary)","width":"2px"},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"#fafafa"}}} -->
		<div class="wp-block-column" style="border-radius:8px;border:2px solid var(--wp--preset--color--secondary);padding:var(--wp--preset--spacing--40);background-color:#fafafa">

			<!-- wp:paragraph {"align":"center","style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--large)"},"color":{"text":"#888888"},"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} -->
			<p class="has-text-align-center" style="font-weight:700;font-size:var(--wp--preset--font-size--large);color:#888888;margin-bottom:var(--wp--preset--spacing--30)"><?php esc_html_e( '従来の運用', 'agent-neo' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","orientation":"vertical"}} -->
			<div class="wp-block-group" style="gap:var(--wp--preset--spacing--20)">

				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.6"},"color":{"text":"#555555"}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.6;color:#555555">✕ &nbsp;<?php esc_html_e( 'ライターへの発注・管理コストが高い', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.6"},"color":{"text":"#555555"}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.6;color:#555555">✕ &nbsp;<?php esc_html_e( '更新頻度が担当者のリソースに依存', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.6"},"color":{"text":"#555555"}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.6;color:#555555">✕ &nbsp;<?php esc_html_e( 'SEO 施策が属人的で継続しにくい', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.6"},"color":{"text":"#555555"}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.6;color:#555555">✕ &nbsp;<?php esc_html_e( '複数ツールで成果の把握に時間がかかる', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","lineHeight":"1.6"},"color":{"text":"#555555"}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium);line-height:1.6;color:#555555">✕ &nbsp;<?php esc_html_e( '担当者交代のたびにノウハウが失われる', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->

			</div>
			<!-- /wp:group -->

		</div>
		<!-- /wp:column -->

		<!-- AGENT NEO -->
		<!-- wp:column {"style":{"border":{"radius":"8px","color":"var(--wp--preset--color--accent)","width":"2px"},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"#ffffff"}}} -->
		<div class="wp-block-column" style="border-radius:8px;border:2px solid var(--wp--preset--color--accent);padding:var(--wp--preset--spacing--40);background-color:#ffffff">

			<!-- wp:paragraph {"align":"center","style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--large)"},"color":{"text":"var(--wp--preset--color--accent)"},"spacing":{"margin":{"bottom":"var:preset|spacing|30"}}}} -->
			<p class="has-text-align-center" style="font-weight:700;font-size:var(--wp--preset--font-size--large);color:var(--wp--preset--color--accent);margin-bottom:var(--wp--preset--spacing--30)">AGENT NEO</p>
			<!-- /wp:paragraph -->

			<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","orientation":"vertical"}} -->
			<div class="wp-block-group" style="gap:var(--wp--preset--spacing--20)">

				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","fontWeight":"500","lineHeight":"1.6"},"color":{"text":"#222222"}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium);font-weight:500;line-height:1.6;color:#222222">◎ &nbsp;<?php esc_html_e( 'AI がコンテンツを自動生成・管理', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","fontWeight":"500","lineHeight":"1.6"},"color":{"text":"#222222"}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium);font-weight:500;line-height:1.6;color:#222222">◎ &nbsp;<?php esc_html_e( '24時間・365日、自律的に更新', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","fontWeight":"500","lineHeight":"1.6"},"color":{"text":"#222222"}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium);font-weight:500;line-height:1.6;color:#222222">◎ &nbsp;<?php esc_html_e( 'SEO 最適化がシステムで一貫維持', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","fontWeight":"500","lineHeight":"1.6"},"color":{"text":"#222222"}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium);font-weight:500;line-height:1.6;color:#222222">◎ &nbsp;<?php esc_html_e( 'ひとつの画面で全成果をリアルタイム確認', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->
				<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)","fontWeight":"500","lineHeight":"1.6"},"color":{"text":"#222222"}}} -->
				<p style="font-size:var(--wp--preset--font-size--medium);font-weight:500;line-height:1.6;color:#222222">◎ &nbsp;<?php esc_html_e( '組織の知見がシステムに蓄積される', 'agent-neo' ); ?></p>
				<!-- /wp:paragraph -->

			</div>
			<!-- /wp:group -->

		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

</div>
<!-- /wp:group -->
