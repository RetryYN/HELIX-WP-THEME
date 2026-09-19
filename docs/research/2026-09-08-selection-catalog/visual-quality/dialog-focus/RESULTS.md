# 実装・検証結果

`catalog.mjs` の各ダイアログにヘッダーのResizeObserver、`catalog.css` に計測値を使うscroll-padding-topを追加した。
候補を変えても、画面幅を変えても、sticky header の実高に追従する。

## 実測

| 幅 | 修正前の最小距離 | 修正後の最小距離 |
| --- | ---: | ---: |
| 320px | -133.45px | 7.83px |
| 390px | -132.97px | 8.31px |
| 768px | -130.45px | 7.83px |
| 1440px | -130.47px | 7.88px |

距離はフォーカス対象上端とsticky header下端の差。負値は被覆を表す。`before.json` / `after.json` が原記録。
`before-390.png` / `after-390.png` は同じ第3逆順Tab操作を撮影したもの。

## 実行した検査

- `CATALOG_BASE_URL=<local server> node .../dialog-focus/capture.mjs before` / `after`: 4幅の計測・画像取得。
- `npx playwright test tests/e2e/selection-catalog.spec.ts --workers=1`: 33 passed。
- `node .../filter-context/verify.mjs`: selection + filterの38件がpass。新規4件は詳細・比較の逆順Tabと開いたままの幅変更を検査する。
- `decision-panel/capture.mjs` / `detail-sequence/capture.mjs` / `tablet-components/capture.mjs`: 元のbaselineを保持して再撮影・再計測した。過去のbefore画像を保持し、現ソースへのdigestを実測後に更新した。
- `npm test`: pass。初回は既存visual artifactのdigest不一致で停止したため、上記capture/verifyを再実行してから通過した。
- `git diff --check`: pass。

## 範囲と残件

これはローカルChromiumでのカタログ操作検証。660候補すべての目視確認、他ブラウザ・支援技術の適合確認、テーマの要求達成、独立レビューを表さない。
390pxの被覆修正と既存decision-panelの1440px画像を目視確認した。
公開情報検査には非公開対応表が必要。現環境には配置されておらず、commit / push / PR は実施していない。

## #231 再監査・束縛修正

main 2680b9e から、明示baseline `5961bde` の HTML/CSS/JS を capture が自動配信して再測定した。
上表の before/after 全8行は一致し、390px画像も既存ファイルとバイト一致した。目視でも after のフォーカス枠全体がヘッダー下へ露出している。
テーマのビジュアル実装は現行mainのまま。今回の変更は撮影条件と後続変更の検知を固定する。

再実行は `CATALOG_BASELINE_REF=5961bde CATALOG_BASE_URL=<local server> node docs/research/2026-09-08-selection-catalog/visual-quality/dialog-focus/capture.mjs before`、続いて同じ環境で `after`。
`CATALOG_CAPTURE_OUTPUT` を指定すると検証結果をリポ外へ保存できる。

- capture: 320 / 390 / 768 / 1440px、after 全4行が6px以上。違反時は非ゼロ終了。
- selection-catalog E2E: 33 passed。
- `node --test tests/dialog-focus-capture.test.mjs`: 3 passed（baseline未指定・同一ソース・別ソース配信を拒否）。
- CSSへ一時的な差分を入れる否定検査: after.json が stale として検出され exit 1。復元後の束縛検査はpass。
- `npm test`: pass。

before.json は歴史的baselineへのdigest、after.json は現行ソースへのdigestを記録する。beforeだけが既存の歴史的snapshot例外を使う。
全候補・他ブラウザ・支援技術の追加検証、独立review、CIの結果はこのローカル検証には含まれない。

今回の公開情報検査は、実在するリポ外の非公開対応表を環境変数で注入し、統合層の `check-public-safety.sh --staged` を対象worktreeで実行してpassした。前節の対応表未配置という記録は初回実装時の状態。
