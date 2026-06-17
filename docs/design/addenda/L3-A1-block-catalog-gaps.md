# L3 設計 Addendum A1 — ブロックカタログ空白仕様（Block Catalog Gaps）

> **Addendum ID**: L3-A1  
> **担当 Wave**: Wave 4（ブロックカタログ領域）  
> **作成日**: 2026-06-18  
> **参照 GAP-RT**: GAP-RT-001, 002, 003, 004, 005, 006, 007, 008, 009  
> **ステータス**: L3-resolved（本 addendum で L3 仕様確定） / 一部 L4-carry（後述 §9 参照）  
> **共有ファイル編集禁止**: L3-detailed-design.md / L2-design.md / carry-register.md は Wave5 が統合担当

---

## 0. 設計前提（全ブロック共通）

### 0.1 FSE テーマとしての再設計方針

SWELL / JIN:R はクラシックテーマ（widgets + functions.php + customizer 中心）であり、AGENT NEO FSE テーマへの「直接移植」は行わない。以下の変換原則に従い再設計する。

| クラシックテーマ側機能 | AGENT NEO FSE 代替設計 |
|---|---|
| PHP shortcode（例: `[fukidashi]`） | block.json 正本の Gutenberg ブロック |
| 専用 DB テーブル（例: `swell_balloons`） | reusable-part CPT / block.json attributes / WP post_meta JSON |
| customizer 設定 | theme.json + block.json `supports` 節 |
| PHP render（動的） | 静的 Save 関数（原則）または render_callback（動的必須時のみ） |
| REST CRUD API（例: balloon 5本） | AGENT NEO Core Plugin の AI 操作契約（agent-neo/v1 名前空間） |

### 0.2 REQ-NF-025（AI ロジック完全分離）厳守

- **テーマ側**: レンダリング層・block.json 構造・計測 ID 生成・検証パイプラインのみ
- **Automation SEO 側**: 出し分け判断・条件評価ロジック・AI による variant 選定
- 本 addendum 全ブロックにおいて AI 判断ロジックをテーマに持ち込まない。宣言的スキーマのみ定義する

### 0.3 ブロック namespace 規約

すべてのブロックは `agent-neo/*` namespace で登録する。

```
agent-neo/blog-card          （内部リンクカード）
agent-neo/blog-card-external （外部 OGP カード）
agent-neo/balloon            （吹き出し）
agent-neo/restricted-area    （コンテンツゲート）
agent-neo/post-list          （投稿一覧 + loadmore）
agent-neo/toc                （目次）
agent-neo/faq                （FAQ アコーディオン）
agent-neo/step               （ステップ）
agent-neo/tab                （タブ）
agent-neo/timeline           （タイムライン）
agent-neo/banner-link        （バナーリンク）
agent-neo/link-list          （リンクリスト）
agent-neo/icon-box           （アイコンボックス）
```

---

## 1. Blog Card（内部リンクカード）— GAP-RT-001

### 1.1 仕様概要

| 項目 | 内容 |
|---|---|
| ブロック名 | `agent-neo/blog-card` |
| 静的 or 動的 | **動的**（render_callback 必須）。内部記事メタ（タイトル・アイキャッチ・抜粋）を WP REST 経由でフェッチするため、save 関数は空 |
| 対応 REQ-F | REQ-F-004（個人版収益化ブロック内リンク導線）、REQ-F-021（安定 block_id）|

### 1.2 block.json supports 概要

```json
{
  "name": "agent-neo/blog-card",
  "apiVersion": 3,
  "category": "agent-neo",
  "attributes": {
    "postId": { "type": "integer" },
    "displayMode": { "type": "string", "enum": ["auto", "minimal", "full"], "default": "auto" },
    "block_id": { "type": "string" },
    "section_id": { "type": "string" },
    "cta_id": { "type": "string" }
  },
  "supports": {
    "html": false,
    "anchor": true,
    "color": { "background": true, "text": true },
    "spacing": { "margin": true, "padding": true },
    "typography": { "fontSize": true }
  },
  "usesContext": ["agent-neo/pageType"]
}
```

### 1.3 主要 attributes

| attribute | 型 | 説明 |
|---|---|---|
| `postId` | integer | 対象 WP 記事 ID。エディタで URL 入力 → 内部 REST `/wp/v2/posts/{id}` でタイトル・抜粋・アイキャッチ解決 |
| `displayMode` | enum | `auto`（テーマが適切なカード形式を選択）/ `minimal`（タイトルのみ）/ `full`（アイキャッチ + 抜粋 + 日付） |
| `block_id` | string | 安定 block_id（UUID v4）。REQ-F-021 |
| `section_id` | string | 親 H2 セクション ID（REQ-F-022 との連携） |
| `cta_id` | string | 任意。このカードが CTA として機能する場合に付与（REQ-F-006 計測） |

### 1.4 render_callback 仕様

```
render: inc/blocks/blog-card/render.php
- get_post( $attrs['postId'] ) で記事取得
- get_the_post_thumbnail_url / get_the_excerpt を使用
- 存在しない / 非公開投稿の場合: fallback として URL テキストリンクを表示
- 出力は wp_kses 許可済みタグでエスケープ
- data-block-id / data-cta-id / data-section-id 属性を付与
```

### 1.5 AI 操作契約

