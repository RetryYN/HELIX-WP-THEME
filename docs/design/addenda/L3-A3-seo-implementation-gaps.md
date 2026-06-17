# L3-A3 SEO 実装ギャップ設計 Addendum

> **ドキュメント種別**: L3 設計 Addendum（GAP-RT 補完）
> **担当領域**: SEO 実装 — JIN:R 核 / E-E-A-T
> **参照 GAP-RT**: GAP-RT-015 / GAP-RT-017 / GAP-RT-018 / GAP-RT-019 / GAP-RT-020
> **ベース設計**: L2-design.md §8.2 / L3-detailed-design.md §0.1 / ADR-005
> **作成日**: 2026-06-18
> **ステータス**: DRAFT（L4 着手前 TL レビュー必要）

---

## 0. 前提・設計原則

### 0.1 FSE + REQ-NF-025 責務境界

本 addendum が扱う SEO 実装はすべて **AGENT NEO テーマ側の責務境界**（出力レンダリング + スキーマ構造）に限定する。

| 境界 | AGENT NEO（本 addendum 対象） | Automation SEO |
|------|-------------------------------|----------------|
| JSON-LD / meta / canonical / OGP の **出力レンダリング** | ✅ Owner | — |
| **スキーマ構造** の定義・保存（seo-profile / seo-meta / entity-graph） | ✅ Owner | 読み取り参照 |
| SEO **判断ロジック**（最適 meta 生成 / Entity 提案 / SEO スコア算出） | ❌ 禁止（REQ-NF-025） | ✅ Owner |
| **AI による meta 自動生成** | ❌ 禁止（REQ-NF-025） | ✅ Owner |

### 0.2 JIN:R 実コードとの対応

解析レポート `14-JINR親テーマ実コードSEO解析.md` / `13-SEO設計比較-JINR優先分析.md` / `31-JINR-seo-hero-deep-extract.md` が根拠。
JIN:R は `include/head/tags.php`（任意タグ）・`include/json-ld.php`（Person/Organization/Article 等）・`include/custom-functions.php`（SEO post meta REST 公開）を中核とするが、raw echo / sanitize_callback 欠落 / ` @graph` 未採用など複数の改良点が必要。AGENT NEO はこれを FSE / block.json / REST / JSON 契約で再設計する。

---

## 1. GAP-RT-019 — SEO Core 4 契約スキーマの L3 定義

**振り分け**: L3-patch / **重大度**: 高 / **関連 REQ**: REQ-F-011, REQ-NF-002, REQ-NF-018

### 1.1 スキーマ概要

L2-design.md §8.2 ではスキーマ名が列挙されているのみで内容が未定義。本節が L3 レベル定義の正本となる。

#### (A) `seo-profile.schema.json` — サイト・組織・著者基本設定

**用途**: サイト全体の SEO 基盤設定。WordPress site-level options に保存。`SeoProfileRepository` が担当。

```jsonc
{
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "title": "SeoProfile",
  "type": "object",
  "required": ["site", "organization"],
  "properties": {
    "site": {
      "type": "object",
      "required": ["name", "url"],
      "properties": {
        "name":             { "type": "string",  "maxLength": 60 },
        "url":              { "type": "string",  "format": "uri" },
        "description":      { "type": "string",  "maxLength": 160 },
        "titleTemplate":    { "type": "string",  "description": "%s は記事/ページ title に置換。例: '%s | サイト名'" },
        "titleSeparator":   { "type": "string",  "enum": ["|", "-", "–", "—", "»", "·", "•"], "default": "|" },
        "titleOmitSiteName":{ "type": "boolean", "default": false },
        "defaultRobots":    { "$ref": "#/$defs/robotsDirective" },
        "language":         { "type": "string",  "pattern": "^[a-z]{2}(-[A-Z]{2})?$", "default": "ja-JP" },
        "logo":             { "$ref": "#/$defs/imageRef" }
      }
    },
    "organization": {
      "type": "object",
      "required": ["name"],
      "properties": {
        "schemaType":  { "type": "string", "enum": ["Organization", "Corporation", "LocalBusiness", "NGO", "EducationalOrganization"], "default": "Organization" },
        "name":        { "type": "string" },
        "alternateName": { "type": "string" },
        "url":         { "type": "string", "format": "uri" },
        "logo":        { "$ref": "#/$defs/imageRef" },
        "sameAs":      { "type": "array", "items": { "type": "string", "format": "uri" }, "description": "SNS / Wikipedia / Wikidata 等の公式 URL 一覧" },
        "foundingDate":{ "type": "string", "format": "date" },
        "address":     { "$ref": "#/$defs/postalAddress" }
      }
    },
    "website": {
      "type": "object",
      "properties": {
        "searchAction": {
          "type": "boolean",
          "description": "WebSite @type + SearchAction を出力するか（サイト内検索 schema.org 対応）",
          "default": true
        }
      }
    },
    "sns": {
      "type": "object",
      "description": "サイト全体の SNS アカウント。OGP og:site_name / twitter:site に使用",
      "properties": {
        "twitterHandle": { "type": "string", "pattern": "^@[A-Za-z0-9_]{1,50}$" },
        "twitterCardType": { "type": "string", "enum": ["summary", "summary_large_image"], "default": "summary_large_image" },
        "facebookAppId":  { "type": "string" },
        "instagramHandle":{ "type": "string" },
        "youtubeChannelId":{ "type": "string" }
      }
    },
    "indexabilityPolicy": {
      "type": "object",
      "description": "ページ種別ごとのデフォルト robots 設定（JIN:R noindex 設計を FSE 契約化）",
      "properties": {
        "post":         { "$ref": "#/$defs/robotsDirective" },
        "page":         { "$ref": "#/$defs/robotsDirective" },
        "lp":           { "$ref": "#/$defs/robotsDirective" },
        "category":     { "$ref": "#/$defs/robotsDirective" },
        "tag":          { "$ref": "#/$defs/robotsDirective" },
        "author":       { "$ref": "#/$defs/robotsDirective", "default": { "index": false, "follow": true } },
        "date":         { "$ref": "#/$defs/robotsDirective", "default": { "index": false, "follow": true } },
        "search":       { "$ref": "#/$defs/robotsDirective", "default": { "index": false, "follow": false } },
        "attachment":   { "$ref": "#/$defs/robotsDirective", "default": { "index": false, "follow": false } },
        "pagination":   { "$ref": "#/$defs/robotsDirective", "default": { "index": false, "follow": true } },
        "404":          { "$ref": "#/$defs/robotsDirective", "default": { "index": false, "follow": false } }
      }
    }
  },
  "$defs": {
    "robotsDirective": {
      "type": "object",
      "properties": {
        "index":  { "type": "boolean", "default": true },
        "follow": { "type": "boolean", "default": true }
      }
    },
    "imageRef": {
      "type": "object",
      "properties": {
        "mediaId": { "type": "integer" },
        "url":     { "type": "string", "format": "uri" },
        "width":   { "type": "integer" },
        "height":  { "type": "integer" },
        "altText": { "type": "string" }
      }
    },
    "postalAddress": {
      "type": "object",
      "properties": {
        "streetAddress":  { "type": "string" },
        "addressLocality": { "type": "string" },
        "addressRegion":  { "type": "string" },
        "postalCode":     { "type": "string", "pattern": "^[0-9]{3}-[0-9]{4}$" },
        "addressCountry": { "type": "string", "enum": ["JP"], "default": "JP" }
      }
    }
  }
}
```

