# G2 carry register (L2 → L3/L4 carry)

## 目的・対象
- 目的: L2 G2（設計凍結）時点でブロッカーを除外後に残存した P1/P2 を L3/L4 へ担保し、追跡・解消先を明示する。
- 対象: L2 設計凍結時点の G2 所見（P1×18、P2×10）
- 凍結日: 2026-06-14
- 集計: `P1 18件 / P2 10件`
- 運用意図: 全28件 PM承認済の L3/L4 carry として G2（設計凍結）を block しない。  
  L3 API契約凍結時に契約精度系の妥当性は `§17` 正本と突合する。

## G2 carry 一覧

| ID | priority | 所見(要約) | 該当箇所 | L3/L4 disposition (対応先) | 備考 | owner(担当ロール) | ETA(対応フェーズ) | PM承認 |
|---|---|---|---|---|---|---|---|---|
| CARRY-G2-001 | P1 | ADR-002 と ADR-012 の責務記述が重複し、catalog-update 契約の所有責務が曖昧 | `docs/design/L2-design.md`（ADR テーブル） | L3:詳細設計 | ADR 更新と章間責務表の一本化を追加 | TL/SE | L3 | 承認済(PM orchestrator / 2026-06-14 / L3-L4 carry) |
| CARRY-G2-002 | P1 | ADR-018（再定義しない互換実装）と §8.7 `catalog-update.schema.json` 定義の矛盾 | `docs/design/L2-design.md` §8.7 / ADR 表 | L3:詳細設計 | 互換実装原則とスキーマ適用条件を収束 | TL/SE | L3 | 承認済(PM orchestrator / 2026-06-14 / L3-L4 carry) |
| CARRY-G2-003 | P1 | ADR-012 の outbox/retry/DLQ 方針と §8.7 ジョブ状態遷移の衝突 | `docs/design/L2-design.md` §8.7、ADR-012 | L3:詳細設計 | 失敗時遷移と再試行条件を再定義 | TL/SE | L3 | 承認済(PM orchestrator / 2026-06-14 / L3-L4 carry) |
| CARRY-G2-004 | P1 | `deduplicated=true` 時の response フィールドが正本(automation SEO §17)と不一致 | `docs/design/api-catalog.md`（catalog-update 契約） | L3:API契約凍結 | `catalog-update` 正本差分(deduplicated response field)は **L3 API契約凍結(G3)で automation SEO §17 と完全一致させる** | TL/SE | L3 | 承認済(PM orchestrator / 2026-06-14 / L3-L4 carry) |
| CARRY-G2-005 | P1 | `event_id` が `idempotency_key` を兼ねるかどうかの意味論が未明確 | `docs/design/api-catalog.md`（`idempotency` 定義） | L3:API契約凍結 | 用語仕様の意味論を明確化し、監査トレース規則を統一 | TL/SE | L3 | 承認済(PM orchestrator / 2026-06-14 / L3-L4 carry) |
| CARRY-G2-006 | P1 | AGENT NEO 側 retry contract の初回 backoff が正本と齟齬（1s vs 5s） | `docs/design/api-catalog.md`（catalog-update / retry） | L3:API契約凍結、L4:実装 | `backoff` 正本差分は **L3 API契約凍結(G3)で automation SEO §17 と完全一致させる** | TL/SE | L4 | 承認済(PM orchestrator / 2026-06-14 / L3-L4 carry) |
| CARRY-G2-007 | P1 | once-token の atomic insert fallback に起因する競合リスク（TB-05a / HMAC replay） | `docs/security/threat-model.md`（TB-05a, once-token） | L4:実装 | once-token の同時処理安全性を再設計 | Security | L4 | 承認済(PM orchestrator / 2026-06-14 / L3-L4 carry) |
| CARRY-G2-008 | P1 | 旧鍵受付制約（F-01 のみ）は L2 設計書へ未転記 | `docs/security/threat-model.md`, `docs/design/L2-design.md` | L3:詳細設計 | 旧鍵受付制約の受入側責務を明文化 | Security | L3 | 承認済(PM orchestrator / 2026-06-14 / L3-L4 carry) |
| CARRY-G2-009 | P1 | `slug` 系 ID の多言語文字混入でインジェクション/セレクタ破壊の懸念 | `docs/design/data-model-ids.md`（slug 生成） | L3:詳細設計 / L4:実装 | 非ASCII slug の影響範囲を明示し、変換規則へ落とし込む | SE/DBA | L4 | 承認済(PM orchestrator / 2026-06-14 / L3-L4 carry) |
| CARRY-G2-010 | P1 | `S-011` Quality Governance と §8.9 複数ゲート整合の不一致 | `docs/design/L2-design.md`（§6/§8.9） | L3:詳細設計 | ゲート一覧と受入基準を同一表現へ統合 | TL/SE | L3 | 承認済(PM orchestrator / 2026-06-14 / L3-L4 carry) |
| CARRY-G2-011 | P1 | `§8.1 render blocking third-party: 0` と GTM/GA4/広告タグ「同意後ロード」の矛盾 | `docs/design/L2-design.md`（§8.1, §8.9） | L3:詳細設計 | 非同期読込方針と同意時読み込み条件を整合 | TL/SE | L3 | 承認済(PM orchestrator / 2026-06-14 / L3-L4 carry) |
| CARRY-G2-012 | P1 | `lp-blueprint` 標準12セクションの Problem/Consequence が不一致 | `docs/design/L2-design.md`（§8.3） | L3:詳細設計 | BP/アウトプット構造と影響差分の定義を統一 | TL/SE | L3 | 承認済(PM orchestrator / 2026-06-14 / L3-L4 carry) |
| CARRY-G2-013 | P1 | `§8.9` i18n/RTL Gate と `sanitize_title()` の非 ASCII slug 問題 | `docs/design/L2-design.md`, `docs/design/data-model-ids.md` | L3:詳細設計 / L4:実装 | `sanitize_title` 運用値と表示用 slug 制約を分離 | SE/DBA | L4 | 承認済(PM orchestrator / 2026-06-14 / L3-L4 carry) |
| CARRY-G2-014 | P1 | `§8.6` ライセンス検証失敗フォールバックと TB-18a(deny→個人版縮退) の不整合 | `docs/design/L2-design.md` §8.6, `docs/security/threat-model.md` TB-18a | L3:詳細設計, L4:実装 | 失敗時フォールバックを仕様レベルで先行定義し、実装差分を潰す | Security | L4 | 承認済(PM orchestrator / 2026-06-14 / L3-L4 carry) |
| CARRY-G2-015 | P1 | `§8.9` `sbom.cdx.json` 管理タイミングと WordPress.org Theme Review の不一致 | `docs/design/L2-design.md` §8.9 / Theme Review Gate | L3:詳細設計 | SBOM 作成・検証タイミングを契約化 | TL/SE | L3 | 承認済(PM orchestrator / 2026-06-14 / L3-L4 carry) |
| CARRY-G2-016 | P1 | `§5` と `§8.7` で `PATCH /pages/{id}/apply` と `POST /pages/{id}/apply` 仕様が不整合 | `docs/design/api-catalog.md`（endpoint カタログ） | L3:API契約凍結 | 適用経路を統一し、冪等・dryRun 係の前提を明確化 | TL/SE | L3 | 承認済(PM orchestrator / 2026-06-14 / L3-L4 carry) |
| CARRY-G2-017 | P1 | `§10 R-013` の SSRF ガードと `§8.7` API ルール整合不足 | `docs/design/L2-design.md`, `docs/security/threat-model.md` TB-20 | L4:実装 | SSRF ガード（URL バリデーション/リゾルブ）実装条件を明文化 | Security | L4 | 承認済(PM orchestrator / 2026-06-14 / L3-L4 carry) |
| CARRY-G2-018 | P1 | `§8.10 claim-risk.schema.json` 受信ペイロード記述の最終確認（文言確定） | `docs/design/L2-design.md` §8.10 | L3:詳細設計 | 受け取り形式の最終文面を承認済みに更新 | TL/SE | L3 | 承認済(PM orchestrator / 2026-06-14 / L3-L4 carry) |
| CARRY-G2-019 | P2 | ADR-008（テーマ/プラグイン分離）後の `§2.4` 配布境界と整合確認 | `docs/design/L2-design.md` ADR-008, §2.4 | L3:詳細設計 | 配布境界一覧へ catalog-update の載せ場所を再確認 | TL/SE | L3 | 承認済(PM orchestrator / 2026-06-14 / L3-L4 carry) |
| CARRY-G2-020 | P2 | ADR-013 の機械契約と `crawler-access-matrix.json` の整合 | `docs/design/L2-design.md` ADR-013 | L3:詳細設計 | AI可読/公開可否判定項目を一元化 | TL/SE | L3 | 承認済(PM orchestrator / 2026-06-14 / L3-L4 carry) |
| CARRY-G2-021 | P2 | ADR-019「preview-only限定」と `safe_apply_state` の整合 | `docs/design/L2-design.md` ADR-019、`safe_apply_state` | L3:詳細設計 | preview-only 仕様と状態遷移を同一表現へ統合 | TL/SE | L3 | 承認済(PM orchestrator / 2026-06-14 / L3-L4 carry) |
| CARRY-G2-022 | P2 | `REQ-F-026` outbound webhook と catalog-update 分離が L2 traceability に未明示 | `docs/design/L2-design.md`（traceability 表） | L3:詳細設計 | トレース表へ分離責務と失敗時遷移を明記 | TL/SE | L3 | 承認済(PM orchestrator / 2026-06-14 / L3-L4 carry) |
| CARRY-G2-023 | P2 | `next_action` が `deduplicated=true` 時返却一覧に欠落 | `docs/design/api-catalog.md`（catalog-update 応答） | L3:API契約凍結 | `next_action` の返却条件を明確化 | TL/SE | L3 | 承認済(PM orchestrator / 2026-06-14 / L3-L4 carry) |
| CARRY-G2-024 | P2 | `REQ-NF-025` の tracking-context と `tracking-context` 責務分界（F-007/A-008） | `docs/design/L2-design.md`（REQ-NF-025） | L3:詳細設計 | 責務境界（AI判断、公開API、計測送信）を分割 | TL/SE | L3 | 承認済(PM orchestrator / 2026-06-14 / L3-L4 carry) |
| CARRY-G2-025 | P2 | TB-22 の webhook 送信先 SSRF でリトライ時 re-resolve 要件が実装値へ反映不十分 | `docs/security/threat-model.md` TB-22 | L4:実装 | 再試行時の事前DNS再解決と private IP 再拒否を実装 | Security | L4 | 承認済(PM orchestrator / 2026-06-14 / L3-L4 carry) |
| CARRY-G2-026 | P2 | `public snapshot` `section_id` 外部公開と TB-24 内部ID変換ルールの最終確認 | `docs/security/threat-model.md` TB-24 | L4:実装 | `public_section_id` 変換実装と監査レイヤーの最終受入条件を追加 | Security | L4 | 承認済(PM orchestrator / 2026-06-14 / L3-L4 carry) |
| CARRY-G2-027 | P2 | `§8.12 tracking-context-v2.schema.json` の `selector_contract`（追跡要件） | `docs/design/L2-design.md` §8.12 | L3:詳細設計 | スキーマ項目定義と責務（追跡取得元）を統一 | TL/SE | L3 | 承認済(PM orchestrator / 2026-06-14 / L3-L4 carry) |
| CARRY-G2-028 | P2 | `§8.13 safe_apply_state`（既存テーマ=preview-only）の AGENT NEO 以外扱い | `docs/design/L2-design.md` §8.13 | L3:詳細設計 | 既存テーマ側の扱い例外条件と運用制約を補足 | TL/SE | L3 | 承認済(PM orchestrator / 2026-06-14 / L3-L4 carry) |
