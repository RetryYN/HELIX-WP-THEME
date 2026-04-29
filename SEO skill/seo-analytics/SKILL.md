---
name: seo-analytics
description: Use when connecting SEO measurement: Google Search Console, GA4, rankings, traffic analysis, KPI tracking, CTR, conversion, crawl stats, URL Inspection API, and reporting contracts.
---

# seo-analytics

## Purpose

SEO施策を、検索露出、クリック、行動、CV、技術状態のデータで検証できるようにする。

## Inputs

- GSC Search Analytics、URL Inspection API、Page Indexing、Crawl Stats。
- GA4 events、Measurement Protocol、CV、funnel、landing page。
- PageSpeed、CrUX、Lighthouse、rank tracker。

## Process

1. KGI、KPI、補助指標をページ種別ごとに定義する。
2. GSCとGA4をURL、query、content cluster、section_idで突合できる形にする。
3. URL Inspection APIでindex status、canonical、mobile usability、rich resultsを取得する。
4. GA4イベントはCTA、affiliate click、form start、form submit、scroll、section viewを標準化する。
5. レポートは施策、対象URL、変更日、期待効果、結果を紐づける。

## Outputs

- `seoKpiProfile`
- `gscQueryReport`
- `urlInspectionReport`
- `ga4EventMap`
- `seoDashboardSpec`

## AGENT NEO Rules

- GA4 Measurement Protocolは自動計測の代替ではなく補完として使う。
- API認証、OAuth、プロパティ接続は人間確認を必要とする。
- データ欠損、サンプリング、タイムラグ、URL正規化をレポートに明記する。

