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
| カテゴリ面 | article/pc: –<br>article/sp: – | – | – | index のみ | #129: 参照サイトは「ステップ 1→2→3」の画像バナーで子カテゴリ導線、各ブロック十数件 + 一覧へボタン、PV ランキング |

### 1b. 再取得 v2（footer・記事末尾・カテゴリ面、`recapture-v2/`）

§1 で観察不足だった 3 領域を、末尾までスクロールしてから再取得し（対象 278 サイト、1 面以上取得できたのは 268 サイト。1,047 key = サイト×面×端末、画像はリポ外）、語彙 v2（`recapture-v2/PARTS-VOCAB-v2.md`）でコーディングした。集計は `recapture-v2/aggregate-v2.md`、取得記録は `recapture-v2/CAPTURE-SUMMARY.md`。%は n 比（na を含む）。

| パーツ | 実サイト（PC） | 実サイト（SP） | 読み取り（Claude 案） |
|---|---|---|---|
| footer 構成 | top: mega(sitemap) 35%, single-row 23%, columns-3 9%, columns-4 8%（n=254）<br>cat: mega(sitemap) 40%, single-row 23%, columns-3 12%, columns-4 9%（n=184） | top: single-row 26%, mega(sitemap) 19%, accordion(sp) 16%, stacked-centered 11%（n=267）<br>cat: mega(sitemap) 28%, single-row 25%, accordion(sp) 16%, stacked-centered 10%（n=190） | PC は sitemap 型（mega）と 1 行型の 2 極。SP は 1 行 / mega / アコーディオン / 中央積み。列数指定（2〜4 列）は PC 専用の変種で、SP では積みかアコーディオンへ畳む |
| footer 直上の帯 | top: banner-row 33%, none 26%, cta-band 17%, newsletter 4%（n=272） | top: none 35%, banner-row 21%, cta-band 17%, newsletter 6%（n=276） | バナー列と CTA 帯が 2 大。newsletter・問い合わせブロックは用途依存（ポータル / 企業 HP） |
| footer ナビ | top: sitemap-full 54%, legal-only 14%, primary-only 11%, none 6%（n=254） | top: sitemap-full 42%, legal-only 14%, primary-only 11%, none 10%（n=267） | 全サイトマップが半数。法定リンクのみ・主要ナビのみが各 1 割強。SP ではカテゴリリンク型が増える |
| footer 付属 | top: sns-icons 38%, none 20%, related-sites 9%, certification-badges 9%（n=320） | top: sns-icons 36%, none 20%, certification-badges 8%, address 7%（n=343） | SNS アイコンが最多。関連サイト・認証バッジ・住所 / 電話は用途依存 |
| 法定表示 | top: copyright+links 44%, copyright-only 30%, none 5%, other:tagline 0%（n=254） | top: copyright+links 35%, copyright-only 35%, none 7%, other:links-only 0%（n=267） | copyright + 法定リンク列 と copyright のみ の 2 型で十分 |
| back-to-top | top: none 58%, button-fixed 15%, inline-link 3%（n=254） | top: none 62%, button-fixed 11%, inline-link 4%（n=267） | 無しが多数。固定ボタンは 1 割強。既定 OFF の選択肢 |
| footer 周辺の固定パーツ | top: none 57%, cookie-consent 11%, float-cta 7%, float-chat 6%（n=257） | top: none 59%, cookie-consent 11%, float-chat 5%, float-cta 5%（n=272） | cookie 同意 1 割、float CTA / chat 各 5%、SP 下部バー 4% |
| 記事末尾の並び順 | related 29%, author 12%, share 12%, cta 9%, prev-next 7%, comments 5%（n=177） | related 22%, author 14%, cta 11%, category-links 9%, share 9%, ad 6%（n=142） | 頻度上位は 関連 / 著者 / シェア / CTA（順序は集計していない。並び順の既定は by-purpose §2b の Claude 案）。SP はカテゴリリンク・ランキングが末尾に出る |
| 記事末 CTA | none 65%, box-with-copy 10%, banner-image 8%, button-only 5%（n=78） | none 73%, box-with-copy 13%, banner-image 5%, line/newsletter 2%（n=73） | 無しが 2/3。あるときはコピー付き箱 > バナー画像 > ボタンのみ |
| 記事末シェア | none 60%, icons-row 30%, icons-with-count 2%, text-buttons 1%（n=78） | none 71%, icons-row 13%, text-buttons 4%, icons-with-count 2%（n=73） | アイコン列 1 型で足りる。件数付きは 2% |
| 著者欄 | none 60%, avatar+bio+sns 14%, avatar+bio 14%, name-only 5%（n=78） | none 65%, avatar+bio+sns 12%, avatar+bio 9%, name-only 6%（n=73） | 無し 6 割。avatar+bio（SNS 有 / 無）の 2 型 + 監修別枠 |
| 関連記事（末尾） | grid-cards 36%, none 29%, thumb-list-1line 13%, featured-big+small 5%（n=79） | none 46%, thumb-list-1line 20%, grid-cards 9%, ranking-numbers 6%（n=73） | PC はグリッド、SP はサムネ 1 行リストが主。件数は 1–3 / 4–6 / 7+ に分散（`tail.related.count`） |
| 前後記事 | none 78%, with-thumb 11%, text-only 6%（n=78） | none 89%, text-only 5%, with-thumb 4%（n=73） | 無しが 8〜9 割。サムネ付き 1 型で足りる |
| カテゴリ見出し | name-only 42%, name+description 26%, hero-style 5%, name+count 4%（n=184） | name-only 46%, name+description 24%, hero-style 5%, name+count 2%（n=190） | 名前のみ 4 割、名前 + 説明 1/4。hero 型・件数付きは 5% 以下 |
| 子カテゴリ導線 | none 56%, chips 29%, list 3%, cards 2%（n=184） | none 55%, chips 25%, list 7%, cards 2%（n=190） | 無し半数、chips 1/4〜3 割。カード / 画像バナー / ステップ番号は稀（参照サイトの型は世の中では少数） |
| カテゴリ導入文 | none 67%, lead-text 21%, editorial-article 3%（n=184） | none 68%, lead-text 20%, editorial-article 5%（n=190） | リード文 2 割、編集記事型 3〜5% |
| カテゴリ ミニ HOME | none 86%, yes(sections per child) 8%（n=184） | none 87%, yes(sections per child) 7%（n=190） | 子カテゴリ別セクション構成は 7〜8%（compare / portal / major / service に分布）。要求 SEO-01 の「カテゴリ ミニ HOME」は少数派の型を選ぶ判断であり、既定にするなら理由が要る |
| カテゴリ内ランキング | none 85%, sidebar 8%, bottom 1%, top 0%（n=184） | none 88%, bottom 4%, sidebar 1%, top 1%（n=190） | 無し 85%。PC はサイドバー、SP は末尾に置く |
| 一覧レイアウト | grid 44%, text-list 19%, thumb-list 14%, featured+grid 4%（n=184） | thumb-list 35%, text-list 23%, grid 14%, featured+grid 8%（n=190） | PC グリッド、SP サムネリスト（または文字リスト）。featured+grid は 4〜8% |
| 一覧カード | image-top 25%, with-date 24%, with-category-chip 16%, with-excerpt 12%, title-only 8%（n=355） | image-top 25%, with-date 23%, with-category-chip 17%, with-excerpt 12%, title-only 9%（n=373） | 画像上 + 日付 + カテゴリチップ が基本要素。抜粋は 12% |
| 1 画面あたり件数 | 7-12 26%, 13+ 15%, 7+ 15%, 1-6 14%（n=184） | 7+ 21%, 1-6 16%, 7-12 14%, 13+ 6%（n=190） | PC は 7–12 件が最多。SP は na が 36% で 7+ と 1–6 に分かれ、1 画面で件数を読める割合が低い |
| ページ送り | numbers 27%, none 16%, load-more 9%, prev-next 3%（n=185） | numbers 19%, none 9%, load-more 6%, prev-next 3%（n=190） | 観察できた範囲では番号 > もっと見る > 前後。無限スクロールは 1 件 |
| カテゴリ面サイドバー（PC） | none 52%, categories 16%, popular-ranking 10%, profile 4%, tags 3%（n=186） | – | 無し半数。カテゴリ一覧・人気ランキングが主 |