**保存**: `wp_options` の `agent_neo_seo_profile`（JSON 文字列）/ REST: `GET /agent-neo/v1/seo/profile` で取得可。
**更新**: `POST /agent-neo/v1/seo/profile` — `manage_options` capability 必須。

---

#### (B) `seo-meta.schema.json` — 投稿 / LP / 分類ごとの SEO メタ契約

**用途**: ページ単位の SEO メタデータ。JIN:R の `_jinr_seotitle_display` 等を標準化した AGENT NEO 版。`SeoMetaRepository` が担当。

```jsonc
{
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "title": "SeoMeta",
  "type": "object",
  "required": ["target_type", "target_id"],
  "properties": {
    "target_type": { "type": "string", "enum": ["post", "page", "lp", "term", "front_page"] },
    "target_id":   { "type": ["integer", "string"], "description": "WP post_id or term_id or 'front'" },
    "title": {
      "type": "object",
      "description": "SEO title（titleTemplate 適用前の記事固有タイトル）",
      "properties": {
        "value":  { "type": "string", "maxLength": 70 },
        "source": { "type": "string", "enum": ["manual", "ai", "post_title", "template"], "default": "post_title" }
      }
    },
    "description": {
      "type": "object",
      "description": "meta description（160 文字推奨）",
      "properties": {
        "value":  { "type": "string", "maxLength": 320 },
        "source": { "type": "string", "enum": ["manual", "ai", "excerpt", "auto_generated"], "default": "excerpt" }
      }
    },
    "robots": {
      "type": "object",
      "description": "ページ個別 robots（seo-profile.indexabilityPolicy より優先）",
      "properties": {
        "index":       { "type": "boolean" },
        "follow":      { "type": "boolean" },
        "noarchive":   { "type": "boolean" },
        "nosnippet":   { "type": "boolean" },
        "max_snippet": { "type": "integer", "minimum": -1, "maximum": 300 }
      }
    },
    "canonical": {
      "type": "object",
      "properties": {
        "url":    { "type": "string", "format": "uri", "description": "カスタム canonical。空の場合は permalink を使用" },
        "source": { "type": "string", "enum": ["manual", "ai", "auto_permalink"] }
      }
    },
    "ogp": {
      "type": "object",
      "description": "Open Graph Protocol メタ",
      "properties": {
        "title":       { "type": "string", "maxLength": 95, "description": "og:title。空の場合は seo.title を使用" },
        "description": { "type": "string", "maxLength": 200 },
        "imageMediaId":{ "type": "integer", "description": "WP media ID。0 = site fallback 画像" },
        "imageUrl":    { "type": "string",  "format": "uri", "description": "imageMediaId 変換後の URL（読み取り専用）" },
        "type":        { "type": "string",  "enum": ["website", "article"], "description": "og:type。home/front = website、それ以外は article" }
      }
    },
    "schema_override": {
      "type": "string",
      "description": "ページ固有のカスタム JSON-LD スニペット（上級ユーザー向け。EntityGraphBuilder の出力に追加 merge される）。REQ-NF-002 に基づき wp_json_encode + schema validation 必須"
    },
    "updated_at": { "type": "string", "format": "date-time", "description": "最終更新日時（UTC）" }
  }
}
```

**保存**: WP `post_meta` の `_agent_neo_seo_meta`（JSON 文字列）/ term_meta の `agent_neo_seo_meta`（term の場合）。
**REST**: `GET /agent-neo/v1/seo/{post_id}` / `POST /agent-neo/v1/seo/{post_id}/apply`（SEO risk diff 必須）。

---

#### (C) `entity-graph.schema.json` — JSON-LD @graph Entity 契約

**用途**: `EntityGraphBuilder` が組み立てる JSON-LD `@graph` の Node 仕様。SWELL 寄りの `@graph` 統合方式（解析レポート §14-9 改良案）を採用。

