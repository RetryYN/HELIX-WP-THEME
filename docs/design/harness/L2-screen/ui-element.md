---
layer: L2
sub_doc: ui-element
status: candidate_projection
source_authority: docs/requirements/l2/ui-element.md
source_sha256: 07e6ba1716bed8ccf56f4586003df6b40e71f6cec35d83577d8b1bd47ef960e7
pair_artifact: docs/design/harness/L2-screen/wireframe.md
---

# HELIX L2 UI-element compatibility projection

固有要素の存在を HELIX reader へ投影する。詳細契約の正本は `docs/requirements/l2/ui-element.md`。

| HELIX reader ID | WT surface | required specific element |
| --- | --- | --- |
| **PM-01** | WT-UI-01 | PatternPicker / PartSwitcher / VariantPicker / StructureDiff |
| **PM-02** | WT-UI-02 | VariationPicker / ScaleGuard |
| **PM-03** | WT-UI-03 | ValueZoneBadge / DestructiveStop / SectionOutline |
| **PM-04** | WT-UI-04 | PerPostToggles |
| **PM-05** | WT-UI-05 | ZoneSlot / VocabBlocks / SpSurfacePanel / SpVocabularyMap / SpPreview / TocAnchor / PrNotice / JsonLdEmitter / ShareProfile / ShareButtons / CvDefinitionPanel / BannerSlot / AuditBadge |
| **PM-06** | WT-UI-06 | HeroSlot / StickyStack / SpSurfacePanel / SpVocabularyMap / AnnouncementBar / CollectionJsonLd / ShareButtons / FeedEmbed / MessageCta / MicrocopyPicker / BannerSlot |
| **PM-07** | WT-UI-07 | GateReport / RawValueCounter / PerformanceBudgetReport |
| **PM-08** | WT-UI-08 | CapabilityManifest / SelectionApply / DryRunDiff / ApiContractDiff / DataLayerContractPanel / ConsentStatePanel / PluginCapabilityMatrix / PluginConflictWarning / CapabilityParityCheck / AuditExport |
| **PM-09** | WT-UI-09 | EvidenceLedger / EvidenceLink |
| **PM-10** | WT-UI-10 | ThemeSettingsForm / SpSurfacePanel / SpVocabularyMap / SpPreview / TagSlotPanel / DataLayerContractPanel / ConsentStatePanel / PluginCapabilityMatrix / PluginConflictWarning / SettingsExportImport / ProductCatalogTable / AbVariantPanel / ImageOptimizationPanel / OperationsLog / DryRunReview / RollbackPanel / KeyManagement / CvDefinitionPanel / BannerCatalog / BannerHealth / AuditReviewPanel |
| **PM-11** | WT-UI-11 | CrawlDashboard / RobotsAiCrawlerToggle |

## S3 projection note

WT-UI-10 に著者・監修者、レコメンド、フォント、読み戻し、リンク切れ、host capability、選択セット、同意・privacy・asset ledger を追加し、WT-UI-11 に 4 分類台帳と response origin を追加する。
