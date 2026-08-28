# 共通強化プラグインとAutomation SEOプラグイン情報設計

## 結論

ThemeB/テーマA/テーマC/テーマD/テーマEを横断して強化するなら、作るべきものは「各テーマを直接改造する専用アダプタ群」ではなく、**Automation SEO Theme Bridge Plugin**である。

このプラグインの役割は、テーマの見た目を置き換えることではない。既存テーマのDOM、SEOメタ、CTA、LP/記事構造、計測状態、外部プラグイン依存を読み取り、Automation SEOが安全に診断・提案・移行判断できる**機械可読な契約層**を作ることにある。

AGENT NEOではさらに一歩進めて、Theme Bridgeが発見する情報を最初から `section_id`、`cta_id`、`offer_id`、`seo_meta`、`blueprint`、`safe apply` として正規契約化する。つまり、既存テーマ向けブリッジはリード獲得・診断・移行入口、AGENT NEOは第一級の書き込み先として設計する。

## 1. 各テーマを強化できる共通プラグイン機能

| 機能 | 強化される点 | 対象テーマ |
|---|---|---|
| Theme Capability Scanner | 現在テーマ、親/子テーマ、FSE対応、SEO出力元、LP/CTA/計測対応、速度リスクを診断 | 全テーマ |
| Section/CTA ID Layer | 見出しやCSS selector依存を補い、AIと計測が使える `section_id` / `cta_id` を付与 | 全テーマ |
| SEO Meta Normalizer | テーマ固有メタ、Yoast/Rank Math等、テーマA型テーマ内SEOを共通形式へ正規化 | ThemeB/テーマA/テーマC/テーマD |
| CTA/Offer Registry | ASPリンク、資料DL、外部フォーム、問い合わせCTAを `offer_id` と紐付ける | 個人/法人両方 |
| Tracking Context v2 | `page_type`、`section_type`、`cta_ids`、`offer_ids`、`selector_confidence` をAutomation SEOへ送る | 全テーマ |
| Plugin Conflict Detector | SEO重複、schema重複、cache/minify破壊、GA/GTM二重計測を検出 | 全テーマ |
| Performance Impact Collector | テーマ・プラグイン・タグ単位でCore Web Vitals悪化要因を記録 | 全テーマ |
| Privacy/Data Map | 外部送信、Cookie、計測、保持期間、同意状態を可視化 | 全テーマ |
| Migration Blueprint Exporter | 既存ページをAGENT NEOのLP/HP/記事blueprintへ変換する下準備 | 全テーマ |
| AI Safe Apply Gateway | AGENT NEOではdryRun/apply/rollback、既存テーマでは原則preview/提案止まり | AGENT NEO優先 |
| Support Bundle | WP/PHP/テーマ/プラグイン/REST/cron/cache状態を診断zip化 | 全テーマ |
| LLMO Snapshot | answer unit、citation anchor、evidence、AI visibility policyの不足を検出 | 全テーマ |

## 2. 不都合な真実

| 不都合 | 実害 | 対応 |
|---|---|---|
| DOMやCSS classはAPIではない | AIがselectorで書き換えるとアップデートで壊れる | `selector_confidence` とstable IDを必須化 |
| 見出しベースのsection推定は弱い | h2順序変更、LP構造、装飾見出しで誤計測する | `data-agent-section-id`、block anchor、content hashを併用 |
| SEOメタの保存先がバラバラ | title/canonical/noindex/schemaが重複・競合する | source付きのSEO Meta Normalizerを持つ |
| CTAの正体が標準化されていない | クリックは取れても何のCV導線か学習できない | CTA/Offer Registryを導入 |
| WP-Cronは信頼できるジョブ基盤ではない | 低PVサイトやcache環境で同期が遅延する | external cron/webhookとidempotent jobを併用 |
| public tracking endpointはノイズを受ける | spam/bot/改ざんイベントで学習が汚れる | token、domain、rate limit、署名、bot filter、dedupe |
| FSE DBテンプレートはテーマファイルと乖離する | ファイル上の設計と本番表示が一致しない | theme DB drift detectorを持つ |
| cache/minify/CDNは計測とA/Bを壊す | nonce、variant、遅延JS、beaconが欠落する | cache compatibility profileを持つ |
| 既存テーマの深い個別adapterは保守地獄になる | テーマ更新ごとに破綻する | deep writeはAGENT NEOだけ、既存テーマは診断/移行中心 |
| プラグインが持つべきでない情報まで抱えると危険 | PII、秘密情報、SEO保証、ライセンス情報漏洩 | 最小保持、マスク、retention、監査ログを必須化 |

