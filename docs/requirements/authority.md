# WT 要求 authority

- initiative_id: `WT-AGENT-VARIETY`
- canonical V-model: `L1-L12`
- development style candidate: `V_DESIGN_SCRUM_IMPLEMENTATION`
- case-driven model: `DISCOVERY_POC`（PoC 証跡は入力、S4 前に canonical 化しない）
- lifecycle: `candidate_revision`（WT-AGREE-01 の G1 承認・G2 合意は event head 0231 へのもの。後続の改定・追加は未凍結。compile は backflow_required）
- freeze: **G1 承認・G2 合意（PO 2026-09-05、WT-EVT-0232）。G3 未実施**
- authority owner: PO
- updated: 2026-09-08
- 最新整理: [`current-alignment.md`](current-alignment.md)（WT-EVT-0308、132候補 / 289 AC / 132 test ID。旧123件から6件改定・9件追加。実装完了件数ではない）

## 起点

`docs/planning/L0-agent-controlled-variety.md`（PO 指示 2026-09-02 の書き起こし）。本テーマの JSON 中間言語による機械可読性を維持したまま、
テーマA / B が示す一般想定水準の面・語彙・引き出しを取り込み、エージェント制御下でバリエーションを最大化する。
要求は拡大の提案として並べる。WT-EVT-0299 / 0300 の後続指示に従い、未観察も調査対象に残し、9系統・5継承セットを上限にしない。今回の新旧差分は現行 initiative 内の revision 比較であり、旧 AGENT NEO kit の工程を復活させるものではない。

## 正本境界

| 層 | 正本 | 状態 | 次の昇格条件 |
| --- | --- | --- | --- |
| L1 | `docs/requirements/l1/` の 5 sub-doc | 9月5日承認済み基準を保持。後続改定は current-alignment.md と events / L3 候補で明示 | 最新 revision の合意時に L1 改定・pair を照合。過去承認を後続改定へ自動適用しない |
| L2 | `docs/requirements/discovery/events.jsonl` と `candidate-projection.json`、最新差分 `current-alignment.md` | フロント先行の画面プロト往復（WT-EVT-0239）。最新整理 WT-EVT-0308、過去の合意 WT-AGREE-01 は head 0231 のみ | 正式判断2件（G3 / 開発スタイル）は保留。後続のPO反応、改定範囲、優先度の合意を揃える |
| L3 | `docs/requirements/l3/requirements-ir.json`（全件ビュー `l3/g3-approval-summary.md`） | 132候補、289 AC。compile は backflow_required、G3 未実施。追加9件のP1は暫定 | pending_resolution 3件、最新revisionの合意、直近2 iterationの優先度安定 → compile → specified → G3承認で frozen |

## 入力資産と扱い

| 資産 | 扱い |
| --- | --- |
| `docs/planning/L0-agent-controlled-variety.md` | L0 企画。要求の起点 |
| `docs/requirements/current-alignment.md` / `discovery/page-type-ledger.md` | 最新PO判断・候補改定・試作証跡・残件・着手順。元7分類を保持する開かれた9系統索引 |
| 統合層 `docs/plans/2026-08-28-wp-theme-and-graphix-neo-plan.md` | WP-THEME と GRAPHIX-NEO の役割分担。一方向原則。本テーマは記録を残すだけで依存を作らない |
| `docs/research/2026-08-26-theme-structure-audit/` | 3 テーマ構造監査・RE（INV-01〜17、差分レジスタ、機構比較、統合レポート）。拡大提案の根拠 |
| `docs/research/2026-08-27-poc-browser-verification/`, `docs/research/2026-08-28-poc-conversion-and-variations/`, `docs/research/2026-08-28-poc-styles-parts-gates/`, `docs/research/2026-08-29-ge1-local/`, `docs/research/2026-08-31-poc-display-errors/` | PoC 証跡。`docs/poc/wt-poc-inventory.json` に digest 束縛 |
| `docs/design/catalog/` | パーツ図鑑・カスタマイズ性・デザイン力比較 |
| `docs/design/consistency-responsibilities.md` / `token-structure.md` / `parts-catalog.md` | 現行設計 3 文書（層 1 の所有権・尺度・パーツ一覧） |
| `themes/` `plugins/` `bin/` | 実装資産。維持する土台（JSON 契約・health・boundary guard・REST 34 / MCP / CLI・パターン 71・variation 9・ゲート 6 + 実機）の根拠 |
| ADR-001〜030 | 旧時代の判断記録。現行拘束は本正本経由の継承のみ |

## トレーサビリティ

`L1 BR / FRL1 / NFRL1 / TRL1 / SCR → L2 candidate / surface → L3 WT-* → WT-AC-* → WT-AT-*` を stable ID で接続する。
孤児、重複 ID、存在しない参照、受入条件のない要求、要求に紐づかない問いは L3 進行を拒否する（`npm run requirements:validate`）。

## HELIX 本体との接続

HELIX 本体（pin 19d0bffd）の機械検査は `docs/design/harness/` と `docs/test-design/harness/` の互換 projection を読む。
projection は `source_sha256` で本正本へ束縛され、単独で正本化しない。`helix l1-l2 gap-check` と `helix vmodel lint` を green に保つ。

## PoC の扱い

PoC は実現可能性を裏づける入力であり、要求や人間合意の代替ではない。参照可能な PoC は
`docs/poc/wt-poc-inventory.json` に HEAD、ファイル digest、採用結論、非採用・制約を固定する。
秘密情報、実運用サイトの固有名、第三者製品名はこのリポジトリへ複製しない（テーマA / テーマB、site-A / site-B の伏せ字を使う）。
