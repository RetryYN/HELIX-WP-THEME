# 低い画面で詳細画像の下端を可視範囲へ収める

基準: `41c90a607dc5123ebf684cfb67b420c6d11a802e`。対象はカタログ詳細viewerのCSSのみ。

## PoC

LP standard を詳細で開き「幅に合わせる」を選択、dialog を500pxスクロールした。
844×390pxではviewer top166.5 / height234 / bottom400.5pxに対しdialog bottom370.5pxで30px切れる。
844×320pxではviewer bottom355 / dialog bottom304pxで51px切れる。
1024×600pxでは切れない。sticky位置と60vhの高さを同時に使うため、低い画面でviewer全体が収まらない。
一時CSSでmax-heightをdialogの90dvhから既存の見出し実測変数と余白28pxを引く値に制限すると、
844×390pxでは高さ185px・下端351.5pxとなり、dialog下端まで19pxの余裕を得た。

## 設計と範囲

701px以上の詳細viewerにだけmax-heightを追加する。既存の60vhより可視領域が狭いときだけ縮む。
見出し実測は既存の `--dialog-header-clearance` を利用し、JSは変更しない。
28pxは余白と境界の余裕で、既存のsticky配置で下端に19pxを残す。
画像の倍率・スクロール機構・内容は保持する。共有lab・テーマ・比較画面・要求・受入台帳は変更しない。
[W3C Reflow](https://www.w3.org/WAI/WCAG22/Understanding/reflow.html) の、画像の独立スクロール領域を可視範囲へ収める考え方を参照した。

## 検証計画

844×320/390、390×844、1024×600、1440×900で全体/幅/原寸の3モードを検査する。
低い画面ではsticky中のviewer上下端、幅/原寸の画像末尾到達、閉じる操作を確認する。
通常高・モバイルではbefore/afterのviewer寸法一致を確認する。
同じ基準CSS・同じ画像でbefore/afterを撮影し、source digestを記録する。
Chromiumの代表LP検査であり、全ブラウザ・全候補・WCAG全項目適合は主張しない。

## 実測結果

専用E2E15件成功。30観察（5画面×3モード×before/after）で内容digestは全15組一致。
低い画面の3モードとも切れは0pxとなり、幅/原寸では画像内スクロール末尾まで到達した。

| 画面 | viewer高さ before → after | 下端切れ before → after | 幅モード画像末尾 after / dialog下端 |
| --- | --- | --- | --- |
| 844×320 | 192 → 122px | 51 → 0px | 273.23 / 304px |
| 844×390 | 234 → 185px | 30 → 0px | 339.73 / 370.5px |

390×844 / 1024×600 / 1440×900のviewer幅・高さは全モードでbefore/after一致。
保存画像は低い2画面×before/afterの4枚で、幅モードの画像内スクロール末尾を撮影した。
全スクロール位置、ブラウザUIの動的拡縮、全候補の個別目視は検査範囲外。

関連検査は選択/絞り込み39件、比較15件、画像操作4件成功。
既存12組のvisual captureを実行し、filter-contextも再検証した。
強制配色captureの初回は1440px通常配色control画像のdigest不一致で停止、単独再実行で成功した。
この既存撮影の安定性を今回の改善成果には含めない。

## main更新への追従

並行PRのmerge後、`03e5637` を取り込み、同mainをbefore基準として本captureを再実行した。既存12組も再capture済み。
カタログデータの更新はmainからの取り込みで、本PRは要求・受入台帳を編集しない。

main取り込み後も専用15件・選択/絞り込み39件成功。強制配色captureは通常色darkの画像digest不一致で一度停止し、単独再実行で成功した。
