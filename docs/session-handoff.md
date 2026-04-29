# AGENT NEO — セッション引き継ぎ

> 引き継ぎ日: 2026-04-30
> 前セッション PM: Opus 4.7 (1M context)
> 状態: **L1 完成 + NSRM 検証完了 + TL レビュー実施済**

## 🎯 今すぐ次セッションがやるべきこと（優先順位）

1. **TL レビュー結果を確認**: `docs/reviews/L1-tl-review-*.md`（Codex tl ロールが生成）
2. **TL ブロッカーがあれば対応** → なければ **G1 (要件完了ゲート) PO 承認準備**
3. **G1 通過後 → L2 全体設計に着手**: 9 ADR 凍結（NSRM 統合グループ単位）
4. OPEN QUESTIONS Q-001/Q-002 (個人→法人アップグレード方式 / ローンチ順) を PO 確認

## 📋 プロジェクト概要

**AGENT NEO** は AI エージェント第一級ユーザーの商用 WordPress FSE テーマ。

| ライン | 価格 | 課金 |
|---|---:|---|
| 個人版（アフィリエイター）| ¥19,800 | 一括 |
| 法人版（HP/LP/BLP）| ¥98,000 | 一括 |
| 移行プラグイン | 無料 | — |
| S1 初回構築サービス | 別途見積 | 受託 |
| Open Editor Bridge | ¥3,000-5,000/月想定 | 月額（Phase 2 候補）|

## 🏛 製品哲学（第一原理 4 件）

1. **無駄な JavaScript を組まない**
2. **ページスピード最優先（ページタイプ別最適化）**
3. **結果（CV）を届けるテーマ**
4. **非 AI ユーザーも単独で使える**（AI-first だが AI-only ではない）← 重要、見落とさない

## 📊 L1 規模（NSRM 検証済）

```
機能要件:      43 件 (REQ-F-001 〜 REQ-F-043)
非機能要件:    26 件 (REQ-NF-001〜020 + 001a〜f + 021〜023)
受入条件:      75 件 + 異常系 5 件
用語定義:      49 件
未決事項:      10 件 (Q-001 〜 Q-010)
ゴール:        20 件 (G-001 〜 G-020)
否定境界:      22 件 (NEG-001 〜 NEG-022)
ID 種別:       18 種
API endpoint:  56 件 (agent-neo/v1)
```

## ✅ NSRM 検証結果（必要十分要件メソッド）

| 検証 | 結果 |
|---|---|
| 必要性 | 39 必須 + 4 条件付き必須 + **0 削除推奨** |
| 十分性 | 孤児ゴール 0 件 = 抜け漏れなし |
| 実根拠 | 38/43 件 (88%) に解析レポート根拠 |
| 競合差別化 | 16 killer + 13 差別化 = 29/43 (67%) |
| Agent 5 統合グループ | 9 グループ → L2 で 9 ADR 候補 |

**結論: L1 は必要十分要件集合。L2 凍結に進める品質。**

## 📂 主要ドキュメント（読む順序）

### Tier 0（必読・5 分）
1. `docs/session-handoff.md` — 本ドキュメント
2. `docs/requirements/nsrm-08-integrated-summary.md` — NSRM 統合サマリ

### Tier 1（必読・30 分）
3. `docs/planning/L0-planning.md` — 企画書
4. `docs/requirements/L1-requirements.md` — 要件定義（本体）
5. `docs/reviews/L1-tl-review-*.md` — TL レビュー結果（要確認）

### Tier 2（参照・必要時）
6. `docs/requirements/nsrm-01〜07-*.md` — NSRM 各分析
7. `docs/design/data-model-ids.md` — 18 ID 関係図
8. `docs/design/api-catalog.md` — 56 endpoint カタログ
9. `docs/reverse/wp-theme-reference-analysis.md` — Reverse 解析
10. `解析レポート/01〜40` — Codex 詳細解析（必要な箇所のみ）

### Tier 3（連携先）
11. `seo-tool-v2-docs/Automation SEO/system_design_max/` — Automation SEO v2 設計書

