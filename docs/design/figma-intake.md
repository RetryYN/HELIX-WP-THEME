# Figma からの取り込み規約（無料プラン前提）

目的: Figma で設計した **トークン（層 1）** と **構図（層 2/3）** を、正本である theme.json とパターンへ「値の差し替え／名前の参照」だけで取り込み、カスタマイズ性を上げつつ一貫性の責務（`consistency-responsibilities.md`）を崩さない。

## 0. 無料プラン（Starter）で使う経路

| 使う | 使わない |
|---|---|
| Design ファイル 3 本 + 個人ドラフト | Dev Mode（Professional 以上） |
| ファイル内で動くプラグイン（Variables → theme.json 書き出し） | Variables REST API（Enterprise 限定） |
| REST `GET /v1/files/:key`（個人アクセストークン、構造取得） | 公式 MCP（Starter は月 6 コールで実用外） |

Figma は **正本ではない**。正本はリポジトリの theme.json / patterns。Figma からの書き出し結果を commit した時点で有効になる。

## 1. ファイル構成（3 本）

1. `AGENT NEO / Tokens` — Variables 4 コレクション（下記）と型見本
2. `AGENT NEO / Layouts` — テンプレ骨格・共有 slot・記事内部品の構図
3. `AGENT NEO / Rules` — elevation 段・AI 的排除規約のサンプル（比較用、書き出し対象外）

## 2. Variables のコレクションとスラッグ（層 1、増減禁止）

| グループ（1 コレクション内） | Variable 名 = theme.json スラッグ | 備考 |
|---|---|---|
| `color` | primary, secondary, accent, accent-aa, background, foreground, footer-bg, muted | 8 色固定。モードは light / dark の 2 つ（style variation に対応） |
| `font-size` | small, medium, large, x-large, xx-large, xxx-large | 6 段固定。**px 実数**で持つ（書き出し器が整数 px に丸めるため rem 小数は不可） |
| `space` | 10, 20, 30, 40, 50, 60 | 6 段固定。px 実数 |
| `elevation` | 0, 1, 2, 3, 4 | shadow presets。0 = なし、4 = オーバーレイ。書き出しでは `settings.custom.elevation` に落ちる |

グループ名は書き出しプラグインの区分認識名に合わせる（`size` と書くと CUSTOM 扱いになり fontSizes に入らない）。
スラッグを増やす・改名する場合は **設計判断 + CHANGELOG** が必要（取り込みスクリプトは `--allow-scale-change` なしでは拒否する）。

### 2-1 DTCG JSON でバリアブルを流し込む（初期投入・一括更新）

Figma の Variables「インポート」は DTCG 形式 JSON を受ける。実測した制約（2026-08-28、無料プラン）:

- 色は `"$value": "#ff6b00"` の文字列だと **0 件で落ちる**。オブジェクト形式が必須:
  `{"$type":"color","$value":{"colorSpace":"srgb","components":[1,0.42,0],"alpha":1,"hex":"#ff6b00"}}`
- 数値は `"$type":"number"`。単位は持てないので px 実数（`14`, `8`）で入れ、rem 換算は取り込み側が行う。
- shadow 等の文字列は `"$type":"string"` で `custom` に落ちる（theme.json の shadow.presets へは手動写像）。
- 1 ファイル = 1 コレクション。既存コレクションへの上書きはできない（削除して再インポート）。

リポジトリの `tools/design/fixtures/figma-plugin-export.sample.json` は、この手順で作った 25 バリアブルを書き出した実物（理想状態 = 親 theme.json と差分なし）。

## 3. 取り込み手順

### 3-1 トークン（PoC 実証済み 2026-08-28）
1. Figma で `Tokens` を開き、ツール（プラグイン）検索で **Variables を theme.json に書き出すコミュニティプラグイン** を実行する
   （2 種を検証。バリアブルを直読して `settings.color.palette / typography.fontSizes / spacing.spacingSizes / custom` を出すものを採用。
   「theme.json ⇄ Variables 変換」を謳う方はバリアブルを拾わず settings が空になった。名称はリポジトリ外メモ）。
   書き出し側の **「SPACING → CLAMP()」「FONT-SIZE → FLUID」トグルは OFF** にする（ON だと 48px 以上が clamp() に変換され、値の差し替えでなくなる）。
2. 書き出し JSON をリポジトリ外に保存し、dry-run:
   `php tools/design/figma-tokens-to-theme-json.php ~/Downloads/theme.json`
   - 書き出し器が付ける接頭辞（`color-primary` / `space-10` / `font-size-small`）は取り込み側が剥がして照合する。
   - 親が `rem` で書き出しが `px` のときは 16px = 1rem で換算するので、値が同じなら「差分なし」になる。
3. 差分が「値の差し替えのみ」であることを確認して `--write`。
4. `bash bin/check-design-consistency.sh` で G-T1 / G-T3 が緑であることを確認して PR（Draft、scope manifest 付き）。

**PoC 証跡**: Figma で `accent` を `#ff6b00 → #0d9488`、`accent-aa` を `#bf5200 → #0f766e` に変更 → 書き出し → dry-run が 2 行の色差分のみ → `--write` → ゲート FAIL=0 → PoC へ theme.json を配置 → `--wp--preset--color--accent: #0d9488` を実機で確認 → 元に戻して `#ff6b00` を確認。

**注意（層の上書き）**: agent-neo-core の design tokens presenter は option `agent_neo_core_design_tokens` を `wp_theme_json_data_theme` で theme.json の上に重ねる（優先順: theme.json < style variation < bridge/presenter < user）。
この option に値が残っていると theme.json をいくら変えても表示に出ない。theme.json の検証では option を空にしてから行う（実際に 2026-08-22 の REST PoC の残骸で 1 度誤判定した）。

### 3-2 構図
1. `Layouts` のフレームを命名規約で作る（§4）。
2. `FIGMA_TOKEN=<個人アクセストークン> node tools/design/figma-structure-to-patterns.mjs --file <FILE_KEY> --out tools/design/out`
   （トークンは環境変数のみ。`.env` やログに残さない。オフラインなら保存済み JSON を `--json` で）
3. 出力された `*.php` は **骨格**。文言・画像を入れ、`patterns/` へ移す前に `bin/check-design-consistency.sh`（G-T2 の生値 0 を確認）と、PoC でのエディタ挿入（Block validation 0）を通す。

## 4. フレーム命名規約（構造 → ブロックの写像）

| フレーム名 | 変換先 |
|---|---|
| `pat/<slug>` | パターン 1 本（`agent-neo/<slug>`） |
| `sec/<name> @space:40 @color:secondary` | `core/group`（`an-section--<name>`、padding は spacing プリセット、背景は palette スラッグ） |
| `col/3` | `core/columns`（子フレーム = 各カラム） |
| `h2 @size:x-large` / `h3 @size:large` | `core/heading`（fontSize はプリセット参照） |
| `p` / `btn` / `img` / `list` / `quote` | 対応する core ブロック |

**値は書かない。** `@size:` `@space:` `@color:` は Variable 名（= スラッグ）だけを受け付ける。数値を書いても無視される。

## 5. Figma でやらないこと
- 本文・メタ・ナビ項目（層 4）— HELIX 側が持つ
- 最終の見た目調整 — サイトエディタ側（本テーマはブロックテーマなので GUI で完結できる）
- 記事内部品の variant 決定 — block style 名で表し、THEME-CAT-03 の受け皿設計に従う