| 操作 | エンドポイント | ペイロード例 |
|---|---|---|
| カード追加 | `POST /agent-neo/v1/actions/dry-run` + apply | `{"action":"patch_post","changes":[{"op":"add","path":"/blocks/-","value":{"name":"agent-neo/blog-card","postId":123,"cta_id":"related-cta-01"}}]}` |
| postId 差し替え | `POST /agent-neo/v1/elements/swap` | `{"element_type":"blog_card","block_id":"uuid","target_post_id":456}` |
| displayMode 変更 | `PATCH /agent-neo/v1/posts/{id}/blocks/{block_id}` | `{"attributes":{"displayMode":"full"}}` |

### 1.6 計測 ID 連携

- `data-section-id` = 親 H2 `section_id`
- `data-cta-id` = `cta_id`（設定時のみ）
- `data-block-id` = `block_id`（必須）
- 計測イベント: `POST /agent-neo/v1/tracking/event` の `event_type: "block_click"`

### 1.7 受入条件（TC 候補）

| TC-ID 案 | 条件 | 期待結果 |
|---|---|---|
| TC-BC-001 | 有効な postId でブロックを描画 | タイトル・アイキャッチ・抜粋が表示される |
| TC-BC-002 | 削除済み記事の postId | fallback テキストリンクが表示され fatal error なし |
| TC-BC-003 | cta_id 付きブロックをクリック | `tracking/event` に `event_type: block_click, cta_id` が記録される |
| TC-BC-004 | displayMode=minimal | タイトルのみ表示、アイキャッチ非表示 |

---

## 2. 外部 Blog Card（外部 OGP カード + SSRF 対策）— GAP-RT-002

### 2.1 仕様概要

| 項目 | 内容 |
|---|---|
| ブロック名 | `agent-neo/blog-card-external` |
| 静的 or 動的 | **動的**（render_callback 必須）。外部 OGP 取得は **バックエンド（PHP / Action Scheduler）経由のみ**。フロントエンドから外部 URL への直接リクエスト禁止 |
| 対応 REQ-F | REQ-F-004（個人版外部リンク導線）、REQ-NF-002（セキュリティ）、REQ-NF-014（API 契約） |

### 2.2 SSRF 対策契約（必須 / REQ-NF-002 enforced_by）

JIN:R 実装（`file_get_contents($url)` 直叩き）は **絶対禁止**。以下をすべて実装する。

| 対策 | 仕様 |
|---|---|
| **WP HTTP API 使用** | `wp_safe_remote_get( $url, ['timeout' => 5] )` のみ使用。`file_get_contents` / `curl_exec` 直呼び禁止 |
| **allowlist 検証** | Core Plugin の `agent_neo_ogp_allowlist` option で許可ドメインを管理。空の場合は全拒否。AI は `PATCH /agent-neo/v1/settings/ogp-allowlist` で更新 |
| **内部 IP ブロック** | `wp_safe_remote_get` に加え、解決された IP が RFC 1918 / リンクローカル / メタデータ endpoint（169.254.169.254 等）に該当する場合は 403 を返す検証を独自追加 |
| **timeout** | 接続 5 秒 / 読み取り 10 秒を上限とする |
| **rate limit** | 同一 IP から 1 分間に 10 回を超える OGP 取得リクエストは 429 |
| **キャッシュ** | OGP 取得結果を `wp_cache_set`（TTL 3600 秒）でキャッシュし、外部リクエストを最小化 |
| **コンテンツ検証** | レスポンスの `Content-Type` が `text/html` 以外の場合は取得中断 |

### 2.3 block.json supports 概要

```json
{
  "name": "agent-neo/blog-card-external",
  "apiVersion": 3,
  "attributes": {
    "externalUrl": { "type": "string", "format": "uri" },
    "ogpTitle": { "type": "string" },
    "ogpDescription": { "type": "string" },
    "ogpImageUrl": { "type": "string", "format": "uri" },
    "ogpFetchedAt": { "type": "string" },
    "block_id": { "type": "string" },
    "cta_id": { "type": "string" }
  },
  "supports": { "html": false, "anchor": true, "color": true, "spacing": true }
}
```

### 2.4 AI 操作契約

| 操作 | エンドポイント | 説明 |
|---|---|---|
| 外部カード追加（URL のみ指定） | `POST /agent-neo/v1/actions/dry-run` → apply | `{"action":"patch_post","changes":[{"op":"add","path":"/blocks/-","value":{"name":"agent-neo/blog-card-external","externalUrl":"https://example.com/article"}}]}` |
| OGP 強制再取得 | `POST /agent-neo/v1/posts/{id}/blocks/{block_id}/refresh-ogp` | Core Plugin が Action Scheduler 経由で OGP を再フェッチし block attribute を更新 |
| allowlist 更新 | `PATCH /agent-neo/v1/settings/ogp-allowlist` | `{"domains":["example.com","partner-site.jp"]}` |

### 2.5 受入条件（TC 候補）

| TC-ID 案 | 条件 | 期待結果 |
|---|---|---|
| TC-EBC-001 | 許可ドメインの外部 URL を設定 | OGP タイトル・説明・画像がカード表示される |
| TC-EBC-002 | allowlist 未登録ドメイン | 取得拒否、エラーメッセージ表示（外部リクエスト発生しない） |
| TC-EBC-003 | 169.254.169.254 を URL に指定 | 内部 IP ブロックで拒否、監査ログに記録 |
| TC-EBC-004 | `file_get_contents` コードパスが存在しない | 静的解析（grep）で `file_get_contents` ヒットゼロ |
| TC-EBC-005 | 同一 IP から 11 回連続 OGP リクエスト | 429 を返し 10 回目まではキャッシュから応答 |

---

## 3. 吹き出し（Balloon）— GAP-RT-003, GAP-RT-004

### 3.1 仕様概要

