# HP・イベントページ デザイン観察 サマリー v2（2026-09-06、research-r19。gen_summary.py の全生成）

固有名・URL の対応表は別管理（リポ外）で、本ディレクトリには含めない。本ファイルは `python3 gen_summary.py > summary.md` で observations-home.json / observations-event.json から**全生成**する（手計算値を残さない）。PO 反応 19 回目（WT-EVT-0287「homeとイベントはもっとバリエーションを出して。調査不足じゃない？…固定ページ継投で使えるパーツをしっかりと作りこむこと」）を受けた再収集。前回台帳（home-event-recapture）の**追加ではなく独立した再収集**として扱う。対応表の URL 照合で前回と同一 URL だった HP 9 件（H26=前回 H38, H27=前回 H42, H28=前回 H39, H29=前回 H40, H33=前回 H16, H34=前回 H17, H35=前回 H20, H40=前回 H25, H64=前回 H19）は `prev_dup` を付けて**HP の集計対象から除外**した（一覧は本段落のとおり。§1 は件数のみ）。イベントは同一 URL なし（同一ホストの別ページが 5 件あるが、ページが異なるため採用）。前回台帳の値は ../home-event-recapture/observations-*.json（前回台帳）から算出する（集計方法（分母・欠測除外）は共通、主集計の採用条件は各台帳の定義に従う。§0 参照）。id は台帳ごとの別体系。

## 0. 用語・分母・ラベルの定義

語彙コードの注記: contact_band=messaging-app・fixed=float-tel 等は導線の種類を表す語彙コードで、第三者サービス名としての記述ではない。`other:*` は語彙外の観察で、集計の分母には含める。原則として型の候補にはしない。応募方法の 3 語（receipt-upload / postcard / messaging-app）は当初 other:* で記録したが、PO 決定 WT-EVT-0288（2026-09-06「全部追加」）で語彙へ正式採用したため、本台帳では other: 接頭辞なしで集計する。**apply は複数選択**（複数の応募経路があるページは全経路を列挙し、代表経路を選ばない）。header/footer の語彙統合: signin→login、badge / cert-badges→badges、cert-logo→cert。footer の hours-table は営業時間・診療時間を**表**で示すもの、hours は 1〜2 行の文字表記。

- **na**: 取得できた（fetched:true）が、その項目の値を本文要約から判定できない。**fetched:false**: 取得失敗（どの集計にも含めない。HTTP 403/404・名前解決失敗・証明書不一致・本文空）。
- 分母: HP は取得件数から prev_dup を除いた集計対象、イベントは主集計（page_kind = `individual-event-or-campaign`）。na がある項目は欠測除外後の分母を行内に明記する。
- ラベル 3 種: **観察事実**（件数と%）／**Claude 案**（解釈・仮説）／**暫定既定値**（試作へ持ち越す候補、PO 確認前提）。小標本（n≤5）の最多型は既定値化の根拠にしない。
- 取得方法: WebFetch による本文要約経由の観察（1 ページ 1 回）。実ブラウザの目視・SP 表示は未検証。hero の型は要約の記述から判定したもので、`slider` と `cards-carousel` の境界（写真の横送りか、カード状の横送りか）は要約の表現に依存する。

### site_pattern 判定基準
- HP: A=製造/士業/医療（中小企業コーポレート）、B=SaaS・サービスのトップ、C=オウンドメディア・比較/ランキングメディア、D=店舗・自由診療クリニック・スクール・チェーン、E=学校法人・大学・団体。
- イベント: A=セミナー/ウェビナー個別申込、B=展示会・カンファレンス、C=地域フェス・祭り・花火、D=期間限定キャンペーン・コンテスト・学校の参加型催事（オープンキャンパス）。

