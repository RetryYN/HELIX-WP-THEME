# L1 要件定義書 — AGENT NEO

## 1. プロジェクト概要

### 1.1 目的・背景

AGENT NEO は、AIエージェントが第一級ユーザーとして操作できる商用 WordPress FSE テーマである。既存の有料テーマは人間GUI前提で、AIが安全にサイト構造、ブロック、CTA、計測、SEOを更新するための安定したJSON契約が弱い。AGENT NEO は WordPress エコシステムを維持しつつ、REST API、MCP、WP CLI、React管理画面を通じてAI運用可能なテーマ基盤を提供する。

### 1.2 ターゲットユーザー

| ティア | ペルソナ | 主な利用シナリオ |
|---|---|---|
| 個人版 | 中級以上のアフィリエイター（Amazon/メルカリ/AdSense/ASP系） | **出口クリック最適化**: 高 CTR 記事から ASP/AdSense へ送客し、クリック収益を最大化 |
| 法人版 | 中小企業、スタートアップ、代理店 | **全ファネル所有**: HP/LP/BLP 導線で問い合わせ・資料 DL を獲得 → 顧客行動・育成・LTV まで自社管理 |
| 移行プラグイン | 既存WPサイト所有者 | 既存コンテンツをAGENT NEOの標準構造へ移す |
| S1 | 法人、本気の事業者 | Automation SEOでIA/SEO/LPを再設計し、検収付きで公開する |

### 1.3 成功指標

| 指標 | 目標値 | 測定方法 |
|---|---:|---|
| 個人版初期CVR | 公式LP訪問から購入 `2%` 以上 | 販売サイト計測 |
| 法人版商談化率 | 資料DL/問い合わせから商談 `10%` 以上 | CTA/CRM連携 |
| JSON操作成功率 | dryRun成功後の本実行成功 `95%` 以上 | 操作ログ |
| Core Web Vitals | LCP `2.5s` 以下、INP `200ms` 以下、CLS `0.1` 以下 | Lighthouse/実測 |
| 計測イベント欠損率 | `1%` 未満 | seo-tool-connector/Automation SEO |

### 1.4 市場ポジション

AGENT NEOは、SWELL/JIN:R/AFFINGER/Cocoon/Lightningと同じ「汎用WPテーマ」カテゴリで価格・デザイン・SEOだけを競わない。狙う市場カテゴリは **AI運用型WPテーマ基盤** とし、個人版はアフィリエイト収益化とAI記事投入/計測、法人版はLP改善/A-B/CTA計測/LLMO/運用品質を中核価値にする。

## 2. 機能要件 (D-REQ-F)

