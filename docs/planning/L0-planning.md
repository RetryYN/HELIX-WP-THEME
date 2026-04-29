# L0 企画書 — AGENT NEO

> 本ドキュメントは L1 要件定義の上流ソース（企画書）。HELIX G0.5（企画突合ゲート）で L1 との整合をチェックするための正本。

**プロジェクト名:** AGENT NEO
**作成日:** 2026-04-28
**ステータス:** Draft
**駆動タイプ:** agent（HELIX 分類）/ 規模 L

---

## 1. ビジョン（一文）

**AI エージェントが第一級ユーザーとなる、商用 WordPress FSE テーマ。**
すべてのコンテンツ・テーマ・ブロック操作が JSON ベース API で完結することを設計の最上位目標とする。

---

## 2. 背景・課題

| 観点 | 現状の課題 |
|---|---|
| 既存 WP テーマ | 人間 GUI 最適化が前提。AI エージェントが操作するには DOM・セレクタ・編集面が不安定 |
| AI 自動化ニーズ | 記事生成・SEO・LP 構築を AI に任せたい需要が増えているが、受け皿となる「AI 操作前提」のテーマがない |
| 既存ヘッドレス構成 | headless WP は強力だが移行コスト・運用負荷が高く、個人〜中小企業層には届かない |
| トラッキング基盤 | 動的 CTA / A/B テスト / セクション計測の基盤を備えるテーマが少ない |

→ **「WordPress エコシステムを使いつつ AI 完全操作可能」** という新しいセグメントを創出する余地がある。

---

## 3. ソリューション概要

AGENT NEO は以下 3 SKU + 1 サービスで構成される製品ライン。

| # | プロダクト | 価格 | ライセンス | 配布形態 |
|---|---|---|---|---|
| 1 | AGENT NEO テーマ（個人 / アフィリエイター版） | ¥19,800 | 商用配布 | 自社販売 |
| 2 | AGENT NEO テーマ（法人版） | ¥98,000 | 商用配布 | 自社販売 |
| 3 | AGENT NEO 移行プラグイン | **無料** | GPL v2 | wp.org 公式申請視野 |
| S1 | 初回構築サービス（プロフェッショナルサービス） | 別途見積 | サービス契約 | 受託 |
| A1 | Automation SEO 連携/AI実行枠 | 別課金 | サービス/アドオン契約 | 自社販売 |

**価格差 5 倍** → 機能差を明確に設計（共通 Core を細く、法人専用機能を厚く）。
Automation SEO はテーマ価格に無制限同梱しない。AI原価・移行支援・法人運用支援は S1 / 有料アドオン / 従量 / BYO APIキー候補で回収する。

---

## 4. 製品仕様（高レベル）

### 4.1 共通基盤
- 独立した自作 **FSE（フルサイト編集 / ブロック）テーマ**。SWELL 等他社テーマには非依存
- WordPress 6.6+ / PHP 8.1+ / theme.json + block.json 中心
- GPL 互換ライセンス・i18n（ja/en）対応・第三者依存最小
- 機械可読フロント（section_id 規約 / JSON-LD / 安定セレクタ / WCAG 2.2 AA）
- **4 つの操作面（Operation Surfaces）を提供:**
  | 操作面 | 用途 | 主な利用者 |
  |---|---|---|
  | REST API（JSON）| HTTP 経由、リモート操作 | Web ベースの AI、Automation SEO 等 |
  | MCP サーバー同梱 | Claude Desktop / MCP クライアント直接連携 | Claude Computer Use / MCP 対応エージェント |
  | React 管理画面 | GUI 操作・データ確認 | 人間管理者 |
  | **WP CLI 拡張コマンド** | ターミナル / SSH / CI 経由 | DevOps スクリプト・terminal ベース AI |
- WP CLI: `wp agent-neo` 名前空間でカスタムコマンドを登録（記事 CRUD / 設定 / ライセンス / 移行 / ログ等）

