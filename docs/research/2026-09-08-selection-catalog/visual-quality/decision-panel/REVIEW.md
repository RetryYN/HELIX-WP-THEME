# 選択メモを判断する場所として整える

担当: Astra / effort low。2026-09-20。対象は選択カタログの詳細・比較内の選択メモ。

## PoC と要求

実装前の Chromium 実測を `poc.json` と before画像へ保存した。390pxでは未選択・採用候補・保留・除外が横並びの同形ボタンで、判断の意味やメモの所属を示す見出しがない。選択色も状態によらず緑である。
既存のギャラリーは状態別に色と文字を表示済みだが、実際に判断する詳細・比較では一貫していない。

このバッチは既存の選択メモ操作の表現改善であり、要求件数や受入達成状態を増やさない。

- 四状態の意味を操作する場所で読めること。選択は文字・チェック・aria-pressedで示し、色だけに依存しない。
- 選択と理由入力を一つの明確な領域にまとめる。採用候補が正式承認ではないことをその場で説明する。
- 詳細・比較の両方で同じ操作を使い、既存の保存、再読込、比較表反映を維持する。
- 320 / 390 / 1440pxで操作がはみ出さず、44px以上の操作高を保つ。

## 設計

候補説明と選択事実の後に、薄い紙色の判断パネルを置く。短い見出し、現在の状態、2列の選択肢、理由欄の順にする。
未選択は中立、採用候補は深緑、保留は黄土、除外は赤茶。非選択肢は白に揃えて選択済みの差を明確にする。
既存のクリックイベントとストレージ形式は維持する。保存成功の新しい表示は追加しない。

調査: W3Cの [Button pattern](https://www.w3.org/WAI/ARIA/apg/patterns/button/) の押下状態、
[Focus visible](https://www.w3.org/WAI/WCAG22/Understanding/focus-visible) のフォーカス表示、
WordPress公式の [List view](https://wordpress.org/documentation/article/list-view/) と公式既定テーマのパターン構成を確認。
カタログ固有の判断パネルへの適用は本バッチの設計判断であり、公式に推奨された配色ではない。

## 検証結果

基準HEAD: `12aa3bd0cfbb68f16c6afd866eed248b28e037cf`。
専用静的serverの実プロセスcwdを確認した上でChromium実行。

- [PC変更前](before-1440.png) / [PC変更後](after-1440.png)
- [SP変更前](before-390.png) / [SP変更後](after-390.png)
- `observations.json` に同じ候補・採用候補・同じ理由入力による4枚の観測と現source digestを保存。
- 320 / 390 / 1440pxで四状態の操作、選択チェック1つ、44px以上の高さ、パネル横溢れなしを確認。
- 選択と理由の再読込保持、比較側での選択変更と比較表の同期を確認。
- `npm run test:e2e:catalog`: 47 / 47成功（既存46 + 新規1）。
- `npm test`: 成功。filter-contextの5テストも再実行成功後に既存証跡のsource bindingを更新。
- `git diff --check`、一時index経由の公開情報検査: 成功。

再撮影例: `CATALOG_BASELINE_REF=12aa3bd0cfbb68f16c6afd866eed248b28e037cf CATALOG_BASE_URL=http://127.0.0.1:8099 node docs/research/2026-09-08-selection-catalog/visual-quality/decision-panel/capture.mjs`。
出力先は `CATALOG_CAPTURE_OUTPUT` で変更できる。baseline必須、同一画像は失敗、全撮影成功後に証跡を更新する。

判断パネルは説明を加えた分だけ縦に長くなる。トップの候補一覧の密度は変えない。
スクリーンリーダー・高コントラスト・他ブラウザでの検収、Claudeの現HEADレビューは未実施。
テーマ自体の全要求再現や、全候補の個別目視を完了した証拠にはしない。
