# デザイン試作 03 受入条件ドラフト（段5・Claude 案）

- 位置づけ: L2 プロト往復（前段の PO 発言〔会話、2026-09-05〕を出発点とする。この会話は `docs/requirements/discovery/events.jsonl` の `WT-EVT-0239`（`WT-DIR-FRONT-FIRST-01`、フロント先行）と `WT-EVT-0240`（`WT-DIR-AUTONOMY-01`、Astra 往復・merge 許可）に記録済み。ただし `WT-EVT-0239` の `claude_interpretation` 自身が明記するとおり、「比較・アフィリエイト媒体 1 パターンに絞った」のは Claude 案であり PO 決定ではない）の出力。**PO 反応待ちのドラフトであり、要求正本ではない**。
  `docs/requirements/l3/requirements-ir.json` / `acceptance-cases.json` / `docs/requirements/discovery/events.jsonl` は本 PR で変更していない。
- 凍結・採用・決定は主張しない。本書内の「% 観察」は台帳（`docs/research/2026-09-05-parts-pattern-taxonomy/`）の集計値、「既定値の案」「書き直し案」はすべて Claude の提案であり、PO が採否だけを判断する対象。
- 対象: デザイン系要件ファミリー（WT-FR-LOOK / PARTS / VOCAB / ZONE / SP / META / TPL / RECO / BANNER / LP / SECTION / TYPO / IMG / NAV と、画面表示に関わる WT-NFR-A11Y / SP / PERF）計 37 件。段4（LP、PR #143）merge 済みのため LP 2 件も他要件と同じ書式で記載している。
- 入力: デザイン試作 03（`docs/research/2026-09-05-design-prototype-03/README.md`、`results/verify.json`、`CATALOG-INDEX.json`）とパーツ別パターン台帳（`docs/research/2026-09-05-parts-pattern-taxonomy/by-purpose.md` §2/§2b、`README.md` §1/§1b）。
- 各節の書式: (a) 現行 statement / 既存 AC の要約（priority・既存 AC ID 付き）、(b) 試作 03 の実装 variant、(c) 書き直し案の受入条件（1 行 1 条件 + 検証手段）、(d) 台帳の観察 % と既定値の Claude 案、(e) PO に問うべき点（機能の採否のみ）。
- (e) 以外の未決事項は 4 節に分離する: 「問い一覧」＝ PO への機能採否のみ、「TL 判断事項」＝要求文は変えず検証工程・実装方式・AC 範囲を TL が決める事項、「要求変更の候補」＝既存要求の縮小・置換・緩和の提案（PO 判断待ち、問いにはしない）、「次段候補」＝次段の試作対象・優先順位の提案。

---

## WT-FR-ZONE-01（共有 slot 6 種）

**(a)** 本文前・関連前後・固定ページ上下・ヘッダー内・SP 下部固定・追尾サイドバーの 6 slot をテンプレ / パーツ内の挿入位置として持つ。既存 AC: 共通宣言 + device 別差分で描画され、空なら DOM に残らない。

*要件情報: WT-FR-ZONE-01: priority P0, 既存AC WT-AC-ZONE-01A/WT-AC-ZONE-01B*

**(b)** 試作 03 での実装（`?wt=` 軸と型名は README §1）: ヘッダー内 = `header:announce`（お知らせ帯 slot）、追尾サイドバー = `toc:float`（PC 1200px 以上）/ `share:float`、SP 下部固定 = `share:float`（SP 右下）、固定ページ相当 = `parts/cv-slot.html`（404 の CV 導線レーン、`.wt-cv`）、本文前後 = `pr` 表記（本文先頭自動挿入）と `parts/article-tail.html`（関連・CTA・著者・前後記事）。

**(c) 書き直し案**
1. ヘッダー直下 slot にお知らせ帯を出し、`wt=header:announce` で切替できる — 検証: `results/header-announce-pc.jpg` / `results/header-announce-sp.jpg` 目視 + `verify.json.summary.checks`（ヘッダー系タップ監査）。
2. PC 1200px 以上で目次フロート slot が本文と重ならず追従する — 検証: `README.md` §2.3、目視 `toc-float-pc.jpg`。
3. SP でシェア float と totop ボタンが互いに重ならない — 検証: `verify.json.fixedOverlap.sp.intersects === false`（この項目はシェア float と totop の重なりのみを測り、本文本体や CTA との重なりは別項目であり `fixedOverlap` の対象外。本文 / CTA との被覆有無は未検証）。
4. 404 の CV slot レーンに LP / 比較記事 / 問い合わせの 3 枠が出る — 検証: `verify.json.status404.has.cvSlot === 3`。
5. **空 slot が DOM に要素を残さない（お知らせ帯を閉じた場合等）ことは検証手段なし** — `verify.json.noJs`（`scripts/verify.mjs:29-43`）の `announceVisible` は `vis()`（`display`/`visibility`/矩形サイズによる可視性判定）であり、非表示（`display:none`）と DOM からの削除を区別しない。対象セレクターの要素数が 0 であることを確認する専用の検査が無く、on 状態の実測（`announceExists:true, announceVisible:true`）のみで、off 時の DOM 非残存は未検証。

**(d)** 台帳: 固定・追従パーツは「お知らせ帯 17%（PC top）/ 27%（SP top）」観察（README §1）。追尾サイドバー相当は目次「フロート PC25%・SP1%」観察（README §1「目次」、article/pc・article/sp の観察列。台帳の観察列と旧 Issue コメント列は分母が異なるため混同しない）。既定値案: お知らせ帯・目次フロート・SP シェア float はいずれも「既定 OFF またはコンテンツ次第」（現行 `wt_axes()` の既定は `header:search` / `toc:box` / `share:topbottom` で、フロート系は非既定）。
**(e)** 問い: 共有 slot は、内容が無い場合に空要素を DOM に残さない実装にする。採用するか。

---

## WT-FR-ZONE-02（ゾーン語彙 23 種 JSON schema・first-match-wins）

**(a)** ゾーン語彙 23 種を JSON schema で宣言し、creative は参照（ID）、overrides は first-match-wins の配列で持つ。既存 AC: schema 外 ID は拒否、複数規則同時適用は FAIL。

*要件情報: WT-FR-ZONE-02: priority P1, 既存AC WT-AC-ZONE-02A/WT-AC-ZONE-02B*

**(b)** 試作 03 は `functions.php` の `wt_axes()` に 34 の**選択軸**（header / eyecatch / toc / footer_extra 等）を列挙し、解決順「プレビュー引数 → 記事 post meta → theme_mod → 既定値」の**単一解決チェーン**で body class を出す実装（README §1 冒頭）。これは「軸ごとに 1 つの値を選ぶ」仕組みであり、要求が指す「ゾーンへ creative（コンテンツ）を配置する overrides 配列・first-match-wins」の仕組みそのものではない。

**(c) 書き直し案**
1. 34 軸が `functions.php` の `wt_axes()`（**PHP 配列**として実装、JSON ファイルとしての宣言ではない）に列挙でき、未定義の軸 key・値は body class に現れない — 検証: 目視でソースの `wt_axes()` 配列を確認（自動検査は未実装）。要求文言の「JSON schema で宣言する」を満たすには、`wt_axes()` の PHP 配列を JSON へ書き出すか、JSON schema から生成する変更が必要（現行は PHP 配列のみ）。
2. 解決順（プレビュー引数 → post meta → theme_mod → 既定値）が 1 箇所に決まっている — 検証: 目視でソースを確認。
3. **overrides の first-match-wins 配列は試作 03 に存在しないため検証手段なし。**

**(d)** 台帳の直接該当なし（軸の型数は他要件の台帳に分散）。既定値案: 軸選択方式（現行実装の解決順）を「ゾーン語彙 23 種」要求の実現手段として読み替える。
**(e)** 問い: なし（現行要求と異なる実現手段を採ったため、要求変更の候補 #1 へ）。

---

## WT-FR-ZONE-03（SP 下部固定の積層順・広告面積上限・初回モーダル禁止）

**(a)** 同意バー（下部固定選択時のみ）> メニュー > シェアの順で積層し CTA と重ねない。広告面積上限を宣言し、初回モーダルは同意バー以外禁止（LP は例外）。既存 AC: 3 要素同時有効化でも規約順で積層、CTA と重ならない。

*要件情報: WT-FR-ZONE-03: priority P2, 既存AC WT-AC-ZONE-03A/WT-AC-ZONE-03B/WT-AC-ZONE-03C/WT-AC-ZONE-03D*

**(b)** 試作 03 は「シェア float（SP 右下）」と「totop（page-top ボタン）」の重なりのみ実測（`verify.json.fixedOverlap`）。**同意バー（consent）は `wt_axes()` に軸として存在せず、試作 03 では実装していない**。広告面積上限の宣言・初回モーダル禁止の検査も試作 03 の範囲外。

**(c) 書き直し案**
1. SP でシェア float と totop ボタンが重ならず、両方がタップ到達可能 — 検証: `verify.json.fixedOverlap.sp.intersects === false` かつ `reachable` 全 true。
2. PC でも同様に重ならない — 検証: `verify.json.fixedOverlap.pc.intersects === false`。
3. **同意バーを含む 3 要素同時の積層順検査、広告面積上限、初回モーダル禁止は試作 03 に実装がなく検証不能**（次の試作課題）。

**(d)** 台帳: 固定・追従パーツの cookie 同意は「PC 15%・SP 11%」観察（README §1）。既定値案: PO 決定済み（events.jsonl WT-EVT-0214）で「同意バー既定 OFF、選択は ON/OFF と位置（先頭非固定 / 下部固定）の 2 つ」。
**(e)** 問い: なし（AC 範囲の整理は TL 判断事項 #1 へ）。

---

## WT-FR-PARTS-01（header/footer/sidebar/hero パターン群・footer サイトマップ/関連サイト枠）

**(a)** header（ロゴ位置・ナビ形・CTA・検索・固定挙動・透過）/ footer / sidebar / hero の複数案を同一 Block Types のパターン群として持つ。footer にサイトマップ枠と関連サイト / ページ枠。

*要件情報: WT-FR-PARTS-01: priority P0, 既存AC WT-AC-PARTS-01A/WT-AC-PARTS-01B/WT-AC-PARTS-01C*

**(b)** header 4 型（PC: with-search / logo-left-nav-right / logo-left-cta-right(no-nav) / with-announce-bar）+ SP 3 型（hamburger+search / hamburger-right / hamburger-left）+ 固定方式（sticky-header）（README §2.1）。footer は `footer_layout`（sitemap / single-row / columns-3）・`footer_above`（none / cta-band / banner-row / newsletter）・`footer_extra`（sns / sites / badges / address の組合せ）・`footer_totop`（off / button）の 4 軸で `parts/footer.html` を構成（README 冒頭表 + §2.12 表）。

**(c) 書き直し案**
1. header は PC 4 型・SP 3 型・お知らせ帯付き 1 型を `?wt=header:*` / `wt-sp-*` で切替でき、切替後も template part 参照が壊れない — 検証: `header-*-pc.jpg` / `header-*-sp.jpg` 目視 + `verify.json` のタップ監査（帯 + カルーセル + float 共有 72/72）。
2. **sitemap 型（既定の footer_layout）が `<details>` による SP アコーディオンへ縮退し、JS 無効でも中身が見える** — 検証: `verify.json.footerNoJs`（既定記事の sitemap 型 1 ケースのみを検査、`details:4, open:4, contentsVisible:true`）。**`footer_layout` の残り 2 型（single-row / columns-3）の JS 無効時の挙動は `footerNoJs` の対象外で未検証。**
3. footer に関連サイト / ページ枠（`footer_extra: sites`）とサイトマップ枠が独立に ON/OFF できる — 検証: `footer-extra-sites-*.jpg` / `footer-layout-sitemap-*.jpg` 目視 + `CATALOG-INDEX.json` の `part` 一致確認。
4. footer の文字コントラストが AA を満たす — 検証: `verify.json.footerContrast`（brand 15.83:1 等、全項目 pass）。