| 項目 | 内容 |
|---|---|
| ブロック名 | `agent-neo/balloon` |
| 静的 or 動的 | **静的**（Save 関数で HTML 出力）。キャラクター画像はブロック attributes に保持し、動的フェッチ不要 |
| 対応 REQ-F | REQ-F-004（個人版ブロック）、REQ-F-025（JSON 統一データモデル） |

### 3.2 データ保持方式の比較と推奨

SWELL は `swell_balloons` 専用 DB テーブル + 5 本 REST CRUD を持つ。JIN:R は `[fukidashi]` shortcode ベース。AGENT NEO FSE テーマとして以下 2 案を比較する。

| 案 | 方式 | メリット | デメリット |
|---|---|---|---|
| **案 A（推奨）: reusable-part CPT** | キャラクター定義を `agent-neo-balloon-character` CPT で管理（name / avatar_url / default_position）。balloon ブロックは `characterId`（CPT post ID or slug）を参照 | AI が `GET /agent-neo/v1/balloon-characters` でキャラクター一覧を取得し、ブロックに assign 可能。CPT は Core Plugin が管理しテーマ無効化時も保持。AGENT NEO の reusable-part 設計と整合 | キャラクター追加時に CPT 投稿が必要（エディタから直接作れる）|
| 案 B: ブロック内完結 | キャラクター画像 URL / 名前をすべてブロック attributes に直接保持。CPT なし | シンプル。外部依存なし | キャラクター変更時にすべての投稿のブロックを更新する必要あり。AI の一括更新コストが高い |

**推奨: 案 A（reusable-part CPT）**  
理由: AI がキャラクターを識別・管理・一括差し替えできるため REQ-F-021（部分更新性）/ REQ-F-023（Element Swap）と整合する。`characterId` による stable ID 参照は AI 操作の信頼性を高める。

### 3.3 block.json supports 概要

```json
{
  "name": "agent-neo/balloon",
  "apiVersion": 3,
  "attributes": {
    "characterId": { "type": "string", "description": "balloon-character CPT slug" },
    "position": { "type": "string", "enum": ["left", "right"], "default": "left" },
    "tailStyle": { "type": "string", "enum": ["normal", "thinking", "none"], "default": "normal" },
    "text": { "type": "string" },
    "block_id": { "type": "string" },
    "section_id": { "type": "string" }
  },
  "supports": {
    "html": false,
    "anchor": true,
    "color": { "background": true, "text": true },
    "typography": { "fontSize": true },
    "spacing": { "margin": true, "padding": true }
  }
}
```

### 3.4 balloon-character CPT スキーマ（Core Plugin 管理）

```
post_type: agent-neo-balloon-character
fields（post_meta JSON）:
  - name: string（キャラクター名、表示用）
  - avatar_url: string（wp_attachment ID または URL）
  - default_position: "left" | "right"
REST: GET /agent-neo/v1/balloon-characters（一覧）
      GET /agent-neo/v1/balloon-characters/{id}（詳細）
      POST /agent-neo/v1/balloon-characters（作成）
      PATCH /agent-neo/v1/balloon-characters/{id}（更新）
```

### 3.5 JIN:R shortcode 互換（GAP-RT-004）

JIN:R の `[fukidashi]` shortcode はクラシックテーマ構造であり AGENT NEO では FSE ブロックに統合する。

- **移行プラグイン対応**: `[fukidashi]` shortcode を含む記事を AGENT NEO に移行する際、移行プラグイン（REQ-F-008）が shortcode を `agent-neo/balloon` ブロックに変換する変換ルールを提供する
- **shortcode レイヤー禁止**: AGENT NEO テーマ内に `add_shortcode( 'fukidashi', ... )` を追加しない（ADR-008 / CR-002 違反）
- ADR 記録: GAP-RT-004 対応は移行プラグインの変換ルール設計として L4 で実装。ADR への明記は Wave5（carry-register 統合）で carry-021 として追記

### 3.6 AI 操作契約

| 操作 | エンドポイント | ペイロード例 |
|---|---|---|
| balloon 追加 | `POST /agent-neo/v1/actions/dry-run` → apply | `{"op":"add","path":"/blocks/-","value":{"name":"agent-neo/balloon","characterId":"chara-alice","position":"left","tailStyle":"normal","text":"こんにちは！"}}` |
| キャラクター差し替え | `POST /agent-neo/v1/elements/swap` | `{"element_type":"balloon_character","block_id":"uuid","target_character_id":"chara-bob"}` |
| テキスト更新 | `PATCH /agent-neo/v1/posts/{id}/blocks/{block_id}` | `{"attributes":{"text":"ありがとうございます！"}}` |

### 3.7 受入条件（TC 候補）

| TC-ID 案 | 条件 | 期待結果 |
|---|---|---|
| TC-BL-001 | characterId 付き balloon ブロックを描画 | キャラクター画像・名前・吹き出しテールが正しく表示される |
| TC-BL-002 | position=right | 吹き出しがキャラクター左・テキスト右にレイアウトされる |
| TC-BL-003 | AI が characterId を差し替え | `elements/swap` 呼び出し後、すべての該当ブロックのキャラクター画像が切り替わる |
| TC-BL-004 | 存在しない characterId | fallback（匿名アバター + 名前「？」）で表示、fatal error なし |

---

## 4. Restricted Area（コンテンツゲート）— GAP-RT-005

### 4.1 仕様概要

| 項目 | 内容 |
|---|---|
| ブロック名 | `agent-neo/restricted-area` |
| 静的 or 動的 | **動的**（render_callback 必須）。条件評価はサーバーサイドで実施（セキュリティ上フロント JS のみでの開閉禁止） |
| 対応 REQ-F | REQ-F-004（個人版）、REQ-F-005（法人版）、REQ-NF-025（AI ロジック分離） |

