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

### 1.1 製品哲学（第一原理 — 全設計判断の評価軸）

| # | 原理 | 具体ライン |
|---|---|---|
| 1 | **無駄な JavaScript を組まない** | 全 JS は「CV 直結 / 計測 / AI 操作」のいずれかに寄与する場合のみ。jQuery 非依存。block.json で個別宣言した JS のみ条件付き読み込み。**採用時は `defer`/`async` 必須・メインスレッドブロック禁止・1 ブロック ≤ 5KB 目安** |
| 2 | **ページスピード最優先（ページタイプ別最適化）** | LCP / INP / CLS は **ページ種別ごとに** 予算設定。「重くなってはいけないページ（記事・アーカイブ）」と「重くなることを許容するページ（LP）」を構造的に分離。詳細表は §1.1.1 |
| 3 | **結果（CV）を届けるテーマ** | 「きれいなテーマ」ではなく「成果が出るテーマ」。個人版=アフィリクリック、法人版=リード獲得、両方の CV を直接最大化する設計を最上位指標 |
| 4 | **非 AI ユーザーも単独で使える**（AI-first だが AI-only ではない）| AI 連携 OFF でも全 P0 機能が動作。WP 標準エディタ完全互換。AI 機能は**オプトイン**（明示的な有効化が必要）。日本語 UI / 段階的開示 / IT に詳しくない個人ユーザーでも使える設計 |

**実装ルール（第一原理の運用具体化）:**

**画像メディアポリシー（必須）**
- **写真・コンテンツ画像**: JPEG / WebP がデフォルト
- **WebP 自動生成**: アップロード時に WebP を自動生成し、元 JPEG をフォールバックに保持。`<picture>` 要素で配信
- **PNG**: アイコン・透過必須時のみ。コンテンツ写真には使用しない
- **GIF**: 禁止（アニメーション必要時は WebM / MP4）
- **AVIF**: オプション（WebP の上位互換、対応ブラウザ拡大時に有効化）
- **alt 属性**: 必須化（a11y + SEO + AI 機械可読のため）

**JS 採用時の性能担保（必須）**
- `defer` または `async` 必須（メインスレッドブロック禁止）
- minify + tree-shake 済みのみ配信
- 外部スクリプト（YouTube, Twitter 等）は `<link rel=preconnect>` で接続最適化
- 1 ブロックあたり JS ≤ 5KB を目安、超過時は L3 で分割設計レビュー
- `requestIdleCallback` / lazy 初期化を活用

#### 1.1.1 ページタイプ別性能予算（隠れ killer feature）

**問題認識**: 既存 WP テーマは「機能を追加 → 全ページに影響 → 全体が重くなる」循環に陥る。SWELL/JIN:R も used_block_css で部分対応するが、**ページタイプ別の予算分離は提供していない**。

**AGENT NEO の解決策**: ページ種別ごとに JS/CSS 予算を**構造的に分離**。`block.json` で page_type allowlist を宣言、フロント enqueue で `is_singular()` / `page_template()` / `is_archive()` を判定して条件付き読み込み。

##### ページタイプ別予算（必達 SLO）

| ページ種別 | 個人版 JS 予算 | 法人版 JS 予算 | LCP 目標 | 想定理由 |
|---|---:|---:|---:|---|
| **記事 / BLP** | < 15KB | < 20KB | < 2.0s | SEO 主戦場、モバイル流入最多 |
| **アーカイブ / カテゴリ** | < 15KB | < 20KB | < 2.0s | 回遊起点、SEO ランディング |
| **検索結果** | < 10KB | < 15KB | < 2.0s | UX 重要、軽量必須 |
| **HP** | < 30KB | < 40KB | < 2.5s | ブランド演出を一定許容 |
| **固定ページ（about/contact 等）** | < 25KB | < 30KB | < 2.5s | フォーム込み |
| **LP** | < 50KB | < 80KB | < 2.8s | A/B + 動的 CTA + フォーム必要、機能性優先 |

##### 実装機構

1. **block.json で page_type 宣言**:
   ```json
   {
     "name": "agent-neo/lp-hero-slider",
     "agentNeo": {
       "allowedPageTypes": ["lp"],
       "jsKB": 12,
       "cssKB": 4
     }
   }
   ```
   → 記事/アーカイブで誤って配置されない・読み込まれない

2. **asset-policy.schema.json でページタイプ別予算定義**: 数値を JSON で凍結、CI で違反検出

3. **フロント enqueue 条件分岐**: `wp_enqueue_scripts` で `is_singular('post')` / `is_page_template('lp')` / `is_archive()` 等を判定し、必要なバンドルのみ読み込み

4. **plugin dequeue adapter**: 軽量ページで Yoast/CF7/Elementor 等のグローバルロード型プラグインを dequeue（管理画面で plugin allowlist を page_type 別に設定可能）

5. **CI per-page-type Lighthouse**: GitHub Actions で代表 URL（記事 / HP / LP / アーカイブ）に対し Lighthouse を個別実行、各予算違反時は CI 失敗

##### 競合との差別化

| 項目 | SWELL | JIN:R | AGENT NEO |
|---|---|---|---|
| ブロック単位 CSS 分離 | ✓（used_block_css）| ✗ | ✓ |
| **ページタイプ別 JS 予算分離** | ✗ | ✗ | **✓**（隠れ killer feature）|
| **記事だけ < 15KB 保証** | ✗ | ✗ | **✓**（CI で強制）|
| プラグイン dequeue adapter | △（手動）| ✗ | ✓（page_type 連動）|
| ページタイプ別 Lighthouse CI | ✗ | ✗ | ✓ |

「LP に機能を盛り込んでも記事は重くならない」を **契約レベルで保証**。マーケ訴求材料: 「他社テーマは全ページが LP の重さに引きずられる。AGENT NEO は記事を絶対に守る」。

**画像変換パイプライン（必須）**
- アップロード時に **WebP 自動生成**（GD or Imagick 自動選択、Imagick 優先）
- 元 JPEG を**フォールバック保持**、`<picture>` 要素で配信
- subsizes（thumbnail / medium / large）も WebP 生成
- 5MB 超のアップは **Action Scheduler でバックグラウンド処理**（タイムアウト回避）
- 失敗時は `_agent_neo_webp_status: failed` を attachment meta に記録、admin 通知
- WP CLI: `wp agent-neo media regenerate-webp --all` で既存メディアの一括変換
- agent-api（`POST /agent-neo/v1/media/upload`）も同一変換パイプラインを通過
- 既に WebP の場合は二重変換せずスキップ
- GIF アニメは変換せず警告通知

### 1.2 データ統一・連携最適化（Automation SEO v2 連携の核）

すべての変更可能データは **JSON で統一**。Automation SEO v2 が「引き出しやすく拡張しやすい」ことを設計上の最上位制約として扱う（v2 は AGENT NEO を中央データハブとして利用する想定）。

#### JSON 統一の範囲

| 領域 | JSON 化 |
|---|---|
| デザイントークン | design-tokens.json |
| HP/LP/BLP 構造 | home-blueprint.json / lp-blueprint.json / blp-blueprint.json |
| ブロック設定 | block-registry.json + block.json |
| パッケージ機能フラグ | package.matrix.json |
| AI 操作スキーマ | agent-actions.schema.json |
| トラッキング契約 | tracking-context-v2.json（seo-tool-connector 互換） |
| ブロックコンテンツ | post_content（Gutenberg JSON）+ block_meta（jsonb）|
| variant 構成 | variants 配列（JSON）|
| 移行プレビュー差分 | JSON Patch（RFC 6902）|

独自バイナリ形式・シリアライズ形式は**使わない**。永続化も WP post_meta + jsonb（custom table）で統一。

#### Automation SEO v2 連携最適化（拡張性ファースト）

v2 が AGENT NEO から「引き出す」操作を**先回りして API 設計**:

| 機能 | エンドポイント / 仕様 | v2 への利益 |
|---|---|---|
| **bulk read** | `GET /agent-neo/v1/posts?since=<ts>` | 差分取得で全件 polling 不要 |
| **sparse fieldset** | `?fields=id,title,blocks,seo_meta` | 必要列のみ返却、転送量削減 |
| **ETag / If-None-Match** | 条件付き GET | v2 キャッシュ効率化 |
| **JSON Patch 出力** | `GET /agent-neo/v1/posts/<id>/diff?from=<ts>` | v2 が差分取り込みしやすい |
| **outbound webhook** | post/cta/section 変更を v2 にプッシュ | v2 polling 削減 |
| **vector-friendly export** | `GET /agent-neo/v1/posts/<id>/markdown` | v2 の embedding 生成用 plain text |
| **schema versioning** | `Accept: application/vnd.agent-neo.v1+json` | v2 が破壊的変更を検知できる |
| **batch write** | `PATCH /agent-neo/v1/batch` | v2 が一括変更を 1 リクエストで完了 |

#### v2 DB スキーマとの直接マッピング

v2 の `system_design_max/overall/db_schema.md` で定義された主要テーブルと AGENT NEO の構造を 1:1 対応:

| v2 テーブル | AGENT NEO 側の対応 |
|---|---|
| `WORDPRESS_CONNECTIONS` | site_token / site_id（seo-tool-connector 経由）|
| `WP_PAGES` | post_id / template / page_type を露出 |
| `WP_PAGE_SECTIONS` | section_id / section_type を全ブロックで自動付与 |
| `SECTION_METRICS_DAILY` | seo-tool-connector が集計、AGENT NEO は section_id を提供するだけ |
| `GENERATED_ARTICLES` | cms_post_id ↔ post_id の bidirectional マッピング、article_id を post_meta で保持 |
| `TRACKING_EVENTS` (jsonb data) | event payload は JSON、agent-neo の dispatch も同じ shape |

v2 が「クロール → 5D クラスタリング → 推論 → AGENT NEO に書き戻し」のループを高速で回せるよう、**AGENT NEO 側で v2 の都合を吸収**する設計を採用。

#### 拡張性の確保

- 新しい block type 追加時に v2 の DB スキーマ変更不要（block_meta の jsonb で吸収）
- 新しい SNS / 計測サービス追加時も adapter で吸収（コア API 変更なし）
- agent-neo/v1 の破壊的変更は最低 6 ヶ月のデプリケート期間 + v2 で agent-neo/v2 を併走可能
- 第三者開発者が AGENT NEO 用 plugin を作る際も同じ JSON 契約 + REST API を利用可能（オープン拡張）

### 1.3 AI 自律最適化機構（killer feature・継続価値の核）

AGENT NEO の核は「AI が記事を書ける」ではなく **「AI が継続的にコンテンツを部分最適化し、AB テストで勝者を採用していく」** ループ。これが回ることで、買い切りテーマでありながら**使うほど CV が上がる継続価値**を提供する。

#### 4 つの能力

##### 1. 部分更新性（Partial Update）
- すべてのブロックが**安定した block_id**（コンテンツ変更で ID が変わらない）を持つ
- block 単位の CRUD（フル投稿書き換え不要）
- idempotency-key ヘッダで同一操作の再実行を吸収
- `PATCH /agent-neo/v1/posts/<id>/blocks/<block_id>` で 1 ブロックだけ更新

##### 2. H2 単位 LLM 編集
- 各 H2 セクションが**自動的に addressable**（`section_id` 付与）
- セクション = 開始 H2 + 次の H2 までのコンテンツ範囲
- AI は単一セクションを **rewrite / expand / summarize / translate / restructure** 可能
- セクション単位の **dryRun**（差分プレビュー）+ **diff review** + **rollback**
- セクション単位の version 履歴（最新 N 版を保持、いつでも復元可）

##### 3. 要素差し替え機構（Element Swap）

| 要素 | 安定 ID | swap 内容 |
|---|---|---|
| 内部リンク | `link_id` | **テキスト保持・URL のみ差替え** |
| CTA | `cta_id` | CTA ブロック全体を別の cta_id に差替え |
| バナー | `banner_id` | バナーブロック全体を差替え |
| 画像 | `media_id` | `<picture>` の WebP/JPEG ペアを差替え |
| LP | `blueprint_id` | LP blueprint 全体（全 12 セクション）を別 blueprint に差替え |
| セクションパーツ | `reusable_part_id` | 法人版の再利用パーツを別パーツに差替え |

すべて **安定 ID 経由で個別 swap 可能**。AI は計測データを見て「この記事の CTA-A は CTR が低い → CTA-B に差替え」を自動実行。

##### 4. AI 自律 A/B テスト

AI 介入のフルループ:

```
[AI 提案] variant 候補生成 (LLM プロンプトで A/B/C ... 案出力)
    ↓
[自動配信] variant を seo-tool-connector の variant_id 経由で配信
    ↓
[計測] tracking_event API で impression / click / conversion を記録
    ↓
[統計判定] サンプルサイズ閾値到達 + 統計的有意差 → 勝者決定
    ↓
[自動採用] 勝者を default に昇格 / loser を archive
    ↓
[次サイクル] 勝者を base にさらに variant 生成（継続最適化）
```

