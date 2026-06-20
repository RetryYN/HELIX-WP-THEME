# L3 PO 裁定パケット — AGENT NEO

> **文書種別**: PO 上申資料（裁定依頼）
> **作成日**: 2026-06-18
> **作成者**: ドラフタ（PM 補佐 / Sonnet）
> **対象ゲート**: L4 着手前 / G3 carry 解消
> **目的**: PM・設計者が独断できない領域（ライセンス・課金・法務・販売戦略・本番影響）を PO がその場で決定できる状態に整える。**結論は出さず、選択肢と PM 推奨を提示する**。

---

## ⭐ 2026-06-20 PO確定: AI開示法規制は Automation SEO 登録時の同意に集約（ADR-025）

> **確定日**: 2026-06-20 / **根拠**: ADR-025

以下の事項が PO により確定。2026-06-18 の PO 確定（ADR-024）とは独立した新規論点。

| 確定事項 | 内容 |
|---|---|
| 開示責務の集約先 | **Automation SEO 登録（サインアップ）時の同意フロー**に AI 生成コンテンツ開示法規制への対応責務を集約。開示義務が運営者（契約者）自身に帰属することを同意フローで確認する |
| マーキングロジックの所在 | **Automation SEO 側に集約**（REQ-NF-025 / ADR-024 と整合）。AGENT-NEO テーマ側に AI 判断ロジックを持ち込まない |
| テーマ（AGENT-NEO）の役割 | **任意 disclosure レンダリングフック（disclosure スロット / schema.org creator フィールド）のみ提供**。マーキング有無・内容の判断は Automation SEO が制御 |
| C2PA latent マーキング | **画像生成パイプライン（Automation SEO 側）の責務**。AGENT-NEO は画像ファイルをそのままレンダリングするのみ |
| 対象法規制 | EU AI Act Article 50（2026-08-02 施行 / 既存システム猶予 2026-12-02）/ California SB 942（2026-01-01 施行済み）/ C2PA v2.4 |

この確定により **GAP-RT-055 は RESOLVED-BY-DECISION**（ADR-025 参照）。L4 carry CARRY-ADR025-001〜005 として継続管理。

---

## ⭐ 2026-06-18 PO確定: Automation SEO 専用配布に一本化（ADR-024）

> **確定日**: 2026-06-18 / **根拠**: ADR-024

以下の事項が PO により確定。本パケットの各論点を下記前提で再評価する。

| 確定事項 | 内容 |
|---|---|
| 配布モデル | **Automation SEO 専用配布に一本化**。テーマ単体販売（個人版 ¥19,800 / 法人版 ¥98,000 の 3 SKU）は廃止 |
| 課金モデル | **Automation SEO のプラン階層のみで課金**。テーマ独立の課金ライン廃止 |
| 機能・個人/法人スコープ差 | 機能境界は維持。課金は Automation SEO プラン階層に統合 |
| REQ-F-043 廃止 | **外部AI write 受口（Open Editor Bridge Plugin）を廃止**。AI 操作は Automation SEO 経由のみ。OAuth 設計は不要 |
| 移行プラグイン | **無料 / lead magnet 維持可**。wp.org 申請の可否のみが残課題 |

この確定により **GAP-RT-053 は RESOLVED-BY-DECISION**（ADR-024 参照）。PO-ESCALATION から除外済み。

---

## 2026-06-20 PM確定（L4着手前 残6件）

> **確定日**: 2026-06-20 / **根拠**: PM 裁定（AI 補助）

以下6論点の disposition を PM-RESOLVED（再評価）として確定。

| 論点ID | disposition | PM 裁定内容 |
|---|---|---|
| CARRY-ADR023-004 | PM-RESOLVED | 選択肢A：旧テーマのショートコード変換は主要3種（`fukidashi` / `jin_icon` / `blogcard`）のみを Phase1 で提供、残りは非対応で明記 |
| Q-012 | PM-RESOLVED | 選択肢B：SNS フィードウィジェットは Phase2 送り。シェア/OGP/X Card/埋め込み/プロフィール表示は Phase1 とする |
| Q-005 | PM-RESOLVED | ライセンス検証は Automation SEO 契約 entitlement 確認へ統合。自社 API 実装。48h transient grace は凍結維持で再オープンしない |
| PO-WP7-01 | VERIFIED(2026-06-21) | WP7.0 GA と WP 6.9.4 の双方で Abilities API register -> get -> execute を実測済み。根拠: `poc/wp7-abilities/RESULTS.md` |
| PERF-CARRY-002 | PM-RESOLVED | 選択肢A：Cookie Consent は外部プラグイン adapter 方式。テーマは Consent Mode v2 の受け口（入力 API / 更新受け取り）を提供 |
| Q-013 | PM-RESOLVED | 選択肢B：安全側（Cookie Consent バナーあり前提）で進行。保存期間・集計閾値・表示責任者は L4 で Automation SEO 側 retention と整合して確定。将来「通知のみで法的に足りる」根拠が示されれば再評価 |

---

## 概要

本パケットは以下の 5 グループ計 12 論点を収録する（うち 1 件は ADR-024 により解消済み）。各論点に「緊急度」を付与し、末尾に **L4 着手前に必達の裁定リスト** を一覧化する。

| グループ | 論点数 | 緊急度「L4着手前必須」件数 | ADR-024 影響 |
|---|---:|---:|---|
| G1: ライセンス・課金・販売 | 4 | 1 | Q-005 縮小（grace 凍結済み・entitlement 統合のみ残課題）/ Q-006 を移行プラグイン Sprint 前カテゴリへ移動 |
| G2: 公開・法務 | 2 | 1 | 変更なし |
| G3: 機能スコープ（Phase 1 含有可否） | 2 | 2 | 変更なし |
| G4: WP7・同意基盤（技術+コスト/UX 判断） | 3 | 2 | 変更なし |
| G5: セキュリティ（外部AI操作境界） | 1 | 0 | **GAP-RT-053 RESOLVED-BY-DECISION（解消）** |
| **合計** | **12** | **6** | G5 解消 + G1 縮小 + Q-006 placement 変更で 9 → 6 に減少 |

