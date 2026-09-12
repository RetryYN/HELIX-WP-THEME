# 404 回復導線の視覚PoC

対象は現行 `helix-wt` の `WT-AC-TPL-01C`。404の3変種（記事案内 / CTA / 検索候補）に対し、検索・カテゴリ・共通CV導線のまとまりを改善した。

## 変更

- 404コードを既存accentで明確にし、説明本文を既存m尺度（16px以上）へ変更。
- 選択した案内とカテゴリを既存罫線・角丸・余白トークンで囲み、段階的に読める配置にした。
- SPの記事カードを1列にし、CVカードの上端とボタン位置を揃えた。
- 検索欄のフォーカスを明示。候補リンクの長い語句を折返す。
- URLの不正percent escapeで候補生成が例外終了しない。数字だけのpathでも既定候補を消さない。

## 検証

`node docs/research/2026-09-13-notfound-recovery/probe.mjs`

3変種 × 390/1440px × JS有効/無効 = 12条件成功。全条件でHTTP404、noindex、見える変種1つ、h1 1つ、検索欄、比較記事/LP/問い合わせリンク、main内タップ高さ44px以上、説明本文16px以上、横溢れ0、JS例外0。全条件でreduced-motionを指定し、新規モーションはない。加えて数字だけのpathと不正percent escapeを確認。

不正percent escapeの直接HTTP要求はApacheが400にするため、テーマJSの負例は正常404取得後にhistoryのpathだけを変えて同じJSを実行する。サーバー400をテーマ404対応とは数えない。

`npm test`、`git diff --check`も成功。PHP変更なし。PC/SP画像を目視検収し、PCのCVカードに残っていたブロック既定marginの段差を修正した。

## 証跡の範囲

`verify.json`に名前付き検査と実測12条件、ソースdigestを保存。`popular/cta/suggest-{390,1440}.png`がafter、`before-{390,1440}.png`が改善前のsuggest。

リンクの表示とhrefが対象で、リンク先の公開状態・管理画面でのリンク変更・全Site Editor操作・人気集計の正確さはこのPoCの検証対象外。ローカル記事データは既存labのものを使い、新規fixtureやDB設定変更は行わない。色は既存パレットを使用したが、このprobeはコントラスト比を自動計測していないためAA全件合格とは主張しない。

## 調査根拠

2026-09-13参照: [Canada.ca 404 error page template](https://design.canada.ca/recommended-templates/404-error-page.html) は、エラー解消の案内と主要セクションへのリンクを組み合わせる。これを検索・カテゴリ・3種CV導線の順序を維持する根拠にした。[MDN HTTP 404](https://developer.mozilla.org/en-US/docs/Web/HTTP/Reference/Status/404) のステータスの意味を維持し、ホームへの自動リダイレクトを追加しない。