| ID | 要件名 | 説明 | 優先度 | 受入条件ID |
|---|---|---|---|---|
| REQ-F-001 | FSEテーマ基盤 | WordPress 6.6+ / PHP 8.1+ / theme.json / block.json中心の自作テーマを提供する | P0 | ACC-001 |
| REQ-F-002 | JSON操作API | テーマ設定、ブロック、レイアウト、CTAをREST JSONで操作できる | P0 | ACC-002 |
| REQ-F-003 | 4操作面 | REST API、MCP、WP CLI、React管理画面を提供する | P0 | ACC-003 |
| REQ-F-004 | 個人版収益化ブロック | **出口クリック最適化を核心とする**。Amazon アソシエイト/メルカリ/Google AdSense/もしも/a8.net 等主要 ASP に対応する Review、Ranking、Comparison、Pros Cons、Ad Tag、Affiliate CTA、商品カード（Amazon Product API 連携）、AdSense 配置最適化、PR 表記自動付与（景表法対応）、ASP 別 CTR/収益サマリを提供する | P0 | ACC-004 |
| REQ-F-005 | 法人HP/LP/BLP三位一体 | **法人版限定**。自社サービスへの導線強化のため、HP（ブランド入口・Gateway）、LP（1オファー成約・標準12セクション: Hero→Problem→Consequence→Solution→Feature→Benefit→Use Case→Proof→Comparison→Pricing→FAQ→CTA）、BLP（記事LP=記事下CTA/関連サービス導線/インラインCTA/Trust要素/問い合わせ導線）を統合した営業導線基盤を提供する。個人版はこの機能を持たず、固定テンプレート構成（HP/sidebar/archive/single）のみ提供 | P0 | ACC-005 |
| REQ-F-006 | 計測/A-B/CTA | section_id、cta_id、variant_idを持つ計測とA/Bテスト基盤を提供する | P0 | ACC-006 |
| REQ-F-007 | Automation SEO連携 | seo-tool-connector APIと整合し、記事/LP/計測データを同期できる | P0 | ACC-007 |
| REQ-F-008 | 移行プラグイン | 既存WPサイトから投稿/メディア/分類/メニューを抽出し、AGENT NEO構造へ変換する | P1 | ACC-008 |
| REQ-F-009 | 設定エクスポート/インポート | design tokens、layout、package settingsをJSONで入出力できる | P1 | ACC-009 |
| REQ-F-010 | ライセンス/パッケージ制御 | 個人版/法人版/アドオンの機能境界を制御できる。AI 操作スコープも個人版（記事 CRUD のみ）/ 法人版（記事 + 構造変更）で agent-api の操作許可リストを切り替える | P1 | ACC-010 |
| REQ-F-016 | 個人版テンプレ固定構成 | **個人版限定**。HP / sidebar / archive / single の標準テンプレ 1 セットを提供し、ユーザー（および AI）はテンプレ構造を変更できない（記事・カテゴリ・タグ・メディアのみ操作可）。個人ブログ運用にフォーカスし、保守コスト・テスト範囲を最小化する | P0 | ACC-016 |
| REQ-F-011 | SEO Core | title、description、robots、canonical、OGP、構造化データをテーマ標準UI/APIで管理できる | P0 | ACC-011 |
| REQ-F-012 | LP/HP/BLPブループリント | **法人版限定**。LP・HP・BLPを別JSON契約で生成・更新し、必須section_id/cta_id/offer_id/service_idを持つブループリントを管理できる。複数サービスを持つ企業向けにservice-aware IA（service_idでコンテンツとサービスを紐付け）に対応する | P0 | ACC-012 |
| REQ-F-013 | 法人版リード獲得 | **法人版限定**。問い合わせフォーム / 資料 DL / 無料相談予約 / Webinar 登録 / メルマガ登録ブロックを提供し、reCAPTCHA・honeypot・レート制限による spam 対策、自動返信メール、submission の管理画面一覧 + CSV エクスポートを備える | P0 | ACC-013 |
| REQ-F-014 | 法人版顧客行動管理 | **法人版限定**。セッション単位ジャーニー追跡、ファネル分析、リードスコアリング、顧客健康度（health score）を提供。admin-dashboard でジャーニー可視化・リード一覧・スコアランキングを表示する | P0 | ACC-014 |
| REQ-F-015 | CRM/MA 連携アドオン | **法人版限定（A1 系・別課金候補）**。HubSpot / Salesforce / kintone / Zoho / Pipedrive 等の CRM、SendGrid / Mailchimp 等のメール基盤に対する Webhook + REST 連携、Zapier / Make 経由のアダプタブロックを提供する | P1 | ACC-015 |

### 2.1 ユースケース

| ID | ユースケース | 主アクター | 成功条件 |
|---|---|---|---|
| UC-001 | AIが記事を生成して標準ブロックで投稿する | Automation SEO | article_id付きで公開前レビューできる |
| UC-002 | AIが法人LPのCTA文言をA/Bテストする | 法人管理者/AI | variant_id単位で露出とクリックを計測できる |
| UC-003 | アフィリエイターが比較表を作る | 個人ユーザー | 商品カード、評価、CTA、PR表記が出力される |
| UC-004 | 既存WPから移行する | サイト所有者 | 変換プレビュー後に投入できる |
| UC-005 | 管理者がブランドトークンを更新する | 法人管理者 | JSON差分レビュー後にCSS変数へ反映される |

### 2.2 要件間の依存関係