### page_kind（イベントのみ）と主集計定義
主集計 = 「来場者・参加希望者が**そのページから応募/申込を行う**個別ページ」。募集終了後に取得したページは、**申込導線の位置、または同一ページ内の終了表示**（closed-notice / ended バッジ）を確認できたものを採用する。前回台帳の条件は「申込位置の終了表示」のみで、本台帳は同一ページの status バッジも根拠として認める（条件の拡張。該当は status-badge の行数で示す）。根拠は各行の `closed_evidence`（apply-position = 申込位置の受付終了 / アーカイブ・オンデマンド案内、status-badge = 同一ページの終了バッジのみ）で、どちらも無い終了ページは `ended-apply-evidence-insufficient` として除外する。出店者・出演者向けの募集ページは前回と同じく `performer-facing-not-visitor-apply` として除外する。除外（別掲）:
主集計で status_badge=ended の 13 件の closed_evidence: apply-position 12 / status-badge 1。
- E10 `portal-listing`: 102 講座の一覧型（主集計から除外）
- E11 `performer-facing-not-visitor-apply`: 月例マルシェ、来場は申込不要（出店申込のみ）。来場は申込不要（出店申込のみ）のため前回台帳と同じ条件で主集計から除外
- E12 `portal-listing`: 出店者向け複数イベント一覧（主集計から除外）
- E14 `portal-listing`: 複数プログラムのハブ（主集計から除外）
- E32 `ended-apply-evidence-insufficient`: 募集期間 6/1〜7/31。ポータル側は「募集終了」表示、本文要約では判定できず na。終了表示を同一ページで確認できないため主集計から除外
- E41 `portal-listing`: 種別 × 日程のカレンダー一覧（主集計から除外）

## 1. 件数・fetched 内訳

### HP 76 件
- fetched:true 71 / fetched:false 5（H21, H45, H57, H61, H63）。
- fetched:true のうち前回台帳と同一 URL（prev_dup）9 件を除外 → **集計対象 n=62**。
- site_pattern 内訳（集計対象 62）: A=26 / B=9 / C=7 / D=12 / E=8。

### イベント 54 件
- fetched:true 46 / fetched:false 8（E05, E08, E17, E18, E19, E20, E39, E48）。
- 取得 46 件のうち page_kind 除外 6 件 → **主集計（個別募集ページ）n=40**。site_pattern 内訳: A=26 / B=4 / C=2 / D=8。

## 2. HP: 型内訳（観察事実。集計対象集合 = prev_dup 除外後 n=62）

