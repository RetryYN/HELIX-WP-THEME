<?php
/**
 * Title: 商品カード束（画像・名前・星・価格・CTA ×2）
 * Slug: helix-wt/product-bundle
 * Categories: helix-wt, helix-wt-page
 * Description: product-card-bundle 型。比較媒体の記事内 CTA 既定
 */
$u = get_theme_file_uri( 'assets/img' );
?>
<!-- wp:group {"className":"is-style-wt-product wt-product wt-reveal","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-wt-product wt-product wt-reveal"><!-- wp:image {"sizeSlug":"full"} --><figure class="wp-block-image size-full"><img src="<?php echo esc_url( $u ); ?>/product-a.png" alt="<?php echo esc_attr__( 'リフトワン L1 の製品画像', 'helix-wt' ); ?>" width="512" height="512"/></figure><!-- /wp:image -->
<!-- wp:group {"layout":{"type":"default"},"style":{"spacing":{"blockGap":"0.25rem"}}} -->
<div class="wp-block-group"><!-- wp:paragraph {"className":"wt-product__head"} --><p class="wt-product__head"><span class="wt-badge wt-badge--rank"><?php esc_html_e( '総合 1 位', 'helix-wt' ); ?></span><span class="wt-stars" aria-label="<?php echo esc_attr__( '5 点満点中 4.6 点', 'helix-wt' ); ?>"><span aria-hidden="true">★★★★★</span><b>4.6</b></span></p><!-- /wp:paragraph --><!-- wp:paragraph {"className":"wt-product__name"} --><p class="wt-product__name"><?php esc_html_e( 'リフトワン L1（電動・メモリー 4 件）', 'helix-wt' ); ?></p><!-- /wp:paragraph --><!-- wp:paragraph {"className":"wt-product__price"} --><p class="wt-product__price">59,800<small><?php esc_html_e( '円（税込・送料込）', 'helix-wt' ); ?></small></p><!-- /wp:paragraph --><!-- wp:list {"className":"is-style-wt-check","fontSize":"s"} --><ul class="wp-block-list is-style-wt-check has-s-font-size"><li><?php esc_html_e( '昇降 62〜127cm、耐荷重 100kg', 'helix-wt' ); ?></li><li><?php esc_html_e( '天板 120×60 / 140×70 の 2 サイズ', 'helix-wt' ); ?></li><li><?php esc_html_e( '5 年保証・30 日返品可', 'helix-wt' ); ?></li></ul><!-- /wp:list --><!-- wp:buttons {"layout":{"type":"flex","flexWrap":"wrap"}} --><div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"cta","textColor":"cta-contrast"} --><div class="wp-block-button"><a class="wp-block-button__link has-cta-contrast-color has-cta-background-color has-text-color has-background wp-element-button" href="#" rel="sponsored nofollow"><?php esc_html_e( '公式サイトで価格を見る', 'helix-wt' ); ?></a></div><!-- /wp:button --><!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#h-5"><?php esc_html_e( 'レビューを読む', 'helix-wt' ); ?></a></div><!-- /wp:button --></div><!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
