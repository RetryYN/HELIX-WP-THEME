# タスク: AI ロジック完全分離原則の L0/L1 反映（γ 実装）

> ロール: research（思考量大、新規 REQ 追加 + 既存 REQ 責務明確化 + NSRM 再検証）
> 出力先:
> - 修正反映: docs/planning/L0-planning.md / docs/requirements/L1-requirements.md / .helix/nsrm.yaml
> - 検証用ドラフト: docs/planning/drafts/L0-L1-ai-logic-separation-draft.md（新規）

## PO 判断（2026-05-03 確定）

「とくに AI エージェント側の機能だけを引っ張りたい」 = **AI 関連の判断ロジックを完全に Automation SEO 側に集約、AGENT NEO 側は AI フック付き静的テーマとして完結する**

## 設計原則

### 分離テーブル

| AGENT NEO テーマ側（GPL 配布、解析される前提）| Automation SEO 側（クローズド、保護対象）|
|---|---|
| レンダリング層（FSE / block.json / theme.json） | **AI variant 生成** |
| canvas + slot blueprint（構造定義のみ）| **AI suggested CTA 判定** |
| 検証パイプライン（sanitize / scope / a11y / budget）| **自律 A/B 統計判定** |
| ページタイプ別性能予算 enforce | **CV 設計監査ロジック**（cta.overload / proof.too_late / hero.vague 等の判定）|
| 計測 ID 提供（section_id / cta_id / variant_id 生成）| **認知バイアスパターン適用判断** |
| REST API ハンドラ（薄い、判断ロジックは外部）| **リードスコアリング / health score** |
| 記事 CRUD / WP 標準互換 | **LLMRouter / 5D クラスタリング / ML feedback** |
| 静的ブロック実装（Review / Ranking / Hero 等の構造）| **Migration Plan B AI 再構築判断** |
| 静的フォーム（フィールド定義 / バリデーション）| **顧客行動解析 / ファネル分析判断** |
| 移行プラグイン Plan A（REST API 機械変換）| **AI 主導 CV 最適化全般**（Personalized hero / Smart internal linking / Dynamic pricing 判断）|
| H2 単位編集の **API ハンドラ**（受け取り→反映） | **H2 編集の判断ロジック**（rewrite/expand/summarize/translate の選択 + プロンプト生成）|
| 要素 swap の **実行 API**（cta_id swap 等）| **要素 swap の判断ロジック**（どの cta_id に swap するか）|
| 自律 A/B の **配信機構**（variant 切替）| **自律 A/B の variant 生成 + 統計判定 + 勝者選定**|

### 戦略的意義

1. **GPL の弱点を Automation SEO で埋める**: WP テーマは GPL 配布必須でコードが配布される → AI 判断ロジックを Automation SEO 側に置けばロジック保護完成
2. **第一原理 4 との完全整合**: AI 連携 OFF = AI ロジックがそもそも存在しないので OFF 時の挙動が設計上自明
3. **解析回避の最大化**: AGENT NEO 単体を解析しても「カラの canvas + 静的部品 + AI フック」しか手に入らない
4. **課金経路の防衛**: AI 改善ロジックを使うには Automation SEO 月額契約が必要 → BYOK / S1 / Phase 2 Credits の経済性確保
5. **L2 ADR-001 の最強の前提条件**: 責務境界を契約レベルで明文化することで ADR 凍結時の揺れを防ぐ

## 修正項目

### 1. docs/planning/L0-planning.md §1.6 責務境界の最終形（または §1.5 直後に新セクション §1.6.1）

§1.6 に以下を追記または書き換え:

#### §1.6.x AI ロジック完全分離原則（解析回避の核心防衛）

「AI エージェント機能の判断ロジックは AGENT NEO 側に一切置かない」を契約レベルの設計原則として明文化。

含める内容:
- 分離テーブル（上記）の縮約版または全文
- 戦略的意義 5 件
- AGENT NEO 側に残るのは「AI フック（API ハンドラ + ブロック構造 + 計測 ID）」のみ
- AI 連携 OFF 時 = 静的テーマとして完結（第一原理 4 との整合）
- L2 ADR-001 でこの原則を契約化することを宣言

### 2. docs/requirements/L1-requirements.md §3 非機能要件 に新規 REQ-NF-025 追加

REQ-NF-024（外部 API TOS 監査）の直後に追加:

| REQ-NF-025 | AI ロジック完全分離原則 | AI エージェント機能の判断ロジック（variant 生成 / AI suggested CTA 判定 / 自律 A/B 統計判定 / CV 設計監査 / 認知バイアス適用判断 / リードスコアリング / LLMRouter 等）は AGENT NEO 側に一切置かず、すべて Automation SEO 側に集約する。AGENT NEO 側は AI フック（API ハンドラ + ブロック構造 + 計測 ID 生成）のみ提供。AI 連携 OFF 時は静的テーマとして完結（第一原理 4 enforced_by）。GPL 配布の弱点を Automation SEO で埋めることで AI 判断ロジックを完全保護 | P0 |

ACC-NF-015 として受入条件を §4 受入条件 に追加:

| ACC-NF-015 | REQ-NF-025 | AGENT NEO テーマソースを完全静的解析 | AI 判断ロジック（variant 生成アルゴリズム / CV 監査判定ロジック / バイアスパターン適用ルール / 統計判定ロジック等）が一切含まれず、すべて Automation SEO API 呼び出し（aseo/v1）に委譲されている | ロジック分離契約テスト |

