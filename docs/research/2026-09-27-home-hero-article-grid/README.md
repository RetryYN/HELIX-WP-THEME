# HOME article-grid 画像の非選択時読込検証（#377）

HOME hero の `article-grid` 案を選択していない間に、記事featured imageが取得されるかを確認するローカルWordPress検証です。実投稿queryに入るowner付きfeatured-image投稿を一時作成し、`text-only` と `article-grid` のSP/PC各条件を比較します。テーマ実装の変更や性能受入判定は行いません。

| 表示条件 | viewport / DPR | 必須assert |
| --- | --- | --- |
| `text-only`（article-grid非選択） | 390×844 / 3、1440×900 / 1 | gridが非表示、画像がlazy、対象画像requestが0件 |
| `article-grid`（選択） | 390×844 / 3、1440×900 / 1 | gridが表示、対象画像request成功、`decode()`完了、画像がviewport内に表示 |

各実行のraw reportはGit管理外の`local-evidence/issue-377/attempts/`にattempt別で保存します。repo内の`verification-summary.json`にはURL、container名、投稿・attachment ID、slugを含めず、結果、版、ソースhashだけを残します。

検証前後にテーマの対象入力10ファイルのworktree/mounted hashを照合し、所有fixtureのcleanupと不存在、`wt_home_hero` optionの不変を確認します。プレビューqueryで既存optionは変更しません。実行先はloopbackの開発WordPressに限定してください。既定値以外の既存labを使う場合は、同labの所有者と空き状況を確認したうえで環境変数を指定します。

```sh
WTCF_BASE_URL=http://127.0.0.1:PORT \
WTCF_WP_CONTAINER=WORDPRESS_CONTAINER \
node docs/research/2026-09-27-home-hero-article-grid/verify.mjs
```

この検証のbrowserはrepo lockfileのPlaywright 1.61.0に付属するChromiumです。#352性能CIで用いるChrome 156.0.8075.0のLighthouse結果とは別の、画像読込の機能PoCです。過去の探索時に発生した待機条件の誤りはテーマ挙動の結果として数えず、以下のsummaryは修正済み検証器による独立実行を示します。

## 今回の実行

[`verification-summary.json`](./verification-summary.json)に、最新main上のソースhash、実行版、4条件の結果を記録しています。summaryの成功は、記載したローカル構成でIssue #377の先読みを再現しなかったことだけを示します。CWV閾値、Lighthouse性能、他のHOME variant、実ユーザー計測の受入を意味しません。
