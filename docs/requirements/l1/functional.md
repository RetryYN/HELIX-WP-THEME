---
layer: L1
sub_doc: functional
status: confirmed_input
pair_artifact: docs/test-design/l12-operational-value-test-design.md
authority: docs/requirements/authority.md
---

# L1 Functional Requirements

| ID | ユーザー視点の要求 | 下流family |
| --- | --- | --- |
| WT-FRL1-01 | 編集者はパターン差し替え・ブロック編集・テンプレート変種で構造を変えられる | STRUCT |
| WT-FRL1-02 | 編集者は安全域の値を自由に選べ、生値は警告され、破壊域は止められる | VALUE |
| WT-FRL1-03 | 編集者はスタイルバリエーションを切り替えでき、意匠は尺度を所有しない | STYLE |
| WT-FRL1-04 | 編集者は共有パーツ（header / footer / sidebar / post-header / post-footer）を差し替えられる | PARTS |
| WT-FRL1-05 | AI エージェントは記事・ページ・パーツを中間 JSON と REST で決定論的に投入できる | AGENT |
| WT-FRL1-06 | 運用者は収益と回遊の置き場所（記事内広告・CV・追尾サイドバー・メニュー・お知らせバー・SP 下部固定）を持てる | ZONE |
| WT-FRL1-07 | 運用者は 4 層の一貫性ゲートと実機ゲートを実行し、結果を確認できる | GATE |
| WT-FRL1-08 | 運用者は実証済みパターンを取り込み台帳へ記録できる | INTAKE |
| WT-FRL1-09 | 移行者は既存サイトの設定とコンテンツを写像し、写像不能を台帳化できる | MIGRATE |
| WT-FRL1-10 | 公開面は構造化データと canonical を単一出力元から出す | SEO |

L1 の ID はユーザー要求であり、L3 の system requirement ID とは区別する。
旧 REQ-F-045（Style Variations）・REQ-F-046（記事用パーツ）・REQ-F-025（JSON 統一データモデル）は WT-FRL1-03 / 04 / 05 へ継承し、
旧 REQ-F-016 / F-037 は WT-Q-STRUCT-01 / 02 の決定まで継承しない。
