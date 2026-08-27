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

| コレクション | Variable 名 = theme.json スラッグ | 備考 |
|---|---|---|
| `color` | primary, secondary, accent, accent-aa, background, foreground, footer-bg, muted | 8 色固定。モードは light / dark の 2 つ（style variation に対応） |
| `size` | small, medium, large, x-large, xx-large, xxx-large | 6 段固定 |
| `space` | 10, 20, 30, 40, 50, 60 | 6 段固定 |
| `elevation` | 0, 1, 2, 3, 4 | shadow presets。0 = なし、4 = オーバーレイ |

スラッグを増やす・改名する場合は **設計判断 + CHANGELOG** が必要（取り込みスクリプトは `--allow-scale-change` なしでは拒否する）。

## 3. 取り込み手順

### 3-1 トークン
1. Figma で `Tokens` を開き、[10up figma-to-wordpress-theme-json-exporter](https://github.com/10up/figma-to-wordpress-theme-json-exporter) または Theme.json generator プラグインで **settings** を書き出す。
2. 書き出し JSON をリポジトリ外に保存し、dry-run:
   `php tools/design/figma-tokens-to-theme-json.php ~/Downloads/tokens.json`
3. 差分が「値の差し替えのみ」であることを確認して `--write`。
4. `bash bin/check-design-consistency.sh` で G-T1 / G-T3 が緑であることを確認して PR（Draft、scope manifest 付き）。

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
