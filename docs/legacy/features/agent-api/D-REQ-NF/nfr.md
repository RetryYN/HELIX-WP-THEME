# agent-api — D-REQ-NF（非機能要件）

## 概要

`agent-api` の非機能要件は、AIエージェントと人間管理者が安全・安定・高速に REST API を利用できることを保証する。セキュリティ・性能・可観測性・互換性・配布品質の5観点を中心に定義する。

Companion Plugin が全 REST ルートを所有するアーキテクチャを前提とし、Theme Core から REST ルートが漏れ出す状態をリリース前に検出するガードを含む。

## 非機能要件の分類

| 観点 | 要件ID | 件数 |
|---|---|---|
| セキュリティ | ANF-001〜005 | 5 |
| 性能 | ANF-006〜008 | 3 |
| 互換性 | ANF-009〜011 | 3 |
| 信頼性 | ANF-012〜013 | 2 |
| 配布品質 | ANF-014 | 1 |
| 運用 | ANF-015〜016 | 2 |

## 詳細要件

| ID | 要件名 | 観点 | 説明 | 優先度 | 上位 L1 ID |
|---|---|---|---|---|---|
| ANF-001 | 書き込み認証必須 | セキュリティ | 全 apply エンドポイントは nonce または Application Password / APIキー認証を必須とする。未認証リクエストは 401、不十分な権限は 403 を返す | P0 | REQ-NF-002 |
| ANF-002 | スキーマバリデーション | セキュリティ | 全リクエストは JSON Schema で検証し、不正入力を 400 で拒否する。AI 生成 HTML 混入時は sanitize して保存する | P0 | REQ-NF-002, REQ-NF-014 |
| ANF-003 | rate limit 実装 | セキュリティ | 公開エンドポイント 60 req/min、認証済み 300 req/min。超過は 429 + Retry-After ヘッダーを返す。Transient またはオブジェクトキャッシュで管理する | P0 | REQ-NF-002, REQ-NF-014 |
| ANF-004 | audit log | セキュリティ | 全 apply 操作に actor, request_id, target_type, target_id, diff_hash, result, timestamp を記録する。PII・secret はマスクして保存する | P0 | REQ-NF-007, REQ-NF-014 |
| ANF-005 | SSRF ガード | セキュリティ | 外部 URL 取得は private IP・ループバック・link-local を deny。リダイレクト3回まで、タイムアウト5秒、レスポンス上限512KB を適用する | P0 | REQ-NF-014 |
| ANF-006 | レスポンスタイム | 性能 | read 系エンドポイントは p95 200ms 以下。dryRun は p95 800ms 以下。apply は p95 2000ms 以下（外部連携除く） | P1 | REQ-NF-001 |
| ANF-007 | ページネーション | 性能 | 一覧系 GET は `per_page`（最大100）+ `cursor` ベースのページネーションを実装する。レスポンスに `total_count` と `next_cursor` を含める | P1 | REQ-NF-001 |
| ANF-008 | 条件付きリクエスト | 性能 | ETag / Last-Modified を実装し、304 Not Modified を返せるようにする。AI エージェントのポーリングコストを削減する | P2 | REQ-NF-001 |
| ANF-009 | OpenAPI lint/diff CI | 互換性 | `openapi.yaml` の変更時に openapi-diff を CI で実行し、破壊的変更（フィールド削除・型変更・必須追加）を検出して PR をブロックする | P0 | REQ-NF-014 |
| ANF-010 | contract version 互換 | 互換性 | `contract_version` が古い場合は `CONTRACT_VERSION_UNSUPPORTED` を返す。前バージョンの互換サポート期間は最低6ヶ月 | P0 | REQ-NF-014 |
| ANF-011 | WP バージョン互換 | 互換性 | WP 6.6+ / PHP 8.1+ を最低要件とし、compatibility matrix を公開する。テスト環境で major WP リリースごとに検証する | P0 | REQ-NF-013 |
| ANF-012 | idempotency | 信頼性 | `idempotency_key` を持つ apply リクエストは同一キーの再送で同じ結果を返す。24時間以内の重複リクエストを検出してキャッシュから返す | P0 | REQ-NF-014 |
| ANF-013 | rollback | 信頼性 | apply 前に rollback point を生成し、`POST /rollback/{rollback_id}` で直前状態へ復旧できる。ロールバック操作も audit log に記録する | P0 | REQ-NF-014 |
| ANF-014 | Theme Core 分離監査 | 配布品質 | テーマ本体（theme.json / templates / parts）に REST ルート登録コードが含まれていないことを静的解析で検証する | P0 | REQ-NF-008 |
| ANF-015 | 操作ログ保持期間 | 運用 | 操作ログはデフォルト90日保持し、設定で変更可能にする。個人情報を含むフィールドは保持前にマスクする | P1 | REQ-NF-007 |
| ANF-016 | エラーコード網羅性 | 運用 | 全エラーコードを `error-catalog.json` に定義し、code, HTTP status, 原因, 復旧方法を記載する。未定義エラーを catch-all 500 で隠蔽しない | P0 | REQ-NF-014 |

