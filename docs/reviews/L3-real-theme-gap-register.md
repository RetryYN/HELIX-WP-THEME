# L3 実テーマギャップレジスタ（Real Theme Gap Register）

## 監査ソース

| 項目 | 内容 |
|---|---|
| 実テーマ ZIP 開封 | SWELL 2.16.0（`swell-2.16.0/`）/ JIN:R ※版数注意（ZIP は `jinr-20260428T161343Z-3-001.zip`＝古い可能性あり。正式は JIN:R 1.4.6 を要再確認） |
| 解析レポート | `解析レポート/` 配下 49 本（`01-`〜`41-`） |
| 並列監査体制 | 6体並列監査（実コード根拠化・PM観点統合考察を含む） |
| 設計ドキュメント参照先 | `docs/design/L2-design.md` / `docs/design/L3-detailed-design.md` / `docs/security/threat-model.md` / `docs/design/api-catalog.md` / `docs/design/data-model-ids.md` / `docs/test-plan/L3-test-plan.md` |

## FSE 転換の構造的前提

SWELL / JIN:R はどちらも**クラシックテーマ**（widgets + `functions.php` + `customizer` 中心）。AGENT-NEO はフルサイト編集（FSE / Block Theme）で設計されている。このため SWELL/JIN:R の customizer・ウィジェットエリア・PHP フィルター群は FSE 上では「直接移植不可」となる領域が広く、機能等価物を Block Pattern / theme.json / Block Binding API で再設計する必要がある。本レジスタはその「再設計コスト」を明示することも目的の一つ。

## JIN:R 版数注意

監査 ZIP `jinr-20260428T161343Z-3-001.zip` は旧版（1.0.5 相当の可能性）。設計参照先として挙げているファイル名・関数名は SWELL 2.16.0 側は確定、JIN:R 側は最新 1.4.6 での再確認が必要。GAP の重大度は保守的（高め）に設定している。

---

## 2026-06-21 disposition 同期（L3 close check / 本節が以下個別行の状態に優先する正本）

> L3 上下チェック（L1↔L2↔L3）で、PM裁定(2026-06-20)・VERIFIED(2026-06-21) が本レジスタの個別行・サマリに未伝播だった陳腐化を是正。**以下の最新状態が正本**であり、本文中の個別 GAP/CARRY 行・旧サマリ表の古い記述は本節で上書きされる（個別行の逐次修正は L4 hygiene で実施）。根拠正本: `docs/reviews/L3-PO-decision-packet.md`（PM-RESOLVED / VERIFIED）。

| 項目 | 旧状態 | 最新状態（正本） | 根拠 |
|---|---|---|---|
| GAP-RT-043（Q-005 ライセンス検証方式） | PO-ESCALATION | **PM-RESOLVED(2026-06-20)** Automation SEO 契約 entitlement 確認に統合 | PO-decision-packet §PM確定 |
| GAP-RT-045（Q-013 公開指標ポリシー） | PO-ESCALATION | **PM-RESOLVED(2026-06-20)** 安全側＝同意バナーあり前提 | 同上 |
| GAP-RT-048（Q-012 SNS フィード Phase 境界） | PO-ESCALATION | **PM-RESOLVED(2026-06-20)** フィードのみ Phase2 送り | 同上 |
| 純 OPEN（PO 裁定待ち）残 | 6 件（043〜048） | **3 件（GAP-RT-044/046/047）** | 同上 |
| CARRY-WP7-001（Abilities API 検証） | blocking=true（PO-WP7-01 待ち） | **blocking=false / VERIFIED(2026-06-21)** WP7.0 GA+6.9.4 で register→get→execute 実証 | PO-decision-packet PO-WP7-01 / poc/wp7-abilities |
| CARRY-ADR023-004（Bridge ショートコード変換） | blocking=true（PO 裁定待ち） | **blocking=false / PM-RESOLVED(2026-06-20)** 主要3種 Phase1 確定 | PO-decision-packet §PM確定 |
| PERF-CARRY-002（Cookie Consent） | blocking=true（Q-013 待ち） | **blocking=false / PM-RESOLVED(2026-06-20)** 外部 adapter 方式・Q-013 確定 | 同上 |
| CARRY-ADR023-001（S-DESIGN-TOKEN） | blocking=true | **blocking=true（維持）** ※PO 裁定 stale ではなく、L4 内のスプリント順序依存（design-token 先行）。L3 close を妨げない | — |

### L3 close 前の真の blocking 残（L4 sprint 着手前に解消 / PO 裁定 stale 由来は除外済み）
P0 実装系のみ: C-A1-001（SSRF）/ CARRY-A2-001/002/003/005（広告ゾーン・ad_tag CPT・event_type・disclosure）/ CARRY-A3-004（監査ログ）。これらは **L4 各 Sprint の初手タスクであり L3 設計クローズの妨げにはならない**（設計は確定済み、実装が L4）。

### WP7.0 固有機能の採否（新規 L4 carry 登録 / WP7-THEME-COMPLETENESS-AUDIT §C 由来）
| carry-id | 内容 | 解消条件 | 優先度 | blocking |
|---|---|---|---|---|
| **CARRY-WP7-013** | Block Bindings API の採用/非採用を ADR で凍結 | L4 entry / theme scaffold 着手前に決定（参照: WP7-THEME-COMPLETENESS-AUDIT.md §C） | P1 | false |
| **CARRY-WP7-014** | Interactivity API の採用/非採用を ADR で凍結 | 同上（a11y 実装方式と連動） | P1 | false |
| **CARRY-WP7-015** | Section Styles（theme.json）の採用/非採用を ADR で凍結 | 同上（theme.json v3 構造に影響） | P1 | false |

---

## ギャップ一覧