| 依存元 | 依存先 | 理由 |
|---|---|---|
| REQ-F-002 | REQ-F-001 | JSON操作はFSEテーマ基盤の上で動く |
| REQ-F-003 | REQ-F-002 | MCP/WP CLI/管理画面は同じJSON契約を使う |
| REQ-F-006 | REQ-F-007 | Automation SEO連携はsection/CTA計測IDに依存する |
| REQ-F-008 | REQ-F-002 | 移行後投入はJSON操作APIに依存する |
| REQ-F-010 | REQ-F-004, REQ-F-005 | 個人/法人の機能境界を制御する |
| REQ-F-011 | REQ-F-002, REQ-F-007 | SEO操作はJSON契約とAutomation SEO連携に依存する |
| REQ-F-012 | REQ-F-002, REQ-F-006, REQ-F-011 | LP/HP設計はJSON操作、計測ID、SEOメタに依存する |
| REQ-F-002/REQ-F-003/REQ-F-006/REQ-F-007 | REQ-NF-014 | AI操作、計測、Automation SEO連携、自動化jobは契約ファーストで安全性を担保する |
| REQ-F-002/REQ-F-011/REQ-F-012 | REQ-NF-015 | AIエージェント運用とAIクローラ向け公開状態は、安定DOM、snapshot、crawler policyで担保する |
| REQ-F-001/REQ-F-002/REQ-F-011/REQ-F-012 | REQ-NF-016 | 商用テーマとしての配布、アクセシビリティ、国際化、検索公開、Privacy、Release、Support品質を担保する |
| REQ-F-006/REQ-F-007/REQ-F-011/REQ-F-012 | REQ-NF-017 | LLMO時代のAI検索可視性、引用、根拠、AI経由CV計測を担保する |
| REQ-F-001/REQ-F-002/REQ-F-006/REQ-F-007/REQ-F-011/REQ-F-012 | REQ-NF-018 | SEO、WP運用、セキュリティ、AI運用で静かに発生する障害をrisk-ledgerとして検出、警告、復旧できる |
| REQ-F-006/REQ-F-007/REQ-F-011/REQ-F-012 | REQ-NF-019 | Automation SEO連携ではSWELL/JIN:Rを移行・診断対象に限定し、AGENT NEOの安定section/CTA/SEO契約を正規運用ターゲットにする |
| REQ-F-007/REQ-F-008/REQ-F-011/REQ-F-012 | REQ-NF-020 | Automation SEO Theme Bridge Pluginは既存テーマを診断・正規化・移行入口に限定し、AGENT NEO Core Pluginをsafe applyの正規書き込み先にする |

## 3. 非機能要件 (D-REQ-NF)

