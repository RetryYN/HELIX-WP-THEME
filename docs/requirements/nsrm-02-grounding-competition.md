# NSRM-02: REQ-F 実コード根拠 × 競合差別化マトリクス

> 作成日: 2026-04-30
> 作成者: PM Opus (Sonnet Agent 実行)
> 目的: L1 の全 REQ-F に対し、実コード根拠と競合差別化を紐付けし、設計優先度を決定する
> 参照元: 解析レポート 30〜36、L1-requirements.md

---

## 凡例

**根拠判定**
- `根拠あり`: 解析レポート 30〜36 に実コード・実観測による裏付けがある
- `推論あり`: 01〜29 番 Codex 解析または論理推論による根拠（実コード観測なし）
- `根拠なし`: 解析レポートに根拠が見当たらない（要再評価）

**競合判定**
- `✓`: 競合が同等以上に持っている
- `△`: 競合が部分的に持っている（限定機能/別途プラグイン等）
- `✗`: 競合は持っていない（AGENT NEO の差別化点）

**差別化レベル**
- 標準: 競合同等、差別化要素なし（実装は必須だが訴求材料にならない）
- 部分差別化: 一部競合が持っている、AGENT NEO は強化版
- **差別化**: 競合が持っていない、AGENT NEO 独自
- **killer**: 競合が構造的に追従不能

---

## メインマトリクス

