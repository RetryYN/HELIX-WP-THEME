#!/usr/bin/env python3
"""research-r21: sidebar / forms / gap の observations.json → summary.md 全生成（手計算値を残さない）"""
import json
from collections import Counter
SB = json.load(open('sidebar/observations.json')); FM = json.load(open('forms/observations.json')); GP = json.load(open('gap/observations.json'))
def pct(a, b): return f"{a}/{b}({a/b*100:.0f}%)" if b else "n/a"
def split(v): return [s.strip() for s in str(v).split(',') if s.strip()]
def dist(entries, k, exclude=('na',)):
    vals = [e[k] for e in entries]; valid = [v for v in vals if v not in exclude]; ex = {x: vals.count(x) for x in exclude if x in vals}
    c = Counter(valid); items = sorted(c.items(), key=lambda kv: (-kv[1], kv[0]))
    s = " / ".join(f"{v} {pct(n, len(valid))}" for v, n in items)
    if ex: s += "〔欠測 " + ", ".join(f"{x} {n}" for x, n in ex.items()) + " 件を除いた分母 " + str(len(valid)) + "。欠測込み n=" + str(len(vals)) + "〕"
    return s
def multi(entries, k):
    rows = [e for e in entries if e[k] not in ('na',)]; c = Counter()
    for e in rows:
        for v in set(split(e[k])): c[v] += 1
    items = sorted(c.items(), key=lambda kv: (-kv[1], kv[0])); n = len(rows); na = len(entries) - n
    s = " / ".join(f"{v} {pct(cnt, n)}" for v, cnt in items)
    if na: s += f"〔na {na} 件を除いた分母 {n}〕"
    return s, n
