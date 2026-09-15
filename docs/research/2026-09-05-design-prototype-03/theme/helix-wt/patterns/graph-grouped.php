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
<ul class="wt-graph__legend"><li><i class="wt-graph__sw wt-graph__sw--a"></i><?php esc_html_e( '定価', 'helix-wt' ); ?></li><li><i class="wt-graph__sw wt-graph__sw--b"></i><?php esc_html_e( '実売（9 月）', 'helix-wt' ); ?></li></ul>
<div class="wt-graph__rows">
<div class="wt-graph__row"><span class="wt-graph__label"><?php esc_html_e( 'リフトワン L1', 'helix-wt' ); ?></span><span class="wt-graph__pair"><span class="wt-graph__bar" style="--v:67" data-v="7.5"><i></i></span><span class="wt-graph__bar wt-graph__bar--b" style="--v:53" data-v="5.9"><i></i></span></span></div>
<div class="wt-graph__row"><span class="wt-graph__label"><?php esc_html_e( 'スタンド・ライト S2', 'helix-wt' ); ?></span><span class="wt-graph__pair"><span class="wt-graph__bar" style="--v:44" data-v="4.9"><i></i></span><span class="wt-graph__bar wt-graph__bar--b" style="--v:35" data-v="3.9"><i></i></span></span></div>
<div class="wt-graph__row"><span class="wt-graph__label"><?php esc_html_e( 'フレックス・プロ F3', 'helix-wt' ); ?></span><span class="wt-graph__pair"><span class="wt-graph__bar" style="--v:88" data-v="9.8"><i></i></span><span class="wt-graph__bar wt-graph__bar--b" style="--v:80" data-v="8.9"><i></i></span></span></div>
</div>
<table class="wt-graph__data screen-reader-text"><caption><?php esc_html_e( '定価と実売価格（万円）', 'helix-wt' ); ?></caption><thead><tr><th scope="col"><?php esc_html_e( '製品', 'helix-wt' ); ?></th><th scope="col"><?php esc_html_e( '定価', 'helix-wt' ); ?></th><th scope="col"><?php esc_html_e( '実売', 'helix-wt' ); ?></th></tr></thead><tbody><tr><th scope="row"><?php esc_html_e( 'リフトワン L1', 'helix-wt' ); ?></th><td>7.5</td><td>5.9</td></tr><tr><th scope="row"><?php esc_html_e( 'スタンド・ライト S2', 'helix-wt' ); ?></th><td>4.9</td><td>3.9</td></tr><tr><th scope="row"><?php esc_html_e( 'フレックス・プロ F3', 'helix-wt' ); ?></th><td>9.8</td><td>8.9</td></tr></tbody></table>
<figcaption><?php esc_html_e( '定価と実売価格の比較（万円、編集部調べ）', 'helix-wt' ); ?></figcaption>
</figure>
<!-- /wp:html -->
