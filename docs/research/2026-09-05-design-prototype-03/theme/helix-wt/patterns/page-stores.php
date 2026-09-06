<?php
/**
 * Title: 店舗・拠点一覧（カード + 検索欄）
 * Slug: helix-wt-page/stores
 * Categories: helix-wt-page
 * Description: 段8（2026-09-06 PO 反応 19 回目 WT-EVT-0287「固定ページ継投で使えるパーツ」）の Claude 案。HP D（店舗・スクール）の stores。検索欄は非送信の PoC。 文言・数値は PoC 用の架空。
 */
$u = get_theme_file_uri( 'assets/img' );
?>
<!-- wp:group {"anchor":"part-stores","className":"wt-part wt-part--stores","align":"full","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull wt-part wt-part--stores" id="part-stores">
<!-- wp:html -->
<div class="wt-lp-section-inner">
<p class="wt-eyebrow">STORES</p><h2 id="part-stores-title">店舗・拠点</h2><div class="wt-part-stores__search" role="search" aria-label="店舗を探す"><label for="part-stores-q">エリア・駅名で探す</label><input id="part-stores-q" type="search" name="q" placeholder="例: 中央駅" autocomplete="off"><button type="button" aria-describedby="part-stores-note"><i class="wt-i wt-i--s wt-i--search" aria-hidden="true"></i>探す</button></div><p id="part-stores-note" class="wt-lp-form__note">PoC のため検索は動作しない（送信しない）。</p><ul class="wt-part-stores"><li><a href="#part-stores"><img src="<?php echo esc_url( $u ); ?>/case-factory.jpg" alt="" width="640" height="400" loading="lazy" decoding="async"><b>中央店</b><span class="wt-part-stores__addr"><i class="wt-i wt-i--s wt-i--pin" aria-hidden="true"></i>設定された所在地 1</span><span class="wt-part-stores__hours"><i class="wt-i wt-i--s wt-i--clock" aria-hidden="true"></i>10:00-19:00 / 水休</span><span class="wt-part-stores__tel">000-000-0001（ダミー）</span></a></li><li><a href="#part-stores"><img src="<?php echo esc_url( $u ); ?>/case-tax.jpg" alt="" width="640" height="400" loading="lazy" decoding="async"><b>駅前校</b><span class="wt-part-stores__addr"><i class="wt-i wt-i--s wt-i--pin" aria-hidden="true"></i>設定された所在地 2</span><span class="wt-part-stores__hours"><i class="wt-i wt-i--s wt-i--clock" aria-hidden="true"></i>9:00-21:00 / 年中無休</span><span class="wt-part-stores__tel">000-000-0002（ダミー）</span></a></li><li><a href="#part-stores"><img src="<?php echo esc_url( $u ); ?>/case-clinic.jpg" alt="" width="640" height="400" loading="lazy" decoding="async"><b>北クリニック</b><span class="wt-part-stores__addr"><i class="wt-i wt-i--s wt-i--pin" aria-hidden="true"></i>設定された所在地 3</span><span class="wt-part-stores__hours"><i class="wt-i wt-i--s wt-i--clock" aria-hidden="true"></i>9:00-12:30・15:00-18:30</span><span class="wt-part-stores__tel">000-000-0003（ダミー）</span></a></li></ul>
</div>
<!-- /wp:html -->
</div>
<!-- /wp:group -->
