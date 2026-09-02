# L2 Low-Fi Wireframe

```text
┌─ Site Editor: パターン / パーツ / 変種 ─────────────────┐
│ [family] header footer sidebar hero lp section article  │
│ [list] ── [preview] ── [StructureDiff]  [差し替え][取消]│
├─ Block Editor: 値 / 記事単位 ───────────────────────────┤
│ 余白 [preset ▼]  ValueZoneBadge: 安全域 / 生値 / 破壊域  │
│ 破壊域: DestructiveStop（規則・値・境界）  [保存 不可]   │
│ PerPostToggles: sidebar ☑ toc ☑ share ☑ pr ☑            │
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
- ゲート FAIL から対象ファイルと原因へ 1 手で辿れるか
- 台帳の 1 行から証跡と参照元 commit へ辿れるか