**(d)** 台帳: header レイアウトは「ロゴ左ナビ右 22%・検索付き 18%・ロゴ左 CTA 右 14%・お知らせ帯 10%」（top/PC、README §1）。footer は「mega(sitemap) 35%・single-row 23%」（PC top、README §1b）。既定値案: header は検索付き（既定）、footer は sitemap（既定）—現行実装と一致。
**(e)** 問い: なし（次段の型追加候補は次段候補 #1 へ）。

---

## WT-FR-PARTS-02（テンプレ変種名・footer カラム可変・wp_navigation 参照）

**(a)** テンプレ変種（single-2col / single-1col）と footer のカラム可変を「テンプレ名」で表し、属性で幅・余白を変えない。ナビは `wp_navigation` を ref 参照。

*要件情報: WT-FR-PARTS-02: priority P1, 既存AC WT-AC-PARTS-02A/WT-AC-PARTS-02B*

**(b)** 試作 03 の README には single-2col / single-1col の明示的な言及がなく、footer のカラム可変は `footer_layout: columns-3` の 1 型のみ（列数固定、可変列数の宣言は README に記載なし）。ナビの `wp_navigation` ref 参照は試作 03 のソース確認範囲外（README に言及なし）。

**(c) 書き直し案**
1. **`footer_layout:columns-3` が「テンプレ名」（既存 AC が求める `contentSize`/`wideSize` を再定義しないテンプレ変種）として実装されているかは、目視（`footer-layout-columns-3-*.jpg`）では確認できない**（軸値であることと、layout 属性オーバーライドを使わずテンプレ変種として実装されていることは別であり、スクリーンショットから属性オーバーライドの不使用は判定できない）。既存 AC（`WT-AC-PARTS-02A`: `contentSize`/`wideSize` が再定義されない、`WT-AC-PARTS-02B`: 層1を再定義すれば FAIL）を検証するには、`theme.json` の `contentSize`/`wideSize` 宣言箇所と `footer_layout:columns-3` 適用時の CSS ソースを確認する必要がある（未実施）。検証手段: ソース確認（`theme.json` / `theme.css` の `footer_layout` 分岐箇所）、次段課題。列数の可変性自体も未確認。
2. **single-2col / single-1col のテンプレ変種名、および `wp_navigation` ref 参照の検証は試作 03 の範囲外**。

**(d)** 台帳の直接該当なし（サイト構造の内部実装に近い要件のため）。
**(e)** 問い: なし（対象範囲の整理は TL 判断事項 #2 へ）。

---

## WT-FR-VOCAB-01（記事内語彙 14 種・新規ブロック上限 6+1・SP device 別差分・メディア枠 5 択）

**(a)** 14 語彙を core + block style で受け、新規ブロックは 6 種 + 空き 1 枠（上限 7）。SP は比較を横スクロール / カード、タブをアコーディオン等に変換。メディア枠は自前 SVG / アップロード / 写真 / 番号 / なしから選べる。

*要件情報: WT-FR-VOCAB-01: priority P0, 既存AC WT-AC-VOCAB-01A/WT-AC-VOCAB-01B/WT-AC-VOCAB-01C*

**(b)** 試作 03 は「新規ブロック」（`register_block_type()`、`functions.php:216-220`）と「ブロックパターン」（`patterns/*.php`、コア `wp:pattern {"slug":...}` 経由で挿入する定型コンテンツ）を区別する実装になっている。**新規ブロックは 5 種**: `helix-wt/category-children`（chips/cards/steps）、`helix-wt/category-minihome`、`helix-wt/category-ranking`、`helix-wt/tail-prevnext`、`helix-wt/tail-author`（`functions.php:216-220`）。**ブロックパターンは** `helix-wt/product-bundle`（商品カード束）、`helix-wt/cta-banner`、`helix-wt/cta-button`、`helix-wt/cta-box`、`helix-wt/linkcard`（`patterns/product-bundle.php` 等、`patterns/catalog-03.php:47-55` で `wp:pattern` として挿入）。囲み 7 型・見出し h2 6 型 / h3 3 型は block style（README §2.4/§2.5）。比較表は `.is-style-wt-compare`（PC sticky 先頭列 + SP カード縦積み、README §2.6）。

**(c) 書き直し案**
1. **比較表の `scripts/verify.mjs`（7. 節、`verify.mjs:160-164`）による検査は SP コンテキスト（`browser.newContext(SP)`）で取得している。** SP 幅の DOM で `thead` が保持され、`scope="col"` が 4 列分・`data-th` 属性が 24 セル分付く — 検証: `verify.json.table`（`theadIntact:true, thWithScopeCol:4, rowHeaders:8, dataTh:24, caption:true, pass:true`。**SP の DOM 検査であり、PC の sticky 表示や横スクロールの実測ではない**）。PC sticky / 横スクロールの実挙動、SP でのカード縦積み表示は別途 `table-compare-pc.jpg` / `table-compare-sp.jpg` の目視で確認する。
2. 記事内 CTA パターン 4 種（商品カード束 / バナー画像 / ボタンのみ / コピー付きボックス）を切り替えても本文構造（見出し・段落の位置）が変わらない — 検証: `cta-*-pc.jpg` / `cta-*-sp.jpg` 目視。
3. **既存 AC のうち件数上限条件（`WT-AC-VOCAB-01B`「8 つ目の新規ブロックがあれば FAIL」）に限り PASS と判定できる**: 試作 03 で `register_block_type()` により新規登録されたブロックは 5 種（`category-children` / `category-minihome` / `category-ranking` / `tail-prevnext` / `tail-author`）で、これを VOCAB-01 が数える対象そのものと解釈すると 5 ≤ 7（8 つ目は存在しない）となり `WT-AC-VOCAB-01B` の FAIL 条件に該当しない — 検証: 目視でソース `functions.php:216-220` の `register_block_type()` 呼び出しを数える（自動検査は未実装）。**一方、`WT-AC-VOCAB-01A`（「14 語彙それぞれに受け皿が対応表で1対1に決まる」「実使用上位7種と販売系4種が描画される」）は、本ドラフトでは 14 語彙 × 受け皿の対応表そのものや実使用上位7種・販売系4種の描画確認を行っておらず、未判定**（次段課題）。ブロックパターン（`product-bundle` 等 5 種）は新規「ブロック」ではないため上限の対象に含めない。**なお試作 03 の 5 種はいずれもカテゴリ面・記事末尾のもので、VOCAB-01 の統計文が挙げる「吹き出し・レビュー・商品カード・ランキング・比較専用テーブル・CTA束」という記事内本文カテゴリとは対応が付いていない。この不一致を「カテゴリ系と記事内本文系を別枠でカウントする」形で解消する案は、`WT-AC-VOCAB-01B` の合否基準（8つ目でFAIL）に例外を設けることになるため要求変更に当たる。要求変更の候補 #3 へ切り出す。**
4. **メディア枠 5 択（自前 SVG・アップロード・写真・番号・なし）の切替検証は試作 03 の README に個別記載がなく、カテゴリ children の steps 型（`#129`）や見出しアイコン（`data-wt-icon`）は部分実装のみ** — 検証: 未実測、次段課題。

**(d)** 台帳（README §1「囲み」、article/pc・article/sp の観察列）: 淡塗り（tinted）PC24%/SP25%、罫線（plain-border）PC15%/SP19%、帯タイトル（band-title）PC12%/SP8%。比較表は「先頭列固定 12〜18%」（README §1「表」）。既定値案: 囲みは淡塗り + 罫線の 2 型優先、比較表は先頭列固定 SP スクロールを既定。priority: P0（`requirements-ir.json`）。既存 AC: `WT-AC-VOCAB-01A/B/C`。
**(e)** 問い: なし（新規ブロック件数上限は `WT-AC-VOCAB-01B` のとおり 5 ≤ 7 で PASS。`WT-AC-VOCAB-01A` の受け皿対応表・描画確認は未判定。カテゴリ系/記事本文系を別枠にする例外の要否は要求変更の候補 #3 へ）。

---

## WT-FR-VOCAB-02（目次: テーマ内蔵・機械導出・第三者プラグイン水準を下限）

**(a)** 目次は本文 h2/h3 から機械導出（保存 HTML に固定しない）。配置方式（固定埋め込み / フロート追従 / 開閉ボタン化）・表示条件（ページ種別・投稿単位）・見た目（block style）を選べる。第三者目次プラグインの一般水準（階層・折りたたみ・現在位置追従・見出し数閾値・除外指定）を下限とする。

*要件情報: WT-FR-VOCAB-02: priority P1, 既存AC WT-AC-VOCAB-02A/WT-AC-VOCAB-02B*

**(b)** 試作 03: 目次 4 型（box-inline 既定 / float(PC 1200px 以上) / collapsible / none）。機能: h2/h3 2 階層の機械導出、`h2 ≥ 3` のしきい値、現在位置強調（IO + scroll）、`scroll-margin-top` 76px、章数バッジ、記事単位非表示 hook（`wt_toc=none`）、外部スクリプトなし（README §2.3）。

**(c) 書き直し案**
1. 記事の h2 5・h3 7 に対し目次が同数（5/7）を機械導出する — 検証: `verify.json.toc`（`h2Count:5, h3Count:7, tocH2:5, tocH3:7, pass:true`）。
2. SP は box-inline が `<details>` で既定閉、JS 無効でも開いた状態で内容が読める — 検証: `toc-box-sp.jpg` / `toc-box-open-sp.jpg` 目視。
3. `scroll-margin-top` がヘッダー高 + 1rem（76px）に設定され、見出しジャンプ時にヘッダーで隠れない — 検証: `verify.json.toc.scrollMarginTop === "76px"`。
4. 記事単位で目次を非表示にできる（`wt_toc=none`） — 検証: post meta 設定後の目視（実測は README に記載、`verify.json` には非表示ケースの実測なし）。

**(d)** 台帳（README §1「目次」、article/pc・article/sp の観察列）: なし PC44%/SP53%、フロート PC25%/SP1%、枠内（box-inline）PC18%/SP28%、開閉（collapsible）PC9%/SP13%。既定値案（Claude 案。`events.jsonl` に「目次は非固定・開閉を既定にする」という個別の PO 決定は無く、WT-EVT-0220 は LOOK-01/PARTS-01/VOCAB-02/TPL-01 を「用途由来の型一覧へ置き換える」ことの採否のみを記録している）: 試作 03 の既定 `box-inline` は `<details>` による開閉式で、比較媒体の回遊・読了に寄与すると Claude は判断したが、台帳は「なし」が両画面で最多（PC44%/SP53%）である。
**(e)** 問い: なし（既定値は上記 (d) の Claude 案。台帳最多は「なし」だが、比較媒体の回遊・読了への寄与を理由に `box-inline` を既定案とした。既定値の選択自体は PO への採否問いにしない）。

---

## WT-FR-VOCAB-03（PR 表記の自動判定・記事上部固定 1 箇所）

**(a)** PR 表記は自己基準を満たし、広告パーツ・アフィリエイト / 商品リンクの有無から機械判定して該当ページだけ自動出力。編集者は表示デザインと表示ページ制御のみ選択可、本文編集で消せない。

*要件情報: WT-FR-VOCAB-03: priority P0, 既存AC WT-AC-VOCAB-03A/WT-AC-VOCAB-03B*

**(b)** 試作 03: 本文先頭に `p.wt-pr` を自動挿入（1 行・xs・mute 色）、post meta `wt_pr=off` で抑止（README §2.6・§2.11）。ただし「広告パーツ・アフィリエイト / 商品リンクの有無から機械判定」ではなく、**全記事に自動挿入し `wt_pr` メタで手動抑止する**実装（README「post meta wt_pr=off で抑止」の記述から、既定は全記事 ON）。