AGENT NEO はこの自律ループの**実行基盤**を提供。実際の「最適化」自体は Automation SEO の LLM が判断（連携前提）。

人間介入ポイント:
- 法人版: variant 提案を **承認制**（編集者・承認者ロールで gate 可能）
- 個人版: 全自動（小規模・低リスク前提）
- 緊急停止: 任意のタイミングで `wp agent-neo ab-test stop --post_id=X` で停止可能

#### 競合との差別化

| | SWELL / JIN:R / AFFINGER | AGENT NEO |
|---|---|---|
| AI で記事を書ける | △（外部プラグイン経由） | ✓（Automation SEO 連携） |
| **block 単位の AI 編集** | ✗ | **✓** |
| **H2 単位の AI 編集** | ✗ | **✓** |
| **要素差し替え（CTA/banner/link）by ID** | ✗ | **✓** |
| **AI 自律 A/B テスト** | ✗（手動 A/B ツールはあり） | **✓** |
| 継続価値（買い切り後の CV 改善） | △（人間が手動改善） | **✓**（AI が自動改善） |

**この 4 能力が AGENT NEO 法人版 ¥98,000 の正当化の核**。「買い切りなのに使うほど CV が上がる」は他社にない継続価値。

### 1.4 AI フリーフォーム HTML/CSS ブロック（固定パーツ拡充戦略からの脱却）

JIN:R/AFFINGER 路線の「固定パーツをひたすら増やす」戦略は時代遅れ。AI が任意の HTML/CSS を生成できる現代では、**「テーマは安全な canvas を提供、AI が最適なパーツを毎回生成」** が正解。

#### コンセプトの転換

```
[従来テーマの戦略]
  テーマが 50 個のブロックを提供
       ↓
  ユーザーが 5 個選んで使う、残り 45 個は使われない肥大化要因
       ↓
  デザイン変更にはテーマアップデート待ち or 自前 PHP 改造

[AGENT NEO の戦略]
  テーマは「安全な canvas + 計測フレーム」を提供
       ↓
  AI が記事/サイト/サービスに最適化された HTML/CSS を生成
       ↓
  即時適用、無限カスタマイズ
       ↓
  AI モデル進化 → AGENT NEO サイトも自動進化（テーマ改修不要）
```

#### 安全性との両立（fail-safe 設計）

「自由」と「安全」の両立は以下で実現:

| 制約 | 仕組み |
|---|---|
| **XSS 防止** | `wp_kses` 拡張 allowlist で sanitize。`<script>` 禁止 / `on*=` 属性禁止 / `javascript:` および `data:` URL 制限（画像 base64 は許可）/ inline event handler 禁止 |
| **CSS 漏洩防止** | 全 selector を `.agent-neo-ai-block-{block_id}` でプレフィックス自動付与（CSS Modules 風）。または Shadow DOM オプション（厳密分離が必要な場合）|
| **a11y 必達** | axe-core で WCAG 2.2 AA 自動チェック、alt 必須、heading 階層検証、コントラスト比検証 |
| **性能予算遵守** | HTML/CSS バイト数を page_type 予算にカウント、超過時 dryRun で警告、apply ブロック |
| **安定 anchor 保護** | block wrapper の `data-agent-section-id` / `cta_id` / `variant_id` は AI が変更不可（システム自動付与）。内部の HTML/CSS のみ自由 |
| **プロンプトインジェクション防止** | コメント形式の system 命令（`<!-- ignore previous instructions -->` 等）を sanitize で除去 |
| **JS は禁止** | フリーフォームブロック内に JS は書けない。動的挙動が必要なら別途 block 化（block.json で性能宣言済みの JS のみ） |

#### 2 モード提供

| モード | 用途 | 操作フロー |
|---|---|---|
| **ガイドモード**（推奨）| AI が prompt から生成 | プロンプト入力 → Automation SEO 経由で AI 生成 → dryRun プレビュー → 検証パイプライン通過 → 承認 → 適用 |
| **フリーモード**（上級者）| 直接 HTML/CSS 編集 | エディタで直書き → 保存時に sanitize/scope/a11y/budget 検証 → 違反時 警告/ブロック |

#### 再利用パターン化

完成した AI 生成 HTML/CSS は **reusable-part CPT** に保存可能:
- 同サイト内で再利用
- 法人版: service_id 連動でサービス別分類（「サービス A の Hero」「サービス B の CTA」）
- 法人版: 編集者・承認者 ロールで承認フロー（パターン化前に承認 gate）

#### 編集領域は Slot で制限（自由 ≠ 無秩序）

完全 free-form は「Hero に CTA を置けない」「ナビが消える」等の構造破綻を招く。Blueprint が **named slots** を定義し、各 slot に制約を持たせる:

```
LP Blueprint 例:
  [Header — locked]
  [Hero slot — AI 編集可 / cta_id 必須 / max 10KB CSS]
  [Problem slot — AI 編集可 / テキスト中心 / max 5KB CSS]
  [Solution slot — AI 編集可]
  ...
  [Pricing slot — AI 編集可 / 構造化データ必須]
  [CTA slot — AI 編集可 / cta_id 必須 / Sticky 候補]
  [Footer — locked]
```

各 slot の constraint 仕様:

| 制約 | 内容 |
|---|---|
| `allowed_blocks` | 配置可能 block type の allowlist |
| `max_html_kb` / `max_css_kb` | バイト数上限（page_type 予算と連動） |
| `required_attributes` | 必須 data 属性（CTA slot なら cta_id 必須） |
| `page_type` | この slot が有効なページ種別 |
| `editable` | true / false（locked slot は AI 編集不可） |
| `must_contain` | このタイプ要素を最低 1 つ持つこと（CTA slot なら button 必須等） |

これで「自由」と「構造の正しさ」を両立。Slot は blueprint.json で定義、AI は slot 内のみ書き換え可能。

#### 競合との根本的な差別化

| 戦略 | SWELL | JIN:R | AFFINGER | AGENT NEO |
|---|---|---|---|---|
| 固定パーツ拡充 | ✓（中） | ✓（多） | ✓（多） | ✗（最小限のみ） |
| **AI フリーフォーム HTML/CSS** | ✗ | ✗ | ✗ | **✓** |
| **テーマ更新なしでデザイン進化** | ✗ | ✗ | ✗ | **✓** |
| **AI モデル進化に自動追従** | ✗ | ✗ | ✗ | **✓** |

訴求材料: 「他社テーマは買った瞬間が機能のピーク。AGENT NEO は AI が賢くなるたびにサイトも進化する初の WP テーマ。」

### 1.5 外部エディタアクセス制御（デフォルト閉鎖 + 有料 Bridge Plugin）

外部 AI エディタ（**Claude Computer Use / Codex CLI / Cursor / Cline / Continue**等）からの**直接書き込みはデフォルト拒否**。

#### 許可される Write 経路（2 つのみ）
1. **agent-neo/v1** — AGENT NEO 自身（管理画面 + Tier 1 サンドボックス）
2. **aseo/v1** — Automation SEO 連携

それ以外（外部 AI エディタ / 自前スクリプト / wp/v2 直接の構造的書き込み等）は拒否。

#### 拒否する理由

| 観点 | 内容 |
|---|---|
| 品質劣化 | 外部エディタは slot 制約 / page_type 予算 / 検証パイプライン を認識しない → ランダムに構造を壊す |
| CV 防衛 | 第一原理 3「結果を届ける」を担保するには編集経路の絞り込みが必須 |
| セキュリティ | 外部エディタは個別の attack surface（認証 / rate limit / 監査が別系統）|
| 持続可能性 | LLM 課金経路（aseo/v1 / S1 / Phase 2 Credits）を回避されると AGENT NEO 経済が成立しない |

#### Open Editor Bridge Plugin（別売・月額サブスク必須）

外部エディタを使いたいユーザー向けの**有料アドオン**:

```
Open Editor Bridge Plugin
  価格: 月額固定（¥3,000-5,000/月想定）
  ├─ Whitelisted External Editors:
  │     Claude Computer Use / Codex CLI / Cursor / Cline / Continue / 自前 OAuth 申請
  ├─ Bridge の役割:
  │     外部エディタの書き込み → AGENT NEO 検証パイプライン強制通過
  │     （sanitize / CSS scope / a11y / budget / anchor 保護 / slot 制約）
  └─ 月額固定の理由:
        各外部エディタ統合は個別保守コスト（API 変更追従、互換性テスト、サポート）
        単発購入では外部エディタの仕様変更で破綻
        サブスク前提でしか開発リソースを継続投入できない
```

#### 訴求のロジック

```
「他社テーマは Web エディタ系プラグインで誰でも書き込めて壊しやすい。
 AGENT NEO は AI 時代の品質を守るため、編集経路を 2 系統に絞った。
 それ以上の自由度が必要な上級者は Bridge Plugin（月額）で開放できる。
 強制ではなく選択肢として提供する。」
```

### 1.6 サンドボックス環境（2 ティア + Write Authority Lock）

**主用途: HP / LP / 固定ページのデザイン編集サンドボックス**

AI フリーフォーム HTML/CSS / slot-based blueprint / 要素 swap / A/B variant 等は**プレビュー検証が必須**。全機能を AGENT NEO に持たせると複雑化するため、Automation SEO との **責務分担で 2 ティア構成**。

#### コンテンツ種別ごとの編集経路

| コンテンツ | ステークス | 更新頻度 | 編集経路 | サンドボックス |
|---|---|---|---|---|
| **HP** | 高（ブランド入口）| 低 | Tier 1 / Tier 2 必須 | ✅ 必須 |
| **LP** | 高（CV 装置）| 中 | Tier 1 / Tier 2 必須 | ✅ 必須 |
| **固定ページ**（about / contact / pricing 等）| 中-高 | 低-中 | Tier 1 / Tier 2 | ✅ 必須 |
| **記事 / BLP**（テキスト中心）| 低-中 | 高 | aseo/v1 直接 PATCH or Tier 1 軽量 | ⚪ 任意 |
| **アーカイブ / カテゴリ**（自動生成）| 低 | リアルタイム | サンドボックス対象外 | ✗ 不要 |

→ **HP/LP/固定ページに限ってサンドボックス必須化**、記事は軽量経路で済ませる。

#### Tier 1: AGENT NEO 内蔵ライトサンドボックス（standalone 用）

WP 内完結:
- 各投稿に preview meta `_agent_neo_preview_content`
- preview URL token: `/?p=123&agent-neo-preview=<token>`
- agent-neo/v1 PATCH で apply（preview → production）
- block-level version 履歴は最新 N 版

| 用途 | 特性 |
|---|---|
| 個人版・BYOK ユーザー・小規模・単独編集 | 軽量・WP 標準 revision + 少量 jsonb meta |
| Automation SEO 不要で動作 | AGENT NEO 単独で完結 |

#### Tier 2: Automation SEO 側ヘビーサンドボックス（フル機能）

v2 PostgreSQL で:
- multi-version time-machine（5〜N preview branches 並行）
- A/B variant 並行管理 + 計測
- AI 自律最適化ループの orchestration
- 多投稿の協調的最適化
- Migration Plan B の AI 再構築サンドボックス
- 確定時に aseo/v1 → agent-neo/v1 PATCH で AGENT NEO 反映

| 用途 | 特性 |
|---|---|
| 法人版・本格運用・チーム編集 | 既存 v2 インフラ活用（PostgreSQL + Redis + ML feedback） |
| Automation SEO サブスク必須 | スケール・並行性・履歴の充実 |

#### Write Authority Lock（法人版オプション）

法人版の管理画面で **「Automation SEO Only Mode」ON**:
- Tier 1（AGENT NEO 内蔵編集）を無効化
- 全編集が aseo/v1 経由に強制
- WP 管理画面の編集 UI もロック（Automation SEO 連携メッセージのみ表示）

| 用途 | 価値 |
|---|---|
| コンプライアンス重視 | 編集ログの一元化・監査の容易化 |
| 編集権限の中央集権 | 「記事の更新は Automation SEO のみ」を強制 |

#### 責務境界の最終形

```
AGENT NEO（テーマ + Companion Plugin）:
  ├─ レンダリング層
  ├─ 安全な canvas + slot-based blueprint
  ├─ Tier 1 ライトサンドボックス
  ├─ agent-neo/v1 REST API（Read 全開放、Write は両 Tier 受付）
  ├─ ページタイプ別性能予算 enforce
  ├─ 検証パイプライン（sanitize / scope / a11y / budget / anchor 保護）
  └─ 計測 ID 提供（cta_id / section_id / variant_id 等）

Automation SEO（外部システム）:
  ├─ AI ブレイン（LLM ルーター / 5D クラスタリング / ML feedback）
  ├─ Tier 2 ヘビーサンドボックス（multi-version / time-machine）
  ├─ AI 自律最適化ループの orchestration
  ├─ A/B variant 並行管理 + 統計判定
  ├─ 複数投稿の協調的最適化
  ├─ Migration Plan B の AI 再構築
  └─ aseo/v1 REST API
```