### 4.2 個人 / アフィリエイター版（¥19,800）
**核心価値: 出口（アウトバウンド）クリック最適化** — アフィリエイトリンク・広告クリックまでが勝負。クリック後は ASP 側に責務移譲。

#### 設計の核となる 2 つの絞り込み
1. **AI 操作スコープは「記事管理」中心**: 投稿・カテゴリ・タグ・メディア・アフィリリンクの CRUD のみ。HP / LP / 固定ページの構造変更はスコープ外
2. **提供形態はテンプレ固定構成**: HP / sidebar / archive / single の標準テンプレ 1 セット。個人ブログは HP に凝るメリットが薄いため、テンプレ固定で十分

リード獲得・CRM・問い合わせフォーム・行動管理は**スコープ外**（法人版へ）。これにより agent-api の操作許可リスト・テスト範囲・保守コストが小さく抑えられる。

#### 対応収益化チャネル
- Amazon アソシエイト（Amazon Product API 連携で商品情報自動取得）
- メルカリ（商品リンク・カード）
- Google AdSense（配置最適化・自動広告）
- もしもアフィリエイト / a8.net / バリューコマース 等の主要 ASP（汎用 link block）

#### 機能セット
- 共通基盤 +
- 高 CTR 設計ブロック: Review / Ranking / Comparison / Pros Cons / Ad Tag / Affiliate CTA / Blog Card / Pickup Banner
- 商品カード（Amazon Product API / メルカリリンク）
- AdSense 配置最適化（above-the-fold / インライン / sidebar / sticky）
- **PR 表記自動付与**（景表法対応・Affiliate Disclosure ブロック必須化）
- 構造化データ: Review, FAQ, Breadcrumb, Article
- SEO Core: title / description / canonical / noindex / OGP / Entity Graph をテーマ標準で管理
- 軽量クリック計測（ASP 別 / 商品別 / 配置別の CTR レポート）
- 収益サマリダッシュボード（ASP 別売上推定・クリック単価）

### 4.3 法人版（¥98,000）
**核心価値: 全ファネル（インバウンド）所有 — HP/LP/BLP 導線 + リード獲得 + 顧客行動管理**

個人版が「クリックまで」の出口最適化なのに対し、法人版は **訪問 → リード → 商談 → 顧客 → LTV までを自社所有する**設計。HP・LP・BLP の導線で**問い合わせ・資料 DL・無料相談**を獲得し、その後の顧客行動・育成・継続関係までをテーマ内で完結（または CRM/MA 連携で）管理する。

#### AI 操作スコープが個人版と決定的に異なる
| 領域 | 個人版 | 法人版 |
|---|---|---|
| 記事 CRUD | ✓ | ✓ |
| メディア管理 | ✓ | ✓ |
| アフィリリンク | ✓ | — |
| **HP / LP / BLP の構造変更** | ✗（テンプレ固定） | ✓（ブループリント差し替え可能） |
| **固定ページのレイアウト変更** | ✗ | ✓ |
| **design-tokens 操作** | ✗ | ✓ |
| **テンプレートパーツ操作** | ✗ | ✓ |
| **フォーム / リード管理** | ✗ | ✓ |
| **顧客行動分析・スコアリング** | ✗ | ✓ |

価格差 5 倍は「**AI に何を触らせるか**」のスコープ差で正当化される。

#### サイト種別ごとの役割分担

| 種別 | 役割 | 主要セクション |
|---|---|---|
| **HP**（Home Page）| ブランド入口・回遊ハブ・サービス入口の整理 | Hero variant（still / article_slider / image_slider / movie / product_focus / lead_gen）+ Gateway Grid + Pickup + 信頼形成 |
| **LP**（Landing Page）| 1 サービス 1 オファーの成約装置 | Hero → Problem → Consequence → Solution → Feature → Benefit → Use Case → Proof → Comparison → Pricing → FAQ → CTA の標準 12 セクション |
| **BLP**（Blog LP / 記事 LP）| ブログ記事を**自社サービスへの送客装置**化 | 記事下 CTA / 関連サービス導線 / 記事内インライン CTA / Trust 要素 / 問い合わせ導線 |

