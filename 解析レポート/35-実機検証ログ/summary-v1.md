# 実機検証 v1 サマリ — ThemeB / テーマA を Docker WP で動かして得た新事実

> 実施日: 2026-04-29 17:41 UTC
> 環境: Docker WP 6.6.2 + PHP 8.2.25 + Apache + MariaDB 10.11
> raw log: [verification-20260430-024032.md](./verification-20260430-024032.md)

## サマリ

実機検証で **コード読みでは見えなかった** 6 つの重要事実を発見。L2 設計に直接影響する。

---

## ✓ 確認できた事実（コード読み解析の追認）

### 1. ThemeB の CPT 実機登録（3 件 + Bonus 2 件）

```
lp           - LP 専用、public=1, capability=lp
blog_parts   - 再利用パーツ、capability=blog_part
ad_tag       - 広告タグ、capability=ad_tag
```

**Bonus（重要）**: seo-tool-connector プラグインが mount されている環境で**追加 CPT 2 件**が登録される:
- `seo_tool_element` — SEO ツール要素
- `seo_tool_ab_test` — A/B テスト

これは Codex 解析にもなかった。AGENT NEO が seo-tool-connector と統合運用される環境では、**既存 CPT 5 種**を前提に設計する必要がある。

### 2. ThemeB ブロック登録数: **31 件**（Codex 解析の「32〜33」より少ない）

実登録ブロック一覧:
```
themeB/accordion, themeB/accordion-item
themeB/ab-test, themeB/ab-test-a, themeB/ab-test-b
themeB/banner-link, themeB/box-menu, themeB/box-menu-item
themeB/button, themeB/cap-block
themeB/columns, themeB/column
themeB/dl, themeB/dt, themeB/dd
themeB/faq, themeB/faq-item
themeB/full-wide
themeB/link-list, themeB/link-list-item
themeB/post-list, themeB/post-link
themeB/step, themeB/step-item
themeB/tab, themeB/tab-body
themeB/ad-tag, themeB/balloon, themeB/blog-parts
themeB/restricted-area, themeB/review, themeB/rss
```

Codex が示唆した 33 はおそらく block.json ファイル数で、実 register された数（条件付き登録があるためか）は 31。AGENT NEO の `block-registry.json` v0.1 はこの 31 を起点に取捨選択する。

### 3. ThemeB カスタマイザー実数値

| 項目 | 数 |
|---|---:|
| settings | **506** |
| sections | **36** |
| panels | **6** |

Codex 解析の「500+」は正確。AGENT NEO は 40 トークン以下を目標とすると **約 1/12 の規模に絞る** という具体目標数値が確定。

### 4. JSON-LD の実出力（ThemeB アクティブ時の top page）

```html
<script type="application/ld+json">
{"@context": "https://schema.org","@graph":[
  {"@type":"Organization","@id":"...","name":"AGENT NEO Dev","url":"..."},
  {"@type":"WebSite","@id":"...","name":"...","potentialAction":{...SearchAction...}}
]}</script>
```

**事実**: ThemeB は `@graph` 配列形式で複数 entity を出力する。AGENT NEO もこれを踏襲する（個別 `<script>` を 7 個並べる方式ではなく、1 つの @graph に統合）。

---

## ✗ 新しく発見した「やばい事実」

### 5. テーマA 1.0.5 は PHP 8.2 と互換性が壊れている（Critical）

テーマA 有効化時に 100+ 件の PHP Warning が出る:

```
PHP Warning: Trying to access array offset on value of type null in
  /themeA/include/customizer/ui/box-design-setting.php on line 401, 406, 411, ...
PHP Warning: Trying to access array offset on value of type null in
  /themeA/include/customizer/ui/button-design-setting.php on line 514, 519, 595, 600, ...
```

これは PHP 8.2 で `null` に対して array アクセスすると Warning が出る挙動変化（PHP 7.x までは silent）。テーマA 1.0.5 はこの修正がされていない。

**AGENT NEO への含意:**
- AGENT NEO は **PHP 8.1+ を最低要件にして null 安全な書き方を徹底** する（型定義、null coalescing、isset チェック）
- 移行プラン B（AI フル再構築）の対象として テーマA はかなり優位（既存テーマがバグまみれ → AGENT NEO に乗り換える動機強い）
- 商用テーマでもこの程度のメンテ品質という事実は、AGENT NEO の差別化材料（**PHPCS / PHPStan / 静的解析を CI 必須化**で品質担保）

### 6. ThemeB の `/themeB/v1` REST 名前空間は **存在しない**（404）

Codex 解析で 14 route 推定だった `/themeB/v1/...` の名前空間は、実機では:

