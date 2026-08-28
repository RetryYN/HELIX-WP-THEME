# LP/HP設計方針

## 結論

AGENT NEOでは、LPとHPを同じ「固定ページデザイン」として扱わない。役割が違うため、JSON契約もUIも分ける。

| 種別 | 目的 | 成功指標 | 優先パッケージ |
|---|---|---|---|
| LP | 1商品/1サービス/1オファーを成約・問い合わせ・資料DLへ誘導する | CTA CVR、スクロール到達、フォーム到達、商談化 | 法人版 |
| HP | サイト全体の入口として、ブランド理解、カテゴリ回遊、信頼形成、記事/商品導線を作る | 回遊率、主要カテゴリ遷移、再訪、指名検索補助 | Core + 個人/法人 |
| 個人向け収益ページ | 比較・ランキング・レビューからASPクリックへ誘導する | affiliate click、CTR、PR表記遵守 | 個人版 |

参考テーマからの採用方針は次の通り。

| 領域 | 優先参考 | 理由 |
|---|---|---|
| LP構造 | ThemeB | `lp` CPT、専用テンプレート、ヘッダー/フッター切替、コンテンツ幅、ブログパーツ再利用がある |
| HP/トップ演出 | テーマA | メインビジュアル、トップページ1/2カラム、リッチメニュー、デモプリセットの訴求力がある |
| AGENT NEO | 独自 | LP/HPをAIがJSONで生成・差分更新・計測改善できる契約にする |

## ThemeBから見るLP設計

### 実コード根拠

| ファイル | 観測内容 | AGENT NEOへの示唆 |
|---|---|---|
| `lib/post_type.php` | `lp` CPTを登録。REST対応、thumbnail、revisions、custom-fields対応 | LPは通常固定ページと分けたほうが運用・権限・計測が整理しやすい |
| `single-lp.php` | LP専用テンプレート。子テーマの `lp/{slug}.php/html` を優先表示 | 高度なLPだけ個別テンプレート上書きを許可できる |
| `lib/post_meta/meta_lp.php` | content width、囲み枠、アイキャッチ、タイトル、ThemeBスタイル適用、ヘッダー/フッター使用を制御 | LPごとの表示制御はJSON化すべき |
| `lib/gutenberg/block_pattern/page.php` | ページ用パターンを登録 | LP/HPセクションをpatternとして管理できる |
| `lib/shortcode.php`, `lib/gutenberg/block/blog-parts.php` | ブログパーツを再利用可能部品として挿入 | AGENT NEOではReusable Section/Global Partとして再設計する |
| `parts/single/after_article.php` | 記事下CTA、著者、関連記事などの導線 | LPだけでなく記事/レビューからLPへ送る導線に使える |

### 採用する設計

- LPは `lp_blueprint` として通常固定ページから分ける。
- ヘッダー/フッターあり、なし、簡易ナビ、追従CTAをLPごとに切り替える。
- LPごとに `offer_id`、`primary_cta_id`、`section_id` を必須化する。
- 子テーマ上書きのような自由度は、`custom-template-slot` として安全に制御する。
- ブログパーツ思想は、`global-section`、`reusable-part`、`cta-part` として再設計する。

## テーマAから見るHP設計

### 実コード根拠

| ファイル | 観測内容 | AGENT NEOへの示唆 |
|---|---|---|
| `home.php` | トップページ用ウィジェット、投稿リスト、1/2カラム切替 | HPは回遊ハブとして設計されている |
| `page.php` | front page時にトップウィジェットとカラム制御を追加 | 固定ページ型HPとブログ型HPの両方を扱う必要がある |
| `object/main-visual/stillimage.php` | メインコピー、サブコピー、ボタン、画像/オーバーレイ | HP Heroの基本モデルとして有効 |
| `object/main-visual/post-slider.php` | 記事URL指定、サムネイル、タイトル、Read more | メディア/アフィリエイトHPのpickup導線に有効 |
| `include/customizer/ui/main-visual-setting.php` | post slider、image slider、still image、movieを選択 | Hero variantを契約化できる |
| `include/customizer/ui/site-design-setting.php` | トップページ1カラム/2カラム、ページフレーム | HPの情報密度をペルソナ別に変える設計に使える |
| `class-themeA-demo-import-control.php` | richmenu、デモページ、ブロックパターンを大量に持つ | デモプリセット/業種別スターターの販売価値が高い |

### 採用する設計

