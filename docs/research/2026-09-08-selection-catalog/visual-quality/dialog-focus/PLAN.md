# ダイアログ内の逆順フォーカス被覆を解消する

基準: main 5961bde。カタログの既存キーボード操作・sticky header の視認性を保つ局所修正。

## PoC と設計

詳細を開き、最後の関連要求ボタンから Shift+Tab で先頭へ戻ると、フォーカスした操作が sticky header の背後に入る。
`before.json` は320 / 390 / 768 / 1440pxで被覆を記録する。390pxの第3逆順操作は `before-390.png`。
ネイティブのフォーカススクロールがヘッダー占有高を知らないことが原因。

ダイアログごとに既存 `.dialog-head` の高さを ResizeObserver で計測し、8pxの余裕を足した `scroll-padding-top` を適用する。
詳細の前後ナビ追加、比較タイトルの折返し、viewport変更も同じ観測で扱う。フォーカス移動自体はブラウザの既存動作を使う。
候補、説明、選択メモ、要求の内容・状態は変更しない。既存 `catalog.mjs` の compare clearance と同じ計測方式を用いる。

要求入口は `docs/requirements/authority.md`、設計責務は `docs/design/consistency-responsibilities.md` / `token-structure.md` / `parts-catalog.md` を確認。
テーマ・pluginへの変更や新しい要求達成の宣言ではない。
[MDN scroll-padding-top](https://developer.mozilla.org/en-US/docs/Web/CSS/Reference/Properties/scroll-padding-top) が説明する、他コンテンツで隠れるスクロール領域の除外を適用する。

## 検証計画

4幅で詳細・比較それぞれの逆順Tabを巡回し、ヘッダー外の操作がヘッダー下端より6px以上離れていることを検査する。
同じ詳細を開いたまま幅を変更し、再巡回する。既存 selection catalog E2E を実行する。
同一操作の390px before/after と4幅の幾何計測を保存する。全候補の目視検査・独立レビューの代替にはしない。