**(c) 書き直し案**
1. PR 表記が記事上部にファーストビュー内で 1 箇所出て、最小文字サイズ以上・AA コントラストを満たす — 検証: `pr-notice-one-line-pc.jpg` / `-sp.jpg` 目視 + `verify.json.contrast`（helper text 系のコントラスト実測、値は同ファイル参照）。
2. `wt_pr=off` の記事単位上書きで表記が消える — 検証: post meta 設定後の目視（試作 03 の実測は on 状態のみ、off 実測は未取得）。
3. **「広告パーツ・アフィリエイト / 商品リンクの有無からの自動機械判定」（対象外記事には出さない）は試作 03 未実装**（現行は全記事既定 ON + 手動 off）。

**(d)** 台帳の直接該当なし（PR 表記は法令 / 業界慣行の要件、type=§2「全用途共通」に位置づけ）。
**(e)** 問い: なし（機械判定の要否は要求変更の候補 #2 へ）。

---

## WT-FR-VOCAB-04（内部リンクカード: url_to_postid 直呼び、外部 URL 検証付き HTTP）

**(a)** 内部リンクカードは REST を経由せず `url_to_postid()` 直呼びで解決し、外部 URL は検証付き HTTP のみ。

*要件情報: WT-FR-VOCAB-04: priority P1, 既存AC WT-AC-VOCAB-04A/WT-AC-VOCAB-04B*

**(b)** 試作 03: `helix-wt/linkcard`（`.is-style-wt-linkcard`、タイトルの `a::after` で全面クリック、README §2.6）を実装。ただし解決方式（REST 不使用・`url_to_postid()` 直呼び・外部 URL の検証付き HTTP）は README に記載がなく、見た目の型としてのみ確認できる。

**(c) 書き直し案**
1. リンクカード（内部）が画像左 + タイトル + 全面クリックの型で表示される — 検証: `linkcard-internal-pc.jpg` / `-sp.jpg` 目視。
2. **解決方式（REST 不使用、`url_to_postid()` 直呼び、外部 URL 検証付き HTTP）は見た目の試作では検証できず、実装 PR 側のコードレビュー観点として扱う必要がある。**

**(d)** 台帳: リンクカードは「なし 58〜70%・内部サムネ左 16〜20%」（README §1）。既定値案は現状維持（型として保持、頻度は少数派）。
**(e)** 問い: なし（対象範囲の整理は TL 判断事項 #3 へ）。

---

## WT-FR-SECTION-01（見出し区間の一級単位化・階層 ID の安定性）

**(a)** 見出し区間（section）を H2/H3 の階層で定義し、ID は親子で安定（文言変更でずれない）。中間 JSON に境界と ID が出る。

*要件情報: WT-FR-SECTION-01: priority P0, 既存AC WT-AC-SECTION-01A/WT-AC-SECTION-01B*

**(b)** **試作 03 は body class によるテーマの見た目切替の PoC であり、「中間 JSON への section 境界出力」「AI によるリライト対象の機械可読な単位化」は範囲外。** 目次機能が h2/h3 に `id="h-n"` を機械付与する点（README §2.3）は関連するが、親子の安定 ID・中間 JSON 出力は未実装。

**(c) 書き直し案**
1. **目次の見出し数が本文の h2/h3 数と一致することは `verify.json.toc`（`h2Count/h3Count` と `tocH2/tocH3` の一致）で確認できるが、これは件数の一致であり、見出し `id="h-n"` が実際に本文の h2/h3 順へ機械付与されている規則自体・ID の親子安定性は verify.json に個別のフィールドがなく検証できない。** 件数一致は必要条件だが十分条件ではないため、検証手段なしとして扱う。
2. **見出し文言変更後も ID が変わらないこと、H4 以下を区間としないこと、中間 JSON への境界出力は試作 03 で未検証**（この要件はテーマ実装というより中間表現・AI 連携の設計課題）。

**(d)** 台帳の直接該当なし。
**(e)** 問い: なし（分類の整理は TL 判断事項 #4 へ）。

---

## WT-FR-SECTION-02（区間単位の差し替え・リライト・順序・面挿入・計測）

**(a)** H2/H3 区間単位の差し替え・リライト（diff→apply/rollback）・順序入れ替え・面挿入・表示制御・計測（tracking 経路、section ID 等）を人 / AI 双方から行える。

*要件情報: WT-FR-SECTION-02: priority P0, 既存AC WT-AC-SECTION-02A/WT-AC-SECTION-02B*

**(b)** **試作 03 に該当機能なし**（body class 切替と静的パターンのみで、区間単位の diff/apply/rollback、tracking 経路への計測送信は未実装）。

**(c)** 書き直し案は保留。試作 03 が触れていない要件のため、末尾「未実装」一覧に計上する。

**(d)** 台帳の直接該当なし。
**(e)** 問い: なし（この要件は本ドラフトの対象外として扱う）。

---

## WT-FR-LOOK-01（用途別台帳を入力にした見た目の型・カード高さ自動統一・4 軸・自動コントラスト guard）

**(a)** 用途（サイトパターン×面×目的）から必要な型を選ぶ台帳を入力にする。カード高さ自動統一、見出し 1 行収め、4 軸（動き・奥行き・空間・脱テキスト感）、自動コントラスト guard（4.5:1、大文字 3:1）。

*要件情報: WT-FR-LOOK-01: priority P0, 既存AC WT-AC-LOOK-01A/WT-AC-LOOK-01B/WT-AC-LOOK-01C/WT-AC-LOOK-01D*

**(b)** 試作 03 は比較・アフィリエイト媒体 1 サイトパターンに絞って実装（フロント先行の方針は `events.jsonl:239`（`WT-EVT-0239`、PO 決定 `WT-DIR-FRONT-FIRST-01`）に記録済み。ただし「1 サイトパターンに絞った」こと自体は同イベントの `claude_interpretation`（「台帳の観察が最も厚いため」）による **Claude 案であり、PO 決定ではない**、と同イベント内に明記されている）。4 軸すべてに `?wt=` 軸あり（motion / depth / density / detext、README 冒頭表・§2.8）。自動コントラスト guard は `assets/js/contrast.js` が輝度 L を算出し `data-wt-lum` でスクリム強度を切替（README §2.9）。見出し 1 行収めは実測済み（README §2.4「SP 390 の本文列 358px で 18.5px × 18 字が 1 行」）。

**(c) 書き直し案**
1. 同一列のカードの高さが本文量によらず統一される — 検証: `related-grid-pc.jpg` 目視（自動測定は未実装、レイアウト上は grid/flex で揃う設計）。
2. SP 390px 幅で見出しが 18 字まで 1 行に収まる — 検証: `verify.json.headline`（`chars:18, lines:1, pass:true`）。
3. reduced-motion 環境で装飾モーション（fade-up・count-up）が停止し、最終状態が表示される — 検証: `verify.json.reducedMotion`（`revealHidden:0, pass:true`）。
4. JS 無効でも `.wt-reveal` 要素が非表示のまま残らない — 検証: `verify.json.noJs`（`revealHidden:0`）。
5. **写真・色地上の文字が dark/mid/light いずれの guard レベルでも AA を満たすかは、`verify.json.contrastGuard` / `contrastGuardPass === true`（`ratioText`＝平均輝度による概算値）では合格する（guard 用サンプル画像: dark 本文 15.98:1・worst 13.52、mid 7.86:1・worst 6.43、light 9.62:1・worst 9.12。いずれも `ratioWorstPixel` も基準以上）。ただし実写真では、`article hero h1` は `ratioText` 11.21:1 に対し `ratioWorstPixel` 3.38（大文字基準 3 は上回るが僅差）、`categoryHeroContrast` のカテゴリ hero h1 は `ratioText` 5.21:1 に対し `ratioWorstPixel` 2.49（大文字基準 3 未満）で、文字要素の矩形に対応する画像領域内の最大輝度とスクリムの近似（`scripts/verify.mjs:124-137,150-151`。文字矩形部分だけの最大輝度であり画像全体の最明画素ではない）では基準未達になりうる。判定コード（`scripts/verify.mjs`）は `ratioText`（平均輝度ベース）だけで `pass:true` にしており、`ratioWorstPixel` は合否に使われていない。**「文字直下の背景で AA を満たす」ことは平均輝度の概算では確認できるが、最悪画素基準では未検証として扱う（次段で実描画ピクセル計測を追加する）。
6. 生値や `!important` による装飾がなく、見出し尺度が単調非増加である — 検証: 目視 + 既存 AC WT-AC-LOOK-01A/B（G-T3 相当、試作 03 では未自動化）。

**(d)** 台帳: h2 見出しは「無装飾太字 44〜45%」最多、比較媒体は「控えめな装飾 + アイコン前置」を用途別に選択（by-purpose §2）。4 軸は台帳外（PO 個別承認 WT-EVT-0227「採用で」）。既定値案: `motion:off` / `depth:0` / `density:normal` / `detext:off`（現行 `wt_axes()` の既定値、README §1 軸表と一致）。
**(e)** 問い: なし（検証工程の選択は TL 判断事項 #5 へ）。

---

## WT-FR-LOOK-02（デザインプリセット→style variation 1 本、色 8 スラッグ）

**(a)** デザインプリセット 1 個を style variation 1 本（色 8 スラッグの値差し替え）として写像し、部品別プリセットは block style で受ける。

*要件情報: WT-FR-LOOK-02: priority P2, 既存AC WT-AC-LOOK-02A/WT-AC-LOOK-02B*

**(b)** **試作 03 は単一 variation（比較媒体用の 1 セット）のみで、複数プリセット→複数 variation の写像は範囲外**（README に variation 複数化の言及なし）。

**(c) 書き直し案**
1. **現行 variation が色スラッグ 8 個の集合を変えずに動作するかは、カタログ画像の目視だけでは確認できない**（目視では実際に使われた色が「元の 8 スラッグの範囲内か」を判定できず、スラッグ定義ファイル `theme.json` 側の直接確認が必要）。既存 AC（G-T1b 相当）は試作 03 では自動化されておらず、検証手段なしとして扱う。
2. **複数プリセット→複数 variation の写像は未検証**（次段課題）。

**(d)** 台帳の直接該当なし（WT-FR-LOOK-03 の調査待ち）。
**(e)** 問い: なし（試作の性質上、複数プリセットの検証は WT-FR-LOOK-03 の調査完了後に回すのが妥当）。

---

## WT-FR-LOOK-03（サイトパターン別の実在調査に基づく variation 群）

**(a)** variation / block style の写像対象をサイトパターン（企業HP/サービスLP/ブランド/ポータル/比較サイト）の品質水準までカバーする。前提として実在 Web ページの大量調査を PoC 証跡として取る。

*要件情報: WT-FR-LOOK-03: priority P1, 既存AC WT-AC-LOOK-03A/WT-AC-LOOK-03B*

**(b)** 台帳（`by-purpose.md` §1）はサイトパターン別の観察を持つが、**試作 03 は「比較・アフィリエイト媒体」1 パターンのみ実装**（フロント先行の方針は `events.jsonl:239`（`WT-EVT-0239`、PO 決定 `WT-DIR-FRONT-FIRST-01`）に記録済み。ただし「1 サイトパターンに絞る」こと自体は同イベントの `claude_interpretation` による Claude 案であり、PO 決定ではないと同イベント内に明記されている）。企業HP・サービスLP・ブランド・ポータルの実装 variation は未着手。

**(c) 書き直し案**
1. 比較・アフィリエイト媒体パターンについては台帳の観察と試作 03 の実装が対応する（本ドラフトの各節参照） — 検証: 本ドラフトの (b) 各節。
2. **他 4 サイトパターンの variation / block style 実装は試作 03 の範囲外**（次のプロト往復で扱う）。