### 4.2 条件種別の宣言的スキーマ

**重要**: 条件の評価ロジック（誰がアクセス可能か）はテーマ側の render_callback が WP 標準 API（`is_user_logged_in()` / `wp_get_current_user()->roles` 等）で判定する。**AI や Automation SEO が条件をリアルタイムに動的制御することは行わない**（REQ-NF-025）。AI は条件スキーマ（宣言）を操作するのみ。

> **[P1] ロール判定の設計修正（2026-06-18）**: 旧設計では `conditionType=role` の評価に `current_user_can( $role )` を使っていたが、これは `subscriber` / `editor` 等をケイパビリティ名として解釈するため、当該ロールを持つ正規ユーザーでも拒否される誤動作が生じる。本仕様では `conditionType=role` を **ロール配列照合**、`conditionType=capability` を **ケイパビリティ判定** として明示的に分離する。

```json
{
  "conditionType": {
    "type": "string",
    "enum": [
      "logged_in",
      "role",
      "capability",
      "date_range",
      "page_type",
      "taxonomy"
    ],
    "description": "条件種別。複数条件は条件配列 conditions[] で AND 結合"
  }
}
```

| conditionType | 評価方法（PHP / WP API） | attributes | 備考 |
|---|---|---|---|
| `logged_in` | `is_user_logged_in()` | なし（ログイン有無のみ） | |
| `role` | `in_array( $role, (array) wp_get_current_user()->roles, true )` | `requiredRole: string`（例: `"subscriber"`, `"editor"`） | **ロール名を roles 配列で照合**。`current_user_can()` を使わない |
| `capability` | `current_user_can( $capability )` | `requiredCapability: string`（例: `"edit_posts"`, `"manage_options"`） | **ケイパビリティ名で判定**する場合に使用。ロール判定とは別種別 |
| `date_range` | `current_time( 'timestamp' )` との比較 | `startDate: string（ISO8601）`, `endDate: string（ISO8601）` | |
| `page_type` | `is_singular()` / `is_archive()` / `is_page_template()` | `allowedPageTypes: string[]` | |
| `taxonomy` | `has_term( $term, $taxonomy )` | `taxonomy: string`, `terms: string[]` | |

**ロール判定の擬似コード（`conditionType=role`）**:

```php
// render_callback 内の条件評価（conditionType=role の場合）
function agent_neo_evaluate_role_condition( string $required_role ): bool {
    // wp_get_current_user()->roles は現在ユーザーが保持するロール名の配列
    // in_array で厳密照合（ロール名 = 文字列比較）
    return in_array( $required_role, (array) wp_get_current_user()->roles, true );
}

// NG: current_user_can( 'subscriber' ) は 'subscriber' をケイパビリティ名として評価するため
// subscriber ロールを持つユーザーが拒否される誤動作が発生する
```

**ケイパビリティ判定の擬似コード（`conditionType=capability`）**:

```php
// conditionType=capability の場合は current_user_can() を使用
function agent_neo_evaluate_capability_condition( string $capability ): bool {
    return current_user_can( $capability );
}
```

### 4.3 block.json supports 概要

```json
{
  "name": "agent-neo/restricted-area",
  "apiVersion": 3,
  "attributes": {
    "conditions": {
      "type": "array",
      "items": {
        "type": "object",
        "properties": {
          "conditionType": { "type": "string", "enum": ["logged_in","role","capability","date_range","page_type","taxonomy"] },
          "requiredRole": { "type": "string", "description": "conditionType=role 時に使用。wp_get_current_user()->roles 配列と照合" },
          "requiredCapability": { "type": "string", "description": "conditionType=capability 時に使用。current_user_can() で評価" },
          "startDate": { "type": "string" },
          "endDate": { "type": "string" },
          "allowedPageTypes": { "type": "array", "items": { "type": "string" } },
          "taxonomy": { "type": "string" },
          "terms": { "type": "array", "items": { "type": "string" } }
        },
        "required": ["conditionType"]
      }
    },
    "fallbackMode": { "type": "string", "enum": ["hide", "blur", "message"], "default": "message" },
    "fallbackMessage": { "type": "string", "default": "この内容をご覧いただくにはログインが必要です。" },
    "block_id": { "type": "string" },
    "section_id": { "type": "string" }
  },
  "supports": { "html": false, "anchor": true, "spacing": true },
  "allowedBlocks": ["*"]
}
```

### 4.4 AI 操作契約

AI は **条件スキーマの宣言** を操作する。実行時評価はテーマ render_callback が担い、AI は介在しない。

| 操作 | エンドポイント | ペイロード例 |
|---|---|---|
| ログイン限定ゾーン追加 | `PATCH /agent-neo/v1/posts/{id}/blocks/{block_id}` | `{"attributes":{"conditions":[{"conditionType":"logged_in"}],"fallbackMode":"message"}}` |
| ロール + 期間の複合条件 | `PATCH /agent-neo/v1/posts/{id}/blocks/{block_id}` | `{"attributes":{"conditions":[{"conditionType":"role","requiredRole":"subscriber"},{"conditionType":"date_range","startDate":"2026-07-01","endDate":"2026-09-30"}]}}` ※ `requiredRole` は roles 配列照合で評価 |
| ケイパビリティ制限 | `PATCH /agent-neo/v1/posts/{id}/blocks/{block_id}` | `{"attributes":{"conditions":[{"conditionType":"capability","requiredCapability":"edit_posts"}]}}` |

