# テーマB 独自ブロック カタログ（PoC エディタ実挿入）

ブロックエディタで `createBlock` により各ブロックを既定属性（example があれば example）で挿入し、編集ビューを撮影（画像はリポジトリ外・製品名を含むため）。22 ブロック（子ブロックを除く）。Block validation 警告 0。

| block | title | category | attrs | example | inner | 本テーマでの受け皿（案） |
|---|---|---|---|---|---|---|
| vendorB/accordion | アコーディオン | themeB-blocks | 8 | ✓ | 2 | core/details |
| vendorB/banner-link | バナーリンク | themeB-blocks | 25 | ✓ | 0 | core/cover + link |
| vendorB/box-menu | ボックスメニュー | themeB-blocks | 15 | – | 4 | home-gateway 系パターン |
| vendorB/button | themeBボタン | themeB-blocks | 21 | ✓ | 0 | core/button + block style |
| vendorB/cap-block | キャプションボックス | themeB-blocks | 9 | ✓ | 1 | core/group + style |
| vendorB/columns | リッチカラム | themeB-blocks | 13 | ✓ | 2 | core/columns |
| vendorB/dl | 説明リスト(DL) | themeB-blocks | 6 | ✓ | 4 | core/list / パターン |
| vendorB/faq | FAQ | themeB-blocks | 10 | ✓ | 2 | lp-faq → core/details 化 |
| vendorB/full-wide | フルワイド | themeB-blocks | 23 | ✓ | 2 | core/group alignfull |
| vendorB/step | ステップ | themeB-blocks | 11 | ✓ | 3 | 新規パターン（番号付き） |
| vendorB/tab | タブ | themeB-blocks | 14 | ✓ | 2 | 新規ブロック（タブ） |
| vendorB/ab-test | ABテスト | themeB-blocks | 6 | ✓ | 2 | HELIX 側（REQ-NF-025） |
| vendorB/ad-tag | 広告タグ | themeB-blocks | 5 | – | 0 | HELIX 側 |
| vendorB/balloon | ふきだし | themeB-blocks | 15 | ✓ | 0 | 新規ブロック（吹き出し） |
| vendorB/blog-parts | ブログパーツ | themeB-blocks | 6 | – | 0 | wp_block（同期パターン） |
| vendorB/link-list | リンクリスト | themeB-blocks | 14 | ✓ | 2 | core/list + style |
| vendorB/post-list | 投稿リスト | themeB-blocks | 38 | ✓ | 0 | core/query + パターン |
| vendorB/post-link | 関連記事 | themeB-blocks | 19 | ✓ | 0 | core/embed（内部） |
| vendorB/restricted-area | 制限エリア | themeB-blocks | 15 | – | 1 | スコープ外（INV-11） |
| vendorB/review | 商品レビュー | themeB-blocks | 16 | – | 0 | 新規ブロック（星評価） |
| vendorB/rss | RSS | themeB-blocks | 18 | – | 0 | core/rss |
| vendorB/style-block | 装飾ブロック | themeB-blocks | 5 | – | 0 | core/group + style |
