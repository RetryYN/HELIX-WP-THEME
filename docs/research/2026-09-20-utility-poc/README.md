# 対話型ユーティリティの代表PoC

WT-FR-UTILITY-01 / WT-AC-UTILITY-01A〜Dの**部分証跡**。正本は [gap調査](../2026-09-20-interactive-utility-gap/README.md) と現行L3。G3承認・製品完成・実外部API対応を主張しない。

## 操作と責務

calculator（予算）、grader（自己申告の準備確認）、generator（固定型の見出し）を、入力→検証→処理→結果→使用データ・基準・更新日・丸め・対象外→再入力で揃えた。説明・ラベル・手元で確かめる方法は初期HTMLに残す。入力と結果は保存せず、共有・メール送信もしない。

`definitions.mjs`と`build-utility-poc.mjs`は静的HTMLの生成。`view.mjs`は入力状態と共通 `wt-utility-result.v1` の表示のみ。計算・採点・文章組立は `scripts/utility-poc-provider.mjs` とローカルHTTPサービス側へ分離。テーマ・プラグイン配布物には追加していない。API資格情報、AI呼出し、外部通信、DB・ファイル保存はない。入力をHTTP POSTでローカルサービスへ渡し、プロセス内で応答後に破棄する。

外部処理の失敗・遅延・再試行はPlaywrightの応答差し替えで再現し、実外部サービスへの接続とは区別する。処理中は状態通知・中止を表示し、再入力・失敗・遅延後に古い成功結果を残さない。JS無効時は自動実行を無効化し、同じラベル順序と手動代替を提示する。

## 検証

```sh
node scripts/build-utility-poc.mjs
node scripts/utility-poc-server.mjs
# 表示先はCLI出力を参照。サービスはloopbackのみ。
npx playwright test tests/e2e/utility-poc.spec.ts --workers=1
node scripts/verify-utility-poc.mjs
node scripts/build-selection-catalog.mjs
```

46検査、PC1440 / SP390 / 狭幅320、100% / 200% root文字、JS有無、キーボード実行・再入力、入力ラベル、数値の空/0/最大/尺度外/小数、文字の空/空白/1/80/81文字/多言語/HTML文字列、graderの0/3件、HTTP失敗・遅延・中止・タイムアウト・再試行、POST先、local/session storage書込なし・cookieなし・再読込後の空入力を実測する。ネイティブ入力欄内の文字スクロールは許容し、ページ・操作部品の横はみ出しを検査する。

verifierは専用ローカルサーバーをテスト内で開始し、実行予定テストと実行結果の名前・件数・失敗/skip/retryを照合する。成功時だけスクリーンショット6枚、検証行、ソースdigest、候補3件、4 AC向け候補を生成する。日時や実行時間を成果物に含めない。証拠の正式登録は `admit-acceptance-evidence.mjs` による再実行付きtransactionを用いる。

## 未検証範囲

全4 ACはpartial。WordPress 7.2管理画面・保存/再読込・製品テーマ/Coreへの組込み、実外部API・認証・負荷、全ブラウザ、実スクリーンリーダー音声、ブラウザーzoomは未検証。200%は同一viewportでroot font-sizeを16→32pxに変更した実測である。結果のlive region属性は検査するが読み上げ品質の保証ではない。

## 参照

[WordPressのテーマと機能の境界](https://developer.wordpress.org/themes/getting-started/what-is-a-theme/)、[W3C入力エラーの通知](https://www.w3.org/WAI/tutorials/forms/notifications/)、[W3C状態メッセージ](https://www.w3.org/WAI/WCAG21/Understanding/status-messages)を参照し、表示と処理の分離、入力へのエラー参照、live statusを採用した。
