# Automation SEO連携観点のThemeB/テーマA採点

## 結論

Automation SEO連携だけで見ると、**テーマAはSEOメタ/構造化データの操作対象として強く、ThemeBは速度・部品構造・LP/再利用パーツの土台として強い**。

ただし両テーマとも、Automation SEOが本当に必要とする `section_id`、`cta_id`、`variant_id`、`content_hash`、dryRun/apply、rollback、差分同期、双方向マッピングをテーマ標準契約としては持っていない。したがって、AGENT NEOでは両テーマを直接上書き運用するのではなく、移行・診断・設計参考に限定し、運用ターゲットはAGENT NEO Theme + Core Pluginの契約に集約する。

## 採点軸

| 評価軸 | 配点 | Automation SEOが必要とする条件 |
|---|---:|---|
| SEOメタ/Entity連携 | 20 | title、description、canonical、robots、OGP、JSON-LDをAPI/REST/差分で扱える |
| セクション契約 | 20 | `section_id`、selector、order、content_hashが安定し、見出し変更で壊れない |
| CTA/A-B/CRO連携 | 15 | `cta_id`、`offer_id`、`variant_id`、impression/click/conversionを持てる |
| 記事/LP投入 | 15 | AI生成記事、LPセクション、再利用パーツ、画像、構造化データを投入しやすい |
| 速度/計測共存 | 10 | 計測JSやA/Bを足してもCore Web Vitalsを壊しにくい |
| API/自動化安全性 | 10 | REST、Cron、外部HTTP、nonce/capability、rate limit、retryの設計が堅い |
| 移行/ポータビリティ | 10 | 既存データを抽出しやすく、テーマ依存ロックインが少ない |

## 総合スコア

| テーマ | 点数 | 判定 |
|---|---:|---|
| ThemeB | 73 | Automation SEOの運用基盤としては速度・部品・JSON-LDが強いが、SEOメタが外部プラグイン寄りでAI一括制御が弱い |
| テーマA | 77 | SEOメタ/OGP/canonical/noindex/JSON-LDの一体感はAutomation SEO向き。ただし速度、セクション安定性、FSE/API契約が弱い |

テーマAのほうがAutomation SEOの「SEO再生成・メタ更新・Entity提案」とは相性が良い。ThemeBはAutomation SEOの「LP/記事を高速に配信し、部品と計測を壊さない受け皿」として参考価値が高い。

## Automation SEO側の既存連携資産

ローカル確認したAutomation SEO/seo-tool-connector側の資産は次の通り。

| 領域 | 既存実装 | 評価 |
|---|---|---|
| WP連携API | `backend/app/api/v1/wordpress.py` に `/pages/sync/{site_id}` | WP投稿/固定ページを `wp_pages` に同期できる |
| ページ構造同期 | `backend/app/api/v1/tracking.py` の `/tracking/context` | `wp_post_id`、`page_url`、`sections`、`content_hash`を保存できる |
| セクションDB | `wp_pages`、`wp_page_sections`、`section_metrics_daily` | セクション単位改善のデータ基盤がある |
| セクション計測 | `assets/js/section-tracker.js`、`/section-engagement` | time、scroll、click、copy、link_clickを収集できる |
| CTA/A-B | `tracker.js`、AB test API、`variant_id` | variant別の露出/クリック/コンバージョン導線がある |
| 改善文脈注入 | `brief_context_injector.py` | GSC/section metricsを記事改善コンテキストへ渡せる |
| WordPress publish/sync | `wp_integration.py` | WP投稿同期、WP要素同期、推奨反映の土台がある |

### 現状の不都合な真実

| 問題 | 影響 |
|---|---|
| `section-tracker.js` は初期値で `h2` をsection境界にする | 見出し追加/削除/並び替えで `section_1` などが変わり、過去データと改善対象がずれる |
| `selector` が `h2:nth-of-type(n)` になりやすい | テーマ変更、広告挿入、ブロック挿入でselectorが壊れる |
| CTAは `.seo-tool-element` 中心 | テーマ標準CTA、アフィリエイトCTA、LP CTAが自動的に同一契約へ乗るとは限らない |
| `track-context` は公開RESTでtoken/ドメイン制御だが、署名・idempotency・schema versionが薄い | bot/noise/重複送信/契約変更に弱い |
| `wp_pages` は投稿/固定ページ同期中心 | LP CPT、再利用パーツ、FSE template part、pattern、CTA registryまでの正規同期が不足 |
| 改善提案の適用先がHTML/selector寄りになりやすい | AIが「このsectionを安全に置換する」ためのdryRun/apply/rollbackが不足 |

