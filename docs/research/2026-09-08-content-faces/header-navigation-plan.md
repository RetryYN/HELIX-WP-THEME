# 共通ヘッダーのナビゲーション参照

e867147の共通ヘッダーでは、保存したwp_navigationを参照せず、試作の固定リンクと存在しないページ内アンカーを表示していた。専用labの一時ナビを用いた初回検査は18件中16件失敗（設定復元・ソース不変のみ成功）。WT-FR-PARTS-02のwp_navigation参照・更新反映に対する空白である。

[Navigationブロックのref属性](https://developer.wordpress.org/block-editor/reference-guides/core-blocks/core-blocks-theme/core-block-navigation/)と[pre_render_block](https://developer.wordpress.org/reference/hooks/pre_render_block/)を使用する。sharedヘッダーの描画中だけ、core/navigationを公開済みのwp_navigation参照へ置き換え、固定のinnerBlocksを除去する。ネストしたブロックの属性キャッシュへ依存せず、refを束縛した標準ブロックを新しく描画する。本文に置かれた別ナビやnative表示は対象外。参照先はtheme_modの`wt_content_navigation_ref`で選ぶ。未設定・型違い・非公開ではナビを出さず、暗黙のページ一覧や固定リンクへ戻さない。

JS有効では標準メニューを操作し、JS無効ではリンクを直接表示する。標準ナビの保存内容を更新し、9ヘッダー型への反映、リンク先への実移動、未設定・非公開への変更を検査する。フッター・サイドバーの試作リンク、ヘッダーの別CTA、Site Editorでの参照先選択UIと全権限行列は別の残件である。

## カタログ接続

`seed-navigation.php`は専用labに標準wp_navigationを初期作成し、記事・人物・学習・会社案内の実在する4導線を設定する。既存の参照値がある場合は値が無効でも上書きせず、同名ナビの本文や公開状態も変更しない。通常seedから呼び出すが、本番には適用しない。

共通パーツの428検査と36画像をこの設定で更新する。ナビ検証はPC/SPとJS有無、9ヘッダー型への保存内容の反映、contentSize / wideSizeの維持、空・未設定・非公開・不正参照を対象とする。ネイティブ表示の固定ナビ、独立CTA、フッター・サイドバーのリンク、参照先を選択する管理UIは残るため、WT-AC-PARTS-02A/Bは部分検証として扱う。

初期ナビの日本語JSONをwp_insert_postへ渡す際、エスケープが失われて文字コード表記になる不具合を画像の目視で検出した。wp_slashを付けて保存し、共通パーツ検証へ6面×2状態×PC/SPの24件のナビ文言照合を追加した。レイアウトだけの成功を、文言の正しさの証拠にしない。
