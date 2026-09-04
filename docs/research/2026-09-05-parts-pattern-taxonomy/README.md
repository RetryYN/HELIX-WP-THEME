# パーツ別パターン台帳（世の中の型 × テーマ A/B × 試作 02）

- 作成: 2026-09-05（PO 指示「作るより先に、世の中のサイトからどんなパターンがあるかパーツ一覧で出す」）
- 入力: (1) 実サイト調査の全長ショット 730 枚（サイト 271 件: トップ PC 271 / SP 270、記事 PC 95 / SP 94。調査 ID 単位で数え、同一サイトのトップと記事は 1 件）を上部 / 中央 / 末尾に切り出し、`PARTS-VOCAB.md` の語彙で 12 並列コーディング（`coded/`、集計 `aggregate.md` / `aggregate.json`）。(2) テーマ A / B の公式パーツ一覧・機能ページ・デモ 24 サイトをブラウザで全長取得し、実際に見えた variant を `vendor-variants.md` に列挙（固有名は伏せ字）。(3) PO 指定の参照サイト（固有名は非公開対応表のみ）の 3 面を `ref-site-notes.md` に言語化。
- 位置づけ: L2 探索証跡。要求の確定ではない。「採用候補」列は Claude 案で、PO 判断待ち。
- 数値の読み方: 出現率の分母は「na（切り出し範囲に無い / 未描画）を除いたタグ出現数」。1 shot で複数の型が出た場合は各々を 1 と数えるため、分母は shot 数より大きい（例: header.layout top/PC は 547 タグ / 244 shot）。`aggregate.md` の n は na を含む総数。§1 最終列（Issue 番号付き）の割合は Issue コメント時点の na 込み分母で、観察列（na 除外）とは分母が異なる。

## 1. 台帳

