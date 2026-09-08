# フッターのデータ接続と空枠省略

- 対象要求: WT-FR-PARTS-01 / WT-AC-PARTS-01C。
- 範囲: 試作テーマのサイトマップ・関連サイト/ページ。共通設定と面ごとの継承、PC/SPを保持する。
- 根拠: 現行parts/footer.htmlは固定HTML。関連サイト2件がどちらも `/` を指す。functions.phpはフッター全体のoffを除きCSSクラス選択だけを行い、入力データを読まない。

## 基準測定

`node scripts/verify-footer-baseline.mjs` は専用labへGETだけを行う。
PC1440/SP375 × JS有無 × 関連サイトnone/sitesの8条件、16検査中8失敗。
選択と表示の一致は8成功。none時のDOM省略4件、sites時の別リンク先4件は不成立。
これは空データ保存の受入試験ではないため、WT-AC-PARTS-01Cを確認済みへ昇格しない。
`baseline.json` に実測値とソースdigestを保存した。

## 次の実機試作

1. サイトマップのグループ・リンクと関連リンクを、空/1件/複数件で保存して公開面へ接続する。固定サンプルと保存データの区別を明示する。
2. 空配列は見出し・リスト・親枠ごと省略する。非表示設定もHTMLを出さない。不正データと未設定を混同してサンプルに戻さない。
3. 各リンクの表示名と遷移先を照合し、空名・無効URL・重複・長文、日本語、親子構造を試す。ラベルとURLは出力時に適切にescapeする。
4. Site Editorの保存結果と公開DOM、面の継承/独自/off、PC/SP・JS有無の折り畳み、キーボード操作を確認する。
5. 空/少数/多列の実画像をカタログへ追加し、関連要求と確認範囲へ接続する。画面枚数だけでは完了としない。

WordPress標準のメニューAPIは、メニューがなければ既定で別出力へfallbackするため、空を維持する場合はfallbackを無効化する必要がある。
[公式 wp_nav_menu reference](https://developer.wordpress.org/reference/functions/wp_nav_menu/)。
既存テーマはblock themeであるため、このAPIを直ちに採用する判断ではない。既存Navigationブロックの保存・空出力との整合を実機で比較して方式を選ぶ。

## PHP検査環境

PO 2026-09-09の導入許可によりホストPHP CLI 8.5.4とComposer 2.9.5を導入。
既存composer.lockからPHPUnit 9.6.34 / PHPCS 3.13.5 / WPCSをインストール。lockの変更なし。
unit,securityは201テスト・492 assertions成功。既存PHPCS対象pluginsの57ファイルはerror/warning 0。
試作テーマとcontent-faces pluginの66 PHPファイルは構文検査成功。
PHPCSの既存設定は試作テーマを対象に含めない。ホスト8.5の成功を最低対応8.1の検証で代用せず、CI8.1/8.3とWP実機8.3を引き続き用いる。

## 標準Navigation保存データの実機比較

WordPress 7.1 / PHP 8.3上で、所有するwp_navigation fixtureを空→1件→2件→draft→private→空へ保存し、各回を別PHPプロセスで描画した。標準core/navigationは空でもnavを残し、draft/private参照では既存の別公開メニューへfallbackする。11検査中6失敗（navigation-source.json）。非公開内容の漏えいという結果ではなく、指定と異なる公開メニューが出る挙動である。

[Navigation block公式属性](https://developer.wordpress.org/block-editor/reference-guides/core-blocks/core-blocks-theme/core-block-navigation/)のref/overlayMenuを使用。既存inc/content-navigation.phpの公開参照チェックも照合した。

新規inc/footer-navigation.phpで、公開wp_navigation・非空content・ラベルを確認してから標準ブロックを描画し、リンク出力のないnavを省略する候補関数を実装した。`node scripts/verify-footer-navigation-source.mjs --guarded`は同じ保存系列の11検査成功。fixture削除を含む。PHP8.3実機/8.5ホストの構文検査成功。
現時点では候補関数単体の実機PoCであり、parts/footer.htmlにはまだ組み込んでいない。Site Editor保存UI、無効URL/空リンク名、不正ref、階層・多列・継承・カタログ画像は未検証。受入条件は引き続き未対応として扱う。

入力境界を追加: 空名・空白名・空URL・script scheme・正常/空名混在を保存して照合。候補関数の基準20検査中2失敗は空白名に集中し、名前のないリンクとnav枠が残った。navigation-input-baseline.jsonに記録。フッター描画中だけnavigation-linkの空白名を省略し、finallyでfilterを解除する。再検証20成功。階層や非ASCII空白など全入力を網羅した主張ではない。
