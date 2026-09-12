<?php
/**
 * Title: 対象者（こんな方におすすめ）
 * Slug: helix-wt-page/target-audience
 * Categories: helix-wt-page
 * Description: 段8（2026-09-06 PO 反応 19 回目 WT-EVT-0287「固定ページ継投で使えるパーツ」）の Claude 案。イベント主集計 n=40 で target-audience（A 69%）。チェック付きの箇条書き 2 列。 文言・数値は PoC 用の架空。
 */
$u = get_theme_file_uri( 'assets/img' );
?>
<!-- wp:group {"anchor":"part-target-audience","className":"wt-part wt-part--target-audience","align":"full","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull wt-part wt-part--target-audience" id="part-target-audience">
<!-- wp:html -->
<div class="wt-lp-section-inner">
<p class="wt-eyebrow">FOR WHOM</p><h2 id="part-audience-title"><?php esc_html_e( 'こんな方におすすめ', 'helix-wt' ); ?></h2><ul class="wt-part-audience"><li><i class="wt-i wt-i--check-circle" aria-hidden="true"></i><span><?php esc_html_e( '日報・申請・記録の集計に毎月 10 時間以上かかっている', 'helix-wt' ); ?></span></li><li><i class="wt-i wt-i--check-circle" aria-hidden="true"></i><span><?php esc_html_e( '担当者が変わるたびに手順が消える', 'helix-wt' ); ?></span></li><li><i class="wt-i wt-i--check-circle" aria-hidden="true"></i><span><?php esc_html_e( '何から改善すればよいか順番が決められない', 'helix-wt' ); ?></span></li><li><i class="wt-i wt-i--check-circle" aria-hidden="true"></i><span><?php esc_html_e( '従業員 5〜100 名の製造・士業・医療の経営者・管理部門', 'helix-wt' ); ?></span></li><li><i class="wt-i wt-i--check-circle" aria-hidden="true"></i><span><?php esc_html_e( 'はじめて業務改善に取り組む方', 'helix-wt' ); ?></span></li><li><i class="wt-i wt-i--check-circle" aria-hidden="true"></i><span><?php esc_html_e( '過去に導入したツールが定着しなかった方', 'helix-wt' ); ?></span></li></ul><p class="wt-part-audience__note"><?php esc_html_e( '対象外の方もお申し込みいただけますが、内容は上記の方向けです（PoC 用の文言）。', 'helix-wt' ); ?></p>
</div>
<!-- /wp:html -->
</div>
<!-- /wp:group -->
