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
| VocabBlocks | 記事内語彙 14 種の受け皿（core + block style / 新規ブロック 3） |
| TocAnchor | 目次の配置意図（埋め込み / フロート追従 / 開閉ボタン、既定は最初の h2 直前）とページ種別ごとの表示条件・block style。目次本体は機械導出で一級要素にしない |
| PrNotice | PR 表記。広告パーツ / アフィリエイトリンクの有無から機械判定し該当ページだけに控えめに自動出力。選べるのは表示デザインと表示ページ制御。編集者が消せない |
| JsonLdEmitter / CollectionJsonLd | 単一出力元の構造化データ。型ごとに 1 本。一覧は CollectionPage |
| HeroSlot / StickyStack / AnnouncementBar | LP / ホームの hero、SP 下部固定の積層（同意バー > メニュー > シェア）、お知らせバー |
| GateReport / RawValueCounter | ゲート ID・FAIL / WARN・対象・原因・baseline 値、生値件数と baseline の差 |
| CapabilityManifest / McpPack / DryRunDiff | 面・部品・値・変種・hook の一覧、設定で束ねた MCP 常用パック（1 呼び出し = 1 作業単位、dry-run / apply / rollback 内包）、dry-run の差分。REST / CLI は同じ manifest を読む従属経路 |
| ThemeSettingsForm / SettingsExportImport | サイト全体の既定（目次の配置・表示条件、PR 表記のデザイン・表示制御、slot / ゾーン割当、SP 下部積層、LP 種別既定、MCP パック構成）を schema 付き設定 JSON 1 本として編集・export / import する。同じ JSON を manifest に載せる |
| EvidenceLedger / EvidenceLink | 実証記録行（パターン ID・参照元 commit・証跡パス・ゲート結果）と証跡リンク。他リポの項目は持たない。secret 値・実サイト情報は表示しない |

表示 field は `docs/requirements/l3/traceability.json` の surface relation へ 1 つ以上で接続する。
