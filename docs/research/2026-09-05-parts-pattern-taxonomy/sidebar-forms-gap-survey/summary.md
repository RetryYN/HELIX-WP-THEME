# サイドバー / フォーム / 抜け漏れ 観察サマリー（2026-09-06、research-r21。gen_summary.py の全生成）

PO 反応 21 回目（WT-EVT-0289「進めて。あとフォームの項目追加とかの項目調査。ほかのサイト見て抜け漏れがないか徹底的に調べて。」、WT-DIR-RESEARCH-FORMS-01 / WT-DIR-GAP-SURVEY-01）を受けた 3 系統の観察。固有名・URL の対応表は別管理（リポ外）で本ディレクトリには含めない。本ファイルは `python3 gen_summary.py > summary.md` で 3 つの observations.json から**全生成**する。

## 0. 用語・分母・ラベル

- 取得は WebFetch の本文要約経由（1 ページ 1 回、PC 相当）。追尾（sticky）・SP の扱い・JS で出る要素は要約の記述に依存し、**実ブラウザでは未検証**。na = 取得できたが判定できない、fetched:false = 取得失敗（404 / 403 / 名前解決失敗 / 別ドメインへの転送 / 本文空）。どの集計にも含めない。
- 語彙は `CODING-BRIEF.md`。語彙に無い型は other:<説明>。複数選択は "," 区切り。第三者サービス名は書かない（external_service は embedded-form-service / link-to-external の語彙のみ）。
- ラベル: **観察事実**（件数と%）／**Claude 案**（解釈・提案）／**暫定既定値**（PO 確認前提）。区分 n≤5 の最多型は既定値の根拠にしない。
- サイト区分: A=製造 / 士業、B=SaaS / オウンドメディア / 制作会社、C=比較メディア、D=店舗 / クリニック / スクール、E=学校法人、P=ポータル / 個人ブログ。候補は前回台帳（HP 76 件 + 記事 URL 118 件）から選んだ。

## 1. サイドバー / サイドナビ（記事・固定ページ・HP）

- 件数: 50 件、fetched:true **48**、失敗 2（S08, S19）。page_kind 内訳: article 39 / category 2 / home 6 / page 1。区分: B=7 / C=18 / P=23。
- **side_layout**（取得 n=48）: right 41/48(85%) / both 5/48(10%) / left 1/48(2%) / none 1/48(2%)。記事のみ（n=39）: right 36/39(92%) / both 2/39(5%) / left 1/39(3%)。
- **side_sticky**（取得 n=48）: toc-only 18/42(43%) / last-widget 17/42(40%) / none 5/42(12%) / whole 2/42(5%)〔欠測 na 6 件を除いた分母 42。欠測込み n=48〕。
- **side_widgets**（複数選択、サイドバーあり = side_layout が none 以外 n=47）: popular-ranking 41/47(87%) / categories 37/47(79%) / search 33/47(70%) / new-posts 28/47(60%) / cta-banner 27/47(57%) / related-posts 21/47(45%) / toc-sticky 19/47(40%) / ad 18/47(38%) / tags 15/47(32%) / profile 10/47(21%) / archive 6/47(13%) / contact-box 6/47(13%) / banner-stack 5/47(11%) / sns-follow 4/47(9%) / newsletter 3/47(6%) / recruit 3/47(6%) / event-list 2/47(4%) / other:toc-dropdown 1/47(2%)。
- **記録リストの先頭のウィジェット**（サイドバーあり n=47、全種。両側カラムも 1 本のリストで記録しているので先頭 = 右カラムの先頭、左は含めない）: search 32/47(68%) / popular-ranking 5/47(11%) / profile 4/47(9%) / categories 3/47(6%) / cta-banner 1/47(2%) / tags 1/47(2%) / toc-sticky 1/47(2%)。
- **ウィジェット数**（サイドバーあり n=47）: 中央値 6、最小 2、最大 10。
- **side_nav**（取得 n=48）: mega-menu-dropdown 17/48(35%) / none 17/48(35%) / toc-side-only 11/48(23%) / drawer-from-hamburger(pc) 1/48(2%) / fixed-left-nav 1/48(2%) / fixed-right-icons 1/48(2%)。
- **side_sp**: drawer 1/1(100%)〔欠測 na 47 件を除いた分母 1。欠測込み n=48〕（要約では SP の扱いはほぼ判定できない。推測の行は na に戻した）。
- **side_width**: 全行 na（要約では判定できず、集計対象外）。
- **本文中の目次**: あり 34/48(71%)。目次をサイドで追尾する型（side_widgets に toc-sticky、または side_sticky=toc-only、または side_nav=toc-side-only）: 19/48(40%)。
- 区分別（観察事実、n が小さい区分は断定しない）:
  - B（n=7）: layout right 5/7(71%) / both 2/7(29%)。widgets 上位 search 6/7(86%) / categories 5/7(71%) / new-posts 5/7(71%) / popular-ranking 5/7(71%) / tags 4/7(57%) / cta-banner 3/7(43%)。
  - C（n=18）: layout right 16/18(89%) / both 2/18(11%)。widgets 上位 cta-banner 18/18(100%) / popular-ranking 17/18(94%) / categories 16/18(89%) / search 16/18(89%) / toc-sticky 13/18(72%) / ad 12/18(67%)。
  - P（n=23）: layout right 20/23(87%) / both 1/23(4%) / left 1/23(4%) / none 1/23(4%)。widgets 上位 popular-ranking 19/23(83%) / categories 16/23(70%) / new-posts 15/23(65%) / related-posts 11/23(48%) / search 11/23(48%) / profile 9/23(39%)。