| パーツ | 実サイトで観察された型（上位、top または article / PC・SP） | テーマA 型数 | テーマB 型数 | 試作 02 | 差分と採用候補（Issue） |
|---|---|---|---|---|---|
| header レイアウト | top/pc: logo-left-nav-right 22%, with-search 18%, logo-left-cta-right(no text nav) 14%, with-announce-bar 10%, two-rows 8%, transparent-over-hero 8%（n=547）<br>top/sp: logo-left-cta-right(no text nav) 24%, with-search 17%, logo-center-only 17%, transparent-over-hero 11%, with-announce-bar 11%, logo-left-nav-right 7%（n=390） | 6 | 6 | 1（ロゴ｜CTA｜≡） | #100: 9 型提示済 → 実サイト上位 6 型 + テーマ A/B の帯色・中央ロゴ型を採用候補 |
| header SP 配置 | top/sp: hamburger-right 31%, hamburger+search 22%, hamburger-left 16%, hamburger+cta 13%, no-hamburger(text nav) 10%, other:menu-text 0%（n=305） | （検索左・ロゴ中央・≡ 右） | （≡ 左 or 右） | ≡ 右 | #100: ≡ 右 26% / ≡+検索 19% / ≡ 左 13% / ≡+CTA 11% / テキストナビ 9% → S8 で 5 型 |
| hero / メインビジュアル | top/pc: fullbleed-photo-overlay 17%, text-only 13%, split-text-image 12%, slider 11%, illustration 9%, article-grid(media) 9%（n=433）<br>top/sp: text-only 20%, fullbleed-photo-overlay 19%, illustration 10%, slider 9%, product-shot 9%, article-grid(media) 9%（n=381） | 7 | 5 | 1（split） | #101: 全面写真 / 文字のみ / split / スライダー / イラスト / 記事グリッド / 商品 / 動画 の 8 型 |
| hero CTA | top/pc: none 58%, single 17%, double 17%, form-inline 5%, other:banner-links 0%, other:3連CTA 0%（n=256）<br>top/sp: none 66%, single 15%, double 11%, form-inline 5%, other:triple 0%（n=226） | – | – | 2 つ | 単 / 二 / フォーム内蔵 / なし |
| トップのセクション種 | top/pc: banner-row 12%, article-grid 10%, news-list 9%, category-cards 9%, tabs 5%, logos-row 5%（n=660）<br>top/sp: article-grid 11%, banner-row 10%, category-cards 10%, news-list 8%, blog-cards 6%, features-icon-list 5%（n=580） | 記事リスト 9・リッチメニュー 8 | 記事リスト 9・ピックアップバナー | 7 本 | #96: バナー列 / 記事グリッド / お知らせ / カテゴリカード / タブ / ロゴ列 / 特長 3 列 / ランキング / 受賞バッジ / CTA 帯 が上位 |
| カード様式 | top/pc: flat-no-border 29%, image-top 24%, shadow 10%, image-left 9%, border 7%, photo-full-overlay 7%（n=421）<br>top/sp: image-top 29%, flat-no-border 27%, shadow 10%, border 9%, image-left 7%, icon-top 5%（n=416） | 白角丸＋影 が既定 | 白角丸 / 影 / 枠 | 罫線・影 2 型 | flat 無枠 24% / 画像上 20% / 影 8% / 画像左 8% / 枠 6% / 写真全面重ね 6% / アイコン上 4% / 番号上 2% |
| footer | top/pc: logo-only-legal 20%, columns-3 13%, mega(sitemap) 13%, none 13%, with-sns-icons 13%, single-row 6%（n=15）<br>top/sp: mega(sitemap) 16%, with-sns-icons 16%, single-row 11%, logo-only-legal 11%, other:accordion 11%, none 11%（n=18） | 3 | 3 | 1 | #100: 撮影範囲が上部中心で観察不足（下記 §5）。テーマ A/B は CTA 枠 + 1 行、Profile 枠 + 1 行、法定 1 行の 3 型 |
| 固定・追従パーツ | top/pc: none 20%, announce-bar 17%, sticky-header 15%, cookie-consent 15%, float-cta 11%, float-chat 5%（n=268）<br>top/sp: none 41%, announce-bar 27%, cookie-consent 11%, float-cta 4%, other:age-gate 1%, sticky-header 1%（n=154） | SP CV バー | SP 下部メニュー・追従ヘッダー | なし | #104: お知らせバー / cookie 同意 / 追従ヘッダー / SP 下部バー / フロート CTA |
| 記事タイトル部 | article/pc: title-then-image 57%, no-image 16%, hero-overlay-title 8%, image-then-title 7%, side-thumb 5%, other:tinted-hero 1%（n=97）<br>article/sp: title-then-image 58%, no-image 18%, image-then-title 9%, hero-overlay-title 9%, side-thumb 1%, other:tinted-hero 1%（n=91） | 画像→タイトル | 画像→タイトル or タイトル→画像 | タイトル下画像 | #126: タイトル→画像 55% / 画像なし 15% / 画像→タイトル 6〜9% / ヒーロー重ね 7〜9% / 横サムネ 1〜4% |
| 記事メタ | article/pc: date 21%, category-chip 16%, updated 13%, author 12%, share-top 8%, tags 5%（n=262）<br>article/sp: date 22%, category-chip 16%, updated 14%, author 12%, share-top 6%, tags 4%（n=232） | 日付・更新・カテゴリ・PR | 同 | 日付・更新 | #108: PR チップ / 広告表記帯を語彙に追加 |
| 目次 | article/pc: none 44%, float-side 25%, box-inline 18%, collapsible 9%, other:tab-nav 2%（n=76）<br>article/sp: none 53%, box-inline 28%, collapsible 13%, other:tab-nav 1%, float-side 1%, other:sticky-tabs 1%（n=66） | 2（枠 / サイドバー） | 2 | details 1 | #107: なし 33% / フロート側 18%（PC）/ 枠内 13〜18% / 開閉 6〜8% |
| h2 見出し | article/pc: plain-bold 44%, icon-prefix 11%, bar-left 9%, underline 7%, band-fill 5%, background-tint 5%（n=52）<br>article/sp: plain-bold 45%, underline 12%, icon-prefix 10%, bottom-border-2tone 8%, band-fill 5%, bar-left 3%（n=57） | 11 | 5 | 2 | #122: 無装飾太字 21〜24% / アイコン前置 5% / 下線 3〜6% / 左バー / 帯塗り / 2 色下線 / 背景淡 …実サイトは無装飾が最多、テーマは装飾型を多数持つ |
| h3 見出し | article/pc: underline 28%, bar-left 28%, plain-bold 28%, icon-prefix 14%（n=7）<br>article/sp: plain-bold 28%, bar-left 21%, number-prefix 14%, icon-prefix 14%, underline 14%, bottom-border-2tone 7%（n=14） | 下線 | – | 0 | #122 |
| 囲み | article/pc: tinted 24%, none 16%, plain-border 15%, band-title 12%, shadow-card 9%, label-title 7%（n=108）<br>article/sp: tinted 25%, plain-border 19%, none 16%, band-title 8%, shadow-card 7%, label-title 6%（n=106） | 20 + 引用 | 10 | 6 | 淡塗り 19〜21% / 細枠 12〜16% / 帯タイトル 7〜10% / 影カード 6〜7% / ラベル 5〜6% / チェック 3% / タブ 3% / 引用 2% |
| リスト | article/pc: dot-plain 52%, check 15%, number-circle 10%, icon-custom 7%, arrow 5%, other:numbered-bold 2%（n=38）<br>article/sp: dot-plain 50%, check 17%, number-circle 12%, arrow 7%, icon-custom 5%, other:numbered-bold 2%（n=40） | 3 | 4 | 1（チェック） | 丸番号 / チェック / 矢印 / アイコン |
| リンクカード | article/pc: none 70%, internal-thumb-left 20%, text-band 10%（n=20）<br>article/sp: none 58%, text-band 25%, internal-thumb-left 16%（n=24） | 2 | 2 | 0 | #112: 観察例は少ないがテーマ A/B は標準搭載 |
| 吹き出し | article/pc: none 82%, yes-icon-left 13%, yes-both-sides 2%, other:右アイコン 2%（n=45）<br>article/sp: none 81%, yes-icon-left 13%, yes-both-sides 2%, other:右アイコン 2%（n=43） | 6 | 3 | 1（手組み） | #113 |
| 表 | article/pc: none 38%, product-table-with-cta 32%, simple-striped 12%, compare-sticky-first-col 12%, other:横スクロール 3%（n=31）<br>article/sp: none 33%, product-table-with-cta 27%, compare-sticky-first-col 18%, simple-striped 12%, other:tabs-table 3%, other:横スクロール表 3%（n=33） | 2（比較カード表） | 記述 4 | 1 | #116/#117: 先頭列固定 / 縞 / 商品表 + CTA |
| 記事内 CTA | article/pc: product-card-bundle 25%, banner-image 24%, none 24%, button-only 17%, box-with-copy 8%（n=58）<br>article/sp: banner-image 23%, product-card-bundle 23%, button-only 21%, none 20%, box-with-copy 10%（n=64） | 6 | 4 | 1 | #117: ボタンのみ / コピー付き箱 / バナー画像 / 商品カード束 |
| 関連・人気の出し方 | article/pc: sidebar-widget-list 54%, grid-cards 18%, ranking-numbers 13%, carousel 5%, thumb-list-1line 5%, series-prev-next 2%（n=37）<br>article/sp: grid-cards 35%, thumb-list-1line 29%, carousel 11%, series-prev-next 5%, text-numbered 5%, other:テキスト罫線 5%（n=17） | 9（記事リスト） | 9 | 1（手組み） | #110: 8 型提示済。観察: サイドバー list 19%（PC）/ グリッド 6% / 番号ランキング 4% / 1 行サムネ / カルーセル / シリーズ前後 |
| 著者欄 | article/pc: avatar+bio 31%, none 31%, avatar+bio+sns 18%, supervisor-separate 12%, other:left-column 6%（n=16）<br>article/sp: avatar+bio 38%, none 38%, supervisor-separate 23%（n=13） | 4（プロフィール） | 3 | 1 | #109 |
| シェア | article/pc: top-and-bottom 43%, float 31%, none 23%, other:left-column 1%（n=64）<br>article/sp: top-and-bottom 47%, none 33%, float 16%, other:inline-bar 2%（n=42） | – | SNS シェアバー | 0 | #105 |
| サイドバー | article/pc: cta-banner 20%, none 18%, categories 10%, popular-ranking 9%, toc-sticky 8%, search 7%（n=174） | あり | 24 エリア | 0 | #95 |
| カテゴリ面 | article/pc: –<br>article/sp: – | – | – | index のみ | #129: 参照サイトは「ステップ 1→2→3」の画像バナーで子カテゴリ導線、各ブロック 11 件 + 一覧へボタン、PV ランキング |

