---
layer: L1
sub_doc: nfr
status: confirmed_input
pair_artifact: docs/test-design/l12-operational-value-test-design.md
authority: docs/requirements/authority.md
---

# L1 Non-functional Requirements

| ID | 品質要求 | 測定方向 |
| --- | --- | --- |
| WT-NFRL1-01 | 4 層一貫性（トークン→骨格→部品→内容）を静的ゲートで守る。層 1 だけが尺度を持ち、下位は名前で参照する | G-T1 / T1b / T2 / T3 / S1 / S2 FAIL=0 |
| WT-NFRL1-02 | 静的検査で通っても実機で壊れる事例があるため、パターン・パーツ変更は実機ゲート（Block validation）を完了条件にする | G-E1 invalid=0 / 全パターン |
| WT-NFRL1-03 | 描画に副作用を持たない: 描画パスで DB へ書かない。同じ入力は同じ出力になる（決定論） | 描画時 option / theme_mod write 0。同一入力の出力 digest 一致 |
| WT-NFRL1-04 | 未認証 REST を持たない。外部 URL 取得は検証付き HTTP 経由のみ。PHP Warning を公開面へ出さない | 未認証ルート 0。SSRF 形の関数呼び出し 0。公開面 Warning 0 |
| WT-NFRL1-05 | アクセシビリティ: 域の判定・状態表示は色だけに依存しない。img alt 欠落 0。AA コントラスト | axe gate 違反 0 |
| WT-NFRL1-06 | 性能予算: web-vitals-budget を維持する。面・語彙を増やしても CSS は使用分だけ読む | 予算超過 0。未使用ブロック CSS の読み込み 0 |
| WT-NFRL1-07 | 観測性: health / gate / 台帳の出力は JSON で機械可読。証跡は HEAD と digest に束縛する | JSON 出力率 100%。digest 不一致 0 |
| WT-NFRL1-08 | credential・実サイト固有名・第三者製品名を公開リポジトリへ置かない。接続情報は環境変数 | public-safety check OK |
| WT-NFRL1-09 | 復旧: 変更は dry-run → apply → rollback の経路を持ち、失敗時に元へ戻る | rollback 成功率 100% |
| WT-NFRL1-10 | 法令: PR 表記（景表法ステマ規制）は編集者が消せない位置に出る | 対象記事の表記欠落 0 |
| WT-NFRL1-11 | プライバシー: 計測・広告タグの正本はテーマ外（HELIX / プラグイン側） | テーマ内の計測 ID 0 |
| WT-NFRL1-12 | 権限で破壊域停止を迂回できない | 迂回経路 0 |
| WT-NFRL1-13 | コスト: ゲートと PoC はローカル docker で完結し、有料・無料枠制限のある外部 API に依存しない | 外部 API 依存のゲート 0 |
| WT-NFRL1-14 | SEO 準拠検査は test lane で走り、Google 公式ドキュメントの出典 URL と参照日を改定時に更新する | SEO 準拠検査の未実施 0。出典 URL / 参照日未更新 0 |
| WT-NFRL1-15 | クロールログは既定 90 日で保持・間引きし、bot 判定外の個人閲覧を記録しない。WP が応答したリクエストだけを対象とし、キャッシュ / CDN 応答は見えない限界を明記する | 保持期間・間引きの逸脱 0。bot 判定外の個人閲覧の記録 0。対象・限界の未明記 0 |

## 出典

- `WT-NFRL1-01` / `02`: `docs/design/consistency-responsibilities.md`、`docs/research/2026-08-29-ge1-local/README.md`（静的検査で通っても実機で壊れる事例）
- `WT-NFRL1-03` / `04`: 第三者テーマ監査で観測した欠陥（描画時 DB write、未認証 REST / SSRF、グローバル改変）を本テーマで再発させない教訓（`docs/research/2026-08-26-theme-structure-audit/20-reverse-engineering-synthesis.md` 第 2 部）。第三者テーマ自体の是正は本リポの要求ではない
- 数値（invalid=0、baseline 438）は 2026-08-29 時点の実測
- `WT-NFRL1-14` の SEO 準拠先: https://developers.google.com/search/docs/crawling-indexing（参照日: 2026-09-02）。構造化データの一般指針: https://developers.google.com/search/docs/appearance/structured-data/intro（参照日: 2026-09-02）
