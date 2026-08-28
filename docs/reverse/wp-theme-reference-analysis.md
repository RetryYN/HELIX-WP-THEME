# WPテーマ参照解析レポート

## 目的

新規WPテーマ開発に向けて、参照テーマから「何を取り込んで設計すべきか」を抽出する。コード、画像、固有デザインのコピーは対象外とし、設計パターン、機能カテゴリ、操作契約、パッケージ分割方針を抽出対象にする。

## 解析対象

| 対象 | 種別 | 状態 |
|---|---|---|
| `themeB-2.16.0/themeB` | 親テーマ | 主解析対象 |
| `themeB_child/themeB_child` | 子テーマ | 差分小。CSS読み込みテンプレート相当 |
| `themeA-child/themeA-child` | 子テーマ | 差分小。親CSS enqueueのみ |
| `themeA-parent/themeA/themeA` | 親テーマ | SEO統合設計、プリセットUX、classic template制約の解析対象 |

## R0 Evidence

`themeB` は `functions.php` を薄い起動層にして、実体を `lib/`、`classes/`、`parts/`、`src/gutenberg/blocks/`、`build/` に分割している。

| 領域 | 観測 |
|---|---|
| PHP | 331ファイル。テーマ本体、管理画面、CPT、REST、ショートコード、ウィジェット、メタ、Gutenberg連携が中心 |
| CSS | 137ファイル。フロント、管理画面、エディタ、ブロック、ページ種別別CSSに分割 |
| JS | 100ファイル。フロント動作、管理画面、カスタマイザー、Gutenberg、軽量プラグイン |
| block.json | 33件前後。独自Gutenbergブロックを機能単位で定義 |
| テンプレート | `404.php`、`archive.php`、`author.php`、`home.php`、`page.php`、`single.php`、`single-lp.php` など |

主要ディレクトリ:

| ディレクトリ | 役割 |
|---|---|
| `lib/` | WordPressフック、CPT、REST、カスタマイザー、読み込み制御、出力制御 |
| `classes/` | 状態管理、設定データ、スタイル生成、ユーティリティ、管理画面UI |
| `parts/` | header/footer/single/post-list/topなどのテンプレート部品 |
| `src/gutenberg/blocks/` | 独自ブロック定義とSCSS |
| `build/` | 配布用CSS/JS |

## R1 Observed Contracts

### WordPress統合契約

| 契約 | 観測 | 新テーマでの設計候補 |
|---|---|---|
| Theme bootstrap | `functions.php` で環境チェック、autoload、主要lib読込 | `ThemeKernel` または `Bootstrap` を置き、読み込み順をJSON化 |
| Theme supports | menus/widgets/title-tag/post-thumbnails/align-wide/html5/editor paletteなど | 最小core supportを共通化し、packageごとの差分をfeature flag化 |
| Nav menus | header/sp/footer/fixed bottom/pickup banner | 個人: header/footer/mobile、法人: pickup/CTA/mega menu |
| Widget areas | sidebar、front/page/single上下、CTA、related前後、footer複数 | 個人: 少数、法人: LP/CTA/部門別エリアまで拡張 |
| CPT | LP、blog_parts、ad_tag | 個人: LP + reusable parts、法人: reusable parts + ad/CTA + approval-ready entity |
| Taxonomy | `parts_use` で再利用パーツ用途を分類 | ブロックパターン/CTA/カテゴリ用パーツ分類として採用候補 |
| REST | block settings、PV、button/ad counter、resetなど | JSON操作基盤のAPI候補。ただし公開POSTは認可・nonce・rate limitを強化 |
| Gutenberg | accordion、FAQ、step、tab、post-list、button、review、restricted-areaなど | エージェントが生成しやすいブロック契約として優先度高 |
| Customizer | header/top/post_list/single/footer/sidebar/snsなど | 個人は簡易プリセット、法人はJSON theme profile + 管理画面 |
| Dynamic style | `classes/Style/*` で設定値からCSS生成 | デザイントークンJSONからCSS変数生成へ置き換える |

### 独自ブロック候補

優先して設計に取り込む価値が高いもの:

