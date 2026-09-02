# WPサイト運用とSEOの不都合な真実

## 1. 結論

WordPressテーマをAI運用・SEO運用・法人LP運用の基盤にする場合、最大のリスクは「機能不足」ではなく、運用後に静かに壊れる要素を事前に契約化していないことにある。

AGENT NEOでは、SEO、Core Web Vitals、AI/LLMO、WP-Cron、REST API、プラグイン衝突、更新、キャッシュ、構造化データ、プライバシー、復旧を個別機能ではなく `risk-ledger` として扱うべきである。

## 2. 公式情報から見える前提

| 観点 | 公式前提 | AGENT NEOでの意味 |
|---|---|---|
| AI大量生成 | Googleは「検索順位操作を主目的に大量ページを生成すること」をscaled content abuseとして扱う。AI生成か人力かは免罪符ではない。 | Automation SEO連携は、量産ではなく根拠・差分・人間レビュー・公開理由を持つ必要がある。 |
| canonical/noindex | Googleは重複URLの正規化にはcanonicalを推奨し、noindexはcanonical選択の代替ではない。 | SEO変更は単純トグルでは危険。canonical/noindex/robots/sitemapを同時評価する必要がある。 |
| Lazy Load/JS | Google Searchはユーザー操作をしないため、クリックやスクロール後にしか出ない重要コンテンツは見えない可能性がある。 | FAQ、比較表、CTA、価格、レビュー、根拠は初期HTMLまたはAI snapshotに露出させる。 |
| Core Web Vitals | LCP/INP/CLSは実ユーザーのfield metricが重要。LighthouseだけではINPを直接測れない。 | 速度はデモ値ではなくRUM/CrUX前提。GTM、広告、AB、フォームが劣化要因になる。 |
| WP-Cron | WP-Cronはページロードで起動し、常時動くsystem cronではない。 | 予約投稿、AI同期、レポート生成、A/B集計をWP-Cronだけに依存しない。 |
| REST API | WordPress RESTは `permission_callback`、`current_user_can`、sanitize/validateが前提。 | AIが触るAPIほど、nonceだけでなくcapability、package scope、dryRunが必須。 |
| Nonce | WordPress nonceは認証・認可・アクセス制御の代替ではない。 | nonce成功を「安全」と扱う設計は不可。権限チェックを別に置く。 |
| Privacy | 個人データ、REST露出、export/erase、外部サービス送信はプライバシー設計対象。 | 計測、AIログ、crawlerログ、サポートバンドルはマスキングと保持期限が必要。 |

## 3. SEO上の不都合な真実

| ID | 不都合な真実 | 実害 | AGENT NEOの対策 |
|---|---|---|---|
| SEO-01 | WordPressは初期状態でSEOに強いのではなく、重複URLを作りやすい。 | タグ、カテゴリ、著者、日付、検索結果、添付ファイル、ページネーション、絞り込みURLが薄いページや重複として増える。 | `seo-indexing-policy.json` に indexable / noindex / canonical / sitemap inclusion をページタイプ別に定義する。 |
| SEO-02 | sitemap送信はインデックス保証ではない。 | sitemapにあるがクロールされない、canonicalが別URLに寄る、低品質扱いでindexされない。 | `indexing-safety-check.schema.json` で sitemap、canonical、robots、status code、internal linksを同時検証する。 |
| SEO-03 | canonicalとnoindexを混ぜると意図しない除外が起きる。 | 正規URLのシグナル統合ではなく、ページ自体が検索から落ちる。 | canonical/noindex/robots変更は `seo-risk-diff` でHigh警告にする。 |
| SEO-04 | AI記事量産はSEO資産ではなくスパム負債になり得る。 | scaled content abuse、低品質評価、ブランド信頼毀損、法人案件での説明不能。 | `content-origin.schema.json`、`claim-risk.schema.json`、人間レビュー、根拠ID、公開理由を必須化する。 |
| SEO-05 | 構造化データは出せば有利ではない。 | 本文にない情報、古い価格、偽レビュー、不可視FAQをマークアップするとリッチリザルト対象外や手動対応リスク。 | JSON-LDは可視本文と `evidence_id` に同期し、古い価格・レビューは `valid_until` で失効させる。 |
| SEO-06 | アフィリエイト比較は法務・信頼・SEOの三重リスク。 | PR表記不足、根拠不明ランキング、古い価格、誇大表現でCV以前に信頼を失う。 | PR表示、比較基準、検証日、代替案、価格更新日をブロック契約に入れる。 |
| SEO-07 | JavaScript依存のタブ、アコーディオン、load moreは検索/AIに弱い。 | 重要情報がレンダリング後・操作後にしか現れず、検索/AI snapshotで欠落する。 | 重要情報はHTML本文、JSON-LD、public snapshot、answer unitに重複ではなく同期露出する。 |
| SEO-08 | 絞り込み・検索・パラメータURLはクロール予算を溶かす。 | EC/比較/ランキング系で無限URLが生成され、重要ページの発見が遅れる。 | faceted URL policy、parameter allowlist、canonical/noindexルール、crawl mapを設計に入れる。 |
| SEO-09 | SEOプラグインとテーマSEOの二重出力はよく壊れる。 | title、description、canonical、OGP、JSON-LDが重複・矛盾する。 | `seo-output-owner` を1つに決め、Yoast/Rank Math等は検出して出力抑制する。 |
| SEO-10 | 移行は最大級のSEO事故ポイント。 | 旧URL、添付URL、canonical、内部リンク、画像URL、構造化データ、リダイレクトが崩れる。 | `migration-seo-checklist.json` と公開前crawl diffを必須にする。 |

