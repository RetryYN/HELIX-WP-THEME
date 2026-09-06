<?php
/**
 * Title: ご挨拶（写真 + 挨拶文）
 * Slug: helix-wt-page/greeting
 * Categories: helix-wt-page
 * Description: 段8（2026-09-06 PO 反応 19 回目 WT-EVT-0287「固定ページ継投で使えるパーツ」）の Claude 案。HP 集計対象 n=62 で greeting 40%（A 65%）。写真 + 見出し + 本文 + 署名。 文言・数値は PoC 用の架空。
 */
$u = get_theme_file_uri( 'assets/img' );
?>
<!-- wp:group {"anchor":"part-greeting","className":"wt-part wt-part--greeting","align":"full","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull wt-part wt-part--greeting" id="part-greeting">
<!-- wp:html -->
<div class="wt-lp-section-inner">
<div class="wt-part-greeting"><figure class="wt-part-greeting__photo"><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt="" width="320" height="320" loading="lazy" decoding="async"><figcaption>代表取締役 サンプル 太郎（架空）</figcaption></figure><div class="wt-part-greeting__body"><p class="wt-eyebrow">GREETING</p><h2 id="part-greeting-title">現場の言葉で、仕組みを残す。</h2><p>私たちは、担当者が変わっても回る仕組みを現場と一緒に作ることを大切にしています。見た目より先に流れを決め、翌日から使える形で引き渡します。</p><p>これからも、地域の中小企業の「困った」に最初に相談される会社であり続けます（PoC 用の文言）。</p><p class="wt-part-greeting__sign">サンプル株式会社 代表取締役 <b>サンプル 太郎</b></p></div></div>
</div>
<!-- /wp:html -->
</div>
<!-- /wp:group -->
