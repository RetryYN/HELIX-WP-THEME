# 詳細を開いたまま候補を順に選別する

## 観察・要求・設計

2026-09-20、main `009a125` の共通部品を1440×900 / 390×900で開いた。
詳細で選択メモを付けた後、次の候補へ進むには毎回閉じてカードを探し直す必要があった。
165候補を順に確認する操作の連続性を改善するため、詳細の固定ヘッダーへ「前の候補」「次の候補」と位置・件数を追加する。
ユーザーのカタログ取捨選択UX改善指示の範囲であり、テーマ要求の合格数を増やす変更ではない。

開いた時点の絞り込み結果と順序を保持する。未選択で絞り込んで採用メモを付けても、途中の候補を飛ばしたり前の判断へ戻れなくしたりしない。
閉じると選択メモの現在値で一覧を更新する。候補が残っていればそのカードへ、絞り込みから外れていれば検索欄へフォーカスを戻す。
表示上限36件より先の候補へ進んだ場合は、閉じた際にそのカードまで一覧を表示する。

前後の端ではnative buttonをdisabledにし、勝手に先頭へ循環しない。画像内の矢印キーは既存スクロール用のまま保持する。
操作は44px以上、移動先でダイアログ上部へ戻し、同方向のボタン（端では反対側）へフォーカスを置く。
画像の固定位置もヘッダーの増加分へ合わせ、操作欄と重ならないようにする。
[WAI modal dialog pattern](https://www.w3.org/WAI/ARIA/apg/patterns/dialog-modal/)のフォーカス保持・閉じた後の復帰を参照した。

## 比較証跡と検証

- PC: [変更前](before-1440.png) / [変更後](after-1440.png)
- SP: [変更前](before-390.png) / [変更後](after-390.png)
- [観察値](observations.json): 同じ候補、同じ幅、reduced-motion有効。画像はPC/SPとも目視確認。

```sh
CATALOG_BASELINE_REF=009a125 CATALOG_BASE_URL=http://127.0.0.1:8119 node docs/research/2026-09-08-selection-catalog/visual-quality/detail-sequence/capture.mjs
CATALOG_BASE_URL=http://127.0.0.1:8119 npx playwright test tests/e2e/detail-sequence-selection-catalog.spec.ts tests/e2e/selection-catalog.spec.ts tests/e2e/selection-catalog-filters.spec.ts --workers=1
npm test
```

新規検査は1440/390/320pxの連続判断、メモ保全、Enter操作、Escape復帰、一覧の末尾、1件だけの一覧、37件目への移動と閉じた後のカード復帰、固定画像の被覆なしを確認する。
JS無効時は既存の撮影索引リンクを維持し、追加の操作は生成しない。
スクリーンリーダー実機と利用者の選別所要時間は未測定。

上記のブラウザ検査は41件成功、`npm test`も成功した。
ソース変更で失効したdecision-panelとtablet-componentsの観察は各captureで再生成した。
filter-contextの手書き検証結果は、実際に既存34件を再実行して生成する`../filter-context/verify.mjs`へ置き換えた。
source digestの手編集はしていない。要求台帳と受入条件の証拠レジストリは変更していない。
