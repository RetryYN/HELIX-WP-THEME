# L3 実装工程表

> G3 通過条件を満たすための WBS / 依存 / feature flag / rollback / L4 Sprint 接続を固定する。

## 0. 前提

| 項目 | 値 |
|------|----|
| plan_id | PLAN-XXX |
| 対象機能 / scope | |
| API Freeze | YYYY-MM-DD / N/A |
| Schema Freeze | YYYY-MM-DD / N/A |
| 対象リリース | |
| 作成日 | YYYY-MM-DD |
| owner | |

## 1. Gate 前提

| 成果物 | パス | 状態 | 備考 |
|--------|------|------|------|
| D-REQ-F | docs/features/.../D-REQ-F/ | pending / ready / N/A | |
| D-REQ-NF | docs/features/.../D-REQ-NF/ | pending / ready / N/A | |
| D-ACC | docs/features/.../D-ACC/ | pending / ready / N/A | |
| D-API | docs/features/.../D-API/ | pending / ready / N/A | |
| D-CONTRACT | docs/features/.../D-CONTRACT/ | pending / ready / N/A | |
| D-DB | docs/features/.../D-DB/ | pending / ready / N/A | |
| D-TEST | docs/features/.../D-TEST/ | pending / ready / N/A | |

## 2. WBS

| WBS ID | タスク | 担当 role | 依存 | 期間 | 環境 | L4 Sprint | HELIX command / delegation | feature flag | rollback | 受入条件 |
|--------|--------|-----------|------|------|------|-----------|----------------------------|--------------|----------|----------|
| WBS-001 | 影響範囲調査 | tl | N/A | 0.5d | local | .1a | `helix code find`; `helix codex --role legacy --read-only` | N/A | N/A | 影響ファイルと既存テストが列挙済み |
| WBS-002 | 変更計画固定 | tl/se | WBS-001 | 0.5d | local | .1b | `helix plan status`; `helix task status`; `helix codex --role tl --read-only` | N/A | N/A | 実装順・依存・テスト方針が確定 |
| WBS-003 | 最小実装 | se/pg | WBS-002 | 1.0d | dev | .2 | `helix codex --role se`; `helix review --uncommitted` | ff_<domain>_<feature> | flag off | 主要 happy path が通る |
| WBS-004 | 安全性・例外処理 | se/security | WBS-003 | 0.5d | dev | .3 | `helix codex --role security`; `helix review --uncommitted` | ff_<domain>_<feature> | flag off | 異常系・権限・入力検証が通る |
| WBS-005 | 検証固定 | qa | WBS-004 | 0.5d | local/ci | .4 | `helix codex --role qa`; project tests | N/A | test baseline restore | unit/integration が通る |
| WBS-006 | 仕上げ・docs sync | docs/tl | WBS-005 | 0.5d | local | .5 | `helix codex --role docs`; `helix review --uncommitted` | N/A | revert docs patch | docs と gate evidence が同期済み |

## 3. L4 Sprint 接続

| Sprint | 目的 | 対象 WBS | 完了条件 |
|--------|------|----------|----------|
| .1a | コード調査 | WBS-001 | 影響範囲、既存テスト、依存が明確 |
| .1b | 変更計画 | WBS-002 | 実装順、担当、受入条件が明確 |
| .2 | 骨格実装 | WBS-003 | 最小動作と flag 配下実装が完了 |
| .3 | 強化実装 | WBS-004 | セキュリティ、互換性、例外処理が完了 |
| .4 | 検証固定 | WBS-005 | テストと合否基準が固定 |
| .5 | 仕上げ | WBS-006 | review、docs、残課題管理が完了 |

## 4. クリティカルパス

```text
WBS-001 -> WBS-002 -> WBS-003 -> WBS-004 -> WBS-005 -> WBS-006
```

## 5. feature flag 定義

| flag | default | scope | rollout | owner | metrics | cleanup deadline |
|------|---------|-------|---------|-------|---------|------------------|
| ff_<domain>_<feature> | false | all / tenant / user | internal -> beta -> all | | error_rate, latency_p95 | YYYY-MM-DD |

flag 不要の場合も、WBS の `feature flag` 列に `N/A` を明記する。

## 6. Rollback Plan

### 発火条件

- エラーレートが既存比で悪化:
- p95 latency が既存比で悪化:
- Sev1 / Sev2:
- 契約テスト失敗:

### 手順

1. feature flag を OFF。
2. 新規 routing / job / UI entrypoint を旧経路へ戻す。
3. DB 変更がある場合は down 可否とデータ整合方針に従う。
4. smoke test と contract test を再実行する。
5. 監視値が復帰したことを確認する。

### 体制

| 役割 | 担当 |
|------|------|
| 実行者 | |
| 承認者 | |
| 連絡先 | |

## 7. リスクと緩和

| リスク | 影響 | 緩和策 | owner | 期限 |
|--------|------|--------|-------|------|
| | high / medium / low | | | |

## 8. G3 チェック

- [ ] 全 WBS に担当 role、依存、期間、環境がある。
- [ ] 全 WBS に L4 Sprint がある。
- [ ] 全 WBS に HELIX command / delegation がある。
- [ ] 全 WBS に feature flag または `N/A` がある。
- [ ] 全 WBS に rollback または `N/A` がある。
- [ ] API / Schema Freeze の状態が明記されている。
- [ ] クリティカルパスと高リスク対策が明記されている。

## 9. TL 実行規律

- Codex / Claude Code はこの WBS の順序、依存、受入条件に従って実装する。
- WBS 外の変更が必要になった場合、実装を止めて工程表を更新するか、ユーザー確認へ戻る。
- ユーザーへ実装計画を提示した場合、明示承認があるまで編集へ進まない。
- 各 WBS 完了時は、実行した HELIX command / delegation とテスト証跡を final または gate evidence に残す。

---

## Appendix A: FSE カスタマイズ余地 工程表（FC-001〜FC-008）

> 追記: 2026-06-26。正本: `fse-customization-design-spec.md §5 末尾工程表`。
> 対象要件: REQ-F-045 / REQ-F-046 / REQ-NF-026 / ADR-028。
> **実装状況**: FC-001〜FC-008 は HEAD=e5b7e24 で L4 実装完了済み。本 Appendix は設計根拠・工程記録として残す。

### A.0 前提

| 項目 | 値 |
|------|-----|
| plan_id | FSE-CUSTOM-2026-06 |
| 対象機能 / scope | FC-001〜FC-008（FSE カスタマイズ余地） |
| API Freeze | N/A（theme.json / HTML 変更のみ） |
| Schema Freeze | N/A |
| 対象リリース | G5 接続 |
| 作成日 | 2026-06-26 |
| owner | fe-style / fe-component / docs |

### A.1 Gate 前提

| 成果物 | パス | 状態 |
|--------|------|------|
| D-REQ-F（REQ-F-045） | docs/features/fse-customization/D-REQ-F/ | L1 要件として正本に記載済み |
| D-REQ-NF（REQ-NF-026） | docs/features/fse-customization/D-REQ-NF/ | L1 要件として正本に記載済み |
| D-ACC | docs/features/fse-customization/D-ACC/ | AC1〜AC7 を L1 受入条件として定義済み |
| D-API | N/A | テーマ内変更のみ（REST API 変更なし） |
| D-CONTRACT | N/A | ADR-028 で契約確定 |
| D-DB | N/A | DB 変更なし |
| D-TEST | docs/test-plan/L3-test-plan.md | 既存テスト基盤（unit 47 / security 48）を継承 |

### A.2 WBS