| GAP-ID | カテゴリ | 内容 | 実コード・解析根拠 | 設計側カバー状態 | 重大度 | 振り分け先 | 状態 |
|---|---|---|---|---|---|---|---|
| GAP-RT-001 | A-1 ブロック仕様 | Blog Card（内部リンクカード）の専用ブロック仕様（URL入力→oEmbed/内部REST取得→カード描画）が設計に未定義 | SWELL: `inc/block/post-link/` + `rest-api/` / 解析レポート `30-SWELL-block-cpt-rest-deep-extract.md` | MISSING | 高 | L3-patch | **RESOLVED-IN-L3**（L3-A1 §1 でブロックカタログ設計補完済み） |
| GAP-RT-002 | A-1 ブロック仕様 | 外部 Blog Card（外部 URL → OGP 取得）の SSRF リスク対策仕様が未定義。JIN:R は `file_get_contents` 直叩き実装 | JIN:R: `blogcard.php` + `external_url` parameter（`file_get_contents` raw call） / 解析レポート `14-JINR-seo-hero-deep-extract.md` | MISSING | 高 | L3-patch / ADR | **RESOLVED-IN-L3**（L3-A1 §2 SSRF 契約定義済み）+ **CARRY-TO-L4**（→ C-A1-001: IP ブロック実装 + Action Scheduler 実装）|
| GAP-RT-003 | A-1 ブロック仕様 | 吹き出し（balloon）ブロック仕様: SWELL は専用 DBテーブル（`swell_balloons`）+ 5本 REST CRUD を持ち、キャラクター管理・位置・テール形状を構造データで保持 | SWELL: `inc/cpt/balloon/` + `rest-api/balloon.php`（`GET/POST/PUT/DELETE/PATCH` 5本）/ 解析レポート `06-設計参考とパーツカタログ.md` | MISSING | 高 | L3-patch | **RESOLVED-IN-L3**（L3-A1 §3 balloon ブロック仕様補完済み）|
| GAP-RT-004 | A-1 ブロック仕様 | 吹き出し（balloon）ブロック仕様: JIN:R は shortcode ベース（`[fukidashi]`）。FSE 移行ではショートコード互換レイヤーの扱いを ADR に明記する必要がある | JIN:R: `inc/shortcode/fukidashi.php` | MISSING | 高 | ADR | **CARRY-TO-L4**（→ C-A1-002: balloon JIN:R shortcode 互換変換ルール実装）|
| GAP-RT-005 | A-1 ブロック仕様 | Restricted Area（会員限定・期間限定・ページ種別条件付き表示）ブロックの仕様が設計に存在しない | SWELL: `inc/block/restricted-area/` + customizer 設定 / 解析レポート `02-機能別特徴分析.md` | MISSING | 高 | L3-patch | **RESOLVED-IN-L3**（L3-A1 §4 Restricted Area ブロック仕様補完済み）|
| GAP-RT-006 | A-1 ブロック仕様 | Related Post / Post List バリアントの詳細仕様（PVソート・AJAX loadmore パラメータ・ページネーション）が未定義 | SWELL: `inc/block/post-list/`（`sort_by=views` パラメータ + AJAX endpoint）/ JIN:R: `inc/block/postlist/` / 解析レポート `02-機能別特徴分析.md` | MISSING | 中 | L3-patch | **RESOLVED-IN-L3**（L3-A1 §5 スキーマ・REST 契約定義済み）+ **CARRY-TO-L4**（→ C-A1-003: `post_views_count` PV カウント実装）|
| GAP-RT-007 | A-1 ブロック仕様 | TOC（目次）自動生成の仕様（対象見出しレベル・スクロール連動・customizer 制御）が未定義 | SWELL: `assets/js/toc.js` + customizer 設定 / 解析レポート `15-ページスピード設計比較.md` | MISSING | 中 | L3-patch | **RESOLVED-IN-L3**（L3-A1 §6 TOC 仕様補完済み）|
| GAP-RT-008 | A-1 ブロック仕様 | FAQ（`loos/faq`）ブロックの JSON-LD 生成（FAQPage schema）・accordion 動作仕様が未定義 | SWELL: `inc/block/loos/faq/` / JIN:R: `inc/block/faq/` / 解析レポート `30-SWELL-block-cpt-rest-deep-extract.md` | MISSING | 中 | L3-patch | **RESOLVED-IN-L3**（L3-A1 §7 FAQ ブロック仕様補完済み）|
| GAP-RT-009 | A-1 ブロック仕様 | Step / Tab / Timeline / Banner Link / Link List / Iconbox 各ブロックのバリアント仕様（子ブロック数・allowedBlocks・inner spacing）が未定義 | SWELL: `inc/block/loos/` 配下複数ディレクトリ / JIN:R: 各 block ディレクトリ / 解析レポート `06-設計参考とパーツカタログ.md` | MISSING | 中 | L3-patch | **RESOLVED-IN-L3**（L3-A1 §8 バリアント仕様補完済み）|
| GAP-RT-010 | A-2 広告・収益化 | 広告ゾーン管理（H2前挿入・記事終・関連上・カテゴリ別上書き）の設計が存在しない。JIN:R は PHPフィルターで 4ゾーンを実装 | JIN:R: `template-parts/ad-finish.php` + `ad-related.php` + `functions.php` category override filter / 解析レポート `07-計測と連携性分析.md` | MISSING | 高 | L3-patch | **CARRY-TO-L4**（→ CARRY-A2-001: ad-zone.schema.json 生成 + REST 4 本実装）|
| GAP-RT-011 | A-2 広告・収益化 | ad_tag CPT（Custom Post Type）の詳細スキーマ（amazon / affiliate / ranking / normal / text の 5 分岐・REST 計測エンドポイント）が未定義 | SWELL: `inc/cpt/ad_tag/` + `rest-api/ad_tag.php`（impression/click 計測 REST）/ 解析レポート `30-SWELL-block-cpt-rest-deep-extract.md` | MISSING | 高 | L3-patch | **CARRY-TO-L4**（→ CARRY-A2-002: agent_neo_ad_tag CPT + 5 分岐スキーマ + REST 5 本実装）|
| GAP-RT-012 | A-2 広告・収益化 | Tracking event の細粒度設計が不足。現設計は impression/click/conversion の 3 値のみ。実テーマは `ad_impression` / `affiliate_click` / `scroll_depth` / `view_time` を個別に計測 | SWELL `rest-api/tracking.php` + JS `tracking.js`（`scroll_depth` threshold 25/50/75/100%, `view_time` 秒）/ 解析レポート `07-計測と連携性分析.md` | PARTIAL | 高 | L3-patch / test-plan | **CARRY-TO-L4**（→ CARRY-A2-003: event_type 拡張 enum + フロント JS 実装）|
| GAP-RT-013 | A-2 広告・収益化 | 外部アフィリエイト CSS adapter（かえるべあ / appreach / Amazon アソシエイト）の注入仕様が未定義 | JIN:R: `assets/css/others/` 配下（`appreach.css` / `amazon.css` 等）/ 解析レポート `33-plugin-compat-style-variations-deep-extract.md` | MISSING | 中 | L3-patch | **CARRY-TO-L4**（→ CARRY-A2-004: affiliate-css-adapter 実装）|
| GAP-RT-014 | A-2 広告・収益化 | Disclosure（PR表記・ステマ規制対応）ブロックの詳細仕様（表示テキスト・配置ルール・景表法 REQ-NF-009 との紐づけ）が未定義 | 解析レポート `20-運用セキュリティ可用性更新性分析.md` / 設計: `docs/requirements/` REQ-NF-009 | MISSING | 高 | L3-patch | **CARRY-TO-L4**（→ CARRY-A2-005: disclosure ブロック実装 / CARRY-A2-006: 広告収益化ダッシュボード）|
| GAP-RT-015 | A-3 SEO / E-E-A-T | Author / E-E-A-T schema（Person / Organization / jobTitle / sameAs を WP user meta で管理し JSON-LD 出力）の仕様が未定義 | SWELL: `inc/class/Meta_User.php`（`sameAs` / `jobTitle` / `sns_*` 各 user meta）/ JIN:R: `inc/json-ld.php`（Person schema 生成）/ 解析レポート `14-JINR-seo-hero-deep-extract.md` | MISSING | 高 | L3-patch | **RESOLVED-IN-L3**（L3-A3 §2 Author E-E-A-T スキーマ補完済み）+ **CARRY-TO-L4**（→ CARRY-A3-003: sameAs 候補提案 API、Phase 2）|
| GAP-RT-016 | A-3 SEO / E-E-A-T | OGP / meta description の生成責務境界が未確定。SWELL は外部プラグイン（Yoast/AIOSEO）に全委任、JIN:R は内蔵出力。ADR-005 が曖昧なまま | 解析レポート `13-SEO設計比較-JINR優先分析.md` + `14-JINR-seo-hero-deep-extract.md` / `docs/adr/ADR-005.md` | MISSING | 高 | ADR | **RESOLVED-IN-L3**（ADR-022 で OGP/meta 責務境界を確定済み）|
| GAP-RT-017 | A-3 SEO / E-E-A-T | 任意タグ入力 UI（`<head>` / `<body>` 挿入: GA4 / GTM / AdSense / Search Console 認証タグ）の仕様が未定義 | JIN:R: `inc/head/tags.php` + 管理画面設定フォーム / 解析レポート `22-AIエージェント運用性とクローラビリティ分析.md` | MISSING | 高 | L3-patch | **RESOLVED-IN-L3**（L3-A3 §3 第三者タグ 5 層設計定義済み）+ **CARRY-TO-L4**（→ CARRY-A3-002: third-party-tags.schema.json 統合仕様 / CARRY-A3-004: 監査ログストレージ設計）|
| GAP-RT-018 | A-3 SEO / E-E-A-T | FAQPage JSON-LD（FAQ ブロック render 時の自動生成）の出力仕様・条件（複数 FAQ 存在時のマージ）が未定義 | SWELL / JIN:R: `inc/json-ld.php` 内 FAQPage 生成ロジック / 解析レポート `24-LLMO時代のテーマ設計重要観点.md` | MISSING | 中 | L3-patch | **RESOLVED-IN-L3**（L3-A3 §4 FAQPage 出力仕様補完済み）+ **CARRY-TO-L4**（→ CARRY-A3-005: FAQ 重複 question マージ警告 UI）|
| GAP-RT-019 | A-3 SEO / E-E-A-T | SEO Core 4 契約スキーマ（`seo-profile.schema.json` / `seo-meta.schema.json` / `entity-graph.schema.json` / `seo-conflict-rules.schema.json`）の L3 レベル定義が欠落 | 設計: `docs/design/api-catalog.md` §SEO 節（スキーマ名のみ列挙、内容未定義）/ 解析レポート `22-AIエージェント運用性とクローラビリティ分析.md` | MISSING | 高 | L3-patch | **RESOLVED-IN-L3**（L3-A3 §1 4 スキーマ定義完了。CARRY-A3-001 は ADR-022 で解消済み）|
| GAP-RT-020 | A-3 SEO / E-E-A-T | SNS hashtag の post meta 管理（`_sns_hashtags`）とシェア URL 付与仕様が未定義 | JIN:R: `inc/social/share.php`（`get_post_meta( $id, '_sns_hashtags' )`）/ 解析レポート `07-計測と連携性分析.md` | MISSING | 中 | L3-patch | **RESOLVED-IN-L3**（L3-A3 §5 SNS シェア仕様補完済み）|
| GAP-RT-021 | A-4 パフォーマンス | `critical-css.schema.json` が未定義。Critical CSS 抽出・インライン化の仕様・トリガー条件が設計に存在しない | 解析レポート `15-ページスピード設計比較.md` + `32-SWELL-asset-customizer-pipeline-deep-extract.md` / 設計: 言及なし | MISSING | 高 | L3-patch | **RESOLVED-IN-L3**（L3-A4 §1 スキーマ定義 + ADR-021 で critical v7.2+ 採用確定済み。PERF-CARRY-001 は ADR-021 に統合して解消）|
| GAP-RT-022 | A-4 パフォーマンス | `third-party-tags.schema.json` が未定義。GTM / GA4 / 広告タグの同意後ロード戦略（consent mode v2）のスキーマ契約が欠落 | 解析レポート `15-ページスピード設計比較.md` / 設計: `docs/design/L2-design.md §8.1`（`render blocking third-party: 0` 宣言のみ） | MISSING | 高 | L3-patch | **RESOLVED-IN-L3**（L3-A4 §2 スキーマ定義済み）+ **CARRY-TO-L4**（→ PERF-CARRY-002: Cookie Consent バナー選定・実装 / P1 blocking、Q-013 PO 裁定待ち）|
| GAP-RT-023 | A-4 パフォーマンス | `font-policy.schema.json` が未定義。Google Fonts 選択 UI・サブセット・preload 制御の仕様が設計に存在しない | SWELL: `inc/customizer/font/` + Fonts API / 解析レポート `32-SWELL-asset-customizer-pipeline-deep-extract.md` | MISSING | 中 | L3-patch | **RESOLVED-IN-L3**（L3-A4 §3 スキーマ定義済み）+ **CARRY-TO-L4**（→ PERF-CARRY-003: Google Fonts API キー管理方式）|
| GAP-RT-024 | A-4 パフォーマンス | Web Vitals RUM 送信経路（LCP / CLS / INP の実測値を AGENT-NEO が受信→蓄積）のスキーマ・エンドポイント仕様が未定義 | 解析レポート `15-ページスピード設計比較.md` + `31-WPテーマ実測解析.md` / 設計: 言及なし | MISSING | 高 | L3-patch | **RESOLVED-IN-L3**（L3-A4 §4 スキーマ定義済み）+ **CARRY-TO-L4**（→ PERF-CARRY-004: web-vitals npm バージョン固定）|
| GAP-RT-025 | A-4 パフォーマンス | コンテンツ遅延 REST（SWELL `lazyload-contents` endpoint・遅延ロードブロック識別フラグ）の仕様が未定義 | SWELL: `rest-api/lazyload.php` / 解析レポート `15-ページスピード設計比較.md` | MISSING | 中 | L3-patch | **RESOLVED-IN-L3**（L3-A4 §5 REST EP 定義済み）+ **CARRY-TO-L4**（→ PERF-CARRY-005: 遅延ブロックサーバーキャッシュ設計）|
| GAP-RT-026 | A-4 パフォーマンス | カスタム画像サイズ定義（`add_image_size` で追加した AGENT-NEO 固有サイズ名とトリミング基準）が設計に存在しない | 解析レポート `32-SWELL-asset-customizer-pipeline-deep-extract.md` / 設計: 言及なし | MISSING | 中 | L3-patch | **RESOLVED-IN-L3**（L3-A4 §6 画像サイズ定義済み）+ **CARRY-TO-L4**（→ PERF-CARRY-006: an-hero-pc WebP 生成接続仕様、P3）|
| GAP-RT-027 | B WP7 先回り | WordPress 7.0 Abilities API（`WP_Block_Type_Registry::get_abilities()`）との互換 CI マトリクスが未設計。ブロック機能フラグが WP 7.0 で変わると登録ロジックが破綻しうる | 解析レポート `38-WP7事前情報とテーマ対応検証.md` / 設計: 言及なし | MISSING | 高 | ADR | **RESOLVED-IN-L3**（ADR-020 D-1 / D-2 で Abilities API 互換 CI マトリクス設計済み）+ **CARRY-TO-L4**（→ CARRY-WP7-001〜005、PO 論点: PO-WP7-01 承認待ち）|
| GAP-RT-028 | B WP7 先回り | 共同編集（Collaborative Editing、WP 7.0 予定）における同時書き込み衝突の対処方針が未設計。AGENT-NEO が REST で記事を書き換えるタイミングと編集者の衝突が起きうる | 解析レポート `38-WP7事前情報とテーマ対応検証.md` | MISSING | 高 | ADR | **RESOLVED-IN-L3**（ADR-020 D-3 で 423 Locked 方針確定）+ **CARRY-TO-L4**（→ CARRY-WP7-005、PO 論点: PO-WP7-02 裁定待ち）|
| GAP-RT-029 | B WP7 先回り | PHP 8.2 〜 8.5 互換マトリクス（deprecated dynamic properties / 型チェック強化）の CI テストが未設計 | 解析レポート `38-WP7事前情報とテーマ対応検証.md` / 設計: `docs/design/L2-design.md`（PHP バージョン記載なし） | MISSING | 高 | ADR | **RESOLVED-IN-L3**（ADR-020 D-2 + ADR-021 §A で PHP 8.1+ 推奨・PHP 互換 CI 設計済み）|
| GAP-RT-030 | B WP7 先回り | WordPress.org Theme Check / Abilities API に対応した自動 CI パイプライン設計が未定義（WP 7.0 でテーマレビュー要件が変わる可能性） | 解析レポート `38-WP7事前情報とテーマ対応検証.md` | MISSING | 高 | ADR | **RESOLVED-IN-L3**（ADR-020 D-2 で Theme Check CI 設計済み）+ **CARRY-TO-L4**（→ CARRY-WP7-003: wp.org 申請要否は PO-escalation Q-006 で確定後）|
| GAP-RT-031 | B OSS/CI ツールチェーン | PHPCompatibilityWP（PHP 非互換検出ツール）の導入・CI 組み込みが ADR に未記載 | 解析レポート `40-OSSライブラリ技術転用候補.md` | MISSING | 中 | ADR | **RESOLVED-IN-L3**（ADR-021 §A PHPCompatibilityWP 採用・CI 組み込み確定済み）|
| GAP-RT-032 | B OSS/CI ツールチェーン | PHPStan（静的解析）の導入レベル（`level` 設定・baseline）が ADR に未記載 | 解析レポート `40-OSSライブラリ技術転用候補.md` | MISSING | 中 | ADR | **RESOLVED-IN-L3**（ADR-021 §B PHPStan level / baseline 確定済み）|
| GAP-RT-033 | B OSS/CI ツールチェーン | `opis/json-schema`（PHP 側スキーマ検証）の採用可否・バージョン固定方針が ADR に未記載 | 解析レポート `40-OSSライブラリ技術転用候補.md` | MISSING | 中 | ADR | **RESOLVED-IN-L3**（ADR-021 §C-1 opis/json-schema 採用・バージョン固定確定済み）|
| GAP-RT-034 | B OSS/CI ツールチェーン | Ajv（JS 側 JSON Schema 検証）のバージョン・`strict` モード設定が ADR に未記載 | 解析レポート `40-OSSライブラリ技術転用候補.md` | MISSING | 中 | ADR | **RESOLVED-IN-L3**（ADR-021 §C-2 Ajv バージョン・strict モード確定済み）|
| GAP-RT-035 | B OSS/CI ツールチェーン | `composer audit`（依存脆弱性スキャン）の CI 組み込みと fail 基準が ADR に未記載 | 解析レポート `40-OSSライブラリ技術転用候補.md` | MISSING | 中 | ADR | **RESOLVED-IN-L3**（ADR-020 D-2 + ADR-021 §D composer audit CI 組み込み確定済み）|
| GAP-RT-036 | B テスト計画 | ACC-NF-007（運用品質: 更新耐性・バックワード互換）に独立 TC が存在しない | `docs/test-plan/L3-test-plan.md`（ACC-NF-007 はカバレッジ対象外）/ 解析レポート `29-ドキュメントテーマ群抜け漏れレビュー.md` | MISSING | 高 | test-plan | **RESOLVED-IN-L3**（L3-test-plan §8.1 で TC-031〜036 を追加。PHP 8.3/8.4/8.5 互換 TC は L4 carry）|
| GAP-RT-037 | B テスト計画 | ACC-NF-011（LLMO: AI クローラビリティ・構造化データ品質）に独立 TC が存在しない | `docs/test-plan/L3-test-plan.md`（ACC-NF-011 はカバレッジ対象外）/ 解析レポート `24-LLMO時代のテーマ設計重要観点.md` | MISSING | 高 | test-plan | **RESOLVED-IN-L3**（L3-test-plan §8.2 で TC-037〜041 を追加。LLMO ダッシュボードは Phase 2 carry）|
| GAP-RT-038 | B テスト計画 | Cookie Consent Gate（同意バナー表示→スクリプト発火タイムライン）の TC が存在しない | 解析レポート `20-運用セキュリティ可用性更新性分析.md` / `docs/test-plan/L3-test-plan.md`（TC-028 は Lighthouse render-blocking に特化、consent タイムラインは別） | MISSING | 高 | test-plan | **RESOLVED-IN-L3**（L3-test-plan §8.3 で TC-042〜046 を追加）+ **CARRY-TO-L4**（→ PERF-CARRY-002 blocking: バナープラグイン選定前は Mock 差し替えで実施）|
| GAP-RT-039 | B テスト計画 | Snapshot allowlist（公開 snapshot から draft/private/nonce/license が漏洩しないことを保証するセキュリティ TC）が存在しない。※ 視覚回帰（style-diff 承認フロー / BackstopJS）とは別軸であり、視覚回帰は MG-009 系として ADR-021 PoC BackstopJS・L4-L5 carry で追跡する | 解析レポート `19-デザインUI思想逆引き設計.md` / 設計: 言及なし | MISSING | 中 | test-plan | **RESOLVED-IN-L3**（L3-test-plan §8.4 で TC-047〜051 を追加。TC-047〜051 は情報漏洩防止 TC であり、視覚回帰 TC は含まない）|
| GAP-RT-040 | B テスト計画 | Canonical URL と OGP URL の同時評価 TC（同一ページで両属性の整合）が存在しない | 解析レポート `13-SEO設計比較-JINR優先分析.md` | MISSING | 中 | test-plan | **RESOLVED-IN-L3**（L3-test-plan §8.5 で TC-052〜055 を追加。TC-053 は CARRY-A3-001 / ADR-022 確定後に期待結果を更新）|
| GAP-RT-041 | B テスト計画 | 移行 SEO TC（旧テーマ→AGENT-NEO 移行時の 301 リダイレクト・permalink 保持）が存在しない | 解析レポート `23-テーマ構築観点総合レビュー.md` | MISSING | 中 | test-plan | **RESOLVED-IN-L3**（L3-test-plan §8.6 で TC-056〜060 を追加。TC-060 bit-identical 条件は Q-007 解決後に更新）|
| GAP-RT-042 | B FSE 転換コスト | SWELL / JIN:R はクラシックテーマ。AGENT-NEO は FSE（Block Theme）。customizer・ウィジェット・PHP フィルターの直接移植不可領域の範囲と代替実装方針（Block Pattern / theme.json / Block Binding API）が ADR に未明示 | `swell-2.16.0/functions.php`（`add_theme_support( 'customize-selective-refresh-widgets' )`等）/ 解析レポート `18-テーマコーディングルール逆引き設計.md` | MISSING | 高 | ADR | **RESOLVED-IN-L3**（ADR-023 で FSE 再設計コスト方針確定）+ **CARRY-TO-L4**（→ CARRY-ADR023-001〜004）|
| GAP-RT-043 | C PO 裁定待ち | Q-005: ライセンス検証方式（wp.org API 参照 vs 自社 license server）の採用可否が未確定 | `docs/design/L2-design.md §8.6` / `docs/security/threat-model.md §TB-18a` | OPEN | 高 | PO-escalation | **PO-ESCALATION**（L3-PO-decision-packet Q-005: L4 着手前必須裁定。Freemius 推奨）|
| GAP-RT-044 | C PO 裁定待ち | Q-006: wp.org への機能ロック（`is_plugin_active` ゲート）の粒度・フォールバック方針が未確定 | `docs/design/L2-design.md §8.6` | OPEN | 高 | PO-escalation | **PO-ESCALATION**（L3-PO-decision-packet Q-006: L4 着手前必須裁定。移行プラグインのみ wp.org 申請推奨）|
| GAP-RT-045 | C PO 裁定待ち | Q-013: 公開パフォーマンス指標（Web Vitals / CWV スコア）の公開ポリシー（ユーザー向け開示レベル）が未確定 | 解析レポート `26-競合テーマ総合評価と市場ポジション.md` | OPEN | 中 | PO-escalation | **PO-ESCALATION**（L3-PO-decision-packet Q-013: L4 着手前必須裁定。同意バナー必須推奨。PERF-CARRY-002 の前提）|
| GAP-RT-046 | C PO 裁定待ち | Q-003: S1 プラン価格帯（月額・年額・初期費）の確定。移行 PG ユーザーへの課金フローが未確定 | `docs/design/L2-design.md §ライセンス` / 解析レポート `09-価格戦略と売れる理由.md` | OPEN | 高 | PO-escalation | **PO-ESCALATION**（L3-PO-decision-packet Q-003: L7 前でOK。¥300,000〜¥500,000 明示推奨）|
| GAP-RT-047 | C PO 裁定待ち | Q-004: 移行プログラム（PG）ユーザーへの課金開始タイミング・猶予期間の確定 | `docs/design/L2-design.md §ライセンス` | OPEN | 高 | PO-escalation | **PO-ESCALATION**（L3-PO-decision-packet Q-004: L4 途中まで猶予。無料・単独配布推奨）|
| GAP-RT-048 | C PO 裁定待ち | Q-012: F-018「プロフィール表示 / SNS フィードウィジェット」の ACC 適合レベル / Phase 1・2 スコープ決定（PO 裁定）。Phase 1 対象範囲と Phase 2 送りウィジェットの境界が未確定 | `docs/requirements/` F-018 / `docs/test-plan/L3-test-plan.md` ACC-NF | OPEN | 中 | PO-escalation | **PO-ESCALATION**（L3-PO-decision-packet Q-012: L4 着手前必須裁定。SNS フィードウィジェットは Phase 2 送り推奨。CARRY-ADR023-002 と連動）|
| GAP-RT-049 | C 設計内部バグ | L1 ACC-NF 番号不整合: 設計書上の ACC-NF-002〜007 が実際は ACC-NF-008〜013 を指している（採番が 6 ずれている）。テスト計画との突合が壊れている | `docs/requirements/` ACC-NF 節 vs `docs/test-plan/L3-test-plan.md` CAT 節 | MISSING | 高 | L1-fix | **RESOLVED-IN-L3**（L1 整合補正パッチ適用済み / 2026-06-18。`docs/requirements/L1-requirements.md` で ACC-NF-016〜021 追加・採番整合修正により解消）|
| GAP-RT-050 | C 設計内部バグ | REQ-NF-001〜007 のトレーサビリティが L1→L3 を通じて欠落。テスト計画・詳細設計のどこで対処されるかが未連結 | `docs/requirements/` REQ-NF 節 / `docs/design/L3-detailed-design.md`（REQ-NF-001〜007 への明示的ポインタなし） | MISSING | 高 | L1-fix | **RESOLVED-IN-L3**（L1 整合補正パッチ適用済み / 2026-06-18。`docs/requirements/L1-requirements.md` §9 トレーサビリティ補完（REQ-NF-001〜007 → L3 設計ポインタ追記）により解消）|
| GAP-RT-051 | C 設計内部バグ | L5 カラートークン `#xxxx` 形式の未確定値が設計書内に 11 箇所。G5 デザイン凍結未判定のため L4 実装でハードコードされるリスク | `docs/design/L5-visual-design.md`（カラートークン節）/ G5 ゲート状態: 未実施 | DEFERRED | 中 | L1-fix | **RESOLVED-IN-L3**（L5-visual-design.md で全カラートークン確定済み / 2026-06-18。`#xxxx` 残ゼロ（grep 確認済み）。CARRY-ADR023-001 S-DESIGN-TOKEN スプリントと連動済み）|
| GAP-RT-052 | セキュリティ精査（外部AI操作境界） | WP7 Abilities API（ADR-020）の**公開スコープ未定義**。`wp_register_ability()` の ability 宣言が WP REST 経由で機械可読公開されると、AGENT NEO の内部操作構造（エンドポイント/操作可否）が第三者AIに露出する情報漏洩になりうる | `docs/security/threat-model.md` §3／§5.1／TB-19 / `docs/adr/ADR-020.md`（D-1: `wp_register_ability()` 宣言 / 公開 REST 前提の記述）/ `docs/design/api-catalog.md`（公開 API 一覧） | MISSING | **低**（ADR-024 前提で露出リスク低減: 外部AI write 受口を廃止（REQ-F-043 廃止）したため Abilities API が外部AIに悪用される攻撃経路が消滅。READ 系メタ公開のみ残存するため重大度を 中→低 に見直し） | ADR補足 / L4-carry | **CARRY-TO-L4**（ADR-020 補足として: Abilities レスポンスを `agent-neo/v1` 認証必須エンドポイントから提供する or 公開参照は操作 ID のみに限定する方針を L4 entry 前に確定。CARRY-SEC-001 として L4 CI/セキュリティ Sprint で実装。ADR-024 により外部AI write 受口廃止のため当初リスクより低減済み）|
| GAP-RT-053 | セキュリティ精査（外部AI操作境界） | **REQ-F-043 Open Editor Bridge Plugin の OAuth 申請フロー未定義**。許可リスト済み外部AIエディタ（Claude/Codex/Cursor/Cline/Continue + 自前OAuth申請）の write を許可する有料アドオンだが、OAuth の発行主体・scope 制限・審査フロー・revoke 手順が未定義。設計を誤ると第三者AIの write 受け口が意図せず開く | L1 `docs/requirements/` REQ-F-043 / `docs/security/threat-model.md` §5.1（外部AI write 攻撃面）/ `docs/design/api-catalog.md`（公開 API 一覧） | MISSING | 中 | PO-ESCALATION / security | **RESOLVED-BY-DECISION**（2026-06-18 / ADR-024）: REQ-F-043 廃止により外部AI write 受口が消滅し OAuth 設計は不要。AI 操作は Automation SEO 経由のみに一本化。PO-ESCALATION から除外。|
| GAP-RT-054 | セキュリティ精査（外部AI操作境界） | `mcp-tools.schema.json` が L3 設計書で参照されるのみで**実ファイルが docs 内に不在**。L4 で tool allowlist 境界が曖昧化しないよう、L4 entry 時に実 schema を確認/作成する必要がある | `docs/security/threat-model.md` §5.1（TB-19: tool allowlist 境界）/ `docs/design/L3-detailed-design.md`（`mcp-tools.schema.json` 参照箇所）/ `docs/design/api-catalog.md` | MISSING | 低 | L4-carry | **CARRY-TO-L4**（L4 entry 時に `mcp-tools.schema.json` の実ファイル存在を確認し、未存在なら作成。CARRY-SEC-002 として L4 セキュリティ Sprint 冒頭タスクに追加）|
| GAP-RT-055 | D 法規制対応 | **AI 生成コンテンツ開示の法規制**（EU AI Act Article 50 / California SB 942 / C2PA v2.4）が本製品の AI 記事量産ユースケースに正面から適用される。これらの法規制に対する対応方針・責務帰属・テーマ側の関与範囲が設計ドキュメントに未収載であった | EU AI Act Article 50（2026-08-02 施行 / 既存システム 2026-12-02 猶予）/ California SB 942（2026-01-01 施行済み）/ C2PA v2.4 / ADR-024 §Decision §1〜3（AI write 操作の Automation SEO 経由一本化）/ REQ-NF-025（AI 判断ロジック完全分離） | MISSING | **高**（EU AI Act の猶予期限 2026-12-02 が L4 実装サイクル内に到来。施行済みの SB 942 も対象となりうる） | ADR / L4-carry | **RESOLVED-BY-DECISION**（2026-06-20 / ADR-025）: Automation SEO 登録時の同意に開示責務を集約。マーキングロジックは Automation SEO 側。AGENT-NEO は disclosure レンダリングフック（スロット / schema.org creator フィールド）のみ提供。詳細は ADR-025 参照。L4 carry CARRY-ADR025-001〜005 として継続管理。|

