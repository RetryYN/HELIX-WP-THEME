#!/usr/bin/env python3
"""observations-home.json / observations-event.json → summary.md 全生成（手計算値を残さない）"""
import json
from collections import Counter
H = json.load(open('observations-home.json')); E = json.load(open('observations-event.json'))
PH = json.load(open('../home-event-recapture/observations-home.json')); PE = json.load(open('../home-event-recapture/observations-event.json'))  # 前回台帳（同リポ内）
def pct(a, b): return f"{a}/{b}({a/b*100:.0f}%)" if b else "n/a"
def oc(vals): c = Counter(vals); return sorted(c.items(), key=lambda kv: (-kv[1], kv[0]))
def split(v): return [s for s in str(v).split(',') if s]
def line_dist(entries, k, exclude=('na','n/a')):
    vals = [e[k] for e in entries]; ex = {x: sum(1 for v in vals if v == x) for x in exclude if any(v == x for v in vals)}
    valid = [v for v in vals if v not in exclude]
    return valid, ex
def fmt_dist(entries, k, denom_name, denom_all=True):
    valid, ex = line_dist(entries, k); n_all = len(entries); n_valid = len(valid)
    parts = [f"{v} {pct(c, n_all)}" for v, c in oc(valid)]
    if ex: parts += [f"{x} {pct(c, n_all)}" for x, c in ex.items()]
    if not ex: return f"- **{k}**（{denom_name} n={n_all}）: " + " / ".join(parts)
    s = f"- **{k}**（{denom_name} n={n_all}、欠測 {', '.join(f'{x} {c}' for x, c in ex.items())} 件を除いた分母 {n_valid}）: " + " / ".join(f"{v} {pct(c, n_valid)}" for v, c in oc(valid))
    s += "〔欠測込み n=" + str(n_all) + ": " + " / ".join(parts) + "〕"
    return s
def multi(entries, k, denom_name):
    rows = [e for e in entries if e[k] != 'na']; c = Counter()
    for e in rows:
        for v in split(e[k]): c[v] += 1
    n = len(rows); na = len(entries) - n
    items = sorted(c.items(), key=lambda kv: (-kv[1], kv[0]))
    s = f"- **{k}**（複数選択。{denom_name} n={len(entries)}、{k}=na {na} 件を除いた分母 {n}）: " + " / ".join(f"{v} {pct(cnt, n)}" for v, cnt in items)
    if na: s += "〔欠測込み n=" + str(len(entries)) + ": " + " / ".join(f"{v} {pct(cnt, len(entries))}" for v, cnt in items) + f" / na {pct(na, len(entries))}〕"
    return s
def sections(entries, top=14):
    tot = Counter(); pages = Counter()
    for e in entries:
        tot.update(e['sections_order']); pages.update(set(e['sections_order']))
    items = [(k, tot[k], pages[k]) for k in tot if not k.startswith('other:')]
    items.sort(key=lambda t: (-t[2], -t[1], t[0]))
    return " / ".join(f"{k} {t}回・{p}ページ" for k, t, p in items[:top])
def has(e, sec): return sec in e['sections_order']
def first2(e, secs): return any(s in e['sections_order'][:2] for s in secs)
HALL = [e for e in H if e['fetched'] is True]; HX = [e for e in H if e['fetched'] is not True]
HD = [e for e in HALL if e.get('prev_dup')]; HF = [e for e in HALL if not e.get('prev_dup')]
EF = [e for e in E if e['fetched'] is True]; EX = [e for e in E if e['fetched'] is not True]
MAIN_KIND = 'individual-event-or-campaign'
EM = [e for e in EF if e.get('page_kind') == MAIN_KIND]; EO = [e for e in EF if e.get('page_kind') != MAIN_KIND]
PHF = [e for e in PH if e.get('fetched') is True]; PEM = [e for e in PE if e.get('fetched') is True and e.get('page_kind') == MAIN_KIND]
def dist1(entries, k, v): return pct(sum(1 for e in entries if e[k]==v), len(entries))
def region_line(es, k):
    valid, ex = line_dist(es, k); n = len(es)
    s = '上位 3 語 ' + ' / '.join(f'{a} {pct(b, len(valid))}' for a, b in Counter(valid).most_common(3))
    if ex: s += '（欠測 ' + ', '.join(f'{x} {c}' for x, c in ex.items()) + ' 件を除いた分母 ' + str(len(valid)) + '。欠測込み n=' + str(n) + ': ' + ' / '.join(f'{a} {pct(b, n)}' for a, b in Counter(e[k] for e in es).most_common(3)) + '）'
    return s
