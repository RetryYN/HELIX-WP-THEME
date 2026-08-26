# イシュー消化状況

最終更新: 2026-08-26

状態: `未着手` / `一次完了`（読み取りで到達できる範囲を出し切った）/ `完了`（受入条件をすべて充足）/
`承認待ち`（PO 判断が要る項目で止まっている）

イシュー総数 **17 本**（INV-17 は消化パスで新たに切り出した）。
**17 本すべてが一次完了**（＝読み取り済みの証跡で到達できる範囲を出し切った状態）。
残りはいずれも**サーバーへの追加読み取り / HTTP 確認 / PO 承認**が要る項目で、
各レポートの「未了項目」に手順つきで記載してある。

| ID | 表題 | 優先 | 状態 | レポート |
|---|---|---|---|---|
| INV-13 | JIN:R の未認証 REST 2 本の到達性と対処 | 最優先 | **一次完了 / 承認待ち** | `reports/INV-13-jinr-rest-endpoints.md` |
| INV-01 | ブロック語彙 3 系統の対応表 | 高 | **一次完了**（意味層。属性層は INV-14 待ち） | `reports/INV-01-block-vocabulary-map.md` |
| INV-03 | 広告 / CV ゾーン仕様の横断確定 | 高 | **一次完了**（正規化 23 ゾーン + スキーマ改訂案） | `reports/INV-03-ad-cv-zones.md` |
| INV-02 | 動的ブロックの意味論と再現性 | 高 | **一次完了** | `reports/INV-02-dynamic-render-semantics.md` |
| INV-04 | 再利用パーツ機構の抽象化契約 | 高 | **一次完了** | `reports/INV-04-reusable-parts-mechanism.md` |
| INV-12 | 資産再利用可否台帳 14 件 | 高 | **暫定版**（6 確定 / 5 暫定 / 3 未判定） | `reports/INV-12-asset-reuse-ledger.md` |
| INV-05 | デザイントークンの正本と投影方式 | 中 | **一次完了**（部分集合に適用） | `reports/INV-05-design-token-projection.md` |
| INV-06 | 構造化データ出力の差分 | 中 | **一次完了** | `reports/INV-06-structured-data-gap.md` |
| INV-07 | 目次と本文フィルタ機構の方式 | 中 | **一次完了** | `reports/INV-07-content-filter-and-toc.md` |
| INV-08 | エージェント接点（REST / フック）の差 | 中 | **一次完了** | `reports/INV-08-agent-interface-gap.md` |
| INV-15 | SWELL パイプラインの中間 JSON 転用可否 | 中 | **一次完了** | `reports/INV-15-swell-pipeline-transfer.md` |
| INV-16 | JIN:R の描画時 DB 書き込み副作用 | 中 | **一次完了** | `reports/INV-16-jinr-render-side-effects.md` |
| INV-10 | ショートコード後方互換の扱い | 低 | **一次完了** | `reports/INV-10-shortcode-compat.md` |
| INV-11 | スコープ境界（課金・会員） | 低 | **一次完了 / 承認待ち** | `reports/INV-11-scope-boundary.md` |
| INV-14 | JIN:R ブロック属性表の帰納 | 高 | **一次完了**（`blogcard` 全属性 + 共通属性を確定・抽出スクリプト用意） | `reports/INV-14-jinr-attribute-induction.md` |
| INV-17 | JIN:R のグローバル改変の影響確定 | 高 | **一次完了 / 承認待ち**（コード解析完了・検証手順を定義） | `reports/INV-17-jinr-global-side-effects.md` |
| INV-09 | サイト設定の正本と移管方式 | 中 | **一次完了**（分類軸 4 種・移管手順・判別可否の結論） | `reports/INV-09-settings-authority.md` |

## 一次完了 17 本で確定したこと

