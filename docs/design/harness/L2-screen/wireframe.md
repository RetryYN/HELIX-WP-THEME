---
layer: L2
sub_doc: wireframe
status: candidate_projection
source_authority: docs/requirements/l2/wireframe.md
source_sha256: 8a649f2080dec7f4a656af2b18c5a442022305a6b59197393762b1a0d8ddc575
pair_artifact: self
---

# HELIX L2 wireframe compatibility projection

現行 wireframe の正本は `docs/requirements/l2/wireframe.md`（WT-PROT-UI-01-r1）。
この文書は HELIX reader との互換 projection であり、PO agreement または G2 freeze を表さない。

### PM-01 / WT-UI-01 構造編集

### PM-02 / WT-UI-02 スタイル切替

### PM-03 / WT-UI-03 値の 3 域

### PM-04 / WT-UI-04 記事単位切替

### PM-05 / WT-UI-05 記事ページ

サイト設定で選んだ主たる確認面（既定 SP）と PC の両幅で、共通 + device 別差分、SP ヘッダー・ドロワー・語彙挙動・下部固定・SP 専用広告面を確認する。タグは 3 slot、version 付きデータ層契約、Consent Mode v2 7 種の写像、consent default 最初の注入順、同意状態を表示する。

### PM-06 / WT-UI-06 ホーム / LP / 一覧

共通の面・語彙・CTA と SP / PC の device 別差分を両幅で比較し、資料 DL・バナー・同意状態のイベントがデータ層契約へ接続することを確認する。

### PM-07 / WT-UI-07 ゲートレポート

### PM-08 / WT-UI-08 エージェント制御面

### PM-09 / WT-UI-09 実証記録台帳

### PM-10 / WT-UI-10 テーマ設定画面

ProductCatalogTable: 商品一覧、追加・更新、記事への差し込み。
WT-UI-10 のタブとして SpSurfacePanel / SpVocabularyMap / SpPreview（主たる確認面、共通 + device 別差分、SP / PC 両幅プレビュー）、TagSlotPanel / DataLayerContractPanel / ConsentStatePanel（3 slot、version、7 種写像、consent default 最初、同意前非発火）、PluginCapabilityMatrix / PluginConflictWarning（領域別既定、検出、警告）、AbVariantPanel（variant、承認、停止、rollback）、ImageOptimizationPanel（ブラウザ側第一経路、WebP / WebM、dry-run、進行、削減見込み、alt 警告）、OperationsLog / DryRunReview / RollbackPanel / KeyManagement（ログ、差分、復旧、鍵の発行・失効）、CvDefinitionPanel / MicrocopyPicker、BannerCatalog / BannerHealth、AuditReviewPanel / AuditExport を置く。microcopy 未選択はエラーにしない。画面数は 11 のまま。

### PM-11 / WT-UI-11 クローラーダッシュボード

CrawlDashboard: bot 別推移、古い URL、404 / 5xx、初回捕捉時間、llms.txt / crawl-map の AI 来訪、llms.txt の既定出力の効果実証用アクセス時系列。RobotsAiCrawlerToggle: robots.txt と AI クローラー許可 / 拒否。
