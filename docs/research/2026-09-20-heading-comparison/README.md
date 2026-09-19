# 同じ本文で選ぶ見出し15型

対象は現行 `helix-wt` の既存 H2 10型 / H3 5型。新しいblock・styleは追加しない。15候補の画像と選択説明を差し替え、同じ短文／長文、PC1440／SP390で装飾の強さ、区切り方、用途を比較できるようにした。

## PoC → 要求 → 設計

変更前の実機画像では、長い見出しの番号ボックス・星・小マーカーが複数行全体の中央に置かれ、記号が2行目に属しているように見えた。`before-*.jpg` と `before.json` が変更前の観測証拠。502検査のうち、3型×3幅×JS有無の18件が先頭行への装飾配置条件を満たさなかった。

この観測から、複数行でも装飾を見出しの冒頭に置き、本文と見出しを省略しないことを今回の改善条件とする。番号ボックスは上揃え、星と小マーカーは最初の文字のbaselineへ揃える。実装は既存3ルールの `align-items` だけを変更し、色・寸法・文字尺度・counter・HTML階層を変えない。

[WordPress公式Heading block](https://wordpress.org/documentation/article/heading-block/)と[見出し階層の公式学習資料](https://learn.wordpress.org/lesson/how-to-use-headings-for-accessibility-and-seo/)を確認（2026-09-20）。スタイルと意味上の階層を分離し、見た目のためにH2/H3を変えない方針を採った。公開OSSテーマの見出し配置に関する議論も確認し、既存core/headingの選択を維持する。第三者の画面・本文は転載せず、全て架空の共通本文で再現した。

## 実機証拠と再現

`node scripts/verify-heading-comparison.mjs`。専用labで所有者付き固定ページを一件作り、保存本文を読み戻す。登録済み15styleの存在、H2→H3→H4のサイズ単調非増加、各styleのサイズ継承、headingタグ、全文、文字領域の非クリップ、横溢れ、reduced-motion、番号counterをPC1440／SP390／狭幅320×JS有無で確認する。装飾の下線が要素外へ描画されること自体は文字切れと扱わず、実際のテキストRangeで判定する。

変更後は **502/502成功**。PC/SP各15、計30枚を撮影。変更前30枚も保持。numboxのPC/SP、iconとmarkerのSPを目視し、長文の先頭行に記号が揃い、本文が欠けないことを確認した。finallyで所有者を照合し、自分のfixtureだけを回収する。共有theme_modは変更しない。

## 要求への対応と限界

- `WT-AC-LOOK-01A`: 既定styleのH2/H3/H4実測尺度、15styleの登録、PC/SP比較は部分的に確認。正式G-T3、全style variation、editor挿入・保存・再読込、REST/MCP経路は未確認。全達成とは扱わない。
- `WT-AC-LOOK-01B`: 今回の3行変更は生値・`!important`を追加しない。ただし既存見出しCSSには固定rem/px/emと白色の直書きがあり、テーマ全体の装飾も対象となるため、Bの合格は主張しない。トークン正本への移行は別の残件。
- 短文／長文は同じ15styleの状態比較であり、型数を30へ増やさない。`catalog-candidates.json` は既存IDへの更新のみ。
- 200%文字拡大、全字体、任意のラテン連続文字、RTL、全ページaxe、スクリーンリーダーは未検証。CSS counterの意味情報への変換やSECTIONラベルの編集機構は追加していない。

`tests/e2e/heading-selection-catalog.spec.ts` は15候補の検索、用途/保存先/階層の説明、3候補比較、PC/SP切替、H3の5候補抽出を検査する。カタログ生成時には新しい証跡のsource digestと全30画像digestを照合し、既存候補が無い場合は失敗させる。
