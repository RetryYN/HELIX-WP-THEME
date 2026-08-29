<?php
/**
 * Title: ヒーロー ⑦ 大型タイポグラフィ
 * Slug: agent-neo/hero-typographic
 * Categories: agent-neo, agent-neo-home
 * Description: 画像を使わず、大型見出しと短い補足だけで視線をつくるテキスト主体のヒーロー。編集誌やポートフォリオの表紙に向く。
 * Keywords: hero, typography, text-only, editorial, statement
 * Viewport Width: 1280
 * Block Types: core/group, core/heading
 * Post Types: wp_template, page
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"align":"full","className":"an-section an-section\u002d\u002dhero an-hero\u002d\u002dtypographic","style":{"border":{"bottom":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dprimary)","width":"1px","style":"solid"}},"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull an-section an-section--hero an-hero--typographic" style="border-bottom-color:var(--wp--preset--color--primary);border-bottom-style:solid;border-bottom-width:1px;padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40)"><!-- wp:group {"align":"wide","style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide"><!-- wp:paragraph {"textColor":"accent-aa","style":{"typography":{"fontWeight":"700","textTransform":"uppercase"}},"fontSize":"small"} -->
<p class="has-accent-aa-color has-text-color has-small-font-size" style="font-weight:700;text-transform:uppercase"><?php esc_html_e( '静かな表紙から始める', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":1,"style":{"typography":{"fontWeight":"800","lineHeight":"0.98"}},"fontSize":"xxx-large"} -->
<h1 class="wp-block-heading has-xxx-large-font-size" style="font-weight:800;line-height:0.98"><?php esc_html_e( 'いい仕事は、伝わる余白を持っている。', 'agent-neo' ); ?></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"textColor":"muted","fontSize":"large"} -->
<p class="has-muted-color has-text-color has-large-font-size"><?php esc_html_e( '要素を足す前に、何を残すかを決める。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|30"}}}} -->
<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--30)"><!-- wp:button {"backgroundColor":"primary","textColor":"background","style":{"typography":{"fontWeight":"700"}}} -->
<div class="wp-block-button"><a class="wp-block-button__link has-background-color has-primary-background-color has-text-color has-background wp-element-button" href="/about/"><?php esc_html_e( 'コンセプトを読む', 'agent-neo' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
