<!-- helix_template_version: 5 -->
<!-- HELIX-MANAGED-START -->
# AGENT NEO

@~/ai-dev-kit-vscode/skills/SKILL_MAP.md
@~/ai-dev-kit-vscode/helix/HELIX_CORE.md
@~/ai-dev-kit-vscode/helix/CODEX_TL_MODE.md

> **⚠️ このリポは automation SEO（/opt/seo-tool）とは別リポ・別 GitHub（RetryYN/AGENT-NEO）。**
> **cross-repo 編集・混同は絶対禁止。** /opt/seo-tool 配下のファイルを本リポから変更しない。逆も同様。

## 概要

AGENT NEO = AI エージェントが第一級ユーザーとなる商用 WordPress FSE テーマ + 2 プラグイン構成。automation SEO 専用 1st party 配布テーマ。公式リポ `git@github.com:RetryYN/AGENT-NEO.git`。

- **配布モデル**: automation SEO 専用配布（ADR-024）。wp.org 申請は非採用・公式サイト一本化で確定（2026-06-25 PO 裁定）
- **進捗正本**: `docs/design/L2-design.md` / `docs/reviews/G2-carry-register.md`

## 技術スタック

- **テーマ**: WordPress FSE（フルサイト編集）/ theme.json v3 / Block API / PHP >= 8.1
- **プラグイン**: PHP >= 8.1（WordPress Coding Standards 準拠）
- **テスト**: PHPUnit 9 + Brain Monkey + wp-phpunit（unit / security / integration）、Playwright（E2E）
- **連携**: automation SEO backend（catalog-update push 等）

## アーキテクチャ

```
themes/agent-neo-theme/     FSE テーマ本体
  ├── config/               ブロックスタイル等設定
  ├── patterns/             ブロックパターン（home-* / lp-* 等）
  ├── parts/                ヘッダー / フッター parts
  ├── inc/                  PHP 関数群
  ├── assets/               CSS / JS
  └── templates/            FSE テンプレート

plugins/agent-neo-core/     agent-neo/v1 REST API（57 エンドポイント契約）
  ├── inc/                  コントローラ・サービス層
  ├── schema/               OpenAPI スキーマ
  └── config/               設定

plugins/agent-neo-embed/    AI 生成 HTML 差込ブロック
  ├── src/                  ブロック登録・JS
  └── assets/               ビルド成果物

automation SEO（/opt/seo-tool）
  └── AI 判定・生成は全てこちら。テーマ側は結果を表示するだけ（REQ-NF-025）
```

## ⚠️ REQ-NF-025 — AI ロジック完全分離（絶対制約）

**最重要アーキテクチャ制約。違反禁止。**

### テーマ・Core Plugin の責務範囲（GPL 公開側）

- **許可**: データ受信・表示・静的定義・テンプレートレンダリング・ブロック登録
- **禁止**: AI 判定ロジック・variant 生成・統計判定・CV 監査・リスクスコア計算・モデル呼び出し

### AI 判定は automation SEO 側（closed）が担う

- variant 生成・統計判定・CV 監査・リスクスコア等の **AI 判定はすべて automation SEO 側**に実装する
- テーマ / プラグインは automation SEO から受け取った結果を**表示するだけ**

### 違反チェック（コード変更前に確認）

- OpenAI / Anthropic / xAI SDK を theme / plugin に import していないか
- モデル呼び出し・プロンプト組み立てをテーマ内でしていないか
- 判定ロジック（`if score > threshold` 等）をテーマ内に書いていないか

## catalog-update 契約

- **正本**: automation SEO `D-PLUGIN-CONTRACT §17` のミラー。独自定義しない
- 契約変更時は automation SEO 側で先に D-PLUGIN-CONTRACT を更新し、AGENT NEO 側はそれに追従する

## 現在地

| 項目 | 状態 |
|------|------|
| Drive | agent |
| Size | L |
| 現在フェーズ | **L4（実装）** |
| G0.5 / G1 / G2 / G3 | passed |
| **G4（実装凍結）** | **passed 2026-06-22** |
| G5（デザイン凍結）/ G6（統合検証） | 次の候補 |
| API coverage | 56 / 57（agent-neo/v1） |
| テスト | unit + security / integration / E2E 構築済（G4 PASS 101 件全緑） |
| carry | `docs/reviews/` の gap-register / carry-register で追跡 |

