# semantic-frontend — D-REQ-F（機能要件）

## 概要

`semantic-frontend` は AGENT NEO のフロントエンド HTML が AI エージェント・AI クローラ・検索エンジン・支援技術の全てに対して意味的に正確で安定した構造を持つことを定義する feature である。人間が視覚的に閲覧するだけでなく、AIが JS 実行なしに読めること（AI Snapshot）、安定した DOM アンカーで操作できること（Stable DOM Anchor）、構造化データが出力されること（JSON-LD）、アクセシビリティ基準を満たすことを要件化する。

section_id / cta_id / offer_id / service_id / variant_id / article_id の命名規約を定め、計測・A/B テスト・AI 操作・移行の全操作面で ID が一貫して機能することを保証する。

## ID 体系

| 接頭辞 | 対象 |
|---|---|
| `SF-` | semantic-frontend 機能要件 |

## ID 命名規約

| ID 種別 | 形式 | 例 | 用途 |
|---|---|---|---|
| `section_id` | `sec_{role}_{seq}` | `sec_hero_001`, `sec_problem_01` | LP/HP/記事のセクション単位識別 |
| `cta_id` | `cta_{intent}_{seq}` | `cta_primary_001`, `cta_final_demo` | CTA ボタン・フォーム識別 |
| `offer_id` | `offer_{slug}` | `offer_saas_basic`, `offer_ebook_dl` | LP オファー・資料 DL 識別 |
| `service_id` | `svc_{slug}` | `svc_analytics`, `svc_consulting` | 企業複数サービスの紐付け |
| `variant_id` | `var_{experiment}_{name}` | `var_hero_a`, `var_hero_b` | A/B テスト variant 識別 |
| `article_id` | `art_{post_id}` | `art_00123` | 記事・投稿の安定識別子 |

## 詳細要件

| ID | 要件名 | 説明 | 優先度 | 上位 L1 ID |
|---|---|---|---|---|
| SF-001 | data-agent 属性 | 全 LP/HP/BLP の主要セクション・CTA に `data-agent-section-id`, `data-agent-role`, `data-cta-id`, `data-offer-id`, `data-variant-id` を付与する | P0 | REQ-F-006, REQ-NF-015 |
| SF-002 | 安定 DOM anchor | `data-agent-section-id` は WP/テーマ更新後も変化しない安定 ID とし、CSS クラス依存の動的 selector を使用しない | P0 | REQ-NF-015 |
| SF-003 | AI Snapshot エンドポイント | `GET /wp-json/agent-neo/v1/public/pages/{id}/snapshot` で JS なしに全セクション・CTA・JSON-LD 状態を取得できる | P0 | REQ-NF-015 |
| SF-004 | Crawler Access Matrix | `crawler-access-matrix.json` で Googlebot/OAI-SearchBot/GPTBot/ClaudeBot 別に search/ai-input/ai-train の許可方針を定義し、robots.txt に反映する | P0 | REQ-NF-015 |
| SF-005 | Article JSON-LD | 記事ページに schema.org/Article（author, datePublished, dateModified, headline, image, publisher）を出力する | P0 | REQ-F-011, REQ-NF-017 |
| SF-006 | FAQ JSON-LD | FAQ セクション（`data-agent-role="faq"`）に schema.org/FAQPage を自動生成する | P0 | REQ-F-011, REQ-NF-017 |
| SF-007 | Review JSON-LD | レビューブロック（Pros Cons / Review Detail）に schema.org/Review（reviewRating, author, itemReviewed）を出力する | P0 | REQ-F-004, REQ-NF-017 |
| SF-008 | Organization JSON-LD | HP に schema.org/Organization（name, url, logo, sameAs, contactPoint）を出力する | P0 | REQ-F-011, REQ-NF-017 |
| SF-009 | Breadcrumb JSON-LD | 全ページに schema.org/BreadcrumbList を出力し、canonical と整合させる | P0 | REQ-F-011, REQ-NF-017 |
| SF-010 | WCAG 2.2 AA | color contrast ratio 4.5:1 以上、全インタラクション要素に keyboard 操作・focus visible・aria-label を実装する | P1 | REQ-NF-005, REQ-NF-016 |
| SF-011 | JS 非依存コンテンツ | タブ・アコーディオン・スライダーの主要コンテンツは HTML に展開した状態を持ち、JS が無効でも閲覧・クロールできる | P0 | REQ-NF-015 |
| SF-012 | crawl-map | `GET /wp-json/agent-neo/v1/public/crawl-map` で全公開ページの canonical・robots・更新日・section 数・content_type を一覧できる | P1 | REQ-NF-015 |
| SF-013 | content_hash | ページスナップショットに `content_hash` と `schema_hash` を含め、AI エージェントが差分検出に使えるようにする | P1 | REQ-NF-015 |
| SF-014 | BLP 計測 ID | 記事 LP（BLP）の `cta_id`, `service_id`, `offer_id` を article_id と紐付け、記事下・インライン CTA の計測が Automation SEO に届く構造を保証する | P0 | REQ-F-005, REQ-F-006 |
| SF-015 | service-aware IA | `data-service-id` を HP の Gateway Grid・BLP の関連サービス導線に付与し、複数サービスのコンテンツ紐付けを HTML レベルで表現する | P0 | REQ-F-012 |

## 補足・設計指針

**data 属性とクラスの分離**: CSS のスタイリング用クラス（`c-hero`, `p-section`）とAI 操作用 data 属性（`data-agent-section-id`）は完全に分離する。AI クローラが CSS クラスを意味契約として解釈することを避ける。

**JSON-LD の重複抑制**: 外部 SEO プラグイン（Yoast/Rank Math）との JSON-LD 重複を検出し、AGENT NEO の SEO Core が出力管理を行う場合は外部プラグインの JSON-LD を無効化するオプションを提供する。

**AI Snapshot の公開情報境界**: スナップショットには非公開投稿・会員限定情報・管理者トークン・nonce を含めない。title / description / canonical / robots / section 見出し / CTA label / offer_id / JSON-LD のみを返す。

## section_id と cta_id の計測 ID 規約まとめ

| ページ種別 | 必須 ID | 任意 ID |
|---|---|---|
| 法人 LP | section_id, cta_id, offer_id | proof_id, pricing_id, faq_id |
| 法人 HP | section_id, gateway_id | service_id |
| 個人収益ページ | section_id, cta_id | offer_id |
| BLP（記事 LP）| article_id, cta_id, service_id | offer_id |
| 通常記事 | article_id | section_id（インライン CTA 使用時） |

## Crawler Access Matrix 初期値

| Bot | search | ai-input | ai-train | 備考 |
|---|---|---|---|---|
| Googlebot | allow | allow | allow | SEO の基本 |
| OAI-SearchBot | allow | allow | - | AI 検索露出 |
| GPTBot | - | - | deny | デフォルト deny |
| ClaudeBot | - | - | deny | デフォルト deny |
| Google-Extended | - | - | deny | デフォルト deny |

管理画面から preset（`ai_search_open_train_closed` 等）を選択して変更できる。

## 参照

- L1: REQ-F-005, REQ-F-006, REQ-F-011, REQ-F-012, REQ-NF-005, REQ-NF-015, REQ-NF-017
- 解析レポート: 22-AIエージェント運用性（§AI運用しやすいDOM規約, §AI Snapshot設計, §AIクローラ/検索AIへの考慮）
- 解析レポート: 16-LP-HP設計方針（§LPの標準構成 §計測設計）
