# 端末別の読み方を選ぶ完成比較

起点 a1a0211。監査 missing236 / partial26 / verified32 / stale0。WT-FR-SP-03（WT-AC-SP-03A/B、2 missing）を中心にVOCAB-01、LOOK-01Eの部分実証を増やす。Issue #114/#116。既存比較表2styleを使い、core Tabsへのstyle拡張、gallery、目次、CTAを一つの読書フローで選ぶ。商品正本・計測に依存するSELLより独立し、カタログで端末差を直接比較できる。

## 一次資料観測 2026-09-16

- https://developer.wordpress.org/block-editor/reference-guides/core-blocks/core-blocks-design/core-block-tabs/ : core/tabsとtab-list/tab-panels/tab-panelを使用。labにも登録実装あり。新規custom blockを作らない。
- https://www.w3.org/WAI/ARIA/apg/patterns/tabs/ : 矢印/Home/End、選択状態とpanel参照を検証候補とする。
- https://www.w3.org/WAI/ARIA/apg/patterns/accordion/ : 見出し内button、aria-expanded/controls、Enter/Spaceを検証候補とする。

PCはtab、SPはaccordion。JSなしでは全本文を開いた見出し群として読む。初期HTMLに本文を一度だけ保持する。galleryは自動再生せず横scrollとbutton代替。比較表は商品正本の販売比較語彙と区別する。設定は静的JSONの選択宣言で、MCP/管理画面一致を実証したとは扱わない。現行theme.jsonのトークンを使う。
