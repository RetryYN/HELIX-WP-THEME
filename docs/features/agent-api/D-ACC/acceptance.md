# agent-api — D-ACC（受入条件）

## 概要

`agent-api` の受入条件は、REST API が OpenAPI 仕様通りに動作し、セキュリティ要件・dryRun/apply フロー・エラーハンドリングが正しく機能することを検証する。AI エージェントが安全に操作できる契約通りの動作を確認する。

## 受入条件テーブル

| ID | 対応要件 | テスト条件 | 期待結果 | 測定方法 |
|---|---|---|---|---|
| ACC-AF-001 | AF-001 | `wp/v2/agent-neo-xxx` のような混在ルートが存在するか確認する | 全 REST ルートが `/wp-json/agent-neo/v1/` 配下にある | OpenAPI lint + route enumeration |
| ACC-AF-002 | AF-002 | 任意のエンドポイントを GET/POST する | レスポンスに `success`, `data`, `meta.request_id`, `meta.contract_version` が含まれる | REST contract test |
| ACC-AF-003 | AF-003 | `POST /pages/{id}/apply` に `dry_run: true` を付けて実行する | 副作用なしで `diff`, `risk_score`, `diff_hash` が返る | dryRun contract test |
| ACC-AF-004 | AF-003 | dryRun なしで直接 apply する（`diff_hash` を省略） | `DRY_RUN_REQUIRED` エラーが返り、変更が適用されない | security test |
| ACC-AF-005 | AF-004 | 未認証で `POST /sections/{id}/apply` を実行する | 401 が返り、audit log に記録される | セキュリティテスト |
| ACC-AF-006 | AF-004 | 有効な APIキーで `GET /pages` を実行する | 200 と正常なページ一覧が返る | REST contract test |
| ACC-AF-007 | AF-005 | `agent_readonly` 権限の API キーで apply エンドポイントを呼ぶ | 403 が返る | 権限テスト |
| ACC-AF-008 | AF-006 | 認証済みキーで 301 req/min を超えてリクエストを送る | 300 req/min 超過分に 429 + `Retry-After` ヘッダーが返る | rate limit test |
| ACC-AF-009 | AF-007 | CI で `openapi.yaml` を lint する | エラー 0 件で通過する | CI lint |
| ACC-AF-010 | AF-007 | `openapi.yaml` に破壊的変更（必須フィールド削除）を入れた PR を作る | openapi-diff が破壊的変更を検出して CI が失敗する | CI diff check |
| ACC-AF-011 | AF-008 | `GET /pages/{id}` でページ構造を取得する | blueprint schema 準拠の JSON が返る | schema validation |
| ACC-AF-012 | AF-009 | 存在しない `section_id` で `GET /sections/{section_id}` を呼ぶ | 404 が返る | error handling test |
| ACC-AF-013 | AF-011 | `POST /seo/{id}/apply` で `canonical` を変更する dryRun を実行する | SEO risk diff（影響スコア, 変更前後 canonical）が返る | SEO contract test |
| ACC-AF-014 | AF-013 | `POST /jobs` で非同期ジョブを作成し、同一 `idempotency_key` で2回送信する | 2回目は 200 + 既存ジョブの結果が返り、重複実行されない | idempotency test |
| ACC-AF-015 | AF-013 | `GET /jobs/{job_id}` でジョブ状態を取得する | `queued/running/succeeded/failed` のいずれかの状態が返る | job contract test |
| ACC-AF-016 | AF-014 | `POST /batch` に20件を超える操作を送る | 400 + バリデーションエラーが返る | batch validation test |
| ACC-AF-017 | AF-016 | apply 操作後に `GET /logs` を呼ぶ | actor, request_id, target_type, diff_hash が記録されている | audit log test |
| ACC-AF-018 | AF-017 | `GET /health` を未認証で呼ぶ | 200 と REST/cron/DB 状態が返る | health check test |
| ACC-AF-019 | ANF-005 | 外部 URL 取得を行うエンドポイントで `169.254.169.254`（AWS metadata）宛のリクエストを試みる | SSRF_BLOCKED エラーが返る | SSRF security test |
| ACC-AF-020 | ANF-013 | apply 後に rollback を実行する | apply 前の状態に復旧され、rollback も audit log に記録される | rollback test |

## 異常系・境界値

| ID | 条件 | 期待動作 |
|---|---|---|
| ACC-AF-ERR-001 | JSON 不正形式のリクエスト | 400 + `VALIDATION_ERROR` |
| ACC-AF-ERR-002 | `contract_version` が古い値 | 400 + `CONTRACT_VERSION_UNSUPPORTED` |
| ACC-AF-ERR-003 | apply 対象パスが操作許可リスト外 | 403 + `FORBIDDEN` |
| ACC-AF-ERR-004 | dryRun の diff_hash が期限切れ（10分超過） | 409 + `CONFLICT` |
| ACC-AF-ERR-005 | 同一対象に処理中ジョブが存在する状態で apply を実行 | 409 + `LOCKED` |
| ACC-AF-ERR-006 | Automation SEO 連携先が利用不能な状態で apply する | 503 + `EXTERNAL_SERVICE_UNAVAILABLE` |

## 受入条件のカバレッジマップ

| 要件 | ACC ID |
|---|---|
| AF-001 名前空間統一 | ACC-AF-001 |
| AF-003 dryRun/apply | ACC-AF-003, 004 |
| AF-004 認証 | ACC-AF-005, 006 |
| AF-005 capability | ACC-AF-007 |
| AF-006 rate limit | ACC-AF-008 |
| AF-007 OpenAPI | ACC-AF-009, 010 |
| AF-008 ページ操作 | ACC-AF-011 |
| AF-009 セクション操作 | ACC-AF-012 |
| AF-011 SEO メタ | ACC-AF-013 |
| AF-013 ジョブ | ACC-AF-014, 015 |
| AF-014 batch | ACC-AF-016 |
| AF-016 ログ | ACC-AF-017 |
| AF-017 health | ACC-AF-018 |
| ANF-005 SSRF | ACC-AF-019 |
| ANF-013 rollback | ACC-AF-020 |

## 契約テストの実行方針

Postman Collection または hurl ファイルを `tests/contract/agent-api/` に配置し、CI で全受入条件を自動実行する。

| テスト種別 | ツール | 実行タイミング |
|---|---|---|
| REST contract test | Postman / hurl | PR マージ時・リリース前 |
| OpenAPI lint | openapi-generator lint | commit 時 |
| OpenAPI diff | oasdiff | PR 時（破壊的変更検出） |
| Security test（SSRF/認証） | OWASP ZAP + 手動 | リリース前 |
| rate limit test | k6 | リリース前 |
| idempotency test | カスタムスクリプト | PR マージ時 |

## 参照

- L1: ACC-002, ACC-003, ACC-NF-008, ACC-SEC-001, ACC-ERR-001〜005
- 解析レポート: 21-自動化CronAPI契約設計（§検証計画）
