# 同一商品正本の販売面 PoC（WT-FR-SELL-02）

架空のデスクライト３点から、商品カード・ランキング・比較専用テーブル・CTA束・レビューの５候補を生成する。価格・評価・レビュー・順位は説明用fixtureであり、実商品の推薦や実測ではない。２ACはpartial。G3完了や実WordPress実装を主張しない。

## 同一正本と表示契約

`products.mjs` の商品名・価格・評価・配送/返品条件をHTMLとJSON-LDへ投影する。本文の手組み比較表とは `commerce-comparison` の語彙で分ける。比較表は列が商品、行が項目、固定の先頭列、用途別の優位表示、最下行のCTAを持つ。SPは表の中だけ横スクロールする。CTAと条件文は同じ文字サイズ。rankingの根拠と架空レビューの著者・良い点/注意点を表示する。

affiliate/external-store は product snippet 形、self-EC は配送/返品を含む merchant listing 形へ分岐する。商品ごとのOfferにpolicyを持つローカル候補であり、サイト共通policyやSEO-02共通出力機構への統合は未実装。５商品を扱うページではなく同じ３商品を５表示面へ切り替える。JSON-LDの形・値一致の検査はGoogle検索の表示適格性を証明しない。架空情報はnoindexであり、公開商品構造化データとして運用しない。

CTAのvariant IDと目標CV IDは静的属性に出力する。JS有効時はローカルCustomEventと画面内診断に投影し、外部送信・実購入・API接続・保存を行わない。JS無効時も商品/条件/表を読め、CTAはページ内のデモ説明へ移動する。計測/広告の処理はテーマ外、認証情報・AIなし。

## 実測と残件

| AC | ローカル検査 | 未検証 |
|---|---|---|
| SELL-02A | ５面の同一値、snippet/merchant分岐、配送返品、表構造/優位/最下CTA、ID出力、1440/390/320px・JS有無・keyboard | WP 7.2実機、商品正本の保存/API、SEO-02共通出力、本番計測 |
| SELL-02B | 正本を変える反証、policy欠損/未知経路の拒否、ID欠損時イベント停止、レビュー根拠とJSON-LD一致 | Rich Results、URL Inspection、実購入適格性、実商品/レビュー、全ブラウザ/実スクリーンリーダー |

代表card/comparisonの200% root文字（16px→32px）を検査する。ブラウザズーム検査ではない。WP 7.2未提供境界のため互換は候補契約に留める。

## 再現と一次資料

`node scripts/build-product-surfaces-poc.mjs` でHTML/SVGを生成し、`node scripts/verify-product-surfaces-poc.mjs` で42 E2E・PC/SP 10画像・verification・catalog候補・partial admission入力を再生成する。`node scripts/product-surfaces-server.mjs` で配信し `/docs/research/2026-09-20-product-surfaces-poc/card.html` を開く。SVGはコードで作成した架空イラスト。

要求正本はWT-FR-SELL-02 revision 1 / WT-AC-SELL-02A/B。調査正本は `../2026-09-03-external-gap-research.md`。既存product-bundle/compare-articleの販売面語彙と既存PoC証跡契約を踏襲する。

[Google Product snippet](https://developers.google.com/search/docs/appearance/structured-data/product-snippet) と [Merchant listing](https://developers.google.com/search/docs/appearance/structured-data/merchant-listing) を分岐の一次資料とする。単一商品ページ/購入可否/レビュー適格性などの本番判断、Rich Results/URL Inspectionは未検証。
