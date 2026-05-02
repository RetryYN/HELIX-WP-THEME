# L1 凍結 TL 最終判定 v3

> 判定日: 2026-05-03
> 判定者: TL（Codex）
> 上流: v1 (条件付き) / v2 (条件付き、G1.5 gate 不整合) / v3 (修正反映後)

## 総合判定

⚠️ 条件付き

v2 の主要ブロッカーだった `G1.5 static gate` と `nsrm.yaml` の不整合は解消され、ローカル Python による等価再実行でも static 10/10 PASS を確認した。`ACC-NF-015` の機械検証粒度明示、`Q-011` の L1 追記、`negation_boundaries` の list 化も前進として評価できる。

一方で、`.helix/nsrm.yaml` では `sufficiency_chains` が独立トップレベルキーになっておらず、コメント崩れにより `necessity_proofs` 配下へ混入している。形式 gate は通るが、NSRM 正本としての機械可読性と「§4 sufficiency_chains を 5 件保持している」という説明が YAML 構造上は成立していない。よって **G1 通過に技術反対はないが、L1 凍結の最終承認はこの 1 点の修正を条件とする**。

## 観点 1〜10 の判定

### 1. P1-01 修正完全性
判定: `pass`

根拠: v2 の blocker は `gate-checks.yaml` の期待値と `nsrm.yaml` 実値の不整合だった。今回の修正は consumer 側である `gate-checks.yaml` を変えず、producer 側である `nsrm.yaml` の `goals_without_sufficiency_chain: 0` と `negation_boundaries` list 化で実態整合を取っており、最小変更として妥当。実際に static 条件相当の再実行で 10/10 PASS を確認した。

指摘事項: なし

次アクション: なし

### 2. nsrm.yaml の構造変更整合性
判定: `conditional`

根拠: `negation_boundaries` 自体は list になっており、`negation_boundaries_meta.count=22` と実長 22 件も一致している。`gate-checks.yaml` の `len(negation_boundaries)>=10` 条件とも整合する。さらにリポジトリ内参照は `gate-checks.yaml` と `nsrm-08-integrated-summary.md` が中心で、dict 前提の参照は確認できなかった。

ただし、同一ファイル内で `sufficiency_chains` がトップレベルキーとして存在せず、`necessity_proofs` 配下へ混入しているため、「他キーへの影響なし」とまでは言えない。`negation_boundaries` 変更自体は正しいが、YAML 正本全体の構造整合は未完了である。

指摘事項: `P1-01` `.helix/nsrm.yaml` の `sufficiency_chains` がトップレベル構造を失っている。

次アクション: `sufficiency_chains:` を独立キーとして復元し、`necessity_proofs` との境界を明確化する。

### 3. coverage_matrix が sufficiency proof として機能する論理
判定: `conditional`

根拠: `docs/requirements/nsrm-01-goals-coverage.md` では全 20 ゴールについて孤児 0 件が確認され、`coverage_matrix` は少なくとも「全ゴールが 1 つ以上の REQ-F に直接カバーされる」事実を示している。複雑度の高い 5 ゴールについては `nsrm.yaml` に明示チェーンが記述されており、重要ゴールの追加証明という位置づけ自体は妥当である。

ただし、YAML 上は `sufficiency_chains` が独立キーになっていないため、「5 件の詳細チェーン + 残り 15 件は coverage_matrix から導出」という説明が機械可読な形で表現されていない。論理は防御可能だが、現状は **説明としては通るが、構造化証跡としては弱い**。

指摘事項: `P1-01` sufficiency proof の説明と YAML 構造が一致していない。

次アクション: `coverage_matrix` と `sufficiency_chains` の責務分担を YAML 上でも明示し、5 件詳細チェーンの保持場所を正す。

### 4. P2-01 修正完全性
判定: `pass`

根拠: `ACC-NF-015` は `AST + grep + 許可 API 層リスト照合` まで具体化され、v2 時点の「ロジック分離契約テスト」という抽象名だけの状態は脱した。L1 受入条件としては、禁止ロジックの類型、禁止キーワードの例、許可される通信層の境界が記述されており、L2/L3 で CI スイートへ落とせる粒度に達している。

指摘事項: なし

次アクション: L2 で禁止キーワード辞書と許可 API リストの正本を ADR-001 付属ルールとして固定する。

### 5. P2-03 修正完全性
判定: `pass`

根拠: `L1-requirements.md` §8 に `Q-011` が追加され、`Q-001〜Q-013` の全 ID が存在することを機械確認した。L0 §6.7 / §10 側にも `Q-011` は保持されており、少なくとも handoff 上の欠落は解消している。

指摘事項: なし

次アクション: なし

### 6. P2-02（マージ後 TODO 扱い）の妥当性
判定: `conditional`

根拠: `REQ-NF-025` と第一原理 4 により「AI 連携 OFF でも静的テーマとして完結」は L1 契約として既に固定されている。そのため、`REQ-F-030` の Smart recommendation / AI suggested CTA の代替経路は、L2 で「何を静的 fallback とするか」を設計具体化する論点として扱える。

ただし、現時点の `REQ-F-030` / `ACC-030` 文面だけでは AI OFF 時の表示劣化モードが明記されていない。L1 凍結を直ちに妨げるほどではないが、**ADR-001 入力としては未完成** である。

