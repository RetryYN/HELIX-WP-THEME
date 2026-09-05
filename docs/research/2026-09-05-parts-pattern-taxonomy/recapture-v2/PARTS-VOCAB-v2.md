# パーツ別パターン語彙 v2（footer・記事末尾・カテゴリ面の再取得コーディング用）

v1（PARTS-VOCAB.md）を継承し、観察不足だった 3 領域を細分化する。該当なし= none、見えない= na、語彙に無い型= other:<10 字以内>。複数該当は | 区切り。

## 領域 A: footer（top / article / cat の `--foot` 画像）
- footer.layout: columns-4 | columns-3 | columns-2 | single-row | mega(sitemap) | logo-only-legal | accordion(sp) | stacked-centered
- footer.above: cta-band | banner-row | newsletter | contact-block | map | recruit-band | none
- footer.nav: sitemap-full | primary-only | legal-only | category-links | none
- footer.extra: sns-icons | related-sites | app-badges | tel | address | certification-badges | search | tag-cloud | none
- footer.legal: copyright-only | copyright+links | none
- footer.back-to-top: button-fixed | inline-link | none
- footer.fixed.parts: sp-bottom-bar | float-cta | float-chat | cookie-consent | none

## 領域 B: 記事末尾（article の `--tail` 画像。本文終了直後〜footer 手前）
- tail.order (list in observed order, |区切り): cta | share | tags | author | related | prev-next | comments | ranking | category-links | sidebar-below | ad
- tail.cta: button-only | box-with-copy | banner-image | product-card-bundle | line/newsletter | none
- tail.share: icons-row | icons-with-count | text-buttons | none
- tail.author: avatar+bio | avatar+bio+sns | name-only | supervisor-separate | none
- tail.related.layout: thumb-list-1line | grid-cards | featured-big+small | ranking-numbers | text-numbered | carousel | series-prev-next | none
- tail.related.count: 1-3 | 4-6 | 7+ | na
- tail.prev-next: with-thumb | text-only | none
- tail.comments: form | none

## 領域 C: カテゴリ面（cat の `--hero` `--mid` `--foot`）
- category.header: name-only | name+description | name+description+image | name+count | hero-style | breadcrumb-only
- category.children: chips | list | cards | image-banners | steps-numbered | none
- category.intro: lead-text | editorial-article | none
- ranking.in-category: top | sidebar | bottom | none
- list.layout: grid | thumb-list | featured+grid | masonry | text-list
- list.card: image-top | image-left | title-only | with-excerpt | with-date | with-category-chip
- list.count-visible: 1-6 | 7-12 | 13+ | na
- pagination: numbers | load-more | infinite | prev-next | none
- category.sidebar(pc): none | popular-ranking | profile | categories | search | cta-banner | new-posts | tags
- category.mini-home: yes(sections per child) | none
