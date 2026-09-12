# フォームの入力ステップ表示

現行 `helix-wt` の段階式フォームを対象にしたAstra lowの視覚改善。旧 `themes/agent-neo-theme` と `plugins` は変更していない。

## 判断

変更前は3段階が細い下線で区切られ、現在地と残りの工程の関係が弱かった。番号を独立した円形マーカーにし、3点を一本のレールで結んだ。現在の段はアクセント色の塗りと外周で示し、完了した段には既存JSが付ける `is-done` を使う。文言・DOM順・操作順・フォーム処理は変えていない。

関連項目を小さなまとまりとして把握しやすくする考え方は[WAI Forms Tutorial](https://www.w3.org/WAI/tutorials/forms/grouping/)を参照した。WordPress側は[Global Settings and Styles](https://developer.wordpress.org/block-editor/how-to-guides/themes/global-settings-and-styles/)と[Spacing](https://developer.wordpress.org/themes/global-settings-and-styles/settings/spacing/)を参照し、現行テーマの色・間隔トークンを維持した。

## 再現と検証

```sh
node docs/research/2026-09-12-current-theme-form-progress/probe.mjs before
node docs/research/2026-09-12-current-theme-form-progress/probe.mjs after
```

`probe.mjs` はslug衝突を拒否して一時固定ページを作り、作成IDとslugを照合して `finally` で削除する。390/1440px、JS有効/無効の4条件を同じqueryで撮影・計測する。

- 4条件すべてHTTP 200、document横overflowなし。
- ステップは3件、現在位置は1件、ステップ幅はフォーム幅以内。
- JS有効では現在段のfieldsetだけ、JS無効では3段すべてを表示。
- ステップ一覧のアクセシブル名を維持し、現在マーカーの文字/背景コントラストは4.5:1以上。
- 表示中の通常入力・select・textarea・buttonは高さ44px以上。
- `verify.json` は変更した `theme.css` のSHA-256を保持する。

画像は `before/after-{390,1440}-{js,nojs}.png`。これは段階表示の代表的な視覚・reflow検証であり、全フォーム種別、全style variation、支援技術実機の完了を示さない。

## 残件

全9フォーム種別の長い文言、RTL、各style variationでの色コントラスト、スクリーンリーダー実機は未検証。フォーム処理と全入力境界は既存の専用検査を正本とする。
