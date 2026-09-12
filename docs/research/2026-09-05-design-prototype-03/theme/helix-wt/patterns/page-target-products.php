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
<p class="wt-eyebrow">PRODUCTS</p><h2 id="part-products-title"><?php esc_html_e( '対象商品', 'helix-wt' ); ?></h2><ul class="wt-part-products"><li><img src="<?php echo esc_url( $u ); ?>/product-a.png" alt="" width="640" height="400" loading="lazy" decoding="async"><b><?php esc_html_e( 'サンプル商品 A', 'helix-wt' ); ?></b><span><?php esc_html_e( '1,200 円', 'helix-wt' ); ?></span></li><li><img src="<?php echo esc_url( $u ); ?>/feature-1.png" alt="" width="640" height="400" loading="lazy" decoding="async"><b><?php esc_html_e( 'サンプル商品 B', 'helix-wt' ); ?></b><span><?php esc_html_e( '980 円', 'helix-wt' ); ?></span></li><li><img src="<?php echo esc_url( $u ); ?>/feature-3.png" alt="" width="640" height="400" loading="lazy" decoding="async"><b><?php esc_html_e( 'サンプル商品 C', 'helix-wt' ); ?></b><span><?php esc_html_e( '1,500 円', 'helix-wt' ); ?></span></li><li><img src="<?php echo esc_url( $u ); ?>/media-pickup-3.jpg" alt="" width="640" height="400" loading="lazy" decoding="async"><b><?php esc_html_e( 'サンプル商品 D（限定）', 'helix-wt' ); ?></b><span><?php esc_html_e( '2,400 円', 'helix-wt' ); ?></span></li></ul>
</div>
<!-- /wp:html -->
</div>
<!-- /wp:group -->