- **hero**（集計対象 n=62、欠測 na 1 件を除いた分母 61）: fullbleed-photo-overlay 21/61(34%) / slider 17/61(28%) / cards-carousel 7/61(11%) / text-only 6/61(10%) / article-grid 4/61(7%) / product-shot 4/61(7%) / search-box 1/61(2%) / split-text-image 1/61(2%)〔欠測込み n=62: fullbleed-photo-overlay 21/62(34%) / slider 17/62(27%) / cards-carousel 7/62(11%) / text-only 6/62(10%) / article-grid 4/62(6%) / product-shot 4/62(6%) / search-box 1/62(2%) / split-text-image 1/62(2%) / na 1/62(2%)〕
- **hero_cta**（集計対象 n=62）: double 27/62(44%) / none 15/62(24%) / single 15/62(24%) / tel-button 3/62(5%) / search 2/62(3%)
- **news**（集計対象 n=62）: list-with-date 24/62(39%) / cards 22/62(35%) / none 12/62(19%) / tabs 4/62(6%)
- **contact_band**（集計対象 n=62）: form-only 19/62(31%) / tel+form 16/62(26%) / tel-only 10/62(16%) / none 9/62(15%) / double-cta 5/62(8%) / messaging-app 3/62(5%)
- **fixed**（複数選択。集計対象 n=62、fixed=na 0 件を除いた分母 62）: sticky-header 60/62(97%) / float-tel 32/62(52%) / sp-bottom-bar 7/62(11%) / float-cta 3/62(5%) / none 1/62(2%)
- **header**（複数選択。集計対象 n=62、header=na 11 件を除いた分母 51）: logo-left-nav-right 47/51(92%) / with-cta 17/51(33%) / lang 9/51(18%) / login 7/51(14%) / dropdown 6/51(12%) / with-tel 6/51(12%) / search 3/51(6%) / two-rows 3/51(6%) / download 2/51(4%) / with-sns 2/51(4%) / accordion-menu 1/51(2%) / account 1/51(2%) / app 1/51(2%) / brand-submenu 1/51(2%) / cart 1/51(2%) / catalog 1/51(2%) / categories 1/51(2%) / dual-brand 1/51(2%) / icons 1/51(2%) / locations 1/51(2%) / logo 1/51(2%) / logo-center 1/51(2%) / logo-hamburger 1/51(2%) / logo-minimal 1/51(2%) / parent-link 1/51(2%) / products 1/51(2%) / recruit-entry 1/51(2%) / signup 1/51(2%) / store 1/51(2%) / store-search 1/51(2%) / visitor-types 1/51(2%)〔欠測込み n=62: logo-left-nav-right 47/62(76%) / with-cta 17/62(27%) / lang 9/62(15%) / login 7/62(11%) / dropdown 6/62(10%) / with-tel 6/62(10%) / search 3/62(5%) / two-rows 3/62(5%) / download 2/62(3%) / with-sns 2/62(3%) / accordion-menu 1/62(2%) / account 1/62(2%) / app 1/62(2%) / brand-submenu 1/62(2%) / cart 1/62(2%) / catalog 1/62(2%) / categories 1/62(2%) / dual-brand 1/62(2%) / icons 1/62(2%) / locations 1/62(2%) / logo 1/62(2%) / logo-center 1/62(2%) / logo-hamburger 1/62(2%) / logo-minimal 1/62(2%) / parent-link 1/62(2%) / products 1/62(2%) / recruit-entry 1/62(2%) / signup 1/62(2%) / store 1/62(2%) / store-search 1/62(2%) / visitor-types 1/62(2%) / na 11/62(18%)〕
- **footer**（複数選択。集計対象 n=62、footer=na 11 件を除いた分母 51）: columns 44/51(86%) / with-sns 28/51(55%) / single-row 7/51(14%) / hours-table 4/51(8%) / badges 3/51(6%) / lang 3/51(6%) / cert 2/51(4%) / hours 2/51(4%) / offices 2/51(4%) / app 1/51(2%) / banners 1/51(2%) / brand-sites 1/51(2%) / brands 1/51(2%) / group-media 1/51(2%) / partner-logos 1/51(2%) / pdf-download 1/51(2%) / totop 1/51(2%)〔欠測込み n=62: columns 44/62(71%) / with-sns 28/62(45%) / single-row 7/62(11%) / hours-table 4/62(6%) / badges 3/62(5%) / lang 3/62(5%) / cert 2/62(3%) / hours 2/62(3%) / offices 2/62(3%) / app 1/62(2%) / banners 1/62(2%) / brand-sites 1/62(2%) / brands 1/62(2%) / group-media 1/62(2%) / partner-logos 1/62(2%) / pdf-download 1/62(2%) / totop 1/62(2%) / na 11/62(18%)〕
- **先頭セクション**（hero の直後、集計対象 n=62）: banner-row 11/62(18%) / news 11/62(18%) / greeting 10/62(16%) / cta-band 5/62(8%) / features 5/62(8%) / category-cards 3/62(5%) / contact-band 3/62(5%) / logos/clients 3/62(5%)。
- **セクションの出現ページ数**（`other:*` を除く、集計対象 n=62）: contact-band 52/62(84%) / features 42/62(68%) / news 42/62(68%) / service-cards 42/62(68%) / banner-row 27/62(44%) / greeting 25/62(40%) / products 17/62(27%) / recruit 17/62(27%) / works/cases 17/62(27%) / blog-latest 16/62(26%) / company-info 16/62(26%) / cta-band 13/62(21%) / courses 12/62(19%) / faq 11/62(18%) / voice/testimonial 11/62(18%) / access/map 10/62(16%) / numbers 10/62(16%) / logos/clients 9/62(15%) / sns-feed 8/62(13%) / article-grid 7/62(11%) / events/open-campus 7/62(11%) / price 7/62(11%) / category-cards 6/62(10%) / download 6/62(10%) / pickup 6/62(10%) / stores 6/62(10%) / ranking 5/62(8%) / contact-form 4/62(6%) / events/seminar 3/62(5%) / flow/steps 3/62(5%)。
- **セクション数**（1 ページあたり、集計対象 n=62）: 中央値 8、最小 3、最大 13。