## ThemeB: 73点

### 良い部分

| 観点 | Automation SEOとの相性 |
|---|---|
| 速度基盤 | 条件付きアセット、ブロックCSS、遅延読み込み思想があり、計測JS/A-B追加後も崩れにくい設計参考になる |
| JSON-LD | `@graph` 型のEntity Graph設計があり、Automation SEOのEntity提案と相性が良い |
| LP/再利用パーツ | LP CPT、Blog Parts、広告タグ、ブロック/パターン系の部品化があり、LP/CTA/再利用部品の抽出対象として有効 |
| REST/設定 | 設定や部品管理RESTの分類があり、AGENT NEOの契約設計の参考になる |
| SEOプラグイン共存 | SEO SIMPLE PACK等との分担思想があり、既存SEOデータの移行/共存に向く |

### 悪い部分

| 観点 | 連携上の弱点 |
|---|---|
| SEOメタ主導権 | title/description/canonical/noindexの主導権がテーマ外プラグインに寄りやすく、Automation SEOが一括制御しづらい |
| セクション安定ID | 標準でAutomation SEO向けの `section_id`、`cta_id`、`offer_id` を出す設計ではない |
| 双方向適用 | Automation SEOの改善提案を安全にdryRun/apply/rollbackする契約がない |
| FSE正本 | AGENT NEOが狙うtheme.json/block.json/FSE契約とは完全一致しない |
| 計測責務 | 参照テーマ内の計測/CTAをそのまま使うとseo-tool-connectorの計測と二重化しやすい |

### カバー方法

| 対応側 | 対策 |
|---|---|
| Automation SEO側 | ThemeB専用の深いアダプタを作りすぎない。移行時はREST/HTML解析で `wp_post_id`、見出し、Blog Parts、LP CPT、広告/CTA候補を抽出し、AGENT NEO標準blueprintへ変換する |
| Automation SEO側 | SEO SIMPLE PACK等の既存メタを検出して `seo-meta.schema.json` に正規化する。出力元の優先順位と重複警告を持つ |
| Automation SEO側 | `content_hash` と `selector_confidence` を持ち、`nth-of-type` 依存の低信頼selectorはAI適用対象にしない |
| WPテーマ側 | AGENT NEOでは全セクションに `data-agent-section-id`、CTAに `data-cta-id`、商品/資料DLに `data-offer-id` を出す |
| WPテーマ側 | ThemeB型の条件付きアセットだけを取り込み、tracking/A-B/CTAはCore Pluginのevent contractへ寄せる |
| WPテーマ側 | LP/Blog Parts相当を `reusable-section` としてJSON export/importできるようにする |

## テーマA: 77点

### 良い部分

| 観点 | Automation SEOとの相性 |
|---|---|
| SEO統合UX | title、description、canonical、noindex、OGP、JSON-LDをテーマ側で扱う思想がAutomation SEOのSEO提案反映に近い |
| REST可能性 | SEO post metaに `show_in_rest` があり、Automation SEOが投稿単位のSEOメタを扱いやすい |
| Entity/著者/SNS | Article、WebPage、WebSite、Breadcrumb、Person、OrganizationなどのSEO文脈が揃っている |
| デザインプリセット | AIがサイト再構築時に「見栄えの初期値」を作る参考になる |
| トップ/HP設計 | メインビジュアル、リッチメニュー、デモ再現の思想はHP/回遊ハブのblueprintへ変換しやすい |

### 悪い部分

| 観点 | 連携上の弱点 |
|---|---|
| 速度/計測共存 | グローバルCSS、jQuery、外部CDN直書き要素があり、Automation SEOの計測/A-B追加時に速度・INP面で不利 |
| セクション安定ID | SEOは強いが、LP/記事の各sectionをAutomation SEOが恒久IDで改善する契約はない |
| クラシック構造 | FSE/theme.json/block.json正本ではないため、AIがブロック/テンプレート単位で安全に編集しにくい |
| 外部URL/公開REST | URL解決系のpublic REST/AJAXはSSRF/rate limit/schema観点でそのまま参考にできない |
| SEOロックイン | SEOをテーマが持つ強みはあるが、移行時にはテーマA独自メタを標準SEO契約へ変換する必要がある |

