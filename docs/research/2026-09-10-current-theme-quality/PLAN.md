# 現行 helix-wt の品質監査・最小是正

対象は試作03 theme/helix-wt のみ。旧 agent-neo-theme の変更・尺度を移植しない。要求入口は authority.md / current-alignment.md、作業目的は planning/2026-09-08-catalog-completion.md。font9段、spacing8段、bodyのwidth/density/depth/motion/detext軸を維持する。

## 監査所見

- コメントを除いたpx/rem/emの字面出現2987。token定義やbreakpointを含むため違反数と同一視しない。
- !important154件: theme.css116（表示選択48・motion停止13・その他視覚/配置55）、content-faces.css38（表示1・視覚/組版37）。表示指定も自動的な許容ではなく、軸の選択規則との衝突を実測する必要がある。
- フォームのcaptcha question/placeholder/sidebarの3箇所が未定義color--softを参照。既存surfaceの意味（補助面）へ修正する。
- variation rules/minchoは親paletteのok/warn-soft/ok-softを欠く。既存親値を追加してslug集合を揃え、意図する色を変えない。
- content-faces.cssの全!importantをブラウザのCSSレスポンス置換で除いたtrialでは、24条件中、ボタン文字色だけ12要素で差が出た。`.wtcf a{color:inherit}`とのspecificity競合を`.wtcf .wtcf-button`の通常cascadeで解消してから38件撤去する。

## 実装対象

- assets/css/theme.css: 未定義soft3箇所だけsurfaceへ変更。
- styles/rules.json / mincho.json: 親の既存palette3slugを追加。
- assets/css/content-faces.css: 不要!importantを撤去。ボタンの所有scopeをselectorへ明示。

## 検証

- 専用content lab（現行CSSとソースSHA一致を確認）で有料/人物/BLP/獲得LP/一覧/学習入口×PC/SP×2designの24条件をbefore/trial/after比較。全子孫の主要computed propertyを比較。
- 主要面は専用一時post/page4件を作成（既存slug不在を確認）。記事/LP/イベント/フォームでPC/SP、WordPress WP_Theme_JSONが生成したdefault/rules/mincho stylesheet、JS無効、reduced-motionを検査。fixture IDと削除結果を保存。
- 変化前後の同じ内容・幅を撮影。横overflow、HTTP200、実内容、フォーム面背景、既存tokenの解決を確認。
- 同一WP設定でvariation stylesheetをページへ適用する比較であり、Site Editorからvariationを保存する操作の検証ではない。
- 残存raw/importantの用途台帳を保存。LOOK-01B全体を完了扱いしない。
