# 機構比較 — 同じ問題を 3 テーマがどう解いているか

`10-reverse-themeA.md` / `11-reverse-themeB.md` と、本リポの `agent-neo-theme` + `agent-neo-core` の実装から。
構造の一覧（何を持っているか）は `04-diff-register.md`。本書は**どう動いているか**の比較。

## 0. agent-neo の機構（比較軸として）

```php
// inc/bootstrap.php
require class-config-loader / setup/class-boundary-guard / setup/class-theme-setup
      / class-related-query / assets/class-third-party-manager
      / seo/class-head-meta / seo/class-structured-data / seo/class-oembed-lazy
      / class-agent-neo-theme;
$agent_neo_theme = new Agent_Neo_Theme();
$agent_neo_theme->register();
function agent_neo_health(): array { return $agent_neo_theme->health(); }
```

- `final class Agent_Neo_Theme` が **起動 step の記録（`$steps`）と読み込み済み module（`$loaded_modules`）を
  自己申告する**。`agent_neo_health()` で外から起動状態を検査できる。
- `Agent_Neo_Config_Loader` が `config/*.json` を読み、**最小 schema を fail-fast 検証**する。
  設定が壊れていれば起動時に落とす。
- `Agent_Neo_Boundary_Guard` が境界（REQ-NF-025: AI ロジック分離）を守る番人として常駐。
- 型宣言（`private array $steps`, `: array`）を使う PHP 8 系のコード。

→ **3 者の中で唯一「自分の状態を機械可読に説明する」テーマ。** ただし機能面積は最小。

---

## 1. 起動と依存解決

| | テーマA | テーマB | agent-neo |
|---|---|---|---|
| 構成単位 | グローバル関数のみ | クラス + trait + 名前空間 | final クラス + module |
| 読み込み | `require` / `get_template_part` の直列 | オートローダ + 順序付き require 24 本 | 明示 require + `register()` |
| 依存の保証 | **行順のみ**（暗黙） | 設定確定 → 各モジュール（明示） | config 検証 → module 登録 |
| 条件分岐 | ほぼ無し | 管理者 / 管理画面で読み込み範囲を絞る | — |
| 自己診断 | 無し | 無し（`check_environment.php` は環境要件のみ） | **`health()` で step と module を返す** |

**含意**: テーマA は「どのファイルが何に依存しているか」を機械的に知る方法が無い。
移植の第一歩（依存グラフの把握）に静的解析が効かない。

## 2. 設定の正本と読み出し

| | テーマA | テーマB | agent-neo |
|---|---|---|---|
| 格納 | `themeA_*` 個別 option 1,225 + theme_mod | `themeB_options` 等 **配列 4 グループ** + 独自テーブル `themeB_balloon` | `theme.json` + `config/*.json` 7 本 |
| 既定値 | **各アクセサ関数に散在** | `Data/Default_Settings.php` 1 ファイル（540 キー） | JSON に宣言 |
| 読み出し | 手書きアクセサ **707 関数** | `self::$setting` 等の静的プロパティ（起動時に確定） | Config_Loader（検証付き） |
| 列挙可能性 | **不可**（総体を機械的に取れない） | **可**（既定値ファイルが目録） | **可**（JSON） |
| 検証 | 無し | 無し（既定値とのマージのみ） | **fail-fast schema 検証** |

**含意（THEME-INV-09 に直結）**: 「サイト設定の移管」は
テーマB 側は既定値ファイルとの差分を取れば機械的に抽出できるが、
**テーマA 側は 707 のアクセサを 1 つずつ読むしか目録を作る方法が無い**。

## 3. 動的 CSS の生成

| | テーマA | テーマB |
|---|---|---|
| 実装 | **単一 2,098 行関数**を `wp_head` / `admin_head` にフック | `Style` クラス（アキュムレータ）+ 生成器 11 ファイル |
| メディアクエリ | 関数内ローカル変数（5 段） | **バケットとして一級**（all/pc/sp/tab/mobile） |
| エディタ対応 | 同じ関数を `admin_head` にも刺す | `$branch` で front/editor を出し分け、セレクタも自動切替 |
| 分離 | 無し（全部インライン） | `$modules` として別ファイル化・キャッシュ経路あり |
| 副作用 | **`set_theme_mod()` で描画中に DB へ書く（5 箇所）** | 無し（起動時に確定済み） |
| 変数の性格 | 部品の見た目そのもの（`--cv-button` 等 151） | 同じく部品寄り（155）だが生成器が責務分割 |

**含意（THEME-INV-05）**: どちらも「意味的トークン」ではないが、
テーマB は生成器が分割されているぶん**意味への再解釈がしやすい**。
テーマA は 2,098 行の巨大関数を読み解く以外に対応表を作る道が無い。
さらに テーマA の DB write 副作用は、**読み取り専用のつもりの解析でサイト状態が変わりうる**という
運用上の注意点になる（本調査ではファイル読み取りのみで、当該コードパスは実行していない）。

## 4. 本文（the_content）の扱い — ここが最大の差