## コーディング規約

- **PHP**: WordPress Coding Standards 準拠 / コメント日本語
- **変更後は必ず `php -l <file>`** で構文チェック
- **実装前に対象ファイルを Read** し、既存パターンへ合わせる
- JS / TS: strict / `any` 禁止 / コメント日本語
- テストなしの完了宣言は禁止

## テストルール

```bash
# PHP 変更時
php -l <changed-file>

# unit + security
./vendor/bin/phpunit --testsuite unit,security

# 統合（wordpress_test DB 使用・ライブ agent_neo と分離・並行禁止）
composer test:integration

# E2E
npx playwright test
```

- 統合テストは `wordpress_test` 分離 DB を使用。ライブ `agent_neo` DB を汚染しない
- PHPUnit 統合テストは並行実行禁止

## プロジェクト固有の開発方針

- **検証駆動開発（VDD）**: 検証完了 = 機能確定。未検証は「実装済み」と見なさない
- **事前調査強化**: 実装前に Web 検索・公式ドキュメント調査を必ず実施
- **3 点セット + Web 検索補強チェック義務化**: 設計補強・計画書起票・仕様判断の着手前に以下 4 点を整合性チェック:
  1. **(a) 要件**: `docs/requirements/L1-requirements.md` + `docs/design/L2-design.md`
  2. **(b) 既存実装**: `themes/` / `plugins/` 配下の関連ファイル
  3. **(c) 設計ドキュメント**: `docs/design/` + `docs/reviews/G2-carry-register.md`
  4. **(d) Web 検索**: WordPress 公式 doc + GitHub（FSE テーマ OSS）+ テックブログ
- **デッドコード掃除**: フェーズ移行時は旧スタブ・未登録ブロック定義を削除

## Claude Code 固有

- **Edit 前に Read 必須**: 未読ファイルの Edit は失敗する
- **cwd 取り違え注意**: agent-neo 操作は `cd /opt/agent-neo &&` / `git -C /opt/agent-neo` を明示（/opt/seo-tool に戻る事象あり）

### サブエージェント Opus 禁止（2026-05-06 確定）

`Agent` tool dispatch で必ず `model` を明示指定。省略すると親 Opus 継承。

```
Agent({
  description: "...",
  subagent_type: "...",
  model: "sonnet" or "haiku",  // 絶対必須 / "opus" 禁止 / 省略禁止
  prompt: "..."
})
```

- `sonnet` = 設計 / 実装 / レビュー / adversarial / 監査
- `haiku` = リサーチ / grep / Web 検索集約
- FE サブエージェント: @fe-design / @fe-component / @fe-style / @fe-a11y / @fe-test

## 禁止事項

- secret / PII / credential を docs / rules / examples に書かない
- テーマ・プラグインに AI ロジックを持ち込まない（REQ-NF-025）
- cross-repo 編集（/opt/seo-tool を本リポから変更しない）
- `.helix/` runtime state・`.claude/settings.local.json` をドキュメント目的で追跡しない

## コマンド

```bash
helix init                                     # 初期化
helix status                                   # 状態確認
helix size --files <N> --lines <N> --drive agent
helix plan draft --title "..."
helix gate <G0.5|G2|G3|G4|G5|G6|G7>
helix sprint status
helix test
helix codex --role <role> --task "..."
helix claude --role <role> --task "..." --dry-run
helix review --uncommitted
helix skill search "<task>" -n 5
```

## HELIX ワークフロー

- Forward: `size` → `plan` → `matrix` → `gate` → `sprint` → `test`
- Reverse: `reverse <type> R0` → `R1` → `R2` → `R3` → `R4` → `rgc`
- Interrupt: 設計ギャップ・要件変更は `helix interrupt` で IIP / CC として扱う
- Handover: `.helix/handover/CURRENT.json` がある場合は `helix handover status --json` を確認し、stale でなければ `CURRENT.md` の Next Action に従う

## 指示ファイル

- Claude Code project context: `CLAUDE.md`（本ファイル）
- Codex CLI project rules: `AGENTS.md`
- 個人差分: `CLAUDE.local.md` / `AGENTS.override.md`（gitignore）
<!-- HELIX-MANAGED-END -->
