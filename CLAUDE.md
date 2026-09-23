# AGENT NEO

> **⚠️ このリポは automation SEO（/opt/seo-tool）とは別リポ・別 GitHub（RetryYN/AGENT-NEO）。**
> **cross-repo 編集・混同は絶対禁止。** /opt/seo-tool 配下のファイルを本リポから変更しない。逆も同様。

## 共通規律（採用版 2026-09-23）

旧統合層 HELIX-MARKETING-HARNESS（2026-09-23 に PO 判断で廃止）の「傘下リポ共通規律」を本リポの正本として採用する。
以降の節はリポ固有の追記であり、本節と矛盾する場合は本節が優先。

1. PO 承認前に外部（本番 WP・公開先・第三者サービス）への write をしない。
2. credential を repository・DB・ログへ書かない。
3. 進行順序は PoC（実機証跡）→ 要求 → 設計 → 実装。証跡なしに実装へ進まない。
4. cross-repo 編集禁止。他リポへの書き込みは指示に含まれていても着手前に PO へ確認する。
   `RetryYN/HELIX-HARNESS` と `RetryYN/TAKUMI_CMO-Claude_Cowark` は read-only 参照。
5. 破壊的・不可逆な操作（削除・rename・force-push・履歴改変）は PO の明示判断を得てから行う。
6. 公開リポジトリへ、credential に限らず、実運用サイトを特定する情報、個人環境の絶対パス、
   広告・affiliate の追跡識別子、転載許諾を確認していない記事本文、非公開調査対象の固有名を
   記録しない。read-only 証跡も公開可能な最小表現へ変換し、原文・対応表はリポジトリ外で扱う。
7. commit / push / Issue・PR 起票前に `bash scripts/public-safety-guard.sh --staged` を通す。
   調査・証跡・PoC artifact を変更する場合は、非公開の固有名対応表を
   `PUBLIC_REDACTION_GUARD_RE` または `.public-safety.local.regex` から注入する。
   clone / worktree 作成後は `bash scripts/install-public-safety-hooks.sh` で tracked hook を有効にする。

公開情報の分類、例外、事故対応の正本は `docs/governance/public-repository-safety.md`。
検査を通すために実値を allowlist へ追加してはならず、例外は理由・owner・期限を持つ PO 判断として扱う。

非公開の PoC 証跡は `/poc-wp/`・`/local-evidence/`（gitignore 済み）に置き、commit しない。

メモリ（Claude の auto-memory・共有ハーネスメモリとも）へは、PO の明示指示があるときだけ書く。
指摘を受けたら、その場限りの指摘か永続的な指摘かを先に区別する。
## 概要

AGENT NEO = AI エージェントが第一級ユーザーとなる商用 WordPress FSE テーマ + 2 プラグイン構成。automation SEO 専用 1st party 配布テーマ。公式リポ `git@github.com:RetryYN/AGENT-NEO.git`。

- **配布モデル**: automation SEO 専用配布（ADR-024）。wp.org 申請は非採用・公式サイト一本化で確定（2026-06-25 PO 裁定）
- **要求正本**: `docs/requirements/authority.md`（起点は `docs/planning/L0-agent-controlled-variety.md`）。旧 AGENT NEO 設計書は削除済み（git 履歴のみ）

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
  └── AI 判定・生成は全てこちら。テーマ側は結果を表示するだけ（WT-TR-CORE-03（旧 REQ-NF-025））