| ID | 要件名 | 内容 | 優先度 |
|---|---|---|---|
| REQ-NF-001 | 性能 | 条件付きアセット、ブロックCSS分割、不要JS抑制、LCP画像制御、Web Vitals実測を行う | P0 |
| REQ-NF-002 | セキュリティ | 書き込みAPIはnonce/capability/rate limit/schema validationを必須にする | P0 |
| REQ-NF-003 | ライセンス | テーマ本体/プラグインはGPL互換。参照テーマのコード/画像/CSS/固有文言はコピーしない | P0 |
| REQ-NF-004 | データ保護 | 計測データは必要最小限とし、個人情報を直接収集しない設計を基本にする | P0 |
| REQ-NF-005 | アクセシビリティ | WCAG 2.2 AAを目標にする | P1 |
| REQ-NF-006 | 国際化 | 初版は日本語/英語対応 | P1 |
| REQ-NF-007 | 可観測性 | JSON操作、計測イベント、同期失敗をログ化する | P1 |
| REQ-NF-008 | 配布/機能境界 | Theme CoreはFSE表示層に限定し、CPT、SEO保存、計測、A/B、JSON操作APIはCompanion Pluginへ分離する | P0 |
| REQ-NF-009 | 法令/表示ガード | アフィリエイトPR表記、外部送信同意、第三者依存ライセンス、SEO保証表現禁止を製品仕様に組み込む | P0 |
| REQ-NF-010 | プラグイン依存度管理 | 必須依存をAGENT NEO Theme + Core Pluginに限定し、SEO/フォーム/キャッシュ/計測系の外部プラグインは任意adapterとして扱う | P0 |
| REQ-NF-011 | テーマコーディング規約 | WordPress Coding Standards、薄いbootstrap、block.json正本、context escape、schema sanitize、used block assetを必須規約にする | P0 |
| REQ-NF-012 | デザイン/UI思想 | SWELL/JIN:Rから逆引きした情報設計/プリセットUXをコピーせず契約化し、CTA過多・証拠不足・PR不足をUI Auditで検出する | P0 |
| REQ-NF-013 | 運用品質 | WP/PHP互換、更新前後チェック、rollback、plugin衝突検出、可用性fallback、SLO/health checkを契約化する | P0 |
| REQ-NF-014 | API/自動化契約 | REST/MCP/WP CLI/ジョブ/イベント/Cron/WebhookをOpenAPI/JSON Schemaで契約化し、破壊的変更検出、idempotency、retry/DLQ、SSRF/rate limitを必須化する | P0 |
| REQ-NF-015 | AI運用性/クローラビリティ | AIエージェントが安全に触れるための安定DOM anchor、公開content snapshot、crawler access matrix、AI crawler log、SEO risk diffを契約化する | P0 |
| REQ-NF-016 | テーマ品質/配布準備 | Theme Review、アクセシビリティ実査、i18n/RTL、Release/SBOM、ホスティング互換、Privacy retention、SEO indexing、Support/Documentationを品質ゲート化する | P0 |
| REQ-NF-017 | LLMO/AI検索最適化 | answer unit、evidence graph、content origin、AI visibility policy、citation anchor、LLMO計測、claim riskを契約化する | P0 |
| REQ-NF-018 | SEO/WP運用ハザード管理 | canonical/noindex/robots/sitemap、Core Web Vitals、WP-Cron、cache、plugin conflict、update/rollback、privacy/log、AI snapshotの危険変更をrisk-ledgerで契約化する | P0 |
| REQ-NF-019 | Automation SEO連携適合性 | Theme Capability Scanner、Section ID Resolver、Context Contract v2、SEO Meta Normalizer、CTA/Offer Mapper、Safe Recommendation Applyを契約化し、Automation SEO側とWPテーマ側の責務を分離する | P0 |
| REQ-NF-020 | Automation SEO Theme Bridge Plugin情報設計 | 既存テーマ横断でsite/theme/plugin/page/section/CTA/offer/SEO/tracking/privacy/health/safe apply/migration blueprintを保持し、既存テーマではpreview/診断中心、AGENT NEOではsafe apply対象として扱う | P0 |

## 4. 受入条件 (D-ACC)

