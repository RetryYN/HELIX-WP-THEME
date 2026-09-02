# wp-cli-commands — D-REQ-F（機能要件）

## 概要

`wp-cli-commands` は `wp agent-neo` 名前空間で提供する WP CLI 操作面を定義する feature である。AIエージェント・CI/CD・サーバー管理者が、管理画面なしでコンテンツ操作・設計トークン管理・ライセンス確認・移行実行・ログ確認を安全に行えるコマンドセットを提供する。

全コマンドは `agent-api`（REST）と同一 JSON 契約を共有し、WP CLI は REST の別操作面として動作する。`--dry-run` フラグと `--format=json` オプションを全コマンドに必須化し、AI が機械可読な出力を受け取れることを保証する。

## ID 体系

| 接頭辞 | 対象 |
|---|---|
| `WF-` | wp-cli-commands 機能要件 |
| コマンド接頭辞 | `wp agent-neo` |

## 詳細要件

| ID | 要件名 | 説明 | 優先度 | 上位 L1 ID |
|---|---|---|---|---|
| WF-001 | 名前空間登録 | `wp agent-neo` を WP CLI 名前空間として登録し、`wp help agent-neo` でコマンド一覧を表示できる | P0 | REQ-F-003 |
| WF-002 | `post create` | `wp agent-neo post create --post_type=post --title="" --content="" --status=draft --dry-run` で投稿を下書き作成する。`--format=json` で machine-readable 出力 | P0 | REQ-F-002, REQ-F-003 |
| WF-003 | `post update` | `wp agent-neo post update <post_id> --data=<json_file> --dry-run` で投稿を更新する。差分を `--format=json` で出力する | P0 | REQ-F-002, REQ-F-003 |
| WF-004 | `post publish` | `wp agent-neo post publish <post_id> --dry-run` で投稿をレビュー出力後に公開する。`--force` なしではレビューを出力するのみ | P0 | REQ-F-002, REQ-F-003 |
| WF-005 | `block list` | `wp agent-neo block list [--post_id=<id>] --format=json` でブロック一覧と block.json 準拠の構造を出力する | P0 | REQ-F-002, REQ-F-003 |
| WF-006 | `design-tokens export` | `wp agent-neo design-tokens export --output=<path> --format=json` でデザイントークンを JSON に出力する | P1 | REQ-F-009 |
| WF-007 | `design-tokens import` | `wp agent-neo design-tokens import --input=<path> --dry-run` でデザイントークンを検証・適用する。dry-run 時はスキーマ差分のみ出力する | P1 | REQ-F-009 |
| WF-008 | `license activate` | `wp agent-neo license activate --license-key=<key>` でライセンスを有効化する。環境変数 `AGENT_NEO_LICENSE_KEY` からの読み込みも可とする | P1 | REQ-F-010 |
| WF-009 | `license status` | `wp agent-neo license status --format=json` でライセンス種別・有効期限・機能フラグを出力する | P0 | REQ-F-010 |
| WF-010 | `migrate plan-a` | `wp agent-neo migrate plan-a --source=<wp_url_or_export> --dry-run` でREST機械変換プランの変換プレビューを出力する | P1 | REQ-F-008 |
| WF-011 | `migrate plan-b` | `wp agent-neo migrate plan-b --source=<wp_url_or_export> --dry-run` でAIフル再構築プランのblueprintプレビューを出力する | P1 | REQ-F-008 |
| WF-012 | `migrate status` | `wp agent-neo migrate status [--job_id=<id>] --format=json` で移行ジョブの進捗と結果を出力する | P1 | REQ-F-008 |
| WF-013 | `migrate rollback` | `wp agent-neo migrate rollback --job_id=<id> --dry-run` で移行を指定ジョブ前の状態に戻す | P1 | REQ-F-008 |
| WF-014 | `log tail` | `wp agent-neo log tail [--lines=50] [--level=error] --format=json` でリアルタイム操作ログを出力する | P1 | REQ-NF-007 |
| WF-015 | `--dry-run` グローバルフラグ | 全書き込みコマンドで `--dry-run` を実装し、副作用なしで変更内容・リスクスコア・差分を出力する | P0 | REQ-F-002, REQ-NF-014 |
| WF-016 | `--format=json` グローバルオプション | 全コマンドで `--format=json` を実装し、機械可読な構造化出力を保証する。デフォルト表示は人間可読テーブルとする | P0 | REQ-F-003, REQ-NF-014 |
| WF-017 | exit code 設計 | 成功: 0、バリデーションエラー: 1、認証エラー: 2、対象未発見: 3、外部連携エラー: 4、dryRun 必須: 5 を標準化する | P0 | REQ-F-003, REQ-NF-014 |
| WF-018 | agent-api 共有契約 | WP CLI コマンドは内部で REST エンドポイントを呼ぶか、同一 JSON Schema を参照する。CLI 独自のデータ変換は持たない | P0 | REQ-F-003, REQ-NF-014 |

