<?php
/**
 * Title: 対象商品
 * Slug: helix-wt-page/target-products
 * Categories: helix-wt-page
 * Description: 段8（2026-09-06 PO 反応 19 回目 WT-EVT-0287「固定ページ継投で使えるパーツ」）の Claude 案。キャンペーン D で target-products 50%。商品カード 4 枚。 文言・数値は PoC 用の架空。
 */
$u = get_theme_file_uri( 'assets/img' );
?>
<!-- wp:group {"anchor":"part-target-products","className":"wt-part wt-part--target-products","align":"full","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull wt-part wt-part--target-products" id="part-target-products">
<!-- wp:html -->
<div class="wt-lp-section-inner">
<p class="wt-eyebrow">PRODUCTS</p><h2 id="part-products-title">対象商品</h2><ul class="wt-part-products"><li><img src="<?php echo esc_url( $u ); ?>/product-a.png" alt="" width="640" height="400" loading="lazy" decoding="async"><b>サンプル商品 A</b><span>1,200 円</span></li><li><img src="<?php echo esc_url( $u ); ?>/feature-1.png" alt="" width="640" height="400" loading="lazy" decoding="async"><b>サンプル商品 B</b><span>980 円</span></li><li><img src="<?php echo esc_url( $u ); ?>/feature-3.png" alt="" width="640" height="400" loading="lazy" decoding="async"><b>サンプル商品 C</b><span>1,500 円</span></li><li><img src="<?php echo esc_url( $u ); ?>/media-pickup-3.jpg" alt="" width="640" height="400" loading="lazy" decoding="async"><b>サンプル商品 D（限定）</b><span>2,400 円</span></li></ul>
</div>
<!-- /wp:html -->
</div>
<!-- /wp:group -->
