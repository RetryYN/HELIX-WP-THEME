# L3 WBS（SSOT）

本書は `docs/design/L3-detailed-design.md` の §6「工程表（WBS）」を分離して SSOT 化したものです。

## 6.1 Phase1 ローンチセット（29件）

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
| T-009 | F-022 | `POST /posts/{id}/sections/{section_id}/edit` 実装 | T-007 | target section のみ差分反映 | .1b | `docs/design/api-catalog.md` |
| T-010 | F-002 | `/pages/{id}/apply` + `from_preview_token` 昇格実装 | T-009 | patch 版依存排除、rollback_point 取得 | .2 | `docs/design/api-catalog.md`, `docs/api/openapi.yaml` |
| T-011 | F-002 | rollback API（`/pages/{id}/rollback` / `/rollback/{rollback_id}`）実装 | T-010 | 410/404 を明示ハンドル | .2 | `docs/design/api-catalog.md` |
| T-012 | F-006 | `/tracking/event` 署名/nonce/bot filter | T-010 | `section_id/cta_id/variant_id` required | .2 | `docs/design/api-catalog.md`, `docs/security/threat-model.md` |
| T-013 | F-010 | `/license/validate` + 2モード failure 制御（transient→grace/readonly、invalid→即時deny） | T-011 | (A) サーバ到達不能（502/3回連続失敗）: grace period 48h 中は write 系 503 で readonly 縮退、grace 超過後に個人版スコープへ自動降格。(B) ライセンス invalid/失効: 即時 403 deny、個人版スコープへ縮退（grace なし）。挙動詳細は `L2-design.md` §8.6 / threat-model CARRY-G2-014 を参照 | .2 | `docs/design/L2-design.md` |
| T-014 | F-044 | catalog-update request schema 受け口と応答固定（4フィールド） | T-013 | 400/409/500 挙動固定 | .2 | `docs/design/api-catalog.md`, `docs/api/openapi.yaml` |
| T-015 | F-044 | Outbox enqueue / retry(初回1s・2^n指数・最大5回・±10% jitter) / DLQ→409 RETRY_EXHAUSTED | T-014 | 5xx 時 backoff が再試行可能 | .2 | `docs/design/api-catalog.md`, `docs/design/api-catalog.md §17.11`, `docs/design/L2-design.md` |
| T-016 | F-025 | JSON 統合データ入出力（settings/export/import） | T-005 | bit-identical を再現 | .2 | `docs/design/L3-detailed-design.md` |
| T-017 | F-011 | SEO Core 入力検証・共存（重複 meta/JSON-LD） | T-016 | 重複検知で warning が必須 | .3 | `docs/design/L3-detailed-design.md` |
| T-018 | F-011 | 計測/SEO 監査ログ保存（`agent_action` CPT） | T-017 | log 参照 API 可用 | .3 | `docs/design/L3-detailed-design.md` |
| T-019 | F-010/F-016 | 個人版 package 境界（S-007） | T-013 | 個人版で HP/LP 書換え拒否 | .3 | `docs/design/api-catalog.md` |
| T-020 | F-004/F-030 | 個人版 CV モジュール最小限表示（入力バリデーション） | T-019 | 不要変更で write-only を制御 | .3 | `docs/design/L3-detailed-design.md` |
| T-021 | F-005/F-012 | 法人版 LP/HP blueprint API とページ apply 接続 | T-010 | blueprint_id と section_id 一貫 | .3 | `docs/design/api-catalog.md` |
| T-022 | F-013/F-031 | 法人版リード寄与機能の権限制御（CTA/フォーム） | T-021 | 個人→法人の越境を拒否 | .4 | `docs/design/api-catalog.md` |
| T-023 | F-011/F-023/F-024 | Performance + a11y + i18n/RTL gate パイプライン | T-017 | LCP/INP/CLS + axe + RTL 判定（CARRY-G2-009: TC-025 PASS / CARRY-G2-013: TC-017b PASS を含む） | .4 | `docs/design/L2-design.md` §8.9 |
| T-024 | F-006/F-007/F-026/F-027 | 連携契約（tracking-context / webhook / catalog cache）【INT-003】 | T-015 / T-018 | 契約テスト群（CAT-001〜CAT-009 + TC-013）全 PASS | .5 | `docs/design/api-catalog.md`, `docs/api/openapi.yaml`, `docs/test-plan/L3-test-plan.md` |
| T-025 | F-020/F-021/F-023 | SBOM 生成・検証（release build 時 `sbom.cdx.json` 生成→Release/SBOM Gate→Theme Review 提出前確定）【INT-003 / CARRY-G2-015】 | T-024 | `sbom.cdx.json` 生成・依存整合・脆弱性/ライセンス検査・Release/SBOM Gate PASS。Theme Review 提出前に SBOM 固定 | .5 | `docs/design/L2-design.md` §8.9 |
| T-026 | F-003 | F-003 操作面（REST/MCP/WP CLI/React UI）統合検証 | T-007 / T-010 | REST/MCP/WP CLI/React UI の 4 操作面が同一契約で一貫して受入可能（機能差異・欠落なし） | .1a〜.2 | `docs/design/L2-design.md` §5, `docs/design/api-catalog.md` |
| T-027 | REQ-F-038 / ADR-026 | `agent-neo/embed` static モード実装（Shadow DOM + 外部 reset CSS） | T-024 / T-026 | strict mode での host/style 継承干渉ゼロ・Light DOM 侵入なし（静的検証） | .4 | `docs/adr/ADR-026.md`, `docs/test-plan/L3-test-plan.md`, `poc/embed-isolation/RESULTS.md` |
| T-028 | REQ-F-038 / ADR-026 | `agent-neo/embed` interactive モード実装（別オリジン sandbox iframe / postMessage source+nonce / CARRY-EMBED-002,003,005） | T-027 | `egress sink0` + cross-origin opaque + `allow-top-navigation*` / `allow-same-origin` 不在・`allow-scripts` 必須・`allow-forms` は L4 判断で許容／`form-action` 無害化確認 | .4 | `docs/adr/ADR-026.md`, `docs/test-plan/L3-test-plan.md`, `poc/embed-isolation/verify.py`, `poc/embed-isolation/RESULTS.md` |
| T-029 | CARRY-EMBED-006 / ADR-026 | embed 隔離 CI ゲート（`poc/embed-isolation/verify.py` を Playwright CI 化） | T-027 / T-028 | `poc/embed-isolation/verify.py` を CI 実行パイプラインへ統合し、10本テストを継続回帰。all green / fail-fast | .4 | `poc/embed-isolation/verify.py`, `docs/test-plan/L3-test-plan.md`, `docs/adr/ADR-026.md` |