通信:
- Read: AGENT NEO 露出 ↔ Automation SEO 引き出し
- Write: Tier 1 = AGENT NEO 単独 / Tier 2 = Automation SEO 経由
- Lock: 法人版「Automation SEO Only Mode」で Tier 1 無効化可能

### 1.7 販売寄与モジュール強化（CV 直結ブロック群の充実）

第一原理 3「結果（CV）を届けるテーマ」の具体化。CV に直接寄与するブロック・モジュールを**意図的に厚く実装**する。

#### 個人版（出口クリック最大化）

| 既存 | 追加強化 |
|---|---|
| Review / Ranking / Comparison / Pros Cons / Ad Tag / Affiliate CTA / 商品カード / AdSense 配置 | **Sticky/Floating CTA**（スクロール追従・常時表示）/ **Exit-intent CTA**（離脱検知 - 慎重設計、UX 配慮）/ **Smart product recommendation**（記事文脈→関連商品自動表示）/ **AI suggested CTA**（記事内容を AI 解析 → 最適 CTA 自動配置）/ **Click heatmap data 収集**（将来の最適化材料）/ **Pickup banner**（季節/特集強調）|

#### 法人版（リード獲得最大化）

| 既存（LP 12 セクション）| 追加強化 |
|---|---|
| Hero / Problem / Consequence / Solution / Feature / Benefit / Use Case / Proof / Comparison / Pricing / FAQ / CTA + 問い合わせフォーム | **Sticky CTA**（LP 常時表示）/ **Multi-step form**（フィールド分割で心理的負担軽減）/ **Click-to-call** / **Click-to-chat** / **LINE 友だち追加ブロック**（日本特有の最強 CTA）/ **Resource DL** / **Webinar registration** / **Demo booking** / **Trust badges**（導入ロゴ・認証マーク・受賞）/ **Social proof**（お客様の声・星評価）/ **Conditional CTA**（utm / リファラ / 時間帯で variant 出し分け）|

#### 共通: AI 主導 CV 最適化

- **AI suggested CTA**: 記事内容を Automation SEO 経由で解析 → 最適 CTA を自動配置（aseo/v1 連携）
- **Personalized hero**: 流入元別（utm / リファラ / 検索キーワード）に Hero variant 出し分け
- **Smart internal linking**: 関連コンテンツ → CTA への誘導強化、CTR 最大化のリンク提案
- **Dynamic pricing display**: キャンペーン期間連動の価格表示・年額割引バッジ

#### 全 CV モジュール共通の必須仕様

1. **計測 ID 必須**: cta_id / offer_id / variant_id / section_id を全 CV ブロックで必須化（自律 A/B テスト連携）
2. **配置パターン両対応**: above-the-fold / inline / sticky / floating / exit-intent
3. **CV 設計監査**（L5 Visual Refinement 時必須）: cta.overload / proof.too_late / hero.vague / comparison.missing_basis / affiliate.disclosure_weak 等の UI risk を自動検出
4. **認知バイアスパターンライブラリ**: scarcity（残り個数）/ authority（受賞・専門家推薦）/ social proof（利用者数）/ commitment（無料お試し）/ reciprocity（無料資料）を再利用パターンとして提供

#### 競合との差別化

| 項目 | SWELL/JIN:R/AFFINGER | AGENT NEO |
|---|---|---|
| アフィリ系ブロック | ✓（基本セット） | ✓ + AI 自動配置 + heatmap |
| LP セクション | ✓ または ✗ | ✓ + 12 標準 + Sticky + Multi-step + LINE |
| **AI suggested CTA** | ✗ | **✓** |
| **Personalized hero** | ✗ | **✓** |
| **CV 設計監査機能** | ✗ | **✓**（UI risk 自動検出） |
| **認知バイアスパターン化** | ✗（個別実装） | **✓**（再利用パターンライブラリ） |

### 1.8 SNS 連携（基準レベルの必須要素）

**LLMO・分散 SEO 時代において SNS 連携なしのテーマはあり得ない。** SNS 経由トラフィック・ブランドメンション・社会的シグナルは検索評価と AI 検索エンジンの引用に直接寄与する。

#### 必須対応 SNS

| SNS | 主要機能 | 採用パッケージ |
|---|---|---|
| **X（旧 Twitter）** | シェア / 自動投稿 / 埋め込み / OGP / Twitter Card | 個人・法人共通 |
| **Instagram** | シェア / 自動投稿（Meta Graph API）/ 埋め込み / プロフィール表示 | 個人・法人共通 |
| **Threads** | シェア / 自動投稿（Meta Threads API）/ 埋め込み | 個人・法人共通 |
| **LINE** | シェア（友だち追加）/ LINE 公式アカウント連携 / LINE Login（法人版） | 個人共通 + 法人版で深い統合 |

#### オプション対応（Phase 2）
Facebook / Pinterest / YouTube / TikTok — adapter 経由で拡張可能

#### 機能セット

1. **シェアボタン**: JS 軽量・非同期。ボタン未クリック時は何も読み込まない
2. **自動投稿**: 投稿公開時に指定 SNS へ送信。成功/失敗を post meta に記録、失敗時リトライ
3. **埋め込み**: oEmbed 標準で X/Insta/Threads 投稿を記事内に展開（lazy load）
4. **プロフィール表示**: SNS アカウントを footer/sidebar に自動配置（OGP メタと整合）
5. **SNS フィード ウィジェット**: 最新投稿表示（オプション、lazy load 必須）
6. **法人版限定**:
   - **LINE 公式アカウント深い統合**: 友だち追加リンクブロック、Webhook で LINE 経由 CV 計測、Bot シナリオ連携
   - **SNS 経由 CV 計測**: utm + 自社計測 ID で SNS チャネル別の CV 寄与を可視化
   - **A/B テスト連携**: SNS 流入時の variant 出し分け
   - **複数アカウント管理**: 複数 SNS アカウント（複数サービス対応）

#### 認証情報管理

- 各 SNS API キーは Companion Plugin の管理画面で設定
- API キーは **WP options に暗号化保存**（`openssl_encrypt` + `AUTH_KEY` ベース）
- 管理画面ダッシュボードで連携状態（接続中 / トークン期限 / 失敗履歴）を可視化
- 法人版は権限分離（API キー閲覧は管理者のみ、自動投稿は編集者以上）

**この 4 原理に反する機能追加は L2/L3/L4 のいずれのゲートでもブロックする。**

#### 第一原理 4 の運用ルール（非 AI ユーザビリティ）

| 領域 | ルール |
|---|---|
| **AI 連携の前提条件化禁止** | 「AI 連携前提」「Automation SEO 必須」のような前提条件化は禁止。AI なしで動く基本機能を必ず併設 |
| **オプトイン方式** | インストール直後は AI 連携 OFF。ユーザーが管理画面で API キー登録 / Automation SEO 接続を**明示実行**で初めて有効化 |
| **WP 標準エディタ完全互換** | ブロックエディタ・古い Classic Editor 両対応。AGENT NEO 独自ブロックは WP 標準ブロックのスーパーセット |
| **段階的開示** | 設定画面は「基本（5 項目以下）→ 詳細（必要時に展開）」の階層。デフォルトで動く |
| **日本語 UI 完全対応** | 全管理画面・通知・エラーメッセージは日本語。英語は二次（i18n） |
| **AI 不在時の UI** | AI 提案・自律最適化・ジャーナル等の UI は **AI 連携 OFF 時に非表示**（混乱回避） |
| **CV モジュールは AI 不在でも動作** | Sticky CTA / フォーム / Trust badges 等の CV ブロックはテーマ単独で動作 |
| **手動運用可能性** | 管理者が WP エディタで普通に記事更新・LP 編集できる経路を常に確保 |
| **非 IT 系ユーザー向けチュートリアル** | 動画・スクリーンショット付きマニュアルを日本語で提供（販売パッケージに同梱）|

差別化の柱: 競合テーマ（SWELL/JIN:R 含む）が「機能追加 → 設定肥大化 → JS/CSS 肥大化」の循環に陥っている中で、AGENT NEO は「**機能を絞る → 高速 → CV が上がる**」の循環を選択する。

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

## 6. 謌ｦ逡･繝ｻ雋ｩ螢ｲ繝輔ぃ繝阪Ν

### 6.0 陬ｽ蜩√ヵ繧｡繝阪Ν・域里蟄假ｼ・
```text
辟｡譁吶・繝ｩ繧ｰ繧､繝ｳ・医・繝ｩ繝ｳ A 讖滓｢ｰ螟画鋤・・   竊・繝ｦ繝ｼ繧ｶ繝ｼ閾ｪ霄ｫ縺後・繝ｩ繝ｳ繧帝∈謚・繝励Λ繝ｳ B・・I 繝輔Ν繧ｻ繝・ヨ繧｢繝・・・・ S1 繧ｵ繝ｼ繝薙せ隱ｲ驥・   竊・蜿励￠逧ｿ: AGENT NEO 繝・・繝橸ｼ亥倶ｺｺ ﾂ･19,800 / 豕穂ｺｺ ﾂ･98,000・・霑ｽ蜉蜿守寢: Automation SEO 蛻･隱ｲ驥・/ S1 / 譛画侭繧｢繝峨が繝ｳ / 蠕馴㍼隱ｲ驥・```

**謌ｦ逡･逧・葎縺ｿ:**
- 辟｡譁吶・繝ｩ繝ｳ A = lead magnet縲る俣蜿｣譛螟ｧ蛹悶・wp.org 蜈ｬ蠑上ョ繧｣繝ｬ繧ｯ繝医Μ邨檎罰縺ｮ閾ｪ辟ｶ豬∝・
- 繝励Λ繝ｳ B = AI 蜀肴ｧ狗ｯ峨・迢ｬ閾ｪ萓｡蛟､縺ｧ隱ｲ驥托ｼ・utomation SEO 縺ｮ譌｢蟄・LLMRouter 繧呈ｴｻ逕ｨ・・- 蛟倶ｺｺ/豕穂ｺｺ繝・・繝・= 蜿励￠逧ｿ縲ゅい繝・・繧ｰ繝ｬ繝ｼ繝峨ヱ繧ｹ縺ｧ LTV 諡｡螟ｧ
- 繝ｦ繝ｼ繧ｶ繝ｼ縺瑚・蛻・〒繝励Λ繝ｳ繧帝∈謚・竊・謚ｼ縺怜｣ｲ繧頑─繧ｼ繝ｭ繝ｻ邏榊ｾ玲─縺ｮ鬮倥＞雉ｼ雋ｷ菴馴ｨ・- 繝・・繝槫挨繧｢繝繝励ち荳崎ｦ・竊・蜈ｨ繝・・繝槫叉蟇ｾ蠢懊・螳｣莨昴′謇薙※繧・+ 髢狗匱繝ｻ菫晏ｮ医さ繧ｹ繝亥炎貂・
### 6.1 繧ｰ繝ｭ繝ｼ繧ｹ謌ｦ逡･縺ｮ蝓ｺ譛ｬ蜴溷援

