# footer・記事末尾・カテゴリ面 出現率（再取得コーディング v2 集計）

- keys: 1047（サイト×面×端末。スクリプト scripts/aggregate-v2.py で再生成）
- n は付与された値の総数（複数値は個別に数える）。観察 = n − na。%は n 比。

## category.children
- cat/pc (n=184, 観察 172): none 104 (56%), chips 54 (29%), na 12 (6%), list 7 (3%), cards 4 (2%), image-banners 2 (1%), other:filter-dd 1 (0%)
- cat/sp (n=190, 観察 178): none 105 (55%), chips 49 (25%), list 15 (7%), na 12 (6%), cards 4 (2%), image-banners 2 (1%), other:filter-accordion 1 (0%), other:filter-radio 1 (0%), other:filter-dd 1 (0%)

## category.header
- cat/pc (n=184, 観察 160): name-only 78 (42%), name+description 48 (26%), na 24 (13%), hero-style 11 (5%), name+count 9 (4%), breadcrumb-only 9 (4%), name+description+image 2 (1%), other:about-page 1 (0%), other:splash 1 (0%), none 1 (0%)
- cat/sp (n=190, 観察 162): name-only 89 (46%), name+description 46 (24%), na 28 (14%), hero-style 11 (5%), name+count 5 (2%), other:article 4 (2%), name+description+image 3 (1%), breadcrumb-only 3 (1%), other:service 1 (0%)

## category.intro
- cat/pc (n=184, 観察 170): none 125 (67%), lead-text 39 (21%), na 14 (7%), editorial-article 6 (3%)
- cat/sp (n=190, 観察 178): none 130 (68%), lead-text 38 (20%), na 12 (6%), editorial-article 10 (5%)

## category.mini-home
- cat/pc (n=184, 観察 176): none 160 (86%), yes(sections per child) 16 (8%), na 8 (4%)
- cat/sp (n=190, 観察 180): none 166 (87%), yes(sections per child) 14 (7%), na 10 (5%)

## category.sidebar(pc)
- cat/pc (n=186, 観察 172): none 97 (52%), categories 30 (16%), popular-ranking 19 (10%), na 14 (7%), profile 8 (4%), tags 7 (3%), cta-banner 5 (2%), search 4 (2%), new-posts 2 (1%)
- cat/sp (n=126, 観察 2): na 124 (98%), none 1 (0%), categories 1 (0%)

## footer.above
- article/pc (n=82, 観察 75): none 38 (46%), banner-row 17 (20%), newsletter 11 (13%), cta-band 8 (9%), na 7 (8%), contact-block 1 (1%)
- article/sp (n=75, 観察 65): none 43 (57%), na 10 (13%), cta-band 8 (10%), banner-row 7 (9%), newsletter 6 (8%), contact-block 1 (1%)
- cat/pc (n=191, 観察 179): none 91 (47%), cta-band 37 (19%), banner-row 28 (14%), newsletter 16 (8%), na 12 (6%), contact-block 6 (3%), other:sns-feed 1 (0%)
- cat/sp (n=192, 観察 182): none 95 (49%), banner-row 33 (17%), cta-band 31 (16%), newsletter 12 (6%), na 10 (5%), contact-block 9 (4%), recruit-band 2 (1%)
- top/pc (n=272, 観察 239): banner-row 92 (33%), none 73 (26%), cta-band 47 (17%), na 33 (12%), newsletter 13 (4%), contact-block 10 (3%), recruit-band 2 (0%), other:blank-block 1 (0%), map 1 (0%)
- top/sp (n=276, 観察 240): none 99 (35%), banner-row 59 (21%), cta-band 48 (17%), na 36 (13%), newsletter 19 (6%), contact-block 11 (3%), recruit-band 2 (0%), map 1 (0%), related-sites 1 (0%)

## footer.back-to-top
- article/pc (n=78, 観察 70): none 45 (57%), button-fixed 24 (30%), na 8 (10%), inline-link 1 (1%)
- article/sp (n=74, 観察 58): none 40 (54%), na 16 (21%), button-fixed 13 (17%), inline-link 5 (6%)
- cat/pc (n=184, 観察 161): none 113 (61%), button-fixed 41 (22%), na 23 (12%), inline-link 7 (3%)
- cat/sp (n=190, 観察 158): none 125 (65%), na 32 (16%), button-fixed 24 (12%), inline-link 9 (4%)
- top/pc (n=254, 観察 198): none 149 (58%), na 56 (22%), button-fixed 39 (15%), inline-link 10 (3%)
- top/sp (n=267, 観察 211): none 166 (62%), na 56 (20%), button-fixed 32 (11%), inline-link 13 (4%)