#### 法人版限定機能
- 共通基盤 + 個人版機能 +
- ブランドトークン管理（色・タイポ・余白・ロゴ）— `design-tokens.json`
- HP / LP / BLP それぞれの blueprint（JSON 契約）
- LP セクションビルダー（標準 12 セクション + カスタム拡張）
- 再利用パーツ管理（Global Section / Reusable Part / CTA Part）
- 製品/サービス導線（製品カード・機能比較・導入事例・問い合わせ CTA）
- 権限分離（管理者 / 編集者 / 寄稿者 / 承認者）
- A/B テスト・CTA 計測詳細・variant 別レポート
- 設定エクスポート / インポート JSON
- 管理画面で詳細権限管理タブ開放
- **複数サービスを持つ企業向けの service-aware IA**（service_id でコンテンツとサービスを紐付け）

#### リード獲得機能（法人版限定）
- 問い合わせフォーム / 資料 DL フォーム / 無料相談予約 / Webinar 登録 / メルマガ登録ブロック
- reCAPTCHA / honeypot / レート制限による spam 対策
- フォーム送信完了後の thank-you ページ・自動返信メール
- フォーム submission の管理画面一覧 + CSV エクスポート

#### 顧客行動管理（法人版限定）
- セッション単位ジャーニー追跡（どのページ → どの CTA → どのフォームへ）
- ファネル分析（HP → LP → form → submit のドロップオフ可視化）
- **リードスコアリング**（行動・属性・コンテンツ閲覧でスコア付与）
- 顧客健康度（health score）= ハイスコア顧客の優先抽出
- 顧客ジャーニーの可視化を admin-dashboard に提供

#### CRM/MA 連携アドオン（A1 系・別課金候補）
- HubSpot / Salesforce / kintone / Zoho / Pipedrive 等の CRM への Webhook + REST 連携
- SendGrid / Mailchimp / WPForms 等のメール基盤連携
- 既存連携が不足する場合は Zapier / Make 経由のアダプタブロック

### 4.4 移行プラグイン（無料）— 2 プラン選択型

ユーザーが UI でプランを選択する。テーマ別アダプタは作らず、両プランとも WP REST API で抽出する点は共通。

#### プラン A: REST API ベースのデータ移行（DIY）
- 対象: ブロガー・小規模サイト・コスト重視層
- 動作: WP REST API で投稿/メディア/タクソノミー/メニュー取得 → AGENT NEO 標準ブロックへ素直な変換 → 投入
- LLM 利用: 最小限
- ユーザーが後追いで微調整する前提
- 価格: 完全無料 or 軽課金（要決定）

#### プラン B: AI ベースのフルセットアップ移行（プレミアム）
- 対象: 法人・本気の事業者
- 動作: 抽出 → **Automation SEO の LLMRouter で意味的に再構築**（IA 再設計・セクション分割・SEO メタ再生成・デザイントークン抽出）→ 検収 → 公開
- 価格: 初回構築サービス S1 として別途見積課金
- AGENT NEO テーマ（個人/法人）の購入が前提
- Automation SEO のAI実行コストはテーマ本体価格とは分離する

#### 検証ケース順序
日本国内 WP テーマシェア順: SWELL → Cocoon → AFFINGER → JIN → Lightning

### 4.5 初回構築サービス S1
プラン B の実行・検収・公開支援を提供するプロフェッショナルサービス。価格レンジ・契約形態は未決。

---

## 5. 連携先システム

