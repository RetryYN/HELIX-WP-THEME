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
| WT-BR-01 | 機械可読性を維持する。面・部品・値・変種を追加しても、すべてが JSON 宣言（theme.json / config / schema / openapi）から列挙できる | capability manifest の列挙率 100%。PHP にしか存在する面・部品が 0 |
| WT-BR-02 | テーマA / B が示す一般想定水準に到達する。12 種別の必須パーツ充足に加え、未整備 16 項目に受け皿を持つ | `web-patterns` 12 種別で本テーマだけに欠ける必須パーツ 0。未整備 16 項目の受け皿有無が台帳化 |
| WT-BR-03 | エージェント制御下でバリエーションを生成できる。面・部品・値・変種・テンプレの選択が JSON 経由で完結し、破壊域へ落ちない | AI 経路の変更のうち JSON 外の操作を要した件数 0。破壊域停止の誤警告 0 |
| WT-BR-04 | 実証済みパターンを GRAPHIX-NEO へ一方向で渡せる | 台帳行の証跡付き率 100%。逆方向取り込み 0 |
| WT-BR-05 | AI 判定ロジックをテーマ・プラグインに持ち込まない | 静的検出（判定ロジック・モデル呼び出し）0 |

## Actor と scope

- actor: PO、編集者（Site Editor / Block Editor で編集する人）、運用者、移行者、AI エージェント（HELIX-WP-HARNESS 経由）、決定論ゲート、GRAPHIX-NEO（取り込み側）
- product boundary: 本テーマは「機械可読性を保ったままエージェント制御でバリエーションを最大化する」ための知見蓄積の場（`docs/planning/L0-agent-controlled-variety.md`）。製品化は GRAPHIX-NEO が担う
- release boundary: 実用試験。実運用サイトへの配備は PO 承認ごと
- non-goal: 課金・会員機能（移行プラグインへ委譲）、第三者テーマの是正、外部デザインツール取り込み経路、AI 判定ロジックのテーマ内実装

## PO への問い

WT-Q-* は総数 15 件（採用済み 8: WT-Q-ZONE-01, WT-Q-PARTS-01, WT-Q-VOCAB-01, WT-Q-VOCAB-02, WT-Q-VOCAB-03, WT-Q-LOOK-01, WT-Q-LOOK-02, WT-Q-META-01。未決 7 件は `docs/requirements/discovery/candidate-projection.json` の `unresolved`）。いずれも「X ができる（証跡あり）。採用するか」の形で、
方式・配置先・境界値の詳細は問わない（PoC 証跡で決める）。
