# THEME-INV-06 レポート — 構造化データ出力の差分

- 対象イシュー: `issues/THEME-INV-06-structured-data-gap.md`
- 状態: **コード解析パート完了 / ②〜④（実ページ出力の採取と重複判定）は未了**
- 調査日: 2026-08-26
- 手段: ホスティング SSH 読み取り専用（ソースからの `@type` 抽出）
- 一次証跡: `evidence/probe6-raw.txt`（テーマA / テーマB の JSON-LD `@type` 抽出結果）・
  `~/dev/HELIX-WP-THEME/themes/agent-neo-theme/inc/seo/class-structured-data.php`

## 1. 実装の所在と規模

| テーマ | 実装 | 行数 |
|---|---|---|
| テーマA | `include/json-ld.php` | 344 |
| テーマB | `classes/Json_Ld.php` | 472 |
| AGENT NEO | `inc/seo/class-structured-data.php` | — |

テーマA の `json-ld.php` は `functions.php` から**条件付きで** `get_template_part` される
（`evidence/re-themeA-boot.txt` の require 一覧 L217 に条件分岐あり）。常時出力ではない。

## 2. 出力される `@type` の比較

証跡（`evidence/probe6-raw.txt` の抽出結果をそのまま整理）:

| `@type` | テーマA | テーマB | AGENT NEO |
|---|---|---|---|
| `Article` | — | **○** | — |
| `BlogPosting` | — | — | **○** |
| `WebPage` | — | ○ | ○ |
| `WebSite` | — | ○ | ○ |
| `CollectionPage` | — | **○** | — |
| `SearchAction` | — | **○** | — |
| `BreadcrumbList` | — | ○ | ○ |
| `Organization` | ○ | ○ | ○ |
| `Person` | ○ | ○ | ○ |
| `ImageObject` | ○ | ○ | ○ |
| `ListItem` | ○ | ○ | ○ |

**読み取れること**

1. **テーマA は記事本体の型（`Article` / `BlogPosting`）を出していない。**
   出力は `Organization` / `Person` / `ImageObject` / `ListItem` の 4 型のみで、
   これは「パンくず（`ListItem`）＋ 発行者・著者情報」に相当する。記事そのものの構造化データが無い。
   → 記事型は SEO プラグイン側に委ねている可能性が高い（§4 で検証が必要）。
2. **テーマB が最も広い。** 記事（`Article`）に加え、
   アーカイブ（`CollectionPage`）とサイト内検索（`SearchAction`）まで出す。
3. **AGENT NEO は記事型に `BlogPosting` を採用**。`Article` のサブタイプであり、
   ブログ記事としては `BlogPosting` のほうが具体的で妥当。
   欠けているのは **`CollectionPage`** と **`SearchAction`** の 2 型。

## 3. AGENT NEO に不足する型（優先度付き）

| 型 | 用途 | 優先度 | 根拠 |
|---|---|---|---|
| `CollectionPage` | カテゴリ・タグ・アーカイブページ | **高** | 記事一覧が主要な流入面。テーマB は実装済み。テンプレートは `archive.html` が既にある |
| `SearchAction`（`WebSite` の `potentialAction`） | サイト内検索（サイトリンク検索ボックス） | 中 | `search.html` テンプレートが既にある。実装コストが小さい |
| `FAQPage` | FAQ ブロックを含む記事 | 中 | 実使用: `themeB/faq` 6 / `themeB/faq-item` 56。テーマA 側にも FAQ 相当あり。**中間 JSON の faq ノードから機械的に導出できる** |
| `HowTo` | 手順ブロックを含む記事 | 低 | 実使用: `themeB/step` 7 / `themeB/step-item` 28。Google のリッチリザルト対象は縮小傾向のため優先度低 |
| `ItemList` | 比較・ランキング記事 | 低〜中 | テーマA の `compare` 59 / `comparechild` 177。収益記事の中心なので効果は要検討 |

**設計上の含意**: `FAQPage` / `HowTo` / `ItemList` は**中間 JSON の意図ノードから自動生成できる**。
テーマが本文を後から解析する必要がない（テーマB は本文から拾う必要がある）。
これは中間 JSON 方式の明確な優位点で、INV-01（意図語彙）の設計に反映すべき。