```jsonc
{
  "$schema": "https://json-schema.org/draft/2020-12/schema",
  "title": "EntityGraph",
  "type": "object",
  "description": "wp_footer フック経由で <script type='application/ld+json'> として出力される @graph 構造体",
  "required": ["@context", "@graph"],
  "properties": {
    "@context": { "const": "https://schema.org" },
    "@graph": {
      "type": "array",
      "items": { "$ref": "#/$defs/EntityNode" }
    }
  },
  "$defs": {
    "EntityNode": {
      "type": "object",
      "required": ["@type", "@id"],
      "properties": {
        "@type": {
          "description": "schema.org type。単一型は文字列、複数型（例: [\"Product\",\"Review\"]）は配列で表現",
          "oneOf": [
            {
              "type": "string",
              "enum": [
                "WebSite", "WebPage", "Article", "BlogPosting", "NewsArticle",
                "CollectionPage", "SearchResultsPage",
                "Organization", "Corporation", "LocalBusiness",
                "Person",
                "Product", "Offer",
                "Review", "AggregateRating",
                "FAQPage", "Question", "Answer",
                "BreadcrumbList", "ListItem",
                "HowTo", "HowToStep"
              ]
            },
            {
              "type": "array",
              "items": {
                "type": "string",
                "enum": [
                  "WebSite", "WebPage", "Article", "BlogPosting", "NewsArticle",
                  "CollectionPage", "SearchResultsPage",
                  "Organization", "Corporation", "LocalBusiness",
                  "Person",
                  "Product", "Offer",
                  "Review", "AggregateRating",
                  "FAQPage", "Question", "Answer",
                  "BreadcrumbList", "ListItem",
                  "HowTo", "HowToStep"
                ]
              },
              "minItems": 1
            }
          ]
        },
        "@id": { "type": "string", "format": "uri", "description": "このノードの正規 URI（通常は URL#fragment）" }
      },
      "allOf": [
        { "$ref": "#/$defs/WebSiteNode" },
        { "$ref": "#/$defs/ArticleNode" },
        { "$ref": "#/$defs/PersonNode" },
        { "$ref": "#/$defs/OrganizationNode" },
        { "$ref": "#/$defs/FAQPageNode" },
        { "$ref": "#/$defs/BreadcrumbListNode" }
      ]
    },
    "WebSiteNode": {
      "if": { "properties": { "@type": { "const": "WebSite" } } },
      "then": {
        "properties": {
          "name":        { "type": "string" },
          "url":         { "type": "string", "format": "uri" },
          "publisher":   { "$ref": "#/$defs/EntityRef" },
          "potentialAction": {
            "type": "object",
            "description": "SearchAction（サイト内検索 schema.org）",
            "properties": {
              "@type": { "const": "SearchAction" },
              "target": { "type": "string" },
              "query-input": { "const": "required name=search_term_string" }
            }
          }
        }
      }
    },
    "ArticleNode": {
      "if": { "properties": { "@type": { "enum": ["Article", "BlogPosting", "NewsArticle"] } } },
      "then": {
        "properties": {
          "headline":        { "type": "string", "maxLength": 110 },
          "description":     { "type": "string", "maxLength": 320 },
          "url":             { "type": "string", "format": "uri" },
          "datePublished":   { "type": "string", "format": "date-time" },
          "dateModified":    { "type": "string", "format": "date-time" },
          "author":          { "$ref": "#/$defs/EntityRef" },
          "publisher":       { "$ref": "#/$defs/EntityRef" },
          "image":           { "$ref": "#/$defs/ImageObjectNode" },
          "mainEntityOfPage":{ "$ref": "#/$defs/EntityRef" }
        }
      }
    },
    "PersonNode": {
      "if": { "properties": { "@type": { "const": "Person" } } },
      "then": {
        "required": ["name"],
        "properties": {
          "name":          { "type": "string", "description": "表示名。WP display_name" },
          "alternateName": { "type": "string", "description": "別名・ペンネーム" },
          "honorificPrefix":{ "type": "string", "description": "敬称（Dr. / 先生 等）" },
          "jobTitle":      { "type": "string", "description": "役職・肩書き" },
          "description":   { "type": "string", "description": "著者プロフィール文（160 文字推奨）" },
          "url":           { "type": "string", "format": "uri", "description": "著者プロフィールページ URL" },
          "image":         { "$ref": "#/$defs/ImageObjectNode" },
          "sameAs": {
            "type": "array",
            "items": { "type": "string", "format": "uri" },
            "description": "Twitter / X / LinkedIn / Wikipedia / Wikidata 等の公式プロフィール URL。E-E-A-T の同一性信号"
          },
          "worksFor": { "$ref": "#/$defs/EntityRef" }
        }
      }
    },
    "OrganizationNode": {
      "if": { "properties": { "@type": { "const": "Organization" } } },
      "then": {
        "properties": {
          "name":          { "type": "string" },
          "alternateName": { "type": "string" },
          "url":           { "type": "string", "format": "uri" },
          "logo":          { "$ref": "#/$defs/ImageObjectNode" },
          "sameAs":        { "type": "array", "items": { "type": "string", "format": "uri" } }
        }
      }
    },
    "FAQPageNode": {
      "if": { "properties": { "@type": { "const": "FAQPage" } } },
      "then": {
        "required": ["mainEntity"],
        "properties": {
          "mainEntity": {
            "type": "array",
            "minItems": 1,
            "description": "FAQPage の Question/Answer リスト。FAQ ブロックの visible content と完全同期（§4 参照）",
            "items": {
              "type": "object",
              "required": ["@type", "name", "acceptedAnswer"],
              "properties": {
                "@type": { "const": "Question" },
                "name":  { "type": "string", "description": "質問文（FAQ ブロックの question フィールドと完全一致）" },
                "acceptedAnswer": {
                  "type": "object",
                  "required": ["@type", "text"],
                  "properties": {
                    "@type": { "const": "Answer" },
                    "text":  { "type": "string", "description": "回答テキスト（HTML タグ除去済み）" }
                  }
                }
              }
            }
          }
        }
      }
    },
    "BreadcrumbListNode": {
      "if": { "properties": { "@type": { "const": "BreadcrumbList" } } },
      "then": {
        "properties": {
          "itemListElement": {
            "type": "array",
            "items": {
              "type": "object",
              "required": ["@type", "position", "name", "item"],
              "properties": {
                "@type":    { "const": "ListItem" },
                "position": { "type": "integer", "minimum": 1 },
                "name":     { "type": "string" },
                "item":     { "type": "string", "format": "uri" }
              }
            }
          }
        }
      }
    },
    "ImageObjectNode": {
      "type": "object",
      "properties": {
        "@type":  { "const": "ImageObject" },
        "url":    { "type": "string", "format": "uri" },
        "width":  { "type": "integer" },
        "height": { "type": "integer" }
      }
    },
    "EntityRef": {
      "type": "object",
      "required": ["@id"],
      "properties": {
        "@id": { "type": "string", "format": "uri" }
      }
    }
  }
}
```

**生成**: `EntityGraphBuilder::build(WP_Post $post): array` が `@graph` 配列を返す。`wp_footer` hook で `wp_json_encode()` + `<script type="application/ld+json">` 出力。
**バリデーション**: `SeoValidationService` が `opis/json-schema`（PHP）で必須項目 / URL / 画像 を検証（GAP-RT-033 参照）。

---

#### (D) `seo-conflict-rules.json` — SEO プラグイン競合検知・出力優先順位

**用途**: `SeoConflictDetector` が参照する競合検知ルールセット。外部 SEO プラグイン（Yoast / Rank Math / SLIM SEO 等）との meta/JSON-LD 重複を検知・制御する。

