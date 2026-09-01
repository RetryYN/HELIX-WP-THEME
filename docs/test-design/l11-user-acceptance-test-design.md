# L11 User Acceptance Test Design

| UAT ID | L2要求 | POが確認すること | 状態 |
| --- | --- | --- | --- |
| WT-UAT-01 | WT-UI-01 | パターン差し替えで何が変わるかが分かり、権限エラーが出ない | WT-Q-STRUCT-01 解消後 |
| WT-UAT-02 | WT-UI-02 | スタイルを切り替えても尺度が崩れない | candidate |
| WT-UAT-03 | WT-UI-03 | header / footer / sidebar を別案にできる | WT-Q-PARTS-01 解消後 |
| WT-UAT-04 | WT-UI-04 | 安全域 / 生値 / 破壊域の区別と停止理由が分かる | WT-Q-VALUE-01 解消後 |
| WT-UAT-05 | WT-UI-05 / 06 | 広告・CV・目次・構造化データ・追尾要素が崩れず出る | WT-Q-ZONE-01 解消後 |
| WT-UAT-06 | WT-UI-07 | ゲート FAIL から原因ファイルへ 1 手で辿れる | candidate |
| WT-UAT-07 | WT-UI-08 | 台帳 1 行から証跡と参照元 commit へ辿れる | candidate |

スクリーンショットだけを合意とみなさず、PO の reaction、accepted / rejected、対象 revision、日時を discovery event へ追記する。
