# wp-cli-commands — D-REQ-NF（非機能要件）

## 概要

`wp-cli-commands` の非機能要件は、WP CLI 操作面が AI エージェント・CI/CD パイプライン・サーバー管理者の多様な実行環境で安定して動作することを保証する。セキュリティ・性能・互換性・信頼性・配布品質の観点を定義する。

## 非機能要件の分類

| 観点 | 要件ID | 件数 |
|---|---|---|
| セキュリティ | WNF-001〜003 | 3 |
| 性能 | WNF-004〜005 | 2 |
| 互換性 | WNF-006〜007 | 2 |
| 信頼性 | WNF-008〜011 | 4 |
| 配布品質 | WNF-012〜013 | 2 |
| 運用 | WNF-014〜015 | 2 |

## 詳細要件

| ID | 要件名 | 観点 | 説明 | 優先度 | 上位 L1 ID |
|---|---|---|---|---|---|
| WNF-001 | capability 検証 | セキュリティ | 全書き込みコマンドは WordPress のロール・ケイパビリティを確認し、権限不足時は exit code 2 を返す | P0 | REQ-NF-002 |
| WNF-002 | ライセンスキー保護 | セキュリティ | `--license-key` の値をコマンド実行ログに平文で残さない。引数よりも環境変数を推奨し、ドキュメントで案内する | P0 | REQ-NF-002 |
| WNF-003 | sanitize/escape | セキュリティ | CLI 引数はすべて WordPress のサニタイズ関数または JSON Schema で検証し、未エスケープ出力をしない | P0 | REQ-NF-011 |
| WNF-004 | コマンド実行タイムアウト | 性能 | 個別コマンドのデフォルトタイムアウトは30秒とし、長時間ジョブは非同期ジョブとして起動して job_id を返す | P1 | REQ-NF-001 |
| WNF-005 | JSON 出力の機械可読性 | 互換性 | `--format=json` の出力は RFC 8259 準拠の UTF-8 JSON とし、改行コードを含む生 HTML は JSON エスケープして出力する | P0 | REQ-NF-014 |
| WNF-006 | WP CLI バージョン互換 | 互換性 | WP CLI 2.8+ を最低要件とし、`wp --version` でバージョンが不足している場合はエラーで終了する | P0 | REQ-NF-013 |
| WNF-007 | PHP 8.1+ 互換 | 互換性 | PHP 8.1〜8.3 で動作確認する。`declare(strict_types=1)` を適用し、型エラーをビルド時に検出する | P0 | REQ-F-001 |
| WNF-008 | idempotency | 信頼性 | `--idempotency-key=<key>` を write コマンドに提供し、CI/CD 環境での再実行を安全にする | P1 | REQ-NF-014 |
| WNF-009 | exit code 統一 | 信頼性 | 全コマンドの exit code を `wp-cli-contract.json` で一元管理し、CI/CD が正確にハンドリングできることを保証する | P0 | REQ-NF-014 |
| WNF-010 | `--quiet` モード | 信頼性 | `--quiet` フラグで progress/info 出力を抑制し、stdout に `--format=json` の出力だけを残す。CI パイプラインでの解析を容易にする | P1 | REQ-NF-014 |
| WNF-011 | `--dry-run` の副作用ゼロ保証 | 信頼性 | `--dry-run` を付けたコマンドは DB への書き込み・ファイル変更・外部 API 呼び出しを行わないことを統合テストで確認する | P0 | REQ-NF-014 |
| WNF-012 | CLI テスト契約 | 配布品質 | `wp-cli-contract.json` に全コマンド・引数・exit code・出力 schema を定義し、CI で contract test を実行する | P0 | REQ-NF-014 |
| WNF-013 | ヘルプテキスト品質 | 配布品質 | 全コマンドは `wp help agent-neo <command>` で引数・オプション・使用例を表示する。docblock に `@example` を必須化する | P1 | REQ-NF-016 |
| WNF-014 | WP-Cron fallback | 運用 | WP-Cron が動作しない環境でも `wp cron event run` での手動実行を代替手段として文書化する | P1 | REQ-NF-013 |
| WNF-015 | 操作ログ書き込み | 運用 | 全 write コマンドは操作内容・actor（cli）・timestamp を操作ログに記録する。dry-run は記録しない | P1 | REQ-NF-007 |

