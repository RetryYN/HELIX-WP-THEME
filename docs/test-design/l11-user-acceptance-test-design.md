# L11 User Acceptance Test Design

| UAT ID | L2要求 | POが確認すること | 状態 |
| --- | --- | --- | --- |
| WT-UAT-01 | WT-UI-01 | パターン・パーツ・変種の差し替えで何が変わるかが分かる | WT-Q-PARTS-01 採否後 |
| WT-UAT-02 | WT-UI-02 | variation を切り替えても尺度が崩れない | WT-Q-LOOK-01 / 02 採否後 |
| WT-UAT-03 | WT-UI-03 | 安全域 / 生値 / 破壊域の区別と停止理由が分かる | WT-Q-VALUE-01 採否後 |
| WT-UAT-04 | WT-UI-04 | 投稿ごとに sidebar / toc / share / pr を切り替えられる | WT-Q-META-01 採否後 |
| WT-UAT-05 | WT-UI-05 | SP 幅を既定にした面・語彙・目次・PR・構造化データ、A/B variant、画像、SNS share、CV・バナー・データ層イベントが崩れず出る | WT-Q-ZONE-01 / VOCAB-01〜03 / SEO-01 / AB-01 / IMG-01 / SNS-01 / CV-01 / BANNER-01 / SP-01 / TAG-01〜03 採否後 |
| WT-UAT-06 | WT-UI-06 | SP ヘッダー・ドロワー・下部固定・お知らせバーの積層と一覧の構造化データ、feed・資料 DL・バナー・同意状態が出る | WT-Q-ZONE-01 / LP-01 / IMG-01 / SNS-01 / CV-01 / BANNER-01 / SP-01 / TAG-01〜03 採否後 |
| WT-UAT-07 | WT-UI-07 | SP の 44px / 16px / 横スクロール 0、タグ同意前非発火、性能予算と CWV 閾値割れが blocking になることをゲートで確認できる | WT-Q-PERF-01 / SP-01 / TAG-01 採否後 |
| WT-UAT-08 | WT-UI-08 | manifest、SP プレビュー、データ層契約、同意信号、差分 API、MCP / REST / WP-CLI の同一能力集合、第三者プラグイン領域別警告を確認できる | WT-Q-AGENT-01 / 02 / API-01 / CLI-01 / AUDIT-01 / TAG-01〜03 / PLUGIN-03 採否後 |
| WT-UAT-09 | WT-UI-09 | 台帳 1 行から証跡と参照元 commit へ辿れる | candidate |
| WT-UAT-10 | WT-UI-10 | 管理画面だけでサイト全体の SP 既定・SP プレビュー、A/B、画像、タグ slot・データ層・同意、第三者プラグインの領域別既定・警告、操作ログ、差分、rollback、鍵、CV、バナー、監査を扱え、AI が同じ値を読む | WT-Q-ADMIN-01 / 02 / AB-01 / IMG-01 / CV-01 / BANNER-01 / AUDIT-01 / SP-01 / TAG-01〜03 / PLUGIN-03 採否後 |
| WT-UAT-11 | WT-UI-11 | クローラー別の来訪指標を確認し、robots.txt と AI クローラーの許可 / 拒否を同じ画面から設定できる | WT-Q-CRAWL-01 採否後 |

スクリーンショットだけを合意とみなさず、PO の reaction、accepted / rejected、対象 revision、日時を discovery event へ追記する。
