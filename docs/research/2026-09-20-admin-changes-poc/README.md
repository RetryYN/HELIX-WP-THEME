# 差分レビューと復旧の代表 PoC（WT-FR-ADMIN-03）

通常差分・破壊域停止・部分適用からの復旧を３候補で比較する。WordPress管理画面ではなく、ブラウザ内の状態遷移を表示する静的/ローカルPoC。３ACはpartialで、G3完了・本番保存・WP7.2実装を主張しない。

## 操作契約

- dry-run前や確認チェック前に適用できない。画面のaria-disabledを外しても状態遷移側で拒否する。
- 破壊域を含む変更案は全体停止。保護された本文は保持し、安全な項目だけの暗黙適用もしない。
- 却下・保留後は確認が解除され、再dry-runが必要。
- 適用後は直近snapshotへrollbackし、復旧事実を同じページのログに記録する。
- dry-run・適用・却下・rollbackの失敗を一度だけ注入できる。現在値と差分を保持し、ローカルID/原因コード/変更案ID/次の操作を表示する。
- 部分適用では２項目中１項目だけを変え「未完了」を明示。再適用を止め、snapshotへのrollbackを案内する。rollback失敗時もsnapshotと文脈を保持する。
- 診断IDは `LOCAL-*` の連番で、実サーバーの追跡IDではない。コピーAPIが失敗したらテキストを選択して手動コピーへ戻す。
- ページのメモリだけを使用する。再読込・画面移動で操作ログも初期化する。ブラウザストレージ・cookie・外部送信なし。AI/credentialなし、計測・広告はテーマ外。

## 受入条件と未検証

| AC | 実測する代表範囲 | 未検証 |
|---|---|---|
| 03A | dry-run・差分・適用/却下・直近rollbackとローカル操作ログ | WP実保存・実rollback・永続監査ログ・MCP |
| 03B | 未検査/未確認・却下/保留・破壊域停止の適用阻止、復旧記録 | サーバー側の権限・競合・破壊域判定全体 |
| 03C | ４操作の失敗、コピー成功/fallback、再試行、部分適用とrollback失敗からの復旧 | 実API障害、実clipboard権限、WP7.2実機、全ブラウザ/実スクリーンリーダー |

200%はroot font-size 32pxでの代表リフロー検査であり、ブラウザズームではない。コピーはAPI成功/拒否をstubして両分岐を検査し、OSクリップボードへの実書込を完了とは主張しない。JS無効時は差分と停止理由のみ閲覧可能。

## 再現

`node scripts/build-admin-changes-poc.mjs` でHTMLを生成。`node scripts/verify-admin-changes-poc.mjs` はブラウザ実行が成功した場合だけverification・６画像・候補・partial admission入力を更新する。`node scripts/utility-poc-server.mjs` で研究用静的サーバーを起動し `/docs/research/2026-09-20-admin-changes-poc/review.html` を開く。utility処理APIは使用しない。

## 根拠

現行 `WT-FR-ADMIN-03` revision 2 と `WT-AC-ADMIN-03A/B/C`、`docs/research/2026-09-20-wp72-admin-surface/README.md` を正本として参照。[DataViews/DataForm公式資料](https://make.wordpress.org/core/2026/03/04/dataviews-dataform-et-al-in-wordpress-7-0/)の操作・状態表示を候補入力として使用。実DataViews/DataFormやWP7.2に接続した証跡ではない。既存settings controllerと#256設定画面PoCは調査参照のみ。
