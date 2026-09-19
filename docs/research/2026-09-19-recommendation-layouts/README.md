# 読了後の記事一覧・表示型比較

未対応 `WT-AC-RECO-01C` を起点に、同じ新着記事3件を写真中心のカード／説明中心のリストで比較する。既存の用途別一覧へ参照できる2つのpattern slugを与える。選定理由は、記事の選び方を変えず、情報密度と読み方の差だけを判断できるため。

`baseline.json` は変更前の専用WordPress labで両パターンが未登録だった実測。要求・設計後の追加は現行 `theme/helix-wt/patterns/` のみ。core Queryの同一query（投稿・新着順・3件）を保持し、core Post TemplateとGroupのflex行で表示型だけを変える。写真がない場合は空の画像枠を作らない。新着順は人気順や関連度を意味しない。

参照: [WordPressのパターン登録](https://developer.wordpress.org/themes/patterns/registering-patterns/)。テーマのpatternsディレクトリから登録する標準方式を使用する。

## 検証

`node scripts/verify-recommendation-layouts.mjs`。専用labへ所有者付きfixtureページ・分類・投稿を作成し、公開投稿3件と非公開投稿1件で同件数・同順・非公開除外、PC/SP、JS有無、リンク到達、画像なし、長い見出しを確認する。finallyで自分のfixtureを回収し、検証ソースと画像のSHA-256を記録する。

候補slug: `helix-wt/recommendation-cards` / `helix-wt/recommendation-list`。同じ3件・同じ分類を使用した比較画像のみをカタログへ追加する。

## 残件

人気の方式・期間、手動指定、関連記事3方式、IPなし集計、MCPとの設定往復、管理画面での型切替は未確認。カタログの関連付けはAC全体達成を意味しない。スクリーンリーダー実機・全デザイン軸も未確認。

## 2026-09-19 実測結果

- 実機検証 **98/98成功**。同じ公開投稿3件・同順、非公開投稿の除外、画像2件の読込、画像なし1件、長い見出し、記事リンク到達、PC1440/SP390 × JS有無を確認。fixture回収とsource不変も成功。横メディア行は画像右端と本文左端の位置関係、本文幅180px以上、画像の枠充足、画像なし本文全幅をPC/SP×JS有無で追加検証。
- PHP lint 2ファイル、WordPress-Core PHPCS、JS構文、capability manifest、i18n POT生成・一致検査、`npm test` は成功。
- 4枚の実機画像: [カードPC](cards-pc.jpg) / [カードSP](cards-sp.jpg) / [リストPC](list-pc.jpg) / [リストSP](list-sp.jpg)。PCで狭い3列を試した後、本文幅680pxでも読みやすい2列へ調整した。
- 画像なしのリストでは画像要素を省略し、PC/SPとも本文が行の全幅を使用する。SPも画像100px／残りに本文の横メディア行を維持し、カードの縦積みと選択差を残す。専用CSSは追加せずcore Groupのflex行を使う。写真はcoreのfixedNoShrinkで縮小を防ぎ、本文はfillで伸縮する。
- 初回のWP-CLI分類meta引数不整合は修正し、作成済み分類をUUID照合のうえ回収。テーマpatternキャッシュの更新も再現手順へ追加した。失敗した試行を成功証拠へ数えていない。

カタログ操作は `npx playwright test tests/e2e/recommendation-selection-catalog.spec.ts --workers=1` の **2/2成功**。記事面で2候補の発見、PC/SP画像、同じ新着順・件数と参照IDの比較、選択理由保存・再読込、横溢れなしを確認した。[カタログPC](catalog-pc.png) / [カタログSP](catalog-sp.png)。初回specは記事面の選択が抜けて0候補となったため、実際の面切替を手順に追加して再実行した。