**(d)** 台帳: `by-purpose.md` §1 は用途（サイトパターン）× 目的 × 面の観察を、通常区分 7 種（企業HP/サービスLP/比較・アフィリエイト媒体/ポータル・ニュース/ブランド・EC/個人ブログ・ポートフォリオ/大手メディア）+ 参考区分 1 種（表現重視、`by-purpose.md:123`）の計 8 区分で持つ。要求（`requirements-ir.json:611`、WT-FR-LOOK-03 の statement）が挙げる主要対象は 5 パターン（企業HP/サービスLP/ブランド/ポータル/比較サイト）で、台帳の通常区分 7 種のうち「個人ブログ」「大手メディア」の 2 種は要求の主要対象に含まれない追加観察。**「表現重視」（3D アニメーション / ゲーミング系）は要求外ではない** — 同 statement は「調査対象には 3D アニメーション / ゲーミング系のサイトパターンも含める（PO 問い 2026-09-02）」と明記しており、調査対象としては要求に含まれる。ただし同じ statement は「配色・タイポ・部品は variation / block style で届くが、3D・常時アニメーションは値差し替えの外にあり、動きの資産層（JS / アセット）を持つかは調査結果を見て別提案として出す」とも定めており、**調査対象に含めることと、3D・常時アニメーションの実装を採用することは別**。試作 03 では「表現重視」パターンの調査・実装のいずれも未着手。
**(e)** 問い: なし（次段の優先順位は次段候補 #2 へ）。

---

## WT-FR-META-01（投稿メタ 5 キー・eyecatch 位置と有無）

**(a)** 投稿メタ 5 キー（sidebar/toc/share/pr/eyecatch）を登録。eyecatch は位置（本文上/タイトル上/全幅hero/サイドバー寄せ）と有無を持ち、サイト既定は設定 JSON、記事単位はメタで上書き。

*要件情報: WT-FR-META-01: priority P1, 既存AC WT-AC-META-01A/WT-AC-META-01B/WT-AC-META-01C*

**(b)** 試作 03: eyecatch 5 型（title-image 既定 / image-title / hero / side / none）を `?wt=eyecatch:*` 軸 + 記事単位は post meta `wt_eyecatch` で上書き（README §2.2、解決順はプレビュー引数→post meta→theme_mod→既定値）。toc/share/pr も同様に post meta 上書き対応（README「post meta wt_<key>（eyecatch/toc/pr/share のみ」）。

**(c) 書き直し案**
1. eyecatch 5 型（タイトル→画像 既定 / 画像→タイトル / ヒーロー重ね / 横サムネ / なし）を記事単位メタで切替できる — 検証: `eyecatch-*-pc.jpg` / `-sp.jpg` 目視 + `wp post meta set` 実行結果（README 記載のコマンド例）。
2. hero 型（画像重ね）で自動コントラスト guard が働く — 検証: `verify.json.contrastGuard`（hero 該当ケース）。
3. メタ未設定の記事はサイト既定（`theme_mod`）に従う — 検証: 目視（複数記事の比較、試作 03 では単一記事での上書き確認のみ実施）。

**(d)** 台帳: 記事タイトル部は「タイトル→画像 57〜58%・画像なし 16〜18%・ヒーロー重ね 8〜9%・画像→タイトル 7〜9%・横サムネ 1〜5%」（README §1「記事タイトル部」）。既定値案: タイトル→画像（現行既定と一致、最多派）。
**(e)** 問い: なし（既存 PO 決定 WT-EVT-0211 と実装が一致）。

---

## WT-FR-TPL-01（404/検索の複数テンプレ変種・CV 導線 slot）

**(a)** 404 と検索結果に選べる複数のテンプレ変種（人気記事/CTA/検索語提案）を持ち、404 には LP・比較記事・問い合わせへの CV 導線 slot。検索結果は noindex 既定、404 は HTTP 404、検索語ログは bot 除外・IP 非保存。

*要件情報: WT-FR-TPL-01: priority P1, 既存AC WT-AC-TPL-01A/WT-AC-TPL-01B/WT-AC-TPL-01C*

**(b)** 試作 03: 404 変種 3 種（`nf:popular` 既定 / `nf:cta` / `nf:suggest`）+ CV slot レーン（`parts/cv-slot.html`）（README §2.10）。

**(c) 書き直し案**
1. 404 の 3 変種すべてで HTTP 404 が返る — 検証: `verify.json.status404`（`nf:popular`/`nf:cta`/`nf:suggest` の3 URL とも ステータス `404`）。**robots meta の `noindex`/`max-image-preview:large` は `scripts/verify.mjs` の実装上、ループ最後に遷移したページ（`nf:suggest` 変種）でのみ評価されており、3 変種すべてで robots meta を確認したわけではない**（`robotsAll`/`noindex` は最後の1ページの値）。
2. 404 に謝意・原因・検索（ボタン付き）・カテゴリ・ホームの各要素が出る — 検証: `verify.json.status404.has`（`apology/cause/search/categories/home` すべて true）。
3. CV slot レーンに 3 枠（比較記事 / LP / 問い合わせ）が出る — 検証: `verify.json.status404.has.cvSlot === 3`。
4. 検索語提案変種で URL パスから語を抽出し検索リンク化する（4 件） — 検証: `verify.json.status404.has.suggestLinks === 4`。
5. **検索結果テンプレートの noindex 既定、bot 除外・IP 非保存の検索語ログは試作 03 の実測範囲外**（404 のみ実測、検索結果ページ自体は README/verify.json に記載なし）。

**(d)** 台帳の直接該当なし（404/検索は「全用途共通・守り」、by-purpose §2 末行）。
**(e)** 問い: なし（次段の対象候補は次段候補 #3 へ）。

---

## WT-FR-RECO-01（関連/人気/おすすめ 3 方式・表示型の選択）

**(a)** 記事一覧に関連（カテゴリ→タグ→手動）・人気（集計方式と期間選択、bot/管理者除外・IP非保存）・おすすめ（手動指定）の3方式。表示型（カード/リスト/ランキング/サムネ大小/横スクロール）を用途別一覧から選べる。

*要件情報: WT-FR-RECO-01: priority P0, 既存AC WT-AC-RECO-01A/WT-AC-RECO-01B/WT-AC-RECO-01C*

**(b)** 試作 03: 関連記事の表示型 4 種（grid 既定 / list / rank / carousel、README §2.7）。カテゴリ面のランキング（`cat_ranking: none/sidebar/bottom`）とミニ HOME（`cat_minihome`）はいずれも「PoC では新着順（日付順）をランキング表示へ投影」（README §2.12 冒頭「AI判定・外部API・人気度の推定は行わない」）。

**(c) 書き直し案**
1. 関連記事の表示型（グリッド/横サムネ1行/ランキング番号/カルーセル）を切り替えても記事件数・選定ロジックは変わらない — 検証: `related-*-pc.jpg` / `-sp.jpg` 目視。
2. カルーセルは自動送りをしない — 検証: README §2.7「カルーセル（自動送りなし）」の記載＋目視。
3. **人気の集計方式・期間選択、bot/管理者除外・IP 非保存の実装は試作 03 では「日付順で代替」と明記されており未実測**（README §2.12「PoC では日付順をランキング表示へ投影」）。

**(d)** 台帳: 関連・人気の出し方は「サイドバーlist 54%（PC article）・グリッド 35%（SP article）・番号ランキング 13%」（README §1）。既定値案: グリッド既定（現行と一致）。カテゴリ ミニ HOME は「比較媒体の回遊用」少数派（8%/7%）選択肢（README §2.12）。
**(e)** 問い: なし（AC 範囲の整理は TL 判断事項 #6 へ）。

---

## WT-FR-LOOK-04（和文フォント複数系統・自己ホスト・サブセット）

**(a)** 和文フォントをゴシック/明朝/丸ゴ/手書き・デザイン系など複数系統から選べ、unicode-range 分割サブセット・size-adjust・font-display:swap・OFL 表記。既定はシステムフォント。

*要件情報: WT-FR-LOOK-04: priority P0, 既存AC WT-AC-LOOK-04A/WT-AC-LOOK-04B*

**(b)** **試作 03 の README にフォント切替 axis の記載なし**（見出し・本文のサイズ調整は §2.4 にあるが、フォント系統選択・サブセット化・OFL 表記の実装言及はない）。

**(c) 書き直し案は保留。**「未実装」一覧に計上する。

**(d)** 台帳の直接該当なし（ブランドパターンで「明朝または display 系書体」の言及あり、by-purpose §2「ブランド・EC」行）。
**(e)** 問い: なし（試作 03 未実装のため本ドラフト対象外）。

---

## WT-FR-BANNER-01（バナー正本・お知らせバー派生・全面ゾーン配置）

**(a)** バナー正本（画像/リンク/alt/種別/有効期間/PR要否）を登録し商品バナーは商品IDから派生。お知らせバーはバナー正本から派生しヘッダー直下 slot、閉状態は端末記憶。バナー/問い合わせボタン枠は全面ゾーンに置ける。

*要件情報: WT-FR-BANNER-01: priority P1, 既存AC WT-AC-BANNER-01A/WT-AC-BANNER-01B/WT-AC-BANNER-01C/WT-AC-BANNER-01D/WT-AC-BANNER-01E*

**(b)** 試作 03: お知らせ帯（`header:announce`）は 1 行・`role=status`・閉ボタン 44px・閉状態 `localStorage`（`wt-announce-closed:<id>`）、初期描画前に `html.wt-announce-closed` 付与（README §2.1）。CTA バナー画像型は記事内 CTA の 1 型として実装（README §2.6「バナー画像」）。**「バナー正本」（管理画面での一元管理・商品ID派生・全面ゾーンへの配置UI）は README に記載がなく未実測。**

**(c) 書き直し案**
1. お知らせ帯の閉ボタンは 44px 以上、閉じると `localStorage` に記録され再訪時も非表示のまま初期描画される — 検証: 目視（README 記載の実装、`verify.json` に閉状態の実測項目なし）。
2. お知らせ帯は `role=status` を持つ — 検証: 目視ソース確認。
3. 記事内 CTA のバナー画像型が表示される — 検証: `cta-banner-pc.jpg` / `-sp.jpg` 目視。
4. **バナー正本（PC/SP画像・リンク・alt・種別・有効期間・PR要否を1箇所で管理し商品バナーが商品IDから派生する仕組み）、全面ゾーンへの配置UIは試作03に未実装。**

**(d)** 台帳: 固定・追従パーツの「お知らせバー PC 17%・SP 27%」（README §1）。footer 直上帯は「バナー列 33%・CTA帯 17%」（README §1b）。既定値案: お知らせ帯は既定 OFF（現行実装通り、コンテンツ依存）。
**(e)** 問い: なし（対象範囲の整理は TL 判断事項 #7 へ）。

---

## WT-FR-BANNER-02（バナー計測・rel=sponsored・広告タグ分離）

**(a)** バナーの impression/click を計測し CV ID・A/Bvariant ID等で扱う。管理画面/MCPから登録・差し替え・停止。アフィリエイト種別は `rel="sponsored"` と PR 判定へ接続。広告配信タグはテーマ外。

*要件情報: WT-FR-BANNER-02: priority P1, 既存AC WT-AC-BANNER-02A/WT-AC-BANNER-02B*

**(b)** **試作 03 に計測実装なし**（見た目の型切替のみで、tracking 経路・rel 属性・管理画面連携は範囲外）。

**(c)** 書き直し案は保留。「未実装」一覧に計上する。

**(d)** 台帳の直接該当なし。
**(e)** 問い: なし。

---

## WT-FR-SP-01（面・語彙・パーツの共通宣言+device別差分の個別編集）

**(a)** 共通宣言を1本のfluid定義で持ち、専用面・並び順等のdevice別差分を`@mobile`/`@tablet`上書きとして宣言。Site EditorとAI双方からSP/PCを個別編集。

