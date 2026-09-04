# L2 UI Elements

| element | contract |
| --- | --- |
| PatternPicker / PartSwitcher / VariantPicker | 差し替え可能なパターン・パーツ・テンプレ変種を家族（header / footer / sidebar / hero / lp / section / article）で示す |
| StructureDiff | 差し替え前後の骨格差分（テンプレート・パーツ参照）を示す |
| VariationPicker / ScaleGuard | variation 一覧と、尺度を所有しないことの検証結果（G-T1b）、見出し尺度の単調非増加（G-T3）を示す |
| ValueZoneBadge | 入力値が安全域 / 生値 / 破壊域のどれかを label と icon で示し、色だけに依存しない |
| DestructiveStop | 破壊域の値の保存を止め、触れた規則・値・境界を示す。解除手段を持たない |
| PerPostToggles | sidebar / toc / share / pr の投稿メタ 4 キーを切り替える |
| ZoneSlot | 共有 slot 6 種とゾーン語彙 23 種の置き場所。空なら描画しない |
| SectionOutline / SectionTools | H2 / H3 の階層 section 境界と安定 ID をエディタに表示し、区間単位の差し替え・リライト（diff → apply / rollback）・順序入れ替え・面の挿入・表示制御を AI を介さず操作する |
| VocabBlocks | 記事内語彙 14 種の受け皿（core + block style / 新規ブロック 6 + 空き 1 枠: 吹き出し・レビュー・商品カード・ランキング・比較専用テーブル・CTA 束）。同意バーは新規ブロックに数えず、既存パーツ（core ブロック + block style）として扱う |
| TocAnchor | 目次の配置意図（埋め込み / フロート追従 / 開閉ボタン、既定は最初の h2 直前）とページ種別ごとの表示条件・block style。目次本体は機械導出で一級要素にしない |
| PrNotice | PR 表記。広告パーツ / アフィリエイト / 商品リンクの有無から機械判定し該当ページだけに控えめに自動出力。選べるのは表示デザインと表示ページ制御。編集者が消せない |
| JsonLdEmitter / CollectionJsonLd | 単一出力元の構造化データ。型ごとに 1 本。一覧は CollectionPage、WebSite は name / alternateName / url のみとし SearchAction は出さない。FAQPage / HowTo は JSON-LD にせず、ItemList は本文語彙から導出する |
| HeroSlot / StickyStack / AnnouncementBar | LP / ホームの hero、SP ヘッダー、ドロワー、SP 下部固定の積層（同意バー > メニュー > シェア）、SP 専用広告面、お知らせバー |
| GateReport / RawValueCounter | ゲート ID・FAIL / WARN・対象・原因・baseline 値、生値件数と baseline の差 |
| CapabilityManifest / McpPack / DryRunDiff | 面・部品・値・変種・hook の一覧、設定で束ねた MCP 常用パック（1 呼び出し = 1 作業単位、dry-run / apply / rollback 内包）、dry-run の差分。REST / CLI は同じ manifest を読む従属経路 |
| ThemeSettingsForm / SettingsExportImport / ProductCatalogTable | 主たる確認面（既定 SP）と、共通宣言に対する device 別差分、目次の配置・表示条件、PR 表記のデザイン・表示制御、slot / ゾーン割当、SP 下部積層、LP 種別既定、MCP パック構成、商品正本を schema 付き設定 JSON 1 本として編集・export / import する。SP / PC の両幅をプレビューでき、商品一覧の追加・更新・記事への差し込みも行い、同じ JSON を manifest に載せる |
| AbVariantPanel / ImageOptimizationPanel / OperationsLog / DryRunReview / RollbackPanel / KeyManagement | WT-UI-10 のタブで variant の登録・固定配信・承認 / 停止 / rollback、ブラウザ側を第一経路とする WebP / WebM の生成、非ブラウザ経路の再生成 dry-run、操作ログの絞り込み・export、差分の適用 / 却下、直近変更の復旧、HELIX 接続用 API key の発行・失効と権限を扱う。鍵の値は一度だけ表示する |
| ShareProfile / ShareButtons / FeedEmbed / MessageCta | SNS profile の一元設定、記事上下・フロート・section 末尾の share、遅延読込 feed、メッセージアプリ公式アカウント追加ボタン・QR の LP CTA を表す。配信と資格情報はテーマ外とする |
| CvDefinitionPanel / MicrocopyPicker | 本 CV・マイクロ CV・補助指標の ID / 種別 / 重み / 到達条件、資料 DL の完了イベント、記事・LP・section の目標 CV、CTA の主文言と任意 microcopy の候補を選ぶ。microcopy 未選択はエラーにしない |
| BannerCatalog / BannerSlot / BannerHealth | PC / SP バナー正本と商品 ID、ゾーン・ページ種別・カテゴリ・記事単位の固定 / ローテーション、期限切れ・リンク切れ・計測ゼロを WT-UI-10 で管理する。impression / click は CV ID と variant に接続する |
| AuditReviewPanel / AuditBadge / AuditExport | HELIX 側の指摘を WT-UI-10 で一覧・対象リンク・適用 / 却下 / 保留し、記事一覧の件数バッジと JSON / CSV export を提供する。適用は dry-run → 差分レビューを通す |
| PerformanceBudgetReport / ApiContractDiff / CapabilityParityCheck | ページ種別の JS / CSS / 画像予算、主たる確認面の設定、SP / PC 両幅の Lighthouse / Core Web Vitals 測定結果、API schema / OpenAPI 差分、MCP / REST / WP-CLI の能力集合差分を機械可読な結果として示す |
| CrawlDashboard / RobotsAiCrawlerToggle | クローラー別来訪数推移、最終クロールが古い URL、404 / 5xx URL、新規公開記事の初回捕捉時間、llms.txt / crawl-map への AI クローラーアクセス有無と、llms.txt の既定出力の効果実証用アクセス時系列を示し、robots.txt と AI クローラーの許可 / 拒否を設定 JSON へ保存する。WP が応答したリクエストだけを対象とし、キャッシュ / CDN 応答は対象外と表示する |
| SpSurfacePanel / SpVocabularyMap / SpPreview | 共通の面・語彙・パーツ定義と SP / PC の device 別差分を選び、SP ヘッダー（ロゴ・ハンバーガー・検索・主要 CTA）、ドロワー（階層・CTA・SNS）、SP 下部固定（3〜5 タブ）、SP 専用広告面、比較テーブル・タブ・目次・画像ギャラリー・CTA の両幅の挙動を JSON で確認する。主たる確認面はサイト設定で選び、SP / PC の両幅プレビューを WT-UI-10 と MCP から取得する |
| TagSlotPanel / DataLayerContractPanel / ConsentStatePanel | head・body 先頭・body 末尾のタグ slot、version 付きデータ層 schema、必須 ID、必須・計測・広告の 3 カテゴリと Consent Mode v2 の 7 種への写像、同意信号、consent default を最初にする注入順、遅延発火・サーバー受信検証の契約を WT-UI-10 と MCP から確認する |
| PluginCapabilityMatrix / PluginConflictWarning | 第三者プラグインの検出結果・現在の選択・領域別既定・キャッシュ等の警告を capability manifest と管理画面で同じ値として示し、二重出力・二重フォーム送信の検査結果を表示する |
| EvidenceLedger / EvidenceLink | 実証記録行（パターン ID・参照元 commit・証跡パス・ゲート結果）と証跡リンク。他リポの項目は持たない。secret 値・実サイト情報は表示しない |

