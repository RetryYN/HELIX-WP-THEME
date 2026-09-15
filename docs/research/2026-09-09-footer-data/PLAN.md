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

## フッター本体への接続（作業中）

footer.htmlの固定サイトマップ/関連サイトを標準ブロックパターンへ置換。core/details + core/navigationとcore/groupで構成し、Site Editorが保存できるrefを読む。functions.phpで描画filterを読込み、対象クラスのナビだけ公開参照を束縛。空/非公開では外側の見出し・グループごと省略する。関連サイトnoneもDOMを省略。

`verify-footer-rendering.mjs`が所有するwp_navigation/wp_template_partを作成し、空→2リンク→空へ更新、PC1440/SP375×JS有無を照合。50検査成功、fixture2件の削除も確認。メニュー保存内容、4グループ/関連枠、off、viewport、Enter折り畳みを含む。2画像を保存しSP表示を目視確認。最初の実行は新規パターンがtheme pattern cacheへ未反映のため失敗し、専用labの検証準備でcacheを破棄して再実行した。

共通functions.php変更のため既存29 ACをstaleへ戻し、カタログの確認状況にも反映した。再検証せずhashだけ更新しない。継承検証のsourceに新規描画module/patternを加えて実行中。Site Editorの実ブラウザー編集、他回帰、カタログ画像追加/新AC対応付けは未完了。

継承回帰は428検査すべて成功。npm test（要求整合性・consumer health）も成功。その他の依存証跡の再取得が残るため、ACのstale状態はまだ解除していない。

回帰の継続: イベント通常状態はフッター接続後も121検査成功。フォーム5系統とイベント2系統のsource digestにfooter.html / footer-navigation.php / 新規2パターンを追加した。日時・定員境界、フォームflow/kinds/steps/slots/boundariesを専用labで順次実行中。共有設定・fixtureを使う検証は並行実行しない。完了した証跡だけをACレジストリへ反映する。

回帰更新: イベント121+341、フォームflow218が成功。継承428とイベント462の全行・現行source digestを照合し、PARTS-03A/BとEVENT-01A/Bの4 ACを部分確認へ復帰。他25 ACはstaleを維持。フォーム残系統は同じ逐次実行を継続。学習表示のread-only検証にも共通フッター依存を追加し、学習固有ルートの再検証を開始した。

学習表示252と案内ページ品質265の回帰成功。追加した共通フッター依存も現行hashと一致。LEARN-01BをPoC確認済み、NFR-SP-01A/Bを部分確認へ復帰し、カタログ表示も更新した。現時点1確認/6部分/22 stale/265未対応。小さい操作領域の検査対象内指摘0であり、全サイトの品質保証ではない。

PC画像の目視でサイトマップ列の見出しY位置の不一致を確認。標準groupのflow余白と既存gridの組合せを調整する必要がある。画像はEnter検証後なので先頭列が閉じた状態であり、カタログ用には操作前の初期状態と開閉後を区別して撮影する。機能50成功をデザイン仕上げ完了と扱わない。

イベント/フォーム回帰の逐次実行完了: 121+341+218+282+395+83+112の1552検査が全成功。FORM-01A/Bも部分確認へ復帰し、確認1/部分8/stale20/未対応265へ更新。次の逐次実行はheader-navigation→site-pages→learning-publication→interview-lifecycle→content-management→content-faces。最初の検証を実行中であり、後続成功はまだ主張しない。

header-navigationの62検査成功によりPARTS-02A/Bも部分確認へ復帰。確認1/部分10/stale18/未対応265。
site-pages検証はhandoff先のローカル静的サーバー停止で失敗した。port 8099のlistenなし、ブラウザーchrome-error、HTTP取得不能を確認してサーバーを再起動し、providerページ200を確認後にsite-pages以降の列を再実行。アプリ不具合との混同や、失敗証跡の成功扱いはしない。

復旧後site-pages397とlearning-publication15が成功。現行sourceを含む実測と対応付けてPAGE-01C/LEARN-01CをPoC確認済みへ復帰。確認3/部分10/stale16/未対応265。interview-lifecycle以降の逐次実行を継続中。

