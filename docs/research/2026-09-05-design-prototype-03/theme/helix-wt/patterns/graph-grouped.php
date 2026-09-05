<?php
/**
 * Title: データグラフ: グループ横棒（2 系列比較）
 * Slug: helix-wt/graph-grouped
 * Categories: helix-wt
 * Description: 2026-09-06 PO 反応 17 回目（WT-EVT-0273「graph はパターン強化」）の Claude 案。項目ごとに 2 系列（例: 定価 / 実売）を並べる。--v は最大値 9.8 万円を 88% に置いた相対長（右端の数値ラベルの余白のため）。JS なし。
 */
?>
<!-- wp:html -->
<figure class="wt-graph wt-graph--grouped" data-wt-graph="grouped">
<ul class="wt-graph__legend"><li><i class="wt-graph__sw wt-graph__sw--a"></i>定価</li><li><i class="wt-graph__sw wt-graph__sw--b"></i>実売（9 月）</li></ul>
<div class="wt-graph__rows">
<div class="wt-graph__row"><span class="wt-graph__label">リフトワン L1</span><span class="wt-graph__pair"><span class="wt-graph__bar" style="--v:67" data-v="7.5"><i></i></span><span class="wt-graph__bar wt-graph__bar--b" style="--v:53" data-v="5.9"><i></i></span></span></div>
<div class="wt-graph__row"><span class="wt-graph__label">スタンド・ライト S2</span><span class="wt-graph__pair"><span class="wt-graph__bar" style="--v:44" data-v="4.9"><i></i></span><span class="wt-graph__bar wt-graph__bar--b" style="--v:35" data-v="3.9"><i></i></span></span></div>
<div class="wt-graph__row"><span class="wt-graph__label">フレックス・プロ F3</span><span class="wt-graph__pair"><span class="wt-graph__bar" style="--v:88" data-v="9.8"><i></i></span><span class="wt-graph__bar wt-graph__bar--b" style="--v:80" data-v="8.9"><i></i></span></span></div>
</div>
<table class="wt-graph__data screen-reader-text"><caption>定価と実売価格（万円）</caption><thead><tr><th scope="col">製品</th><th scope="col">定価</th><th scope="col">実売</th></tr></thead><tbody><tr><th scope="row">リフトワン L1</th><td>7.5</td><td>5.9</td></tr><tr><th scope="row">スタンド・ライト S2</th><td>4.9</td><td>3.9</td></tr><tr><th scope="row">フレックス・プロ F3</th><td>9.8</td><td>8.9</td></tr></tbody></table>
<figcaption>定価と実売価格の比較（万円、編集部調べ）</figcaption>
</figure>
<!-- /wp:html -->
