---
name: seo-strategy
description: Use when planning SEO strategy, keyword research, search intent classification, competitor analysis, keyword clustering, topical authority, or package positioning for AGENT NEO sites.
---

# seo-strategy

## Purpose

検索需要、検索意図、競合、商材、CVを結び、テーマとAutomation SEOが何を作るべきか決める。

## Inputs

- サイト種別、商材、対象顧客、地域、言語、収益モデル。
- 既存URL、GSCクエリ、GA4 CV、競合URL、商品カテゴリ。
- 個人パッケージまたは法人パッケージの区分。

## Process

1. 検索意図を Informational、Commercial、Transactional、Navigational、Local、Comparison、Troubleshooting に分類する。
2. キーワードをピラー、クラスタ、記事、LP、FAQ、比較表、ランキングに分ける。
3. 競合上位ページの見出し、SERP機能、構造化データ、CTA、内部リンクを抽象化する。
4. `keyword-cluster.schema.json` と `search-intent.schema.json` に落とす。
5. 実装対象をテーマ、Automation SEO、運用タスクに分ける。

## Outputs

- `keywordCluster`
- `searchIntentMap`
- `competitorGap`
- `contentRoadmap`
- `packageFit`

## AGENT NEO Rules

- 個人版はアフィリエイト収益化、比較、ランキング、レビュー、FAQを優先する。
- 法人版はLP、製品ページ、導入事例、資料請求、問い合わせ、ABテストを優先する。
- 競合の文言、画像、CSS、固有コンポーネントは流用しない。設計抽象だけを使う。