*要件情報: WT-FR-SP-01: priority P0, 既存AC WT-AC-SP-01A/WT-AC-SP-01B*

**(b)** 試作03は body class（`wt-<key>-<value>`）+ CSSメディアクエリで SP/PC 差分を実現（README冒頭）。ただし `@mobile`/`@tablet` という WordPress 7.1 の新記法そのものの使用は README に明記されておらず、実装詳細は本ドラフトの調査範囲外。

**(c) 書き直し案**
1. **PC/SP 両方の描画は目視確認できる**が、**「各軸の設定値を端末別に独立して保持・変更できる」ことは確認できない** — 検証: `results/`内のPC/SP両方のスクリーンショット（例 `header-search-pc.jpg`/`header-hamburger-search-sp.jpg`、両幅の描画結果の確認のみ）。`functions.php` の `wt_opt()` は軸ごとに単一の値をプレビュー引数→post meta→theme_mod→既定値の順で解決する実装であり、同じ軸に PC 用と SP 用の別々の値を持たせる仕組みではない（CSS メディアクエリによる見た目の分岐は別）。**「device 別差分を個別に編集できる」という受入条件は専用の検証手段がなく未検証。**
2. **SP側の横スクロール量は `verify.json` に専用フィールドが無く、タップ監査（`tap.*`）はタップ対象の44px/24px判定のみで横スクロール自体を測っていない。横スクロール0の主張は検証手段なし**（次段で `document.documentElement.scrollWidth` 等の専用検査を追加する必要がある）。

**(d)** 台帳の直接該当なし（実装方式の要件）。
**(e)** 問い: なし（実装記法の選択は TL 判断事項 #10 へ）。

---

## WT-FR-SP-02（SPヘッダー/ドロワー/下部固定3〜5タブ/SP専用広告面）

**(a)** SPヘッダー（ロゴ/ハンバーガー/検索/CTA）、ドロワー、SP下部固定（3〜5タブ: 電話/メッセージ/資料DL/目次/トップへ）、SP専用広告面をdevice別差分として選べ、PC側にも同構造。重いブロックはBlock Visibilityで隠さずslot条件描画。

*要件情報: WT-FR-SP-02: priority P0, 既存AC WT-AC-SP-02A/WT-AC-SP-02B*

**(b)** 試作03: SPヘッダー3型（README §2.1）実装済み。**下部固定3〜5タブ（電話/メッセージ/資料DL含む）、ドロワー階層、SP専用広告面は README に個別実装の記載なし**（シェアfloatとtotopボタンのみ固定要素として実測）。

**(c) 書き直し案**
1. SPヘッダー3型（ハンバーガー+検索/右/左）を切替できる — 検証: `header-hamburger-*-sp.jpg` 目視。
2. **下部固定タブ（電話/メッセージ/資料DL/目次/トップへの3〜5タブ構成）、ドロワーメニュー、SP専用広告slotは試作03に未実装。**

**(d)** 台帳: header SP配置は「ハンバーガー右31%・+検索22%・左16%・+CTA13%」（README §1）。
**(e)** 問い: なし（次段の対象候補は次段候補 #4 へ）。

---

## WT-FR-SP-03（device別語彙差分: 比較横スクロール/カードタブアコーディオン等）

**(a)** SPで比較テーブルは横スクロール/カード、タブはアコーディオン、目次はフロートから開閉ボタン、ギャラリーはスワイプ、CTAは全幅/stickyとして選ぶ。管理画面/MCPからSP/PCプレビュー確認。

*要件情報: WT-FR-SP-03: priority P1, 既存AC WT-AC-SP-03A/WT-AC-SP-03B*

**(b)** 試作03: 比較表はSPでカード縦積み（README §2.6「SP: 行ごとのカード縦積み」）、目次はSPでbox既定閉（README §2.3）。**タブのアコーディオン変換、ギャラリーのスワイプ、CTAの全幅/sticky変換は試作03に個別実装の記載なし**（VOCAB-01のstatementには「タブはコアTabs+block style、SPはアコーディオンへ変換」という記述があるが、試作03のREADMEには実装確認の記載がない）。

**(c) 書き直し案**
1. 比較表がSPでカード縦積みに変わり、`data-th`属性で見出しラベルが各セルに付く — 検証: `verify.json.table`（`dataTh:24`）+ `table-compare-sp.jpg`目視。
2. 目次がSPで既定閉の`<details>`になる — 検証: `toc-box-sp.jpg`目視 + `verify.json.toc`。
3. **タブのアコーディオン変換、ギャラリーのスワイプ、CTAの全幅/sticky変換は未実測。**

**(d)** 台帳の直接該当なし。
**(e)** 問い: なし（次段の対象候補は次段候補 #5 へ）。

---

## WT-NFR-A11Y-01（域判定label+icon・alt0欠落・AAコントラスト・横スクロール0・APG契約）

**(a)** 色だけに依存しない状態表示、img alt欠落0、AAコントラスト、横スクロール0、WCAG2.2 AA、APGのrole/aria-expanded/キーボード契約、focus可視、reflow、text-spacing、24px下限。

*要件情報: WT-NFR-A11Y-01: priority P1, 既存AC WT-AC-NFR-A11Y-01A/WT-AC-NFR-A11Y-01B*

**(b)** 試作03の`verify.json`は複数の該当検査を実測: `contrast`（CTAボタン5.18:1、本文リンク6.7:1等）、`tap`（44px/24px両方全件pass）、`toc`（開閉のaria状態は`<details>`ネイティブ機構）。

**(c) 書き直し案（試作03で実測した範囲に限定する。`verify.json.summary`の`pass:40,fail:0`はaxe実行の要約ではなく、404・ページ送り・LP面判定などaxeと無関係な検査を含む合算値のため、この総数を役割/キーボード等の受入根拠には使わない）**
1. 主要な色コントラスト（CTAボタン・本文リンク・ヘルパーテキスト）がAA基準を満たす — 検証: `verify.json.contrast`各項目`pass:true`。
2. タップ対象が24px以上（WCAG2.5.8）かつ44px以上（P05目標）を満たす — 検証: `verify.json.tap.*`（記事68/68、404 45/45、カタログ31/31、いずれもok44=ok24=total）。
3. reduced-motion環境で動きが停止する（A11Y-02と共通の実測） — 検証: `verify.json.reducedMotion.pass === true`。
4. JS無効環境でも主要要素が表示される（no-JS実測） — 検証: `verify.json.noJs.pass === true`。
5. **role/aria-expanded、キーボード操作契約、focus可視、reflow、text-spacingのAPG/WCAG検査（axe相当の自動監査）は`verify.json`に実行記録が無く、試作03では未検証。`verify.json.summary`の`pass:40`はこれらを含まない合算値であり代替根拠にできない。**

**(d)** 台帳の直接該当なし（全用途共通の守り要件）。
**(e)** 問い: なし（検証工程の選択は TL 判断事項 #8 へ）。

---

## WT-NFR-A11Y-02（reduced-motion時の停止・操作完了の必須条件化禁止）

**(a)** `prefers-reduced-motion`検出時は動き/autoplayを停止または静的縮退し、animationを操作完了の必須条件にしない。

*要件情報: WT-NFR-A11Y-02: priority P1, 既存AC WT-AC-A11Y-02A/WT-AC-A11Y-02B*

**(b)** 試作03: `verify.json.reducedMotion`実測あり（`revealHidden:0, headerTransition:"none", buttonTransition:"none", pass:true`）。

**(c) 書き直し案**
1. reduced-motion環境で`.wt-reveal`の初期非表示要素が0件（全件表示済み） — 検証: `verify.json.reducedMotion.revealHidden === 0`。
2. reduced-motion環境でヘッダー・ボタンのtransitionが無効化される — 検証: `verify.json.reducedMotion.headerTransition/buttonTransition === "none"`。
3. count-upは最終値がテキストとして表示される — 検証: `verify.json.reducedMotion.countUpText`（実測「1,284」）。

**(d)** 台帳の直接該当なし。
**(e)** 問い: なし（実測が要求文言と一致）。

---

## WT-NFR-SP-01（44px/16px/横スクロール0・固定要素の被覆0・積層順固定）

**(a)** SPのタップ対象44px以上、本文16px以上、横スクロール0。下部固定/ドロワー/同意バーは本文/CTAを隠さず積層順固定。PC側も同構造・検査。

*要件情報: WT-NFR-SP-01: priority P0, 既存AC WT-AC-NFR-SP-01A/WT-AC-NFR-SP-01B*

**(b)** 試作03: `verify.json.tap`全項目実測（記事68/68・404 45/45等、44px/24px両方pass）。`verify.json.fixedOverlap`で固定要素の重なり検査（share/totop）。**同意バーは未実装のため積層順3要素同時検査は不可**（ZONE-03と同じ制約）。

**(c) 書き直し案**
1. SP記事本文のタップ対象が44px以上（全68件） — 検証: `verify.json.tap.article`（`total:68, ok44:68`）。
2. SP/PC双方でシェアfloatとtotopが重ならずタップ到達可能 — 検証: `verify.json.fixedOverlap`（sp/pc両方`intersects:false, reachable`全true）。
3. **同意バーを含む3要素の積層順検査は未実装**（ZONE-03参照）。

**(d)** 台帳の直接該当なし。
**(e)** 問い: なし（ZONE-03 と同じ AC 範囲の整理。TL 判断事項 #1 へ）。

---

## WT-NFR-SP-02（主測定面選択・両幅ゲート・device別A/B集計）

**(a)** 主測定面をサイト設定で選択（既定SP）。代表ページのLighthouse mobile/PC幅・両幅スクリーンショット比較・ローカルDocker実機相当ゲート・管理画面/MCP両幅プレビュー・両幅速度/CWV計測・device別A/B集計を同一revisionで束縛。

*要件情報: WT-NFR-SP-02: priority P0, 既存AC WT-AC-NFR-SP-02A/WT-AC-NFR-SP-02B*

**(b)** 試作03: `results/`配下にカタログ311ファイル（重複なし、全ファイル実在、PC155/SP156、`CATALOG-INDEX.json`で`dev:"pc"/"sp"`管理）が揃い、`verify.json`もSP/PC両方の検査を含む。ただし `(face, part, variant)` で照合すると、header の端末固有型6件（PC限定: search/nav/cta、SP限定: hamburger-search/hamburger-right/hamburger-left）と `toc: box-open`（SP限定）の計7項目は片側の端末にしかスクリーンショットが無い。**Lighthouse/CWV測定、device別A/B集計、管理画面/MCPプレビュー機能は試作03の範囲外**（静的テーマファイルとverify.mjsのみ）。

**(c) 書き直し案**
1. カタログの311件は重複なく全ファイルが実在し、`CATALOG-INDEX.json`から`dev`属性で参照できる（PC155/SP156） — 検証: `CATALOG-INDEX.json`（311件、`dev`フィールド）。**「全variantにPC/SP両方が揃う」は誤りで、header の端末固有型6件とtoc:box-openの計7項目は片側の端末のみに存在する**（意図的な端末固有型のため、両幅対応の欠落ではない）。
2. タップ・コントラスト・404等の静的検査がSP/PC両方で実行される — 検証: `verify.json`各項目。
3. **Lighthouse/CWV測定、device別A/B集計は未実装**（次段の実装課題）。

**(d)** 台帳の直接該当なし。
**(e)** 問い: なし（LighthouseゲートはNFR-PERF側で扱う）。

---

## WT-NFR-PERF-01 / WT-NFR-PERF-02 / WT-NFR-PERF-03（速度予算・CWV閾値・CIゲート）

**(a)** JS無し表示の原則、web-vitals-budget、CSS語彙単位分割、critical CSS inline、フォントswap、Lighthouse/CWV（LCP2.5s/INP200ms/CLS0.1）のCI blocking gate。