## 2. フォーム（問い合わせ / 申込 / 資料請求 / 予約 / メルマガ / 採用 / 見積 / トライアル）

- 件数: 51 件、fetched:true 42、失敗 9（F01, F31, F32, F33, F34, F38, F44, F45, F51）。取得できたページの内訳（form_presence）: **本体を観察 31** / 無いと確認（電話・メール案内、ハブ、外部リンクのみ）5 / 有無を判定できない 6（F08, F11, F12, F17, F18, F27）。fields 以降の項目別集計は「本体を観察」の行が分母（form_kind / external_service / cta_side はページ単位で取得全件）。
- **form_kind**（ページ単位の集計、取得 n=42。form_kind / external_service / cta_side の 3 項目は分母の例外 = 本体未観察のページも判定できるため取得全件を対象に na を除外）: contact 18/42(43%) / download 8/42(19%) / reservation 5/42(12%) / recruit 4/42(10%) / newsletter 2/42(5%) / quote 2/42(5%) / trial 2/42(5%) / other:diagnosis 1/42(2%)。
- **fields**（複数選択、フォーム本体 n=31。other:* は語彙外）: name 30/31(97%) / email 29/31(94%) / message 24/31(77%) / tel 24/31(77%) / address 19/31(61%) / consent-checkbox 19/31(61%) / privacy-link 18/31(58%) / postal 16/31(52%) / company 15/31(48%) / name-kana 13/31(42%) / subject-select 12/31(39%) / captcha 6/31(19%) / email-confirm 6/31(19%) / other:gender 6/31(19%) / other:grade 5/31(16%) / other:industry 5/31(16%) / other:birthdate 4/31(13%) / other:faculty 4/31(13%) / other:school-name 4/31(13%) / subject-radio 4/31(13%) / date-pref 3/31(10%) / department 3/31(10%) / how-found 3/31(10%) / attachment 2/31(6%) / other:business-model 2/31(6%) / other:hidden-tracking 2/31(6%) / other:occupation 2/31(6%) / other:product 2/31(6%) / other:relationship 2/31(6%) / other:school-type 2/31(6%) / position 2/31(6%) / other:classroom 1/31(3%) / other:company-kana 1/31(3%) / other:eligibility-questions 1/31(3%) / other:employee-count 1/31(3%) / other:fax 1/31(3%) / other:format 1/31(3%) / other:founded-date 1/31(3%) / other:frequency 1/31(3%) / other:guardian 1/31(3%) / other:option-questions 1/31(3%) / other:order-number 1/31(3%) / other:revenue 1/31(3%) / other:subject-free 1/31(3%) / other:teacher-name 1/31(3%) / people-count 1/31(3%) / time-pref 1/31(3%)。
- **符号化項目数**（fields のトークン数。実際の入力欄数ではなく、複数の yes/no を 1 トークンに圧縮した行がある。フォーム本体 n=31）: 中央値 10、最小 2、最大 24。
- **required_mark**（フォーム本体 n=31）: asterisk 21/26(81%) / 必須-label 5/26(19%)〔欠測 na 5 件を除いた分母 26。欠測込み n=31〕。
- **layout**（フォーム本体 n=31）: 1col 20/26(77%) / steps 5/26(19%) / 2col 1/26(4%)〔欠測 na 5 件を除いた分母 26。欠測込み n=31〕。
- **confirm_page**（フォーム本体 n=31）: yes 9/10(90%) / no 1/10(10%)〔欠測 na 21 件を除いた分母 10。欠測込み n=31〕。
- **thanks_page**（フォーム本体 n=31）: separate 2/2(100%)〔欠測 na 29 件を除いた分母 2。欠測込み n=31〕。
- **submit_text**（フォーム本体 n=31）: other:個人情報の取り扱いに同意して送信する 2/15(13%) / other:同意して、入力内容を確認する 2/15(13%) / other:確認する 2/15(13%) / 送信する 2/15(13%) / other:ダウンロード 1/15(7%) / other:メールを送信する 1/15(7%) / other:次へ進む 1/15(7%) / other:資料請求（無料） 1/15(7%) / other:送信内容確認画面にお進みください 1/15(7%) / 確認画面へ 1/15(7%) / 送信 1/15(7%)〔欠測 na 16 件を除いた分母 15。欠測込み n=31〕。
- **error_display**（フォーム本体 n=31）: top-summary 2/3(67%) / inline-under-field 1/3(33%)〔欠測 na 28 件を除いた分母 3。欠測込み n=31〕。
- **external_service**（ページ単位、取得 n=42）: none 6/15(40%) / link-to-external 5/15(33%) / embedded-form-service 4/15(27%)〔欠測 na 27 件を除いた分母 15。欠測込み n=42〕。
- **cta_side**（複数選択、ページ単位、取得 n=42。messaging-app = メッセージアプリ経由の代替導線）: tel 28/42(67%) / none 11/42(26%) / email 4/42(10%) / chat 3/42(7%) / messaging-app 2/42(5%)。
- **フォーム種別ごとの項目**（観察事実、種別 n が小さいものは参考）:
  - contact（n=16）: message 16/16(100%) / name 16/16(100%) / email 14/16(88%) / tel 12/16(75%) / address 10/16(62%) / consent-checkbox 10/16(62%) / privacy-link 10/16(62%) / company 9/16(56%) / subject-select 9/16(56%) / name-kana 8/16(50%) / postal 8/16(50%) / captcha 4/16(25%) / email-confirm 4/16(25%) / department 2/16(12%) / other:birthdate 2/16(12%) / other:gender 2/16(12%) / other:industry 2/16(12%) / other:product 2/16(12%) / subject-radio 2/16(12%) / attachment 1/16(6%) / how-found 1/16(6%) / other:business-model 1/16(6%) / other:company-kana 1/16(6%) / other:employee-count 1/16(6%) / other:faculty 1/16(6%) / other:fax 1/16(6%) / other:grade 1/16(6%) / other:hidden-tracking 1/16(6%) / other:occupation 1/16(6%) / other:order-number 1/16(6%) / other:revenue 1/16(6%) / other:school-name 1/16(6%) / other:school-type 1/16(6%) / other:subject-free 1/16(6%) / position 1/16(6%)。
  - download（n=5）: email 5/5(100%) / name 5/5(100%) / address 4/5(80%) / postal 4/5(80%) / tel 3/5(60%) / name-kana 2/5(40%) / other:faculty 2/5(40%) / privacy-link 2/5(40%) / company 1/5(20%) / consent-checkbox 1/5(20%) / how-found 1/5(20%) / message 1/5(20%) / other:gender 1/5(20%) / other:grade 1/5(20%) / other:relationship 1/5(20%) / other:school-name 1/5(20%)。
  - newsletter（n=1）: consent-checkbox 1/1(100%) / email 1/1(100%)。
  - other:diagnosis（n=1）: address 1/1(100%) / company 1/1(100%) / email 1/1(100%) / name 1/1(100%) / other:eligibility-questions 1/1(100%) / other:founded-date 1/1(100%) / other:industry 1/1(100%) / people-count 1/1(100%) / subject-radio 1/1(100%) / subject-select 1/1(100%) / tel 1/1(100%)。
  - quote（n=2）: company 2/2(100%) / email 2/2(100%) / message 2/2(100%) / name 2/2(100%) / other:industry 2/2(100%) / tel 2/2(100%) / address 1/2(50%) / attachment 1/2(50%) / captcha 1/2(50%) / consent-checkbox 1/2(50%) / department 1/2(50%) / other:business-model 1/2(50%) / other:frequency 1/2(50%) / other:hidden-tracking 1/2(50%) / position 1/2(50%) / postal 1/2(50%) / privacy-link 1/2(50%) / subject-select 1/2(50%)。
  - recruit（n=1）: address 1/1(100%) / consent-checkbox 1/1(100%) / date-pref 1/1(100%) / email 1/1(100%) / message 1/1(100%) / name 1/1(100%) / name-kana 1/1(100%) / postal 1/1(100%) / privacy-link 1/1(100%) / subject-select 1/1(100%) / tel 1/1(100%)。
  - reservation（n=3）: consent-checkbox 3/3(100%) / email 3/3(100%) / message 3/3(100%) / name 3/3(100%) / tel 3/3(100%) / address 2/3(67%) / date-pref 2/3(67%) / name-kana 2/3(67%) / other:birthdate 2/3(67%) / other:gender 2/3(67%) / other:grade 2/3(67%) / other:school-name 2/3(67%) / postal 2/3(67%) / privacy-link 2/3(67%) / captcha 1/3(33%) / company 1/3(33%) / email-confirm 1/3(33%) / other:faculty 1/3(33%) / other:format 1/3(33%) / other:guardian 1/3(33%) / other:occupation 1/3(33%) / other:option-questions 1/3(33%) / other:school-type 1/3(33%) / other:teacher-name 1/3(33%) / subject-radio 1/3(33%) / time-pref 1/3(33%)。
  - trial（n=2）: consent-checkbox 2/2(100%) / email 2/2(100%) / name 2/2(100%) / privacy-link 2/2(100%) / tel 2/2(100%) / company 1/2(50%) / email-confirm 1/2(50%) / how-found 1/2(50%) / message 1/2(50%) / other:classroom 1/2(50%) / other:gender 1/2(50%) / other:grade 1/2(50%) / other:relationship 1/2(50%)。

