# THEME-INV-12 レポート — 資産再利用可否台帳（暫定版）

- 対象イシュー: `issues/THEME-INV-12-asset-reuse-ledger.md`
- 状態: **暫定（9 本の消化結果から埋められる行のみ確定）**。
  6 行が確定、5 行が暫定、3 行が未判定
- 調査日: 2026-08-26
- 台帳の正本: `RetryYN/GRAPHIX-NEO` の `docs/references/helix-wp-theme-reference.md`
  （**本レポートは判定根拠であり、正本の更新は PO 承認後に GRAPHIX-NEO 側で行う**）

判定値: `参照のみ`（読んで学ぶが持ち込まない）/ `契約付き移植`（契約確定後に移植）/
`不採用` / `未判定`

## 台帳

| # | 資産 | 所在（HELIX-WP-THEME 内） | 判定 | 根拠 |
|---|---|---|---|---|
| 1 | FSE / theme.json | `themes/agent-neo-theme/` | **契約付き移植** | 3 者で唯一の宣言的設定機構。`Config_Loader` の fail-fast 検証と対で価値がある（`12-mechanism-comparison.md` §0・§2）。ただし palette 8 / fontSizes 6 は Graphix NEO の意匠に合わせて再定義 |
| 2 | Design Tokens | `themes/agent-neo-theme/` + `styles/{light,dark}.json` | **暫定: 契約付き移植** | 意味的トークンとして宣言されている点は 2 テーマ（部品固有 151 / 155 var）に対する優位。ただし**不足カテゴリの洗い出しが未了**（INV-05） |
| 3 | Gutenberg block / pattern | `themes/agent-neo-theme/patterns/`（24） | **参照のみ** | パターンは AGENT NEO のセクション構造に依存。Graphix NEO は Context Page 構造が別物のため、**構成は新規に起こす**。パターン化という方法論だけ継承 |
| 4 | 中間 JSON と決定論 render | `plugins/agent-neo-core/inc/json/` | **契約付き移植** | 中核。INV-02 で「動的 7 種のうち 6 種は正規化で載る」と判定済み。INV-07 で 3 層分離、INV-15 で抽出器の最小実装範囲を定義済み。**契約（意図語彙）は INV-01 の完了待ち** |
| 5 | REST controller | `plugins/agent-neo-core/inc/rest/`（34） | **一部契約付き移植 / 一部不採用** | INV-08 §5 で 4 群に仕分け済み。**A 群 16 本（posts / pages / media / seo / tracking / ab-test / logs / health / status / jobs / risks / llmo-summary 等）= 契約付き移植候補**、B 群 9 本（sections / blocks / elements / design-tokens / blueprint 等）= **不採用**（Graphix NEO の構造で新規に定義）、C 群 4 本（ad-zones / ad-tags / ctas / affiliate）= **契約のみ移植 + per-site アダプタ**、D 群 4 本 = 基盤 |
| 6 | MCP / Abilities | `plugins/agent-neo-core/inc/mcp/` | **未判定** | ディレクトリの存在のみ確認。WP 7.1 の Abilities API（コア標準）との関係を精査していない。**要精読** |
| 7 | dry-run / apply / rollback | `plugins/agent-neo-core/`（`actions` / `jobs` controller） | **未判定** | `schema/action-dry-run-request.schema.json` / `action-apply-request.schema.json` の存在は確認済み。実装の精読が未了 |
| 8 | idempotency | `plugins/agent-neo-core/` | **未判定** | 同上。PoC-1/2/4 でラウンドトリップの bit-perfect 保持は実証済みだが、プラグイン側の冪等性実装は未読 |
| 9 | tracking / A/B test | `themes/agent-neo-theme/` + `inc/tracking/` | **暫定: 契約付き移植** | INV-08 §5 の A 群。テーマ非依存で意味を持つ。テーマB 側にも `themeB-ct-*`（クリック計測）があり**契約の突き合わせ相手が実在する**（未精読） |
| 10 | HP / LP patterns | `themes/agent-neo-theme/patterns/lp-*`（12） | **参照のみ** | LP 構成（hero/problem/agitation/solution/benefit/feature/proof/use-case/comparison/pricing/faq/final-cta）は**構成の型として有用**。ただし テーマB は CPT `lp` + `single-lp.php`、テーマA は `template-full-width.php` と実装が割れており、**機構は INV-03 で確定**してから |
| 11 | security / SSRF / audit log | `plugins/agent-neo-core/` | **暫定: 契約付き移植** | INV-13 で **テーマA に未認証 SSRF 形状のルートが実在**することが判明した。`wp_safe_remote_get()` / 許可リスト / `permission_callback` の規約を**製品要件として持つ根拠ができた**。実装の精読は未了 |
| 12 | WordPress 7 対応知見 | `docs/` + `poc/wp7-*` | **参照のみ** | PoC 3 件（`wp7-abilities` / `wp7-theme-audit` / `embed-isolation`）は 2026-06 実測で PASS 済み。**知見として参照**するが、Graphix NEO では WP 7.1 前提で取り直す（本番 2 サイトは 7.0.2、検証台は 7.1） |
| 13 | embed isolation | `plugins/agent-neo-embed/`（`agent-neo/embed` 1 種） | **暫定: 契約付き移植** | DSD shadowroot SSR による隔離は PoC で 10 項目 all-green。**AI 生成 HTML を安全に差し込む**という要件は Graphix NEO でも残る見込み。ただし Context Article の構造が決まるまで形は確定できない |
| 14 | test / CI / SBOM | `tests/` / `.github/` / `sbom.cdx.json` | **契約付き移植** | 資産というより規律。`docs/reviews/` の L6 記録、SBOM、E2E 固定化は**新リポでも同じ形で必要**。ただし L7（リリース zip workflow・SBOM ゲート CI）は未完了のまま残課題 |

