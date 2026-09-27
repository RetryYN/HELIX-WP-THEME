# LP hero の非選択画像読み込み

LP template は4種類のheroを同じHTMLに含み、表示中のvariantをCSSで選びます。従来は非表示のhero画像にも `loading="eager"` と `fetchpriority="high"` が付いていたため、選ばれていない画像まで取得される可能性がありました。

テーマの `render_block_core/html` にLP専用フィルターを登録し、`wt-lp-hero-slot` の中で `wt_opt('lp_hero')` が選択したvariant以外のhero画像だけを遅延読み込みにします。選択中の画像とslot外の画像は変更せず、未知のvariant、DIV/SECTIONの境界不整合、選択heroの欠落を検出した場合は元のHTMLを返します。

WordPress 7.1.2で4 variantそれぞれのPHP描画を検査し、68件の属性・画像数・順序・範囲チェックが成功しました。Chromium 156.0.8075.0では4 variant × SP/PCの8条件が成功し、選択画像のdecodeと別URLの非選択画像が要求されないことを確認しました。split/fullbleedは同じhero画像URLを共有するため、そのURLを非選択画像の取得失敗には数えません。検査レポートと画面画像はCI artifactに保存します。

ローカルの専用WordPress環境では、検査対象の候補テーマを読み取り専用でmountし、WordPressのhome URLと `WTCF_BASE_URL` を一致させてから次のように実行します。各値は手元のloopback環境に合わせてください。

```bash
WTCF_BASE_URL=http://127.0.0.1:8098 \
WTCF_WP_CONTAINER=helix-content-wp \
CHROME_PATH=/path/to/chrome \
node scripts/verify-lp-hero-images.mjs
```

この回帰検査は画像の属性・取得を確認するもので、Core Web Vitalsの閾値達成を示しません。正式43コマンドはすべてexit 0、rebind checkは0でした。追加12件のproducerと最終artifact source-binding checkも成功しました。追加実行にはlayout-definitionsとhome-source-qualityの静的producer、header-navigation（62 checks）、HOME関連producer、HOMEのLighthouse 3回測定が含まれます。

HOMEの追加Lighthouse測定はChrome binary 156.0.8075.0、Lighthouse 13.5.0、mobile 412×823 / DPR 1.75 / `simulate` で行い、LCP推定値は順に2257.1623、2332.7623、2333.4761 ms、observed LCPは817、552、1559 ms、CLSは各0、転送量は各1,150,892 bytesでした。初回のvalidator集計は、実binary版とLighthouse user-agentの短縮表記を混同してexit 1になりました。3回のraw収集CLIはすべてexit 0で、保全済みrawを修正版validatorで再集計し、測定自体は再実行していません。このHOME測定だけではINPや8条件の性能受入を評価しておらず、Core Web Vitals全体の達成を示しません。

正式43コマンドに伴うcleanup assertions 69件は成功しましたが、その実行前後でthemeModsの内容hashに差が残りました。差はWordPressのtheme切替時に `sidebars_widgets.time` が更新される動作と整合します。ただし開始前の値を保存していなかったため、正式43時点のthemeMods完全一致は確認できません。追加の実機検証では各実行前後のthemeMods snapshotが一致しました。

## VPS停止後のソース保全

このブランチは未完了の保存用Draftです。現HEADの受入合格を主張せず、マージしません。実機検査・証拠再生成は停止しています。元の作業ツリーの画像・生成証拠・私用ログはこの保存PRに含めていません。再開時にソース、実機、証拠の対応を確認してください。
