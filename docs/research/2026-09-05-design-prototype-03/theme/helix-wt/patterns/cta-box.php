<?php
/**
 * Title: CTA ボックス（コピー付き）
 * Slug: helix-wt/cta-box
 * Categories: helix-wt, helix-wt-page
 * Description: box-with-copy 型。見出し・一言・CTA 1 つ・補足
 */
?>
<!-- wp:group {"className":"is-style-wt-cta-box wt-reveal","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-wt-cta-box wt-reveal"><!-- wp:paragraph {"className":"wt-cta-box__title"} --><p class="wt-cta-box__title"><?php esc_html_e( '迷ったら、3 分の無料診断へ', 'helix-wt' ); ?></p><!-- /wp:paragraph --><!-- wp:paragraph {"fontSize":"s"} --><p class="has-s-font-size"><?php esc_html_e( '部屋の広さ・作業時間・予算の 5 問に答えると、この記事の 3 製品から合うものを提案します。', 'helix-wt' ); ?></p><!-- /wp:paragraph --><!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} --><div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"cta","textColor":"cta-contrast"} --><div class="wp-block-button"><a class="wp-block-button__link has-cta-contrast-color has-cta-background-color has-text-color has-background wp-element-button" href="/lp/"><?php esc_html_e( '無料で診断する', 'helix-wt' ); ?></a></div><!-- /wp:button --></div><!-- /wp:buttons --><!-- wp:paragraph {"className":"wt-cta-box__note"} --><p class="wt-cta-box__note"><?php esc_html_e( '登録不要・所要 3 分。結果はその場で表示されます。', 'helix-wt' ); ?></p><!-- /wp:paragraph --></div>
<!-- /wp:group -->
