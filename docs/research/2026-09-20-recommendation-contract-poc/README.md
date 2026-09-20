# 推薦方式・集計境界契約 PoC

`WT-FR-RECO-01` の未検証部分を、表示の見た目と記事選定の責務に分けて確認する静的PoC。関連（カテゴリ→タグ→手動）、人気（方式・期間を選択し、自前集計または外部読み戻し）、おすすめ（手動順）の3方式を一つの設定JSONで表し、表示型を変えても方式・件数・記事IDが変わらない契約を検証する。

WordPressの表示層はQuery Loopとpatternを使う。パターンの `/patterns` 自動登録は公式のテーマ標準方式に従い、Query Loopの variation/pattern は表示型を差し替えてもクエリの責務を分離できる。参照: [Registering Patterns](https://developer.wordpress.org/themes/patterns/registering-patterns/)、[Extending the Query Loop block](https://developer.wordpress.org/block-editor/how-to-guides/block-tutorial/extending-the-query-loop-block/)。

## 検証

```bash
node scripts/verify-recommendation-contract-poc.mjs
```

正例は3方式、人気の方式・期間、手動順、JSON読み戻し、bot/管理者除外の日次集計、5表示型の参照IDを確認する。負例は人気方式の固定、手動順の欠落、IP・生訪問データ保存、テーマ所有のAI順位判定を拒否する。画像は既存のカード／リスト実測を比較用に参照し、表示品質の新規判断を混ぜない。

## 境界

このPoCは静的契約であり、WordPress 7.2 の管理画面保存、REST/MCP 実接続、外部集計サービスの実通信、実デバイス支援技術検証は未接続。テーマは確定済みの選定結果を表示するだけで、順位判定やAI呼び出しを持たない。