### 4.5 受入条件（TC 候補）

| TC-ID 案 | 条件 | 期待結果 |
|---|---|---|
| TC-RA-001 | `logged_in` 条件、未ログインでアクセス | fallbackMessage が表示され、インナーブロック非表示 |
| TC-RA-002 | `logged_in` 条件、ログイン済みでアクセス | インナーブロックが表示される |
| TC-RA-003 | `conditionType=role, requiredRole: subscriber`、subscriber ロールを持つユーザーでアクセス | `wp_get_current_user()->roles` に `"subscriber"` が含まれるため表示される（`current_user_can('subscriber')` は使わない） |
| TC-RA-003b | `conditionType=capability, requiredCapability: edit_posts`、editor 権限ユーザーでアクセス | `current_user_can('edit_posts')` が true のため表示される |
| TC-RA-004 | `date_range` が未来の期間 | fallback 表示（コンテンツ非表示） |
| TC-RA-005 | `date_range` 内にアクセス | コンテンツ表示 |
| TC-RA-006 | AI が conditions を dryRun で更新 | diff preview に conditions 変更差分が表示される |
| TC-RA-007 | フロント JS を無効化してアクセス | サーバーサイド render のみで正しく条件評価（JS依存なし） |

---

## 5. Related Post / Post List（バリアント + AJAX load-more）— GAP-RT-006

### 5.1 仕様概要

| 項目 | 内容 |
|---|---|
| ブロック名 | `agent-neo/post-list` |
| 静的 or 動的 | **動的**（render_callback 必須）。AJAX load-more は WP REST `/wp/v2/posts` を使用 |
| 対応 REQ-F | REQ-F-004（関連記事 / 個人版収益化導線）、REQ-F-006（計測 section_id） |

### 5.2 block.json supports 概要

```json
{
  "name": "agent-neo/post-list",
  "apiVersion": 3,
  "attributes": {
    "variant": { "type": "string", "enum": ["related", "recent", "popular", "category", "tag", "manual"], "default": "related" },
    "postsPerPage": { "type": "integer", "default": 6, "minimum": 1, "maximum": 20 },
    "sortBy": { "type": "string", "enum": ["date", "modified", "views", "comment_count"], "default": "date" },
    "enableLoadMore": { "type": "boolean", "default": false },
    "loadMoreText": { "type": "string", "default": "もっと見る" },
    "displayMode": { "type": "string", "enum": ["card", "list", "compact"], "default": "card" },
    "categoryIds": { "type": "array", "items": { "type": "integer" } },
    "tagIds": { "type": "array", "items": { "type": "integer" } },
    "manualPostIds": { "type": "array", "items": { "type": "integer" } },
    "block_id": { "type": "string" },
    "section_id": { "type": "string" },
    "cta_id": { "type": "string" }
  },
  "supports": {
    "html": false, "anchor": true, "spacing": true,
    "color": { "background": true }
  }
}
```

### 5.3 AJAX load-more 仕様

- `enableLoadMore: true` の場合、初回描画は `postsPerPage` 件を server-side render
- 「もっと見る」クリック時: フロント JS が `GET /wp/v2/posts?per_page={n}&page={p}&orderby={sortBy}` を発行
- PV ソート（`sortBy: "views"`）: Core Plugin が `post_views_count` custom field を管理。`orderby=meta_value_num&meta_key=post_views_count` で取得
- page 最大値: 100 ページ（それ以上は load-more ボタン非表示）
- JS バジェット: load-more インタラクション JS ≤ 5KB（REQ-NF-001e 準拠）

### 5.4 AI 操作契約

| 操作 | ペイロード例 |
|---|---|
| 関連記事ブロック追加（PV ソート） | `{"name":"agent-neo/post-list","variant":"popular","sortBy":"views","postsPerPage":5,"enableLoadMore":true}` |
| manual variant で記事を指定 | `{"variant":"manual","manualPostIds":[10,20,30]}` |
| section_id / cta_id 付与 | `{"section_id":"related-sec-xxx","cta_id":"related-cta-01"}` |

### 5.5 受入条件（TC 候補）

| TC-ID 案 | 条件 | 期待結果 |
|---|---|---|
| TC-PL-001 | `variant: related` で記事ページを表示 | 同カテゴリ / タグの記事が最大 `postsPerPage` 件表示される |
| TC-PL-002 | `enableLoadMore: true` で「もっと見る」クリック | 追加記事が DOM に追加される（ページ遷移なし） |
| TC-PL-003 | `sortBy: views` | PV 順で記事が並ぶ（`post_views_count` meta 降順） |
| TC-PL-004 | 全件表示後は load-more ボタン非表示 | ボタンが消える |

---

## 6. TOC（目次）— GAP-RT-007

### 6.1 仕様概要

| 項目 | 内容 |
|---|---|
| ブロック名 | `agent-neo/toc` |
| 静的 or 動的 | **動的**（render_callback で記事コンテンツ内の h2/h3 を抽出）|
| 対応 REQ-F | REQ-F-004（個人版ブロック）、REQ-NF-001（パフォーマンス：スクロール連動 JS ≤ 5KB）|

### 6.2 自動生成方式

render_callback が `get_post_content()` の HTML を DOMDocument / `preg_match_all` でパースし、h2 / h3 タグの `id` 属性または テキストから slug を生成して目次リストを出力する。

