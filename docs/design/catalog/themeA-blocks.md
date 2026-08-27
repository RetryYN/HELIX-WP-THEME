# テーマA 独自ブロック カタログ（PoC エディタ実挿入）

ブロックエディタで `createBlock` により各ブロックを既定属性（example があれば example）で挿入し、編集ビューを撮影（画像はリポジトリ外・製品名を含むため）。19 ブロック（子ブロックを除く）。Block validation 警告 0。

| block | title | category | attrs | example | inner | 本テーマでの受け皿（案） |
|---|---|---|---|---|---|---|
| themeA-blocks/postlist | 記事リスト | themeA-blocks | 96 | – | 0 | core/query + パターン |
| themeA-blocks/designtitle | デザイン見出し | themeA-blocks | 34 | – | 0 | core/heading + block style |
| themeA-blocks/button | ボタン | themeA-blocks | 34 | – | 0 | core/button + block style |
| themeA-blocks/simplebox | ボックス | themeA-blocks | 18 | – | 0 | core/group + block style |
| themeA-blocks/iconbox | アイコンボックス | themeA-blocks | 26 | – | 0 | core/group + block style（アイコン） |
| themeA-blocks/richmenu | リッチメニュー | themeA-blocks | 29 | – | 4 | home-gateway 系パターン |
| themeA-blocks/designborder | 区切り線 | themeA-blocks | 17 | – | 0 | core/separator + style |
| themeA-blocks/fukidashi | 吹き出し | themeA-blocks | 27 | – | 0 | 新規ブロック（吹き出し） |
| themeA-blocks/fullwidth | フルワイド | themeA-blocks | 26 | – | 0 | core/group alignfull |
| themeA-blocks/blogcard | ブログカード | themeA-blocks | 27 | – | 0 | core/embed（内部）+ style |
| themeA-blocks/accordion | アコーディオン | themeA-blocks | 19 | – | 1 | core/details |
| themeA-blocks/timeline | タイムライン | themeA-blocks | 20 | – | 3 | 新規パターン（core/list） |
| themeA-blocks/syntax-hl | コード | themeA-blocks | 15 | – | 0 | core/code |
| themeA-blocks/background | 背景 | themeA-blocks | 30 | – | 0 | core/cover |
| themeA-blocks/profile | プロフィール | themeA-blocks | 16 | – | 0 | author-profile パターン |
| themeA-blocks/compare | 比較表 | themeA-blocks | 9 | – | 2 | core/table + style |
| themeA-blocks/category | カテゴリー区別 | themeA-blocks | 18 | – | 0 | core/categories |
| themeA-blocks/paidpost | 有料コンテンツ | themeA-blocks | 13 | – | 0 | スコープ外（INV-11） |
| themeA-blocks/tab | タブブロック | themeA-blocks | 11 | – | 2 | 新規ブロック（タブ） |