| ブロック種別 | 理由 | パッケージ |
|---|---|---|
| FAQ | SEO/記事品質/法人サイトFAQに共通で有用 | 共通 |
| Step | 手順記事、サービス説明、導入フローに有用 | 共通 |
| Button | CTA設計の基礎 | 共通 |
| Post List | 回遊性、メディア運用、法人ニュースに有用 | 共通 |
| Link List | ランディング導線、資料リンクに有用 | 共通 |
| Accordion | FAQ/補足情報/料金注記に有用 | 共通 |
| Full Wide | LPセクション構築に有用 | 個人+法人 |
| Banner Link | キャンペーン/サービス導線に有用 | 個人+法人 |
| Blog Parts | 再利用部品の基盤 | 法人優先 |
| Restricted Area | 会員/社内/限定公開に近い要求が出やすい | 法人 |
| Ad Tag/Review | アフィリエイト・比較記事向け | 個人優先 |

## R2 As-Is Design

### 取り込むべき設計パターン

| 設計パターン | 採用判断 | 理由 |
|---|---|---|
| 起動層を薄くして機能を分割 | 採用 | AIエージェントが対象範囲を限定して編集しやすい |
| parts分割テンプレート | 採用 | header/footer/single/top/post-list単位で差分設計しやすい |
| Gutenberg block.json中心 | 採用 | JSONベース操作と相性が良い |
| 設定値を一元保存してCSS生成 | 採用。ただしCSS変数中心に再設計 | パッケージ別テーマプリセットを作りやすい |
| CPTによる reusable parts | 採用候補 | 法人向けの共通CTA、部署別導線、広告枠に有効 |
| ショートコード大量提供 | 制限付き採用 | ブロック優先。ショートコードは互換・埋め込み用途に限定 |
| RESTでカウンター更新 | 再設計 | 認可なしPOSTは濫用されやすい。nonce/rate limit/集計方式が必要 |
| 管理画面独自メニュー | 法人向けに採用 | 個人向けは設定が重くなりやすい |
| 旧Widget互換 | 原則非優先 | 新規開発ではブロックウィジェット/パターン優先 |

### JSONベース操作に落とすべきもの

| JSON | 目的 |
|---|---|
| `theme-manifest.json` | テーマ機能、対応WP/PHP、package種別、feature flags |
| `design-tokens.json` | 色、余白、角丸、影、タイポグラフィ、ブレークポイント |
| `layout-registry.json` | header/footer/sidebar/single/archive/LP の構成 |
| `block-registry.json` | 独自ブロック一覧、属性、許可innerBlocks、package対応 |
| `component-contracts.json` | PHP template part と block/render callback の入出力 |
| `agent-actions.schema.json` | AIエージェントが実行可能な操作の制約 |
| `package.matrix.json` | personal/corporateで有効化する機能差分 |

## R3 Intent Hypotheses

参照テーマの意図は、単なる見た目ではなく「記事作成者が管理画面とブロックだけでサイト部品を組み立てられること」にあると推定する。

新テーマでの意図は次のように再定義するのがよい。

| 意図 | 個人パッケージ | 法人パッケージ |
|---|---|---|
| 速く立ち上げる | プリセットと最低限の設定 | ブランドプリセット、部門別テンプレート |
| 記事を書く | FAQ/Step/Button/PostList/Review | FAQ/Step/Button/PostList/CTA/資料導線 |
| 回遊させる | 関連記事、人気記事、投稿リスト | 導入事例、ニュース、サービス導線 |
| LPを作る | single LP + Full Wide | 複数LP、CTA管理、テンプレート承認 |
| 再利用する | 少数のパターン | Blog Parts相当の共通部品管理 |
| 計測する | 簡易クリック/PV | CTA/広告/資料DLのイベント設計 |
| 運用する | 低設定、軽量 | 権限、監査、承認、チーム運用 |

## R4 Package Routing

### 共通Core