interview-lifecycle73、content-management5、content-faces191成功。content-facesにはsourceDigestsを追加して再実行191成功し、functions/footer依存を追跡した。PAID/INTERVIEW/BLPと学習管理のACを実測へ再対応付け。確認10/部分13/stale6/未対応265。検索系は基本105・入力297成功、境界/日本語/公開範囲/パスワードと案内編集の逐次実行を継続中。Site Editor初期調査はストア存在だけではUI読込完了と判定できず、ブラウザー保存の確認はまだ行っていない。

検索回帰6系統（105+297+62+74+335+319=1192）と固定ページ編集42が成功。対応するSEARCH-01A〜D/PAGE-01A〜Bの全証跡行とsource hashを検査して更新した。正規カタログbuilder成功: 611候補/1094画像、ACは確認13/部分16/未対応265/stale0。npm testの要求整合性とconsumer healthも成功。

Site Editorの読み込み後にcore/block-editorのブロック状態をread-onlyで調査し、外側のcore/group（tagName: footer）だけがisValid=falseであることを確認した。属性には上下paddingがあるが保存HTMLに対応するstyle属性がなく、coreの期待するsave出力と一致しない。編集画面の警告を目視でも確認。ブラウザーでの編集保存は未検証のまま。次はこの保存形式の不一致とサイトマップ列の余白を修正し、編集・公開反映と依存証跡を再検証する。

保存形式・列見出しの修正: footer外側groupの保存HTMLに属性で指定済みの上下paddingを出力し、サイトマップgroupのblockGapを0へ設定。WordPress公式の保存時検証仕様も再確認した（https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/）。専用labのSite Editor読み込み後、core/block-editorで再帰的に取得した20ブロックすべてisValid=falseではないことを確認。ブラウザー保存はまだ検証していない。

公開面の検証にPC列見出しのY座標差1px以内を追加し、52検査成功。カタログ用画像はEnter操作前の初期状態へ変更し、PCの4列見出し整列を画像でも確認した。PHP構文検査成功。footer.htmlとパターン更新に伴い、関連29 ACはstaleへ戻し、カタログにも再検証待ちを反映。古いsource hashを実測なしで更新しない。

Site Editor実操作で追加発見: ブランドgroupに既存のlayout.type=flowがあり、WordPress block-editorのgetLayoutTypeがundefinedを返してgetOrientation呼び出しで例外になっていた。ブロックのisValid検査だけでは検出できない。実機配布JSのgetLayoutTypeとdefaultLayout定義を確認し、footer内をdefaultへ修正。再検証でUI本文入力・保存とDB反映までは成功し、公開確認の検証セレクターがsite-titleのpにも一致したため停止。検証を本文のhas-mute-colorに限定して再実行する。

横断調査: 同じlayout.type=flowが他のテンプレート・パーツ・パターン19ファイルにも残っている。次の横断編集検証対象として扱う。現段階では他19ファイルの実行時エラーを実測済みとはしない。

本文セレクター修正後のverify-footer-editorは8検査すべて成功。初期ブロック妥当性、実UI入力、DB保存、公開面反映、再読込本文、再読込妥当性、source不変、専用テンプレートパーツ削除を確認した。editor.jsonに証跡を保存。ナビゲーション選択・編集UIは引き続き未検証。

横断修正: 他19ファイルの113箇所はすべてGroupのlayout.type=flowであり、実機が返す対応一覧default/constrained/flex/gridにない指定だった。defaultへ統一し、変更したPHPの構文検査は全成功。verify-layout-definitions.pyを追加し、テーマのPHP/HTMLソース内に明示されたlayout.typeの308箇所が対応一覧に含まれることを確認した。PHP実行で展開される全内容や保存HTML全体の妥当性を保証する検査ではない。フッター編集8検査も再成功。継承面の回帰を次に取得する。

