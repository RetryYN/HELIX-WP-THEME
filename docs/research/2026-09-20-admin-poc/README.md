# 設定画面の代表 PoC（WT-FR-ADMIN-01）

サイト既定・パーツ・記事の３候補を、同じ `wt-admin-poc.v1` fields/actions 契約から生成する。静的画面とブラウザ内の一時適用であり、WordPress 管理画面や実設定の実装ではない。AIなし、credentialなし、計測・広告はテーマ外。

- `site.html`: 既定セットの視覚ピッカー、管理名、適用済み値と編集中の区別。
- `parts.html`: 目次のピッカーと見出し閾値（代表P19）。
- `article.html`: 継承/上書きと１件のデモbulk選択。投稿メタ接続プレビュー。
- JSONの書出しはテキスト欄への出力。importは同じ検証器を通し、失敗時に適用済み値を保持する。
- export文書は研究用の移送エンベロープ。`settings` と `postMetaPreview` を区別した投影を表示する。既存製品の `agent-neo-settings.v1` と互換とは主張しない。
- メモリ内の適用済み文書だけを正本とする。別画面への移動・再読込で初期化。ブラウザストレージ、実DB、外部送信は使用しない。

## 要求との対応と未検証

| AC | 代表実測 | 未検証 |
|---|---|---|
| 01A | 同じ文書のピッカー→検証→投影、export/import一致 | WP実保存、実manifest/MCP読出し、実digest契約 |
| 01B | 未知キー、版、enum、pattern、length、range、構文エラーを拒否し適用済み値維持 | WP側の状態分散・保存層全体 |
| 01C | サイト/パーツ/記事３層と投稿メタ投影、継承/上書き | 実投稿メタ書込、複数画面間保存 |
| 01D | P19代表値、P05/P08/P31の固定表示、Cの要自社検証注記 | 全P01–P33実装、全操作領域/コントラスト監査、効果検証 |
| 01E | fields/actionsと階層、pattern/length/range、bulk、dirty/invalid/applied、JSON fallback | サーバー登録、DataViews/DataForm実接続、WP 7.2実機、権限保持 |

５ACすべてpartial。G3完了ではない。全ブラウザ、実スクリーンリーダー、ブラウザズーム、外部API、認証/認可、永続保存は未検証。200%はroot文字サイズ32pxの代表検査でありブラウザズームではない。JS無効時は説明・既定値・schemaリンクを閲覧し、適用ボタンは送信しない。

## 再現

`node scripts/build-admin-poc.mjs` でHTML/schema生成、`node scripts/verify-admin-poc.mjs` でE2Eと画像を再生成。ローカル閲覧は `node scripts/utility-poc-server.mjs` の `/docs/research/2026-09-20-admin-poc/site.html`。既存静的研究サーバーだけを再利用し、utility処理エンドポイントは呼ばない。

選択カタログでは既存「共通設定の継承」分類へ３候補を追加。catalog本体の操作契約は変更しない。検証行と画像のsource/hashを照合してから候補を登録する。

## 判断根拠

要求正本は `docs/requirements/l3/requirements-ir.json` の WT-FR-ADMIN-01 revision 2 と５AC。候補入力は `docs/research/2026-09-20-wp72-admin-surface/README.md` と `docs/research/2026-09-05-cro-usability-evidence/README.md`。既存 settings controller は調査のみ、変更なし。

[DataViews公式資料](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-dataviews/)と[DataViews/DataForm 7.0](https://make.wordpress.org/core/2026/03/04/dataviews-dataform-et-al-in-wordpress-7-0/)の宣言的fields/actions・検証を参考にした候補契約。[7.2 roadmap](https://make.wordpress.org/core/2026/09/18/roadmap-to-7-2/)は将来境界の調査入力であり、7.2実装証跡ではない。
