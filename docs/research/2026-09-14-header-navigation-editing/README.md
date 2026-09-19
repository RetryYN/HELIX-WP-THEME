# Issue #185 — ヘッダーナビの編集責務と端末表示

親 Issue #100。現行 helix-wt のみを対象とする。新規ブロックは追加しない。

## 編集者に見える動作

SP補助ナビは、Site Editorキャンバス内で「SP専用テキストナビ」のラベルと破線枠を持つ。標準NavigationのInspectorでは「公開PCでは非表示」「SPのテキストナビ配置で表示」を説明する。公開面へこのラベル・枠・Editor JSを出さない。

標準Navigationのメニュー参照を変更しても、サイト全体の参照へ即時POSTしない。変更は標準ブロック履歴に入り、undo/redoできる。保存済みブロック属性と共通設定が異なる場合も、利用者が保存済み参照を明示選択した操作をundoと混同しない。Inspectorには公開中と変更後の参照先、未適用の説明、「全ヘッダーに適用」「変更を取り消す」を出す。

「全ヘッダーに適用」はサイト全体の設定の明示保存であり、通常のテンプレート保存とは別操作。適用後にundoすると、前の参照が未適用として復元される。公開設定を戻すには、その復元内容をもう一度明示適用する。標準Navigationのリンク内容編集・Save・再入場は維持する。

REST失敗時は公開設定を変えず、変更予定とエラーを表示して取消・再試行を可能にする。同じ適用の二重クリックは1 POSTにまとめる。通信中に別の参照を選んだ場合、送信時に確認した参照だけを保存し、最新の選択は未適用として残す。

## 構造的な境界

共有束縛の根拠は正規の9 header template-part identityである。PHPのallowlistをEditorへ渡して同じ定義を使う。CSS class、header-*という名前のprefix、footer/bodyへのコピーだけでは束縛しない。実行時のheader描画文脈はfinallyで閉じ、次に描画される本文ナビへ漏らさない。

実機負例は本文/footer groupへのmarker複製、実footer template-part、headerish・header-copied-bodyという誤認用template-partを含む。Editorでも本文投稿とfooter template-partにSP/shared classを付けた独立Navigationを開き、共有参照・SP編集枠が適用されないことを確認する。

## 検証の区別

- 新Editorランナー: 保存済み公開72条件と、実UI参照選択、保存済み属性と共通設定が異なる状態での明示再選択、undo/redo、離脱、REST失敗、二重クリック、通信中再変更、取消、標準リンク編集、全9part保存・再入場、権限・nonce負例。
- Scopeランナー: 正規header 9、body/footer 6、非header template-part 3の18条件。cleanupと描画文脈復帰もassertする。
- Recoveryランナー: SIGTERM、uncaughtException、unhandledRejectionと冪等な再回収の4条件。
- Public isolationランナー: 9header × native/shared × 390/1440 × JS有無の72条件。Editor専用DOMとJSの不在を検査する。
- 既存header PoCの504条件・459境界条件・72保存済み公開条件は別途再実行する。重複する公開条件を足して「固有条件数」とは報告しない。

`verify.json`、`scope.json`、`recovery.json`、`public-isolation.json`が個別の実測値。統合は `summary.json`。受入対応はWT-AC-PARTS-02A/Bの既存PoCに追記し、未検証の他条件を達成扱いしない。

## DB-backed headerの経路

保存済みheaderの正規Site Editor経路 `site-editor.php?postId=helix-wt%2F%2Fheader&postType=wp_template_part&canvas=edit` を実操作する。WP 7.1では数値IDの `post.php?post=ID&action=edit` は管理者でも403となるため、この非対応経路を成功扱いしない。

Editorの判定には編集対象slugも使用し、currentPostIdが数値の環境へ備える。数値IDの検査は実ブラウザ内でselectorのIDだけを置き換え、実際のslugとblock treeを維持する状態適応テスト。WordPressがこの数値URLを正式サポートした証拠とは区別する。

## 再現・復旧

```sh
node scripts/verify-header-navigation-scope.mjs
node scripts/verify-header-navigation-recovery.mjs
node scripts/verify-header-navigation-editing.mjs
node scripts/verify-header-navigation-editor-isolation.mjs
```

同じ専用labを変更するランナーを並列実行しない。開始時に完全なtheme_mods・既存part・所有fixture IDのsnapshotをOSの一時ディレクトリへ保存し、終了時に削除する。中断された場合は `node scripts/recover-header-navigation-editing.mjs` で所有fixtureとsnapshotを復元する。SIGKILL・ホスト停止の場合は自動handlerが動かない。snapshotが残っていればこの回収手順を使えるが、一時snapshot自体を失った場合の元状態復元は保証しない。

開発中の最初の遅延通信テストでは、Playwright routeの解除順序の誤りで旧runnerのfinally前に終了した。その時点では完全snapshotが未保存だったため、所有nav ID 8539/8540を確認して削除し、theme_mods内のwt_content_navigation_refキーだけを削除、その他のキーを保存した。初回中断前へのbyte-exact復元とは主張しない。親による独立probeでキーと所有投稿の不在を確認した後、完全snapshot付きの検証へ移行した。

## 最終検収と引継ぎ

最終source digestで新Editor293、scope29、異常終了復旧14、公開面のEditor非混入144検査が成功。実検査480に集計・digest整合43を加え、summaryは166実行条件 / 523 assertions、completed:true。既存header回帰は1,035条件 / 5,496 assertionsで成功。PHP 2ファイルのWordPress-Core PHPCSはエラー0・警告0、PHP/JS構文、i18n14検査も成功した。環境はWordPress 7.1 / PHP 8.3.33 / Chromium。

新しい画像7枚はscreenshots.htmlから参照できる。公開390/1440pxの画像は既存header回帰フォルダで再取得した。

WT-AC-PARTS-02A/Bは新旧の現在ソース証跡へ接続し、AI境界10・privacy8・capability12・i18n14の実再実行に対応する静的受入6件も更新した。引継ぎ後、実機依存21 ACも各oracleを再実行して成功行を確認し、最終監査はmissing 236 / partial 26 / verified_in_poc 32 / stale 0。カタログは633候補 / 1,137画像 / 133要求として再生成した。inheritance oracleは暗黙のcore navigation fallback依存を除き、所有fixtureを作成・選択・削除して設定を復元する自己完結型へ修正した。詳細はregression-pending.json。

範囲外: モバイル管理画面自体の全面操作検収、他ブラウザ、同時に複数管理者が共通参照を書き換える競合制御。数値post.php経路はcoreの403を確認し、数値currentPostIdの対応は状態適応テストとして区別した。G3または全要求完了は主張しない。commit/pushは実施していない。