SF = [e for e in SB if e['fetched'] is True]; SX = [e for e in SB if e['fetched'] is not True]
FF = [e for e in FM if e['fetched'] is True]; FX = [e for e in FM if e['fetched'] is not True]
FW = [e for e in FF if e.get('form_presence') == 'observed']  # フォーム本体を観察できた行
FABS = [e for e in FF if e.get('form_presence') == 'absent']; FUNK = [e for e in FF if e.get('form_presence') == 'unknown']
GF = [e for e in GP if e['fetched'] is True]; GX = [e for e in GP if e['fetched'] is not True]
# 試作 03 の現状（functions.php の軸 / pattern / template から。Claude が転記した固定表）
PROTO_FACES = {'search-results': '無し（templates に search.html 無し、index.html が受ける）', 'tag-archive': '無し（archive.html が受けるが専用の型なし）', 'author-page': '無し', 'date-archive': '無し', 'thanks-page': '無し', 'privacy': '無し（通常固定ページで代用）', 'terms': '無し（同上）', 'sitemap-page': '無し', 'faq-page': '部分（FAQ パーツはあるが面としては無し）', 'recruit-page': '部分（recruit 区間 / LP で代用）', 'company-page': '部分（会社概要の表パーツ）', 'contact-page': '部分（HP の問い合わせ区間。独立ページの型なし）', 'news-archive': '部分（カテゴリ面で代用）', 'glossary': '無し', 'comparison-page': '部分（比較記事の型）', '404': '有り（3 型）'}
PROTO_PARTS = {'breadcrumb': '無し', 'mega-menu': '無し（ヘッダー 9 型にメガメニュー無し）', 'tabs': '部分（お知らせタブ / 目次 / コア Tabs）', 'accordion': '有り（FAQ / スケジュール）', 'modal': '部分（Q&A モーダル）', 'cookie-banner': '無し', 'announce-bar': '有り（header announce）', 'lang-switch': '無し', 'font-size-switch': '無し', 'chat-widget': '無し', 'popup': '無し', 'sticky-bar': '有り（sticky header / SP 下部バー / 追尾 CTA）', 'back-to-top': '有り', 'pagination-numbers': '有り', 'infinite-scroll': '無し（load-more はあり）', 'comments': '無し', 'rating-stars': '有り（レビュー星）', 'video-embed': '部分（hero video の枠）', 'map-embed': '有り（段 7）', 'sns-embed': '有り（段 8 SNS フィード）', 'dark-toggle': '不採用（PO 決定）', 'search-suggest': '無し', 'reading-progress': '無し', 'table-of-contents': '有り（4 型）', 'image-lightbox': '無し', 'slider': '有り（hero / 関連 / カルーセル）', 'countdown': '有り（段 8）'}
L = []; w = L.append
w("# サイドバー / フォーム / 抜け漏れ 観察サマリー（2026-09-06、research-r21。gen_summary.py の全生成）")
w("")
w("PO 反応 21 回目（WT-EVT-0289「進めて。あとフォームの項目追加とかの項目調査。ほかのサイト見て抜け漏れがないか徹底的に調べて。」、WT-DIR-RESEARCH-FORMS-01 / WT-DIR-GAP-SURVEY-01）を受けた 3 系統の観察。固有名・URL の対応表は別管理（リポ外）で本ディレクトリには含めない。本ファイルは `python3 gen_summary.py > summary.md` で 3 つの observations.json から**全生成**する。")
w("")
w("## 0. 用語・分母・ラベル")
w("")
w("- 取得は WebFetch の本文要約経由（1 ページ 1 回、PC 相当）。追尾（sticky）・SP の扱い・JS で出る要素は要約の記述に依存し、**実ブラウザでは未検証**。na = 取得できたが判定できない、fetched:false = 取得失敗（404 / 403 / 名前解決失敗 / 別ドメインへの転送 / 本文空）。どの集計にも含めない。")
w("- 語彙は `CODING-BRIEF.md`。語彙に無い型は other:<説明>。複数選択は \",\" 区切り。第三者サービス名は書かない（external_service は embedded-form-service / link-to-external の語彙のみ）。")
w("- ラベル: **観察事実**（件数と%）／**Claude 案**（解釈・提案）／**暫定既定値**（PO 確認前提）。区分 n≤5 の最多型は既定値の根拠にしない。")
w("- サイト区分: A=製造 / 士業、B=SaaS / オウンドメディア / 制作会社、C=比較メディア、D=店舗 / クリニック / スクール、E=学校法人、P=ポータル / 個人ブログ。候補は前回台帳（HP 76 件 + 記事 URL 118 件）から選んだ。")
w("")
w("## 1. サイドバー / サイドナビ（記事・固定ページ・HP）")
w("")
w(f"- 件数: {len(SB)} 件、fetched:true **{len(SF)}**、失敗 {len(SX)}（{', '.join(e['id'].replace('site-','') for e in SX)}）。page_kind 内訳: " + " / ".join(f"{k} {v}" for k, v in sorted(Counter(e['page_kind'] for e in SF).items())) + "。区分: " + " / ".join(f"{k}={v}" for k, v in sorted(Counter(e['site_pattern'] for e in SF).items())) + "。")
SA = [e for e in SF if e['page_kind'] == 'article']
SW = [e for e in SF if e['side_layout'] not in ('none', 'na')]  # サイドバーあり（side_layout=none を除く）
w(f"- **side_layout**（取得 n={len(SF)}）: " + dist(SF, 'side_layout') + "。記事のみ（n={}）: ".format(len(SA)) + dist(SA, 'side_layout') + "。")
w(f"- **side_sticky**（取得 n={len(SF)}）: " + dist(SF, 'side_sticky') + "。")
s, n = multi(SW, 'side_widgets'); w(f"- **side_widgets**（複数選択、サイドバーあり = side_layout が none 以外 n={n}）: " + s + "。")
w(f"- **記録リストの先頭のウィジェット**（サイドバーあり n={n}、全種。両側カラムも 1 本のリストで記録しているので先頭 = 右カラムの先頭、左は含めない）: " + " / ".join(f"{k} {pct(v, n)}" for k, v in sorted(Counter(split(e['side_widgets'])[0] for e in SW).items(), key=lambda kv: (-kv[1], kv[0]))) + "。")
w(f"- **ウィジェット数**（サイドバーあり n={n}）: 中央値 {sorted(len(split(e['side_widgets'])) for e in SW)[n//2]}、最小 {min(len(split(e['side_widgets'])) for e in SW)}、最大 {max(len(split(e['side_widgets'])) for e in SW)}。")
w(f"- **side_nav**（取得 n={len(SF)}）: " + dist(SF, 'side_nav') + "。")
w(f"- **side_sp**: " + dist(SF, 'side_sp') + "（要約では SP の扱いはほぼ判定できない。推測の行は na に戻した）。")
w("- **side_width**: 全行 na（要約では判定できず、集計対象外）。")
w(f"- **本文中の目次**: あり {pct(sum(1 for e in SF if e['toc_in_body'] is True), len(SF))}。目次をサイドで追尾する型（side_widgets に toc-sticky、または side_sticky=toc-only、または side_nav=toc-side-only）: {pct(sum(1 for e in SF if 'toc-sticky' in split(e['side_widgets']) or e['side_sticky']=='toc-only' or e['side_nav']=='toc-side-only'), len(SF))}。")
w("- 区分別（観察事実、n が小さい区分は断定しない）:")
for p in sorted(set(e['site_pattern'] for e in SF)):
    es = [e for e in SF if e['site_pattern'] == p]; s2, n2 = multi(es, 'side_widgets')
    w(f"  - {p}（n={len(es)}）: layout " + dist(es, 'side_layout') + "。widgets 上位 " + " / ".join(s2.split(" / ")[:6]) + "。")
