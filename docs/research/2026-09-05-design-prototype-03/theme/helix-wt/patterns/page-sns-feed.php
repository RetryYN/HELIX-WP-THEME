<?php
/**
 * Title: SNS フィード（外部サービスの遅延埋め込み）
 * Slug: helix-wt-page/sns-feed
 * Categories: helix-wt-page
 * Description: 段8（2026-09-06 PO 反応 19 回目 WT-EVT-0287「固定ページ継投で使えるパーツ」）の Claude 案。HP E で sns-feed 62%。URL は option（helix_wt_sns_feed_embed_url）。未設定なら外部へ接続しない（WT-EVT-0284: WT-CAND-SNS の埋め込み方針）。 文言・数値は PoC 用の架空。
 */
$u = get_theme_file_uri( 'assets/img' );
?>
<!-- wp:group {"anchor":"part-sns-feed","className":"wt-part wt-part--sns-feed","align":"full","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull wt-part wt-part--sns-feed" id="part-sns-feed">
<!-- wp:html -->
<div class="wt-lp-section-inner">
<p class="wt-eyebrow">SNS</p><h2 id="part-sns-title">最新の投稿</h2><?php echo wt_render_sns_feed_embed(); ?><ul class="wt-part-sns__links" aria-label="公式アカウント（PoC のダミー導線）"><li><a class="wt-sns" href="#part-sns-feed" rel="nofollow" aria-label="X の公式アカウント"><i class="wt-i wt-i--sns-x" aria-hidden="true"></i></a></li><li><a class="wt-sns" href="#part-sns-feed" rel="nofollow" aria-label="Instagram の公式アカウント"><i class="wt-i wt-i--sns-ig" aria-hidden="true"></i></a></li><li><a class="wt-sns" href="#part-sns-feed" rel="nofollow" aria-label="YouTube の公式チャンネル"><i class="wt-i wt-i--sns-yt" aria-hidden="true"></i></a></li></ul>
</div>
<!-- /wp:html -->
</div>
<!-- /wp:group -->
