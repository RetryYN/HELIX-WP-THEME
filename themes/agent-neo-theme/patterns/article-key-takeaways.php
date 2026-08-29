<?php
/**
 * Title: 記事内 ② 要点まとめ
 * Slug: agent-neo/article-key-takeaways
 * Categories: agent-neo
 * Description: 記事の要点を 3 項目に絞って冒頭や末尾に置くまとめボックス。長い記事の読み始めを助ける。
 * Keywords: article, takeaways, summary, checklist, highlights
 * Viewport Width: 800
 * Block Types: core/group, core/list
 * Post Types: wp_template, post, page
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"className":"an-article-box an-article-box\u002d\u002dtakeaways","style":{"border":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dprimary)","width":"1px","style":"solid","radius":"var:preset|spacing|20"},"spacing":{"padding":{"top":"var:preset|spacing|30","bottom":"var:preset|spacing|30","left":"var:preset|spacing|30","right":"var:preset|spacing|30"},"margin":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|20"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group an-article-box an-article-box--takeaways has-border-color" style="border-color:var(--wp--preset--color--primary);border-style:solid;border-width:1px;border-radius:var(--wp--preset--spacing--20);margin-top:var(--wp--preset--spacing--40);margin-bottom:var(--wp--preset--spacing--40);padding-top:var(--wp--preset--spacing--30);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--30);padding-left:var(--wp--preset--spacing--30)"><!-- wp:heading {"level":3,"style":{"typography":{"fontWeight":"700"}},"fontSize":"large"} -->
<h3 class="wp-block-heading has-large-font-size" style="font-weight:700"><?php esc_html_e( 'この記事の要点', 'agent-neo' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:list {"style":{"spacing":{"blockGap":"var:preset|spacing|10"}},"fontSize":"medium"} -->
<ul class="wp-block-list has-medium-font-size"><!-- wp:list-item -->
<li><?php esc_html_e( '最初に結論を一文で書く', 'agent-neo' ); ?></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><?php esc_html_e( '理由や手順を具体例で補う', 'agent-neo' ); ?></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><?php esc_html_e( '読み終えた後の行動を示す', 'agent-neo' ); ?></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:group -->
