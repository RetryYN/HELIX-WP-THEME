# SEO Skill Map Coverage Matrix

この表は `seo-skill-map.md` の10カテゴリと個別スキル項目が、`SEO skill` 配下のどの `SKILL.md` で扱われるかを示すトレーサビリティです。

## Coverage Summary

| 項目 | 状態 |
|---|---|
| カテゴリ網羅 | 10/10 covered |
| 個別スキル項目 | 53/53 covered |
| 追加運用スキル | `seo-skill-fork`, `seo-automation-crawl` |
| 正本 | `SEO skill/references/seo-skill-map.md` |

## Category Mapping

| 元カテゴリ | 担当スキル | 補助スキル | カバー方針 |
|---|---|---|---|
| 1. Strategy | `seo-strategy` | `seo-analytics`, `seo-growth` | 需要、検索意図、競合、クラスタ、勝ち筋を定義する |
| 2. On-Page | `seo-onpage` | `seo-technical`, `seo-ux-cro` | ページ単位のメタ、見出し、URL、内部リンク、画像、本文を最適化する |
| 3. Technical | `seo-technical` | `seo-automation-crawl`, `seo-analytics` | クロール、インデックス、速度、schema、canonical、sitemapを扱う |
| 4. Content | `seo-content` | `seo-strategy`, `seo-ai-llmo`, `seo-growth` | 記事、ピラー、クラスタ、ツール、更新、量産品質を扱う |
| 5. Off-Page | `seo-offpage` | `seo-analytics`, `seo-growth` | 被リンク、PR、ブランド言及、アウトリーチを扱う |
| 6. International | `seo-international` | `seo-technical`, `seo-content` | hreflang、多言語、地域、ローカライズを扱う |
| 7. Analytics | `seo-analytics` | `seo-growth`, `seo-automation-crawl` | GA4、GSC、順位、トラフィック、KPIを扱う |
| 8. UX | `seo-ux-cro` | `seo-technical`, `seo-analytics` | 速度UX、モバイル、UI、A/B、CVファネルを扱う |
| 9. AI SEO | `seo-ai-llmo` | `seo-content`, `seo-technical` | NLP、生成SEO、SERP機能、LLM可視性を扱う |
| 10. Growth | `seo-growth` | `seo-strategy`, `seo-analytics` | トピックオーソリティ、拡張、リンク獲得、実験を扱う |

## Item Mapping

