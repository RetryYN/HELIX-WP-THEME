# L0/L1 AI ロジック完全分離原則 反映ドラフト

## 1. L0 §1.6.1 全文

### 1.6.1 AI ロジック完全分離原則（解析回避の核心防衛）

**契約原則**: AI エージェント機能の**判断ロジックは AGENT NEO 側に一切置かない**。AGENT NEO 側に残すのは **AI フック（API ハンドラ + ブロック構造 + 計測 ID）** のみとし、variant 生成、CTA 判定、統計判定、CV 監査、認知バイアス適用判断、リードスコアリング、LLM ルーティング等の保護対象ロジックは **Automation SEO 側に全集約**する。

| AGENT NEO テーマ側（GPL 配布、解析される前提） | Automation SEO 側（クローズド、保護対象） |
|---|---|
| レンダリング層（FSE / block.json / theme.json） | **AI variant 生成** |
| canvas + slot blueprint（構造定義のみ） | **AI suggested CTA 判定** |
| 検証パイプライン（sanitize / scope / a11y / budget） | **自律 A/B 統計判定** |
| ページタイプ別性能予算 enforce | **CV 設計監査ロジック**（cta.overload / proof.too_late / hero.vague 等の判定） |
| 計測 ID 提供（section_id / cta_id / variant_id 生成） | **認知バイアスパターン適用判断** |
| REST API ハンドラ（薄い、判断ロジックは外部） | **リードスコアリング / health score** |
| 記事 CRUD / WP 標準互換 | **LLMRouter / 5D クラスタリング / ML feedback** |
| 静的ブロック実装（Review / Ranking / Hero 等の構造） | **Migration Plan B AI 再構築判断** |
| 静的フォーム（フィールド定義 / バリデーション） | **顧客行動解析 / ファネル分析判断** |
| 移行プラグイン Plan A（REST API 機械変換） | **AI 主導 CV 最適化全般**（Personalized hero / Smart internal linking / Dynamic pricing 判断） |
| H2 単位編集の **API ハンドラ**（受け取り→反映） | **H2 編集の判断ロジック**（rewrite / expand / summarize / translate の選択 + プロンプト生成） |
| 要素 swap の **実行 API**（cta_id swap 等） | **要素 swap の判断ロジック**（どの cta_id に swap するか） |
| 自律 A/B の **配信機構**（variant 切替） | **自律 A/B の variant 生成 + 統計判定 + 勝者選定** |

1. **GPL の弱点を Automation SEO で埋める**: WP テーマは GPL 配布必須でコードが配布されるが、AI 判断ロジックを Automation SEO 側に置くことでロジック保護を完成させる。
2. **第一原理 4 との完全整合**: AI 連携 OFF 時は AGENT NEO が静的テーマとして完結し、AI ロジックがないため OFF 時挙動が設計上自明になる。
3. **解析回避の最大化**: AGENT NEO 単体を解析しても「空の canvas + 静的部品 + AI フック」しか得られず、改善ロジック本体は露出しない。
4. **課金経路の防衛**: AI 改善ロジックを使うには Automation SEO 契約が必要となり、BYOK / S1 / Phase 2 Credits の経済性を防衛できる。
5. **L2 ADR-001 の前提固定**: 責務境界を L0/L1 契約レベルで明文化し、ADR 凍結時に AI 判断と実行の境界が揺れないようにする。

- AGENT NEO 側に残るのは **AI フック（API ハンドラ + ブロック構造 + 計測 ID）** のみ
- AI 連携 OFF 時は **静的テーマとして完結**し、第一原理 4 を満たす
- L2 ADR-001 で本原則を**責務境界契約として凍結**する
- REQ-F-022/023/024/032/033/034 は「機能として存在する」が、**判断ロジックは持たない**

## 2. L1 追加要件

### REQ-NF-025

| ID | 要件名 | 内容 | 優先度 |
|---|---|---|---|
| REQ-NF-025 | AI ロジック完全分離原則 | AI エージェント機能の判断ロジック（variant 生成 / AI suggested CTA 判定 / 自律 A/B 統計判定 / CV 設計監査 / 認知バイアス適用判断 / リードスコアリング / LLMRouter 等）は AGENT NEO 側に一切置かず、すべて Automation SEO 側に集約する。AGENT NEO 側は AI フック（API ハンドラ + ブロック構造 + 計測 ID 生成）のみ提供。AI 連携 OFF 時は静的テーマとして完結（第一原理 4 enforced_by）。GPL 配布の弱点を Automation SEO で埋めることで AI 判断ロジックを完全保護 | P0 |

### ACC-NF-015

| ID | 対応要件 | テスト条件 | 期待結果 | 測定方法 |
|---|---|---|---|---|
| ACC-NF-015 | REQ-NF-025 | AGENT NEO テーマソースを完全静的解析 | AI 判断ロジック（variant 生成アルゴリズム / CV 監査判定ロジック / バイアスパターン適用ルール / 統計判定ロジック等）が一切含まれず、すべて Automation SEO API 呼び出し（aseo/v1）に委譲されている | ロジック分離契約テスト |

## 3. 既存 REQ-F の責務明確化 diff

