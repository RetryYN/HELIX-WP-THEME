# WP エコシステム調査 (2026-06-20)

> 本ファイルは 2026-06-20 WP エコシステム調査の AGENT-NEO 内 SSOT。
> 出典: Haiku 4体並列調査 / 公式ドキュメント・make.wordpress.org・WordPress News を参照。

## バージョン地形

- WP 6.9 (2025-12-10)
- **WP 7.0 "Armstrong" (2026-05-20 GA = 現行)**
- WP 7.1 (2026-08-19 予定)
- Gutenberg v23.4 (2026-06-17、〜11.7k★ 非常に活発)

---

## WP 7.0 = AI インフラ標準装備（4 本柱）

WP 7.0 は「インフラであってエンドユーザー AI 機能ではない」という重大なニュアンスを持つ。
機能は plugin 側が乗せる。テーマが直接 AI 機能を実装する設計は WP 公式の想定とは異なる。

### 4 AI Building Blocks

| ブロック | 概要 |
|---|---|
| **Connectors API** | OpenAI / Anthropic / Google 標準 multi-LLM |
| **Abilities API** | PHP = 6.9 / JS = 7.0 |
| **PHP AI Client SDK** | WP 組み込みの PHP クライアント |
| **MCP Adapter** | 公式。Abilities → MCP → Claude / Cursor / VSCode |

出典: developer.wordpress.org / make.wordpress.org/core WP 7.0 リリースノート

---

## Abilities API 採用実態

- **採用数: 19 plugin / 0 theme / 約 8 割 read-only**
- テーマ採用ゼロ → AGENT-NEO は先行者かつ「テーマ層採用は異例」

出典: developer.wordpress.org/news の MCP Adapter 記事 / 2026-06-20 調査

---

## theme.json v3

- WP 6.6+ で version 3 が標準（v4 予定なし）
- `appearanceTools` + fluid typography をサポート
- WP 7.0 追加: `:hover` / `:focus` / `:active` 擬似クラス + dimension preset
- 新規プロジェクトゆえ v2 からの移行不要

---

## Block Bindings API

- 6.5: post-meta バインディング
- 6.9: post-data / term-data バインディング追加
- 7.0: カスタムブロック + pattern overrides サポート
- 記事 meta / SEO 値 / 計測 ID をブロックに動的束縛できる
- 実装: `register_block_bindings_source()` + `current_user_can()` + transient キャッシュ
- AGENT-NEO 設計との整合: 「テーマ = 計測 ID 提供 / Automation SEO = データ」分担と一致

---

## Interactivity API

- 6.5: コア導入
- 7.0: `watch()` / Preact signals サポート
- React / Vue なしの軽量フロントエンドが実現可能
- AGENT-NEO 設計方針「無駄 JS 禁止」と整合
- 注: React 19 upgrade は compat でリバート中

---

## 性能

- ブロックテーマは LCP -27% / CWV 合格 44%
- 性能最適化機構:
  - `should_load_separate_core_block_assets`
  - Speculative Loading (6.8)
  - Font Library (6.5)
  - AVIF 画像対応 (6.5)
- ページタイプ別性能予算 enforce を後押し

---

## アクセシビリティ（a11y）

- **WCAG 2.2 AA が WP.org accessibility-ready 新基準**（2026-05-06 改定 / 2026-06-30 再評価期限）
- 新 5 要件:
  1. reflow / text-spacing 対応
  2. context-change 防止
  3. hover-focus 対応
  4. a11y 声明の提供
  5. 非 a11y plugin 推奨禁止
- EU EAA 施行 (2025-06) / ADA 訴訟 +23.8%
- AGENT-NEO の WCAG 2.2 AA 目標と一致

---

## AI 生成コンテンツ開示に関する法規制

AGENT-NEO + Automation SEO が AI 記事を量産するユースケースに以下の法規制が正面から適用される。

| 法規制 | 概要 | 施行状況 |
|---|---|---|
| **EU AI Act Article 50** | 機械可読マーキング（watermarking 等）義務 | 2026-08-02 施行 / 既存システム 2026-12-02 猶予 |
| **California SB 942**（AI Transparency Act） | Manifest 開示（可視ラベル）+ Latent 開示（不可視 WM）+ 無償検出ツール提供 | 2026-01-01 施行済み |
| **C2PA v2.4** | AI 生成コンテンツのコンテンツ来歴を証明する業界標準（manifest + watermark 2 層） | 業界標準（法的義務ではないが事実上の基準） |

AGENT-NEO の対応方針は ADR-025 参照。
出典: EUR-Lex EU AI Act / California Legislative Information SB 942 / C2PA.org

---

## llms.txt

- 採用率 〜10%（2026-06-20 時点）
- 主要 AI ベンダーの本番 commit なし
- Google 非対応を明言
- **低優先 / skip 妥当**
- llmo 公開面は **schema.org 構造化データ（Article/BlogPosting + Author + 日付 / content-parity 必須）を主軸**に
- AI クローラ UA: GPTBot / ClaudeBot / Google-Extended は robots 尊重、Grok / Perplexity は無視報告あり

---

## 競合テーマ / ツール

| 項目 | 内容 |
|---|---|
| AI-first テーマ（12 個調査） | 実態はほぼマーケ fiction。AI 実装 0 |
| 日本市場 | SWELL 〜33% / JIN:R niche / Cocoon・Lightning 無料（概ね classic 寄り） |
| AGENT-NEO の差別化 | 「真の AI-native + FSE + 外部脳分離」は差別化成立 |

---

## create-block-theme（公式ツール）

- 活発に開発中
- design token 層（color / typo / theme.json export）に限り流用可
- templates / patterns は独自実装を推奨
- Playground + GitHub workflow が FSE CI の標準化方向

---

## WP 7.1 注目ポイント（2026-08-19 予定）

- **AI Guidelines feature**（editorial rules）予定 → AGENT-NEO のスタイルガイド構想と重複/競合の可能性。仕様公開を監視
- **RTC（同時編集）**: WP 7.0 で除外 → 7.1 再検討（AGENT-NEO の site_id 排他設計が先行優位）

出典: make.wordpress.org/core/2026/06/19/roadmap-to-7-1/

---

## AGENT-NEO 戦略上の主要含意

1. **READ 公開 3 面（snapshot / crawl-map / llmo）は WP-native 公開の実装機構として Abilities API（`permission_callback` + JSON Schema I/O 等）を採用**する方針が将来安全（独自 REST より陳腐化しにくい）。**公開スコープ（認証要否 / allowlist / `meta.mcp.public` の値等の具体値）は GAP-RT-052 の L4 entry 確定事項に従い、本ファイルでは断定しない**（ADR-020 D-1 補足と同方針）
2. **生成 / write は WP Connectors を使わず Automation SEO に完全集約**（REQ-NF-025 追認）。WP Connectors 採用はロジック露出 / 課金経路崩壊で却下が正
3. Block Bindings API でテーマ = 計測 ID 提供 / Automation SEO = データ の分担が自然に実現
4. Interactivity API + theme.json v3 でパフォーマンス優先設計と整合
5. WCAG 2.2 AA 目標は WP.org 新基準と一致 → accessibility-ready 認定を視野に入れた開発が可能

---

*作成: 2026-06-20 / Haiku 4体並列調査に基づき AGENT-NEO リポ内 SSOT として再構成*
*出典: developer.wordpress.org / make.wordpress.org/core / WordPress News / EUR-Lex / California Legislative Information / C2PA.org*