指摘事項: `P2-02` AI OFF 時の代替挙動は L2 冒頭で明文化が必要。

次アクション: ADR-001 で `REQ-F-030` の fallback 契約を定義する。

### 7. 全変更累積による新規矛盾チェック
判定: `conditional`

根拠: `phase_1_mvp=24`、`phase_2=20`、`REQ-F` 全 43 件の割当、`Q-001〜Q-013` の連番、`negation_boundaries` 22 件はすべて整合していた。一方で、新規矛盾として `.helix/nsrm.yaml` の `sufficiency_chains` トップレベル欠落を確認した。これは negation 変更の副作用ではないが、v3 時点の累積構造不整合である。

指摘事項: `P1-01` NSRM 正本の YAML 構造不整合が 1 件残存。

次アクション: `sufficiency_chains` の階層を修正し、YAML parse 結果で独立キー化を再確認する。

### 8. G1.5 static 10/10 PASS の信頼性
判定: `pass`

根拠: ローカル Python で `gate-checks.yaml` の static 10 条件と等価な検査を再実行し、`goals_len=20`、`orphan_req_count=0`、`orphan_goal_count=0`、`reqs_without_necessity_proof=0`、`goals_without_sufficiency_chain=0`、`unassigned_phase_count=0`、`negation_boundaries_len=22`、`phase_1_mvp_len=24`、必要ドキュメント存在を確認した。少なくとも **v2 の不一致は再現しない**。

指摘事項: なし

次アクション: `sufficiency_chains` 修正後に同等 sanity check を 1 回再実施する。

### 9. L1 凍結品質の最終判定 v3
判定: `conditional`

根拠: 形式 gate は通過済みで、v2 の blocker は解消した。`ACC-NF-015` と `Q-011` も前進しており、G1 通過の方向性自体に技術反対はない。

ただし、NSRM 正本の YAML 構造が 1 点だけ崩れているため、**「G1 に反対」ではないが「無条件承認」までは上げない**。このため判定は `conditional` とする。

指摘事項: `P1-01`

次アクション: `.helix/nsrm.yaml` のキー構造を正してから最終承認へ移行する。

### 10. L2 着手準備（再確認）
判定: `pass`

根拠: `ADR-009 → ADR-002 → ADR-001` の順序は依然として最も自然である。JSON/versioning/命名規則を先に凍結し、その上で v2 連携契約、最後に AI 判断と実行の責務境界を閉じる流れは変わらない。`REQ-NF-025` は ADR-001 の前提条件として扱うべきであり、この優先度判断も妥当である。

指摘事項: なし

次アクション: L2 開始時に上記 ADR 順序を明示固定し、`REQ-NF-025` を ADR-001 の前提制約として昇格する。

## ブロッカー
件数: 1

`P1-01` `.helix/nsrm.yaml` で `sufficiency_chains` がトップレベルキーとして成立しておらず、`necessity_proofs` 配下へ混入している。形式 gate の static 10/10 PASS は確認できるが、NSRM 正本の機械可読性と「§4 sufficiency_chains 詳細 5 件保持」の説明が YAML 構造上は一致していない。

## 軽微指摘
件数: 1

`P2-02` `REQ-F-030` / `ACC-030` の AI OFF 時 fallback は L2 設計で具体化が必要。L1 凍結阻害ではないが、ADR-001 の初期入力としては未完成。

## マージ後 TODO（v3 段階での累積残）

- `REQ-F-030` の Smart recommendation / AI suggested CTA の AI OFF fallback を ADR-001 で定義する。
- `Q-011` の KPI 数値目標を PO が確定し、L0 §6.7 / §10 と L2 計測設計へ接続する。
- `Q-013` の公開指標ポリシー未確定値を PO/法務で固定し、G2 前提条件へ反映する。

## L2 着手前 必須事項

- `.helix/nsrm.yaml` の `sufficiency_chains` を独立トップレベルへ修正し、YAML parse 結果で再確認する。
- 上記修正後、G1.5 static 相当の sanity check を再実施し、10/10 PASS を再確認する。
- `ADR-009 → ADR-002 → ADR-001` の順序を L2 開始時に固定する。
- `REQ-NF-025` を ADR-001 の前提制約として明示昇格する。

## TL 推奨次アクション

1. `.helix/nsrm.yaml` の `necessity_proofs` と `sufficiency_chains` の階層を正し、コメント崩れを除去する。
2. 修正後に `nsrm.yaml` のトップレベルキー一覧と G1.5 static 10 条件を再確認する。
3. ADR-001 で `REQ-F-030` の AI OFF fallback 契約を定義する。
4. L2 キックオフ時に `ADR-009 → ADR-002 → ADR-001` と `REQ-NF-025` 前提昇格を合意事項として固定する。

## 総合所見

v3 は v2 より明確に前進している。少なくとも、前回の blocker だった G1.5 gate と `nsrm.yaml` の数値・型不整合は解消され、`ACC-NF-015` と `Q-011` の不足も補われた。この意味で、**L1 内容そのものに対する技術反対はない**。

最終承認を保留した理由は内容不足ではなく、NSRM 正本の **YAML 構造の 1 点だけが文書意図と一致していない** ためである。ここを直せば、G1 通過に対して TL として反対理由は残らない。
