# Abilities 3面・権限・receipt 契約 PoC

`WT-FR-AGENT-01`、`WT-NFR-SEC-01`、`WT-NFR-PERM-01` の既存3面PoCを、カタログで比較できる部分契約へ束ねる。パック定義の3 abilityが WP-CLI / REST / MCP に現れ、dry-run → receipt → apply の順序と匿名拒否・receipt拒否が証跡で再現できることを検査する。

既存証跡はローカル WordPress 7.1 で取得されている。REST匿名一覧・実行、MCP匿名tools/list、receiptなし・偽receipt、誤HTTPメソッドを負例として確認する。実サイトの資格情報は記録しない。

契約の `sourceDigests` は、契約・pack・比較結果だけでなく、上記の8つのセキュリティ観察JSONそのものにも束縛する。観察JSONだけを変更して検証成果物を更新しない場合は、artifact source binding検査で検出する。

部分契約であり、現行HEADの再実行receipt、全REST/MCP匿名メソッド、実装全体のSSRF・Warning静的監査、WordPress 7.2実機は残件として開く。`completion=false` を固定し、既存PoCの成功を製品完成とは扱わない。