## 2. 読み取り（Claude 案）

- **実サイトは「装飾が少ない」側に寄る**（h2 は無装飾太字が最多、カードは無枠が最多、囲みは淡塗りが最多）。テーマ A/B が多数持つ装飾型は「引き出し」として必要だが、既定は控えめでよい。試作 02 の方向はここでは外れていない。
- **型数を目標にしない**（PO 指示 2026-09-05）。型は用途 × 目的 × 面ごとに「必要な分だけ」入れる。整理は `by-purpose.md`。テーマ A/B の型数は参考値であって目標ではない。要求 LOOK-01 の「h2 ×3 / h3 ×2 / ボタン ×3」は固定数ではなく用途由来の一覧に置き換える（#122）。
- 世の中の幅（header 6 型、hero 8 型、囲み 10 型、関連 8〜9 型など）は「上限の目安」であり、用途に無い型は作らない。
- **カテゴリ面**は再取得 v2（§1b）で 195 サイト分を観察した。世の中の多数派は「名前（+ 説明）→ グリッド / サムネリスト → 番号ページ送り」で、子カテゴリ導線は chips、ミニ HOME 構成は 7〜8%。参照サイトの構成（ステップ型子カテゴリ導線・件数付きブロック・PV ランキング、#129）は少数派の型として位置づけ、比較媒体 / ポータルの回遊目的に限って採る案。
- **footer と記事末尾**は再取得 v2（§1b）で解消。footer は PC「サイトマップ型 / 1 行型」+ SP「1 行 / アコーディオン / 中央積み」、直上は「バナー列 / CTA 帯」、記事末尾は 関連 / 著者 / シェア / CTA の 4 要素が頻度上位（並び順は集計しておらず、既定順は Claude 案）。

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

