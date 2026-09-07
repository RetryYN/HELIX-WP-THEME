# 常設案内パターンの編集画面

4863168の専用WordPress編集画面で、PHPに登録したhelix-wt/site-pageがクライアント側では未登録となり、保存内容がcore/missingとして読み込まれることを実測した。公開側の画像とパターン台帳だけでは挿入・編集・保存の証拠にならない。

[動的ブロックの公式手順](https://developer.wordpress.org/block-editor/how-to-guides/block-tutorial/creating-dynamic-blocks/)と[メタデータ登録](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-metadata/)に従い、block.jsonを宣言の正本にして編集用JSを登録する。11種類の選択肢は既存のJSON宣言から渡す。属性のみを保存し、本文・事業者情報を別のコピーへ展開しない。編集画面のプレビューも同じPHP表示を用いる。

ページ全体用のテンプレートは明示ボタンで適用する。選択・保存・再読込・公開表示の一致、登録済みパターンの挿入を専用の一時固定ページで確認する。共通事業者設定を編集する管理UI、既存の有料記事等の専用編集UI、第三者サービスへの実送信契約は別の未完了事項である。

## 同期用チェックポイント（2026-09-08）

作業途中の変更を `codex/site-page-editor-checkpoint-20260908` に保存する。機能完了・受入合格を示すものではない。`results/site-pages/editor.json` は `completed: false`、検証行は0件。編集UI検証はパターン分類「常設案内・規約」のbuttonロール検索で停止しており、実DOMに合わせたロケータ確認から再開する。

同期時にはPHP構文、JavaScript構文、`npm test` の要求整合性・consumer healthを確認した。編集画面での挿入・保存・再読込や公開側の回帰試験の代替にはならない。既存の公開画面証跡は変更前の実行結果なので、編集用登録を含む現headの検証証跡として扱わず、再実行後に対応を更新する。