- HPは `home_blueprint` として、ブランド入口と回遊導線を管理する。
- Heroは `still`, `article_slider`, `image_slider`, `movie`, `product_focus`, `lead_gen` のvariantを用意する。
- テーマAのリッチメニュー思想は、`gateway-grid` として採用する。
- デモインポート思想は、業種別/目的別スターターとして商品価値にする。
- ただし巨大CSS、外部画像、直書きHTMLは採用しない。FSE pattern + JSON契約で再設計する。

## LPの標準構成

### 法人LP

法人版は `¥98,000` を正当化する必要があるため、「きれいなページ」ではなく「改善できる営業導線」として設計する。

| 順序 | セクション | 目的 | 必須ID |
|---:|---|---|---|
| 1 | Hero | 誰向けに何を解決するか、主CTAを即提示 | `section_id`, `primary_cta_id` |
| 2 | Problem | 現状課題を言語化 | `section_id` |
| 3 | Consequence | 放置リスク/機会損失を示す | `section_id` |
| 4 | Solution | 製品/サービスの解決策 | `section_id` |
| 5 | Feature | 機能説明 | `section_id`, `feature_id` |
| 6 | Benefit | 導入後の成果 | `section_id` |
| 7 | Use Case | 業種/役割別ユースケース | `section_id` |
| 8 | Proof | 導入事例、実績、ロゴ、数値 | `section_id`, `proof_id` |
| 9 | Comparison | 代替手段/競合/現状との比較 | `section_id` |
| 10 | Pricing | 料金、プラン、見積導線 | `section_id`, `pricing_id` |
| 11 | FAQ | 不安解消 | `section_id`, `faq_id` |
| 12 | Final CTA | 資料DL/問い合わせ/無料相談 | `section_id`, `cta_id` |

### 個人アフィリエイトLP

| 順序 | セクション | 目的 |
|---:|---|---|
| 1 | Lead Hero | 検索意図に対する結論、PR表記 |
| 2 | Best Pick | 迷う人向けの一番手 |
| 3 | Ranking | 複数商品の比較導線 |
| 4 | Comparison Table | 価格/機能/向き不向き |
| 5 | Review Detail | 各商品の詳細レビュー |
| 6 | Pros Cons | メリット/デメリット |
| 7 | How To Choose | 選び方 |
| 8 | FAQ | 購入前不安解消 |
| 9 | Affiliate CTA | ASPクリック |

## HPの標準構成

### 法人HP

| 順序 | セクション | 目的 |
|---:|---|---|
| 1 | Brand Hero | 企業/製品の第一印象と主導線 |
| 2 | Gateway Grid | サービス/製品/資料/事例への入口 |
| 3 | Product Overview | 主要製品・サービス概要 |
| 4 | Case Studies | 実績と導入事例 |
| 5 | Resources | ホワイトペーパー、記事、ニュース |
| 6 | Trust | 会社情報、認証、ロゴ、受賞歴 |
| 7 | Final CTA | 問い合わせ/資料DL/相談 |

### 個人/メディアHP

| 順序 | セクション | 目的 |
|---:|---|---|
| 1 | Media Hero | サイトの専門性と読者メリット |
| 2 | Pickup/Rich Menu | 収益カテゴリ・重要記事へ誘導 |
| 3 | Ranking/Best Articles | 収益性の高い記事を露出 |
| 4 | Category Gateway | カテゴリ回遊 |
| 5 | Latest Posts | 新着/更新記事 |
| 6 | Author Proof | 運営者/専門性/実績 |
| 7 | Newsletter/CTA | 継続接点または商材導線 |

## JSON操作契約案

```json
{
  "pageBlueprint": {
    "pageType": "corporate_lp",
    "goal": "lead_generation",
    "offerId": "offer_demo_001",
    "primaryCtaId": "cta_consultation_001",
    "sections": [
      {
        "sectionId": "hero_001",
        "type": "hero",
        "variant": "product_focus",
        "headline": "AIで運用できるLP基盤",
        "ctaIds": ["cta_consultation_001"],
        "measurement": {
          "trackImpression": true,
          "trackScrollDepth": true
        }
      },
      {
        "sectionId": "proof_001",
        "type": "proof",
        "variant": "case_metrics",
        "proofIds": ["case_001", "metric_001"]
      }
    ]
  }
}
```

### 必須スキーマ