| ID | 対応要件 | テスト条件 | 期待結果 | 測定方法 |
|---|---|---|---|---|
| ACC-001 | REQ-F-001 | WP 6.6+ / PHP 8.1+ 環境でテーマ有効化 | fatal errorなく有効化される | WP環境テスト |
| ACC-002 | REQ-F-002 | dryRun付きJSON操作を実行 | schema validation、差分、対象パスが返る | RESTテスト |
| ACC-003 | REQ-F-003 | REST/MCP/WP CLI/管理画面から同一設定を取得 | 同じ設定値が返る | 統合テスト |
| ACC-004 | REQ-F-004 | 比較表/ランキング/CTA/商品カード/AdSense ブロックを作成 | PR 表記が自動付与され、Amazon Product API 経由の商品情報・AdSense 配置・ASP 別 CTR が計測される | ブロックテスト |
| ACC-005 | REQ-F-005 | LP/HP/BLPブループリントをJSONから生成 | LPは標準12セクションが必須順序で出力、HPはHero variant + Gateway、BLPは記事下CTA・インラインCTA・関連サービス導線・問い合わせ導線が出力される | E2E |
| ACC-005a | REQ-F-005 | BLP（記事LP）を有効化した記事を表示 | 記事内インラインCTA + 記事下CTA + 関連サービス導線がcta_id/service_id経由で計測される | 計測テスト |
| ACC-005b | REQ-F-005, REQ-F-012 | 複数サービス（service_id 3件以上）を持つ企業のIAを生成 | service_idでコンテンツがフィルタ・紐付けされ、HP Gatewayから各サービスへ正しく送客される | E2E |
| ACC-006 | REQ-F-006 | CTAを2variantで表示 | impression/clickがvariant別に記録される | 計測テスト |
| ACC-007 | REQ-F-007 | `/v1/tracking/context` 互換データを送信 | site_id/article_id/section_idが同期される | API統合テスト |
| ACC-008 | REQ-F-008 | 既存WP RESTから投稿を抽出 | 変換プレビューと投入結果が一致する | 移行テスト |
| ACC-009 | REQ-F-009 | 設定をexport/import | 差分なしで再現される | JSON比較 |
| ACC-010 | REQ-F-010 | 個人版環境で法人専用機能にアクセス | 権限/ライセンスエラーになる | 権限テスト |
| ACC-011 | REQ-F-011 | 記事/LPにSEOメタとEntity Graphを設定 | canonical/noindex/OGP/JSON-LDが重複なく出力される | SEO出力テスト |
| ACC-012 | REQ-F-012 | 法人LP/法人HP/個人収益ページのblueprintを生成 | 必須section、cta_id、section_id、SEOメタ、計測設定が揃う | JSON契約/E2E |
| ACC-013 | REQ-F-013 | 問い合わせフォーム/資料 DL ブロックを設置し submit 実行 | reCAPTCHA/honeypot 検証通過後に submission が記録され、自動返信メール送信、管理画面で確認可能、CSV エクスポート可能 | フォームテスト + E2E |
| ACC-014 | REQ-F-014 | セッション単位の訪問ジャーニーを発生させる | HP → LP → form のドロップオフがファネル分析に表示され、リードスコアが付与され、admin dashboard でジャーニー可視化される | 行動追跡テスト |
| ACC-015 | REQ-F-015 | HubSpot or kintone への Webhook 連携を設定 | フォーム submission が外部 CRM に Webhook で送信され、CRM 側で受信される | 統合テスト |
| ACC-016 | REQ-F-016 | 個人版環境で HP / LP ブループリント API を叩く | ライセンス/権限エラーが返り、テンプレ構造変更が拒否される | 権限テスト |
| ACC-017 | REQ-F-005, REQ-F-010, REQ-F-016 | 個人版で記事 CRUD API + 構造変更 API を試行 | 記事 CRUD は成功、構造変更（HP/LP/design-tokens）は権限エラー | API スコープ境界テスト |
| ACC-NF-001 | REQ-NF-001 | 代表テンプレートで速度予算を測定 | LCP `2.5s` 以下、INP `200ms` 以下、CLS `0.1` 以下。初期CSS/JSと第三者タグが予算内 | Lighthouse/CrUX/RUM |
| ACC-NF-002 | REQ-NF-008 | 配布物と機能境界を監査 | Theme Coreに永続データ/CPT/計測保存/SEO保存を持たせず、Companion Plugin側で扱う | compliance review |
| ACC-NF-003 | REQ-NF-009 | PR表記、外部送信、第三者依存、販売文言を監査 | PR表記block、privacy policy template、依存ライセンス一覧、SEO保証禁止ルールが揃う | compliance review |
| ACC-NF-004 | REQ-NF-010 | 主要外部プラグインなしで代表機能を検証 | Yoast/Rank Math/Contact Form/キャッシュ/GA系プラグインなしでも表示、基本SEO、CTA、ローカル軽量計測、JSON操作が成立する | dependency test |
| ACC-NF-005 | REQ-NF-011 | Theme実装規約を検証 | PHPCS/WPCS、Theme Check、block.json schema、未escape出力検出、未使用asset読み込み検出が通る | static analysis |
| ACC-NF-006 | REQ-NF-012 | 代表blueprintをUI Audit | hero.vague、cta.overload、proof.too_late、affiliate.disclosure_weak等が検出される | design audit |
| ACC-NF-007 | REQ-NF-013 | WP更新/プラグイン追加/外部連携障害を検証 | compatibility matrix、update preflight/postflight、plugin conflict、fallback、rollback readinessが判定される | ops/security test |
| ACC-NF-008 | REQ-NF-014 | API/ジョブ契約を検証 | OpenAPI lint/diff、JSON Schema validation、REST/MCP/WP CLI contract tests、Cron retry/DLQ/idempotency testsが通る | contract test |
| ACC-NF-009 | REQ-NF-015 | AI運用性とクローラビリティを検証 | 全主要section/CTAにstable DOM anchorがあり、public snapshot、crawl map、crawler access matrix、SEO risk diff、AI crawler logが検証される | AI operability test |
| ACC-NF-010 | REQ-NF-016 | テーマ品質/配布準備を検証 | Theme Review checklist、a11y audit、i18n/RTL、release/SBOM、hosting compatibility、privacy retention、SEO indexing、support docs、QA matrixが通る | quality gate |
| ACC-NF-011 | REQ-NF-017 | LLMO/AI検索最適化を検証 | 主要ページにanswer unit、evidence graph、content origin、citation anchor、AI visibility policy、AI crawler policy、LLMO visibility events、claim risk判定が揃う | LLMO contract test |
| ACC-NF-012 | REQ-NF-018 | SEO/WP運用ハザードを検証 | canonical/noindex/robots/sitemap/cache/cron/plugin/update/privacy/AI snapshotの危険変更が検出され、risk-ledgerにseverity、検出手段、対策、残リスクが残る | hazard contract test |
| ACC-NF-013 | REQ-NF-019 | Automation SEO連携適合性を検証 | SWELL/JIN:R/AGENT NEOのtheme capability scan、section ID confidence、Context Contract v2、SEO meta normalization、CTA/Offer mapping、safe apply fallbackが検証される | integration contract test |
| ACC-NF-014 | REQ-NF-020 | Theme Bridge Plugin情報設計を検証 | site/theme/plugin/page/section/CTA/offer/SEO/tracking/privacy/health/safe apply/migration blueprintがsource/confidence付きで出力され、既存テーマはpreview-only、AGENT NEOはdryRun/apply/rollback対象として判定される | integration contract test |
| ACC-SEC-001 | REQ-NF-002 | 未認証で書き込みAPIを実行 | 拒否され、監査ログに残る | セキュリティテスト |