## 🚀 Phase 1 MVP スコープ（22 REQ-F、約 6 ヶ月想定）

### 必達コア（5 件）
F-001 (FSE) / F-002 (JSON API) / F-003 (4 操作面) / F-011 (SEO Core) / F-025 (JSON 統一)

### 個人版（3 件）
F-004 / F-016 / F-030

### 法人版（4 件）
F-005 / F-012 / F-013 / F-031

### 性能・連携（7 件）
F-029 / F-017 / F-006 / F-007 / F-026 / F-027 / F-018 (シェア基本)

### その他（3 件）
F-021 (部分更新基盤) / F-010 (パッケージ制御) / F-042 (外部エディタ閉鎖)

## 🔮 Phase 2 候補（21 件）

詳細は `nsrm-08-integrated-summary.md §4` 参照。主要グループ:
- AI 自律最適化深化（F-022/023/024/032/033）
- AI フリーフォーム（F-035/036/037）
- サンドボックス 2 ティア（F-038/039/040/041）
- 法人版深化（F-014/015/019/020）
- 認知バイアス・拡張性・移行（F-008/009/028/034）
- Open Editor Bridge（F-043）

## ❓ OPEN QUESTIONS（10 件）

| ID | 内容 | 担当 | 期限 |
|---|---|---|---|
| Q-001 | 個人 → 法人アップグレード方式 | PO | L1 凍結前 |
| Q-002 | ローンチ順（個人 / 法人 / 同時）| PO | L1 凍結前 |
| Q-003 | S1 価格レンジ・契約形態 | PO | L2 凍結前 |
| Q-004 | 移行プラグイン Plan A の有料化 | PO | L2 凍結前 |
| Q-005 | ライセンス検証プロバイダ | PO/TL | L2 凍結前 |
| Q-006 | 自社配布 vs wp.org 申請の機能ロック | PO/TL | L2 凍結前 |
| Q-007 | 移行プレビュー差分粒度 | TL | L3 開始前 |
| Q-008 | 販売チャネル | PO | L7 前 |
| **Q-009** | **AGENT NEO Credits の go/no-go** | PO + 経営判断 | Phase 2 開始前 |
| **Q-010** | **Open Editor Bridge 月額価格・対応エディタ** | PO | Phase 2 開始前 |

## 🛠 開発環境

```
Docker WP テスト環境（localhost:8086）:
  - WordPress 6.9.4 + PHP 8.3
  - SWELL 2.16.0 + JIN:R 1.4.6 マウント済（解析対象）
  - admin / admin

起動コマンド:
  cd "/c/Users/tenni/Desktop/AGENT NEO"
  docker compose up -d
  bash scripts/dev-init.sh

検証スクリプト:
  bash scripts/verify-themes.sh
```

## 🔐 連携先システム情報

