<?php
/**
 * Title: ランキング 1 位強調 CTA
 * Slug: helix-wt/cta-rank-featured
 * Categories: helix-wt
 * Description: rank-featured 型。「総合1位」バッジ付きカードで CTA を強調
 */
?>
<!-- wp:group {"className":"wt-cta-rank","layout":{"type":"default"}} -->
<div class="wp-block-group wt-cta-rank">
<!-- wp:paragraph {"className":"wt-cta-rank__badge"} --><p class="wt-cta-rank__badge"><?php esc_html_e( '総合 1 位', 'helix-wt' ); ?></p><!-- /wp:paragraph -->
<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700"}},"fontSize":"l"} --><p class="has-l-font-size" style="font-weight:700"><?php esc_html_e( 'リフトワン L1', 'helix-wt' ); ?></p><!-- /wp:paragraph -->
<!-- wp:paragraph {"fontSize":"s"} --><p class="has-s-font-size"><?php esc_html_e( '静かで低い位置まで下がる。迷ったらこの 1 台。', 'helix-wt' ); ?></p><!-- /wp:paragraph -->
<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} --><div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"cta","textColor":"cta-contrast"} --><div class="wp-block-button"><a class="wp-block-button__link has-cta-contrast-color has-cta-background-color has-text-color has-background wp-element-button" href="#" rel="sponsored nofollow"><?php esc_html_e( '公式サイトで最新価格を見る', 'helix-wt' ); ?></a></div><!-- /wp:button --></div><!-- /wp:buttons -->
</div>
<!-- /wp:group -->
