<?php
/**
 * Title: 記事パーツ拡張（見出し付きボックス・吹き出し・ステップ・評価・メリデメ）
 * Slug: helix-wt/article-parts
 * Categories: helix-wt
 * Description: テーマA 系のパーツ参照
 */
$u = get_theme_file_uri( "assets/img" );
?>
<!-- wp:heading --><h2 class="wp-block-heading"><?php esc_html_e( '見出し付きボックス 3 型', 'helix-wt' ); ?></h2><!-- /wp:heading -->
<!-- wp:html -->
<div class="wt-box wt-box--band"><p class="wt-box__title"><i class="wt-i wt-i--lightbulb"></i><?php esc_html_e( 'この記事でわかること', 'helix-wt' ); ?></p><div class="wt-box__body"><ul class="wt-ilist"><li><i class="wt-i wt-i--check-circle"></i><?php esc_html_e( '3 社の月額と自動仕訳の違い', 'helix-wt' ); ?></li><li><i class="wt-i wt-i--check-circle"></i><?php esc_html_e( '従業員数別のおすすめ', 'helix-wt' ); ?></li><li><i class="wt-i wt-i--check-circle"></i><?php esc_html_e( '税理士と共有するときの注意', 'helix-wt' ); ?></li></ul></div></div>
<div class="wt-box wt-box--tab"><p class="wt-box__title"><i class="wt-i wt-i--s wt-i--pin"></i><?php esc_html_e( 'ポイント', 'helix-wt' ); ?></p><div class="wt-box__body"><p><?php esc_html_e( '月額の差より、仕訳の自動化で減る作業時間のほうが年間コストに効きます。時給換算で比べてください。', 'helix-wt' ); ?></p></div></div>
<div class="wt-box wt-box--label wt-box--soft"><p class="wt-box__title"><i class="wt-i wt-i--s wt-i--info"></i><?php esc_html_e( '補足', 'helix-wt' ); ?></p><div class="wt-box__body"><p><?php esc_html_e( '価格は 2026 年 8 月時点の公式サイトの表示。キャンペーン価格は含めていません。', 'helix-wt' ); ?></p></div></div>
<div class="wt-box wt-box--band wt-box--warn"><p class="wt-box__title"><i class="wt-i wt-i--alert"></i><?php esc_html_e( '注意', 'helix-wt' ); ?></p><div class="wt-box__body"><p><?php esc_html_e( '無料プランは仕訳数に上限があり、月 50 件を超えると自動で有料へ切り替わるサービスがあります。', 'helix-wt' ); ?></p></div></div>
<!-- /wp:html -->
<!-- wp:heading --><h2 class="wp-block-heading"><?php esc_html_e( '吹き出し', 'helix-wt' ); ?></h2><!-- /wp:heading -->
<!-- wp:html -->
<div class="wt-talk"><div class="wt-talk__who"><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt=""><?php esc_html_e( '田中', 'helix-wt' ); ?></div><div class="wt-talk__bubble"><p><?php esc_html_e( '最初は最安のプランで十分です。仕訳が月 100 件を超えたら見直しましょう。', 'helix-wt' ); ?></p></div></div>
<div class="wt-talk wt-talk--r"><div class="wt-talk__who"><img src="<?php echo esc_url( $u ); ?>/avatar.png" alt=""><?php esc_html_e( '読者', 'helix-wt' ); ?></div><div class="wt-talk__bubble"><p><?php esc_html_e( '税理士さんに見てもらうときは、どのプランでも共有できますか？', 'helix-wt' ); ?></p></div></div>
<!-- /wp:html -->
<!-- wp:heading --><h2 class="wp-block-heading"><?php esc_html_e( '手順（タイムライン）', 'helix-wt' ); ?></h2><!-- /wp:heading -->
<!-- wp:html -->
<ol class="wt-timeline"><li><b><?php esc_html_e( '無料登録して銀行口座を連携する', 'helix-wt' ); ?></b><?php esc_html_e( '主要行なら 5 分で終わります。', 'helix-wt' ); ?></li><li><b><?php esc_html_e( '過去 3 か月の明細を取り込む', 'helix-wt' ); ?></b><?php esc_html_e( '自動仕訳の精度を最初に確かめます。', 'helix-wt' ); ?></li><li><b><?php esc_html_e( '税理士を招待する', 'helix-wt' ); ?></b><?php esc_html_e( '共有設定は「メンバー」から。', 'helix-wt' ); ?></li></ol>
<!-- /wp:html -->
<!-- wp:heading --><h2 class="wp-block-heading"><?php esc_html_e( '評価バーとメリット・デメリット', 'helix-wt' ); ?></h2><!-- /wp:heading -->
<!-- wp:html -->
<div class="wt-box wt-box--label"><p class="wt-box__title"><?php esc_html_e( 'サービス A の評価', 'helix-wt' ); ?></p><div class="wt-box__body">
<div class="wt-rate"><span><?php esc_html_e( '自動仕訳', 'helix-wt' ); ?></span><i style="--v:92%"></i><b>4.6</b></div><div class="wt-rate"><span><?php esc_html_e( '銀行連携', 'helix-wt' ); ?></span><i style="--v:96%"></i><b>4.8</b></div><div class="wt-rate"><span><?php esc_html_e( '料金', 'helix-wt' ); ?></span><i style="--v:70%"></i><b>3.5</b></div><div class="wt-rate"><span><?php esc_html_e( 'サポート', 'helix-wt' ); ?></span><i style="--v:80%"></i><b>4.0</b></div>
</div></div>
<div class="wt-prosc"><div class="wt-box wt-box--label wt-prosc--good"><p class="wt-box__title"><i class="wt-i wt-i--thumb-up"></i><?php esc_html_e( 'メリット', 'helix-wt' ); ?></p><div class="wt-box__body"><ul class="wt-ilist"><li><i class="wt-i wt-i--check"></i><?php esc_html_e( '仕訳の学習が速い', 'helix-wt' ); ?></li><li><i class="wt-i wt-i--check"></i><?php esc_html_e( '税理士招待が無料', 'helix-wt' ); ?></li><li><i class="wt-i wt-i--check"></i><?php esc_html_e( '地方銀行も連携', 'helix-wt' ); ?></li></ul></div></div><div class="wt-box wt-box--label wt-prosc--bad"><p class="wt-box__title"><i class="wt-i wt-i--thumb-down"></i><?php esc_html_e( 'デメリット', 'helix-wt' ); ?></p><div class="wt-box__body"><ul class="wt-ilist"><li><i class="wt-i wt-i--close"></i><?php esc_html_e( '電話サポートは平日のみ', 'helix-wt' ); ?></li><li><i class="wt-i wt-i--close"></i><?php esc_html_e( '月額は最安ではない', 'helix-wt' ); ?></li></ul></div></div></div>
<!-- /wp:html -->
<!-- wp:heading --><h2 class="wp-block-heading"><?php esc_html_e( 'アイコン付きボタン', 'helix-wt' ); ?></h2><!-- /wp:heading -->
<!-- wp:html -->
<div class="wp-block-buttons is-layout-flex"><div class="wp-block-button"><a class="wp-block-button__link has-cta-background-color has-cta-contrast-color has-background has-text-color wp-element-button" href="#" rel="sponsored nofollow"><?php esc_html_e( '公式サイトで無料で試す', 'helix-wt' ); ?> <i class="wt-i wt-i--s wt-i--external"></i></a></div><div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#"><i class="wt-i wt-i--s wt-i--download"></i> <?php esc_html_e( '比較表を PDF で保存', 'helix-wt' ); ?></a></div></div>
<!-- /wp:html -->
