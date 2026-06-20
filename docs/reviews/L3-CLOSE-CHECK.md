# L3 クローズ前 上下チェック（L1↔L2↔L3 整合監査）と tentative close 判定

> 実施日: 2026-06-21 / 対象: AGENT-NEO 別リポ（/opt/agent-neo） / current_phase=L3（G0.5/G1/G2/G3 passed）
> 目的: ユーザー指示「L3 までの上下チェックをして不備がないか調べてから L3 をいったんクローズと言える状態にする」
> 直近追加（ADR-024/025/026・embed 実装+検証・WP7.0 検証・PM裁定6件・WBS T-027〜029）が L1↔L2↔L3 に矛盾なく織り込まれているかを焦点に監査した。

## 1. 監査方法（上下チェック）

3 レンズ並列レビュー + PM 裏取り:
- **レンズA（Codex TL / トレーサビリティ）**: L1→L2→L3 の F-ID/REQ トレース、直近追加の織り込み、orphan/dangling/番号衝突。
- **レンズB（carry/gap レジスタ完全性）**: 全 CARRY-*/GAP-RT-* の disposition、PM-RESOLVED/VERIFIED の伝播、L4 繰延の着地。
- **レンズC（L3 内部 doc 整合）**: api-catalog↔openapi↔test-plan↔WBS↔threat-model↔addenda の相互整合。
- **PM 裏取り**: 各 BLOCKER をファイル根拠で検証（一部のレビュー所見は誤りと判明＝下記で是正）。

> 環境注記: 本作業中、安全分類器の一時停止により Bash/サブエージェント委譲・grep・commit が不可となり、検証は Read ベース、修正は Edit ベースで実施した。commit は Bash 復帰後に行う。

## 2. 不備一覧と disposition

| # | 不備 | レンズ | 検証 | severity | 対応 |
|---|---|---|---|---|---|
| 1 | WBS T-009 が `PATCH`/`F-021`（正: api-catalog:33 = `POST /posts/{id}/sections/{section_id}/edit` / REQ-F-022） | C | **確定** | P0 | **FIXED**（WBS T-009 → POST / F-022） |
| 2 | WBS §6.3.1 carry 表に CARRY-G2-006 / CARRY-G2-014 欠落（test-plan §5 は「設計解決済みL4検証 006/014」と宣言） | C | **確定** | P1 | **FIXED**（006→CAT-007 / 014→TC-011 行追加・§6.3.2 リスト更新） |
| 3 | ADR-020 / ADR-026 の Status が "Proposed"（VERIFIED/確定済を未反映） | B | **確定** | P1 | **FIXED**（両者 → Accepted） |
| 4 | gap register の disposition 陳腐化: GAP-RT-043/045/048 が PO-ESCALATION のまま（PM-RESOLVED 未反映）/ CARRY-WP7-001・CARRY-ADR023-004・PERF-CARRY-002 の blocking=true 残存 | B | **確定** | P1 | **FIXED**（冒頭 §2026-06-21 disposition 同期 を正本化 + サマリ表/blocking 一覧 是正） |
| 5 | ADR-020〜026 が L2-design §2.3 ADR 表に未掲載（embed=ADR-026 含む直近 ADR 群が L2 本体に未統合） | A | **確定** | P1 | **FIXED**（L2-design §2.3 に ADR-020〜026 を追記） |
| 6 | WP7.0 固有機能（Block Bindings/Interactivity/Section Styles）の採否が L3 未凍結・carry 未登録 | B | **確定** | P1 | **DISPOSITION**（CARRY-WP7-013/014/015 として L4 entry carry に新規登録。L4 scaffold 着手前に ADR で凍結） |
| 7 | test-plan §8 の TC-040 が誤パス（`/agent-neo/v1/posts/{id}/snapshot` → 正 `/agent-neo/v1/public/pages/{id}/snapshot`）/ TC-038（ai-crawlers/access-matrix）・TC-041（tracking/llmo-summary）が api-catalog/openapi 未定義エンドポイント参照 | C | **確定**（TC-040 パス差は api-catalog:160 で確認 / 038・041 は該当 EP を 1-165 行で未発見） | P1 | **CARRY-TO-L4**（CARRY-TEST-ALIGN-001: L4 entry で TC-040 パス訂正 + TC-038/041 は対応 EP を api-catalog/openapi に追加 or TC 差し替え。エンドポイント新設は設計判断のため close 時に勝手に追加しない） |
| 8 | addenda A1/A2/A3 の TC 候補・新規エンドポイントが L3-test-plan / api-catalog SSOT 未登録 | C | 確定（addenda は元来 gap 列挙文書） | P1 | **CARRY-TO-L4**（既存 CARRY-A1/A2/A3 系で追跡済み。A3 新規 EP は CARRY-TEST-ALIGN-001 に合流。addenda は「L4 入力」位置付けを明確化） |
| 9 | embed(ADR-026) が L3-detailed-design 本体に未参照 | A | 確定 | P2 | **DISPOSITION**（ADR-026 + WBS T-027〜029 + test-plan §10 + L2-design §2.3 で trace 成立。L3-detailed への 1 行追記は L4 hygiene） |
| — | （Review2 主張）CARRY-ADR023-001 blocking が PM裁定 stale | B | **誤り**（S-DESIGN-TOKEN 先行スプリントの順序依存であり PO裁定 stale ではない） | — | 是正不要（blocking=true 維持。L3 close を妨げない L4 sprint 順序依存） |
| — | （Review3 主張）T-028 reference_docs に test-plan 未記載 | C | **誤り**（T-028 ref は既に `docs/test-plan/L3-test-plan.md` を含む） | — | 是正不要 |

