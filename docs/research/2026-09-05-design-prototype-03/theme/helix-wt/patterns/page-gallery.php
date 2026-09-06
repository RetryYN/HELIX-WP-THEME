<?php
/**
 * Title: ギャラリー（画像グリッド）
 * Slug: helix-wt-page/gallery
 * Categories: helix-wt-page
 * Description: 段8（2026-09-06 PO 反応 19 回目 WT-EVT-0287「固定ページ継投で使えるパーツ」）の Claude 案。未観察の追加提案（v2 で notes に「ギャラリー」1 件、区間としては未符号化）。6 枚のグリッド + 説明文。 文言・数値は PoC 用の架空。
 */
$u = get_theme_file_uri( 'assets/img' );
?>
<!-- wp:group {"anchor":"part-gallery","className":"wt-part wt-part--gallery","align":"full","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull wt-part wt-part--gallery" id="part-gallery">
<!-- wp:html -->
<div class="wt-lp-section-inner">
<p class="wt-eyebrow">GALLERY</p><h2 id="part-gallery-title">施設・作品ギャラリー</h2><ul class="wt-part-gallery"><li><figure><img src="<?php echo esc_url( $u ); ?>/media-pickup-1.jpg" alt="" width="640" height="400" loading="lazy" decoding="async"><figcaption>実習室</figcaption></figure></li><li><figure><img src="<?php echo esc_url( $u ); ?>/media-pickup-2.jpg" alt="" width="640" height="400" loading="lazy" decoding="async"><figcaption>講堂</figcaption></figure></li><li><figure><img src="<?php echo esc_url( $u ); ?>/media-pickup-3.jpg" alt="" width="640" height="400" loading="lazy" decoding="async"><figcaption>図書室</figcaption></figure></li><li><figure><img src="<?php echo esc_url( $u ); ?>/media-pickup-4.jpg" alt="" width="640" height="400" loading="lazy" decoding="async"><figcaption>カフェテリア</figcaption></figure></li><li><figure><img src="<?php echo esc_url( $u ); ?>/media-pickup-5.jpg" alt="" width="640" height="400" loading="lazy" decoding="async"><figcaption>学生作品 A</figcaption></figure></li><li><figure><img src="<?php echo esc_url( $u ); ?>/media-pickup-6.jpg" alt="" width="640" height="400" loading="lazy" decoding="async"><figcaption>学生作品 B</figcaption></figure></li></ul>
</div>
<!-- /wp:html -->
</div>
<!-- /wp:group -->
