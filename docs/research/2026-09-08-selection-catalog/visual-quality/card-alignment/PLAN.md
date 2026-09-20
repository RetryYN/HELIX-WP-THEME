# 混在カードの比較操作・判断表示を揃える

基準main: b7b39c9。「すべて」には短い共通部品と背の高いページ画像が混在する。
390pxの先頭2カードは同じ高さだが、比較操作の上端が146.70pxずれ、短いカードでは判断バッジの下へ大きな空白が残っていた。

`.tile .tile-facts` の上余白をautoにし、撮影情報・比較操作・判断表示をカード下部へまとめる。
画像・候補名の上端、カード高、文字、選択動作は変更しない。1列や共通部品の自然な高さには余白を追加しない。
CSS flexのauto marginによる余白配分を利用する。参考: https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_flexible_box_layout/Aligning_items_in_a_flex_container

320/390/768/1440pxでカード高と内容を確認し、複数列の先頭行の比較操作・判断表示の位置差が1px以内になることを測定する。
比較checkboxの操作も確認する。390/1440pxでbefore/afterを撮影し、現行ソースへのdigestを記録する。
単独のChromium検査であり、全候補・実機・支援技術の検収を代替しない。

## 実測結果

比較操作の上端差は390pxで146.70→0px、768pxで205.80→0px、1440pxで195.14→0px。
320pxは1列なので縦位置の差を維持した。4幅で先頭2カードの高さ・上端・preview/見出し上端・文字列はbefore/after完全一致。
専用E2Eは4 passed。比較チェック操作、判断バッジの下部配置、横overflowなしを確認した。
390/1440pxのbefore/afterを目視し、短い部品カードも比較・判断行が隣の候補と揃うことを確認した。

既存9種類のvisual captureは各baselineを保持して実行し、現CSSへ再束縛する。
ブラウザのTMPDIRは空きのあるリポ外領域を指定した。要求本文・受入台帳・意味契約・JavaScriptへの変更なし。
再現: `CATALOG_BASE_URL=<local server> CATALOG_BASELINE_REF=b7b39c9 node docs/research/2026-09-08-selection-catalog/visual-quality/card-alignment/capture.mjs`。
`CATALOG_CAPTURE_OUTPUT` で保存先を指定できる。配信バイトを照合し、同一baselineを拒否する。

既存selection/filter検証は39 passed。関連9capture再実行後の全visual束縛はstale 0、受入証跡auditはpass。
公開guardは実在するリポ外の非公開対応表を環境変数へ注入して実行する。
