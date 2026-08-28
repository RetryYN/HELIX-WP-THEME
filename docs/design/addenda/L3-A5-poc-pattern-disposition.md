# L3 設計 Addendum A5 — PoC 成果の棚卸し（使える / 使えない）

> **Addendum ID**: L3-A5
> **作成日**: 2026-08-28
> **入力**: PoC 証跡 PR #38（スタイル 3 案）、#39（共有パーツ 22 パターン）、#36（一貫性ゲート・Figma 経路）
> **参照 L1**: REQ-F-045 / REQ-F-046 / REQ-F-037 / REQ-F-016 / REQ-F-025
> **ステータス**: Draft。PR #38/#39/#36 は**証跡であり実装ではない**（merge 対象にしない）。採用分は L4/L5 設計に書き直してから L7 実装へ。
> **前提**: `docs/planning/drafts/L0-ai-editing-freedom-draft.md` §2 の G0.5 判断（REQ-F-016/037）が先。判断前は「要 PO」行を確定しない。

## 0. 判定基準

| 判定 | 意味 |
|---|---|
| 採用 | 既存 L1 要求に紐づき、L5 設計へ転記して実装対象にできる |
| 要 PO | L0/L1 の判断（REQ-F-016/037）に依存する |
| 保留 | 要求が無い。要求 ID 新設が要る |
| 不採用 | 証跡として残すのみ（知見は §4 に記録） |

## 1. スタイルバリエーション（PR #38）

| 成果 | 対応 L1 | L5 §0.3 プリセット対応 | 判定 | 根拠 |
|---|---|---|---|---|
| `styles/editorial.json` | REQ-F-045 | `affiliate-editorial` | 採用 | palette slug 参照のみ・G-T1b PASS。§0.3 の視覚方針（長文可読・控えめ CTA）に一致 |
| `styles/depth.json` | REQ-F-045 | `startup-bold` に近い | 採用（条件付き） | shadow プリセット 0–4 を親 theme.json に要追加（設計に書く）。`!important` 上書きはカード側の inline box を消す設計へ置換 |
| `styles/business.json` | REQ-F-045 | `corporate-trust` | 採用 | プリセット余白へ置換済み |
| theme.json shadow presets / defaultPresets false | REQ-F-045 / REQ-NF-008 | §1.2 デザイントークン | 採用 | トークン層の追加。L5 §1.2 に shadow 尺度を追記 |

§0.3 の残り 3 プリセット（affiliate-clear / corporate-product / local-business）は未着手。要求は既にある。

## 2. 共有パーツ・レイアウト（PR #39）

### 2.1 テンプレートパーツ（header / footer / sidebar）

| 成果 | 対応 L1 | 判定 | 根拠 |
|---|---|---|---|
| header 7 案（centered/topbar/minimal/image/search/split + 既定） | REQ-F-046 の拡張（現行は post-header/footer のみ） | 保留 | 「共有パーツの複数案切替」の要求 ID が無い。L1 追記後に L5 §2.3 へ |
| footer 5 案 | 同上 | 保留 | 同上 |
| sidebar 5 案 + `parts/sidebar.html` | REQ-F-046 拡張 / REQ-F-016 | 要 PO | 個人版はテンプレ固定（REQ-F-016）。切替を人に開放するかは G0.5 |
| `sidebar-sticky`（追尾 + 目次） | REQ-F-030（個人版 Sticky CTA）| 採用候補 | 目次は REQ-F-022（H2 単位）と整合させて設計に書く |
| `footer-cta-band` / `sidebar-cta` | REQ-F-030 / REQ-F-031 | 採用候補 | cta_id 必須（REQ-F-048 計装）を設計に含めること。現状は無い＝そのままでは不採用 |
| `header-search` | — | 不採用 | 要求なし。検索 UI は L2 画面設計に無い |

### 2.2 セクション・レイアウト

| 成果 | 対応 L1 | 判定 | 根拠 |
|---|---|---|---|
| `hero-gradient` / `hero-cover` / gradients 5 | REQ-F-012（LP Blueprint）/ REQ-F-045 | 採用候補 | Hero slot の候補として L5 §2.3 に。`cta_id` required（REQ-F-037 / ACC-037）を満たす形に修正が要る |
| `section-cover-band` | REQ-F-012 | 採用候補 | 同上 |
| `layout-bento` / `layout-split-asym` / `layout-4col-features` | REQ-F-012 / L5 §2.2 グリッド | 採用候補 | §2.3 レイアウトパターン（現在空欄）の初期内容にする |
| `placeholder-cover.jpg` | REQ-F-017（画像は WebP） | 不採用 | JPEG 単体は画像ポリシー違反。WebP + fallback の設計に従い差し替え |

### 2.3 実装知見（証跡として残す。設計制約に昇格させる）

- WP 7.1 では flex/grid group の `blockGap` は inline `gap:` を出さない。border は per-side longhand + `u002d` 逃がし。→ L5 のパターン記述規約に「エディタ canonical 出力を正とする」を追加（Issue #40）。
- 静的検査で取れない Block validation は PoC 実機で検出する（Issue #42 G-E1）。
- パターン file list は transient に載る。追加時は `wp_theme_files_patterns-*` を削除。

## 3. ゲート・取り込み経路（PR #36）

| 成果 | 対応 | 判定 | 根拠 |
|---|---|---|---|
| `bin/check-design-consistency.sh`（G-T1/T1b/T2/T3/S1/S2） | REQ-NF-008 / L5 §1.2 | 採用候補 | 設計に「4 層一貫性」の規約が無い。L4 `ui-standard` に規約を書き、その検査としてゲートを位置づける |
| baseline 438（生値）| — | 要 PO | 「増やさない」方式は L0 改定案の値方針に依存 |
| Figma tokens → theme.json / structure → patterns | REQ-F-009（設定 I/O）に近い | 不採用（当面） | 無料枠 REST 制限で運用に乗らない。知見のみ `docs/design/figma-intake.md` |

## 4. まとめ

- 採用: スタイル 3 案 + shadow トークン（REQ-F-045 に直結）。
- 採用候補（設計後）: Hero/セクション/レイアウト 6 種、sticky sidebar、CTA 系パーツ、ゲート。いずれも cta_id・WebP・canonical 出力の設計条件を満たしてから。
- 要 PO: header/footer/sidebar の複数案切替（REQ-F-016/037、G0.5）、baseline 方式。
- 不採用: header-search、JPEG placeholder、Figma 経路。

次: G0.5 判断 → L1 追記 → L5 §0.3/§1.2/§2.3 に設計転記 → 実装 PR を設計から起こし直す（#38/#39 は close、証跡として参照）。
