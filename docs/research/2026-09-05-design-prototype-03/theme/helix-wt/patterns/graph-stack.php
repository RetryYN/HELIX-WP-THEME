<?php
/**
 * Title: データグラフ: 割合バー（100% 積み上げ）
 * Slug: helix-wt/graph-stack
 * Categories: helix-wt
 * Description: 構成比を 1 本の帯で示す型。各区分は --v（%）で幅を決める。
 */
?>
<!-- wp:html -->
<figure class="wt-graph wt-graph--stack" data-wt-graph="stack">
<div class="wt-graph__stack" role="img" aria-label="<?php echo esc_attr__( '用途の内訳: 在宅ワーク 54%、ゲーム 28%、学習 18%', 'helix-wt' ); ?>"><span class="wt-graph__seg wt-graph__seg--a" style="--v:54"><b>54%</b></span><span class="wt-graph__seg wt-graph__seg--b" style="--v:28"><b>28%</b></span><span class="wt-graph__seg wt-graph__seg--c" style="--v:18"><b>18%</b></span></div>
<ul class="wt-graph__legend"><li><i class="wt-graph__sw wt-graph__sw--a"></i><?php esc_html_e( '在宅ワーク', 'helix-wt' ); ?></li><li><i class="wt-graph__sw wt-graph__sw--b"></i><?php esc_html_e( 'ゲーム', 'helix-wt' ); ?></li><li><i class="wt-graph__sw wt-graph__sw--c"></i><?php esc_html_e( '学習', 'helix-wt' ); ?></li></ul>
<table class="wt-graph__data screen-reader-text"><caption><?php esc_html_e( '用途の内訳（%）', 'helix-wt' ); ?></caption><tbody><tr><th scope="row"><?php esc_html_e( '在宅ワーク', 'helix-wt' ); ?></th><td>54</td></tr><tr><th scope="row"><?php esc_html_e( 'ゲーム', 'helix-wt' ); ?></th><td>28</td></tr><tr><th scope="row"><?php esc_html_e( '学習', 'helix-wt' ); ?></th><td>18</td></tr></tbody></table>
<figcaption><?php esc_html_e( '購入者の用途の内訳（編集部アンケート、n=120）', 'helix-wt' ); ?></figcaption>
</figure>
<!-- /wp:html -->