---

## グループ 1 — ライセンス・課金・販売

### Q-005: ライセンス検証方式

**論点 ID**: Q-005 / GAP-RT-043 / PO-escalation
**ADR-024 影響**: **縮小再評価** / 旧スコープ（3 SKU 独立ライセンス管理）は不要化

**背景（ADR-024 後）**

ADR-024（2026-06-18）により AGENT NEO のテーマ単体販売 SKU が廃止され、課金は Automation SEO のプラン階層に統合された。このため、従来想定していた「個人版 / 法人版 / Open Editor Bridge Plugin の 3 SKU 独立ライセンス管理」は不要となる。

AGENT NEO テーマは Automation SEO の backend と既に通信する設計であるため、**ライセンス検証は Automation SEO 契約状態の確認に統合**できる。テーマ側での独立ライセンスサーバー構築（旧選択肢 A/C）は原則不要。移行プラグインは無料配布（lead magnet）のため課金ゲートは不要。

**残課題（PO 確認が必要なもの）**

| 項目 | 内容 |
|---|---|
| Automation SEO 契約確認の実装方式 | テーマ初回有効化時に Automation SEO バックエンドへの疎通確認 + 契約状態取得の具体的フローを確定する必要あり（Automation SEO が 1st-party backend のため自社 API で完結可能） |

> **48h transient grace は凍結済み（package-matrix SSOT / TC-011）で変更しない。** Automation SEO backend が一時停止した場合に 48 時間まで既存テーマ機能を継続するオフライン猶予期間はすでに L2/L3/OpenAPI/threat-model/WBS/TC-011 で確定・凍結された実装基盤。ここで再オープンすると下流の凍結契約・テストと矛盾するため PO 確認事項から除外する。

**PM 推奨（ADR-024 後）**

旧 Freemius 推奨は不要化。**Automation SEO 契約確認を自社 API で実装する方向**で設計シンプル化可。残課題は「ライセンス検証方式を Automation SEO 契約 entitlement 検証へ統合する実装フロー確定」のみ。grace period の再設計は含めない。

**この決定が unblock する GAP・ADR・carry**

- GAP-RT-043（Q-005 PO 裁定待ち）→ 残課題確認後に close 方向
- REQ-F-010 実装設計（スコープが Automation SEO 連携に縮小）

**緊急度**: L4 着手前必須（縮小後もライセンス確認ロジックは Companion Plugin の最初のスプリントで実装される。残課題の PO 確認は残置）

---

### Q-006: wp.org 配布方針（テーマ確定後の残課題）

**論点 ID**: Q-006 / GAP-RT-044 / PO-WP7-03 / PO-escalation
**ADR-024 影響**: **スコープ大幅縮小** / テーマ本体の wp.org 販売は廃止確定

**背景（ADR-024 後）**

ADR-024（2026-06-18）により AGENT NEO テーマは Automation SEO 専用配布に確定した。テーマ本体を wp.org で無料公開 / 有料販売する選択肢はなくなり、テーマ審査の要否判断も原則不要（Automation SEO 契約者向けダウンロードのみ）。

**残課題: 移行プラグイン（REQ-F-008）の wp.org 申請可否のみ**

移行プラグインは「無料 / lead magnet」として維持可（ADR-024 確定）。wp.org に申請して集客ツールとして活用するかどうかが残課題。

| 選択肢 | 概要 | メリット | デメリット |
|---|---|---|---|
| A: 移行プラグインを wp.org 申請（lead magnet） | REQ-F-008 移行プラグインを wp.org で無料公開。GPL 公開必須 | SWELL/JIN:R ユーザーへのリーチ。AGENT NEO / Automation SEO の認知拡大 | プラグイン審査コスト・維持コスト。GPL 公開による模倣リスク（移行プラグインのコードは公開） |
| B: 移行プラグインは自社サイトのみ配布（wp.org 非提出） | Automation SEO 契約者向けに自社サイトから配布のみ | 審査コスト不要。配布管理がシンプル | wp.org エコシステムからの集客なし |

**PM 推奨（ADR-024 後）**

**選択肢 A（wp.org 申請）**を推奨する。移行プラグインは Automation SEO への入口として機能する lead magnet であり、wp.org 審査を通じた第三者品質保証は信頼構築にも寄与する。ただし審査コスト・GPL 公開の判断は PO 確定が必要。

**この決定が unblock する GAP・ADR・carry**

- GAP-RT-044（Q-006 PO 裁定待ち）→ 残課題確認後に close 方向
- CARRY-WP7-003（WordPress.org Theme Check CI 組み込みの要否: **テーマ本体は不要確定**。移行プラグインのみ要否が残る）

**緊急度**: **移行プラグイン Sprint 着手前**（L4 全体のブロッカーではない。L4 内の移行プラグイン実装 Sprint 着手前に確定すれば足りる）

---

### Q-003: S1 初回構築サービス価格レンジ

**論点 ID**: Q-003 / GAP-RT-046 / PO-escalation
**背景**

S1 は「Automation SEO を使った AI 再構築支援」で、法人向けの初回構築検収付きサービス。L1 §8 では「L2 凍結前」を期限と設定したが未確定。S1 の価格設定は (1) AGENT NEO 販売 LP への掲載内容、(2) S1 の採算計算（工数見積×単価）、(3) 個人版・法人版 SKU との位置づけ整理 に影響する。法人版（¥98,000）との価格差がつかないと S1 の差別化が困難。L7 前の LP 公開前に確定が必要。

