# AGENT NEO Theme

## 検証ログ

- `php -l` は `themes/agent-neo-theme/` 配下の全 PHP ファイルで実行する。
- `bin/check-prefix.sh` で CR-002 / CR-003 を検査する。
- fail-fast 確認は `config/section-registry.json` の `required_sections` に未登録の `agent_neo_missing` を一時追加し、`agent_neo_health()` の `config_valid=false` と `config_errors` の `section-registry missing required section_id: agent_neo_missing` を確認する。
- 2026-06-21: `php -l themes/agent-neo-theme/inc/setup/class-boundary-guard.php` PASS。
- 2026-06-21: 現行 `config/theme-manifest.json` を `Agent_Neo_Boundary_Guard::validate()` に渡し、`is_valid()===true` を確認。
- 2026-06-21: 一時 PHP スニペット内で `boundary.json_operation_api.theme_allowed=true` に差し替え、`is_valid()===false` と `boundary.json_operation_api (core-plugin-owned) must not grant theme ownership (theme_allowed=true)` を確認。manifest 本体は未変更。
- 2026-06-21: `boundary.seo_head_render.theme_allowed=true` は `theme_adapter` として valid のまま維持されることを確認。
- 2026-06-21: `bin/check-prefix.sh` PASS。

## 境界

テーマは FSE templates、patterns、theme.json、表示 adapter のみを持つ。JSON 操作 API、CPT、A/B、tracking storage、catalog-update 発火は Core Plugin 側の責務。

## 制作側カスタマイズ方針（ADR-028）

AGENT NEO は「エンドユーザー向けの分厚い設定パネルを持たない」と「制作・運営側が AI 生成物を上書き・微調整できる余地を残す」の二方針を採る（ADR-028）。

### 上書き可能な経路

| 経路 | 内容 | 投稿再生成で消えるか |
|---|---|---|
| Global Styles（サイトエディタ） | 色 / タイポグラフィ / 余白をサイト全体で上書き | **消えない**（WP core が theme_mods に保存） |
| 追加 CSS | 管理画面「外観 > カスタマイズ > 追加 CSS」 | **消えない** |
| Style Variation 選択・複製 | サイトエディタの「スタイル」から light / dark を切り替え、または独自 variation を追加 | **消えない** |
| 同梱パターンの流し込み後編集 | 挿入後は独立ブロックに変換されるため個別編集が可能 | **消えない** |
| インラインブロックスタイル上書き | エディタ上で個別ブロックのスタイルを直接変更 | **消えうる**（AI が記事を再生成する際に上書きされる可能性あり / 非推奨） |

**見た目の調整は必ずテーマ層（Global Styles / Style Variation / 追加 CSS）で行うこと。** インラインスタイルは AI 再生成と競合するため非推奨。

### Global Styles 上書きが有効な設定項目

theme.json は値を「初期値」として提供し、ロックフラグ（`custom:false` 等）を一切使用しない。以下の全項目がサイトエディタから自由に上書きできる。

- 色パレット全8色（background / foreground / primary / secondary / accent / accent-aa / footer-bg / muted）
- タイポグラフィ（フォントサイズ / フォントファミリー）
- 余白・ボーダー等の appearanceTools 対応プロパティ

### 同梱パターンについて

同梱パターンは全て**非同期（unsynced）**。挿入後は独立したブロックとして保存されるため、個別に編集しても他のページや再利用箇所には影響しない。synced パターン（再利用ブロック）は同梱しない方針（REQ-NF-026）。

## 業種別バリエーションの作り方

AGENT NEO は `styles/light.json`（標準）と `styles/dark.json`（ダーク）を同梱する。業種別の配色は light.json を複製するだけで作れる。

### 手順

1. `themes/agent-neo-theme/styles/light.json` をコピーして別名で保存する（例: `styles/legal.json`）

2. ファイル冒頭の `"title"` を変更する

   ```json
   {
     "version": 3,
     "$schema": "...",
     "title": "法律事務所（ネイビー）",
     ...
   }
   ```

3. `"settings"` > `"color"` > `"palette"` の色値を変更する。**変えるべき箇所は accent / accent-aa / primary / secondary の4色が基本。** base 系（background / foreground / footer-bg / muted）は用途に応じて変更する

   ```json
   "palette": [
     { "slug": "background",  "color": "#ffffff",  "name": "背景" },
     { "slug": "foreground",  "color": "#1a1a1a",  "name": "本文" },
     { "slug": "primary",     "color": "#1a1a1a",  "name": "見出し・濃色" },
     { "slug": "secondary",   "color": "#f0f0f0",  "name": "カード・淡面" },
     { "slug": "accent",      "color": "#1a3a5c",  "name": "アクセント（ネイビー）" },
     { "slug": "accent-aa",   "color": "#0f2a4a",  "name": "ボタン背景（WCAG AA 確保）" },
     { "slug": "footer-bg",   "color": "#111111",  "name": "フッター背景" },
     { "slug": "muted",       "color": "#767676",  "name": "補助文字" }
   ]
   ```

4. ファイルを保存するとサイトエディタ「スタイル」の一覧に新 variation が自動で表示される

5. `styles` セクション（ブロックスタイルのオーバーライド）は**触らない**。theme.json の `var(--wp--preset--color--*)` 参照が新 palette を自動的に指すため、レイアウト・タイポグラフィの再定義は不要

### 注意事項

- `styles/` に置いた `.json` ファイルは全てサイトエディタに variation として表示される。未完成のファイルを `styles/` に置かない
- block styles（ボタンの形状・角丸等）は `theme.json` 一元管理。variation ファイルに block styles のオーバーライドを書くのは dark のようにコントラスト確保が必要な最小限のケースのみ
- ボタン背景色（`accent-aa`）を変更した場合は必ず axe-core でコントラスト比（WCAG 2.2 AA: 3:1 以上）を実測して確認する
