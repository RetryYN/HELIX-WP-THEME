# SEOスキルマップ観点テーマ調査

## 結論

`SEO skill/references/seo-skill-map.md` の10カテゴリでSWELL/JIN:Rを見直すと、既存レポートの「JIN:RはSEO統合UX、SWELLは構造化データ/速度/収益化/計測が強い」という判断は維持できる。

ただし、SEO業務全体のスキルマップで見ると、両テーマとも **オンページSEOとテクニカルSEOに寄っており、戦略、キーワードクラスタ、Search Console分析、国際SEO、オフページ、AI SEO/LLMO、成長実験はテーマ契約として未成熟** である。AGENT NEOはここを「テーマ機能」ではなく、Automation SEOとCore Pluginが扱うSEO運用基盤として製品化すべきである。

## 調査対象

| 対象 | 状態 | 主な確認ファイル |
|---|---|---|
| `SEO skill/references/seo-skill-map.md` | 10カテゴリ/SEO業務観点の入力 | `SEO skill/references/seo-skill-map.md` |
| SWELL | 実コード解析 | `classes/Json_Ld.php`, `lib/gutenberg/block/review.php`, `lib/gutenberg/render_hook/faq.php`, `lib/menu/settings/speed.php`, `lib/post_meta/meta_button.php`, `lib/hooks/admin_display.php`, `lib/hooks/remove.php` |
| JIN:R | 実コード解析 | `header.php`, `include/head/*`, `include/json-ld.php`, `include/custom-functions.php`, `include/jinr-setting.php`, `functions.php` |

## SEOスキルマップ別評価

| カテゴリ | SWELL | JIN:R | 観測結果 | AGENT NEOでの設計方針 |
|---|---:|---:|---|---|
| 1. 戦略 | 35 | 40 | キーワードリサーチ、検索意図、競合分析、クラスタリングはテーマ本体には存在しない。JIN:Rはカテゴリ作り込み導線があり、IAに少し寄与する | Automation SEO側で `keyword_cluster`, `search_intent`, `competitor_gap` を持ち、テーマは受け皿に徹する |
| 2. オンページSEO | 65 | 82 | JIN:Rはtitle/description/canonical/noindex/OGPをテーマ内で管理し、post metaをREST公開する。SWELLはFAQ/Review/内部リンク部品が強いがSEOメタは外部プラグイン寄り | JIN:R型SEO UX + SWELL型ブロック/構造化データを統合する |
| 3. テクニカルSEO | 84 | 68 | SWELLは`@graph` JSON-LD、FAQ/Product/Review schema、速度設定、不要WP出力抑制が強い。JIN:Rはnoindex/canonicalは強いが、JSON-LDが分割scriptで、jQuery依存も強い | `indexability_policy`, `entity_graph`, `performance_budget`, `crawlability_profile` を契約化する |
| 4. コンテンツSEO | 78 | 70 | SWELLは投稿リスト、関連記事、FAQ、レビュー、AB、広告タグ、PV表示が強い。JIN:Rはプロフィール、関連記事、カテゴリ固定ページ化、デモ/プリセットUXが強い | 記事/LP/BLPを `content_blueprint` として扱い、topic clusterと内部リンクを機械可読にする |
| 5. オフページSEO | 30 | 32 | SNS/sameAs/profile/shareはあるが、リンク獲得、PR、ブランド言及、アウトリーチ管理はテーマ領域外 | Themeには `brand_entity` と `citation_source` だけ持たせ、PR/被リンクは外部運用・Automation SEOへ逃がす |
| 6. 国際SEO | 45 | 25 | SWELLは翻訳ファイル/i18n配慮が見える。JIN:Rは`html lang="ja"`固定で多言語/hreflang設計は弱い | 初版日本語/英語でも `hreflang_map`, `locale_content_policy`, `canonical_locale_pair` を先に定義する |
| 7. アナリティクス | 75 | 45 | SWELLはPV、広告CTR、ボタン計測、ABブロックがある。JIN:RはAnalytics/Search Console/AdSenseタグをraw出力できるがKPI統合は弱い | GA4/GSC/Automation SEO/ローカル計測を `seo_kpi_profile` へ集約する |
| 8. UX | 82 | 72 | SWELLは速度、TOC、条件付きasset、ブロックUXが強い。JIN:Rはプリセット、メインビジュアル、著者/信頼感演出が強い | CWVとCVを同じ画面で扱い、LP/記事ごとにUX riskを出す |
| 9. AI SEO | 55 | 58 | 両方ともschemaや著者情報はあるが、answer unit、evidence graph、AI crawler policy、citation anchorはない。JIN:RはSEO meta REST公開がAI操作の土台になる | `llmo_profile`, `answer_unit`, `evidence_graph`, `content_origin`, `ai_visibility_policy` をP0にする |
| 10. 成長 | 65 | 60 | SWELLはAB/CTR/PV/人気順で改善サイクルに近い。JIN:Rはデモ/プリセットによる初速が強い。どちらもSEO実験管理やコンテンツ刷新ワークフローはない | `seo_experiment`, `content_refresh_queue`, `topic_authority_score` をCore Plugin/Automation SEOで扱う |

