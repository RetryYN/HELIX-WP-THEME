<?php
/**
 * Title: データグラフ: ゲージ（達成率）
 * Slug: helix-wt/graph-gauge
 * Categories: helix-wt
 * Description: WT-EVT-0273 の Claude 案。割合や達成率を半円ゲージ 3 つで示す型（conic-gradient）。JS なし。
 */
?>
<!-- wp:html -->
<figure class="wt-graph wt-graph--gauge" data-wt-graph="gauge">
<div class="wt-graph__gauges">
<div class="wt-graph__gaugewrap"><div class="wt-graph__gauge" style="--v:92" role="img" aria-label="満足度 92%"><b>92<small>%</small></b></div><span class="wt-graph__gauge-label">満足度</span></div>
<div class="wt-graph__gaugewrap"><div class="wt-graph__gauge" style="--v:78" role="img" aria-label="再購入意向 78%"><b>78<small>%</small></b></div><span class="wt-graph__gauge-label">再購入意向</span></div>
<div class="wt-graph__gaugewrap"><div class="wt-graph__gauge" style="--v:64" role="img" aria-label="推奨意向 64%"><b>64<small>%</small></b></div><span class="wt-graph__gauge-label">推奨意向</span></div>
</div>
<table class="wt-graph__data screen-reader-text"><caption>購入後アンケート（%）</caption><tbody><tr><th scope="row">満足度</th><td>92</td></tr><tr><th scope="row">再購入意向</th><td>78</td></tr><tr><th scope="row">推奨意向</th><td>64</td></tr></tbody></table>
<figcaption>購入後 3 か月のアンケート（編集部、n=120）</figcaption>
</figure>
<!-- /wp:html -->