### 4.1 異常系・境界値

| ID | 条件 | 期待動作 |
|---|---|---|
| ACC-ERR-001 | 不正JSON | 400 + validation error |
| ACC-ERR-002 | 対象外path | 403 + path allowlist error |
| ACC-ERR-003 | rate limit超過 | 429 |
| ACC-ERR-004 | 存在しないsection_id | 404またはvalidation error |
| ACC-ERR-005 | AI生成HTMLにscript混入 | sanitizeされ保存されない |

## 5. スコープ

### 5.1 対象範囲

- AGENT NEO Coreテーマ
- 個人/アフィリエイター版
- 法人/LP版
- 移行プラグイン
- Automation SEO / seo-tool-connector連携
- SEO Core、OGP、canonical/noindex、Entity Graph
- JSON操作API、MCP、WP CLI、React管理画面

### 5.2 対象外

- 参照テーマのコード、画像、CSS、固有デザインの流用
- WordPress.com向け保証
- 初版での完全なMA/CRM内蔵
- 初版での独自LLM基盤開発

### 5.3 将来対応

- マーケットプレイス
- テーマプリセット販売
- 法人保守サブスク
- CRM/MAネイティブ連携
- 多言語LP自動生成

## 6. 制約・前提条件

| 種別 | 内容 |
|---|---|
| 技術制約 | WordPress 6.6+、PHP 8.1+、FSE、theme.json、block.json |
| 価格制約 | 個人版 `¥19,800`、法人版 `¥98,000`、Automation SEOは別課金 |
| 外部依存 | Automation SEO、seo-tool-connector |
| ライセンス | GPL互換。第三者依存は監査必須 |
| 配布 | 移行プラグインはwp.org申請可能品質 |
| 機能境界 | Theme Coreはtheme.json/templates/parts/patterns/styles、Companion PluginはREST/MCP/WP CLI/CPT/SEO/計測/A-B/LP-HP Blueprint |
| プラグイン依存 | 必須依存はTheme + Core Plugin。外部SEO/フォーム/キャッシュ/GA/GTM系は任意adapter |
| 表示/同意 | アフィリエイトPR表記、外部送信同意、privacy policy templateを必須化 |
| AI操作 | dryRun、diffReview、rollback、schema versioning、data portabilityを必須化 |
| コーディング規約 | `functions.php`はbootstrapのみ。`block.json`を正本にし、WPCS/PHPCS/Theme Checkを必須化 |
| デザイン | 見た目コピー禁止。design preset / visual composition / section pattern / trust layer / UI riskを契約化 |
| 運用 | compatibility matrix、update policy、security baseline、plugin conflict rules、availability profile、ops runbookを必須化 |
| API/自動化 | `agent-neo/v1`名前空間、標準レスポンス、dryRun/apply分離、OpenAPI/JSON Schema、job contract、idempotency、retry/DLQを必須化 |
| AI運用/クローラ | stable DOM anchor、content snapshot、crawlability profile、crawler access matrix、AI crawler log、SEO risk diffを必須化 |
| テーマ品質 | theme review checklist、accessibility profile、i18n/RTL profile、release policy、SBOM、hosting compatibility、privacy retention、uninstall cleanup、SEO indexing policy、support bundleを必須化 |
| LLMO | answer unit、evidence graph、content origin、AI visibility policy、citation anchor、LLMO visibility、claim risk、AI answer sitemapを必須化 |

