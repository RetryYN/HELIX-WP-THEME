# reduced-motion ローカル検証記録

2026-09-10。対象要求 WT-NFR-A11Y-02、AC WT-AC-A11Y-02A / WT-AC-A11Y-02B。

`node docs/research/2026-09-10-reduced-motion/verify.mjs` は PASS。

- 配布テーマ style.css、Core管理画面 app.css、再現テーマ theme.css に実CSSを読み込み、強制したanimation/transition/smoothを停止。内容の可視性と手動ボタンを維持。
- 実article/side/footer/home/reveal.jsを実行。reveal即表示、counter最終値と初期frame 0、counter途中の設定変更による最終値確定を確認。
- 先頭移動、関連送り、関連ドット、home送り、homeドット、汎用carouselの6操作は reduce → no-preference → reduce の各状態で auto / smooth / auto を確認。ロード後の設定変更にも追従。
- 同じ `gate(sample)` へ正常fixtureと animation、transition、smooth、内容非表示、操作不能の5故障fixtureを直接投入し、正常のみ受理。

監査: themes/agent-neo-theme、plugins/agent-neo-core、prototype再現ソースのJS/PHP/HTMLを autoplay / requestAnimationFrame / setInterval / scrollTo / scrollBy / animate で走査。現在のslider/carouselに自動送りはない。唯一の描画frameはarticle counter。homeの60000ms intervalはイベント残り時間のテキスト更新であり継続装飾・自動scrollではないため維持。配布depth style variationとthird-party placeholderのtransitionも共通CSS停止が適用される。静的transformは一律解除しない（方向アイコンと配置を壊さない）。

限界: 実WPの第三者プラグイン、投稿者が追加する任意HTML/CSS/動画、ブラウザ拡張の動きは本ローカルfixtureの対象外。autoplay未実装という確認は現在の所有ソースに限る。全体完了や本番への反映は意味しない。

補助検証: `npm test` PASS（要求検証・V-model・capability・AI境界・公開情報/i18n verifier・consumer doctor・31 unit tests）。`git diff --check` PASS。公開情報shellの `--base-ref HEAD` は未commit差分を検査せず0行だったため、この結果を今回差分の公開承認根拠にしない。staged検査は親レーンのcommit準備時に必要。

追加検収: `npm run reduced-motion:verify` は19行PASS、failed=0。`verify.json` に名前付きrowsと実ソース・verifier・package・CIのSHA-256を保存する。時間や環境絶対パスは含めない。CIは既存test.ymlのPlaywright jobにてChromium install後に実行し、JSONをartifactへ保存。関連ソース・verifier・package変更もpush/PRトリガーに追加。npm testにはbrowser依存を追加しない。

最終検収: autoplay=falseを共通gateの必要条件に追加し、autoplay=trueの負例を同じgateで拒否。3所有rootのJS/PHP/HTMLを再帰列挙し、autoplay/play呼出し不存在と未監査timer不存在を6名前付きrowで検証。対象一覧と全ソースdigestはverify.jsonに記録。既存timerはarticleのresize/コピー通知、homeの分単位テキスト更新、ad-trackingの計測だけを全文SHA-256固定で許容し、将来のcallback変更・timer追加を無条件除外しない。26 rows PASS、failed=0。連続2回のJSONはバイト一致。6操作経路のブラウザ検証を維持。