| REQ-F ID | 要件名 | 実コード根拠 | 根拠判定 | ThemeB | テーマA | テーマC | 差別化レベル |
|---|---|---|---|---|---|---|---|
| REQ-F-001 | FSEテーマ基盤 | 35-§確認事実1: ThemeB 実機 31 ブロック登録確認、30-§領域A: block.json 全カタログ、32-§領域A: wp_head Pri:0 ブロック検知機構実確認 | 根拠あり | ✓ | △（旧来PHP型、FSE非採用）| △（カスタマイザー型、FSE移行中）| 標準（差別化なし） |
| REQ-F-002 | JSON操作API | 36-§2.1: テーマA REST は 3 routes のみ、ThemeB も同程度（36-§5表）。34-§3: AGENT NEO 30+ routes 設計根拠確立。35-§6: `/themeB/v1` は実機で 404（REST 公開が実質なし）| 根拠あり | ✗（API なし）| ✗（3 routes のみ）| ✗（API なし）| **killer** |
| REQ-F-003 | 4操作面（REST/MCP/WP CLI/管理画面）| 36-§2.1: テーマ自体の REST 公開は両競合とも最小限。34-§3: AGENT NEO API ツリー設計。11-競合比較: AI 操作は「競合に明確な空白」と判定 | 根拠あり | ✗ | ✗ | ✗ | **killer** |
| REQ-F-004 | 個人版収益化ブロック | 30-§領域B CPT: `ad_tag` CPT（5タイプ: normal/text/affiliate/amazon/ranking）、`themeB/review` block.json 実確認。34-§6: ad_tag show_in_rest=false（AI 不可）を改善。26-テーマC の収益化訴求強いと分析 | 根拠あり | △（ad_tag あるが REST 非公開）| △（YYI Rinker 連携、本番観測）| ✓（収益化最強）| 部分差別化（AI 操作 + PR 表記自動 + ASP 別 CTR が差別化軸）|
| REQ-F-005 | 法人HP/LP/BLP三位一体 | 36-§2.5: テーマA 本番が固定ページ + template-full-width で HP 実装（重要発見）。30-§領域B: ThemeB は `lp` CPT（public=true, show_in_rest=true）実確認。16-LP-HP設計方針。34-§2: `agent-neo/lp` CPT 設計根拠 | 根拠あり | △（lp CPT のみ、BLP/三位一体なし）| △（固定ページ活用確認済みだが BLP 概念なし）| △（LP 機能あるが JSON 契約なし）| **差別化**（三位一体 + section_id 標準契約）|
| REQ-F-006 | 計測/A-B/CTA | 30-§領域A: `themeB/ab-test/ab-test-a/ab-test-b` ブロック実確認、`themeB_btn_cv_data` post meta（JSON形式）確認。35-§1: seo_tool_ab_test CPT が実機登録確認。27-§連携弱点: variant_id/section_id 標準契約なし | 根拠あり | △（A/B ブロックあるが variant_id 契約なし）| △（計測は外部プラグイン依存）| ✓（CTR 計測が強み）| 部分差別化（section_id/cta_id/variant_id の標準契約が差別化）|
| REQ-F-007 | Automation SEO連携 | 36-§4: 両本番サイトで aseo/v1 稼働確認（30 routes）。27-§現状不都合: ThemeB/テーマA とも section_id/cta_id 標準契約なし確認。36-§6: agent-neo/v1 連携指針確定 | 根拠あり | ✗（標準連携なし）| ✗（標準連携なし）| ✗（標準連携なし）| **killer** |
| REQ-F-008 | 移行プラグイン | 11-競合比較: 競合は主に手動移行。26-テーマD: 無料リード獲得モデル参考。34-§2: ThemeB の CPT 構造が移行対象として分析済み | 推論あり（競合調査根拠） | ✗（公式移行ツールなし）| ✗ | ✗ | **差別化** |
| REQ-F-009 | 設定エクスポート/インポート | 35-§3: ThemeB カスタマイザー 506 設定実確認。32-§領域B: CSS 変数生成パイプライン + transient キャッシュ詳細。34-§4: design-tokens.json 40 トークン以下目標の具体値確定 | 根拠あり | △（カスタマイザーエクスポートのみ、JSON 標準契約なし）| △（同様）| △（同様）| 部分差別化（JSON Patch / RFC 6902 標準が差別化）|
| REQ-F-010 | ライセンス/パッケージ制御 | 30-§領域B: ThemeB のロール権限マッピング詳細（Administrator/Editor/Author別）実確認。34-§3: 個人版/法人版でルート公開範囲切替設計 | 根拠あり | △（ライセンス制御なし、全機能解放）| △（同様）| △（PACK 別機能あるが JSON 契約なし）| 部分差別化 |
| REQ-F-011 | SEO Core | 31-§領域A: テーマA `_themeA_*` SEO post meta 10個完全抽出（seotitle/description/canonical/noindex/OGP）。36-§2.2/3.1: 両本番サイトの実 OGP/SEO meta 出力確認。35-§4: ThemeB の @graph JSON-LD 実機出力確認 | 根拠あり | ✓（SEO 機能強）| ✓（SEO 統合 UX が強み）| △（外部プラグイン依存が多い）| 部分差別化（AI 操作可能 + diff/rollback が差別化）|
| REQ-F-012 | LP/HP/BLPブループリント | 30-§領域B CPT: ThemeB `lp` CPT（offer_id/service_id なし）。36-§2.5: テーマA 固定ページ Blueprint 使用確認。34-§2: AGENT NEO `agent-neo/blueprint` CPT（新設）設計根拠 | 根拠あり | △（lp CPT のみ、blueprint JSON 契約なし）| △（固定ページ活用のみ）| △（LP 機能あるが JSON 契約なし）| **差別化** |
| REQ-F-013 | 法人版リード獲得 | 36-§1: 両本番で contact-form-7 プラグイン稼働確認。26-テーマE: 法人安心感があるが LP 改善/計測は弱いと分析 | 推論あり（CF7 観測 + 競合分析）| △（外部フォームプラグイン前提）| △（同様）| △（同様）| 部分差別化（cta_id/offer_id 標準計測が差別化）|
| REQ-F-014 | 法人版顧客行動管理 | 27-§連携: seo-tool-connector に section_metrics_daily / SECTION_METRICS_DAILY テーブル実確認。36-§4: aseo/v1 の tracking/context/section-engagement routes 実観測 | 根拠あり（aseo 側根拠）| ✗ | ✗ | ✗ | **差別化** |
| REQ-F-015 | CRM/MA連携アドオン | 36-§4: aseo/v1 に Webhook 送信 routes 確認。26-テーマE: 法人エコシステムが強いが CRM 連携なし | 推論あり（外部連携設計は Codex 解析ベース）| △（Zapier アドオン等）| △（同様）| ✗ | 部分差別化 |
| REQ-F-016 | 個人版テンプレ固定構成 | 36-§2.5/3.5: テーマA が固定ページ Template、ThemeB が動的 blog を採用という実観測から逆引き設計。34-§7: パターン/テンプレ設計根拠 | 根拠あり（逆引き設計根拠）| ✗（個人版制限機能なし）| ✗ | ✗ | **差別化** |
| REQ-F-017 | 画像変換パイプライン | 35-§確認事実: ThemeB/テーマA の画像処理は Codex レベル確認のみ（`loading='lazy'`, srcset 実装）。36-§観測: WebP 自動変換の本番実装は確認できず。15-§テーマA: `loading=lazy` 付与確認 | 推論あり（WebP 自動変換は競合比較から追加）| △（手動/プラグイン依存）| △（同様）| △（同様）| **差別化** |
| REQ-F-018 | SNS連携基盤 | 36-§2.2: テーマA 本番で twitter:card/og:site_name 実出力確認。36-§1: 両サイトで CF7 実稼働確認。テーマA の自動投稿機能は解析レポートに根拠なし | 推論あり（SNS 自動投稿は Codex 解析 02 ベース）| △（oEmbed のみ、自動投稿なし）| △（OGP 出力のみ）| △（限定的）| **差別化**（自動投稿 + lazy embed + SNS 計測統合）|
| REQ-F-019 | 法人版SNS深い統合 | 36-§6: 解析確定事項に SNS 連携は記載なし。Codex 解析 02 での機能分析止まり | 根拠なし（要再評価）| ✗ | ✗ | ✗ | **差別化**（根拠不足・要精査）|
| REQ-F-020 | SNS API認証情報管理 | 36-§3.2: ThemeB 本番の Cache-Control はクリーン。32-§領域A: カスタマイザー設定管理の実装詳細確認。API Key の暗号化保存は解析対象外 | 根拠なし（暗号化保存の実コード根拠なし）| △（管理画面設定のみ）| △（同様）| △（同様）| 部分差別化（要根拠補強）|
| REQ-F-021 | 部分更新性（partial update）| 34-§3: AGENT NEO dryRun フラグ・idempotency-key 設計根拠。35-§6: ThemeB の `/themeB/v1` は実機 404、REST 安定性が皆無と確認。36-§2.1: テーマA の REST は 3 routes 限定 | 根拠あり（競合の不在根拠）| ✗（ブロック単位 API なし）| ✗ | ✗ | **killer** |
| REQ-F-022 | H2単位LLM編集 | 27-§連携弱点: section-tracker.js が h2 境界依存で見出し変更時に ID がずれる問題実確認。36-§5: `data-agent-section-id` 必須化の根拠となる両競合の data 属性不在確認 | 根拠あり（競合の弱点根拠）| ✗ | ✗ | ✗ | **killer** |
| REQ-F-023 | 要素差し替え機構（Element Swap）| 34-§6: ad_tag show_in_rest=false で AI が CTA を操作できない欠陥を確認。36-§5: data-agent-* 属性が競合では観測されず確認。27-§連携弱点: selector 壊れ問題確認 | 根拠あり（競合の欠陥根拠）| ✗ | ✗ | ✗ | **killer** |
| REQ-F-024 | AI自律A/Bテスト機構 | 30-§領域A: ThemeB `themeB/ab-test` ブロック実確認（rate/syncMode/syncId）。35-§1: seo_tool_ab_test CPT 実機登録確認。27-§ThemeB弱点: 双方向 dryRun/apply なし確認 | 根拠あり | △（ブロックあるが自律ループなし）| ✗ | △（A/B 機能あるが自律化なし）| **killer** |
| REQ-F-025 | JSON統一データモデル | 34-§5: ThemeB Pre_Parse_Blocks が静的配列で管理（JSONではない）。35-§7: ThemeB 設定値がオプションテーブルに保存されない（fresh install）という重要事実確認。34-§2: AGENT NEO JSON 統一設計の根拠 | 根拠あり | ✗（独自シリアライズ混在）| ✗ | ✗ | **killer** |
| REQ-F-026 | v2連携最適化API | 36-§4: aseo/v1 の `/posts/<id>` `/ai/write` `/ai/bulk` 等 routes 実観測。27-§セクションDB: wp_pages/wp_page_sections テーブル確認。34-§3: bulk read/sparse fieldset 設計根拠 | 根拠あり（aseo 側根拠）| ✗ | ✗ | ✗ | **killer** |
| REQ-F-027 | v2DBスキーマ直接マッピング | 27-§連携資産: WORDPRESS_CONNECTIONS/WP_PAGES/WP_PAGE_SECTIONS/SECTION_METRICS_DAILY の実テーブル確認。36-§4: aseo/v1 route 構造と DB マッピング関係確認 | 根拠あり（aseo 側根拠）| ✗ | ✗ | ✗ | **killer** |
| REQ-F-028 | 拡張性保証（schema versioning + adapter）| 34-§8: 3 ティアプラグイン互換戦略確定（seo-tool-connector / Yoast adapter / プレーン WP）。33-§領域A: ThemeB は SSP のみ深い統合、テーマA は独立志向という対照的な実装確認 | 根拠あり（競合比較根拠）| △（バージョニングなし）| △（同様）| △（同様）| **差別化** |
| REQ-F-029 | ページタイプ別アセット振り分け機構 | 32-§領域A: ThemeB Pre_Parse_Blocks の `$hook_suffix`, `$post_type === 'ad_tag'`, `is_separate_css()` 判定ロジック詳細実確認。32-§キャッシュ: transient 30日 + カスタマイザー変更時 invalidate 実確認。36-§6: `core_blocks_first` 確定事項 | 根拠あり | △（ThemeB は用ブロック CSS 分割あるが page_type budget JSON なし）| ✗（グローバル CSS 大、496KB 実測）| ✗ | 部分差別化（asset-policy.schema.json + CI budget 検証が差別化）|
| REQ-F-030 | 個人版販売寄与モジュール強化 | 30-§領域A: ThemeB `themeB/review`, `themeB/banner-link` ブロック実確認。32-§領域C: ad_tag の imp_count/pv_count/btn_clicked_ct 計測メタ実確認。36-§2.7: テーマA 本番で YYI Rinker クリック計測 JS preload 実観測 | 根拠あり | △（Sticky CTA なし、Exit-intent なし）| △（YYI Rinker で一部対応）| ✓（収益化ブロック強）| 部分差別化（AI suggested CTA + cta_id 標準計測 + A/B 連携が差別化）|
| REQ-F-031 | 法人版販売寄与モジュール強化 | 36-§1: CF7 本番稼働確認（問い合わせフォーム）。36-§2.1: テーマA の REST は 3 routes のみ（LINE 連携なし）。26-テーマE: LP 改善は弱点として確認 | 推論あり（LINE 連携等は競合調査のみ）| ✗（LINE 友だち追加ブロックなし）| ✗ | ✗ | **差別化** |
| REQ-F-032 | AI主導CV最適化 | 22-§不都合な真実2: DOM class は意味契約ではなく CV 意図を示さない実確認。34-§6: AI が ad_tag を操作できない欠陥確認。36-§5: `data-agent-*` 属性が両競合で観測されず | 根拠あり（競合の不在根拠）| ✗ | ✗ | ✗ | **killer** |
| REQ-F-033 | CV設計監査機能 | 19-デザインUI思想逆引き: CTA 過多・証拠不足・PR 不足を ThemeB/テーマA から逆引き。26-§テーマC弱点: UI 複雑性と認知負荷が問題と分析 | 推論あり（デザイン逆引き根拠）| ✗（UI 監査機能なし）| ✗ | ✗ | **差別化** |
| REQ-F-034 | 認知バイアスパターンライブラリ | 33-§領域C: ThemeB `blog_parts` CPT → block_pattern 自動登録の革新的実装確認。34-§7: `agent-neo/reusable-part` CPT + service_id 分類設計根拠 | 根拠あり（パターン機能の競合根拠）| △（blog_parts CPT あるが bias pattern 分類なし）| ✗（Pattern 機能なし実確認）| ✗ | 部分差別化 |
| REQ-F-035 | AIフリーフォームHTML/CSSブロック | 30-§領域A: `themeB/restricted-area` ブロック（条件付き表示）実確認。22-§不都合な真実4: REST があっても契約ないと AI が壊す問題確認 | 推論あり（フリーフォーム自体は競合未観測）| ✗ | ✗ | ✗ | **差別化** |
| REQ-F-036 | AI HTML/CSS検証パイプライン | 32-§領域C: ThemeB `ad_img` フィールドが HTML をそのまま出力する設計（サニタイズ最小）確認。33-§主要発見1: プラグイン互換はフォールバック戦略が妥当と確認。36-§6: CSS scope 化必要性根拠 | 根拠あり（競合のサニタイズ不備根拠）| ✗（CSS scope なし）| ✗ | ✗ | **killer** |
| REQ-F-037 | SlotベースBlueprintと編集領域制限 | 34-§2: ThemeB CPT の capability 分離実確認（lp/blog_parts/ad_tag 別権限）。27-§連携弱点: AI 適用でセレクタが壊れる問題確認 | 推論あり（Slot 設計は Codex 解析ベース）| ✗ | ✗ | ✗ | **killer** |
| REQ-F-038 | HP/LP/固定ページ デザイン編集サンドボックス Tier 1 | 36-§2.5: テーマA 本番が固定ページ + preview token で実装（重要観測）。35-§7: ThemeB の設定は options に保存されない事実（preview 機構の不在根拠）| 根拠あり（競合の不在根拠）| ✗ | ✗ | ✗ | **killer** |
| REQ-F-039 | HP/LP/固定ページ デザイン編集サンドボックス Tier 2 | 36-§4: aseo/v1 の multi-version 管理・AI 最適化ループ routes 実観測（WIP）。27-§連携資産: aseo 側 DB 構造確認 | 根拠あり（aseo 側根拠）| ✗ | ✗ | ✗ | **killer** |
| REQ-F-040 | Write Authority Lock | 36-§6: AGENT NEO の書き込み経路設計確定事項（aseo/v1 + agent-neo/v1 限定）根拠。22-§不都合な真実1: AI はブラウザ操作だけでは安定運用できない確認 | 推論あり（Write Lock 自体は設計追加）| ✗ | ✗ | ✗ | **差別化** |
| REQ-F-041 | 記事編集経路（サンドボックス対象外）| 36-§4: aseo/v1 の `/ai/write` `/ai/block-edit` routes が記事直接編集をターゲット実確認。35-§1/2: ThemeB ブロック 31 件実登録確認（記事編集の実態）| 根拠あり | △（WP 標準エディタのみ）| △（同様）| △（同様）| 部分差別化（AI 直接編集経路が差別化）|
| REQ-F-042 | 外部エディタアクセス制御（デフォルト閉鎖）| 36-§6: 設計確定事項として agent-neo/v1 + aseo/v1 限定の書き込み経路確定。22-§不都合な真実4: REST 契約なしで AI が壊す問題確認 | 根拠あり（セキュリティ根拠）| ✗（wp/v2 直接書き込み可）| ✗ | ✗ | **killer** |
| REQ-F-043 | Open Editor Bridge Plugin（別売）| 36-§6: 外部エディタのホワイトリスト設計根拠。34-§8: 3 ティア戦略（seo-tool-connector / adapter / プレーン WP）設計 | 推論あり（収益モデル設計は Codex 解析）| ✗ | ✗ | ✗ | **差別化** |