| GAP-RT-056 | A-4 パフォーマンス（enforce 機構） | ページ別アセット分離 enforce 機構（`should_load_separate_core_block_assets` / Speculative Loading / Font Library ローカル配信）が設計に記載されていなかった。「無駄JS禁止」原則を enforce 可能にする具体機構の方針が未追記 | `docs/research/wp-ecosystem-20260620.md` §性能 / REQ-NF-001a / ADR-006 | MISSING（addendum 追記前） | 中 | L3-patch（addendum追記） | **RESOLVED-IN-L3 + CARRY-TO-L4**（L3-A4 addendum §2026-06-20 追記で方針確定 / 具体実装・閾値は PERF-CARRY-007〜009 / L4 carry）|
| GAP-RT-057 | L5 アクセシビリティ（新 5 要件） | WordPress.org accessibility-ready 基準が 2026-05-06 に改定（WCAG 2.2 AA ベース / 新 5 要件）されたが L5 設計書に反映されていなかった。再評価期限 2026-06-30 を考慮すると L4 着手前に受入条件確定が必要 | `docs/research/wp-ecosystem-20260620.md` §アクセシビリティ / WordPress.org accessibility-ready 改定（2026-05-06）/ L5-visual-design.md §5 | MISSING（L5 追記前） | 高 | L3-patch（L5追記） | **RESOLVED-IN-L3 + CARRY-TO-L4**（L5-visual-design.md §5.1.A に新 5 要件を追記済み / 具体 TC は CARRY-A11Y-001 として L4 着手前に test-plan **§11（TC-074〜078）** に登録済み / 再評価期限 2026-06-30）|
| GAP-RT-058 | B 埋め込みブロック未設計 | Automation SEO が生成するフリーフォーム自己完結 HTML（図解 / ゲーム / 診断）を投稿・固定ページ等の任意のブロック編集領域に差し込める特殊ブロック（`agent-neo/embed`）として CSS 隔離・セキュリティ隔離で実装する設計が存在しなかった。テーマ CSS との相互干渉・XSS 封じ込め・SEO 非indexed 領域の管理方針が未定義 | REQ-NF-001a / REQ-NF-025 / `docs/research/wp-ecosystem-20260620.md` | MISSING | 高 | ADR | **RESOLVED-IN-L3 + CARRY-TO-L4**（ADR-026 で dual-mode 設計・セキュリティ原則・ADR 整合を確定 / 投稿・固定ページ双方での利用方針を明記。**dual-mode 設計概要: mode=interactive = 別オリジン sandbox iframe（`<iframe src="https://<sandbox-origin>/embed/{id}" sandbox="allow-scripts">` / Automation SEO が生成・ホスト・配信する専用サブドメイン / CSP はサンドボックスオリジン HTTP ヘッダで配信 / 親ページは frame-src で sandbox-origin のみ allowlist / postMessage は `allow-same-origin` を外した sandbox のため `event.origin` は opaque（`"null"`）になり特定 origin 一致照合は機能しない。テーマ側は **`event.source === iframe.contentWindow`**（送信元 Window 照合）+ **iframe 生成時に埋め込む一意トークン（nonce / payload-id）のペイロード内検証** を主軸とし `event.origin === <sandbox-origin>` の特定 origin 一致は要求しない（ADR-026 / TC-068 と整合）/ standalone・個人版では static のみ）; mode=static = Shadow DOM + DSD（Declarative Shadow DOM）。旧 srcdoc / opaque origin モデルは廃止。** / 具体 block.json / sandbox 属性最終セット / CSP 文字列 / sanitize allowlist / DSD 詳細は CARRY-EMBED-001〜006 として L4 carry）|

