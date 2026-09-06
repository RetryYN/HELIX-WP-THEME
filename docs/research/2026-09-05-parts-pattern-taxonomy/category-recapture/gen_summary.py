#!/usr/bin/env python3
"""observations.json → summary.md を全生成する（手計算値を残さないため、本文の数値はすべてここで計算する）"""
import json, re
from collections import Counter, OrderedDict
O = json.load(open('observations.json'))
def pct(a, b): return f"{a}/{b}({a/b*100:.1f}%)" if b else "n/a"
def ordered_counter(vals):
    c = Counter(vals); return sorted(c.items(), key=lambda kv: (-kv[1], kv[0]))
main = [e for e in O if e['fetched'] == 'full' and not e.get('page_kind')]
excluded = [e for e in O if not (e['fetched'] == 'full' and not e.get('page_kind'))]
N = len(main)
byp = Counter(e['site_pattern'] for e in main)
pats = sorted(byp)
SINGLE = ['header','lead','children_nav','list_layout','columns_pc','filter_sort','pagination','ranking','pickup','minihome','cta']
MULTI = ['card_elements','sidebar']
def dist(entries, k):
    vals = [e[k] for e in entries]; na = sum(1 for v in vals if v == 'na'); valid = [v for v in vals if v != 'na']
    return len(valid), na, ordered_counter(valid)
def mdist(entries, k):
    c = Counter(); 
    for e in entries: 
        for v in e[k]: c[v] += 1
    return sorted(c.items(), key=lambda kv: (-kv[1], kv[0]))
def val(entries, k, v):
    n, na, c = dist(entries, k); d = dict(c); return d.get(v, 0), n