**選択肢**

| 選択肢 | 価格帯 | 前提・想定工数 | リスク |
|---|---|---|---|
| A: ¥300,000〜¥500,000 | ミドルレンジ | IA 再設計 + AI 記事生成 + 計測設定で 3〜5 人日相当 | 法人向け IT 投資として「お試し」に相当する価格帯。採算は工数次第 |
| B: ¥500,000〜¥1,000,000 | プレミアム | HP/LP 全面再設計 + 検収期間付きで 5〜10 人日相当 | 中小企業には高額感。商談→受注サイクルが長くなる |
| C: 要問い合わせ（範囲見積） | 公表しない | 規模・業種ごとに個別見積 | LP での訴求力が弱まる。問い合わせ障壁が高まる |

**PM 推奨（参考）**

PO 確定必須（課金・事業計画）。PM 視点では **選択肢 A（¥300,000〜¥500,000 の明示掲載）**を推奨する。理由: (1) 法人版 ¥98,000 との価格差を 3〜5 倍に設定することで「工数と専門知識を買う」位置づけが明確になる、(2) LP への価格帯明示により問い合わせ障壁を下げてリード獲得を優先する初期フェーズに適合する。最終的には PO の事業計画・原価計算に基づく確定が必要。

**この決定が unblock する GAP・ADR・carry**

- GAP-RT-046（Q-003 PO 裁定待ち）
- L7 前の販売 LP 設計（LP 価格掲載の要否確定）
- S1 の受入条件・SLA 設計

**緊急度**: L7 前でOK（L4 実装には直接影響しない。ただし LP 制作着手前に確定が望ましい）

---

### Q-004: 移行プラグイン 無料配布

**論点 ID**: Q-004 / GAP-RT-047 / PO-escalation
**ADR-024 影響**: **無料方針に大きく傾く** / PO 最終確認は残置

**背景（ADR-024 後）**

ADR-024（2026-06-18）により移行プラグイン（REQ-F-008）は **「無料 / lead magnet として維持可」** と PO 確定済み。軽課金（旧選択肢 C）は実質除外された。

「無料・単独配布（wp.org または自社サイト）」が基本方針。Q-006 の wp.org 申請判断と連動する。

**残課題（PO 最終確認）**

| 項目 | 内容 |
|---|---|
| 配布範囲 | Automation SEO 契約者向け同梱（単独ダウンロード不可）か、誰でもダウンロード可能な単独配布（wp.org / 自社）か |

**PM 推奨（ADR-024 後）**

**無料・単独配布（wp.org または自社サイト）** を推奨。Automation SEO への認知拡大目的の lead magnet として機能させる。Q-006 で wp.org 申請を選んだ場合は自動的に「無料・単独配布（wp.org）」が確定する。PO 最終確認は残置。

**この決定が unblock する GAP・ADR・carry**

- GAP-RT-047（Q-004 PO 裁定待ち）→ 無料方針の確認後に close 方向
- REQ-F-008 実装スコープ（MVP 範囲の確定）

**緊急度**: L4 途中まで猶予（移行プラグインは L4 Phase C 以降の実装が想定される。L4 Phase A 開始前に確定すれば十分）

---

## グループ 2 — 公開・法務

### Q-013: 公開指標ポリシー

**論点 ID**: Q-013 / GAP-RT-045 / PO-escalation
**背景**

AGENT NEO はユーザーサイトの Web Vitals RUM（GAP-RT-024 / L3-A4-performance-contract-gaps.md §4）・CWV スコア・トラッキングイベントを収集する設計である。「公開指標ポリシー（L0 §6.4）」として、これらの指標をどのレベルでユーザー（サイト訪問者）に開示するか、同意取得が必要かが未確定。REQ-NF-004（データ保護）・REQ-NF-009（法令/表示ガード）に直接影響し、日本の電気通信事業法（外部送信規律）・改正個人情報保護法・EU GDPR（日本語サイトでも EU 訪問者がいれば対象）の解釈を含む法務判断を伴う。

**未確定項目**

| 項目 | 未確定内容 |
|---|---|
| 同意取得要否 | Web Vitals RUM を「外部送信」として電気通信事業法の規律対象と解釈するか。プライバシーポリシー通知のみで足りるか、同意バナーが必要か |
| 集計閾値 | 個人を特定しうる粒度（特定 URL の 1 ユーザーのみのセッションデータ等）をどの閾値で公開しないか |
| 保存期間 | トラッキングイベント・RUM データの保存期間（Automation SEO DB 側の retention policy）|
| 公開遅延 | リアルタイム公開 vs 集計後遅延公開（24h/7d）の方針 |
| 表示責任者 | AGENT NEO 運営者側の個人情報取扱事業者として誰が責任を持つか（Automation SEO との役割分担） |

**選択肢**

| 選択肢 | 概要 | メリット | デメリット |
|---|---|---|---|
| A: プライバシーポリシー通知のみ（同意バナー不要） | 「個人を直接識別しない統計データ」との解釈で、プライバシーポリシーに明記するだけで対応 | 実装シンプル。UX 阻害なし | 法的グレーゾーン。将来的な法改正リスク |
| B: 同意バナー必須（Cookie Consent Gate） | 外部送信（Automation SEO サーバーへの RUM 送信）を「Cookie 等を用いた情報収集」として扱い、同意取得を義務化 | 法令遵守の確実性。GDPR 対応でも安全 | UX コスト。PERF-CARRY-002（Cookie Consent バナー実装）が blocking carry になる |
| C: オプトイン方式（デフォルト OFF） | RUM 送信をデフォルト無効とし、ユーザーが明示的に有効化した場合のみ送信 | プライバシー配慮を訴求できる。法的リスク最小 | データ収集量が大幅に減り、Automation SEO の分析精度が下がる |

