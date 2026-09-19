# モバイルの検索・絞り込み入力を読みやすくする

現行mainの390px実測で、検索・目的・選択メモの文字は12px、検索入力高さは43pxだった。
要求: 700px以下では候補検索・要求検索・絞り込み・メモを16px以上、入力高さ44px以上とし、320/390/700pxで横溢れを起こさない。PCの一覧密度は維持する。
設計: 入力要素だけにモバイルCSSを適用し、ボタンやカードの情報密度は維持する。

参考: https://www.w3.org/WAI/WCAG22/Understanding/target-size-enhanced.html の44 CSS pxを入力高さの改善目標に使用。全WCAG適合や実機iOS動作を主張しない。文字サイズ16pxはこのPoCの可読性目標。

検証: `CATALOG_BASE_URL=<local server> node docs/research/2026-09-08-selection-catalog/visual-quality/mobile-forms/capture.mjs`。
320/390/700/1440pxをChromiumで前後撮影。検索・要求検索・目的・選択メモ・確認状況・判断メモの計測と横溢れ検査を同時実行し、sourcesをHTTPレスポンスと照合してdigestを保存する。
390pxで検索文字12→16px、高さ43→50px。目的・選択メモは文字12→16px、高さ45→52px。PCの入力文字は変更しない。

既存のdecision-panel/detail-sequence/dialog-focus/tablet-componentsはcaptureを再実行し、filter-contextは38件のブラウザ回帰を再実行して現CSSへ結合。歴史的before画像は保持。