- h2/h3 の `id` 属性が存在する場合: **そのまま利用**（既存 `id` を最優先とし、生成処理を行わない）
- `id` 属性が存在しない場合: テキストから **`sanitize_slug()`（ASCII 限定スラッグ生成ヘルパ）** で slug を生成し、元の h2/h3 タグに `id` を付与する。`sanitize_title()` は percent-encoded な非 ASCII ID を生成して TOC リンクや scroll-spy セレクタを壊すため **使用禁止**（CARRY-G2-013 / TC-017b / data-model §R-09a 準拠）
  - `sanitize_slug()` 実装方針: テキストを NFD 正規化 → ASCII アルファベット・数字・ハイフン以外を除去 → 小文字化 → 先頭末尾ハイフン除去 → 連続ハイフン圧縮
  - **同名見出し衝突時**: 2 件目以降は `-2`, `-3`, ... の連番サフィックスを付与して一意性を保証する
  - **ASCII 化後に空文字になる場合 (fallback)**: `section-{見出しの出現インデックス}` を使用する（例: `section-1`, `section-2`）
- **最小見出し数**: デフォルト 3。`minHeadings` attribute が 3 未満の場合は目次非表示。AI が設定変更可能

### 6.3 block.json supports 概要

```json
{
  "name": "agent-neo/toc",
  "apiVersion": 3,
  "attributes": {
    "headingLevels": { "type": "array", "items": { "type": "string", "enum": ["h2","h3","h4"] }, "default": ["h2","h3"] },
    "minHeadings": { "type": "integer", "default": 3, "minimum": 1 },
    "enableScrollSpy": { "type": "boolean", "default": true },
    "style": { "type": "string", "enum": ["default", "numbered", "compact"], "default": "default" },
    "title": { "type": "string", "default": "目次" },
    "block_id": { "type": "string" },
    "section_id": { "type": "string" }
  },
  "supports": {
    "html": false, "anchor": true,
    "color": { "background": true, "text": true },
    "spacing": { "margin": true, "padding": true }
  }
}
```

### 6.4 スクロール連動（ScrollSpy）

- `enableScrollSpy: true` の場合、Intersection Observer API を使用（IE 不要のため polyfill 不要）
- h2/h3 が viewport に入った時点で対応する目次項目をアクティブ化（CSS クラス `is-active` 付与）
- JS は defer + 1 ファイル（≤ 5KB minify 済み）。block.json `viewScript` で宣言し条件付き読み込み

### 6.5 AI 操作契約

| 操作 | ペイロード例 |
|---|---|
| 目次ブロック追加（h2 のみ） | `{"name":"agent-neo/toc","headingLevels":["h2"],"minHeadings":2,"style":"numbered"}` |
| スクロール連動 OFF | `{"attributes":{"enableScrollSpy":false}}` |

### 6.6 受入条件（TC 候補）

| TC-ID 案 | 条件 | 期待結果 |
|---|---|---|
| TC-TOC-001 | h2 が 3 件ある記事に toc ブロック配置 | 3 件の目次リンクが表示される |
| TC-TOC-002 | h2 が `minHeadings`(3) 未満 | 目次非表示 |
| TC-TOC-003 | `style: numbered` | 各項目に順序番号が付く |
| TC-TOC-004 | ScrollSpy 有効状態でスクロール | 現在位置の見出しに対応する目次項目が `is-active` クラスを持つ |
| TC-TOC-005 | `headingLevels: ["h2","h3"]` で h4 は含まれない | h4 見出しが目次に出現しない |

---

## 7. FAQ（アコーディオン + FAQPage JSON-LD）— GAP-RT-008

### 7.1 仕様概要

| 項目 | 内容 |
|---|---|
| ブロック名 | `agent-neo/faq` |
| 静的 or 動的 | **静的**（Save 関数で HTML 出力 + JSON-LD を `wp_head` でフック出力）|
| 対応 REQ-F | REQ-F-004（個人版 SEO 強化）、REQ-F-011（SEO Core: 構造化データ）|

### 7.2 アコーディオン動作仕様

- `<details>` / `<summary>` ネイティブ HTML 要素を使用（JS なしで開閉動作）
- `open` 属性をデフォルトで付与しない（全閉じ初期状態）
- CSS アニメーション（height transition）は ≤ 2KB の inline style または `viewStyle` 専用 CSS

### 7.3 FAQPage JSON-LD 出力仕様

render_callback（または `the_content` フィルタ）が記事内の全 `agent-neo/faq` ブロックを集約し、FAQPage schema を `<script type="application/ld+json">` として `wp_head` に出力する。

```json
{
  "@context": "https://schema.org",
  "@type": "FAQPage",
  "mainEntity": [
    {
      "@type": "Question",
      "name": "{question}",
      "acceptedAnswer": { "@type": "Answer", "text": "{answer}" }
    }
  ]
}
```

**複数 FAQ ブロックのマージ**: 記事内に複数の `agent-neo/faq` ブロックが存在する場合、すべての Question を単一の FAQPage にマージして出力する（Google 推奨形式）。

### 7.4 block.json supports 概要

```json
{
  "name": "agent-neo/faq",
  "apiVersion": 3,
  "innerBlocks": {
    "allowedBlocks": ["agent-neo/faq-item"]
  },
  "attributes": {
    "outputJsonLd": { "type": "boolean", "default": true },
    "block_id": { "type": "string" },
    "section_id": { "type": "string" }
  },
  "supports": { "html": false, "anchor": true, "spacing": true }
}
```

**`agent-neo/faq-item`（子ブロック）**:

```json
{
  "name": "agent-neo/faq-item",
  "parent": ["agent-neo/faq"],
  "attributes": {
    "question": { "type": "string" },
    "answer": { "type": "string" }
  }
}
```

### 7.5 AI 操作契約