```

## ⚠️ WT-TR-CORE-03（旧 REQ-NF-025） — AI ロジック完全分離（絶対制約）

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

## 旧 AGENT NEO kit の扱い（PO 前提 2026-09-02）

> 本リポは独立運用の開発ベースであり（2026-09-23 まで旧統合層 HELIX-MARKETING-HARNESS の `base/wp-theme/` submodule）、
> HELIX-WP-HARNESS の現行プロジェクト進捗ではない。
> 旧 AGENT NEO kit 由来の工程物（G0.5〜G7 gate、G2 carry register、`.helix/phase.yaml` 等の旧 state、
> 旧 L1〜L7 文書の「進捗」「passed」表示、`.helix/handover`）は**破棄前提**で、現行の拘束・formal state ではない。
> 参照してよいのは実装（`themes/` `plugins/`）と設計資産（ADR、設計 doc、監査証跡）に限り、
> 要求は現行 HELIX の L1→L2→L3 で整理しなおす（入力: `docs/research/` の監査証跡、L0 改定ドラフト）。

| 項目 | 状態 |
|------|------|
| 用途 | 開発ベース / read-only 参照を既定とする |
| 現行 state | HELIX consumer（末尾 managed block の `helix status` / `helix doctor --profile consumer`） |
| 旧 kit 由来ファイル | PO 判断（2026-09-02）により削除済み（#69）。残る旧設計 doc 内の参照は歴史的記述として扱う |

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

- `npm test` は HELIX consumer の health check（`helix doctor --profile consumer`）であり、テーマのテストではない。テーマのテストは上記コマンドを直接実行する

- 統合テストは `wordpress_test` 分離 DB を使用。ライブ `agent_neo` DB を汚染しない
- PHPUnit 統合テストは並行実行禁止

## プロジェクト固有の開発方針

- **検証駆動開発（VDD）**: 検証完了 = 機能確定。未検証は「実装済み」と見なさない
- **事前調査強化**: 実装前に Web 検索・公式ドキュメント調査を必ず実施
- **3 点セット + Web 検索補強チェック義務化**: 設計補強・計画書起票・仕様判断の着手前に以下 4 点を整合性チェック:
  1. **(a) 要求**: `docs/requirements/authority.md`（L1 5 sub-doc → L2 discovery → L3 IR。旧 L1 / L2 設計は削除済み）
  2. **(b) 既存実装**: `themes/` / `plugins/` 配下の関連ファイル
  3. **(c) 設計ドキュメント**: `docs/design/consistency-responsibilities.md` / `token-structure.md` / `parts-catalog.md` + `docs/adr/`（旧 L2〜L5 設計は削除済み）
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
- 旧 kit 由来の fe-* agents は削除済み（#69）。FE 委譲先は HELIX consumer 投影の agents に揃える（#70 で model frontmatter 付与後に利用可）

## 禁止事項

- secret / PII / credential を docs / rules / examples に書かない
- テーマ・プラグインに AI ロジックを持ち込まない（WT-TR-CORE-03（旧 REQ-NF-025））
- cross-repo 編集（/opt/seo-tool を本リポから変更しない）
- `.helix/` runtime state・`.claude/settings.local.json` をドキュメント目的で追跡しない

## HELIX

現行の `helix` CLI と手順は本ファイル末尾の HELIX managed block を正本とする。
旧 AGENT NEO 時代の `helix init / size / gate / sprint / interrupt / handover` は現行 CLI に存在せず廃止。

## 指示ファイル

- Claude Code project context: `CLAUDE.md`（本ファイル）
- Codex CLI project rules: `AGENTS.md`
- 個人差分: `CLAUDE.local.md` / `AGENTS.override.md`（gitignore）

<!-- HELIX:managed:start -->
# HELIX 共有コンテキスト

harness state と delegation には repository-local の現行 `helix` command を使う。PLAN-M-02 までは command 名を `helix` とする。

PO への進捗報告・調査結論・確認依頼など chat 出力は日本語を既定とする。docs / handover / adapter prose も日本語を基本とし、CLI 名・識別子・技術用語は原語のまま扱ってよい。

- `helix status` は local runtime mode を報告する。
- `helix completion decision-packet --json` は completionClaimAllowed=false と未完了 blocker queue を確認する。
- `helix completion review-bundle --json` は S4 / version-up / rename / action-binding の scoped review packet、exact digest、semantic digest を確認する。
- `helix version-up dry-run --current v0.1.0 --target v0.1.4 --release-remote https://github.com/RetryYN/HELIX-HARNESS-DevOS.git --json` は distribution tag 更新を plan-only / no-write 証跡として確認する。
- `helix doctor --profile consumer` は consumer repo 向け health check を実行する。
- `helix rename plan --json` は PLAN-M-02 承認前の blocked packet を確認する。
- `helix status` は DB-backed cross-runtime continuation state を報告する。
- `helix codex --role <role> --task "..."` は Codex へ委譲する。
- `helix claude --role <role> --task "..."` は Claude へ委譲する。

adapter doc に secret、token、machine-local absolute path を書かない。
<!-- HELIX:managed:end -->