*要件情報: WT-NFR-PERF-01: priority P1, 既存AC WT-AC-NFR-PERF-01A/WT-AC-NFR-PERF-01B；WT-NFR-PERF-02: priority P1, 既存AC WT-AC-NFR-PERF-02A/WT-AC-NFR-PERF-02B；WT-NFR-PERF-03: priority P0, 既存AC WT-AC-NFR-PERF-03A/WT-AC-NFR-PERF-03B*

**(b)** 試作03: `verify.json.noJs`でJS無効時の表示成立を実測（`wtJsClass:false, tocVisible:true, tocOpen:true, productVisible:true, tableVisible:true, headerVisible:true, pass:true`）。**Lighthouse/CWV測定、CSS転送量予算、critical CSS inline、CIゲート接続は試作03の範囲外**（静的HTMLの目視・DOM検査のみ）。

**(c) 書き直し案**
1. JS無効環境で目次・商品カード・比較表・ヘッダー・お知らせ帯が表示される — 検証: `verify.json.noJs`（該当5項目すべてtrue、`pass:true`）。
2. JS無効環境で本文文字数が一定以上表示される（コンテンツが欠落しない） — 検証: `verify.json.noJs.textChars === 3048`。
3. **Lighthouse/CWV実測、CSS/JS予算のCI gate接続は試作03に未実装**（次段の実装課題）。

**(d)** 台帳の直接該当なし。
**(e)** 問い: なし（検証工程の選択は TL 判断事項 #11 へ）。

---

## WT-FR-IMG-01 / WT-FR-IMG-02 / WT-FR-IMG-03（画像生成パイプライン・非同期ジョブ・alt必須警告）

**(a)** 全subsizeをWebP/AVIF生成、GIF→WebM+fallback、5MB超・一括処理は非同期ジョブ、alt必須警告・GIF動画置換提案・Discover代表画像検査。

*要件情報: WT-FR-IMG-01: priority P0, 既存AC WT-AC-IMG-01A/WT-AC-IMG-01B；WT-FR-IMG-02: priority P1, 既存AC WT-AC-IMG-02A/WT-AC-IMG-02B；WT-FR-IMG-03: priority P1, 既存AC WT-AC-IMG-03A/WT-AC-IMG-03B*

**(b)** **試作03のREADMEに画像生成パイプラインの実装記載なし**（アイキャッチ画像の表示位置切替のみを扱い、WebP/AVIF生成・非同期ジョブ・alt警告UIは範囲外）。alt属性の保証は「画像altは未設定はalt=""を保証」の既定のみ言及（README §2.11）。

**(c) 書き直し案**
1. 画像にalt属性が保証される（未設定でも空文字が入る） — 検証: README §2.11の記載（自動検査はverify.jsonに専用項目なし）。
2. **WebP/AVIF生成、非同期ジョブ、alt必須警告UI、Discover代表画像検査は試作03に未実装。**

**(d)** 台帳の直接該当なし。
**(e)** 問い: なし（実装インフラ要件のため本ドラフト対象外、次段実装PRで扱う）。

---

## WT-FR-TYPO-01（和文改行制御・text-wrap balance/pretty）

**(a)** `line-break:strict`、`overflow-wrap:anywhere`、`word-break:normal`、`text-autospace`、`text-spacing-trim`、見出し`text-wrap:balance/pretty`を宣言し、未対応環境はfallback。

*要件情報: WT-FR-TYPO-01: priority P1, 既存AC WT-AC-TYPO-01A/WT-AC-TYPO-01B*

**(b)** **試作03のREADMEに個別CSSプロパティの明記なし**（見出しの1行収め・行間はREADME §2.4に実測あり、改行制御プロパティの適用有無は本ドラフトの調査範囲では未確認）。

**(c) 書き直し案**
1. **SP幅で見出し・本文が横スクロールを起こさず折り返すかは、`verify.json` に横スクロール量の専用フィールドが無く、タップ監査（`tap.*`）はタップ対象サイズのみを測るため検証手段なし**（目視でも折り返しの逸脱は見逃しうる。次段で `scrollWidth` 等の専用検査を追加する必要がある）。
2. **`text-wrap:balance/pretty`等の個別プロパティ適用はソースコード確認が必要で、本ドラフトの証跡（README/verify.json/カタログ）だけでは検証不能。**

**(d)** 台帳の直接該当なし。
**(e)** 問い: なし（実装コードレビュー観点として扱う）。

---

## WT-FR-NAV-01（コアBreadcrumbs+BreadcrumbList同一正本）

**(a)** コアBreadcrumbsブロックを表示しBreadcrumbList構造化データを同じ出力元から生成。階層は投稿/固定ページ/LPの構造から機械導出。

*要件情報: WT-FR-NAV-01: priority P0, 既存AC WT-AC-NAV-01A/WT-AC-NAV-01B*

**(b)** 試作03: 自動コントラストguardの検査対象にパンくず2リンクが含まれる（README §2.9「パンくず2リンク」実測、`verify.json.contrastGuard`に `article hero meta:A#0`（10.39:1）・`article hero meta:A#1`（7.38:1）として実測あり）ことから、パンくずが記事hero上に表示される実装は確認できる。**BreadcrumbList構造化データが同一出力元から生成されるかは試作03の証跡（見た目のスクリーンショット）からは検証不能。**

**(c) 書き直し案**
1. **パンくずが記事hero（画像重ね型）上で4.5:1以上のコントラストで読めるかは、平均輝度による概算では満たすが（`verify.json.contrastGuard`の`article hero meta:A#0`が10.39:1、`article hero meta:A#1`が7.38:1、いずれも`ratioText`で4.5以上）、同じ項目の`ratioWorstPixel`（文字要素の矩形に対応する画像領域内の最大輝度とスクリムの近似から算出、`scripts/verify.mjs:124-137,150-151`。画像全体の最明画素ではない）はA#0が3.87、A#1が3.56で、いずれも4.5未満。文字直下の実際の背景画素との適合は未検証**（`scripts/verify.mjs`の判定コードは`ratioText`＝平均輝度ベースの比だけで`pass:true`にしており、`ratioWorstPixel`は合否判定に使われていない）。
2. **BreadcrumbListのJSON-LD出力が表示パンくずと同一正本かは未検証**（次段でJSON-LD出力の目視・構造化データテスト結果を追加する必要がある）。

**(d)** 台帳の直接該当なし。
**(e)** 問い: なし（検証工程の選択は TL 判断事項 #9 へ）。

---

## WT-FR-LP-01（LP を CPT として分離・ディレクトリ非依存 URL・種別列挙）

**(a)** LP を投稿型（CPT、`show_in_rest`）として持ち、一覧・テンプレ割当・REST を固定ページから分離する。URL はディレクトリ非依存（階層を持たないスラッグ）。種別（通常/イベント/比較特設）を JSON で列挙できる。page template + lp パターン 12 本を初期パターン群として引き継ぐ。

*要件情報: WT-FR-LP-01: priority P0, 既存AC WT-AC-LP-01A/WT-AC-LP-01B*

**(b)** 試作 03 段 4 の実装（README §2.13）: LP は **`post_type=page` + 固定ページテンプレート `page-lp`**（`wp post create --post_type=page --page_template=page-lp`）として作成しており、**CPT ではなく固定ページの1バリエーションとして代替した**。`wt_is_lp_page()` は `is_page_template( array( 'page-lp', 'page-lp.html' ) )` で判定し、body class に `wt-face-lp` を付与する（README「重大」是正記録、459行目）。`patterns/lp.php` は `hero-split` / `numbers` / `features` / `steps` / `pricing` / `faq` の既存 pattern と比較表 style を流用し、LP 専用のロゴ枠・声・バッジ枠・CTA 帯を追加する構成。

**(c) 書き直し案**
1. LP ページで body class に `wt-face-lp` が付き footer 既定が `single-row` になる — 検証: `verify.json.lpFooterFaceDefault`（`lpHasFace:true, lpHasSingleRow:true`）。**ただし `lpFooterFaceDefault` が検査するのは `wt-face-lp` / `wt-footer-layout-single-row` の body class のみで、`_wp_page_template` メタの保存値や `page-template-page-lp` class 自体は verify.json に専用フィールドが無く検証不能**（README 本文の記載（`wp post meta get 601 _wp_page_template` で確認したという記述）はあるが、`verify.json` には反映されていない）。
2. 非 LP 面（記事）には `wt-face-lp` が付かず、footer 既定（sitemap）のままである — 検証: `verify.json.lpFooterFaceDefault`（`articleHasFace:false, articleHasSitemap:true`）。
3. LP 面限定の CSS（`lp_fixed:sp-bottom-bar` 由来の totop 位置調整）が非 LP 面へ漏れない — 検証: `verify.json.lpFaceScopedTotop`（`baseline:"16px", withLpFixed:"16px"`、非LP面で不変）。
4. LP header 3 型（minimal / logo-only / none）が切り替えられる — 検証: `lp-header-*-pc.jpg` / `-sp.jpg` 目視。`none` のアンカーナビについては、**`verify.json.lpAnchorNav`（`scripts/verify.mjs:448-451`）はリンク先 ID が `document.getElementById` で存在すること（`targetExists`）とリンク要素自身が可視であること（`visible`、3 リンクとも `true`）だけを検査しており、遷移先要素（ジャンプ先セクション）自体が可視かどうかは検査していない。「遷移先の可視性」は未検証**。
5. **CPT 化（`show_in_rest` による REST 分離）、ディレクトリ非依存 URL（固定ページ階層から独立したスラッグ体系）、種別（通常/イベント/比較特設）の JSON 列挙は試作 03 に実装がなく検証不能。** 現行実装は固定ページの子として存在するため、要求が指す「固定ページからの分離」を満たしていない。

**(d)** 台帳の直接該当なし（CPT 化はテーマ実装規約の話であり、`by-purpose.md` の観察対象外）。
**(e)** 問い: LP を CPT（`show_in_rest`）として固定ページから分離し、ディレクトリ非依存 URL・種別（通常/イベント/比較特設）の JSON 列挙を持たせる（現行の固定ページ + `page-lp` テンプレート代替を置き換える）。採用するか。

---

## WT-FR-LP-02（LP のフォーム制御・デザイン拡張性・イベント計測）

**(a)** LP はフォーム制御（配置・項目・送信先の JSON 宣言）、デザイン面の拡張性（LP 専用 variation/block style/セクションパターン）、イベント計測（表示・スクロール・CTAクリック・フォーム送信を WT-FR-TAG-02 のデータ層契約で送信、目標CV ID・A/B variant ID を伴う）を持つ。

*要件情報: WT-FR-LP-02: priority P0, 既存AC WT-AC-LP-02A/WT-AC-LP-02B*

**(b)** 試作 03 段 4 の実装（README §2.13）: header 3 型・hero 4 型（split/fullbleed/product/text-only）・hero CTA 3 型（single/double/form-inline）・sections 3 構成（full/short/trust）・CTA style 3 型（solid/outline/pill）・fixed 3 型（none/sp-bottom-bar/float-cta）・legal on/off の**7 軸**を LP 専用パターン（`patterns/lp.php`）として実装。フォームは `method="post"` `action="/lp/"`、`input` に固有 `id`、対応する `label[for]` を持ち JS 無しで送信可能な構造（README §2.13「段4の guard」）。**イベント計測（表示/スクロール/CTAクリック/送信のデータ層契約送信、CV ID・A/B variant ID）は試作 03 に実装がない**（見た目とフォームの静的構造のみ）。