**PM 推奨（参考）**

PO 確定必須（法務確認が必要）。PM 視点では **選択肢 B（同意バナー必須）**を推奨する。理由: (1) 2023 年改正電気通信事業法（外部送信規律）の対象となりうる、(2) 商用テーマとして法令リスクを製品設計で排除する姿勢は信頼性に直結する、(3) PERF-CARRY-002 の Cookie Consent バナー実装が blocking になるが、それ自体が製品としての価値要素になる。ただし法務確認なしには PO 判断が困難であり、必要に応じて弁護士・法務アドバイザーへの相談を推奨する。

**この決定が unblock する GAP・ADR・carry**

- GAP-RT-045（Q-013 PO 裁定待ち）
- PERF-CARRY-002（Cookie Consent バナー選定・実装、後述 G4 グループと連動）
- REQ-NF-004 / REQ-NF-009 の受入条件（ACC-NF-018 / ACC-NF-003）
- ACC-NF-018（データ保護監査の対象データ定義）

**緊急度**: L4 着手前必須（RUM 送信設計・Cookie Consent バナー実装はL4 Phase A の性能・トラッキング実装に先行して確定が必要）

---

### ADR-022 Yoast Premium 独自スキーマ非サポート告知方針

**論点 ID**: CARRY-ADR022-002 / ADR-022
**背景**

ADR-022（SEO 出力責務境界）において、AGENT NEO は Yoast の `wpseo-schema` スクリプトを `wp_dequeue` で排除することを決定した。これにより Yoast Premium の固有スキーマ（Local Business Schema / WooCommerce Product Schema / Review Schema 等）が出力されなくなる。Yoast Premium ユーザーが AGENT NEO に移行した場合に、これらの機能が利用不可になることを事前告知する文書が必要。どの媒体・タイミング・強度で告知するかはマーケティング・サポート方針の判断を含む。

**選択肢**

| 選択肢 | 告知方式 | メリット | デメリット |
|---|---|---|---|
| A: 販売 LP + ドキュメントサイトに明示（購入前通知） | 販売 LP の「制限事項」セクション、公式ドキュメントの「Yoast Premium との共存ガイド」に記載 | 購入前の期待値調整。返金クレーム防止 | 販売 LP にネガティブ情報を掲載することへの抵抗感 |
| B: インストール時 / 管理画面での警告のみ（購入後通知） | Yoast Premium 検出時に管理画面バナーで「AGENT NEO は Yoast Premium 固有スキーマを代替します」と告知 | 販売 LP はクリーンに保てる | Yoast Premium ユーザーが購入後に初めて制限を知る。クレームリスク |
| C: 機能代替ロードマップを明示（制限+代替計画の組み合わせ） | 販売 LP + ドキュメントに制限を明示しつつ、AGENT NEO の @graph 実装で対応済みの schema 一覧と今後の対応予定を記載 | 誠実な告知と製品ロードマップの透明性。ユーザー信頼向上 | ロードマップへのコミットが追加の開発約束になる |

**PM 推奨（参考）**

**選択肢 C（機能代替ロードマップ明示）**を推奨する。理由: (1) Yoast Premium ユーザーは SEO 投資意識が高く、制限の隠蔽は後のクレームにつながりやすい、(2) AGENT NEO の @graph 実装で代替済みの schema を積極的に訴求する機会でもある、(3) マーケティングメッセージとしては「より高度な統合スキーマ管理への移行」として正向きに表現できる。この判断は販売・マーケティング戦略の問題であり PO 確定が必要。

**この決定が unblock する GAP・ADR・carry**

- CARRY-ADR022-002（非サポート告知ドキュメント整備）
- L6 受入テスト前のドキュメント整備タスクのスコープ確定

**緊急度**: L7 前でOK（L4 実装には直接影響しない。L6 受入テスト前の告知ドキュメント整備が実施できれば十分）

---

## グループ 3 — 機能スコープ（Phase 1 に含めるか）

### Q-012: F-018「SNS フィードウィジェット」と「プロフィール表示」の Phase1/2 境界

**論点 ID**: Q-012 / GAP-RT-048 / CARRY-ADR023-002（連動） / PO-escalation
**背景**

REQ-F-018（SNS 連携基盤）の現定義では「Phase 1 範囲: シェアボタン・OGP/X Card 配信・埋め込み・プロフィール表示・SNS フィードウィジェット（lazy load）」と記載されているが、ADR-023 §PO 判断事項 2 では SNS フィードウィジェットの Phase1/2 は「PO 判断」とされている。また ACC（受入条件）の切り出し方針（ACC として F-018 内に補完するか別 REQ-F として切り出すか）も未確定（L1 §8 Q-012）。

SNS フィードウィジェットはリアルタイム API（X API / Instagram Graph API）に依存するため、API 利用規約変更・Rate Limit・OAuth 認証フローの保守コストが高い。Phase 1 に含めると API 保守負荷が L4 実装全体に影響する。

**選択肢**

