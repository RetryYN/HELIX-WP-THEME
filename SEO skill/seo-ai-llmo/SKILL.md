---
name: seo-ai-llmo
description: Use when designing AI SEO, LLMO, AEO, answer units, entity optimization, source evidence, structured content, SERP features, and crawler-friendly content for AI agents.
---

# seo-ai-llmo

## Purpose

LLMやAI検索に引用、要約、理解されやすい形で、根拠あるコンテンツと機械可読データを出す。

## Inputs

- FAQ、短答、定義、比較、手順、価格、仕様、著者、監修、出典。
- JSON-LD、evidence graph、更新履歴、Organization/Profile情報。

## Process

1. ページごとに主要質問と短答を定義する。
2. 回答、根拠、一次情報、出典、更新日、著者を紐づける。
3. schema.orgのOrganization、Person、Article、FAQ、Product、Review、Breadcrumbを設計する。
4. AIクローラがJS依存なしで本文と構造を読めるか確認する。
5. 事実、主張、推測、広告表現を分離する。

## Outputs

- `llmoAnswerUnit`
- `entityMap`
- `evidenceGraph`
- `aiCrawlerReadabilityAudit`
- `structuredSnippetPlan`

## AGENT NEO Rules

- LLMOは順位保証ではなく、機械可読性、引用可能性、根拠整備として扱う。
- 構造化データは本文に見える内容と一致させる。
- AI生成コンテンツは出典、レビュー状態、更新日を必ず持つ。
- FAQや短答はユーザーに見える形で表示し、隠しテキストにしない。