**(c) 書き直し案**
1. LP hero を 4 型（split 既定 / fullbleed / product / text-only）から選べ、hero CTA を 3 型（single 既定 / double / form-inline）から選べる — 検証: `lp-hero-*-pc.jpg` / `-sp.jpg`、`lp-hero-cta-*-pc.jpg` / `-sp.jpg` 目視。
2. sections を 3 構成（full/short/trust）から選べ、各構成で期待した slot だけが可視になる — 検証: `verify.json.lpSections`（full: 12 slot、short: 4 slot、trust: 該当 slot、いずれも `visible === expected`、`pass:true`）。
3. form-inline の hero で、JS 無効でも `method=post` `action=/lp/`、`input id` と `label[for]` の対応が成立する — 検証: `verify.json.lpFormNoJs`（`method:"post", action:"/lp/", inputId:"lp-email-split", labelFor:"lp-email-split", pass:true`）。
4. CTA style 3 型（solid/outline/pill）のいずれも header CTA・hero CTA・CTA帯・pricing 見出し/価格/CTA が AA コントラスト（本文 4.5:1、大文字 3:1）を満たす — 検証: `verify.json.lpContrast`（3 style × 各 21 項目、すべて `pass:true`。例: solid header CTA 5.18:1、CTA帯見出し 17.13:1）。
5. **fullbleed hero の自動コントラスト guard は平均輝度による概算（`ratioText`）では合格する（`verify.json.lpFullbleedContrast`、`pass:true`。h1 4.77:1、lead 5.46:1）が、`ratioWorstPixel`（文字要素の矩形に対応する画像領域内の最大輝度とスクリムの近似から算出。`lpFullbleedContrast` の実装は `scripts/verify.mjs:407` から、標本化・合否判定は同422〜430行。画像全体の最明画素ではない）は h1 1.84・lead 3.26 でいずれも基準（大文字3:1・本文4.5:1）未達。文字直下の実際の背景画素との適合は未検証**（判定は `ratioText` のみを見ており `ratioWorstPixel` は合否に使われていない）。
6. fixed 3 型（none/sp-bottom-bar/float-cta）のいずれでも、シェア float・totop ボタンと重ならずタップ到達可能 — 検証: `verify.json.lpFixedOverlap.sp/.pc`（各 variant で `intersections: []`, `clickable` 全 true, `pass:true`）。
7. hero 画像（split/fullbleed/product）に `fetchpriority="high"` と `width`/`height` があり、text-only は画像を持たない — 検証: `verify.json.lpLcpHero.variants`（各 `attrs.fetchpriority==="high"`、`pass:true`）。
8. reduced-motion 環境で LP の出現要素が非表示のまま残らず、CTA/section の transition が停止する — 検証: `verify.json.lpReducedMotion`（`revealHidden:0, actionTransition:"none", sectionTransition:"none", pass:true`）。
9. LP 全面のタップ対象が 44px 目標・24px 下限を満たす（SP/PC 両方） — 検証: `verify.json.tap.lpSp` / `tap.lpPc`（各 `total:24, ok44:24, ok24:24, pass:true`）。
10. legal on では打消し表示 + PR 表記が出て、off では出ない — 検証: `lp-legal-on-*.jpg` / `lp-legal-off-*.jpg` 目視。
11. **イベント計測（表示・スクロール・CTAクリック・フォーム送信のデータ層契約送信、CV ID・A/B variant ID の付与）は試作 03 に実装がなく検証不能。**

**(d)** 台帳。分母の異なる2種類の値を区別する。

- **サービス/SaaS LP 限定の観察**（`by-purpose.md:30` 付近の表。分母は「na（切り出し範囲外・未描画）を除いたタグ出現数」であり、1 shot に複数の型が出れば各々を1と数える（`by-purpose.md:7`）。**サイト数ではない**。header/hero は `by-purpose.md` 表の分母、footer は v2 台帳の分母を用いており、v2 は na を含むタグ出現数で v1 とは分母の扱いが異なる（`by-purpose.md:7` 後段）): header は PC `logo-left-nav-right` 34%/`logo-left-cta-right` 34%（同率首位）、SP `logo-left-cta-right` 74%（最多）。hero は PC `split-text-image` 35%（最多）、SP **`text-only` 35%が最多、`split-text-image` は14%**（少数派）。footer（v2）は PC `mega(sitemap)` 91%（最多）、SP `accordion(sp)` 50%（最多、`mega(sitemap)` は35%、`single-row` は8%）。
- **全用途 top/PC・top/SP の観察**（`README.md:15`「hero CTA」、分母は同じくタグ出現数でサイトパターンを問わない全体。サービス LP 限定の値ではない）: `none` PC58%/SP66%（最多）、`single` PC17%/SP15%、`double` PC17%/SP11%、`form-inline` PC5%/SP5%。LP では `none`（CTAなし）を軸の選択肢に採用していないため、採用した3型（single/double/form-inline）のうち **PC は single と double が同率（17%/17%）だが、SP では single 15%・double 11%で同率ではない**。

既定値案（試作 03 実装）: header=minimal、hero=split、hero CTA=single、sections=full、CTA style=solid、fixed=none、legal=on、footer=single-row。**台帳との整合を要件ごとに見ると**: header の既定 `minimal`（3型中の1つ）は台帳の型そのものとは対応しない簡易ヘッダーのため多数派比較の対象外。hero の既定 `split` は **PC の多数派とは整合するが、SP の多数派（`text-only` 35%）とは異なる少数派の選択**である。hero CTA の既定 `single` は、PC では `single`/`double` が同率（17%）の一方を選んだもの（SP では `single` 15%が `double` 11%を上回る）。footer の既定 `single-row` は台帳の多数派 `mega(sitemap)`（PC91%）とは異なる少数派の選択であり、単一 LP ページでは項目数が少なく sitemap 型が過剰という Claude の判断による。**hero（SP）と footer は台帳の多数派と不整合であることを意図的な逸脱として記録する**。
**(e)** 問い: LP の表示・スクロール・CTAクリック・フォーム送信を、目標 CV ID・A/B variant ID 付きの version 付きデータ層契約で計測する機能を持たせる。採用するか。

---

## 問い一覧（WT-Q-PROTO3-nn、PO への機能採否のみ）

本節は「機能 X ができる。採用するか」の形だけを残す。実現方式の選択・AC 範囲の縮小・対象除外・検証工程や自動検査の採否・次段の対象や優先順位・実装記法の選択は PO への問いにせず、次の「TL 判断事項」「次段候補」へ分離した（HELIX の問いの規律、`docs/requirements/README.md:6`）。

| ID | 問い | 対象要件 |
|---|---|---|
| WT-Q-PROTO3-01 | 共有 slot は、内容が無い場合に空要素を DOM に残さない実装にする。採用するか | WT-FR-ZONE-01 |
| WT-Q-PROTO3-02 | LP を CPT（`show_in_rest`）として固定ページから分離し、ディレクトリ非依存 URL・種別（通常/イベント/比較特設）の JSON 列挙を持たせる（現行の固定ページ + `page-lp` テンプレート代替を置き換える）。採用するか | WT-FR-LP-01 |
| WT-Q-PROTO3-03 | LP の表示・スクロール・CTA クリック・フォーム送信を、目標 CV ID・A/B variant ID 付きのデータ層契約で計測する機能を持たせる。採用するか | WT-FR-LP-02 |

## 要求変更の候補（Claude 案、PO 判断待ち。問いにはしない — 要求正本の変更提案そのものであり、L2 の問い形式や TL 裁量には乗せない）

本節は既存要求の**縮小・置換・緩和の提案**を分離して記録する。試作 03 で検証した範囲の限定（TL 判断事項）とは異なり、ここは要求文そのものを変える提案であり、PO の判断（L2 の問い→改定サイクル）を経ない限り採用しない。

1. **WT-FR-ZONE-02**: 現行要求は「ゾーン語彙 23 種を JSON schema で宣言し、creative は参照（ID）、overrides は first-match-wins の配列で持つ」（`requirements-ir.json`、`events.jsonl:19` の WT-CAND-ZONE 採用に基づく）。試作 03 が実装したのは、この要求とは別の「34 軸 + 単一解決チェーン」方式である。**両者は別の実現手段であり、34 軸方式を要求の代替として採用するには、要求文自体を書き換える PO 判断が必要**（現状は試作 03 が要求を実現していない状態）。Claude 案（提案であり決定ではない）: 34 軸方式を正式な実現手段として要求文を置き換える。
2. **WT-FR-VOCAB-03**: 現行要求は「表示の要否は記事内の広告パーツ・アフィリエイトリンク・商品リンクの有無から機械判定して該当ページだけに自動出力する」（`requirements-ir.json`、`events.jsonl:23` で PR 表記が改定された経緯を持つ）。試作 03 は「全記事既定 ON + 記事単位の手動 off」であり、機械判定を実装していない。**機械判定を要件から外し手動運用に置き換えるかどうかは要求の変更であり、TL 裁量では決められない**。Claude 案（提案であり決定ではない）: 当面は手動 off 運用とし、機械判定は将来の改定候補とする。
3. **WT-FR-VOCAB-01**: 既存 AC（`WT-AC-VOCAB-01B`「8 つ目の新規ブロックがあれば FAIL」）は数える対象を区分せず一体として扱う。試作 03 の登録ブロック 5 種（カテゴリ系 3 + tail 系 2）は現状この一体の上限に対して 5 ≤ 7 で PASS するが、**将来カテゴリ系と記事内本文系を「別枠」でカウントする（例: 記事内本文系だけを 6+1 の対象にし、カテゴリ系は対象外にする）扱いに変えると、`WT-AC-VOCAB-01B` の合否基準に例外を設けることになり、要求の変更に当たる**。Claude 案（提案であり決定ではない）: 現時点では別枠にせず一体のままとし、将来ブロック数が上限に近づいた時点で別枠化を検討する。

## TL 判断事項（要求文・既存 AC の合否条件は変えず、試作 03 の検証範囲内で実装方式・AC 記述・検証工程を TL が決める事項。Claude の推奨を示すが、いずれも要求や AC の採否を変えない）

1. **ZONE-03 / NFR-SP-01**: TL が決める事項: AC の記述を「試作で検証済みのシェア float・totop の重なりなし」に一旦限定して書き、同意バー・広告面積上限・初回モーダル禁止・積層順検査の残り項目は次段の試作課題として別記する。Claude 推奨: この記述方針を採る（既存 AC の合否条件自体は変えず、試作 03 の検証範囲を記述する扱い）。
2. **PARTS-02**: TL が決める事項: 見た目の型ではなくテーマ実装規約（属性でなくテンプレ名で表現する）の要件のため、デザイン系受入条件の対象から外し実装 PR のレビュー観点として扱う。Claude 推奨: 対象外とする（既存 AC の合否条件は実装 PR 側で維持する）。
3. **VOCAB-04**: TL が決める事項: バックエンド解決方式（`url_to_postid()` 直呼び等）の受入条件のため、本ドラフトの対象から外し実装 PR 側で扱う。Claude 推奨: 対象外とする（既存 AC の合否条件は実装 PR 側で維持する）。
4. **SECTION-01/02**: TL が決める事項: 見た目の試作では検証しきれない「中間表現・AI 連携」要件のため、「未実装」として次段（実装設計 L3→L4）へ送る。Claude 推奨: 送る（既存 AC の合否条件は変えず、次段で検証する）。
5. **LOOK-01**: TL が決める事項: カード高さの自動統一の検証工程（自動検査付きにするか目視確認に留めるかの選択）。Claude 推奨: 次段で自動検査（実測高さ差分ゼロ確認）を追加する（既存 AC WT-AC-LOOK-01C の合否条件自体は変えない）。
6. **RECO-01**: TL が決める事項: 人気集計は試作 03 の日付順代替のまま「表示型のみ受入条件化」し、集計ロジック（bot/管理者除外・IP非保存）は別要件（実装 PR）で検証する。Claude 推奨: その扱いで進める（既存 AC の合否条件は実装 PR 側で維持する）。
7. **BANNER-01**: TL が決める事項: バナー正本（管理画面での一元管理・商品ID派生・全面ゾーン配置UI）は見た目の試作では検証できないため、実装設計（L3→L4）側の受入条件として切り出す。Claude 推奨: 切り出す（既存 AC の合否条件は実装設計側で維持する）。
8. **A11Y-01**: TL が決める事項: role/aria-expanded・キーボード操作契約・focus可視・reflow・text-spacing の axe 相当自動監査を検証工程に追加する。Claude 推奨: 次段の試作で追加する（既存 AC WT-AC-NFR-A11Y-01A/B の合否条件は変えない）。
9. **NAV-01**: TL が決める事項: BreadcrumbList の JSON-LD 検証（構造化データテストツール等）を検証手段に追加する。Claude 推奨: 追加する（既存 AC WT-AC-NAV-01A/B の合否条件は変えない）。
10. **SP-01**: TL が決める事項: SP/PC の device 別差分の実装方式として WordPress 7.1 の `@mobile`/`@tablet` 記法を採る。Claude 推奨: 採用する（PO への機能採否ではなく実装記法の選択。既存 AC WT-AC-SP-01A/B の合否条件は変えない）。
11. **NFR-SP-02 / NFR-PERF-01〜03**: TL が決める事項: Lighthouse/CWV 実測・device別A/B集計の検証工程を、次段（実サーバー相当環境）で行う。Claude 推奨: 次段で実施する（既存 AC の合否条件は変えない）。

