---
name: seo-ux-cro
description: Use when designing UX and conversion improvements that affect SEO: page speed UX, mobile layout, accessibility, CTA placement, A/B tests, LP funnels, affiliate clicks, and Core Web Vitals.
---

# seo-ux-cro

## Purpose

検索流入をCVにつなげ、同時にページ体験とCore Web Vitalsを悪化させない。

## Inputs

- LP構成、CTA、フォーム、アフィリエイトリンク、ヒートマップ、GA4イベント。
- LCP element、INP interaction、CLS source、mobile viewport。

## Process

1. ファーストビューの検索意図一致、主CTA、証拠、反論処理を確認する。
2. LCP候補画像、フォント、CSS、JS、third-party scriptsを点検する。
3. INP悪化要因の重いJS、クリックハンドラ、A/Bツールを点検する。
4. CLS悪化要因の画像サイズ未指定、広告枠、遅延挿入CTAを点検する。
5. A/BテストはSEOメタやindexabilityを変えず、CV仮説として設計する。

## Outputs

- `uxCroAudit`
- `coreWebVitalsFixPlan`
- `ctaExperimentPlan`
- `funnelEventMap`

## AGENT NEO Rules

- CTAの追加は表示速度、CLS、アクセシビリティ、広告表記に影響しない形で行う。
- 法人LPは問い合わせ、資料請求、事例、FAQ、比較、導入ステップを計測する。
- 個人版はランキングクリック、比較表クリック、レビューCTA、広告リンクを計測する。

