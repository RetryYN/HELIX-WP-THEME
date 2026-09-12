<?php
/**
 * Title: データグラフ（横棒比較・割合バー・ドーナツ・折れ線）
 * Slug: helix-wt/graphs
 * Categories: helix-wt
 * Description: 2026-09-05 PO 反応 15 回目（WT-EVT-0257「データグラフいれない？」）の Claude 案 4 型。JS なし・CSS/SVG のみ。
 *              値は CSS 変数（--v）と data 属性で持ち、視覚化の下に同じ値の表（screen-reader-text）を置く。
 */
?>
<!-- wp:html -->
<figure class="wt-graph wt-graph--bar" data-wt-graph="bar">
<div class="wt-graph__rows">
<div class="wt-graph__row" style="--v:62"><span class="wt-graph__label"><?php esc_html_e( 'リフトワン L1', 'helix-wt' ); ?></span><span class="wt-graph__bar"><i></i></span><span class="wt-graph__val">62<small>cm</small></span></div>
<div class="wt-graph__row" style="--v:71"><span class="wt-graph__label"><?php esc_html_e( 'スタンド・ライト S2', 'helix-wt' ); ?></span><span class="wt-graph__bar"><i></i></span><span class="wt-graph__val">71<small>cm</small></span></div>
<div class="wt-graph__row" style="--v:58"><span class="wt-graph__label"><?php esc_html_e( 'デスクフロー D3', 'helix-wt' ); ?></span><span class="wt-graph__bar"><i></i></span><span class="wt-graph__val">58<small>cm</small></span></div>
</div>
<table class="wt-graph__data screen-reader-text"><caption><?php esc_html_e( '最低高さ（cm、低いほど良い）', 'helix-wt' ); ?></caption><tbody><tr><th scope="row"><?php esc_html_e( 'リフトワン L1', 'helix-wt' ); ?></th><td>62</td></tr><tr><th scope="row"><?php esc_html_e( 'スタンド・ライト S2', 'helix-wt' ); ?></th><td>71</td></tr><tr><th scope="row"><?php esc_html_e( 'デスクフロー D3', 'helix-wt' ); ?></th><td>58</td></tr></tbody></table>
<figcaption><?php esc_html_e( '最低高さの比較（cm、低いほど良い）', 'helix-wt' ); ?></figcaption>
</figure>
<!-- /wp:html -->
