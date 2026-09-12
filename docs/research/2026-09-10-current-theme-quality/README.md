# 現行テーマのデザイン品質 — 小さな是正と残件

対象: `docs/research/2026-09-05-design-prototype-03/theme/helix-wt/`。旧theme/pluginは変更していない。

## 修正

1. 未定義 `color--soft` を参照していたフォーム3箇所を既存 `surface` へ。背景が透明になっていた質問・外部captcha枠・補助連絡面に、既存の補助面色が適用される。
2. rules/mincho variationへ親のok/warn-soft/ok-softを同値追加。paletteは親と同じ12slug。WordPressが解決した色は変更前後同一。
3. content-faces.cssの!important38件を撤去。単純削除のtrialでは12ボタンの白文字が継承色へ変化したため、`.wtcf .wtcf-button`の通常specificityで所有scopeを明示した。見出し・組版の強制指定37件は不要だった。

font9段、spacing8段、全body軸、raw値は維持した。既存の型を減らしていない。

## 実測

- 現行CSSとlab配備CSSのSHA一致を開始時に確認。labはrepoの現行テーマをread-only bind mountしており、編集は自動反映される。docker cpはread-onlyで拒否され、sourceとlabの最終SHA一致を再確認した。
- 独立コンテンツ6経路×PC1440/SP390×editorial/standardの24条件。変更前後の全子孫のfont、色、背景、padding、margin、border、shadow、display、gridを比較。差は意図した獲得LPフォーム補助面4要素の背景のみ。ボタン白文字・組版は維持。
- 一時fixture4件で記事・LP・イベント・フォームを描画。default/rules/mincho×PC/SPの30条件とSP/JS無効5条件。HTTP200、内容あり、横overflow0。reduced-motionでは測定対象のanimation:none / transition:0s。フォームの2種類×PC/SP×3variationで24要素の補助面背景だけが変わった。
- 同じ内容・幅のbefore/after画像36枚。WP_Theme_JSONが生成するvariation CSSをブラウザへ適用し比較した。Site Editorからvariationを保存する操作の証跡ではない。
- 集約 `node .../verify.mjs`: **138 pass / 0 fail**。失敗・overflow・空内容を同じ判定関数へ入れた負例を含む。source digestとfixture清掃を検査。
- fixture2066/2067/2068/2069は作成前slug不在を確認し、検査後に全件削除・不存在を確認。fixtures.json参照。既存ページ・theme_mod・front page設定は書き換えていない。
- `npm test`は旧themeのAI境界2検査で失敗（manifest-denies-local-ai-logic / boundary-guard-rejects-theme-ai）。共有worktreeの別差分に属するため、この作業から修正していない。
- 今回PHP変更なし。CSS/JSON4ファイルの差分・JSON構文・git diff --checkを確認。

## 未完了の監査

`audit.py` / `audit.json` が現行テーマ全体を用途別に記録する。字面のraw出現2987は違反数ではない。

| 区分 | 出現 |
| --- | ---: |
| theme.json等のtoken定義 | 75 |
| CSS内のlocal token/軸上書き | 39 |
| breakpoint条件 | 78 |
| 1px罫線 | 183 |
| 単位付き0 | 7 |
| JSの寸法/監視値 | 3 |
| structured style値 | 27 |
| 部品/コンテンツ寸法（自動許容しない） | 2575 |

!importantは154→116。残りはtheme.cssの視覚/配置55、表示選択48、reduced-motion保護13。表示選択48を契約上許容と決めてはいない。全軸の競合・非表示要素の復活・SP配置を実測してから順に整理する。**LOOK-01B全体は未完了**。

次の優先箇所はヘッダー幅（partsのliteral wideSizeとCSS importantの競合）、density軸の二重定義、cover/scrimの明度別背景、関連/カテゴリのgridと表示選択。各軸の旧PO是正を再発させないため、単純な一括置換はしない。

## 再現

`fixtures.mjs create` → `common-probe.mjs <before|after>` → `fixtures.mjs cleanup`。途中失敗でも必ずcleanupする。
`probe.mjs <before|trial|after>`は既存公開コンテンツを読み、trialだけCSSレスポンスをブラウザ内で置換する。beforeは変更前ソースで撮る。設定保存・購入・フォーム送信は行わない。
