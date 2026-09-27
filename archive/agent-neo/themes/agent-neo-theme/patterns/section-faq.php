<?php
/**
 * Title: セクション ⑨ FAQ
 * Slug: agent-neo/section-faq
 * Categories: agent-neo
 * Description: 質問を開閉できる Details ブロックでまとめる FAQ セクション。長い説明を段階的に読ませたい LP 向け。
 * Keywords: section, faq, details, questions, answers
 * Viewport Width: 1280
 * Block Types: core/group, core/details
 * Post Types: wp_template, page
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"anchor":"faq","align":"full","className":"an-section an-section\u002d\u002dfaq","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|40"}},"layout":{"type":"constrained"}} -->
<div id="faq" class="wp-block-group alignfull an-section an-section--faq" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40)">
<!-- wp:group {"align":"wide","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide">
<!-- wp:paragraph {"textColor":"accent-aa","style":{"typography":{"fontWeight":"700","textTransform":"uppercase"}},"fontSize":"small"} -->
<p class="has-accent-aa-color has-text-color has-small-font-size" style="font-weight:700;text-transform:uppercase"><?php esc_html_e( 'よくある質問', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"style":{"typography":{"fontWeight":"800"}},"fontSize":"xx-large"} -->
<h2 class="wp-block-heading has-xx-large-font-size" style="font-weight:800"><?php esc_html_e( '気になる点を先に解消する', 'agent-neo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"textColor":"muted","fontSize":"medium"} -->
<p class="has-muted-color has-text-color has-medium-font-size"><?php esc_html_e( '判断に必要な情報を、短い答えから確認できます。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:group {"align":"wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|10"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide" style="margin-top:var(--wp--preset--spacing--40)">
<!-- wp:details {"className":"an-faq-item"} -->
<details class="wp-block-details an-faq-item"><summary><?php esc_html_e( 'どこから始めればよいですか？', 'agent-neo' ); ?></summary>
<!-- wp:paragraph {"textColor":"muted","fontSize":"small"} -->
<p class="has-muted-color has-text-color has-small-font-size"><?php esc_html_e( 'まず目的と読み手を整理し、必要なページから小さく始めます。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->
</details>
<!-- /wp:details -->

<!-- wp:details {"className":"an-faq-item"} -->
<details class="wp-block-details an-faq-item"><summary><?php esc_html_e( '内容の変更はあとからできますか？', 'agent-neo' ); ?></summary>
<!-- wp:paragraph {"textColor":"muted","fontSize":"small"} -->
<p class="has-muted-color has-text-color has-small-font-size"><?php esc_html_e( '公開後もページの構成や文章は、運用状況に合わせて見直せます。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->
</details>
<!-- /wp:details -->

<!-- wp:details {"className":"an-faq-item"} -->
<details class="wp-block-details an-faq-item"><summary><?php esc_html_e( '相談前に準備するものはありますか？', 'agent-neo' ); ?></summary>
<!-- wp:paragraph {"textColor":"muted","fontSize":"small"} -->
<p class="has-muted-color has-text-color has-small-font-size"><?php esc_html_e( '現在の課題や目指したい状態を、箇条書きでご用意いただければ十分です。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->
</details>
<!-- /wp:details -->
</div>
<!-- /wp:group -->
</div>
<!-- /wp:group -->
