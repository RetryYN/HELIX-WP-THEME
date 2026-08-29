<?php
/**
 * Title: 記事内 ④ 関連記事グリッド
 * Slug: agent-neo/article-related-grid
 * Categories: agent-neo
 * Description: 関連記事を 3 枚のカードとして並べる記事末尾用グリッド。本文から次の読みものへ自然に誘導する。
 * Keywords: article, related, posts, grid, cards
 * Viewport Width: 800
 * Block Types: core/group, core/columns
 * Post Types: wp_template, post, page
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"className":"an-article-box an-article-box\u002d\u002drelated","style":{"spacing":{"margin":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|30"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group an-article-box an-article-box--related" style="margin-top:var(--wp--preset--spacing--50);margin-bottom:var(--wp--preset--spacing--40)"><!-- wp:heading {"style":{"typography":{"fontWeight":"800"}},"fontSize":"x-large"} -->
<h2 class="wp-block-heading has-x-large-font-size" style="font-weight:800"><?php esc_html_e( 'こちらの記事もおすすめ', 'agent-neo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:columns {"style":{"spacing":{"margin":{"top":"var:preset|spacing|30"},"blockGap":{"left":"var:preset|spacing|20","top":"var:preset|spacing|20"}}}} -->
<div class="wp-block-columns" style="margin-top:var(--wp--preset--spacing--30)"><!-- wp:column {"style":{"border":{"color":"var(u002du002dwpu002du002dpresetu002du002dcoloru002du002dmuted)","width":"1px","style":"solid","radius":"var:preset|spacing|20"},"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20","left":"var:preset|spacing|20","right":"var:preset|spacing|20"}}}} -->
<div class="wp-block-column" style="border-color:var(u002du002dwpu002du002dpresetu002du002dcoloru002du002dmuted);border-style:solid;border-width:1px;border-radius:var(--wp--preset--spacing--20);padding-top:var(--wp--preset--spacing--20);padding-right:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20);padding-left:var(--wp--preset--spacing--20)"><!-- wp:paragraph {"textColor":"accent-aa","style":{"typography":{"fontWeight":"700","textTransform":"uppercase"}},"fontSize":"small"} -->
<p class="has-accent-aa-color has-text-color has-small-font-size" style="font-weight:700;text-transform:uppercase"><?php esc_html_e( '読みもの 01', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3,"style":{"typography":{"fontWeight":"700"}},"fontSize":"medium"} -->
<h3 class="wp-block-heading has-medium-font-size" style="font-weight:700"><a href="/blog/first-step/"><?php esc_html_e( '最初に整えるべき運用の基本', 'agent-neo' ); ?></a></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"textColor":"muted","fontSize":"small"} -->
<p class="has-muted-color has-text-color has-small-font-size"><?php esc_html_e( '迷いを減らすための土台を確認します。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"border":{"color":"var(u002du002dwpu002du002dpresetu002du002dcoloru002du002dmuted)","width":"1px","style":"solid","radius":"var:preset|spacing|20"},"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20","left":"var:preset|spacing|20","right":"var:preset|spacing|20"}}}} -->
<div class="wp-block-column" style="border-color:var(u002du002dwpu002du002dpresetu002du002dcoloru002du002dmuted);border-style:solid;border-width:1px;border-radius:var(--wp--preset--spacing--20);padding-top:var(--wp--preset--spacing--20);padding-right:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20);padding-left:var(--wp--preset--spacing--20)"><!-- wp:paragraph {"textColor":"accent-aa","style":{"typography":{"fontWeight":"700","textTransform":"uppercase"}},"fontSize":"small"} -->
<p class="has-accent-aa-color has-text-color has-small-font-size" style="font-weight:700;text-transform:uppercase"><?php esc_html_e( '読みもの 02', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3,"style":{"typography":{"fontWeight":"700"}},"fontSize":"medium"} -->
<h3 class="wp-block-heading has-medium-font-size" style="font-weight:700"><a href="/blog/content-plan/"><?php esc_html_e( '続けられるコンテンツ計画のつくり方', 'agent-neo' ); ?></a></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"textColor":"muted","fontSize":"small"} -->
<p class="has-muted-color has-text-color has-small-font-size"><?php esc_html_e( '更新のリズムをつくる考え方を紹介します。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"style":{"border":{"color":"var(u002du002dwpu002du002dpresetu002du002dcoloru002du002dmuted)","width":"1px","style":"solid","radius":"var:preset|spacing|20"},"spacing":{"padding":{"top":"var:preset|spacing|20","bottom":"var:preset|spacing|20","left":"var:preset|spacing|20","right":"var:preset|spacing|20"}}}} -->
<div class="wp-block-column" style="border-color:var(u002du002dwpu002du002dpresetu002du002dcoloru002du002dmuted);border-style:solid;border-width:1px;border-radius:var(--wp--preset--spacing--20);padding-top:var(--wp--preset--spacing--20);padding-right:var(--wp--preset--spacing--20);padding-bottom:var(--wp--preset--spacing--20);padding-left:var(--wp--preset--spacing--20)"><!-- wp:paragraph {"textColor":"accent-aa","style":{"typography":{"fontWeight":"700","textTransform":"uppercase"}},"fontSize":"small"} -->
<p class="has-accent-aa-color has-text-color has-small-font-size" style="font-weight:700;text-transform:uppercase"><?php esc_html_e( '読みもの 03', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3,"style":{"typography":{"fontWeight":"700"}},"fontSize":"medium"} -->
<h3 class="wp-block-heading has-medium-font-size" style="font-weight:700"><a href="/blog/review-checklist/"><?php esc_html_e( '公開前に確認したいチェック項目', 'agent-neo' ); ?></a></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"textColor":"muted","fontSize":"small"} -->
<p class="has-muted-color has-text-color has-small-font-size"><?php esc_html_e( '最後の確認を短いリストにまとめました。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