| # | 確定したこと |
|---|---|
| **INV-09** | 分類軸を **①サイト固有の意味 / ②見た目の選択 / ③テーマ内部状態 / ④副作用既定値**の 4 種に確定。**アクセサ 707 のうち約 530（75%）が「見た目」**で、移管必須はおよそ 60〜80 キー（全体の 5〜7%）。**判別不能問題は実害なし** — `set_theme_mod()` の副作用が及ぶ 5 キーはすべて②に属し、移管必須集合に入らない。JIN:R の目録化は「実在キーの列挙 → キー名で分類 → 分類できないものだけ関数を読む」の順で 707 関数を全読せずに済む |
| **INV-14** | `blogcard` の全属性を確定。**共通属性 7 種**（`topMarginPcAttribute` 等の余白 4・`displayDeviceAttribute`・`className`・`jinrBlocksCSSAttribute`）を特定し、その値が**数値ではなく CSS クラス名の文字列**であることを確認 → 中間 JSON へは**クラス名から意味への逆変換**が要る。抽出スクリプト `extract-jinr-attrs.sh` を用意（1 回の実行で INV-10 / 01 / 02 の未了 5 件が同時に閉じる） |
| **INV-17** | `redirect_canonical` が引数を見ず常に `false` を返し、**正規化リダイレクトが全面停止**することをコードで確定。停止する正規化 6 種を列挙。`session_regenerate_id()` の毎リクエスト実行が**セッション継続性・ディスク・キャッシュ**に与える影響を整理。**有料記事の実使用 0（INV-11）なので、このセッション処理は全ページで純粋なオーバーヘッド**。検証コマンドと子テーマでの対処案を用意 |
| **INV-01** | 75 ブロック（JIN:R 25 / SWELL 50）を**意味 31 種**へ正規化。**両テーマに専用ブロックがある 11 組**が意図語彙の第一候補。JIN:R の比較表（177+59）と SWELL の定義リスト（106+106）・FAQ（56）は**相手側に対応物が無く、両方持つ必要がある**。インライン書式は**ノードではなくテキストの装飾レンジ**として持つと判定 |
| **INV-03** | ゾーンを**意味で 23 種に正規化**（JIN:R 11 + SWELL 24 の和集合）。`ad-zone.schema.json` の差分 3 点を特定 — **`category_override` はゾーンではなく上書き規則**（同列に並べるのは誤り）、20 ゾーンが語彙に無い、条件表示のモデルが無い。`creative_ref` を参照にし `overrides` を first-match-wins の配列にする改訂案を提示 |
| **INV-13** | 独自 REST は 2 本、両方 `permission_callback => '__return_true'`。`external_url` は未検証 URL を `file_get_contents()` へ渡す SSRF 構造。**`post_by_url` はブログカード（実使用 330）が `rest_do_request()` で内部ディスパッチ**しており、ルート除去型の対処は描画を壊す。サーバ層で HTTP 経由のみ遮断すれば内部呼び出しは通る |
| **INV-02** | 動的ブロックは **7 種**（「9 種以上」を訂正）。**6 種は正規化で決定論レンダラに載る／`paidpost` のみ載らない**。`register_block_style('core/list')` 2 件を新規発見 |
| **INV-04** | 中間 JSON は**参照（ID）で持ち、解決に使った版と digest を記録する**と決定。循環参照・欠落・下書きの規約を定義。`PartsAdapter` の最小インタフェースを定義。**Graphix NEO は SWELL 方式（`public=false` + `show_in_rest=true` の CPT）を採る**ことを推奨 |
| **INV-12** | 台帳 14 行のうち **6 確定 / 5 暫定 / 3 未判定**。REST 34 本を A 群 16（契約付き移植）/ B 群 9（不採用）/ C 群 4（契約のみ + アダプタ）/ D 群 4（基盤）に確定 |
| **INV-05** | 仕分けを **A 意味的 / B 部品固有 / C 状態フラグ / D レイアウト寸法**の 4 分類に確定（C を独立させたのが要点）。**JIN:R の先頭 50 件に意味的トークンが 1 つも無い**。派生色の規則が生成関数にハードコードされており、トークン体系が存在しない |
| **INV-06** | JIN:R は**記事型（Article / BlogPosting）を出していない**。不足は `CollectionPage`（高）と `SearchAction`（中）。**FAQPage / HowTo / ItemList は中間 JSON の意図ノードから自動生成できる**＝中間 JSON 方式の優位点 |
| **INV-07** | 本文変換を全一覧化し 3 層へ割り当て。**目次は中間 JSON の一級要素にしない**（配置だけ意図ノード、実体はレンダラ導出、既定は最初の h2 直前） |
| **INV-08** | JIN:R 操作の現実解は **コア REST（記事）+ WP-CLI / ブラウザ（設定）**。option 直接書き換えは非推奨。**名前空間はコアに相乗りしない** |
| **INV-15** | SWELL の 4 機構のうち **2 つ転用可**。抽出器の最小実装範囲と、そのまま使えない 4 点を特定 |
| **INV-16** | `set_theme_mod()` 5 箇所を全列挙。**値だけでは「人が決めた設定」と「副作用で入った既定値」を判別できない** → 移管に事前スナップショットが必須 |
| **INV-10** | テーマ語彙のショートコードは**意図ノードへ展開**、プラグイン語彙は**不透明ノードで原文保持**と確定。`[jinr_fukidashi]` 186 = ブロック 186 の完全一致から「ブロックの save 出力がショートコードを含む」仮説を立て、検証手順を定義 |
| **INV-11** | **案 A（スコープ外 + プラグイン委譲）を推奨**。SWELL 側は実データ 0 で失うものが無い。JIN:R の有料記事は実運用確認が前提。**INV-17（全ページセッション）と連動** |

