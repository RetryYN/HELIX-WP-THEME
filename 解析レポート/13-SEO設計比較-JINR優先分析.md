# SEO設計比較 — JIN:R優先分析

## 1. 結論

AGENT NEO のSEO設計は、SWELL型よりも **JIN:R型の統合SEO UX** を優先する。

理由は、AGENT NEO の中核が「AIエージェントがJSONで完結操作できるWPテーマ」であり、title、description、robots、canonical、OGP、構造化データがテーマ外プラグインに分散すると、AI操作契約が不安定になるためである。

ただし、SWELLの設計も捨てない。SWELLのJSON-LD実装は参考価値が高く、SEO SIMPLE PACKとの重複回避・既存SEOデータ参照というポータビリティ設計は、AGENT NEOでも採用すべきである。

## 2. 解析状態

| 項目 | 状態 |
|---|---|
| SWELL親テーマ | ローカルに存在。コードレベルで解析済み |
| JIN:R親テーマ | `jinr-parent/jinr/jinr` に展開済み。コードレベルで解析済み |
| JIN:R子テーマ | ローカルに存在。ただし親テーマCSS読込のみでSEO実装は含まれない |

JIN:R親テーマの実コード解析は `14-JINR親テーマ実コードSEO解析.md` に分離して記録する。本レポートではSWELL/JIN:Rの比較とAGENT NEO設計判断を扱う。

## 3. SWELLのSEO設計

### 3.1 強み

| 観点 | 観測内容 | AGENT NEOへの示唆 |
|---|---|---|
| JSON-LD | `classes/Json_Ld.php` が `@graph` で Organization、WebSite、WebPage、CollectionPage、Article、Author、BreadcrumbList を生成 | Entity Graph Builderの参考になる |
| 構造化データ設定 | `lib/menu/settings/structure_data.php` でJSON-LD有効化、組織名、URL、別名、sameAs、ロゴ、創業者を管理 | SEO Profile UI/APIに取り込む |
| FAQ構造化データ | `lib/gutenberg/render_hook/faq.php` が表示中FAQブロックからFAQPage JSON-LDを生成 | visible content と schema の同期ルールとして採用 |
| パンくず | `parts/breadcrumb.php` がHTML表示とJSON-LD用データを生成 | BreadcrumbはHTMLとschemaを同一ソースから生成する |
| SEOプラグイン連携 | SEO SIMPLE PACKが存在する場合、meta/canonical/description/OG画像を参照 | 他SEOプラグインとの重複回避ガードが必要 |

### 3.2 弱み

| 観点 | 課題 | AGENT NEOでの改良 |
|---|---|---|
| AI操作性 | title、description、canonical、noindex、OGPの主導権がテーマ外プラグインに寄りやすい | テーマ標準のSEO Core契約を持つ |
| 統合UX | SEO設定が構造化データ中心になり、記事別SEO管理は外部依存になりやすい | 記事/LP/商品/カテゴリ単位のSEO操作APIを提供 |
| 法人LP | Product、Offer、Service、FAQ、Lead CTAのSEO/計測統合がテーマ中核ではない | 法人版でLP SEOとCTA計測を統合する |

SWELLは「テーマは軽く、SEOメタは専用プラグインへ寄せる」設計として妥当だが、AGENT NEOのAI運用思想とは一部相性が悪い。

## 4. JIN:RのSEO設計

JIN:R公式情報と親テーマ実コードの両方から、テーマ側でSEO設定を持ち、プラグインなしでSEO管理できる方針を確認できた。主な確認内容は以下である。

| 観点 | 公式情報ベースの内容 | AGENT NEOへの示唆 |
|---|---|---|
| 構造化データ | トップページ・記事ページでJSON-LD形式の構造化データをテーマ側が自動出力 | SEO Coreはテーマ標準機能にする |
| 記事著者/SNS | 記事著者とSNSアカウント紐付けなど細かいSEO施策に配慮 | Author/Person/SameAsを標準Entityにする |
| SEO設定 | noindex、パンくず、トップページSEO、タイトル区切りなどをテーマ管理 | robots/canonical/title規則をJSON契約化する |
| 記事別SEO | title、description、keyword、canonical、noindex、ハッシュタグ設定を移行対象として扱う | 投稿単位のSEOメタAPIを必須にする |
| OGP | トップページOGP画像をカスタマイザーで設定 | OGP/Twitter CardをSEO Coreに含める |
| プラグイン方針 | SEO専用プラグインは重複出力の恐れがあり不要という方針 | AGENT NEOは重複検知と出力優先順位を持つ |

