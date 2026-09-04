<?php
/**
 * Title: 商品カード束（画像・名前・星・価格・CTA ×2・PR）
 * Slug: helix-wt/product-bundle
 * Categories: helix-wt
 * Description: product-card-bundle 型。比較媒体の記事内 CTA 既定
 */
$u = get_theme_file_uri( 'assets/img' );
?>
<!-- wp:group {"className":"is-style-wt-product wt-product wt-reveal","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-wt-product wt-product wt-reveal"><!-- wp:image {"sizeSlug":"full"} --><figure class="wp-block-image size-full"><img src="<?php echo esc_url( $u ); ?>/product-a.png" alt="リフトワン L1 の製品画像" width="512" height="512"/></figure><!-- /wp:image -->
<!-- wp:group {"layout":{"type":"flow"},"style":{"spacing":{"blockGap":"0.25rem"}}} -->
<div class="wp-block-group"><!-- wp:paragraph {"className":"wt-product__head"} --><p class="wt-product__head"><span class="wt-badge wt-badge--rank">総合 1 位</span><span class="wt-stars" aria-label="5 点満点中 4.6 点"><span aria-hidden="true">★★★★★</span><b>4.6</b></span><span class="wt-badge wt-badge--pr">PR</span></p><!-- /wp:paragraph --><!-- wp:paragraph {"className":"wt-product__name"} --><p class="wt-product__name">リフトワン L1（電動・メモリー 4 件）</p><!-- /wp:paragraph --><!-- wp:paragraph {"className":"wt-product__price"} --><p class="wt-product__price">59,800<small>円（税込・送料込）</small></p><!-- /wp:paragraph --><!-- wp:list {"className":"is-style-wt-check","fontSize":"s"} --><ul class="wp-block-list is-style-wt-check has-s-font-size"><li>昇降 62〜127cm、耐荷重 100kg</li><li>天板 120×60 / 140×70 の 2 サイズ</li><li>5 年保証・30 日返品可</li></ul><!-- /wp:list --><!-- wp:buttons {"layout":{"type":"flex","flexWrap":"wrap"}} --><div class="wp-block-buttons"><!-- wp:button {"backgroundColor":"cta","textColor":"cta-contrast"} --><div class="wp-block-button"><a class="wp-block-button__link has-cta-contrast-color has-cta-background-color has-text-color has-background wp-element-button" href="#" rel="sponsored nofollow">公式サイトで価格を見る</a></div><!-- /wp:button --><!-- wp:button {"className":"is-style-outline"} --><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#h-5">レビューを読む</a></div><!-- /wp:button --></div><!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
