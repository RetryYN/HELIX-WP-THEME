# 目次の保存設定と見出し追従

対象ACは `WT-AC-VOCAB-02A/B`（partial）。658候補・1187画像を監査し、目次は既に box / float / collapsible / none と box-open の5候補があることを確認した。新型を追加せず、既存4候補の画像を同じ記事の投稿メタ `wt_toc` から描画した実機証跡へ更新する。候補IDと総数を維持する。

既存 `functions.php` は登録メタとサイト既定を読み、本文描画時にH2/H3から目次を導出する。既存CSSとnative detailsで4型を検証したところ、PCのfloatがsidebar right/bothでPR表記に重なる欠陥を発見した。theme.cssを限定修正し、sidebar right/left/bothではfloatを本文内へ戻す。本文前で全体像を見せるbox、sidebarなしPCでは横レールを使うfloat、初期状態を閉じるcollapsible、本文へ直行するnoneを比較する。floatはSPとsidebar併設PCで本文内へ戻る。boxはSPのJSありでは初期閉、JSなしでは初期開となる。どちらも内容は失われない。

## 検証

`node scripts/verify-toc-settings.mjs` は専用WordPress labに所有者付き投稿2件と固定ページ1件を作る。保存値の読み戻し、4型×PC1440/SP390×JS有無、reduced-motion、横溢れ、見出しラベルとリンク先、H3階層、初期開閉、キーボード操作、対照記事の不変を確認する。さらにメタ解除時のサイト既定継承、見出し編集追従、保存本文に生成目次がないこと、H2が2つの場合の非表示、固定ページへの非挿入を検査する。finallyでサイト既定を戻し、自分のfixtureだけを回収する。

画像はPC/SPの公開ページ全体を撮影し、floatの本文外レールも残す。カタログは既存候補の画像・選択説明だけを更新する。`npx playwright test tests/e2e/toc-selection-catalog.spec.ts` で4候補発見、2型比較、端末画像、保存値・継承説明、選択理由の保存と再読込を確認する。

## 公式一次資料（参照2026-09-19）

- [WordPress the_content](https://developer.wordpress.org/reference/hooks/the_content/): DB読出し後・画面描画前のフィルタであり、main queryとloopを限定する。現行目次の生成位置と適用境界を照合した。
- [WordPress core filter registration](https://github.com/WordPress/WordPress/blob/master/wp-includes/default-filters.php): block展開を含む本文フィルタの順序を再観察した。
- [MDN details](https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/details): native開閉とopen属性を確認し、JS無効でも開閉可能なことを実機で検査した。

## 残件

ページ種別ごとの設定UI・REST/MCP往復、全style variation、任意のsidebar幅・本文幅の全組合せ、任意HTML見出し・既存ID衝突・巨大目次、全項目44pxと文字サイズの一般保証は未確認。固定ページ非挿入は現行のpost限定条件の証拠であり、ページ種別の設定機能を実現済みとは扱わない。AC全体をverifiedへ昇格させない。

## 修正と再検証の境界

`baseline.json` は修正前203検査中10失敗を記録する。right/bothでPR表記と重なり、right/left/bothの本文内fallbackが未適用だった。本文のp/H2/H3矩形と目次矩形の交差を判定し、right/left/both/none × PC/SP × JS有無の16構成を検査へ追加した。CSS変更後の最終結果は `verification.json`。baseline内の画像digestは過去撮影時の記録であり、最終画像の検証には使用しない。

CSS変更で既存24ACのsource digestがstaleになる。`revalidation-impact.json` にAC→proof→verifierを記録した。親レーンが関連verifier再実行とacceptance-evidence再束縛を行う。staleを消すためだけのdigest書換えは行わない。

## 最終実機結果

修正後 **203/203成功**。4型PC/SPの8画像とfloat sidebar4型PCの4画像を保存した。rightの本文内fallbackとSP collapsibleを目視し、PR表記・本文が隠れないことを確認した。JS構文検査と `git diff --check` は成功。

カタログ比較E2Eは親レーンの既存証拠再検証・ビルド後に実行する。最初の試行では検索対象外のdescriptionを検索して0件となったため、既存4候補のlabelを「目次設定比較」にし、テストもそのlabelを検索するよう修正した。修正後のE2E成功はまだ主張しない。