### Automation SEO（既存・成熟）
- リポジトリ: `git@github.com:RetryYN/Automation-SEO.git`
- ローカル: `C:\Users\tenni\Desktop\seo-tool-v2-docs\Automation SEO\`
- 別名: SEO Tool v2 / seo-tool-connector
- 状態: Phase 9（テスト実行）/ ~255,000 LOC / API 571+ / 開発期間 30 日

#### 技術スタック
- Frontend: Next.js 16 (App Router) / React 19 / TypeScript
- Backend: FastAPI / Python 3.13
- DB: PostgreSQL 16 + pgvector / Redis 7
- LLM: Claude / GPT / Gemini / Grok（LLMRouter 経由）

#### 既存 WP プラグイン: `seo-tool-connector` v1.1.0（GPL v2）
| 機能 | 内容 |
|---|---|
| 動的 CTA 最適化 | リアルタイムでの CTA 出し分け |
| A/B テスト | variant 切替・露出計測 |
| セクション計測 | section_id 単位のエンゲージメント計測 |

#### 既存 API（AGENT NEO は整合させる）
- POST /v1/tracking/event
- POST /v1/tracking/section-engagement
- POST /v1/tracking/context（ページ構造・セクション・CTA マップ）
- POST /wordpress/pages/sync/{site_id}

#### 既存スキーマ
site_token / site_id / wp_post_id / article_id / section_id / cta_id / variant_id

#### データモデル
SITES → WORDPRESS_CONNECTIONS → WP_PAGES → WP_PAGE_SECTIONS → TRACKING_EVENTS

### AGENT NEO テーマと seo-tool-connector の関係
- `seo-tool-connector` プラグイン = 連携・トラッキングの glue 層（既存・成熟）
- AGENT NEO テーマ = レンダリング層（新規開発）
- AGENT NEO の `agent-actions.schema.json` は既存 API スキーマと整合させる（ゼロから作らない）
- AGENT NEO ブロックは安定した `section_id` を宣言、CTA は `variant_id` 切替対応、投稿は `article_id` を露出

#### SWELL/JIN:RとのAutomation SEO連携評価

| テーマ | Automation SEO連携点数 | 良い点 | 弱い点 |
|---|---:|---|---|
| SWELL | 73 | 速度基盤、Entity Graph、LP/再利用パーツ、SEOプラグイン共存 | SEOメタ主導権が外部依存寄り、stable `section_id`/`cta_id`、safe apply契約がない |
| JIN:R | 77 | SEO統合UX、SEO post meta REST公開、canonical/noindex/OGP/JSON-LD | 巨大CSS/jQuery/CDNリスク、classic template、stable `section_id`/`cta_id`契約がない |

結論として、SWELL/JIN:RはAutomation SEOの直接運用対象ではなく、**移行・診断・設計参考**として扱う。Automation SEO側はTheme Capability Scanner、Section ID Resolver、SEO Meta Normalizer、Context Contract v2を持ち、WPテーマ側はAGENT NEOでSection Registry、CTA Registry、Automation SEO Adapter、Safe Write APIを持つ。

#### Automation SEO Theme Bridge Plugin方針

既存テーマを横断的に強化するプラグインは、テーマ別の深い自動書き換えではなく、Theme Capability Scanner、SEO Meta Normalizer、Section/CTA Registry、Tracking Context v2、Privacy/Data Map、Migration Blueprint Exporterを持つ診断・正規化・移行入口として設計する。既存テーマでは原則preview/提案止まり、AGENT NEO Core Pluginだけをsafe applyの第一級書き込み先にする。

---

## 6. 戦略・販売ファネル

```
無料プラグイン（プラン A 機械変換）
   ↓ ユーザー自身がプランを選択
プラン B（AI フルセットアップ）= S1 サービス課金
   ↓