## 補足・設計指針

**非同期ジョブとの連携**: `migrate plan-a`・`migrate plan-b`・`blueprint_rebuild` は実行時間が長くなるため、`POST /jobs` を内部呼び出しして job_id を返す形式にする。完了確認は `wp agent-neo migrate status --job_id=<id>` で行う。

**CI/CD での活用パターン**: GitHub Actions 等で `wp agent-neo post create --dry-run --format=json | jq '.data.risk_score'` のように出力を jq で処理し、リスクスコアが閾値を超えた場合に CI をブロックするパターンを推奨する。

**マルチサイト対応**: WP Multisite 環境では `--url=<site_url>` で対象サイトを指定できるようにし、誤ったサイトへの操作を防ぐ。

**`--format=table` の人間可読設計**: デフォルト出力（`--format=table`）は WP CLI 標準テーブル形式で表示し、`success/error` 状態を色（`WP_CLI::success` / `WP_CLI::error`）で視覚化する。AI エージェントが解析する場合は `--format=json --quiet` を使う。

**非機能要件テストの実行環境**: WNF-011（dry-run 副作用ゼロ）と WNF-015（操作ログ書き込み）のテストは `wp-env` Docker コンテナで実行する。テスト後に `wp db reset` でデータベースをリセットし、テスト間の状態汚染を防ぐ。WNF-008（idempotency）のテストは同一 `--idempotency-key` で同じコマンドを2回実行し、2回目の結果が1回目と同一であることを assert する。

**WP CLI コマンドの PHP クラス設計**: 各コマンドサブグループは独立した PHP クラス（`Post_Command`, `Block_Command`, `License_Command`, `Migrate_Command`, `Log_Command`）として実装し、`WP_CLI::add_command('agent-neo post', Post_Command::class)` で登録する。クラスは `AgentNeo\CLI\` 名前空間に配置し、Companion Plugin の autoload に含める。

## 非機能要件と CI の対応表

| 要件 | CI ツール / 実行方法 | 実行タイミング |
|---|---|---|
| WNF-005 JSON 機械可読性 | `jq . output.json` での parse test | PR マージ時 |
| WNF-006 WP CLI バージョン | `wp --version` でバージョン確認 | 環境セットアップ時 |
| WNF-009 exit code 統一 | `wp-cli-contract.json` の contract test | PR マージ時 |
| WNF-011 dry-run 副作用ゼロ | wp-env + DB 状態チェック | PR マージ時 |
| WNF-012 CLI contract | 自動 contract test runner | PR マージ時 |
| WNF-013 ヘルプテキスト | `wp help agent-neo <cmd>` 出力チェック | リリース前 |

**contract.json の管理**: `wp-cli-contract.json` はバージョン管理下に置き、コマンドの追加・変更時は必ず更新する。CI で `wp-cli-contract.json` と実際のコマンド定義の整合性をテストし、差分があれば PR をブロックする。

## exit code 設計詳細

| exit code | 意味 | 例 |
|---|---|---|
| 0 | 成功 | 正常完了、dry-run 完了 |
| 1 | バリデーションエラー | 不正な JSON 引数、必須引数不足 |
| 2 | 認証・権限エラー | capability 不足、API キー無効 |
| 3 | 対象未発見 | 存在しない post_id, job_id |
| 4 | 外部連携エラー | Automation SEO 接続失敗、ライセンスサーバー障害 |
| 5 | dryRun 必須 | `--dry-run` なしの write 実行試み |
| 6 | ライセンス不足 | パッケージ外機能の実行試み |

## 参照

- L1: REQ-NF-002, REQ-NF-007, REQ-NF-011, REQ-NF-013, REQ-NF-014, REQ-NF-016
- 解析レポート: 21-自動化CronAPI契約設計（§必須契約ファイル, §Job必須属性）
