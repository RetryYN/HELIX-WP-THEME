# HP・イベントページ デザイン観察 サマリー（2026-09-06、最新: codex-astra 7巡目反映・gen_summary.py の全生成）

固有名・URLは本ディレクトリの mapping.json のみに記載。本ファイルは `python3 gen_summary.py > summary.md` で observations-home.json / observations-event.json から**全文を生成**し、本台帳の集計値（件数・%・区分 n・変動幅）はすべてスクリプト計算値。既存台帳からの引用値と語彙の定義上の数値は計算対象外で、その旨を各所に明記する。%は四捨五入で合計が 100% から ±1 ポイントずれることがある。

### 対応履歴（過去の巡回。件数は当時の値で、現在の値は §1 を正とする）
- 7巡目: E03（2020 年のオンデマンド配信・継続受付の根拠なし）を E06 と同基準で除外。履歴の件数を当時の固定値に変更。
- 6巡目: E06（2020 年開催・フォーム残存で採用根拠を追跡できない）を E19 と同基準で除外。更新履歴を最新と過去に分離。
- 5巡目: E19（開催後取得で募集期間中の申込ページだった証拠なし）を除外。D の apply 解釈文・区分 n・変動幅を集計から生成。`line` 語彙コードの注記。複数選択行に欠測込み値を併記。
- 4巡目: 募集終了後ページの採用条件（同一ページに closed-notice/ended、E17・E24）を明文化。E25（複数催事の入口）を除外。分母を 2 段（集計対象集合 / 欠測除外後）に整理。
codex-astra 3巡目の指摘反映（当時）: (1) イベントの hero・info_block を「主集計（個別募集ページ）」と「取得全体の参考値」に分けて表示、(2) 主集計定義「来場者・参加希望者がそのページから応募/申込を行う個別ページ」を E14（無料参加で申込導線なし）にも文字通り適用して除外（E20 と同じ基準）→ 主集計 n=12（当時）、(3) HP・イベントとも D 区分単独の値を提示、(4) 分母名を「取得件数 / 観察可能件数 / 有効件数」で統一、(5) 「優勢」等の解釈語を削除、(6) 固有名の残存を除去。

## 0. 用語・分母・ラベルの定義

語彙コードの注記: contact_band=line・fixed=float-line・apply=other:messaging-app の `line` は「メッセージングアプリ導線」を表す既存語彙コードで、第三者サービス名としての記述ではない（category 台帳と語彙を揃えるため保持）。

- **na**: 取得できた（fetched:true）が、その項目の値を本文から判定できない。**n/a**: 対象外（このページ自体に当該情報が存在しない）。**fetched:false**: 取得失敗（どの集計にも含めない）。**fetched:"partial"**: 本文未取得（全比率から除外）。
- 分母の考え方: まず**集計対象集合**（HP = 取得件数、イベント = 主集計）を決め、次に**項目ごとの欠測（na・n/a）除外後の分母**を行内に明記する。欠測がある項目は欠測除外後の値を主表示、欠測込みの値を括弧で併記する。
- ラベル 3 種: **観察事実**（件数と%）／**Claude 案**（解釈・仮説）／**暫定既定値**（試作へ持ち越す候補、PO 確認前提）。小標本（n≤5）の最多型は既定値化の根拠にしない。

### site_pattern 判定基準
- HP: A=製造/士業/医療（中小企業コーポレート）、B=SaaS・サービスのトップ、C=オウンドメディア・比較/ランキングメディア、D=店舗・自由診療クリニック・スクール、E=学校法人・大学・団体。
- イベント: A=セミナー/ウェビナー個別申込、B=展示会・カンファレンス、C=地域フェス・祭り、D=期間限定キャンペーン・コンテスト・学校の参加型催事。