---

## 検証分析

### 1. 根拠なし REQ-F（要再評価）

以下の要件は、解析レポート 30〜36 の実コード・実観測根拠が見当たらず、推論または Codex 解析（01〜29番）のみに基づく。実装前に追加調査を推奨。

| REQ-F ID | 要件名 | 懸念点 |
|---|---|---|
| REQ-F-019 | 法人版SNS深い統合 | LINE 公式アカウント連携の実需要・API 仕様・本番事例の根拠なし。LINE Bot / Webhook 設計は要追加調査 |
| REQ-F-020 | SNS API認証情報管理 | openssl_encrypt + AUTH_KEY による暗号化設計の根拠なし。WP 標準の Secrets 管理の実装パターン要調査 |
| REQ-F-033 | CV設計監査機能 | cta.overload/proof.too_late 等の検出ロジックは設計仮説。実計測データによる閾値根拠なし |
| REQ-F-040 | Write Authority Lock | 法人版での中央集権編集の実需要（ユーザーインタビュー等）の根拠なし |
| REQ-F-043 | Open Editor Bridge Plugin | 月額 ¥3,000-5,000 の価格根拠・需要の根拠なし |

**根拠なし要件数: 5件**（部分的に根拠ありのものを除く。REQ-F-017/018/031 は「推論あり」に分類）