### カバー方法

| 対応側 | 対策 |
|---|---|
| Automation SEO側 | テーマAのSEOメタを最優先で抽出し、title/description/canonical/noindex/OGP/JSON-LDを `seo-meta.schema.json` と `entity-graph.schema.json` へ正規化する |
| Automation SEO側 | テーマAページを再構築する場合、見た目ではなくmain visual/rich menu/heading/CTA/FAQなどの構造だけをblueprint化する |
| Automation SEO側 | グローバルCSSやテーマ固有classを再利用せず、AGENT NEO design presetへマッピングする |
| WPテーマ側 | AGENT NEOではテーマA型SEO統合UXを採用しつつ、SEO保存・出力・重複検知をCore Pluginの契約に分離する |
| WPテーマ側 | 速度はThemeB型に寄せ、テーマA型の巨大CSS/jQuery/CDN直書きを避ける |
| WPテーマ側 | SEO変更は `dryRun -> diff -> risk score -> apply -> rollback point` を必須にする |

## 両テーマに共通する欠損

| 欠損 | なぜ問題か | AGENT NEOでの必須対応 |
|---|---|---|
| stable section contract | Automation SEOのsection改善はIDがずれると学習・計測が壊れる | `section-registry.schema.json` と `data-agent-section-id` を正本化 |
| CTA registry | CTAがリンク/ボタン/class依存だとCV改善が不安定 | `cta-registry.schema.json`、`data-cta-id`、`data-offer-id` を必須化 |
| intent-aware blueprint | 既存テーマは見た目/設定中心で、目的・訴求・証拠・CTAの意味が弱い | `lp-blueprint.schema.json`、`conversion-intent.schema.json`、`proof.schema.json` |
| safe apply | AI改善提案を直接HTML置換すると破損・SEO事故が起きる | dryRun、diff hash、idempotency、rollback、audit log |
| SEO conflict guard | SEOプラグイン/テーマ/Automation SEOでmeta/schemaが重複する | `seo-conflict-rules.json` と出力優先順位 |
| tracking consent | 計測・外部送信を後付けすると法務/プライバシーで詰まる | opt-in、data map、privacy policy template |
| performance budget | A/B/計測/外部タグで速度が落ちる | `third-party-tags.schema.json` とWeb Vitals RUM |
| contract tests | API/REST/JS/DBの連携が暗黙だと更新で壊れる | OpenAPI/JSON Schema/consumer-driven contract tests |

## Automation SEO側で実装すべきこと

| 優先度 | 実装 | 内容 |
|---|---|---|
| P0 | Theme Capability Scanner | 現在テーマがThemeB/テーマA/AGENT NEOかを判定し、SEOメタ、section、CTA、LP、速度リスク、プラグイン衝突を診断する |
| P0 | Section ID Resolver | `data-agent-section-id` > `id` > block anchor > heading hash > nth-of-type の順でID信頼度を付ける |
| P0 | Context Contract v2 | `/tracking/context` に `contract_version`、`page_type`、`section_type`、`cta_ids`、`offer_ids`、`selector_confidence`、`schema_hash` を追加する |
| P0 | Safe Recommendation Apply | 推奨反映を直接HTML更新ではなく、AGENT NEOのdryRun/apply APIに送る。非対応テーマは提案止まりにする |
| P0 | SEO Meta Normalizer | ThemeB系SEOプラグイン、テーマAメタ、WP標準metaを共通 `seo-meta` に変換する |
| P1 | CTA/Offer Mapper | `.seo-tool-element` だけでなく、テーマ標準CTA/ASPリンク/資料DL/外部フォームを `cta_id` へ正規化する |
| P1 | Performance Impact Collector | Web Vitals、third-party tag、A/B script影響をsection/CTAと紐づける |
| P1 | Blueprint Exporter | 既存ThemeB/テーマAサイトをAGENT NEOのLP/HP/article blueprintへ変換する |

## WPテーマ側で実装すべきこと