### page_kind（イベントのみ）と主集計定義
主集計 = 「来場者・参加希望者が**そのページから応募/申込を行う**個別ページ」（`individual-event-or-campaign`）。募集終了後に取得したページは、申込導線の位置に closed-notice / ended 表示を置く**同一ページ**が募集期間中の申込ページだった場合に限り採用する（apply=closed-notice、status_badge=ended として記録。E17・E24）。複数催事への入口サイトは単一催事ページと判別できないため除外（E25）。この定義で以下を除外・別掲する。
- E03 `ended-apply-evidence-insufficient`: 2020 年当時のオンデマンド配信ページで、現在も受付中である観察根拠が無いため E06・E19 と同基準で主集計から除外・別掲（Astra 7巡目）
- E06 `ended-apply-evidence-insufficient`: 2020 年開催の後も申込フォームが残るが、現在も申込（録画視聴等）を受け付けているのか終了フォームの残存かを観察から判別できず、E19 と同じ基準（募集期間中の申込ページだった証拠なし）で主集計から除外・別掲（Astra 6巡目）
- E08 `portal-listing`: 開催予定/過去の一覧型セミナーポータル
- E09 `exhibitor-overview`: 来場者向けでなく出展社向け概要ページ
- E10 `multi-edition-hub`: 4展合同ハブページ、複数開催回を並列掲載
- E12 `faq-only`: FAQページのみ観察、本体イベントページ未取得
- E14 `no-apply-flow-free-entry`: 主集計定義「応募/申込を行う個別ページ」に当たらないため主集計から除外・別掲（Astra 3巡目。E20 と同じ基準）
- E16 `performer-facing-not-visitor-apply`: 出演団体向け募集ページで来場者向け個別応募ページではないため主集計から除外・別掲
- E19 `ended-apply-evidence-insufficient`: 開催後の取得で、同一ページが募集期間中の申込ページだった証拠（closed-notice 等）が無く、E17・E24 の採用条件を満たさないため主集計から除外・別掲（Astra 5巡目）
- E20 `ticket-deferred-to-other-page`: チケット販売・応募導線が別ページに委譲されており本ページ自体は申込ページでないため主集計から除外・別掲
- E21 `product-showcase-not-campaign`: 応募制キャンペーンでなく商品訴求ページのため主集計から除外・別掲
- E25 `multi-event-entry-site`: 受験生向け特設サイト。複数の催事（オープンキャンパス等）への入口であり単一催事の個別ページと判別できないため主集計から除外・別掲（Astra 4巡目）。申込はメッセージングアプリ経由

## 1. 件数・fetched 内訳

### HP 42件
- fetched:true 39 / fetched:false 3（H14, H26, H29）。
- site_pattern 内訳（取得件数 39）: A=14 / B=9 / C=6 / D=6 / E=4。

### イベント 25件
- fetched:true 20 / partial 1（E07）/ fetched:false 4（E04, E05, E13, E18）。
- 取得 20 件のうち page_kind 除外 12 件 → **主集計（個別募集ページ）n=8**。site_pattern 内訳: A=2 / B=1 / C=2 / D=3。

## 2. HP: 型内訳（観察事実。集計対象集合 = 取得件数 n=39。項目に na がある行は欠測除外後の分母を行内に明記）

