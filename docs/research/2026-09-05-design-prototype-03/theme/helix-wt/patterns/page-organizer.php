<?php
/**
 * Title: 主催・共催・後援
 * Slug: helix-wt-page/organizer
 * Categories: helix-wt-page
 * Description: 段8（2026-09-06 PO 反応 19 回目 WT-EVT-0287「固定ページ継投で使えるパーツ」）の Claude 案。イベント主集計で organizer 85%（A）。表 + 問い合わせ先。 文言・数値は PoC 用の架空。
 */
$u = get_theme_file_uri( 'assets/img' );
?>
<!-- wp:group {"anchor":"part-organizer","className":"wt-part wt-part--organizer","align":"full","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull wt-part wt-part--organizer" id="part-organizer">
<!-- wp:html -->
<div class="wt-lp-section-inner">
<p class="wt-eyebrow">ORGANIZER</p><h2 id="part-organizer-title"><?php esc_html_e( '主催・お問い合わせ', 'helix-wt' ); ?></h2><table class="wt-part-organizer"><tbody><tr><th scope="row"><?php esc_html_e( '主催', 'helix-wt' ); ?></th><td><?php esc_html_e( 'サンプル株式会社（架空）', 'helix-wt' ); ?></td></tr><tr><th scope="row"><?php esc_html_e( '共催', 'helix-wt' ); ?></th><td><?php esc_html_e( 'サンプル商工会（架空）', 'helix-wt' ); ?></td></tr><tr><th scope="row"><?php esc_html_e( '後援', 'helix-wt' ); ?></th><td><?php esc_html_e( '設定された自治体・団体（PoC 用の表記）', 'helix-wt' ); ?></td></tr><tr><th scope="row"><?php esc_html_e( '運営事務局', 'helix-wt' ); ?></th><td><?php esc_html_e( 'サンプル株式会社 イベント事務局', 'helix-wt' ); ?></td></tr><tr><th scope="row"><?php esc_html_e( 'お問い合わせ', 'helix-wt' ); ?></th><td><a href="#part-organizer"><?php esc_html_e( 'event@example.invalid（ダミー）', 'helix-wt' ); ?></a><?php esc_html_e( '／000-000-0000（平日 9:00-18:00）', 'helix-wt' ); ?></td></tr></tbody></table>
</div>
<!-- /wp:html -->
</div>
<!-- /wp:group -->
