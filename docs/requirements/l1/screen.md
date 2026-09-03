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
| WT-SCR-01 | 構造編集面（Site Editor） | パターン・共有パーツ・テンプレ変種の共通宣言と device 別差分を、何が変わるか分かる形で両幅から差し替えられるか | パターン差し替え / パーツ切替 / device 別変種選択 |
| WT-SCR-02 | スタイル面（Site Editor styles） | variation と device 別差分を切り替えても共通尺度と両幅の表示が崩れないか | variation 選択 / 幅別差分編集 / 尺度検証表示 |
| WT-SCR-03 | 値編集面・区間編集面（Block Editor） | 入力値が安全域 / 生値 / 破壊域のどれか分かり破壊域で止まるか。H2 / H3 区間の境界と区間単位の操作が見えるか | 値入力 / 域表示 / 停止 / 区間操作 |
| WT-SCR-04 | 記事単位切替面（Post Editor パネル） | サイドバー・目次・シェア・PR を投稿ごとに切り替えられるか | メタ 4 キー切替 |
| WT-SCR-05 | 公開面: 記事 | 設定した主たる確認面（既定 SP）と PC の両幅で、面・語彙・目次・PR・構造化データが崩れず出るか | 両幅の閲覧（read-only） |
| WT-SCR-06 | 公開面: ホーム / LP / 一覧 | 設定した主たる確認面（既定 SP）と PC の両幅で、hero・追尾・下部固定・お知らせバーの device 別積層と一覧の構造化データが出るか | 両幅の閲覧（read-only） |
| WT-SCR-07 | ゲートレポート（CLI） | FAIL から対象ファイルと原因へ 1 手で辿れるか | 静的 6 ゲート / 実機 G-E1 |
| WT-SCR-08 | エージェント制御面（REST / MCP manifest） | 面・部品・値・変種を JSON だけで列挙・選択・適用できるか | manifest 取得 / dry-run / apply / rollback |
| WT-SCR-09 | 実証記録台帳 | 1 行から証跡と参照元 commit へ辿れるか | 台帳追記 / 検証 |
| WT-SCR-10 | テーマ設定画面（WP 管理画面）| 共通宣言・device 別差分・主たる確認面（既定 SP）、計測タグ、同意状態、A/B、画像、運用ログ、差分、rollback、鍵、CV、バナー、監査、第三者プラグインの検出結果と領域別既定を AI を介さず管理画面から設定・確認でき、同じ正本を AI も読めるか | 幅別の設定 JSON 編集 / export / import / 適用 / 却下 / 保留 |
| WT-SCR-11 | クローラーダッシュボード（WP 管理画面） | クローラー別の来訪推移・古い URL・404 / 5xx・新規記事の初回捕捉時間・AI クローラーの llms.txt / crawl-map 来訪と効果実証用のアクセス計測を確認し、robots.txt と AI クローラーの許可 / 拒否を設定できるか | ダッシュボード確認 / robots.txt・AI クローラー設定 |

編集面は WP Site Editor / Block Editor の既存 UI を面として扱う。サイト全体の既定はテーマ設定画面（WT-SCR-10）で人が直接設定でき、正本は schema 付きの設定 JSON 1 本で AI と共有する（PO 2026-09-02）。
WT-Q-AB-01、WT-Q-IMG-01、WT-Q-IMG-02、WT-Q-IMG-03、WT-Q-ADMIN-02、WT-Q-CV-01、WT-Q-BANNER-01、WT-Q-AUDIT-01、WT-Q-SP-01、WT-Q-SP-02、WT-Q-SP-03、WT-Q-TAG-01、WT-Q-TAG-02、WT-Q-PLUGIN-03、WT-Q-SEO-06〜09、WT-Q-VOCAB-04、WT-Q-VALUE-02、WT-Q-SELL-02 の追加操作は WT-SCR-10 のタブとして扱い、画面数は 11 のまま（既存 amendment に基づく claude の判断。PO 決定は各問いの採用のみ）。
ゲートレポート・エージェント制御面・実証記録台帳は画面ではなく CLI / REST / JSON 文書だが、運用者と AI の判断面として screen 要求に含める。