- **hero**（取得件数 n=39）: text-only 13/39(33%) / slider 10/39(26%) / fullbleed-photo-overlay 8/39(21%) / article-grid 3/39(8%) / split-text-image 2/39(5%) / video 2/39(5%) / illustration 1/39(3%)
- **hero_cta**（取得件数 n=39）: double 24/39(62%) / single 10/39(26%) / none 4/39(10%) / tel+button 1/39(3%)
- **news**（取得件数 n=39）: list-with-date 18/39(46%) / cards 7/39(18%) / none 7/39(18%) / tabs 7/39(18%)
- **contact_band**（取得件数 n=39）: tel+form 14/39(36%) / form-only 9/39(23%) / none 8/39(21%) / tel-only 4/39(10%) / line 3/39(8%) / tel+button 1/39(3%)
- **double × tel+form の重複**: double 24 件と tel+form 14 件の重複は 11 件（double の 46%、tel+form の 79%）。double は「CTA ボタンが 2 個」の構造観察で、用途（電話/フォーム）を意味しない。
- **fixed**（複数選択。取得件数 n=39、fixed=na 1 件を除いた分母 38）: sticky-header 21/38(55%) / none 10/38(26%) / float-tel 8/38(21%) / float-cta 5/38(13%) / float-line 1/38(3%) / sp-bottom-bar 1/38(3%)〔欠測込み n=39: sticky-header 21/39(54%) / none 10/39(26%) / float-tel 8/39(21%) / float-cta 5/39(13%) / float-line 1/39(3%) / sp-bottom-bar 1/39(3%) / na 1/39(3%)〕
- **header**（複数選択。取得件数 n=39、header=na 1 件を除いた分母 38）: logo-left-nav-right 35/38(92%) / with-tel 10/38(26%) / with-search 7/38(18%) / with-sns 3/38(8%) / transparent-over-hero 2/38(5%) / with-announce 2/38(5%) / logo-center 1/38(3%)〔欠測込み n=39: logo-left-nav-right 35/39(90%) / with-tel 10/39(26%) / with-search 7/39(18%) / with-sns 3/39(8%) / transparent-over-hero 2/39(5%) / with-announce 2/39(5%) / logo-center 1/39(3%) / na 1/39(3%)〕
- **footer**（複数選択。取得件数 n=39、footer=na 2 件を除いた分母 37）: columns 33/37(89%) / with-sns 13/37(35%) / single-row 4/37(11%)〔欠測込み n=39: columns 33/39(85%) / with-sns 13/39(33%) / single-row 4/39(10%) / na 2/39(5%)〕
- **sections_order 頻出**（延べ回数/採用ページ数、取得件数 n=39、`other:*` を除く上位）: news 27回・23ページ / service-cards 23回・23ページ / company-info 13回・13ページ / contact-form 12回・12ページ / cta-band 12回・12ページ / features-3col 12回・12ページ / banner-row 11回・11ページ / numbers/metrics 11回・11ページ / blog-latest 11回・10ページ / recruit 9回・9ページ / works/cases 9回・9ページ / access/map 8回・8ページ / greeting 8回・8ページ / staff 7回・7ページ。

### 区分別の値（観察事実。区分 n が 4〜14 で、1 件で 7〜25 ポイント動く）

- A（n=14）: news/greeting が先頭 2 セクション以内 7/14(50%) / hero_cta=none 0/14(0%) / blog-latest 2/14(14%) / voice/testimonial 1/14(7%) / price または faq 0/14(0%) / news 9/14(64%) / staff 5/14(36%) / access/map 5/14(36%) / recruit 7/14(50%) / banner-row 6/14(43%)。
- B（n=9）: news/greeting が先頭 2 セクション以内 0/9(0%) / hero_cta=none 0/9(0%) / blog-latest 0/9(0%) / voice/testimonial 3/9(33%) / price または faq 4/9(44%) / news 5/9(56%) / staff 0/9(0%) / access/map 0/9(0%) / recruit 1/9(11%) / banner-row 1/9(11%)。
- C（n=6）: news/greeting が先頭 2 セクション以内 2/6(33%) / hero_cta=none 2/6(33%) / blog-latest 6/6(100%) / voice/testimonial 0/6(0%) / price または faq 0/6(0%) / news 2/6(33%) / staff 0/6(0%) / access/map 0/6(0%) / recruit 0/6(0%) / banner-row 2/6(33%)。
- D（n=6）: news/greeting が先頭 2 セクション以内 2/6(33%) / hero_cta=none 1/6(17%) / blog-latest 1/6(17%) / voice/testimonial 0/6(0%) / price または faq 0/6(0%) / news 4/6(67%) / staff 2/6(33%) / access/map 3/6(50%) / recruit 1/6(17%) / banner-row 0/6(0%)。
- E（n=4）: news/greeting が先頭 2 セクション以内 3/4(75%) / hero_cta=none 1/4(25%) / blog-latest 1/4(25%) / voice/testimonial 0/4(0%) / price または faq 0/4(0%) / news 3/4(75%) / staff 0/4(0%) / access/map 0/4(0%) / recruit 0/4(0%) / banner-row 2/4(50%)。
- **Claude 案**: B（SaaS）で voice/testimonial・price・faq は一部に見られるが「共通の型」とは言えない水準。E（学校法人）の値は観察標本 4 件に限った結果で一般化しない。D は上記の D 単独行を根拠とし、D+E 合算は用いない。