---

## 振り分け先別サマリ

| 振り分け先 | 件数 | GAP-ID |
|---|---|---|
| **L3-patch** | 20 | GAP-RT-001〜009, 010〜012, 014〜015, 017〜021, 024〜026 |
| **L3-patch / ADR** | 2 | GAP-RT-002, 012（重複カウント除外のため ADR 列で計上） |
| **L3-patch / test-plan** | 1 | GAP-RT-012 |
| **ADR** | 9 | GAP-RT-002（兼掲載）, 004, 016, 027, 028, 029, 030, 031〜035, 042 |
| **test-plan** | 5 | GAP-RT-036, 037, 038, 039, 040, 041 |
| **PO-escalation** | 6 | GAP-RT-043, 044, 045, 046, 047, 048 |
| **L1-fix** | 3 | GAP-RT-049, 050, 051 |
| **セキュリティ精査（外部AI操作境界）** | 3 | GAP-RT-052, 053, 054 |
| **法規制対応（ADR）** | 1 | GAP-RT-055 |

### 振り分け先別（重複なし・主分類ベース）

| 振り分け先 | 件数 |
|---|---|
| L3-patch | 19 件（GAP-RT-001〜026 のうち主分類が L3-patch のもの） |
| ADR / L4-carry | 11 件（GAP-RT-004, 016, 027, 028, 029, 030, 031, 032, 033, 034, 035, 042 のうち L3-patch との兼掲載除く + GAP-RT-052（ADR補足/L4-carry）/ GAP-RT-054（L4-carry））|
| test-plan | 6 件（GAP-RT-036〜041） |
| PO-escalation | 6 件（GAP-RT-043〜048）※ GAP-RT-053 は 2026-06-18 ADR-024 により RESOLVED-BY-DECISION に変更 |
| L1-fix | 3 件（GAP-RT-049〜051） |
| **法規制対応（ADR）** | **1 件（GAP-RT-055）** |
| **L3-patch（addendum 追記 / 2026-06-20）** | **2 件（GAP-RT-056, 057）** |
| **ADR（addendum / 2026-06-20）** | **1 件（GAP-RT-058）** |
| **合計** | **58 件** |