- §1 の切り出しは各面の上部〜中央が中心で、footer・記事末尾・サイドバー下部は多くが na だった。→ 再取得 v2（§1b、`recapture-v2/`）で footer / 記事末尾 / カテゴリ面を末尾までスクロールして取得し直し、語彙 v2 でコーディングした（1,047 key、単一コーダー 12 並列、評価者間一致は未測定）。
- v2 の限界: カテゴリ導線をトップから見つけられなかったサイトが 91 件（`no_cat_link`、サイト×端末では 172 行）、bot 壁・タイムアウトで取れなかった面がある（`recapture-v2/CAPTURE-SUMMARY.md`）。カテゴリ面として取得したページの一部は単一記事だった（`na` / `other:article` として記録）。記事末尾は記事 URL を持つ 95 サイトのうち 78 件のみ。SP の `pagination` は 6 割が na（1 画面に入らない）。
- テーマ A/B の本体コードはローカルに無く（vendor-themes は空の placeholder）、variant 数は公式ページとデモで「実際に見えた数」。記述上の数（例: ボタン 10 種）は別掲。

## 6. 公開情報の扱い

- サイト・テーマ・ベンダー・参照サイトの固有名と URL は書かない。`coded/` のキーは調査 ID のみ。`vendor-variants.md` はテーマ A / B 表記。`ref-site-notes.md` は「参照サイト」表記。取得スクリプトのうち URL を含むもの（vendor-urls / ref）はリポジトリ外に置く。
### 1c. LP 面の再収集（`lp-recapture/`、2026-09-06、PO 指示 WT-EVT-0268）

PO 反応 16 回目「まだLPは弱すぎるからリスティング広告とかクリックしてデザインを再収集してきてくれるか？」を受けた LP 面の観察。広告のクリックは広告主に課金が発生するため行わず、検索の自然結果と LP ギャラリー系サイトの一覧から URL を集めて read-only で取得した（PO へ方針を提示済み）。

- 収集: **39 件**（A 企業向け SaaS / BtoB 8、B 個人向けサービス 14、C 資料 DL・比較媒体 5、D イベント・セミナー・展示会・キャンペーン 8、E EC・単品商品 4）。fetched: full 34 / partial 5 / failed 0（除外 4〜5 件の理由は `summary.md` §5）。
- 記録: `observations.json`（1 件 1 オブジェクト。`sections_order`、9 パーツの型（`interview_card` / `review` / `external_rating` / `download` / `embedded_form` / `line_cta` / `float_button` / `hero` / `cta_style`）、`notes`）と `summary.md`（区分 × 7 パーツの出現率、区分別セクション順、型内訳、多数派の型、限界）。
- 多数派の型（観察事実、`summary.md` §4）: インタビューカードは summary-card（遷移リンクなしのサマリー表示）、口コミは quote+photo、外部評価は certification（認証・許認可）、資料 DL は button-to-form、フォームは external（外部遷移）、LINE 導線は button、フロートボタンは sp-bottom-bar。D（イベント）区分は埋め込みフォーム 75% 以外のパーツがほぼ 0 で、来場登録は外部フォームへの誘導が多い。
- 限界（`summary.md` §5）: 39 件のうち 20 件（site-L06〜L25）は初回パスの記録を再分類したもので、対応表に元 URL が引き継がれず今回のパスで実ページを再検証できていない。各件の `reverified: false` と `reverify_note` で識別できる（`fetched` は初回取得時の状態で、再検証状態とは別）。集計はこの 20 件を含む 39 件で行っている。JS 描画中心のページはフィールド数などを判定できず partial。区分 C・E は n が 4〜5 件で割合は少数からの算出。
- 公開情報: 固有名・ドメインは書かない。site-Lxx と実サイトの対応表はリポジトリ外（scratchpad / 非公開）にのみ置く。
- 次段（Claude 案、PO 判断待ち）: 試作 03 の LP に 7 パーツ（インタビューカード・口コミ評価・外部評価・資料 DL・埋め込みフォーム・LINE 導線・フロートボタン）を、ここで観察された多数派の型を既定にして試作する。