```jsonc
{
  "version": "1.0.0",
  "description": "SEO プラグインとの競合検知・出力優先順位ルール",
  "plugins": [
    {
      "id": "yoast-seo",
      "slug": "wordpress-seo",
      "detection": "function_exists('wpseo_replace_vars')",
      "outputs": ["title", "description", "canonical", "robots", "og:*", "twitter:*", "json-ld"],
      "conflict_severity": "high",
      "action": "warn_and_suppress",
      "suppress_agent_neo_outputs": false,
      "suppress_plugin_outputs": false,
      "comment": "Yoast が有効な場合、AGENT NEO は自 title/description/canonical を出力しない（Yoast 側に委任）。JSON-LD は AGENT NEO が @graph で出力し、Yoast の出力と重複するノードは agent_neo_seo_conflict_override フィルタで制御"
    },
    {
      "id": "rank-math",
      "slug": "seo-by-rank-math",
      "detection": "class_exists('RankMath')",
      "outputs": ["title", "description", "canonical", "robots", "og:*", "twitter:*", "json-ld"],
      "conflict_severity": "high",
      "action": "warn_and_suppress"
    },
    {
      "id": "slim-seo",
      "slug": "slim-seo",
      "detection": "class_exists('SlimSEO\\Plugin')",
      "outputs": ["title", "description", "canonical", "robots", "og:*"],
      "conflict_severity": "medium",
      "action": "warn"
    },
    {
      "id": "all-in-one-seo",
      "slug": "all-in-one-seo-pack",
      "detection": "function_exists('aioseo')",
      "outputs": ["title", "description", "canonical", "robots", "og:*", "json-ld"],
      "conflict_severity": "high",
      "action": "warn_and_suppress"
    }
  ],
  "priority_rules": {
    "meta_title":       { "winner": "external_seo_plugin", "fallback": "agent_neo", "note": "外部 SEO プラグイン有効時はそちらを優先" },
    "meta_description": { "winner": "external_seo_plugin", "fallback": "agent_neo" },
    "canonical":        { "winner": "agent_neo",            "note": "AGENT NEO canonical が常に優先（AI 操作の安全性）" },
    "robots":           { "winner": "agent_neo",            "note": "AGENT NEO robots が常に優先" },
    "json_ld_graph":    { "winner": "agent_neo",            "note": "AGENT NEO は @graph で統合出力。外部プラグインの個別 script は suppress 対象" },
    "ogp":              { "winner": "external_seo_plugin",  "fallback": "agent_neo", "note": "外部 SEO プラグイン有効時はそちらを優先。ADR Wave3 申し送り先" }
  },
  "actions": {
    "warn_and_suppress": "管理画面に競合警告を表示し、競合する出力要素を重複排除する",
    "warn":              "管理画面に警告を表示するのみ。出力は両方を許可",
    "suppress_external": "外部プラグインの該当出力を wp_dequeue / remove_filter で抑制する"
  },
  "admin_notice_level": "error",
  "risk_ledger_key": "seo_plugin_conflict"
}
```

**OGP 出力責務境界の ADR 申し送り**: OGP の最終的な出力責務（外部 SEO プラグイン共存時の `og:*` 優先ルール詳細）は ADR Wave3（SEO 出力境界 ADR）で確定する。本 `seo-conflict-rules.json` の `priority_rules.ogp.note` を当該 ADR の起点論点とする。

---

## 2. GAP-RT-015 — Author / E-E-A-T Schema

**振り分け**: L3-patch / **重大度**: 高 / **関連 REQ**: REQ-F-011, REQ-NF-002, REQ-NF-015

### 2.1 背景

Google E-E-A-T（経験 / 専門性 / 権威性 / 信頼性）において著者情報の構造化が最重要施策。JIN:R は `include/json-ld.php` で Person schema + sameAs を出力するが、FSE / block.json / WP user_meta への対応が必要。

### 2.2 WP User Meta スキーマ設計

`PersonNode`（§1.1(C) で定義）への反映元となる WP user_meta キー群。`register_meta()` で `sanitize_callback` + `show_in_rest: true` 必須（REQ-NF-002 / CR-004）。

| meta_key | 型 | sanitize | 用途 | E-E-A-T 項目 |
|---|---|---|---|---|
| `agent_neo_schema_type` | string | `sanitize_text_field` | `Person` / `Organization`（デフォルト `Person`）| — |
| `agent_neo_author_job_title` | string | `sanitize_text_field` / maxLength 100 | 役職・専門肩書き（例: 「公認会計士」「SEO コンサルタント」）| 専門性 |
| `agent_neo_author_honorific` | string | `sanitize_text_field` / maxLength 20 | 敬称（Dr. / 先生 等）| 専門性 |
| `agent_neo_author_alternate_name` | string | `sanitize_text_field` / maxLength 60 | 別名・ペンネーム | 同一性 |
| `agent_neo_author_description` | string | `sanitize_textarea_field` / maxLength 500 | プロフィール文（JSON-LD description に反映）| 経験・信頼性 |
| `agent_neo_author_same_as` | array（JSON） | `wp_json_encode` + URL 検証各要素 | sameAs URL 一覧（X/Twitter, LinkedIn, Wikipedia, Wikidata, Amazon 著者ページ等）| 権威性・同一性 |
| `agent_neo_author_profile_url` | string | `esc_url_raw` | 著者プロフィールページ URL（外部サイト可）| 信頼性 |
| `agent_neo_author_image_id` | integer | `absint` | WP media ID（著者アバター）| — |

### 2.3 JSON-LD 出力フロー

```
WP 著者情報取得
  ↓ get_user_meta($user_id, 'agent_neo_author_*')
  ↓
EntityGraphBuilder::buildPersonNode(WP_User $author)
  → PersonNode {
      @type: "Person",
      @id: "{site_url}/author/{slug}/#person",
      name: display_name,
      alternateName: agent_neo_author_alternate_name,
      honorificPrefix: agent_neo_author_honorific,
      jobTitle: agent_neo_author_job_title,
      description: agent_neo_author_description,
      url: agent_neo_author_profile_url || get_author_posts_url(),
      image: ImageObject{url: avatar_url},
      sameAs: [...agent_neo_author_same_as],
      worksFor: { @id: "{site_url}/#organization" }
    }
  ↓
Article/BlogPosting Node の author: { @id: "...#person" } で参照
  ↓
wp_footer フックで @graph 統合出力（wp_json_encode / <script type="application/ld+json">）
```

### 2.4 管理 UI 仕様

**場所**: WP 標準ユーザープロフィール編集画面に「E-E-A-T 著者情報」セクションを追加（`show_user_profile` / `edit_user_profile` アクション）。

| フィールド | UI 種別 | バリデーション |
|---|---|---|
| Schema Type（Person / Organization） | radio | 必須 |
| 専門肩書き | text input | maxLength 100 |
| 敬称 | text input | maxLength 20 |
| 別名 | text input | maxLength 60 |
| プロフィール文 | textarea | maxLength 500 |
| sameAs URL（複数） | repeatable URL input | `filter_var(FILTER_VALIDATE_URL)` 各要素 |
| プロフィール URL | URL input | `filter_var(FILTER_VALIDATE_URL)` |
| 著者アバター | media picker（WP standard） | media_id 型チェック |

**AI 自動入力の扱い**: E-E-A-T プロフィール情報は著者本人の証言が信頼性の源泉であるため、**AI による自動生成・自動挿入は原則禁止**。Automation SEO から `sameAs` 候補リストを提案することは可能だが、確定は人間（管理者 / 著者本人）が承認してから `POST /agent-neo/v1/seo/profile/author/{user_id}` で適用する（REQ-NF-025 境界）。

