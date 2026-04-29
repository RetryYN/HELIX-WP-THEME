---
name: seo-content
description: Use when planning or auditing blog content, pillar pages, topic clusters, tool content, evergreen assets, programmatic SEO pages, refreshes, E-E-A-T evidence, and affiliate or corporate LP content.
---

# seo-content

## Purpose

検索意図に対して、回答品質、専門性、更新性、収益導線を満たすコンテンツ設計を作る。

## Inputs

- `keywordCluster`
- `content-blueprint.schema.json`
- 著者、監修、実績、出典、商品情報、FAQ、レビュー情報。

## Process

1. ピラーページ、クラスタ記事、LP、FAQ、比較、ランキング、ツールに分類する。
2. 各ページの主張、根拠、一次情報、著者、更新日を定義する。
3. AI生成可能な部分と人間レビュー必須の部分を分ける。
4. エバーグリーン更新条件と古くなる情報を分ける。
5. プログラマチックSEOは重複、薄いページ、noindex条件を先に設計する。

## Outputs

- `contentBlueprint`
- `pillarClusterMap`
- `refreshPlan`
- `evidenceGraph`
- `programmaticSeoPolicy`

## AGENT NEO Rules

- YMYL主張は人間確認なしで公開しない。
- アフィリエイト記事は広告表記、比較基準、レビュー根拠を明示する。
- 法人LPは機能訴求だけでなく、導入効果、証拠、CTA、FAQ、反論処理を含める。
- AI生成文は出典とレビュー状態をJSONに残す。