---

### 2. 標準のみ REQ-F（競合同等 — なぜ作るかの再考材料）

以下の要件はいずれかの競合が同等機能を持っており、「作ること」自体が差別化にならない。

| REQ-F ID | 要件名 | 「なぜ作るか」の再回答 |
|---|---|---|
| REQ-F-001 | FSEテーマ基盤 | FSE は前提インフラ。差別化でなく土台。block.json 単一ソース原則 + PHP 8.1+ 対応（テーマA の 100+ Warning vs クリーン実装）が差別化根拠 |
| REQ-F-011 | SEO Core | ThemeB/テーマA ともに SEO は強い。差別化は「AI が安全に diff/rollback できる契約」。SEO 機能自体は実装必須だが訴求材料は AI 操作性 |

**標準のみ REQ-F: 2件**（厳密には REQ-F-001/011 のみ。他は差別化要素を持つ）

---

### 3. 差別化集中分析

`✗` が3競合すべてに並ぶ要件（AGENT NEO 独自）をカテゴリ別に集計:

#### AI操作・自律化カテゴリ（最大の差別化軸）

| REQ-F ID | 要件名 | 差別化レベル |
|---|---|---|
| REQ-F-002 | JSON操作API | **killer** |
| REQ-F-003 | 4操作面（REST/MCP/WP CLI）| **killer** |
| REQ-F-007 | Automation SEO連携 | **killer** |
| REQ-F-021 | 部分更新性（partial update）| **killer** |
| REQ-F-022 | H2単位LLM編集 | **killer** |
| REQ-F-023 | 要素差し替え機構 | **killer** |
| REQ-F-024 | AI自律A/Bテスト機構 | **killer** |
| REQ-F-025 | JSON統一データモデル | **killer** |
| REQ-F-026 | v2連携最適化API | **killer** |
| REQ-F-027 | v2DBスキーマ直接マッピング | **killer** |
| REQ-F-032 | AI主導CV最適化 | **killer** |