## 次段候補（Claude 案。次段の試作対象・優先順位の提案であり、PO への採否問いではない）

1. **PARTS-01**: header の透過ヘッダー型（transparent-over-hero、top/PC 8%・top/SP 11%観察）を次段の型追加候補に含める。
2. **LOOK-03**: 次段（段6以降）で企業HP・サービスLPパターンの試作に進む。優先順位は企業HP→サービスLPの順を Claude 案とする。
3. **TPL-01**: 検索結果テンプレート（noindex・ログ非保存）の実測を次段の試作対象に加える。
4. **SP-02**: 下部固定3〜5タブ（CVタブバー: 電話/メッセージ/資料DL/目次/トップへ）を次段の試作対象に含める。
5. **SP-03**: タブのアコーディオン変換・ギャラリーのスワイプ・CTAの全幅/sticky変換を次段の試作対象に含める。

---

## 未実装・未検証で試作 03 が触れていないデザイン系要件

**選定基準**: 要件の (c) に、①条件そのものが 1 つも書けない（全面未実装）、②書いた条件の一部に検証手段がない・未実測と明記した、のいずれかに該当する要件を挙げる（一部の条件だけ検証できた要件も含む。全条件が検証できた要件は挙げない）。**照合方法**: 本文の (c) から「未検証」「検証手段なし」「未実測」「未判定」等の語を含む記述を grep で全件抽出し、それぞれの記述が属する要件 ID が本一覧の同じ要件 ID の項目に内容として含まれることを確認した（35 件・要件 ID の重複なし）。

1. WT-FR-ZONE-01 — 空 slot が DOM に要素を残さない（お知らせ帯 OFF 時）ことの検証（`verify.json.noJs`の`vis()`判定は非表示とDOM削除を区別せず、専用の検査がない）。また `fixedOverlap.sp.intersects` はシェア float と totop の重なりのみを測り、本文本体や CTA との被覆有無は未検証
2. WT-FR-ZONE-02 — ゾーン語彙 23 種 JSON schema・overrides first-match-wins（軸方式に部分対応のみ）
3. WT-FR-ZONE-03 — 同意バー・広告面積上限・初回モーダル禁止（一部のみ、同意バー自体が未実装）
4. WT-FR-PARTS-01 — footer_layout の残り 2 型（single-row / columns-3）の JS 無効時の挙動（`footerNoJs` は既定の sitemap 型 1 ケースのみ検査）
5. WT-FR-PARTS-02 — テンプレ変種名・footer カラム可変・wp_navigation ref 参照
6. WT-FR-VOCAB-01 — メディア枠 5 択（自前SVG・アップロード・写真・番号・なし）の切替検証（README に個別記載がなく未実測）、および `WT-AC-VOCAB-01A`（14 語彙×受け皿の対応表、実使用上位7種・販売系4種の描画）は未判定（件数上限条件 `WT-AC-VOCAB-01B` のみ PASS 判定済み）
7. WT-FR-VOCAB-03 — 広告パーツ・アフィリエイト/商品リンクの有無からの機械判定・自動出力（試作03は全記事既定ON+手動offで代替、機械判定自体は未実装）。試作03の実測は`wt_pr`既定ON状態のみで、記事単位で`wt_pr=off`にした場合の表記消失は未実測（手動off実測未取得）
8. WT-FR-VOCAB-04 — url_to_postid 直呼び・外部 URL 検証付き HTTP（解決方式）
9. WT-FR-SECTION-01 — 見出し区間の中間 JSON 出力・安定 ID・見出し ID 付与規則自体（目次件数の一致以外に検証手段がなく全面未検証）
10. WT-FR-SECTION-02 — 区間単位のリライト・diff/apply/rollback・計測
11. WT-FR-LOOK-01 — 文字直下の背景でのコントラスト適合（最悪画素基準。`ratioWorstPixel` は合否判定に使われておらず未検証）
12. WT-FR-LOOK-02 — 複数プリセット→複数 variation の写像（色スラッグ集合不変の自動検査を含め全面未検証）
13. WT-FR-LOOK-03 — 他 4 サイトパターン（企業HP/サービスLP/ポータル/ブランド）の variation 実装
14. WT-FR-LOOK-04 — 和文フォント複数系統の選択・自己ホスト・サブセット化
15. WT-FR-META-01 — メタ未設定の記事がサイト既定に従うことの確認（単一記事での上書き確認のみで、複数記事の比較は未実施）
16. WT-FR-BANNER-01 — バナー正本の一元管理・商品ID派生・全面ゾーン配置UI（一部のみ、お知らせ帯派生は実装）
17. WT-FR-BANNER-02 — バナー計測（impression/click・rel=sponsored・広告タグ分離）
18. WT-FR-SP-01 — `@mobile`/`@tablet` 記法の使用有無・管理画面/MCP個別編集UI・SP横スクロール量の検査（tap監査は横スクロールを測らない）・各軸の設定値を端末別に独立して保持/変更できることの検証（`wt_opt()` は軸ごと単一値の解決で専用検査なし）
19. WT-FR-SP-02 — SP下部固定3〜5タブ（電話/メッセージ/資料DL）・ドロワー階層・SP専用広告面
20. WT-FR-SP-03 — タブのアコーディオン変換・ギャラリーのスワイプ・CTAの全幅/sticky変換
21. WT-FR-TPL-01 — 検索結果テンプレートの noindex 既定、bot 除外・IP 非保存の検索語ログ（404 のみ実測、検索結果自体は未実測）
22. WT-FR-RECO-01 — 人気の集計方式・期間選択、bot/管理者除外・IP非保存（試作03は日付順代替で集計ロジック自体は未実装）
23. WT-NFR-A11Y-01 — role/aria-expanded・キーボード操作契約・focus可視・reflow・text-spacingのaxe相当自動監査（`verify.json.summary`はaxe実行を含まない合算値のため代替不可）
24. WT-NFR-SP-01 — SP/PC横スクロール量の検査（tap監査は横スクロールを測らない）、同意バーを含む3要素の積層順検査
25. WT-NFR-SP-02 — Lighthouse/CWV測定・device別A/B集計・管理画面/MCPプレビュー
26. WT-NFR-PERF-01 — 予算超過ゲート・第三者スクリプト遅延注入のCI接続
27. WT-NFR-PERF-02 — CSS転送量予算・critical CSS inline・フォントswapのビルド検証
28. WT-NFR-PERF-03 — Lighthouse/CWV閾値のCI blocking gate
29. WT-FR-IMG-01 — WebP/AVIF生成パイプライン・GIF→WebM変換
30. WT-FR-IMG-02 — 非同期ジョブ（WP-Cron等）・dry-run
31. WT-FR-IMG-03 — alt必須警告UI・GIF動画置換提案・Discover代表画像検査（alt属性が空文字で保証される点のみ(c)に条件あり、警告UI・置換提案・Discover検査自体は未実装）
32. WT-FR-TYPO-01 — 和文改行制御の個別CSSプロパティ（`text-wrap`等）、横スクロール量の検査
33. WT-FR-NAV-01 — BreadcrumbList JSON-LD の同一正本検証（表示は確認できたが構造化データ出力は未検証）。加えて、パンくずの文字直下背景とのコントラスト適合は、平均輝度による概算（`ratioText`）では基準を満たすが`ratioWorstPixel`（A#0 3.87・A#1 3.56）はいずれも4.5未満で、文字直下の実際の背景画素との適合自体は未検証
34. WT-FR-LP-01 — CPT化（`show_in_rest`によるREST分離）・ディレクトリ非依存URL・種別（通常/イベント/比較特設）のJSON列挙（試作03は固定ページ+専用テンプレートで代替、CPT化自体は未実装。`_wp_page_template`保存値・`page-template-page-lp`classもverify.json未検証）。また `lpAnchorNav`（`lp_header:none` のアンカーナビ、(c)条件4）はリンク先IDの存在とリンク自身の可視性のみ検査しており、遷移先要素（ジャンプ先セクション）自体の可視性は未検証
35. WT-FR-LP-02 — イベント計測（表示/スクロール/CTAクリック/送信のデータ層契約送信、CV ID・A/B variant ID の付与）。fullbleed hero の文字直下背景とのコントラスト適合は、平均輝度による概算（`ratioText`）では基準を満たすが`ratioWorstPixel`（h1 1.84・lead 3.26）はいずれも基準未達で、文字直下の実際の背景画素との適合自体は未検証（アンカー遷移先の可視性未検証は WT-FR-LP-01 の項目を参照）

以上 35 件（本一覧は「(c) を全く記載していない全面未実装」と「(c) に一部条件はあるが別の一部が未実装・未検証」の両方を含む）。

**集計基準**: 「検証手段あり」は、(c) の条件のうち少なくとも 1 件が、引用した実測値・検査結果から明確な PASS/FAIL の受入判定に至っている要件を数える。「全面未検証」は、(c) の全条件が受入判定に至らず（条件が書けない、または書いた条件がすべて「未検証」「検証手段なし」で締められている）要件を数える。この基準では、平均輝度の概算値の引用だけで実際の受入判定（文字直下の背景での適合）に至っていない場合は「検証手段あり」に数えない。

対象 37 件のうち、(c) に検証手段付きの条件を 1 件以上記載した要件は 28 件（WT-FR-IMG-03 は alt 属性保証の条件が明確に PASS 相当のため「1 件以上記載」に含む）、(c) の全条件が受入判定に至っていない要件は 9 件（WT-FR-SECTION-01 / WT-FR-SECTION-02 / WT-FR-LOOK-02 / WT-FR-LOOK-04 / WT-FR-BANNER-02 / WT-FR-IMG-01 / WT-FR-IMG-02 / WT-FR-TYPO-01 / WT-FR-NAV-01）。**WT-FR-NAV-01 は (c) の 2 条件がいずれも「未検証」で締められている（条件1は平均輝度の概算値は引用するが最終的な適合判定は未検証、条件2はJSON-LD自体が未検証）ため、本集計では全面未検証に含める。** WT-FR-LP-01 は (c) に受入判定に至った条件を含むため「全面未検証」には含めない（CPT 化に限定した未実装として上記一覧に計上）。なお WT-NFR-PERF-02/03 の (c) にある「JS無効環境での表示成立」条件は WT-NFR-PERF-01 との共通 no-JS 検査であり、これを CSS転送量予算・Lighthouse/CWV閾値そのものの達成証拠と混同しない（予算・閾値自体は本一覧の26〜28番として未実装に計上している）。