### 区分別の値（観察事実。区分 n が 7〜26 で、1 件で 4〜14 ポイント動く）

- A（n=26）: hero 上位 3 語 fullbleed-photo-overlay 10/26(38%) / slider 8/26(31%) / text-only 4/26(15%)。セクション上位 contact-band 26/26(100%) / news 22/26(85%) / service-cards 20/26(77%) / features 17/26(65%) / greeting 17/26(65%) / company-info 12/26(46%) / products 12/26(46%) / blog-latest 11/26(42%)。hero_cta=double 7/26(27%) / contact_band=form-only 8/26(31%) / float-tel 13/26(50%) / sp-bottom-bar 1/26(4%)。
- B（n=9）: hero 上位 3 語 fullbleed-photo-overlay 5/9(56%) / product-shot 3/9(33%) / cards-carousel 1/9(11%)。セクション上位 features 9/9(100%) / contact-band 8/9(89%) / service-cards 8/9(89%) / numbers 6/9(67%) / works/cases 6/9(67%) / banner-row 5/9(56%) / download 4/9(44%) / logos/clients 4/9(44%)。hero_cta=double 9/9(100%) / contact_band=form-only 3/9(33%) / float-tel 3/9(33%) / sp-bottom-bar 1/9(11%)。
- C（n=7）: hero 上位 3 語 article-grid 4/7(57%) / cards-carousel 2/7(29%) / search-box 1/7(14%)。セクション上位 article-grid 7/7(100%) / banner-row 6/7(86%) / category-cards 6/7(86%) / pickup 6/7(86%) / ranking 5/7(71%) / newsletter 3/7(43%) / tags 3/7(43%) / cta-band 2/7(29%)。hero_cta=double 0/7(0%) / contact_band=form-only 3/7(43%) / float-tel 0/7(0%) / sp-bottom-bar 0/7(0%)。
- D（n=12）: hero 上位 3 語 slider 5/11(45%) / fullbleed-photo-overlay 4/11(36%) / cards-carousel 1/11(9%)（欠測 na 1 件を除いた分母 11。欠測込み n=12: slider 5/12(42%) / fullbleed-photo-overlay 4/12(33%) / na 1/12(8%)）。セクション上位 service-cards 12/12(100%) / contact-band 10/12(83%) / features 9/12(75%) / news 9/12(75%) / banner-row 7/12(58%) / courses 6/12(50%) / faq 6/12(50%) / recruit 6/12(50%)。hero_cta=double 7/12(58%) / contact_band=form-only 3/12(25%) / float-tel 10/12(83%) / sp-bottom-bar 3/12(25%)。
- E（n=8）: hero 上位 3 語 slider 4/8(50%) / fullbleed-photo-overlay 2/8(25%) / text-only 1/8(12%)。セクション上位 contact-band 8/8(100%) / events/open-campus 7/8(88%) / features 7/8(88%) / news 7/8(88%) / courses 6/8(75%) / sns-feed 5/8(62%) / banner-row 4/8(50%) / cta-band 4/8(50%)。hero_cta=double 4/8(50%) / contact_band=form-only 2/8(25%) / float-tel 6/8(75%) / sp-bottom-bar 2/8(25%)。