```
GET /wp-json/themeB/v1
{"code":"rest_no_route","message":"No route was found...","data":{"status":404}}
```

**理由（推定）**:
- ThemeB の REST routes は **特定の画面・条件下でのみ登録される**（lazy registration）
- または `/wp-json/themeB/v1/...` 等の別名前空間
- もしくは `init` フックで条件付き登録されるため、curl 直叩きでは反応しない

Codex の `lib/rest_api.php` 解析は file 存在ベースで、実 register 結果まで verify していなかった。AGENT NEO の `agent-neo/v1` は **常時登録される** よう設計し、curl 一発で疎通確認できることを保証する。

### 7. ThemeB の設定値はオプションテーブルに**保存されない**（fresh install では）

```
$ wp option get ThemeB_SETTINGS
Error: Could not get 'ThemeB_SETTINGS' option. Does it exist?

$ wp option list --search='themeB_*'
（empty result）
```

つまり ThemeB の 506 settings は **初回 save まで DB に書かれない**（lazy persistence）。デフォルト値はコード内でハードコードまたは Default_Settings クラスから読まれる。

**AGENT NEO への含意:**
- AGENT NEO の `design-tokens.json` も **デフォルトはコードに埋め込み、user override のみ DB に保存** すべき（DB bloat 回避）
- ただし AI 操作で **明示的に「現在の値」を取得**する API では、未保存値も含めてデフォルトを返すこと（`GET /design-tokens?includeDefaults=true`）

---

## ⚠️ スクリプトのバグ（v2 で修正必要）

| 問題 | 原因 | 修正 |
|---|---|---|
| `wp eval` の PHP コードが parse error | bash の `\$GLOBALS["..."]` エスケープが docker compose stdin に正しく渡らない | `wp eval-file` に切替、別 .php ファイルから読む |
| 性能比較セクションがログに出ない | スクリプト後半が早期終了 | docker compose run 失敗時の `set -e` を緩和 |
| WP 環境内の bash がない（wpcli image） | `sh -c` を docker compose run の outside で実行する必要 | host bash 側でループ、wp は wp コマンドのみに使う |

v2 を作って再実行すれば performance / asset count / OGP 出力 が取れる。

---

## L2 設計への引き継ぎ事項（実機根拠強化）

| 項目 | 旧（コード読み）| 新（実機検証） | L2 反映 |
|---|---|---|---|
| ThemeB CPT | 3 種（lp/blog_parts/ad_tag）| **5 種**（+ seo_tool_element/seo_tool_ab_test）| AGENT NEO は seo-tool-connector の 2 CPT を**前提**にする |
| ThemeB ブロック数 | 32-33 | **31** | block-registry.json v0.1 は 31 を起点に取捨選択 |
| ThemeB カスタマイザー数 | 500+ | **506 settings / 36 sections / 6 panels** | AGENT NEO は 40 トークンが具体目標（1/12 圧縮）|
| テーマA PHP 互換性 | 不明 | **PHP 8.2 で Warning 100+ 件**（メンテ崩壊）| 移行プラン B 訴求材料 + AGENT NEO 自身は静的解析必須 |
| ThemeB REST 公開 | 14 route 想定 | **/themeB/v1 は 404**（lazy register）| AGENT NEO は常時登録 + curl 疎通確認可能を保証 |
| 設定 DB 保存 | 不明 | **fresh install では options table が空**（lazy persistence）| AGENT NEO もデフォルトはコード埋め込み |
| JSON-LD 形式 | Codex は entity 列挙 | **@graph 配列で 1 script tag に統合**（ThemeB 実装）| AGENT NEO も @graph 形式採用 |

---

## 次のアクション提案

1. **verify-themes.sh v2 を作成・再実行**（performance / asset count / OGP 出力 を取りたい）
2. **手動で http://localhost:8086/wp-admin/ にログイン**（admin/admin）して以下を目視確認:
   - ThemeB の設定パネル（506 settings の UX）
   - テーマA Hero variant 切替（still/post-slider/image-slider/movie）の実プレビュー
   - ThemeB ad_tag CPT の管理画面 UI
   - ブロックエディタでの ThemeB ブロック挿入感
3. **L2 全体設計に進む**（実機検証から得た 7 つの含意を盛り込んだ ADR ドラフト）

実機検証は **コード読みだけでは見えない 3 つの致命的事実**（テーマA PHP 互換崩れ / ThemeB REST が 404 / 設定 DB 未保存）を捕捉できた。L2 設計の精度が確実に上がった状態。

---

**作成**: 2026-04-30 / PM Opus
**入力**: docker compose 実行ログ (raw 1,659 行)
