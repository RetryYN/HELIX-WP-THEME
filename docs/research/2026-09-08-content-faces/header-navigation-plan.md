# 共通ヘッダーのナビゲーション参照

e867147の共通ヘッダーでは、保存したwp_navigationを参照せず、試作の固定リンクと存在しないページ内アンカーを表示していた。専用labの一時ナビを用いた初回検査は18件中16件失敗（設定復元・ソース不変のみ成功）。WT-FR-PARTS-02のwp_navigation参照・更新反映に対する空白である。

[Navigationブロックのref属性](https://developer.wordpress.org/block-editor/reference-guides/core-blocks/core-blocks-theme/core-block-navigation/)と[pre_render_block](https://developer.wordpress.org/reference/hooks/pre_render_block/)を使用する。sharedヘッダーの描画中だけ、core/navigationを公開済みのwp_navigation参照へ置き換え、固定のinnerBlocksを除去する。ネストしたブロックの属性キャッシュへ依存せず、refを束縛した標準ブロックを新しく描画する。本文に置かれた別ナビやnative表示は対象外。参照先はtheme_modの`wt_content_navigation_ref`で選ぶ。未設定・型違い・非公開ではナビを出さず、暗黙のページ一覧や固定リンクへ戻さない。

JS有効では標準メニューを操作し、JS無効ではリンクを直接表示する。標準ナビの保存内容を更新し、9ヘッダー型への反映、リンク先への実移動、未設定・非公開への変更を検査する。フッター・サイドバーの試作リンク、ヘッダーの別CTA、Site Editorでの参照先選択UIと全権限行列は別の残件である。

## 作業保存時点

このブランチはナビゲーション修正の作業保存用。`results/header-navigation/verify.json` の46検査は成功し、記録されたソースdigestと一致している。カタログ全体への統合完了は主張しない。

次の統合作業では、lab用の永続ナビ設定、共通パーツの再撮影・回帰検査、変更した共通ソースを参照するacceptance evidenceの再検証が必要。既存カタログの証跡は親コミットe867147時点のものであり、このブランチの変更へ検証済み判定を引き継がない。既存Draft PR #174への取り込みとmainへのmergeは未実施。
