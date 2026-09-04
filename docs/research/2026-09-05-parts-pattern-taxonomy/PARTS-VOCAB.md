# パーツ別パターン語彙 v1（画像コーディング用）

各サイト・各面について、以下の PART ごとに「観察された型」を語彙から選ぶ。該当なし= none、見えない= na、語彙に無い型= other:<短い説明>。複数該当は | 区切り。

## 面: top（トップ / LP）
- header.layout: logo-left-nav-right | logo-left-nav-center | logo-center-nav-below | logo-center-only | two-rows | logo-left-cta-right(no text nav) | transparent-over-hero | with-search | with-tel | with-announce-bar
- header.sp: hamburger-right | hamburger-left | hamburger+cta | hamburger+search | bottom-tabs | no-hamburger(text nav)
- hero.type: split-text-image | fullbleed-photo-overlay | text-only | video | slider | bento-grid | illustration | product-shot | article-grid(media) | none
- hero.cta: single | double | form-inline | none
- section.types (list all seen): numbers | features-3col | features-icon-list | cases-cards | logos-row | steps | pricing | faq-accordion | testimonials | team | news-list | article-grid | ranking | category-cards | tabs | video | map | cta-band | banner-row | blog-cards | timeline | comparison-table | badges-awards | app-download | newsletter
- card.style: border | shadow | flat-no-border | image-top | image-left | icon-top | number-top | photo-full-overlay
- footer.layout: columns-4 | columns-3 | columns-2 | single-row | mega(sitemap) | cta-band-above | banner-row-above | logo-only-legal | with-related-sites | with-sns-icons | with-newsletter | with-back-to-top
- fixed.parts: sp-bottom-bar | float-cta | float-chat | sticky-header | cookie-consent | announce-bar | none

## 面: article（記事）
- title.area: title-then-image | image-then-title | hero-overlay-title | no-image | side-thumb
- meta: date | updated | author | category-chip | tags | reading-time | share-top
- toc.type: box-inline | float-side | collapsible | none ; toc.style: numbered | bullet | plain | with-progress
- heading.h2: bar-left | underline | band-fill | band-fill-rounded | bottom-border-2tone | icon-prefix | number-prefix | plain-bold | background-tint | bracket/ribbon
- heading.h3: bar-left | underline | plain-bold | dotted | icon-prefix
- box.types: band-title | tab-title | label-title | plain-border | tinted | warn | check-list | dashed | shadow-card | quote-style
- list.styles: check | number-circle | arrow | dot-plain | icon-custom
- link.card: internal-thumb-left | internal-thumb-right | text-band | external-ogp | none
- talk.bubble: yes-icon-left | yes-both-sides | none
- table: compare-sticky-first-col | simple-striped | product-table-with-cta | none
- cta.inpost: button-only | box-with-copy | banner-image | product-card-bundle | none
- related.layout: thumb-list-1line | grid-cards | featured-big+small | ranking-numbers | text-numbered | carousel | sidebar-widget-list | series-prev-next
- author.box: avatar+bio | avatar+bio+sns | supervisor-separate | none
- share: top-and-bottom | bottom-only | float | none
- sidebar: none | popular-ranking | profile | categories | search | cta-banner | toc-sticky | new-posts | tags
- footer.cta: yes | none

## 面: category（一覧）
- category.header: name-only | name+description | name+description+image | name+count | hero-style
- category.children: chips | list | cards | none
- reading.order: series-numbered | none
- ranking.in-category: yes | none
- list.layout: grid | thumb-list | featured+grid | masonry
- pagination: numbers | load-more | infinite | prev-next