AGENT NEO 縺ｮ雋ｩ螢ｲ縺ｯ縲√ユ繝ｼ繝槭ｒ螢ｲ繧九◆繧√・蛻･繧ｵ繧､繝医ｒ菴懊ｋ讒矩縺ｧ縺ｯ縺ｪ縺上・*閾ｪ遉ｾ繧ｵ繧､繝医◎縺ｮ繧ゅ・繧・AGENT NEO 縺ｧ遞ｼ蜒阪＆縺帙√◎縺ｮ驕狗畑謌先棡繧定ｲｩ螢ｲ譬ｹ諡縺ｫ螟峨∴繧区ｧ矩**繧呈治繧九り・遉ｾ繧ｵ繧､繝医・縲瑚｣ｽ蜩∫ｴｹ莉九・繝ｼ繧ｸ縲阪〒縺ｯ縺ｪ縺上、GENT NEO 縺ｮ HP/LP/BLP/SEO/LLMO/險域ｸｬ/A-B 謾ｹ蝟・ｒ譛ｬ逡ｪ縺ｧ蝗槭＠縺ｦ縺・ｋ **live demo** 縺ｧ縺ゅｋ縲・
縺薙・蜑肴署縺ｧ縺ｯ縲∬・遉ｾ繧ｵ繧､繝医′蜊∝・縺ｫ蜍輔°縺ｪ縺・憾諷九〒蜈医↓雋ｩ螢ｲ繧貞ｧ九ａ繧句愛譁ｭ縺ｯ謌千ｫ九＠縺ｪ縺・ゅ↑縺懊↑繧峨√し繧､繝医′豁｢縺ｾ繧九％縺ｨ縺ｯ蜷梧凾縺ｫ雋ｩ螢ｲ蟆守ｷ壹→險ｼ諡縺ｮ蛛懈ｭ｢繧呈э蜻ｳ縺励∵､懃ｴ｢豬∝・繝ｻX 豬∝・繝ｻ蝠上＞蜷医ｏ縺帙・繝・・繝櫁ｳｼ蜈･縺悟酔譎ゅ↓蠑ｱ縺上↑繧九◆繧√〒縺ゅｋ縲ゅ＠縺溘′縺｣縺ｦ Phase 1 繝ｭ繝ｼ繝ｳ繝√・縲梧怙蛻昴↓蜃ｺ縺帙ｋ蠖｢縲阪〒縺ｯ縺ｪ縺上・*閾ｪ遉ｾ繧ｵ繧､繝医ｒ譛ｬ逡ｪ驕狗畑縺励↑縺後ｉ雋ｩ螢ｲ髢句ｧ九〒縺阪ｋ螳梧・繧ｻ繝・ヨ**繧呈э蜻ｳ縺吶ｋ縲・
譛ｬ遶縺ｧ縺ｯ萓｡譬ｼ謌ｦ逡･繧・ｫｶ蜷域ｯ碑ｼ・・蜀肴軸縺帙★縲∵ｵ∝・繝ｻ驕狗畑繝ｻ閾ｪ遉ｾ螳溯ｨｼ繝ｫ繝ｼ繝励↓髯仙ｮ壹＠縺ｦ霑ｽ險倥☆繧九ゆｾ｡譬ｼ縺ｮ螯･蠖捺ｧ縺ｯ [萓｡譬ｼ謌ｦ逡･縺ｨ螢ｲ繧後ｋ逅・罰](../../隗｣譫舌Ξ繝昴・繝・09-萓｡譬ｼ謌ｦ逡･縺ｨ螢ｲ繧後ｋ逅・罰.md)縲∝ｸょｴ繝昴ず繧ｷ繝ｧ繝ｳ縺ｯ [遶ｶ蜷医ユ繝ｼ繝樒ｷ丞粋隧穂ｾ｡縺ｨ蟶ょｴ繝昴ず繧ｷ繝ｧ繝ｳ](../../隗｣譫舌Ξ繝昴・繝・26-遶ｶ蜷医ユ繝ｼ繝樒ｷ丞粋隧穂ｾ｡縺ｨ蟶ょｴ繝昴ず繧ｷ繝ｧ繝ｳ.md) 繧貞盾辣ｧ縺吶ｋ縲・
### 6.2 豬∝・繝√Ε繝阪Ν險ｭ險・
AGENT NEO 縺ｮ蛻晄悄繧ｰ繝ｭ繝ｼ繧ｹ縺ｯ **SEO 繧剃ｸｻ霆ｸ縲々 繧剃ｸｻ蜉・SNS** 縺ｨ縺吶ｋ縲ゆｸ｡閠・ｒ蛻・屬縺帙★縲、utomation SEO 縺瑚ｨ倅ｺ狗函謌舌ヾEO 繝｡繧ｿ譛驕ｩ蛹悶∝・譛牙ｰ守ｷ夐°逕ｨ縲∵隼蝟・ｨ育判縺ｾ縺ｧ繧剃ｸ騾｣縺ｮ驕狗畑縺ｨ縺励※謇ｱ縺・１hase 1 縺ｯ蜈ｱ譛牙ｰ守ｷ・OGP/X Card/莠ｺ謇区兜遞ｿ・・P 驕狗畑閠・′蜈ｱ譛牙ｰ守ｷ壹°繧・X 縺ｸ謇句虚縺ｧ謚慕ｨｿ縺吶ｋ驕狗畑・峨ｒ謇ｱ縺・々 閾ｪ蜍墓兜遞ｿ縺ｯ Phase 2 縺ｸ騾√ｋ縲・
| 繝√Ε繝阪Ν | 蠖ｹ蜑ｲ | 荳ｻ縺ｪ繝壹・繧ｸ/譁ｽ遲・| AGENT NEO 縺悟酔譎ゅ↓遉ｺ縺吩ｾ｡蛟､ |
|---|---|---|---|
| SEO | 荳ｻ霆ｸ豬∝・ | 險倅ｺ九√い繝ｼ繧ｫ繧､繝悶。LP縲∵ｳ穂ｺｺ迚・LP縲∫ｧｻ陦後・繝ｩ繧ｰ繧､繝ｳ LP縲・｡ｧ螳｢莠倶ｾ・| 繝壹・繧ｸ繧ｿ繧､繝怜挨諤ｧ閭ｽ莠育ｮ励ヾEO Core縲´LMO縲∬ｨ域ｸｬ縲∝・驛ｨ蟆守ｷ・|
| X | 蛻晞滓僑謨｣縺ｨ邯咏ｶ壽磁轤ｹ | 陬ｽ蜩∵峩譁ｰ縲∵ｩ溯・隗｣隱ｬ縲∬ｨ倅ｺ句・髢九∝ｼ慕畑 RT縲∽ｺ倶ｾ狗ｴｹ莉・| Phase 1 縺ｯ OGP/X Card縲∝・譛牙ｰ守ｷ壹∽ｺｺ謇区兜遞ｿ縲１hase 2 縺ｧ閾ｪ蜍墓兜遞ｿ縺ｸ諡｡蠑ｵ |
| Threads | Phase 1 縺ｮ雋ｩ菫・ｸｻ謌ｦ蝣ｴ縺ｧ縺ｯ縺ｪ縺・/ Phase 2 capability | 遏ｭ譁・ｦ∫ｴ・・幕逋ｺ騾ｲ謐励∝・謗ｲ蟆守ｷ・| Meta 邉ｻ繝√Ε繝阪Ν諡｡蠑ｵ縺ｮ adapter 險ｭ險医ｒ菫晄戟縺励∵ｷｱ縺・°逕ｨ縺ｯ Phase 2 |
| LINE 蜈ｬ蠑・| Phase 1 縺ｮ雋ｩ菫・ｸｻ謌ｦ蝣ｴ縺ｧ縺ｯ縺ｪ縺・/ Phase 2 capability | 豕穂ｺｺ蜷代￠蝠上＞蜷医ｏ縺帙∬ｳ・侭隲区ｱゅ∫ｶ咏ｶ壽磁轤ｹ | 豕穂ｺｺ迚医・豺ｱ縺・ｵｱ蜷医√メ繝｣繝阪Ν蛻･ CV 險域ｸｬ縺ｯ Phase 2 |
| 譛画侭蠎・相 | 蛻晄悄縺ｯ謗｡繧峨↑縺・| 讀懃ｴ｢蠎・相縲ヾNS 蠎・相 | 險ｴ豎よ枚繧医ｊ蜈医↓縲∬・遉ｾ驕狗畑螳溽ｸｾ繧定塘遨阪☆縺ｹ縺肴ｮｵ髫・|
| PR Times | 蛻晄悄縺ｯ謗｡繧峨↑縺・| 繝励Ξ繧ｹ驟堺ｿ｡ | 荳驕取ｧ豬∝・繧医ｊ縲∵､懃ｴ｢雉・肇縺ｨ閾ｪ遉ｾ驕狗畑險ｼ諡縺ｮ闢・ｩ阪ｒ蜆ｪ蜈・|

**SEO・井ｸｻ霆ｸ・・*

