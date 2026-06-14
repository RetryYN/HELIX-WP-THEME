# L3 詳細設計 / G3 ミニレトロ（2026-06-14）

> Drive=agent / Size=L / branch `design/l2-g2-freeze`。G2 凍結（2026-06-14）からの継続。

## 成果（L3 deliverable）
| deliverable | 種別 | 概要 |
|---|---|---|
| `docs/design/api-catalog.md` | ツール契約 | 55 endpoint 凍結。catalog-update を §17 正本へ完全ミラー |
| `docs/api/openapi.yaml` | ツール契約（機械可読） | OpenAPI 3.1 / 54 operation / Error enum 9 値 |
| `docs/design/L3-detailed-design.md` | 詳細設計 | D-API（A-001〜009）/ storage / 処理フロー / §5・§6 はポインタ |
| `docs/test-plan/L3-test-plan.md` | 統合テスト設計 | 35 TC + 14 CAT（contract test 含む） |
| `docs/design/L3-WBS.md` | 工程表 | Phase1 ローンチ 25 タスク + クリティカルパス |
| `docs/design/L2-design.md` / `threat-model.md` / `data-model-ids.md` | carry 解決 | 該当 §へ反映 |
| `docs/reviews/G2-carry-register.md` | carry 追跡 | 28 件に L3解決状態列付与（RESOLVED-IN-L3=22 / CARRY-TO-L4=6） |

## ゲート
- **G3 PASS**（2026-06-14）。readiness ready:true（has_api=openapi.yaml / has_plan=test-plan+WBS）。
- carry 28 件: L3 で 22 件解決、6 件（007/009/013/017/025/026）を L4 実装 carry として受入条件明文化。

## プロセス（多エージェント・オーケストレーション）
- drafting: Codex docs drafter をファイル別に並列（WS-A〜F + R1/R2 fixer）。
- 検証: adversarial Workflow 2 巡（5レンズ→finding毎独立検証）。
  - 1 巡目: 34 findings 全確認 → R1 修正。
  - 2 巡目（回帰＋完全性 critic）: 12 findings → R2 修正。

## 学び（重要）
1. **§17 正本の読み逃し**: 当初 catalog-update の backoff を §17.4（初回5s / Connectors 全般）で固定したが、正本は **§17.11（AGENT-NEO push 専用 / 初回1s・2^n・429含む）**。adversarial 検証が捕捉し全 deliverable を 1s/429 へ訂正。LLM provider 契約は「最も限定的・専用の節」を正本とする。
2. **機械検証の併用**: openapi.yaml の YAML 構文崩れ（未引用 description 内コロン）は LLM レビューでは捕捉できず、`python3 yaml.safe_load` で検出。契約ファイルは必ず parser で検証。
3. **claimed-but-missing の検出価値**: drafter が「解決した」と報告した carry のうち複数が実ファイル未反映だった。carry register の evidence(file:line) を実 Read で監査する lens が有効。

## 別リポ申し送り（automation SEO / cross-repo）
- `D-PLUGIN-CONTRACT §17.4`（初回5s/600s/4xx一律除外）が §17.11（push専用/1s/429含む）と未整合。AGENT-NEO 側 deliverable は §17.11 で正。automation SEO 側で §17.4 本文に §17.11 への誘導を追記すべき（cross-repo 編集禁止のため本タスクでは未修正）。詳細は `G2-carry-register.md` 末尾。

## 次フェーズ
- L4 実装着手前に G4 carry（007/009/013/017/025/026）の受入条件を確認。Phase1 ローンチ 25 タスク / クリティカルパス `T-001→T-004→T-006→T-007→T-010→T-011→T-014→T-015→T-018→T-024`。
