<?php
/**
 * Title: データグラフ: 折れ線（推移）
 * Slug: helix-wt/graph-line
 * Categories: helix-wt
 * Description: 時系列の推移を inline SVG で示す型。JS なし。線・点・目盛りはテーマ色で描く。
 */
?>
<!-- wp:html -->
<figure class="wt-graph wt-graph--line" data-wt-graph="line">
<svg class="wt-graph__svg" viewBox="0 0 320 170" role="img" aria-label="実売価格の推移（万円）: 1月 5.9、2月 5.8、3月 5.4、4月 5.5、5月 4.9、6月 4.6">
<g class="wt-graph__grid"><line x1="40" y1="20" x2="300" y2="20"/><line x1="40" y1="60" x2="300" y2="60"/><line x1="40" y1="100" x2="300" y2="100"/><line x1="40" y1="140" x2="300" y2="140"/></g>
<g class="wt-graph__ticks"><text x="34" y="24">6.0</text><text x="34" y="64">5.5</text><text x="34" y="104">5.0</text><text x="34" y="144">4.5</text></g>
<polyline class="wt-graph__line" points="52,28 100,36 148,68 196,60 244,108 292,132"/>
<g class="wt-graph__dots"><circle cx="52" cy="28" r="4"/><circle cx="100" cy="36" r="4"/><circle cx="148" cy="68" r="4"/><circle cx="196" cy="60" r="4"/><circle cx="244" cy="108" r="4"/><circle cx="292" cy="132" r="4"/></g>
<g class="wt-graph__labels"><text x="52" y="162">1月</text><text x="100" y="162">2月</text><text x="148" y="162">3月</text><text x="196" y="162">4月</text><text x="244" y="162">5月</text><text x="292" y="162">6月</text></g>
</svg>
<table class="wt-graph__data screen-reader-text"><caption>実売価格の推移（万円）</caption><tbody><tr><th scope="row">1月</th><td>5.9</td></tr><tr><th scope="row">2月</th><td>5.8</td></tr><tr><th scope="row">3月</th><td>5.4</td></tr><tr><th scope="row">4月</th><td>5.5</td></tr><tr><th scope="row">5月</th><td>4.9</td></tr><tr><th scope="row">6月</th><td>4.6</td></tr></tbody></table>
<figcaption>リフトワン L1 の実売価格の推移（万円、編集部調べ）</figcaption>
</figure>
<!-- /wp:html -->
