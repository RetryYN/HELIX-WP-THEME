<?php
/**
 * Title: データグラフ: ドーナツ
 * Slug: helix-wt/graph-donut
 * Categories: helix-wt
 * Description: 構成比を円で示す型。conic-gradient の境目を --a / --b（累積 %）で決め、中央に主値を置く。
 */
?>
<!-- wp:html -->
<figure class="wt-graph wt-graph--donut" data-wt-graph="donut">
<div class="wt-graph__donut-wrap">
<div class="wt-graph__donut" style="--a:54;--b:82" role="img" aria-label="<?php echo esc_attr__( '満足度: 満足 54%、ふつう 28%、不満 18%', 'helix-wt' ); ?>"><span class="wt-graph__center"><b>54<small>%</small></b><small><?php esc_html_e( '満足', 'helix-wt' ); ?></small></span></div>
<ul class="wt-graph__legend wt-graph__legend--col"><li><i class="wt-graph__sw wt-graph__sw--a"></i><?php esc_html_e( '満足 54%', 'helix-wt' ); ?></li><li><i class="wt-graph__sw wt-graph__sw--b"></i><?php esc_html_e( 'ふつう 28%', 'helix-wt' ); ?></li><li><i class="wt-graph__sw wt-graph__sw--c"></i><?php esc_html_e( '不満 18%', 'helix-wt' ); ?></li></ul>
</div>
<table class="wt-graph__data screen-reader-text"><caption><?php esc_html_e( '満足度（%）', 'helix-wt' ); ?></caption><tbody><tr><th scope="row"><?php esc_html_e( '満足', 'helix-wt' ); ?></th><td>54</td></tr><tr><th scope="row"><?php esc_html_e( 'ふつう', 'helix-wt' ); ?></th><td>28</td></tr><tr><th scope="row"><?php esc_html_e( '不満', 'helix-wt' ); ?></th><td>18</td></tr></tbody></table>
<figcaption><?php esc_html_e( '購入後 3 か月の満足度（編集部アンケート、n=120）', 'helix-wt' ); ?></figcaption>
</figure>
<!-- /wp:html -->
