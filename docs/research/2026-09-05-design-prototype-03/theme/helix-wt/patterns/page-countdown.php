<?php
/**
 * Title: カウントダウン（開催日まで）
 * Slug: helix-wt-page/countdown
 * Categories: helix-wt-page
 * Description: 段8（2026-09-06 PO 反応 19 回目 WT-EVT-0287「固定ページ継投で使えるパーツ」）の Claude 案。未観察の追加提案（v2 主集計 40 件で countdown は none 100%）。JS で残り日数を数え、JS 無効時は開催日の文字だけ出す。 文言・数値は PoC 用の架空。
 */
$u = get_theme_file_uri( 'assets/img' );
?>
<!-- wp:group {"anchor":"part-countdown","className":"wt-part wt-part--countdown","align":"full","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull wt-part wt-part--countdown" id="part-countdown">
<!-- wp:html -->
<div class="wt-lp-section-inner">
<div class="wt-part-countdown" data-wt-countdown="2026-10-15T14:00:00+09:00"><p class="wt-eyebrow">COUNTDOWN</p><h2 id="part-countdown-title">開催まで</h2><p class="wt-part-countdown__digits" aria-live="off"><span><b data-wt-cd="d">&#45;&#45;</b><small>日</small></span><span><b data-wt-cd="h">&#45;&#45;</b><small>時間</small></span><span><b data-wt-cd="m">&#45;&#45;</b><small>分</small></span></p><p class="wt-part-countdown__date"><time datetime="2026-10-15T14:00">2026 年 10 月 15 日（木）14:00 開始</time></p><p class="wt-lp-form__note">JS 無効時は開催日のみ表示。開催後は「開催しました」に切り替わる。</p></div>
</div>
<!-- /wp:html -->
</div>
<!-- /wp:group -->
