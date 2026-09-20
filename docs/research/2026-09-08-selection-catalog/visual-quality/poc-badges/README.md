# PoC確認数バッジの読み分け

対象は静的な選択カタログの要求一覧。現行 main `1d97c11` を390pxで観察すると、PoC確認2/2条件と0/2条件が同じ黄地で表示され、一覧の走査で区別しにくかった。要求・受入条件・証拠状態は変更しない。

既存の確認数を保持し、全条件PoC確認は淡緑、一部は淡黄、ゼロは中立色にする。12pxの数字を使い、420px以下では要求IDとバッジを別行へ揃える。緑はPoC確認数だけを示し、製品完成や要求合格を意味しない。部分確認だけでPoC確認済み条件がゼロの要求は0件として表示する。

既存PR #216（選択メモ状態）・#237（入力文字）・#240（証拠索引）を確認し、それぞれの実装対象との重複を避けた。要求正本の WT-NFR-A11Y-01 / WT-NFR-SP-01 にある読み取り・reflowの方向に沿う局所改善。WP 7.2管理画面・製品テーマには適用せず、それらの受入は検証済みとしない。

参照: [W3C Use of Color](https://www.w3.org/WAI/WCAG21/Understanding/use-of-color.html)、[WordPress Accessibility](https://developer.wordpress.org/themes/classic-themes/functionality/accessibility/)。色だけに依存せず数値ラベルを保持し、背景とのコントラストを実測する。

再現:

```sh
CATALOG_BASE_URL=http://127.0.0.1:8127 node docs/research/2026-09-08-selection-catalog/visual-quality/poc-badges/capture.mjs
CATALOG_BASE_URL=http://127.0.0.1:8127 npx playwright test tests/e2e/poc-badges-selection-catalog.spec.ts --workers=1
```

配信元のCSS/JS/HTMLをローカル実体と照合してから、baselineのCSS/JSへ差し替えたbeforeと現行afterを撮影する。320 / 390 / 768 / 1440pxで3段階の数値、配色区別、12px以上、4.5:1以上、はみ出しなし、キーボード開閉を検査する。Chromiumの静的カタログ範囲のみ。
