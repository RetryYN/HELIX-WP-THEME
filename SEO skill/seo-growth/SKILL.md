---
name: seo-growth
description: Use when scaling SEO operations: topical authority, content scaling, link acquisition, SEO experiments, refresh cycles, programmatic SEO governance, and monthly improvement loops.
---

# seo-growth

## Purpose

SEO施策を一回限りで終わらせず、仮説、実装、計測、学習、再投入のループにする。

## Inputs

- `seoKpiProfile`
- 施策履歴、対象URL、変更日、GSC/GA4結果、順位、CV、CWV。
- コンテンツ在庫、内部リンク、被リンク、競合差分。

## Process

1. 伸びているクラスタ、落ちているクラスタ、未着手クラスタを分ける。
2. SEO実験を title、CTA、内部リンク、FAQ、構造化データ、リライト、LP順序に分類する。
3. 実験ごとに対象URL、仮説、期間、勝敗条件、停止条件を定義する。
4. スケール対象は品質ゲート、重複判定、noindex条件を持たせる。
5. 月次で勝ちパターンをテーマパターンまたはAutomation SEOテンプレートに昇格する。

## Outputs

- `seoExperiment`
- `growthBacklog`
- `refreshQueue`
- `scaleQualityGate`
- `monthlySeoReview`

## AGENT NEO Rules

- 大量生成より、クラスタ単位の品質、内部リンク、更新性、CVを優先する。
- 実験は同時に複数要因を変えすぎない。
- 勝ちパターンは再利用可能なFSE pattern、section template、JSON blueprintにする。