L = []
w = L.append
w("# カテゴリ一覧ページ デザイン観察リサーチ（R17）集計（最新: codex-astra 10巡目反映・summary は gen_summary.py の全生成）")
w("")
w("固有名・URLは書かない（mapping.jsonのみに記録）。id は observations.json 参照。")
w("本ファイルは `python3 gen_summary.py > summary.md` で observations.json から**全文を生成**する。本台帳の集計値（件数・%・区分 n）はすべてスクリプト計算値。既存台帳からの引用値と語彙の定義上の数値は計算対象外で、その旨を各所に明記する。")
w("### 対応履歴（過去の巡回。件数は当時の値で、現在の値は §1 を正とする）")
w("- 10巡目: ranking の優先順（本体側 > sidebar）を定義し assert を全 entry 対象に。1col と sidebar 併存の位置は未確認と明記。訂正件数を 14 件に訂正。")
w("- 9巡目: ranking の記録基準を統一（sidebar に popular-ranking がある entry は ranking=sidebar。none との併存を全 14 件（主集計内 12・主集計外 2）訂正）。columns_pc と sidebar の意味を §0 に定義。既存台帳との比較語を数値併記のみに変更。")
w("- 8巡目: E12 の sidebar から重複した none を除去（sidebar=none の過大計上 1 件を是正）。")
w("- 7巡目: title 注記の説明を G05（date+title）と矛盾しない表現に修正。")
w("- 6巡目: card_elements の `title` は記録基準が全件で揃っていないため比率解釈対象外と明記。更新履歴を最新と過去に分離。")
w("- 5巡目: A04・G08 を card_elements=[title] に訂正して採用、G07 を empty-list-at-observation で除外。区分別件数・変動幅を集計から生成。`line` 語彙コードの注記。")
w("- 4巡目: 適格判定の追跡条件を §0 に明文化。A05・D07（記事カードなし）・D04（利用者投稿まとめ）を除外。『多数派』を撤回。")
w("codex-astra 3巡目（2026-09-06）の指摘反映（当時）:")
w("- 主集計の適格基準を「**編集記事の一覧ページ**（記事一覧）」と明文化し、境界例 D08（媒体を列挙する一覧記事）と E02（利用者投稿の一覧）を page_kind で主集計から除外・別掲")
w("- 区分別の説明文の件数を機械集計と一致させる（A の name-desc-image、C の minihome 分母、grid 系合計の名称と範囲、E の pagination）")
w("- 「最も定型的」等の解釈語を削り、既存台帳の引用値は出典を明示して「本台帳からは再計算不能」と注記")
w("")
w("## 0. 主集計の適格基準")
w("")
w("項目の意味（Astra 9巡目で明文化）: `ranking` はランキング表示の位置で、複数位置に併存する場合は一覧本体側を優先して 1 値を記録する（優先順: top=一覧上部 > inline=一覧内 > bottom=一覧下 > sidebar=補助列 > none）。したがって sidebar に popular-ranking がある entry は ranking≠none（本体側にあれば top/inline/bottom、無ければ sidebar）。全 entry を対象に assert で担保。`columns_pc` は一覧本体の列構成（1col=一覧が全幅）、`sidebar` は一覧の外にある補助ウィジェットの内容で、位置（右列か一覧の下か）は記録していない。したがって 1col と sidebar ありは併存し得る（配置位置は未確認。矛盾とは扱わない。該当 " + str(sum(1 for e in main if e["columns_pc"]=="1col" and e["sidebar"]!=["none"])) + " 件）。")
w("語彙コードの注記: cta=line 等の `line` は「メッセージングアプリ導線」を表す既存語彙コードで、第三者サービス名としての記述ではない（台帳間で語彙を揃えるため保持）。")
w("")
w("- fetched=full（本文 HTML を取得できた）かつ page_kind なし。")
w("- page_kind は「取得はできたが観察対象として不適格」の理由コード: `product-list`（商品カタログ・EC トップ）、`not-a-category-list`（診断ツール・トップページ等で記事一覧のカード構造がない）、`listicle-article`（複数の対象を列挙する **一覧記事**。記事本文であって記事一覧ではない）、`ugc-post-list`（利用者投稿の一覧。編集記事の一覧ではない）。")
w("- 「記事一覧」= サイト運営者が編集した記事（コラム・ニュース・お知らせ・ブログ）が一覧カードで並ぶページ。判定の追跡条件: card_elements が空（記事カードが無い）なら not-a-category-list、利用者投稿（口コミ・コーディネート・まとめ）の一覧なら ugc-post-list、複数対象を列挙する記事本文なら listicle-article。4巡目で A05・D07（card_elements 空）・D04（利用者投稿まとめ）を除外。5巡目: 記事タイトルのテキストリンクだけの一覧は card_elements=[title] として採用（A04・G08 を訂正）、取得時に一覧が空で観察できなかったものは empty-list-at-observation として除外（G07）。主集計に card_elements 空の entry は残さない。")
assert all(e["card_elements"] for e in main), "card_elements 空の entry が主集計に残っている"
assert all(not ("none" in e["sidebar"] and len(e["sidebar"]) > 1) for e in main), "sidebar に none と他の値が同居している"
assert all(not ("popular-ranking" in e["sidebar"] and e["ranking"] == "none") for e in O), "sidebar に popular-ranking があるのに ranking=none（全 entry 対象）"
w("")
w("## 1. 件数の内訳（fetched / page_kind / 主集計対象）")
w("")
w(f"observations.json 全体は **{len(O)}件**。")
w("")
w("```")
w(f"total_all_entries: {len(O)}")
w(f"fetched_counts_all_entries: {dict(Counter(e['fetched'] for e in O))}")
w(f"page_kind_counts_all_entries: {dict(Counter(e.get('page_kind') or 'main' for e in O))}")
w(f"main_pool_size: {N}")
w("excluded_entries:")
for e in sorted(excluded, key=lambda x: x["id"]): w(f"  {e['id']} {e['fetched']} {e.get('page_kind')}")
w(f"by_pattern_main: {dict(sorted(byp.items()))}")
w("```")
w("")
w(f"- fetched: full {sum(1 for e in O if e['fetched']=='full')} / partial {sum(1 for e in O if e['fetched']=='partial')}（partial は {', '.join(e['id'] for e in O if e['fetched']=='partial')}）。")
w("- 除外の重複加算なし（1 entry に理由は 1 つ。A03 は partial かつ not-a-category-list だが 1 件として数える）。")
w("- 区分: A=比較・アフィリエイト、B=ニュース、C=企業オウンドメディア、D=ポータル、E=EC の記事一覧（コラム/特集/お知らせ）、F=個人・専門ブログ、G=官公庁・大学・団体。区分別件数は " + ", ".join(f"{k} {v}" for k, v in sorted(byp.items())) + "（G が多いのは自治体サイトが取得しやすかった結果で、意図的な積み増しではない）。")
w("")
w(f"## 2. 観察項目の型内訳（主集計 n={N}、na 除外・分母併記、機械集計そのまま）")
w("")
w("```")
for k in SINGLE:
    n, na, c = dist(main, k); w(f"[{k}] valid_n={n} na={na} total={N}")
    for v, cnt in c: w(f"   {v}: {cnt} ({cnt/n*100:.1f}%)")
w(f"[count_per_page] na={sum(1 for e in main if e['count_per_page']=='na')}/{N}")
for k in MULTI:
    w(f"[{k}] denom(n_main)={N}")
    for v, cnt in mdist(main, k): w(f"   {v}: {cnt} ({cnt/N*100:.1f}%)")
