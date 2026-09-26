# HOME hero の非選択画像と LCP（#352）

`WT-NFR-PERF-03` の遅延原因を切り分ける WordPress 実機 PoC。
テーマ本体への組込み前の実験であり、受入台帳の状態は変更しない。

## 観察と修正案

現行 `helix-wt` の HOME hero は9案を同じHTMLへ出力し、選択案をCSSで表示する。
文字だけの既定案でも、非選択案にある `loading="eager"` の画像が取得される。
ブラウザー上で対象画像の描画矩形が0件なのにリクエストが発生することを確認した。

`hidden-hero-loading.php` は、描画時の `wt_opt('home_hero')` と同じ選択値を使い、
非選択案の画像に `loading="lazy"` を設定して `fetchpriority` を除く。
選択案の属性、全案のDOM、CSS、画像ファイルは保持する。
今回はmu-pluginとして隔離ラボだけに適用し、比較終了時に除去する。

Lighthouse の `simulate` による LCP と `observedLargestContentfulPaint` は別の測定条件の値であり、
一致を要求しない。旧 Lighthouse 12.1.0 / Chrome 153 の `frame_sequence` 診断例外と、この違いを混同しない。
比較には Lighthouse **13.5.0** と Chrome for Testing **156.0.8075.0** を使用する。

2026-09-27の実測は `verification.json` に記録した。18通りの画像比較はすべて一致し、
カルーセルをスクロールした後の画像読込も成功した。

| 既定HOME・モバイル | 修正前 | 修正案適用後 |
| --- | --- | --- |
| 推定LCP（3回、秒） | 17.404 / 17.405 / 17.404 | 2.404 / 2.403 / 2.479 |
| 転送量 | 3,572,560 bytes | 1,157,988〜1,538,507 bytes |
| CLS（最大） | 0.000228 | 0.000228 |
| 診断例外 | 0件 | 0件 |

これはローカルラボでの比較測定であり、CI上の性能合格やフィールドINPは証明しない。

## 再現

既存ラボを共有せず、空の専用 Compose project（名前は `helix-perf352-` で始める）を用意する。
Compose 定義は `../2026-09-26-news-column-purpose-gap/compose.yaml`（WordPress 7.1.2）を再利用する。
DBパスワード2種とポートを設定した非公開envファイルをリポジトリ外に用意し、値はログへ出さない。

```sh
export HELIX_PERF_PROJECT=helix-perf352-repro
export HELIX_PERF_ENV_FILE=/path/to/private.env
export HELIX_PERF_BASE_URL=http://127.0.0.1:18252/
# envファイル: HELIX_ARTICLE_DB_PASSWORD / HELIX_ARTICLE_ROOT_PASSWORD / HELIX_ARTICLE_PORT=18252
docker compose -p "$HELIX_PERF_PROJECT" --env-file "$HELIX_PERF_ENV_FILE" \
  -f docs/research/2026-09-26-news-column-purpose-gap/compose.yaml up -d --wait
docker compose -p "$HELIX_PERF_PROJECT" --env-file "$HELIX_PERF_ENV_FILE" \
  -f docs/research/2026-09-26-news-column-purpose-gap/compose.yaml \
  exec -T -e HELIX_PERF_BASE_URL="$HELIX_PERF_BASE_URL" wordpress php \
  < docs/research/2026-09-27-home-hero-image-loading/fixture.php
npm ci
```

Lighthouse 13.5.0を専用ディレクトリへインストールする。次はLinuxでの例。

```sh
HELIX_PERF_TOOLS="$(mktemp -d)"
npm install --prefix "$HELIX_PERF_TOOLS" lighthouse@13.5.0 @puppeteer/browsers@2.11.2
"$HELIX_PERF_TOOLS/node_modules/.bin/browsers" install chrome@156.0.8075.0 --path "$HELIX_PERF_TOOLS/browser"
export CHROME_PATH="$HELIX_PERF_TOOLS/browser/chrome/linux-156.0.8075.0/chrome-linux64/chrome"
export HELIX_PERF_LIGHTHOUSE_BIN="$HELIX_PERF_TOOLS/node_modules/.bin/lighthouse"
node docs/research/2026-09-27-home-hero-image-loading/verify.mjs
```

検査は既存の同名mu-pluginがあれば停止する。修正前・修正後それぞれで9案×390/1440pxを描画し、
選択案が1つであること、スクリーンショットの一致、スクロールで露出した画像の読込成功を確認する。
続いてHOMEのモバイルLighthouseを各3回実行し、測定値・版数・診断例外の有無を確認する。
生レポート・画像・集計はGit管理外の `poc-wp/perf352/` に保存する。
`completed` は実験の完走だけを意味し、製品性能の合格ではない。
開始時に前回の完走状態を無効化する。ブラウザー起動を意図的に失敗させる負例で、
非ゼロ終了と `completed: false` を確認した。

## 残作業

- 本体への組込み、現行HEADのCI、共通ファイルに依存する回帰証跡の再生成と正式rebind。
- 記事・LP・一覧・商品比較の4面×SP/PC、実際の操作からのINP、性能悪化の負例。
- 要求本文の Lighthouse major/insight ID固定、`unload` 0、`no-store` 不使用、`pagehide` close のCI確認。
- HOMEで得た結果を他の画像主体のhero案のLCP合格へ拡張しない。LCPの受入閾値は2.5秒のまま。

## 一次資料

- [Lighthouse の throttling](https://github.com/GoogleChrome/lighthouse/blob/main/docs/throttling.md)
- [WordPress HTML Tag Processor](https://developer.wordpress.org/reference/classes/wp_html_tag_processor/)
- [ブロック種別ごとの render filter](https://developer.wordpress.org/reference/hooks/render_block_this-name/)