## 2. 読み取り（Claude 案）

- **実サイトは「装飾が少ない」側に寄る**（h2 は無装飾太字が最多、カードは無枠が最多、囲みは淡塗りが最多）。テーマ A/B が多数持つ装飾型は「引き出し」として必要だが、既定は控えめでよい。試作 02 の方向はここでは外れていない。
- **型数を目標にしない**（PO 指示 2026-09-05）。型は用途 × 目的 × 面ごとに「必要な分だけ」入れる。整理は `by-purpose.md`。テーマ A/B の型数は参考値であって目標ではない。要求 LOOK-01 の「h2 ×3 / h3 ×2 / ボタン ×3」は固定数ではなく用途由来の一覧に置き換える（#122）。
- 世の中の幅（header 6 型、hero 8 型、囲み 10 型、関連 8〜9 型など）は「上限の目安」であり、用途に無い型は作らない。
- **カテゴリ面**は実サイト調査では観察数が少なく、参照サイトの構成（ステップ型子カテゴリ導線・件数付きブロック・PV ランキング）が最も具体的な手本になる（#129）。
- **footer と記事末尾は観察不足**（§5）。ブラウザで末尾だけ再取得する追加調査が必要。

## 3. 語彙外で繰り返し出た型（other:）

- top: 年齢確認ゲート / 地域・言語選択モーダル / メール登録モーダル / ローダー・オープニング / 左縦ナビ / 製品カルーセル / タグ・件数チップ / フィルタ・検索フォーム / 3 連 CTA / marquee
- article: PR チップ・広告表記帯 / 章番号目次 / 左ガター（シェア・著者を左列に置く）/ 横スクロール表 / 評価星・閲覧数 / 中央寄せ h2 / 両側罫線 h2
- これらは語彙 v2 で正式な型に昇格させる候補。

