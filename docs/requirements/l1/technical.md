---
layer: L1
sub_doc: technical
status: confirmed_input
pair_artifact: docs/test-design/l12-operational-value-test-design.md
authority: docs/requirements/authority.md
---

# L1 Technical Requirements

| ID | 技術境界 |
| --- | --- |
| WT-TRL1-01 | WordPress FSE / theme.json v3 / Block API / PHP >= 8.1 / WP 7.1 系。子テーマは層 1（尺度）を再定義しない |
| WT-TRL1-02 | 契約正本は JSON: theme.json、config/*.json（fail-fast schema 検証）、schema/*.json、openapi.yaml。面・部品・変種の追加は JSON 宣言を伴う |
| WT-TRL1-03 | エージェント接点は自前 REST 名前空間 + MCP（abilities）+ WP-CLI。WP コア名前空間へ相乗りしない |
| WT-TRL1-04 | AI 判定ロジックはテーマ・プラグインの外（HELIX 側）。boundary guard を維持する |
| WT-TRL1-05 | 実機ゲートはローカル docker WP 7.1。実運用サイトは read-only。接続情報は環境変数 |
| WT-TRL1-06 | 実証記録は本リポ内で完結（参照元 commit・証跡・ゲート結果）。他プロダクトへの参照・依存・書き込みを持たない |

PoC で成立した経路は `docs/poc/wt-poc-inventory.json` へ digest 束縛する。PoC 未検証の一般化は行わない。
