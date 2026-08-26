# THEME-INV-11 レポート — スコープ境界（課金・会員）の PO 上申材料

- 対象イシュー: `issues/THEME-INV-11-scope-boundary.md`
- 状態: **③（上申材料の作成）完了 / ①②（実運用有無の確定）は未了**
- 調査日: 2026-08-26
- 手段: XServer SSH 読み取り専用
- 一次証跡: `evidence/probe3-raw.txt`（`paidpost` 登録・セッション開始）・
  `evidence/theme-structure-raw.txt`（vendor/stripe のファイル数）・
  `evidence/usage-raw.txt`（実使用回数）・`evidence/probe4-raw.txt`（テーマB CPT 定義）

## 1. 課金・会員機能の実装状況

### 1.1 テーマA — 決済がテーマに内蔵されている

| 要素 | 実測 | 証跡 |
|---|---|---|
| Stripe SDK | `vendor/stripe/` **286 ファイル**（テーマ全 679 ファイルの 42%） | `evidence/theme-structure-raw.txt` |
| ブロック | `themeA-blocks/paidpost`（**動的**・`themeA_paidpost_dynamic_render_callback`） | `evidence/probe3-raw.txt` |
| 本文フィルタ | `themeA_paid_content_display_switch`（優先度 **9**） | `evidence/re-themeA-render.txt` |
| セッション | `template_redirect` で `session_start()` + `session_regenerate_id()` | `evidence/probe3-raw.txt` L287-294 |
| post meta | `_themeA_paidpost` / `_themeA_paidpost_product_id` | `evidence/re-themeA-accessors.txt` 系 |
| option | `themeA_paidpost_secret_key`（5 参照）/ `themeA_paidpost_subscription_check`（4 参照） | `evidence/theme-features-raw.txt` |
| 表示部品 | `object/paidpost-popup.php`（186 行） | `evidence/probe5-raw.txt` |
| JS | `lib/js/paidpost.js` | 同上 |
| テーマソース内の文字列出現 | 16 回 | `evidence/probe2-raw.txt` |
| **公開記事の本文中の実使用** | **0 回** | `evidence/usage-raw.txt` |

**訂正**: 当初「本文中の参照 16 回」と書いたが、これは誤り。
16 は `evidence/probe2-raw.txt` の**テーマディレクトリ内の文字列出現数**であって、
記事本文の使用回数ではない。`evidence/usage-raw.txt`（公開記事 59 + 固定 10 の
`post_content` 集計）に `themeA-blocks/paidpost` は**現れない = 実使用 0**。

**この訂正はスコープ判断を大きく動かす。** 有料記事ブロックは
topic-A の公開記事で 1 度も使われていない。

> 補足: `evidence/usage-raw.txt` に `themeA-blocks/profile` が 1 回現れるが、
> このブロック名は `create_block_themeA_blocks_block_init()` の登録一覧（25 種）に**存在しない**。
> 旧バージョンで廃止されたブロックが本文に残っている可能性がある（INV-14 で確認）。

### 1.2 テーマB — 会員限定表示のみ・決済なし

| 要素 | 実測 | 実使用 |
|---|---|---|
| `themeB/restricted-area`（動的ブロック） | 登録あり | **0** |
| `[only_login]` / `[only_logout]`（ショートコード） | 登録あり | **0** |
| 決済 | **なし** | — |

証跡: `evidence/re-themeB-blocks.txt`（dynamic blocks 一覧）・`evidence/usage-raw.txt`（実使用 0）。

### 1.3 AGENT NEO — いずれも無し

課金・会員に相当する機構を持たない。`agent-neo-core` の 34 コントローラにも該当なし
（`license` は**テーマ自体のライセンス認証**であって、閲覧者の課金ではない）。

## 2. PO 上申 — スコープに入れるか

### 2.1 スコープ外にした場合に失われるもの

