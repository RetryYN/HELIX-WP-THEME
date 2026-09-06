<?php
/**
 * Title: 沿革（縦タイムライン）
 * Slug: helix-wt-page/timeline
 * Categories: helix-wt-page
 * Description: 段8（2026-09-06 PO 反応 19 回目 WT-EVT-0287「固定ページ継投で使えるパーツ」）の Claude 案。HP A で history/timeline。年 + 出来事の縦タイムライン（イベントの schedule timeline を転用）。 文言・数値は PoC 用の架空。
 */
$u = get_theme_file_uri( 'assets/img' );
?>
<!-- wp:group {"anchor":"part-timeline","className":"wt-part wt-part--timeline","align":"full","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull wt-part wt-part--timeline" id="part-timeline">
<!-- wp:html -->
<div class="wt-lp-section-inner">
<p class="wt-eyebrow">HISTORY</p><h2 id="part-timeline-title">沿革</h2><ol class="wt-part-timeline"><li><time datetime="2016">2016</time><div><b>創業</b><span>業務改善支援を 2 名で開始</span></div></li><li><time datetime="2018">2018</time><div><b>法人化</b><span>サンプル株式会社を設立（架空）</span></div></li><li><time datetime="2020">2020</time><div><b>オンライン支援を開始</b><span>全国の中小企業へ対応範囲を拡大</span></div></li><li><time datetime="2023">2023</time><div><b>支援 200 社</b><span>累計支援社数が 200 社に</span></div></li><li><time datetime="2026">2026</time><div><b>現在</b><span>従業員 24 名・支援 320 社（PoC 用の架空値）</span></div></li></ol>
</div>
<!-- /wp:html -->
</div>
<!-- /wp:group -->