| 選択肢 | スコープ | メリット | デメリット |
|---|---|---|---|
| A: SNS フィードウィジェット・プロフィール表示ともに Phase 1 に含める | 現 REQ-F-018 定義どおり全項目 Phase 1 | Phase 1 で SNS 連携を完結させ「LLMO・分散 SEO 時代の必須要素」の訴求を早期実現 | X API v2 の Rate Limit・Instagram Graph API 審査・LINE Messaging API の保守コストが L4 初期から発生 |
| B: SNS フィードウィジェットのみ Phase 2 送り（プロフィール表示は Phase 1）| フィードウィジェットを除き、プロフィール表示・シェアボタン・OGP・埋め込みは Phase 1 | リアルタイム API 依存の高リスク機能を Phase 2 に分離しつつ、静的な SNS 連携（プロフィール表示・シェア）は Phase 1 で提供 | REQ-F-018 の再記述が必要。「Phase 1 機能」の訴求が若干弱まる |
| C: SNS フィードウィジェット・プロフィール表示ともに Phase 2 送り | フィードウィジェットとプロフィール表示の両方を Phase 2 に移動。Phase 1 は シェアボタン・OGP/X Card・埋め込みのみ | Phase 1 の実装スコープを最小化し、速度（Core Web Vitals）・SEO 基盤・収益化ブロックに集中できる | Phase 1 の SNS 連携機能が薄くなる。競合との差別化要素が遅れる |

**PM 推奨（参考）**

PO 確定必須。PM 視点では **選択肢 B（SNS フィードウィジェットのみ Phase 2 送り）**を推奨する。理由: (1) X API v2 の利用規約・Rate Limit 変更リスクは製品品質を直撃するため Phase 1 から除外する方が安全、(2) プロフィール表示は静的実装が可能で API 依存が低い、(3) Phase 1 では「シェアボタン + OGP + 埋め込み + プロフィール表示」で SNS 連携の基本価値を提供できる。

**この決定が unblock する GAP・ADR・carry**

- GAP-RT-048（Q-012 PO 裁定待ち）
- CARRY-ADR023-002（ウィジェット系ブロックスプリント分解）
- REQ-F-018 の受入条件（ACC-018 / ACC-018a）の確定版作成
- L4 F-018 実装スプリントの入力（Phase 1 スコープが確定しないと実装開始不可）

**緊急度**: L4 着手前必須（F-018 実装スプリントのスコープが確定しないと L4 Phase A スプリント計画が立てられない）

---

### CARRY-ADR023-004: Bridge Plugin ショートコード変換スコープ

**論点 ID**: CARRY-ADR023-004 / ADR-023 §PO 判断事項 1
**背景**

Bridge Plugin（ADR-019）は既存テーマ（SWELL/JIN:R）のショートコード（`[fukidashi]`・`[jin_icon]`・`[blogcard]` 等）を AGENT NEO ブロックへ変換する役割を担う。ADR-023 の CARRY-ADR023-004（P1: blocking=true）として記録されており、変換対象ショートコードの一覧・変換方式・どのリリースバージョンで提供するかが未確定。これが確定しないと Bridge Plugin の L4 実装スプリントが着手不可。

既存 JIN:R ユーザーが AGENT NEO に移行した場合、過去コンテンツ（ショートコードを含む投稿が多数ある場合がある）の互換性を保証するレベルが製品の移行容易性に直結する。「全ショートコードを変換する」と約束することは保守コミットメントになる。

**選択肢**

| 選択肢 | 変換スコープ | 提供リリース | メリット | デメリット |
|---|---|---|---|---|
| A: 主要ショートコード（fukidashi / jin_icon / blogcard）のみ最優先で提供、Phase 1 含める | 3 種のみ | Phase 1 リリース時 | AGENT NEO の主要競合（JIN:R）からの移行を Phase 1 でサポート。移行訴求が強い | 3 種以外のショートコードは「非対応」と告知が必要 |
| B: 主要 3 種は Phase 1、残り（第三者プラグイン等）は Phase 2 / オープンソース化 | 3 種 Phase 1、それ以外は第三者が対応可能な拡張ポイントのみ提供 | 段階リリース | 拡張性を設計に組み込むことで、将来のショートコード対応をコミュニティに開放できる | Phase 1 時点では変換が3種のみと告知が必要 |
| C: Phase 2 送り（Bridge Plugin の核心機能は Phase 2 以降） | 0（Phase 1 では変換なし）| Phase 2 | Phase 1 の実装コスト削減。Core テーマ・API 基盤・収益化ブロックに集中 | 移行訴求が弱まる。既存 JIN:R/SWELL ユーザーへの訴求が遅れる |

**PM 推奨（参考）**

PO 確定必須。PM 視点では **選択肢 A（主要3種を Phase 1 で提供）**を推奨する。理由: (1) fukidashi / jin_icon / blogcard は JIN:R / SWELL ユーザーが最も使うショートコードで、移行の象徴的な価値がある、(2) 「3 種のみ対応」と明確に告知することでコミットメントを限定できる、(3) CARRY-ADR023-004 が P1 blocking であることから、早期の確定が L4 工程表に与える影響を最小化できる。

**この決定が unblock する GAP・ADR・carry**

- CARRY-ADR023-004（P1 blocking / ADR-019 追記の前提）
- ADR-019 の更新（Bridge Plugin スコープ確定）
- L4 Bridge Plugin スプリント（CARRY-ADR023-001 の工程表組み込みと連動）

**緊急度**: L4 着手前必須（CARRY-ADR023-004 は blocking=true のため、L4 Bridge Plugin スプリント着手前に確定が必要）

---

## グループ 4 — WP7・同意基盤（技術だがコスト/UX 判断を含む）

### PO-WP7-01: WP 7.0 Abilities API 本格組み込み検証 人日投資承認