| 操作 | ペイロード例 |
|---|---|
| FAQ ブロック追加（3 問） | `{"name":"agent-neo/faq","innerBlocks":[{"name":"agent-neo/faq-item","question":"Q1?","answer":"A1"},{"name":"agent-neo/faq-item","question":"Q2?","answer":"A2"}],"outputJsonLd":true}` |
| FAQ item テキスト更新 | `PATCH /agent-neo/v1/posts/{id}/blocks/{faq_item_block_id}` + `{"attributes":{"answer":"更新後の回答"}}` |

### 7.6 受入条件（TC 候補）

| TC-ID 案 | 条件 | 期待結果 |
|---|---|---|
| TC-FAQ-001 | FAQ 2 件の記事を表示 | 2 件のアコーディオン + FAQPage JSON-LD が出力される |
| TC-FAQ-002 | 複数 FAQ ブロック（別々に配置） | JSON-LD は単一 FAQPage にマージ |
| TC-FAQ-003 | `outputJsonLd: false` | JSON-LD 非出力（HTML のみ） |
| TC-FAQ-004 | JS 無効状態 | `<details><summary>` ネイティブで開閉動作 |
| TC-FAQ-005 | AI が faq-item の question/answer を更新 | 再描画後の JSON-LD も更新される |

---

## 8. Step / Tab / Timeline / Banner Link / Link List / Iconbox（バリアントグループ）— GAP-RT-009

### 8.1 共通設計方針

これら 6 ブロックは「構造 = インナーブロックの繰り返し」パターン。共通原則:
- 親ブロック（コンテナ）+ 子ブロック（アイテム）の 2 層構造
- `allowedBlocks` で子ブロック種を制限
- 子ブロック数制限（min / max）は `lock` 設定で制御
- すべて **静的**（Save 関数）。PHP render_callback 不要
- AI は innerBlocks の配列操作（add / reorder / remove）で編集

### 8.2 Step（ステップブロック）

| attribute | 型 | 説明 |
|---|---|---|
| `variant` | enum: `"numbered"` / `"icon"` / `"checkpoint"` | 表示スタイル |
| `orientation` | enum: `"vertical"` / `"horizontal"` | レイアウト方向 |
| `block_id` | string | 安定 ID |
| `section_id` | string | 親セクション ID |

子ブロック `agent-neo/step-item`:

| attribute | 型 |
|---|---|
| `title` | string |
| `description` | string |
| `icon` | string（SVG 名 or URL）|

AI 操作例: `{"op":"add","path":"/innerBlocks/-","value":{"name":"agent-neo/step-item","title":"ステップ3","description":"手順の説明"}}`

受入条件 TC 候補:
- TC-STEP-001: `variant: numbered` で各 item に連番表示
- TC-STEP-002: `orientation: horizontal` でレイアウトが横並び

### 8.3 Tab（タブブロック）

| attribute | 型 | 説明 |
|---|---|---|
| `variant` | enum: `"default"` / `"boxed"` / `"underline"` | タブスタイル |
| `defaultTab` | integer | デフォルトアクティブタブ index（0 始まり）|
| `block_id` | string | |

子ブロック `agent-neo/tab-panel`:

| attribute | 型 |
|---|---|
| `label` | string（タブラベル）|

- タブ切り替え JS: Intersection Observer 不要。click event のみ。≤ 3KB。`viewScript` で宣言
- a11y: `role="tablist"` / `role="tab"` / `aria-selected` / `role="tabpanel"` / `aria-labelledby` 必須（REQ-NF-005）

受入条件 TC 候補:
- TC-TAB-001: キーボード（← →）でタブ切り替え可能
- TC-TAB-002: `defaultTab: 1` でページ初期表示時に 2 番目タブがアクティブ

### 8.4 Timeline（タイムラインブロック）

| attribute | 型 | 説明 |
|---|---|---|
| `variant` | enum: `"vertical"` / `"horizontal"` | |
| `showDates` | boolean | 日付表示 |
| `block_id` | string | |

子ブロック `agent-neo/timeline-item`:

| attribute | 型 |
|---|---|
| `date` | string（表示用テキスト）|
| `title` | string |
| `description` | string |
| `icon` | string |

### 8.5 Banner Link（バナーリンクブロック）

| attribute | 型 | 説明 |
|---|---|---|
| `imageUrl` | string（URL）| バナー画像 |
| `alt` | string | alt 属性（必須）|
| `href` | string（URL）| リンク先 |
| `openInNewTab` | boolean | |
| `cta_id` | string | CTA 計測 ID（必須推奨） |
| `banner_id` | string | Element Swap 用安定 ID |
| `block_id` | string | |

AI 操作例（banner swap）: `{"element_type":"banner","block_id":"uuid","target_banner_id":"banner-summer-v2"}`

受入条件 TC 候補:
- TC-BNR-001: alt なしで保存試行 → axe-core 違反警告
- TC-BNR-002: `banner_id` 付きバナーを swap → 画像・href 両方切り替わる

### 8.6 Link List（リンクリストブロック）

| attribute | 型 | 説明 |
|---|---|---|
| `variant` | enum: `"default"` / `"card"` / `"arrow"` | スタイル |
| `columns` | integer（1-4）| カラム数 |
| `block_id` | string | |

子ブロック `agent-neo/link-list-item`:

| attribute | 型 |
|---|---|
| `label` | string |
| `href` | string |
| `description` | string |
| `icon` | string |
| `link_id` | string（REQ-F-023 / Element Swap） |

受入条件 TC 候補:
- TC-LL-001: `variant: card` / `columns: 3` でグリッドレイアウト
- TC-LL-002: `link_id` 付き item を AI が URL 差し替え → テキスト保持、href のみ変わる