w("```")
w("")
w("card_elements の `title` は一部の entry（A04・G05・G08）でのみ明示記録され、他の entry ではタイトルの有無を記録していない（全件で記録基準が揃っていない）。したがって `title` の行は「タイトルの存在率」ではなく記録の不統一を含むため**比率の解釈対象外**とする。")
w("")
w("minihome は none/no の表記ゆれを `none` に統一済み。sidebar は空配列を `[\"none\"]` に統一した上で multi-select 集計（分母は n_main、合計は 100% を超える）。")
w("")
g3, _ = val(main,'list_layout','grid-3'); g2,_ = val(main,'list_layout','grid-2'); fpg,_ = val(main,'list_layout','featured-plus-grid'); tl,_ = val(main,'list_layout','thumb-list'); txt,_ = val(main,'list_layout','text-list')
w(f"list_layout の **grid 系合計**（grid-3 + grid-2 + featured-plus-grid。thumb-list は「サムネイル付き縦リスト」でグリッドではないため含めない）= {g3}+{g2}+{fpg} = {pct(g3+g2+fpg, N)}。grid-3 単体は {pct(g3, N)} で過半数ではなく、text-list {pct(txt, N)} と並ぶ。")
w("")
w(f"## 3. 区分別の型内訳（主集計、機械集計そのまま）")
w("")
w("```")
for k in ['header','list_layout','children_nav','pagination','ranking','minihome']:
    w(f"[{k}]")
    for p in pats:
        es = [e for e in main if e['site_pattern']==p]; w(f"  {p}(n={len(es)}): {dict(Counter(e[k] for e in es))}")