## 7. 用語定義

| 用語 | 定義 |
|---|---|
| Operation Surface | REST API、MCP、WP CLI、React管理画面の操作面 |
| section_id | LP/記事内セクションを安定識別するID |
| cta_id | CTAを識別するID |
| variant_id | A/Bテストのvariant識別子 |
| BLP | Blog Landing Page。ブログ記事を自社サービスへの送客装置として機能させる構造（記事下CTA + インラインCTA + 関連サービス導線 + Trust要素 + 問い合わせ導線） |
| service_id | 企業の複数サービスをコンテンツに紐付けるID。service-aware IAの基盤 |
| offer_id | LPの主要オファー識別子。1 LPに1 offer_idを必須化 |
| 出口クリック最適化（個人版） | アフィリエイトリンクや AdSense 広告のクリックを最大化する設計。クリック後の挙動は ASP に委ねる |
| 全ファネル所有（法人版） | 訪問 → リード → 商談 → 顧客 → LTV までを自社で所有・最適化する設計 |
| リードスコアリング | 顧客の行動・属性・コンテンツ閲覧履歴に基づき自動でスコア付与 |
| 顧客健康度（health score） | 既存顧客の継続意欲・満足度を行動データから推定するスコア |
| AI 操作スコープ | 個人版 = 記事 CRUD のみ / 法人版 = 記事 + 構造変更（HP/LP/BLP/固定ページ/design-tokens/テンプレートパーツ）。agent-api の操作許可リストで強制 |
| テンプレ固定構成（個人版）| 個人版は HP/sidebar/archive/single の標準テンプレ 1 セットのみ提供し、構造変更不可。個人ブログ運用にフォーカスし保守コスト最小化 |
| AI Snapshot | AIエージェント/クローラがJS操作なしに読める公開ページ構造 |
| Crawler Access Matrix | 検索、AI入力、AI学習の許可方針をcrawler別に表す設定 |
| Theme Quality Governance | 配布、a11y、i18n、release、privacy、host互換、SEO indexing、support docsを品質ゲートとして扱う方針 |
| SBOM | 依存ライブラリ、ライセンス、バージョン、供給元を機械可読にした部品表 |
| LLMO | Large Language Model Optimization。AI検索/AI回答で読まれ、引用され、CVへ接続されるための設計 |
| Answer Unit | AIが引用しやすいように、質問、短い回答、詳細、根拠、更新日、CTAを持つセクション単位 |
| Evidence Graph | claim、source、reviewer、検証日、Entity Graphを接続する根拠データ |
| S1 | 初回構築サービス。Automation SEOを使ったAI再構築支援 |

## 8. 未決事項

| ID | 内容 | 担当 | 期限 | 状態 |
|---|---|---|---|---|
| Q-001 | 個人から法人へのアップグレード方式 | PO | L1凍結前 | open |
| Q-002 | ローンチ順 | PO | L1凍結前 | open |
| Q-003 | S1価格レンジ | PO | L2凍結前 | open |
| Q-004 | 移行プラグインPlan Aの無料/軽課金 | PO | L2凍結前 | open |
| Q-005 | ライセンス検証方式 | PO/TL | L2凍結前 | open |
| Q-006 | 自社配布テーマとwp.org申請プラグインで機能ロック/アップセル範囲をどう分けるか | PO/TL | L2凍結前 | open |
| Q-007 | 移行プレビューの差分表示粒度（HTML diff / セマンティック diff / 両方） | TL | L3開始前 | open |
| Q-008 | 販売チャネル（自社サイト / マーケットプレイス併用 / 代理店） | PO | L7前 | open |