## 3. イベント: 型内訳

### 3a. 主集計（個別募集ページ n=40、site_pattern A26 / B4 / C2 / D8）

- **hero**（主集計 n=40）: date-place-block 25/40(62%) / text-only 8/40(20%) / key-visual-only 4/40(10%) / photo-overlay 3/40(8%)
- **info_block**（主集計 n=40）: icon-list 22/40(55%) / inline-text 9/40(22%) / table 9/40(22%)
- **apply**（複数選択。主集計 n=40、apply=na 0 件を除いた分母 40）: external-form 18/40(45%) / closed-notice 13/40(32%) / ticket-service-link 6/40(15%) / postcard 3/40(8%) / receipt-upload 3/40(8%) / messaging-app 2/40(5%)
- **schedule**（主集計 n=40）: date-place-block 32/40(80%) / date-place-block-in-hero 4/40(10%) / table 3/40(8%) / accordion 1/40(2%)
- **status_badge**（主集計 n=40）: open 26/40(65%) / ended 13/40(32%) / none 1/40(2%)
- **speakers**（主集計 n=40）: list 12/40(30%) / single-profile 11/40(28%) / cards-photo 9/40(22%) / none 8/40(20%)
- **countdown**（主集計 n=40）: none 40/40(100%)
- **map**（主集計 n=40）: none 23/40(57%) / text-only 12/40(30%) / static-image 4/40(10%) / embed 1/40(2%)
- **fixed**（主集計 n=40）: none 24/40(60%) / sp-bottom-bar 16/40(40%)
- **share**（主集計 n=40）: none 29/40(72%) / icons 11/40(28%)
- **セクションの出現ページ数**（`other:*` を除く、主集計 n=40）: overview 40/40(100%) / notes/terms 34/40(85%) / organizer 34/40(85%) / program-detail 30/40(75%) / schedule 29/40(72%) / apply/register 27/40(68%) / speakers 27/40(68%) / target-audience 18/40(45%) / info 16/40(40%) / access/map 14/40(35%) / tickets/price 13/40(32%) / faq 9/40(22%) / sponsors 7/40(18%) / entry-steps 5/40(12%) / prizes 5/40(12%) / target-products 4/40(10%) / news 3/40(8%) / judges 1/40(2%) / participant-feedback 1/40(2%) / past-events 1/40(2%)。
- **先頭セクション**（主集計 n=40）: overview 40/40(100%)。

### 3b. 参考: 取得全体（n=46、page_kind 除外 6 件を含む。主集計の値ではない）

- **hero**（取得件数 n=46）: date-place-block 26/46(57%) / text-only 11/46(24%) / photo-overlay 5/46(11%) / key-visual-only 4/46(9%)
- **info_block**（取得件数 n=46）: icon-list 25/46(54%) / table 11/46(24%) / inline-text 10/46(22%)
- **apply**（複数選択。取得件数 n=46、apply=na 0 件を除いた分母 46）: external-form 24/46(52%) / closed-notice 13/46(28%) / ticket-service-link 6/46(13%) / postcard 3/46(7%) / receipt-upload 3/46(7%) / messaging-app 2/46(4%)

### 3c. 区分別（主集計内、観察事実。区分 n が 2〜26。n≤5 の区分は断定しない）

