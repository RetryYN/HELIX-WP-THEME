# HOME hero 画像読込みのテーマ組込み

先行PoC（`../2026-09-27-home-hero-image-loading/`）で確認した処理を、現行テーマの `inc/home-hero-images.php` へ組み込む。
非選択heroの画像だけを遅延読込みにし、選択案の画像属性と表示を維持する。
CSS・画像素材・候補数は変更しない。

## 再現

このチェックは、現行テーマをマウントしてHOMEを表示できる専用WordPressラボを使う。
既存のContent Labを作成した場合は、その起動時と同じ `WTCF_BASE_URL` と `WTCF_WP_CONTAINER` を設定する。

```sh
node scripts/verify-home-hero-images.mjs
```

検査はDBを書き換えない。WordPress内の実ファイルとローカルソースのハッシュ、
テーマ名、表示URLを照合してから実行する。開始時に前回の完走状態を無効化する。

- PHP: 9種類について、登録済みの `core/html` フィルター、選択画像の属性保持、
  非選択画像の遅延、入れ子の非表示継承、hero外と無関係HTMLの不変を検査する。
- ブラウザ: 390/1440pxそれぞれ9種類を表示し、選択heroが1つであることと非選択画像の属性を検査する。
  選択画像はカルーセルの画面外分もスクロールで露出させ、実際の読込み成功を確認する。
- 出力: `verification.json`。入力ソースを変更した場合は再実行する。

この結果は画像読込みの回帰検証であり、4面×SP/PCの性能受入、INP、CI上のLCP合格を意味しない。
先行PoCの測定値を本体組込み後の性能測定として転記しない。

## 今回の確認

WordPress 7.1.2上でPHPの216項目とブラウザの18条件が成功した。
意図的にラボと異なるURLを指定した負例は非ゼロ終了し、保存結果が `completed: false` になることを確認した。
その後、正しいURLで正例を再実行して `verification.json` を更新した。

## 通常表示のLighthouse計測

HOMEの通常表示を、専用記事ラボ（WordPress 7.1.2、PHP 8.3.33、`helix-wt`）で3回連続測定した。
この測定ではテーマを変更せず、MU pluginも追加しない。Lighthouse 13.5.0とChrome for Testing 156.0.8075.0を使い、Lighthouseのmobile設定（412×823、device scale factor 1.75、`simulate` throttling）で同じURLを測った。実測条件とソースhashは [`performance.json`](performance.json) に記録した。

再現時は `CHROME_PATH` にChrome 156、`LIGHTHOUSE_BIN` にLighthouse 13.5.0の実行ファイルを指定し、raw JSONはGit管理外の一時ディレクトリへ出力する。

```sh
export CHROME_PATH=/path/to/chrome-156/chrome
export LIGHTHOUSE_BIN=/path/to/lighthouse-13.5.0
export HELIX_HOME_LH_OUTPUT_DIR="$(mktemp -d)"
for trial in 1 2 3; do
  "$LIGHTHOUSE_BIN" http://127.0.0.1:18255/ \
    --chrome-flags='--headless --no-sandbox --disable-dev-shm-usage' \
    --only-categories=performance --output=json \
    --output-path="$HELIX_HOME_LH_OUTPUT_DIR/home-$trial.json" --quiet
done
```

記録値はLighthouseの推定LCPが2,330.7922 / 2,329.4988 / 2,329.4704 ms、`observedLargestContentfulPaint` が1,397 / 266 / 1,363 ms、CLSが0.0002153 / 0.0002153 / 0、転送量は各1,150,892 bytesだった。推定LCPとobserved LCPは別の監査値として記録し、混同しない。この3回はHOMEの通常表示だけの計測であり、性能ACや記事・LP・一覧・商品比較の性能合格を示さない。

固定フッターのスクリーンショットでは、バナーリンクが黒文字・中央寄せになる既存の表示問題も見える。これはmainの既存CSSで `.wt-side-widget a:not(.wt-lp-cta-action)` が `.wt-side-banners a` より強く適用される問題として確認した。今回のhero画像helperによる回帰ではないため、HOME変更へ混ぜず別の修正対象として扱う。
