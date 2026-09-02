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
| VocabBlocks | 記事内語彙 14 種の受け皿（core + block style / 新規ブロック 7: 吹き出し・タブ・レビュー・商品カード・ランキング・比較専用テーブル・CTA 束） |
| TocAnchor | 目次の配置意図（埋め込み / フロート追従 / 開閉ボタン、既定は最初の h2 直前）とページ種別ごとの表示条件・block style。目次本体は機械導出で一級要素にしない |
| PrNotice | PR 表記。広告パーツ / アフィリエイト / 商品リンクの有無から機械判定し該当ページだけに控えめに自動出力。選べるのは表示デザインと表示ページ制御。編集者が消せない |
| JsonLdEmitter / CollectionJsonLd | 単一出力元の構造化データ。型ごとに 1 本。一覧は CollectionPage |
| HeroSlot / StickyStack / AnnouncementBar | LP / ホームの hero、SP 下部固定の積層（同意バー > メニュー > シェア）、お知らせバー |
| GateReport / RawValueCounter | ゲート ID・FAIL / WARN・対象・原因・baseline 値、生値件数と baseline の差 |
| CapabilityManifest / McpPack / DryRunDiff | 面・部品・値・変種・hook の一覧、設定で束ねた MCP 常用パック（1 呼び出し = 1 作業単位、dry-run / apply / rollback 内包）、dry-run の差分。REST / CLI は同じ manifest を読む従属経路 |
| ThemeSettingsForm / SettingsExportImport / ProductCatalogTable | サイト全体の既定（目次の配置・表示条件、PR 表記のデザイン・表示制御、slot / ゾーン割当、SP 下部積層、LP 種別既定、MCP パック構成）と商品正本を schema 付き設定 JSON 1 本として編集・export / import する。商品一覧の追加・更新・記事への差し込みも行い、同じ JSON を manifest に載せる |
| AbVariantPanel / ImageOptimizationPanel / OperationsLog / DryRunReview / RollbackPanel / KeyManagement | WT-UI-10 のタブで variant の登録・固定配信・承認 / 停止 / rollback、WebP / WebM の生成・再生成 dry-run、操作ログの絞り込み・export、差分の適用 / 却下、直近変更の復旧、HELIX 接続用 API key の発行・失効と権限を扱う。鍵の値は一度だけ表示する |
| ShareProfile / ShareButtons / FeedEmbed / MessageCta | SNS profile の一元設定、記事上下・フロート・section 末尾の share、遅延読込 feed、メッセージアプリ公式アカウント追加ボタン・QR の LP CTA を表す。配信と資格情報はテーマ外とする |
| CvDefinitionPanel / MicrocopyPicker | 本 CV・マイクロ CV・補助指標の ID / 種別 / 重み / 到達条件、資料 DL の完了イベント、記事・LP・section の目標 CV、CTA の主文言と任意 microcopy の候補を選ぶ。microcopy 未選択はエラーにしない |
| BannerCatalog / BannerSlot / BannerHealth | PC / SP バナー正本と商品 ID、ゾーン・ページ種別・カテゴリ・記事単位の固定 / ローテーション、期限切れ・リンク切れ・計測ゼロを WT-UI-10 で管理する。impression / click は CV ID と variant に接続する |
| AuditReviewPanel / AuditBadge / AuditExport | HELIX 側の指摘を WT-UI-10 で一覧・対象リンク・適用 / 却下 / 保留し、記事一覧の件数バッジと JSON / CSV export を提供する。適用は dry-run → 差分レビューを通す |
| PerformanceBudgetReport / ApiContractDiff / CapabilityParityCheck | ページ種別の JS / CSS / 画像予算、Lighthouse / Core Web Vitals の測定結果、API schema / OpenAPI 差分、MCP / REST / WP-CLI の能力集合差分を機械可読な結果として示す |
| CrawlDashboard / RobotsAiCrawlerToggle | クローラー別来訪数推移、最終クロールが古い URL、404 / 5xx URL、新規公開記事の初回捕捉時間、llms.txt / crawl-map への AI クローラーアクセス有無を示し、robots.txt と AI クローラーの許可 / 拒否を設定 JSON へ保存する。WP が応答したリクエストだけを対象とし、キャッシュ / CDN 応答は対象外と表示する |
| EvidenceLedger / EvidenceLink | 実証記録行（パターン ID・参照元 commit・証跡パス・ゲート結果）と証跡リンク。他リポの項目は持たない。secret 値・実サイト情報は表示しない |

表示 field は `docs/requirements/l3/traceability.json` の surface relation へ 1 つ以上で接続する。