- A（n=26）: hero 上位 3 語 date-place-block 20/26(77%) / text-only 6/26(23%)。apply（複数選択、全語） external-form 12/26(46%) / closed-notice 11/26(42%) / ticket-service-link 3/26(12%)。status 上位 3 語 open 15/26(58%) / ended 10/26(38%) / none 1/26(4%)。セクション上位 overview 26/26(100%) / notes/terms 24/26(92%) / speakers 24/26(92%) / organizer 22/26(85%) / program-detail 22/26(85%) / apply/register 20/26(77%) / schedule 18/26(69%) / target-audience 18/26(69%)。
- B（n=4）: hero 上位 3 語 date-place-block 4/4(100%)。apply（複数選択、全語） ticket-service-link 2/4(50%) / external-form 1/4(25%) / closed-notice 1/4(25%)。status 上位 3 語 open 3/4(75%) / ended 1/4(25%)。セクション上位 organizer 4/4(100%) / overview 4/4(100%) / schedule 4/4(100%) / access/map 3/4(75%) / program-detail 3/4(75%) / speakers 3/4(75%) / sponsors 3/4(75%) / tickets/price 3/4(75%)。
- C（n=2）: hero 上位 3 語 text-only 1/2(50%) / photo-overlay 1/2(50%)。apply（複数選択、全語） closed-notice 1/2(50%) / ticket-service-link 1/2(50%)。status 上位 3 語 ended 2/2(100%)。セクション上位 access/map 2/2(100%) / faq 2/2(100%) / notes/terms 2/2(100%) / overview 2/2(100%) / program-detail 2/2(100%) / sponsors 2/2(100%) / info 1/2(50%) / news 1/2(50%)。
- D（n=8）: hero 上位 3 語 key-visual-only 4/8(50%) / photo-overlay 2/8(25%) / text-only 1/8(12%)。apply（複数選択、全語） external-form 5/8(62%) / receipt-upload 3/8(38%) / postcard 3/8(38%) / messaging-app 2/8(25%)。status 上位 3 語 open 8/8(100%)。セクション上位 overview 8/8(100%) / organizer 7/8(88%) / notes/terms 6/8(75%) / schedule 6/8(75%) / apply/register 5/8(62%) / entry-steps 5/8(62%) / prizes 5/8(62%) / target-products 4/8(50%)。

## 4. 固定ページ用パーツの棚卸し（観察事実 + Claude 案）

PO 指示「固定ページ継投で使えるパーツをしっかりと作りこむこと。記事パーツからの転用でも可」（WT-EVT-0287）に向け、HP・イベントの sections_order に現れた区間を**パーツ候補**として列挙する。出現ページ数は観察事実、右列の「記事パーツからの転用」は Claude 案（試作 03 の既存部品名。要求・設計判断ではない）。

