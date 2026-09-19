# 記事内語彙の選択カタログ PoC

2026-09-13。現行 HELIX WT の要求 `WT-FR-VOCAB-01` / `WT-AC-VOCAB-01A/B` に対する**静的表示提案**。`index.html` をブラウザで開く。WordPress本体での登録・編集・保存・配信を証明するものではなく、受入全体は部分対応。

## 今回できること

14語彙を1つずつ対応表へ割り当て、記事内の用例から受け皿を比較できる。販売系4種を別セクションに配置し、本文中の手組み比較表と商品比較専用テーブルを別の識別子・見出し・データとして表示する。商品・価格・順位は架空。購入や申込みは発生しない。

深緑の文字と明るい紙色、章番号、余白、見本ごとの境界で情報を整理した。スマートフォンでは1列化し、表の横スクロールを局所化、CTA束を縦積みする。JSを必要としないdetailsと実在するページ内リンクを使い、操作の行き止まりを避ける。各カードの技術ラベルは製品UIではなく選定・レビュー用の注記。

## 14語彙と受け皿の提案

正本は `mapping.json`。全行 `proposed`。受け皿が1つ決まることと、実装済みであることを混同しない。

| 語彙 | 受け皿案 |
|---|---|
| 囲み | core/group + style |
| ボタン | core/buttons + style |
| ブログカード | core/group + style |
| 吹き出し | 新規 speech |
| 手順 | core/list + style |
| 記事一覧 | core/query + style |
| アコーディオン | core/details + style |
| タブ | core/tabs + style |
| 全幅 | core/group alignfull + style |
| リッチメニュー | core/navigation + style |
| 会員制限 | core/group + style / 外部認可 |
| 本文比較表 | core/table + style |
| 定義リスト | core/htmlのsemantic dl + style |
| FAQ | core/details + style |

定義リストは編集体験が未検証のため暫定案。会員制限は表示の受け皿のみで、認可機構は外部側責務。限定本文そのものをこのHTMLへ埋め込んでいない。タブ見本はSP・noJSフォールバック用のdetailsであり、core/tabsの実レンダリングや相互変換の証拠にはならない。ブログカードのURL解決・キャッシュ・REST非依存も未検証。

## 上位7の根拠

`docs/research/2026-08-26-theme-structure-audit/reports/INV-01-block-vocabulary-map.md` §2の行1〜7に従う。囲み、ボタン、ブログカード、吹き出し、本文比較表、定義リスト、FAQ。元資料の値はテーマ別・親子ブロック別で、安易に合算した全市場ランキングではない。今回の7種は受入で追跡する原票の7語彙であり、市場の最新シェアを主張しない。

## 新規ブロック上限

提案の6枠は speech / review / product-card / ranking / product-comparison / cta-bundle。第7枠を空け、第8枠は検査で拒否する。負例は受け皿欠落、比較表の混同、第8枠の3種。第7枠を追加した正例も通す。

**この検査は提案契約の検査だけで、実際のWordPress登録を制限しない。** 現行試作のカスタム登録は7を超えており、`docs/requirements/l3/g3-approval-summary.md` の pending resolution が残る。カテゴリ系などを勝手に対象外にして上限達成と判定しない。今回の追加登録数は0。

## 検証

`node scripts/verify-vocabulary-catalog.mjs`：42/42。PC 1440px / SP 390px × JSあり・なし。

- 14語彙、原票の上位7、販売4種の存在・表示
- ページ横はみ出しなし、カード同士の重なりなし
- 全リンクとsummaryの44px対象サイズ、ページ内リンク解決
- 可視テキストの計算色でAA 4.5:1以上（画像内文字・ブラウザ外UIは対象外）
- FAQの実クリックで開閉
- 対応表と上限の正例・負例

`pc-js.png` / `sp-js.png` / `pc-nojs.png` / `sp-nojs.png` が全景。`*-box.png` / `*-product-card.png` / `*-mapping.png` は詳細。専用スクリプトとHTML/CSS/JSONのSHA256を `verification.json` に保存。製品全体のa11y適合やWordPressでの互換性はこの検査では判定しない。

## 残る範囲

WordPressの実登録と6+1枠の整合、core+style登録、編集保存の往復、core/tabs実機動作、SP変換とdevice別編集、会員認可、商品正本・構造化データ・計測、5択メディア、レビュー枠の実装。受入01A/B全体は未完了。

## 公式資料（参照 2026-09-13）

- [Core Tabs](https://developer.wordpress.org/block-editor/reference-guides/core-blocks/core-blocks-design/core-block-tabs/)：受け皿候補が公式coreに存在することを確認。今回の静的見本で実行してはいない。
- [Details block](https://wordpress.org/documentation/article/details-block/)：追加内容の開閉の基本動作。
- [Table block](https://wordpress.org/documentation/article/table-block/)：本文中で扱う表の基本受け皿。

現行 `theme/helix-wt/patterns/article-kit.php` のcore/group・table・商品表示の既存例を読み取り参照した。旧AGENT NEO配下・他リポジトリには変更していない。
