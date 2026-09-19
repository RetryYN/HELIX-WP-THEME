# 適用中の絞り込みを結果のそばで確認・解除する

## 観察と範囲（2026-09-20）

既存の静的カタログを起動し、1440×900 / 390×900で「すべて → 料金検索 → 未選択」の順に操作した。PCでは目的・選択メモのselectが長い左ナビの下へ隠れ、結果周辺には適用された状態が見えない。SPでも選択メモは検索・結果から離れている。検索以外の条件を残したまま面を移動すると、少数・ゼロ件の原因を特定するために元の操作欄へ戻る必要がある。

変更前画像: [PC](before-1440.png) / [SP](before-390.png)。表示件数は3候補。

## このバッチの設計

既存のカタログ完成計画の探索操作改善として、結果の直前に適用中の面・関連候補範囲・目的・選択メモ・検索語を表示する。各条件は個別解除でき、全解除は既存のゼロ件時解除と同じ挙動に揃える。解除時は残った条件へキーボード操作位置を移し、最後の解除後は検索へ戻す。メモ・比較候補・PC/SPは保持する。文字列をHTMLとして解釈せず、長い検索語は折り返す。

要求文書・acceptance registry・候補データの意味と件数を変更しない。部品・テーマの完成判断にはしない。

操作はnative buttonを使い、可視ラベルを含む解除名を付ける。[WAIの名前と説明の指針](https://www.w3.org/WAI/ARIA/apg/practices/names-and-descriptions/)を参照。スクリーンリーダー実機と選別時間のユーザーテストは対象外。

## 結果と再現

変更後画像: [PC](after-1440.png) / [SP](after-390.png)。同じ3候補に対し、結果直前で「選択メモ: 未選択」「検索: 料金」を確認・個別解除できる。PCは1行、SPは折り返す。全候補に戻したときは条件欄自体を閉じる。PC/SPとも画像を目視確認した。

```sh
CATALOG_BASELINE_REF=513c0516db9994d3b85e4e23d67466251d151ff0 CATALOG_BASE_URL=http://127.0.0.1:8099 node docs/research/2026-09-08-selection-catalog/visual-quality/filter-context/capture.mjs
CATALOG_BASE_URL=http://127.0.0.1:8099 npx playwright test tests/e2e/selection-catalog-filters.spec.ts tests/e2e/selection-catalog.spec.ts --workers=1
npm test
```

`capture.mjs`は`CATALOG_BASELINE_REF`を必須とし、未指定の再撮影を拒否する。before/after PNGが同一バイトなら比較証跡として失敗する。一時出力で回帰確認する場合は`CATALOG_CAPTURE_OUTPUT`を指定する。[観察JSON](observations.json)は同じ候補データで変更前後を比較した記録。

- 新規5ケース成功: PC/SPで個別解除、Enter操作とフォーカス復帰、保存から再開、ゼロ件回復、長い検索語の折返し、文字列の安全表示、比較候補・選択メモ・PC/SPの保全、関連要求範囲の解除。
- 既存の表示密度検査も1440/375/320pxで成功。カード開始位置700px未満・横溢れなしを維持。
- `npm test`成功（要求整合・境界・公開情報・artifact binding・consumer診断・31件のunit検査を含む）。
- 既存カタログ検証の要求数固定値2件は変更前から不一致だった。期待値を現データの134要求/298受入条件へ更新し、要求データを変更せず解消した。

選別時間短縮の定量評価、スクリーンリーダー実機、すべての組合せ条件の網羅は未実施。正式なテーマ・全要求完了とは区別する。

最終の合わせたPlaywright実行は31/31成功。公開情報検査はローカルの非公開対応regexを注入し、実indexを変更しない一時indexで今回の差分全体を検査して成功した。