## 補足・設計指針

**認証方式の優先順位**: JWT はカスタム実装の複雑さとトークン失効管理リスクから初版は見送る。WP 標準の Application Password と Companion Plugin 発行の APIキー（site_token形式）の2方式に絞る。

**公開エンドポイントのトラッキング保護**: `POST /tracking/event` は公開エンドポイントだが、site_token + HMAC 署名 + rate limit + bot filter を必須とする。bare public write は禁止。

**rate limit 実装**: WP Transient API または Redis/Memcached オブジェクトキャッシュに依存する。管理画面から制限値を変更可能にし、誤設定でサイトがロックアウトされないよう下限値をガードする。

**rollback の保持期間**: rollback point は apply 後30日間保持し、それ以降は自動削除する。WP CLI で `wp agent-neo rollback list` で一覧表示し、`wp agent-neo rollback cleanup --older-than=30d` で手動削除もできる。

**Contract test の網羅基準**: 全エンドポイントについて、少なくとも「正常系1件・認証エラー1件・バリデーションエラー1件」の3パターンをカバーする。write 系エンドポイントは加えて「rate limit 超過・dryRun 必須エラー・idempotency 重複」をカバーする。

**DB テーブル設計方針**: 操作ログは Custom Table（`wp_agent_neo_logs`）に保存し、WP の `$wpdb` 経由でアクセスする。テーブル作成は `register_activation_hook` + `dbDelta` を使用し、マイグレーション履歴を `wp_agent_neo_db_version` オプションで管理する。

**REST ルート登録の場所**: 全ルートは `rest_api_init` アクションフックで登録し、`is_admin()` 条件で絞らず全リクエストで利用可能にする。ただし Companion Plugin の PHP autoload と `register_activation_hook` 等の依存を `functions.php` に書かず、Plugin の main file に閉じ込める。これにより Theme Core からの分離が維持される。

**操作ログのインデックス設計**: 操作ログは `wp_agent_neo_logs` カスタムテーブルに保存し、`(actor, target_type, timestamp)` の複合インデックスを設定してフィルタクエリを高速化する。90日分を超えたログは WP-Cron で自動クリーンアップする。

**テスト戦略の概要**: 性能要件（ANF-006, ANF-007）は代表エンドポイント（`GET /pages`, `POST /sections/{id}/apply` dryRun）で k6 または Locust でベンチマークし、p95 レイテンシを CI 上で計測する。セキュリティ要件（ANF-001〜005）は OWASP ZAP または手動ペネトレーションで検証する。

## 非機能要件サマリー表

| 観点 | 要件数 | 最重要 |
|---|---|---|
| セキュリティ | ANF-001〜005 | 書き込み認証・スキーマ検証・SSRF ガード |
| 性能 | ANF-006〜008 | read p95 200ms・dryRun p95 800ms |
| 互換性 | ANF-009〜011 | OpenAPI CI・contract version・WP 6.6+ |
| 信頼性 | ANF-012〜013 | idempotency・rollback |
| 配布品質 | ANF-014 | Theme Core 分離監査 |
| 運用 | ANF-015〜016 | ログ保持・エラーコード網羅 |

## 参照

- L1: REQ-NF-002, REQ-NF-007, REQ-NF-014, REQ-NF-013, REQ-NF-008
- 解析レポート: 21-自動化CronAPI契約設計（§Security/Availability Guard, §検証計画）
