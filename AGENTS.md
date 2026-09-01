# Codex CLI — AGENT NEO

このファイルは Codex CLI 向けの project rules。Claude Code 側の project context は `CLAUDE.md`、Claude runtime / hook の詳細は `~/.claude/CLAUDE.md` を参照する。

> **⚠️ このリポは automation SEO（/opt/seo-tool）とは別リポ・別 GitHub（RetryYN/AGENT-NEO）。**
> **cross-repo 編集・混同は絶対禁止。** /opt/seo-tool 配下のファイルを本リポから変更しない。逆も同様。

## Core Reads

タスク受領時は必ず以下を Read してフローに従う。

- `CLAUDE.md` — プロジェクト固有の概要・アーキテクチャ・進捗
- `docs/design/L2-design.md` — L2 全体設計（設計資産として参照。旧 gate 表示は拘束ではない）
- `docs/requirements/L1-requirements.md` — L1 要件定義
- `docs/security/threat-model.md` — 脅威モデル

## Session Start

1. `helix status` で継続状態を確認する（末尾の HELIX managed block を参照）。
2. 継続状態がなければ通常開始し、「OK: セッション初期化完了」と宣言する。

## ⚠️ REQ-NF-025 — AIロジック完全分離（絶対制約）

**このプロジェクトの最重要アーキテクチャ制約。違反禁止。**

### テーマ・Core Plugin の責務範囲（GPL 公開側）

- **許可**: データ受信・表示・静的定義・テンプレートレンダリング・ブロック登録
- **禁止**: AI 判定ロジック・variant 生成・統計判定・CV 監査・リスクスコア計算・モデル呼び出し

### AI 判定は automation SEO 側（closed）が担う

- variant 生成・統計判定・CV 監査・リスクスコア等の **AI 判定はすべて automation SEO 側** に実装する
- AGENT NEO テーマ・Core Plugin には AI ロジックを一切実装しない
- テーマ/プラグインは automation SEO から受け取った結果を**表示するだけ**

### 違反チェック（コード変更前に確認）

- OpenAI / Anthropic / xAI SDK を theme/plugin に import していないか
- モデル呼び出し・プロンプト組み立てをテーマ内でしていないか
- 判定ロジック（if score > threshold 等）をテーマ内に書いていないか

## catalog-update 契約

- 正本: automation SEO `D-PLUGIN-CONTRACT §17`
- AGENT NEO 側はそのミラー実装。**仕様は automation SEO 側の契約書から読み込み、独自に定義しない**
- 契約変更時は automation SEO 側で先に D-PLUGIN-CONTRACT を更新し、AGENT NEO 側はそれに追従する

## 旧 AGENT NEO kit の扱い（PO 前提 2026-09-02）

> 本リポは HELIX-MARKETING-HARNESS の `base/wp-theme/` に置かれた開発ベースであり、
> `media/wp/` の現行プロジェクト進捗ではない。
> 旧 AGENT NEO kit 由来の工程物（G0.5〜G7 gate、G2 carry register、`.helix/phase.yaml` 等の旧 state、
> 旧 L1〜L7 文書の「進捗」「passed」表示、`.helix/handover`）は**破棄前提**で、現行の拘束・formal state ではない。
> 参照してよいのは実装（`themes/` `plugins/`）と設計資産（ADR、設計 doc、監査証跡）に限り、
> 要求は現行 HELIX の L1→L2→L3 で整理しなおす（入力: `docs/research/` の監査証跡、L0 改定ドラフト）。

| 項目 | 状態 |
|------|------|
| 用途 | 開発ベース / read-only 参照を既定とする |
| 現行 state | HELIX consumer（末尾 managed block の `helix status` / `helix doctor --profile consumer`） |
| 旧 kit 由来ファイルの削除 | 破壊的操作のため PO の明示判断を得てから別 Issue で扱う |

## TL Driven Mode

Codex CLI 単体利用時は TL（テックリード）として自律動作する。

- 設計、技術的難易度評価、実装、レビュー、テスト、検証を一気通貫で進める。
- 適用ゲートは HELIX managed block の手順に従う。
- ゲート判定は順番固定で行い、結果を final で簡潔に示す。
- 不明点、本番影響、認証、認可、決済、PII、ライセンス、外部 API / infrastructure / env 変更は人間に確認する。

## HELIX Workflow

現行の workflow と CLI は末尾の HELIX managed block を正本とする。旧 `size / gate / sprint / interrupt / handover` は廃止。

## Codex / Claude Code Harness

Codex と Claude Code は API 直叩きではなく、契約プラン + ローカル CLI / hook を HELIX が管理する対象。

- Codex 実行: `helix codex --role <role> --task "..."`
- Claude Code prompt 生成: `helix claude --role <role> --task "..." --dry-run`
- 複数 role 委譲: `helix team run --definition .helix/teams/<team>.yaml`
- 差分レビュー: `helix review --uncommitted`

外部 provider SDK や認証情報を前提にした fallback を通常導線として追加しない。外部通信で保留するのは recipe remote hub など HELIX 外の配布・取得だけに限定する。

## Codex モデル割当