## 補足・設計指針

**`--dry-run` の強制適用**: 投稿公開・設計トークン適用・移行実行・ロールバックは `--force` フラグなしでは dry-run と同等の preview 出力のみ行い、実際の変更を適用しない。これにより誤った自動実行を防ぐ。

**`--format=json` の出力仕様**: JSON 出力は `{"success": true, "data": {...}, "meta": {"command": "...", "dry_run": true}}` の envelope を標準とする。エラー時は `{"success": false, "error": {"code": "...", "message": "..."}}` を標準化する。

**exit code のスクリプト活用**: CI/CD や AI エージェントが exit code を判定してフローを分岐できるように、非 0 の exit code をエラーカタログと対応させる。

**WP CLI server cron との連携**: `wp cron event run agent-neo/*` の形式で agent-neo のジョブを手動実行できるようにし、WP-Cron が動かない環境での fallback を提供する。

**ライセンスキーの安全な受け渡し**: `--license-key` は CLI 引数での指定を許可するが、プロセステーブルへの露出を避けるため環境変数 `AGENT_NEO_LICENSE_KEY` を推奨する。

## コマンド一覧サマリー

| コマンド | dry-run | format | 優先度 |
|---|---|---|---|
| `wp agent-neo post create` | 必須 | json/table | P0 |
| `wp agent-neo post update` | 必須 | json/table | P0 |
| `wp agent-neo post publish` | 必須（`--force` で解除） | json/table | P0 |
| `wp agent-neo block list` | - | json/table | P0 |
| `wp agent-neo design-tokens export` | - | json | P1 |
| `wp agent-neo design-tokens import` | 必須 | json/table | P1 |
| `wp agent-neo license activate` | - | json/table | P1 |
| `wp agent-neo license status` | - | json/table | P0 |
| `wp agent-neo migrate plan-a` | 必須 | json/table | P1 |
| `wp agent-neo migrate plan-b` | 必須 | json/table | P1 |
| `wp agent-neo migrate status` | - | json/table | P1 |
| `wp agent-neo migrate rollback` | 必須 | json/table | P1 |
| `wp agent-neo log tail` | - | json/table | P1 |

## wp-cli-contract.json の構造

```json
{
  "namespace": "agent-neo",
  "version": "1.0.0",
  "commands": [
    {
      "name": "post create",
      "requires_dry_run": true,
      "exit_codes": { "success": 0, "validation": 1, "auth": 2 },
      "output_schema": "$ref: post-create-output.schema.json"
    }
  ]
}
```

## 参照

- L1: REQ-F-002, REQ-F-003, REQ-F-008, REQ-F-009, REQ-F-010, REQ-NF-007, REQ-NF-014
- 解析レポート: 21-自動化CronAPI契約設計（§名前空間とバージョン, §必須契約ファイル §wp-cli-contract.json）
