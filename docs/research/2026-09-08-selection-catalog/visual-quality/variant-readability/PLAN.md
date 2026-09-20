# モバイルの候補variantを読み比べやすくする

基準main: 5bb926f。小画面の候補variantは11pxで、PC側の12pxから縮小されている。
候補を区別する長い設定名を読み比べやすくするため、700px以下でも12pxへ揃える。
既存の `overflow-wrap:anywhere` と1.6行高は保ち、文字列・候補・意味・選択操作は変更しない。

予備調査では見出しのscrollWidth超過を検出したが、閉じ括弧の和文ぶら下がりで、欠落ではなかった。
見出しへ不要な折返しを加えず、variantの可読性だけを扱う。

320/390/700/1440pxの初期候補と長いフッター設定名を検査し、12px・19.2px以上の行高、文字の非clipを確認する。
390pxのbefore/afterをソース束縛付きで保存する。1440pxは既存の文字サイズ・行高・寸法を維持する。
実機や支援技術・全候補の適合宣言はしない。

## 実測結果

390pxで短いvariant・長い設定名とも、文字サイズ11→12px、行高17.6→19.2px。
長い設定名の表示高は88→96pxとなり、内容を省略せず折り返す。1440pxの文字サイズ・行高・寸法・文字列はbefore/after一致。
4幅の専用E2Eは4 passed。390pxの短い/長いvariantのbefore/afterを目視し、文字欠落が無いことを確認した。
既存8種類のvisual captureは各記録のbaselineを維持して実行し、現CSSへ再束縛する。

環境上の初期停止はChromium font_data_serviceの一時領域quota超過。ブラウザのTMPDIRを空きのある領域へ指定して解消した。
既存worktreeや他作業のファイルは削除していない。この回避は実行環境だけで、リポの設定には追加しない。

参考: https://developer.mozilla.org/en-US/docs/Web/CSS/font-size
再現: `CATALOG_BASE_URL=<local server> CATALOG_BASELINE_REF=5bb926f node docs/research/2026-09-08-selection-catalog/visual-quality/variant-readability/capture.mjs`。
`CATALOG_CAPTURE_OUTPUT` で出力先を変更可能。配信バイト一致を検査し、同一baseline CSSを拒否する。

既存selection/filter検証は39 passed、全visualソース束縛はstale 0、受入証跡auditはpass。
公開guardは実在するリポ外の非公開対応表を環境変数から注入して実行する。