## 3. Automation SEOプラグインに持たせるべき情報

| 情報カテゴリ | 主なフィールド | 用途 |
|---|---|---|
| Site Profile | `site_id`、`site_token`、`home_url`、`allowed_domains`、`wp_version`、`php_version` | 同期・認証・互換診断 |
| Theme Profile | `theme_slug`、`theme_version`、`parent_theme`、`child_theme`、`is_fse`、`template_mode` | テーマ能力判定 |
| Plugin Profile | `active_plugins`、`seo_plugins`、`cache_plugins`、`form_plugins`、`tracking_plugins` | 競合検出 |
| Page Registry | `wp_post_id`、`url`、`post_type`、`status`、`template`、`canonical`、`noindex`、`content_hash` | ページ同期 |
| Section Registry | `section_id`、`source`、`selector`、`selector_confidence`、`title`、`order`、`section_type`、`content_hash` | セクション単位改善 |
| CTA Registry | `cta_id`、`section_id`、`label`、`destination_url`、`cta_type`、`variant_id`、`offer_id` | CRO/計測 |
| Offer Registry | `offer_id`、`merchant`、`affiliate_network`、`sponsored`、`nofollow`、`disclosure_state` | アフィリエイト/法人CV |
| SEO Meta Normalized | `title`、`description`、`canonical`、`robots`、`ogp`、`schema_entities`、`source`、`conflicts` | SEO改善 |
| Tracking Profile | `event_names`、`web_vitals`、`section_engagement`、`clicks`、`conversions`、`last_seen_at` | 学習・効果測定 |
| Privacy Profile | `consent_required`、`tracking_enabled`、`external_send_targets`、`cookie_use`、`retention_days` | 法令/運用 |
| Integration Health | `last_sync_at`、`last_error`、`retry_count`、`api_latency_ms`、`connector_version` | 可用性監視 |
| Safe Apply State | `supports_write`、`dry_run_diff`、`rollback_token`、`risk_level`、`human_approval_required` | AI反映制御 |
| Migration Blueprint | `target_blueprint`、`unsupported_parts`、`manual_review_items`、`confidence` | AGENT NEO移行 |

## 4. Automation SEOプラグインに持たせないもの

| 持たせないもの | 理由 |
|---|---|
| 参照テーマのCSS/画像/固有デザイン資産 | ライセンス/コピーリスクがある |
| WP本文の正本 | WordPress本体とデータ二重管理になる |
| 長期の生PIIログ | 個人情報・同意・漏洩リスクが高い |
| LLMプロンプト全文や秘密キー | 漏洩時の影響が大きい |
| 決済/ライセンス秘密情報の平文 | 販売・認証リスクが高い |
| SEO順位保証のような販売表現 | 景表法・信頼性リスクがある |
| 既存テーマごとの深い書き換えロジック | 更新追従コストが高すぎる |

## 5. テーマ別に強化できるポイント

| テーマ | プラグインで強化できる点 | 限界 |
|---|---|---|
| ThemeB | 速度基盤を崩さず、LP/再利用パーツ/広告/Entity GraphをAutomation SEOの診断対象にできる | SEOメタ主導権が外部SEOプラグイン寄りになりやすく、書き込みは慎重にすべき |
| テーマA | テーマ内SEOメタを正規化し、canonical/noindex/OGP/JSON-LDをAutomation SEOへ渡しやすい | classic template、global CSS、jQuery、CDN前提は速度・AI編集の制約になる |
| テーマC | 収益化/CTA/タグ/A-B系の価値を共通CTA/Offer Registryに吸収できる | 設定が複雑で、AIが安全に触るにはcapability mapが必須 |
| テーマD | 無料導入口としてSEO/広告/ランキング/高速化状態を診断し、AGENT NEO移行提案に使える | 高額課金への転換には診断結果と改善差分の見せ方が必要 |
| テーマE | 法人HP/LPのCTA、フォーム、事例、価格、資料DLをlead trackingに接続しやすい | LP改善/A-B/AI運用の訴求は標準では弱い |

## 6. Automation SEO側とAGENT NEO側の責務分離