### Automation SEO（既存・本番運用中）
- 公式リポジトリ: `git@github.com:RetryYN/Automation-SEO.git`
- ローカル: `C:\Users\tenni\Desktop\seo-tool-v2-docs\Automation SEO\`
- VPS デプロイ済み（X VPS / Linux）
- 本番運用 URL（aseo/v1 確認済）:
  - https://it-shukatu-college.com/wp-json/aseo/v1（JIN:R）
  - https://solobiz-lab.com/wp-json/aseo/v1（SWELL）

### 既存 WP プラグイン
- `seo-tool-connector` v1.1.0（GPL v2、Automation SEO 側成果物）
- 動的 CTA / A/B テスト / セクション計測

## 🎓 設計の重要判断（決定済）

1. **Theme Core / Companion Plugin / Migration Plugin の責務分離**
2. **agent-neo/v1 と aseo/v1 の双方向 REST 連携**
3. **JSON 統一データモデル**（独自バイナリ禁止）
4. **2 ティアサンドボックス**（HP/LP/固定ページのみ、記事は軽量経路）
5. **外部エディタはデフォルト閉鎖**（Open Editor Bridge は別売月額）
6. **ページタイプ別性能予算**（記事 < 15KB、LP < 80KB 等）
7. **AI フリーフォーム HTML/CSS + Slot 制限**（自由と安全の両立）
8. **個人/法人 二極化**（個人=記事 CRUD のみ、法人=全構造編集）
9. **AI 自律最適化機構**（部分更新・H2 編集・要素 swap・自律 A/B）
10. **第一原理 4 で非 AI ユーザビリティ担保**

## 🚧 設計上の重要警告

### Don't
- AI 連携を機能の前提条件にする（第一原理 4 違反）
- 記事ページの JS を 15KB 超にする（ページタイプ別予算違反）
- 外部エディタを Bridge Plugin なしで許可する（ガバナンス違反）
- 独自バイナリ形式を導入する（JSON 統一違反）
- WP 標準エディタ非互換のブロックを作る（非 AI ユーザビリティ違反）
- LP の重さを記事に波及させる（page_type allowlist 違反）

### Do
- 全ブロックに data-agent-section-id / cta_id を必須化
- AI 機能は OFF をデフォルト、明示的オプトインで ON
- 全 API に dryRun + apply 分離 + idempotency-key
- 全永続化を WP post_meta + jsonb で統一
- 全機能追加で「CV にどう寄与するか」を提示

## 🧪 競合との差別化（実証済）

| | SWELL | JIN:R | AFFINGER | AGENT NEO |
|---|---|---|---|---|
| AI 第一級 API | ✗ | ✗ | ✗ | **✓** |
| ページタイプ別予算 | ✗ | ✗ | ✗ | **✓** |
| AI 自律最適化 | ✗ | ✗ | ✗ | **✓** |
| AI フリーフォーム | ✗ | ✗ | ✗ | **✓** |
| HP/LP/BLP 三位一体 | △ | △ | △ | **✓** |
| 非 AI ユーザビリティ担保 | ✓ | ✓ | ✓ | **✓** |
| PHP 8.x 完全互換 | ✓ | ⚠️（残バグ）| ✓ | **✓**（CI 強制）|
| Cookie 汚染ゼロ | ✓ | ✗（PHPSESSID）| ✓ | **✓** |

実証根拠: `解析レポート/35-実機検証ログ/` と `解析レポート/36-本番運用サイト観測.md`

## 📝 セッション引き継ぎ チェックリスト

次セッション開始時に確認すべきこと:

- [ ] 本 handoff ドキュメントを読む
- [ ] `nsrm-08-integrated-summary.md` を読む
- [ ] TL レビュー結果（`docs/reviews/L1-tl-review-*.md`）を読む
- [ ] TL ブロッカーがあれば対応、なければ G1 PO 承認準備
- [ ] G1 通過後、L2 全体設計開始
- [ ] L2 で 9 ADR 凍結（NSRM Agent 5 統合グループ単位）
- [ ] OPEN QUESTIONS Q-001/Q-002 を PO 確認

## 🆘 困った時のリファレンス

```
要件で迷った時:        nsrm-05-necessity-proofs.md（必要性証明）
スコープで迷った時:    nsrm-03-negation-boundaries.md（やらない領域）
API で迷った時:        docs/design/api-catalog.md（56 endpoint）
ID で迷った時:         docs/design/data-model-ids.md（18 ID 関係）
競合と比較したい時:    nsrm-02-grounding-competition.md
エッジケースを知りたい時: nsrm-04-edge-cases.md
HELIX 状態:           .helix/nsrm.yaml + .helix/phase.yaml
```

## 🔄 HELIX 現在のフェーズ

```
Phase: L1（要件定義）
Mode: forward
Drive type: agent
Size: L
Gates:
  G0.5 (企画突合): passed_with_draft
  G1 (要件完了): pending（PO 承認待ち）
  G1.5 (NSRM 必要十分性): 機械検証準備完了

次フェーズ: G1 通過 → L2 全体設計
```

---

**作成**: 2026-04-30 / PM Opus 4.7 (1M context)
**バージョン**: handoff v1.0
