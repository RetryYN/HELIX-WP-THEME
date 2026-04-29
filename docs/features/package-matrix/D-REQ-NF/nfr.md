# package-matrix — D-REQ-NF（非機能要件）

## 概要

`package-matrix` の非機能要件は、ライセンス管理・feature flag・Theme Core / Companion Plugin の責務境界が商用配布品質・セキュリティ・互換性・運用品質の基準を満たすことを保証する。ライセンスサーバー依存によるサイト停止リスクの最小化と、競合プラグインとの機能境界の明確化を重点とする。

## 非機能要件の分類

| 観点 | 要件ID | 件数 |
|---|---|---|
| セキュリティ | PNF-001〜003 | 3 |
| 信頼性 | PNF-004〜005 | 2 |
| 配布品質 | PNF-006〜010 | 5 |
| 互換性 | PNF-011〜012 | 2 |
| データ保護 | PNF-013 | 1 |

## 詳細要件

| ID | 要件名 | 観点 | 説明 | 優先度 | 上位 L1 ID |
|---|---|---|---|---|---|
| PNF-001 | ライセンスキーの安全保存 | セキュリティ | license_key はハッシュ化して WP Options に保存する。平文での保存・ログへの出力・REST レスポンスへの含有を禁止する | P0 | REQ-NF-002, REQ-NF-004 |
| PNF-002 | 検証通信の安全性 | セキュリティ | ライセンスサーバーへの通信は HTTPS 必須・タイムアウト10秒・証明書検証を有効化する | P0 | REQ-NF-002 |
| PNF-003 | feature flag 整合性 | セキュリティ | feature flag は REST API・WP CLI・管理画面の全面でライセンス検証結果から導出し、フロントエンドのみで書き換えられない設計にする | P0 | REQ-NF-002, REQ-NF-008 |
| PNF-004 | grace period 設計 | 信頼性 | ライセンスサーバーが24時間応答しない場合でも、grace period（48時間）中はサイト運用を継続できる。grace period 超過後は readonly モードに自動降格する | P0 | REQ-NF-013 |
| PNF-005 | ライセンスサーバー SLA | 信頼性 | ライセンス検証サーバーは99.9% uptime を目標とする。定期ライセンス再検証（24時間ごと）の失敗時は Transient キャッシュ値を継続使用する | P1 | REQ-NF-013 |
| PNF-006 | アンインストールクリーンアップ | 配布品質 | Companion Plugin アンインストール時に license_key_hash・API キー・操作ログの WP Options・Transient を削除する。uninstall.php に明示する | P0 | REQ-NF-016 |
| PNF-007 | Theme Core 分離監査 | 配布品質 | CI で `functions.php`・テンプレートディレクトリ・`block.json` に `add_action('rest_api_init', ...)` や `register_post_type()` が含まれないことを PHPCS で確認する | P0 | REQ-NF-008, REQ-NF-011 |
| PNF-008 | GPL 互換 | 配布品質 | Theme Core・Companion Plugin・移行プラグインの全コードが GPL-2.0-or-later または互換ライセンス下にある | P0 | REQ-NF-003 |
| PNF-009 | SBOM 生成 | 配布品質 | リリース時に SBOM（依存ライブラリ・バージョン・ライセンス）を生成し、配布物に同梱する | P1 | REQ-NF-016 |
| PNF-010 | wp.org Theme Review 準拠 | 配布品質 | Theme Core は WordPress Theme Review Guidelines を通過できる品質にする。必須プラグイン（Companion Plugin）の通知は TGM Plugin Activation 等の適切な方法で行う | P0 | REQ-NF-016 |
| PNF-011 | メジャー WP バージョン互換検証 | 互換性 | WP 6.6 / 6.7 / 7.x（仮）の各メジャーリリースで compatibility matrix を更新し、動作確認済みマトリクスを公開する | P0 | REQ-NF-013 |
| PNF-012 | PHP バージョン互換 | 互換性 | PHP 8.1 / 8.2 / 8.3 で CI を実行し、全バージョンで PHPCS/PHPSTAN がクリーンであること | P0 | REQ-NF-011 |
| PNF-013 | ライセンス保有者 PII 最小化 | データ保護 | サーバーに送信するデータは license_key + home_url に限定する。購入者名・メールアドレスをプラグイン側に保持しない | P0 | REQ-NF-004 |

