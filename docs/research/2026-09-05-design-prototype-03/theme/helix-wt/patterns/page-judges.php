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
<p class="wt-eyebrow">JUDGES</p><h2 id="part-judges-title">審査員</h2><ul class="wt-part-judges"><li><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt="" width="160" height="160" loading="lazy" decoding="async"><b>審査員 A</b><small>業務改善コンサルタント</small><span>製造業 120 社の現場改善を支援（架空）</span></li><li><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt="" width="160" height="160" loading="lazy" decoding="async"><b>審査員 B</b><small>士業事務所 代表</small><span>問い合わせ対応の 1 本化が専門（架空）</span></li><li><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt="" width="160" height="160" loading="lazy" decoding="async"><b>審査員 C</b><small>医療法人 事務長</small><span>現場の記録改善を 10 年（架空）</span></li></ul>
</div>
<!-- /wp:html -->
</div>
<!-- /wp:group -->
