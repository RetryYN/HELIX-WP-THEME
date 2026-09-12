<?php
/**
 * Title: 審査員（カード）
 * Slug: helix-wt-page/judges
 * Categories: helix-wt-page
 * Description: 段8（2026-09-06 PO 反応 19 回目 WT-EVT-0287「固定ページ継投で使えるパーツ」）の Claude 案。コンテスト D で judges。登壇者カードを転用。 文言・数値は PoC 用の架空。
 */
$u = get_theme_file_uri( 'assets/img' );
?>
<!-- wp:group {"anchor":"part-judges","className":"wt-part wt-part--judges","align":"full","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull wt-part wt-part--judges" id="part-judges">
<!-- wp:html -->
<div class="wt-lp-section-inner">
<p class="wt-eyebrow">JUDGES</p><h2 id="part-judges-title"><?php esc_html_e( '審査員', 'helix-wt' ); ?></h2><ul class="wt-part-judges"><li><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt="" width="160" height="160" loading="lazy" decoding="async"><b><?php esc_html_e( '審査員 A', 'helix-wt' ); ?></b><small><?php esc_html_e( '業務改善コンサルタント', 'helix-wt' ); ?></small><span><?php esc_html_e( '製造業 120 社の現場改善を支援（架空）', 'helix-wt' ); ?></span></li><li><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt="" width="160" height="160" loading="lazy" decoding="async"><b><?php esc_html_e( '審査員 B', 'helix-wt' ); ?></b><small><?php esc_html_e( '士業事務所 代表', 'helix-wt' ); ?></small><span><?php esc_html_e( '問い合わせ対応の 1 本化が専門（架空）', 'helix-wt' ); ?></span></li><li><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt="" width="160" height="160" loading="lazy" decoding="async"><b><?php esc_html_e( '審査員 C', 'helix-wt' ); ?></b><small><?php esc_html_e( '医療法人 事務長', 'helix-wt' ); ?></small><span><?php esc_html_e( '現場の記録改善を 10 年（架空）', 'helix-wt' ); ?></span></li></ul>
</div>
<!-- /wp:html -->
</div>
<!-- /wp:group -->
