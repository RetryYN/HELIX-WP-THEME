# L3 WBS（SSOT）

本書は `docs/design/L3-detailed-design.md` の §6「工程表（WBS）」を分離して SSOT 化したものです。

## 6.1 Phase1 ローンチセット（24件）

| T-ID | 対応 F-ID | 内容 | 依存 | 受入条件 | 想定 L4 sprint | reference_doc |
|---|---|---|---|---|---|---|
| T-001 | F-001 | Theme 起動トレース整備（bootstrap/health） | - | theme bootstrap 呼び出しログ取得可 | .1a | `docs/design/L3-detailed-design.md` |
| T-002 | F-001 | Theme/Plugin 境界表の実行定義化 | T-001 | owner/責務不一致ゼロ | .1a | `docs/design/L2-design.md` |
| T-003 | F-001 | `theme-manifest` / `section-registry` スキーマ反映 | T-002 | catalog 未登録時に fail-fast | .1a | `docs/design/L3-detailed-design.md` |
| T-004 | F-001 | `agent_neo` プレフィックス規約静的検査 | T-003 | static grep で違反 0 件 | .1a | `docs/design/L3-detailed-design.md` |
| T-005 | F-025 | `agent_neo` 統一 JSON 方針と schema 参照表作成 | T-004 | openapi 与件と表記一致 | .1a | `docs/design/L2-design.md` §5 |
| T-006 | F-002 | `POST /actions/dry-run` 実装 I/O 契約 | T-005 | dry-run で `diff_hash` を返却 | .1b | `docs/design/api-catalog.md`, `docs/api/openapi.yaml` |
| T-007 | F-002 | `POST /actions/apply` idempotency + rollback point | T-006 | `diff_hash` 検証＋再送時 no-op | .1b | `docs/design/api-catalog.md`, `docs/api/openapi.yaml` |
| T-008 | F-021 | ブロック PATCH endpoint 契約実装（単位更新） | T-007 | section/block 履歴 N 版 | .1b | `docs/design/api-catalog.md` |
| T-009 | F-021 | `PATCH /posts/{id}/sections/{section_id}/edit` 実装 | T-007 | target section のみ差分反映 | .1b | `docs/design/api-catalog.md` |
| T-010 | F-002 | `/pages/{id}/apply` + `from_preview_token` 昇格実装 | T-009 | patch 版依存排除、rollback_point 取得 | .2 | `docs/design/api-catalog.md`, `docs/api/openapi.yaml` |
| T-011 | F-002 | rollback API（`/pages/{id}/rollback` / `/rollback/{rollback_id}`）実装 | T-010 | 410/404 を明示ハンドル | .2 | `docs/design/api-catalog.md` |
| T-012 | F-006 | `/tracking/event` 署名/nonce/bot filter | T-010 | `section_id/cta_id/variant_id` required | .2 | `docs/design/api-catalog.md`, `docs/security/threat-model.md` |
| T-013 | F-003 | `/license/validate` + failure fallback(readonly) | T-011 | invalid 時 readonly が返る | .2 | `docs/design/L2-design.md` |
| T-014 | F-044 | catalog-update request schema 受け口と応答固定（4フィールド） | T-013 | 400/409/500 挙動固定 | .2 | `docs/design/api-catalog.md`, `docs/api/openapi.yaml` |
| T-015 | F-044 | Outbox enqueue / retry(初回1s・2^n指数・最大5回・±10% jitter) / DLQ→409 RETRY_EXHAUSTED | T-014 | 5xx 時 backoff が再試行可能 | .2 | `docs/design/api-catalog.md`, `docs/design/api-catalog.md §17.11`, `docs/design/L2-design.md` |
| T-016 | F-025 | JSON 統合データ入出力（settings/export/import） | T-005 | bit-identical を再現 | .2 | `docs/design/L3-detailed-design.md` |
| T-017 | F-011 | SEO Core 入力検証・共存（重複 meta/JSON-LD） | T-016 | 重複検知で warning が必須 | .3 | `docs/design/L3-detailed-design.md` |
| T-018 | F-011 | 計測/SEO 監査ログ保存（`agent_action` CPT） | T-017 | log 参照 API 可用 | .3 | `docs/design/L3-detailed-design.md` |
| T-019 | F-010/F-016 | 個人版 package 境界（S-007） | T-013 | 個人版で HP/LP 書換え拒否 | .3 | `docs/design/api-catalog.md` |
| T-020 | F-004/F-030 | 個人版 CV モジュール最小限表示（入力バリデーション） | T-019 | 不要変更で write-only を制御 | .3 | `docs/design/L3-detailed-design.md` |
| T-021 | F-005/F-012 | 法人版 LP/HP blueprint API とページ apply 接続 | T-010 | blueprint_id と section_id 一貫 | .3 | `docs/design/api-catalog.md` |
| T-022 | F-013/F-031 | 法人版リード寄与機能の権限制御（CTA/フォーム） | T-021 | 個人→法人の越境を拒否 | .4 | `docs/design/api-catalog.md` |
| T-023 | F-011/F-023/F-024 | Performance + a11y + i18n/RTL gate パイプライン | T-017 | LCP/INP/CLS + axe + RTL 判定 | .4 | `docs/design/L2-design.md` §8.9 |
| T-024 | F-006/F-007/F-026/F-027 | 連携契約（tracking-context / webhook / catalog cache）【INT-003】 | T-015 | 契約テスト 24/24 成功 | .5 | `docs/design/api-catalog.md`, `docs/api/openapi.yaml`, `docs/test-plan/L3-test-plan.md` |
| T-025 | F-020/F-021/F-023 | SBOM 生成・検証（release build 時 `sbom.cdx.json` 生成→Release/SBOM Gate→Theme Review 提出前確定）【INT-003 / CARRY-G2-015】 | T-024 | `sbom.cdx.json` 生成・依存整合・脆弱性/ライセンス検査・Release/SBOM Gate PASS。Theme Review 提出前に SBOM 固定 | .5 | `docs/design/L2-design.md` §8.9 |

## 6.2 クリティカルパス

`T-001 → T-004 → T-006 → T-007 → T-010 → T-011 → T-014 → T-015 → T-018 → T-024`

- 理由: Theme Kernel/JSON API/操作面/SEO Core/連携の順で、データ整合、監査、外部連携の前提が成立するため

## 6.3 L4 carry（実装引き継ぎ）

### 6.3.1 carry 別の着手先 sprint

| Carry | 想定着手 sprint | 対応 WBS | テスト観点 |
|---|---|---|---|
| CARRY-G2-007 | .2 | T-012 / T-024 | `docs/test-plan/L3-test-plan.md` |
| CARRY-G2-015 | .5 | T-025 | 同上 |
| CARRY-G2-009 | .4 | T-023 | 同上 |
| CARRY-G2-013 | .4 | T-023 | 同上 |
| CARRY-G2-017 | .5 | T-024 | 同上 |
| CARRY-G2-025 | .5 | T-015 / T-024 | 同上 |
| CARRY-G2-026 | .3 | T-018 | 同上 |

### 6.3.2 L4 で満たすべき追加受入観点

- carry 検証は `docs/test-plan/L3-test-plan.md` の「L4 carry（007/009/013/015/017/025/026）検証観点」を満たし、該当 TC が PASS していること。
- carry 由来の追加失敗は `docs/reviews/G2-carry-register.md` の承認条件と整合すること。