def Hp(p): return [e for e in HF if e['site_pattern']==p]
def Ep(p): return [e for e in EM if e['site_pattern']==p]
def secs_pages(entries, top=40, exclude_other=True):
    pages = Counter()
    for e in entries: pages.update(set(s for s in e['sections_order'] if not (exclude_other and s.startswith('other:'))))
    return sorted(pages.items(), key=lambda kv: (-kv[1], kv[0]))[:top]
def first_secs(entries, k=1):
    c = Counter()
    for e in entries:
        if e['sections_order']: c[e['sections_order'][0]] += 1
    return c
L = []; w = L.append
w("# HP・イベントページ デザイン観察 サマリー v2（2026-09-06、research-r19。gen_summary.py の全生成）")
w("")
w("固有名・URL の対応表は別管理（リポ外）で、本ディレクトリには含めない。本ファイルは `python3 gen_summary.py > summary.md` で observations-home.json / observations-event.json から**全生成**する（手計算値を残さない）。PO 反応 19 回目（WT-EVT-0287「homeとイベントはもっとバリエーションを出して。調査不足じゃない？…固定ページ継投で使えるパーツをしっかりと作りこむこと」）を受けた再収集。前回台帳（home-event-recapture）の**追加ではなく独立した再収集**として扱う。対応表の URL 照合で前回と同一 URL だった HP " + str(len(HD)) + " 件（" + ', '.join(e['id'].replace('site-','') + '=前回 ' + e['prev_dup'].replace('site-','') for e in HD) + "）は `prev_dup` を付けて**HP の集計対象から除外**した（一覧は本段落のとおり。§1 は件数のみ）。イベントは同一 URL なし（同一ホストの別ページが 5 件あるが、ページが異なるため採用）。前回台帳の値は ../home-event-recapture/observations-*.json（前回台帳）から算出する（集計方法（分母・欠測除外）は共通、主集計の採用条件は各台帳の定義に従う。§0 参照）。id は台帳ごとの別体系。")
w("")
w("## 0. 用語・分母・ラベルの定義")
w("")
w("語彙コードの注記: contact_band=messaging-app・fixed=float-tel 等は導線の種類を表す語彙コードで、第三者サービス名としての記述ではない。`other:*` は語彙外の観察で、集計の分母には含める。原則として型の候補にはしない。応募方法の 3 語（receipt-upload / postcard / messaging-app）は当初 other:* で記録したが、PO 決定 WT-EVT-0288（2026-09-06「全部追加」）で語彙へ正式採用したため、本台帳では other: 接頭辞なしで集計する。**apply は複数選択**（複数の応募経路があるページは全経路を列挙し、代表経路を選ばない）。header/footer の語彙統合: signin→login、badge / cert-badges→badges、cert-logo→cert。footer の hours-table は営業時間・診療時間を**表**で示すもの、hours は 1〜2 行の文字表記。")
w("")
w("- **na**: 取得できた（fetched:true）が、その項目の値を本文要約から判定できない。**fetched:false**: 取得失敗（どの集計にも含めない。HTTP 403/404・名前解決失敗・証明書不一致・本文空）。")
w("- 分母: HP は取得件数から prev_dup を除いた集計対象、イベントは主集計（page_kind = `individual-event-or-campaign`）。na がある項目は欠測除外後の分母を行内に明記する。")
w("- ラベル 3 種: **観察事実**（件数と%）／**Claude 案**（解釈・仮説）／**暫定既定値**（試作へ持ち越す候補、PO 確認前提）。小標本（n≤5）の最多型は既定値化の根拠にしない。")
w("- 取得方法: WebFetch による本文要約経由の観察（1 ページ 1 回）。実ブラウザの目視・SP 表示は未検証。hero の型は要約の記述から判定したもので、`slider` と `cards-carousel` の境界（写真の横送りか、カード状の横送りか）は要約の表現に依存する。")
w("")
w("### site_pattern 判定基準")
w("- HP: A=製造/士業/医療（中小企業コーポレート）、B=SaaS・サービスのトップ、C=オウンドメディア・比較/ランキングメディア、D=店舗・自由診療クリニック・スクール・チェーン、E=学校法人・大学・団体。")
w("- イベント: A=セミナー/ウェビナー個別申込、B=展示会・カンファレンス、C=地域フェス・祭り・花火、D=期間限定キャンペーン・コンテスト・学校の参加型催事（オープンキャンパス）。")
w("")
w("### page_kind（イベントのみ）と主集計定義")
w("主集計 = 「来場者・参加希望者が**そのページから応募/申込を行う**個別ページ」。募集終了後に取得したページは、**申込導線の位置、または同一ページ内の終了表示**（closed-notice / ended バッジ）を確認できたものを採用する。前回台帳の条件は「申込位置の終了表示」のみで、本台帳は同一ページの status バッジも根拠として認める（条件の拡張。該当は status-badge の行数で示す）。根拠は各行の `closed_evidence`（apply-position = 申込位置の受付終了 / アーカイブ・オンデマンド案内、status-badge = 同一ページの終了バッジのみ）で、どちらも無い終了ページは `ended-apply-evidence-insufficient` として除外する。出店者・出演者向けの募集ページは前回と同じく `performer-facing-not-visitor-apply` として除外する。除外（別掲）:")
w("主集計で status_badge=ended の " + str(sum(1 for e in EM if e['status_badge']=='ended')) + " 件の closed_evidence: " + ' / '.join(f'{k} {v}' for k, v in Counter(e['closed_evidence'] for e in EM if e['status_badge']=='ended').items()) + "。")
for e in sorted(EO, key=lambda x: x['id']): w(f"- {e['id'].replace('site-','')} `{e.get('page_kind')}`: {e['notes']}")
w("")
w("## 1. 件数・fetched 内訳")
w("")
w(f"### HP {len(H)} 件")
w(f"- fetched:true {len(HALL)} / fetched:false {len(HX)}（{', '.join(e['id'].replace('site-','') for e in HX)}）。")
w(f"- fetched:true のうち前回台帳と同一 URL（prev_dup）{len(HD)} 件を除外 → **集計対象 n={len(HF)}**。")
w(f"- site_pattern 内訳（集計対象 {len(HF)}）: " + " / ".join(f"{k}={v}" for k, v in sorted(Counter(e['site_pattern'] for e in HF).items())) + "。")
w("")
w(f"### イベント {len(E)} 件")
w(f"- fetched:true {len(EF)} / fetched:false {len(EX)}（{', '.join(e['id'].replace('site-','') for e in EX)}）。")
w(f"- 取得 {len(EF)} 件のうち page_kind 除外 {len(EO)} 件 → **主集計（個別募集ページ）n={len(EM)}**。site_pattern 内訳: " + " / ".join(f"{k}={v}" for k, v in sorted(Counter(e['site_pattern'] for e in EM).items())) + "。")
w("")
w(f"## 2. HP: 型内訳（観察事実。集計対象集合 = prev_dup 除外後 n={len(HF)}）")
w("")
for k in ['hero','hero_cta','news','contact_band']: w(fmt_dist(HF, k, '集計対象'))
w(multi(HF, 'fixed', '集計対象'))
w(multi(HF, 'header', '集計対象'))
w(multi(HF, 'footer', '集計対象'))
fs = first_secs(HF)
w(f"- **先頭セクション**（hero の直後、集計対象 n={len(HF)}）: " + " / ".join(f"{k} {pct(v, len(HF))}" for k, v in sorted(fs.items(), key=lambda kv: (-kv[1], kv[0]))[:8]) + "。")
w(f"- **セクションの出現ページ数**（`other:*` を除く、集計対象 n={len(HF)}）: " + " / ".join(f"{k} {pct(v, len(HF))}" for k, v in secs_pages(HF, 30)) + "。")
w(f"- **セクション数**（1 ページあたり、集計対象 n={len(HF)}）: 中央値 {sorted(len(e['sections_order']) for e in HF)[len(HF)//2]}、最小 {min(len(e['sections_order']) for e in HF)}、最大 {max(len(e['sections_order']) for e in HF)}。")
w("")
HN=[len(Hp(p)) for p in sorted(set(e['site_pattern'] for e in HF))]
w(f"### 区分別の値（観察事実。区分 n が {min(HN)}〜{max(HN)} で、1 件で {100/max(HN):.0f}〜{100/min(HN):.0f} ポイント動く）")
w("")
for p in sorted(set(e['site_pattern'] for e in HF)):
    es = Hp(p); n = len(es)
    hero = region_line(es, 'hero')
    top = " / ".join(f"{k} {pct(v, n)}" for k, v in secs_pages(es, 8))
    w(f"- {p}（n={n}）: hero {hero}。セクション上位 {top}。hero_cta=double {pct(sum(1 for e in es if e['hero_cta']=='double'), n)} / contact_band=form-only {pct(sum(1 for e in es if e['contact_band']=='form-only'), n)} / float-tel {pct(sum(1 for e in es if 'float-tel' in split(e['fixed'])), n)} / sp-bottom-bar {pct(sum(1 for e in es if 'sp-bottom-bar' in split(e['fixed'])), n)}。")