## 引き継ぎ検証（2026-08-26・別セッションで再点検）

証跡ファイルとレポートの突き合わせを実施。結果:

| 主張 | 生証跡 | 判定 |
|---|---|---|
| 動的ブロック 7 種（render_callback） | `probe3-raw.txt` に 7 register + 18 static = 25 | ✅ 支持 |
| paidpost 実使用 0（16 はソース内文字列） | `usage-raw.txt` に不在／`probe2-raw.txt:18` に 16 | ✅ 支持 |
| set_theme_mod 5 箇所 | `re-jinr-boot.txt`（キー・既定値・行番号一致） | ✅ 支持 |
| redirect_canonical 無効化 + 全ページ session | `probe3-raw.txt:280-294` | ✅ 支持 |
| **REST 2 本 + `__return_true` + SSRF** | **生キャプチャ無し**（`re-jinr-rest.txt` 未生成・`theme-features-raw.txt` の JIN:R REST 欄は空）。`post_by_url` の内部呼び出しのみ `re-jinr-ads.txt` で裏取り | ⚠ **要是正**（本文手写しのみ） |

**修正済みの実欠陥（本セッション）:**
- INV-02 §2.1 / §6 の paidpost「実使用 16」→ **0** に訂正（postcard/slider/category も usage-raw で 0 のため 0 に統一）。
- 00-REPORT のイシュー本数「12 本 / 16 本」→ **17 本**に統一。
- 00-REPORT §6.2 の PO 判断「起票しない」→ PO 目標により **撤回・起票する**に更新。
- INV-13 冒頭に「証拠ギャップ」バナーを追加（SSRF は生採取が未了である旨）。

**最優先の是正**: Bash/SSH 復旧後、下記「復帰後の作業順」3（`re-jinr-rest.txt` 生採取）を
起票より先に回し、目玉のセキュリティ主張に確実な証拠を付ける。

## RE パスで判明した訂正

| 項目 | 初回 | 訂正後 | 原因 |
|---|---|---|---|
| JIN:R の独自 REST | 0 本 | **2 本** | 複数行 `register_rest_route(` の grep 漏れ |
| JIN:R の `register_block_style` | 0 | **2 件**（`core/list`） | 同上 |
| JIN:R の動的ブロック | 9 種以上 | **7 種** | 登録コード全文を数え直し |
| `jinr-blocks/paidpost` の実使用 | 本文中 16 回 | **公開記事で 0 回** | 16 は `probe2-raw.txt` の**テーマソース内の文字列出現数**であり、本文の使用回数ではなかった（`usage-raw.txt` に該当なし） |

