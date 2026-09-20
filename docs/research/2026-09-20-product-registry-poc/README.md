# 商品正本と複数記事参照 PoC（WT-FR-SELL-01）

P0・zero-candidateのSELL-01を、記事の商品カード・ランキング・比較表の３候補と共通CTAで検査する。既存SELL-02の架空ライトfixtureとSVGを読み取り再利用し、旧AGENTNEO実装・既存記事は変更しない。２ACはpartial。G3完了や実保存は主張しない。

## データの所有者

`model.mjs` のschema付き正本から `product-schema.json` / `product-registry.json` を生成する。名前・価格・特徴・評価・画像・リンク先・取得元/時刻・確認期間を商品ごとに持つ。`article-references.json` は記事ID・タイトル・商品IDの並びだけで、商品値やURLの上書きは拒否する。JSON-LDの入力も同じ正本から投影する。

画面のJSON編集→検証→反映は同じページ内の３記事プレビューに一括反映し、エラー時はすべての前状態を保持する。これは複数の実記事への保存や配信を検証したものではない。schemaに宣言した型・必須・追加プロパティ禁止・範囲/enum/patternをローカル検査器で扱う。検査器はこのschema用の部分実装で、汎用JSON Schemaエンジン互換を主張しない。

## 鮮度と境界

取得元は手動fixture（外部取得なし）。固定時計2026-09-20T12:00:00Zで、取得時刻＋確認期間を超えると「要再確認」を表示する。実時間監視や自動再取得は未実装。affiliate/external-store/self-ECのURLは予約ドメインの例示値。クリックはJS有効時に移動を止める。JS無効では初期表示のみで、例示URLへの実遷移は未検証。

外部EC/ASP APIクライアント・認証情報・決済・カート・会員は持たない。affiliate用merchant feedとProductGroupは要求対象外。WP7.2実機（未提供境界）、JSON/CPT永続保存、実記事への横断反映、権限/排他更新、外部API、検索適格性、実スクリーンリーダーは未検証。SELL-02のshipping/returns適格性検査を置換せず、本PoCのJSON-LDは共通入力の値一致に限る。

## 実測と再現

30 E2E（PC1440/SP390/320、JS有無、keyboard、200% root文字）と3摂動。全正本フィールドの変更を３記事の可視値/リンクと構造化入力で直接照合する。価格負数・URL欠落/外部URL・追加checkout・評価範囲・未来時刻・重複ID・記事上書き/参照欠落を拒否する。価格テキスト乖離・記事override guard除去・評価上限guard除去の各摂動を専用１テストで検出し、source復元をdigest検査する。

SELL-01Aは正本値の共有と一括反映、01Bは記事上書き禁止とテーマ外境界へ対応する。200%はroot文字サイズでありブラウザズームではない。３候補は最初に表示する記事の形を変え、下に同じ他記事を並べて横断反映を確認する。

`node scripts/build-product-registry-poc.mjs` で静的artifact、`node scripts/verify-product-registry-poc.mjs` でE2E/摂動/６画像/verification/partial admission候補を生成する。`node scripts/product-surfaces-server.mjs` の共通研究用localhost配信を使用する。

JSON Schemaの[object/required/additionalProperties](https://json-schema.org/understanding-json-schema/reference/object)を参照（2026-09-20）。実APIや外部サービスのスキーマを取り込んだものではない。
