<?php
/**
 * Title: 記事内 ① 注意ボックス
 * Slug: agent-neo/article-notice
 * Categories: agent-neo
 * Description: 読者が先に知っておくべき注意点や前提を記事内で目立たせるボックス。免責や手順の補足に使える。
 * Keywords: article, notice, alert, note, aside
 * Viewport Width: 800
 * Block Types: core/group, core/heading
 * Post Types: wp_template, post, page
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"className":"an-article-box an-article-box\u002d\u002dnotice","tagName":"aside","style":{"border":{"left":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002daccent-aa)","width":"1px","style":"solid"}},"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"margin":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|20"}},"backgroundColor":"secondary","layout":{"type":"constrained"}} -->
<aside class="wp-block-group an-article-box an-article-box--notice has-secondary-background-color has-background" style="border-left-color:var(--wp--preset--color--accent-aa);border-left-style:solid;border-left-width:1px;margin-top:var(--wp--preset--spacing--40);margin-bottom:var(--wp--preset--spacing--40);padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)">
<!-- wp:heading {"level":3,"textColor":"accent-aa","style":{"typography":{"fontWeight":"700"}},"fontSize":"medium"} -->
<h3 class="wp-block-heading has-accent-aa-color has-text-color has-medium-font-size" style="font-weight:700"><?php esc_html_e( '読む前に知っておきたいこと', 'agent-neo' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size"><?php esc_html_e( 'ここに前提、対象範囲、注意点などを短く記載してください。本文の流れを止めずに大事な情報を伝えられます。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->
</aside>
<!-- /wp:group -->