## 9. トレーサビリティマトリクス

| 要件ID | 機能ID | API ID | 画面ID | テストID |
|---|---|---|---|---|
| REQ-F-001 | F-001 | A-001 | S-001 | TC-001 |
| REQ-F-002 | F-002 | A-002 | S-002 | TC-002 |
| REQ-F-003 | F-003 | A-002/A-003/A-004 | S-002 | TC-003 |
| REQ-F-004 | F-004 | A-005 | S-003 | TC-004 |
| REQ-F-005 | F-005 | A-006 | S-004 | TC-005 |
| REQ-F-006 | F-006 | A-007 | S-005 | TC-006 |
| REQ-F-007 | F-007 | A-008 | S-005 | TC-007 |
| REQ-F-008 | F-008 | A-009 | S-006 | TC-008 |
| REQ-F-009 | F-009 | A-010 | S-002 | TC-009 |
| REQ-F-010 | F-010 | A-011 | S-007 | TC-010 |
| REQ-F-011 | F-011 | A-012 | S-008 | TC-011 |
| REQ-F-012 | F-012 | A-013 | S-009 | TC-012 |
| REQ-NF-008 | F-001/F-003/F-006/F-011/F-012 | A-002/A-003/A-012/A-013 | S-001/S-008/S-009 | TC-NF-002 |
| REQ-NF-009 | F-004/F-006/F-007/F-010/F-011 | A-005/A-007/A-008/A-011/A-012 | S-003/S-005/S-007/S-008 | TC-NF-003 |
| REQ-NF-010 | F-001/F-003/F-006/F-007/F-011/F-012/F-013 | A-001/A-004/A-007/A-008/A-012/A-013 | S-001/S-005/S-008/S-009 | TC-NF-004 |
| REQ-NF-011 | F-001/F-012/F-015 | A-004/A-013 | S-001/S-009 | TC-NF-005 |
| REQ-NF-012 | F-012/F-016 | A-004/A-013 | S-009 | TC-NF-006 |
| REQ-NF-013 | F-001/F-003/F-014/F-017 | A-001/A-004 | S-001/S-002/S-007 | TC-NF-007 |
| REQ-NF-014 | F-002/F-003/F-006/F-007/F-018 | A-002/A-003/A-004/A-007/A-008/A-014/A-015/A-016/A-017 | S-002/S-005/S-001 | TC-NF-008 |
| REQ-NF-015 | F-002/F-011/F-012/F-019 | A-004/A-012/A-013/A-018/A-019/A-020 | S-001/S-002/S-008/S-009/S-010 | TC-NF-009 |
| REQ-NF-016 | F-001/F-011/F-012/F-017/F-020 | A-001/A-004/A-017 | S-001/S-010/S-011 | TC-NF-010 |
| REQ-NF-017 | F-006/F-007/F-011/F-012/F-019/F-021 | A-004/A-012/A-013/A-018/A-019/A-020/A-021 | S-008/S-010/S-012 | TC-NF-011 |
| REQ-NF-018 | F-001/F-002/F-006/F-007/F-011/F-012/F-017/F-018/F-019/F-022 | A-004/A-012/A-013/A-017/A-018/A-019/A-020/A-022 | S-001/S-008/S-010/S-011/S-013 | TC-NF-012 |
| REQ-NF-019 | F-006/F-007/F-011/F-012/F-023 | A-004/A-008/A-012/A-013/A-023 | S-005/S-008/S-009/S-014 | TC-NF-013 |
| REQ-NF-020 | F-007/F-008/F-011/F-012/F-014/F-024 | A-004/A-008/A-012/A-013/A-024 | S-005/S-008/S-009/S-014/S-015 | TC-NF-014 |

## Gate

| Gate | 判定 | 根拠 |
|---|---|---|
| G0.5 | passed_with_draft | L0企画書をL1要件へ反映 |
| L1 | draft | PO未レビューのため凍結前 |
| Security | passed_with_caution | 書き込みAPIと参照テーマライセンスの注意点を明記 |
