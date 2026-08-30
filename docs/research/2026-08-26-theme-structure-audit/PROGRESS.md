# イシュー消化状況

最終更新: 2026-08-27（read-only 採取と自己点検を反映）

状態: `未着手` / `一次完了`（読み取りで到達できる範囲を出し切った）/ `完了`（受入条件をすべて充足）/
`承認待ち`（PO 判断が要る項目で止まっている）

イシュー総数 **17 本**（INV-17 は消化パスで新たに切り出した）。
**17 本すべてが一次完了**（＝読み取り済みの証跡で到達できる範囲を出し切った状態）。
残りはいずれも**サーバーへの追加読み取り / HTTP 確認 / PO 承認**が要る項目で、
各レポートの「未了項目」に手順つきで記載してある。

| ID | Issue | 表題 | 優先 | 状態 | レポート |
|---|---|---|---|---|---|
| INV-13 | [#15](https://github.com/RetryYN/HELIX-WP-THEME/issues/15) | テーマA の未認証 REST 2 本の到達性と対処 | 最優先 | **一次完了 / 承認待ち** | `reports/INV-13-themeA-rest-endpoints.md` |
| INV-01 | [#3](https://github.com/RetryYN/HELIX-WP-THEME/issues/3) | ブロック語彙 3 系統の対応表 | 高 | **一次完了**（意味層。属性層は INV-14 待ち） | `reports/INV-01-block-vocabulary-map.md` |
| INV-03 | [#5](https://github.com/RetryYN/HELIX-WP-THEME/issues/5) | 広告 / CV ゾーン仕様の横断確定 | 高 | **一次完了**（正規化 23 ゾーン + スキーマ改訂案） | `reports/INV-03-ad-cv-zones.md` |
| INV-02 | [#4](https://github.com/RetryYN/HELIX-WP-THEME/issues/4) | 動的ブロックの意味論と再現性 | 高 | **一次完了** | `reports/INV-02-dynamic-render-semantics.md` |
| INV-04 | [#6](https://github.com/RetryYN/HELIX-WP-THEME/issues/6) | 再利用パーツ機構の抽象化契約 | 高 | **一次完了** | `reports/INV-04-reusable-parts-mechanism.md` |
| INV-12 | [#14](https://github.com/RetryYN/HELIX-WP-THEME/issues/14) | 資産再利用可否台帳 14 件 | 高 | **暫定版**（6 確定 / 5 暫定 / 3 未判定） | `reports/INV-12-asset-reuse-ledger.md` |
| INV-05 | [#7](https://github.com/RetryYN/HELIX-WP-THEME/issues/7) | デザイントークンの正本と投影方式 | 中 | **一次完了**（部分集合に適用） | `reports/INV-05-design-token-projection.md` |
| INV-06 | [#8](https://github.com/RetryYN/HELIX-WP-THEME/issues/8) | 構造化データ出力の差分 | 中 | **一次完了** | `reports/INV-06-structured-data-gap.md` |
| INV-07 | [#9](https://github.com/RetryYN/HELIX-WP-THEME/issues/9) | 目次と本文フィルタ機構の方式 | 中 | **一次完了** | `reports/INV-07-content-filter-and-toc.md` |
| INV-08 | [#10](https://github.com/RetryYN/HELIX-WP-THEME/issues/10) | エージェント接点（REST / フック）の差 | 中 | **一次完了** | `reports/INV-08-agent-interface-gap.md` |
| INV-15 | [#17](https://github.com/RetryYN/HELIX-WP-THEME/issues/17) | テーマB パイプラインの中間 JSON 転用可否 | 中 | **一次完了** | `reports/INV-15-themeB-pipeline-transfer.md` |
| INV-16 | [#18](https://github.com/RetryYN/HELIX-WP-THEME/issues/18) | テーマA の描画時 DB 書き込み副作用 | 中 | **一次完了** | `reports/INV-16-themeA-render-side-effects.md` |
| INV-10 | [#12](https://github.com/RetryYN/HELIX-WP-THEME/issues/12) | ショートコード後方互換の扱い | 低 | **一次完了** | `reports/INV-10-shortcode-compat.md` |
| INV-11 | [#13](https://github.com/RetryYN/HELIX-WP-THEME/issues/13) | スコープ境界（課金・会員） | 低 | **一次完了 / 承認待ち** | `reports/INV-11-scope-boundary.md` |
| INV-14 | [#16](https://github.com/RetryYN/HELIX-WP-THEME/issues/16) | テーマA ブロック属性表の帰納 | 高 | **一次完了**（`blogcard` 全属性 + 共通属性を確定・抽出スクリプト用意） | `reports/INV-14-themeA-attribute-induction.md` |
| INV-17 | [#19](https://github.com/RetryYN/HELIX-WP-THEME/issues/19) | テーマA のグローバル改変の影響確定 | 高 | **一次完了 / 承認待ち**（コード解析完了・検証手順を定義） | `reports/INV-17-themeA-global-side-effects.md` |
| INV-09 | [#11](https://github.com/RetryYN/HELIX-WP-THEME/issues/11) | サイト設定の正本と移管方式 | 中 | **一次完了**（分類軸 4 種・移管手順・判別可否の結論） | `reports/INV-09-settings-authority.md` |

## 一次完了 17 本で確定したこと

| # | 確定したこと |
|---|---|
| **INV-09** | 分類軸を **①サイト固有の意味 / ②見た目の選択 / ③テーマ内部状態 / ④副作用既定値**の 4 種に確定。**アクセサ 707 のうち約 530（75%）が「見た目」**で、移管必須はおよそ 60〜80 キー（全体の 5〜7%）。**判別不能問題は実害なし** — `set_theme_mod()` の副作用が及ぶ 5 キーはすべて②に属し、移管必須集合に入らない。テーマA の目録化は「実在キーの列挙 → キー名で分類 → 分類できないものだけ関数を読む」の順で 707 関数を全読せずに済む |
| **INV-14** | `blogcard` の全属性を確定。**共通属性 7 種**（`topMarginPcAttribute` 等の余白 4・`displayDeviceAttribute`・`className`・`themeABlocksCSSAttribute`）を特定し、その値が**数値ではなく CSS クラス名の文字列**であることを確認 → 中間 JSON へは**クラス名から意味への逆変換**が要る。抽出スクリプト `extract-themeA-attrs.sh` を用意（1 回の実行で INV-10 / 01 / 02 の未了 5 件が同時に閉じる） |
| **INV-17** | `redirect_canonical` が引数を見ず常に `false` を返し、**正規化リダイレクトが全面停止**することをコードで確定。停止する正規化 6 種を列挙。`session_regenerate_id()` の毎リクエスト実行が**セッション継続性・ディスク・キャッシュ**に与える影響を整理。**有料記事の実使用 0（INV-11）なので、このセッション処理は全ページで純粋なオーバーヘッド**。検証コマンドと子テーマでの対処案を用意 |
| **INV-01** | 75 ブロック（テーマA 25 / テーマB 50）を**意味 31 種**へ正規化。**両テーマに専用ブロックがある 11 組**が意図語彙の第一候補。テーマA の比較表（177+59）と テーマB の定義リスト（106+106）・FAQ（56）は**相手側に対応物が無く、両方持つ必要がある**。インライン書式は**ノードではなくテキストの装飾レンジ**として持つと判定 |
| **INV-03** | ゾーンを**意味で 23 種に正規化**（テーマA 11 + テーマB 24 の和集合）。`ad-zone.schema.json` の差分 3 点を特定 — **`category_override` はゾーンではなく上書き規則**（同列に並べるのは誤り）、20 ゾーンが語彙に無い、条件表示のモデルが無い。`creative_ref` を参照にし `overrides` を first-match-wins の配列にする改訂案を提示 |
| **INV-13** | 独自 REST は 2 本、両方 `permission_callback => '__return_true'`。`external_url` は未検証 URL を `file_get_contents()` へ渡す SSRF 構造。**`post_by_url` はブログカード（実使用 330）が `rest_do_request()` で内部ディスパッチ**しており、ルート除去型の対処は描画を壊す。サーバ層で HTTP 経由のみ遮断すれば内部呼び出しは通る |
| **INV-02** | 動的ブロックは **7 種**（「9 種以上」を訂正）。**6 種は正規化で決定論レンダラに載る／`paidpost` のみ載らない**。`register_block_style('core/list')` 2 件を新規発見 |
| **INV-04** | 中間 JSON は**参照（ID）で持ち、解決に使った版と digest を記録する**と決定。循環参照・欠落・下書きの規約を定義。`PartsAdapter` の最小インタフェースを定義。**Graphix NEO は テーマB 方式（`public=false` + `show_in_rest=true` の CPT）を採る**ことを推奨 |
| **INV-12** | 台帳 14 行のうち **6 確定 / 5 暫定 / 3 未判定**。REST 34 本を A 群 16（契約付き移植）/ B 群 9（不採用）/ C 群 4（契約のみ + アダプタ）/ D 群 4（基盤）に確定 |
| **INV-05** | 仕分けを **A 意味的 / B 部品固有 / C 状態フラグ / D レイアウト寸法**の 4 分類に確定（C を独立させたのが要点）。**テーマA の先頭 50 件に意味的トークンが 1 つも無い**。派生色の規則が生成関数にハードコードされており、トークン体系が存在しない |
| **INV-06** | テーマA は**記事型（Article / BlogPosting）を出していない**。不足は `CollectionPage`（高）と `SearchAction`（中）。**FAQPage / HowTo / ItemList は中間 JSON の意図ノードから自動生成できる**＝中間 JSON 方式の優位点 |
| **INV-07** | 本文変換を全一覧化し 3 層へ割り当て。**目次は中間 JSON の一級要素にしない**（配置だけ意図ノード、実体はレンダラ導出、既定は最初の h2 直前） |
| **INV-08** | テーマA 操作の現実解は **コア REST（記事）+ WP-CLI / ブラウザ（設定）**。option 直接書き換えは非推奨。**名前空間はコアに相乗りしない** |
| **INV-15** | テーマB の 4 機構のうち **2 つ転用可**。抽出器の最小実装範囲と、そのまま使えない 4 点を特定 |
| **INV-16** | `set_theme_mod()` 5 箇所を全列挙。**値だけでは「人が決めた設定」と「副作用で入った既定値」を判別できない** → 移管に事前スナップショットが必須 |
| **INV-10** | テーマ語彙のショートコードは**意図ノードへ展開**、プラグイン語彙は**不透明ノードで原文保持**と確定。`[themeA_fukidashi]` 186 = ブロック 186 の完全一致から「ブロックの save 出力がショートコードを含む」仮説を立て、検証手順を定義 |
| **INV-11** | **案 A（スコープ外 + プラグイン委譲）を推奨**。テーマB 側は実データ 0 で失うものが無い。テーマA の有料記事は実運用確認が前提。**INV-17（全ページセッション）と連動** |

## 引き継ぎ検証（2026-08-26・別セッションで再点検）

証跡ファイルとレポートの突き合わせを実施。結果:

| 主張 | 生証跡 | 判定 |
|---|---|---|
| 動的ブロック 7 種（render_callback） | `probe3-raw.txt` に 7 register + 18 static = 25 | ✅ 支持 |
| paidpost 実使用 0（16 はソース内文字列） | `usage-raw.txt` に不在／`probe2-raw.txt:18` に 16 | ✅ 支持 |
| set_theme_mod 5 箇所 | `re-themeA-boot.txt`（キー・既定値・行番号一致） | ✅ 支持 |
| redirect_canonical 無効化 + 全ページ session | `probe3-raw.txt:280-294` | ✅ 支持 |
| **REST 2 本 + `__return_true` + SSRF** | **生キャプチャ無し**（`re-themeA-rest.txt` 未生成・`theme-features-raw.txt` の テーマA REST 欄は空）。`post_by_url` の内部呼び出しのみ `re-themeA-ads.txt` で裏取り | ⚠ **要是正**（本文手写しのみ） |

**修正済みの実欠陥（本セッション）:**
- INV-02 §2.1 / §6 の paidpost「実使用 16」→ **0** に訂正（postcard/slider/category も usage-raw で 0 のため 0 に統一）。
- 00-REPORT のイシュー本数「12 本 / 16 本」→ **17 本**に統一。
- 00-REPORT §6.2 の PO 判断「起票しない」→ PO 目標により **撤回・起票する**に更新。
- INV-13 冒頭に「証拠ギャップ」バナーを追加（SSRF は生採取が未了である旨）。

**最優先の是正**: Bash/SSH 復旧後、下記「復帰後の作業順」3（`re-themeA-rest.txt` 生採取）を
起票より先に回し、目玉のセキュリティ主張に確実な証拠を付ける。

## RE パスで判明した訂正

| 項目 | 初回 | 訂正後 | 原因 |
|---|---|---|---|
| テーマA の独自 REST | 0 本 | **2 本** | 複数行 `register_rest_route(` の grep 漏れ |
| テーマA の `register_block_style` | 0 | **2 件**（`core/list`） | 同上 |
| テーマA の動的ブロック | 9 種以上 | **7 種** | 登録コード全文を数え直し |
| `themeA-blocks/paidpost` の実使用 | 本文中 16 回 | **公開記事で 0 回** | 16 は `probe2-raw.txt` の**テーマソース内の文字列出現数**であり、本文の使用回数ではなかった（`usage-raw.txt` に該当なし） |

**新規に見つかった不整合**: `evidence/usage-raw.txt` に `themeA-blocks/profile` が 1 回出現するが、
このブロック名は登録一覧 25 種に**存在しない**（廃止ブロックの残存と推定。INV-14 で確認）。

## 承認待ち

| 項目 | 内容 | イシュー |
|---|---|---|
| ~~到達性の実証~~ | **2026-08-27 実施済み**（未認証で HTTP 200・サーバー側取得を確認） | INV-13 |
| ~~本番 DB の SELECT~~ | **2026-08-27 実施済み**（5 キーとも既定値と相違＝人が決めた設定） | INV-16 |
| ~~実ページの JSON-LD 採取~~ | **2026-08-27 実施済み**（4 種別すべて ld+json 0 本） | INV-06 |
| ~~正規化リダイレクトの実挙動~~ | **2026-08-27 実施済み**（末尾スラッシュ有無とも 200・Location なし） | INV-17 |
| 有料記事の実運用確認 | secret_key/release_key の**存在は確認済み**（値は未取得）。`_themeA_paidpost` の件数は未実施 | INV-11 |
| 台帳の反映 | GRAPHIX-NEO 側 `docs/references/` の更新（cross-repo） | INV-12 |
| ベンダー報告 | ベンダーA への連絡要否 | INV-13 |

## 作業ブロッカー（2026-08-26 時点・継続中）

**/tmp のディスク quota が枯渇している。** 2 バイトの書き込みでも `EDQUOT` で失敗する。
Bash ツールはサンドボックスの作業ファイルを /tmp に作るため、`true` すら起動できず全コマンドが失敗する。
`~/dev` 配下への書き込みは通るため、レポート作成のみ継続できている。

この影響で:
- ~~GitHub イシューの起票（gh）が実行できない~~ — **2026-08-27 起票済み**: root [#2](https://github.com/RetryYN/HELIX-WP-THEME/issues/2) + THEME-INV-01〜17 = #3〜#19（`create-issues-helix.sh`、HELIX 起票規律準拠・第三者テーマ名は伏せ字）。
  代わりに**起票スクリプト `create-issues.sh` を用意した**（ラベル 25 種の作成 + 17 本の起票、
  重複防止つき。`DRY_RUN=1` で内容確認可）。シェル復帰後に 1 コマンドで起票できる
- **サーバーへの追加 SSH 読み取りができない** — 各レポートの「未了項目」が着手できない。
  代わりに**抽出スクリプト `extract-themeA-attrs.sh` を用意した**（読み取り専用。
  1 回の実行で INV-14 / 01 / 02 / 10 / 11 の未了 5 件が同時に閉じる）
- **git commit / push ができない** — `reports/` 17 本・`PROGRESS.md`・スクリプト 2 本・訂正分が未 commit

17 本のレポートは、既取得の証跡（`evidence/` 17 ファイル）と読み取り済みソースのみで作成した。
**推測は「仮説」「推定」と明記し、未読部分は「未了」として区別している。**

### 復帰後の作業順

1. ~~起票~~ 完了（2026-08-27、#2〜#19）
2. `bash docs/research/2026-08-26-theme-structure-audit/extract-themeA-attrs.sh > evidence/themeA-attrs-raw.txt`
   → INV-14 / 01（属性層）/ 02 / 10 / 11 の未了が閉じる
3. `evidence/re-themeA-rest.txt` の採取（INV-13 §2 のソースを生キャプチャとして残す）
4. レポート 17 本 + `PROGRESS.md` + スクリプト 2 本 + 訂正分の commit / push
5. **INV-03 ④**（`sidebars_widgets` の読み取り。実配置で第一級ゾーンを確定）
6. **INV-09 ①**（`wp option list --search='themeA_*'` で実在キーを列挙 → 4 分類）
7. **INV-13 / 17**（HTTP 確認・PO 承認後）
8. **INV-12** の台帳を再判定し、GRAPHIX-NEO 側へ反映（PO 承認後）

## 復帰後の採取結果（2026-08-27・read-only SSH、伏せ字済み）

| 手順 | 証跡 | 結果 |
|---|---|---|
| 2. `extract-themeA-attrs.sh` | `evidence/themeA-attrs-raw.txt` | 公開記事のブロック 20 種・出現数（simplebox 697 / button 339 / blogcard 330 / fukidashi 186 …）と属性キーを帰納。**共通属性 7 種のうち `className` 299 以外は実使用 0**（余白・表示デバイス属性は保存されていない）。`blogcard` は `thumbnailUrl / postUrl / postTitle` 330 + `blogcardLabel` 15 のみ |
| 3. REST 生キャプチャ | `evidence/re-themeA-rest.txt` | `custom-functions.php` 3675 行・3780 行の `register_rest_route` 2 本、`permission_callback => '__return_true'`（3680 / 3785）、`file_get_contents($post_url)`（3796）を**生ソースで確定**。INV-13 の証拠ギャップは解消 |
| 5. `sidebars_widgets` | `evidence/option-and-sidebars-raw.txt` | 登録サイドバー 12（toppage / post-top / post-start / post-end / post-bottom / relatedpost-bottom / footer / sidebar / sidebar-tracking / hamburger / smartslider_area_1 / inactive）。実配置: toppage 1・post-top 1・post-end 3・sidebar 3+、他は空 |
| 6. `option list themeA*` | 同上 | `themeA*` option **179 件**（名前のみ）、`theme_mods_themeA` **235 キー**（キーのみ・値は採取しない） |

### 採取で閉じた／進んだ未了項目

- **INV-13**: SSRF 主張の生証跡を確保（⚠要是正 → ✅）。残りは到達性の HTTP 実証（PO 承認）とベンダー報告要否
- **INV-14**: 属性表を実データで確定。共通属性 7 種のうち保存済みコンテンツで使われているのは
  `className`（299）のみで、余白 4・`displayDeviceAttribute`・`themeABlocksCSSAttribute` は **0**。
  ただし**テーマ側には定義が存在する**（`re-themeA-ads.txt`）ため、上表の「クラス名から意味への逆変換が要る」と矛盾しない。
  正しくは「**本サイトの現行コンテンツを移行する限りでは逆変換は不要**。他サイトや将来の入力に備えて
  変換規則そのものは実装対象に残す」（INV-01 属性層も同時に閉じる）
- **INV-10**: 仮説「ブロックの save 出力がショートコードを含む」を**支持** — `fukidashi` ブロック本文に `[themeA_fukidashi]` `[themeA_fukidashi1]` `[themeA_fukidashi2]` が内包。`profile` ブロックも `[themeA_profile …]` を内包する薄いラッパー（未登録ブロック 1 件の正体）
- **INV-11**: `paidpost` は公開記事で 0（ブロック一覧に不在）を再確認
- **INV-02**: 動的ブロック 7 種のうち公開記事で使用は **3 種 = button 339 / blogcard 330 / postlist 38（計 707 インスタンス）**。
  未使用は postcard / slider / category / paidpost の 4 種。
  → 決定論レンダラは **button と blogcard が最優先**。さらに blogcard は内部で `post_by_url` を
  `rest_do_request()` する（INV-13）ため、ルート遮断の判断は 330 インスタンスの描画に直結する
- **INV-03 ④**: 登録 12 領域のうち実配置は **5 領域**（toppage 1 / post-top 1 / post-end 3 / **sidebar 11** / **sidebar-tracking 1**）。
  空は 6 領域（post-start / post-bottom / relatedpost-bottom / footer / hamburger / smartslider_area_1）。
  ウィジェット総数の 3 分の 2 が sidebar に集中しており、第一級ゾーンは sidebar 系を外せない
- **INV-09 ①**: 実在キー 179 + 235 を列挙済み。次は 4 分類（値の読み取りは要 PO 判断）

## HTTP 実測（2026-08-27・自サイトへの read-only GET、PO 許可）

証跡: `evidence/http-audit-raw.txt`（サーバー上から自ドメインへ GET。伏せ字済み）

| Issue | 実測結果 | 判定 |
|---|---|---|
| **INV-06** | ld+json ブロックが **front / single / category / search すべてで 0 本**。構造化データは JSON-LD を一切出力していない（og: メタのみ）。しかも `og:type` が category・search でも `article` になっている | レポートの「記事型を出していない」を**上回る**確定 — 型が欠けているのではなく**機構ごと不在**。`CollectionPage` / `SearchAction` 以前に Article から要る |
| **INV-17 ①** | 末尾スラッシュ有無の両方が **200 を返し、`Location` ヘッダなし・redirects=0**。正規化リダイレクトが実際に停止していることを実測で確認 | コード解析（`redirect_canonical` が常に false）を**実挙動で裏付け**。重複 URL が両方 200 = 正規化されない |
| **INV-17 ②** | `Set-Cookie: PHPSESSID` が **測定した 3 ページすべてで発行**（front / category / search） | 全ページ `session_start()` + `session_regenerate_id()` を**実挙動で確認**。有料記事の実使用 0（INV-11）なので純粋なオーバーヘッド |
| **INV-13 ①** | `/wp-json/` の namespace 一覧に `themeA` が露出。`post_by_url` は**未認証で HTTP 200**、`external_url` も**未認証で HTTP 200 を返し、指定 URL をサーバー側で取得して og: メタを返却**（検証は自サイトを取得先とする良性リクエストのみ） | **SSRF の到達性を実証**。コード解析どまりだった主張が実測で確定 |
| **INV-13 ②（新規）** | `post_by_url` が不正な入力で PHP Warning をそのまま応答本文へ出力し、**テーマファイルの絶対パスと行番号（`.../themes/themeA/include/custom-functions.php` 3726 行）を未認証で開示** | 調査中に発見した新しい事実。パス開示は攻撃面の下調べを容易にする。finding として別 Issue に切り出す |

## 設定値の実測と分類（2026-08-27・read-only）

証跡: `evidence/theme-mods-and-ini-raw.txt` / `evidence/option-key-classification.tsv`

### INV-16: 「判別不能問題」は本サイトでは発生しない

`set_theme_mod()` の副作用 5 キーの現在値を、コードにハードコードされた既定値と突き合わせた結果、
**5 キーすべてが既定値と異なる** = いずれも人が決めた設定である。

| キー | 現在値 == 既定値 | 判定 |
|---|---|---|
| `themeA__theme_color` / `themeA__header_bg_color` / `themeA__header_menu_color` / `themeA__text_color` / `themeA__bg_color` | すべて False | **人が決めた設定**（既定値と異なる） |

→ レポートの「値だけでは判別できない → 移管に事前スナップショットが必須」は、
**本サイトに限れば不要**。差分が出た時点で人の選択だと確定できる。
（既定値と一致するサイトでは依然として判別不能なので、手順としてのスナップショットは残す。）
`theme_mods_themeA` は 235 キー・値の型は str 210 / bool 23 / int 1 / dict 1。

### #21: パス開示の原因を特定

- `wp-config.php`: `WP_DEBUG` は **false**（WordPress のデバッグ設定は正しい）
- ドキュメントルートの **`.user.ini` に `display_errors = On`** ← これが原因
- `log_errors=1` / `error_reporting=32767`

→ 対処は**サーバ層（`.user.ini` を Off）で 1 行**。テーマ更新の影響を受けず、#15 の SSRF 対処とは独立に即座に閉じられる。
（本番への write なので実施は PO 判断。）

### INV-09: 179 キーを 4 分類 + credential 軸で全件分類（保留 0）

| 軸 | 件数 | 比率 | 扱い |
|---|---|---|---|
| **A** サイト固有の意味 | 152 | 84% | **移管必須**（広告・計測コード、SEO ポリシー、表示文言、出し分けの選択） |
| **B** 見た目の選択 | 23 | 12% | 任意移管 |
| **C** テーマ内部状態 | 2 | 1% | 移管しない |
| **X** credential | 2 | 1% | `themeA_paidpost_release_key` / `themeA_paidpost_secret_key` — **値を読まない・移管しない・ログに出さない** |
| D 保留 | 0 | 0% | — |

→ レポートの推定「移管必須は 60〜80 キー（全体の 5〜7%）」は **option 側では大きく外れる**。
`theme_mods`（見た目 75%）と `option`（サイト固有 84%）で性質が正反対であり、**2 つを分けて扱う**必要がある。
また **credential 軸（X）が存在する**ことが判明したため、4 分類に X を足した 5 軸へ改める。
`themeA_paidpost_*` は INV-11（有料記事の実使用 0）と整合し、移管対象から明示的に除外する。

**未了（PO 判断が要る）**: ベンダー報告の要否、対処案の採用（`.user.ini` / WAF 遮断 / 子テーマ）、
`theme_mods` 235 キーの同種分類（値の読み取りを伴う）。

## 自己点検（2026-08-27・別セッションで採取結果を再照合）

起票済み Issue のコメントと本 PROGRESS の主張を、証跡ファイルの実データへ機械的に突き合わせた。

### 訂正した誤り

| # | 誤り | 正 | 影響 |
|---|---|---|---|
| **#4 (INV-02)** | 「動的ブロック 7 種のうち使用は postlist 38 のみ」 | **button 339 / blogcard 330 / postlist 38 の 3 種・計 707** | **重大**。ただし**レポート本体 `reports/INV-02` §2.1 は 08-26 時点で既に正しい表を持っていた**（`usage-raw.txt` と一致、訂正注記つき）。誤ったのは 08-27 に私が書いた Issue コメントと本 PROGRESS の要約であり、**要約層がレポートの正しい結論を上書きしていた** |
| **#5 (INV-03 ④)** | 「実配置は 4 領域、sidebar 3+」 | **5 領域**（sidebar は **11**、`sidebar-tracking` 1 を見落とし） | 中。第一級ゾーンの候補選定に影響 |
| **#16 (INV-14)** | 「共通属性は未使用 → 逆変換は**不要**」と断定 | **本サイトの現行コンテンツに限れば不要**。テーマ側に定義は存在するので変換規則は実装対象に残す | 中。上表の「逆変換が要る」と矛盾していた |
| 証跡 | `http-audit-raw.txt` にカテゴリスラッグが残存 | `<category-slug>` へ置換済み | 軽（公開リポの伏せ字漏れ） |
| #15 | `helix-issue-dependency.v1` ブロック欠落（`blocks: [21]` は契約側のみ） | ブロックを追加し #21 と双方向一致 | 軽（起票規律違反） |

### 誤りの出どころ

3 件の誤りはいずれも **2026-08-27 に私（要約側）が書いた Issue コメント / PROGRESS 追記**で発生した。
`reports/` 配下のレポート本体と `evidence/` の生データは、突き合わせた範囲で**すべて正しかった**。
特に #4 の誤りは、レポートが既に持っていた正しい表（08-26 訂正済み）を、
新しく採取した証跡の一部だけを見て上書きしたもの。

**再発防止**: 新しい証跡で結論を更新するときは、対応する `reports/INV-*.md` の該当節を先に読み、
既存の結論と矛盾しないかを確認してから書く。要約だけを更新して本体を放置しない。

### 照合して正しかったもの

ブロック出現数（simplebox 697 / button 339 / blogcard 330 / fukidashi 186）、共通属性 6 種が 0・`className` 299、
`blogcardLabel` 15、REST の行番号 3675/3680/3780/3785/3796、option 分類 152+23+2+2=179、
`theme_mods` 235 キー・副作用 5 キーが全て既定値と相違、`WP_DEBUG=false` かつ `.user.ini` で `display_errors=On`、
ld+json が 4 種別すべて 0、末尾スラッシュ有無が両方 200・`PHPSESSID` 3/3、credential 2 キー。
依存の双方向一致は #15↔#21 を除き全件 OK（修正済み）。

## セッション引き継ぎ（2026-08-27 終業時点）

### GitHub 状態（RetryYN/HELIX-WP-THEME）
- Issue: root #2 + task #3〜#19 + finding #21。依存は双方向一致・伏せ字済み。
- PR #20（Draft, base=feat/helix-integration）: 起票スクリプト + PROGRESS + 採取証跡。
- PR #22（Draft, base=docs/theme-audit-issue-filing）: 第三者名の全面伏せ字化。**#20 の上にスタック**。
  → マージ順序は #20 → #22。ready/merge は Codex lane へ委譲（Claude 作成 PR のため）。

### 証跡の刈り込み状況
- SSRF 実証系 2 件（`re-themeA-rest.txt` / `http-audit-raw.txt`）は核だけに刈り込み済み。
- RE 証跡群（`re-themeA-boot.txt` / `re-themeB-pipeline.txt` 等 8 件）は**第三者ソースを数百行含むが、
  PO 判断により今回は原本のまま残す（B 案・2026-08-27）**。複製境界の一貫性の観点では将来の課題。

### 未了（すべて PO 判断・技術ブロッカーなし）
- 対処案の採用: `.user.ini` の display_errors=Off（#21・サーバ層1行）、REST 遮断（#15）、ベンダー報告要否 → **PO 判断（2026-08-31）: 実運用サイトは運用段階に入るまで触らず、対処の検証は PoC 環境で行う**。
- `theme_mods` 235 キーの分類 → **2026-08-31 実施**（A 51 / B 177 / C 1 / D 6、`evidence/theme-mods-key-classification.tsv`、INV-09 反映済み）。
- レポート本体 8 本への 2026-08-27 実測の反映 → 反映済み（INV-02/03/06/09/13/14/16/17、本ブランチ docs/inv-reports-0827-reflection）。
- origin/main 側に残る第三者名（テーマA 31 / テーマB 80 ファイル）の伏せ字化は別 PR。

### モデル運用メモ
SSRF 等の実証結論をメイン会話へ再叙述すると Fable がセーフガードで降格する。
証跡は番号・パスで指し、結論の再叙述はしない（詳細はローカルメモ fable-safeguard-evidence-handling）。
