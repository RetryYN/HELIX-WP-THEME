# SEO構造化データ契約 PoC

`WT-FR-SEO-04` と `WT-NFR-SEO-01` のうち、公式出典との対応、単一 graph、必須プロパティ、対象外型の拒否を専用fixtureで検査する。検索結果への掲載、Rich Results Testの本番接続、WordPress 7.2実機は主張しない。

`article.html` は可視本文・meta・JSON-LDを同じfixtureから生成し、`registry.html` は型ごとの必須プロパティと非推奨型の出典台帳を表示する。負例は必須プロパティ欠落、対象外型追加、古い出典日をそれぞれ拒否する。

残件は Google の検索表示適格性、実サイトの第三者SEOプラグイン譲渡、12ページ種別、SP/PCサイト設定、WordPress 7.2の保存経路である。ローカルの契約検査を完了扱いに昇格させない。
