# LOOK-03 サイトパターン契約 PoC

`WT-FR-LOOK-03` の既存調査を、サイトパターン別の分布、variation の導出、未観察索引へ束ねる静的契約である。実サイトの再アクセスや配布テーマの追加実装は行わず、既存の269件の調査台帳、集計、旧 AGENT NEO テーマの9 style variation、およびその G-T1b / G-T3 の実行結果を digest で固定する。**現行 PoC `helix-wt` の variation は `rules` と `mincho` の2件であり、この検査は現行9 variationの合格証拠ではない。**

観察済みは `corporate`、`service`、`brand`、`portal`、`compare`、`motion` の6系統で、分布から9 variationを対応付ける。`commerce`、`membership`、`jobs`、`maintenance` は未観察索引に残し、対応済み件数へ算入しない。

受入候補は partial である。旧9 variationから現行 PoC への写像、現行 style variation の編集画面保存、REST/MCP経路、未観察系統の実サイト再調査、動きの資産層は未接続である。