> **2026-06-20 更新**: WP 7.0 は 2026-05-20 に GA リリース済み（ADR-020 2026-06-20 追記参照）。以下の「RC 段階」「RC PoC」「RC → final」の記述は起票時点（GA前）の草案表現であり、現在は「GA済み本格組み込み検証」に読み替える。RC 乖離リスクは解消済み。選択肢 A の「RC 段階で即 PoC」は「GA 環境で本格組み込み検証」として既に選択済みと解釈してよい（ADR-020 CARRY-WP7-001 更新済み）。
>
> **2026-06-21 検証完了**: PO-WP7-01 は **VERIFIED(2026-06-21)**。WordPress 7.0 (GA) と WordPress 6.9.4 の双方で `agent-neo/diag-ping` ability の register -> get -> execute を確認した。実測根拠は `poc/wp7-abilities/RESULTS.md`。

**論点 ID**: PO-WP7-01 / ADR-020 §PO 論点
**背景**

ADR-020（WP 7.0 先回り対応）の D-1 では「~~WP 7.0 RC 段階で Docker WP 7.0-RC 環境を構築し~~ WP 7.0（GA/stable）環境で `wp_register_ability()` の実装可能性を検証する（CARRY-WP7-001）」を計画している。~~RC → final の間に API シグネチャが変更されるリスクがある（先行投資が無駄になる可能性）。~~ GA 済みのため API シグネチャ変更リスクは解消。Abilities API 本格組み込み検証への人日投資を承認するか否かはコスト判断であり PO の意思決定が必要。

**選択肢**

| 選択肢 | 内容 | メリット | デメリット |
|---|---|---|---|
| A: ~~RC 段階で即 PoC~~（CARRY-WP7-001 実施）→ **GA 環境で本格組み込み検証** | L4 entry 時点で Docker WP 7.0（GA/stable）環境構築 + Abilities API 本格検証を実施（推定 0.5〜1 人日） | WP 7.0 GA 対応を L4 内で完結できる。先行採用で差別化訴求 | — （RC 乖離リスクは GA 済みで解消） |
| B: WP 7.0 final リリース確認後に PoC | ~~final 確認を待ってから PoC・本格実装~~ → GA 済みのため本選択肢は実質 A と同一 | — | — |
| C: PoC なしで final 後に直接実装 | final 後に PoC なしで直接実装 | PoC 工数をゼロにできる | Abilities API の干渉リスクを事前確認なしに本実装するリスク |

**PM 推奨（参考）**

**選択肢 A（GA 環境で本格組み込み検証）**を推奨する。理由: (1) 推定 0.5〜1 人日の投資に対して「先行採用による差別化」の価値は大きい、(2) SWELL/JIN:R が Abilities API 実装 0 件である現状での先行採用は明確な訴求ポイントになる、(3) WP 7.0 GA 済みのため RC 乖離リスクはなく、コスト的リスクはほぼゼロ。最終的には L4 の工数計画との兼ね合いで PO が承認する。

**この決定が unblock する GAP・ADR・carry**

- CARRY-WP7-001（WP 7.0 GA 環境 Abilities API 本格組み込み検証 / P1）
- ADR-020 D-1（Abilities API 採用方針の検証根拠化）
- GAP-RT-027（Abilities API 互換 CI マトリクス）

**緊急度**: L4 着手前必須（CARRY-WP7-001 は L4 entry 時に着手条件があるため、承認を L4 開始前に取る必要がある）

---

### PO-WP7-02: 共同編集 ON 時の 423 Locked 拒否 UX 許容可否

**論点 ID**: PO-WP7-02 / ADR-020 §PO 論点 / CARRY-WP7-005
**背景**

ADR-020 D-3（共同編集衝突時の AGENT NEO 設計）では、`_edit_lock` 保持中の AI REST 操作に対して `423 Locked` を返す仕様を確定した。これは「AI が止まる（= AI 操作が失敗し、ユーザーへのエラー通知が必要になる）」仕様を意味する。Automation SEO 側でリトライロジックを実装するが、最終的に「AI が記事を書き換えようとしたが、編集者が編集中のため止まりました」という体験をどう製品として見せるかは UX・製品方針の判断を含む。

**選択肢**

| 選択肢 | 対応方針 | メリット | デメリット |
|---|---|---|---|
| A: 423 Locked のまま（仕様確定）+ Automation SEO 側リトライ委任 | 現 ADR-020 D-3 の決定を維持。AGENT NEO は `423 Locked` を返し、Automation SEO 側でリトライ制御 | 設計シンプル。「編集者が優先」という明確なポリシー | Automation SEO のリトライが失敗した場合、エラーがユーザーに到達する。AI 操作の「勝手に止まった」感がある |
| B: 423 時にキューイング（AGENT NEO 側で再試行キュー保持）| AGENT NEO が `423` を返す前に内部キューに格納し、`_edit_lock` 解放後に自動再実行 | AI 操作が「失敗」ではなく「待機中」になる。ユーザー体験が向上 | AGENT NEO 側でキュー実装が必要（追加実装コスト）。キューの溢れ・タイムアウト設計が複雑になる |
| C: 423 時に dryRun 結果を返し、ユーザーが手動で apply を選択 | 編集者ロック中は「apply できません。プレビューを確認して手動適用してください」と UI で案内 | 「AI が勝手に操作しない」安全感を提供できる | 手動介入が増えるため、自動化の価値が下がる |

**PM 推奨（参考）**

PO 確定必須（UX 方針・製品哲学）。PM 視点では **選択肢 A（423 Locked 維持 + Automation SEO リトライ委任）**を推奨する。理由: (1) AGENT NEO 側の設計をシンプルに保つ（REQ-NF-025 AI ロジック完全分離原則に従い、リトライ判断は Automation SEO 側）、(2) 共同編集 ON のユーザーはエンタープライズ利用であり、Automation SEO 側のリトライ設計で対応可能と想定する、(3) B 案のキュー実装は L4 工数を増加させリスクが高い。ただし「AI が止まる体験」が製品として許容できるかは PO の価値観に依存する。

**この決定が unblock する GAP・ADR・carry**