| 元カテゴリ | 元スキル項目 | 主担当 | 補助 | AGENT NEOでの成果物 |
|---|---|---|---|---|
| Strategy | キーワードリサーチ | `seo-strategy` | `seo-analytics` | `keywordCluster`, `contentRoadmap` |
| Strategy | 検索意図 | `seo-strategy` | `seo-onpage` | `searchIntentMap` |
| Strategy | 競合分析 | `seo-strategy` | `seo-growth` | `competitorGap` |
| Strategy | キーワードクラスタリング | `seo-strategy` | `seo-content` | `keyword-cluster.schema.json` |
| Strategy | SEOの成功事例 | `seo-strategy` | `seo-growth` | `packageFit`, `growthBacklog` |
| On-Page | タイトル | `seo-onpage` | `seo-analytics` | `metaRewritePlan` |
| On-Page | メタ記述 | `seo-onpage` | `seo-analytics` | `metaRewritePlan` |
| On-Page | ヘッダー構造 | `seo-onpage` | `seo-content` | `headingMap` |
| On-Page | URL構造 | `seo-onpage` | `seo-technical` | `contentBlockPatch` |
| On-Page | 内部リンク | `seo-onpage` | `seo-growth` | `internalLinkPatch`, `internal-link-graph.schema.json` |
| On-Page | 画像最適化 | `seo-onpage` | `seo-ux-cro` | `onPageAudit` |
| On-Page | コンテンツ最適化 | `seo-onpage` | `seo-content` | `contentBlockPatch` |
| Technical | クローラビリティ | `seo-technical` | `seo-automation-crawl` | `crawlabilityAudit` |
| Technical | インデックス化 | `seo-technical` | `seo-analytics` | `indexabilityPolicy` |
| Technical | サイト速度 | `seo-technical` | `seo-ux-cro` | `coreWebVitalsBudget` |
| Technical | スキーママークアップ | `seo-technical` | `seo-ai-llmo` | `structuredDataGraph` |
| Technical | 正規URLタグ | `seo-technical` | `seo-onpage` | `canonicalConflictReport` |
| Technical | XMLサイトマップ | `seo-technical` | `seo-automation-crawl` | `indexabilityPolicy` |
| Content | ブログコンテンツ | `seo-content` | `seo-onpage` | `contentBlueprint` |
| Content | ピラーページ | `seo-content` | `seo-strategy` | `pillarClusterMap` |
| Content | トピッククラスタ | `seo-content` | `seo-strategy` | `pillarClusterMap` |
| Content | ツールコンテンツ | `seo-content` | `seo-ux-cro` | `contentBlueprint` |
| Content | エバーグリーンコンテンツ | `seo-content` | `seo-growth` | `refreshPlan` |
| Content | プログラマチックSEO | `seo-content` | `seo-technical` | `programmaticSeoPolicy` |
| Content | コンテンツ刷新 | `seo-content` | `seo-growth` | `refreshQueue` |
| Off-Page | リンクビルディング | `seo-offpage` | `seo-growth` | `offPageOpportunityMap` |
| Off-Page | ゲスト投稿 | `seo-offpage` | `seo-strategy` | `outreachList` |
| Off-Page | デジタルPR | `seo-offpage` | `seo-growth` | `digitalPrPlan` |
| Off-Page | ブランド言及 | `seo-offpage` | `seo-analytics` | `brandMentionTracker` |
| Off-Page | アウトリーチ | `seo-offpage` | `seo-growth` | `outreachList` |
| International | Hreflang | `seo-international` | `seo-technical` | `hreflangMap` |
| International | 多言語対応 | `seo-international` | `seo-content` | `translationReviewPlan` |
| International | ジオターゲティング | `seo-international` | `seo-strategy` | `localeUrlPolicy` |
| International | ローカライズ | `seo-international` | `seo-content` | `internationalSeoAudit` |
| Analytics | Google Analytics | `seo-analytics` | `seo-ux-cro` | `ga4EventMap` |
| Analytics | Search Console | `seo-analytics` | `seo-technical` | `gscQueryReport`, `urlInspectionReport` |
| Analytics | 順位追跡 | `seo-analytics` | `seo-growth` | `seoKpiProfile` |
| Analytics | トラフィック分析 | `seo-analytics` | `seo-growth` | `seoDashboardSpec` |
| Analytics | KPI追跡 | `seo-analytics` | `seo-growth` | `seoKpiProfile` |
| UX | ページ速度UX | `seo-ux-cro` | `seo-technical` | `coreWebVitalsFixPlan` |
| UX | モバイル最適化 | `seo-ux-cro` | `seo-technical` | `uxCroAudit` |
| UX | UXデザイン | `seo-ux-cro` | `seo-onpage` | `uxCroAudit` |
| UX | A/Bテスト | `seo-ux-cro` | `seo-growth` | `ctaExperimentPlan` |
| UX | コンバージョンファネル | `seo-ux-cro` | `seo-analytics` | `funnelEventMap` |
| AI SEO | コンテンツ最適化 | `seo-ai-llmo` | `seo-content` | `llmoAnswerUnit` |
| AI SEO | NLP最適化 | `seo-ai-llmo` | `seo-strategy` | `entityMap` |
| AI SEO | 生成SEO | `seo-ai-llmo` | `seo-content` | `evidenceGraph` |
| AI SEO | SERP機能 | `seo-ai-llmo` | `seo-onpage` | `structuredSnippetPlan` |
| AI SEO | LLM可視性 | `seo-ai-llmo` | `seo-technical` | `aiCrawlerReadabilityAudit` |
| Growth | トピックオーソリティ | `seo-growth` | `seo-strategy` | `growthBacklog` |
| Growth | コンテンツスケーリング | `seo-growth` | `seo-content` | `scaleQualityGate` |
| Growth | リンク獲得 | `seo-growth` | `seo-offpage` | `growthBacklog` |
| Growth | SEO実験 | `seo-growth` | `seo-analytics` | `seoExperiment` |

## Additional Operational Mapping

| 追加領域 | 担当スキル | 役割 |
|---|---|---|
| GitHub fork運用 | `seo-skill-fork` | SEOスキル群のupstream/fork、PR、同期、リリース管理 |
| 定期クロール監査 | `seo-automation-crawl` | GitHub ActionsなどでのURL監査、Lighthouse、GSC URL Inspection、差分通知 |
| Automation SEO契約 | `references/agent-seo-contracts.md` | JSON契約、API境界、個人/法人パッケージの責務分離 |
| WPテーマ責務境界 | `references/wp-theme-seo-boundary.md` | テーマ、Core Plugin、Automation SEOの責務分離 |

## Coverage Rules

- 元マップのカテゴリを増やした場合は、このファイルの `Category Mapping` と `Item Mapping` を更新する。
- 個別スキル項目を追加した場合は、主担当と補助担当を必ず1つ以上指定する。
- どの `SKILL.md` にも対応できない項目は、未カバーとして明記し、新規スキル追加または既存スキル拡張を判断する。
- GitHub forkでプロジェクト固有差分を持つ場合も、upstream側のカバレッジ表を消さない。
