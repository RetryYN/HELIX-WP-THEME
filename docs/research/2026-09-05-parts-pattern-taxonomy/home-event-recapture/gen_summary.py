#!/usr/bin/env python3
"""observations-home.json / observations-event.json → summary.md 全生成（手計算値を残さない）"""
import json
from collections import Counter
H = json.load(open('observations-home.json')); E = json.load(open('observations-event.json'))
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
HF = [e for e in H if e['fetched'] is True]; HX = [e for e in H if e['fetched'] is not True]
EF = [e for e in E if e['fetched'] is True]; EP = [e for e in E if e['fetched'] == 'partial']; EX = [e for e in E if e['fetched'] is False]
MAIN_KIND = 'individual-event-or-campaign'
def Hp(p): return [e for e in HF if e['site_pattern']==p]
HN=[len(Hp(p)) for p in sorted(set(e['site_pattern'] for e in HF))]
EM = [e for e in EF if e.get('page_kind') == MAIN_KIND]; EO = [e for e in EF if e.get('page_kind') != MAIN_KIND]
L = []; w = L.append
w("# HP・イベントページ デザイン観察 サマリー（2026-09-06、最新: codex-astra 7巡目反映・gen_summary.py の全生成）")
w("")
w("固有名・URLは本ディレクトリの mapping.json のみに記載。本ファイルは `python3 gen_summary.py > summary.md` で observations-home.json / observations-event.json から**全文を生成**し、本台帳の集計値（件数・%・区分 n・変動幅）はすべてスクリプト計算値。既存台帳からの引用値と語彙の定義上の数値は計算対象外で、その旨を各所に明記する。%は四捨五入で合計が 100% から ±1 ポイントずれることがある。")
w("")
w("### 対応履歴（過去の巡回。件数は当時の値で、現在の値は §1 を正とする）")
w("- 7巡目: E03（2020 年のオンデマンド配信・継続受付の根拠なし）を E06 と同基準で除外。履歴の件数を当時の固定値に変更。")
w("- 6巡目: E06（2020 年開催・フォーム残存で採用根拠を追跡できない）を E19 と同基準で除外。更新履歴を最新と過去に分離。")
w("- 5巡目: E19（開催後取得で募集期間中の申込ページだった証拠なし）を除外。D の apply 解釈文・区分 n・変動幅を集計から生成。`line` 語彙コードの注記。複数選択行に欠測込み値を併記。")
w("- 4巡目: 募集終了後ページの採用条件（同一ページに closed-notice/ended、E17・E24）を明文化。E25（複数催事の入口）を除外。分母を 2 段（集計対象集合 / 欠測除外後）に整理。")
w("codex-astra 3巡目の指摘反映（当時）: (1) イベントの hero・info_block を「主集計（個別募集ページ）」と「取得全体の参考値」に分けて表示、(2) 主集計定義「来場者・参加希望者がそのページから応募/申込を行う個別ページ」を E14（無料参加で申込導線なし）にも文字通り適用して除外（E20 と同じ基準）→ 主集計 n=12（当時）、(3) HP・イベントとも D 区分単独の値を提示、(4) 分母名を「取得件数 / 観察可能件数 / 有効件数」で統一、(5) 「優勢」等の解釈語を削除、(6) 固有名の残存を除去。")
w("")
w("## 0. 用語・分母・ラベルの定義")
w("")
w("語彙コードの注記: contact_band=line・fixed=float-line・apply=other:messaging-app の `line` は「メッセージングアプリ導線」を表す既存語彙コードで、第三者サービス名としての記述ではない（category 台帳と語彙を揃えるため保持）。")
w("")
w("- **na**: 取得できた（fetched:true）が、その項目の値を本文から判定できない。**n/a**: 対象外（このページ自体に当該情報が存在しない）。**fetched:false**: 取得失敗（どの集計にも含めない）。**fetched:\"partial\"**: 本文未取得（全比率から除外）。")
w("- 分母の考え方: まず**集計対象集合**（HP = 取得件数、イベント = 主集計）を決め、次に**項目ごとの欠測（na・n/a）除外後の分母**を行内に明記する。欠測がある項目は欠測除外後の値を主表示、欠測込みの値を括弧で併記する。")
w("- ラベル 3 種: **観察事実**（件数と%）／**Claude 案**（解釈・仮説）／**暫定既定値**（試作へ持ち越す候補、PO 確認前提）。小標本（n≤5）の最多型は既定値化の根拠にしない。")
w("")
w("### site_pattern 判定基準")
w("- HP: A=製造/士業/医療（中小企業コーポレート）、B=SaaS・サービスのトップ、C=オウンドメディア・比較/ランキングメディア、D=店舗・自由診療クリニック・スクール、E=学校法人・大学・団体。")
w("- イベント: A=セミナー/ウェビナー個別申込、B=展示会・カンファレンス、C=地域フェス・祭り、D=期間限定キャンペーン・コンテスト・学校の参加型催事。")
w("")
w("### page_kind（イベントのみ）と主集計定義")
w("主集計 = 「来場者・参加希望者が**そのページから応募/申込を行う**個別ページ」（`individual-event-or-campaign`）。募集終了後に取得したページは、申込導線の位置に closed-notice / ended 表示を置く**同一ページ**が募集期間中の申込ページだった場合に限り採用する（apply=closed-notice、status_badge=ended として記録。E17・E24）。複数催事への入口サイトは単一催事ページと判別できないため除外（E25）。この定義で以下を除外・別掲する。")
for e in sorted(EO, key=lambda x: x['id']): w(f"- {e['id'].replace('site-','')} `{e.get('page_kind')}`: {e['notes'].split(' / ')[-1]}")
w("")
w("## 1. 件数・fetched 内訳")
w("")
w(f"### HP {len(H)}件")
w(f"- fetched:true {len(HF)} / fetched:false {len(HX)}（{', '.join(e['id'].replace('site-','') for e in HX)}）。")
w(f"- site_pattern 内訳（取得件数 {len(HF)}）: " + " / ".join(f"{k}={v}" for k, v in sorted(Counter(e['site_pattern'] for e in HF).items())) + "。")
w("")
w(f"### イベント {len(E)}件")
w(f"- fetched:true {len(EF)} / partial {len(EP)}（{', '.join(e['id'].replace('site-','') for e in EP)}）/ fetched:false {len(EX)}（{', '.join(e['id'].replace('site-','') for e in EX)}）。")
w(f"- 取得 {len(EF)} 件のうち page_kind 除外 {len(EO)} 件 → **主集計（個別募集ページ）n={len(EM)}**。site_pattern 内訳: " + " / ".join(f"{k}={v}" for k, v in sorted(Counter(e['site_pattern'] for e in EM).items())) + "。")
w("")
w(f"## 2. HP: 型内訳（観察事実。集計対象集合 = 取得件数 n={len(HF)}。項目に na がある行は欠測除外後の分母を行内に明記）")
w("")
for k in ['hero','hero_cta','news','contact_band']: w(fmt_dist(HF, k, '取得件数'))
dbl = [e for e in HF if e['hero_cta']=='double']; tf = [e for e in HF if e['contact_band']=='tel+form']; both = [e for e in dbl if e['contact_band']=='tel+form']
w(f"- **double × tel+form の重複**: double {len(dbl)} 件と tel+form {len(tf)} 件の重複は {len(both)} 件（double の {len(both)/len(dbl)*100:.0f}%、tel+form の {len(both)/len(tf)*100:.0f}%）。double は「CTA ボタンが 2 個」の構造観察で、用途（電話/フォーム）を意味しない。")
w(multi(HF, 'fixed', '取得件数'))
w(multi(HF, 'header', '取得件数'))
w(multi(HF, 'footer', '取得件数'))
w(f"- **sections_order 頻出**（延べ回数/採用ページ数、取得件数 n={len(HF)}、`other:*` を除く上位）: {sections(HF)}。")
w("")
w(f"### 区分別の値（観察事実。区分 n が {min(HN)}〜{max(HN)} で、1 件で {100/max(HN):.0f}〜{100/min(HN):.0f} ポイント動く）")
w("")
def Hp(p): return [e for e in HF if e['site_pattern']==p]
for p in sorted(set(e['site_pattern'] for e in HF)):
    es = Hp(p); n = len(es)
    bits = [f"news/greeting が先頭 2 セクション以内 {pct(sum(1 for e in es if first2(e,['news','greeting'])), n)}",
            f"hero_cta=none {pct(sum(1 for e in es if e['hero_cta']=='none'), n)}",
            f"blog-latest {pct(sum(1 for e in es if has(e,'blog-latest')), n)}",
            f"voice/testimonial {pct(sum(1 for e in es if has(e,'voice/testimonial')), n)}",
            f"price または faq {pct(sum(1 for e in es if has(e,'price') or has(e,'faq')), n)}",
            f"news {pct(sum(1 for e in es if has(e,'news')), n)}", f"staff {pct(sum(1 for e in es if has(e,'staff')), n)}",
            f"access/map {pct(sum(1 for e in es if has(e,'access/map')), n)}", f"recruit {pct(sum(1 for e in es if has(e,'recruit')), n)}",
            f"banner-row {pct(sum(1 for e in es if has(e,'banner-row')), n)}"]
    w(f"- {p}（n={n}）: " + " / ".join(bits) + "。")
