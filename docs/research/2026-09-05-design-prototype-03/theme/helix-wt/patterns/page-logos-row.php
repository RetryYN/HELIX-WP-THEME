<?php
/**
 * Title: 取引先・提携ロゴ枠
 * Slug: helix-wt-page/logos-row
 * Categories: helix-wt-page
 * Description: 段8（2026-09-06 PO 反応 19 回目 WT-EVT-0287「固定ページ継投で使えるパーツ」）の Claude 案。HP で logos/clients（B 44%）。ロゴは第三者の画像を使わずダミー枠。 文言・数値は PoC 用の架空。
 */
$u = get_theme_file_uri( 'assets/img' );
?>
<!-- wp:group {"anchor":"part-logos-row","className":"wt-part wt-part--logos-row","align":"full","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull wt-part wt-part--logos-row" id="part-logos-row">
<!-- wp:html -->
<div class="wt-lp-section-inner">
<p class="wt-eyebrow">CLIENTS</p><h2 id="part-logos-title"><?php esc_html_e( '取引先・提携（ロゴ枠）', 'helix-wt' ); ?></h2><p class="wt-part-logos__lead"><?php esc_html_e( '導入企業 320 社（PoC 用の架空値）。ロゴ枠は実運用で各社の許諾を得た画像に置き換える。', 'helix-wt' ); ?></p><ul class="wt-lp-logo-row wt-part-logos" aria-label="<?php echo esc_attr__( '取引先ロゴ枠', 'helix-wt' ); ?>"><li>LOGO 1</li><li>LOGO 2</li><li>LOGO 3</li><li>LOGO 4</li><li>LOGO 5</li><li>LOGO 6</li><li>LOGO 7</li><li>LOGO 8</li><li>LOGO 9</li><li>LOGO 10</li></ul>
</div>
<!-- /wp:html -->
</div>
<!-- /wp:group -->
