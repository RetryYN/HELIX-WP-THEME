# L2 UI Elements

| element | contract |
| --- | --- |
| PatternPicker | 差し替え可能なパターン一覧を家族（header / footer / hero / lp / section / sidebar / article）で示す |
| StructureDiff | 差し替え前後の骨格差分（テンプレート・パーツ参照）を示す |
| VariationPicker | スタイルバリエーション一覧と、尺度を所有しないことの検証結果（G-T1b）を示す |
| ScaleGuard | 見出し尺度が単調非増加であることを示し、崩れる切替を警告する |
| PartSwitcher | header / footer / sidebar / post-header / post-footer の案を切り替える |
| ValueZoneBadge | 入力値が安全域 / 生値 / 破壊域のどれかを label と icon で示し、色だけに依存しない |
| DestructiveStop | 破壊域の値の保存を止め、触れた規則・値・境界を示す。解除手段を持たない |
| AdZone / CtaSlot | 記事内広告・CV の置き場所。ゾーン語彙 ID と条件表示規則を持つ |
| TocAnchor | 目次の配置意図（既定は最初の h2 直前）。目次本体は一級要素にしない |
| JsonLdEmitter | 単一出力元の構造化データ。型ごとに 1 本 |
| HeroSlot / StickyCta / ConsentStack | LP / ホームの hero、追尾 CTA、SP 下部固定領域の積層 |
| GateReport | ゲート ID・FAIL / WARN・対象・原因・baseline 値 |
| RawValueCounter | 生値件数と baseline の差 |
| IntakeLedger | 取り込み行（パターン ID・参照元 commit・証跡パス・ゲート結果・取り込み先） |
| EvidenceLink | 証跡パス・HEAD・digest。secret 値・実サイト情報は表示しない |

表示 field は `docs/requirements/l3/traceability.json` の surface relation へ 1 つ以上で接続する。
