<!-- HELIX-MANAGED-START -->
# Codex CLI — AGENT NEO

このファイルは Codex CLI 向けの project rules。Claude Code 側の project context は `CLAUDE.md`、Claude runtime / hook の詳細は `~/.claude/CLAUDE.md` を参照する。

> **⚠️ このリポは automation SEO（/opt/seo-tool）とは別リポ・別 GitHub（RetryYN/AGENT-NEO）。**
> **cross-repo 編集・混同は絶対禁止。** /opt/seo-tool 配下のファイルを本リポから変更しない。逆も同様。

## Core Reads

タスク受領時は必ず以下を Read してフローに従う。

- `~/ai-dev-kit-vscode/helix/HELIX_CORE.md` — 共通ガイダンス
- `~/ai-dev-kit-vscode/skills/SKILL_MAP.md` — フロー・ゲート・スキル一覧
- `~/ai-dev-kit-vscode/helix/CODEX_TL_MODE.md` — Codex CLI の TL 主導読み替えルール
- `CLAUDE.md` — プロジェクト固有の概要・アーキテクチャ・進捗
- `docs/design/L2-design.md` — L2 全体設計（G2 passed 2026-06-14）
- `docs/requirements/L1-requirements.md` — L1 要件定義
- `docs/security/threat-model.md` — 脅威モデル
- `docs/reviews/G2-carry-register.md` — G2 carry 28件の追跡台帳

## Session Start

1. `~/ai-dev-kit-vscode/helix/HELIX_CORE.md` が存在するか確認する。
2. `~/ai-dev-kit-vscode/skills/SKILL_MAP.md` が存在するか確認する。
3. `~/ai-dev-kit-vscode/helix/CODEX_TL_MODE.md` が存在するか確認する。
4. `.helix/handover/CURRENT.json` が存在する場合は `helix handover status --json` を実行する。
5. handover が stale なら作業を止め、stale reason をユーザーに伝える。
6. stale でなければ `helix handover update --owner codex` で所有権を移し、`.helix/handover/CURRENT.md` の Next Action に従う。
7. handover がなければ通常開始し、「OK: セッション初期化完了」と宣言する。

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

## 参照スナップショットの状態

> 本リポは HELIX-MARKETING-HARNESS の `base/wp-theme/` に置かれた開発ベースであり、
> `media/wp/` の現行プロジェクト進捗ではない。下表の旧フェーズ表示は廃止し、
> formal state は `.helix/phase.yaml`、後続の検証実績は
> `docs/L6-integration-verification.md` をそれぞれ参照する。両者に差がある場合は差を明示し、
> コミットメッセージだけで gate を更新済みと判断しない。

| 項目 | 状態 |
|------|------|
| 用途 | 開発ベース / read-only 参照を既定とする |
| formal state | `.helix/phase.yaml` |
| 後続検証記録 | `docs/L6-integration-verification.md` |
| carry | `docs/reviews/G2-carry-register.md` |

## TL Driven Mode

Codex CLI 単体利用時は TL（テックリード）として自律動作する。

- 設計、技術的難易度評価、実装、レビュー、テスト、検証を一気通貫で進める。
- 適用ゲートは `skills/SKILL_MAP.md` のタスクサイズとフェーズスキップ決定木に従う。
- ゲート判定は順番固定で行い、結果を final で簡潔に示す。
- 不明点、本番影響、認証、認可、決済、PII、ライセンス、外部 API / infrastructure / env 変更は人間に確認する。

## HELIX Workflow

- Forward: `size` → `plan` → `matrix` → `gate` → `sprint` → `test`
- Reverse: `reverse <type> R0` → `R1` → `R2` → `R3` → `R4` → `rgc`
- Scrum: `scrum init` → `backlog` → `plan` → `poc` → `verify` → `decide`
- Interrupt: 実装中の設計ギャップや要件変更は `helix interrupt` で IIP / CC として扱う。
- Handover: セッションや担当をまたぐ場合は `.helix/handover/` を正本にする。

## Codex / Claude Code Harness

Codex と Claude Code は API 直叩きではなく、契約プラン + ローカル CLI / hook を HELIX が管理する対象。

- Codex 実行: `helix codex --role <role> --task "..."`
- Claude Code prompt 生成: `helix claude --role <role> --task "..." --dry-run`
- 複数 role 委譲: `helix team run --definition .helix/teams/<team>.yaml`
- 差分レビュー: `helix review --uncommitted`
- 引継ぎ: `helix handover status --json`

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
  3. **(c) 設計ドキュメント**: `docs/design/` + `docs/reviews/G2-carry-register.md`
  4. **(d) Web 検索**: WordPress 公式 doc + GitHub（同概念 OSS FSE テーマ）+ テックブログ
- **REQ-NF-025 遵守**: テーマ・プラグインに AI ロジックを持ち込まない（上記参照）。
- **デッドコード掃除**: フェーズ移行時は移行先実装着手前に旧スタブ・未登録ブロック定義を削除。

## Local Overrides

個人差分は `AGENTS.override.md` に書く。`AGENTS.override.md` は Git 追跡しない。
<!-- HELIX-MANAGED-END -->