JIN:RのSEO設計は、SEOをテーマの主要UXとして内包している点で、AGENT NEOのAI操作基盤に近い。

### 4.1 実コードで確認したJIN:RのSEO構成

| ファイル | 役割 |
|---|---|
| `header.php` | OGP、description、noindex、keywords、canonical、任意タグを `wp_head()` 前に読み込む |
| `include/head/title.php` | `pre_get_document_title` でtitleを制御 |
| `include/head/description.php` | 記事別description、抜粋、自動descriptionを出力 |
| `include/head/noindex.php` | 投稿/固定/カテゴリ/タグ/検索/著者/添付/404のnoindexを制御 |
| `include/head/others.php` | WordPress標準canonicalを置き換え、独自canonicalとTwitter Cardを出力 |
| `include/head/ogp.php` | OGP title/description/url/image/typeを出力 |
| `include/json-ld.php` | Article、WebPage、WebSite、CollectionPage、BreadcrumbList、Person、Organizationを出力 |
| `include/jinr-setting.php` | JINR設定にSEO/noindex/パンくず/title設定を登録 |
| `include/custom-functions.php` | SEO post metaを `show_in_rest: true` で登録 |

## 5. AGENT NEOの採用方針

### 5.1 基本方針

AGENT NEOは **JIN:R型の統合SEO UX** を採用し、**SWELL型のJSON-LD品質とプラグイン共存性** を組み合わせる。

| 方針 | 内容 |
|---|---|
| SEO Core化 | SEOをP0機能にする。プラグイン任せにしない |
| JSON契約化 | AIがSEOを安全に更新できるschemaを定義する |
| Entity Graph化 | Organization、Person、WebSite、WebPage、Article、Product、Offer、Review、FAQ、Breadcrumbを統合管理 |
| 重複回避 | SEOプラグインが同種タグを出す場合、検知・警告・出力制御する |
| 永続性 | SEOデータをテーマ専用の閉じた形式だけに保存しない。移行可能なメタキー/JSON exportを持つ |

### 5.2 必須契約ファイル

| ファイル | 役割 |
|---|---|
| `seo-profile.json` | サイト/組織/著者/SNS/ロゴ/検索表示の基本設定 |
| `seo-meta.schema.json` | 投稿、固定ページ、LP、カテゴリ、商品ごとのSEOメタ契約 |
| `entity-graph.schema.json` | JSON-LD `@graph` を構成するEntity契約 |
| `seo-conflict-rules.json` | SEOプラグインとの重複検知・出力優先順位 |

### 5.3 操作API案

```json
{
  "action": "seo.updateMeta",
  "target": {
    "type": "post",
    "id": 123
  },
  "payload": {
    "title": "比較記事タイトル",
    "description": "検索結果とSNSで使う説明文",
    "canonical": "https://example.com/post-slug/",
    "robots": {
      "index": true,
      "follow": true
    },
    "ogp": {
      "title": "SNS用タイトル",
      "description": "SNS用説明文",
      "imageId": 456
    }
  },
  "options": {
    "dryRun": true,
    "validateDuplicates": true
  }
}
```

```json
{
  "action": "seo.attachEntityGraph",
  "target": {
    "type": "landing_page",
    "id": 88
  },
  "payload": {
    "entities": [
      {
        "@type": "Product",
        "name": "AGENT NEO 法人版",
        "offers": {
          "@type": "Offer",
          "price": "98000",
          "priceCurrency": "JPY"
        }
      },
      {
        "@type": "FAQPage",
        "sourceBlockIds": [
          "faq-001",
          "faq-002"
        ]
      }
    ]
  }
}
```

## 6. 個人版SEO

