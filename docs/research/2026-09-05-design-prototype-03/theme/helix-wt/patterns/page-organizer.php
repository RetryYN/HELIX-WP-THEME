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
<p class="wt-eyebrow">ORGANIZER</p><h2 id="part-organizer-title">主催・お問い合わせ</h2><table class="wt-part-organizer"><tbody><tr><th scope="row">主催</th><td>サンプル株式会社（架空）</td></tr><tr><th scope="row">共催</th><td>サンプル商工会（架空）</td></tr><tr><th scope="row">後援</th><td>設定された自治体・団体（PoC 用の表記）</td></tr><tr><th scope="row">運営事務局</th><td>サンプル株式会社 イベント事務局</td></tr><tr><th scope="row">お問い合わせ</th><td><a href="#part-organizer">event@example.invalid（ダミー）</a>／000-000-0000（平日 9:00-18:00）</td></tr></tbody></table>
</div>
<!-- /wp:html -->
</div>
<!-- /wp:group -->