| 区間コード | HP 出現 | イベント主集計 出現 | 記事パーツからの転用（Claude 案） |
|---|---|---|---|
| contact-band | 52/62(84%) | 0/40(0%) | cta-band（問い合わせ帯） |
| news | 42/62(68%) | 3/40(8%) | 記事一覧（お知らせ）: 既存 home-news の 3 型 |
| features | 42/62(68%) | 0/40(0%) | features / steps（特長 3 列） |
| service-cards | 42/62(68%) | 0/40(0%) | cards（サービスカード） |
| overview | 0/62(0%) | 40/40(100%) | 本文（見出し + 段落） |
| notes/terms | 0/62(0%) | 34/40(85%) | 本文（注意事項） |
| organizer | 0/62(0%) | 34/40(85%) | 表（主催） |
| program-detail | 0/62(0%) | 30/40(75%) | 本文 + numbox |
| schedule | 0/62(0%) | 29/40(72%) | event-schedule（3 型既存） |
| apply/register | 0/62(0%) | 27/40(68%) | event-apply（4 型既存） |
| banner-row | 27/62(44%) | 0/40(0%) | banner-row（home 既存） |
| speakers | 0/62(0%) | 27/40(68%) | event-speakers（3 型既存） |
| greeting | 25/62(40%) | 0/40(0%) | greeting（新規: 写真 + 挨拶文） |
| access/map | 10/62(16%) | 14/40(35%) | event-map（静止画 / 文字 / 埋め込み） |
| faq | 11/62(18%) | 9/40(22%) | faq（アコーディオン） |
| target-audience | 0/62(0%) | 18/40(45%) | list（対象者） |
| products | 17/62(27%) | 0/40(0%) | product-bundle / cards |
| recruit | 17/62(27%) | 0/40(0%) | cta-banner（採用バナー） |
| works/cases | 17/62(27%) | 0/40(0%) | cases（事例カード） |
| blog-latest | 16/62(26%) | 0/40(0%) | 記事一覧（関連 6 件と同型） |
| company-info | 16/62(26%) | 0/40(0%) | 表（会社概要）: 既存 table |
| info | 0/62(0%) | 16/40(40%) | event-info（3 型既存） |
| cta-band | 13/62(21%) | 0/40(0%) | cta-band |
| tickets/price | 0/62(0%) | 13/40(32%) | pricing / table |
| courses | 12/62(19%) | 0/40(0%) | cards（コース） |
| voice/testimonial | 11/62(18%) | 0/40(0%) | review-quote-photo（LP 口コミ） |
| numbers | 10/62(16%) | 0/40(0%) | numbers（数字） |
| logos/clients | 9/62(15%) | 0/40(0%) | logos-row（新規） |
| sns-feed | 8/62(13%) | 0/40(0%) | sns-feed（新規、遅延読込の埋め込み） |
| article-grid | 7/62(11%) | 0/40(0%) | article-grid（home 既存） |
| events/open-campus | 7/62(11%) | 0/40(0%) | event-list（新規: イベント一覧カード） |
| price | 7/62(11%) | 0/40(0%) | pricing / cta-price-tier |
| sponsors | 0/62(0%) | 7/40(18%) | logos-row（新規） |
| category-cards | 6/62(10%) | 0/40(0%) | category-cards（home 既存） |
| download | 6/62(10%) | 0/40(0%) | download（LP 資料 DL） |
| pickup | 6/62(10%) | 0/40(0%) | pickup（category 既存） |
| stores | 6/62(10%) | 0/40(0%) | stores（新規: 店舗 / 拠点カード + 検索） |
| entry-steps | 0/62(0%) | 5/40(12%) | steps |
| prizes | 0/62(0%) | 5/40(12%) | cards（賞品）（新規） |
| ranking | 5/62(8%) | 0/40(0%) | ranking（home 既存） |
| contact-form | 4/62(6%) | 0/40(0%) | lp form（非送信 PoC） |
| target-products | 0/62(0%) | 4/40(10%) | product-bundle |
| events/seminar | 3/62(5%) | 0/40(0%) | event-list |
| flow/steps | 3/62(5%) | 0/40(0%) | steps |
| hours-table | 3/62(5%) | 0/40(0%) | table（診療時間） |
| newsletter | 3/62(5%) | 0/40(0%) | newsletter（category cta 既存） |
| tags | 3/62(5%) | 0/40(0%) | tags（category 既存） |
| editors | 2/62(3%) | 0/40(0%) | author（編集部） |
| reservation | 2/62(3%) | 0/40(0%) | cta-band（予約） |
| staff | 2/62(3%) | 0/40(0%) | author / speakers cards |
| awards | 1/62(2%) | 0/40(0%) | badges（footer badges を転用） |
| history/timeline | 1/62(2%) | 0/40(0%) | timeline（新規: event schedule timeline を転用） |
| judges | 0/62(0%) | 1/40(2%) | speakers cards |
| participant-feedback | 0/62(0%) | 1/40(2%) | review-quote-photo |
| past-events | 0/62(0%) | 1/40(2%) | cards / archive-list（新規） |
| video | 1/62(2%) | 0/40(0%) | video（home hero video を転用） |

