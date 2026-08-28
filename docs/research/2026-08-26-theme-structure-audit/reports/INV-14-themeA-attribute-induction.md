# THEME-INV-14 レポート — テーマA ブロック属性表の帰納

- 対象イシュー: `issues/THEME-INV-14-themeA-attribute-induction.md`
- 状態: **③（フォールバック対応）を 1 ブロック分確定 / 共通属性パターンを特定 /
  ①（全量の帰納）は抽出スクリプトを用意して未実行**
- 調査日: 2026-08-26
- 手段: ホスティング SSH 読み取り専用
- 一次証跡: `evidence/re-themeA-ads.txt`（`themeA_blog_card_dynamic_render_callback` 全文）・
  `evidence/probe3-raw.txt`（登録 25 種）・`evidence/usage-raw.txt`（実使用）

## 1. なぜ帰納が要るか

テーマA は **block.json を持たない**。属性の型・既定値はエディタの単一 minified バンドル
（`editor/build/index.js`）の中にしか無く、機械的に読める契約が存在しない。
一方、実記事の `<!-- wp:themeA-blocks/xxx {…} -->` には**実際に使われた属性が JSON で残っている**。
公開記事 59 + 固定 10 という母集団があるので、そこから帰納するのが現実的な唯一の道。

## 2. 判明している属性（`blogcard` — 全文読了）

`themeA_blog_card_dynamic_render_callback($block_attr, $content)` が参照する属性の全列挙。

### 2.1 ブロック固有の属性

| 属性 | 用途 | 未指定時 |
|---|---|---|
| `postUrl` | 内部リンクの URL | 空文字 |
| `postTitle` | タイトルの上書き | 空文字 |
| `thumbnailUrl` | サムネイルの上書き | 空文字 |
| `toggleTab` | タブ切り替え | `false` |
| `blogcardType` | 内部 / 外部の別 | `'d--blogcard-mysite'`（内部リンク） |
| `blogcardDesign` | デザイン種別 | **`themeA__blogcard_design()`** ← サイト設定 |
| `blogcardTitle` | ラベル文言 | **`themeA__blogcard_title()`** ← サイト設定 |
| `blogcardLabel` | ラベル | **`themeA__blogcard_title()`** ← サイト設定 |
| `postTitleExternal` | 外部リンクのタイトル | 空文字 |
| `postUrlExternal` | 外部リンクの URL | 空文字 |
| `postImageExternal` | 外部リンクの画像 | **`themeA_noimage_url('small_size')`** |

### 2.2 共通属性（他ブロックにも同じ名前で存在すると推定）

```php
$topMarginPc    = $block_attr['topMarginPcAttribute']    ?: 'auto';
$bottomMarginPc = $block_attr['bottomMarginPcAttribute'] ?: 'auto';
$topMarginSp    = $block_attr['topMarginSpAttribute']    ?: 'auto';
$bottomMarginSp = $block_attr['bottomMarginSpAttribute'] ?: 'auto';
$displayDevice  = $block_attr['displayDeviceAttribute']  ?: 'all';

$themeABlockClassName     = $block_attr['className'] ?: '';
$themeABlockCSSAttribute  = $block_attr['themeABlocksCSSAttribute']
    ? '<style jsx="true">' . $block_attr['themeABlocksCSSAttribute'] . '</style>' : '';
```

| 属性 | 意味 | 値の性格 |
|---|---|---|
| `topMarginPcAttribute` / `bottomMarginPcAttribute` | PC の上下余白 | **CSS クラス名の文字列**（`'auto'` 以外はクラスとして連結される） |
| `topMarginSpAttribute` / `bottomMarginSpAttribute` | SP の上下余白 | 同上 |
| `displayDeviceAttribute` | 表示デバイス | `'all'` / それ以外はクラス名 |
| `className` | コア標準の追加クラス | 文字列 |
| `themeABlocksCSSAttribute` | **ブロック単位のインライン CSS** | 生 CSS 文字列。`<style jsx="true">` として出力 |

