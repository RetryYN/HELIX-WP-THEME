# メディア枠5択の静的切替 PoC

2026-09-13。`WT-AC-VOCAB-01C` のカード・箇条書き・手順に対する表示提案。入口は `icon.html`。上部の5つのリンクから、自前SVGアイコン、アップロード画像、写真、番号、なしを切り替える。JSなしでも同じリンクで操作できる。

## 証明できる範囲

5ページは `scripts/build-vocabulary-media.mjs` が同じ本文定数から生成する。カードはarticle、箇条書きはul/li、手順はol/liの構造を保持し、各 `.media-body` は全モード・PC/SP・JS有無でouterHTMLまで一致する。メディアだけを本文の兄弟要素として付け、noneは兄弟要素自体を生成しない。非選択のimg/svg/メディア要素はDOMへ出さない。選択UIは別の静的文書への遷移であり、WordPressの編集属性を切り替えた証拠ではない。

画像はalt、width、heightを明示。inline SVGにはroleとaria-label、幅・高さを付与。アップロード画像モードは本PoC用に作った `upload-workspace.svg` をimgとして読む例であり、WordPress Media Libraryへのアップロード操作はしていない。写真は現行helix-wt既存の `media-pickup-2.jpg` を相対参照し、原画像を変更していない。SVG配置図とアイコンは本PoCの自作素材。

## 検証

`node scripts/build-vocabulary-media.mjs` の後に `node scripts/verify-vocabulary-media.mjs`。

**220/220**。5モード × PC1440/SP390 × JSあり/なし × 11検査。

- 本文DOM一致、カード/箇条書き/手順の3構造
- 選択メディアだけの存在、img/svg数と画像srcの一致
- noneの空ラッパー不存在
- imgのalt・寸法属性・読み込み完了
- 全リンク44px、計算テキスト色AA4.5:1
- ページの横はみ出しなし、3見本の重なりなし
- 実リンクをクリックして次モードへ移動

全景20枚は `pc-js-icon.png` など。`verification.json` は各検査とsource SHA256を含む。

## 残件

WordPress実ブロックへの接続、アップロード・エディタ保存・再読み込み、端末別設定の保持、画像変換・レスポンシブsrcset、実際の記事データへの適用は未検証。静的PoCの達成を受入条件全体の製品達成へ広げない。
