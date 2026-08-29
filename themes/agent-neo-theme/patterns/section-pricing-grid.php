<?php
/**
 * Title: セクション ⑧ 料金 3 列
 * Slug: agent-neo/section-pricing-grid
 * Categories: agent-neo
 * Description: 小規模、成長、拡張の 3 プランを横並びで見せる料金セクション。違いと CTA を一度に比較してもらいたい LP 向け。
 * Keywords: section, pricing, plans, comparison, cta
 * Viewport Width: 1280
 * Block Types: core/group, core/columns, core/button
 * Post Types: wp_template, page
 * Inserter: true
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<!-- wp:group {"anchor":"pricing","align":"full","className":"an-section an-section\u002d\u002dpricing-grid","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"},"blockGap":"var:preset|spacing|50"}},"backgroundColor":"secondary","layout":{"type":"constrained"}} -->
<div id="pricing" class="wp-block-group alignfull an-section an-section--pricing-grid has-secondary-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40)">
<!-- wp:group {"align":"wide","style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"layout":{"type":"constrained","justifyContent":"center"}} -->
<div class="wp-block-group alignwide">
<!-- wp:paragraph {"align":"center","textColor":"accent-aa","style":{"typography":{"fontWeight":"700","textTransform":"uppercase"}},"fontSize":"small"} -->
<p class="has-text-align-center has-accent-aa-color has-text-color has-small-font-size" style="font-weight:700;text-transform:uppercase"><?php esc_html_e( '選べる始め方', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"textAlign":"center","style":{"typography":{"fontWeight":"800"}},"fontSize":"xx-large"} -->
<h2 class="wp-block-heading has-text-align-center has-xx-large-font-size" style="font-weight:800"><?php esc_html_e( '今のチームに合うプランから', 'agent-neo' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","textColor":"muted","fontSize":"medium"} -->
<p class="has-text-align-center has-muted-color has-text-color has-medium-font-size"><?php esc_html_e( '必要な範囲から始め、状況に合わせて広げられます。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->
</div>
<!-- /wp:group -->

<!-- wp:columns {"align":"wide","style":{"spacing":{"margin":{"top":"var:preset|spacing|50"},"blockGap":{"left":"var:preset|spacing|30","top":"var:preset|spacing|30"}}}} -->
<div class="wp-block-columns alignwide" style="margin-top:var(--wp--preset--spacing--50)">
<!-- wp:column {"style":{"border":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dmuted)","width":"1px","style":"solid","radius":"var:preset|spacing|20"},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}}} -->
<div class="wp-block-column has-border-color" style="border-color:var(--wp--preset--color--muted);border-style:solid;border-width:1px;border-radius:var(--wp--preset--spacing--20);padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--30)">
<!-- wp:heading {"level":3,"style":{"typography":{"fontWeight":"700"}},"fontSize":"large"} -->
<h3 class="wp-block-heading has-large-font-size" style="font-weight:700"><?php esc_html_e( 'スターター', 'agent-neo' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"textColor":"muted","fontSize":"small"} -->
<p class="has-muted-color has-text-color has-small-font-size"><?php esc_html_e( 'まずは整えるところから。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"textColor":"accent-aa","style":{"typography":{"fontWeight":"800"}},"fontSize":"xx-large"} -->
<p class="has-accent-aa-color has-text-color has-xx-large-font-size" style="font-weight:800"><?php esc_html_e( '小規模', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:list {"style":{"spacing":{"blockGap":"var:preset|spacing|10"}},"fontSize":"small"} -->
<ul class="wp-block-list has-small-font-size">
<!-- wp:list-item -->
<li><?php esc_html_e( '基本構成の整理', 'agent-neo' ); ?></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><?php esc_html_e( '定期的な見直し', 'agent-neo' ); ?></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><?php esc_html_e( '相談窓口', 'agent-neo' ); ?></li>
<!-- /wp:list-item -->
</ul>
<!-- /wp:list -->

<!-- wp:button {"className":"is-style-outline","textColor":"accent-aa","width":100,"style":{"spacing":{"margin":{"top":"var:preset|spacing|30"}},"typography":{"fontWeight":"700"}},"fontSize":"small"} -->
<div class="wp-block-button has-custom-width wp-block-button__width-100 is-style-outline"><a class="wp-block-button__link has-accent-aa-color has-text-color has-small-font-size has-custom-font-size wp-element-button" href="/contact/" style="margin-top:var(--wp--preset--spacing--30);font-weight:700"><?php esc_html_e( '相談する', 'agent-neo' ); ?></a></div>
<!-- /wp:button -->
</div>
<!-- /wp:column -->

<!-- wp:column {"backgroundColor":"primary","textColor":"background","style":{"border":{"radius":"var:preset|spacing|20"},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}}} -->
<div class="wp-block-column has-background-color has-primary-background-color has-text-color has-background" style="border-radius:var(--wp--preset--spacing--20);padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--30)">
<!-- wp:paragraph {"textColor":"accent","style":{"typography":{"fontWeight":"700","textTransform":"uppercase"}},"fontSize":"small"} -->
<p class="has-accent-color has-text-color has-small-font-size" style="font-weight:700;text-transform:uppercase"><?php esc_html_e( 'おすすめ', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3,"textColor":"background","style":{"typography":{"fontWeight":"700"}},"fontSize":"large"} -->
<h3 class="wp-block-heading has-background-color has-text-color has-large-font-size" style="font-weight:700"><?php esc_html_e( 'グロース', 'agent-neo' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"textColor":"background","fontSize":"small"} -->
<p class="has-background-color has-text-color has-small-font-size"><?php esc_html_e( '運用の流れを広げたいチームへ。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:list {"style":{"spacing":{"blockGap":"var:preset|spacing|10"}},"textColor":"background","fontSize":"small"} -->
<ul class="wp-block-list has-background-color has-text-color has-small-font-size">
<!-- wp:list-item -->
<li><?php esc_html_e( '複数の導線を整理', 'agent-neo' ); ?></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><?php esc_html_e( '改善サイクルの設計', 'agent-neo' ); ?></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><?php esc_html_e( '優先順位の相談', 'agent-neo' ); ?></li>
<!-- /wp:list-item -->
</ul>
<!-- /wp:list -->

<!-- wp:button {"backgroundColor":"accent","textColor":"primary","width":100,"style":{"spacing":{"margin":{"top":"var:preset|spacing|30"}},"typography":{"fontWeight":"700"}},"fontSize":"small"} -->
<div class="wp-block-button has-custom-width wp-block-button__width-100"><a class="wp-block-button__link has-primary-color has-accent-background-color has-text-color has-background has-small-font-size has-custom-font-size wp-element-button" href="/contact/" style="margin-top:var(--wp--preset--spacing--30);font-weight:700"><?php esc_html_e( '詳しく相談する', 'agent-neo' ); ?></a></div>
<!-- /wp:button -->
</div>
<!-- /wp:column -->

<!-- wp:column {"style":{"border":{"color":"var(\u002d\u002dwp\u002d\u002dpreset\u002d\u002dcolor\u002d\u002dmuted)","width":"1px","style":"solid","radius":"var:preset|spacing|20"},"spacing":{"padding":{"top":"var:preset|spacing|40","bottom":"var:preset|spacing|40","left":"var:preset|spacing|30","right":"var:preset|spacing|30"}}}} -->
<div class="wp-block-column has-border-color" style="border-color:var(--wp--preset--color--muted);border-style:solid;border-width:1px;border-radius:var(--wp--preset--spacing--20);padding-top:var(--wp--preset--spacing--40);padding-right:var(--wp--preset--spacing--30);padding-bottom:var(--wp--preset--spacing--40);padding-left:var(--wp--preset--spacing--30)">
<!-- wp:heading {"level":3,"style":{"typography":{"fontWeight":"700"}},"fontSize":"large"} -->
<h3 class="wp-block-heading has-large-font-size" style="font-weight:700"><?php esc_html_e( 'エンタープライズ', 'agent-neo' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"textColor":"muted","fontSize":"small"} -->
<p class="has-muted-color has-text-color has-small-font-size"><?php esc_html_e( '複数チームで長く使うために。', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"textColor":"accent-aa","style":{"typography":{"fontWeight":"800"}},"fontSize":"xx-large"} -->
<p class="has-accent-aa-color has-text-color has-xx-large-font-size" style="font-weight:800"><?php esc_html_e( '拡張', 'agent-neo' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:list {"style":{"spacing":{"blockGap":"var:preset|spacing|10"}},"fontSize":"small"} -->
<ul class="wp-block-list has-small-font-size">
<!-- wp:list-item -->
<li><?php esc_html_e( '役割ごとの運用設計', 'agent-neo' ); ?></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><?php esc_html_e( '複数サイトの整理', 'agent-neo' ); ?></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><?php esc_html_e( '伴走プランの相談', 'agent-neo' ); ?></li>
<!-- /wp:list-item -->
</ul>
<!-- /wp:list -->

<!-- wp:button {"className":"is-style-outline","textColor":"accent-aa","width":100,"style":{"spacing":{"margin":{"top":"var:preset|spacing|30"}},"typography":{"fontWeight":"700"}},"fontSize":"small"} -->
<div class="wp-block-button has-custom-width wp-block-button__width-100 is-style-outline"><a class="wp-block-button__link has-accent-aa-color has-text-color has-small-font-size has-custom-font-size wp-element-button" href="/contact/" style="margin-top:var(--wp--preset--spacing--30);font-weight:700"><?php esc_html_e( '話を聞く', 'agent-neo' ); ?></a></div>
<!-- /wp:button -->
</div>
<!-- /wp:column -->
</div>
<!-- /wp:columns -->
</div>
<!-- /wp:group -->
