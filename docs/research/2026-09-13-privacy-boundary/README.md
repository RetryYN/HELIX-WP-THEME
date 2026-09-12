# テーマ内の計測ID・広告コード境界

`WT-NFR-PRIV-01` のうち、現行テーマとコンテンツfixtureプラグインが計測ID・広告コードの生値を保持しない境界を検証する。

検査対象は現行成果物の `helix-wt` と、その実機表示に使うコンテンツfixtureプラグインに限定する。旧 `themes/agent-neo-theme` と旧 `plugins/agent-neo-*` はread-only参照資産であり、現行成果物へ混ぜず、本検査の対象にも含めない。

```sh
npm run privacy:verify
```

PHP・JavaScript・HTML・JSON・CSSを対象に、GA4、Tag Manager、Universal Analytics、Google Ads、AdSense、Meta PixelのID形と、代表的な広告script・markupを検出する。実装ソースを全走査し、各入力ファイルのdigestを結果へ保存する。代表的な不正値を一つずつ投入する負例と、外部所有を示す値なし契約が通る正例も含む。

これはテーマ同梱物に生値がないことの検査であり、実運用DB、第三者プラグイン、本番HTML、人気集計の保存内容を確認するものではない。テーマ外から注入される計測・広告コードの同意制御は `WT-FR-TAG-01`〜`03` の別要求で扱う。