受け皿: AGENT NEO テーマ（個人 ¥19,800 / 法人 ¥98,000）
追加収益: Automation SEO 別課金 / S1 / 有料アドオン / 従量課金
```

**戦略的旨み:**
- 無料プラン A = lead magnet。間口最大化・wp.org 公式ディレクトリ経由の自然流入
- プラン B = AI 再構築の独自価値で課金（Automation SEO の既存 LLMRouter を活用）
- 個人/法人テーマ = 受け皿。アップグレードパスで LTV 拡大
- ユーザーが自分でプランを選択 → 押し売り感ゼロ・納得感の高い購買体験
- テーマ別アダプタ不要 → 全テーマ即対応の宣伝が打てる + 開発・保守コスト削減

---

## 7. ターゲットユーザー

| ティア | ペルソナ | 主な利用シナリオ |
|---|---|---|
| プラン A | 個人ブロガー・小規模アフィリエイター | 既存サイトを最低限のコストで AGENT NEO に乗せ替え、AI で記事更新を自動化したい |
| 個人テーマ | アフィリエイター（中〜上級） | Automation SEO で記事自動生成 → AGENT NEO で機械可読・収益最適化された配信 |
| 法人テーマ | 中小企業・スタートアップ・代理店 | 製品 LP・コーポレートサイトを AI で運用、A/B テスト・CTA 計測まで自動化 |
| プラン B | 上記すべて（特に法人） | サイトリニューアル時のワンストップ移行（IA 再設計込み） |

---

## 8. 競合・差別化

| 競合 | 差別化ポイント |
|---|---|
| SWELL / AFFINGER / JIN 等の汎用 WP テーマ | AI エージェント前提の API・section_id 規約・MCP 同梱 |
| Headless WordPress（Faust.js 等） | WP エコシステムを保持しつつ AI 操作面のみ強化、移行コストが圧倒的に低い |
| 海外 AI 系 WP プラグイン | Automation SEO 連携で記事生成〜公開〜計測まで日本語特化で完結 |
| ブロックエディタのみのテーマ | Automation SEO + seo-tool-connector との連携でトラッキング・A/B が標準装備 |

**核心の差別化:** **「人間 GUI 不要で AI が完結する」** という体験を商用 WP テーマで初めて実現する点。

### 8.1 競合総合評価と狙うポジション

| テーマ | 総合点 | 市場ポジション | AGENT NEOの取り込み方 |
|---|---:|---|---|
| SWELL | 88 | 国産有料テーマの王道・総合型 | 速度設計、設定UX、買い切り安心感を抽象化して取り込む |
| JIN:R | 82 | デザイン/SEO/ブログ収益化寄り | SEO統合UX、プリセット思想、ブロガー心理に刺さる販売導線を取り込む |
| AFFINGER6 / ACTION PACK | 80 | アフィリエイト収益化特化 | 収益化ブロック、CTA/タグ/A-B/CTR、上位PACK戦略を取り込む |
| Cocoon | 78 | 無料テーマの標準・導入口 | 無料移行プラグインのリード獲得モデルとして参考にする |
| Lightning / Vektor | 76 | 法人/中小企業サイト・制作会社向け | 法人安心感、拡張/サブスク、パターン/学習コンテンツを参考にする |

AGENT NEOは「美しいテーマ」「SEOに強いテーマ」「安いテーマ」の競争軸では戦わない。狙うカテゴリは **AI運用型WPテーマ基盤** とし、個人版は「SWELL/JIN:R級の使いやすさ + AFFINGER級の収益化 + AI記事投入/計測/LLMO」、法人版は「Lightning級の法人安心感 + LP改善/A/B/計測 + AI運用」を目標にする。

目標スコアは Core `92`、個人版 `90`、法人版 `94` とする。ただし達成条件は機能数ではなく、JSON契約、AI操作安全性、LP/CTA改善、SEO/LLMO、運用品質まで一貫して製品化できていること。

---

## 9. ロードマップ（高レベル・要決定）

```
[Phase 1] L1 要件定義 → L2 全体設計 → L3 詳細設計
[Phase 2] L4 マイクロスプリント実装
   - 個人テーマ → 法人テーマ → 移行プラグイン（順序は要決定）