### 2.5 受入条件（TC 候補）

| TC-ID | 内容 |
|---|---|
| TC-A3-015-01 | `agent_neo_author_same_as` に不正 URL を保存しようとした場合、sanitize_callback が拒否し保存されない |
| TC-A3-015-02 | WP 著者プロフィール画面で E-E-A-T フィールドを入力して保存 → `GET /agent-neo/v1/seo/profile/author/{user_id}` で値が返る |
| TC-A3-015-03 | `agent_neo_schema_type = Person` の著者が付いた記事で、JSON-LD @graph に Person ノード + sameAs が出力される |
| TC-A3-015-04 | 著者 sameAs に X / LinkedIn / Wikidata URL が含まれる場合、Google Rich Results Test 等価バリデーション（内製）が通る |
| TC-A3-015-05 | AI による `agent_neo_author_same_as` 直接書き換え試行 → 403 Forbidden（管理者承認が必要） |

**関連 REQ**: REQ-F-011, REQ-NF-002（capability / sanitize 必須）, REQ-NF-015（AI 運用性）

---

## 3. GAP-RT-017 — 任意タグ入力 UI（head / body 挿入）

**振り分け**: L3-patch / **重大度**: 高 / **関連 REQ**: REQ-F-011, REQ-NF-002, REQ-NF-009

> **最重要 / Day-1 操作ユースケース**: GA4 / GTM / AdSense / Search Console 認証タグ挿入はユーザーがテーマ導入直後に必ず実行する操作。UX と安全性の両立が設計の核心。

### 3.1 設計方針

JIN:R の `include/head/tags.php` は任意 HTML を raw echo するが、AGENT NEO では **adapter + capability + allowlist + consent guard + 監査ログ**の 5 層を必須とする（解析レポート §14-12/13 の改良指摘に基づく）。

**AI 自動操作の扱い**: 任意タグ挿入は **手動操作専用**（管理者のみ）。Automation SEO や AI エージェントからの自動書き込みは**禁止**。

理由:
- 任意タグには外部 JS を含む可能性があり、AI が自動挿入すると XSS / 外部スクリプト注入リスクが不可逆的に発生する
- GA4 / GTM / AdSense タグは測定の正確性のため人間が意図的に設定するべき
- REQ-NF-002（nonce / capability 必須）+ REQ-NF-009（外部送信同意）が根拠

### 3.2 挿入位置と slot 定義

| slot_name | 挿入位置 | WP hook | ユースケース |
|---|---|---|---|
| `head_start` | `<head>` 直後（優先） | `wp_head` priority 1 | Search Console meta verification |
| `head_end` | `</head>` 直前 | `wp_head` priority 99 | GA4 タグ / AdSense |
| `body_start` | `<body>` 直後 | `wp_body_open` priority 1 | GTM noscript / dataLayer 初期化 |
| `body_end` | `</body>` 直前 | `wp_footer` priority 99 | GTM / 追加スクリプト |

### 3.3 セキュリティ設計（5 層）

#### 層 1: Capability 制限

- 書き込み権限: `manage_options`（= 管理者 / WP_ADMIN のみ）
- 読み取り権限: `manage_options`（設定値も管理者専用）
- AI エージェント（Automation SEO / agent-neo API）からの書き込み: **禁止**（endpoint で拒否）

#### 層 2: タグ種別 Allowlist

`third-party-tags.schema.json`（GAP-RT-022 との連携。本 addendum では SEO 観点の制約を定義）に基づく。

```jsonc
{
  "allowed_tag_categories": [
    {
      "id": "analytics",
      "label": "アクセス解析",
      "examples": ["Google Analytics 4", "Bing Webmaster"],
      "allowed_elements": ["script"],
      "allowed_attrs": ["src", "async", "defer", "type"],
      "script_src_pattern": "^https://(www\\.googletagmanager\\.com|www\\.google-analytics\\.com|analytics\\.google\\.com|bat\\.bing\\.com)/",
      "inline_script_allowed": true,
      "inline_script_pattern": "window\\.dataLayer|gtag\\(|_gaq\\.push"
    },
    {
      "id": "verification",
      "label": "サイト認証",
      "examples": ["Google Search Console", "Bing Webmaster", "Pinterest", "Yahoo! Japan"],
      "allowed_elements": ["meta"],
      "allowed_attrs": ["name", "content"],
      "meta_name_pattern": "^(google-site-verification|msvalidate\\.01|p:domain_verify|y_key)$"
    },
    {
      "id": "advertising",
      "label": "広告タグ",
      "examples": ["Google AdSense", "Google Ad Manager"],
      "allowed_elements": ["script", "ins"],
      "consent_required": true,
      "comment": "同意バナー連携必須（consent_guard = true）"
    },
    {
      "id": "custom",
      "label": "カスタムタグ（上級）",
      "allowed_elements": ["script", "meta", "link", "noscript"],
      "capability_override": "super_admin",
      "requires_explicit_approval": true,
      "comment": "WordPress Multisite super_admin または明示的な承認フラグが必要"
    }
  ],
  "forbidden_patterns": [
    "javascript:",
    "data:text/html",
    "eval\\(",
    "document\\.write\\(",
    "innerHTML\\s*=",
    "on[a-z]+=",
    "<iframe",
    "<object",
    "<embed"
  ]
}
```

#### 層 3: Sanitize / Escape

- `wp_kses()` 拡張 allowlist で `<script>` / `<meta>` / `<link>` / `<noscript>` の許可要素・属性を制限
- `on*` 属性 / `javascript:` URL / `data:text/html` / `eval(` を正規表現で完全拒否
- 保存前に `AgentNeoTagSanitizer::sanitize(string $raw, string $category): string` を通す

#### 層 4: Consent Guard 連携

`advertising` カテゴリのタグは同意バナー（Cookie Consent）が有効化されるまで **出力禁止**。

```php
// inc/seo/CustomTagRenderer.php
if ($tag['category'] === 'advertising' && !AgentNeoConsentGuard::has_consent('advertising')) {
    // 出力しない。consent 付与後に wp_body_open / wp_footer で遅延出力
    return;
}
```

GAP-RT-022（`third-party-tags.schema.json` / Consent Mode v2）および GAP-RT-038（Cookie Consent Gate TC）と連携。

#### 層 5: 監査ログ

