# トークン構造規約（層 1）

外部デザインツールのバリアブル構造を**参考**にした規約。外部ツールは正本ではなく、取り込みツールも持たない。
正本はリポジトリの `theme.json` / `styles/*.json` / patterns であり、値の変更は commit で有効になる。

## 1. 4 グループ・スラッグ固定（増減・改名は設計判断 + CHANGELOG）

| グループ | スラッグ | 備考 |
|---|---|---|
| color | primary, secondary, accent, accent-aa, background, foreground, footer-bg, muted | 8 色固定。style variation は値のみ差し替える |
| font-size | small, medium, large, x-large, xx-large, xxx-large | 6 段固定。`defaultFontSizes=false` |
| space | 10, 20, 30, 40, 50, 60 | 6 段固定。`defaultSpacingSizes=false`、`spacingScale` なし |
| elevation | 0, 1, 2, 3, 4 | shadow presets。0 = なし、4 = オーバーレイ |

## 2. 層の責務

- 層 1（尺度）: 親 `theme.json` だけが所有する。子テーマは再定義しない（G-S1）。style variation は尺度スラッグの集合を変えず、値の差し替えだけを行う（G-T1b）。
- 層 2（骨格）: templates / parts。参照先の存在を保証する（G-S2）。
- 層 3（意匠）: patterns / styles。生の px/rem/em を書かず、プリセット参照で組む（G-T2 / G-T1b）。
  例外: 0・1px（罫線）、および styles 内の `radius` / `letterSpacing` / `width` キー（意匠値として G-T1b の検査対象外）。

## 3. 機械検査

`bash bin/check-design-consistency.sh`（CI `test.yml` で実行）。G-T2 は
`themes/agent-neo-theme/config/design-consistency-baseline.json` の上限を超えた場合のみ FAIL（既存違反の増加を止める）。
G-T3 は見出し h1〜h4 の fontSize プリセットが単調非増加であることを検査し、未定義の見出しは WARN（コア既定依存）とする。
