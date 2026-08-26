# THEME-INV-08 レポート — エージェント接点（REST / フック）の差

- 対象イシュー: `issues/THEME-INV-08-agent-interface-gap.md`
- 状態: **②③④ 完了 / ①（テーマB 14 ルートの入出力契約）は route 名まで。本体の精読は未了**
- 調査日: 2026-08-26
- 手段: XServer SSH 読み取り専用
- 一次証跡: `evidence/theme-features-raw.txt`（両テーマの `register_rest_route` 抽出・filter 本数）・
  `evidence/re-themeB-detail.txt`・`evidence/re-themeA-ads.txt`・`reports/INV-13-themeA-rest-endpoints.md`

## 1. 全体像

| | テーマA | テーマB | AGENT NEO |
|---|---|---|---|
| 独自 REST | 2（`themeA/*`） | 14（**`wp/v2` に相乗り**） | 34 コントローラ（`agent-neo/v1`） |
| 用途 | ブロック描画の内部データ取得 | 管理系（設定・キャッシュ・ふきだし CRUD・計測データ） | 操作 API |
| 認証 | **`__return_true`（なし）** | 未確認（要精読） | `class-auth.php` あり |
| 自前 `apply_filters` | **1** | **79** | プラグイン側で提供 |
| 自前 `do_action` | 3 | 5 | — |
| pluggable 関数 | **なし**（`function_exists` ガードも無い） | `lib/pluggable.php` / `pluggable_parts.php` | — |

## 2. ① テーマB の 14 ルート（route 名と推定用途）

証跡（`evidence/theme-features-raw.txt` の抽出結果そのまま）:

```
register_rest_route( 'wp/v2', '/themeB-block-settings'
register_rest_route( 'wp/v2', '/themeB-ct-ad-data'
register_rest_route( 'wp/v2', '/themeB-ct-btn-data'
register_rest_route( 'wp/v2', '/themeB-ct-pv'
register_rest_route( 'wp/v2', '/themeB-do-update-action'
register_rest_route( 'wp/v2', '/themeB-lazyload-contents'
register_rest_route( 'wp/v2', '/themeB-reset-ad-data'
register_rest_route( 'wp/v2', '/themeB-reset-cache'
register_rest_route( 'wp/v2', '/themeB-reset-settings'
register_rest_route( 'wp/v2', '/themeB-term-list'
register_rest_route('wp/v2', '/themeB-balloon'
register_rest_route('wp/v2', '/themeB-balloon-copy'
register_rest_route('wp/v2', '/themeB-balloon-recover'
register_rest_route('wp/v2', '/themeB-balloon-sort'
```

| ルート | 推定用途 | 機械操作での価値 |
|---|---|---|
| `themeB-block-settings` | ブロック共通設定の取得（エディタ用） | **中** — ブロックの既定値を知れる |
| `themeB-term-list` | タームの一覧取得 | 低（コア REST で代替可） |
| `themeB-balloon` / `-copy` / `-recover` / `-sort` | ふきだしの CRUD・並べ替え | **中** — 独自テーブル `themeB_balloon` への唯一の API 経路 |
| `themeB-ct-ad-data` / `-btn-data` / `-pv` | 広告・ボタンのクリック計測、PV の記録 | **高** — 収益計測データの取得口 |
| `themeB-reset-ad-data` / `-cache` / `-settings` | リセット系（**破壊的**） | 触らない |
| `themeB-do-update-action` | 更新時アクションの実行 | 触らない |
| `themeB-lazyload-contents` | コンテンツ遅延読み込み | 低 |

**注意点**: `-reset-*` と `-do-update-action` は破壊的操作。機械操作の設計では
**明示的な禁止リスト**に入れる（誤爆時の影響が大きい）。

**未了**: 各ルートの `methods` / `permission_callback` / 引数スキーマ / 応答形は未読。
`lib/rest_api/` 配下（1 ファイル）と `classes/` 側の実装を読む必要がある。

## 3. ② テーマA サイトを機械操作する経路

テーマA には操作用 API が存在しない（2 本の REST はブロック描画のための読み取り専用・
詳細は `reports/INV-13-themeA-rest-endpoints.md`）。現実的な経路は次の 4 つ。

| 経路 | 可否 | 何ができるか | リスク |
|---|---|---|---|
| **A. WP コア REST（`wp/v2/posts` 等）** | ○ | 記事・固定ページの CRUD、post meta（`_themeA_*` 27 種）の読み書き | 低。Application Password で認証。PoC-1/2/4 で成立を実証済み |
| **B. WP-CLI（SSH 経由）** | ○ | option / theme_mod / ウィジェット / タームの読み書き、DB クエリ | 中。サーバー到達が前提。実行はサーバー上 |
| **C. option の直接書き換え**（`wp option update` / DB） | △ | カスタマイザ 707 項目・広告設定・`themeA_*` 1,225 キー | **高**。スキーマが無く、値の妥当性を検証する手段が無い。誤値でサイトが壊れる |
| **D. 管理画面のブラウザ操作** | ○ | 上記すべて（人間の操作と同じ） | 中。有人ブラウザ車線。低速だが安全 |

**結論**: テーマA サイトの操作は **A（記事）+ B/D（設定）** の組み合わせになる。
C は「テーマ設定を機械が直接書く」ことになり、**スキーマ無しでの書き込みは推奨しない**。
どうしても必要なら、先に INV-09 で「どのキーが何を意味するか」の目録を作るのが前提条件。