## 4. WordPress運用上の不都合な真実

| ID | 不都合な真実 | 実害 | AGENT NEOの対策 |
|---|---|---|---|
| OPS-01 | WP-Cronは正確なスケジューラではない。 | 予約投稿、AI同期、レポート、Webhook再送、A/B集計が遅延・未実行になる。 | WP-Cron、WP-CLI cron、外部cron、手動再実行を `cron-reliability-contract.json` で管理する。 |
| OPS-02 | キャッシュ/最適化プラグインはSEOとAPIを壊す。 | stale meta、古いcanonical、RESTレスポンスキャッシュ、nonce不整合、AB配信の固定化。 | `cache-compatibility-contract.json` で除外URL、除外ヘッダ、パージ条件を宣言する。 |
| OPS-03 | FSEのテンプレート編集はDBに入り、テーマファイルと乖離する。 | 更新しても見た目が変わらない、AIがファイルを直しても本番に反映されない。 | `theme-db-drift-detector.json` でDBテンプレート差分を検出し、export/importを標準化する。 |
| OPS-04 | プラグイン追加は機能追加ではなく攻撃面とサポート範囲の拡大。 | SEO、cache、security、block、form、analyticsが互いにDOM/API/headを改変する。 | `plugin-conflict-playbook.json` と adapter registryで対応済み/未対応を表示する。 |
| OPS-05 | 自動更新は安全策でもあり破壊要因でもある。 | 遅らせれば脆弱性、即時更新すれば互換性破壊。 | update preflight/postflight、rollback point、fatal recovery、known issue feedを必須にする。 |
| OPS-06 | バックアップは復旧テストしない限り安心材料ではない。 | バックアップはあるが戻せない、DBだけ戻してuploadsやoptionsが合わない。 | `restore-drill-runbook.schema.json` で定期復旧演習と証跡を残す。 |
| OPS-07 | 共有サーバーではREST、loopback、WAF、cron、file permissionが不安定。 | AI操作、更新確認、外部API、画像生成、cronが環境依存で失敗する。 | `wp-environment-diagnostics.schema.json` で導入時診断を行い、利用可能な運用面を切り替える。 |
| OPS-08 | メディアとDBは静かに肥大化する。 | LCP悪化、バックアップ肥大、検索/管理画面遅延、ストレージコスト増。 | `data-bloat-budget.json` で画像サイズ、生成サムネイル、revision、transient、autoload optionを監視する。 |
| OPS-09 | サポートコストの中心は「テーマ機能」ではなく環境差分。 | 問い合わせの大半がプラグイン衝突、古いPHP、WAF、キャッシュ、権限、更新失敗になる。 | `support-bundle.schema.json` でマスク済み環境情報を出力し、診断を自動化する。 |
| OPS-10 | all-in-oneテーマは売りやすいが保守しづらい。 | テーマ停止でSEO/CPT/計測データが失われ、移行不能になる。 | Theme Coreは表示、Core Pluginはデータ/API/SEO/計測に分ける。 |