w(f"- **Claude 案**: B（SaaS）で voice/testimonial・price・faq は一部に見られるが「共通の型」とは言えない水準。E（学校法人）の値は観察標本 {len(Hp('E'))} 件に限った結果で一般化しない。D は上記の D 単独行を根拠とし、D+E 合算は用いない。")
w("")
w(f"## 3. イベント: 型内訳")
w("")
w(f"### 3a. 主集計（個別募集ページ n={len(EM)}、site_pattern " + " / ".join(f"{k}{v}" for k, v in sorted(Counter(e['site_pattern'] for e in EM).items())) + "）")
w("")
for k in ['hero','info_block','apply','schedule','status_badge','speakers','countdown','map','fixed','share']: w(fmt_dist(EM, k, '主集計'))
w(f"- **sections_order 頻出**（延べ回数/採用ページ数、主集計 n={len(EM)}、`other:*` を除く）: {sections(EM)}。")
w("")
w(f"### 3b. 参考: 取得全体（n={len(EF)}、page_kind 除外 {len(EO)} 件を含む。主集計の値ではない）")
w("")
for k in ['hero','info_block']: w(fmt_dist(EF, k, '取得件数'))
tbl = [e for e in EF if e['info_block']=='table']
w(f"- info_block=table の内訳: " + "・".join(f"{e['id'].replace('site-','')}[{e['site_pattern']}]" for e in tbl) + "（セミナーに限定されない）。")
w("")
def Ep(p): return [e for e in EM if e['site_pattern']==p]
EN=[len(Ep(p)) for p in sorted(set(e["site_pattern"] for e in EM))]
w(f"### 3c. 区分別（主集計内、観察事実。区分 n が {min(EN)}〜{max(EN)} で断定しない）")
w("")
for p in sorted(set(e['site_pattern'] for e in EM)):
    es = Ep(p); n = len(es); ids = "/".join(e['id'].replace('site-','') for e in es)
    if n == 1:
        e = es[0]; w(f"- {p}（n=1、{ids}）: サンプル 1 件のため型として集計しない。単体の値: hero={e['hero']}、info_block={e['info_block']}、apply={e['apply']}、schedule={e['schedule']}。"); continue
    w(f"- {p}（n={n}、{ids}）: speakers≠none {pct(sum(1 for e in es if e['speakers']!='none'), n)} / info_block≠none {pct(sum(1 for e in es if e['info_block']!='none'), n)} / apply {dict(Counter(e['apply'] for e in es))} / access/map {pct(sum(1 for e in es if has(e,'access/map')), n)} / hero {dict(Counter(e['hero'] for e in es))}。")