## 3. 抜け漏れ（面・パーツの棚卸し。HP から見える範囲）

- 件数: 45 件、fetched:true 45、失敗 0。区分: A=10 / B=9 / C=4 / D=8 / E=5 / P=9。
- **faces_present**（複数選択、n=45）: contact-page 42/45(93%) / company-page 38/45(84%) / news-archive 38/45(84%) / privacy 37/45(82%) / recruit-page 27/45(60%) / terms 20/45(44%) / faq-page 17/45(38%) / sitemap-page 11/45(24%) / comparison-page 7/45(16%) / tag-archive 5/45(11%) / author-page 4/45(9%) / date-archive 4/45(9%) / glossary 4/45(9%) / other:page-download 3/45(7%) / other:page-blog-medical 2/45(4%) / other:page-quote 2/45(4%) / other:page-seminar 2/45(4%) / other:page-trial 2/45(4%) / search-results 2/45(4%) / other:page-application-ao 1/45(2%) / other:page-award 1/45(2%) / other:page-blog-column 1/45(2%) / other:page-blog-diary 1/45(2%) / other:page-brand-promise 1/45(2%) / other:page-cases 1/45(2%) / other:page-download-contact 1/45(2%) / other:page-landing-specialized 1/45(2%) / other:page-members-staff 1/45(2%) / other:page-program 1/45(2%) / other:page-reservation-demo 1/45(2%) / other:page-reservation-opencampus 1/45(2%) / other:page-reservation-visit 1/45(2%) / other:page-technology 1/45(2%)。
- **parts_present**（複数選択、n=45）: slider 28/45(62%) / back-to-top 27/45(60%) / mega-menu 26/45(58%) / sns-embed 26/45(58%) / sticky-bar 23/45(51%) / accordion 21/45(47%) / breadcrumb 20/45(44%) / pagination-numbers 15/45(33%) / tabs 13/45(29%) / announce-bar 10/45(22%) / lang-switch 8/45(18%) / video-embed 6/45(13%) / map-embed 5/45(11%) / modal 5/45(11%) / other:link-reservation-external 5/45(11%) / other:newsletter-form 5/45(11%) / image-lightbox 4/45(9%) / rating-stars 4/45(9%) / other:hours-table 3/45(7%) / other:sec-client-logos 3/45(7%) / other:archive-monthly 2/45(4%) / other:calculator 2/45(4%) / other:link-reservation-messaging-app 2/45(4%) / other:sec-campaign-banners 2/45(4%) / other:sec-category-menu 2/45(4%) / other:sec-cta-repeat 2/45(4%) / other:sec-partner-list 2/45(4%) / other:sponsored-label 2/45(4%) / other:tag-cloud 2/45(4%) / popup 2/45(4%) / cookie-banner 1/45(2%) / other:archive-select 1/45(2%) / other:calendar-monthly 1/45(2%) / other:calendar-yearly 1/45(2%) / other:filter-industry 1/45(2%) / other:filter-news-category 1/45(2%) / other:filter-panel 1/45(2%) / other:filter-size 1/45(2%) / other:footer-link-groups 1/45(2%) / other:gallery-scroll 1/45(2%) / other:guarantee-flow-diagram 1/45(2%) / other:link-application-external 1/45(2%) / other:link-auto-phone-reception 1/45(2%) / other:link-blogs-multi 1/45(2%) / other:link-bulk-purchase 1/45(2%) / other:link-community 1/45(2%) / other:link-csr-nav 1/45(2%) / other:link-ebook-external 1/45(2%) / other:link-external-sites 1/45(2%) / other:link-hashtag-nav 1/45(2%) / other:link-helpcenter-external 1/45(2%) / other:link-newsletter-external 1/45(2%) / other:link-pdf-catalog 1/45(2%) / other:link-press-mail 1/45(2%) / other:link-recruit-buttons 1/45(2%) / other:link-recruit-external 1/45(2%) / other:link-rss 1/45(2%) / other:link-video-channel 1/45(2%) / other:login-selector 1/45(2%) / other:map-locations 1/45(2%) / other:nav-category-multilevel 1/45(2%) / other:nav-numbered 1/45(2%) / other:nav-product-category 1/45(2%) / other:nav-product-hierarchy 1/45(2%) / other:package-selector 1/45(2%) / other:score-graph 1/45(2%) / other:sec-ad-info 1/45(2%) / other:sec-author-profile 1/45(2%) / other:sec-books 1/45(2%) / other:sec-case-cards 1/45(2%) / other:sec-creator-picks 1/45(2%) / other:sec-culture 1/45(2%) / other:sec-doctor-intro 1/45(2%) / other:sec-donation-options 1/45(2%) / other:sec-download 1/45(2%) / other:sec-download-cards 1/45(2%) / other:sec-editorial-members 1/45(2%) / other:sec-enterprise 1/45(2%) / other:sec-event-banners 1/45(2%) / other:sec-events 1/45(2%) / other:sec-expert-profile 1/45(2%) / other:sec-feature 1/45(2%) / other:sec-feature-collection 1/45(2%) / other:sec-feature-series 1/45(2%) / other:sec-features 1/45(2%) / other:sec-guarantee 1/45(2%) / other:sec-info-cards 1/45(2%) / other:sec-jobs-promo 1/45(2%) / other:sec-jobs-site 1/45(2%) / other:sec-magazine 1/45(2%) / other:sec-media-coverage 1/45(2%) / other:sec-member-tier-categories 1/45(2%) / other:sec-membership-donation 1/45(2%) / other:sec-messaging-app-guide 1/45(2%) / other:sec-new-posts 1/45(2%) / other:sec-new-products 1/45(2%) / other:sec-news-by-department 1/45(2%) / other:sec-owner-profile 1/45(2%) / other:sec-pickup 1/45(2%) / other:sec-pickup-cards 1/45(2%) / other:sec-podcast 1/45(2%) / other:sec-popular-fixed 1/45(2%) / other:sec-product-catalog 1/45(2%) / other:sec-product-grid 1/45(2%) / other:sec-program-compare 1/45(2%) / other:sec-program-tabs 1/45(2%) / other:sec-progressive-disclosure 1/45(2%) / other:sec-ranking-access 1/45(2%) / other:sec-ranking-original 1/45(2%) / other:sec-ranking-top10 1/45(2%) / other:sec-ranking-weekly 1/45(2%) / other:sec-related-service 1/45(2%) / other:sec-reviews 1/45(2%) / other:sec-reviews-expert 1/45(2%) / other:sec-school-notice 1/45(2%) / other:sec-series 1/45(2%) / other:sec-stats-cards 1/45(2%) / other:sec-stats-results 1/45(2%) / other:sec-story-character 1/45(2%) / other:sec-studio-select 1/45(2%) / other:sec-supervisor-profile 1/45(2%) / other:sec-supporter-banner 1/45(2%) / other:sec-testimonial-slider 1/45(2%) / other:sec-value-proposition 1/45(2%) / other:sec-video-demo 1/45(2%) / other:sec-writer-profile 1/45(2%) / other:store-search 1/45(2%) / other:tags-multi 1/45(2%) / other:video-controls 1/45(2%) / search-suggest 1/45(2%) / table-of-contents 1/45(2%)。
- 語彙外（other:*）は notes から行ごとに手で符号化したもので、上の 2 行に含めて数えている（page- = 独立ページ、sec- = 区間、link- = 導線、接頭辞なし = 部品）。§3 末尾の集約はこの other:* を Claude がまとめたもの。

