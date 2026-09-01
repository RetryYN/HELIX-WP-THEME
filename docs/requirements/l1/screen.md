---
layer: L1
sub_doc: screen
status: confirmed_input
pair_artifact: docs/requirements/l2/screen-list.md
authority: docs/requirements/authority.md
---

# L1 Screen Requirements

| ID | 画面 | POの問い | 主操作 |
| --- | --- | --- | --- |
| WT-SCR-01 | サイトエディタ: パターン挿入・差し替え | この構造変更は許されるか、何が変わるか | 挿入、差し替え、保存 |
| WT-SCR-02 | サイトエディタ: スタイルバリエーション切替 | 意匠を変えても尺度は崩れないか | 切替、プレビュー、保存 |
| WT-SCR-03 | サイトエディタ: 共有パーツ差し替え | header / footer / sidebar を別案にできるか | 選択、差し替え、保存 |
| WT-SCR-04 | ブロック編集: 値の変更と警告・停止 | この値は安全域か、生値か、破壊域か | 値入力、警告確認、停止解除不可 |
| WT-SCR-05 | 公開面: 記事ページ | 広告・CV・目次・構造化データが出ているか | read-only |
| WT-SCR-06 | 公開面: ホーム / LP | hero・CTA・追尾要素が崩れず出ているか | read-only |
| WT-SCR-07 | ゲートレポート（CLI / CI 出力） | どのゲートが FAIL / WARN で、原因は何か | 実行、結果確認 |
| WT-SCR-08 | 取り込み台帳 | どのパターンがいつ・どの証跡で GRAPHIX-NEO へ渡ったか | 追記、参照 |

編集面は WP Site Editor / Block Editor の既存 UI を面として扱い、テーマは独自の設定 UI を持たない（ADR-028、WT-Q-ADR-01 で再検討）。
ゲートレポートと取り込み台帳は画面ではなく CLI 出力と JSON 文書だが、運用者の判断面として screen 要求に含める。