## 6.2 クリティカルパス

`T-001 → T-004 → T-006 → T-007 → T-010 → T-011 → T-014 → T-015 → T-024`

- 理由: Theme Kernel/JSON API/操作面/SEO Core/連携の順で、データ整合、監査、外部連携の前提が成立するため
- 注記: T-018（計測/SEO 監査ログ）は T-017 起点の並行ブランチであり、T-024 の前提として依存に含める。T-018 をクリティカルパス本線に置くとパスが T-017 依存の迂回経路を通る矛盾が生じるため、本線からは除外し T-024 の依存列に明示する。
- 注記: T-027〜T-029 は `agent-neo/embed` 用の並行ブランチとして扱う。Phase1本線（`T-001`〜`T-026`）とは別進行で、セキュリティ隔離・PoC移管工数を集中し、完了後に `embed` carry の受入完了を同期する。

## 6.3 L4 carry（実装引き継ぎ）

### 6.3.1 carry 別の着手先 sprint

| Carry | 想定着手 sprint | 対応 WBS | テスト観点 |
|---|---|---|---|
| CARRY-G2-006 | .2 | T-012 / T-024 | CAT-007（once-token replay 防止 / 設計解決済み・L4 検証） |
| CARRY-G2-007 | .2 | T-012 / T-024 | `docs/test-plan/L3-test-plan.md` |
| CARRY-G2-009 | .4 | T-023 | TC-025（slug/selector 安全性） |
| CARRY-G2-011 | .4 | T-023 | TC-028（Lighthouse CI render-blocking third-party=0 / consent 前後タグ） |
| CARRY-G2-012 | .3 | T-021 | TC-029（lp-blueprint 12 セクション整合。※セクション名称は L4 で確定） |
| CARRY-G2-013 | .4 | T-023 | TC-017b（i18n/RTL gate） |
| CARRY-G2-014 | .2 | T-013 | TC-011（license 2モード failure: transient→grace/readonly + invalid→即時deny / 設計解決済み・L4 検証） |
| CARRY-G2-015 | .5 | T-025 | `docs/test-plan/L3-test-plan.md` |
| CARRY-G2-017 | .5 | T-024 | TC-023a（SSRF 初回検証） |
| CARRY-G2-021 | .2 | T-010 | TC-030（既存テーマ preview-only / write 拒否） |
| CARRY-G2-025 | .5 | T-015 / T-024 | TC-023b（SSRF 再試行 re-resolve） |
| CARRY-G2-026 | .5 | T-024 | TC-026（public snapshot 内部 ID 非漏洩） |
| CARRY-G2-028 | .2 | T-010 | TC-030（既存テーマ write 例外条件・preview-only enforcement） |

### 6.3.2 L4 で満たすべき追加受入観点

- carry 検証は `docs/test-plan/L3-test-plan.md` の「L4 carry（006/007/009/011/012/013/014/015/017/021/025/026/028）検証観点」を満たし、該当 TC（CAT-007 / TC-011 / TC-024 / TC-025 / TC-017a / TC-017b / TC-023a / TC-023b / TC-026 / TC-027 / TC-028 / TC-029 / TC-030）が PASS していること。
- carry 由来の追加失敗は `docs/reviews/G2-carry-register.md` の承認条件と整合すること。