w("")
w("## 3. イベント: 型内訳")
w("")
w(f"### 3a. 主集計（個別募集ページ n={len(EM)}、site_pattern " + " / ".join(f"{k}{v}" for k, v in sorted(Counter(e['site_pattern'] for e in EM).items())) + "）")
w("")
for k in ['hero','info_block']: w(fmt_dist(EM, k, '主集計'))
w(multi(EM, 'apply', '主集計'))
for k in ['schedule','status_badge','speakers','countdown','map','fixed','share']: w(fmt_dist(EM, k, '主集計'))
w(f"- **セクションの出現ページ数**（`other:*` を除く、主集計 n={len(EM)}）: " + " / ".join(f"{k} {pct(v, len(EM))}" for k, v in secs_pages(EM, 30)) + "。")
fe = first_secs(EM)
w(f"- **先頭セクション**（主集計 n={len(EM)}）: " + " / ".join(f"{k} {pct(v, len(EM))}" for k, v in sorted(fe.items(), key=lambda kv: (-kv[1], kv[0]))[:5]) + "。")
w("")
w(f"### 3b. 参考: 取得全体（n={len(EF)}、page_kind 除外 {len(EO)} 件を含む。主集計の値ではない）")
w("")
for k in ['hero','info_block']: w(fmt_dist(EF, k, '取得件数'))
w(multi(EF, 'apply', '取得件数'))
w("")
EN=[len(Ep(p)) for p in sorted(set(e['site_pattern'] for e in EM))]
w(f"### 3c. 区分別（主集計内、観察事実。区分 n が {min(EN)}〜{max(EN)}。n≤5 の区分は断定しない）")
w("")
for p in sorted(set(e['site_pattern'] for e in EM)):
    es = Ep(p); n = len(es)
    w(f"- {p}（n={n}）: hero " + region_line(es, 'hero') + "。apply（複数選択、全語） " + " / ".join(f"{k} {pct(v, n)}" for k, v in Counter(x for e in es for x in split(e['apply']) if x != 'na').most_common()) + "。status " + region_line(es, 'status_badge') + "。セクション上位 " + " / ".join(f"{k} {pct(v, n)}" for k, v in secs_pages(es, 8)) + "。")