**killer 件数: 11件**（うち AI 操作/自律化カテゴリに集中）

#### ガバナンス・セキュリティカテゴリ

| REQ-F ID | 要件名 | 差別化レベル |
|---|---|---|
| REQ-F-036 | AI HTML/CSS検証パイプライン | **killer** |
| REQ-F-037 | Slot ベース Blueprint 編集領域制限 | **killer** |
| REQ-F-038 | デザイン編集サンドボックス Tier 1 | **killer** |
| REQ-F-039 | デザイン編集サンドボックス Tier 2 | **killer** |
| REQ-F-042 | 外部エディタアクセス制御 | **killer** |

**killer 件数（ガバナンス）: 5件**

#### CV・収益化カテゴリ

| REQ-F ID | 要件名 | 差別化レベル |
|---|---|---|
| REQ-F-005 | 法人HP/LP/BLP三位一体 | **差別化** |
| REQ-F-008 | 移行プラグイン | **差別化** |
| REQ-F-012 | LP/HP/BLPブループリント | **差別化** |
| REQ-F-014 | 法人版顧客行動管理 | **差別化** |
| REQ-F-016 | 個人版テンプレ固定構成 | **差別化** |
| REQ-F-017 | 画像変換パイプライン | **差別化** |
| REQ-F-018 | SNS連携基盤 | **差別化** |
| REQ-F-028 | 拡張性保証 | **差別化** |
| REQ-F-031 | 法人版販売寄与モジュール強化 | **差別化** |
| REQ-F-033 | CV設計監査機能 | **差別化** |
| REQ-F-035 | AIフリーフォームHTML/CSSブロック | **差別化** |
| REQ-F-040 | Write Authority Lock | **差別化** |
| REQ-F-043 | Open Editor Bridge Plugin | **差別化** |

