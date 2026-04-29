# Agent SEO Contracts

AGENT NEOでは、AIエージェントがWordPressテーマとAutomation SEOを安全に操作できるように、SEO要件をJSONで持つ。

## Core Schemas

- `seo-skill-map.profile.json`: サイト種別、対象市場、商材、収益モデル、SEOカテゴリの有効/無効を定義。
- `keyword-cluster.schema.json`: キーワード、検索意図、親子クラスタ、優先度、対象URL、想定CVを定義。
- `search-intent.schema.json`: Informational、Commercial、Transactional、Navigational、Local、Comparison、Troubleshootingを定義。
- `content-blueprint.schema.json`: H1、見出し、FAQ、CTA、内部リンク、構造化データ、E-E-A-T証跡を定義。
- `indexability-policy.schema.json`: canonical、robots、noindex、sitemap、pagination、archive、thin pageの扱いを定義。
- `internal-link-graph.schema.json`: 起点URL、リンク先、アンカーテキスト、関係、重要度、孤立ページ判定を定義。
- `structured-data-graph.schema.json`: `@id`、`@type`、`mainEntityOfPage`、Breadcrumb、Organization、Article、Product、FAQを定義。
- `seo-kpi-profile.schema.json`: GSC、GA4、CV、CTR、CWV、順位、売上、実験KPIを定義。
- `seo-experiment.schema.json`: 仮説、対象URL、バリアント、期間、停止条件、勝敗判定を定義。
- `llmo-answer-unit.schema.json`: 質問、短答、根拠、出典、更新日、構造化ブロック、引用候補を定義。
- `evidence-graph.schema.json`: 著者、監修、出典、一次情報、更新履歴、組織情報、レビュー証跡を定義。

## API Requirements

- すべての書き込みAPIは `dryRun=true` を受け付ける。
- すべての自動変更は `actor`、`reason`、`before`、`after`、`rollback` を保存する。
- すべての外部連携は接続状態、権限スコープ、最終同期時刻、失敗理由を返す。
- AI生成コンテンツは `sourceEvidence[]` と `humanReviewStatus` を持つ。
- サイト公開に影響する操作は `capability` チェックとnonceまたはアプリケーションパスワード認証を必須にする。

## Theme Package Mapping

個人パッケージ:
- アフィリエイト記事、比較表、ランキング、FAQ、レビュー、CTA、内部リンク、PV/CTR/CV計測を優先。
- Automation SEOはキーワードクラスタ、記事投入、リライト、GSC改善提案を担当。

法人パッケージ:
- LP、製品ページ、サービスページ、導入事例、資料請求、問い合わせ、ABテスト、イベント計測を優先。
- Automation SEOは商材別LP設計、CTA改善、構造化データ、CVファネル分析を担当。