### 3a. 試作 03 との突き合わせ（左 = 観察の出現率、右 = 試作 03 の現状 = **段 10 着手前**の状態。現状の列は Claude が試作の軸 / pattern / template から転記した固定表で、段 10 で共通サイドバーとメガメニュー等を追加した後は変わる）

| 面 | 観察（HP から見えた） | 試作 03 の現状 |
|---|---|---|
| search-results | 2/45(4%) | 無し（templates に search.html 無し、index.html が受ける） |
| tag-archive | 5/45(11%) | 無し（archive.html が受けるが専用の型なし） |
| author-page | 4/45(9%) | 無し |
| date-archive | 4/45(9%) | 無し |
| thanks-page | 0/45(0%) | 無し |
| privacy | 37/45(82%) | 無し（通常固定ページで代用） |
| terms | 20/45(44%) | 無し（同上） |
| sitemap-page | 11/45(24%) | 無し |
| faq-page | 17/45(38%) | 部分（FAQ パーツはあるが面としては無し） |
| recruit-page | 27/45(60%) | 部分（recruit 区間 / LP で代用） |
| company-page | 38/45(84%) | 部分（会社概要の表パーツ） |
| contact-page | 42/45(93%) | 部分（HP の問い合わせ区間。独立ページの型なし） |
| news-archive | 38/45(84%) | 部分（カテゴリ面で代用） |
| glossary | 4/45(9%) | 無し |
| comparison-page | 7/45(16%) | 部分（比較記事の型） |
| 404 | 0/45(0%) | 有り（3 型） |