```php
// 保存・削除・変更時に必ずログ記録
AgentNeoAuditLog::record([
    'action'     => 'custom_tag_update',
    'slot'       => $slot_name,       // head_start / head_end / body_start / body_end
    'category'   => $tag['category'],
    'actor_id'   => get_current_user_id(),
    'actor_role' => implode(',', (array)wp_get_current_user()->roles),
    'old_value'  => $old_tag,         // 旧タグ（差分確認用）
    'new_value'  => $new_tag,         // 新タグ（sanitize 後）
    'timestamp'  => current_time('mysql', true),
    'source'     => 'human_ui',       // AI 操作は forbidden のため 'human_ui' 固定
]);
```

### 3.4 管理 UI 仕様

**場所**: WP 管理画面「AGENT NEO」>「サイト設定」>「タグ管理」タブ

| 項目 | 仕様 |
|---|---|
| タブ構成 | head_start / head_end / body_start / body_end の 4 タブ |
| タグ種別選択 | プリセット選択（GA4 / GTM / AdSense / Search Console / カスタム） |
| textarea | モノスペースフォント / 構文ハイライト（コードエディタ風） |
| forbidden pattern 検出 | リアルタイムバリデーション（JS / PHP 二重）。違反時は保存ブロック + エラーメッセージ表示 |
| 広告タグ同意状態 | 同意バナー未設定時は「⚠ 同意バナーが未設定のため広告タグは出力されません」を警告表示 |
| 変更履歴 | 直近 20 件の監査ログを UI に表示（actor / timestamp / 変更内容 diff） |

### 3.5 REST API 設計

```
GET  /agent-neo/v1/settings/custom-tags          # 現在のタグ取得（manage_options 必須）
POST /agent-neo/v1/settings/custom-tags          # タグ保存（manage_options 必須 / AI 禁止）
DELETE /agent-neo/v1/settings/custom-tags/{slot} # slot のタグ削除（manage_options 必須）
```

**AI からの POST は 403 を返す**（`X-Agent-Neo-Source: automation-seo` ヘッダ検出 or agent-api トークン認証時に拒否）。

### 3.6 受入条件（TC 候補）

| TC-ID | 内容 |
|---|---|
| TC-A3-017-01 | 管理者が `<meta name="google-site-verification" content="xxx">` を head_start に保存 → フロントエンドの `<head>` 直後に出力される |
| TC-A3-017-02 | `<script>eval('xss')</script>` を保存しようとした場合、sanitize が拒否してエラーを返す |
| TC-A3-017-03 | `on*` 属性を含むタグを保存しようとした場合、forbidden pattern 検出でブロックされる |
| TC-A3-017-04 | `advertising` カテゴリのタグを設定 → 同意バナー未設定状態ではフロントに出力されない |
| TC-A3-017-05 | 編集者ロール（non-admin）が `POST /agent-neo/v1/settings/custom-tags` を試みた場合、403 を返す |
| TC-A3-017-06 | Automation SEO API トークンで `POST /agent-neo/v1/settings/custom-tags` を試みた場合、403 を返す |
| TC-A3-017-07 | タグ更新後、監査ログに `actor_id` / `slot` / `old_value` / `new_value` / `timestamp` が記録される |
| TC-A3-017-08 | GA4 タグ（`gtag.js` スクリプト）を head_end に設定 → LCP/INP に影響しないよう `async` 属性が付与される（allowlist の `async` 強制）|

---

## 4. GAP-RT-018 — FAQPage JSON-LD（FAQ ブロック render 時自動生成）

**振り分け**: L3-patch / **重大度**: 中 / **関連 REQ**: REQ-F-011, REQ-NF-015

### 4.1 設計方針

**Visible Content Sync 原則**: FAQPage JSON-LD は FAQ ブロックの表示内容（question / answer テキスト）から render 時に同期生成する。表示と schema が非同期になる実装は禁止。

SWELL の `lib/gutenberg/render_hook/faq.php` が同原則で実装されており（解析レポート §13-3.1）、AGENT NEO はこれを FSE / block.json で再設計する。

### 4.2 FAQ ブロック構造（正本: L3-A1 §7 を参照）

> **ブロックカタログ正本は L3-A1-block-catalog-gaps.md §7 に確定済み。本節はその SEO 実装観点の補足説明であり、block.json 属性定義の正本は L3-A1 §7.4 を参照すること。**

`agent-neo/faq` は **inner-block コンテナ** 構造を採用する。FAQ の各 Q&A は `agent-neo/faq-item` 子ブロックとして管理され、`agent-neo/faq` ブロック自体の attributes に `items[]` 配列は持たない。

**親ブロック `agent-neo/faq` の主要 attributes（L3-A1 §7.4 より）**:

| attribute | 型 | 説明 |
|---|---|---|
| `outputJsonLd` | boolean（default: `true`）| FAQPage JSON-LD 出力を有効にするか。**出力フラグ名は `outputJsonLd` に統一する**（旧称 `schemaEnabled` は採用しない） |
| `block_id` | string | 安定 block_id（UUID v4） |
| `section_id` | string | 親 H2 セクション ID |
| `innerBlocks` | — | `agent-neo/faq-item` のみ許可（`allowedBlocks` で制限） |

**子ブロック `agent-neo/faq-item` の attributes（L3-A1 §7.4 より）**:

| attribute | 型 | 説明 |
|---|---|---|
| `question` | string | 質問文。JSON-LD の `Question.name` に 1:1 対応 |
| `answer` | string | 回答テキスト（HTML 許可 / wp_kses_post）。JSON-LD 出力時は strip_tags でプレーンテキスト化 |

### 4.3 FAQPage JSON-LD 生成フロー

JSON-LD コレクタは **`agent-neo/faq-item` inner-block から question / answer を抽出**し、親ブロックの `outputJsonLd` フラグが `true` のときのみ FAQPage を生成する。`items[]` 属性は存在しないため、ブロックパース経由で innerBlocks を走査する。

```
1. EntityGraphBuilder::collectFAQNodes(int $post_id): array
   ↓ parse_blocks( get_post_field('post_content', $post_id) ) で全ブロックを取得
   ↓ blockName === 'agent-neo/faq' を収集
   ↓ 各 faq ブロックの attrs['outputJsonLd'] === true のもののみ対象
      （false または未設定 = デフォルト true なので未設定も有効）

2. 各 faq ブロックの innerBlocks を走査し FAQPageNode を生成
   ↓ innerBlock.blockName === 'agent-neo/faq-item' を列挙
   ↓ faq-item ごとに:
      - question: attrs['question'] を strip_tags でサニタイズ
      - answer.text: attrs['answer'] を strip_tags / HTML タグ除去してプレーンテキスト化
   ★ faq-item の visible content（question / answer）と JSON-LD が 1:1 同期

3. 複数 FAQ ブロック存在時のマージルール（inner-block 前提）:
   - 同一ページに複数の agent-neo/faq ブロックが存在する場合、
     outputJsonLd=true のブロックの innerBlocks をすべて走査して mainEntity を統合し
     1 つの FAQPage ノードを生成する（Google 推奨の単一 FAQPage 形式）
   - 重複する question テキストは後出しを優先（マージ警告をデバッグログに記録）

4. @graph に FAQPageNode を追加
   ↓ wp_footer priority 1 で EntityGraphBuilder::build() から @graph 統合出力
```

