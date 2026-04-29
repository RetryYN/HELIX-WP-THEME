# NSRM Phase Split — Phase 1 MVP / Phase 2 / Future の境界

> 必要十分要件メソッド（NSRM）Step 6
> Status: **Draft v0.1** — Agent 5 (necessity proofs) 完了後に精度向上予定
> 入力: nsrm-01-goals-coverage.md（20 goals + coverage matrix）

## §1 切り分け原則

**Phase 1 MVP の必要十分条件:**
1. **必要**: P0 ゴールのうち、これがないと「商用テーマとして売れない」もの
2. **十分**: 個人版 ¥19,800 / 法人版 ¥98,000 を**正当化できる**最小機能集合
3. **競合差別化**: SWELL/JIN:R/AFFINGER と同等以上を、最低 5 つの差別化点で達成

**Phase 2 への送り基準:**
- 機能の深堀り（MVP の範囲を拡張するもの）
- killer feature だが MVP がなくても売れる（v1.0 ローンチに不要）
- AI モデルや外部連携の依存度が高い（ポストローンチで成熟させる方が安全）

**Future への送り基準:**
- Phase 1/2 完了後の収益モデル次第で go/no-go
- 経営判断・コスト構造判断が未確定（Q-009/Q-010 の OPEN QUESTIONS）
- 競合動向で優先度が変わる可能性

---

## §2 Phase 1 MVP 割当（v1.0 ローンチ目標）

### 必達コア（テーマとして動く最低限）

| REQ-F | 要件名 | カバーゴール | 含める理由 |
|---|---|---|---|
| REQ-F-001 | FSE テーマ基盤 | G-003 | テーマとして動かない |
| REQ-F-002 | JSON 操作 API | G-001 | AI 第一級の核 |
| REQ-F-003 | 4 操作面（うち REST + WP CLI + 管理画面）| G-002 | MCP は Phase 1.5 で可 |
| REQ-F-011 | SEO Core | G-018 | SEO 必達、商用テーマの基本 |
| REQ-F-025 | JSON 統一データモデル | G-001 | データ統一前提が崩れると全体が崩れる |

### 個人版コア（¥19,800 を正当化）

| REQ-F | 要件名 | カバーゴール | 含める理由 |
|---|---|---|---|
| REQ-F-004 | 個人版収益化ブロック | G-004 | 個人版の核心、出口クリック最大化 |
| REQ-F-016 | 個人版テンプレ固定構成 | G-012 | 個人版差別化、保守簡素化 |
| REQ-F-030 | 個人版販売寄与モジュール | G-004 | Sticky CTA 等の必須 CV モジュール |

### 法人版コア（¥98,000 を正当化）

| REQ-F | 要件名 | カバーゴール | 含める理由 |
|---|---|---|---|
| REQ-F-005 | 法人 HP/LP/BLP 三位一体 | G-005 | 法人版の核心 |
| REQ-F-012 | LP/HP/BLP ブループリント | G-005 | service-aware IA |
| REQ-F-031 | 法人版販売寄与モジュール | G-005 | LINE 友だち追加・Multi-step form |
| REQ-F-013 | 法人版リード獲得 | G-005 | 問い合わせフォーム必須 |

### 性能・品質（CI で機械検証）

| REQ-F/NF | 要件名 | カバーゴール | 含める理由 |
|---|---|---|---|
| REQ-F-029 | ページタイプ別アセット振り分け | G-009 | 隠れ killer feature、競合差別化 |
| REQ-NF-001a/b/d/e/f | 性能予算 + CWV + 画像 + JS 担保 | G-010, G-013 | CV 直結、訴求材料 |
| REQ-F-017 | 画像変換パイプライン | G-013 | WebP 自動生成は MVP に欲しい |

### 連携の基本

| REQ-F | 要件名 | カバーゴール | 含める理由 |
|---|---|---|---|
| REQ-F-006 | 計測 / A/B / CTA 基盤 | G-006（基盤のみ） | seo-tool-connector 互換 |
| REQ-F-007 | Automation SEO 連携 | G-007 | 既存 v2 連携 |
| REQ-F-026 | v2 連携最適化 API | G-007 | bulk read / sparse fieldset |
| REQ-F-027 | v2 DB スキーマ直接マッピング | G-007 | 1:1 対応 |
| REQ-F-018（基本部分のみ）| SNS 連携シェアボタン + OGP | G-014 | 自動投稿は Phase 2 |
| REQ-F-020 | SNS API 認証情報管理 | G-014 | 暗号化保存基盤 |