w("")
w("## 4. 固定ページ用パーツの棚卸し（観察事実 + Claude 案）")
w("")
w("PO 指示「固定ページ継投で使えるパーツをしっかりと作りこむこと。記事パーツからの転用でも可」（WT-EVT-0287）に向け、HP・イベントの sections_order に現れた区間を**パーツ候補**として列挙する。出現ページ数は観察事実、右列の「記事パーツからの転用」は Claude 案（試作 03 の既存部品名。要求・設計判断ではない）。")
w("")
ALL = HF + EM
reuse = {'news':'記事一覧（お知らせ）: 既存 home-news の 3 型','features':'features / steps（特長 3 列）','numbers':'numbers（数字）','service-cards':'cards（サービスカード）','works/cases':'cases（事例カード）','products':'product-bundle / cards','company-info':'表（会社概要）: 既存 table','access/map':'event-map（静止画 / 文字 / 埋め込み）','recruit':'cta-banner（採用バナー）','contact-form':'lp form（非送信 PoC）','contact-band':'cta-band（問い合わせ帯）','cta-band':'cta-band','blog-latest':'記事一覧（関連 6 件と同型）','faq':'faq（アコーディオン）','price':'pricing / cta-price-tier','voice/testimonial':'review-quote-photo（LP 口コミ）','staff':'author / speakers cards','history/timeline':'timeline（新規: event schedule timeline を転用）','logos/clients':'logos-row（新規）','awards':'badges（footer badges を転用）','banner-row':'banner-row（home 既存）','download':'download（LP 資料 DL）','video':'video（home hero video を転用）','sns-feed':'sns-feed（新規、遅延読込の埋め込み）','courses':'cards（コース）','flow/steps':'steps','stores':'stores（新規: 店舗 / 拠点カード + 検索）','events/open-campus':'event-list（新規: イベント一覧カード）','events/seminar':'event-list','greeting':'greeting（新規: 写真 + 挨拶文）','menu':'cards（メニュー）','hours-table':'table（診療時間）','reservation':'cta-band（予約）','category-cards':'category-cards（home 既存）','article-grid':'article-grid（home 既存）','ranking':'ranking（home 既存）','pickup':'pickup（category 既存）','tags':'tags（category 既存）','newsletter':'newsletter（category cta 既存）','editors':'author（編集部）','search':'search（sidebar 既存）','overview':'本文（見出し + 段落）','info':'event-info（3 型既存）','schedule':'event-schedule（3 型既存）','program-detail':'本文 + numbox','speakers':'event-speakers（3 型既存）','tickets/price':'pricing / table','apply/register':'event-apply（4 型既存）','sponsors':'logos-row（新規）','past-events':'cards / archive-list（新規）','notes/terms':'本文（注意事項）','organizer':'表（主催）','participant-feedback':'review-quote-photo','venue-photos':'gallery（新規）','related-events':'event-list（新規）','target-audience':'list（対象者）','prizes':'cards（賞品）（新規）','entry-steps':'steps','judges':'speakers cards','target-products':'product-bundle',}
w("| 区間コード | HP 出現 | イベント主集計 出現 | 記事パーツからの転用（Claude 案） |")
w("|---|---|---|---|")
allc = Counter()
for e in ALL: allc.update(set(s for s in e['sections_order'] if not s.startswith('other:')))
hc = Counter();  [hc.update(set(s for s in e['sections_order'] if not s.startswith('other:'))) for e in HF]
ec = Counter();  [ec.update(set(s for s in e['sections_order'] if not s.startswith('other:'))) for e in EM]
for k, _ in sorted(allc.items(), key=lambda kv: (-kv[1], kv[0])):
    w(f"| {k} | {pct(hc[k], len(HF))} | {pct(ec[k], len(EM))} | {reuse.get(k, '（未整理）')} |")
