---
name: seo-international
description: Use when designing or auditing multilingual and multi-region SEO: hreflang, localized URLs, translated content quality, geotargeting, currency, legal notices, and canonical alignment.
---

# seo-international

## Purpose

多言語・多地域サイトで、重複、誤地域表示、hreflang不整合を避ける。

## Inputs

- 言語、地域、URL構造、翻訳方針、通貨、配送地域、法人所在地。
- canonical、hreflang、sitemap、localized content。

## Process

1. URL構造を subdirectory、subdomain、ccTLD のどれにするか決める。
2. 各言語ページのcanonicalが同一言語の正規URLを指しているか確認する。
3. hreflangの相互参照、x-default、言語地域コードを検証する。
4. 自動翻訳ページの品質、noindex条件、人間レビュー条件を定義する。
5. 通貨、単位、法務表示、CTA、問い合わせ導線を地域別に設計する。

## Outputs

- `hreflangMap`
- `localeUrlPolicy`
- `translationReviewPlan`
- `internationalSeoAudit`

## AGENT NEO Rules

- hreflangとcanonicalを混同しない。
- 機械翻訳を大量公開する場合は品質ゲートとnoindex条件を必須にする。
- LPは地域別の商習慣、証拠、CTAを変える。

