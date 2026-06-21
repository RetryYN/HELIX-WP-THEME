# WP7.0 テーマ完全性監査レポート（不足レジスター）

## 0. エグゼクティブサマリ

- 結論: WordPress 7.0 への**対応方式**自体は実機検証済みで、方式上の致命的差分は見当たらない。
- ただし、AGENT-NEO 側ではテーマ実体（`themes/` 配下の `agent-neo-theme`）が存在せず、実装は基本的に **0%**。
- 現存は `plugins/agent-neo-embed` と監査用 probe fixture のみで、`themes/` 配下に `agent-neo-theme` は存在しない。
- そのため「WP7.0 として致命的不適合は無い（方式は健全）」だが、「WP7.0 として必要なテーマ本体がほぼ未実装」という不一致が、現時点で最大かつ網羅的な不足。

## 1. 実機検証済み（WordPress 7.0 GA + 一部 6.9.4 / 2026-06-21）

### 判定: すべて PASS

| 検証項目 | 実測 | 証拠 |
|---|---|---|
| ブロックテーマ認識 | `wp_is_block_theme()=Y` / `wp theme activate` 成功（fatal なし） | probe fixture を WP7.0 に投入 |
| theme.json v3 受理 | `WP_Theme_JSON_Resolver::get_theme_data()` で `version=3` 解決 | 同上 |
| テンプレート登録 | `block_templates=3`（index/single/page） / `template_parts=2`（header/footer） | 同上 |
| ランタイム deprecation | index/single/page/search/404 描画時の `deprecated/fatal=0件`（WP_DEBUG） | `debug.log` grep 0 |
| Abilities API | `register`（category + ability）→`get`→`execute` 成功（`{"pong":true,"echo":"neo7"}`） / Abilities は WPコア 6.9.0+ 標準 | `poc/wp7-abilities/` |
| embed ブロック | `static=DSD shadowrootmode SSR` / 孤立性 PoC 10項目 all-green | `plugins/agent-neo-embed`, `poc/embed-isolation` |
| style.css ヘッダ | Theme Name / Version / Requires at least 6.6 / Tested up to 7.0 / Requires PHP 8.1 / License / License URI / Text Domain / Tags が充足 | probe |
| theme.json `$schema` | `https://schemas.wp.org/trunk/theme.json`、WP7.0 が version3 を受理 | probe |

## 2. 不足レジスター（WP7.0完全対応テーマに必要 ↔ 実装状況）

### (A) 必須ファイル・構造
- **要件**: `agent-neo-theme` 本体の必要構成（`style.css` / `theme.json` / `templates/index.html` / `parts/*` / `patterns/*`）
- **WP7.0での要否**: 必須
- **AGENT-NEO設計での扱い**: 設計上テーマ本体を想定（ADR/L2に準拠すべき）
- **実装状況**: **未実装（致命）**
- **重大度**: 致命
- **欠落明細**: 本番テンプレ群（home/singular/archive/search/404）、style variations、block-styles、`screenshot.png`（1200x900）、`languages/*.pot` 未配置

### (B) theme.json v3 の実体
- **要件**: schema v3 + `$schema` 正 URL、settings（appearanceTools/layout/color/typography（fontFamilies/fontSizes/fluid）/spacing/border/shadow/dimensions）、styles（root/elements/blocks/疑似クラス `:hover`/`:focus`/`sectionStyles`）、templateParts、customTemplates、patterns
- **WP7.0での要否**: 必須（完全実装化時に必要）
- **AGENT-NEO設計での扱い**: ADR-010/L5 でトークン・IA 方針はあるが、theme.json の実体未作成
- **実装状況**: **未実装**
- **重大度**: 高

### (C) WP7.0固有機能の採否（設計GAP）
- 対象: Block Bindings / Interactivity API / Font Library（fontFace） / Section Styles / Speculative Loading（where 除外） / `should_load_separate_core_block_assets`
- **WP7.0での要否**: 採否要件として重要（性能・編集体験・保守性に影響）
- **AGENT-NEO設計での扱い**: ADR-023 の A4 で方針言及あり。ただし L2/L3 で採用/非採用未凍結
- **実装状況**: 反映なし（実装判断なし）
- **重大度**: 中〜高（設計未確定）

