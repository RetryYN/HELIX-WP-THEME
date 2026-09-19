# 記事別アイキャッチ設定の比較

対象は未対応 `WT-AC-META-01C`。既存の5型（題名→写真、写真→題名、写真重ね、横添え、非表示）を同じ記事・写真で比較し、プレビュー引数を使わず投稿メタ `wt_eyecatch` に保存した値から描画する。未設定の記事はサイト既定を継承する。

新規CTA型も検討したが、既存の `cta-box` / `cta-banner` / `cta-textlink` とカタログ実機画像に同じ強度差があり、判断材料の重複を避けて採用しなかった。本バッチは新型数を増やすためのものではなく、既存デザインを記事ごとに実際に選べるかの証拠不足を埋める。

## 実機検証

`node scripts/verify-eyecatch-meta.mjs`。専用WordPress labに所有者付き投稿2件と画像1件を作成する。対象記事だけを5型へ変更し、対照記事がサイト既定のままであることをPC1440/SP390×JS有無で検査する。reduced-motion、文字の収まり、写真と題名の座標、非表示、メタ解除→継承、サイト既定変更→追従、明示上書きも確認する。finallyでサイト既定を復元し、自分のfixtureだけを回収する。

画像は公開実機DOMの `.wt-posthead` を撮影する。同じ写真・題名を固定して視覚差を比較する。既存のプレビュー画像とは、投稿メタによる選択・対照記事・継承の実測を持つ点が異なる。

## 公式資料の再観察

[WordPressパターン登録](https://developer.wordpress.org/themes/patterns/registering-patterns/) と [Buttonsブロック](https://wordpress.org/documentation/article/buttons-block/)（参照2026-09-19）から、静的パターンの登録とリンクを持つ表示部品の範囲を再確認した。今回は既存CTAの再実装は不要と判断した。

要求候補: アイキャッチ写真を外した記事で、写真重ね型の白文字・重ね順が残らず、通常の見出しとして読めること。現行META-01Cは位置と有無・継承を扱うが、写真自体の欠損状態を明示していない。独立した負例として今後確認し、未検証を達成に含めない。

## 境界

管理画面の視覚ピッカー、REST/MCP往復、画像欠損、全スタイルvariation、全ヘッダーとの組合せ、画像内の明度に応じたコントラスト保証は未確認。新規視覚型の追加や全面のデザイン品質改善とは扱わない。

[Post Featured Imageブロック](https://wordpress.org/documentation/article/post-featured-image-block/) と [REST投稿メタ](https://developer.wordpress.org/rest-api/extending-the-rest-api/modifying-responses/) も参照した。画像の内容は投稿のfeatured image、位置選択は登録メタという異なる責務を維持する。`show_in_rest` 宣言の存在はREST操作成功の証拠と混同しない。

## 結果

実機 **145/145成功**。5型×PC/SPの10画像を保存し、カタログへ5候補を追加した。画像重ねSP・横添えPCを目視し、座標検査と表示結果の一致を確認した。既存実装の修正は不要だった。

カタログE2E **2/2成功**（PC/SP）。5候補の発見、2候補比較、設定値・継承説明、端末画像、選択理由保存・再読込、横溢れなしを確認した。acceptance-evidence登録と独立検収は別レーンで行う。

`npm test`（要求整合性・capability・AI境界・privacy・公開安全性・i18n・consumer health・receipt関連31テスト）、JS構文検査、`git diff --check` も成功した。