## footer.extra
- article/pc (n=91, 観察 84): none 32 (35%), sns-icons 32 (35%), na 7 (7%), related-sites 5 (5%), tag-cloud 4 (4%), search 4 (4%), certification-badges 2 (2%), tel 2 (2%), address 2 (2%), app-badges 1 (1%)
- article/sp (n=95, 観察 90): none 30 (31%), sns-icons 27 (28%), related-sites 13 (13%), tag-cloud 7 (7%), search 5 (5%), na 5 (5%), certification-badges 2 (2%), address 2 (2%), app-badges 2 (2%), contact-block 1 (1%), tel 1 (1%)
- cat/pc (n=223, 観察 209): sns-icons 86 (38%), none 54 (24%), certification-badges 20 (8%), na 14 (6%), related-sites 13 (5%), address 11 (4%), tel 8 (3%), search 7 (3%), tag-cloud 5 (2%), app-badges 5 (2%)
- cat/sp (n=244, 観察 233): sns-icons 89 (36%), none 60 (24%), related-sites 26 (10%), certification-badges 18 (7%), address 15 (6%), tel 12 (4%), na 11 (4%), search 5 (2%), app-badges 5 (2%), tag-cloud 3 (1%)
- top/pc (n=320, 観察 286): sns-icons 123 (38%), none 64 (20%), na 34 (10%), related-sites 30 (9%), certification-badges 29 (9%), address 12 (3%), tel 8 (2%), tag-cloud 7 (2%), app-badges 6 (1%), search 6 (1%), newsletter 1 (0%)
- top/sp (n=343, 観察 308): sns-icons 125 (36%), none 70 (20%), na 35 (10%), certification-badges 29 (8%), address 25 (7%), related-sites 25 (7%), tel 16 (4%), app-badges 7 (2%), search 5 (1%), tag-cloud 4 (1%), contact-block 1 (0%), newsletter 1 (0%)

## footer.fixed.parts
- article/pc (n=80, 観察 76): none 55 (68%), float-cta 8 (10%), cookie-consent 6 (7%), na 4 (5%), float-chat 4 (5%), sp-bottom-bar 1 (1%), other:cmp-bar 1 (1%), other:floating-icon-nav 1 (1%)
- article/sp (n=75, 観察 63): none 51 (68%), na 12 (16%), float-cta 6 (8%), cookie-consent 2 (2%), sp-bottom-bar 2 (2%), other:video-ad 1 (1%), float-chat 1 (1%)
- cat/pc (n=184, 観察 167): none 130 (70%), na 17 (9%), cookie-consent 17 (9%), float-cta 9 (4%), float-chat 9 (4%), other:sticky-bar 1 (0%), other:chat-widget 1 (0%)
- cat/sp (n=191, 観察 170): none 127 (66%), na 21 (10%), cookie-consent 18 (9%), float-chat 11 (5%), sp-bottom-bar 8 (4%), float-cta 4 (2%), other:age-gate 1 (0%), other:chat-mail 1 (0%)
- top/pc (n=257, 観察 212): none 147 (57%), na 45 (17%), cookie-consent 30 (11%), float-cta 18 (7%), float-chat 16 (6%), other:promo-modal 1 (0%)
- top/sp (n=272, 観察 237): none 161 (59%), na 35 (12%), cookie-consent 30 (11%), float-chat 16 (5%), float-cta 15 (5%), sp-bottom-bar 12 (4%), other:age-gate 2 (0%), other:audio-consent 1 (0%)

## footer.layout
- article/pc (n=78, 観察 71): single-row 26 (33%), mega(sitemap) 26 (33%), columns-3 13 (16%), na 7 (8%), columns-4 3 (3%), columns-2 2 (2%), logo-only-legal 1 (1%)
- article/sp (n=74, 観察 67): single-row 25 (33%), mega(sitemap) 15 (20%), stacked-centered 13 (17%), columns-2 8 (10%), na 7 (9%), accordion(sp) 3 (4%), other:widgets 1 (1%), columns-3 1 (1%), logo-only-legal 1 (1%)
- cat/pc (n=184, 観察 171): mega(sitemap) 74 (40%), single-row 43 (23%), columns-3 23 (12%), columns-4 17 (9%), na 13 (7%), columns-2 8 (4%), stacked-centered 5 (2%), logo-only-legal 1 (0%)
- cat/sp (n=190, 観察 180): mega(sitemap) 54 (28%), single-row 49 (25%), accordion(sp) 31 (16%), stacked-centered 20 (10%), columns-2 16 (8%), na 10 (5%), logo-only-legal 5 (2%), other:reco-grid 1 (0%), other:single-col 1 (0%), other:archive 1 (0%), columns-4 1 (0%), columns-3 1 (0%)
- top/pc (n=254, 観察 220): mega(sitemap) 89 (35%), single-row 60 (23%), na 34 (13%), columns-3 24 (9%), columns-4 21 (8%), columns-2 10 (3%), stacked-centered 9 (3%), logo-only-legal 6 (2%), accordion(sp) 1 (0%)
- top/sp (n=267, 観察 234): single-row 72 (26%), mega(sitemap) 51 (19%), accordion(sp) 43 (16%), na 33 (12%), stacked-centered 32 (11%), columns-2 25 (9%), logo-only-legal 7 (2%), columns-4 2 (0%), other:paragraph 1 (0%), columns-3 1 (0%)