## 補足・設計指針

**Theme Core の「薄さ」の維持**: Theme Core は theme.json / templates / parts / patterns / styles に限定するという原則は、パッケージ間で共通のルールである。アップデートで少しずつ責務が滑り込むことを防ぐために、CI での静的解析（PNF-007）を必須化する。

**アップグレードパスの設定継承**: 個人→法人アップグレード時に既存 API キー・設定・ログが消えないことは信頼性要件として記載する。テスト環境でのアップグレードシナリオを受入条件に含める。

**wp.org 申請時の必須プラグイン通知**: Theme Core が Companion Plugin を必要とする場合、wp.org の Theme Review Guidelines に従い、`functions.php` 内で TGM Plugin Activation または `add_action('admin_notices', ...)` で適切に通知する。必須プラグインを自動インストールするような強制的な実装は wp.org ガイドラインで禁止されているため、推奨に留める。

**SBOM の CI 生成手順**: `composer show --format=json` と `npm list --json` の出力を `tools/sbom-generator.php` で CycloneDX JSON に変換する。CI の release workflow で `sbom.json` を生成し、GitHub Releases の assets に添付する。

**ライセンス検証のヘルスチェック統合**: `GET /wp-json/agent-neo/v1/health` のレスポンスに `license_status: {verified_at, grace_period_remaining_hours, mode}` を含める。監視ツールがライセンス状態を外部から確認できるようにする。`mode: readonly` の場合はヘルスチェックが警告を返す。

**ライセンス関連の WP CLI コマンド**: `wp agent-neo license status`・`wp agent-neo license activate`・`wp agent-neo license refresh`（手動再検証）を提供する。`wp agent-neo license status --format=json` は `{package_type, status, expires_at, features[], verified_at, grace_period_remaining_hours}` を返す。

**未決事項 Q-005・Q-006 への対応方針**: ライセンス検証方式（Q-005）の詳細設計と wp.org 申請プラグインでの機能ロック範囲（Q-006）は L1 未決のため、本ドキュメントでは枠組みのみを定義する。具体的な実装方式は L2 凍結時に確定し、`package-matrix` の D-REQ-F を更新する。

## 検証計画サマリー

| 要件 | 検証方法 | 実行タイミング |
|---|---|---|
| PNF-001 安全保存 | DB 監査（平文検索） | リリース前 |
| PNF-003 flag 整合性 | REST + CLI + 管理画面の3面チェック | PR マージ時 |
| PNF-004 grace period | ライセンスサーバーモックで遮断テスト | リリース前 |
| PNF-007 Core 分離 | PHPCS CI | commit 時 |
| PNF-008 GPL | ライセンスヘッダー監査 | リリース前 |

**ライセンスサーバーの単一障害点対策**: ライセンスサーバーは単一障害点にならないよう、複数リージョンまたはフェイルオーバー設計を検討する。プラグイン側では「サーバー応答なし」と「ライセンス無効」を区別してハンドリングする。

**SBOM の生成と更新タイミング**: SBOM は各リリースタグ作成時に CI で自動生成し、配布 zip に `sbom.json` として同梱する。PHP Composer の `composer.lock` と npm の `package-lock.json` を入力として cyclonedx または spdx 形式で出力する。

## パッケージ別機能フラグの詳細

| フラグ名 | personal | corporate | 説明 |
|---|---|---|---|
| `affiliation_blocks` | true | true | Review/Ranking/CTA ブロック |
| `corporate_lp_blueprint` | false | true | 法人 LP/HP ブループリント |
| `ab_test` | false | true | A/B テスト・variant 管理 |
| `migration_apply` | false | true | 移行 apply 実行 |
| `multi_service_ia` | false | true | service-aware IA |
| `api_keys_multi` | false | true | APIキー3件以上発行 |
| `cli_job_logs` | false | true | CLI 実行ログ詳細 |

## 参照

- L1: REQ-NF-002, REQ-NF-003, REQ-NF-004, REQ-NF-008, REQ-NF-010, REQ-NF-011, REQ-NF-013, REQ-NF-016
- 解析レポート: 28-共通強化プラグイン（§9, §10）