| 領域 | Automation SEO Theme Bridge Plugin | AGENT NEO Theme/Core |
|---|---|---|
| 読み取り | 既存テーマから構造・SEO・CTA・計測状態を抽出 | 正規契約をそのまま返す |
| 書き込み | 既存テーマでは原則preview/提案止まり | dryRun/apply/rollback/audit logで安全に反映 |
| セクション識別 | selector、heading、anchor、hashからconfidence付き推定 | `data-agent-section-id` を正本化 |
| SEO | source付き正規化、重複検出 | SEO Coreが保存/出力/重複抑制 |
| CTA/CRO | 既存リンク/ボタンを推定して登録 | CTA/Offer Registryを正本化 |
| 計測 | public endpointは署名/レート制限/同意で防御 | event contractとRUMを標準搭載 |
| 移行 | blueprint exportと不足リスト作成 | blueprint importとsafe apply |

## 7. MVP優先順位

| 優先度 | 機能 | 理由 |
|---|---|---|
| P0 | Theme Capability Scanner | 最初に「このサイトは何ができて何が危険か」を判定するため |
| P0 | SEO Meta Normalizer | テーマA優位のSEO情報をAutomation SEOで使えるようにするため |
| P0 | Section/CTA Registry | AI改善、計測、移行の共通キーになるため |
| P0 | Tracking Context v2 | 既存 `track-context` をAI運用向けに拡張するため |
| P0 | Privacy/Data Map | 外部送信と計測を販売上のリスクにしないため |
| P1 | Plugin Conflict Detector | SEO/cache/tracking重複を早期に見つけるため |
| P1 | Migration Blueprint Exporter | S1/AGENT NEO移行への導線になるため |
| P1 | Support Bundle | 法人運用とサポート工数削減に効くため |
| P2 | Theme-specific deep adapter | 保守コストが高いため、上位顧客/移行支援時だけに限定 |

## 8. 契約サンプル

```json
{
  "contract_version": "2.0",
  "site": {
    "site_id": "wp_123",
    "home_url": "https://example.com",
    "wp_version": "6.6.x",
    "php_version": "8.1+"
  },
  "theme": {
    "theme_slug": "themeB",
    "theme_version": "2.16.0",
    "is_fse": false,
    "capabilities": ["lp", "reusable_parts", "seo_plugin_coexistence"],
    "risks": ["no_stable_section_contract"]
  },
  "page": {
    "wp_post_id": 123,
    "url": "https://example.com/lp/",
    "page_type": "corporate_lp",
    "content_hash": "sha256:..."
  },
  "sections": [
    {
      "section_id": "hero",
      "section_type": "hero",
      "selector": "#hero",
      "selector_confidence": 0.92,
      "content_hash": "sha256:..."
    }
  ],
  "ctas": [
    {
      "cta_id": "hero_primary",
      "section_id": "hero",
      "cta_type": "lead",
      "offer_id": "consultation",
      "destination_url": "/contact/"
    }
  ],
  "seo_meta": {
    "title": "Example LP",
    "canonical": "https://example.com/lp/",
    "robots": "index,follow",
    "source": "theme_or_plugin",
    "conflicts": []
  },
  "safe_apply": {
    "supports_write": false,
    "mode": "preview_only",
    "human_approval_required": true
  }
}
```

## 9. AGENT NEOへ取り込む設計判断

| 判断 | 内容 |
|---|---|
| 既存テーマ向け | Automation SEO Theme Bridge Pluginは診断、計測、正規化、移行入口に限定 |
| AGENT NEO向け | AGENT NEO Core Pluginがwrite targetになり、safe applyを正式提供 |
| 販売導線 | 無料/低価格ブリッジで課題を可視化し、AGENT NEO購入またはS1移行へ誘導 |
| データ設計 | Automation SEOが持つのは正規化されたメタ情報、confidence、差分、計測。WP本文・秘密情報・デザイン資産は持たない |
| 差別化 | 既存テーマを「AIで扱いやすくする」のではなく、AGENT NEOでは「最初からAIが扱える」ことを売る |

## 10. Gate判定

| Gate | 判定 | 根拠 |
|---|---|---|
| RG0 | passed | seo-tool-connector REST、section tracker、Automation SEO tracking schemaを確認 |
| RG1 | passed | `track-context`、`section-engagement`、`section_id`、selector契約を観測 |
| RG2 | passed | 既存契約の不足をTheme Bridge Plugin/AGENT NEO責務に分離 |
| RG3 | passed_with_caution | 既存テーマへの自動書き込みは壊れやすいためpreview/診断中心に制限 |
| R4 | passed | L1/L2へREQ-NF-020、ADR-019、F-024として反映する |
