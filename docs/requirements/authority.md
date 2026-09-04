# WT 要求 authority

- initiative_id: `WT-AGENT-VARIETY`
- canonical V-model: `L1-L12`
- development style candidate: `V_DESIGN_SCRUM_IMPLEMENTATION`
- case-driven model: `DISCOVERY_POC`（PoC 証跡は入力、S4 前に canonical 化しない）
- lifecycle: `accepted`（G1 承認・G2 合意済み。compile は RDJ-FR-007 の 2 iteration 条件で backflow、iteration 2 で再実行）
- freeze: **G1 承認・G2 合意（PO 2026-09-05、WT-EVT-0232）。G3 未実施**
- authority owner: PO
- updated: 2026-09-05

## 起点

`docs/planning/L0-agent-controlled-variety.md`（PO 指示 2026-09-02 の書き起こし）。本テーマの JSON 中間言語による機械可読性を維持したまま、
テーマA / B が示す一般想定水準の面・語彙・引き出しを取り込み、エージェント制御下でバリエーションを最大化する。
要求は拡大の提案として並べ、PO は「X ができる。採用するか」だけを判断する。旧要求との比較は持たない。

## 正本境界

| 層 | 正本 | 状態 | 次の昇格条件 |
| --- | --- | --- | --- |
| L1 | `docs/requirements/l1/` の 5 sub-doc | G1 approved（PO 2026-09-05） | 変更は改定として L2 問い → 再 compile |
| L2 | `docs/requirements/discovery/events.jsonl` と同イベントから生成する `candidate-projection.json` | non-canonical candidate、agreement WT-AGREE-01 記録済み | 問い 0 件（現在 iteration 2 の未決 2 件。総数 81 件、採用 77 件、reject 2 件: WT-Q-AUDIT-02 / WT-Q-LOOK-04。WT-Q-DIRECT-08 は対象外境界の補助確認）と同数。prototype reaction、PO agreement |
| L3 | `docs/requirements/l3/requirements-ir.json`（承認用ビュー: `l3/g3-approval-summary.md`） | non-canonical precompile inventory（revision / owner / semantic digest 付与済み、compile は backflow_required、iteration 2 の PO 確認中） | iteration 2（G3 承認用要件要約の PO 確認）で優先度安定を判定 → compile → `specified` → G3 承認で `frozen` |

## 入力資産と扱い

| 資産 | 扱い |
| --- | --- |
| `docs/planning/L0-agent-controlled-variety.md` | L0 企画。要求の起点 |
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