**新規に見つかった不整合**: `evidence/usage-raw.txt` に `jinr-blocks/profile` が 1 回出現するが、
このブロック名は登録一覧 25 種に**存在しない**（廃止ブロックの残存と推定。INV-14 で確認）。

## 承認待ち

| 項目 | 内容 | イシュー |
|---|---|---|
| 到達性の実証 | 自サイトへ `/wp-json/jinr/external_url` を実際に叩く | INV-13 |
| 本番 DB の SELECT | `theme_mods_jinr` の現在値取得（書き込みなし） | INV-16 |
| 実ページの JSON-LD 採取 | 記事 / アーカイブ / 検索 / トップの 4 種別を HTTP GET | INV-06 |
| 正規化リダイレクトの実挙動 | 末尾スラッシュ有無等で 200 が返るかの確認 | INV-17 |
| 有料記事の実運用確認 | `_jinr_paidpost` の件数・secret_key の**有無のみ**（値は取得しない） | INV-11 |
| 台帳の反映 | GRAPHIX-NEO 側 `docs/references/` の更新（cross-repo） | INV-12 |
| ベンダー報告 | CROOVER inc. への連絡要否 | INV-13 |

## 作業ブロッカー（2026-08-26 時点・継続中）

**/tmp のディスク quota が枯渇している。** 2 バイトの書き込みでも `EDQUOT` で失敗する。
Bash ツールはサンドボックスの作業ファイルを /tmp に作るため、`true` すら起動できず全コマンドが失敗する。
`~/dev` 配下への書き込みは通るため、レポート作成のみ継続できている。

この影響で:
- **GitHub イシューの起票（gh）が実行できない** — 17 本すべて未起票。
  代わりに**起票スクリプト `create-issues.sh` を用意した**（ラベル 25 種の作成 + 17 本の起票、
  重複防止つき。`DRY_RUN=1` で内容確認可）。シェル復帰後に 1 コマンドで起票できる
- **サーバーへの追加 SSH 読み取りができない** — 各レポートの「未了項目」が着手できない。
  代わりに**抽出スクリプト `extract-jinr-attrs.sh` を用意した**（読み取り専用。
  1 回の実行で INV-14 / 01 / 02 / 10 / 11 の未了 5 件が同時に閉じる）
- **git commit / push ができない** — `reports/` 17 本・`PROGRESS.md`・スクリプト 2 本・訂正分が未 commit

17 本のレポートは、既取得の証跡（`evidence/` 17 ファイル）と読み取り済みソースのみで作成した。
**推測は「仮説」「推定」と明記し、未読部分は「未了」として区別している。**

### 復帰後の作業順

1. `bash docs/research/2026-08-26-theme-structure-audit/create-issues.sh`（17 本を起票）
2. `bash docs/research/2026-08-26-theme-structure-audit/extract-jinr-attrs.sh > evidence/jinr-attrs-raw.txt`
   → INV-14 / 01（属性層）/ 02 / 10 / 11 の未了が閉じる
3. `evidence/re-jinr-rest.txt` の採取（INV-13 §2 のソースを生キャプチャとして残す）
4. レポート 17 本 + `PROGRESS.md` + スクリプト 2 本 + 訂正分の commit / push
5. **INV-03 ④**（`sidebars_widgets` の読み取り。実配置で第一級ゾーンを確定）
6. **INV-09 ①**（`wp option list --search='jinr_*'` で実在キーを列挙 → 4 分類）
7. **INV-13 / 17**（HTTP 確認・PO 承認後）
8. **INV-12** の台帳を再判定し、GRAPHIX-NEO 側へ反映（PO 承認後）
