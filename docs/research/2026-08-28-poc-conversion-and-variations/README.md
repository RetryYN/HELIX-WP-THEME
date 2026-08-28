# PoC 証跡: ウィジェット → template part 変換（THEME-JSON-02 #31）／テーマ A プリセット → style variation 写像（THEME-JSON-01 #30）

実施日: 2026-08-28 / 場所: 使い捨て PoC 3 サイト（本テーマ・テーマ A・テーマ B、同一ホスト）/ 手段: WP-CLI 読み取り + 管理画面（Playwright）/ 本番 write なし。
位置づけ: **PoC 証跡**。要求・設計の入力であり実装ではない。

## 1. ウィジェット → template part 変換（#31）

### 1.1 実測
- テーマ A 10 領域 / テーマ B 20 領域の `sidebars_widgets` を読み取り。**参照されているウィジェットはすべて `widget_block`（ブロックウィジェット）**。レガシー種別の option キー（`widget_text` `widget_custom_html` 等）は WP 既定で存在するが、いずれの領域からも参照されていない（PoC サイトは当方が同一手順で構築したもの。A/B 独自ウィジェット種別は 0 件）。
- 含まれるブロック種別: `archives` `button(s)` `categories` `group` `heading` `latest-comments` `latest-posts` `paragraph` `search`（すべて core）。
- 代表 6 領域（A: サイドバー / 記事下 / フッター、B: 共通サイドバー / 記事下部 / フッター 1）を `<!-- wp:group -->` で包んで本テーマの編集画面へ投入し Block validation を集計 → **6 領域すべて invalid=0**（`editor-validate-converted.json`、変換後 markup は `converted/`）。

| 領域 | ウィジェット数 | ブロック数 | invalid |
|---|---|---|---|
| A サイドバー | 3 | 8 | 0 |
| A 記事下 | 1 | 6 | 0 |
| A フッター | 1 | 6 | 0 |
| B 共通サイドバー | 5 | 14 | 0 |
| B 記事下部 | 1 | 6 | 0 |
| B フッター 1 | 1 | 6 | 0 |

### 1.2 変換できない種別（判定基準つき）
| 種別 | 変換 | PoC での件数 |
|---|---|---|
| ブロックウィジェット（`widget_block`） | 内容をそのまま template part / slot へ | 全件 |
| 静的 HTML / テキスト（`widget_custom_html` `widget_text`） | `core/html` / `core/paragraph` へ機械変換 | 0 |
| コアウィジェット（最近の投稿・カテゴリ・検索 等） | 対応コアブロックへ（WP 標準の「ブロックへ変換」経路） | 0（PoC ではブロック版が使われていた） |
| **テーマ / プラグイン独自ウィジェット** | 写像先なし。THEME-CAT-03（#28）の受け皿に依存 | 0（**未検証**。実運用サイトの widget option 種別を read-only で採るのが次手） |

### 1.3 結論（要求入力）
- 変換手順は「領域 → group で包む → 対象 template part / slot に置く」で成立し、本テーマ側の受け皿（THEME-CAT-01 #26）が決まれば機械化できる。
- 独自ウィジェットの受け皿は #28 と同じ判断。ウィジェットとブロックで受け皿を二重管理しない。

## 2. テーマ A デザインプリセット → style variation（#30）

### 2.1 実測
- テーマ A はカスタマイザー export 形式のプリセット JSON を **24 本**同梱（約 250 キー / 本）。プリセット 1 を分類:

| 分類 | キー数 | 本テーマでの写像先 |
|---|---|---|
| 色（hex 値） | 53 | `settings.color.palette`（8 スラッグへ値差し替え）+ 部品別は `styles.blocks.*` / block style |
| 見た目の選択（`d--*` / `t--*` 等の style 選択子） | 54 | block style / `styles.blocks.*` |
| 骨格（カラム数） | 4 | テンプレート変種（single-2col / 1col 等） |
| フォント / サイズ | 19 | `settings.typography`（フォントファミリ 2・サイズ段） |
| その他（アニメーション・グラデーション角度・マーカー・機能フラグ） | 97 | **個別判定**。アニメーション・グラデ角は写像先なし（写像不能候補）。マーカー色は block style + CSS |
| 空 / 未使用 | 23 | — |

分類の生データ: `themeA-preset-1.classification.json`。

- 色 8 件を本テーマの palette スラッグへ差し替えた variation `themeA-preset-1.variation.json` を作成（primary←theme_color / secondary←sub_color / accent←link_color / accent-aa←link_hover_color / background←bg_color / foreground←text_color / footer-bg←footer_bg_color / muted←footer_text_color。**accent-aa は近似**: テーマ A に AA コントラスト用の色は無い）。
- 一貫性ゲート（#36 の `bin/check-design-consistency.sh`（PR #36 の枝のみ・未 merge の PoC））を PoC ホスト上で実行 → **G-T1b PASS（スラッグ集合は親と同一・生値なし）**。

### 2.2 結論（要求入力）
- 「プリセット 1 個 → styles/*.json 1 本」は色の層で成立。部品別の色・見た目 107 件は `styles.blocks.*` + block style の設計（THEME-CAT-03）に依存。
- 写像不能候補は「その他 97」のうちアニメーション・グラデーション角度・スライダー系。確定には各 control を切り替えて DOM / computed style の差分を採る実機作業が要る（未実施）。

## 3. 再現手順
- ウィジェット読み取り: `wp option get sidebars_widgets --skip-themes --skip-plugins --format=json` / `wp option get widget_block --format=json`
- 編集画面検証: `../2026-08-28-poc-styles-parts-gates/scripts/editor-validate-raw.mjs`（`FILES` に markup、`POC_URL` `POC_SSH` `POC_WP` `POC_ENV` を環境変数で）
- 投入した下書きは検証後に削除済み。
