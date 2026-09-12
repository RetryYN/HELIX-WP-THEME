<?php
/**
 * Title: データグラフ: 評価スコア（5 段階）
 * Slug: helix-wt/graph-score
 * Categories: helix-wt
 * Description: WT-EVT-0273 の Claude 案。評価軸ごとの 5 段階スコアを区切り付きバーで示す型（レビュー記事の総評向け）。JS なし。
 */
?>
<!-- wp:html -->
<figure class="wt-graph wt-graph--score" data-wt-graph="score">
<div class="wt-graph__rows">
<div class="wt-graph__row"><span class="wt-graph__label"><?php esc_html_e( '静音性', 'helix-wt' ); ?></span><span class="wt-graph__score" data-v="5"><i></i><i></i><i></i><i></i><i></i></span><span class="wt-graph__val">5</span></div>
<div class="wt-graph__row"><span class="wt-graph__label"><?php esc_html_e( '安定性', 'helix-wt' ); ?></span><span class="wt-graph__score" data-v="4"><i></i><i></i><i></i><i></i><i></i></span><span class="wt-graph__val">4</span></div>
<div class="wt-graph__row"><span class="wt-graph__label"><?php esc_html_e( '昇降範囲', 'helix-wt' ); ?></span><span class="wt-graph__score" data-v="4"><i></i><i></i><i></i><i></i><i></i></span><span class="wt-graph__val">4</span></div>
<div class="wt-graph__row"><span class="wt-graph__label"><?php esc_html_e( '組み立て', 'helix-wt' ); ?></span><span class="wt-graph__score" data-v="3"><i></i><i></i><i></i><i></i><i></i></span><span class="wt-graph__val">3</span></div>
<div class="wt-graph__row"><span class="wt-graph__label"><?php esc_html_e( '価格', 'helix-wt' ); ?></span><span class="wt-graph__score" data-v="2"><i></i><i></i><i></i><i></i><i></i></span><span class="wt-graph__val">2</span></div>
</div>
<table class="wt-graph__data screen-reader-text"><caption><?php esc_html_e( 'リフトワン L1 の評価（5 点満点）', 'helix-wt' ); ?></caption><tbody><tr><th scope="row"><?php esc_html_e( '静音性', 'helix-wt' ); ?></th><td>5</td></tr><tr><th scope="row"><?php esc_html_e( '安定性', 'helix-wt' ); ?></th><td>4</td></tr><tr><th scope="row"><?php esc_html_e( '昇降範囲', 'helix-wt' ); ?></th><td>4</td></tr><tr><th scope="row"><?php esc_html_e( '組み立て', 'helix-wt' ); ?></th><td>3</td></tr><tr><th scope="row"><?php esc_html_e( '価格', 'helix-wt' ); ?></th><td>2</td></tr></tbody></table>
<figcaption><?php esc_html_e( 'リフトワン L1 の項目別評価（5 点満点、編集部）', 'helix-wt' ); ?></figcaption>
</figure>
<!-- /wp:html -->