w("```")
w("")
w(f"### 区分別に言えること（件数を明示し、断定を避ける。各区分 n は {min(byp.values())}〜{max(byp.values())} で 1 件の異同が {100/max(byp.values()):.0f}〜{100/min(byp.values()):.0f} ポイント動く）")
w("")
def P(p): return [e for e in main if e['site_pattern']==p]
def cnt(p,k,v): return sum(1 for e in P(p) if e[k]==v)
def cnt_ex_na(p,k): return sum(1 for e in P(p) if e[k]!='na')
A=P('A'); w(f"- A（比較・アフィリエイト、n={len(A)}）: header は name-count {cnt('A','header','name-count')}件・name-desc-image {cnt('A','header','name-desc-image')}件・name-only {cnt('A','header','name-only')}件。name-desc-image は他区分では {sum(1 for e in main if e['site_pattern']!='A' and e['header']=='name-desc-image')} 件で、A の {cnt('A','header','name-desc-image')}/{len(A)} をもって「A に多い」とは言えない。ranking=top は {cnt('A','ranking','top')}/{len(A)} で全区分中最多。")
B=P('B'); w(f"- B（ニュース、n={len(B)}）: children_nav=chips {cnt('B','children_nav','chips')}/{len(B)}。list_layout は text-list {cnt('B','list_layout','text-list')}・featured-plus-grid {cnt('B','list_layout','featured-plus-grid')}・grid-3 {cnt('B','list_layout','grid-3')}・thumb-list {cnt('B','list_layout','thumb-list')} に分散。")
C=P('C'); w(f"- C（企業オウンドメディア、n={len(C)}）: list_layout は grid-3 {cnt('C','list_layout','grid-3')}＋grid-2 {cnt('C','list_layout','grid-2')}={cnt('C','list_layout','grid-3')+cnt('C','list_layout','grid-2')}/{len(C)}。minihome=yes は na を除いた {cnt_ex_na('C','minihome')} 件中 {cnt('C','minihome','yes')} 件（{cnt('C','minihome','yes')/cnt_ex_na('C','minihome')*100:.1f}%。na 込みの分母なら {cnt('C','minihome','yes')}/{len(C)}）。")
D=P('D'); w(f"- D（ポータル、n={len(D)}）: children_nav は {dict(Counter(e['children_nav'] for e in D))}。値の種類数は他区分と同程度で「最も多様」とは言えない。")
E=P('E'); w(f"- E（EC 記事一覧、n={len(E)}）: list_layout=grid-3 {cnt('E','list_layout','grid-3')}/{len(E)} で全区分中最も高い比率。children_nav は sidebar-tree {cnt('E','children_nav','sidebar-tree')}・chips {cnt('E','children_nav','chips')}・none {cnt('E','children_nav','none')}。pagination は numbers {cnt('E','pagination','numbers')}・load-more {cnt('E','pagination','load-more')}・prev-next {cnt('E','pagination','prev-next')}・none {cnt('E','pagination','none')}（numbers が最多）。")
F=P('F'); w(f"- F（個人・専門ブログ、n={len(F)}）: header=name-only {cnt('F','header','name-only')}/{len(F)} で全区分中最多。pagination=prev-next {cnt('F','pagination','prev-next')}/{len(F)}、children_nav=sidebar-tree {cnt('F','children_nav','sidebar-tree')}/{len(F)}。")
G=P('G'); w(f"- G（官公庁・大学・団体、n={len(G)}）: list_layout=text-list {cnt('G','list_layout','text-list')}/{len(G)}、header=name-count {cnt('G','header','name-count')}/{len(G)}、ranking=none {cnt('G','ranking','none')}/{len(G)}。（観察事実のみ。装飾の多寡は評価しない）")
w("")
w("## 4. 既存台帳との差分の見立て（仮説として明示）")
w("")
w("既存台帳 = リポ内 `docs/research/2026-09-05-parts-pattern-taxonomy/recapture-v2/`（PR #140）のカテゴリ面集計。そこに記載の値（名前のみ 42% / 子導線 chips 29% / ミニ HOME 7〜8% / ランキング none 85%）は**引用**であり、本台帳の JSON からは再計算できない。語彙も区分構成も異なるため、以下は比較条件の違いを含む「Claude 案」。")
w("")
hc,_=val(main,'header','name-count'); ho,_=val(main,'header','name-only'); ch,chn=val(main,'children_nav','chips'); mh,mhn=val(main,'minihome','yes'); rn,_=val(main,'ranking','none'); pn,_=val(main,'pagination','none'); pnum,_=val(main,'pagination','numbers')
w(f"- header: name-only {pct(ho,N)}、name-count {pct(hc,N)}。既存台帳が name-count を「名前のみ」に含めていたなら {pct(ho+hc,N)} 相当で 42% とは乖離する。語彙定義の突合せをしない限り結論は出せない（Claude 案）。")
w(f"- children_nav=chips {pct(ch,chn)}（na 除外）。既存台帳の引用値は 29%（比較条件が異なるため近い/遠いの判断はしない）。")
w(f"- pagination: none {pct(pn,N)}、numbers {pct(pnum,N)}。区分別では G が numbers {cnt('G','pagination','numbers')}/{len(G)}・none {cnt('G','pagination','none')}/{len(G)}、A が none {cnt('A','pagination','none')}/{len(A)}、D が none {cnt('D','pagination','none')}/{len(D)}、E は numbers {cnt('E','pagination','numbers')}/{len(E)} が最多。「番号ページ送りが多数派」という既存記述との差は A・D の none の多さが一因である可能性がある（Claude 案。既存台帳の区分構成が不明で検証不能）。")
w(f"- minihome=yes {pct(mh,mhn)}（na {N-mhn} 件除外）。既存 7〜8% との差は、本回が「視覚的に区切られた子カテゴリ別セクション」まで yes と判定した語彙の緩さが原因の可能性がある（Claude 案。語彙定義の再すり合わせが必要）。")
w(f"- ranking=none {pct(rn,N)}。既存 85% より低いのは、ranking=top の多い A（{cnt('A','ranking','top')}/{len(A)}）を含む区分構成の違いが一因の可能性（Claude 案）。")
w("")
w("## 5. 限界")
w("")
w(f"- 主集計は区分ごと n={min(byp.values())}〜{max(byp.values())} で代表性は小さい。E は専門小売・工芸品・食品・作業服系の D2C/中堅 EC に偏り、F はガジェット・節約・釣り・筋トレに偏る。")
w("- 大手（ニュース・EC・求人）の多くが 403/DNS/タイムアウトで取得不能だったため中小規模へ切り替えた。区分内の「大手 vs 中小」の軸は中小に偏る。")
w("- SP 差分は全件 na。PC 想定の HTML 読み取りのみ。")
w("- JS 描画で実カードが取得できていない可能性、children_nav / filter_sort の語彙ゆれ（tabs の混入、image-banners の判定）が残る。")
w("- page_kind は WebFetch 経由 1 回の判定に基づく。D08・E02 は 3 巡目で境界例として除外したが、目視再確認はしていない。")
w("- 既存台帳との差分は区分定義・サンプル構成の違いを制御できておらず、事実の差ではなく比較条件の違いに起因する可能性が高い。")
w("")
w("## 6. 試作既定値への持ち込み方（Claude 案、PO 確認前提）")
w("")
w(f"- 観察された型から選ぶ試作候補（最多型とは限らない。全体集計であり用途別集計ではないため、特定業種での最多型の証拠には使えない）: header name-count {pct(hc,N)}、children_nav chips {pct(ch,chn)}（none は {pct(val(main,'children_nav','none')[0],chn)}）、list_layout grid-3 {pct(g3,N)}（text-list は {pct(txt,N)}）、columns 2col-sidebar-right {pct(val(main,'columns_pc','2col-sidebar-right')[0],N)}、pagination numbers {pct(pnum,N)}（none は {pct(pn,N)}）。各項目の最多型は §2 の表を正とし、ここでは順位を主張しない。「一般的標準」ではなく**カテゴリ面の試作候補**としてのみ使う。")
w("- 適用範囲: 試作 03 のカテゴリ面 variant の追加候補の選定。検証条件: 試作を PO が見て反応を返すこと（prototype reaction）。要求への昇格は別途 PO 判断。")
print("\n".join(L))
