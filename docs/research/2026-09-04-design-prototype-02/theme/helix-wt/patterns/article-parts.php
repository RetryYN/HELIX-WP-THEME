<?php
/**
 * Title: 記事パーツ拡張（見出し付きボックス・吹き出し・ステップ・評価・メリデメ）
 * Slug: helix-wt/article-parts
 * Categories: helix-wt
 * Description: テーマA 系のパーツ参照
 */
$u = get_theme_file_uri( "assets/img" );
?>
<!-- wp:heading --><h2 class="wp-block-heading">見出し付きボックス 3 型</h2><!-- /wp:heading -->
<!-- wp:html -->
<div class="wt-box wt-box--band"><p class="wt-box__title"><i class="wt-i wt-i--lightbulb"></i>この記事でわかること</p><div class="wt-box__body"><ul class="wt-ilist"><li><i class="wt-i wt-i--check-circle"></i>3 社の月額と自動仕訳の違い</li><li><i class="wt-i wt-i--check-circle"></i>従業員数別のおすすめ</li><li><i class="wt-i wt-i--check-circle"></i>税理士と共有するときの注意</li></ul></div></div>
<div class="wt-box wt-box--tab"><p class="wt-box__title"><i class="wt-i wt-i--s wt-i--pin"></i>ポイント</p><div class="wt-box__body"><p>月額の差より、仕訳の自動化で減る作業時間のほうが年間コストに効きます。時給換算で比べてください。</p></div></div>
<div class="wt-box wt-box--label wt-box--soft"><p class="wt-box__title"><i class="wt-i wt-i--s wt-i--info"></i>補足</p><div class="wt-box__body"><p>価格は 2026 年 8 月時点の公式サイトの表示。キャンペーン価格は含めていません。</p></div></div>
<div class="wt-box wt-box--band wt-box--warn"><p class="wt-box__title"><i class="wt-i wt-i--alert"></i>注意</p><div class="wt-box__body"><p>無料プランは仕訳数に上限があり、月 50 件を超えると自動で有料へ切り替わるサービスがあります。</p></div></div>
<!-- /wp:html -->
<!-- wp:heading --><h2 class="wp-block-heading">吹き出し</h2><!-- /wp:heading -->
<!-- wp:html -->
<div class="wt-talk"><div class="wt-talk__who"><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt="">田中</div><div class="wt-talk__bubble"><p>最初は最安のプランで十分です。仕訳が月 100 件を超えたら見直しましょう。</p></div></div>
<div class="wt-talk wt-talk--r"><div class="wt-talk__who"><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt="">読者</div><div class="wt-talk__bubble"><p>税理士さんに見てもらうときは、どのプランでも共有できますか？</p></div></div>
<!-- /wp:html -->
<!-- wp:heading --><h2 class="wp-block-heading">手順（タイムライン）</h2><!-- /wp:heading -->
<!-- wp:html -->
<ol class="wt-timeline"><li><b>無料登録して銀行口座を連携する</b>主要行なら 5 分で終わります。</li><li><b>過去 3 か月の明細を取り込む</b>自動仕訳の精度を最初に確かめます。</li><li><b>税理士を招待する</b>共有設定は「メンバー」から。</li></ol>
<!-- /wp:html -->
<!-- wp:heading --><h2 class="wp-block-heading">評価バーとメリット・デメリット</h2><!-- /wp:heading -->
<!-- wp:html -->
<div class="wt-box wt-box--label"><p class="wt-box__title">サービス A の評価</p><div class="wt-box__body">
<div class="wt-rate"><span>自動仕訳</span><i style="--v:92%"></i><b>4.6</b></div><div class="wt-rate"><span>銀行連携</span><i style="--v:96%"></i><b>4.8</b></div><div class="wt-rate"><span>料金</span><i style="--v:70%"></i><b>3.5</b></div><div class="wt-rate"><span>サポート</span><i style="--v:80%"></i><b>4.0</b></div>
</div></div>
<div class="wt-prosc"><div class="wt-box wt-box--label wt-prosc--good"><p class="wt-box__title"><i class="wt-i wt-i--thumb-up"></i>メリット</p><div class="wt-box__body"><ul class="wt-ilist"><li><i class="wt-i wt-i--check"></i>仕訳の学習が速い</li><li><i class="wt-i wt-i--check"></i>税理士招待が無料</li><li><i class="wt-i wt-i--check"></i>地方銀行も連携</li></ul></div></div><div class="wt-box wt-box--label wt-prosc--bad"><p class="wt-box__title"><i class="wt-i wt-i--thumb-down"></i>デメリット</p><div class="wt-box__body"><ul class="wt-ilist"><li><i class="wt-i wt-i--close"></i>電話サポートは平日のみ</li><li><i class="wt-i wt-i--close"></i>月額は最安ではない</li></ul></div></div></div>
<!-- /wp:html -->
<!-- wp:heading --><h2 class="wp-block-heading">アイコン付きボタン</h2><!-- /wp:heading -->
<!-- wp:html -->
<div class="wp-block-buttons is-layout-flex"><div class="wp-block-button"><a class="wp-block-button__link has-cta-background-color has-cta-contrast-color has-background has-text-color wp-element-button" href="#" rel="sponsored nofollow">公式サイトで無料で試す <i class="wt-i wt-i--s wt-i--external"></i></a></div><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#"><i class="wt-i wt-i--s wt-i--download"></i> 比較表を PDF で保存</a></div></div>
<!-- /wp:html -->