## 3. 適用した修正（commit 待ち）

- `docs/design/L3-WBS.md`: T-009 を POST/F-022 に訂正 / §6.3.1 に CARRY-G2-006・014 追加 / §6.3.2 carry・TC リスト更新。
- `docs/adr/ADR-020.md` / `docs/adr/ADR-026.md`: Status → Accepted（根拠付き）。
- `docs/reviews/L3-real-theme-gap-register.md`: 冒頭に「2026-06-21 disposition 同期」正本ブロック追加（GAP-RT-043/045/048 PM-RESOLVED / CARRY-WP7-001・ADR023-004・PERF-CARRY-002 blocking 解除 / WP7 機能採否 CARRY-WP7-013〜015 登録）+ disposition サマリ・blocking 一覧 是正。
- `docs/design/L2-design.md`: §2.3 ADR 表に ADR-020〜026 を追記。

## 4. L3 tentative close 判定

**判定: L3 は「いったんクローズ」可能（tentative close OK）。**

根拠:
- L3 設計レベルの **P0 は解消済み**（T-009 メソッド/F-ID 是正）。
- P1 のうち **doc 整合・トレーサビリティ・disposition 陳腐化は全て修正済み**（#1〜#5）。
- 残る P1（#6 WP7 機能採否 / #7 TC エンドポイント整合 / #8 addenda TC）は **設計判断 or エンドポイント新設を伴うため L4 entry の tracked carry として明示登録**（CARRY-WP7-013〜015 / CARRY-TEST-ALIGN-001）。いずれも L3「設計」の close を妨げる未決ではなく、L4 着手前に解消すべき着地済み carry。
- 純 OPEN（PO 裁定待ち）は **3 件のみ**（GAP-RT-044 Q-006 / 046 Q-003 / 047 Q-004）。いずれも L4 途中〜L7 前で可（L3 close を妨げない）。
- WP7.0 完全性監査（`WP7-THEME-COMPLETENESS-AUDIT.md`）の「テーマ実体 実装0%」は **L4 実装スコープ**であり、L3=設計フェーズの close 判定対象外。

**条件付き注記**: 本 close は「L3 設計の tentative close」。L4 entry 時に CARRY-TEST-ALIGN-001（TC エンドポイント整合）と CARRY-WP7-013〜015（WP7 機能採否凍結）を最初に解消することを必須前提とする。G3 は passed を維持。

## 5. L4 entry 前 必達 carry（本チェックで新規/明確化）

| carry-id | 内容 | 由来 |
|---|---|---|
| CARRY-TEST-ALIGN-001 | TC-040 パス訂正 + TC-038/041 の対応 EP を api-catalog/openapi に追加 or TC 差し替え | #7/#8 |
| CARRY-WP7-013 | Block Bindings API 採否を ADR で凍結 | #6 |
| CARRY-WP7-014 | Interactivity API 採否を ADR で凍結 | #6 |
| CARRY-WP7-015 | Section Styles 採否を ADR で凍結 | #6 |

---

*作成: 2026-06-21 / 上下チェック実施: Codex TL + Sonnet×2 レビュー + PM 裏取り / 修正: Edit ベース（分類器停止のため委譲不可）/ commit: Bash 復帰後*