## 3. イベント: 型内訳

### 3a. 主集計（個別募集ページ n=8、site_pattern A2 / B1 / C2 / D3）

- **hero**（主集計 n=8）: key-visual-only 4/8(50%) / photo-overlay 2/8(25%) / date-place-block 1/8(12%) / text-only 1/8(12%)
- **info_block**（主集計 n=8）: inline-text 5/8(62%) / none 2/8(25%) / table 1/8(12%)
- **apply**（主集計 n=8）: inline-form 3/8(38%) / closed-notice 2/8(25%) / external-form 2/8(25%) / ticket-service-link 1/8(12%)
- **schedule**（主集計 n=8）: none 3/8(38%) / date-place-block-in-hero 2/8(25%) / table 2/8(25%) / timeline 1/8(12%)
- **status_badge**（主集計 n=8）: open 5/8(62%) / ended 2/8(25%) / none 1/8(12%)
- **speakers**（主集計 n=8）: none 4/8(50%) / cards-photo 2/8(25%) / list 1/8(12%) / single-profile 1/8(12%)
- **countdown**（主集計 n=8）: none 8/8(100%)
- **map**（主集計 n=8）: none 4/8(50%) / text-only 4/8(50%)
- **fixed**（主集計 n=8）: none 8/8(100%)
- **share**（主集計 n=8）: none 5/8(62%) / icons 3/8(38%)
- **sections_order 頻出**（延べ回数/採用ページ数、主集計 n=8、`other:*` を除く）: overview 7回・7ページ / access/map 4回・4ページ / speakers 3回・3ページ / apply/register 2回・2ページ / notes/terms 2回・2ページ / past-events/report 2回・2ページ / gallery 1回・1ページ / related-events 1回・1ページ / sponsors/logos 1回・1ページ / tickets/price 1回・1ページ / timetable/schedule 1回・1ページ。

### 3b. 参考: 取得全体（n=20、page_kind 除外 12 件を含む。主集計の値ではない）

- **hero**（取得件数 n=20）: photo-overlay 11/20(55%) / key-visual-only 7/20(35%) / date-place-block 1/20(5%) / text-only 1/20(5%)
- **info_block**（取得件数 n=20）: inline-text 10/20(50%) / none 6/20(30%) / table 3/20(15%) / icon-list 1/20(5%)
- info_block=table の内訳: E01[A]・E09[B]・E10[B]（セミナーに限定されない）。

### 3c. 区分別（主集計内、観察事実。区分 n が 1〜3 で断定しない）

- A（n=2、E01/E02）: speakers≠none 2/2(100%) / info_block≠none 2/2(100%) / apply {'external-form': 1, 'inline-form': 1} / access/map 1/2(50%) / hero {'text-only': 1, 'key-visual-only': 1}。
- B（n=1、E11）: サンプル 1 件のため型として集計しない。単体の値: hero=photo-overlay、info_block=inline-text、apply=external-form、schedule=timeline。
- C（n=2、E15/E17）: speakers≠none 1/2(50%) / info_block≠none 1/2(50%) / apply {'ticket-service-link': 1, 'closed-notice': 1} / access/map 1/2(50%) / hero {'date-place-block': 1, 'key-visual-only': 1}。
- D（n=3、E22/E23/E24）: speakers≠none 0/3(0%) / info_block≠none 2/3(67%) / apply {'inline-form': 2, 'closed-notice': 1} / access/map 1/3(33%) / hero {'key-visual-only': 2, 'photo-overlay': 1}。
- **Claude 案**: A（セミナー）は speakers を持つ比率が相対的に高い、D（キャンペーン・学校催事）は apply が closed-notice / inline-form に分散する、という方向は観察と整合するが、各区分 n=1〜3 で 1 件の異同で 33〜100 ポイント動くため暫定既定値化はしない。

### 3d. page_kind 別掲（主集計外、個別事実のみ）