| 機能 | 理由 |
|---|---|
| Theme bootstrap / autoload | 保守性の基盤 |
| Template parts | AIが安全に差分編集しやすい |
| Design tokens -> CSS variables | パッケージ/ブランド差分をJSONで制御 |
| Core blocks: FAQ, Step, Button, Accordion, Post List, Link List | 個人/法人どちらにも必要 |
| Header/footer/mobile navigation | 全サイト共通 |
| SEO最低限: title/meta/schema breadcrumbs | WordPressテーマとして必須級 |
| Performance: conditional assets, lazy load, separate block CSS | 体感速度と保守性に効く |
| Agent action schema | AI完結操作の中核 |

### 個人パッケージ: アフィリエイト特化

| 優先 | 機能 | 備考 |
|---|---|---|
| P0 | Review/Ranking/Comparison | 商品比較、ランキング、レビュー記事を最短で作る |
| P0 | Ad Tag / Affiliate CTA | 広告タグ、ボタン、商品カード、計測IDを管理 |
| P0 | Blog Card / Related Post | 内部回遊とCV導線を強化 |
| P0 | FAQ / Step / Pros Cons | 検討系・商標系記事に必要な構造化コンテンツ |
| P1 | Pickup banner / post slider | 収益記事への導線 |
| P1 | 簡易クリック計測 | ボタン/広告クリックを軽量に記録 |
| P1 | 構造化データ | Review, FAQ, Breadcrumb, Articleを優先 |
| P2 | LP簡易作成 | 商標LPやキャンペーン用。法人ほど高機能にしない |
| P2 | 細かいカスタマイザー | 設定過多を避け、収益化テンプレートを優先 |

### 法人パッケージ: 製品宣伝/LP強化

| 優先 | 機能 | 備考 |
|---|---|---|
| P0 | ブランドトークン管理 | 色、ロゴ、余白、フォント、角丸 |
| P0 | LPセクションビルダー | Hero、Feature、Benefit、Use Case、Pricing、FAQ、CTA |
| P0 | 製品/サービス導線 | 製品カード、機能比較、導入事例、問い合わせCTA |
| P0 | 再利用パーツ管理 | CTA、問い合わせ導線、資料DL、共通セクション |
| P0 | 権限分離 | 管理者、編集者、寄稿者、承認者の操作範囲 |
| P0 | LP/サービスページテンプレート | 複数製品・複数事業・部門に対応 |
| P1 | CTA/リード獲得計測 | 問い合わせ、資料DL、外部フォーム遷移を計測 |
| P1 | Trust要素 | 導入企業、実績、セキュリティ、サポート、保証 |
| P1 | 構造化データ管理 | Organization, Breadcrumb, Article, FAQ |
| P1 | 監査・設定エクスポート | JSONで設定差分を追跡 |
| P2 | restricted area | 会員/代理店/採用資料など用途がある場合 |

## 設計上の注意

| リスク | 対応 |
|---|---|
| ライセンス | 参照テーマのコード・画像・固有CSSはコピーしない。機能要件と設計抽象のみ取り込む |
| REST濫用 | 公開POSTを作る場合は nonce、permission_callback、rate limit、bot対策を必須化 |
| 設定過多 | 個人向けはプリセット中心、法人向けだけ詳細設定を開放 |
| ブロック肥大化 | まず共通ブロックを少数に絞り、package別に追加 |
| AI操作の破壊性 | JSON actionは `dryRun`、対象パス制限、差分レビュー、schema validationを必須にする |

## 次の設計タスク

1. `package.matrix.json` の初版を作る。
2. `agent-actions.schema.json` の初版を作り、許可アクションを固定する。
3. `block-registry.json` で共通/個人/法人のブロック境界を決める。
4. `design-tokens.json` からCSS変数を生成する方針を決める。
5. REST/APIは法人向け計測・設定管理を中心に契約定義する。

## Gate

| Gate | 判定 | 根拠 |
|---|---|---|
| RG0 | passed | ファイル構成、主要ディレクトリ、主要拡張子、エントリーポイントを確認 |
| RG1 | passed | WP契約として theme supports、menus、widgets、CPT、taxonomy、REST、block.json を確認 |
| RG2 | passed | 採用/再設計/非優先の設計判断を分類 |
| RG3 | pending | 個人/法人パッケージの事業優先度はユーザー確認が必要 |
| R4 | passed | 共通Core、個人、法人へのroutingを作成 |
