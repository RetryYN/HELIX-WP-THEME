---
layer: L1
sub_doc: screen
status: confirmed_input
pair_artifact: docs/requirements/l2/screen-list.md
authority: docs/requirements/authority.md
---

# L1 Screen Requirements

| ID | 画面 | PO の問い | 主操作 |
| --- | --- | --- | --- |
| WT-SCR-01 | 構造編集面（Site Editor） | パターン・共有パーツ・テンプレ変種を、何が変わるか分かる形で差し替えられるか | パターン差し替え / パーツ切替 / 変種選択 |
| WT-SCR-02 | スタイル面（Site Editor styles） | variation を切り替えても尺度が崩れないか | variation 選択 / 尺度検証表示 |
| WT-SCR-03 | 値編集面（Block Editor） | 入力値が安全域 / 生値 / 破壊域のどれか分かり、破壊域で止まるか | 値入力 / 域表示 / 停止 |
| WT-SCR-04 | 記事単位切替面（Post Editor パネル） | サイドバー・目次・シェア・PR を投稿ごとに切り替えられるか | メタ 4 キー切替 |
| WT-SCR-05 | 公開面: 記事 | 面・語彙・目次・PR・構造化データが崩れず出るか | 閲覧（read-only） |
| WT-SCR-06 | 公開面: ホーム / LP / 一覧 | hero・追尾・SP 下部・お知らせバーの積層と一覧の構造化データが出るか | 閲覧（read-only） |
| WT-SCR-07 | ゲートレポート（CLI） | FAIL から対象ファイルと原因へ 1 手で辿れるか | 静的 6 ゲート / 実機 G-E1 |
| WT-SCR-08 | エージェント制御面（REST / MCP manifest） | 面・部品・値・変種を JSON だけで列挙・選択・適用できるか | manifest 取得 / dry-run / apply / rollback |
| WT-SCR-09 | 実証記録台帳 | 1 行から証跡と参照元 commit へ辿れるか | 台帳追記 / 検証 |
| WT-SCR-10 | テーマ設定画面（WP 管理画面）| サイト全体の既定を AI を介さず管理画面から設定でき、同じ正本を AI も読めるか | 設定 JSON の編集 / export / import |

編集面は WP Site Editor / Block Editor の既存 UI を面として扱う。サイト全体の既定はテーマ設定画面（WT-SCR-10）で人が直接設定でき、正本は schema 付きの設定 JSON 1 本で AI と共有する（PO 2026-09-02）。
ゲートレポート・エージェント制御面・実証記録台帳は画面ではなく CLI / REST / JSON 文書だが、運用者と AI の判断面として screen 要求に含める。
