<?php
/**
 * Title: LP 実績・エビデンス（数値で示す）
 * Slug: agent-neo/lp-proof
 * Categories: agent-neo
 * Description: LP 実績セクション。大きな数値+ラベルの 4カラム。白背景帯。
 * Keywords: lp, proof, numbers, achievement, result
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

<!-- wp:group {"align":"full","className":"an-lp-proof","backgroundColor":"background","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull an-lp-proof has-background-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40)">

	<!-- wp:heading {"level":2,"textAlign":"center","style":{"typography":{"fontWeight":"700"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
	<h2 class="wp-block-heading has-text-align-center" style="font-weight:700;margin-bottom:var(--wp--preset--spacing--20)"><?php esc_html_e( '数字で見る AGENT NEO の実績', 'agent-neo' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)"},"spacing":{"margin":{"bottom":"var:preset|spacing|50"}},"color":{"text":"#555555"}}} -->
	<p class="has-text-align-center" style="font-size:var(--wp--preset--font-size--medium);margin-bottom:var(--wp--preset--spacing--50);color:#555555"><?php esc_html_e( '導入サイト全体の集計値です（2026年6月現在）', 'agent-neo' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:columns {"isStackedOnMobile":true,"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|30","left":"var:preset|spacing|20"}}}} -->
	<div class="wp-block-columns is-not-stacked-on-mobile" style="gap:var(--wp--preset--spacing--20)">

		<!-- 実績 1 -->
		<!-- wp:column {"style":{"border":{"radius":"8px","bottom":{"color":"var(--wp--preset--color--accent)","width":"3px"},"top":{"color":"var(--wp--preset--color--secondary)","width":"1px"},"left":{"color":"var(--wp--preset--color--secondary)","width":"1px"},"right":{"color":"var(--wp--preset--color--secondary)","width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}},"color":{"background":"#ffffff"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
		<div class="wp-block-column" style="border-radius:8px;border-top:1px solid var(--wp--preset--color--secondary);border-left:1px solid var(--wp--preset--color--secondary);border-right:1px solid var(--wp--preset--color--secondary);border-bottom:3px solid var(--wp--preset--color--accent);padding:var(--wp--preset--spacing--40) var(--wp--preset--spacing--30);background-color:#ffffff;display:flex;flex-direction:column;align-items:center">
			<!-- wp:paragraph {"align":"center","style":{"typography":{"fontWeight":"800","lineHeight":"1.0"},"color":{"text":"var(--wp--preset--color--accent)"},"spacing":{"margin":{"bottom":"var:preset|spacing|10"}}}} -->
			<p class="has-text-align-center" style="font-weight:800;font-size:3.5rem;line-height:1.0;color:var(--wp--preset--color--accent);margin-bottom:var(--wp--preset--spacing--10)">2,400<span style="font-size:1.5rem"><?php esc_html_e( '件', 'agent-neo' ); ?></span></p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"align":"center","style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--medium)"},"color":{"text":"var(--wp--preset--color--primary)"},"spacing":{"margin":{"bottom":"var:preset|spacing|10"}}}} -->
			<p class="has-text-align-center" style="font-weight:700;font-size:var(--wp--preset--font-size--medium);color:var(--wp--preset--color--primary);margin-bottom:var(--wp--preset--spacing--10)"><?php esc_html_e( '自動生成・公開した記事数', 'agent-neo' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"var(--wp--preset--font-size--small)"},"color":{"text":"#888888"}}} -->
			<p class="has-text-align-center" style="font-size:var(--wp--preset--font-size--small);color:#888888"><?php esc_html_e( '平均品質スコア 87点', 'agent-neo' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- 実績 2 -->
		<!-- wp:column {"style":{"border":{"radius":"8px","bottom":{"color":"var(--wp--preset--color--accent)","width":"3px"},"top":{"color":"var(--wp--preset--color--secondary)","width":"1px"},"left":{"color":"var(--wp--preset--color--secondary)","width":"1px"},"right":{"color":"var(--wp--preset--color--secondary)","width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}},"color":{"background":"#ffffff"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
		<div class="wp-block-column" style="border-radius:8px;border-top:1px solid var(--wp--preset--color--secondary);border-left:1px solid var(--wp--preset--color--secondary);border-right:1px solid var(--wp--preset--color--secondary);border-bottom:3px solid var(--wp--preset--color--accent);padding:var(--wp--preset--spacing--40) var(--wp--preset--spacing--30);background-color:#ffffff;display:flex;flex-direction:column;align-items:center">
			<!-- wp:paragraph {"align":"center","style":{"typography":{"fontWeight":"800","lineHeight":"1.0"},"color":{"text":"var(--wp--preset--color--accent)"},"spacing":{"margin":{"bottom":"var:preset|spacing|10"}}}} -->
			<p class="has-text-align-center" style="font-weight:800;font-size:3.5rem;line-height:1.0;color:var(--wp--preset--color--accent);margin-bottom:var(--wp--preset--spacing--10)">+340<span style="font-size:1.5rem">%</span></p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"align":"center","style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--medium)"},"color":{"text":"var(--wp--preset--color--primary)"},"spacing":{"margin":{"bottom":"var:preset|spacing|10"}}}} -->
			<p class="has-text-align-center" style="font-weight:700;font-size:var(--wp--preset--font-size--medium);color:var(--wp--preset--color--primary);margin-bottom:var(--wp--preset--spacing--10)"><?php esc_html_e( 'オーガニック流入の平均増加率', 'agent-neo' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"var(--wp--preset--font-size--small)"},"color":{"text":"#888888"}}} -->
			<p class="has-text-align-center" style="font-size:var(--wp--preset--font-size--small);color:#888888"><?php esc_html_e( '導入後6ヶ月比較', 'agent-neo' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- 実績 3 -->
		<!-- wp:column {"style":{"border":{"radius":"8px","bottom":{"color":"var(--wp--preset--color--accent)","width":"3px"},"top":{"color":"var(--wp--preset--color--secondary)","width":"1px"},"left":{"color":"var(--wp--preset--color--secondary)","width":"1px"},"right":{"color":"var(--wp--preset--color--secondary)","width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}},"color":{"background":"#ffffff"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
		<div class="wp-block-column" style="border-radius:8px;border-top:1px solid var(--wp--preset--color--secondary);border-left:1px solid var(--wp--preset--color--secondary);border-right:1px solid var(--wp--preset--color--secondary);border-bottom:3px solid var(--wp--preset--color--accent);padding:var(--wp--preset--spacing--40) var(--wp--preset--spacing--30);background-color:#ffffff;display:flex;flex-direction:column;align-items:center">
			<!-- wp:paragraph {"align":"center","style":{"typography":{"fontWeight":"800","lineHeight":"1.0"},"color":{"text":"var(--wp--preset--color--accent)"},"spacing":{"margin":{"bottom":"var:preset|spacing|10"}}}} -->
			<p class="has-text-align-center" style="font-weight:800;font-size:3.5rem;line-height:1.0;color:var(--wp--preset--color--accent);margin-bottom:var(--wp--preset--spacing--10)">80<span style="font-size:1.5rem">%</span></p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"align":"center","style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--medium)"},"color":{"text":"var(--wp--preset--color--primary)"},"spacing":{"margin":{"bottom":"var:preset|spacing|10"}}}} -->
			<p class="has-text-align-center" style="font-weight:700;font-size:var(--wp--preset--font-size--medium);color:var(--wp--preset--color--primary);margin-bottom:var(--wp--preset--spacing--10)"><?php esc_html_e( '運用工数の削減率', 'agent-neo' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"var(--wp--preset--font-size--small)"},"color":{"text":"#888888"}}} -->
			<p class="has-text-align-center" style="font-size:var(--wp--preset--font-size--small);color:#888888"><?php esc_html_e( '月間担当者作業時間の比較', 'agent-neo' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

		<!-- 実績 4 -->
		<!-- wp:column {"style":{"border":{"radius":"8px","bottom":{"color":"var(--wp--preset--color--accent)","width":"3px"},"top":{"color":"var(--wp--preset--color--secondary)","width":"1px"},"left":{"color":"var(--wp--preset--color--secondary)","width":"1px"},"right":{"color":"var(--wp--preset--color--secondary)","width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}},"color":{"background":"#ffffff"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
		<div class="wp-block-column" style="border-radius:8px;border-top:1px solid var(--wp--preset--color--secondary);border-left:1px solid var(--wp--preset--color--secondary);border-right:1px solid var(--wp--preset--color--secondary);border-bottom:3px solid var(--wp--preset--color--accent);padding:var(--wp--preset--spacing--40) var(--wp--preset--spacing--30);background-color:#ffffff;display:flex;flex-direction:column;align-items:center">
			<!-- wp:paragraph {"align":"center","style":{"typography":{"fontWeight":"800","lineHeight":"1.0"},"color":{"text":"var(--wp--preset--color--accent)"},"spacing":{"margin":{"bottom":"var:preset|spacing|10"}}}} -->
			<p class="has-text-align-center" style="font-weight:800;font-size:3.5rem;line-height:1.0;color:var(--wp--preset--color--accent);margin-bottom:var(--wp--preset--spacing--10)">150<span style="font-size:1.5rem"><?php esc_html_e( '社', 'agent-neo' ); ?></span></p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"align":"center","style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--medium)"},"color":{"text":"var(--wp--preset--color--primary)"},"spacing":{"margin":{"bottom":"var:preset|spacing|10"}}}} -->
			<p class="has-text-align-center" style="font-weight:700;font-size:var(--wp--preset--font-size--medium);color:var(--wp--preset--color--primary);margin-bottom:var(--wp--preset--spacing--10)"><?php esc_html_e( '継続導入中の企業・メディア数', 'agent-neo' ); ?></p>
			<!-- /wp:paragraph -->
			<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"var(--wp--preset--font-size--small)"},"color":{"text":"#888888"}}} -->
			<p class="has-text-align-center" style="font-size:var(--wp--preset--font-size--small);color:#888888"><?php esc_html_e( '継続率 96%', 'agent-neo' ); ?></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:column -->

	</div>
	<!-- /wp:columns -->

</div>
<!-- /wp:group -->