- CARRY-WP7-005（`423 Locked` / `409 Conflict` 衝突検出実装 / P1）
- ADR-020 D-3 の実装仕様確定
- GAP-RT-028（共同編集衝突方針未設計）

**緊急度**: L4 途中まで猶予（REST エンドポイント実装スプリント着手前に確定すれば足りる。CARRY-WP7-005 は L4 REST 実装フェーズ時に対応）

---

### PERF-CARRY-002: Cookie Consent バナー外部プラグイン対応 vs テーマ内蔵

**論点 ID**: PERF-CARRY-002 / L3-A4-performance-contract-gaps.md §2 carry 節
**背景**

GAP-RT-022（`third-party-tags.schema.json`）と Q-013（公開指標ポリシー）の設計で、Cookie Consent バナー（同意ゲート）の実装方針が必要になる。`third-party-tags.schema.json` の `consentModeVersion: v2` を前提とした実装では、「ユーザーが同意バナーで選択した内容を `gtag('consent', 'update', ...)` として AGENT NEO が受け取る」ための連携 API が必要。

外部プラグイン（CookieYes / Complianz / CMP 等）に同意バナーを委ねる場合と、AGENT NEO テーマが軽量バナーを内蔵する場合で実装工数・保守コスト・ユーザー体験が異なる。また Q-013（公開指標ポリシー）で「同意バナー必須（B 案）」が選択された場合は、この判断が blocking となる。

**選択肢**

| 選択肢 | 実装方針 | メリット | デメリット |
|---|---|---|---|
| A: 外部 Cookie Consent プラグイン対応（アダプタ方式） | AGENT NEO は Consent Mode v2 の update API を公開し、CookieYes / Complianz 等の外部プラグインがコールバックを提供する adapter を実装 | 実装コスト低い。外部プラグインの UI 品質・多言語対応を活用できる | 外部プラグインへの依存。プラグイン更新で adapter が壊れるリスク |
| B: テーマ内蔵ライト同意バナー | AGENT NEO が最小限の同意バナー（日本語/英語の「Cookie を使用しています。同意する/拒否する」）を内蔵 | 外部プラグイン不要。テーマ単体で完結するセールスポイント | バナー UI の保守・多言語対応・デザイン整合性の維持コスト。法的文書（プライバシーポリシー）との連携設計が別途必要 |
| C: 判断を L4 Phase 2 送り（Phase 1 は adapter のみ提供）| Phase 1 は外部プラグイン向けの Consent Mode v2 adapter のみ提供し、内蔵バナーは Phase 2 で判断 | Phase 1 の実装スコープを最小化 | Phase 1 時点で外部プラグインが必要という制約が残る。Q-013 の判断が blocking の場合は PERF-CARRY-002 も blocking になる |

**PM 推奨（参考）**

PO 確定必須（ただし Q-013 の判断が先行する）。PM 視点では Q-013 で「同意バナー必須（B 案）」が確定した場合は **選択肢 A（外部プラグイン adapter 方式）** を推奨する。理由: (1) 同意バナー UI・法的文書連携は既存の専門プラグインの方が品質が高い、(2) AGENT NEO は adapter を通じて Consent Mode v2 の受け取り口を提供するだけで実装コストを最小化できる、(3) REQ-NF-010（プラグイン依存度管理）の「外部プラグインは任意 adapter」方針に準拠する。Q-013 で「プライバシーポリシー通知のみ（A 案）」となった場合は PERF-CARRY-002 の blocking が解除される。

**この決定が unblock する GAP・ADR・carry**

- PERF-CARRY-002（Cookie Consent バナー選定・実装 / P1 blocking）
- GAP-RT-022（third-party-tags.schema.json の consent バナー連携設計）
- GAP-RT-038（Cookie Consent Gate TC の実装）
- third-party-tags.schema.json の `defaultConsentState` 設計確定

**緊急度**: L4 着手前必須（Q-013 の判断を前提とし、third-party-manager.php の実装設計に先行して確定が必要。Q-013 が blocking carry となっている）

---

## グループ 5 — セキュリティ（外部AI操作境界）

### GAP-RT-053: REQ-F-043 Open Editor Bridge Plugin の OAuth 申請フロー — **解消済み（ADR-024）**

**論点 ID**: GAP-RT-053 / REQ-F-043 / ~~PO-ESCALATION~~ → **RESOLVED-BY-DECISION**
**確定日**: 2026-06-18 / **根拠**: ADR-024

**確定内容**

ADR-024（2026-06-18 PO確定）により **REQ-F-043（Open Editor Bridge Plugin）を廃止**。外部AIエディタ（Claude / Codex / Cursor / Cline / Continue 等）からの write 操作受け口は設けない。AI 操作は Automation SEO 経由のみとする。

これにより以下が確定:
- OAuth 発行主体・scope・審査フロー・revoke 手順の設計は **一切不要**
- `docs/security/threat-model.md` §5.1 TB-19（外部AI write 攻撃面）に記載の攻撃経路は **設計段階で封鎖**
- REQ-F-043 の L4 実装 Sprint は **対象外（skip 確定）**

**GAP-RT-053 disposition**: RESOLVED-BY-DECISION（2026-06-18 / ADR-024）

> PO-ESCALATION の裁定を要する論点としては終了。L4 実装計画・threat-model からの REQ-F-043 参照は削除またはアーカイブ処理を推奨（L4 entry 時に確認）。

---

## L4 着手前に必達の裁定 — 優先リスト

> **ADR-024 再集計（2026-06-18）**: GAP-RT-053 解消 + Q-005/Q-006 縮小 + Q-006 を移行プラグイン Sprint 前カテゴリへ移動により 9 件 → 6 件に減少。

