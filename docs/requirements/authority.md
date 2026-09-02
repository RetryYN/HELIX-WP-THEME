# WT 要求 authority

- initiative_id: `WT-STRUCTURE-FREEDOM`
- canonical V-model: `L1-L12`
- development style candidate: `V_DESIGN_SCRUM_IMPLEMENTATION`
- case-driven model: `DISCOVERY_POC`（PoC 証跡は入力、S4 前に canonical 化しない）
- lifecycle: `elicited`
- freeze: **未実施**
- authority owner: PO
- updated: 2026-09-02

## 正本境界

| 層 | 正本 | 状態 | 次の昇格条件 |
| --- | --- | --- | --- |
| L1 | `docs/requirements/l1/` の 5 sub-doc | confirmed input（PO 指示 2026-09-02 による再整理） | G0.5 判断（WT-Q-STRUCT-01/02、WT-Q-VALUE-01）と G1 content/pair/trace の PO 承認 |
| L2 | `docs/requirements/discovery/events.jsonl` と同イベントから生成する `candidate-projection.json` | non-canonical candidate | 未決 14 件（WT-Q-*、`candidate-projection.json` の `unresolved` と同数）の解消、prototype reaction、PO agreement |
| L3 | `docs/requirements/l3/requirements-ir.json` | non-canonical precompile inventory | L2 agreement 後の compile、L10 oracle との pair、G3 PO/TL 承認 |

## 入力資産と扱い

| 資産 | 扱い |
| --- | --- |
| `docs/planning/drafts/L0-ai-editing-freedom-draft.md`（L0 改定ドラフト、PR #48） | 新企画書。構造自由・破壊域停止・知見をためる場。G0.5 判断待ち |
| 統合層 `docs/plans/2026-08-28-wp-theme-and-graphix-neo-plan.md` | WP-THEME と GRAPHIX-NEO の役割分担。取り込みは一方向 |
| 旧 L0-planning.md（PO 判断 2026-09-02 で削除、git 履歴 2904aea 以前） | 4 原理・ページ型別予算は WT-NFRL1-01 等へ継承済み。価格・単体販売・成功指標は失効（ADR-024） |
| 旧 L1-requirements.md・nsrm-*・L2〜L5 設計・features・test-plan・解析レポート（削除済み、同上） | 継承: REQ-F-045 / 046 / 025、ADR-024。衝突記録: REQ-F-016 / 037（WT-Q-STRUCT-01/02）。継承も棄却記録もない項目は WT-Q-CARRY-01 で PO 判断 |
| `docs/research/2026-08-26-theme-structure-audit/` | 調査証跡（INV-01〜17、欠落面 D-01〜07）。要求候補と暗黙要件の入力 |
| `docs/research/2026-08-2[789]-*`, `2026-08-31-*` | PoC 証跡。`docs/poc/wt-poc-inventory.json` に digest 束縛 |
| `themes/` `plugins/` `bin/` | 旧実装。能力棚卸し（パターン 71、スタイル 9、parts 5、templates 10、ゲート 6+実機）の根拠 |
| ADR-001〜030 | 旧時代の判断記録。現行拘束は本正本経由の継承のみ（ADR-024 継承、ADR-028 は WT-Q-ADR-01 で再検討） |
| `docs/design/api-catalog.md` | REST 契約の現行参照（bin/check-impl-coverage.sh が読む）。文書内の旧 L3 凍結表示は拘束ではない |

## トレーサビリティ

`L1 BR/FRL1/NFRL1/TRL1/SCR → L2 candidate/surface → L3 WT-* → WT-AC-* → WT-AT-*` を stable ID で接続する。
孤児、重複 ID、存在しない参照、受入条件のない要求は L3 進行を拒否する（`npm run requirements:validate`）。

## HELIX 本体との接続

HELIX 本体（pin 19d0bffd）の機械検査は `docs/design/harness/` と `docs/test-design/harness/` の互換 projection を読む。
projection は `source_sha256` で本正本へ束縛され、単独で正本化しない。`helix l1-l2 gap-check` と `helix vmodel lint` を green に保つ。

## PoC の扱い

PoC は実現可能性を裏づける入力であり、要求や人間合意の代替ではない。参照可能な PoC は
`docs/poc/wt-poc-inventory.json` に HEAD、ファイル digest、採用結論、非採用・制約を固定する。
秘密情報、実運用サイトの固有名、第三者製品名はこのリポジトリへ複製しない（テーマA / テーマB、site-A / site-B の伏せ字を使う）。
