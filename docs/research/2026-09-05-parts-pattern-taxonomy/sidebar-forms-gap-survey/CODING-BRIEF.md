# research-r21 コーディング要領（サイドバー / フォーム / 抜け漏れ）— PO 指示 WT-EVT-0289〜0291（2026-09-06）

取得: WebFetch の本文要約経由（1 ページ 1 回、PC 表示相当）。固有名・URL は mapping.json のみ（リポ外）。observations は id + 語彙のみ。
語彙に無い型は other:<短い説明>、見えない/判定不能は na、該当なし none。複数選択は "," 区切り。

## sidebar（記事面・固定ページ・HP の右/左カラムとサイドナビ）
- page_kind: article | page | home | category
- side_layout: none | right | left | both
- side_width: narrow(<=280) | standard(300-340) | wide(>=360) | na（要約では判定できないため本収集では全行 na = 集計対象外）
- side_sticky: none | whole | last-widget | toc-only | na
- side_widgets（複数、上から順）: search | profile | categories | popular-ranking | new-posts | tags | cta-banner | toc-sticky | newsletter | sns-follow | archive | calendar | ad | related-posts | event-list | contact-box | tel-box | banner-stack | recruit | none
- side_sp: below-content | drawer | hidden | tabs | na
- side_nav（サイドナビ = 画面端の固定メニュー）: none | fixed-left-nav | fixed-right-icons | drawer-from-hamburger(pc) | mega-menu-dropdown | toc-side-only | other:*
- notes

## forms（問い合わせ / 申込 / 資料請求 / 予約 / ニュースレター / 採用）
- form_kind: contact | apply | download | reservation | newsletter | recruit | quote | trial | other:*
- form_presence（集計用）: observed = フォーム本体を観察 | absent = フォームが無いと確認（電話 / メール案内、ハブ、外部リンクのみ） | unknown = 有無を判定できない（要約が部分的、埋め込み欄が描画されない） | na = 取得失敗
- fields（複数、順に）: name | name-kana | company | department | position | email | email-confirm | tel | postal | address | url | subject-select | subject-radio | message | attachment | date-pref | time-pref | people-count | budget-select | how-found | newsletter-optin | consent-checkbox | privacy-link | captcha | honeypot | other:*
- required_mark: asterisk | 必須-label | color-only | none | na
- layout: 1col | 2col | label-left | placeholder-only | steps | na
- confirm_page: yes | no | inline-review | na
- thanks_page: separate | inline | na
- submit_text: 送信 | 送信する | 確認画面へ | 申し込む | 問い合わせる | 登録する | other:*
- error_display: inline-under-field | top-summary | both | none-visible | na
- external_service: none | embedded-form-service | link-to-external | na（サービス名は書かない）
- cta_side: tel | messaging-app | chat | email | none（フォーム横の代替導線。第三者サービス名は書かない）
- notes

## gap（試作 03 に無い面・パーツの棚卸し。HP + サイト全体の導線から観察）
- faces_present（複数）: search-results | tag-archive | author-page | date-archive | thanks-page | privacy | terms | sitemap-page | faq-page | recruit-page | company-page | contact-page | news-archive | glossary | comparison-page | maintenance | 404 | other:*
- parts_present（複数）: breadcrumb | mega-menu | tabs | accordion | modal | cookie-banner | announce-bar | lang-switch | font-size-switch | chat-widget | popup | exit-intent | sticky-bar | back-to-top | pagination-numbers | infinite-scroll | breadcrumb-schema | comments | rating-stars | video-embed | map-embed | sns-embed | print-css | dark-toggle | search-suggest | reading-progress | table-of-contents | image-lightbox | slider | countdown | notification-badge | other:*
- notes（試作に無いと気づいた型）。notes に書いた面・パーツは、既存語彙で表せるものは既存語彙、表せないものだけ other:* で必ず符号化する（9 巡目の是正: 行ごとに手で付与）。判定基準: 独立した URL を持つ面 = faces_present の other:page-<意味>、ページ内の区間 = parts_present の other:sec-<意味>、外部・別ページへの導線 = other:link-<意味>、部品そのもの（計算機・表・グラフ・検索欄など）= other:<意味>。人物紹介の区間（other:sec-*-profile）と会員制度のページ（other:page-membership-*）は別語。

観察は WebFetch 要約に依存する（JS で出る要素・SP 表示は未検証）。

命名規則: department は会社の部署（正規語彙）、学校の学科・学部は other:faculty。other:<意味> とし入力形式（-select / -radio 等）は付けない（例 other:gender / other:grade / other:industry）。fields の「項目数」は符号化したトークン数で、実際の入力欄数ではない（複数の yes/no を 1 トークンに圧縮した行がある）。