[Phase 3] L5 Visual Refinement（FSE デザイン）
[Phase 4] L6 統合検証 → L7 デプロイ → L8 受入
[Phase 5] wp.org 公式ディレクトリ申請（移行プラグイン）
[Phase 6] 販売開始・初回構築サービス受付
```

ローンチ順（個人 → 法人 / 法人 → 個人 / 同時）は §10 未決事項参照。

---

## 10. 未決事項（OPEN QUESTIONS）

| ID | 内容 | 判断者 | 期限 |
|---|---|---|---|
| Q-001 | 個人 → 法人アップグレードの実装方式（差額課金 ¥78,200 / オンライン課金 / ライセンスキー再発行） | PO | L1 完了前 |
| Q-002 | ローンチ順（個人 → 法人 / 法人 → 個人 / 同時） | PO | L1 完了前 |
| Q-003 | 初回構築サービス S1 の価格レンジ・契約形態（固定 / 従量 / 段階課金） | PO | L2 開始前 |
| Q-004 | Automation SEO / LLM コスト負担 | PO | **決定: テーマ価格とは分離し、S1 / 有料アドオン / 従量 / BYO APIキー候補で回収** |
| Q-005 | プラン A の有料化（完全無料 / 軽課金） | PO | L2 開始前 |
| Q-006 | 移行プレビューの差分表示粒度（HTML diff / セマンティック diff） | TL | L3 開始前 |
| Q-007 | ライセンス検証方式の希望（独自 / Gumroad / WooCommerce / 課金 SaaS） | PO | L2 開始前 |
| Q-008 | 販売チャネル（自社サイト / マーケットプレイス併用） | PO | L7 前 |
| Q-009 | 自社配布テーマと wp.org 申請プラグインで機能ロック/アップセル範囲をどう分けるか | PO/TL | L2 凍結前 |

---

## 11. 制約・前提条件

| 種別 | 内容 |
|---|---|
| 技術 | FSE 必須 / WP 6.6+ / PHP 8.1+ / GPL 互換 |
| ライセンス | テーマ本体・プラグインとも GPL 互換。第三者ライブラリは監査必須 |
| 連携 | seo-tool-connector の API スキーマと整合（ゼロから API を作らない） |
| 配布 | 移行プラグインは wp.org 公式ディレクトリ申請可能な品質 |
| 機能境界 | Theme CoreはFSE表示層、Companion PluginはREST/MCP/WP CLI/CPT/SEO/計測/A-B/Blueprint層 |
| プラグイン依存 | 必須依存はAGENT NEO Theme + Core Pluginに限定。外部SEO/フォーム/キャッシュ/GA/GTM系は任意adapter |
| 表示/同意 | アフィリエイトPR表記、外部送信同意、privacy policy templateを必須化 |
| アクセシビリティ | WCAG 2.2 AA 準拠 |
| 運用 | WP/PHP互換、更新前後チェック、rollback、plugin衝突検出、可用性fallback、SLO/health checkを契約化 |
| API/自動化 | REST/MCP/WP CLI/Cron/Webhook/jobをOpenAPI/JSON Schema契約で統一し、idempotency、retry/DLQ、SSRF/rate limitを必須化 |
| AI運用/クローラ | AIエージェント向けstable DOM anchor、public content snapshot、crawler access matrix、AI crawler log、SEO risk diffを契約化 |
| テーマ品質 | Theme Review、a11y実査、i18n/RTL、Release/SBOM、hosting compatibility、privacy retention、SEO indexing、support bundleを品質ゲート化 |
| LLMO/AI検索 | answer unit、evidence graph、content origin、AI visibility policy、citation anchor、AI経由CV計測を契約化 |
| SEO/WP運用ハザード | canonical/noindex/robots/sitemap、WP-Cron、cache、plugin conflict、update/rollback、privacy/log、AI snapshotの不都合な真実をrisk-ledgerで契約化 |
| 国際化 | 初版は日本語 + 英語 |

---

## 12. 参照ドキュメント

| ドキュメント | 内容 |
|---|---|
| [Reverse 解析](../reverse/wp-theme-reference-analysis.md) | SWELL 親/子テーマ解析・採用パターン抽出・パッケージルーティング |
| [価格戦略と売れる理由](../../解析レポート/09-価格戦略と売れる理由.md) | 競合公式価格、売れている理由、AGENT NEO価格戦略 |
| [深掘り解析実施計画](../../解析レポート/10-深掘り解析実施計画.md) | REST/CPT/block/settings/template/販売訴求の深掘り計画 |
| [競合比較マトリクス](../../解析レポート/11-競合比較マトリクス.md) | 国内主要テーマとの比較と取り込み優先度 |
| [企画書レビューと要件反映](../../解析レポート/12-企画書レビューと要件反映.md) | G0.5企画突合、L1/L2反映状況 |
| [SEO設計比較](../../解析レポート/13-SEO設計比較-JINR優先分析.md) | JIN:R優先のSEO Core方針、SWELL JSON-LD設計の取り込み |
| [JIN:R親テーマSEO実コード解析](../../解析レポート/14-JINR親テーマ実コードSEO解析.md) | JIN:R親テーマZIP展開後のtitle/description/canonical/noindex/OGP/JSON-LD解析 |
| [ページスピード設計比較](../../解析レポート/15-ページスピード設計比較.md) | 速度設計はSWELL優先、SEOはJIN:R優先とする設計分担 |
| [LP/HP設計方針](../../解析レポート/16-LP-HP設計方針.md) | LPとHPを別ブループリント化し、法人LP/個人収益HP/計測導線へ落とす方針 |
| [制約条件と設計ガード](../../解析レポート/17-制約条件と設計ガード.md) | ライセンス、plugin territory、プライバシー、AI操作、SEO/計測の制約と設計ガード |
| [テーマコーディングルール逆引き設計](../../解析レポート/18-テーマコーディングルール逆引き設計.md) | SWELL/JIN:R実コードから逆引きしたTheme本体のコーディング規約と構成案 |
| [デザインUI思想逆引き設計](../../解析レポート/19-デザインUI思想逆引き設計.md) | SWELL/JIN:Rから逆引きした失敗しにくいUI思想、プリセットUX、UI Audit |
| [運用セキュリティ可用性更新性分析](../../解析レポート/20-運用セキュリティ可用性更新性分析.md) | WPバージョン、更新、セキュリティ、可用性、プラグイン追加時の運用品質設計 |
| [自動化CronAPI契約設計](../../解析レポート/21-自動化CronAPI契約設計.md) | WP-Cron、自動化job、REST/AJAX/API契約、OpenAPI/JSON Schema、外部API連携の設計 |
| [AIエージェント運用性とクローラビリティ分析](../../解析レポート/22-AIエージェント運用性とクローラビリティ分析.md) | AIエージェント運用の不都合な真実、stable DOM、content snapshot、AIクローラ制御 |
| [テーマ構築観点総合レビュー](../../解析レポート/23-テーマ構築観点総合レビュー.md) | Web公式情報から逆算した見落とし観点、品質ゲート、L1/L2反映案 |
| [LLMO時代のテーマ設計重要観点](../../解析レポート/24-LLMO時代のテーマ設計重要観点.md) | AI検索/LLMO時代の引用されやすさ、権利制御、answer unit、AI経由CV計測の設計 |
| [WPサイト運用とSEOの不都合な真実](../../解析レポート/25-WPサイト運用とSEOの不都合な真実.md) | SEO、WP運用、セキュリティ、AI運用の静かな失敗要因をrisk-ledger化する設計 |
| [競合テーマ総合評価と市場ポジション](../../解析レポート/26-競合テーマ総合評価と市場ポジション.md) | 主要競合テーマの総合点、良い点/悪い点、AGENT NEOが狙う市場ポジション |
| [Automation SEO連携観点のSWELL/JIN:R採点](../../解析レポート/27-AutomationSEO連携観点のSWELL-JINR採点.md) | Automation SEO連携観点のSWELL/JIN:R採点、Automation SEO側/WPテーマ側のカバー策 |
| [共通強化プラグインとAutomation SEOプラグイン情報設計](../../解析レポート/28-共通強化プラグインとAutomationSEOプラグイン情報設計.md) | 既存テーマを横断強化するTheme Bridge Plugin、保持情報、不都合、AGENT NEOへの責務分離 |
| [L1 要件定義](../requirements/L1-requirements.md) | 本企画書を構造化した要件定義（TL ドラフト中） |
| [Automation SEO 設計書](file:///C:/Users/tenni/Desktop/seo-tool-v2-docs/Automation%20SEO/system_design_max/) | 連携先システムの設計書一式 |
| [seo-tool-connector プラグイン](file:///C:/Users/tenni/Desktop/seo-tool-v2-docs/Automation%20SEO/wordpress-plugin/seo-tool-connector/) | 既存 WP プラグインソース |

---

## 13. 改訂履歴

| 版 | 日付 | 内容 | 作成者 |
|---|---|---|---|
| 0.1 | 2026-04-28 | 初版（PM Opus がチャットセッションから抽出） | PM |
| 0.2 | 2026-04-28 | Automation SEO別課金方針、価格戦略レポート、L1/L2反映を追記 | Codex |
| 0.3 | 2026-04-29 | ページスピード設計を追加。Core Web Vitals、SWELL型速度基盤、JIN:R型SEO UXの分担を反映 | Codex |
| 0.4 | 2026-04-29 | LP/HP設計方針を追加。LP/HP別ブループリント、セクション計測、法人/個人導線を反映 | Codex |
| 0.5 | 2026-04-29 | 制約条件を追加。Theme Core/Companion Plugin分離、PR表記、同意付き計測、データ移植性、プラグイン依存度を反映 | Codex |
| 0.6 | 2026-04-29 | Theme本体のコーディングルール逆引きを追加。薄いbootstrap、block.json正本、WPCS、used block assetsを反映 | Codex |
| 0.7 | 2026-04-29 | デザイン/UI思想逆引きを追加。SWELLの情報設計、JIN:RのプリセットUX、UI Auditを反映 | Codex |
| 0.8 | 2026-04-29 | 運用品質を追加。WP/PHP互換、更新前後チェック、rollback、plugin衝突検出、可用性fallbackを反映 | Codex |
| 0.9 | 2026-04-29 | 自動化/Cron/API契約を追加。OpenAPI、JSON Schema、job contract、WP-Cron/WP CLI/external cron fallbackを反映 | Codex |
| 1.0 | 2026-04-29 | AI運用性/クローラビリティを追加。stable DOM anchor、content snapshot、crawler access matrix、AI crawler logを反映 | Codex |
| 1.1 | 2026-04-29 | テーマ構築観点の総合レビューを追加。Theme Review、a11y、i18n、Release/SBOM、hosting、privacy、SEO indexing、support docsを反映 | Codex |
| 1.2 | 2026-04-29 | LLMO/AI検索観点を追加。answer unit、evidence graph、AI visibility policy、citation anchor、AI経由CV計測を反映 | Codex |
| 1.3 | 2026-04-29 | SEO/WP運用/セキュリティ/AI運用の不都合な真実を追加。risk-ledger、seo-hazard、cron/cache/plugin/update/privacy/snapshot guardを反映 | Codex |
| 1.4 | 2026-04-29 | 競合テーマ総合評価を追加。市場ポジションをAI運用型WPテーマ基盤として明文化 | Codex |
| 1.5 | 2026-04-29 | Automation SEO連携観点のSWELL/JIN:R採点を追加。連携の不足をAutomation SEO側/WPテーマ側の契約でカバーする方針を反映 | Codex |
| 1.6 | 2026-04-29 | Automation SEO Theme Bridge Plugin方針を追加。既存テーマ横断の診断・正規化・移行入口とAGENT NEO safe apply責務を分離 | Codex |