| | テーマA | テーマB | agent-neo |
|---|---|---|---|
| フィルタ本数 | **3**（iframe ラップ / 有料記事切替 / h2 前広告） | **7 種以上のパイプライン**（優先度 12 に統一） | 中間 JSON → 決定論レンダラ（テーマ側は薄い） |
| 目次 | **持たない**（外部プラグイン RTOC） | 内蔵。プレースホルダ置換 + h2 前挿入 + 二重防止フラグ | レンダラ生成 + `toc:false` |
| 遅延読み込み | 持たない（EWWW 等に委譲） | lazysizes 変換を自前実装（noscript 退避・アスペクト比補完） | — |
| URL 自動カード化 | 持たない | 内蔵（`themeB_remove_url_to_card` で無効化可） | — |
| REST 経路の扱い | 区別しない | **`is_rest()` で意図的に除外** | — |

**含意（THEME-INV-07）**: テーマB は「保存 HTML」と「表示 HTML」を明確に分けている。
これは中間 JSON パイプラインの発想と同型で、**変換レイヤの置き場所として既に成立している前例**。
テーマA は本文をほぼ素通しし、装飾はブロックの save 出力に固定されている。

## 5. 「使われているものを知る」機構

- **テーマB**: `Pre_Parse_Blocks` が `wp_head(0)` で
  本文を `do_shortcode` → `parse_blocks` で再帰走査し、ブログパーツ・同期パターンは参照先まで展開。
  ウィジェットは `ob_start()` → 実出力 → `ob_clean()` の**ドライラン**で収集。
  結果を `$used_blocks` に溜め、コアブロック CSS はその場で `wp_enqueue_style()`。
- **テーマA**: 同等の機構は無い。CSS は常に全量インライン。
- **agent-neo**: `section-registry.json` が pattern↔section_id↔template を**静的に**台帳化。
  実行時の走査ではなく、事前の宣言で同じ問題を解いている。

**含意**: 「本文の意味構造をサーバー側で把握する」処理は テーマB に実装例があり、
agent-neo の中間 JSON 側の設計に流用できる（THEME-INV-01 / 02 の実装参考）。

## 6. ブロックの契約

| | テーマA | テーマB |
|---|---|---|
| 定義の正本 | **PHP の `register_block_type()` 呼び出し**（block.json 無し） | **block.json**（`register_block_type_from_metadata`） |
| エディタ JS | 全 25 種が**単一バンドル** `editor/build/index.js` | 共通バンドル + **ブロック別 `index.js`**（`index.asset.php` で依存解決） |
| 環境値 | `wp_localize_script` の `THEMEA_VAR` に一括注入 | ブロック属性 + 設定 API |
| 属性の後方互換 | 記述なし | **v1/v2 の分岐をコードに明示**（`linkData` ↔ `postId`） |
| 未指定属性 | **カスタマイザ値へフォールバック** | 既定値は block.json 側 |
| 切り出し可否 | 実質不可 | **ブロック単位で可能** |

**含意（THEME-INV-01 / 02 の結論に直結）**:
- テーマB のブロックは block.json という機械可読な契約を持つので、**属性表を自動生成できる**。
- テーマA のブロックは契約が PHP と JS バンドルに散っており、**属性は保存済み HTML から
  逆算するしかない**（実記事の `<!-- wp:themeA-blocks/… {…} -->` を集めて帰納する）。
- さらに テーマA は未指定属性がサイト設定で変わるため、**同じ保存内容 → 同じ出力を仮定できない**。
  中間 JSON へ写すときは「保存時点の実効値」を解決して固定する工程が要る。

## 7. 介入点（外部から動かす口）

| | テーマA | テーマB | agent-neo |
|---|---|---|---|
| 自前 filter | 1 | **79** | （プラグイン側で提供） |
| pluggable | 無し（`function_exists` ガードも無い＝再定義は fatal） | `lib/pluggable.php` / `pluggable_parts.php` | — |
| REST | 2（内部用途・未認証） | 14（`wp/v2` 相乗り・管理系中心） | **34 コントローラ（`agent-neo/v1`）+ MCP + CLI** |
| 設定書き換え | option 直接 | option 配列 + REST 一部 | 契約付き REST |

**含意（THEME-INV-08）**: テーマA サイトを機械操作する現実的な経路は
「WP コア REST（投稿本体）」＋「option 直接書き換え」の 2 つだけ。
テーマ由来の安全な操作 API は存在しない。

## 8. 移植方針への含意（まとめ）

1. **テーマA は「実装を移植する」対象ではない。** 契約が無く、環境結合が強く、拡張点も無い。
   取るべきは**出力マークアップと意味構造**であって、コードではない。
2. **テーマB は契約（block.json）と変換パイプラインの前例として読む価値が高い。**
   特に `Pre_Parse_Blocks` の 2 パス解析と `content_filter` の優先度設計、
   `is_rest()` による保存/表示の分離は、中間 JSON 設計に直接効く。
3. **agent-neo が唯一持っているのは「自分を説明する機構」**（health / config 検証 / boundary guard /
   section-registry / JSON Schema）。機能面積では劣るが、**この性質は捨てずに拡張する**のが筋。
4. 3 者に共通して欠けているのは**「設定 → 出力」の依存を機械的に追える形**。
   agent-neo だけが JSON 宣言でそれに近い状態にあるので、
   移管対象（THEME-INV-09）は「テーマA/テーマB の設定を agent-neo 側の JSON へ写せるか」で判定する。
