# L1 F-018 / ACC-018 同期 TL レビュー

> レビュー日: 2026-05-01
> 対象: L0 §6 ドッグフーディング戦略統合に伴う L1 F-018 / ACC-018 系の修正
> 修正範囲: docs/requirements/L1-requirements.md の REQ-F-018 / ACC-018 / ACC-018b 新規

## 総合判定
[⚠️ 要再修正]

- 判定: `conditional`
- 根拠: `REQ-F-018` 本文、`ACC-018`、`ACC-018b` の Phase 1/2 分離自体は `nsrm.yaml` と L0 §6 方針に整合している。`REQ-F-018` は Phase 1 をシェア導線 + OGP/X Card + 埋め込み等、Phase 2 を自動投稿へ分離できており、`REQ-F-019`/`REQ-F-020` も Phase 2 のまま衝突しない（`docs/requirements/L1-requirements.md:59-61,181-185`, `.helix/nsrm.yaml:145-197`）。
- 根拠: 一方で、同一 L1 文書内の用語集と下部トレーサビリティ表が旧い意味の `F-018` を保持しており、「L1 全体として完全同期」は未達である（`docs/requirements/L1-requirements.md:327,403,407`）。
- 指摘事項:
  - `P1-01` 用語集 `SNS 連携基盤` が依然として「シェア・自動投稿・埋め込み・プロフィール表示・フィードの統合機能群」とだけ記載しており、今回の Phase 1/2 境界が反映されていない（`docs/requirements/L1-requirements.md:327`）。
  - `P1-02` `REQ-NF-014` のトレーサビリティ表は `F-018` を参照したままだが、今回の `ACC-018` はもはや API/自動化契約を直接検証していない。上部依存表では `F-018` を `REQ-NF-014` に含めておらず、同一文書内で解釈が割れている（`docs/requirements/L1-requirements.md:112,403`）。
  - `P2-01` `REQ-NF-018` の `F-018` 参照は全体要件としては妥当だが、Phase 注記がないため Phase 1 読み手には自動投稿ハザードまで含むように見える（`docs/requirements/L1-requirements.md:116,407`）。
  - `P2-02` `REQ-F-018` 本文に含まれる `プロフィール表示` と `SNS フィードウィジェット` に対応する受入条件が依然として未明示で、`ACC-018/018a/018b` だけでは Phase 1 範囲の受入が完結していない（`docs/requirements/L1-requirements.md:59,181-183`）。
- 次アクション:
  - 用語集 1 行を Phase 注記付きに修正する。
  - `REQ-NF-014` / `REQ-NF-018` の下部トレーサビリティ表を今回の Phase 配分に合わせて注記修正する。
  - 可能なら `プロフィール表示` / `SNS フィードウィジェット` 用の ACC 追加有無を明示判断する。

## 観点 1〜8 の判定

### 観点 1: F-018 本文の Phase 1/2 分離が NSRM（nsrm.yaml）と論理整合しているか

- 判定: `pass`
- 根拠: `nsrm.yaml` は `REQ-F-018` を `phase_1_mvp` に残しつつ、注記で「シェアボタン + OGP のみ、自動投稿は Phase 2」と明記している。L1 本文も同じ意味に更新されている（`.helix/nsrm.yaml:169`, `docs/requirements/L1-requirements.md:59`）。
- 指摘事項: なし
- 次アクション: なし

### 観点 2: F-018 本文と L0 §6 のドッグフーディング戦略（特に §6.2 §6.3 §6.6）が完全整合しているか

- 判定: `pass`
- 根拠: 既存 TL レビューは L0 §6 を「Phase 1 = 共有導線 + OGP/X Card + 人手投稿」「Phase 2 = 自動投稿/複数アカウント管理/LINE 深い統合」として pass 済みで、今回の L1 文面はその境界と一致する（`docs/reviews/L0-section6-tl-review4-20260501.md:32-53`）。
- 根拠: `nsrm.yaml` の `REQ-F-019`/`REQ-F-020` が Phase 2 配置のまま維持されており、L0 §6.3 / §6.6 の拡張ループ整理とも矛盾しない（`.helix/nsrm.yaml:196-197`）。
- 指摘事項: なし
- 次アクション: なし

### 観点 3: ACC-018 と ACC-018b の対応関係に過不足がないか

- 判定: `conditional`
- 根拠: `ACC-018` は Phase 1 のシェア導線 + OGP/X Card に限定され、`ACC-018b` は Phase 2 の自動投稿に分離されており、「Phase 1 で自動投稿を要求しない」という主目的は達成している（`docs/requirements/L1-requirements.md:181,183`）。
- 指摘事項:
  - `P2-02` `REQ-F-018` 本文にある `プロフィール表示` / `SNS フィードウィジェット` を受ける ACC が存在しない。今回の分離論点には直接関与しないが、Phase 1 受入網羅としては不足が残る。
- 次アクション:
  - `ACC-018c` などで `プロフィール表示` / `SNS フィードウィジェット` を起こすか、意図的に別要件へ出すかを明示する。

### 観点 4: ACC-018a（埋め込み oEmbed）の維持判断が妥当か

- 判定: `pass`
- 根拠: `REQ-F-018` の Phase 1 範囲に埋め込みが残っており、`ACC-018a` の維持は本文と整合する（`docs/requirements/L1-requirements.md:59,182`）。
- 根拠: 既存 L1 TL レビューでも「oEmbed 埋め込みとシェアボタンのみでも Phase 1 の訴求に十分」と評価されている（`docs/reviews/L1-tl-review-20260430.md:55`）。
- 指摘事項: なし
- 次アクション: なし

