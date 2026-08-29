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

	<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)"},"spacing":{"margin":{"bottom":"var:preset|spacing|50"}},"color":{"text":"var(--wp--preset--color--foreground)"}}} -->
	<p class="has-text-align-center" style="font-size:var(--wp--preset--font-size--medium);margin-bottom:var(--wp--preset--spacing--50);color:var(--wp--preset--color--foreground)"><?php esc_html_e( '導入サイト全体の集計値です（2026年6月現在）', 'agent-neo' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:columns {"isStackedOnMobile":true,"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|30","left":"var:preset|spacing|20"}}}} --><div class="wp-block-columns"><!-- wp:column {"style":{"border":{"radius":"8px","bottom":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002daccent)","width":"3px"},"top":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dsecondary)","width":"1px"},"left":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dsecondary)","width":"1px"},"right":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dsecondary)","width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}},"color":{"background":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dbackground)"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
<div class="wp-block-column has-background" style="border-radius:8px;border-top-color:var(--wp--preset--color--secondary);border-top-width:1px;border-right-color:var(--wp--preset--color--secondary);border-right-width:1px;border-bottom-color:var(--wp--preset--color--accent);border-bottom-width:3px;border-left-color:var(--wp--preset--color--secondary);border-left-width:1px;background-color:var(--wp--preset--color--background);padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--30)"><!-- wp:paragraph {"fontSize":"xxx-large","align":"center","style":{"typography":{"fontWeight":"800","lineHeight":"1.0"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002daccent-aa)"},"spacing":{"margin":{"bottom":"var:preset|spacing|10"}}}} -->
<p class="has-xxx-large-font-size has-text-align-center has-text-color" style="color:var(--wp--preset--color--accent-aa);margin-bottom:var(--wp--preset--spacing--10);font-weight:800;line-height:1.0">2,400<span style="font-size:1.5rem"><?php esc_html_e( '件', 'agent-neo' ); ?></span></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontWeight":"700","fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dmedium)"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dprimary)"},"spacing":{"margin":{"bottom":"var:preset|spacing|10"}}}} -->
<p class="has-text-align-center has-text-color" style="color:var(--wp--preset--color--primary);margin-bottom:var(--wp--preset--spacing--10);font-size:var(--wp--preset--font-size--medium);font-weight:700"><?php esc_html_e( '自動生成・公開した記事数', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dsmall)"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dmuted)"}}} -->
<p class="has-text-align-center has-text-color" style="color:var(--wp--preset--color--muted);font-size:var(--wp--preset--font-size--small)"><?php esc_html_e( '平均品質スコア 87点', 'agent-neo' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"border":{"radius":"8px","bottom":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002daccent)","width":"3px"},"top":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dsecondary)","width":"1px"},"left":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dsecondary)","width":"1px"},"right":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dsecondary)","width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}},"color":{"background":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dbackground)"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
<div class="wp-block-column has-background" style="border-radius:8px;border-top-color:var(--wp--preset--color--secondary);border-top-width:1px;border-right-color:var(--wp--preset--color--secondary);border-right-width:1px;border-bottom-color:var(--wp--preset--color--accent);border-bottom-width:3px;border-left-color:var(--wp--preset--color--secondary);border-left-width:1px;background-color:var(--wp--preset--color--background);padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--30)"><!-- wp:paragraph {"fontSize":"xxx-large","align":"center","style":{"typography":{"fontWeight":"800","lineHeight":"1.0"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002daccent-aa)"},"spacing":{"margin":{"bottom":"var:preset|spacing|10"}}}} -->
<p class="has-xxx-large-font-size has-text-align-center has-text-color" style="color:var(--wp--preset--color--accent-aa);margin-bottom:var(--wp--preset--spacing--10);font-weight:800;line-height:1.0">+340<span style="font-size:1.5rem">%</span></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontWeight":"700","fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dmedium)"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dprimary)"},"spacing":{"margin":{"bottom":"var:preset|spacing|10"}}}} -->
<p class="has-text-align-center has-text-color" style="color:var(--wp--preset--color--primary);margin-bottom:var(--wp--preset--spacing--10);font-size:var(--wp--preset--font-size--medium);font-weight:700"><?php esc_html_e( 'オーガニック流入の平均増加率', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dsmall)"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dmuted)"}}} -->
<p class="has-text-align-center has-text-color" style="color:var(--wp--preset--color--muted);font-size:var(--wp--preset--font-size--small)"><?php esc_html_e( '導入後6ヶ月比較', 'agent-neo' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"border":{"radius":"8px","bottom":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002daccent)","width":"3px"},"top":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dsecondary)","width":"1px"},"left":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dsecondary)","width":"1px"},"right":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dsecondary)","width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}},"color":{"background":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dbackground)"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
<div class="wp-block-column has-background" style="border-radius:8px;border-top-color:var(--wp--preset--color--secondary);border-top-width:1px;border-right-color:var(--wp--preset--color--secondary);border-right-width:1px;border-bottom-color:var(--wp--preset--color--accent);border-bottom-width:3px;border-left-color:var(--wp--preset--color--secondary);border-left-width:1px;background-color:var(--wp--preset--color--background);padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--30)"><!-- wp:paragraph {"fontSize":"xxx-large","align":"center","style":{"typography":{"fontWeight":"800","lineHeight":"1.0"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002daccent-aa)"},"spacing":{"margin":{"bottom":"var:preset|spacing|10"}}}} -->
<p class="has-xxx-large-font-size has-text-align-center has-text-color" style="color:var(--wp--preset--color--accent-aa);margin-bottom:var(--wp--preset--spacing--10);font-weight:800;line-height:1.0">80<span style="font-size:1.5rem">%</span></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontWeight":"700","fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dmedium)"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dprimary)"},"spacing":{"margin":{"bottom":"var:preset|spacing|10"}}}} -->
<p class="has-text-align-center has-text-color" style="color:var(--wp--preset--color--primary);margin-bottom:var(--wp--preset--spacing--10);font-size:var(--wp--preset--font-size--medium);font-weight:700"><?php esc_html_e( '運用工数の削減率', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dsmall)"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dmuted)"}}} -->
<p class="has-text-align-center has-text-color" style="color:var(--wp--preset--color--muted);font-size:var(--wp--preset--font-size--small)"><?php esc_html_e( '月間担当者作業時間の比較', 'agent-neo' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"border":{"radius":"8px","bottom":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002daccent)","width":"3px"},"top":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dsecondary)","width":"1px"},"left":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dsecondary)","width":"1px"},"right":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dsecondary)","width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}},"color":{"background":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dbackground)"}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"center"}} -->
<div class="wp-block-column has-background" style="border-radius:8px;border-top-color:var(--wp--preset--color--secondary);border-top-width:1px;border-right-color:var(--wp--preset--color--secondary);border-right-width:1px;border-bottom-color:var(--wp--preset--color--accent);border-bottom-width:3px;border-left-color:var(--wp--preset--color--secondary);border-left-width:1px;background-color:var(--wp--preset--color--background);padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--30)"><!-- wp:paragraph {"fontSize":"xxx-large","align":"center","style":{"typography":{"fontWeight":"800","lineHeight":"1.0"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002daccent-aa)"},"spacing":{"margin":{"bottom":"var:preset|spacing|10"}}}} -->
<p class="has-xxx-large-font-size has-text-align-center has-text-color" style="color:var(--wp--preset--color--accent-aa);margin-bottom:var(--wp--preset--spacing--10);font-weight:800;line-height:1.0">150<span style="font-size:1.5rem"><?php esc_html_e( '社', 'agent-neo' ); ?></span></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontWeight":"700","fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dmedium)"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dprimary)"},"spacing":{"margin":{"bottom":"var:preset|spacing|10"}}}} -->
<p class="has-text-align-center has-text-color" style="color:var(--wp--preset--color--primary);margin-bottom:var(--wp--preset--spacing--10);font-size:var(--wp--preset--font-size--medium);font-weight:700"><?php esc_html_e( '継続導入中の企業・メディア数', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dsmall)"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dmuted)"}}} -->
<p class="has-text-align-center has-text-color" style="color:var(--wp--preset--color--muted);font-size:var(--wp--preset--font-size--small)"><?php esc_html_e( '継続率 96%', 'agent-neo' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div><!-- /wp:columns -->

</div>
<!-- /wp:group -->