**post meta 経由でできる記事単位の制御**（`evidence` より、`_themeA_*` 27 種）:
`_themeA_ads_display`（広告の記事単位オプトアウト）・`_themeA_thumbnail_display`・
`_themeA_relatedpost_display`・`_themeA_snsbutton_display`・`_themeA_sidebar1col_display` /
`_themeA_sidebar2col_display`・`_themeA_noindex_display`・`_themeA_canonical_display`・
`_themeA_seotitle_display`・`_themeA_description_display`・`_themeA_keyword_display`・
`_themeA_representations_display`（PR 表記）・`_themeA_profile_display`・`_themeA_title_display`・
`_themeA_hastag_display`・`_themeA_headtag_article`・`_themeA_ogp_image_url`・
`_themeA_url_youtube` ほか YouTube 系 5・`_themeA_paidpost` 系 2・`_themeA_category` 系 2。

→ **記事単位の表示制御は REST（A）で完結する**。これは機械操作にとって重要な足場。

## 4. ③ `wp/v2` 相乗りの是非と名前空間方針

**テーマB の相乗りが抱える問題**

1. **衝突リスク** — `wp/v2` はコアの名前空間。将来コアが同名ルートを追加した場合、
   後勝ち・先勝ちが登録順に依存する（`register_rest_route` は既存を上書きする）。
2. **権限モデルの混在** — コアの `wp/v2` は投稿・ユーザー等の権限体系を前提にしている。
   そこにテーマ固有の管理操作（`-reset-settings` 等）を混ぜると、
   「`wp/v2` を許可した」ことの意味が実装依存になる。
3. **API バージョニングができない** — 自前名前空間なら `themeB/v1` → `v2` と切れるが、
   `wp/v2` に足したルートは切りようがない。

**AGENT NEO 側の方針（本レポートの結論）**

- **自前名前空間を切る。** 現行の `agent-neo/v1` を維持する（`inc/rest/` に 34 コントローラ、
  コード内参照 54 箇所で確立済み）。
- **Graphix NEO でも自前名前空間を切る。** 名前は製品名に合わせる（例 `graphix/v1`）。
  `agent-neo/v1` を継承しない（GRAPHIX-NEO の参照境界文書が
  「agent-neo/v1 契約は新規に起こす」と明記している）。
- **コア名前空間へは足さない。** テーマ/プラグインのルートは必ず自前名前空間。
- **破壊的操作は別グループに分ける。** `-reset-*` 相当は
  同じ名前空間でも明示的に区別できるパス設計にする（例 `/admin/reset/*`）。

## 5. ④ AGENT NEO の 34 コントローラの仕分け

`plugins/agent-neo-core/inc/rest/` の 34 クラスを、
「実運用テーマ（テーマA / テーマB）に対しても意味を持つか」で仕分ける。

| 区分 | コントローラ | 判定理由 |
|---|---|---|
| **A. テーマ非依存で意味を持つ**（他テーマのサイトにも適用しうる） | `posts` / `pages` / `pages-read` / `media` / `blog-card` / `seo` / `tracking` / `tracking-export` / `ab-test` / `logs` / `health` / `status` / `jobs` / `risks` / `llmo-summary` / `automation-seo` | 記事・計測・SEO はテーマに依らない。WP コア REST の上に立つ層として機能する |
| **B. 自テーマ専用**（AGENT NEO の構造に依存） | `sections` / `sections-read` / `blocks` / `elements` / `design-tokens` / `blueprint` / `features` / `settings` / `migration` | セクション・パターン・トークンは AGENT NEO の構造が前提 |
| **C. 収益・広告系**（他テーマにも概念はあるが実装が違う） | `ad-zones` / `ad-tags` / `ctas` / `affiliate` | テーマA はウィジェット + option、テーマB は CPT `ad_tag`。**per-site アダプタが要る**（INV-03 / INV-04） |
| **D. 基盤** | `auth` / `license` / `public` / `actions` / `rest-controller-base` | — |

**含意**: 「ハーネスから 2 サイトを操作する」なら、必要なのは **A 群の抽象**であって
B 群ではない。C 群はテーマごとに実装が違うため、**契約（インタフェース）だけ共通化し
実装は per-site アダプタに落とす**のが現実的。

これは INV-12（資産再利用可否台帳）の「REST controller」項目の判定材料になる:
- A 群 → **契約付き移植**の候補
- B 群 → Graphix NEO の構造が決まってから起こす（**不採用**、新規に定義）
- C 群 → 契約のみ移植、実装はアダプタ

## 6. 未了項目

- [ ] テーマB 14 ルートの `methods` / `permission_callback` / 引数 / 応答の精読
- [ ] `themeB-ct-*`（計測データ）の応答形式 — 収益計測に使えるか
- [ ] AGENT NEO の `class-auth.php` の認証方式確認（Application Password / nonce / 独自）
- [ ] コア REST（A 経路）で `_themeA_*` post meta が読み書きできるかの実機確認
      （`register_meta` されているか。されていなければ `show_in_rest` が無く REST から見えない）

> 上記最後の項目は重要。`evidence` の grep では `register_meta` の呼び出しが確認できていない。
> **`_themeA_*` が REST に露出していない場合、記事単位の表示制御は WP-CLI か DB 経由になる。**
> §3 の「経路 A で完結する」という結論はこの確認待ちで暫定。

## 7. 証跡ファイル

| 内容 | 場所 |
|---|---|
| 両テーマの `register_rest_route` 抽出・filter/action 本数 | `evidence/theme-features-raw.txt` |
| テーマA の REST 2 本の全文と内部ディスパッチ | `reports/INV-13-themeA-rest-endpoints.md` / `evidence/re-themeA-ads.txt` |
| テーマB の `pluggable` / `rest_api` 配置 | `evidence/re-probe1.txt`（`functions.php` の require 一覧） |
| `_themeA_*` post meta 27 種 | `evidence/re-themeA-accessors.txt` 系の grep 結果 |
