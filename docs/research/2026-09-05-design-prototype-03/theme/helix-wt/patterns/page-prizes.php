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
<p class="wt-eyebrow">PRIZES</p><h2 id="part-prizes-title">賞品</h2><ul class="wt-part-prizes"><li class="is-top"><span class="wt-part-prizes__rank">A 賞</span><img src="<?php echo esc_url( $u ); ?>/product-a.png" alt="" width="640" height="400" loading="lazy" decoding="async"><b>業務改善パッケージ 1 年分</b><span>1 名</span></li><li><span class="wt-part-prizes__rank">B 賞</span><img src="<?php echo esc_url( $u ); ?>/media-pickup-4.jpg" alt="" width="640" height="400" loading="lazy" decoding="async"><b>書籍セット</b><span>10 名</span></li><li><span class="wt-part-prizes__rank">参加賞</span><img src="<?php echo esc_url( $u ); ?>/feature-2.png" alt="" width="640" height="400" loading="lazy" decoding="async"><b>チェックリスト PDF</b><span>全員</span></li></ul><p class="wt-lp-form__note">賞品・当選者数は PoC 用の架空値。当選は発送をもって代えさせていただきます（表記の例）。</p>
</div>
<!-- /wp:html -->
</div>
<!-- /wp:group -->