## footer.legal
- article/pc (n=78, 観察 65): copyright-only 33 (42%), copyright+links 25 (32%), na 13 (16%), none 7 (8%)
- article/sp (n=74, 観察 63): copyright+links 32 (43%), copyright-only 25 (33%), na 11 (14%), none 6 (8%)
- cat/pc (n=184, 観察 159): copyright+links 87 (47%), copyright-only 67 (36%), na 25 (13%), none 4 (2%), other:links-only 1 (0%)
- cat/sp (n=190, 観察 165): copyright+links 80 (42%), copyright-only 76 (40%), na 25 (13%), none 8 (4%), other:links-only 1 (0%)
- top/pc (n=254, 観察 205): copyright+links 113 (44%), copyright-only 78 (30%), na 49 (19%), none 13 (5%), other:tagline 1 (0%)
- top/sp (n=267, 観察 213): copyright+links 96 (35%), copyright-only 94 (35%), na 54 (20%), none 21 (7%), other:links-only 2 (0%)

## footer.nav
- article/pc (n=78, 観察 71): sitemap-full 38 (48%), legal-only 23 (29%), primary-only 7 (8%), na 7 (8%), category-links 2 (2%), none 1 (1%)
- article/sp (n=74, 観察 69): sitemap-full 33 (44%), legal-only 14 (18%), category-links 8 (10%), primary-only 7 (9%), none 7 (9%), na 5 (6%)
- cat/pc (n=184, 観察 171): sitemap-full 107 (58%), legal-only 26 (14%), primary-only 24 (13%), na 13 (7%), none 7 (3%), category-links 7 (3%)
- cat/sp (n=190, 観察 181): sitemap-full 103 (54%), legal-only 26 (13%), primary-only 26 (13%), none 14 (7%), category-links 10 (5%), na 9 (4%), related-sites 1 (0%), other:reco-grid 1 (0%)
- top/pc (n=254, 観察 221): sitemap-full 138 (54%), legal-only 36 (14%), na 33 (12%), primary-only 28 (11%), none 16 (6%), category-links 3 (1%)
- top/sp (n=267, 観察 236): sitemap-full 113 (42%), legal-only 39 (14%), na 31 (11%), primary-only 30 (11%), none 27 (10%), category-links 21 (7%), accordion(sp) 6 (2%)

## list.card
- cat/pc (n=355, 観察 331): image-top 90 (25%), with-date 88 (24%), with-category-chip 58 (16%), with-excerpt 43 (12%), title-only 30 (8%), na 24 (6%), image-left 22 (6%)
- cat/sp (n=373, 観察 346): image-top 94 (25%), with-date 89 (23%), with-category-chip 65 (17%), with-excerpt 47 (12%), title-only 36 (9%), na 27 (7%), image-left 15 (4%)

## list.count-visible
- cat/pc (n=184, 観察 135): na 49 (26%), 7-12 48 (26%), 13+ 29 (15%), 7+ 29 (15%), 1-6 27 (14%), 4-6 2 (1%)
- cat/sp (n=190, 観察 120): na 70 (36%), 7+ 40 (21%), 1-6 32 (16%), 7-12 27 (14%), 13+ 13 (6%), 4-6 8 (4%)

## list.layout
- cat/pc (n=184, 観察 162): grid 82 (44%), text-list 36 (19%), thumb-list 26 (14%), na 22 (11%), featured+grid 9 (4%), featured-big+small 2 (1%), masonry 2 (1%), other:svc-page 1 (0%), other:carousel 1 (0%), thumb-list-1line 1 (0%), other:category-blocks 1 (0%), other:single-post 1 (0%)
- cat/sp (n=190, 観察 164): thumb-list 68 (35%), text-list 44 (23%), grid 28 (14%), na 26 (13%), featured+grid 16 (8%), carousel 3 (1%), featured-big+small 1 (0%), thumb-list-1line 1 (0%), other:follow-buttons 1 (0%), other:review-blocks 1 (0%), masonry 1 (0%)