w("")
w("## 2. フォーム（問い合わせ / 申込 / 資料請求 / 予約 / メルマガ / 採用 / 見積 / トライアル）")
w("")
w(f"- 件数: {len(FM)} 件、fetched:true {len(FF)}、失敗 {len(FX)}（{', '.join(e['id'].replace('site-','') for e in FX)}）。取得できたページの内訳（form_presence）: **本体を観察 {len(FW)}** / 無いと確認（電話・メール案内、ハブ、外部リンクのみ）{len(FABS)} / 有無を判定できない {len(FUNK)}（{', '.join(e['id'].replace('site-','') for e in FUNK)}）。fields 以降の項目別集計は「本体を観察」の行が分母（form_kind / external_service / cta_side はページ単位で取得全件）。")
w(f"- **form_kind**（ページ単位の集計、取得 n={len(FF)}。form_kind / external_service / cta_side の 3 項目は分母の例外 = 本体未観察のページも判定できるため取得全件を対象に na を除外）: " + dist(FF, 'form_kind') + "。")
s, n = multi(FW, 'fields'); w(f"- **fields**（複数選択、フォーム本体 n={n}。other:* は語彙外）: " + s + "。")
w(f"- **符号化項目数**（fields のトークン数。実際の入力欄数ではなく、複数の yes/no を 1 トークンに圧縮した行がある。フォーム本体 n={len(FW)}）: 中央値 {sorted(len(split(e['fields'])) for e in FW)[len(FW)//2]}、最小 {min(len(split(e['fields'])) for e in FW)}、最大 {max(len(split(e['fields'])) for e in FW)}。")
for k in ['required_mark', 'layout', 'confirm_page', 'thanks_page', 'submit_text', 'error_display']: w(f"- **{k}**（フォーム本体 n={len(FW)}）: " + dist(FW, k) + "。")
w(f"- **external_service**（ページ単位、取得 n={len(FF)}）: " + dist(FF, 'external_service') + "。")
s, n = multi(FF, 'cta_side'); w(f"- **cta_side**（複数選択、ページ単位、取得 n={n}。messaging-app = メッセージアプリ経由の代替導線）: " + s + "。")
w("- **フォーム種別ごとの項目**（観察事実、種別 n が小さいものは参考）:")
for fk in sorted(set(e['form_kind'] for e in FW)):
    es = [e for e in FW if e['form_kind'] == fk]; s2, n2 = multi(es, 'fields')
    w(f"  - {fk}（n={len(es)}）: " + s2 + "。")
