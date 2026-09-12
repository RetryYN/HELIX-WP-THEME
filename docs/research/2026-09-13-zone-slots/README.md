# ZONE-01 共通宣言 / device 差分の再現 PoC

現行 `helix-wt` の動的 slot レンダラーを専用ローカル WordPress で検証した。これは選択候補を比較する PoC であり、WT-AC-ZONE-01A/B の全面受入や G3 合意ではない。

`node scripts/verify-zone-slots.mjs` で再現。専用 lab・現行テーマを確認してから一時固定ページを作成し、終了時に当該ページだけを削除する。サイト設定と既存記事は変更しない。検証 JSON に片付け結果とソース digest を記録する。

要求の6種類を増やしていない。`fixture.json` の `families` は本文前、関連前後、固定ページ上下、ヘッダー内、SP下部固定、追尾サイドバーの6種を、前後/上下の展開を含む8個の挿入位置へ対応づける。

- `common` を既定とし、`pc` / `sp` が存在すればカード配列全体を置換する。差分の省略は継承、空配列は明示非表示。
- 空・空白のみのタイトルを持つカードは省略し、有効カードがなければラッパーと領域見出しも返さない。未登録の slot ID も出力しない。
- WordPress の `wp_is_mobile()` でサーバー側条件描画する。非選択カードは HTML に入らず、CSS Block Visibility は使用しない。
- 既存 theme.json の色・サイズ・余白プリセットを参照する。本文を落ち着いた面色で区切り、比較候補をPCでは2列、SPでは1枚のガイドに置換する。

証跡は [PC / JS](pc-js.png)、[SP / JS](sp-js.png)、[PC / noJS](pc-nojs.png)、[SP / noJS](sp-nojs.png)。[verification.json](verification.json) の35検査が PASS。各条件で挿入順、共通継承、差分枚数、非選択側の HTML 不在、横溢れ0、リンク44px以上、可視性、候補間の重なり0、通常テキストAA（4.5:1）を確認。別途両deviceの空DOMとfixture片付けも確認した。PHP構文検査を通過した。

残る範囲: 専用カタログページの挿入順検証であり、通常記事/固定ページの実ヘッダー内・追尾サイドバー・固定SPバーへの接続検証ではない。動的ブロック自体は capability manifest に収録済みだが、6 family の slot 宣言と Site Editor の専用編集UI、設定JSON/schemaへの接続、通常面での配置と重なり検査は未実施。`wp_is_mobile()` はviewport幅ではないので、UA/client hintsと幅の不一致、回転/resize、キャッシュ分離は本番化前に追加検証が必要。35件PASSを要求全体PASSへ読み替えない。

設計参照: WordPress公式の [dynamic rendering](https://developer.wordpress.org/block-editor/getting-started/fundamentals/static-dynamic-rendering/) と [block registration](https://developer.wordpress.org/reference/functions/register_block_type/)（2026-09-13確認）。PHP render callbackで空文字を返せることをPoCへ適用した。