w(f"- **Claude 案**: A（セミナー）は speakers を持つ比率が相対的に高い、D（キャンペーン・学校催事）は apply が {" / ".join(sorted(set(e['apply'] for e in Ep('D'))))} に分散する、という方向は観察と整合するが、各区分 n={min(EN)}〜{max(EN)} で 1 件の異同で {100/max(EN):.0f}〜{100/min(EN):.0f} ポイント動くため暫定既定値化はしない。")
w("")
w("### 3d. page_kind 別掲（主集計外、個別事実のみ）")
w("")
w(" ".join(f"{e['id'].replace('site-','')}[{e.get('page_kind')}] hero={e['hero']}/info_block={e['info_block']}/apply={e['apply']}。" for e in sorted(EO, key=lambda x: x['id'])))
w("")
w("## 4. 既存台帳との差分の見立て（Claude 案）")
w("")
w("既存台帳 = リポ内 `docs/research/2026-09-05-parts-pattern-taxonomy/`（実サイト観察・LP 台帳）。そこに記載の HP hero 比率（fullbleed 17% / text-only 13% / split 12% / slider 11%）と、LP 台帳 D 区分 8 件「埋め込みフォーム 75%」は**引用**であり、本台帳の JSON からは再計算できない。n・語彙定義も未確認のため、以下は比較条件の違いを含む Claude 案。")
hv, _ = line_dist(HF, 'hero'); hc = Counter(hv)
w(f"- HP hero: 本回は text-only {pct(hc['text-only'],len(HF))}・slider {pct(hc['slider'],len(HF))}・fullbleed-photo-overlay {pct(hc['fullbleed-photo-overlay'],len(HF))}。既存台帳と順位が入れ替わるが、サンプル構成差を排除できていない。")
w("- HP セクション: news・service-cards が最頻出という方向は既存の上位セクションと一致。article-grid・category-cards は本回は相対的に少ない。")
D = Ep('D')
w(f"- イベント apply: 本回 D（n={len(D)}）は {dict(Counter(e['apply'] for e in D))}。「埋め込みフォーム」の定義が本回の inline-form と一致するか未確認のため、整合・矛盾いずれも断定しない。")
w("")
w("## 5. 限界")
w("")
w("- 集計値は本スクリプトの機械集計。分母は行ごとに「集計対象集合の n」と「欠測除外後の n」を明記。引用値（§4）は計算対象外。")
w(f"- 主集計のイベント n={len(EM)}、区分 n={min(EN)}〜{max(EN)}。HP の区分 n={min(HN)}〜{max(HN)}。いずれも「傾向」は Claude 案として扱い、暫定既定値化はしない。")
w("- fetched は WebFetch による本文要約経由の観察。実ブラウザでの目視・スクリーンショット照合、SP 表示は未検証。")
w("- page_kind の適格性判定は観察者の解釈に依存する。E03・E06・E14・E16・E19・E20・E21・E25 は境界例として再分類した。今後さらに境界例が見つかる可能性がある。")
w(f"- countdown は主集計 {len(EM)} 件で {sum(1 for e in EM if e['countdown']!='none')} 件。few-seats（残席少）の明示例は {sum(1 for e in EM if e['status_badge']=='few-seats')} 件。存在しないとは言えず観測不足として扱う。")
w("")
w("## 6. 試作既定値への持ち込み方（Claude 案、PO 確認前提）")
w("")
hcta = Counter(e['hero_cta'] for e in HF); nw = Counter(e['news'] for e in HF); cb = Counter(e['contact_band'] for e in HF)
w(f"- HP 面の試作候補: hero text-only（最多 {pct(hc['text-only'],len(HF))}、過半数ではない）、hero_cta double {pct(hcta['double'],len(HF))}、news list-with-date {pct(nw['list-with-date'],len(HF))}、contact tel+form {pct(cb['tel+form'],len(HF))}。全 {len(HF)} 件の全体集計から観察した型で選ぶ候補であり用途別集計ではない（指定用途での最多型を示すものではない）。試作の用途は**中小企業コーポレート・店舗・スクール向け HP**に限定し、SaaS（B）・メディア（C）は区分行の値を別に見る。")
ap = Counter(e['apply'] for e in EM); ih = Counter(e['hero'] for e in EM); ii = Counter(e['info_block'] for e in EM)
w(f"- イベント面の試作候補: hero photo-overlay {pct(ih['photo-overlay'],len(EM))}、info_block inline-text {pct(ii['inline-text'],len(EM))}、apply inline-form {pct(ap['inline-form'],len(EM))}（n={len(EM)} の小標本）。「一般的標準」ではなく**セミナー・地域催事・キャンペーンの個別ページの試作候補**としてのみ扱う。")
w("- 適用範囲: 試作 03 の HP 面・イベント面の初期 variant 選定。検証条件: 試作を PO が見て反応を返すこと。要求への昇格は別途 PO 判断。")
print("\n".join(L))