| 用途 | モデル | 用例 |
|------|--------|------|
| TL / 設計 / レビュー | `gpt-5.4` | `helix codex --role tl --task "..."` / `codex review --uncommitted` |
| 上級実装（スコア 4+） | `gpt-5.3-codex` | `helix codex --role se --task "..."` |
| 通常実装（スコア 1-3） | `gpt-5.3-codex-spark` | `helix codex --role pg --task "..."` |
| 精読 / 大規模スキャン | `gpt-5.2-codex` | `codex exec "精読: [対象]" -m gpt-5.2-codex` |

- 思考トークン: Codex 系は `model_reasoning_effort = "xhigh"` 固定
- フルオート必須: `--full-auto` を付けないと承認待ちでタイムアウトする
- `--quiet` / `-q` オプションは存在しない
- `--uncommitted` とプロンプト引数は併用不可

## Claude Code サブエージェント — Opus 禁止（2026-05-06 確定）

- `Agent` tool dispatch 時に **必ず `model` parameter 明示指定**（`model: "sonnet"` or `"haiku"`）
- 省略すると親 Opus 継承で Opus subagent 起動 → セッション枠消費
- モデル選択基準:
  - `sonnet` = 設計補強 / drafter / Fix / adversarial review / 監査
  - `haiku` = リサーチ / grep cmdline / Web 検索集約
  - `opus` = サブエージェント禁止（= 親 Claude Code = オーケストレーターのみ）

## Skills

- triggers 該当時は該当スキルの `SKILL.md` だけを Read する。
- 全スキル一括読み込みは禁止。
- skill 内の `references/` は skill ディレクトリからの相対パスで解決する。
- スキル推挙: `helix skill search "<task>" -n 5` で gpt-5.4-mini が catalog から自動選定（1 時間キャッシュ）
- 一気通貫: `helix skill chain "<task>"` で search → use まで実行

## Editing Rules

- 実装前に必ず対象ファイルを Read する。
- 既存コードの構造、命名、テスト配置へ合わせる。
- 既存の未コミット変更はユーザー作業として扱い、巻き戻さない。
- secret、PII、credential を docs / rules / examples に書かない。
- **WordPress テーマ・PHP ファイル変更後は必ず `php -l <file>` で構文チェックする。**

## Test Rules

- PHP 変更: `php -l <changed-file>`
- PHPUnit: `./vendor/bin/phpunit --testdox`
- JS/TS 変更: `npm test` または `pnpm test`
- WP ブロック: `wp block validate <block-dir>` (WP-CLI)
- 広い変更: `composer test` または `./vendor/bin/phpunit`

## プロジェクト固有の編集ルール

- **検証駆動開発（VDD）**: 検証完了 = 機能確定。未検証は「実装済み」と見なさない。
- **事前調査強化**: 実装前に Web 検索・先行事例・公式ドキュメント調査を必ず実施。
- **3 点セット + Web 検索補強チェック義務化**: 設計補強・計画書起票・仕様判断の着手前に必ず以下 4 点を整合性チェック:
  1. **(a) 要件**: `docs/requirements/L1-requirements.md` + `docs/design/L2-design.md`
  2. **(b) 既存実装**: `theme/` / `plugin/` / `src/` 配下の関連ファイル
  3. **(c) 設計ドキュメント**: `docs/design/` + `docs/adr/`（旧 carry register は参照のみ）
  4. **(d) Web 検索**: WordPress 公式 doc + GitHub（同概念 OSS FSE テーマ）+ テックブログ
- **REQ-NF-025 遵守**: テーマ・プラグインに AI ロジックを持ち込まない（上記参照）。
- **デッドコード掃除**: フェーズ移行時は移行先実装着手前に旧スタブ・未登録ブロック定義を削除。

## Local Overrides

個人差分は `AGENTS.override.md` に書く。`AGENTS.override.md` は Git 追跡しない。

<!-- HELIX:managed:start -->
# HELIX アダプター

この project は HELIX lifecycle を現行 `helix` command で扱う。PLAN-M-02 で atomic identifier migration が行われるまでは、CLI 名は `helix` のまま扱う。

PO への進捗報告・調査結論・確認依頼など chat 出力は日本語を既定とする。docs / handover / adapter prose も日本語を基本とし、CLI 名・識別子・技術用語は原語のまま扱ってよい。

- 状態確認: `helix status`
- 完了判定 packet 確認: `helix completion decision-packet --json`
- 完了 review bundle 確認: `helix completion review-bundle --json` (exact digest と semantic digest を確認)
- Version-up dry-run: `helix version-up dry-run --current v0.1.0 --target v0.1.4 --release-remote https://github.com/RetryYN/HELIX-HARNESS-DevOS.git --json`
- 診断: `helix doctor --profile consumer`
- rename packet 確認: `helix rename plan --json`
- 継続状態: `helix status`（`harness.db` continuation projection）
- Codex 委譲: `helix codex --role <role> --task "..."`
- Claude 委譲: `helix claude --role <role> --task "..."`
- チーム dry-run: `helix team run --definition .helix/teams/default-hybrid.yaml --mode hybrid --json`

この managed block の外側にある project-owned instruction は consumer 側の所有物として扱い、勝手に上書きしない。
<!-- HELIX:managed:end -->