### (D) アクセシビリティ WCAG 2.2 AA / accessibility-ready
- **WCAG 2.2 AA フル準拠・accessibility-ready 認定・2026-06-30 期限は GAP-RT-057（RESOLVED-BY-DECISION / 2026-06-21）で対象外として撤去済み**。市場=日本・ADR-024 で Automation SEO 専用配布のため wp.org accessibility-ready 基準は非適用。EU EAA / ADA は日本向けサイトに非適用。
- **実装状況**: a11y は SEO/UX と重複する基本配慮を**通常品質**として L4 で実装済み（実機 axe: critical=0 / serious=0 / color-contrast 違反=0、skip link・focus-visible・aria・キーボード操作 実装済、残 moderate=landmark-unique 1件のみ）。
- **重大度**: 低（通常品質）。期限ゲートなし。

### (E) 品質・配布
- **要件**: Theme Check 主要観点（escaping/prefix/text domain/license/deprecated 無し） / i18n (.pot) / readme / SBOM（ADR-014 / T-025）
- **WP7.0での要否**: 品質担保に必須
- **AGENT-NEO設計での扱い**: ADR-024 でテーマ配布は Automation SEO 専用（wp.org 非提出）を明記。wp.org 提出要件は移行プラグインに適用
- **実装状況**: probe は概ね満たすが、テーマ本体未実装のため実効性なし
- **重大度**: 中
- **補足**: wp.org テーマディレクトリ提要件をテーマに課す記述は不整合（この監査では該当しない）

### (F) ブロック / embed
- **要件**: embed 以外の主要カスタムブロック（Review / Ranking / Hero / LP 系）実装、`block.json` の `apiVersion: 3`、`textDomain`、`supports`
- **WP7.0での要否**: `poc` の域外運用時は必須
- **AGENT-NEO設計での扱い**: F-004/F-005 を前提化（未実装）
- **実装状況**: embed は実装・検証済み、その他は未実装
- **重大度**: 中

## 3. Haiku チェックリスト（`CHECKLIST-RAW.md`）是正事項

- **是正1**: `$schema` を `https://schemas.wp.org/wp/7.0/theme.json` としていた記載は誤り。**正は `https://schemas.wp.org/trunk/theme.json`**。
- **是正2**: 「Abilities API 非採用」は誤り。ADR-020 で READ 公開（Abilities + MCP）はテーマ側の役割として採用。非採用は WP Connectors のみ。
- **是正3**: `style.css` 例の `Requires PHP: 7.4` は方針違反。**正: Requires PHP 8.1+**（D0 / ADR-020）。
- **是正4**: 「wp.org 提出要件」をテーマ側に課していた記載は不整合。ADR-024 によりテーマは Automation SEO 専用配布で、wp.org 提出要件は移行プラグインのみ対象。

## 4. 推奨アクション（優先順）

- **P0**: `agent-neo-theme` 本体の L2/L3 を WP7.0 機能採否込みで凍結し、L4 で scaffold（probe を出発点に昇格）
- （参考）a11y は GAP-RT-057 で通常品質化済み・accessibility-ready 訴求と 6-30 期限は撤去。新規アクションなし（RESOLVED-BY-DECISION / 2026-06-21）
- **P1**: WP7.0 機能採否（Block Bindings / Interactivity / Font Library / Section Styles）を ADR 化し凍結
- **P1**: 本番テンプレ群・patterns・style variations・`screenshot.png`・`i18n` の作成計画を WBS へ追加

---

## 変更履歴

| 日付 | 内容 |
|------|------|
| 2026-06-21 | WCAG 2.2 AA 6-30 幻記述を GAP-RT-057 整合へ廃止（§(D) 是正・§4 P0 撤去・通常品質化） |