```diff
- REQ-F-022: 各 H2 セクションを auto-section_id で addressable にし（セクション = H2 + 次 H2 までの範囲）、AI が単一セクションを rewrite / expand / summarize / translate / restructure できる。`POST /agent-neo/v1/posts/<id>/sections/<section_id>/edit` でセクション単位の dryRun + diff preview + apply + rollback
+ REQ-F-022: 各 H2 セクションを auto-section_id で addressable にし（セクション = H2 + 次 H2 までの範囲）、単一セクションに対する `POST /agent-neo/v1/posts/<id>/sections/<section_id>/edit` の実行経路を提供する。AGENT NEO 側はセクション単位の dryRun + diff preview + apply + rollback を提供し、判断ロジック（rewrite / expand / summarize / translate / restructure の選択 + プロンプト生成）は Automation SEO 側、AGENT NEO 側は POST API ハンドラ + 結果の dryRun / apply / rollback 機構のみ提供する（REQ-NF-025 enforced_by）

- REQ-F-023: ... AI が計測データを見て「CTR が低い CTA を別の cta_id に差替え」等を自律実行
+ REQ-F-023: ... 安定 ID 経由で個別 swap 可能にする実行 API を提供する。swap 判断ロジック（どの cta_id / banner_id / blueprint_id に swap するか）は Automation SEO 側、AGENT NEO 側は安定 ID による swap 実行 API のみ提供する（REQ-NF-025 enforced_by）

- REQ-F-024: AI が variant 候補を生成 → 自動配信 → 計測 → 統計判定 → 勝者を default に自動昇格 / loser を archive、の自律ループを提供
+ REQ-F-024: variant の自動配信 / 計測 / 勝者反映 / loser archive / 緊急停止 CLI を含む実行基盤を提供する。variant 生成・統計判定・勝者選定は Automation SEO 側、AGENT NEO 側は variant 配信機構 + 計測連携のみ提供する（REQ-NF-025 enforced_by）

- REQ-F-032: AI suggested CTA / Personalized hero / Smart internal linking / Dynamic pricing display を提供
+ REQ-F-032: AI suggested CTA / Personalized hero / Smart internal linking / Dynamic pricing display の配信実行面を提供する。判定・計算ロジックはすべて Automation SEO 側、AGENT NEO 側は配信実行のみ提供する（REQ-NF-025 enforced_by）

- REQ-F-033: cta.overload / proof.too_late / hero.vague ... を検出しレポート出力
+ REQ-F-033: ... 監査結果を受け取り表示する機能を提供し、修正 UI とレポート出力を行う。監査判定ロジック（cta.overload / proof.too_late / hero.vague 等の判定ルール）は Automation SEO 側、AGENT NEO 側は監査結果の表示と修正 UI のみ提供する（REQ-NF-025 enforced_by）

- REQ-F-034: scarcity / authority / social proof ... を再利用ブロックパターンとして提供
+ REQ-F-034: ... 再利用可能な静的ブロックパターンとして提供する。パターン適用判断は Automation SEO 側、AGENT NEO 側はパターン定義（静的）と実体ブロックのみ提供する（REQ-NF-025 enforced_by）
```

## 4. nsrm.yaml 更新箇所

```diff
- last_updated: "2026-04-30"
+ last_updated: "2026-05-03"

- required_count: 39
+ required_count: 40

+    - REQ-NF-025  # AI ロジック完全分離原則

- total_req_nf: 26
+ total_req_nf: 27

- total_acc: 75
+ total_acc: 76

- phase_1_count: 23
+ phase_1_count: 24

- enforced_by: [REQ-NF-021, REQ-NF-022, REQ-NF-023]
+ enforced_by: [REQ-NF-021, REQ-NF-022, REQ-NF-023, REQ-NF-025]
```

## 5. §13 改訂履歴 2.0

| Version | Date | Summary | Author |
|---|---|---|---|
| 2.0 | 2026-05-03 | AI ロジック完全分離原則を §1.6.1 に追記。AI エージェント機能の判断ロジックを Automation SEO 側に完全集約する設計原則を契約化（解析回避の核心防衛） | PM (Opus) + Codex (research) |

## 6. TL レビュー観点

1. 第一原理 4 と REQ-NF-025 の拘束関係が L0/L1/NSRM で一致しているか。
2. AGENT NEO 側に残す責務が「AI フック」に限定され、判断ロジックを含んでいないか。
3. REQ-F-022/023/024/032/033/034 が「機能の存在」と「判断ロジックの所在」を明確に分離できているか。
4. SKU 境界（個人版=記事 CRUD / 法人版=全領域）と今回の責務境界が矛盾していないか。
5. Phase 配分が変更されていないか。F-022/F-023/F-024 等は従来どおり Phase 2 のままか。
6. REQ-NF-025 を Phase 1 基盤原則として置く必然性が NSRM 上で説明可能か。
7. GPL 配布前提の AGENT NEO とクローズドな Automation SEO の防衛線が十分に明文化されているか。
8. AI 連携 OFF 時の静的テーマ完結性が、要件文と原理文の両方で確認できるか。
9. `aseo/v1` への委譲を acceptance レベルで検証可能な形に落とせているか。
10. L2 ADR-001 で凍結すべき責務境界が、L0 の原則として十分具体化されているか。
11. 既存の §3.1 3 軸統合モデルと、AI ロジックを Automation SEO 側に寄せる戦略が整合しているか。
12. §6 ドッグフーディング戦略に対して、AGENT NEO 単体の価値が毀損していないか。
13. NSRM の集計値、必要性証明、Phase 集計、第一原理拘束が相互に食い違っていないか。
14. 将来の実装者が「判断」と「実行」を混同しない表現密度になっているか。