## 確定サマリ

| 判定 | 件数 | 対象 |
|---|---|---|
| 契約付き移植 | **4**（+ 暫定 4） | 1 FSE/theme.json・4 中間 JSON・14 test/CI/SBOM・5 の A 群 / 暫定: 2 tokens・9 tracking・11 security・13 embed |
| 参照のみ | **3** | 3 patterns・10 LP patterns・12 WP7 知見 |
| 不採用 | **1**（5 の B 群 9 本） | sections / blocks / elements / design-tokens / blueprint / features / settings / migration / sections-read |
| 未判定 | **3** | 6 MCP/Abilities・7 dry-run/apply/rollback・8 idempotency |

## 「契約付き移植」としたものの後続作業

| # | 資産 | 起こすべき契約 | 依存 |
|---|---|---|---|
| 1 | FSE / theme.json | Graphix NEO の設定スキーマ（`config/*.json` の schema 定義） | Context Page 構造の確定 |
| 4 | 中間 JSON | **意図語彙の定義**（doc_type 共通語彙 + 型別語彙） | **INV-01**（ブロック語彙対応表） |
| 5 | REST（A 群 16 本） | 各コントローラの入出力契約（OpenAPI） | INV-08 の未了（テーマB 14 ルートの契約精読） |
| 14 | test / CI / SBOM | consumer 向けの CI 契約 | HELIX Lite の consumer 契約と整合 |

## 未判定 3 件の消化手順

| # | 手順 |
|---|---|
| 6 MCP / Abilities | `plugins/agent-neo-core/inc/mcp/` を精読し、WP 7.1 の Abilities API（`poc/wp7-abilities/RESULTS.md` で PASS 実測済み）との重複・棲み分けを判定する |
| 7 dry-run / apply / rollback | `schema/action-dry-run-request.schema.json` / `action-apply-request.schema.json` と `inc/rest/class-actions-controller.php` / `class-jobs-controller.php` を精読 |
| 8 idempotency | 同上 + `inc/lifecycle/` を精読。PoC-1/2/4 のラウンドトリップ実測（sha256 一致）との関係を整理 |

## 注記

- 本台帳は **HELIX-WP-THEME 側の資産**に対する判定。
  正本は GRAPHIX-NEO の `docs/references/helix-wp-theme-reference.md` にあり、
  **cross-repo のため反映は PO 承認後**（統合層 CLAUDE.md 規律 4）。
- 判定は INV-01〜11 の完了を待たずに、**現時点で根拠のある行だけ**確定させた暫定版。
  INV-01（意図語彙）と INV-03（ゾーン）の完了時に #4 / #5 の C 群 / #10 を再判定する。

## 証跡

各行の根拠は本ディレクトリの以下に記載:
`reports/INV-02` `INV-04` `INV-06` `INV-07` `INV-08` `INV-10` `INV-11` `INV-13` `INV-15` `INV-16` /
`12-mechanism-comparison.md` / `03-structure-agent-neo.md` / `04-diff-register.md`
