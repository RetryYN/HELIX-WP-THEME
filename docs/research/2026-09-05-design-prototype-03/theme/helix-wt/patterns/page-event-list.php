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
<p class="wt-eyebrow">EVENTS</p><h2 id="part-events-title"><?php esc_html_e( 'イベント・説明会', 'helix-wt' ); ?></h2><ul class="wt-part-events"><li><a href="/event/"><span class="wt-part-events__date"><b>10/15</b><small><?php esc_html_e( '木', 'helix-wt' ); ?></small></span><span class="wt-part-events__body"><span class="wt-part-events__status is-open"><?php esc_html_e( '受付中', 'helix-wt' ); ?></span><b><?php esc_html_e( '業務改善セミナー 2026 秋', 'helix-wt' ); ?></b><span><i class="wt-i wt-i--s wt-i--pin" aria-hidden="true"></i><?php esc_html_e( 'オンライン + 会場', 'helix-wt' ); ?></span></span></a></li><li><a href="/event/"><span class="wt-part-events__date"><b>11/02</b><small><?php esc_html_e( '日', 'helix-wt' ); ?></small></span><span class="wt-part-events__body"><span class="wt-part-events__status is-open"><?php esc_html_e( '受付中', 'helix-wt' ); ?></span><b><?php esc_html_e( 'オープンキャンパス（体験授業）', 'helix-wt' ); ?></b><span><i class="wt-i wt-i--s wt-i--pin" aria-hidden="true"></i><?php esc_html_e( '本校キャンパス', 'helix-wt' ); ?></span></span></a></li><li><a href="/event/"><span class="wt-part-events__date"><b>11/20</b><small><?php esc_html_e( '金', 'helix-wt' ); ?></small></span><span class="wt-part-events__body"><span class="wt-part-events__status is-few"><?php esc_html_e( '残席わずか', 'helix-wt' ); ?></span><b><?php esc_html_e( '個別相談会（平日夜）', 'helix-wt' ); ?></b><span><i class="wt-i wt-i--s wt-i--pin" aria-hidden="true"></i><?php esc_html_e( '駅前校', 'helix-wt' ); ?></span></span></a></li><li><a href="/event/"><span class="wt-part-events__date"><b>12/06</b><small><?php esc_html_e( '土', 'helix-wt' ); ?></small></span><span class="wt-part-events__body"><span class="wt-part-events__status is-ended"><?php esc_html_e( '受付終了', 'helix-wt' ); ?></span><b><?php esc_html_e( '年末感謝祭', 'helix-wt' ); ?></b><span><i class="wt-i wt-i--s wt-i--pin" aria-hidden="true"></i><?php esc_html_e( '中央店', 'helix-wt' ); ?></span></span></a></li></ul><p class="wt-part-events__more"><a href="/event/"><?php esc_html_e( 'イベント一覧を見る', 'helix-wt' ); ?> <i class="wt-i wt-i--s wt-i--arrow-right" aria-hidden="true"></i></a></p>
</div>
<!-- /wp:html -->
</div>
<!-- /wp:group -->