## 主要な発見

### JIN:Rの良い点

| 観点 | 証拠 | 評価 |
|---|---|---|
| SEO head統合 | `header.php` がOGP、description、noindex、keywords、canonical、任意タグを `wp_head()` 前に読み込む | 人間UXとしては分かりやすく、AI操作対象も見つけやすい |
| REST操作性 | `_jinr_seotitle_display`, `_jinr_description_display`, `_jinr_canonical_display`, `_jinr_noindex_display` が `show_in_rest: true` | SEOメタを外部から操作する土台がある |
| index制御 | `include/head/noindex.php` が投稿/固定/カテゴリ/タグ/検索/著者/添付/404を制御 | WPの薄い/重複ページ対策として実用的 |
| 著者/Entity | `include/json-ld.php` がPerson/Organization/SameAsを出す | E-E-A-T/AI SEOの基礎情報として参考になる |

### JIN:Rの弱い点

| 観点 | 証拠 | リスク |
|---|---|---|
| rawタグ出力 | `include/head/tags.php` がAnalytics/Search Console/headタグをraw echo | セキュリティ、重複、外部送信同意、監査ログが弱い |
| canonicalの安全性 | `include/head/others.php` が独自canonicalを直接出力 | SEOプラグインやWP標準canonicalとの重複検知が別途必要 |
| JSON-LD品質 | `include/json-ld.php` は`json_encode`で分割script出力 | `@graph`統合、schema validation、Product/Review/FAQ拡張が不足 |
| 速度/保守性 | `functions.php` がフロントjQueryを常時寄せ、複数JSがjQuery依存 | Core Web Vitals/AIクローラ向け静的性ではSWELLに劣る |
| 国際SEO | `html lang="ja"`固定 | hreflang/多言語展開の拡張余地が弱い |

### SWELLの良い点

| 観点 | 証拠 | 評価 |
|---|---|---|
| Entity Graph | `classes/Json_Ld.php` がOrganization/WebSite/WebPage/Article/BreadcrumbListを `@graph` で生成 | AGENT NEOのEntity Graph Builderの基礎にしやすい |
| Product/Review | `lib/gutenberg/block/review.php` がProduct/Offer/Review JSON-LDを出す | 個人アフィリエイト版の中核部品として参考価値が高い |
| FAQ visible sync | `lib/gutenberg/render_hook/faq.php` が表示FAQからFAQPageを生成 | 表示内容とschemaを同期する設計として良い |
| 速度設計 | `lib/menu/settings/speed.php` がCSS分割、lazyload、delay JS、prefetch/pjaxを管理 | テクニカルSEO/CWVの設計参考として強い |
| 計測 | `meta_button.php`, `admin_display.php`, `check_code.php` がボタン/広告/PV/CTRを扱う | SEO流入後のCV改善まで繋げやすい |

### SWELLの弱い点

| 観点 | 証拠 | リスク |
|---|---|---|
| SEOメタ統合 | title/description/canonical/noindexはSEOプラグイン寄り | AIがSEOを一括操作する正規契約が不安定 |
| 戦略/分析 | キーワード、検索意図、GSC、競合ギャップはテーマ外 | SEOスキルマップ全体を満たすには外部基盤が必要 |
| ABテスト | `ab-test.php` は表示分岐中心 | SEO/CVの実験仮説、勝敗判定、KPI帰属までは持たない |
| sitemap/robots | コアサイトマップ停止やrobots画像制御はあるが統合ポリシーではない | canonical/noindex/sitemap/robotsを同時に評価するrisk-ledgerが必要 |

## AGENT NEOへ取り込むべき設計

### 1. SEO Skill Map Contract

SEOを単なるメタタグ管理ではなく、以下の機械可読契約に分割する。

| 契約 | 役割 |
|---|---|
| `seo-skill-map.profile.json` | 戦略/オンページ/テクニカル/コンテンツ/分析/AI SEOなどの有効範囲と担当面を定義 |
| `keyword-cluster.schema.json` | keyword、intent、difficulty、target_page、pillar/cluster関係 |
| `search-intent.schema.json` | Informational/Commercial/Transactionalなどの検索意図 |
| `content-blueprint.schema.json` | 記事/LP/BLPの見出し、内部リンク、CTA、schema、更新周期 |
| `indexability-policy.schema.json` | canonical/noindex/robots/sitemap/hreflangの統合ポリシー |
| `internal-link-graph.schema.json` | ピラー、クラスタ、関連記事、サービス導線のリンクグラフ |
| `seo-kpi-profile.schema.json` | GSC/GA4/ローカル計測/Automation SEOのKPI統合 |
| `seo-experiment.schema.json` | 仮説、variant、KPI、勝敗、rollback条件 |
| `llmo-answer-unit.schema.json` | AI回答向けのQ/A、根拠、更新日、引用anchor、CTA |
| `evidence-graph.schema.json` | 主張、根拠、出典、監修者、検証日、Entityの紐付け |