### 部分更新の最低限

| REQ-F | 要件名 | カバーゴール | 含める理由 |
|---|---|---|---|
| REQ-F-021 | 部分更新性（block_id ベース）| G-008（基盤）| AI 自律最適化の前提基盤、これがないと Phase 2 の全機能が成立しない |

### ガバナンス

| REQ-F | 要件名 | カバーゴール | 含める理由 |
|---|---|---|---|
| REQ-F-010 | ライセンス/パッケージ制御 | G-012 | 個人/法人の境界制御 |
| REQ-F-042 | 外部エディタアクセス制御 | G-015 | デフォルト閉鎖、品質保護 |
| REQ-F-NF-002 | セキュリティ（書き込み API）| G-017 | 必達 |
| REQ-F-NF-003 | ライセンス（GPL / 第三者監査）| G-019 | 配布前提 |
| REQ-F-NF-005 | アクセシビリティ（WCAG 2.2 AA）| G-019 | 必達 |
| REQ-F-NF-006 | i18n（ja/en）| G-019 | 必達 |

### Phase 1 MVP REQ-F 数（暫定）: **24 件**（43 件中 56%）

---

## §3 Phase 2 割当（v2.0 拡張、ローンチ後 6-12 ヶ月）

### AI 自律最適化フル機能

| REQ-F | 要件名 | 送り理由 |
|---|---|---|
| REQ-F-022 | H2 単位 LLM 編集 | 部分更新（F-021）の上位機能、MVP では block 単位で十分 |
| REQ-F-023 | 要素差し替え機構 | swap API は Phase 2 で安定化 |
| REQ-F-024 | AI 自律 A/B テスト機構 | 統計判定エンジンは Phase 2 で本格運用 |
| REQ-F-032 | AI 主導 CV 最適化 | aseo/v1 連携前提、AI 成熟後 |
| REQ-F-033 | CV 設計監査機能 | UI risk 自動検出は Phase 2 |

### AI フリーフォーム + Slot

| REQ-F | 要件名 | 送り理由 |
|---|---|---|
| REQ-F-035 | AI HTML/CSS フリーフォームブロック | killer feature、検証パイプライン成熟必要 |
| REQ-F-036 | AI HTML/CSS 検証パイプライン | 7 層検証は Phase 2 で実装・テスト |
| REQ-F-037 | Slot ベース Blueprint | フリーフォームの安全弁、F-035 と同時 |

### サンドボックス

| REQ-F | 要件名 | 送り理由 |
|---|---|---|
| REQ-F-038 | HP/LP/固定 Tier 1 サンドボックス | F-035 が出るタイミングで必要 |
| REQ-F-039 | HP/LP/固定 Tier 2 サンドボックス | Automation SEO 側のヘビー機能 |
| REQ-F-040 | Write Authority Lock | 法人版オプション、Phase 2 |
| REQ-F-041 | 記事編集経路 | MVP 後の運用最適化で確立 |

### 法人版深化

| REQ-F | 要件名 | 送り理由 |
|---|---|---|
| REQ-F-014 | 法人版顧客行動管理 | リードスコアリング・ジャーニー追跡 |
| REQ-F-015 | CRM/MA 連携アドオン | 元から P1、Phase 2 |
| REQ-F-019 | 法人版 SNS 深い統合 | LINE 公式アカウント Webhook 等 |

### SNS 自動投稿

| REQ-F | 要件名 | 送り理由 |
|---|---|---|
| REQ-F-018（自動投稿部分）| SNS 自動投稿 | API 各社の仕様変更追従が継続コスト、Phase 2 で本格対応 |

### 拡張性 / その他

| REQ-F | 要件名 | 送り理由 |
|---|---|---|
| REQ-F-028 | 拡張性保証（schema versioning + adapter）| Phase 1 ローンチ後に成熟 |
| REQ-F-034 | 認知バイアスパターンライブラリ | 元から P1、UX 配慮で慎重に |
| REQ-F-009 | 設定エクスポート/インポート | 元から P1、運用ニーズで Phase 2 |
| REQ-F-008 | 移行プラグイン Plan A（REST 機械変換）| Phase 2 で対応 |
| REQ-F-043 | Open Editor Bridge Plugin | 別売、月額サブスク。Q-010 価格決定後 |

### Phase 2 REQ-F 数（暫定）: **19 件**（43 件中 44%）

