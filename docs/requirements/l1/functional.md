---
layer: L1
sub_doc: functional
status: confirmed_input
pair_artifact: docs/test-design/l12-operational-value-test-design.md
authority: docs/requirements/authority.md
---

# L1 Functional Requirements

| ID | ユーザー視点の要求（拡大の提案） | 下流 family |
| --- | --- | --- |
| WT-FRL1-01 | 置き場所（面）を選べる: 記事内広告・CV・関連前後・固定ページ上下・ヘッダー内・SP 下部固定・追尾サイドバーなど、テーマA/B にあって本テーマに無い面を slot として持つ | ZONE |
| WT-FRL1-02 | 共有パーツと骨格を選べる: header / footer / sidebar / hero の複数案、テンプレ変種を GUI と AI の双方から差し替えられる。LP は投稿型で持ち（イベント / 比較特設を含む種別、ディレクトリ非依存 URL）、フォーム制御・デザイン拡張・イベント計測を備え、LP / section の目標 CV ID と A/B variant ID を選べる | PARTS / LP |
| WT-FRL1-03 | 記事内語彙で書ける: 囲み・ボタン・リンクカード・吹き出し・手順・比較表・定義リスト・FAQ・タブなど実使用上位の語彙で記事を組める。販売系 4 つ（商品カード・ランキング・比較専用テーブル・CTA 束）を加えた新規ブロック 7 つに限定し、本文中の手組み比較表とは別語彙にする。商品テーブルは variant ID と CV ID を計測へ渡す。目次と PR 表記が自動で出る | VOCAB |
| WT-FRL1-04 | 見た目の引き出しがある: 見出し階層・見出し / ボタンの block style・最小限の動き・レスポンシブ段・style variation でテーマA/B 並みの表現に届き、さらにサイトパターン（コーポレート / サービス / ブランド / ポータル / 比較）の品質水準まで広げる（大量調査が前提） | LOOK |
| WT-FRL1-05 | 記事単位で切り替えられる: サイドバー・目次・シェア・PR を投稿ごとに ON/OFF できる | META |
| WT-FRL1-06 | 既存サイトの設定を写せる: カスタマイザ・設定画面・ウィジェット・プリセット・独自ブロックの写像先を、取得項目定義とマッピングフォーマットとしてテーマが公開する。移行の実行はハーネスの責務 | MIGRATE |
| WT-FRL1-07 | エージェントが JSON で全部を操作できる: 面・部品・値・変種・テンプレの選択、中間 JSON の抽出、再利用パーツの参照が、設定で束ねた MCP 常用パック（主経路）と REST / CLI（従属経路）から行える | AGENT |
| WT-FRL1-08 | 値は 3 域で制御される: 安全域は自由、生値は警告、破壊域は停止する。境界値は PoC で決める | VALUE |
| WT-FRL1-09 | 構造化データと AI 向け出力が単一出力元から出る: CollectionPage / SearchAction を加え、FAQ / HowTo / ItemList は語彙から自動生成する。SEO の要件と実装は Google 検索セントラルの公式ドキュメントに準拠し、必須プロパティ・非推奨型・title / meta / canonical / robots / sitemap / hreflang・Core Web Vitals・モバイル・リンク rel 属性を機械検査する（出典: https://developers.google.com/search/docs/crawling-indexing、参照日: 2026-09-03） | SEO |
| WT-FRL1-10 | 実証済みパターンを証跡付きの記録台帳に残せる（他プロダクトは記録を読んで採否を自分で決める。依存なし） | INTAKE |
| WT-FRL1-11 | 管理画面から普通に設定できる: サイト全体の既定（目次・PR 表記・slot / ゾーン・SP 下部・LP 種別・MCP パック）を AI を介さずテーマ設定画面で設定でき、正本は schema 付き設定 JSON 1 本で AI と共有する | ADMIN |
| WT-FRL1-12 | 見出し区間（H2 / H3）単位で制御できる: 安定 ID を持つ階層 section を単位に、差し替え・リライト・順序入れ替え・面の挿入・表示制御・計測を人（エディタ）と AI（MCP パック）の双方から行える。section の variant ID と目標 CV ID を計測へ渡す | SECTION |
| WT-FRL1-13 | 売るための構成ができる: 商品正本から商品カード・ランキング・比較専用テーブル・CTA 束・レビューを同じ正本で出し、Product / Offer / AggregateRating / ItemList 構造化データ、複数記事参照、一括反映、商品リンクのクリック計測、AI を介さない商品編集、MCP 常用パックへの追加・更新・差し込みを行える。決済・カート・会員と購入完了の計測はテーマ外 | SELL |
| WT-FRL1-14 | クローラー計測ダッシュボードを持てる: bot の来訪を専用ログへ記録し、クローラー別推移・古い URL・404 / 5xx・新規公開記事の初回捕捉時間・llms.txt / crawl-map への AI クローラーアクセス有無を管理画面で確認し、robots.txt と AI クローラーの許可 / 拒否を同画面から設定できる。Search Console 突合と順位計測はテーマ外 | CRAWL |
| WT-FRL1-15 | A/B の配信・計測・停止ができる: H2 / H3 section、hero / CTA / 商品テーブルのパーツ、LP 全体を variant 単位で選べ、Core プラグイン API の DB 選択と cookie 固定割当で配信する。bot には既定案のみを返し、impression / click / CV は variant ID 付きで追跡し、承認・停止・rollback は WT-UI-10 と MCP 常用パックから行う | AB |
| WT-FRL1-16 | 画像・短尺動画を高速に最適化できる: WebP / WebM を生成・配信し、picture / srcset、hero の高優先度、その他の遅延読込、非同期一括処理、管理画面・CLI・MCP の再生成 dry-run、alt 警告、GIF 置換提案、Discover 代表画像要件を扱う。商品画像・バナー画像も同じ経路にする | IMG |
| WT-FRL1-17 | 管理画面で運用の裏方を扱える: 操作ログの絞り込みと CSV / JSON export、dry-run 差分の適用 / 却下、破壊域停止分の確認、直近変更の rollback と記録、HELIX 接続用 API key の発行・失効と読み専用 / 書き込み可の権限を WT-UI-10 のタブで操作する | ADMIN |
| WT-FRL1-18 | SNS 連携を持てる: SNS profile を設定 JSON の一か所から header / footer / 著者欄 / sameAs へ反映し、対象と配置を選べる share、クリック計測、遅延読込の feed 埋め込み、メッセージアプリ公式アカウント追加ボタン・QR の LP CTA を提供する。配信と資格情報はテーマ外の境界を保つ | SNS |
| WT-FRL1-19 | 複数の CV を設計・選択できる: 本 CV・マイクロ CV・補助指標を ID 付き正本へ登録し、資料 DL の 2 経路と完了計測、記事・LP・section の目標選択、A/B・section の CV ID 集計、CTA の主文言 + 任意 microcopy の候補選択を提供する。microcopy は必須化しない | CV |
| WT-FRL1-20 | バナーを登録・管理・配置・計測できる: PC / SP 画像、リンク、alt、種別、有効期間、PR 要否を正本化し、商品 ID 派生とゾーン割当、固定 / ローテーション、impression / click・CV ID・variant の追跡、期限切れ等の警告を WT-UI-10 と MCP 常用パックで扱う | BANNER |
| WT-FRL1-21 | HELIX 側の監査結果を管理画面で見て直せる: 記事・section・LP・バナーの指摘を受け取り、件数バッジ・対象へのリンク・適用 / 却下 / 保留を表示し、適用は dry-run → 差分レビューを通す。決定論的に検査できる項目だけをローカル検査し、その他の判断は受け取った結果を表示する | AUDIT |

L1 の ID はユーザー要求であり、L3 の system requirement ID とは区別する。
各行はテーマA / B との PoC 比較で本テーマに不足すると分かった面・語彙・引き出しを、機械可読性を保つ形で取り込む提案である
（出典: `docs/research/2026-08-26-theme-structure-audit/04-diff-register.md`、`docs/design/catalog/customizability.md`、`docs/research/2026-08-27-poc-browser-verification/theme-comparison.md`）。