w("")
w("- **Claude 案（パーツ化の順序）**: 出現ページ数が HP で 30% 以上、またはイベント主集計で 50% 以上のものを第 1 群（必須パーツ）、10% 以上を第 2 群、それ未満を第 3 群（選べるが既定に入れない）とする。第 1 群の多くは記事面 / LP の既存部品を固定ページ用 pattern として再登録すれば足り、観察に基づく新規は greeting / logos-row / stores / event-list / sns-feed / timeline。")
w(f"- **未観察の追加提案（棚卸し由来ではない Claude 案）**: countdown（観察 {dist1(EM, 'countdown', 'none')} が none。観察は根拠にしない）。gallery（HP の sections_order に区間なし。notes に「ギャラリー」の記述が " + str(sum(1 for e in HF if 'ギャラリー' in e.get('notes',''))) + " 件（集計対象内）あるだけで、区間として符号化していないため未観察扱い）。")
w("")
w("## 5. 限界")
w("")
w(f"- HP は集計対象 n={len(HF)}（取得 {len(HALL)}、prev_dup 除外 {len(HD)}）、区分 n={min(HN)}〜{max(HN)}。イベント主集計 n={len(EM)} だが区分 n={min(EN)}〜{max(EN)} で、A（セミナー）に偏る（B 展示会・C フェスは小標本）。")
w("- 取得は WebFetch の本文要約経由で、hero の写真 / 文字の別、固定導線の SP 表示、セクションの視覚的な区切りは要約に依存する。fetched:false は無作為ではなく、大手・JS 依存サイトが落ちやすい偏りがある。")
w("- 前回台帳（取得 n=" + str(len(PHF)) + " / 主集計 n=" + str(len(PEM)) + "）とは prev_dup 除外後に集合が独立で、割合の差は標本差を含む。既定値の扱い（Claude 案）: 両台帳で最多の型のみ**暫定既定値**の候補にし、片方だけの最多型は「選べる型」に留める。既存の既定値（試作 03 で PO 反応を経たもの）は、この規則で置き換え候補が出ない限り据え置く。")
w("")
w("## 6. 試作への持ち込み方（Claude 案、PO 確認前提）")
w("")
HV = [e for e in HF if e['hero'] != 'na']; PHV = [e for e in PHF if e['hero'] != 'na']
w(f"- HP hero: 本台帳（欠測除外 n={len(HV)}）では " + " / ".join(f"{k} {pct(v, len(HV))}" for k, v in Counter(e['hero'] for e in HV).most_common(4)) + "。前回台帳（前回台帳 home-event-recapture, 欠測除外 n=" + str(len(PHV)) + "）は " + " / ".join(f"{k} {pct(v, len(PHV))}" for k, v in Counter(e['hero'] for e in PHV).most_common(3)) + " で最多型が一致しないため、暫定既定値は現行（試作 03 の既定）を据え置き、fullbleed / slider / cards-carousel / product-shot を選べる型として揃える。cards-carousel（製品・コースをカードで横送り）と product-shot（製品画像の hero）が新規の型。")
w(f"- HP セクション: 出現ページ数上位の区間を固定ページ用パーツにし（§4）、区間セットは「観察された並び」の代表 3〜4 本（企業 / サービス / メディア / 店舗・スクール）として持つ。先頭セクションの最多は " + " / ".join(f"{k} {pct(v, len(HF))}" for k, v in sorted(fs.items(), key=lambda kv: (-kv[1], kv[0]))[:3]) + "。")
w(f"- イベント: hero は date-place-block が主集計 {pct(sum(1 for e in EM if e['hero']=='date-place-block'), len(EM))} で、前回台帳（前回台帳 home-event-recapture, 主集計 n=" + str(len(PEM)) + "）の " + " / ".join(f"{k} {pct(v, len(PEM))}" for k, v in Counter(e['hero'] for e in PEM).most_common(2)) + " と食い違う。両台帳の合算で判断せず、§5 の規則（両台帳で最多の型のみ暫定既定値候補）では候補にならない。date-place-block を暫定既定値へ上げるのは**規則の例外**（前回 n=8 の小標本より本台帳 n を優先する）で、PO 決定 WT-EVT-0288（2026-09-06「全部追加」）により既定値へ採用した。info_block は icon-list が " + pct(sum(1 for e in EM if e['info_block']=='icon-list'), len(EM)) + "。apply は external-form / closed-notice が多く、inline-form は観察 0 件（" + str(sum(1 for e in EM if 'inline-form' in split(e['apply']))) + "/" + str(len(EM)) + "）。target-audience（対象者）・tickets/price・organizer が高頻度で、試作 03 の 11 区間に無い「対象者」「主催」「参加費」を区間として加える。")
w("- キャンペーン / コンテスト（D）: prizes / entry-steps / target-products / judges の区間と、応募方法（`receipt-upload` / `postcard` / `messaging-app`、主集計での出現 " + ' / '.join(f"{k} {pct(v, len(EM))}" for k, v in Counter(x for e in EM for x in split(e['apply']) if x in ('receipt-upload','postcard','messaging-app')).most_common()) + "）を語彙 receipt-upload / postcard / messaging-app として正式採用し event_apply の追加型として持つ（PO 決定 WT-EVT-0288 で採用済み）。")
print("\n".join(L))