## pagination
- cat/pc (n=185, 観察 107): na 78 (42%), numbers 51 (27%), none 31 (16%), load-more 17 (9%), prev-next 7 (3%), other:year-list 1 (0%)
- cat/sp (n=190, 観察 76): na 114 (60%), numbers 37 (19%), none 18 (9%), load-more 13 (6%), prev-next 6 (3%), infinite 1 (0%), other:archive 1 (0%)

## ranking.in-category
- cat/pc (n=184, 観察 176): none 157 (85%), sidebar 16 (8%), na 8 (4%), bottom 2 (1%), top 1 (0%)
- cat/sp (n=190, 観察 180): none 168 (88%), na 10 (5%), bottom 8 (4%), sidebar 2 (1%), top 2 (1%)

## tail.author
- article/pc (n=78, 観察 73): none 47 (60%), avatar+bio+sns 11 (14%), avatar+bio 11 (14%), na 5 (6%), name-only 4 (5%)
- article/sp (n=73, 観察 70): none 48 (65%), avatar+bio+sns 9 (12%), avatar+bio 7 (9%), name-only 5 (6%), na 3 (4%), supervisor-separate 1 (1%)

## tail.comments
- article/pc (n=78, 観察 75): none 65 (83%), form 10 (12%), na 3 (3%)
- article/sp (n=73, 観察 72): none 66 (90%), form 6 (8%), na 1 (1%)

## tail.cta
- article/pc (n=78, 観察 74): none 51 (65%), box-with-copy 8 (10%), banner-image 7 (8%), na 4 (5%), button-only 4 (5%), line/newsletter 4 (5%)
- article/sp (n=73, 観察 71): none 54 (73%), box-with-copy 10 (13%), banner-image 4 (5%), na 2 (2%), line/newsletter 2 (2%), button-only 1 (1%)

## tail.order
- article/pc (n=177, 観察 173): related 53 (29%), author 23 (12%), share 22 (12%), cta 17 (9%), prev-next 13 (7%), comments 10 (5%), ad 10 (5%), tags 9 (5%), category-links 9 (5%), na 4 (2%), none 3 (1%), banner-image 1 (0%)
- article/sp (n=142, 観察 142): related 32 (22%), author 21 (14%), cta 16 (11%), category-links 14 (9%), share 13 (9%), ad 9 (6%), tags 9 (6%), prev-next 7 (4%), comments 6 (4%), ranking 6 (4%), none 4 (2%), sidebar-below 4 (2%)

## tail.prev-next
- article/pc (n=78, 観察 75): none 61 (78%), with-thumb 9 (11%), text-only 5 (6%), na 3 (3%)
- article/sp (n=73, 観察 72): none 65 (89%), text-only 4 (5%), with-thumb 3 (4%), na 1 (1%)

## tail.related.count
- article/pc (n=78, 観察 51): na 27 (34%), 4-6 19 (24%), 1-3 17 (21%), 7+ 15 (19%)
- article/sp (n=73, 観察 36): na 37 (50%), 1-3 14 (19%), 4-6 13 (17%), 7+ 9 (12%)

## tail.related.layout
- article/pc (n=79, 観察 76): grid-cards 29 (36%), none 23 (29%), thumb-list-1line 11 (13%), featured-big+small 4 (5%), na 3 (3%), text-numbered 3 (3%), carousel 3 (3%), other:text-list 1 (1%), series-prev-next 1 (1%), other:text-links 1 (1%)
- article/sp (n=73, 観察 73): none 34 (46%), thumb-list-1line 15 (20%), grid-cards 7 (9%), ranking-numbers 5 (6%), featured-big+small 2 (2%), other:breadcrumb 1 (1%), other:sitemap 1 (1%), other:banners 1 (1%), text-numbered 1 (1%), other:event-banner 1 (1%), other:bullet-list 1 (1%), other:day-cards 1 (1%)

## tail.share
- article/pc (n=78, 観察 74): none 47 (60%), icons-row 24 (30%), na 4 (5%), icons-with-count 2 (2%), text-buttons 1 (1%)
- article/sp (n=73, 観察 67): none 52 (71%), icons-row 10 (13%), na 6 (8%), text-buttons 3 (4%), icons-with-count 2 (2%)