### 3. 既存 REQ-F-022/023/024/032/033/034 の責務記述を「実行 API のみ」に明確化

各 REQ-F の本文を更新し、「判断ロジックは Automation SEO 側、AGENT NEO 側は実行 API のみ提供」を明示する。例:

- **REQ-F-022（H2 単位 LLM 編集）**: 本文末尾に「**判断ロジック（rewrite/expand/summarize/translate の選択 + プロンプト生成）は Automation SEO 側、AGENT NEO 側は POST API ハンドラ + 結果の dryRun/apply/rollback 機構のみ提供（REQ-NF-025 enforced_by）**」を追記

- **REQ-F-023（要素差し替え機構）**: 末尾に「**swap 判断ロジック（どの cta_id/banner_id/blueprint_id に swap するか）は Automation SEO 側、AGENT NEO 側は安定 ID による swap 実行 API のみ提供（REQ-NF-025 enforced_by）**」を追記

- **REQ-F-024（AI 自律 A/B テスト）**: 末尾に「**variant 生成・統計判定・勝者選定は Automation SEO 側、AGENT NEO 側は variant 配信機構 + 計測連携のみ提供（REQ-NF-025 enforced_by）**」を追記

- **REQ-F-032（AI 主導 CV 最適化）**: 末尾に「**判定・計算ロジックすべて Automation SEO 側、AGENT NEO 側は配信実行のみ（REQ-NF-025 enforced_by）**」を追記

- **REQ-F-033（CV 設計監査機能）**: 末尾に「**監査判定ロジック（cta.overload / proof.too_late / hero.vague 等の判定ルール）は Automation SEO 側、AGENT NEO 側は監査結果の表示と修正 UI のみ提供（REQ-NF-025 enforced_by）**」を追記

- **REQ-F-034（認知バイアスパターン）**: 末尾に「**パターン適用判断は Automation SEO 側、AGENT NEO 側はパターン定義（静的）と実体ブロックのみ提供（REQ-NF-025 enforced_by）**」を追記

### 4. .helix/nsrm.yaml 更新

- §3 必要性証明: REQ-NF-025 を必須要件として追加（required_count: 39 → 40）
- §4 十分性チェーン: 第一原理 4「非 AI ユーザーも単独で使える」の enforced_by に REQ-NF-025 を追加
- §6 Phase 割当: REQ-NF-025 は Phase 1（基盤原則のため）
- §7 監査メトリクス: total_req_nf を 26 → 27 に更新、phase_1_count: 23 → 24 に更新（REQ-NF-025 追加）
- §8 第一原理 P-004 の enforced_by に REQ-NF-025 を追加

### 5. docs/planning/L0-planning.md §13 改訂履歴

末尾に追加:
| 2.0 | 2026-05-03 | AI ロジック完全分離原則を §1.6.x に追記。AI エージェント機能の判断ロジックを Automation SEO 側に完全集約する設計原則を契約化（解析回避の核心防衛） | PM (Opus) + Codex (research) |

### 6. 検証用ドラフト docs/planning/drafts/L0-L1-ai-logic-separation-draft.md（新規）

TL レビュー用に以下を 1 ファイルにまとめる:
- §1.6.x（または該当箇所）の全文
- REQ-NF-025 の全文 + ACC-NF-015 の全文
- 既存 REQ-F-022/023/024/032/033/034 の責務明確化前後の diff
- nsrm.yaml の更新箇所
- §13 改訂履歴 2.0
- TL レビュー観点（10 件以上）

## 制約

- PO 原則「MVP 不採用」「自社サイト = 製品 = 販売 LP」「レビューサイクル徹底」を遵守
- 禁則語（MVP / ベータ / α / pilot / 最小限の検証 / 実験 / 仮説検証）使用禁止
- 既存の L0 §6 ドッグフーディング戦略（4 次 TL レビュー pass）/ §3.1 3 軸統合モデル（2 次 TL レビュー pass）と論理整合
- 既存の L1 SKU 境界（個人版 = 記事 CRUD / 法人版 = 全領域）と論理整合
- F-022/F-023/F-024 等の Phase 配分（Phase 2）は維持（責務明確化のみで Phase 変更なし）
- REQ-NF-025 は Phase 1 配分（基盤原則）

## 成功条件

- L0 §1.6.x AI ロジック分離原則が新設されている
- REQ-NF-025 + ACC-NF-015 が L1 に追加されている
- 既存 REQ-F-022/023/024/032/033/034 の責務明確化が反映されている
- nsrm.yaml が更新されている（required_count 40 / total_req_nf 27 / phase_1_count 24 / P-004 enforced_by に NF-025 追加）
- L0 §13 改訂履歴 2.0 追加
- 検証用ドラフトが TL レビュー耐久性ある品質
- 全変更で論理整合（特に第一原理 4 / SKU 境界 / Phase 配分との矛盾なし）
- 末尾に差分サマリと TL レビュー観点を含む

## 注意

- AI ロジックの「判断」と「実行」の境界を明確に。判断は Automation SEO、実行（API ハンドラ + 結果反映）は AGENT NEO
- ヒンジになるのは「責務」の言葉。F-022 等は「機能として存在する」が「判断ロジックは持たない」
- L1 凍結前の修正なので、NSRM 再検証ゲート（G1.5）も pass する整合性を保つ