### 観点 5: F-018 修正で他の REQ-F / REQ-NF / ACC との整合性が崩れていないか

- 判定: `conditional`
- 根拠: `REQ-F-019`（法人版 SNS 深い統合）と `REQ-F-020`（SNS API 認証情報管理）は引き続き Phase 2 で、今回の `REQ-F-018` 自動投稿分離と整合する（`docs/requirements/L1-requirements.md:60-61`, `.helix/nsrm.yaml:196-197`）。
- 根拠: `REQ-NF-018` は SEO/WP運用ハザード管理なので、OGP/X Card を含む `F-018` との関連自体は残してよい（`docs/requirements/L1-requirements.md:151,407`）。
- 指摘事項:
  - `P1-01` 用語集が旧い意味のままで、L1 文書内の説明責任が分断している（`docs/requirements/L1-requirements.md:327`）。
  - `P1-02` `REQ-NF-014` の下部トレーサビリティ表だけが `F-018` を参照し続けており、上部依存表と整合しない。現状の `ACC-018` は API/自動化契約ではなく share/OGP 検証であるため、少なくとも注記が必要（`docs/requirements/L1-requirements.md:112,147,181,403`）。
  - `P2-01` `REQ-NF-018` トレーサビリティに Phase 注記がないため、`F-018` 参照が Phase 1 読み手へ過大に見える（`docs/requirements/L1-requirements.md:116,151,407`）。
- 次アクション:
  - `REQ-NF-014` は `F-018 拡張（ACC-018b）` または `F-020` 経由へ寄せるか、`Phase 2 のみ` 注記を付与する。
  - `REQ-NF-018` は `OGP/X Card/share preview を含む` 旨を補足し、Phase 1 と矛盾しない形にする。

### 観点 6: Phase 1 ローンチセット 23 件の全体整合

- 判定: `pass`
- 根拠: `REQ-F-018` は ID 自体を維持したままスコープ縮小されており、`phase_1_mvp` の件数は 23 のまま。機械確認でも `phase_1_mvp_len=23`、`phase_1_count_metric=23`、`unassigned_phase_count=0` を確認した（`.helix/nsrm.yaml:145-230`）。
- 指摘事項: なし
- 次アクション: なし

### 観点 7: 既存 G1.5 NSRM 検証ゲート（gate-checks.yaml）が引き続き pass 可能か

- 判定: `pass`
- 根拠: 今回の変更は `L1-requirements.md` 側の意味同期であり、`nsrm.yaml` の phase 件数・孤児数・未割当数は不変。`gate-checks.yaml` の今回影響しうる条件はそのまま通る（`.helix/gate-checks.yaml:7-28`）。
- 根拠: 実行確認でも `orphan_req_count=0`、`orphan_goal_count=0`、`reqs_without_necessity_proof=0`、`unassigned_phase_count=0`、`phase_1_mvp` 件数条件 pass を確認した。
- 指摘事項: なし
- 次アクション: metrics 値変更は不要

### 観点 8: 既存 L1 TL レビューの判断との整合

- 判定: `pass`
- 根拠: 既存 TL レビューは `F-018` を「SNS シェア基本のみ Phase 1、自動投稿は Phase 2 が妥当」と評価しており、今回の修正はその結論を L1 正本へ反映したものになっている（`docs/reviews/L1-tl-review-20260430.md:55`）。
- 根拠: 同レビューが指摘した `ADR-006` の Phase 1/2 分離方針、および `F-019/F-020` を Phase 2 拡張として扱う整理とも矛盾しない（`docs/reviews/L1-tl-review-20260430.md:170,204`）。
- 指摘事項: なし
- 次アクション: なし

## ブロッカー
件数: 2

- `P1-01` 用語集 `SNS 連携基盤` の旧記述が残存
- `P1-02` `REQ-NF-014` トレーサビリティが Phase 分離後の `F-018` 解釈と未整合

## マージ後 TODO

- `docs/requirements/L1-requirements.md:327` の用語集を「Phase 1 の共有導線/OGP/埋め込み」と「Phase 2 の自動投稿/深い統合」に分けて更新
- `docs/requirements/L1-requirements.md:403,407` のトレーサビリティ表に Phase 注記を追加し、`REQ-NF-014` 参照を再配置
- `docs/requirements/nsrm-04-edge-cases.md:281-296` の `REQ-F-018` エッジケースを Phase 1 / Phase 2 で見出し分離
- `REQ-F-018` の `プロフィール表示` / `SNS フィードウィジェット` に対応する ACC を追加するか、別要件へ切り出すか判断

## 総合所見

今回の修正の中核目的である「F-018 自動投稿を Phase 2 へ明示退避し、Phase 1 を share + OGP + embed へ絞る」は達成している。NSRM 正本、L0 §6 方針、既存 L1 TL レビューとの論理整合も取れているため、方向性評価は `pass` でよい。

ただし、L1 文書を「同期済み正本」として扱うにはまだ 2 点足りない。第 1 に用語集が旧い意味の `F-018` を保持している。第 2 に `REQ-NF-014` の下部トレーサビリティが今回の Phase 分離を反映していない。どちらも実装スコープや NSRM メトリクスを壊す欠陥ではないが、TL 観点では「完全同期」の成立条件に未達である。上記 2 点を直せば `修正承認` に切り替えてよい。
