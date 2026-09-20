# 高コントラストで選択状態が消える欠陥を修正する

基準main: 8bb9592。既存visual-quality/REVIEW.mdが未確認としていた高コントラストを実測した。
Chromium forced-colorsではPC/SPと画像倍率の選択中・未選択が同じ白地、黒文字、黒枠になり、現在の選択が視覚で判別できなかった。

forced-colors限定で、選択中のカテゴリ・workspaceタブ・端末・倍率・比較候補・完成候補絞り込みへ太い下線を付ける。
既存ARIA状態に従うCSSだけの変更とし、背景色の強制解除（forced-color-adjust:none）や状態管理変更をしない。
focus outlineとは別のtext decorationで示すため、選択とフォーカスを同時に読み分けられる。
判断ボタンは既存のチェック表示、比較checkboxはネイティブのチェックを保持する。
参考: https://developer.mozilla.org/en-US/docs/Web/CSS/@media/forced-colors

390/1440px、forced-colors active/noneで検証する。カテゴリ・PC/SP・倍率・比較候補を切替え、下線が選択に追従し、未選択へ残らないことを確認する。
通常配色のbefore/after画像一致、高コントラストの画像差分、ソースdigestを保存する。
これはChromiumのエミュレーションであり、OS高コントラスト全テーマや支援技術の適合保証ではない。

## 検証結果

専用E2Eは390/1440px×forced-colors active/none×light/darkの8件pass。
カテゴリ・workspaceタブ・PC/SP・画像倍率・比較候補・完成候補絞り込みで、選択中だけの下線と切替追従を確認した。
比較候補のキーボードfocus outlineと選択下線の併存も確認した。

captureは32観察を記録。通常配色の端末/倍率コントロール領域は8組すべてbefore/afterバイト一致。
forced-colorsのviewport画像は8組すべて差分あり。390pxのlight/dark画像を目視し、選択中の下線が判別できることを確認した。
ラベル・選択状態・コントロール寸法は全組で不変。
当初は通常配色のviewport全体へバイト一致を要求したが、非対象領域の描画差が生じたため、実装対象のコントロール領域に比較範囲を限定した。
viewport全体のバイト一致は主張しない。

既存10種類のvisual captureを各baselineで再実行。selection/filterは39 passed、全束縛stale 0、受入証跡auditはpass。
公開guardには実在するリポ外の非公開対応表を環境変数から注入する。通常テーマ・要求本文・受入台帳は未変更。

再現: `CATALOG_BASE_URL=<local server> CATALOG_BASELINE_REF=8bb9592 node docs/research/2026-09-08-selection-catalog/visual-quality/forced-colors/capture.mjs`。
`CATALOG_CAPTURE_OUTPUT` で保存先を変更できる。配信バイト照合と同一baseline拒否を行う。
