<?php
/**
 * Title: 記事内 ③ 著者ボックス
 * Slug: agent-neo/article-author-box
 * Categories: agent-neo
 * Description: 記事の末尾に著者アバター、名前、プロフィールを横並びで表示する著者ボックス。専門性と次の記事への回遊を補強する。
 * Keywords: article, author, profile, avatar, bio
 * Viewport Width: 800
 * Block Types: core/group, core/avatar, core/post-author-name, core/post-author-biography
 * Post Types: wp_template, post
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"className":"an-article-box an-article-box\u002d\u002dauthor","style":{"border":{"top":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dmuted)","width":"1px","style":"solid"}},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|30"},"margin":{"top":"var:preset|spacing|50"},"blockGap":"var:preset|spacing|30"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group an-article-box an-article-box--author" style="border-top-color:var(--wp--preset--color--muted);border-top-style:solid;border-top-width:1px;margin-top:var(--wp--preset--spacing--50);padding-top:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--30)"><!-- wp:paragraph {"textColor":"accent-aa","style":{"typography":{"fontWeight":"700","textTransform":"uppercase"}},"fontSize":"small"} -->
<p class="has-accent-aa-color has-text-color has-small-font-size" style="font-weight:700;text-transform:uppercase"><?php esc_html_e( 'この記事を書いた人', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:columns {"verticalAlignment":"center","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|30","top":"var:preset|spacing|20"}}}} -->
<div class="wp-block-columns are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"18%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:18%"><!-- wp:avatar {"size":80,"style":{"border":{"radius":"var:preset|spacing|60"}}} /--></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"82%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:82%"><!-- wp:post-author-name {"style":{"typography":{"fontWeight":"700"}},"fontSize":"large"} /-->

<!-- wp:post-author-biography {"textColor":"muted","fontSize":"small"} /-->

<!-- wp:button {"className":"is-style-outline","textColor":"accent-aa","fontSize":"small"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-accent-aa-color has-text-color has-small-font-size has-custom-font-size wp-element-button" href="/author/"><?php esc_html_e( '著者の記事を見る', 'agent-neo' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