**これが テーマA の「詳細設定」の実体**。値は数値やトークンではなく
**CSS クラス名そのもの**で、レンダラは文字列連結するだけ:

```php
$detail_setting .= $topMarginPc !== "auto" ? $topMarginPc . " " : "";
```

→ **中間 JSON へ写す際は、クラス名から意味（余白サイズ・表示デバイス）へ逆変換する工程が要る。**
   クラス名の語彙は CSS（`--bottom-margin-s-pc` / `--bottom-margin-xs-pc` 等が
   `evidence/probe6-raw.txt` に出現）と対応すると見られる。

### 2.3 レンダリング時の外部参照（決定論に効く）

| 参照 | 内容 |
|---|---|
| ファイルシステム | `-320x180` サフィックスのサムネイルを `file_exists()` で確認して差し替え |
| 自サイト REST | `rest_do_request('/themeA/post_by_url')` の内部ディスパッチ |
| 投稿 DB | `url_to_postid()` → `get_the_post_thumbnail_url()` ほか |
| サイト設定 | `themeA_get_this_site_domain()` / `themeA__thumbnail_original_used()` |

## 3. フォールバック対応表（③・1 ブロック分確定）

| ブロック | 属性 | フォールバック先 |
|---|---|---|
| `blogcard` | `blogcardDesign` | `themeA__blogcard_design()` |
| `blogcard` | `blogcardTitle` / `blogcardLabel` | `themeA__blogcard_title()` |
| `blogcard` | `postImageExternal` | `themeA_noimage_url('small_size')` |
| （残り 6 動的ブロック） | — | **未読** |

`button`（実使用 339）は CV ボタン設定（`themeA__spcv_all_color` / `_category1〜3_color` 等）を
参照する可能性が高いが未確認。**実使用が最多クラスなので優先して読む。**

## 4. 抽出手順（①・スクリプト化済み・未実行）

`extract-themeA-attrs.sh` を同ディレクトリに用意した。行うことは 3 つ。

1. 公開記事の `post_content` から `<!-- wp:themeA-blocks/… {…} -->` を全抽出
2. ブロック名ごとに属性キーを集計（出現数・値のサンプル）
3. 未登録ブロック（`themeA-blocks/profile`）と `themeABlocksCSSAttribute` の実使用を数える

```bash
bash docs/research/2026-08-26-theme-structure-audit/extract-themeA-attrs.sh > evidence/themeA-attrs-raw.txt
```

**読み取り専用**（`wp db query` の SELECT のみ）。サーバーへの書き込みは無い。

## 5. 併せて解ける問い

この抽出 1 回で、次の未了項目が同時に閉じる。

| 問い | 出典イシュー |
|---|---|
| `[themeA_fukidashi]` 186 回はブロック由来か手書きか | INV-10 §2 |
| `themeA-blocks/profile`（未登録ブロック）の実体 | INV-01 §3.4 / INV-11 補足 |
| `themeABlocksCSSAttribute` の実使用有無 | INV-02 §7 |
| `is-style-themeA-checkmark` 系クラスの実使用 | INV-02 §2.3 |
| 25 ブロック × 属性キー × 値域 | INV-01 §5（属性層） |

## 6. 未了項目

- [ ] `extract-themeA-attrs.sh` の実行と結果の分類
- [ ] 残り 6 動的ブロックの render_callback 精読（特に `button` 実使用 339）
- [ ] `editor/build/index.js` からの属性既定値の復元（minified の解析。②）
- [ ] 余白クラス名 → 意味（サイズ）の対応表作成
- [ ] 実効属性の解決関数の定義（④）— 上記が揃ってから

## 7. 証跡ファイル

| 内容 | 場所 |
|---|---|
| `themeA_blog_card_dynamic_render_callback` 全文 | `evidence/re-themeA-ads.txt` |
| 登録 25 種と静的 / 動的の別 | `evidence/probe3-raw.txt` |
| ブロック実使用回数・未登録 `profile` の出現 | `evidence/usage-raw.txt` |
| 余白系 CSS 変数 | `evidence/probe6-raw.txt` |