| スキーマ | 役割 |
|---|---|
| `page-blueprint.schema.json` | LP/HP全体のページ構造 |
| `section-registry.schema.json` | セクション種別、variant、必須フィールド |
| `lp-blueprint.schema.json` | LP専用のCV導線、offer、CTA、FAQ、pricing |
| `home-blueprint.schema.json` | HP専用のHero、gateway、pickup、post list |
| `conversion-path.schema.json` | CTA、フォーム、資料DL、ASPリンクの経路 |
| `visual-composition.schema.json` | グリッド、余白、視線誘導、背景、密度 |
| `copy-intent.schema.json` | 見出し、訴求、反論処理、CTA文言 |
| `proof.schema.json` | 導入事例、実績数値、レビュー、ロゴ |

### Agent操作

| Action | 目的 |
|---|---|
| `page.createBlueprint` | LP/HPの構成を新規生成 |
| `page.updateSection` | 指定sectionだけ差分更新 |
| `page.reorderSections` | セクション順序を変更 |
| `page.generateVariant` | A/Bテスト用のHero/CTA/Proof variantを生成 |
| `page.auditConversionPath` | CTA欠落、導線過多、計測ID不足を検査 |
| `page.applyPreset` | 業種別/目的別スターターを適用 |

## ビジュアル設計方針

AGENT NEOは汎用テーマに見せない。個人版と法人版で視覚言語を変える。

| パッケージ | 方向性 | 避けること |
|---|---|---|
| Core | 余白、グリッド、トークン、アクセシビリティを安定化 | 色や装飾を固定しすぎる |
| 個人版 | 収益記事に強い、比較しやすい、信頼できるメディア感 | 安っぽいランキング、過剰装飾、PR表記の隠蔽 |
| 法人版 | B2Bらしい信頼、営業資料化できる整理、強いCTA | 無難なSaaS風、薄い抽象イラスト、CTA過多 |

### 法人版の推奨ビジュアル

- Heroは1画面で「対象者」「成果」「証拠」「CTA」を見せる。
- 背景は単色ではなく、製品カテゴリに応じたグラデーション/図形/静かなパターンを使う。
- 事例/数値/ロゴをHero直下または中盤に置き、価格前の不安を下げる。
- CTAは2種類まで。例: `資料DL` と `無料相談`。
- フォームは初期表示で重くしない。外部フォームはクリック後/遅延表示にする。

### 個人版の推奨ビジュアル

- 比較表、ランキング、レビューカードを読みやすくする。
- 収益導線は目立たせるが、PR表記と公平性を隠さない。
- カテゴリHPはリッチメニュー型で「何を読めばいいか」を最初に提示する。
- 著者/検証方法/更新日を見える位置に置く。

## 計測設計

LP/HPはデザインだけでなく、改善単位を埋め込む。

| ID | 対象 | イベント |
|---|---|---|
| `section_id` | 全セクション | impression、scroll_depth、view_time |
| `cta_id` | CTA | impression、click |
| `variant_id` | A/B対象 | exposure、conversion |
| `offer_id` | LPオファー | lead、download、inquiry |
| `gateway_id` | HP導線 | click、next_page |
| `proof_id` | 事例/実績 | impression、expand、click |

Automation SEOへ渡す改善材料は、ページ単位ではなくセクション単位にする。これにより「Heroは見られているがPricingで落ちる」「FAQ後のCTAが弱い」のような改善指示が可能になる。

## 採用/改良/不採用

| 参照元 | 採用 | 改良して採用 | 不採用 |
|---|---|---|---|
| ThemeB | LP CPT、LP個別設定、再利用パーツ、ページパターン | LPメタ設定をJSON契約化、Reusable Section化 | PHP/HTML上書き前提の自由実装をそのまま採用 |
| テーマA | Hero variant、rich menu、デモプリセット、トップページカラム | FSE pattern + JSON blueprint + section_id化 | 巨大CSS、直書きHTML、外部画像依存、クラシックテンプレート固定 |

## Gate

| Gate | 判定 | 根拠 |
|---|---|---|
| RG0 | passed | ThemeB/テーマAのLP/HP関連コードと設定を確認 |
| RG1 | passed | LP、HP、収益ページの観測契約を抽出 |
| RG2 | passed | LPとHPを別ブループリントに分離する設計を定義 |
| RG3 | passed | 法人/個人パッケージごとの標準構成と計測IDを定義 |
| R4 | passed | L1/L2/package matrix/summaryへ反映対象を決定 |
