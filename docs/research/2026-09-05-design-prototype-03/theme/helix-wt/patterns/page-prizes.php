<?php
/**
 * Title: 賞品・特典
 * Slug: helix-wt-page/prizes
 * Categories: helix-wt-page
 * Description: 段8（2026-09-06 PO 反応 19 回目 WT-EVT-0287「固定ページ継投で使えるパーツ」）の Claude 案。キャンペーン D（n=8）で prizes 62%。A 賞 / B 賞 / 参加賞のカード。 文言・数値は PoC 用の架空。
 */
$u = get_theme_file_uri( 'assets/img' );
?>
<!-- wp:group {"anchor":"part-prizes","className":"wt-part wt-part--prizes","align":"full","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull wt-part wt-part--prizes" id="part-prizes">
<!-- wp:html -->
<div class="wt-lp-section-inner">
<p class="wt-eyebrow">PRIZES</p><h2 id="part-prizes-title"><?php esc_html_e( '賞品', 'helix-wt' ); ?></h2><ul class="wt-part-prizes"><li class="is-top"><span class="wt-part-prizes__rank"><?php esc_html_e( 'A 賞', 'helix-wt' ); ?></span><img src="<?php echo esc_url( $u ); ?>/product-a.png" alt="" width="640" height="400" loading="lazy" decoding="async"><b><?php esc_html_e( '業務改善パッケージ 1 年分', 'helix-wt' ); ?></b><span><?php esc_html_e( '1 名', 'helix-wt' ); ?></span></li><li><span class="wt-part-prizes__rank"><?php esc_html_e( 'B 賞', 'helix-wt' ); ?></span><img src="<?php echo esc_url( $u ); ?>/media-pickup-4.jpg" alt="" width="640" height="400" loading="lazy" decoding="async"><b><?php esc_html_e( '書籍セット', 'helix-wt' ); ?></b><span><?php esc_html_e( '10 名', 'helix-wt' ); ?></span></li><li><span class="wt-part-prizes__rank"><?php esc_html_e( '参加賞', 'helix-wt' ); ?></span><img src="<?php echo esc_url( $u ); ?>/feature-2.png" alt="" width="640" height="400" loading="lazy" decoding="async"><b><?php esc_html_e( 'チェックリスト PDF', 'helix-wt' ); ?></b><span><?php esc_html_e( '全員', 'helix-wt' ); ?></span></li></ul><p class="wt-lp-form__note"><?php esc_html_e( '賞品・当選者数は PoC 用の架空値。当選は発送をもって代えさせていただきます（表記の例）。', 'helix-wt' ); ?></p>
</div>
<!-- /wp:html -->
</div>
<!-- /wp:group -->
