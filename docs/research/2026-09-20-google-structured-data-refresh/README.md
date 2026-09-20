# Google 構造化データ・検索仕様の再確認（2026-09-20）

## 目的

既存の `WT-FR-SEO-01` / `WT-FR-SEO-02` / `WT-FR-SEO-04` / `WT-NFR-SEO-01` が、現在の Google Search Central の公開情報とずれていないかを再確認した。ここでは検索表示を保証せず、テーマ側が出してよい構造化データと、出してはいけない型を整理する。

## 再確認結果

| 確認対象 | 2026-09-20 の確認結果 | 既存要求への影響 |
| --- | --- | --- |
| Breadcrumb (`BreadcrumbList`) | `itemListElement` の順序、`position`、`name`、`item` を要求する。少なくとも 2 つの `ListItem` を持ち、最後の項目だけ `item` を省略できる。URL の分解をそのまま表示するのではなく、利用者の典型的な導線を表す。 | `WT-FR-NAV-01` の同一正本・機械導出を維持する。Breadcrumb の表示と JSON-LD を別々に手書きしない。 |
| 一般ガイドライン | JSON-LD が推奨形式。構造化データはページ上の可視内容を表し、隠し内容や空のデータ保持ページを作らない。正しい構文でも検索表示は保証されない。 | `WT-FR-SEO-04` の「機械検査」と「表示保証を主張しない」境界を維持する。 |
| 検索機能の更新 | Practice problem は Search の表示対象から外れ、Dataset は Google Search のリッチリザルト対象ではなく Dataset Search 用。過去に削除・非推奨となった型を既定出力しない。 | `WT-FR-SEO-02` と `WT-NFR-SEO-01` の廃止型台帳へ更新差分を取り込む。FAQPage / HowTo / SearchAction を既定出力しない判断は変わらない。 |
| Product | 購入できない編集記事は product snippet、購入可能な商品面は merchant listing の契約を分ける。 | `WT-FR-SELL-02` の表示と JSON-LD の一元化、販売経路別の未検証境界を維持する。 |
| Merchant listing technical guidance | 価格・在庫のように変化が速い商品情報は、JavaScriptだけで後挿入するより初期 HTML に構造化データを置く方が安定する。 | `WT-FR-SELL-02` では初期HTMLを優先する設計境界として記録するが、検索適格性・購入連携は未検証のままにする。 |

## 要求化と採用判断

- 新しい要求 ID は追加しない。今回の差分は既存の SEO / NAV 要求がすでに表現している範囲の更新であり、未検証の実装を完了扱いにしない。
- 公式 URL、参照日、状態差分、採用判断をこの資料に固定し、次の SEO PoC の証跡にはこのファイルを source として束縛する。
- `WT-FR-SEO-04` と `WT-NFR-SEO-01` の検査器は、廃止型の追加・削除を台帳差分として赤にし、参照日の古さを PASS にしない。
- `WT-FR-NAV-01` の LP は、ディレクトリ階層に依存しない URL をパンくずへ無理に投影せず、サイト設定が選ぶ典型導線を別の正本として扱う設計課題を残す。
- `WT-FR-SELL-02` の現行 PoC は、検索表示適格性・実購入・外部 API を検証していない。今回の更新でその未検証範囲を縮めたとは扱わない。

## 一次資料

確認日: **2026-09-20 (UTC)**

- [Breadcrumb structured data](https://developers.google.com/search/docs/appearance/structured-data/breadcrumb)
- [Introduction to structured data](https://developers.google.com/search/docs/appearance/structured-data/intro-structured-data)
- [General structured data guidelines](https://developers.google.com/search/docs/appearance/structured-data/sd-policies)
- [Google Search documentation updates](https://developers.google.com/search/updates)
- [Product structured data](https://developers.google.com/search/docs/appearance/structured-data/product)
- [Merchant listing structured data](https://developers.google.com/search/docs/appearance/structured-data/merchant-listing)

検索結果への掲載、順位、リッチリザルトの表示は Google の判断に依存するため、この資料は実装の適格性と更新監視の根拠だけを扱う。