- **Claude 案（パーツ化の順序）**: 出現ページ数が HP で 30% 以上、またはイベント主集計で 50% 以上のものを第 1 群（必須パーツ）、10% 以上を第 2 群、それ未満を第 3 群（選べるが既定に入れない）とする。第 1 群の多くは記事面 / LP の既存部品を固定ページ用 pattern として再登録すれば足り、観察に基づく新規は greeting / logos-row / stores / event-list / sns-feed / timeline。
- **未観察の追加提案（棚卸し由来ではない Claude 案）**: countdown（観察 40/40(100%) が none。観察は根拠にしない）。gallery（HP の sections_order に区間なし。notes に「ギャラリー」の記述が 1 件（集計対象内）あるだけで、区間として符号化していないため未観察扱い）。

## 5. 限界

- HP は集計対象 n=62（取得 71、prev_dup 除外 9）、区分 n=7〜26。イベント主集計 n=40 だが区分 n=2〜26 で、A（セミナー）に偏る（B 展示会・C フェスは小標本）。
- 取得は WebFetch の本文要約経由で、hero の写真 / 文字の別、固定導線の SP 表示、セクションの視覚的な区切りは要約に依存する。fetched:false は無作為ではなく、大手・JS 依存サイトが落ちやすい偏りがある。
- 前回台帳（取得 n=39 / 主集計 n=8）とは prev_dup 除外後に集合が独立で、割合の差は標本差を含む。既定値の扱い（Claude 案）: 両台帳で最多の型のみ**暫定既定値**の候補にし、片方だけの最多型は「選べる型」に留める。既存の既定値（試作 03 で PO 反応を経たもの）は、この規則で置き換え候補が出ない限り据え置く。

## 6. 試作への持ち込み方（Claude 案、PO 確認前提）

- HP hero: 本台帳（欠測除外 n=61）では fullbleed-photo-overlay 21/61(34%) / slider 17/61(28%) / cards-carousel 7/61(11%) / text-only 6/61(10%)。前回台帳（前回台帳 home-event-recapture, 欠測除外 n=39）は text-only 13/39(33%) / slider 10/39(26%) / fullbleed-photo-overlay 8/39(21%) で最多型が一致しないため、暫定既定値は現行（試作 03 の既定）を据え置き、fullbleed / slider / cards-carousel / product-shot を選べる型として揃える。cards-carousel（製品・コースをカードで横送り）と product-shot（製品画像の hero）が新規の型。
- HP セクション: 出現ページ数上位の区間を固定ページ用パーツにし（§4）、区間セットは「観察された並び」の代表 3〜4 本（企業 / サービス / メディア / 店舗・スクール）として持つ。先頭セクションの最多は banner-row 11/62(18%) / news 11/62(18%) / greeting 10/62(16%)。
- イベント: hero は date-place-block が主集計 25/40(62%) で、前回台帳（前回台帳 home-event-recapture, 主集計 n=8）の key-visual-only 4/8(50%) / photo-overlay 2/8(25%) と食い違う。両台帳の合算で判断せず、§5 の規則（両台帳で最多の型のみ暫定既定値候補）では候補にならない。date-place-block を暫定既定値へ上げるのは**規則の例外**（前回 n=8 の小標本より本台帳 n を優先する）で、PO 決定 WT-EVT-0288（2026-09-06「全部追加」）により既定値へ採用した。info_block は icon-list が 22/40(55%)。apply は external-form / closed-notice が多く、inline-form は観察 0 件（0/40）。target-audience（対象者）・tickets/price・organizer が高頻度で、試作 03 の 11 区間に無い「対象者」「主催」「参加費」を区間として加える。
- キャンペーン / コンテスト（D）: prizes / entry-steps / target-products / judges の区間と、応募方法（`receipt-upload` / `postcard` / `messaging-app`、主集計での出現 receipt-upload 3/40(8%) / postcard 3/40(8%) / messaging-app 2/40(5%)）を語彙 receipt-upload / postcard / messaging-app として正式採用し event_apply の追加型として持つ（PO 決定 WT-EVT-0288 で採用済み）。