---

## §4 Future（経営判断未確定）

| REQ-F / 機能 | OPEN QUESTION | 状態 |
|---|---|---|
| AGENT NEO Credits（内蔵 SDK + クレジット）| Q-009 | コスト構造判断未決 |
| Open Editor Bridge 月額価格・対応エディタ拡張 | Q-010 | Phase 2 開始前に PO 決定 |
| 移行プラグイン Plan B（AI フル再構築）| 派生 | Phase 2 後半 / Phase 3 候補 |
| 個人 → 法人アップグレード差額課金 | Q-001 | L2 凍結時 PO 決定 |

---

## §5 Phase 1 MVP の検証

### 必要性検証（Phase 1 が本当に必要か）

各 Phase 1 REQ-F に対し:
- 削除すると企画書のゴール（G-001 to G-019 の P0 17 件）の達成が崩れるか?
- 答え yes → Phase 1 必須
- 答え no → Phase 2 へ送る

**Agent 5（necessity proofs）の出力を待ってこの精度を上げる。**

### 十分性検証（Phase 1 だけで売れるか）

**売れる根拠:**
- 個人版 ¥19,800: REQ-F-004 + F-016 + F-030 + F-NF-001a-f + F-029 で「**業界最速のアフィリエイト特化テーマ**」として訴求可能
- 法人版 ¥98,000: REQ-F-005 + F-012 + F-013 + F-031 + F-029 で「**国内唯一の AI 連携 LP/BLP プラットフォーム**」として訴求可能

**懸念点:**
- AI 自律最適化（Phase 2）が MVP にないと「テーマ更新で進化」の訴求は使えない
- → 解決策: MVP は **「土台 + Automation SEO 連携で AI 操作可能」** を訴求、Phase 2 で「自律進化」を追加訴求
- AI フリーフォーム（Phase 2）がないと「テーマが古くならない」訴求も使えない
- → MVP は固定パーツ + 法人 LP テンプレで売る（SWELL/JIN:R 同等水準で先行）

### Phase 1 MVP の最小性検証

削れるか検討:
- REQ-F-026/027（v2 連携最適化）→ 個人版だけなら削れる、法人版なら必要
- REQ-F-018（SNS シェアボタン）→ 削れない（基準レベル）
- REQ-F-021（部分更新）→ 削れない（Phase 2 の前提基盤）
- REQ-F-029（ページタイプ別アセット）→ 削れない（隠れ killer feature）

→ 24 件はおおむね最小集合と見られる。

---

## §6 ロードマップ（暫定タイムライン）

```
[Phase 1 - MVP] (3-6 ヶ月)
  └─ 24 REQ-F 実装
  └─ 個人版 + 法人版 同時ローンチ（Q-002 候補）or 個人版先行
  └─ 移行プラグイン Plan A も含めるか PO 判断（現在は Phase 2 送り）

[Phase 1.5 - 安定化] (1-2 ヶ月)
  └─ MCP サーバー（REST + WP CLI 先行のため）
  └─ wp.org 公式申請（移行プラグイン）
  └─ ライセンス検証プロバイダ確定（Q-005）

[Phase 2 - 拡張] (6-12 ヶ月)
  └─ 19 REQ-F 実装
  └─ AI 自律最適化フル機能
  └─ AI フリーフォーム HTML/CSS + Slot
  └─ サンドボックス 2 ティア
  └─ Open Editor Bridge Plugin リリース

[Future - 経営判断]
  └─ Q-009: AGENT NEO Credits
  └─ Q-010: Bridge 拡張対応エディタ
  └─ Migration Plan B (AI 再構築) の本格化
```

---

## §7 残作業

- ⏳ Agent 5（necessity proofs）完了 → 削除候補 / 統合候補を踏まえて Phase 1 MVP を精緻化
- ⏳ Agent 4（Edge cases）完了 → 各 REQ-F のリスクを評価し Phase 配分の安定性確認
- ⏳ Agent 2（Data Model）完了 → MVP の最小データモデルを確定
- ⏳ Agent 3（Grounding + Competition）完了 → 標準のみ REQ-F の必要性再検証
- ⏳ nsrm.yaml の `phase_assignments` を本ドラフトから機械可読形式で記入
- ⏳ G1.5 ゲート機械検証通過

---

**作成**: 2026-04-30 / PM Opus
**Status**: Draft v0.1（Agent 結果待ち、後続バージョンで精度向上）
