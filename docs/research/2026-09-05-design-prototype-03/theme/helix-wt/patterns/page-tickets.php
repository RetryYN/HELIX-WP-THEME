<?php
/**
 * Title: 参加区分・料金
 * Slug: helix-wt-page/tickets
 * Categories: helix-wt-page
 * Description: 段8（2026-09-06 PO 反応 19 回目 WT-EVT-0287「固定ページ継投で使えるパーツ」）の Claude 案。イベント主集計で tickets/price（B 75%）。3 区分のカード（イベントページの tickets を固定ページ用に再登録）。 文言・数値は PoC 用の架空。
 */
$u = get_theme_file_uri( 'assets/img' );
?>
<!-- wp:group {"anchor":"part-tickets","className":"wt-part wt-part--tickets","align":"full","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull wt-part wt-part--tickets" id="part-tickets">
<!-- wp:html -->
<div class="wt-lp-section-inner">
<p class="wt-eyebrow">TICKETS</p><h2 id="part-tickets-title">参加区分・料金</h2><ul class="wt-event-tickets wt-part-tickets"><li><b>一般</b><p class="wt-event-tickets__price">3,000<small>円</small></p><span>当日会場でお支払い</span></li><li class="is-featured"><b>早割</b><p class="wt-event-tickets__price">2,000<small>円</small></p><span>9 月末までの申込・資料付き</span></li><li><b>学生</b><p class="wt-event-tickets__price">無料</p><span>学生証の提示が必要</span></li></ul><p class="wt-lp-form__note">価格は PoC 用の架空値（税込表記の例）。</p>
</div>
<!-- /wp:html -->
</div>
<!-- /wp:group -->