## 4. テーマ A/B の実物 variant 数（`vendor-variants.md` §4 より）

| 項目 | テーマA | テーマB |
|---|---|---|
| ヘッダー | 6 | 6 |
| ヒーロー | 7 | 5 |
| フッター | 3 | 3 |
| 囲み | 20（+引用） | 10 |
| 見出し | 11 | 5 |
| ボタン | 6（記述 10） | 5（記述 5×3 色） |
| リスト | 3 | 4 |
| 表 | 2 | 0（記述 4） |
| ブログカード | 2 | 2 |
| 記事リスト | 9 | 9 |
| ランキング | 2 | 1 |
| 目次 | 2 | 2 |
| CTA | 6 | 4 |
| プロフィール | 4 | 3 |
| 吹き出し | 6 | 3 |
| ステップ | 4 | 1（記述 3） |
| タブ / アコーディオン | 2 / 2 | 1 / 1（+FAQ 2） |
| SP 下部バー | 記述のみ | 1 |
| 色プリセット | 18（デモ数） | 6（デモ数） |
| アイコン | 1 系統（記述 400） | 2 系統 |

## 5. 限界と次の追加調査

- 各 shot は単一コーダー（12 並列）で、評価者間一致は未測定。出現率は探索的頻度として扱う。
- SP の末尾切り出しは高さ上限 1000px のため、末尾 400px が欠ける場合がある（`scripts/crop3.js` の regions 1400 と cap 1000 の差）。footer の na にはこの欠落分も含まれる。

- 切り出しは各面の上部〜中央が中心で、**footer・記事末尾（関連 / 著者 / シェア）・サイドバー下部は多くが na**。遅延読込で未描画の shot も一定数ある（bot 検証・年齢ゲート・ローダーで潰れたサイトは約 20 件）。
- 追加調査案: (a) ブラウザで末尾までスクロールしてから footer / 記事末尾だけを再取得（730 shot）。(b) カテゴリ面を対象に追加した再調査（現状はカテゴリ面の shot がほぼ無い）。(c) 語彙 v2 で再コーディング。
- テーマ A/B の本体コードはローカルに無く（vendor-themes は空の placeholder）、variant 数は公式ページとデモで「実際に見えた数」。記述上の数（例: ボタン 10 種）は別掲。

## 6. 公開情報の扱い

- サイト・テーマ・ベンダー・参照サイトの固有名と URL は書かない。`coded/` のキーは調査 ID のみ。`vendor-variants.md` はテーマ A / B 表記。`ref-site-notes.md` は「参照サイト」表記。取得スクリプトのうち URL を含むもの（vendor-urls / ref）はリポジトリ外に置く。