### 4.4 受入条件（TC 候補）

| TC-ID | 内容 |
|---|---|
| TC-A3-018-01 | `agent-neo/faq-item` に question/answer を入力して記事を公開 → ページの JSON-LD に FAQPage が含まれ、`Question.name` が question と一致する |
| TC-A3-018-02 | `agent-neo/faq-item` の question テキストを変更して再保存 → JSON-LD の `Question.name` が変更後テキストと一致する（visible content sync） |
| TC-A3-018-03 | 親ブロック `agent-neo/faq` の `outputJsonLd=false` → JSON-LD 非出力（HTML アコーディオンのみ） |
| TC-A3-018-04 | 同一ページに `agent-neo/faq` ブロックが 2 つある場合（どちらも `outputJsonLd=true`）、両方の innerBlocks が統合された単一 FAQPage ノードが出力される |
| TC-A3-018-05 | answer テキストの HTML タグ（`<strong>` 等）が JSON-LD 出力時に除去され、プレーンテキストになっている |
| TC-A3-018-06 | `GET /agent-neo/v1/public/pages/{id}/snapshot` レスポンスに FAQPage JSON-LD が含まれる |

---

## 5. GAP-RT-020 — SNS Hashtag Post Meta

**振り分け**: L3-patch / **重大度**: 中 / **関連 REQ**: REQ-F-011, REQ-F-018, REQ-NF-002

### 5.1 背景

JIN:R の `_jinr_hastag_display` post meta（`include/custom-functions.php` で `show_in_rest: true` 登録）を AGENT NEO 標準に移植。SNS シェア時のハッシュタグ付与と、X（Twitter）Card / シェア URL への反映が目的。

### 5.2 Post Meta 仕様

| meta_key | 型 | sanitize | REST | 用途 |
|---|---|---|---|---|
| `_agent_neo_sns_hashtags` | string（カンマ区切り） | `sanitize_text_field` → ハッシュタグ正規化 | `show_in_rest: true` | SNS シェア URL の `hashtags` パラメータ / `seo-meta.schema.json` の sns.hashtags に反映 |

**ハッシュタグ正規化ルール**:
- `#` プレフィックスは任意（入力時に付与しても除去しても正規化）
- 保存は `#` なしのカンマ区切り文字列（例: `seo,wordpress,agent_neo`）
- 全角スペース / 空白は除去
- 1 タグあたり最大 50 文字 / 1 記事あたり最大 10 タグ

### 5.3 シェア URL 付与仕様

#### X（Twitter）シェアボタン

```
https://twitter.com/intent/tweet
  ?url={canonical_url}
  &text={seo_title}
  &hashtags={hashtags_comma_separated}  // _agent_neo_sns_hashtags の値
  &via={seo_profile.sns.twitterHandle の @ 除去}
```

#### OGP / Twitter Card との関係

`_agent_neo_sns_hashtags` は OGP 出力（`og:*` / `twitter:*`）への直接反映は行わない（X の hashtags パラメータはシェアボタン URL 専用）。`seo-meta.schema.json` に `sns.hashtags` フィールドを持たせ、API レスポンスで返す。

### 5.4 管理 UI 仕様

**場所**: WP 記事・固定ページ編集画面の「AGENT NEO SEO」メタボックス内「SNS 設定」セクション。

| フィールド | UI | バリデーション |
|---|---|---|
| ハッシュタグ | タグ入力（chips UI）| 1 タグ 50 文字以内 / 10 個まで / 英数字・アンダースコア・日本語 |

### 5.5 受入条件（TC 候補）

| TC-ID | 内容 |
|---|---|
| TC-A3-020-01 | 記事編集画面でハッシュタグ「seo, wordpress」を入力保存 → `_agent_neo_sns_hashtags` に `seo,wordpress` が保存される |
| TC-A3-020-02 | 全角・前後空白を含むハッシュタグ入力 → sanitize 後に除去された正規形で保存される |
| TC-A3-020-03 | `GET /agent-neo/v1/seo/{post_id}` レスポンスに `sns.hashtags` フィールドが含まれる |
| TC-A3-020-04 | X シェアボタンの URL に `hashtags=seo,wordpress` パラメータが含まれる |
| TC-A3-020-05 | 10 個超のハッシュタグを保存しようとした場合、バリデーションエラーが返る |

---

## 6. 横断設計事項

### 6.1 SEO Core コンポーネントマップ（L3 確定版）

```
inc/seo/
  ├── SeoProfileRepository.php     # seo-profile.schema.json の保存・取得
  ├── SeoMetaRepository.php        # seo-meta.schema.json の保存・取得（post_meta / term_meta）
  ├── AuthorEeatRepository.php     # E-E-A-T user_meta の保存・取得（§2 担当）
  ├── EntityGraphBuilder.php       # entity-graph.schema.json に準拠した @graph 構築
  ├── SeoConflictDetector.php      # seo-conflict-rules.json を参照した競合検知
  ├── SeoValidationService.php     # schema.org 必須項目 + URL + 画像 + robots バリデーション
  ├── SeoActionController.php      # REST / MCP / WP CLI / UI からの受付（統一エントリポイント）
  ├── AutomationSeoAdapter.php     # Automation SEO からの meta 提案・Entity 提案を受信・反映
  ├── CustomTagRenderer.php        # 任意タグ出力（§3 担当 / 5 層セキュリティ）
  ├── AgentNeoTagSanitizer.php     # タグ sanitize / allowlist 実装
  └── AgentNeoAuditLog.php         # 任意タグ操作の監査ログ
```

### 6.2 データ保存マップ

| データ | 保存先 | キー |
|---|---|---|
| サイト SEO プロフィール | `wp_options` | `agent_neo_seo_profile`（JSON） |
| 投稿 SEO メタ | `wp_postmeta` | `_agent_neo_seo_meta`（JSON） |
| term SEO メタ | `wp_termmeta` | `agent_neo_seo_meta`（JSON） |
| Author E-E-A-T | `wp_usermeta` | `agent_neo_author_*`（各フィールド個別） |
| 任意タグ | `wp_options` | `agent_neo_custom_tags`（JSON / slot 別） |
| SNS ハッシュタグ | `wp_postmeta` | `_agent_neo_sns_hashtags`（string） |
| 任意タグ監査ログ | `wp_options` or カスタムテーブル | `agent_neo_tag_audit_log`（FIFO 100件） |

