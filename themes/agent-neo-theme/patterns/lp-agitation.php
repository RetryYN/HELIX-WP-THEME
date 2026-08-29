<?php
/**
 * Title: LP 課題増幅（このままだと…）
 * Slug: agent-neo/lp-agitation
 * Categories: agent-neo
 * Description: LP 課題増幅セクション。放置コスト・危機感をオレンジ強調の警告調で訴求。secondary 背景帯。
 * Keywords: lp, agitation, warning, risk, cost
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

<!-- wp:group {"align":"full","className":"an-lp-agitation","backgroundColor":"secondary","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull an-lp-agitation has-secondary-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--40)">

	<!-- wp:heading {"level":2,"textAlign":"center","style":{"typography":{"fontWeight":"700"},"spacing":{"margin":{"bottom":"var:preset|spacing|20"}}}} -->
	<h2 class="wp-block-heading has-text-align-center" style="font-weight:700;margin-bottom:var(--wp--preset--spacing--20)"><?php esc_html_e( 'このまま放置すると、', 'agent-neo' ); ?><br><?php esc_html_e( '競合にどんどん差をつけられます。', 'agent-neo' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"var(--wp--preset--font-size--medium)"},"spacing":{"margin":{"bottom":"var:preset|spacing|50"}},"color":{"text":"var(--wp--preset--color--foreground)"}}} -->
	<p class="has-text-align-center" style="font-size:var(--wp--preset--font-size--medium);margin-bottom:var(--wp--preset--spacing--50);color:var(--wp--preset--color--foreground)"><?php esc_html_e( '「いつかやろう」と先延ばしにするたびに、失っているものがあります。', 'agent-neo' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"flex","orientation":"vertical"}} --><div class="wp-block-group"><!-- wp:group {"style":{"border":{"radius":"8px","left":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002daccent)","width":"4px"}},"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dbackground)"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group has-background" style="border-radius:8px;border-left-color:var(--wp--preset--color--accent);border-left-width:4px;background-color:var(--wp--preset--color--background);padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--40)"><!-- wp:paragraph {"style":{"typography":{"fontWeight":"800","fontSize":"1.5rem","lineHeight":"1.0"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002daccent-aa)"},"spacing":{"margin":{"right":"var:preset|spacing|30"}}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--accent-aa);margin-right:var(--wp--preset--spacing--30);font-size:1.5rem;font-weight:800;line-height:1.0">01</p>
<!-- /wp:paragraph -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|10"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dlarge)"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dprimary)"}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--primary);font-size:var(--wp--preset--font-size--large);font-weight:700"><?php esc_html_e( '検索順位は時間とともに下落する', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dmedium)","lineHeight":"1.7"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dforeground)"}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--foreground);font-size:var(--wp--preset--font-size--medium);line-height:1.7"><?php esc_html_e( "競合が記事を更新し続ける中、更新が止まったサイトは Googleから「鮮度の低いコンテンツ」と評価され、半年で順位が大幅に落ちるケースが多数あります。", 'agent-neo' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"border":{"radius":"8px","left":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002daccent)","width":"4px"}},"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dbackground)"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group has-background" style="border-radius:8px;border-left-color:var(--wp--preset--color--accent);border-left-width:4px;background-color:var(--wp--preset--color--background);padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--40)"><!-- wp:paragraph {"style":{"typography":{"fontWeight":"800","fontSize":"1.5rem","lineHeight":"1.0"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002daccent-aa)"},"spacing":{"margin":{"right":"var:preset|spacing|30"}}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--accent-aa);margin-right:var(--wp--preset--spacing--30);font-size:1.5rem;font-weight:800;line-height:1.0">02</p>
<!-- /wp:paragraph -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|10"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dlarge)"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dprimary)"}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--primary);font-size:var(--wp--preset--font-size--large);font-weight:700"><?php esc_html_e( '人件費は増え続け、成果は比例しない', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dmedium)","lineHeight":"1.7"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dforeground)"}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--foreground);font-size:var(--wp--preset--font-size--medium);line-height:1.7"><?php esc_html_e( 'ライター発注・編集・校正・入稿で1記事あたり数万円のコストがかかります。それでも更新速度には限界があり、投資対効果が出にくい構造です。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"border":{"radius":"8px","left":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002daccent)","width":"4px"}},"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}},"color":{"background":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dbackground)"}},"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"center"}} -->
<div class="wp-block-group has-background" style="border-radius:8px;border-left-color:var(--wp--preset--color--accent);border-left-width:4px;background-color:var(--wp--preset--color--background);padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--40)"><!-- wp:paragraph {"style":{"typography":{"fontWeight":"800","fontSize":"1.5rem","lineHeight":"1.0"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002daccent-aa)"},"spacing":{"margin":{"right":"var:preset|spacing|30"}}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--accent-aa);margin-right:var(--wp--preset--spacing--30);font-size:1.5rem;font-weight:800;line-height:1.0">03</p>
<!-- /wp:paragraph -->

<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|10"}},"layout":{"type":"flex","orientation":"vertical"}} -->
<div class="wp-block-group"><!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dlarge)"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dprimary)"}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--primary);font-size:var(--wp--preset--font-size--large);font-weight:700"><?php esc_html_e( '担当者が変わるたびにゼロからやり直し', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontSize":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dfont-size\u002d\u002dmedium)","lineHeight":"1.7"},"color":{"text":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dforeground)"}}} -->
<p class="has-text-color" style="color:var(--wp--preset--color--foreground);font-size:var(--wp--preset--font-size--medium);line-height:1.7"><?php esc_html_e( 'SEO ノウハウが個人に依存していると、退職・異動のたびに施策が止まります。属人化した運用は、組織としての競争力を持続できません。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div><!-- /wp:group -->

	<!-- wp:paragraph {"align":"center","style":{"typography":{"fontWeight":"700","fontSize":"var(--wp--preset--font-size--large)","lineHeight":"1.5"},"color":{"text":"var(--wp--preset--color--accent-aa)"},"spacing":{"margin":{"top":"var:preset|spacing|50"}}}} -->
	<p class="has-text-align-center" style="font-weight:700;font-size:var(--wp--preset--font-size--large);line-height:1.5;color:var(--wp--preset--color--accent-aa);margin-top:var(--wp--preset--spacing--50)"><?php esc_html_e( 'この状況を変えるのが、AGENT NEO です。', 'agent-neo' ); ?></p>
	<!-- /wp:paragraph -->

</div>
<!-- /wp:group -->