E03[ended-apply-evidence-insufficient] hero=photo-overlay/info_block=inline-text/apply=inline-form。 E06[ended-apply-evidence-insufficient] hero=photo-overlay/info_block=inline-text/apply=inline-form。 E08[portal-listing] hero=photo-overlay/info_block=none/apply=external-form。 E09[exhibitor-overview] hero=key-visual-only/info_block=table/apply=external-form。 E10[multi-edition-hub] hero=key-visual-only/info_block=table/apply=external-form。 E12[faq-only] hero=photo-overlay/info_block=none/apply=external-form。 E14[no-apply-flow-free-entry] hero=key-visual-only/info_block=none/apply=n/a。 E16[performer-facing-not-visitor-apply] hero=photo-overlay/info_block=none/apply=external-form。 E19[ended-apply-evidence-insufficient] hero=photo-overlay/info_block=inline-text/apply=external-form。 E20[ticket-deferred-to-other-page] hero=photo-overlay/info_block=inline-text/apply=n/a。 E21[product-showcase-not-campaign] hero=photo-overlay/info_block=inline-text/apply=n/a。 E25[multi-event-entry-site] hero=photo-overlay/info_block=icon-list/apply=other:messaging-app。

## 4. 既存台帳との差分の見立て（Claude 案）

既存台帳 = リポ内 `docs/research/2026-09-05-parts-pattern-taxonomy/`（実サイト観察・LP 台帳）。そこに記載の HP hero 比率（fullbleed 17% / text-only 13% / split 12% / slider 11%）と、LP 台帳 D 区分 8 件「埋め込みフォーム 75%」は**引用**であり、本台帳の JSON からは再計算できない。n・語彙定義も未確認のため、以下は比較条件の違いを含む Claude 案。
- HP hero: 本回は text-only 13/39(33%)・slider 10/39(26%)・fullbleed-photo-overlay 8/39(21%)。既存台帳と順位が入れ替わるが、サンプル構成差を排除できていない。
- HP セクション: news・service-cards が最頻出という方向は既存の上位セクションと一致。article-grid・category-cards は本回は相対的に少ない。
- イベント apply: 本回 D（n=3）は {'inline-form': 2, 'closed-notice': 1}。「埋め込みフォーム」の定義が本回の inline-form と一致するか未確認のため、整合・矛盾いずれも断定しない。

## 5. 限界

- 集計値は本スクリプトの機械集計。分母は行ごとに「集計対象集合の n」と「欠測除外後の n」を明記。引用値（§4）は計算対象外。
- 主集計のイベント n=8、区分 n=1〜3。HP の区分 n=4〜14。いずれも「傾向」は Claude 案として扱い、暫定既定値化はしない。
- fetched は WebFetch による本文要約経由の観察。実ブラウザでの目視・スクリーンショット照合、SP 表示は未検証。
- page_kind の適格性判定は観察者の解釈に依存する。E03・E06・E14・E16・E19・E20・E21・E25 は境界例として再分類した。今後さらに境界例が見つかる可能性がある。
- countdown は主集計 8 件で 0 件。few-seats（残席少）の明示例は 0 件。存在しないとは言えず観測不足として扱う。

## 6. 試作既定値への持ち込み方（Claude 案、PO 確認前提）

- HP 面の試作候補: hero text-only（最多 13/39(33%)、過半数ではない）、hero_cta double 24/39(62%)、news list-with-date 18/39(46%)、contact tel+form 14/39(36%)。全 39 件の全体集計から観察した型で選ぶ候補であり用途別集計ではない（指定用途での最多型を示すものではない）。試作の用途は**中小企業コーポレート・店舗・スクール向け HP**に限定し、SaaS（B）・メディア（C）は区分行の値を別に見る。
- イベント面の試作候補: hero photo-overlay 2/8(25%)、info_block inline-text 5/8(62%)、apply inline-form 3/8(38%)（n=8 の小標本）。「一般的標準」ではなく**セミナー・地域催事・キャンペーンの個別ページの試作候補**としてのみ扱う。
- 適用範囲: 試作 03 の HP 面・イベント面の初期 variant 選定。検証条件: 試作を PO が見て反応を返すこと。要求への昇格は別途 PO 判断。