表示 field は `docs/requirements/l3/traceability.json` の surface relation へ 1 つ以上で接続する。

## S3 WT-UI-10 / WT-UI-11 追加要素

| element | contract |
| --- | --- |
| AuthorReviewerPanel | 著者・監修者の名前・経歴・資格・sameAs・画像を正本として管理し、著者欄・監修者欄・著者アーカイブへ反映する |
| RecommendationPanel | 関連・人気・おすすめを分け、人気の集計方式・期間と手動おすすめ順を選ぶ。自前集計は IP なしの日次集約だけを許可する |
| TypographyPanel | 和文フォントの複数系統、unicode-range サブセット、size-adjust、OFL 表記、速度予算を確認する |
| ReadbackPanel / LinkHealthPanel / HostCapabilityPanel | 集計読み戻し、商品・本文外部リンクの HEAD 検査警告、PHP / DB / 画像 / cron / cache / WAF / SMTP の capability を同じ JSON で表示する |
| SelectionSetTransfer | template part・global styles・設定・商品・zone のスラッグ参照を export / import し、staging dry-run から production apply へ進める |
| CrawlerLedger / LogRetentionPanel | 4 分類、公式 endpoint・鮮度、未検証 UA、非準拠、cache origin、生行・日次集約・容量上限を区別して表示する |
| ConsentPrivacyPanel / AssetLedger | 同意の時刻・版・カテゴリ・撤回、外部送信公表、privacy tools、OFL / 画像 metadata / SECURITY.md の台帳を確認する |

J-09 の reader-facing AI disclosure と J-13 の dark mode は要素化しない。

## S4 WT-UI-10 / WT-UI-04 追加要素（2026-09-05）

| 要素 | 役割 |
| --- | --- |
| DefaultSetPicker / PartsPicker / PostOverridePanel | 3 層（サイト既定セット / パーツ単位 / 記事単位）の選択 UI。選択セットとプレビュー付き視覚ピッカーで選ぶ。サイト既定セットとパーツ単位の結果は設定 JSON へ、記事単位の上書きは投稿メタへ書く（WT-Q-ADMIN-04） |
| EyecatchPanel | WT-UI-04 の投稿メタ eyecatch（位置・有無）。未設定はサイト既定に従う（WT-Q-META-02） |
| ConsentBarToggle | 同意バーの ON/OFF と位置（先頭非固定 / 下部固定）の 2 選択のみ（WT-Q-CONSENT-02） |