以下 **6 件**は L4 実装開始前に PO 裁定が必要な論点。残り 4 件は L4 途中または L7 前での裁定でよい。

| 優先順 | 論点 ID | 論点名 | 関連 blocking carry | ADR-024 影響 | disposition |
|---:|---|---|---|---|---|
| 1 | CARRY-ADR023-004 | Bridge Plugin ショートコード変換スコープ | CARRY-ADR023-001, CARRY-ADR023-004 (blocking=true) | 変更なし（主要3種を Phase1 で確定） | PM-RESOLVED(2026-06-20) |
| 2 | PERF-CARRY-002 | Cookie Consent バナー外部 vs 内蔵 | PERF-CARRY-002 (P1 blocking) | 変更なし（外部プラグイン adapter 方針） | PM-RESOLVED(2026-06-20) |
| 3 | Q-013 | 公開指標ポリシー（同意取得要否・保存期間等） | PERF-CARRY-002 の前提 | 変更なし（選択肢B確定） | PM-RESOLVED(2026-06-20) |
| 4 | Q-005 | ライセンス検証方式 **→ Automation SEO 契約確認の実装方式（縮小）** | REQ-F-010 実装設計 | 旧 Freemius 判断不要化。自社 API 方式で大筋確定。48h grace は凍結済み・残課題は契約 entitlement 検証統合のみ | PM-RESOLVED(2026-06-20) |
| 5 | Q-012 | SNS フィードウィジェット Phase 1/2 境界 | F-018 実装スプリントのスコープ | 変更なし（Widget は Phase2） | PM-RESOLVED(2026-06-20) |
| 6 | PO-WP7-01 | WP 7.0 Abilities API 本格組み込み検証 人日承認（~~RC PoC~~ → GA 済みのため本格組み込み検証に移行済み / ADR-020 2026-06-20 追記参照） | CARRY-WP7-001 (P1) | WP7.0 GA + WP 6.9.4 で register -> get -> execute 実測済み。根拠: `poc/wp7-abilities/RESULTS.md` | VERIFIED(2026-06-21) |

> **PO-WP7-04（PHP 7.4 非サポート確認）**: ADR-020 D-2 ですでに「PHP 8.1+ 推奨・PHP 7.4 は非サポート（参考情報のみ）」と設計決定されているが、これを配布 LP の「動作環境」表記に反映させるには PO の最終確認が必要。L4 着手前に確認しておくことで LP 制作と実装の表記が揃う。（Automation SEO 専用配布に変わったため LP 掲載の要否も合わせて確認推奨）

> ~~GAP-RT-053（REQ-F-043 OAuth フロー）~~: **ADR-024 により解消。L4 blocking から除外済み。**

**L4 途中まで猶予（3 件）:**

| 論点 ID | 論点名 | 猶予理由 | ADR-024 影響 |
|---|---|---|---|
| Q-006 | wp.org 配布 **→ 移行プラグイン wp.org 申請可否のみ（縮小）** | **移行プラグイン Sprint 着手前**に確定すれば足りる（L4 全体のブロッカーではない） | テーマ本体の wp.org 判断は不要確定。移行プラグインの申請可否のみ残置。CARRY-WP7-003 への影響は移行プラグインのみ |
| Q-004 | 移行プラグイン 無料配布 | L4 Phase C（移行プラグイン実装）着手前に確定すれば足りる | 無料方針に大筋確定（PO 最終確認残置）。軽課金選択肢は除外 |
| PO-WP7-02 | 423 Locked 拒否 UX 許容可否 | L4 REST 実装スプリント着手前に確定すれば足りる | 変更なし |

**L7 前でOK（2 件）:**

| 論点 ID | 論点名 |
|---|---|
| Q-003 | S1 価格レンジ（配布 LP 公開前に確定）※テーマ配布モデルと独立のサービス価格のため変更なし |
| CARRY-ADR022-002 | Yoast Premium 非サポート告知方針（L6 受入テスト前にドキュメント整備） |

---

## blocking carry 一覧

本パケットに記録された論点が直接 unblock する blocking carry の ID を整理する。

| carry ID | 優先度 | 論点 ID（裁定で unblock） |
|---|---|---|
| CARRY-ADR023-004 | P1 (blocking=true) | CARRY-ADR023-004（Bridge Plugin スコープ確定） |
| PERF-CARRY-002 | P1 (blocking) | Q-013, PERF-CARRY-002 |
| CARRY-WP7-001 | P1 | PO-WP7-01 |
| CARRY-WP7-005 | P1 | PO-WP7-02 |

---

*作成: 2026-06-18 / 担当: ドラフタ（PM 補佐 / Sonnet）*
*次アクション: PO レビュー → 各論点の裁定記録を L1-requirements.md §8（未決事項）と該当 ADR / carry に反映*
*セキュリティ精査追記: 2026-06-18 / GAP-RT-053（REQ-F-043 OAuth フロー / G5 グループ新設）追加 / 論点総数 11 → 12 件・L4着手前必須 8 → 9 件 / 担当: 文書担当（Sonnet）*
*ADR-024 re-evaluation: 2026-06-18 / Automation SEO 専用配布一本化・REQ-F-043 廃止を受けて各論点を再評価 / GAP-RT-053 RESOLVED-BY-DECISION（PO-ESCALATION から除外）/ Q-005 縮小（48h grace 凍結済み確定・Automation SEO 契約 entitlement 統合のみ残課題）/ Q-006 縮小かつ移行プラグイン Sprint 前カテゴリへ placement 変更（L4全体ブロッカーから除外）/ Q-004 無料方針大筋確定（PO 最終確認残置）/ L4着手前必須 9件 → 6件 / 担当: 文書整合担当（Sonnet）*
