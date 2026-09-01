---
layer: L1
sub_doc: business
status: confirmed_input
pair_artifact: docs/test-design/l12-operational-value-test-design.md
authority: docs/requirements/authority.md
---

# L1 Business Requirements

| ID | 要求 | 成功判定 |
| --- | --- | --- |
| WT-BR-01 | テーマは運用知見・開発知見をためる場として機能し、実証済みパターンだけを GRAPHIX-NEO へ一方向に取り込める | 取り込み台帳の各行が参照元 commit・証跡・ゲート結果を持ち、逆方向の取り込みが 0 件 |
| WT-BR-02 | 人と AI の両方が構造・値を自由に編集でき、破壊域だけが止まる | 構造変更で権限エラーが 0 件、破壊域の変更は保存前に停止し、安全域の変更は警告なしで通る |
| WT-BR-03 | 既存サイト（テーマA / テーマB 相当）から移行しても収益と回遊の置き場所を失わない | 監査で特定した欠落面 D-01〜D-07 のすべてに受け皿パーツまたはパターンがある |
| WT-BR-04 | テーマ・プラグインに AI 判定ロジックを持ち込まず、AI 判定は HELIX-WP-HARNESS 側が担う | theme / plugin に model 呼び出し・判定ロジック・外部 AI SDK の import が 0 件 |
| WT-BR-05 | 変更は静的検査と実機ゲートの両方で崩れないことを証跡で示す | 静的ゲート FAIL=0 と実機ゲート invalid=0 が同一 HEAD に束縛された証跡として残る |

## Actor と scope

- actor: PO、編集者（非 AI ユーザー、Site Editor で編集）、運用者、移行者、AI エージェント（HELIX-WP-HARNESS 経由）、決定論ゲート、GRAPHIX-NEO（取り込み側）
- product boundary: 本テーマは商用製品ではなく運用知見・開発知見をためる場（L0 改定ドラフト）。製品化は GRAPHIX-NEO が担う
- release boundary: 実用試験。実運用サイトへの配備は PO 承認ごと
- non-goal: AI 判定ロジックの内蔵、単体販売、第三者テーマとの完全互換、課金・会員機能（案 A: 移行プラグインへ委譲、WT-Q-SCOPE-01）

## 未決事項

構造編集の適用範囲（WT-Q-STRUCT-01/02）、破壊域の境界値（WT-Q-VALUE-01）、共有パーツ切替の開放（WT-Q-PARTS-01）、
ゾーン語彙（WT-Q-ZONE-01）、課金スコープ（WT-Q-SCOPE-01）、ADR-028 の再検討（WT-Q-ADR-01）は L2 candidate として owner と
再入場条件を保持し、freeze へ混入させない。
