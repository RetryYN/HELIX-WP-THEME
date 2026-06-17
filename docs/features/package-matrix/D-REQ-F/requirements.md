# package-matrix — D-REQ-F（機能要件）

## 概要

`package-matrix` は AGENT NEO の個人版・法人版・アドオン・移行プラグインの機能境界を制御する feature である。ライセンス検証・feature flag 管理・個人→法人アップグレードパス・Theme Core と Companion Plugin の責務境界を定義する。

> **2026-06-18 / ADR-024 確定**: AGENT NEO テーマは独立した有償ライセンス販売を行わない。Automation SEO 専用配布とし、課金は Automation SEO 契約のみで管理する。個人版／法人版の区分は機能境界（個人: 記事 CRUD 操作 / 法人: 構造変更・LP/HP/BLP ブループリント等）として引き続き維持し、プラン階層は Automation SEO のプラン階層に紐づける。

ライセンス制御は Companion Plugin が所有し、Theme Core はライセンス情報を保持しない。feature flag はライセンス検証結果から動的に決定され、フラグの変更は REST API から取得できる。

## ID 体系

| 接頭辞 | 対象 |
|---|---|
| `PF-` | package-matrix 機能要件 |

## パッケージ機能マトリクス

| 機能 | Core | 個人版 | 法人版 | 移行プラグイン |
|---|---|---|---|---|
| FSE テーマ基盤・デザイントークン | ✓ | ✓ | ✓ | - |
| REST API 読み取り | ✓ | ✓ | ✓ | - |
| 個人収益ブロック（Review/Ranking/CTA） | - | ✓ | ✓ | - |
| 法人 LP/HP/BLP ブループリント | - | - | ✓ | - |
| A/B テスト・variant 管理 | - | 限定 | ✓ | - |
| Automation SEO 詳細連携 | - | 基本 | ✓ | - |
| 移行プラグイン（Plan A/B） | - | - | - | ✓ |
| CLI 実行ログ・ジョブ管理詳細 | - | - | ✓ | - |
| service-aware IA（複数サービス） | - | - | ✓ | - |
| API キー複数発行（3件以上） | - | 1件 | ✓ | - |

## 詳細要件

| ID | 要件名 | 説明 | 優先度 | 上位 L1 ID |
|---|---|---|---|---|
| PF-001 | ライセンス検証 | サイトごとに license_key を検証し、パッケージ種別（personal/corporate/addon）と有効期限・機能フラグを取得する | P0 | REQ-F-010 |
| PF-002 | ライセンスキャッシュ | ライセンス検証結果を Transient で24時間キャッシュし、外部検証サーバーへの依存を軽減する。キャッシュ失効時は前回結果を grace period（48時間）として維持する | P0 | REQ-F-010 |
| PF-003 | feature flag API | `GET /wp-json/agent-neo/v1/features` でパッケージ別の有効機能フラグ一覧を返す。UI・CLI・REST がこれを参照する | P0 | REQ-F-010 |
| PF-004 | 機能ガード | feature flag が false の機能に対する API リクエストは `FORBIDDEN` を返す。UI は機能を隠蔽またはアップグレード案内に置換する | P0 | REQ-F-010, REQ-NF-008 |
| PF-005 | 個人→法人アップグレードパス | 個人版から法人版への upgrade_key 発行・既存設定引き継ぎ・差分機能即時開放のフローを REST API で提供する | P1 | REQ-F-010 |
| PF-006 | Theme Core 責務境界 | Theme Core（theme.json / templates / parts / patterns / styles / block.json）にライセンスチェック・REST ルート・CPT・計測・SEO 保存を持たせない。静的解析でこれを確認する | P0 | REQ-NF-008 |
| PF-007 | Companion Plugin 責務境界 | REST API / MCP / WP CLI / CPT / SEO 保存 / 計測 / A-B / LP-HP Blueprint / ライセンス管理 / JSON 操作を Companion Plugin が所有する | P0 | REQ-NF-008 |
| PF-008 | 移行プラグイン独立 | 移行プラグインは AGENT NEO Theme + Companion Plugin がなくても起動できる（診断・プレビューのみ）。AGENT NEO がある場合は apply も実行できる | P1 | REQ-F-008 |
| PF-009 | アドオン管理 | 将来のアドオン（業種別スターター・追加計測等）の feature flag を既存フレームワークに追加できる拡張ポイントを設計する | P2 | REQ-F-010 |
| PF-010 | offline fallback | ライセンスサーバーが利用不能の場合、最後の検証済み状態を grace period 中維持し、grace period 超過後は readonly モードに降格する | P0 | REQ-F-010, REQ-NF-013 |
| PF-011 | ライセンス情報の最小保持 | Companion Plugin が保持するライセンス情報は `{license_key_hash, package_type, expires_at, features[], verified_at}` に限定し、決済情報・購入者 PII は保持しない | P0 | REQ-NF-004 |

## 補足・設計指針

**Theme Core と Companion Plugin の境界の厳守**: 将来の開発者がこの境界を侵害しないよう、静的解析（PHPCS custom rule）でコミット時に `add_action('rest_api_init', ...)` が theme ディレクトリに存在する場合に CI を失敗させる。

**個人→法人アップグレードの設定継承**: アップグレード時に既存の API キー・操作ログ・デザイントークン・CTA 設定を引き継ぐ。法人専用機能のデフォルト設定を適用し、管理者がレビューできるようにする。

**offline grace period の設計**: grace period 中は計測・公開・閲覧は維持するが、apply 操作は blocked にする。これにより「ライセンスサーバー障害でサイトが停止する」リスクを排除する。

**Q-001 未決事項の反映**: 個人→法人アップグレード方式（Q-001）は L1 未決のため、REST API で upgrade_key を受け付ける枠組みを設計し、具体的な価格差分請求フローは L2 で確定する。

## ライセンス検証フロー

```
1. サイト起動時 / WP-Cron 24時間周期
   ↓
2. Companion Plugin が license_key_hash + home_url をライセンスサーバーへ送信
   ↓
3. ライセンスサーバーが {package_type, expires_at, features[]} を返す
   ↓
4. 結果を WP Transient（24時間）にキャッシュ
   ↓
5. feature flag API が Transient から機能フラグを返す
   ↓
6. REST / CLI / 管理画面が feature flag を参照して機能を有効化/無効化
```

サーバー応答なし → Transient キャッシュを使用 → grace period（48時間）超過 → readonly モードに降格

## 参照

- L1: REQ-F-010, REQ-NF-008, REQ-NF-013, ACC-010, Q-001, Q-005, Q-006
- 解析レポート: 28-共通強化プラグイン（§9. AGENT NEOへ取り込む設計判断）
