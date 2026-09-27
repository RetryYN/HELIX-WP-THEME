# HOME完成画面の比較と表示検収

既存の企業・サービス・メディア・店舗/スクール・学校/団体の5目的を、heroからfooterまで同じ幅で比較する。用途別の既存型を削除・改名せず、カタログのHOME入口では完成画面を先に並べ、必要になった段階で部品候補へ進む。採用/保留/除外、理由、比較候補、PC/SPの復元は既存カタログの保存経路を使う。

対象は現行 `helix-wt` のみ。新しい設定画面やテーマ設定の保存経路は追加していない。カタログの選択メモはWordPressへの設定適用ではない。

## 変更

- heroと最初の区間の間隔、本文区間の余白、写真枠、カード内の配置を共通尺度で調整。本文列が狭い組合せではカード/heroを折り返す。
- 同じ行のカードを本文長短に応じて伸ばし、見出し・本文を切り捨てない。長い日本語の見出しは必要な行数へ折り返し、無理な1行固定をしない。
- 写真型のhero/sliderの文字領域に不透明な背景を置き、写真明度に依存しないコントラストを確保。
- SP固定導線の問い合わせを主操作として区別。footerのinline paddingが既存の固定領域補償を上書きする重なりを検出し、HOMEで固定導線が有効なときだけfooter内容の後ろに余白を確保。
- HOMEの検索枠とhero CTAの検索型をWordPress標準GET検索に接続。JS有無で同じ検索語を渡す。
- 静的だったお知らせタブをキーボード操作可能にする。JS無効ではタブ操作を出さず、全件一覧を表示。
- 完成画面の比較表に入口の導線、情報量、区間順、sidebar開始位置、共通/独自/非表示の所属を表示。5目的の実際の区間順をDOMと照合する。

## 証拠と再現

`choices.json` が5目的の比較メタデータ。`verification.json` が全宣言と危険な組合せ、`isolation.json` が所属と他面への波及検査。`before-*.jpg` / `after-*.jpg` は同じ本文・正規取込み画像・1440px/375pxでのCSS比較であり、全機能のコミット前後比較ではない。beforeは現在の検証コードで専用 `home-completion.css` の応答だけを空にした状態、afterはそのCSSも読み込む状態。検索・タブの動作変更は個別検査で判定する。

遅延画像はページ末尾までスクロールし、表示画像のdecode完了を待ってから撮影する。完全なページの原寸画像を保存し、画像のhashを検証JSONとカタログ生成器で照合する。

検証用WordPressが起動した状態で、リポジトリルートから以下を順に実行する。他の検証用DB書込みと並行実行しない。

```sh
node scripts/verify-home-isolation.mjs
node scripts/verify-home-completion.mjs --baseline --finished-only
node scripts/verify-home-completion.mjs
```

main検証器は専用lab名を照合し、実行ごとにUUIDで所有する架空記事6件と画像6件を作成する。画像はWordPressの正規取込みでmetadata/thumbnailを生成する。正常終了・例外時に同じ所有markerのIDだけを削除し、不在を確認する。プロセス強制終了時はlabの一時state内にrun/PIDを残し、次回実行を止める。記録されたプロセスが終了したことを確認した上で `node scripts/verify-home-completion.mjs --recover` を実行する。生きたプロセスのfixtureは回収しない。テーマ設定・既存記事は書き換えない。

画面の確認は検証用WordPress上で行う。別HTMLの選択カタログは使用しない。PoC操作の再現には上記の `verify-home-completion.mjs` を使う。

## 範囲と残件

- 主対象は `WT-FR-HOME-01` / `WT-AC-HOME-01A/B`。`WT-FR-LOOK-01` / `WT-AC-LOOK-01C/D/E` と `WT-FR-PARTS-03` / `WT-AC-PARTS-03A/B` はHOMEで使う範囲の追加証拠であり、全面の完了ではない。
- 選択宣言は既存プレビューURLで再現する。Site Editorでの全HOME設定の保存/未保存離脱/不正値拒否、設定JSON・MCPとの往復は未検証。
- 全宣言を各1回以上と危険な組合せで確認するが、直積の全組合せは検査していない。
- PHP patternには非選択型のHTMLも残る。server-sideの重い面の排除、端末判定とviewport差、キャッシュ分離の完了を示さない。
- 既存の5目的別の区間構成は保持しているが、本文・電話番号・動画枠・問い合わせフォーム・ロゴ・実績は架空/PoCのまま。動画再生・問い合わせ送信・実在サイトの管理データ接続は完成していない。
- 長い見出しを隠して1行にすることはしていない。LOOK-01Cの全見出しの1行既定・型台帳全面対応は別途残る。
- 写真上の文字検査は現在のpaletteの不透明パネルとその本文を対象にする。全style variation、任意画像、全サイトのaxe適合や色覚検査を保証しない。
- 保存済み記事や変更済みtemplateの移行、全WordPress/PHP対応マトリクスはこの検証の対象外。

