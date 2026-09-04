---
layer: L1
sub_doc: business
status: g1_approved
pair_artifact: docs/test-design/l12-operational-value-test-design.md
authority: docs/requirements/authority.md
---

# L1 Business Requirements

| ID | 要求 | 成功判定 |
| --- | --- | --- |
| WT-BR-01 | 機械可読性を維持する。面・部品・値・変種を追加しても、すべてが JSON 宣言（theme.json / config / schema / openapi）から列挙できる | capability manifest の列挙率 100%。PHP にしか存在する面・部品が 0 |
| WT-BR-02 | テーマA / B が示す一般想定水準に到達する。12 種別の必須パーツ充足に加え、未整備 16 項目と S3 追加 26 問（採用 24・reject 2）の受け皿を持つ | `web-patterns` 12 種別で本テーマだけに欠ける必須パーツ 0。未整備項目と採用候補の受け皿有無が台帳化 |
| WT-BR-03 | エージェント制御下でバリエーションを生成できる。面・部品・値・変種・テンプレ・CV の選択が JSON 経由で完結し、破壊域へ落ちない | AI 経路の変更のうち JSON 外の操作を要した件数 0。破壊域停止の誤警告 0 |
| WT-BR-04 | 実証済みパターンを証跡付きで記録し、他プロダクト（GRAPHIX-NEO 等）は記録を読んで採否を自分で決める。依存を作らない | 記録行の証跡付き率 100%。他リポへの参照・書き込み 0 |
| WT-BR-05 | AI 判定ロジックをテーマ・プラグインに持ち込まない | 静的検出（判定ロジック・モデル呼び出し）0 |

## Actor と scope

- actor: PO、編集者（Site Editor / Block Editor で編集する人）、運用者、移行者（HELIX-WP-HARNESS 側。テーマは写像先の定義を提供するだけ）、AI エージェント（HELIX-WP-HARNESS 経由）、決定論ゲート、GRAPHIX-NEO（記録を読むだけの第三者。依存なし）
- product boundary: 本テーマは「機械可読性を保ったままエージェント制御でバリエーションを最大化する」ための知見蓄積の場（`docs/planning/L0-agent-controlled-variety.md`）。製品化は GRAPHIX-NEO が担う
- release boundary: 実用試験。実運用サイトへの配備は PO 承認ごと
- non-goal: 決済・カート・会員機能と購入完了の計測（テーマ外。必要なら外部側）、CRM / MA とメッセージ配信そのもの（テーマ外）、第三者テーマの是正、外部デザインツール取り込み経路、AI 判定ロジックのテーマ内実装

## PO への問い

WT-Q-* は総数 79 件（採用 77・reject 2（WT-Q-AUDIT-02 / WT-Q-LOOK-04）: WT-Q-ZONE-01, WT-Q-PARTS-01, WT-Q-VOCAB-01, WT-Q-VOCAB-02, WT-Q-VOCAB-03, WT-Q-LOOK-01, WT-Q-LOOK-02, WT-Q-META-01, WT-Q-LP-01, WT-Q-MIGRATE-01, WT-Q-AGENT-01, WT-Q-AGENT-02, WT-Q-VALUE-01, WT-Q-SEO-01, WT-Q-INTAKE-01, WT-Q-ADMIN-01, WT-Q-SECTION-01, WT-Q-PLUGIN-01, WT-Q-SELL-01, WT-Q-SEO-04, WT-Q-CRAWL-01, WT-Q-AB-01, WT-Q-IMG-01, WT-Q-PERF-01, WT-Q-API-01, WT-Q-ADMIN-02, WT-Q-CLI-01, WT-Q-SNS-01, WT-Q-CV-01, WT-Q-BANNER-01, WT-Q-AUDIT-01, WT-Q-SP-01, WT-Q-TAG-01, WT-Q-PLUGIN-03, WT-Q-SEO-06, WT-Q-SEO-07, WT-Q-SEO-08, WT-Q-VOCAB-04, WT-Q-SP-02, WT-Q-SP-03, WT-Q-VALUE-02, WT-Q-IMG-02, WT-Q-IMG-03, WT-Q-SELL-02, WT-Q-TAG-02, WT-Q-SEO-09。未決 0 件は `docs/requirements/discovery/candidate-projection.json` の `unresolved`）。通常の問いは「X ができる（証跡あり）。採用するか」の形で、直接反映分（WT-Q-DIRECT-01〜08）は「反映内容一覧の確認」の形で記録する。
方式・配置先・境界値の詳細は問わない（PoC 証跡で決める）。

## S3 問い集計（2026-09-03）

問いは既存 46 件に S3 の 26 件を加えた 72 件、採用は 70 件（既存 46 件 + S3 24 件）、reject は WT-Q-AUDIT-02 と WT-Q-LOOK-04 の 2 件である。S3 の問い ID は次のとおり。

`WT-Q-AGENT-03`、`WT-Q-ADMIN-03`、`WT-Q-PLUGIN-04`、`WT-Q-DIRECT-01`〜`WT-Q-DIRECT-08`、`WT-Q-AUTHOR-01`、`WT-Q-NAV-01`、`WT-Q-RECO-01`、`WT-Q-SELL-03`、`WT-Q-SELL-04`、`WT-Q-SEO-10`、`WT-Q-CRAWL-02`、`WT-Q-SEO-11`、`WT-Q-AUDIT-02`、`WT-Q-ZONE-02`、`WT-Q-A11Y-01`、`WT-Q-LOOK-03`、`WT-Q-LOOK-04`、`WT-Q-VOCAB-05`、`WT-Q-PARTS-02`。

## S4 問い集計（2026-09-05）

問いは 72 件に S4 の 7 件を加えた 79 件、採用 77 件（72 件中 70 + S4 7）、reject 2 件、未決 0 件。S4 の問い ID は `WT-Q-META-02`、`WT-Q-CONSENT-02`、`WT-Q-ADMIN-04`、`WT-Q-LOOK-05`、`WT-Q-LOOK-06`、`WT-Q-PARTS-03`、`WT-Q-EVID-01`（PO 2026-09-05「採用で」で全件採用、除外番号なし）。