### 重大度別

| 重大度 | 件数 | 該当 GAP-RT |
|---|---|---|
| 高 | 33 件 | GAP-RT-001〜005, 010〜012, 014〜017, 019, 021〜022, 024, 027〜030, 036〜038, 042〜044, 046〜047, 049〜050, **055**, **057**, **058** |
| 中 | 23 件 | GAP-RT-006〜009, 013, 018, 020, 023, 025〜026, 031〜035, 039〜041, 045, 048, 051, 053（うち GAP-RT-053 は RESOLVED-BY-DECISION）, **056** |
| 低 | 2 件 | GAP-RT-052（ADR-024: 外部AI write 受口廃止により 中→低 に見直し済み）/ GAP-RT-054 |
| DEFERRED | 0 件 | —（GAP-RT-051: RESOLVED-IN-L3 に更新済み / 2026-06-18） |

> **重大度別検算**: 高 33 + 中 23 + 低 2 = **58 件**（GAP-RT 総数と一致）
> - 中の内訳: active 22 件（GAP-RT-006〜009, 013, 018, 020, 023, 025〜026, 031〜035, 039〜041, 045, 048, 051, 056）+ 解消 1 件（GAP-RT-053 / RESOLVED-BY-DECISION）= 23 件
> - disposition サマリと二重検算: disposition 合計 24 + 8 + 18 + 2 + 6 + 0 + 0 = **58 件**（一致）

---

## disposition サマリ

> 更新日: 2026-06-18（セキュリティ精査追記）→ 2026-06-18（ADR-024 PO確定: 配布・課金モデル変更 / GAP-RT-053 re-disposition）→ 2026-06-20（ADR-025 追記: GAP-RT-055 法規制対応 1 件追加 / 総数 54 → 55 件）→ 2026-06-20（Part A/B 追記: GAP-RT-056〜058 の 3 件追加 / 総数 55 → 58 件）/ 本日のギャップ closure 作業（A群 addenda 4本 / ADR 4本 / test-plan TC追加 / PO裁定パケット / L1整合 / L5トークン）による全 51 件 disposition 確定結果に、セキュリティ精査（外部AI操作境界）3件（GAP-RT-052/053/054）を追加し計 54 件、さらに法規制対応 1 件（GAP-RT-055）を追加し計 **55 件**。ADR-024（Automation SEO 専用配布一本化・REQ-F-043 廃止）により GAP-RT-053 を PO-ESCALATION → RESOLVED-BY-DECISION に再 disposition。2026-06-20 Part A/B 反映分で 3 件（GAP-RT-056〜058）を追加し計 **58 件**。

| disposition 種別 | 件数 | 該当 GAP-RT |
|---|---:|---|
| **RESOLVED-IN-L3** | 24 件 | GAP-RT-001, 003, 005, 007, 008, 009（A1 ブロック 6 件）/ GAP-RT-016, 019, 020（A3 SEO 純 RESOLVED 3 件）/ GAP-RT-021（A4 パフォーマンス / PERF-CARRY-001 ADR-021 統合解消）/ GAP-RT-029, 031, 032, 033, 034, 035（OSS/CI 群 6 件）/ GAP-RT-036, 037, 039, 040, 041（test-plan TC 追加 5 件）/ **GAP-RT-049, 050（L1 整合補正 ACC-NF↔REQ-NF / 2026-06-18 適用済み）/ GAP-RT-051（L5 カラートークン #xxxx 残ゼロ確認済み / 2026-06-18）** |
| **CARRY-TO-L4（単独）** | 8 件 | GAP-RT-004（C-A1-002）/ GAP-RT-010（CARRY-A2-001）/ GAP-RT-011（CARRY-A2-002）/ GAP-RT-012（CARRY-A2-003）/ GAP-RT-013（CARRY-A2-004）/ GAP-RT-014（CARRY-A2-005 / CARRY-A2-006）/ **GAP-RT-052（CARRY-SEC-001: ADR-020 補足 + Abilities 公開スコープ確定）/ GAP-RT-054（CARRY-SEC-002: mcp-tools.schema.json 実ファイル作成）** |
| **RESOLVED-IN-L3 + CARRY-TO-L4（両立）** | 18 件 | GAP-RT-002, 006, 015, 017, 018, 022, 023, 024, 025, 026（L3 設計確定 + 残項目が L4 carry）/ GAP-RT-027, 028, 030（ADR-020 方針確定 + carry 残存）/ GAP-RT-038（TC 追加済み + PERF-CARRY-002 blocking）/ GAP-RT-042（ADR-023 + carry 4 件）/ **GAP-RT-056（L3-A4 addendum 追記済み + PERF-CARRY-007〜009 carry）/ GAP-RT-057（L5-visual-design 新 5 要件追記済み + CARRY-A11Y-001 carry）/ GAP-RT-058（ADR-026 方針確定 + CARRY-EMBED-001〜006 carry）** |
| **RESOLVED-BY-DECISION** | 2 件 | **GAP-RT-053**（2026-06-18 / ADR-024: REQ-F-043 廃止により外部AI write 受口消滅・OAuth 設計不要 / carry なし）/ **GAP-RT-055**（2026-06-20 / ADR-025: Automation SEO 登録時の同意に集約・テーマは disclosure フックのみ / **+ CARRY-TO-L4: CARRY-ADR025-001〜005 を L4 継続管理**） |
| **PO-ESCALATION** | 3 件 | GAP-RT-044, 046, 047（GAP-RT-043/045/048 は PM-RESOLVED 2026-06-20 / 冒頭 §同期 参照）|
| **PM-RESOLVED（旧 PO-ESCALATION）** | 3 件 | GAP-RT-043, 045, 048（2026-06-20 / PO-decision-packet §PM確定）|
| **L1-FIX-PENDING** | 0 件 | — （GAP-RT-049/050 は RESOLVED-IN-L3 に更新済み） |
| **DEFERRED** | 0 件 | — （GAP-RT-051 は RESOLVED-IN-L3 に更新済み） |

