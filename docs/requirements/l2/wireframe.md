# L2 Low-Fi Wireframe

```text
┌─ Site Editor: パターン / パーツ / 変種 ─────────────────┐
│ [family] header footer sidebar hero lp section article  │
│ [list] ── [preview] ── [StructureDiff]  [差し替え][取消]│
├─ Block Editor: 値 / 記事単位 ───────────────────────────┤
│ 余白 [preset ▼]  ValueZoneBadge: 安全域 / 生値 / 破壊域  │
│ 破壊域: DestructiveStop（規則・値・境界）  [保存 不可]   │
│ PerPostToggles: sidebar ☑ toc ☑ share ☑ pr ☑            │
├─ 管理画面: テーマ設定 ────────────────────────────────┤
│ 目次 [配置▼][種別別表示☑]  PR表記 [デザイン▼][表示制御]  │
│ slot/ゾーン割当  SP下部積層  LP種別既定  MCPパック構成    │
│ [export] [import]  正本 = 設定 JSON（schema 検証）        │
│ ProductCatalogTable: 商品一覧 [追加][更新][記事へ差し込み] │
│ tabs: A/B [variant][承認][停止][rollback]  画像 [生成][再生成]│
│ 画像: WebP / WebM [dry-run][進行][削減見込み][alt 警告]      │
│ 運用: [操作ログ][差分レビュー][rollback][鍵の発行・失効]   │
│ SNS / CV: [profile][share][feed][CV 定義][microcopy 選択]  │
│ Banner: [登録][ゾーン][期限・リンク・計測警告]             │
│ Audit: [指摘][対象へ][適用][却下][保留][JSON/CSV export]    │
│ SP: [ヘッダー][ドロワー][下部固定 3〜5 タブ][専用広告面]   │
│ SP preview: [SP 幅][代表ページ種別][MCP 取得]               │
│ Tags: [head][body 先頭][body 末尾][出し分け][data layer]    │
│ Consent: [必須][計測][広告][同意信号][遅延発火][検証]       │
│ Plugins: [検出結果][領域別既定][警告][manifest]             │
├─ 管理画面: クローラー計測（WT-UI-11）───────────────────┤
│ CrawlDashboard: bot 別推移 | 古い URL | 404 / 5xx        │
│ 初回捕捉時間 | llms.txt / crawl-map AI 来訪             │
│ RobotsAiCrawlerToggle: robots.txt / AI 許可・拒否 [保存] │
├─ REST / MCP: 制御面 ────────────────────────────────────┤
│ GET capabilities → {slots, patterns, parts, variations, │
│   template_variants, scales, hooks}                     │
│ POST select (dry-run) → diff → POST apply → rollback_id │
├─ CLI: ゲート ───────────────────────────────────────────┤
│ G-T1 PASS  G-T1b PASS  G-T2 433/438  G-T3 PASS          │
│ G-S1 PASS  G-S2 PASS   G-E1 invalid=0 (71)              │
└─ 台帳: pattern | commit | evidence | gates ──────────┘
```

prototype status: `prototyped`（WT-PROT-UI-01-r1、text low-fi）。PO reaction と agreement は未記録であり、G2 freeze ではない。

## Reaction checklist

- 差し替え前に何が変わるかが分かるか（GUI と AI 経路で同じ差分が出るか）
- 値の入力時に安全域 / 生値 / 破壊域の区別が色以外でも分かるか
- 破壊域で止まった理由と、どの値なら通るかが分かるか
- manifest だけを見てエージェントが構造・スタイル・値を選べるか
- 商品正本から商品表示・CTA クリック計測へ同じ値が流れるか、購入完了をテーマ外に保てるか
- クローラー判定外の人の閲覧が記録されず、WP 応答分だけをダッシュボードで確認できるか
- ゲート FAIL から対象ファイルと原因へ 1 手で辿れるか
- 台帳の 1 行から証跡と参照元 commit へ辿れるか
- A/B の停止が既定案への復帰となり、variant / CV ID 付き計測を確認できるか
- 画像の dry-run と非同期進行、WebP / WebM、alt・Discover 警告を確認できるか
- 差分レビューの適用 / 却下 / 保留、rollback、鍵の一度だけの表示を確認できるか
- SNS / CV / バナーの正本と、任意 microcopy・期限・計測警告を確認できるか
- HELIX 監査の適用が dry-run → 差分レビューを通り、JSON / CSV へ出せるか
- SP 幅を既定面として、44px タップ領域・16px テキスト・横スクロール 0・固定要素の被覆なしを確認できるか
- タグ slot 外の script 注入がなく、version 付きデータ層の必須 ID と同意前非発火を確認できるか
- 第三者プラグイン検出時の領域別既定・警告・manifest が管理画面と MCP で一致するか