| パーツ | 観察 | 試作 03 の現状 |
|---|---|---|
| breadcrumb | 20/45(44%) | 無し |
| mega-menu | 26/45(58%) | 無し（ヘッダー 9 型にメガメニュー無し） |
| tabs | 13/45(29%) | 部分（お知らせタブ / 目次 / コア Tabs） |
| accordion | 21/45(47%) | 有り（FAQ / スケジュール） |
| modal | 5/45(11%) | 部分（Q&A モーダル） |
| cookie-banner | 1/45(2%) | 無し |
| announce-bar | 10/45(22%) | 有り（header announce） |
| lang-switch | 8/45(18%) | 無し |
| font-size-switch | 0/45(0%) | 無し |
| chat-widget | 0/45(0%) | 無し |
| popup | 2/45(4%) | 無し |
| sticky-bar | 23/45(51%) | 有り（sticky header / SP 下部バー / 追尾 CTA） |
| back-to-top | 27/45(60%) | 有り |
| pagination-numbers | 15/45(33%) | 有り |
| infinite-scroll | 0/45(0%) | 無し（load-more はあり） |
| comments | 0/45(0%) | 無し |
| rating-stars | 4/45(9%) | 有り（レビュー星） |
| video-embed | 6/45(13%) | 部分（hero video の枠） |
| map-embed | 5/45(11%) | 有り（段 7） |
| sns-embed | 26/45(58%) | 有り（段 8 SNS フィード） |
| dark-toggle | 0/45(0%) | 不採用（PO 決定） |
| search-suggest | 1/45(2%) | 無し |
| reading-progress | 0/45(0%) | 無し |
| table-of-contents | 1/45(2%) | 有り（4 型） |
| image-lightbox | 4/45(9%) | 無し |
| slider | 28/45(62%) | 有り（hero / 関連 / カルーセル） |
| countdown | 0/45(0%) | 有り（段 8） |
| sidebar（語彙外・未集計。§1 で別途観察） | 未集計 | 部分（カテゴリ面のみ。段 10 で記事 / 固定ページ / HP へ） |

