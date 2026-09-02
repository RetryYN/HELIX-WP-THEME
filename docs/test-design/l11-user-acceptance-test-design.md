# L11 User Acceptance Test Design

| UAT ID | L2要求 | POが確認すること | 状態 |
| --- | --- | --- | --- |
| WT-UAT-01 | WT-UI-01 | パターン・パーツ・変種の差し替えで何が変わるかが分かる | WT-Q-PARTS-01 採否後 |
| WT-UAT-02 | WT-UI-02 | variation を切り替えても尺度が崩れない | WT-Q-LOOK-01 / 02 採否後 |
| WT-UAT-03 | WT-UI-03 | 安全域 / 生値 / 破壊域の区別と停止理由が分かる | WT-Q-VALUE-01 採否後 |
| WT-UAT-04 | WT-UI-04 | 投稿ごとに sidebar / toc / share / pr を切り替えられる | WT-Q-META-01 採否後 |
| WT-UAT-05 | WT-UI-05 | 面・語彙・目次・PR・構造化データが崩れず出る | WT-Q-ZONE-01 / VOCAB-01〜03 / SEO-01 採否後 |
| WT-UAT-06 | WT-UI-06 | hero・追尾・SP 下部・お知らせバーの積層と一覧の構造化データが出る | WT-Q-ZONE-01 / LP-01 採否後 |
| WT-UAT-07 | WT-UI-07 | ゲート FAIL から原因ファイルへ 1 手で辿れる | candidate |
| WT-UAT-08 | WT-UI-08 | manifest だけでエージェントが構造・スタイル・値を選べる | WT-Q-AGENT-01 / 02 採否後 |
| WT-UAT-09 | WT-UI-09 | 台帳 1 行から証跡と参照元 commit へ辿れる | candidate |

スクリーンショットだけを合意とみなさず、PO の reaction、accepted / rejected、対象 revision、日時を discovery event へ追記する。
