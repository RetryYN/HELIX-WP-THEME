# 記事・LP・イベントの読みやすさ

Astra lowによる現行テーマ限定の改善。旧テーマ・pluginsは変更しない。

## 根拠と設計

変更前の実機画像を同じ一時fixtureで撮影した。記事には目次が2つあった。自動生成元はarticle.jsではなくfunctions.phpのthe_content filterであり、JSは開閉・現在位置強調のみを担う。article-kitの手書き目次を除き、PHPによる見出し導出を唯一の本文目次生成元にした。既存DBへ展開済みの手書きHTMLを一括移行する変更ではない。

LP split heroの日本語見出しを3つの翻訳可能な文節で囲み、文字サイズを調整した。inline-blockを使用し、文節単位の行送りを優先する。翻訳文節が列幅を超えた場合は折り返しを許容する。改行方式は[W3C CSS Text](https://www.w3.org/TR/css-text-3/)と[visual formatting model](https://www.w3.org/TR/CSS22/visuren.html)を参照した。

イベントのinline-text型はdlのラベル/値に分け、PCでは既存の文章状配置、SPでは項目ごとの2列を使う。他の開催情報型を変更しない。

## 受入結果

`node .../reading-layout/probe.mjs after` で3面×390/1440px×JS有無の12条件、および記事toc:noneの4条件をassertする。

- 全12条件HTTP200、横overflowなし。
- 記事4条件: 目次1件、全リンク先存在。toc:noneの4条件: 目次0件。
- LP4条件: 3文節がそれぞれ1行に収まる。390/1440の目視で語中改行なし。
- イベント4条件: ラベル/値の5項目を維持。SPのラベル列と行区分を目視確認。
- PHP変更3ファイルのphp -l正常。
- 変更前後の同条件画像、構造計測JSONを保存。一時fixtureはslug不在を確認して作成し、finallyで作成IDとslugを照合して清掃。不在結果をJSONに保存。

対応候補: WT-SCR-05 / TocAnchor（本文目次）、WT-SCR-06 / LOOK-01B（LP・イベント両幅）。要求全体の完了やG3承認を示す証跡ではない。

## 残件

POTの最終再生成・広域の既存source digest更新は親レーンと調整する。翻訳済み長文・全variationの描画は未検証。既存記事へ展開済みの旧手書き目次HTMLは、このパターン修正だけでは書き換わらない。
