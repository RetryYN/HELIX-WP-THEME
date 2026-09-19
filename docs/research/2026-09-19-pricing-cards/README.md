# 料金プランカードの比較と本文量の境界

対象は missing の `WT-AC-LOOK-01C`。既存 `helix-wt/pricing` を通常本文と長い本文で比較する。新しい料金正本・決済・プラン選択機構は追加しない。

## PoC → 要求 → 設計

実装前の WordPress 実機では109検査中4件が失敗。PCでカード外枠の高さは揃っていたが、相談CTAの下端は通常本文で約109px、長文で約90pxずれていた。価格の「/月」も狭い3列で不自然に改行した。`before.json` と `before-*.jpg` が変更前の証跡。

この観測から、同じ行のカードは内容を省略せず外枠と末尾CTAを揃え、価格と単位を一体として読み、SPは縦積みで内容に応じた高さにする。既存カード全般へ波及させず、料金パターンの `wt-pricing` / `wt-plan` に限り、縦flexと末尾のauto marginを使う。おすすめ表示はカード上辺のラベルとし、見出し開始位置を揃える。保存先はWP標準の本文core blocks、トークンは既存尺度を継承する。

[WordPress公式パターン登録](https://developer.wordpress.org/themes/patterns/registering-patterns/)と[パターンの種類](https://developer.wordpress.org/themes/patterns/introduction-to-patterns/)を参照（2026-09-19）。テーマパターンを挿入した本文は同期されないため、パターン変更の自動追従は要求しない。挿入済みの旧本文へ新しいclassが遡及しないことも境界として扱う。

## 再現

`node scripts/verify-pricing-cards.mjs`。専用WordPress labへ所有者付き固定ページ2件を作り、登録済みパターンの本文を保存する。通常本文／長文をPC1440/SP390×JS有無、reduced-motionで検証し、本文読み戻し、別ページ不変、見出し・CTA・overflow・focus・アンカー移動を確認する。finallyで所有者を照合し、自分のfixtureだけを回収する。共有theme_modの変更は不要。

カタログ候補は `catalog-candidates.json`。同一デザインの通常／長文の比較であり、選べる型数を水増ししない。新規型は0、改善した既存型は1。

## 残件

LOOK-01C全体の達成ではない。全カード・任意の長いプラン名・全style variation・管理画面の挿入保存・REST/MCP・200%文字拡大は未確認。パターンを挿入済みの本文は同期しない。3桁以上の桁増加や通貨切替、実際の料金・契約条件、決済は対象外。

## 検証結果

実機 **125/125成功**。通常本文と長文のPC/SP計4画像を保存した。カード高さ・CTA下端・見出し基準線、価格単位の同一行、44px操作領域、文字切れなし、横溢れなし、focus表示、相談先アンカーへの移動、保存本文読み戻し、対照ページ不変、fixture回収を確認。PC通常とSP長文画像を目視した。全ページaxe監査・スクリーンリーダーは未実施。

PHP lint、WordPress-Core PHPCS、JS構文検査、`git diff --check`、`npm test`（要求・capability・AI境界・privacy・公開安全性・i18n・consumer healthとreceipt関連31テスト）が成功。`npm test`でAI境界/privacy証跡のソースhashが更新されるため、親レーンで最終rebindする。

変更前は109検査でCTA基準線4失敗、変更後は価格単位と見出し基準線の16検査を追加して125成功。変更前JSONは当時のソースdigestを保持した観測記録であり、現headの合格証拠として再束縛しない。