> **検算**: 24 + 8 + 18 + 2 + 6 + 0 + 0 = **58 件**（GAP-RT 総数と一致）
>
> **注意**: RESOLVED-IN-L3 単独と RESOLVED-IN-L3 + CARRY-TO-L4 の両立を合計すると **RESOLVED（設計確定）= 42 件**。RESOLVED-BY-DECISION = 2 件（GAP-RT-053 / ADR-024 + GAP-RT-055 / ADR-025）。CARRY-TO-L4 単独 = 8 件（うち 2 件は今次追加）。純 OPEN 残存（PO 裁定待ち）= **6 件**（GAP-RT-043〜048）。
>
> **RESOLVED-BY-DECISION 内の carry 有無の区別**:
> - GAP-RT-053（ADR-024）: 純 RESOLVED-BY-DECISION。carry なし。REQ-F-043 廃止により設計判断で完結。
> - GAP-RT-055（ADR-025）: RESOLVED-BY-DECISION **かつ** CARRY-TO-L4 保有。ADR-025 による方針決定（= decision 部分）は完了だが、L4 実装 carry（CARRY-ADR025-001〜005）が残存する。**GAP は 1 件として計上**（disposition 件数への二重計上なし）するが、L4 未完了作業が存在することを本行の注記で明示する。

### ADR 化サマリ（GAP → ADR 対応表）

| ADR | 解消 GAP-RT |
|---|---|
| ADR-020（WP 7.0 先回り） | GAP-RT-027, 028, 029, 030, 032, 035（一部）|
| ADR-021（OSS/CI ツールチェーン） | GAP-RT-029, 031, 032, 033, 034, 035 + PERF-CARRY-001 解消 |
| ADR-022（SEO 出力責務境界） | GAP-RT-016 + CARRY-A3-001 解消 |
| ADR-023（FSE 再設計コスト） | GAP-RT-042 |
| ADR-025（AI 生成コンテンツ開示法規制） | GAP-RT-055 |
| ADR-026（AI 生成 HTML 埋め込みブロック / dual-mode） | GAP-RT-058（RESOLVED-IN-L3 + CARRY-TO-L4 / CARRY-EMBED-001〜006）|

---

## L4-carry 集約表

> 全 carry エントリを一覧化。carry-id は各成果物の付番をそのまま使用（リネームなし）。  
> blocking = true の carry は L4 スプリント着手前に解消または PO 承認が必要。  
> G2-carry-register との重複チェック: CARRY-G2 系は別体系のため carry-id 衝突なし。ただし CARRY-G2-011（TC-028 Lighthouse consent）と GAP-RT-038（TC-042〜046 Consent Gate TC 追加）は関連し、CARRY-G2-011 は L4 carry 既存登録済み、GAP-RT-038 は test-plan 拡充で対応という棲み分け。詳細: `docs/reviews/G2-carry-register.md` §CARRY-G2-011 参照。

