# wp-cli-commands — D-ACC（受入条件）

## 概要

`wp-cli-commands` の受入条件は、`wp agent-neo` の全 MVP コマンドが正常系・異常系ともに契約通りに動作し、AI エージェントと CI/CD が機械可読な出力を確実に受け取れることを検証する。

## 受入条件テーブル

| ID | 対応要件 | テスト条件 | 期待結果 | 測定方法 |
|---|---|---|---|---|
| ACC-WF-001 | WF-001 | `wp help agent-neo` を実行する | コマンド一覧が表示される（post, block, design-tokens, license, migrate, log） | CLI 実行確認 |
| ACC-WF-002 | WF-002 | `wp agent-neo post create --title="テスト" --dry-run --format=json` を実行する | `{"success": true, "data": {...}, "meta": {"dry_run": true}}` が stdout に出力され、exit code 0 | CLI contract test |
| ACC-WF-003 | WF-002 | `wp agent-neo post create --title="テスト" --format=json`（`--dry-run` なし）を実行する | `{"success": false, "error": {"code": "DRY_RUN_REQUIRED", ...}}` が返り、exit code 5 | CLI contract test |
| ACC-WF-004 | WF-003 | `wp agent-neo post update <id> --data=valid.json --dry-run --format=json` を実行する | 差分・影響フィールド・リスクスコアが JSON で返り、DB への書き込みがない | dry-run verification |
| ACC-WF-005 | WF-004 | `wp agent-neo post publish <id> --dry-run` を実行する | 公開前確認情報が出力され、実際に投稿ステータスが変わらない | status check |
| ACC-WF-006 | WF-005 | `wp agent-neo block list --post_id=<id> --format=json` を実行する | block.json 準拠の構造が JSON 配列で返る | schema validation |
| ACC-WF-007 | WF-006, WF-007 | `wp agent-neo design-tokens export --output=/tmp/tokens.json` を実行後、`import --input=/tmp/tokens.json --dry-run` を実行する | export した JSON を import の dry-run で差分なし（0 変更）と判定する | round-trip test |
| ACC-WF-008 | WF-008 | 有効なライセンスキーで `wp agent-neo license activate --license-key=<key>` を実行する | exit code 0 で成功し、次回 `license status` で `active` が返る | license test |
| ACC-WF-009 | WF-009 | `wp agent-neo license status --format=json` を実行する | `{"type": "personal/corporate", "status": "active", "features": [...]}` が返る | license test |
| ACC-WF-010 | WF-010 | `wp agent-neo migrate plan-a --source=<url> --dry-run --format=json` を実行する | 変換プレビュー（対象ページ数・変換率・未対応要素リスト）が JSON で返る | migration test |
| ACC-WF-011 | WF-011 | `wp agent-neo migrate plan-b --source=<url> --dry-run --format=json` を実行する | blueprint プレビュー（section 構成・confidence スコア）が JSON で返る | migration test |
| ACC-WF-012 | WF-012 | `wp agent-neo migrate status --job_id=<id> --format=json` を実行する | `{"status": "running", "progress": 42, "eta_seconds": 120}` の形式で返る | job tracking test |
| ACC-WF-013 | WF-013 | `wp agent-neo migrate rollback --job_id=<id> --dry-run` を実行する | ロールバック対象の変更一覧が出力され、実際には変更されない | rollback dry-run test |
| ACC-WF-014 | WF-014 | `wp agent-neo log tail --lines=10 --format=json` を実行する | 最新10件の操作ログが JSON 配列で返る | log output test |
| ACC-WF-015 | WF-017 | 存在しない post_id で update コマンドを実行する | exit code 3 が返る | exit code test |
| ACC-WF-016 | WF-018 | WP CLI コマンドと REST API から同一設定を取得する | 同じ JSON 構造・値が返る（契約共有の確認） | integration test |
| ACC-WF-017 | WNF-009 | `wp-cli-contract.json` の全コマンドについて contract test を実行する | 全コマンドが contract 通りの exit code・出力スキーマを返す | CI contract test |
| ACC-WF-018 | WNF-011 | `--dry-run` 付き write コマンド実行前後で DB の変更有無を確認する | `--dry-run` 実行後に DB 変更が存在しない | DB state verification |

## 異常系・境界値

| ID | 条件 | 期待動作 |
|---|---|---|
| ACC-WF-ERR-001 | 権限不足のユーザーで write コマンドを実行 | exit code 2 |
| ACC-WF-ERR-002 | 不正な JSON ファイルを `--data` に渡す | exit code 1 + `VALIDATION_ERROR` メッセージ |
| ACC-WF-ERR-003 | WP CLI 2.7 以前の環境で実行 | バージョン不足エラーで exit code 1 |
| ACC-WF-ERR-004 | `--format=invalid` を渡す | サポート外フォーマットエラーで exit code 1 |
| ACC-WF-ERR-005 | migrate rollback に存在しない job_id を渡す | exit code 3 + `NOT_FOUND` メッセージ |

## 受入条件のカバレッジマップ

| 要件 | ACC ID |
|---|---|
| WF-001 名前空間 | ACC-WF-001 |
| WF-002 post create | ACC-WF-002, 003 |
| WF-003 post update | ACC-WF-004 |
| WF-004 post publish | ACC-WF-005 |
| WF-005 block list | ACC-WF-006 |
| WF-006/007 design-tokens | ACC-WF-007 |
| WF-008/009 license | ACC-WF-008, 009 |
| WF-010/011 migrate plan | ACC-WF-010, 011 |
| WF-012 migrate status | ACC-WF-012 |
| WF-013 migrate rollback | ACC-WF-013 |
| WF-014 log tail | ACC-WF-014 |
| WF-017 exit code | ACC-WF-015 |
| WF-018 共有契約 | ACC-WF-016 |
| WNF-009/012 contract | ACC-WF-017 |
| WNF-011 dry-run 副作用 | ACC-WF-018 |

## CI 自動テスト構成

WP CLI テストは `wp-env` を使ってローカルコンテナで実行する。

| テスト種別 | ファイル配置 | 実行環境 |
|---|---|---|
| CLI contract test | `tests/cli/contract/` | wp-env Docker |
| dry-run 副作用ゼロテスト | `tests/cli/dry-run/` | wp-env Docker |
| exit code テスト | `tests/cli/exit-codes/` | wp-env Docker |
| round-trip テスト（export/import） | `tests/cli/round-trip/` | wp-env Docker |
| license テスト | `tests/cli/license/` | モックライセンスサーバー |

## 参照

- L1: ACC-003, ACC-008, ACC-009, ACC-010, ACC-NF-008
- 解析レポート: 21-自動化CronAPI契約設計（§検証計画 §WP CLI contract test）