## 入力と参照

既存要求IR、[用途別台帳](../2026-09-05-parts-pattern-taxonomy/by-purpose.md)、[HOME再観察](../2026-09-05-parts-pattern-taxonomy/home-event-recapture/summary.md)、[追加観察](../2026-09-05-parts-pattern-taxonomy/home-event-recapture-v2/summary.md)を入力に、現行の5目的・hero9型を保持した。新たな外部サイト観察を済ませたとは扱わない。

WordPressの編集導線は[Site Editor](https://wordpress.org/documentation/article/site-editor/)と[Patterns](https://wordpress.org/documentation/article/site-editor-patterns/)を参照（2026-09-15確認）。独自設定UIを増やす根拠としては使っていない。

## 最終検収の記録

- 全宣言12軸58値、完成構成5件、危険組合せ3件の計66シナリオをPC/SP・JS有無で描画。個別の操作/負荷状態を含め **3,235/3,235 PASS**。
- header/footer/固定導線の所属、他4面へのHOME軸の混入、footer末尾の到達は **131/131 PASS**。
- 同一条件の寸法比較は **4/4 PASS**。heroから本文への間隔はPC/SPとも約70.08pxから40pxへ。SPの固定バー上端843pxに対し、footer最後のリンク下端は864.05px（約21px重複）から792.11px（約51px余裕）へ変化した。`presentation-measurements.json` と `fixed-footer-before/after.png` を参照。
- baselineの写真上パネル4検査は意図どおりFAIL、専用CSSを読み込む最終測定では4件ともPASS。透明背景を不透明な黒として誤評価しないようalphaも検査する。
- PHP構文3ファイルPASS。WordPress-Core PHPCSは変更前後とも231 errors / 103 warnings、新規増加0。既存違反を残しており、WPCS全体PASSとは扱わない。
- reduced-motion既存検証は **20/20 PASS**。`home.js` の追加によって全文digest fenceが作動したため、旧コード全体が完全なprefixとして残り、60秒countdownのcallbackが不変、追加timer呼出し0であることを再監査した。`timer-review.json` の記録に基づき監査済みdigestだけ更新し、timer禁止の条件は緩めていない。
- 検証用の記事6件・画像6件は所有markerで全削除し、復旧markerも不在。WordPressの既存記事・theme_modは変更していない。

破棄前の独立HTMLカタログE2Eは **27/27 PASS（25.3秒）**。候補選択→完成比較→理由保存→reloadと、完成HOMEタイルの幅いっぱいプレビューをPC/SPで確認した。再生成は638候補・1,147画像・133要求。受入監査はmissing 236 / partial 26 / verified-in-PoC 32 / stale 0で、全体未完了のまま。`catalog-e2e.json` に画像hashと検収結果を記録する。`regression-pending.json` は41件の再検証・再束縛完了と最終stale 0を記録しており、現時点の未解消一覧ではない。

## カタログ目視で見つけた追加修正

全5目的のPC/SP完成画面を見直し、メディア面の画像なしランキングで順位バッジが見出しに重なる既存不具合を確認した。代替画像を作らず装飾枠を確保し、PCでは文字を枠の下、SPでは枠の横へ配置する。`rank-fallback.json` のPC/SP・JS有無の変更前負例と修正後正例は16/16 PASS。`rank-before/after-*.png` で重なりと解消を確認できる。

8,000px級の完成画像をタイルへ全体縮小すると細い縦線になり、目的別の入口が見分けられなかった。完成HOMEタイルだけ上部を幅いっぱいにプレビューする。詳細・比較の全体/幅合わせ/原寸機能と、既存部品タイルは保持する。

full-page画像内の固定導線は撮影開始時のviewport下端に1回描画される。スクロール途中に固定導線が本文へ挿入される意味ではない。footer末尾との関係は別の `fixed-footer-before/after.png` で確認する。
