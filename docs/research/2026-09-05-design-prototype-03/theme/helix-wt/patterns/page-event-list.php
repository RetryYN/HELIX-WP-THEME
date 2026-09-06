<?php
/**
 * Title: イベント一覧（日付ブロック + カード）
 * Slug: helix-wt-page/event-list
 * Categories: helix-wt-page
 * Description: 段8（2026-09-06 PO 反応 19 回目 WT-EVT-0287「固定ページ継投で使えるパーツ」）の Claude 案。HP E（学校法人・団体）で events/open-campus 88%、D で events。日付ブロック + 場所 + 受付状態。 文言・数値は PoC 用の架空。
 */
$u = get_theme_file_uri( 'assets/img' );
?>
<!-- wp:group {"anchor":"part-event-list","className":"wt-part wt-part--event-list","align":"full","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull wt-part wt-part--event-list" id="part-event-list">
<!-- wp:html -->
<div class="wt-lp-section-inner">
<p class="wt-eyebrow">EVENTS</p><h2 id="part-events-title">イベント・説明会</h2><ul class="wt-part-events"><li><a href="/event/"><span class="wt-part-events__date"><b>10/15</b><small>木</small></span><span class="wt-part-events__body"><span class="wt-part-events__status is-open">受付中</span><b>業務改善セミナー 2026 秋</b><span><i class="wt-i wt-i--s wt-i--pin" aria-hidden="true"></i>オンライン + 会場</span></span></a></li><li><a href="/event/"><span class="wt-part-events__date"><b>11/02</b><small>日</small></span><span class="wt-part-events__body"><span class="wt-part-events__status is-open">受付中</span><b>オープンキャンパス（体験授業）</b><span><i class="wt-i wt-i--s wt-i--pin" aria-hidden="true"></i>本校キャンパス</span></span></a></li><li><a href="/event/"><span class="wt-part-events__date"><b>11/20</b><small>金</small></span><span class="wt-part-events__body"><span class="wt-part-events__status is-few">残席わずか</span><b>個別相談会（平日夜）</b><span><i class="wt-i wt-i--s wt-i--pin" aria-hidden="true"></i>駅前校</span></span></a></li><li><a href="/event/"><span class="wt-part-events__date"><b>12/06</b><small>土</small></span><span class="wt-part-events__body"><span class="wt-part-events__status is-ended">受付終了</span><b>年末感謝祭</b><span><i class="wt-i wt-i--s wt-i--pin" aria-hidden="true"></i>中央店</span></span></a></li></ul><p class="wt-part-events__more"><a href="/event/">イベント一覧を見る <i class="wt-i wt-i--s wt-i--arrow-right" aria-hidden="true"></i></a></p>
</div>
<!-- /wp:html -->
</div>
<!-- /wp:group -->
