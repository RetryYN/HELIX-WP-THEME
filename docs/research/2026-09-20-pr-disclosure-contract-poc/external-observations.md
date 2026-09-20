# PR表記の事前調査

2026-09-20 に公開一次資料を確認した。消費者庁のステルスマーケティングQ&Aは、広告・宣伝・プロモーション・PRを「事業者の表示」を明瞭にする用語の例として挙げ、アフィリエイトサイトの冒頭表示についても説明している。テーマ側では法令適合を断定せず、POが定めた既定文言と、記事に広告シグナルがある場合の機械的な表示境界だけを契約化する。

- 消費者庁: https://www.caa.go.jp/policies/policy/representation/fair_labeling/faq/stealth_marketing/
- WordPress Block Editor Handbook（ブロック登録）: https://developer.wordpress.org/block-editor/getting-started/fundamentals/registration-of-a-block/
- WordPress Block Metadata: https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/

既存の試作03では、`wt_content_has_pr_disclosure()` が先頭3段落・600字を走査し、話題語と開示述語の同一文共起、否定語除外を行う。今回の契約PoCはその境界を独立した正例・負例へ投影し、本文の自由編集で自動表示を消せる契約になっていないことを確認する。本文既存表記との重複抑止と欠落防止を同時にどこまで強制するかは、要求の `pending_resolution` として残す。
