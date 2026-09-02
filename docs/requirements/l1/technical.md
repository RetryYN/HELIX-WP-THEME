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
| WT-TRL1-07 | 構成はテーマ + 薄いプラグイン（PO 確認 2026-09-02）。運用中の AI はテーマファイルを触らず、WP が DB に持つ「サイトの選択」だけをプラグインの API 経由で変える。プラグインは選択を安全に DB へ入れる薄い層（manifest・dry-run・停止・rollback・ログ）とデータ（設定 JSON・投稿メタ・section ID・ゾーン・再利用パーツ・計測・ログ）だけを持ち、表示はテーマ、判定は HELIX 側。移行機能は別プラグイン。第三者プラグインとの出力重複は設定で制御し、全プラグイン互換は非対象 |

PoC で成立した経路は `docs/poc/wt-poc-inventory.json` へ digest 束縛する。PoC 未検証の一般化は行わない。