### 8.7 Iconbox（アイコンボックスブロック）

| attribute | 型 | 説明 |
|---|---|---|
| `variant` | enum: `"default"` / `"outlined"` / `"filled"` | スタイル |
| `columns` | integer（1-4）| グリッドカラム数 |
| `block_id` | string | |

子ブロック `agent-neo/iconbox-item`:

| attribute | 型 |
|---|---|
| `icon` | string（SVG 名 or URL）|
| `title` | string |
| `description` | string |
| `href` | string（任意）|

受入条件 TC 候補:
- TC-ICB-001: iconbox に 6 アイテム追加、`columns: 3` で 2 行グリッド表示
- TC-ICB-002: icon に任意 SVG URL を指定 → 正常表示

---

## 9. GAP-RT 処理状態サマリ

| GAP-RT | タイトル | 処理状態 | 根拠 |
|---|---|---|---|
| GAP-RT-001 | Blog Card（内部） | **L3-resolved** | §1 で仕様確定 |
| GAP-RT-002 | 外部 Blog Card + SSRF 対策 | **L3-resolved**（SSRF 対策契約）+ **L4-carry（C-A1-001）** | §2 で契約定義。実装詳細（IP ブロック実装 / Action Scheduler OGP ジョブ）は L4 |
| GAP-RT-003 | balloon（SWELL 専用 DB → FSE 再設計） | **L3-resolved** | §3 で CPT 方式確定 |
| GAP-RT-004 | balloon（JIN:R shortcode 互換） | **L4-carry（C-A1-002）** | 移行プラグイン変換ルールとして L4 実装。ADR への明記は Wave5 統合時 |
| GAP-RT-005 | Restricted Area | **L3-resolved** | §4 で条件スキーマ確定 |
| GAP-RT-006 | Related Post / Post List（AJAX loadmore） | **L3-resolved**（契約）+ **L4-carry（C-A1-003）** | §5 で仕様確定。PV カウント実装は L4 |
| GAP-RT-007 | TOC（目次）| **L3-resolved** | §6 で確定 |
| GAP-RT-008 | FAQ（JSON-LD 付き）| **L3-resolved** | §7 で確定 |
| GAP-RT-009 | Step / Tab / Timeline / Banner Link / Link List / Iconbox | **L3-resolved** | §8 で各ブロック仕様確定 |

---

## 10. L4-carry エントリ（Wave5 が carry-register に統合）

### C-A1-001: 外部 OGP 取得 — IP ブロック実装 + Action Scheduler ジョブ

| 項目 | 内容 |
|---|---|
| **id** | C-A1-001 |
| **原則** | SSRF 対策の内部 IP ブロックは L3 で契約として定義した（§2.2）。実装（PHPUnit でのモック検証含む）は L4 で実施する |
| **受入条件** | (1) `wp_safe_remote_get` 呼び出し前に解決 IP が RFC 1918 / リンクローカル / 169.254.x.x に該当するか検証する関数が存在する (2) PHPUnit で内部 IP → 拒否 / 外部 IP → 通過の 2 パターンをモック検証 (3) Action Scheduler で OGP フェッチジョブが登録・実行される |
| **sprint 着地案** | L4 Sprint S1（セキュリティ基盤 Sprint）|
| **T-ID 着地案** | T-EBC-IMPL-001 |
| **重大度** | 高（SSRF は P0 セキュリティリスク）|

### C-A1-002: balloon ブロック — JIN:R shortcode 互換変換ルール

| 項目 | 内容 |
|---|---|
| **id** | C-A1-002 |
| **原則** | JIN:R の `[fukidashi]` shortcode を AGENT NEO に移行する際の変換ルールは移行プラグイン（REQ-F-008）の実装スコープ。ADR への明記（「shortcode 互換は移行プラグイン変換ルールで提供し、テーマ側 add_shortcode 禁止」）は Wave5 carry-register 統合時に追記する |
| **受入条件** | (1) 移行プラグインが `[fukidashi]` shortcode を含む記事を AGENT NEO に移行した際、`agent-neo/balloon` ブロックとして正常変換される (2) `add_shortcode( 'fukidashi', ... )` がテーマ側コードに存在しないことを grep で検証 |
| **sprint 着地案** | L4 Sprint S3（移行プラグイン Sprint）|
| **T-ID 着地案** | T-BL-COMPAT-001 |
| **重大度** | 中（移行ユーザーへの影響） |

### C-A1-003: post-list — PV カウント（`post_views_count`）実装

| 項目 | 内容 |
|---|---|
| **id** | C-A1-003 |
| **原則** | `sortBy: "views"` 機能に必要な `post_views_count` カスタムフィールドの更新ロジック（記事閲覧時に increment する仕組み）は Core Plugin で実装する。L3 では `orderby=meta_value_num&meta_key=post_views_count` でソートする契約のみ定義した |
| **受入条件** | (1) 記事ページを表示するたびに `post_views_count` meta が +1 される（ボット / 管理者除外オプション付き） (2) `sortBy: "views"` で post-list を表示すると PV 降順に記事が並ぶ (3) PHPUnit で increment ロジックのモック検証 |
| **sprint 着地案** | L4 Sprint S2（コアブロック実装 Sprint）|
| **T-ID 着地案** | T-PL-VIEWS-001 |
| **重大度** | 中（`sortBy: views` はオプション機能） |

---

*作成: 2026-06-18 / Wave 4 ブロックカタログ担当 / 次アクション: Wave5 が §10 の carry エントリを carry-register.md に統合する*