| サイト | 失われる機能 | 影響 |
|---|---|---|
| site-B.example（テーマB） | 会員限定表示 | **実データ 0 のため実質ゼロ** |
| site-A.example（テーマA） | 有料記事（Stripe 決済 + 購読チェック） | **公開記事での実使用 0**（`evidence/usage-raw.txt`）。下書き・非公開記事や option 設定の有無は未確認だが、**公開面では使われていない** |

### 2.2 スコープに入れた場合のコスト

- **決済は最も規約・法務リスクが高い領域**（特定商取引法・返金・サブスクリプション管理）
- Stripe SDK の同梱は**テーマの責務としては重い**（テーマ全体の 42% がベンダーコード）
- 中間 JSON パイプラインとの相性が悪い — 有料記事は**閲覧者の状態で本文が変わる**ため、
  決定論レンダラの外（`reports/INV-02-dynamic-render-semantics.md` §4 で「載らない」と判定済み）
- Graphix NEO の主題は「Context Page 構造」であり、課金は主題と直交する

### 2.3 代替案

| 案 | 内容 | 評価 |
|---|---|---|
| **A. スコープ外 + プラグイン委譲** | 決済・会員はプラグイン（MemberPress / WP Simple Pay 等）に任せ、テーマは「制限領域」の表現だけ持つ | **推奨**。テーマの責務が明確。中間 JSON には「制限ノード」だけ置き、判定は表示時レイヤ |
| B. スコープ内・自前実装 | Graphix NEO が決済まで持つ | 主題から外れる。法務リスクを製品が背負う |
| C. 完全に切る | 有料記事を廃止する | topic-A が実運用していれば収益を失う |

### 2.4 本レポートの推奨

**案 A（スコープ外 + プラグイン委譲）**を推奨する。

**公開記事での実使用が 0 であることが確認できたため、推奨度は高い。**
両サイトとも公開面では課金・会員機能を使っておらず、スコープ外にしても失うものが無い。
残る確認は「下書き・非公開記事での使用」と「Stripe 側に既存の購読者がいないか」の 2 点で、
どちらも該当が無ければ**単に持ち込まなければよい**。

あわせて **INV-17（全ページセッション）と連動**する。有料記事を使っていないなら、
`session_start()` + `session_regenerate_id()` を全ページで走らせる理由がなくなり、
キャッシュとの衝突も解消できる。

## 3. 未了項目

- [ ] **① topic-A の有料記事の実運用確認**
      - 公開記事の `post_content` に `<!-- wp:themeA-blocks/paidpost` が実在するか
      - `_themeA_paidpost` post meta が入っている記事の件数
      - `themeA_paidpost_secret_key` option に値が入っているか（**credential のため値は出力せず有無だけ判定**）
- [ ] **② テーマB の会員限定表示の実使用確認** — 実使用 0 は確認済み。追加確認は不要
- [ ] 既存購読者の有無（Stripe 側のデータ。PO しか確認できない）

> ①の判定は読み取りのみで可能だが、`themeA_paidpost_secret_key` は **credential にあたる**。
> 値は取得せず `-n`（空でない）判定のみを行う。統合層 CLAUDE.md 規律 2 に従い、
> 値をログ・リポジトリへ残さない。

## 4. 証跡ファイル

| 内容 | 場所 |
|---|---|
| `paidpost` ブロック登録・セッション開始 | `evidence/probe3-raw.txt` |
| vendor/stripe のファイル数・テーマ構成 | `evidence/theme-structure-raw.txt` |
| `paidpost` 系 option の参照回数 | `evidence/theme-features-raw.txt` |
| 本文中の実使用回数 | `evidence/usage-raw.txt` |
| テーマB の動的ブロック一覧（`restricted-area`） | `evidence/re-themeB-blocks.txt` |
| `object/paidpost-popup.php` / `lib/js/paidpost.js` の存在 | `evidence/probe5-raw.txt` |
