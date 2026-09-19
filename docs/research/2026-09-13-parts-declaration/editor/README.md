# PARTS Site Editor 保存経路

2026-09-13。`WT-AC-PARTS-01A/B` の残件を縮小する現行helix-wt PoC。

## 実装

`core/template-part` の `wtPartsDeclaration` をサーバーとクライアントに登録し、Inspector「端末別パーツ」に有効化トグルと共通・PC・SP参照セレクトを追加。選択肢はテーマの実在partsファイルから供給する。PC/SPには「共通を継承」を用意する。新規独自block登録は0。

WordPress REST保存境界（wp_template / wp_template_part / page / post）で子ブロックも含め宣言を検査する。不正参照・device未宣言・不正型はHTTP400 `helix_wt_invalid_parts` で保存を拒否し、既存DB内容を維持する。公開時のfail-closedも継続する。WP-CLIや直接SQLの保存拒否までをこのREST境界の検査で証明するものではない。

## 実操作の証拠

`node scripts/verify-parts-editor.mjs`：**25/25**。

専用labに一時wp_templateとpageを作り、Site Editorのcanvasからtemplate-partを選択。Inspectorの実コントロールで有効化、common=header-center、pc=header-band、sp=継承を選び、実Saveボタンから保存する。DB内容、編集画面再読込後の属性とvalid状態、PC/SP × JSあり/なしの公開参照と非選択DOM不在を確認する。

さらに認証済みエディタのwp.apiFetchで不正なREST保存を3種試し、すべて400とDB不変を確認。これらの負例はUI選択肢を迂回した保存境界の検証である。正常操作のJS runtime errorは0。テンプレートとpageはfinallyで削除し、既存のユーザー資産を上書きしない。credentialは既存labの一時ファイルから読み、証拠へ記録しない。

`inspector.png` は実編集UI。`pc-js.png` / `pc-nojs.png` / `sp-js.png` / `sp-nojs.png` は公開画面。`verification.json` に検査とsource SHA256を保持する。

既存headerのcore/groupにpadding属性があり、保存HTMLのstyleが欠けていたため、Site Editorでinvalid警告を検出した。header/header-center/header-bandの開始タグに対応するpadding styleを補い、今回使うheaderの編集プレビュー警告が0であることを確認した。

## 残件

編集canvasは元のパーツを表示し、端末別参照を反映するのは公開面。UIにもこの違いを記載する。テンプレート全体のdevice切替、cache segmentation、viewportだけのリサイズ、DB-onlyパーツ、全12パーツの編集妥当性は未検証。全受入の完了主張には広げない。

## 公式資料（参照 2026-09-13）

- [Template Parts](https://developer.wordpress.org/themes/templates/template-parts/)
- [Introduction to Templates](https://developer.wordpress.org/themes/templates/introduction-to-templates/)

旧AGENT NEO配下と他リポジトリの変更は0。