- **語彙に無い観察（notes から、Claude が集約）**: 料金 / 計算シミュレータ、教室・店舗検索（郵便番号 / エリア）、診療時間表・診療カレンダー、返金保証のフロー図、成果の数値表示（スコア推移グラフ）、ユーザーレビュー + 専門家評価、キャンペーンバナー群、寄付 / 支援（会員・月額）、スポンサード記事の明示、監修者 / ライター紹介（顔写真）、タグクラウド、月別アーカイブのドロップダウン、複数製品のログイン一覧、PDF カタログ直リンク、デジタルパンフ（外部）、外部フォームサービスへの予約導線（クリニック / 学校に多い）、メインビジュアルの再生 / 停止ボタン。

## 4. 限界

- すべて WebFetch の本文要約経由。追尾・SP 表示・JS で出る要素（ポップアップ / cookie 同意 / チャット）は要約に出にくく**過小評価**の可能性が高い。faces / parts は「トップから見えた範囲」で、サイト内に存在しても見えないものは数えていない。
- トップの要約が挙げたフォーム URL が実在しないことがあり（B 区分で 5 件 404）、失敗として記録した。フォームの項目も要約が部分的なことがある（na）。
- 区分の n は小さい（形式は同じでも母集団が違う）。既定値の根拠にはせず、「型を全部持つ」（WT-EVT-0288）の材料として使う。