### 2. API/画面候補

| API | 役割 | 画面 |
|---|---|---|
| `GET /wp-json/agent-neo/v1/seo/skill-map` | SEOカテゴリ別の対応状況、設定、リスクを取得 | SEO Core |
| `POST /wp-json/agent-neo/v1/seo/audit/page` | 対象ページのオンページ/テクニカル/AI SEO監査 | SEO Audit |
| `POST /wp-json/agent-neo/v1/seo/content-plan` | keyword clusterから記事/LP/BLP計画を生成 | Content Plan |
| `GET /wp-json/agent-neo/v1/seo/internal-link-graph` | 内部リンク、ピラー、関連サービス導線を取得 | Internal Links |
| `GET /wp-json/agent-neo/v1/seo/kpi` | GSC/GA4/CTR/CV/LLMOイベントの統合KPIを取得 | SEO KPI |
| `POST /wp-json/agent-neo/v1/seo/experiments` | title/description/CTA/LP構成のSEO実験を作成 | SEO Experiments |

## パッケージ別の意味

| パッケージ | `SEO skill/references/seo-skill-map.md` で強化すべきカテゴリ | 理由 |
|---|---|---|
| 個人版 | オンページ、テクニカル、コンテンツ、アナリティクス、成長 | アフィリエイトは記事投入、比較/レビュー、内部リンク、CTR、リライトで勝つ |
| 法人版 | 戦略、テクニカル、コンテンツ、UX、アナリティクス、AI SEO、成長 | 法人はサービス別IA、LP/BLP、リード計測、LLMO、証拠/信頼、改善サイクルが価値 |
| Automation SEO | 戦略、競合分析、キーワードクラスタ、コンテンツ計画、AI SEO、成長実験 | テーマ単体で完結させると重すぎる。AI原価もここで回収する |
| Theme Bridge Plugin | オンページ、テクニカル、分析の診断/抽出 | 既存テーマでは書き換えより診断と移行blueprint生成が現実的 |

## 不都合な真実

| 項目 | 内容 | 対策 |
|---|---|---|
| テーマだけではSEO戦略は作れない | キーワード、競合、検索意図、被リンク、GSC実績はテーマ外データ | Automation SEO連携を前提にする |
| SEOメタ統合は移行性と衝突する | JIN:R型はAI操作しやすいが、SEOプラグイン併用時に重複しやすい | `seo_conflict_rules` とexport/import必須 |
| 速度機能はSEOを壊すことがある | lazyload/delay JS/pjax/prefetchは重要コンテンツや計測を隠す可能性 | `crawlability_profile` と公開snapshotで検証 |
| FAQ/Review schemaは乱用できない | schemaは表示内容、品質、ポリシーに依存し、表示保証ではない | visible content syncとclaim risk監査 |
| Rawタグ挿入は売れるが危険 | Analytics/GSC/AdSense/head任意タグは便利だがXSS/重複/同意漏れを生む | adapter/allowlist/consent/audit log化 |
| AI SEOはメタタグでは足りない | AI検索では根拠、引用anchor、更新日、著者、出典、回答単位が重要 | answer unit/evidence graph/content originを持つ |

## 優先アクション

1. `F-011 SEO Core` を「SEOメタ管理」だけでなく、`SEO skill/references/seo-skill-map.md` のカテゴリ別能力表へ拡張する。
2. L3詳細設計で `keyword-cluster`, `content-blueprint`, `indexability-policy`, `internal-link-graph`, `seo-kpi-profile`, `seo-experiment`, `llmo-answer-unit` のschemaを切る。
3. 法人版は `service_id` と `keyword_cluster` を接続し、HP/LP/BLPが検索意図とサービス導線を共有する設計にする。
4. 個人版は `review/product/comparison/internal-link/refresh` を中心にし、構造変更ではなく記事改善と出口クリック最適化へ寄せる。
5. Theme Bridge PluginはSWELL/JIN:RからSEOメタ、schema、CTA、計測、内部リンクをsource/confidence付きで抽出し、AGENT NEO移行blueprintへ変換する。

## Gate判定

| Gate | 判定 | 根拠 |
|---|---|---|
| RG0 | passed | `SEO skill/references/seo-skill-map.md`、SWELL/JIN:R主要SEOファイル、既存SEOレポートを確認 |
| RG1 | passed_with_gaps | オンページ/テクニカル/計測は高信頼で抽出。戦略/国際/オフページ/AI SEOはテーマ実装上の欠落として記録 |
| RG2 | passed | JIN:R統合SEO UX + SWELL構造化データ/速度/計測 + Automation SEO戦略層という設計へ整理 |
| R4 | passed | AGENT NEOの追加契約、API候補、パッケージ別優先度へ接続 |