横断修正後の継承428、content-faces191成功。継承AC2件を現行ソース・全証跡行と照合し部分確認へ復帰し、他27 ACはstaleのまま。フッターのempty/filled初期状態をPC/SPで撮影し、52検査成功。カタログbuilderに2候補4画像の追加処理を用意したが、全証跡が現行になるまで本体生成は行わない。npm test成功。header-navigationから案内/学習/品質/イベント/フォーム/検索への回帰を専用labで順次実行中。

Claudeレビューの追跡: PR #174に既存通知HEADのレビューコメントがあり、正式receiptはIssue #179のengines.node未宣言で拒否されていた。依存HELIXの宣言>=24.15.0 <25に合わせpackage.jsonとlockのルートメタデータへ同じ範囲を追加し、.node-versionに検証中の24.19.0を指定。通常E2Eのsetup-nodeをこのファイル参照へ変更。ローカルassertNodeEngineRuntimeAuthorityはok:true。依存バージョン・integrityは変更しない。

管理対象harness-checkのsetup-nodeに同じ入力を追加するとconsumer-ci-workflowのsetupNodeInputsEmpty規則に違反したため、そのCI変更だけ戻した。HELIX本体はread-onlyのため規則を変更しない。harness-checkは従来通りrunnerのNodeに依存し、範囲固定の保証はまだない。Issue #179の実receipt/ACK記録はClaude側の再実行待ちであり、完了扱いしない。

証跡対応の追加: WT-AC-PARTS-01Cの条件本文と実測52行・Site Editor本文操作8行を照合し、partialとして登録。メニュー選択/リンク編集/削除のUI、グループ別メニューと階層を残件として明記。未対応264/部分7/確認2/stale21。完遂扱いしない。ヘッダー/案内/学習/品質の既存6 ACも新しい成功証跡へ復帰。READMEの古い先頭件数を除き、最新件数を生成JSONから確認する方法へ変更（#178）。末尾の旧件数は日付付きの過去記録として区別した。

イベント証跡の出力先を修復: 今回の逐次実行はevent2系統に--strictを付け忘れ、成功121/341行がbaseline側へ保存されていた。ソースを精読し、同フラグが検査内容を変えず出力先と失敗時exitだけを変えることを確認。completed・全成功行・現行source hashを検査してverify側へ移し、上書きされたhistoric baselineはHEADの内容へ復元した。証跡対応付けは最初のhash不一致で書込み前に止まっており、古いhashのまま昇格していない。

PAID-01B/C・BLP-01C・EVENT-01A/Bを現行実測へ再対応付けし、確認2/部分12/stale16/未対応264。フォーム種類282検査成功、段階検証以降の実行を継続中。

Claudeの#175〜179と階層メニュー申し送りを[レビュー対応表](REVIEW-FOLLOWUP.md)へ整理した。指摘本文をGitHubから取得して、未修正・ローカル修正・実証待ちを分離。#177の提案をそのまま実装せず、postpass cookieの実形式と解除境界を確認した上で設計する。フォーム段階395検査成功、配置検証を継続中。

今回差分のまとめ: 全依存証跡の再取得後、AC監査は確認13/部分17/未対応264/stale0。カタログ613候補/1098画像へ正規生成。既存操作18件と新規フッター比較1件が成功。PHP変更16ファイルの構文検査、既存unit/security 201 tests / 492 assertions、npm testの要求整合性とconsumer検査も成功。既存PHPUnitはPoC全体の保証ではなく、フッター実機52/編集8/レイアウト宣言308の証拠を別途扱う。

レビュー復旧: Node設定だけをf8e6124として#174へpushし、現HEADの3 CI run完了・成功後にgithub pr-notifyで再通知（CI根拠run34248975577 attempt1 success）。投入成功は正式receipt取得ではない。共有ルールの開始時読込は統合層f5d30edで接続し、実hookコマンドの出力を確認した。今回のPLAN追記はこのスレッドの既存差分への追記であり、所有記録が失われたhosted preflightは対象2文書を限定して明示overrideした。
