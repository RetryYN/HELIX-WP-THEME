# 見出し15型の文字拡大と装飾尺度

既存 H2 10型 / H3 5型を対象に、同じ短文と長文を使って100% / 200%文字を比較した。新しい型は追加していない。受入候補は LOOK-01B **partial**。他部品の生値・!important は今回の対象外であり、テーマ全体の合格ではない。

## 観測と変更

変更前は200%文字のH2 5型で、テキストRangeが行の上端を約2px越えた（PC/SP×JS有無で20件）。overflow:hiddenによる実際の欠字を観測したという意味ではなく、文字領域を包む余裕の不足として記録した。`before.json` は1184/1204成功。行間1.4を1.5にして文字領域を収め、`verification.json` は1204/1204成功になった。

文字サイズの既存fluidプリセット（l/xl/xxl）は維持。theme.jsonに21個のheading尺度を集約し、文字に追従するgap・左右余白・帯の上下余白・装飾サイズ・色・線幅をCSSから参照する。H2の節間余白はfluidトークン、H2/H3の残りのmarginは既存spacingプリセットにした。下線の短い色区間は文字サイズに追従し、要素幅を上限とする。見出しは長い連続文字も折り返せるCSSとしたが、任意のラテン連続文字の実機検証は今回の対象外。

[WordPressのfluid typography](https://developer.wordpress.org/themes/global-settings-and-styles/settings/typography/)と[W3CのResize text](https://www.w3.org/WAI/WCAG20/Understanding/resize-text.html)を確認した。尺度はtheme.jsonを正本とし、表示CSSは参照する。1px罫線は既存設計規約の例外として残す。CSSの全ての数値をゼロにしたという意味ではなく、font-weightやflexの数値、SVGのパス、構造上の0/100%は維持する。

## 証拠と再現

- `node scripts/check-heading-tokens.mjs`: 10検査。15型の存在、装飾の生寸法（1px例外）・生色・!importantの不在、参照解決、margin参照、unitless行間、既存fluid rem尺度。
- `node scripts/verify-heading-fluid.mjs`: 1204検査。1440/390/320幅×100/200%文字×JS有無。専用WordPress lab上で15登録style、保存本文一致、意味上のタグ、全文、文字領域、順序、サイズ階層、横溢れ、counter、reduced-motionを検査。所有者付きfixtureはfinallyで回収。
- `npx playwright test tests/e2e/heading-fluid.spec.ts`: 実WordPress上の独立fixtureで12条件。破棄済み独立HTMLカタログの1条件は過去の検証記録であり、再実行対象ではない。
- `npm test`: exit 0。要件・boundary・privacy・public-safety fixture・i18n・consumer health・31 unit検査を含む。実diffのpublic-safety guardとは別。
- `git diff --check`: 成功。

各before/afterに15型×PC/SP×文字100/200%=60画像、計120画像。画像digestは各JSONに保持。スクリーンショットは各型の同じ短文・長文・段落を含むsection。200%は実測した見出しと段落のcomputed font-sizeだけを2倍にするテスト用inline overrideで、CSS zoom・OS拡大・root font-size変更とは異なる。疑似要素のem寸法とunitless line-heightは文字に追従する。

目視確認: before/afterの番号ボックスSP200%、帯SP200%、2色下線PC100%、番号H3 SP200%。先頭の装飾、長文の折り返し、帯の内側の余白を確認した。before画像は改修前の実描画であり再構成画像ではない。

## カタログと残件

既存15候補に用途・装飾の強さ・階層・組合せ・制約・保存先・編集場所・200%検証方法を付記。カタログ本体は該当15エントリだけ差替え、全体再生成は行っていない。builderのheading proof参照は本folderへ更新した。

未確認: 全style variation、全書体、RTL、ブラウザーzoomとroot文字拡大、editor操作と保存再読込、REST/MCP、スクリーンリーダー、全ページaxe。counter/SECTIONの装飾は本文の意味情報を代替しない。line-height共通値は他の見出しにも継承されるため、テーマ全体の既存証跡の再検査・digest再結合は親タスクに残す。旧heading-comparison証跡は歴史的beforeとして保持し、現行ソース適合の主張には使わない。

実diff public-safety guardは暫定index上の20,462追加行を検査しexit 0。ただしローカルprivate mappingが無い状態で通過した。guard内の `cut | grep -q` とpipefailの組合せが大量入力で必須mapping判定をスキップする可能性を親タスクへ報告した。非公開名マッピングの検査済みとは主張しない。