## 5. 試作への持ち込み方（Claude 案、PO 確認前提。WT-EVT-0288「最大数を取りにいく」に従い観察した型は全部入れる）

- **サイドバー（段 10、観察由来）**: 軸 `side_layout`（観察 4 値: none / right / left / both）、`side_sticky`（観察 4 値: none / whole / last-widget / toc-only）、`side_set`（区分別の観察順から: media = C、blog = P、owned = B、minimal = ポータル最小）、`side_nav`（観察 6 値: none / mega-menu-dropdown / fixed-left-nav / fixed-right-icons / drawer-from-hamburger(pc) / toc-side-only）。ウィジェットは観察された 18 種（popular-ranking / categories / search / new-posts / cta-banner / related-posts / toc-sticky / ad / tags / profile / archive / contact-box / banner-stack / sns-follow / newsletter / recruit / event-list / other:toc-dropdown）。
- **サイドバー（未観察 / 少数観察の追加提案、Claude 案）**: `side_sp` は要約でほぼ判定できず（観察は drawer 1 件のみ、below-content / hidden は 0 件）。前回台帳 article/sp の none 93% を根拠に 3 型を持つ。語彙にあるが観察ゼロのウィジェット（calendar / tel-box）、固定ページ / HP 向けの corporate セット（観察の corporate 系は少数）、カテゴリ面 3 型との統合。
- **フォーム（段 11、観察由来）**: 軸 `form_fields`（観察の頻出項目から minimal = 名前 + メール + 本文 / standard = + 会社 + 電話 + 種別 / full = + かな + 郵便番号 + 住所 + 添付 / by-kind = 種別ごとの観察セット）、`form_required`（観察 2 値: asterisk / 必須ラベル）、`form_layout`（観察 3 値: 1col / 2col / steps）、`form_confirm`（観察: 確認画面あり / なし）、`form_consent`（観察: チェックボックス / リンクのみ / 送信ボタン文言に含める）、`form_submit`（観察の文言: 送信 / 送信する / 確認する / 確認画面へ / 同意して送信する / ダウンロード / 次へ進む）、`form_error`（観察 2 値: 項目下 / 上部まとめ）、`form_captcha`（観察: なし / 簡易な質問型 / 外部型の枠）、`form_side`（観察: tel / messaging-app / chat / email / none）。種別は観察された 8 種（contact / download / newsletter / other:diagnosis / quote / recruit / reservation / trial。reservation は第 1〜3 希望日時、other:diagnosis は yes-no の多段）。いずれも PoC は送信しない。
- **フォーム（未観察の追加提案、Claude 案）**: 種別 apply（語彙にあるが本体観察 0 件。イベントの申込は段 5 で既存）、`form_layout` の label-left、`form_confirm` の inline-review（送信前に同一ページで見直す型）。**少数観察**（未観察ではない）: 完了ページ separate 2 件。
- **抜け漏れ（段 12 以降、観察由来 = HP から 1 件以上見えた面・パーツで試作が「無し / 部分」のもの）**: 面 = search-results / tag-archive / author-page / date-archive / privacy / terms / sitemap-page / faq-page / recruit-page / company-page / contact-page / news-archive / glossary / comparison-page。パーツ = breadcrumb / mega-menu / tabs / modal / cookie-banner / lang-switch / popup / video-embed / search-suggest / image-lightbox。語彙外で notes に出た型（§3 末尾）: 料金 / 計算シミュレータ、店舗・教室検索、診療時間表・カレンダー、保証フロー図、スコア推移グラフ、レビュー + 専門家評価、寄付・支援、スポンサード表示、ライター紹介、タグクラウド、アーカイブ選択、動画の再生停止。
- **抜け漏れ（未観察の追加提案、Claude 案。語彙にあるが今回 0 件）**: 面 = thanks-page。パーツ = font-size-switch / chat-widget / infinite-scroll / comments / reading-progress。