| carry-id | 由来 GAP-RT | 内容（要約） | 受入条件 TC | L4 sprint / T-ID 案 | 優先度 | blocking |
|---|---|---|---|---|---|---|
| **C-A1-001** | GAP-RT-002 | 外部 OGP 取得 IP ブロック実装 + Action Scheduler 統合 | TC-023a / TC-023b（SSRF） | L4 ブロックカタログ Sprint | P0 | true |
| **C-A1-002** | GAP-RT-004 | balloon JIN:R `[fukidashi]` shortcode 互換変換ルール実装 | TC（L4 Bridge Plugin Sprint で追加） | L4 Bridge Plugin Sprint | P1 | false |
| **C-A1-003** | GAP-RT-006 | post-list `post_views_count` PV カウント実装（AJAX endpoint 含む） | TC（L4 post-list Sprint で追加） | L4 post-list Sprint | P2 | false |
| **CARRY-A2-001** | GAP-RT-010 | ad-zone.schema.json 生成 + REST 4 本（H2前 / 記事終 / 関連上 / カテゴリ別） | TC（L4 広告 Sprint で追加） | L4 広告 Sprint | P0 | true |
| **CARRY-A2-002** | GAP-RT-011 | agent_neo_ad_tag CPT 実装 + 5 分岐スキーマ + REST 5 本（impression/click 計測） | TC（L4 広告 Sprint で追加） | L4 広告 Sprint | P0 | true |
| **CARRY-A2-003** | GAP-RT-012 | event_type 拡張 enum（ad_impression / affiliate_click / scroll_depth / view_time）+ フロント JS | TC（L4 tracking Sprint で追加） | L4 tracking Sprint | P0 | true |
| **CARRY-A2-004** | GAP-RT-013 | affiliate-css-adapter 実装（appreach / Amazon アソシエイト等） | TC（L4 CSS Sprint で追加） | L4 CSS Sprint | P1 | false |
| **CARRY-A2-005** | GAP-RT-014 | disclosure ブロック実装（PR 表記 / 景表法 REQ-NF-009 対応） | TC（L4 disclosure Sprint で追加） | L4 disclosure Sprint | P0 | true |
| **CARRY-A2-006** | GAP-RT-010, 011, 012 | 広告収益化ダッシュボード（ゾーン / CPT / tracking 横断） | TC（L4 dashboard Sprint で追加） | L4 dashboard Sprint | P1 | false |
| **CARRY-A3-002** | GAP-RT-017 | third-party-tags.schema.json 統合仕様（カテゴリ別ロード制御完全版） | TC-028（CARRY-G2-011）/ TC-042〜046 | L4 third-party-tags Sprint | P2 | false |
| **CARRY-A3-003** | GAP-RT-015 | Author E-E-A-T sameAs 候補提案 API（Phase 2 実装） | TC（Phase 2 Sprint で追加） | Phase 2 | P2 | false |
| **CARRY-A3-004** | GAP-RT-017 | 任意タグ監査ログのストレージ設計（retention / export） | TC（L4 audit-log Sprint で追加） | L4 audit-log Sprint | P1 | true |
| **CARRY-A3-005** | GAP-RT-018 | FAQPage 重複 question マージ警告 UI | TC（L4 FAQ Sprint で追加） | L4 FAQ Sprint | P3 | false |
| **PERF-CARRY-002** | GAP-RT-022 | Cookie Consent バナー外部プラグイン adapter vs 内蔵選定・実装（Q-013 PO 裁定後） | TC-042〜046（§8.3）| L4 consent Sprint | P1 | **true**（Q-013 PO 裁定待ち） |
| **PERF-CARRY-003** | GAP-RT-023 | Google Fonts API キー管理方式（secret 扱い / env 分離） | TC（L4 fonts Sprint で追加） | L4 fonts Sprint | P2 | false |
| **PERF-CARRY-004** | GAP-RT-024 | web-vitals npm バージョン固定（major lock + semver policy） | TC-018（performance gate） | L4 performance Sprint | P2 | false |
| **PERF-CARRY-005** | GAP-RT-025 | 遅延ブロックサーバーキャッシュ設計（lazyload endpoint キャッシュ戦略） | TC（L4 lazyload Sprint で追加） | L4 lazyload Sprint | P2 | false |
| **PERF-CARRY-006** | GAP-RT-026 | an-hero-pc WebP 生成接続仕様 | TC（L4 media Sprint で追加） | L4 media Sprint | P3 | false |
| **CARRY-WP7-001** | GAP-RT-027 | ~~WP 7.0-RC Docker 環境構築 + Abilities API PoC~~ → **WP 7.0（GA/stable）環境構築 + Abilities API 本格組み込み検証**（GA 済み 2026-05-20 / RC PoC は本格実装検証へ移行済み / ADR-020 2026-06-20 追記参照。PO-WP7-01 承認後）| TC（L4 WP7 Sprint で追加） | L4 WP7 Sprint | P1 | **true**（PO-WP7-01 裁定待ち） |
| **CARRY-WP7-002** | GAP-RT-027 | `wp_register_ability()` wrapper 実装 | TC（L4 WP7 Sprint で追加） | L4 WP7 Sprint | P1 | false |
| **CARRY-WP7-003** | GAP-RT-030 | WordPress.org Theme Check CI 組み込み（Q-006 wp.org 申請方針確定後） | TC-027（SBOM gate）/ TC-032（PHP CI gate） | L4 CI Sprint | P1 | false（Q-006 PO 裁定で要否確定） |
| **CARRY-WP7-004** | GAP-RT-027, 030 | WP 7.0 CI マトリクス（~~WP 6.6〜7.0-RC 並列テスト matrix~~ → **WP 6.6〜7.0 Tier A 必達マトリクス / RC レーンは GA 済みで Tier A 昇格済み** / ADR-020 D-2 + CARRY-WP7-012 参照）| TC-031〜034（運用品質 TC）| L4 CI Sprint | P2 | false |
| **CARRY-WP7-005** | GAP-RT-028 | `423 Locked` / `409 Conflict` 衝突検出実装（PO-WP7-02 裁定後） | TC（L4 REST Sprint で追加） | L4 REST Sprint | P1 | false（L4 REST 実装スプリント着手前に裁定） |
| **CARRY-WP7-006** | GAP-RT-030 | Gutenberg pre-release ブランチ互換テスト統合 | TC（L4 CI Sprint で追加） | L4 CI Sprint | P2 | false |
| **CARRY-WP7-007** | GAP-RT-030 | `block.json` `apiVersion` 3 対応スクリプト | TC（L4 block Sprint で追加） | L4 block Sprint | P2 | false |
| **CARRY-WP7-008** | GAP-RT-027, 028 | WP7 Feature Flags ゲート実装 | TC（L4 WP7 Sprint で追加） | L4 WP7 Sprint | P1 | false |
| **CARRY-WP7-009** | GAP-RT-030 | WP Playground + browser-extension 統合テスト | TC（L4 E2E Sprint で追加） | L4 E2E Sprint | P2 | false |
| **CARRY-WP7-010** | GAP-RT-027 | Abilities API PoC 結果の ADR-020 D-1 更新（final 版 API シグネチャ反映） | TC（PoC 結果次第） | CARRY-WP7-001 完了後 | P3 | false |
| **CARRY-ADR-021-001** | GAP-RT-031, 034 | PHPCompatibilityWP baseline 確定（対象 PHP バージョン範囲 + 除外ルール） | TC-032（PHP CI gate） | L4 CI Sprint | P2 | false |
| **CARRY-ADR-021-002** | GAP-RT-032 | PHPStan level / baseline 最終確定（level 8 or 9 の選択） | TC（L4 static analysis Sprint で追加） | L4 CI Sprint | P2 | false |
| **CARRY-ADR-021-003** | GAP-RT-033 | opis/json-schema バージョン lock + test fixture 整備 | TC（L4 schema validation Sprint で追加） | L4 schema Sprint | P2 | false |
| **CARRY-ADR-021-004** | GAP-RT-034 | Ajv strict モード設定 + カスタム keyword 登録 | TC（L4 JS schema Sprint で追加） | L4 JS schema Sprint | P2 | false |
| **CARRY-ADR022-001** | GAP-RT-016 | `SeoConflictDetector` PHP クラス実装（ADR-022 出力制御ロジック） | TC-014 / TC-015 / TC-052〜055 | L4 SEO Sprint | P2 | false |
| **CARRY-ADR022-002** | GAP-RT-016 | Yoast Premium 非サポート告知ドキュメント整備（販売 LP + 管理画面バナー） | TC（L6 受入テスト前） | L6〜L7 | P2 | false（L7 前でOK） |
| **CARRY-ADR022-003** | GAP-RT-016 | ADR-022 §4 AGENT NEO @graph 実装ロードマップ確定 | TC（L4 SEO Sprint で追加） | L4 SEO Sprint | P2 | false |
| **CARRY-ADR023-001** | GAP-RT-042 | S-DESIGN-TOKEN スプリント（FSE デザイントークン体系確立 / theme.json 整備） | TC（L4 S-DESIGN-TOKEN Sprint で追加） | L4 S-DESIGN-TOKEN Sprint | P1 | **true** |
| **CARRY-ADR023-002** | GAP-RT-042 | ウィジェット系ブロックスプリント分解（SNS フィードウィジェット Phase 1/2 境界確定後） | TC（L4 widget Sprint で追加） | L4 widget Sprint（Phase 1/2 境界要 PO 裁定: Q-012） | P2 | false |
| **CARRY-ADR023-003** | GAP-RT-042 | FSE Query Loop 拡張（ウィジェット代替フィルタ実装） | TC（L4 FSE Sprint で追加） | L4 FSE Sprint | P2 | false |
| **CARRY-ADR023-004** | GAP-RT-042 | Bridge Plugin JIN:R ショートコード変換スコープ確定（PO 裁定後、ADR-019 追記） | TC（L4 Bridge Plugin Sprint 着手前に確定） | L4 Bridge Plugin Sprint 着手前 | P1 | **true**（PO 裁定 CARRY-ADR023-004 待ち） |
| **CARRY-ADR025-001** | GAP-RT-055 | 登録時同意フローの具体的な同意文言・UI 設計の法務確認（EU AI Act / SB 942 要件を満たす文言を法務レビュー後に確定）。Automation SEO 登録フロー同意ステップの外部依存契約テスト（TC-061 旧定義部分）は AGENT-NEO リポでは実行不可のため本 carry で管理 | TC-061（外部依存部分） | L4 同意フロー Sprint 着手時に draft、法務レビュー後に確定 | P1 | false |
| **CARRY-ADR025-002** | GAP-RT-055 | California SB 942 適用対象範囲の Legal opinion 取得（日本法人が California 居住者向けにサービス提供する場合の適用可否） | — | L4 法務 Sprint | P2 | false |
| **CARRY-ADR025-003** | GAP-RT-055 | "fully AI-generated" vs "AI-assisted" 区別情報を受け取る disclosure フック仕様の L4 確定（AGENT-NEO 側 disclosure スロット API 設計） | TC-062〜064 | L4 disclosure Sprint 着手前に確定 | P1 | false |
| **CARRY-ADR025-004** | GAP-RT-055 | EU AI Act 既存システム猶予（2026-12-02）に対応するための L4 WBS マイルストーン設定。Automation SEO 側の機械可読マーキング実装タイミングとの調整 | TC-064 | L4 WBS 設計時 | P1 | false |
| **CARRY-ADR025-005** | GAP-RT-055 | C2PA 画像 latent マーキングの Automation SEO 画像生成パイプライン実装計画（AGENT-NEO 側は not-in-scope、Automation SEO 側の PM 議題化を推奨） | — | Automation SEO 側 PM 議題化後 | P2 | false |
| **PERF-CARRY-007** | GAP-RT-056 | `should_load_separate_core_block_assets` フィルター実装（**core ブロック CSS の per-block 分離**のみ / JS 分離は block.json `viewScript` + per-block enqueue で別途対応）/ dequeue 許可リスト確定 | TC（L4 performance Sprint で追加） | L4 performance Sprint | P2 | false |
| **PERF-CARRY-008** | GAP-RT-056 | Speculative Loading（WP 6.8+）`wp_get_speculation_rules()` vs JSON 出力方式の選定・実装。admin/プレビュー/ログイン等 sensitive URL は **`where` 否定ルール**（`"where": { "not": { "href_matches": "..." } }` 等）で明示除外（`eagerness: moderate` は opt-out にならないため不使用。`eagerness: never` は有効値でなく無視されるため使用しない） | TC（L4 performance Sprint で追加） | L4 performance Sprint | P2 | false |
| **PERF-CARRY-009** | GAP-RT-056 | Font Library（WP 6.5+）ローカルフォント配信の採用判断・`source=local` font-policy 統合・preconnect 抑制実装 | TC（L4 fonts Sprint で追加） | L4 fonts Sprint | P2 | false |
| **CARRY-A11Y-001** | GAP-RT-057 | WordPress.org accessibility-ready 新 5 要件（2026-05-06 改定 / WCAG 2.2 AA）の具体受入 TC 追加と CI 自動化（reflow / context-change 防止 / focus outline 禁止ルール化 / statement template / 推奨 plugin 審査フロー） | **TC-074〜TC-078（test-plan §11 / 2026-06-20 登録完了）**。TC 登録済み。CI 自動化（TC-074 Playwright reflow / TC-076 stylelint outline 禁止）は L4 通常作業として継続。§10 は ADR-026 / GAP-RT-058 埋め込みブロック専用のため a11y 新 5 要件は §11 に独立分節 | L4 着手前必須（再評価期限 2026-06-30）。TC 登録は 2026-06-20 完了。CI 自動化は L4 carry として継続 | P1 | false |
| **CARRY-EMBED-001** | GAP-RT-058 | `agent-neo/embed` block.json 完全形の確定（name / apiVersion 3 / supports / attributes / render_callback 等）| TC（L4 embed Sprint で追加） | L4 embed Sprint 着手前 | P1 | false |
| **CARRY-EMBED-002** | GAP-RT-058 | iframe sandbox 属性最終セット確定（`allow-scripts` のみか追加属性を付与するか）/ postMessage プロトコル仕様（height resize / origin 検証方式）。**【非交渉制約】汎用 parent localStorage / storage bridge は禁止（任意キー / 任意値 read/write 不可）。永続化が要る場合も namespace 固定 + 値 schema 検証 + write 専用等の厳格プロトコルに限定し、デフォルトは bridge 不持方針を優先（ADR-026 §Consequences 参照 / 不変制約）** | TC（L4 embed Sprint で追加） | L4 embed Sprint 着手前 | P1 | false |
| **CARRY-EMBED-003** | GAP-RT-058 | CSP 文字列確定（`frame-src` / `default-src` / `script-src` 等）/ Automation SEO から payload を受け取る REST エンドポイント設計。**egress allowlist: `connect-src` / `img-src` / `form-action` を default-deny（`'none'` または自 origin のみ）とし、許可 origin を明示列挙する CSP 文字列を確定する（iframe 内 JS の fetch/XHR/sendBeacon/img beacon/form POST による診断フォーム入力の外部 exfiltration 防止 / ADR-026 §2 egress 制御節参照 / TC-079 の前提）。【mode=interactive 別オリジン配信への更新（案A確定 / 2026-06-20）】旧 srcdoc-scoped CSP（`<meta http-equiv="Content-Security-Policy">` インライン）は廃止。代替: サンドボックスオリジン（`https://<sandbox-origin>`）が HTTP レスポンスヘッダで CSP を配信する（`script-src 'self'` + egress default-deny）。親ページの CSP には `frame-src https://<sandbox-origin>` を追加して sandbox-origin のみ allowlist とする（別オリジン配信のため旧 srcdoc 先頭 prepend 実装も不要となる / TC-079 前提は維持）。** | TC-079（L4 セキュリティ Sprint で実テスト化） | L4 セキュリティ Sprint 着手前 | P1 | false |
| **CARRY-EMBED-004** | GAP-RT-058 | テーマ側 sanitize allowlist 確定（`wp_kses` / DOMPurify）/ mode=static の DSD PHP render_callback 実装詳細。**【mode=interactive 別オリジン配信への更新（案A確定 / 2026-06-20）】旧 srcdoc 属性コンテキストエスケープ（`&`→`&amp;`・`"`→`&quot;` 等）および DOM well-formed 検証は廃止。mode=interactive は `<iframe src="https://<sandbox-origin>/embed/{id}" sandbox="allow-scripts">` による別オリジン配信となるため、srcdoc 文字列組み立て・属性エスケープは不要。standalone / 個人版では interactive は提供不可（Automation SEO 契約必須）。【mode=static 必須項目（変更なし）】Shadow DOM host リセット適用範囲・例外確定: 完全視覚隔離のため shadow root に明示 host リセット（`:host { all: initial }` 等）を適用する。L4 では (a) 採用方式（`all: initial` / プロパティ別リセット）、(b) 意図的に host から継承を残す例外プロパティの有無、(c) 適用範囲を確定する（ADR-026 §mode=static 設計原則・TC-066 (c) 参照）** | TC（L4 embed Sprint で追加） | L4 embed Sprint 着手前 | P1 | false |
| **CARRY-EMBED-005** | GAP-RT-058 | Abilities API 宣言（`readonly: true`）+ `agent-neo/v1/posts/{id}/embed-block/apply` REST エンドポイント設計 | TC（L4 WP7 Sprint と連携） | L4 WP7 Sprint 連携 | P2 | false |
| **CARRY-EMBED-006** | GAP-RT-058 | 検証パイプライン（sanitize / scope / a11y / budget）への `agent-neo/embed` 経路追加 / Automation SEO 側 HTML 生成仕様書との整合確認 | TC（L4 embed Sprint で追加） | Automation SEO 側 PM 議題化後 | P2 | false |