## 5. セキュリティ/可用性上の不都合な真実

| ID | 不都合な真実 | 実害 | AGENT NEOの対策 |
|---|---|---|---|
| SEC-01 | nonceは認証でも認可でもない。 | nonce検証だけで設定変更APIを許すと権限昇格やCSRF周辺事故が起きる。 | すべてのwrite APIに `current_user_can`、package scope、schema validation、audit logを置く。 |
| SEC-02 | AI操作APIは通常の管理画面より危険。 | 速く大量に、正しそうな変更を、意図せず広範囲に適用できる。 | dryRun、diff hash、approval、rollback point、critical SEO warningを必須化する。 |
| SEC-03 | 外部URL取得はSSRFリスク。 | AIが指定したURL、OGP取得、画像取得、RSS、移行元取得で内部ネットワークへ到達する。 | private IP deny、redirect制限、timeout、content-type allowlist、サイズ上限を設ける。 |
| SEC-04 | ログは証跡であると同時に漏洩源。 | IP、メール、token、ライセンス、nonce、外部APIキーがsupport bundleやAIログに混ざる。 | log masking、retention、export/erase、PII分類、secret scannerを標準化する。 |
| SEC-05 | security plugin依存は万能ではない。 | WAFやsecurity pluginがREST/API/cron/loopbackを止める。逆に脆弱なsecurity pluginも存在する。 | security pluginを必須依存にせず、互換性診断と最小権限設計を優先する。 |
| SEC-06 | AIクローラ制御はrobots.txtだけでは強制できない。 | 意図せず学習/取得される、または検索AIまで閉じて可視性を失う。 | crawler access matrix、WAF、log、Content Signals相当、page別方針を分ける。 |

## 6. AIエージェント運用上の不都合な真実

| ID | 不都合な真実 | AI運用での問題 | AGENT NEOの設計 |
|---|---|---|---|
| AI-01 | 人間向けUIはAIにとって不安定なAPIではない。 | DOM変更、翻訳、CSS class変更、プラグイン追加で自動操作が壊れる。 | REST/MCP/WP-CLIを正本にし、DOMは補助経路に限定する。 |
| AI-02 | AIは「更新すべきでないSEO設定」を見分けきれない。 | noindex、canonical、slug、redirect、schemaを不用意に変える。 | `critical_seo_change` をblocking warningにし、人間承認を求める。 |
| AI-03 | AIが生成した比較・価格・レビューは劣化が速い。 | 古い価格、販売終了、規約変更、根拠切れが放置される。 | `last_verified_at`、`valid_until`、定期再検証job、根拠URLを必須化する。 |
| AI-04 | AI向けsnapshotに秘密が混ざりやすい。 | draft、private、license、nonce、管理者情報、ログが公開snapshotに入る。 | snapshot allowlist方式にし、private denylistではなくpublic allowlistで出す。 |
| AI-05 | AI検索流入は計測が不完全。 | queryが取れない、referrerが欠ける、direct扱いになる、citation検出が遅れる。 | `ai_referral_visit` と `ai_citation_detected` は推定値として扱い、通常CVと分ける。 |

## 7. AGENT NEOに追加すべき契約

