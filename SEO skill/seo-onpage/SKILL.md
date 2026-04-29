---
name: seo-onpage
description: Use when designing or auditing page-level SEO: title, meta description, H1-H6, URL structure, internal links, images, anchors, content blocks, FAQ, CTA, and affiliate or LP page markup.
---

# seo-onpage

## Purpose

ページ単位で、検索意図、見出し、本文、CTA、内部リンク、メタ情報を矛盾なく設計する。

## Inputs

- `content-blueprint.schema.json`
- 対象URL、検索意図、キーワードクラスタ、CTA、CVイベント。
- WordPress post meta、block markup、template part、theme.json設定。

## Process

1. title、description、H1が検索意図と一致するか確認する。
2. H2-H4の階層が回答順、比較順、購買判断順になっているか確認する。
3. URL、slug、breadcrumb、canonicalが一致するか確認する。
4. 画像alt、caption、ファイル名、遅延読み込み、サイズ指定を確認する。
5. 内部リンクのアンカー、リンク先、ピラー/クラスタ関係を確認する。
6. FAQ、比較表、レビュー、CTAが本文内容と一致しているか確認する。

## Outputs

- `onPageAudit`
- `metaRewritePlan`
- `headingMap`
- `internalLinkPatch`
- `contentBlockPatch`

## AGENT NEO Rules

- AIが触る単位は `section_id` で識別する。
- CTA、FAQ、比較表、ランキングは計測イベントと紐づける。
- メタ変更は必ずGSC CTR、掲載順位、CVの計測計画を持たせる。
- meta descriptionは順位保証ではなくCTR改善仮説として扱う。

