# 小画面の画像倍率操作を1行に揃える

基準は main `5bb99ef`。320×640px の詳細画面では、画像倍率ボタンの最後の1個が次の行へ折り返す。
3つの同列操作が2+1へ分かれ、操作群と説明がプレビューを下へ押すことをローカルChromiumで確認した。

700px以下の詳細・比較の画像操作群に3等分のCSS Gridを適用し、説明は全列に渡す。
ボタンは12pxの文字・44px以上の高さを維持し、横paddingだけを4pxへ揃える。
倍率の意味、順序、ARIA、画像、選択状態、候補や要求のデータは変更しない。
設計参考: https://developer.mozilla.org/en-US/docs/Web/CSS/minmax

検査は320/390/700pxの詳細・比較で3ボタンの同一行、44pxタップ高、非overflowを測定する。
1440pxで既存の配置を維持することも確認する。320pxのbefore/after画像、ソースdigestと実測を保存する。
既存selection-catalog E2Eを実行する。これはカタログの局所視認性検査で、実機・支援技術・全候補の検収を表さない。

## 実測結果

320px詳細の操作群は169.17pxから117.17pxへ52px短縮し、候補名・説明を同じ画面内で多く確認できる。
比較画面は元から1行だが、詳細と同じ12px・等幅のボタンへ揃えた（操作群114.17px→117.17px）。
320/390/700/1440pxの詳細・比較で44px以上のタップ領域・同一行・横overflowなしを確認し、倍率切替も動作した。
1440pxの各ボタン位置・寸法はbefore/after一致。320px画像を目視し、ボタン文字が欠けず、詳細プレビューが上へ移ることを確認した。

- 新規 `image-controls-selection-catalog.spec.ts`: 4 passed。
- 既存 `selection-catalog.spec.ts`: 34 passed。
- filter-contextの既存検証も再実行。ほか6種類の関連visual captureを各記録のbaselineで再実行し、現CSSに証跡を束縛した。
- 受入台帳・要求正本・意味契約への変更なし。関連スクリーンショットの再生成は現行候補データを使用する。
- 公開情報検査にはリポ外の実在する非公開対応表を環境変数から注入する。

再現: `CATALOG_BASE_URL=<local server> CATALOG_BASELINE_REF=5bb99ef node docs/research/2026-09-08-selection-catalog/visual-quality/image-controls/capture.mjs`。
`CATALOG_CAPTURE_OUTPUT` で保存先を変更できる。配信ソースと作業ツリーの一致を確認し、同一baseline CSSを拒否する。