| 契約 | 目的 |
|---|---|
| `risk-ledger.schema.json` | SEO/運用/セキュリティ/AIのリスクをID、影響度、検出方法、対策、残リスクで管理する。 |
| `seo-hazard-policy.json` | canonical/noindex/robots/sitemap/redirect/schema変更の危険度を定義する。 |
| `indexing-safety-check.schema.json` | 公開前にURL単位でindexabilityを検証する。 |
| `cache-compatibility-contract.json` | キャッシュ、最適化、CDN、AB、REST、nonce、head出力の互換ルールを管理する。 |
| `cron-reliability-contract.json` | WP-Cron、server cron、WP-CLI、external cron、manual retryの実行面を定義する。 |
| `plugin-conflict-playbook.json` | SEO/cache/security/form/block/analyticsプラグインの衝突検出と回避策を管理する。 |
| `wp-environment-diagnostics.schema.json` | PHP/WP/DB/REST/loopback/WAF/cron/file permission/object cacheを導入時に診断する。 |
| `restore-drill-runbook.schema.json` | バックアップ復旧演習の手順、成功条件、証跡を定義する。 |
| `content-quality-risk.schema.json` | AI生成、アフィリエイト、比較、価格、YMYL、PR表示、根拠不足を評価する。 |
| `theme-db-drift-detector.json` | FSE DBテンプレートとテーマファイルの乖離を検出する。 |
| `data-bloat-budget.json` | media、revision、transient、autoload option、logsの肥大化予算を管理する。 |
| `support-cost-model.json` | サポート難度、環境差分、プラグイン衝突、法人SLAを見積もる。 |

## 8. パッケージ別の示唆

| パッケージ | 不都合な真実 | 設計反映 |
|---|---|---|
| 個人/アフィリエイト | 収益化機能ほどSEO・PR・根拠・価格更新リスクが高い。 | 比較基準、PR表示、価格検証日、根拠ID、クリック計測、schema同期をP0にする。 |
| 法人/LP | LPは見た目より、計測、復旧、承認、可用性、プラグイン衝突対応が価値になる。 | 法人版は「高額テーマ」ではなく `LP運用基盤 + risk guard + support bundle` として売る。 |
| 移行プラグイン | 移行は無料リードだが、SEO事故を起こすと信頼を失う。 | 移行前crawl、URL mapping、redirect、canonical、画像、構造化データのdiffを必須にする。 |
| Automation SEO | AI原価だけでなく、品質保証と誤更新防止が価値。 | 別課金の理由を「AI生成」ではなく「検証・差分・公開品質・再検証」に置く。 |

## 9. L1/L2反映

| 反映先 | ID | 内容 |
|---|---|---|
| L1 requirement | `REQ-NF-018` | SEO/運用/セキュリティ/AI運用の不都合な真実をrisk-ledgerとして管理する。 |
| L1 acceptance | `ACC-NF-012` | canonical/noindex/robots/sitemap/cache/cron/plugin/update/privacy/AI snapshotの危険変更を検出できる。 |
| L2 ADR | `ADR-016` | 不都合な真実を個別注意書きではなく、契約・診断・ゲートとして製品化する。 |
| L2 feature | `F-022` | SEO & Ops Risk Ledger |
| L2 API | `A-022` | `GET /wp-json/agent-neo/v1/risks/hazards` |
| L2 screen | `S-013` | Risk Ledger |

## 10. 参考ソース

- Google Search spam policies: https://developers.google.com/search/docs/essentials/spam-policies
- Google canonical guidance: https://developers.google.com/search/docs/crawling-indexing/consolidate-duplicate-urls
- Google lazy loading guidance: https://developers.google.com/search/docs/crawling-indexing/javascript/lazy-loading
- Google faceted navigation guidance: https://developers.google.com/search/blog/2024/12/crawling-december-faceted-nav
- Google structured data guidelines: https://developers.google.com/search/docs/appearance/structured-data/sd-policies
- web.dev Core Web Vitals: https://web.dev/articles/vitals
- WordPress REST API routes/endpoints: https://developer.wordpress.org/rest-api/extending-the-rest-api/routes-and-endpoints/
- WordPress REST custom endpoints: https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/
- WordPress Cron: https://developer.wordpress.org/plugins/cron/
- WordPress system scheduler guidance: https://developer.wordpress.org/plugins/cron/hooking-wp-cron-into-the-system-task-scheduler/
- WordPress Nonces: https://developer.wordpress.org/apis/security/nonces/
- WordPress Security APIs: https://developer.wordpress.org/apis/security/
- WordPress Privacy handbook: https://developer.wordpress.org/plugins/privacy/
- WordPress requirements: https://wordpress.org/about/requirements/