| 機能 | 優先度 | 理由 |
|---|---|---|
| Article/BlogPosting | P0 | 記事投入が中心 |
| Product | P0 | 商品レビュー・比較記事に必要 |
| Review | P0 | アフィリエイト記事の差別化 |
| Breadcrumb | P0 | カテゴリ構造と回遊性を検索エンジンに伝える |
| FAQ | P1 | CV補助とAI理解には有効。ただしGoogleのFAQリッチリザルト露出は限定的 |
| canonical/noindex | P0 | 重複・低品質ページ制御に必須 |
| PR表記 | P0 | アフィリエイトの信頼性と法令/広告表示対応 |

## 7. 法人版SEO

| 機能 | 優先度 | 理由 |
|---|---|---|
| Organization | P0 | 法人サイトの信頼性基盤 |
| Product/Service | P0 | 製品・サービスLPの検索理解に必要 |
| Offer | P1 | 価格ページ・プラン訴求に有効 |
| FAQ | P1 | 商談前の疑問解消とCV補助 |
| Breadcrumb | P0 | コーポレート/LP/事例構造の明示 |
| Case Study schema候補 | P2 | 標準schemaへの写像を要検討 |
| CTA計測連携 | P0 | SEO流入後のCV改善まで一体化するため |

法人版は「SEOタグを出すテーマ」ではなく、「SEO流入からLP改善・CTA計測まで閉じる運用基盤」として設計する。

## 8. アーキテクチャ案

| コンポーネント | 責務 |
|---|---|
| `SeoProfileRepository` | サイト/組織/著者/SNS/ロゴ設定の保存・取得 |
| `SeoMetaRepository` | 投稿/LP/分類ごとのtitle、description、robots、canonical、OGP保存 |
| `EntityGraphBuilder` | ページ文脈からJSON-LD `@graph` を構築 |
| `SeoConflictDetector` | SEOプラグイン・重複meta・重複JSON-LDを検知 |
| `SeoValidationService` | schema.org必須項目、URL、画像、robots、canonicalを検証 |
| `SeoActionController` | REST/MCP/WP CLI/UIから同一操作を受付 |
| `AutomationSeoAdapter` | Automation SEOからのメタ提案、Entity提案、改善指示を反映 |

## 9. 注意点

| 注意点 | 対応 |
|---|---|
| FAQリッチリザルトはGoogleで表示対象が限定された | FAQ schemaを「順位保証/表示保証」として売らない |
| SEOプラグインと重複すると逆効果 | 重複検知、警告、出力停止、優先順位設定を必須にする |
| テーマ依存保存は移行時に弱い | export/importと互換メタキー設計を持つ |
| AIがcanonical/noindexを誤ると致命的 | dryRun、diffReview、危険操作警告、rollbackを必須にする |

## 10. 参照ソース

| ソース | 確認内容 |
|---|---|
| https://jinr.jp/feature/ | JIN:Rの構造化データ自動出力、著者/SNS紐付け |
| https://jinr.jp/manual/seo-setting-3/ | JIN:RのSEO設定、noindex、SEOプラグイン重複注意 |
| https://jinr.jp/manual/jin-to-jinr/ | 記事別SEO設定、canonical、noindex、OGP移行項目 |
| https://jinr.jp/manual/description-2/ | JIN:Rのdescription設定 |
| https://jinr.jp/manual/ogp/ | JIN:RのOGP設定 |
| https://swell-theme.com/feature/ | SWELLのテーマ特徴 |
| https://swell-theme.com/basic-setting/4688/ | SWELLの推奨/注意プラグイン方針 |
| https://developers.google.com/search/blog/2023/08/howto-faq-changes | FAQ/HowToリッチリザルトの表示変更 |

## 11. Gate判定

| Gate | 判定 | 根拠 |
|---|---|---|
| RG0 | passed | SWELL親テーマとJIN:R親テーマを実コード解析済み |
| RG1 | passed | SEO対象の契約候補をtitle/description/robots/canonical/OGP/schemaへ分解 |
| RG2 | passed | JIN:R統合UX + SWELL JSON-LD/共存設計のハイブリッド方針を定義 |
| RG3 | passed | ユーザー仮説「JIN:RのほうがSEO設計が良い」を実コードで支持 |
| R4 | passed | L1/L2/package.matrix/analysis-summaryへSEO Coreとして接続 |