w("")
w("## 3. 抜け漏れ（面・パーツの棚卸し。HP から見える範囲）")
w("")
w(f"- 件数: {len(GP)} 件、fetched:true {len(GF)}、失敗 {len(GX)}。区分: " + " / ".join(f"{k}={v}" for k, v in sorted(Counter(e['site_pattern'] for e in GF).items())) + "。")
s, n = multi(GF, 'faces_present'); w(f"- **faces_present**（複数選択、n={n}）: " + s + "。")
s, n = multi(GF, 'parts_present'); w(f"- **parts_present**（複数選択、n={n}）: " + s + "。")
w("- 語彙外（other:*）は notes から行ごとに手で符号化したもので、上の 2 行に含めて数えている（page- = 独立ページ、sec- = 区間、link- = 導線、接頭辞なし = 部品）。§3 末尾の集約はこの other:* を Claude がまとめたもの。")
w("")
w("### 3a. 試作 03 との突き合わせ（左 = 観察の出現率、右 = 試作 03 の現状 = **段 10 着手前**の状態。現状の列は Claude が試作の軸 / pattern / template から転記した固定表で、段 10 で共通サイドバーとメガメニュー等を追加した後は変わる）")
w("")
fc = Counter(); [fc.update(set(split(e['faces_present']))) for e in GF]
pc = Counter(); [pc.update(set(split(e['parts_present']))) for e in GF]
w("| 面 | 観察（HP から見えた） | 試作 03 の現状 |"); w("|---|---|---|")
for k, cur in PROTO_FACES.items(): w(f"| {k} | {pct(fc.get(k, 0), len(GF))} | {cur} |")
w("")
w("| パーツ | 観察 | 試作 03 の現状 |"); w("|---|---|---|")
for k, cur in PROTO_PARTS.items(): w(f"| {k} | {pct(pc.get(k, 0), len(GF))} | {cur} |")
w("| sidebar（語彙外・未集計。§1 で別途観察） | 未集計 | 部分（カテゴリ面のみ。段 10 で記事 / 固定ページ / HP へ） |")
w("")
w("- **語彙に無い観察（notes から、Claude が集約）**: 料金 / 計算シミュレータ、教室・店舗検索（郵便番号 / エリア）、診療時間表・診療カレンダー、返金保証のフロー図、成果の数値表示（スコア推移グラフ）、ユーザーレビュー + 専門家評価、キャンペーンバナー群、寄付 / 支援（会員・月額）、スポンサード記事の明示、監修者 / ライター紹介（顔写真）、タグクラウド、月別アーカイブのドロップダウン、複数製品のログイン一覧、PDF カタログ直リンク、デジタルパンフ（外部）、外部フォームサービスへの予約導線（クリニック / 学校に多い）、メインビジュアルの再生 / 停止ボタン。")
w("")
w("## 4. 限界")
w("")
w("- すべて WebFetch の本文要約経由。追尾・SP 表示・JS で出る要素（ポップアップ / cookie 同意 / チャット）は要約に出にくく**過小評価**の可能性が高い。faces / parts は「トップから見えた範囲」で、サイト内に存在しても見えないものは数えていない。")
w("- トップの要約が挙げたフォーム URL が実在しないことがあり（B 区分で 5 件 404）、失敗として記録した。フォームの項目も要約が部分的なことがある（na）。")
w("- 区分の n は小さい（形式は同じでも母集団が違う）。既定値の根拠にはせず、「型を全部持つ」（WT-EVT-0288）の材料として使う。")
w("")
w("## 5. 試作への持ち込み方（Claude 案、PO 確認前提。WT-EVT-0288「最大数を取りにいく」に従い観察した型は全部入れる）")
w("")
wc = Counter(); [wc.update(set(split(e['side_widgets']))) for e in SW]
observed_w = [k for k, _ in sorted(wc.items(), key=lambda kv: (-kv[1], kv[0])) if wc.get(k, 0) > 0]  # JSON の観察から（other:* を含む）
unobserved_w = [k for k in ['search','profile','categories','popular-ranking','new-posts','tags','cta-banner','toc-sticky','newsletter','sns-follow','archive','calendar','ad','related-posts','event-list','contact-box','tel-box','banner-stack','recruit'] if wc.get(k, 0) == 0]
w("- **サイドバー（段 10、観察由来）**: 軸 `side_layout`（観察 4 値: none / right / left / both）、`side_sticky`（観察 4 値: none / whole / last-widget / toc-only）、`side_set`（区分別の観察順から: media = C、blog = P、owned = B、minimal = ポータル最小）、`side_nav`（観察 6 値: none / mega-menu-dropdown / fixed-left-nav / fixed-right-icons / drawer-from-hamburger(pc) / toc-side-only）。ウィジェットは観察された " + str(len(observed_w)) + " 種（" + " / ".join(observed_w) + "）。")
w("- **サイドバー（未観察 / 少数観察の追加提案、Claude 案）**: `side_sp` は要約でほぼ判定できず（観察は drawer " + str(sum(1 for e in SF if e['side_sp']=='drawer')) + " 件のみ、below-content / hidden は 0 件）。前回台帳 article/sp の none 93% を根拠に 3 型を持つ。語彙にあるが観察ゼロのウィジェット（" + (" / ".join(unobserved_w) if unobserved_w else "なし") + "）、固定ページ / HP 向けの corporate セット（観察の corporate 系は少数）、カテゴリ面 3 型との統合。")
w("- **フォーム（段 11、観察由来）**: 軸 `form_fields`（観察の頻出項目から minimal = 名前 + メール + 本文 / standard = + 会社 + 電話 + 種別 / full = + かな + 郵便番号 + 住所 + 添付 / by-kind = 種別ごとの観察セット）、`form_required`（観察 2 値: asterisk / 必須ラベル）、`form_layout`（観察 3 値: 1col / 2col / steps）、`form_confirm`（観察: 確認画面あり / なし）、`form_consent`（観察: チェックボックス / リンクのみ / 送信ボタン文言に含める）、`form_submit`（観察の文言: 送信 / 送信する / 確認する / 確認画面へ / 同意して送信する / ダウンロード / 次へ進む）、`form_error`（観察 2 値: 項目下 / 上部まとめ）、`form_captcha`（観察: なし / 簡易な質問型 / 外部型の枠）、`form_side`（観察: tel / messaging-app / chat / email / none）。種別は観察された " + str(len(set(e['form_kind'] for e in FW))) + " 種（" + " / ".join(sorted(set(e['form_kind'] for e in FW))) + "。reservation は第 1〜3 希望日時、other:diagnosis は yes-no の多段）。いずれも PoC は送信しない。")
w("- **フォーム（未観察の追加提案、Claude 案）**: 種別 apply（語彙にあるが本体観察 0 件。イベントの申込は段 5 で既存）、`form_layout` の label-left、`form_confirm` の inline-review（送信前に同一ページで見直す型）。**少数観察**（未観察ではない）: 完了ページ separate " + str(sum(1 for e in FW if e['thanks_page']=='separate')) + " 件。")
obs_faces = [k for k in PROTO_FACES if fc.get(k, 0) > 0 and PROTO_FACES[k].startswith(('無し', '部分'))]; obs_parts = [k for k in PROTO_PARTS if pc.get(k, 0) > 0 and PROTO_PARTS[k].startswith(('無し', '部分'))]
un_faces = [k for k in PROTO_FACES if fc.get(k, 0) == 0 and PROTO_FACES[k].startswith(('無し', '部分'))]; un_parts = [k for k in PROTO_PARTS if pc.get(k, 0) == 0 and PROTO_PARTS[k].startswith(('無し', '部分'))]
w("- **抜け漏れ（段 12 以降、観察由来 = HP から 1 件以上見えた面・パーツで試作が「無し / 部分」のもの）**: 面 = " + " / ".join(obs_faces) + "。パーツ = " + " / ".join(obs_parts) + "。語彙外で notes に出た型（§3 末尾）: 料金 / 計算シミュレータ、店舗・教室検索、診療時間表・カレンダー、保証フロー図、スコア推移グラフ、レビュー + 専門家評価、寄付・支援、スポンサード表示、ライター紹介、タグクラウド、アーカイブ選択、動画の再生停止。")
w("- **抜け漏れ（未観察の追加提案、Claude 案。語彙にあるが今回 0 件）**: 面 = " + (" / ".join(un_faces) or "なし") + "。パーツ = " + (" / ".join(un_parts) or "なし") + "。")
print("\n".join(L))