| 優先度 | 実装 | 内容 |
|---|---|---|
| P0 | Section Registry | 全テンプレート/記事/LP/ブロックに安定 `section_id` を発行し、DOMとJSON双方に出す |
| P0 | CTA Registry | CTA、アフィリエイトリンク、資料DL、外部フォームに `cta_id`、`offer_id`、`variant_id` を付与する |
| P0 | AutomationSeoAdapter | `/tracking/context`、`/tracking/section-engagement`、`/tracking/event`、`/wordpress/pages/sync/{site_id}` と互換にする |
| P0 | SEO Core Contract | title/description/canonical/noindex/OGP/Entity GraphをCore Pluginで保存・出力・検証する |
| P0 | Safe Write API | `actions/dry-run` と `actions/apply` を分離し、diff、risk、rollback、audit logを返す |
| P0 | Theme/Plugin Boundary | テーマは表示層、Core PluginがAPI/CPT/SEO/計測/A-B/blueprintを所有する |
| P1 | Web Vitals RUM | section/CTA/variant単位でLCP/INP/CLS影響をAutomation SEOへ渡す |
| P1 | Migration Preview | ThemeB/テーマAから抽出した構造をAGENT NEO blueprintへ変換し、差分プレビューする |

## AGENT NEOの設計判断

| 判断 | 内容 |
|---|---|
| SEO | テーマA型を採用。ただしテーマロックインではなくCore PluginのSEO契約へ移す |
| 速度 | ThemeB型を採用。計測/A-B/第三者タグ込みで速度予算を管理する |
| LP/HP | ThemeBのLP/再利用パーツ、テーマAのHP/プリセットUXをblueprintへ抽象化する |
| Automation SEO | 既存APIを正本にしつつ、Context Contract v2とSafe Applyを追加する |
| 移行 | ThemeB/テーマAは運用対象ではなく移行/診断/設計参考。AGENT NEOを正規運用ターゲットにする |

## 参照したローカル証拠

| パス | 確認内容 |
|---|---|
| `C:/Users/tenni/Desktop/seo-tool-v2-docs/Automation SEO/wordpress-plugin/seo-tool-connector/seo-tool-connector.php` | REST route、track/context forward、section engagement forward、public tracking endpoint |
| `C:/Users/tenni/Desktop/seo-tool-v2-docs/Automation SEO/wordpress-plugin/seo-tool-connector/assets/js/section-tracker.js` | h2基準のsection収集、content_hash、time/scroll/click/copy計測 |
| `C:/Users/tenni/Desktop/seo-tool-v2-docs/Automation SEO/wordpress-plugin/seo-tool-connector/assets/js/tracker.js` | `.seo-tool-element`、variant、section推定、CTA click/conversion |
| `C:/Users/tenni/Desktop/seo-tool-v2-docs/Automation SEO/backend/app/api/v1/tracking.py` | `/tracking/event`、`/tracking/section-engagement`、`/tracking/context` |
| `C:/Users/tenni/Desktop/seo-tool-v2-docs/Automation SEO/backend/app/schemas/tracking_context.py` | TrackingContextRequest/Section schema |
| `C:/Users/tenni/Desktop/seo-tool-v2-docs/Automation SEO/backend/app/models/wp_pages.py` | `wp_pages`、`wp_page_sections` |
| `C:/Users/tenni/Desktop/seo-tool-v2-docs/Automation SEO/backend/app/services/tracking/section_aggregation.py` | section engagementの日次集約 |
| `C:/Users/tenni/Desktop/seo-tool-v2-docs/Automation SEO/backend/app/services/wordpress/wp_integration.py` | WordPress投稿/固定ページ同期 |
| `解析レポート/13-SEO設計比較-ThemeA優先分析.md` | テーマA SEO優先、ThemeB JSON-LD/共存設計 |
| `解析レポート/15-ページスピード設計比較.md` | 速度はThemeB優先 |
| `解析レポート/21-自動化CronAPI契約設計.md` | API/Cron/外部連携契約 |

## Gate

| Gate | 判定 | 根拠 |
|---|---|---|
| RG0 | passed | Automation SEO/seo-tool-connector、ThemeB/テーマA既存解析レポートを確認 |
| RG1 | passed | 連携契約をSEO、section、CTA、tracking、sync、safe applyへ分解 |
| RG2 | passed_with_caution | テーマAはSEO連携、ThemeBは速度/部品連携で優位。ただし両方ともstable contract不足 |
| R4 | passed | AGENT NEO側とAutomation SEO側の実装分担へrouting済み |