### 6.3 SEO 出力順序

`AgentNeoSeoHead` が `wp_head` hook で以下の順序で出力（JIN:R の head 出力順序を FSE に移植 / 解析レポート §14-3）。

```
priority 1:  head_start 任意タグ（Search Console verification 等）
priority 5:  SeoMetaRenderer::title()
priority 6:  SeoMetaRenderer::description()
priority 7:  SeoMetaRenderer::robots()
priority 8:  SeoMetaRenderer::canonical()
priority 9:  SeoMetaRenderer::ogp()
priority 10: SeoConflictDetector::suppress_external_duplicates()  # 競合プラグイン出力抑制
priority 99: head_end 任意タグ（GA4 / AdSense 等）
```

`wp_body_open` priority 1: body_start 任意タグ（GTM noscript 等）
`wp_footer` priority 1: EntityGraphBuilder @graph JSON-LD
`wp_footer` priority 99: body_end 任意タグ

### 6.4 SeoConflictDetector 動作フロー

```
WordPress 初期化時（after_setup_theme / init）
  ↓ SeoConflictDetector::scan()
  ↓ seo-conflict-rules.json をロード
  ↓ 各プラグインの detection 式を評価（function_exists / class_exists）
  ↓ 競合プラグイン検出 → admin_notices に競合警告を登録
  ↓ priority_rules に基づいて out_suppression フラグをセット
    └─ canonical / robots は AGENT NEO 優先 → 外部プラグインの該当 filter を remove_filter
    └─ title / description / ogp は外部 SEO プラグイン優先 → AGENT NEO 側出力をスキップ
    └─ json_ld は AGENT NEO @graph 優先 → 外部プラグインの JSON-LD script を wp_dequeue
  ↓ risk_ledger に conflict イベントを記録（risk_ledger_key: "seo_plugin_conflict"）
```

**ADR 申し送り（OGP 出力責務境界）**: `seo-conflict-rules.json §priority_rules.ogp` に定義した「外部 SEO プラグイン有効時は OGP を外部プラグインに委任」の詳細ルール（共存時の og:type / og:image fallback 処理 / Yoast との二重出力排除プロセス）は、ADR Wave3（SEO 出力境界 ADR）で確定すること。本 addendum はその申し送り論点を seo-conflict-rules.json に記載済み。

---

## 7. L4 Carry エントリ

本 addendum で L4 着手前に残す carry 項目を以下に整理する。

```yaml
carry:
  - id: "CARRY-A3-001"
    gap: "GAP-RT-019"
    level: P2
    title: "seo-conflict-rules.json OGP 優先ルール詳細"
    description: |
      seo-conflict-rules.json の priority_rules.ogp について、
      外部 SEO プラグイン共存時の og:type / og:image fallback 処理 / 二重出力排除の
      詳細実装ルールを ADR Wave3（SEO 出力境界 ADR）で確定してから L4 実装に入ること。
    blocking: false
    adr_target: "ADR Wave3 / SEO 出力境界"
    next_action: "ADR 起票者が SeoConflictDetector 実装者に ogp 節の decision を共有する"

  - id: "CARRY-A3-002"
    gap: "GAP-RT-017"
    level: P2
    title: "third-party-tags.schema.json との統合仕様確定"
    description: |
      任意タグ（§3）の allowlist は GAP-RT-022（third-party-tags.schema.json）と
      consent mode v2 設計が完成してから統合する。
      L4 実装時は本 addendum の allowlist を暫定版として使い、
      GAP-RT-022 addendum が完成した段階で差し替える。
    blocking: false
    next_action: "GAP-RT-022 担当者（パフォーマンス addendum）に SEO タグ allowlist を共有"

  - id: "CARRY-A3-003"
    gap: "GAP-RT-015"
    level: P2
    title: "Author E-E-A-T sameAs 候補提案 API（Automation SEO 側）契約"
    description: |
      著者 sameAs の Automation SEO からの「候補リスト提案」フローの
      API 契約（Automation SEO 側のエンドポイント仕様）が未確定。
      L4 では人間 UI による手動入力のみ実装し、
      Automation SEO 側提案フローは Phase 2 carry。
    blocking: false
    next_action: "Automation SEO 側 API 契約が確定後、AutomationSeoAdapter に追加実装"

  - id: "CARRY-A3-004"
    gap: "GAP-RT-017"
    level: P1
    title: "任意タグ監査ログのストレージ設計"
    description: |
      監査ログを wp_options（FIFO 100 件）で実装するか
      カスタムテーブル（`agent_neo_audit_log`）で実装するかが未確定。
      100 件超の要件がある場合はカスタムテーブル化が必要（P1 = DB 設計と連動）。
      L4 着手前に DB 設計 addendum（GAP-RT-011 担当）と調整すること。
    blocking: true
    next_action: "DB 設計 addendum 担当者に監査ログテーブル要件を共有し設計合意"

  - id: "CARRY-A3-005"
    gap: "GAP-RT-018"
    level: P3
    title: "FAQPage 重複 question マージ警告の UI 表示"
    description: |
      同一ページに複数 agent-neo/faq ブロック（outputJsonLd=true）がある場合、
      inner-block 走査で重複 question テキストを検出した際のマージ警告を
      管理画面 UI で表示する機能。
      L4 Phase 1 ではデバッグログ記録のみ、UI 表示は Phase 2 carry。
    blocking: false
    next_action: "管理 UI 担当者に警告 notice 仕様を共有"
```

---

## 8. ADR 申し送り論点（Wave3 SEO 出力境界 ADR）

本 addendum から ADR Wave3（SEO 出力境界 ADR）への申し送り論点を 1〜2 行で明示する。

**申し送り論点**: 外部 SEO プラグイン（Yoast / Rank Math）が有効な場合の **OGP（`og:*`）出力責務をどちらが持つか**、および共存時の `og:type` / `og:image` fallback 処理 / `og:description` 二重出力排除の優先ルール詳細を ADR で確定すること。現行 `seo-conflict-rules.json §priority_rules.ogp` では「外部 SEO プラグイン優先 / AGENT NEO は fallback」を暫定方針としているが、canonical は AGENT NEO 優先（AGENT NEO 側常時出力）と非対称な扱いになっており、その根拠と実装上の整合性について ADR レベルの意思決定が必要。

---

*Addendum 作成: 2026-06-18 / 担当 GAP-RT: 015 / 017 / 018 / 019 / 020*
*次アクション: TL（Codex 5.4）レビュー → CARRY-A3-004（監査ログ DB 設計）の P1 解消 → L4 着手*
