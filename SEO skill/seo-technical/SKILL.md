---
name: seo-technical
description: Use when auditing or implementing technical SEO: crawlability, indexability, canonical, robots, sitemap, schema, Core Web Vitals, mobile rendering, WordPress FSE templates, and plugin conflict boundaries.
---

# seo-technical

## Purpose

GooglebotとAIクローラがページを取得、理解、正規化、評価できる状態を作る。

## Inputs

- robots.txt、sitemap.xml、canonical、noindex、HTTP status、redirect。
- template hierarchy、FSE templates、JSON-LD、theme assets。
- PageSpeed、Lighthouse、CrUX、GSC URL Inspection API。

## Process

1. Googlebotがブロックされていないか、HTTP 200か、indexable contentがあるか確認する。
2. canonical、sitemap、internal links、hreflangのURLが矛盾していないか確認する。
3. robots.txtをcanonical代替に使っていないか確認する。
4. JSON-LDが表示内容と一致し、隠し情報や誤認情報を含まないか確認する。
5. LCP、INP、CLSをページ種別ごとに測る。
6. WPテーマとSEOプラグインの責務衝突を検出する。

## Outputs

- `indexabilityPolicy`
- `crawlabilityAudit`
- `canonicalConflictReport`
- `structuredDataGraph`
- `coreWebVitalsBudget`
- `pluginConflictMatrix`

## AGENT NEO Rules

- canonicalは絶対URLで出す。
- sitemapに載せるURLは原則canonical URLだけにする。
- noindex、robots、canonical、sitemapは1つのポリシーJSONから生成する。
- テーマは高速で安定したHTMLを出し、外部API取得や重い監査はAutomation SEO側に寄せる。