- 險倅ｺ九・髮・ｮ｢髱｢縲。LP 縺ｯ險倅ｺ区ｵ∝・縺九ｉ縺ｮ騾∝ｮ｢髱｢縲∵ｳ穂ｺｺ LP 縺ｯ雉ｼ蜈･髱｢縲∝倶ｺｺ迚医・蜿守寢蛹悶ヶ繝ｭ繝・け縺ｯ險倅ｺ・繧｢繝ｼ繧ｫ繧､繝紋ｸ翫・雉ｼ蜈･蟆守ｷ壹ｒ諡・＞縲∝・繝壹・繧ｸ繧定・遉ｾ繝・・繝樔ｸ翫〒邨ｱ荳驕狗畑縺吶ｋ縲・- 繧ｭ繝ｼ繝ｯ繝ｼ繝画婿驥昴・ 4 螻､縺ｧ險ｭ險医☆繧九・  - 蝠城｡瑚ｵｷ轤ｹ: `WordPress AI 繝・・繝杼縲～AI 縺ｧ驕狗畑 WP 繝・・繝杼
  - 陬ｽ蜩√き繝・ざ繝ｪ襍ｷ轤ｹ: `WP 繝・・繝・Automation SEO`縲～AI 驕狗畑蝙・WP 繝・・繝杼
  - 謖・錐襍ｷ轤ｹ: `AGENT NEO`
  - 豈碑ｼ・蟆主・襍ｷ轤ｹ: `豕穂ｺｺ LP WordPress AI`縲～繧｢繝輔ぅ繝ｪ繧ｨ繧､繝・WordPress AI 繝・・繝杼
- 隨ｬ荳蜴溽炊 2 縺ｮ縲後・繝ｼ繧ｸ繧ｿ繧､繝怜挨諤ｧ閭ｽ莠育ｮ励阪・ SEO 隧穂ｾ｡縺ｨ逶ｴ謗･縺､縺ｪ縺後ｋ縲りｨ倅ｺ・BLP/繧｢繝ｼ繧ｫ繧､繝悶・讀懃ｴ｢豬∝・縺ｮ荳ｻ謌ｦ蝣ｴ縺ｧ縺ゅｊ縲´P 逕ｨ縺ｮ驥阪＞ JS 繧・､夜Κ繧ｹ繧ｯ繝ｪ繝励ヨ縺瑚ｨ倅ｺ矩擇縺ｸ貍上ｌ繧九→縲´CP/INP/CLS 縺梧が蛹悶＠縺ｦ豬∝・蝓ｺ逶､閾ｪ菴薙′蠑ｱ繧九・GENT NEO 縺ｯ `page_type` 蜊倅ｽ阪・莠育ｮ怜・髮｢縺ｧ縲√％縺ｮ貍上ｌ繧呈ｧ矩逧・↓髦ｲ縺舌ょ盾辣ｧ: [L0 莨∫判譖ｸ ﾂｧ1.1.1](L0-planning.md)縲ーL1 隕∽ｻｶ螳夂ｾｩ REQ-F-029 / REQ-NF-001f](../requirements/L1-requirements.md)
- LLMO 縺ｯ蟆・擂豬∝・縺ｮ荳区髪縺医→縺励※謇ｱ縺・Ａanswer unit`縲～evidence graph`縲～citation anchor`縲～AI visibility policy` 繧貞ｙ縺医◆繝壹・繧ｸ縺ｯ縲・壼ｸｸ讀懃ｴ｢縺縺代〒縺ｪ縺・AI 讀懃ｴ｢縺ｮ蠑慕畑髱｢縺ｧ繧ょ━菴阪↓縺ｪ繧翫ｄ縺吶＞縲ょ盾辣ｧ: [LLMO譎ゆｻ｣縺ｮ繝・・繝櫁ｨｭ險磯㍾隕∬ｦｳ轤ｹ](../../隗｣譫舌Ξ繝昴・繝・24-LLMO譎ゆｻ｣縺ｮ繝・・繝櫁ｨｭ險磯㍾隕∬ｦｳ轤ｹ.md)

**X・井ｸｻ蜉・SNS・・*

- 閾ｪ遉ｾ繧｢繧ｫ繧ｦ繝ｳ繝医・縲悟相遏･蟆ら畑縲阪〒縺ｯ縺ｪ縺上∬｣ｽ蜩∵峩譁ｰ縲∵ｩ溯・縺ｮ菴ｿ縺・婿縲∵隼蝟・燕蠕後・蟾ｮ蛻・・｡ｧ螳｢莠倶ｾ九∝・髢玖ｨ倅ｺ九・蜀埼・菫｡繧呈球縺・・- 險倅ｺ句・髢区凾縺ｮ X 蟆守ｷ壹・閾ｪ遉ｾ驕狗畑縺ｧ蜈郁｡悟茜逕ｨ縺吶ｋ縲１hase 1 縺ｯ **蜈ｱ譛牙ｰ守ｷ壹＾GP/X Card縲∽ｺｺ謇区兜遞ｿ** 繧呈怙蜆ｪ蜈医↓縺励・*閾ｪ蜍墓兜遞ｿ縺ｯ Phase 2** 縺ｫ騾√ｋ縲り､・焚繧｢繧ｫ繧ｦ繝ｳ繝育ｮ｡逅・∵ｳ穂ｺｺ蜷代￠豺ｱ縺・ｵｱ蜷医ヾNS API 隱崎ｨｼ諠・ｱ邂｡逅・ｂ Phase 2 縺ｫ蜷ｫ繧√ｋ縲・- 蠑慕畑 RT 縺ｯ繧ｳ繝溘Η繝九ユ繧｣謗･邯壹↓菴ｿ縺・ょｯｾ雎｡縺ｯ WP 髢狗匱閠・√い繝輔ぅ繝ｪ繧ｨ繧､繧ｿ繝ｼ縲∝宛菴應ｼ夂､ｾ縲∵ｳ穂ｺｺ繝槭・繧ｱ諡・ｽ薙ヾEO 螳溷漁閠・→縺励√瑚・遉ｾ縺ｧ蝗槭＠縺滄°逕ｨ邨先棡縲阪ｒ隧ｱ鬘後・荳ｭ蠢・↓鄂ｮ縺上・- OGP/X Card 縺ｯ雋ｩ菫・判蜒上〒縺ｯ縺ｪ縺上∬ｨ倅ｺ九・LP繝ｻ莠倶ｾ九＃縺ｨ縺ｫ險ｴ豎ゅｒ蛻・ｊ譖ｿ縺医ｋ豬∝・蜿｣縺ｨ縺励※謇ｱ縺・９ 荳翫〒縺ｮ隕九∴譁ｹ繧・AGENT NEO 縺ｮ螳溯｣・刀雉ｪ縺ｮ荳驛ｨ縺ｧ縺ゅｋ縲・- Threads / LINE 蜈ｬ蠑上・雋ｩ菫・ｸｻ謌ｦ蝣ｴ縺ｨ縺励※縺ｯ Phase 1 縺ｧ菴ｿ繧上↑縺・′縲∬｣ｽ蜩・capability 縺ｨ縺励※縺ｯ菫晄戟縺吶ｋ縲５hreads 縺ｮ豺ｱ縺・°逕ｨ縲´INE 蜈ｬ蠑城｣謳ｺ縲∬､・焚繧｢繧ｫ繧ｦ繝ｳ繝育ｮ｡逅・√メ繝｣繝阪Ν蛻･ CV 險域ｸｬ縺ｯ Phase 2 縺ｧ諡｡蠑ｵ縺吶ｋ縲ょ盾辣ｧ: [nsrm.yaml](../../.helix/nsrm.yaml)縲ーL1 隕∽ｻｶ螳夂ｾｩ](../requirements/L1-requirements.md)

**蛻晄悄縺ｫ謗｡繧峨↑縺・メ繝｣繝阪Ν**

- 譛画侭蠎・相縺ｯ蛻晄悄縺ｮ荳ｻ謌ｦ蝣ｴ縺ｫ縺励↑縺・ら炊逕ｱ縺ｯ縲∝ｺ・相譛驕ｩ蛹悶ｈ繧雁・縺ｫ閾ｪ遉ｾ繧ｵ繧､繝医・閾ｪ辟ｶ豬∝・繝ｻCV繝ｻA-B 謾ｹ蝟・ョ繝ｼ繧ｿ繧定ｲｩ螢ｲ譬ｹ諡縺ｨ縺励※闢・ｩ阪☆繧区婿縺後∬｣ｽ蜩√→雋ｩ螢ｲ縺ｮ荳菴馴°逕ｨ縺ｫ謨ｴ蜷医☆繧九◆繧√〒縺ゅｋ縲・- PR Times 繧ょ・譛溘・荳ｻ謌ｦ蝣ｴ縺ｫ縺励↑縺・ら炊逕ｱ縺ｯ縲∽ｸ驕取ｧ縺ｮ蛻ｰ驕斐ｈ繧頑､懃ｴ｢雉・肇繝ｻ謖・錐讀懃ｴ｢繝ｻX 邯咏ｶ壽磁轤ｹ縺ｮ譁ｹ縺悟・蛻ｩ逕ｨ諤ｧ縺碁ｫ倥￥縲√ラ繝・げ繝輔・繝・ぅ繝ｳ繧ｰ讒矩縺ｨ逶ｸ諤ｧ縺瑚憶縺・◆繧√〒縺ゅｋ縲・
### 6.3 閾ｪ遉ｾ驕狗畑繝輔Ο繝ｼ・・utomation SEO 繝ｫ繝ｼ繝暦ｼ・
**Phase 1 繝ｫ繝ｼ繝・*

```text
閾ｪ遉ｾ繧ｵ繧､繝茨ｼ・GENT NEO 譛ｬ逡ｪ驕狗畑・・        [REQ-F-001 / REQ-F-003 / REQ-F-010 / REQ-F-042]
  竊・險倅ｺ九ロ繧ｿ謚ｽ蜃ｺ・・utomation SEO LLMRouter・・  竊・險倅ｺ狗函謌・竊・AGENT NEO 縺ｫ POST・・gent-neo/v1・・[REQ-F-002 / REQ-F-003 / REQ-F-025 / REQ-F-011]
  竊・險倅ｺ狗ｷｨ髮・譖ｴ譁ｰ・・EQ-F-041 縺ｮ霆ｽ驥冗ｵ瑚ｷｯ + REQ-F-021 縺ｮ驛ｨ蛻・峩譁ｰ・・[REQ-F-021 / REQ-F-041]
  竊・險倅ｺ句・髢九・OGP 逕滓・                         [REQ-F-011 / REQ-F-017 / REQ-F-018 / REQ-F-029]
  竊・seo-tool-connector 縺ｧ險域ｸｬ 竊・Automation SEO 縺ｫ髮・ｨ・[REQ-F-006 / REQ-F-007 / REQ-F-026 / REQ-F-027]
  竊・WP 驕狗畑閠・′蜈ｱ譛牙ｰ守ｷ壹°繧・X 縺ｸ莠ｺ謇区兜遞ｿ       [REQ-F-018 遽・峇蜀・
  竊・SEO 豬∝・邯咏ｶ壼ｼｷ蛹厄ｼ・nswer unit / evidence graph・・[REQ-F-011 / REQ-NF-017]
  竊・CV・亥倶ｺｺ迚郁ｳｼ蜈･ / 豕穂ｺｺ迚亥撫縺・粋繧上○ / Open Editor Bridge 逕ｳ霎ｼ・・[REQ-F-004 / REQ-F-005 / REQ-F-012 / REQ-F-013 / REQ-F-016 / REQ-F-030 / REQ-F-031]
  竊・鬘ｧ螳｢陦悟虚繝・・繧ｿ 竊・谺｡繧ｵ繧､繧ｯ繝ｫ縺ｮ險倅ｺ九ロ繧ｿ縺ｸ繝輔ぅ繝ｼ繝峨ヰ繝・け
```

**Phase 2 諡｡蠑ｵ繝ｫ繝ｼ繝・*

```text
Phase 1 繝ｫ繝ｼ繝・  竊・H2 邱ｨ髮・                                    [REQ-F-022]
  竊・隕∫ｴ swap                                   [REQ-F-023]
  竊・閾ｪ蠕・A/B 縺ｧ CTA / Hero 謾ｹ蝟・                [REQ-F-024]
  竊・X 閾ｪ蜍墓兜遞ｿ縲∬､・焚繧｢繧ｫ繧ｦ繝ｳ繝育ｮ｡逅・√メ繝｣繝阪Ν蛻･ CV 險域ｸｬ縺ｸ諡｡蠑ｵ [REQ-F-018 諡｡蠑ｵ / REQ-F-019 / REQ-F-020]
  竊・蜍晁・治逕ｨ / 髱樊治逕ｨ variant 蛛懈ｭ｢
```

縺薙・繝ｫ繝ｼ繝励・驥崎ｦ∫せ縺ｯ縲・*驕狗畑陦檎ぜ縺昴・繧ゅ・縺瑚｣ｽ蜩√ョ繝｢縺ｫ縺ｪ縺｣縺ｦ縺・ｋ**縺薙→縺ｧ縺ゅｋ縲りｦ玖ｾｼ縺ｿ螳｢縺ｯ隱ｬ譏手ｳ・侭縺ｧ縺ｯ縺ｪ縺上∬・遉ｾ繧ｵ繧､繝井ｸ翫〒縲瑚ｨ倅ｺ九′蜈ｬ髢九＆繧後ｋ縲阪瑚ｨ域ｸｬ縺輔ｌ繧九阪傾 縺ｨ讀懃ｴ｢縺ｸ豬√ｌ繧九阪梧ｬ｡縺ｮ謾ｹ蝟・∈謌ｻ繧九阪→縺・≧騾｣邯夐°霆｢繧定ｦ九ｋ縲１hase 1 縺ｧ縺ｯ雋ｩ螢ｲ蟆守ｷ壹→驕狗畑邯咏ｶ壽ｧ繧堤､ｺ縺励￣hase 2 縺ｧ AI 閾ｪ蠕区隼蝟・→閾ｪ蜍墓兜遞ｿ縺ｾ縺ｧ諡｡蠑ｵ縺吶ｋ縲ゅ◎縺ｮ縺溘ａ縲、utomation SEO 縺ｨ AGENT NEO 縺ｮ雋ｬ蜍吝・諡・・隕九○譁ｹ縺ｧ繧ゅ≠繧九・utomation SEO 縺ｯ蛻､譁ｭ縺ｨ謾ｹ蝟・ｨ育判縲、GENT NEO 縺ｯ譛ｬ逡ｪ蜿肴丐縺ｨ螳牙・縺ｪ螳溯｡悟渕逶､繧呈球縺・・
縺ｾ縺溘√％縺ｮ繝ｫ繝ｼ繝励・蜊倥↑繧矩寔螳｢縺ｧ縺ｯ縺ｪ縺上∬ｲｩ螢ｲ譬ｹ諡縺ｮ闢・ｩ阪〒繧ゅ≠繧九ゅ←縺ｮ CTA 縺梧款縺輔ｌ縺溘°縲√←縺ｮ LP 縺・CV 縺励◆縺九√←縺ｮ BLP 縺瑚・辟ｶ豬∝・繧堤佐蠕励＠縺溘°繧定・遉ｾ縺ｧ謖√※繧九◆繧√∝霧讌ｭ雉・侭繧・LP 譁・ｨ縺ｫ縲瑚・遉ｾ縺ｧ蝗槭＠縺ｦ縺・ｋ謨ｰ蟄励阪ｒ逶ｴ謗･蜿肴丐縺ｧ縺阪ｋ縲・
### 6.4 繧ｵ繧､繝・= 雋ｩ螢ｲ LP 縺ｮ讒矩

閾ｪ遉ｾ繧ｵ繧､繝医・隍・焚繝壹・繧ｸ縺ｮ蟇・○髮・ａ縺ｧ縺ｯ縺ｪ縺上∬｣ｽ蜩∵ｩ溯・縺ｮ螳溯ｨｼ髱｢繧偵・繝ｼ繧ｸ蜊倅ｽ阪〒蛻・球縺吶ｋ讒矩縺ｫ縺吶ｋ縲・
| 繝壹・繧ｸ | 荳ｻ蟇ｾ雎｡ | 雋ｩ螢ｲ荳翫・蠖ｹ蜑ｲ | 螳溯ｨｼ縺吶ｋ AGENT NEO 讖溯・ |
|---|---|---|---|
| HP | 蜈ｨ險ｪ蝠剰・| 繝悶Λ繝ｳ繝峨ワ繝悶∝ｰ守ｷ壼・驟・| HP blueprint縲・ntity Graph縲√・繝ｼ繧ｸ繧ｿ繧､繝怜挨諤ｧ閭ｽ莠育ｮ励∬ｨ域ｸｬ |
| 繧ｵ繝ｼ繝薙せ LP | 豕穂ｺｺ隕玖ｾｼ縺ｿ螳｢ | 蝠上＞蜷医ｏ縺帙・雉・侭隲区ｱら佐蠕・| LP blueprint縲，TA縲√ヵ繧ｩ繝ｼ繝縲」ariant縲、-B 謾ｹ蝟・|
| 遘ｻ陦後・繝ｩ繧ｰ繧､繝ｳ LP | 譌｢蟄・WP 驕句霧閠・| 辟｡譁吝ｰ守ｷ壹〕ead magnet | 遘ｻ陦悟ｰ守ｷ壹∵ｯ碑ｼ・ｨｴ豎ゅ∵ｮｵ髫主ｰ守ｷ・|
| 蛟倶ｺｺ迚亥ｰ守ｷ夲ｼ郁ｨ倅ｺ・/ 繧｢繝ｼ繧ｫ繧､繝厄ｼ・| 繧｢繝輔ぅ繝ｪ繧ｨ繧､繧ｿ繝ｼ | 蛟倶ｺｺ迚郁ｳｼ蜈･蟆守ｷ・| 蜿守寢蛹悶ヶ繝ｭ繝・け縲∝膚蜩√き繝ｼ繝峨・未騾｣險倅ｺ矩∝ｮ｢縲，TR 險域ｸｬ |
| 豕穂ｺｺ迚・LP | 莨∵･ｭ繝ｻ莉｣逅・ｺ・| 豕穂ｺｺ迚郁ｳｼ蜈･/S1 蟆守ｷ・| 豕穂ｺｺ HP/LP/BLP縲∝撫縺・粋繧上○縲∽ｿ｡鬆ｼ險ｴ豎ゅ〉isk guard |
| 髢狗匱繝悶Ο繧ｰ | SEO 豬∝・螻､ | 謖・錐讀懃ｴ｢縺ｨ菫｡鬆ｼ蠖｢謌・| 險倅ｺ狗函謌舌？2 蜊倅ｽ肴峩譁ｰ縲´LMO縲∝・髢九せ繝翫ャ繝励す繝ｧ繝・ヨ |
| 鬘ｧ螳｢莠倶ｾ・| 蟆主・讀懆ｨ主ｱ､ | 譬ｹ諡謠千､ｺ縲∵ｯ碑ｼ・・豎ｺ繧∵焔 | evidence graph縲∽ｺ倶ｾ区ｧ矩蛹悶，V 險域ｸｬ縲∝ｼ慕畑縺励ｄ縺吶＞隕∫ｴ・|

閾ｪ遉ｾ繧ｵ繧､繝井ｸ翫↓縺ｯ縲後％縺ｮ繧ｵ繧､繝郁・菴薙′ AGENT NEO 縺ｧ蜍輔＞縺ｦ縺・ｋ縲阪％縺ｨ繧呈・遉ｺ縺吶ｋ蟆守ｷ壹ｒ謖√◆縺帙ｋ縲ょ呵｣懊・ `Built with AGENT NEO` 繝舌ャ繧ｸ縲√ヵ繝・ち繝ｼ陦ｨ險倥∵ｩ溯・隱ｬ譏弱・繝ｼ繧ｸ縺ｸ縺ｮ蟆守ｷ壹∬ｩｲ蠖薙・繝ｼ繧ｸ縺ｧ菴ｿ縺｣縺ｦ縺・ｋ讖溯・縺ｮ豕ｨ險倥〒縺ゅｋ縲ょ腰縺ｪ繧九Ο繧ｴ謗ｲ蜃ｺ縺ｧ縺ｯ縺ｪ縺上√後％縺ｮ LP 縺ｯ豕穂ｺｺ LP blueprint縲阪後％縺ｮ繝悶Ο繧ｰ縺ｯ BLP 蟆守ｷ夊ｾｼ縺ｿ縺ｧ驕狗畑縲阪後％縺ｮ莠倶ｾ九・繝ｼ繧ｸ縺ｯ answer unit/evidence graph 繧剃ｽｿ逕ｨ縲阪・繧医≧縺ｫ縲√・繝ｼ繧ｸ縺ｨ讖溯・繧堤ｵ舌・莉倥￠縺ｦ隕九○繧九・
閾ｪ遉ｾ繧ｵ繧､繝医・險域ｸｬ繝・・繧ｿ縺ｯ雋ｩ螢ｲ險ｴ豎ゅ↓繧ょ茜逕ｨ縺吶ｋ縲ょ呵｣懊・縲∝諺蜷榊喧縺励◆ live counter縲∝・髢九ム繝・す繝･繝懊・繝峨∬ｨ倅ｺ・LP 縺ｮ謾ｹ蝟・ｱ･豁ｴ縲∫峩霑第悄髢薙・ CTA 螟牙喧縲´CP 荳ｭ螟ｮ蛟､縲∝・髢区悽謨ｰ縲∵峩譁ｰ鬆ｻ蠎ｦ縺ｧ縺ゅｋ縲ゅ◆縺縺励・｡ｧ螳｢蜷阪・蛟句挨 CV繝ｻ蜀・Κ驕狗畑諠・ｱ繧貞性繧蜈ｬ髢九・驕ｿ縺代∝・髢狗ｯ・峇縺ｯ PO/TL/豕募漁縺ｧ蝗ｺ螳壹☆繧九・
**蜈ｬ髢区欠讓吶・繝ｪ繧ｷ繝ｼ**

| 蜈ｬ髢矩・岼 | 蜈ｬ髢句庄蜷ｦ | 蛹ｿ蜷榊喧蜊倅ｽ・| 蜈ｬ髢矩≦蟒ｶ | 髮・ｨ磯明蛟､ | 菫晏ｭ俶悄髢・| 譬ｹ諡繝ｭ繧ｰ隕∝凄 | 蜷梧э蜿門ｾ苓ｦ∝凄 | 陦ｨ遉ｺ雋ｬ莉ｻ閠・|
|---|---|---|---|---|---|---|---|---|
| 閾ｪ遉ｾ繧ｵ繧､繝・LCP 荳ｭ螟ｮ蛟､ | 譚｡莉ｶ莉倥″蜈ｬ髢・| 繧ｵ繧､繝亥・菴薙∪縺溘・繝壹・繧ｸ繧ｿ繧､繝怜腰菴阪・髮・ｨ亥､ | 24h 莉･荳企≦蟒ｶ蜈ｬ髢具ｼ域耳螂ｨ縲∬ｦ∫｢ｺ隱搾ｼ・| 隕∫｢ｺ隱・| 隕∫｢ｺ隱・| 隕・| 隕∫｢ｺ隱・| PO / 豕募漁 / 驕狗畑雋ｬ莉ｻ閠・|
| 逶ｴ霑・30 譌･縺ｮ險倅ｺ句・髢区悽謨ｰ | 蜈ｬ髢句庄 | 譌･谺｡縺ｾ縺溘・騾ｱ谺｡髮・ｨ・| 24h 莉･荳企≦蟒ｶ蜈ｬ髢具ｼ域耳螂ｨ縲∬ｦ∫｢ｺ隱搾ｼ・| 隕∫｢ｺ隱・| 隕∫｢ｺ隱・| 隕・| 隕∫｢ｺ隱・| PO / 驕狗畑雋ｬ莉ｻ閠・|
| 逶ｴ霑・30 譌･縺ｮ譖ｴ譁ｰ鬆ｻ蠎ｦ | 蜈ｬ髢句庄 | 譌･谺｡縺ｾ縺溘・騾ｱ谺｡髮・ｨ・| 24h 莉･荳企≦蟒ｶ蜈ｬ髢具ｼ域耳螂ｨ縲∬ｦ∫｢ｺ隱搾ｼ・| 隕∫｢ｺ隱・| 隕∫｢ｺ隱・| 隕・| 隕∫｢ｺ隱・| PO / 驕狗畑雋ｬ莉ｻ閠・|
| CTA 謾ｹ蝟・ｱ･豁ｴ縺ｮ蜑榊ｾ悟ｷｮ蛻・ｼ域焚蛟､縺ｮ縺ｿ繝ｻ鬘ｧ螳｢迚ｹ螳壹↑縺暦ｼ・| 譚｡莉ｶ莉倥″蜈ｬ髢・| 繝壹・繧ｸ鄒､縺ｾ縺溘・ CTA 遞ｮ蛻･蜊倅ｽ阪・髮・ｨ亥ｷｮ蛻・| 24h 莉･荳企≦蟒ｶ蜈ｬ髢具ｼ域耳螂ｨ縲∬ｦ∫｢ｺ隱搾ｼ・| 隕∫｢ｺ隱・| 隕∫｢ｺ隱・| 隕・| 隕∫｢ｺ隱・| PO / 豕募漁 / 驕狗畑雋ｬ莉ｻ閠・|
| 繝・・繝樒ｴｯ險郁ｲｩ螢ｲ謨ｰ・井ｻｮ・・| 譚｡莉ｶ莉倥″蜈ｬ髢・| SKU 蜊倅ｽ阪・邏ｯ險亥､ | 譌･谺｡莉･荳翫・驕・ｻｶ蜈ｬ髢具ｼ郁ｦ∫｢ｺ隱搾ｼ・| 隕∫｢ｺ隱・| 隕∫｢ｺ隱・| 隕・| 隕∫｢ｺ隱・| PO / 雋ｩ螢ｲ雋ｬ莉ｻ閠・|
| S1 邏ｯ險域｡井ｻｶ謨ｰ・井ｻｮ・・| 譚｡莉ｶ莉倥″蜈ｬ髢・| 譛域ｬ｡縺ｾ縺溘・蝗帛濠譛滄寔險・| 譌･谺｡莉･荳翫・驕・ｻｶ蜈ｬ髢具ｼ郁ｦ∫｢ｺ隱搾ｼ・| 隕∫｢ｺ隱・| 隕∫｢ｺ隱・| 隕・| 隕∫｢ｺ隱・| PO / 雋ｩ螢ｲ雋ｬ莉ｻ閠・|
| 鬘ｧ螳｢蜷阪・蛟句挨 CV繝ｻ蛟句挨繝ｦ繝ｼ繧ｶ繝ｼ陦悟虚 | 蜈ｬ髢倶ｸ榊庄 | 隧ｲ蠖薙↑縺・| 隧ｲ蠖薙↑縺・| 隧ｲ蠖薙↑縺・| 隧ｲ蠖薙↑縺・| 隕・| 隕・| PO / 豕募漁 |

蜈ｬ髢句宛邏・・莉･荳九〒蝗ｺ螳壹☆繧九・
- **蛟倶ｺｺ諠・ｱ菫晁ｭｷ豕・*: 蛟倶ｺｺ繧堤音螳壹〒縺阪ｋ諠・ｱ繝ｻ遶ｯ譛ｫ諠・ｱ縺ｮ蜈ｬ髢九ｒ遖∵ｭ｢縺励∬・遉ｾ繧ｵ繧､繝郁ｨｪ蝠剰・・陦悟虚縺ｯ髮・ｨ亥､縺ｮ縺ｿ繧呈桶縺・・- **譎ｯ陦ｨ豕・*: 縲悟ｮ溽ｸｾ縲阪悟柑譫懊崎｡ｨ迴ｾ縺ｫ縺ｯ譬ｹ諡繝ｭ繧ｰ縺ｨ邂怜・譚｡莉ｶ繧剃ｽｵ險倥＠縲∬ｪ・､ｧ陦ｨ迴ｾ繧帝∩縺代ｋ縲・- **迚ｹ蝠・ｳ・*: 雋ｩ螢ｲ螳溽ｸｾ繧定｡ｨ遉ｺ縺吶ｋ蝣ｴ蜷医・縲∬｡ｨ遉ｺ雋ｬ莉ｻ閠・・｣邨｡蜈医∝叙蠑墓擅莉ｶ繧呈・險倥☆繧九・- **GDPR / EU 險ｪ蝠剰・*: 蛟句挨霑ｽ霍｡繝・・繧ｿ繧・EU 蝓溷､悶し繝ｼ繝舌・縺ｧ蜃ｦ逅・☆繧句ｴ蜷医・縲…ookie consent 繧貞性繧蜷梧э蜿門ｾ礼ｵ瑚ｷｯ繧貞燕謠舌→縺吶ｋ縲・- **蜷梧э蜿門ｾ・*: 蜈ｬ髢句ｯｾ雎｡縺ｫ蜷ｫ繧√ｋ險域ｸｬ繝・・繧ｿ縺ｯ縲ゝoS / 繝励Λ繧､繝舌す繝ｼ繝昴Μ繧ｷ繝ｼ縺ｫ縲悟・髢玖ｨ域ｸｬ蜷梧э縲阪ｒ譏手ｨ倥＠縺溽ｯ・峇縺ｮ縺ｿ繧剃ｽｿ縺・・- **蜈ｬ髢矩≦蟒ｶ**: 繝ｪ繧｢繝ｫ繧ｿ繧､繝蜈ｬ髢九・驕ｿ縺代∵怙菴・24h 莉･荳翫・驕・ｻｶ蜈ｬ髢九ｒ蜴溷援縺ｨ縺吶ｋ縲よ怙邨ょ､縺ｯ PO 遒ｺ隱堺ｺ矩・→縺吶ｋ縲・
### 6.5 繝ｭ繝ｼ繝ｳ繝・・ｺ上・譁ｹ驥・
Q-002 縺ｮ蛻､譁ｭ譚先侭縺ｨ縺励※縲√Ο繝ｼ繝ｳ繝・・・閾ｪ遉ｾ繧ｵ繧､繝医・荳ｻ縺溘ｋ鬘斐▽縺阪→謨ｴ蜷医＆縺帙ｋ蠢・ｦ√′縺ゅｋ縲・
| 驕ｸ謚櫁い | 謨ｴ蜷医＠繧・☆縺・し繧､繝亥ワ | 謌千ｫ区擅莉ｶ | 蜷代＞縺ｦ縺・ｋ蜑肴署 |
|---|---|---|---|
| 蛟倶ｺｺ迚亥・陦・| 險倅ｺ・/ 繧｢繝ｼ繧ｫ繧､繝・荳ｭ蠢・√い繝輔ぅ繝ｪ繧ｨ繧､繝亥ｯ・ｊ | SEO 險倅ｺ玖ｳ・肇・郁ｨ倅ｺ・+ 繧｢繝ｼ繧ｫ繧､繝・+ 蝠・刀繧ｫ繝ｼ繝会ｼ峨ｒ蜴壹￥菴懊ｊ繧・☆縺・ょ倶ｺｺ迚医・蝗ｺ螳壹ユ繝ｳ繝励Ξ讒区・・・EQ-F-016・峨→蜿守寢蛹悶ヶ繝ｭ繝・け・・EQ-F-030・峨ｒ譌ｩ譛溘↓ live demo 縺ｨ縺励※隕九○繧峨ｌ繧九・| 豕穂ｺｺ蜷代￠鬮伜腰萓｡險ｴ豎ゅ・險ｼ諡縺悟ｾ後ｍ蛟偵＠縺ｫ縺ｪ繧九よｳ穂ｺｺ LP/BLP 縺ｮ險ｼ諡縺ｯ豕穂ｺｺ迚医Ο繝ｼ繝ｳ繝√∪縺ｧ菴懊ｌ縺ｪ縺・りｨ倅ｺ・/ 繧｢繝ｼ繧ｫ繧､繝悶ｒ蛻晄悄 IA 縺ｮ荳ｻ蠖ｹ縺ｫ鄂ｮ縺阪∵､懃ｴ｢雉・肇縺ｮ遨阪∩荳翫￡繧貞━蜈医☆繧句ｴ蜷医・|
| 豕穂ｺｺ迚亥・陦・| HP/LP 荳ｭ蠢・∝撫縺・粋繧上○繝ｻ雉・侭隲区ｱょｯ・ｊ | 閾ｪ遉ｾ繧ｵ繧､繝・= 雋ｩ螢ｲ LP = live demo 縺ｮ隲也炊縺梧怙繧ょｼｷ縺・るｫ伜腰萓｡險ｴ豎ゅ→ S1 蟆守ｷ壹′菴懊ｊ繧・☆縺・・| 險倅ｺ矩㍼逕｣縺縺代〒縺ｯ險ｼ譏弱＠縺ｫ縺上＞驕狗畑蜩∬ｳｪ繧ょ・縺ｫ豎ゅａ繧峨ｌ繧九よｳ穂ｺｺ LP 縺ｨ S1 蟆守ｷ壹ｒ閾ｪ遉ｾ繧ｵ繧､繝医・荳ｻ蠖ｹ縺ｫ鄂ｮ縺阪∬ｲｩ螢ｲ譬ｹ諡繧呈掠譛溘↓隕九○縺溘＞蝣ｴ蜷医・|
| 蜷梧凾繝ｭ繝ｼ繝ｳ繝・| HP/LP 縺ｨ險倅ｺ矩°逕ｨ繧貞酔譎ゅ↓蜴壹￥蝗槭☆ | SKU 髢薙・隱ｬ譏弱′荳雋ｫ縺励∝ｾ後〒 IA 繧堤ｵ・∩逶ｴ縺励↓縺上＞縲・| Phase 1 繝ｭ繝ｼ繝ｳ繝√そ繝・ヨ縺ｮ雋諡・′譛繧ょ､ｧ縺阪＞縲りｨｼ諡菴懊ｊ繧ゆｺ碁擇螻暮幕縺ｫ縺ｪ繧九ょ・譛・IA縲・°逕ｨ菴灘宛縲∝・髢区ｹ諡縺ｮ蟇・ｺｦ繧貞酔譎ゅ↓貅縺溘○繧句ｴ蜷医・|

荳願ｨ倥＞縺壹ｌ繧帝∈繧薙〒繧よ・遶九＠縺・ｋ縲よｱｺ螳夊ｦ∝屏縺ｯ縲瑚・遉ｾ繧ｵ繧､繝医・蛻晄悄 IA 繧呈ｳ穂ｺｺ LP 蟇・ｊ縺ｧ險ｭ險医☆繧九°縲∬ｨ倅ｺ・繧｢繝ｼ繧ｫ繧､繝門ｯ・ｊ縺ｧ險ｭ險医☆繧九°縲阪〒縺ゅｋ縲ょ燕閠・ｒ驕ｸ縺ｹ縺ｰ豕穂ｺｺ迚亥・陦後∝ｾ瑚・ｒ驕ｸ縺ｹ縺ｰ蛟倶ｺｺ迚亥・陦後′讖溯・繝ｻ險ｼ諡髱｢縺ｧ謨ｴ蜷医☆繧九ょ酔譎ゅΟ繝ｼ繝ｳ繝√・ Phase 1 繝ｭ繝ｼ繝ｳ繝√そ繝・ヨ縺ｮ螳梧・譚｡莉ｶ縺ｨ蜈ｬ髢区凾縺ｮ險ｼ諡蟇・ｺｦ繧剃ｺ碁擇蜷梧凾縺ｫ貅縺溘☆蠢・ｦ√′縺ゅｊ縲・°逕ｨ雋闕ｷ縺梧怙繧ょ､ｧ縺阪＞驕ｸ謚櫁い縺ｫ縺ｪ繧九よｱｺ螳壹・ PO 縺ｫ蟋斐・繧九・
### 6.6 Phase 1 繝ｭ繝ｼ繝ｳ繝√〒蠢・ｦ√↑讖溯・

迴ｾ陦・`nsrm.yaml` 縺ｮ `phase_1_mvp` 繧ｭ繝ｼ縺ｯ HELIX 讓呎ｺ門多蜷阪→縺励※谿狗ｽｮ縺輔ｌ縺ｦ縺・ｋ縺後∵悽莨∫判縺ｧ縺ｯ **Phase 1 繝ｭ繝ｼ繝ｳ繝√そ繝・ヨ** 縺ｨ隱ｭ縺ｿ譖ｿ縺医ｋ縲ら樟譎らせ縺ｮ 23 莉ｶ縺ｯ縲∬・遉ｾ繧ｵ繧､繝医ｒ譛ｬ逡ｪ驕狗畑縺励↑縺後ｉ雋ｩ螢ｲ髢句ｧ九☆繧玖ｦｳ轤ｹ縺ｧ繧ょ､ｧ遲九〒螯･蠖薙〒縺ゅｊ縲・*螟ｧ縺阪↑讖溯・霑ｽ蜉蟾ｮ蛻・・縺ｪ縺・*縲ゅ◆縺縺励∝腰縺ｫ縲瑚ｼ峨▲縺ｦ縺・ｋ縲阪□縺代〒縺ｪ縺上∬・遉ｾ驕狗畑繝ｫ繝ｼ繝励↓縺ｩ縺・柑縺上°繧呈・遉ｺ縺励※謇ｱ縺・ｿ・ｦ√′縺ゅｋ縲ゅ％縺薙↓縺ｯ `REQ-F-041`・郁ｨ倅ｺ狗ｷｨ髮・ｵ瑚ｷｯ・峨ｒ蜷ｫ繧縲・
| 繧ｰ繝ｫ繝ｼ繝・| 蟇ｾ雎｡ | 閾ｪ遉ｾ繧ｵ繧､繝磯°逕ｨ縺ｧ谺縺代※縺ｯ縺・￠縺ｪ縺・炊逕ｱ |
|---|---|---|
| 蝓ｺ逶､ | REQ-F-001 / REQ-F-002 / REQ-F-003 / REQ-F-025 | 繧ｵ繧､繝域悽菴薙ｒ AI 縺悟ｮ牙・縺ｫ隗ｦ繧後゛SON 螂醍ｴ・〒荳雋ｫ驕狗畑縺吶ｋ蜑肴署縺ｫ縺ｪ繧・|
| SEO/豬∝・ | REQ-F-011 / REQ-F-029 / REQ-F-017 | 讀懃ｴ｢荳ｻ謌ｦ蝣ｴ縺ｮ險倅ｺ・BLP/LP 縺ｮ陦ｨ遉ｺ蜩∬ｳｪ縺ｨ豬∝・蜩∬ｳｪ繧貞ｮ医ｋ |
| 險域ｸｬ/騾｣謳ｺ | REQ-F-006 / REQ-F-007 / REQ-F-026 / REQ-F-027 | 險域ｸｬ縺後↑縺代ｌ縺ｰ謾ｹ蝟・ｹ諡縺梧ｮ九ｉ縺壹、utomation SEO 縺ｨ雋ｩ螢ｲ險ｴ豎ゅ′縺､縺ｪ縺後ｉ縺ｪ縺・|
| 豕穂ｺｺ蟆守ｷ・| REQ-F-005 / REQ-F-012 / REQ-F-013 / REQ-F-031 | 閾ｪ遉ｾ繧ｵ繧､繝医ｒ豕穂ｺｺ蜷代￠雋ｩ螢ｲ LP 縺ｨ縺励※菴ｿ縺・ｴ蜷医・蠢・域ｧ区・ |
| 蛟倶ｺｺ蟆守ｷ・| REQ-F-004 / REQ-F-016 / REQ-F-030 | 閾ｪ遉ｾ繧ｵ繧､繝医ｒ險倅ｺ・繧｢繝ｼ繧ｫ繧､繝紋ｸｭ蠢・〒蝗槭☆蝣ｴ蜷医・蜿守寢蛹冶ｨｴ豎ゅ→騾∝ｮ｢髱｢縺ｫ縺ｪ繧・|
| 蜈ｬ髢句ｰ守ｷ・| REQ-F-018 | Phase 1 縺ｯ繧ｷ繧ｧ繧｢縲＾GP/X Card縲∽ｺｺ謇区兜遞ｿ縺ｮ謗･邯夐擇縺ｨ縺励※蠢・ｦ√り・蜍墓兜遞ｿ縺ｯ Phase 2 縺ｧ諡｡蠑ｵ縺吶ｋ |
| 蜿肴丐螳牙・諤ｧ | REQ-F-021 / REQ-F-041 / REQ-F-042 | 險倅ｺ区峩譁ｰ縲・Κ蛻・峩譁ｰ縲∝､夜Κ邱ｨ髮・宛蠕｡繧呈悽逡ｪ驕狗畑縺ｧ縺阪ｋ迥ｶ諷九↓縺吶ｋ |
| 雋ｩ螢ｲ繧ｬ繝舌リ繝ｳ繧ｹ | REQ-F-010 | SKU 蠅・阜縲∵ｨｩ髯仙｢・阜縲∬ｲｩ螢ｲ譚｡莉ｶ繧貞ｴｩ縺輔↑縺・|

閾ｪ遉ｾ繧ｵ繧､繝磯°逕ｨ隕也せ縺ｧ縺ｮ陬懆ｶｳ縺ｯ 2 轤ｹ縺ゅｋ縲・
1. 讖溯・ 23 莉ｶ縺ｫ蜉縺医※縲√Ο繝ｼ繝ｳ繝∝庄蜷ｦ縺ｯ髱樊ｩ溯・隕∵ｱゅ↓繧ゆｾ晏ｭ倥☆繧九ら音縺ｫ `REQ-NF-001f`・医・繝ｼ繧ｸ繧ｿ繧､繝怜挨諤ｧ閭ｽ莠育ｮ暦ｼ峨～REQ-NF-014`・・PI/閾ｪ蜍募喧螂醍ｴ・ｼ峨～REQ-NF-015`・・I驕狗畑諤ｧ/繧ｯ繝ｭ繝ｼ繝ｩ繝薙Μ繝・ぅ・峨～REQ-NF-017`・・LMO・峨～REQ-NF-018`・・EO/WP驕狗畑繝上じ繝ｼ繝臥ｮ｡逅・ｼ峨・縲∬・遉ｾ繧ｵ繧､繝医′縲悟｣ｲ繧句ｴ縲阪°縺､縲瑚ｨｼ諡縺ｮ蝣ｴ縲阪〒縺ゅｋ莉･荳翫∝ｮ溯ｳｪ逧・↓谺縺代※縺ｯ縺・￠縺ｪ縺・・2. X 蟆守ｷ壹・ Phase 驟榊・縺ｯ譛ｬ譖ｸ縺ｧ蝗ｺ螳壹☆繧九・*Phase 1 縺ｯ蜈ｱ譛牙ｰ守ｷ壹＾GP/X Card縲∽ｺｺ謇区兜遞ｿ縺ｮ縺ｿ**縲・*Phase 2 縺ｧ閾ｪ蜍墓兜遞ｿ縲∬､・焚繧｢繧ｫ繧ｦ繝ｳ繝育ｮ｡逅・´INE 豺ｱ縺・ｵｱ蜷医ヾNS API 隱崎ｨｼ諠・ｱ邂｡逅・∈諡｡蠑ｵ**縺吶ｋ縲・
### 6.7 繧ｰ繝ｭ繝ｼ繧ｹ KPI

謨ｰ蛟､逶ｮ讓吶・ PO 遒ｺ螳壻ｺ矩・→縺励※菫晉蕗縺励▽縺､縲∬ｨ域ｸｬ鬆・岼縺ｯ Phase 1 縺九ｉ螳夂ｾｩ縺励※縺翫￥縲・
| 螻､ | 謖・ｨ・| 逶ｮ逧・|
|---|---|---|
| 豬∝・ | 讀懃ｴ｢陦ｨ遉ｺ蝗樊焚縲∵､懃ｴ｢繧ｯ繝ｪ繝・け謨ｰ縲∽ｸｻ隕∵ｵ∝・繝壹・繧ｸ縲∵欠蜷肴､懃ｴ｢豬∝・ | SEO 荳ｻ謌ｦ蝣ｴ縺瑚ご縺｣縺ｦ縺・ｋ縺九ｒ隕九ｋ |
| 豬∝・ | X 繝輔か繝ｭ繝ｯ繝ｼ謨ｰ縲∵兜遞ｿ蛻ｰ驕斐√Μ繝ｳ繧ｯ繧ｯ繝ｪ繝・け縲∝ｼ慕畑 RT 謨ｰ | X 繧堤ｶ咏ｶ壽磁轤ｹ縺ｨ縺励※閧ｲ縺ｦ繧峨ｌ縺ｦ縺・ｋ縺九ｒ隕九ｋ |
| 蝗樣♀ | 險倅ｺ・PV縲。LP 蛻ｰ驕皮紫縲・未騾｣險倅ｺ矩・遘ｻ邇・√せ繧ｯ繝ｭ繝ｼ繝ｫ豺ｱ蠎ｦ | 險倅ｺ九°繧・LP 縺ｸ騾∝ｮ｢縺ｧ縺阪※縺・ｋ縺九ｒ隕九ｋ |
| 謾ｹ蝟・| CTA CTR縲」ariant 蜍晉紫縲∝・髢句ｾ後・謾ｹ蝟・渚譏蝗樊焚 | 閾ｪ遉ｾ驕狗畑繝ｫ繝ｼ繝励′豁｢縺ｾ縺｣縺ｦ縺・↑縺・°繧定ｦ九ｋ |
| 螟画鋤 | 繝・・繝櫁ｳｼ蜈･謨ｰ縲ヾ1 蝠上＞蜷医ｏ縺帶焚縲∬ｳ・侭隲区ｱよ焚縲＾pen Editor Bridge 逕ｳ霎ｼ謨ｰ | 雋ｩ螢ｲ謌先棡繧堤峩謗･貂ｬ繧・|
| AI/LLMO | answer unit 蛻ｰ驕斐、I 逕ｱ譚･蜿ら・縲∝ｼ慕畑讀懃衍縲、I 邨檎罰 CV | 蟆・擂豬∝・縺ｮ雉ｪ繧呈ｸｬ繧・|
| 驕狗畑 | 險倅ｺ句・髢区悽謨ｰ縲∵峩譁ｰ鬆ｻ蠎ｦ縲〉ollback 逋ｺ逕滓焚縲・囿螳ｳ蝗樊焚 | 驕狗畑邯咏ｶ壽ｧ縺ｨ蜩∬ｳｪ繧呈ｸｬ繧・|

譛ｪ豎ｺ莠矩・∈縺ｮ霑ｽ險俶｡・

| ID | 蜀・ｮｹ | 蛻､譁ｭ閠・| 譛滄剞 |
|---|---|---|---|
| Q-011 | 繧ｰ繝ｭ繝ｼ繧ｹ KPI 縺ｮ謨ｰ蛟､逶ｮ讓呻ｼ域､懃ｴ｢豬∝・縲々 隕乗ｨ｡縲∬ｨ倅ｺ・PV縲，V縲√ユ繝ｼ繝櫁ｲｩ螢ｲ謨ｰ縲ヾ1 蝠上＞蜷医ｏ縺帶焚・峨・遒ｺ螳・| PO | L2 髢句ｧ句燕 |

### 6.8 繝ｪ繧ｹ繧ｯ縺ｨ蟇ｾ蠢・
| 繝ｪ繧ｹ繧ｯ | 蠖ｱ髻ｿ | 蟇ｾ蠢懈婿驥・|
|---|---|---|
| 閾ｪ遉ｾ繧ｵ繧､繝磯囿螳ｳ | 繧ｵ繧､繝亥●豁｢ = 雋ｩ螢ｲ蛛懈ｭ｢ = 險ｼ諡蛛懈ｭ｢ | staging縲〉ollback縲”ealth check縲∝､門ｽ｢逶｣隕悶∵峩譁ｰ蜑榊ｾ後メ繧ｧ繝・け繧呈ｨ呎ｺ夜°逕ｨ縺ｫ蜈･繧後ｋ縲りｲｩ螢ｲ LP 縺ｨ險ｼ諡陦ｨ遉ｺ縺ｯ莠句燕繝薙Ν繝画ｸ医∩髱咏噪 HTML 縺ｸ蜊ｳ譎ょ・譖ｿ縺ｧ縺阪ｋ蟆守ｷ壹ｒ貅門ｙ縺吶ｋ |
| Automation SEO 髫懷ｮｳ | 謾ｹ蝟・Ν繝ｼ繝怜●豁｢縲∬ｨ倅ｺ倶ｾ帷ｵｦ蛛懈ｭ｢縲・寔險磯≦蟒ｶ | AGENT NEO 蜊倡峡縺ｧ縺ｮ蜈ｬ髢・譖ｴ譁ｰ邨瑚ｷｯ繧堤ｶｭ謖√＠縲∵焔蜍暮°逕ｨ縺ｸ蛻・崛蜿ｯ閭ｽ縺ｫ縺吶ｋ |
| X API / 莉墓ｧ伜､画峩 | 謚慕ｨｿ蟆守ｷ壹・蛛懈ｭ｢縲∬ｪ崎ｨｼ螟ｱ蜉ｹ縲・°逕ｨ雋闕ｷ蠅・| adapter 螻､縺ｧ蜷ｸ蜿弱＠縲∝・譛牙ｰ守ｷ壹→ OGP 繧呈怙菴弱Λ繧､繝ｳ縺ｨ縺励※谿九☆縲よｷｱ縺・ｵｱ蜷医・ Phase 2 蛛ｴ縺ｧ諡｡蠑ｵ邂｡逅・☆繧・|
| SEO 繧ｹ繝代Β蛻､螳・| 讀懃ｴ｢隧穂ｾ｡菴惹ｸ九√ヶ繝ｩ繝ｳ繝画ｯ謳・| AI 螟ｧ驥冗函謌舌ｒ逶ｮ逧・喧縺帙★縲∵ｹ諡縲∵峩譁ｰ逅・罰縲∫屮菫ｮ縲∝・髢句愛譁ｭ繧剃ｼｴ縺・刀雉ｪ繧ｲ繝ｼ繝医ｒ Automation SEO 蛛ｴ縺ｧ謖√▽ |
| 蜈ｬ髢九ム繝・す繝･繝懊・繝峨・驕主臆髢狗､ｺ | 鬘ｧ螳｢諠・ｱ繧・・驛ｨ KPI 縺ｮ貍丞・ | 蜈ｬ髢句､縺ｯ蛹ｿ蜷榊喧繝ｻ髮・ｨ亥喧縺励・｡ｧ螳｢蜊倅ｽ阪・謨ｰ蟄励ｄ讖溷ｾｮ諠・ｱ縺ｯ蜃ｺ縺輔↑縺・|
| 險域ｸｬ繝峨Μ繝輔ヨ | 雋ｩ螢ｲ險ｴ豎ゅ→螳滓・縺ｮ荳堺ｸ閾ｴ | 閾ｪ遉ｾ險域ｸｬ縲…onnector 髮・ｨ医∝・髢玖｡ｨ遉ｺ縺ｮ螳夂ｾｩ繧呈純縺医∝・髢句､縺ｮ邂怜・繝ｫ繝ｼ繝ｫ繧貞崋螳壹☆繧・|
| 遶ｶ蜷域ｨ｡蛟｣繝ｪ繧ｹ繧ｯ | 蜈ｬ髢九☆繧区ｩ溯・隱ｬ譏弱°繧牙ｰ守ｷ夊ｨｭ險医・謾ｹ蝟・ｦｳ轤ｹ縺瑚ｪｭ縺ｿ蜿悶ｉ繧後∫ｫｶ蜷医↓螳溯｣・焔豕輔′莨昴ｏ繧・| 蜈ｬ髢九☆繧区ｩ溯・隱ｬ譏弱・蜴溽炊繝ｬ繝吶Ν縺ｫ逡吶ａ縲∝・驛ｨ驕狗畑繝弱え繝上え・亥・菴鍋噪縺ｪ繝励Ο繝ｳ繝励ヨ縲」ariant 逕滓・繝ｫ繝ｼ繝ｫ縲∵隼蝟・愛譁ｭ蝓ｺ貅厄ｼ峨・髢狗､ｺ縺励↑縺・|
| 髫懷ｮｳ譎ゅ・繝悶Λ繝ｳ繝峨Μ繧ｹ繧ｯ | 閾ｪ遉ｾ繧ｵ繧､繝磯囿螳ｳ縺後窟GENT NEO 陬ｽ蜩√・蜩∬ｳｪ荳崎ｶｳ縲阪→隱ｭ縺ｾ繧後ｋ | staging縲・撕逧・ヵ繧ｩ繝ｼ繝ｫ繝舌ャ繧ｯ蟆守ｷ壹・囿螳ｳ譎ゅ・雋ｩ螢ｲ LP 莉｣譖ｿ邨瑚ｷｯ繧呈ｺ門ｙ縺励～Built with AGENT NEO` 陦ｨ遉ｺ荳ｭ縺ｮ繧ｵ繧､繝医′關ｽ縺｡縺ｪ縺・°逕ｨ繧呈球菫昴☆繧・|

蜿ら・:

- [L0 莨∫判譖ｸ](L0-planning.md)
- [L1 隕∽ｻｶ螳夂ｾｩ](../requirements/L1-requirements.md)
- [NSRM 邨ｱ蜷育憾諷犠(../../.helix/nsrm.yaml)
- [LP/HP險ｭ險域婿驥拆(../../隗｣譫舌Ξ繝昴・繝・16-LP-HP險ｭ險域婿驥・md)
- [LLMO譎ゆｻ｣縺ｮ繝・・繝櫁ｨｭ險磯㍾隕∬ｦｳ轤ｹ](../../隗｣譫舌Ξ繝昴・繝・24-LLMO譎ゆｻ｣縺ｮ繝・・繝櫁ｨｭ險磯㍾隕∬ｦｳ轤ｹ.md)
- [WP繧ｵ繧､繝磯°逕ｨ縺ｨSEO縺ｮ荳埼・蜷医↑逵溷ｮ歉(../../隗｣譫舌Ξ繝昴・繝・25-WP繧ｵ繧､繝磯°逕ｨ縺ｨSEO縺ｮ荳埼・蜷医↑逵溷ｮ・md)
- [SEO繧ｹ繧ｭ繝ｫ繝槭ャ繝苓ｦｳ轤ｹ繝・・繝櫁ｪｿ譟ｻ](../../隗｣譫舌Ξ繝昴・繝・30-SEO繧ｹ繧ｭ繝ｫ繝槭ャ繝苓ｦｳ轤ｹ繝・・繝櫁ｪｿ譟ｻ.md)

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
| Q-001 | 個人 → 法人アップグレードの実装方式 | PO | **決定: Automation SEO 加入者は加入割引、非加入者は差額課金 ¥78,200（¥98,000 - ¥19,800）。実装方式（オンライン課金 / ライセンスキー再発行）は L2 で具体化** |
| Q-002 | ローンチ順（個人 → 法人 / 法人 → 個人 / 同時） | PO | L1 完了前 |
| Q-003 | 初回構築サービス S1 の価格レンジ・契約形態（固定 / 従量 / 段階課金） | PO | L2 開始前 |
| Q-004 | Automation SEO / LLM コスト負担 | PO | **決定: テーマ価格とは分離し、S1 / 有料アドオン / 従量 / BYO APIキー候補で回収** |
| Q-005 | プラン A の有料化（完全無料 / 軽課金） | PO | L2 開始前 |
| Q-006 | 移行プレビューの差分表示粒度（HTML diff / セマンティック diff） | TL | L3 開始前 |
| Q-007 | ライセンス検証方式の希望（独自 / Gumroad / WooCommerce / 課金 SaaS） | PO | L2 開始前 |
| Q-008 | 販売チャネル（自社サイト / マーケットプレイス併用） | PO | L7 前 |
| Q-009 | 自社配布テーマと wp.org 申請プラグインで機能ロック/アップセル範囲をどう分けるか | PO/TL | L2 凍結前 |
| Q-010 | **AGENT NEO 内蔵 SDK + クレジットシステム** の go/no-go（Automation SEO 不要で AI 実行可能化）。要決定: LLM 原価マージン / 残クレジット返金 / プライバシー / BYOK 併存ロジック / Automation SEO との競合関係 / 不正利用対策 | PO + 経営判断 | Phase 2 開始前 |
| Q-011 | グロース KPI の数値目標（検索流入、X 規模、記事 PV、CV、テーマ販売数、S1 問い合わせ数）の確定 | PO | L2 開始前 |

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
| 1.7 | 2026-05-01 | §6 にドッグフーディング型グロース戦略を追加（TL レビュー 4 回 pass、指摘 0 件） | Codex (research) + TL レビュー |
| 1.8 | 2026-05-01 | Q-001（個人→法人アップグレード方式）を PO 判断で確定。Automation SEO 加入割引 + 非加入者差額方式 | PM (Opus) |