---

### 4. 差別化サマリ

| 差別化レベル | 件数 | 主な分野 |
|---|---:|---|
| killer | 16 | AI 操作 (11) + ガバナンス (5) |
| **差別化** | 13 | CV/収益化/SNS/拡張性 |
| 部分差別化 | 9 | 収益化ブロック/SEO/A-B/アセット等 |
| 標準 | 2 | FSE 基盤/SEO Core |
| 根拠なし（要再評価）| 5 | SNS 深い統合/Write Lock/Open Bridge 等 |

**総 REQ-F 数: 43件**（REQ-F-001 〜 REQ-F-043）

---

## 設計への示唆

### 訴求の優先順位

AGENT NEO の最大の差別化軸は **AI 操作・自律化（killer 11件）** に集中している。
競合（ThemeB/テーマA/テーマC）はいずれも「人間が UI で操作する」前提設計であり、この軸への追従は構造的に困難（UI を捨てなければ JSON 契約に移行できない）。

最も強力なマーケティングメッセージは:
> 「ThemeB がクリーンで使いやすい理由と、テーマC が収益化で強い理由、その両方を持ちながら、AI が JSON 契約で安全に操作できる唯一のテーマ」

### 根拠強化が必要な REQ-F

REQ-F-019（LINE 統合）、REQ-F-020（SNS 暗号化）、REQ-F-033（CV 監査閾値）の 3 件は L3 詳細設計着手前に追加調査を実施すること。

### 「標準」要件の意義

REQ-F-001（FSE 基盤）と REQ-F-011（SEO Core）は差別化材料ではないが、AGENT NEO の土台として必須。
テーマA の PHP 8.2 互換性破壊（100+ Warning）と Cookie/Cache-Control の矛盾は、「競合が同等機能を持っていても品質が劣る」という間接差別化の根拠として活用できる。

---

**作成**: 2026-04-30 / PM Opus（Sonnet Agent）
**根拠レポート**: 解析レポート 30〜36（実コード・実観測）、11・26・27（競合比較）
