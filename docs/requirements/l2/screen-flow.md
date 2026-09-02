# L2 Screen Flow

## 通常

`WT-UI-01 パターン / パーツ / 変種選択 → 差し替えプレビュー → 構造差分確認 → 保存 → WT-UI-07 ゲート → WT-UI-05 / 06 公開面確認`

AI 経路は `WT-UI-08 manifest 取得 → 選択 → dry-run 差分 → apply → WT-UI-07 ゲート → WT-UI-05 / 06 確認` で同じ結果に到達する。
値の編集は `WT-UI-03 値入力 → 安全域 / 生値 / 破壊域の判定表示 → 保存（破壊域は停止）`。記事単位の切替は `WT-UI-04 メタ切替 → 公開面確認`。サイト全体の既定は `WT-UI-10 設定画面で編集 → 保存（schema 検証）→ 公開面確認`、AI は同じ設定 JSON を MCP パックから読み書きする。

## 取消

差し替え・値入力・メタ切替を保存前に取り消した場合は theme.json・テンプレート・メタ・option に何も書かず、編集前の状態へ戻る。dry-run は何も書かない。

## failure

ゲート FAIL は対象ファイル・ゲート ID・原因（生値、参照欠落、尺度再定義、Block validation invalid）を示し、修正の再入場条件を出す。
破壊域停止は「どの規則に、どの値が、どの境界で」触れたかを示し、権限による解除手段を提示しない。manifest 外の指定は拒否理由付きで返す。

## timeout/recovery

実機ゲート（G-E1）の docker 起動 timeout と dry-run の timeout は再実行で同一結果を返す。途中結果を pass として扱わない。apply 後の失敗は rollback で元の digest に戻す。

## navigation

Site Editor / Block Editor の各面へは WP 標準ナビゲーションで到達し、テーマ独自メニューを追加しない。