| WBS ID | タスク | FC | 担当 role | 依存 | 環境 | L4 Sprint | feature flag | rollback | 受入条件 |
|--------|--------|----|-----------|------|------|-----------|--------------|----------|----------|
| FSE-001 | theme.json 追記（templateParts / customFontFamily） | FC-003 | fe-style | N/A | dev | .2 | N/A | git revert FC-003 変更 | theme.json が parse エラーなし / templateParts に post-header・post-footer が登録されている |
| FSE-002 | styles/light.json 新規作成 | FC-001 | fe-style | FSE-001 | dev | .2 | N/A | git rm styles/light.json | サイトエディタの「スタイル」に「ライト（標準）」が表示される |
| FSE-003 | styles/dark.json 新規作成 + axe 実測確定 | FC-002 | fe-style | FSE-001 | dev | .2 | N/A | git rm styles/dark.json | サイトエディタに「ダーク」が表示される / axe critical/serious=0 |
| FSE-004 | parts/post-header.html 新規作成 | FC-004 | fe-component | FSE-001 | dev | .3 | N/A | git rm parts/post-header.html | エディタで post-header part が選択可能 / breadcrumb・title・meta が含まれる |
| FSE-005 | parts/post-footer.html 新規作成 | FC-005 | fe-component | FSE-001 | dev | .3 | N/A | git rm parts/post-footer.html | エディタで post-footer part が選択可能 / tags・share・cta・author・related・nav が含まれる |
| FSE-006 | single.html を part 参照化 | FC-006 | fe-component | FSE-004, FSE-005 | dev | .3 | N/A | git revert FSE-006 変更 | 表示結果が改修前と同等（AC4） |
| FSE-007 | docs / README: 業種別複製手順・カスタマイズ方針 | FC-007 | docs | FSE-002, FSE-003 | local | .5 | N/A | git revert FSE-007 変更 | 手順に従い variation が作成できる（AC3） |
| FSE-008 | check-theme-quality.sh: synced 不在チェック（任意） | FC-008 | qa | FSE-006 | local/ci | .5 | N/A | スクリプト変更を revert | patterns/ 内に Synced: yes / synced 参照がない（WARN 可） |
| FSE-009 | 全体検証（check-theme-quality / unit / security） | — | qa | FSE-001〜FSE-008 | local/ci | .5 | N/A | 各 FC のロールバック | `bash bin/check-theme-quality.sh` PASS / unit 47 緑 / security 48 緑（AC7） |

### A.3 L4 Sprint 接続

| Sprint | 目的 | 対象 WBS |
|--------|------|----------|
| .2 | スタイル基盤（theme.json + Variations） | FSE-001, FSE-002, FSE-003 |
| .3 | テンプレートパーツ実装 | FSE-004, FSE-005, FSE-006 |
| .5 | 仕上げ（docs + 検証） | FSE-007, FSE-008, FSE-009 |

### A.4 クリティカルパス

```text
FSE-001(theme.json) → FSE-002(light) → FSE-003(dark) → FSE-007(docs) → FSE-009(検証)
                                 ↘ FSE-004(post-header) ↘
FSE-001 ─────────────────────────→ FSE-005(post-footer) → FSE-006(single.html) → FSE-009
```

Wave1（fe-style）: FSE-001 → FSE-002 → FSE-003  
Wave2（fe-component）: FSE-004 → FSE-005 → FSE-006（Wave1 完了不要・並列可。ただし逐次 dispatch で git 事故回避）  
Wave3: FSE-007 + FSE-008 + FSE-009（両 Wave 完了後）

### A.5 feature flag 定義

| flag | 値 |
|------|-----|
| 全 FC | N/A（theme ファイル追加・変更のみ。feature flag 対象外） |

### A.6 Rollback Plan

**発火条件**:
- `bash bin/check-theme-quality.sh` が FAIL になる
- unit または security テスト数が減少する
- single.html の表示崩れ（AC4 違反）
- dark variation の axe critical/serious が 1 以上になる

**手順**:
1. 各 WBS の rollback 列に従い `git revert` または `git rm` を実行する。
2. `bash bin/check-theme-quality.sh` を再実行して PASS を確認する。
3. unit / security テストを再実行して緑を確認する。

### A.7 リスクと緩和

| リスク | 影響 | 緩和策 |
|--------|------|--------|
| dark axe 実測で AA 未達 | 中 | accent-aa / button text の組み合わせを複数案用意し実測で確定 |
| post-footer の PHP 動的パターンが part 参照で動作しない | 高 | ラッパーpart 方式（pattern 参照をそのまま .html に記述）で対応済み。WP 起動で動作確認必須 |
| single.html 改修で表示崩れ | 高 | AC4 の目視確認を FSE-009 で必須化。改修前後のスクリーンショット比較推奨 |

### A.8 G3 チェック（FSE スコープ）

- [x] 全 WBS に担当 role、依存、期間（Sprint）、環境がある。
- [x] 全 WBS に L4 Sprint がある。
- [x] 全 WBS に feature flag または `N/A` がある。
- [x] 全 WBS に rollback または `N/A` がある。
- [x] API / Schema Freeze の状態が明記されている（N/A）。
- [x] クリティカルパスと高リスク対策が明記されている。
- [x] 実装完了済みであることを注記している（HEAD=e5b7e24）。
