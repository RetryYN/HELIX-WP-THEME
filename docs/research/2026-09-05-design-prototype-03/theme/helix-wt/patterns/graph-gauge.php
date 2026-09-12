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
<div class="wt-graph__gaugewrap"><div class="wt-graph__gauge" style="--v:92" role="img" aria-label="<?php echo esc_attr__( '満足度 92%', 'helix-wt' ); ?>"><b>92<small>%</small></b></div><span class="wt-graph__gauge-label"><?php esc_html_e( '満足度', 'helix-wt' ); ?></span></div>
<div class="wt-graph__gaugewrap"><div class="wt-graph__gauge" style="--v:78" role="img" aria-label="<?php echo esc_attr__( '再購入意向 78%', 'helix-wt' ); ?>"><b>78<small>%</small></b></div><span class="wt-graph__gauge-label"><?php esc_html_e( '再購入意向', 'helix-wt' ); ?></span></div>
<div class="wt-graph__gaugewrap"><div class="wt-graph__gauge" style="--v:64" role="img" aria-label="<?php echo esc_attr__( '推奨意向 64%', 'helix-wt' ); ?>"><b>64<small>%</small></b></div><span class="wt-graph__gauge-label"><?php esc_html_e( '推奨意向', 'helix-wt' ); ?></span></div>
</div>
<table class="wt-graph__data screen-reader-text"><caption><?php esc_html_e( '購入後アンケート（%）', 'helix-wt' ); ?></caption><tbody><tr><th scope="row"><?php esc_html_e( '満足度', 'helix-wt' ); ?></th><td>92</td></tr><tr><th scope="row"><?php esc_html_e( '再購入意向', 'helix-wt' ); ?></th><td>78</td></tr><tr><th scope="row"><?php esc_html_e( '推奨意向', 'helix-wt' ); ?></th><td>64</td></tr></tbody></table>
<figcaption><?php esc_html_e( '購入後 3 か月のアンケート（編集部、n=120）', 'helix-wt' ); ?></figcaption>
</figure>
<!-- /wp:html -->
