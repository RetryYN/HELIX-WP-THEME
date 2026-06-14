# L3 詳細設計 / G3 ミニレトロ（2026-06-14）

> Drive=agent / Size=L / branch `design/l2-g2-freeze`。G2 凍結（2026-06-14）からの継続。

## 成果（L3 deliverable）
| deliverable | 種別 | 概要 |
|---|---|---|
| `docs/design/api-catalog.md` | ツール契約 | 55 endpoint 凍結。catalog-update を §17 正本へ完全ミラー |
| `docs/api/openapi.yaml` | ツール契約（機械可読） | OpenAPI 3.1 / 54 operation / Error enum 16 値 |
| `docs/design/L3-detailed-design.md` | 詳細設計 | D-API（A-001〜009）/ storage / 処理フロー / §5・§6 はポインタ |
| `docs/test-plan/L3-test-plan.md` | 統合テスト設計 | **32 TC + 9 CAT**（contract test 含む、合計 41 件）※ 旧来の「35 TC + 14 CAT」は 2026-06-15 再検証で訂正 |
| `docs/design/L3-WBS.md` | 工程表 | Phase1 ローンチ 26 タスク + クリティカルパス |
| `docs/design/L2-design.md` / `threat-model.md` / `data-model-ids.md` | carry 解決 | 該当 §へ反映 |
| `docs/reviews/G2-carry-register.md` | carry 追跡 | 28 件に L3解決状態列付与（RESOLVED-IN-L3=16 / RESOLVED-IN-L2-TABLE=1（022）/ CARRY-TO-L4=11（007/009/011/012/013/015/017/021/025/026/028）） ※ 旧来の「RESOLVED-IN-L3=22 / CARRY-TO-L4=6」は 2026-06-15 再検証で訂正 |

## ゲート
- **G3 初回 PASS**（2026-06-14）。readiness ready:true（has_api=openapi.yaml / has_plan=test-plan+WBS）。
- **G3 一旦 invalidated**（2026-06-15）：L4 着手前の 59 体敵対検証で TC 水増し・carry reclassify・クリティカルパス誤記を検出。deliverable 是正を実施し G3 が一時無効化。
- **G3 再凍結（再 PASS）**（2026-06-15）：是正完了後、phase.yaml を更新し G3 を再 PASS 記録。
- carry 28 件: L3 で 16 件解決（L2-table 解消 1 件含む）、11 件（007/009/011/012/013/015/017/021/025/026/028）を L4 実装 carry として受入条件明文化。

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
- L4 実装着手前に G4 carry（007/009/011/012/013/015/017/021/025/026/028）の受入条件を確認。Phase1 ローンチ 26 タスク / クリティカルパス `T-001→T-004→T-006→T-007→T-010→T-011→T-014→T-015→T-024`（T-018 は T-017 起点の並行ブランチで本線外。旧表記の「…T-015→T-018→T-024」は 2026-06-15 再検証で訂正）。

## 2026-06-15 追記：再検証による reclassify と数値訂正

59体敵対検証（2026-06-15）により、以下の訂正を実施した。

### 数値訂正
| 項目 | 旧値 | 新値 |
|---|---|---|
| test-plan 件数 | 35 TC + 14 CAT | **32 TC + 9 CAT**（合計 41 件） |
| RESOLVED-IN-L3 | 22件 | **16件** |
| RESOLVED-IN-L2-TABLE | （なし） | **1件（022）** |
| CARRY-TO-L4 | 6件（007/009/013/017/025/026） | **11件（007/009/011/012/013/015/017/021/025/026/028）** |
| クリティカルパス | T-015→T-018→T-024 | **T-015→T-024**（T-018 は並行ブランチ） |

### reclassify 詳細（RESOLVED-IN-L3 → CARRY-TO-L4 降格）
- **011**: consent-mode/Lighthouse CI 実装は L4（TC-028 / WBS T-023）。L3 に解決成果物なし
- **012**: lp-blueprint 12セクション名称定義が未確定。L4（WBS T-021 / TC-029）で名称確定＋整合検証
- **015**: SBOM 生成は L4 実装（WBS T-025 / TC-027）。L3 timing 契約注記は残存
- **021**: enforcement は L4（WBS T-010 / TC-030）。スキーマは openapi BridgeProfileResponse で確定済み
- **028**: 既存テーマ write 例外条件は L4。重複 citation・§ラベル誤記を修正

### citation 品質改善
- data-model §R-09:506（boilerplate 行）を CARRY-G2-007/009/013/017/025 の証跡として誤用していたものを削除し、各 carry の所見テーマを実際に閉じる行（threat-model §5.3 受入条件行・test-plan carry マッピング行・data-model 仕様定義行）へ差し替えた
- 重複 citation（012の§8.3:433が2回、028の§8.13:819が2回、022の同一行を2表記）を整理・削除

### Codex 5.4 TL レビュー3巡（2026-06-15）
Codex 5.4 TL レビューを3巡実施し、P2/P3 所見を全是正した（410 GONE enum の openapi.yaml 追加 / BridgeProfileResponse エンベロープ整合 / public-ID スコープ contract 明文化 / WBS タスク件数 26 確定 / L4 carry list に 028 の受入条件補完）。