## 4. プラグインとの二重出力リスク

両サイトの active プラグイン（`<local-poc-evidence>/audit-topic-A.json` ほか）:

| プラグイン | topic-A (テーマA) | site-B (テーマB) | 構造化データへの関与 |
|---|---|---|---|
| `google-sitemap-generator` | ○ | ○ | sitemap のみ。JSON-LD は出さない |
| `website-llms-txt` | ○ | ○ | llms.txt 生成。LLM 向け。JSON-LD とは別系統 |
| `seo-simple-pack` | — | ○ | **メタタグ + JSON-LD を出す可能性あり** |
| `google-site-kit` | ○ | ○ | 計測タグのみ |

**確定（実測 http-audit-raw.txt）**: テーマA サイトでは記事・カテゴリ・検索の全ページ種別で
`ld+json` ブロック数 = 0。すなわち**現状 JSON-LD は誰も出していない**（`seo-simple-pack` は
テーマB サイトにのみ導入、テーマA サイトでは記事型 JSON-LD 未出力）。`og:type=article` の
OGP メタのみ存在。→ 二重出力の懸念は現時点で無く、AGENT NEO 側が唯一の出力元になれる
（空き地に新設できる）。

**方針**: AGENT NEO 側は `inc/seo/class-structured-data.php` が単一の出力元であるべきで、
プラグインが同種の JSON-LD を出す場合は**どちらかを止める**。二重出力は
Google のリッチリザルトテストで警告になり、`@id` の不整合を生む。
既存の `config/third-party-tags.json` と `Third_Party_Manager` が
サードパーティタグの管理機構として存在するので、そこへ「構造化データの出力主体」を
宣言する項目を足すのが筋。

## 5. AIO / LLMO との関係

両サイトに `website-llms-txt` が入っており、LLM 向けの `llms.txt` を生成している。
これは JSON-LD とは別系統だが、**「機械可読な記事情報を出す」という目的は共通**。

中間 JSON を正本に持つ AGENT NEO は、原理的には
**同じ JSON から JSON-LD と llms.txt の両方を導出できる**。
プラグインに任せると正本が二重化するため、INV-12（資産再利用可否台帳）の
「LLMO」項目と合わせて自前化の可否を判断する。
（`agent-neo-core` には `llmo-summary-controller` が既に存在する）

## 6. 未了項目

- [ ] **②実ページ出力の採取** — 記事 / アーカイブ / 検索 / トップの 4 種別で
      実際に出力される JSON-LD を取得して比較する（HTTP GET のみ・読み取り）
- [ ] **テーマA の記事型の出所特定** — `automation-seo` プラグインか、出していないか
- [ ] `seo-simple-pack`（site-B のみ）が JSON-LD を出しているかの確認
- [ ] `class-structured-data.php` の実装詳細（`@id` の付け方・`@graph` 構造の有無）の精読
- [ ] `CollectionPage` / `SearchAction` の実装差分の見積もり

## 7. 証跡ファイル

| 内容 | 場所 |
|---|---|
| テーマA / テーマB の JSON-LD `@type` 抽出 + 行数 | `evidence/probe6-raw.txt` |
| テーマA の `json-ld.php` 読み込み条件 | `evidence/re-themeA-boot.txt`（require 一覧 L217） |
| 両サイトの active プラグイン一覧 | `<local-poc-evidence>/audit-topic-A.json` |
| AGENT NEO の出力型 | `themes/agent-neo-theme/inc/seo/class-structured-data.php` |

## 2026-08-27 実測の反映

`evidence/http-audit-raw.txt` の自サイト read-only GET で、
front / single / category / search の 4 種別すべてに `ld+json` が **0 本**だった。

| 実測項目 | 結果 |
|---|---|
| JSON-LD | 4 種別すべて 0 本 |
| `og:type` | category・search でも `article` |

したがって §2 の「テーマAは記事型を出していない」という結論は、
実際には Article だけでなく JSON-LD 機構そのものが不在という、より強い結論で裏付けられた。
現状は二重出力ではなく、`Article` を含む構造化データを新設できる空き地である。
`CollectionPage` / `SearchAction` は Article の後に検討する順序とする。
実装着手と最終的な単一出力元の採用は PO 判断に残る。