> **G2-carry-register 相互参照**: `docs/reviews/G2-carry-register.md`  
> - 本 L4-carry 集約表は GAP-RT 由来の新規 carry のみを収録。G2 carry 由来（CARRY-G2-007/009/011/012/013/015/017/021/025/026/028 等）は G2-carry-register 本体を正本とし、本表には重複記載しない。  
> - CARRY-G2-011（Lighthouse consent）と本表 PERF-CARRY-002 / TC-042〜046 は関連するが ID 体系が異なり collision なし。

---

## blocking carry 一覧（L4 着手前解消 / PO 裁定必須）

| carry-id | 優先度 | blocking 理由 / 前提裁定 |
|---|---|---|
| C-A1-001 | P0 | 外部 OGP SSRF ガード実装未完。ブロックカタログ Sprint の最初のタスク |
| CARRY-A2-001 | P0 | ad-zone スキーマ未確定のまま広告 Sprint 着手不可 |
| CARRY-A2-002 | P0 | ad_tag CPT スキーマ未確定のまま REST 実装 Sprint 着手不可 |
| CARRY-A2-003 | P0 | event_type enum 未確定のまま tracking Sprint 着手不可 |
| CARRY-A2-005 | P0 | disclosure ブロック仕様未確定のまま景表法対応 Sprint 着手不可 |
| CARRY-A3-004 | P1 | 監査ログストレージ設計が未確定のまま任意タグ Sprint 着手不可 |
| ~~PERF-CARRY-002~~ | — | **RESOLVED(2026-06-20)**: Q-013 PM-RESOLVED / Cookie Consent 外部 adapter 方式確定。blocking 解除（冒頭 §同期 参照）|
| ~~CARRY-WP7-001~~ | — | **RESOLVED(2026-06-21)**: PO-WP7-01 VERIFIED / WP7.0 GA で Abilities API 実証。blocking 解除（冒頭 §同期 参照）|
| CARRY-ADR023-001 | P1 | S-DESIGN-TOKEN スプリント未実施のまま L4 デザイントークン実装着手不可（L4 sprint 順序依存であり L3 設計 close は妨げない）|
| ~~CARRY-ADR023-004~~ | — | **RESOLVED(2026-06-20)**: PM-RESOLVED / Bridge ショートコード変換 主要3種 Phase1 確定。blocking 解除（冒頭 §同期 参照）|

---

*作成: 2026-06-18 / 監査ソース: SWELL 2.16.0 実コード + 解析レポート 49 本 + 6体並列監査 / 次アクション: L3-patch 群を L3 詳細設計へ追記 / ADR 群を新規 ADR 起票 / PO-escalation 群を PM 議題化*
*disposition 更新: 2026-06-18（TL [P2] 指摘対応）/ 担当: 文書統合担当（Sonnet）/ 参照成果物: L3-A1〜A4 addenda / ADR-020〜023 / L3-test-plan §8 / L3-PO-decision-packet / G2-carry-register / L1-requirements.md（ACC-NF 整合補正・§9 トレーサビリティ補完）/ L5-visual-design.md（カラートークン全値確定）*
*セキュリティ精査追記: 2026-06-18 / GAP-RT-052〜054（外部AI操作境界 3 件）追加 / 総数 51 → 54 件 / 担当: 文書担当（Sonnet）*
*ADR-024 re-disposition: 2026-06-18 / GAP-RT-053 を PO-ESCALATION → RESOLVED-BY-DECISION に更新 / GAP-RT-052 重大度 中→低 に見直し（外部AI write 受口廃止による露出リスク低減）/ PO-ESCALATION 7件 → 6件 / 担当: 文書整合担当（Sonnet）*
*ADR-025 追記: 2026-06-20 / GAP-RT-055（AI 生成コンテンツ開示法規制 / 重大度=高）追加・RESOLVED-BY-DECISION（ADR-025 / PO確定）/ 総数 54 → 55 件 / 高 30 → 31 件 / RESOLVED-BY-DECISION 1 → 2 件 / disposition 検算 54 → 55 件 / ADR化サマリ更新 / 担当: 文書整合担当（Sonnet）*
*Part A/B 追記: 2026-06-20 / GAP-RT-056（性能 enforce 機構 / 重大度=中）/ GAP-RT-057（L5 a11y 新 5 要件 / 重大度=高）/ GAP-RT-058（埋め込みブロック未設計 / 重大度=高）の 3 件追加・全て RESOLVED-IN-L3+CARRY-TO-L4 / 総数 55 → 58 件 / 高 31 → 33 件 / 中 22 → 23 件 / RESOLVED-IN-L3+CARRY-TO-L4 15 → 18 件 / disposition 検算 55 → 58 件 / ADR化サマリ（ADR-026 追加）/ L4-carry 集約表（PERF-CARRY-007〜009 / CARRY-A11Y-001 / CARRY-EMBED-001〜006 計 10 件追加）/ 担当: 文書整合担当（Sonnet）*
*P1 是正追記: 2026-06-20 / CARRY-A11Y-001 → TC-074〜TC-078 を test-plan §11 に登録完了（CARRY-A11Y-001 TC 登録部分 RESOLVED）/ ADR-026 postMessage origin 検証を `event.source` 照合 + nonce 検証方式に修正（origin allowlist 文言削除）/ ADR-026 TC 表を test-plan §10（SSOT）と完全一致に是正 / ADR-026 GAP 参照を GAP-RT-056 → GAP-RT-058 に統一（8行・223行）/ test-plan §11 新設（TC-074〜078 / a11y 新 5 要件 / P1 × 5）/ §4 P1 リスト更新（TC-074〜078 追加）/ TC 総数 84 → 89 件（CAT 9 + TC 80）/ L5-visual-design.md のテスト登録先参照を §10 → §11 に修正 / gap-register CARRY-A11Y-001 の TC 参照・blocking 状態を更新 / 担当: 文書整合担当（Sonnet）*
*carry 完全性 cascade 予防是正: 2026-06-20（Codex TL レビュー P1×1 / P2×2 是正 / 15巡目） / CARRY-EMBED-002 carry 行に「汎用 parent storage bridge 禁止（非交渉制約）」を追記 / CARRY-EMBED-003 carry 行に「CSP `<meta>` 先頭 prepend の PHP 実装確定（必須）」を追記 / CARRY-EMBED-004 carry 行に「Shadow DOM host リセット適用範囲・例外確定（mode=static 必須項目）」および「srcdoc 属性コンテキストエスケープ + DOM well-formed 検証」を追記 / 担当: 文書整合担当（Sonnet）*
*案A確定 / 別オリジン iframe モデル反映: 2026-06-20 / GAP-RT-058 状態欄に dual-mode 設計詳細（mode=interactive = 別オリジン sandbox iframe / mode=static = Shadow DOM + DSD）を明記 / CARRY-EMBED-003 の「srcdoc-scoped CSP」を「サンドボックスオリジン HTTP CSP + 親 frame-src allowlist」に更新 / CARRY-EMBED-004 の旧 srcdoc 属性エスケープ項目を「廃止（別オリジン配信のため不要）/ standalone interactive 不可の明示」に更新 / 担当: 文書整合担当（Sonnet）*
