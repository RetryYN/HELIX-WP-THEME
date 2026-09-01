# AGENT NEO 脅威モデル (G2完了用)

- 対象: AGENT NEO Theme + Core Plugin + Automation SEO 連携
- 作成日: 2026-06-14
- 版: G2版（脅威モデル完全記述）
- 参照資料:
  - `docs/design/L2-design.md`（§2.1, §8.6, §8.7, §8.8, §8.11, §9）
  - `docs/requirements/legacy/L1-requirements.md`（REQ-F-003, REQ-F-010, REQ-F-020, REQ-F-042, REQ-NF-004, REQ-NF-025）
  - `docs/design/data-model-ids.md`

## 1. 目的

- G2完了条件を満たすため、資産、信頼境界、DFD、STRIDE×DREAD、残留リスク、所有者、実施時期を同一ドキュメントで確定する。
- WP内部処理と外部連携（Automation SEO・外部AIエディタ）を分離し、**認証方式（nonce/capability、HMAC、Application Password、経路制御）を候補表現から実装固定値へ**切り替える。
- P0/P1 の是正項目は本書内で実装仕様まで落として完結させ、未着手は `P2 carry` として明示する。

---

## 2. 資産一覧（Assets）

| 資産 | 説明 | CIA 重要度（C/I/A） | 備考 |
|---|---|---|---|
| 記事/構造コンテンツ | 投稿本文、セクション、CTA、LP/HP Blueprint、ブロックJSON | 高 / 高 / 高 | `agent-neo/v1` で更新される主要資産 |
| サイト設定 | `site_token`, テナント設定、依存/プラグイン設定、公開ポリシー | 高 / 中 / 高 | `settings`・`health` API と Dashboard 表示対象 |
| ライセンス情報 | ライセンス種別、期間、紐づく実行権限 | 高 / 中 / 中 | REQ-F-010 前提の package 制御に必須 |
| 計測データ | section/CTA 計測、tracking イベント、A/B 結果 | 中 / 高 / 中 | 欠損率・漏れ率がビジネスKPIに影響 |
| Automation SEO 連携トークン | API資格情報、署名キー、更新シークレット | 低 / 高 / 高 | 取引連携・署名検証の中核 |
| WP管理権限 | 管理者/編集者/承認者ロール、nonce、capability | 低 / 低 / 高 | 権限昇格が成立すると全資産へ波及 |
| MCP 操作設定 | `mcp-tools.schema.json` の tool 権限/危険操作分類、MCP session token | 中 / 高 / 中 | MCP 経路の実行制御と監査の基盤 |
| AI判断ロジック（AGENT NEO側不含） | REQ-NF-025 により GPT判定・選別ロジックは保持しない | 高 / 低 / 高 | 配布前提のGPL制約回避として重要（有価値情報保護） |
| 監査ログ | APIアクセス、write経路、署名情報、変更経路 | 中 / 高 / 高 | 事後追跡、監査、コンプラ対応の土台 |
| API契約情報 | `openapi.yaml`, schema, webhook契約, エラーカタログ | 中 / 低 / 高 | 破壊的変更防止と運用品質の前提 |

---

## 3. 信頼境界（Trust Boundaries）

| 境界名 | 対象 | 外部に触れる面 | 脅威の主対象 |
|---|---|---|---|
| WP 内部 | WP Core + DB + 認証基盤 | - | ログイン/権限操作、nonce生成、権限改変 |
| AGENT NEO Core Plugin | `agent-neo/v1`, `agent-neo` CLI, job/health, tracking | 外部: Internet/Automation SEO | 外部入力の検証不備、権限逸脱、公開情報露出 |
| Automation SEO（外部・クローズド） | 外部LLM側判断基盤（aseo） | WP公開系更新 API, webhook受信 | 署名不正、Replay、ジョブ混乱 |
| MCP（ローカル） | MCP Server / MCP Client / MCP tool transport | ローカルIPC（stdio/loopback） | ローカル権限侵害、危険 tool の直接 apply |
| 外部AIエディタ | Claude CU / Codex / Cursor 等 | wp/v2/agent-neo/v1 などの書き込み試行 | 認証バイパス、経路不正 |
| 公開インターネット | 訪問者、bot、crawler | public endpoint、snapshot API | DOS、情報収集、情報漏えい |

---

## 4. DFD（データフロー図）

```mermaid
flowchart LR
  A[WP Admin/管理UI] -->|agent-neo/v1 read/apply (nonce + capability)| B[AGENT NEO Core Plugin]
  A -->|外部設定/権限変更| W[WP Internal]
  W -->|DB更新・設定保存| B
  B -->|catalog-update push (JSON webhook)| C[Automation SEO]
  C -->|aseo/v1 PATCH /agent-neo/v1/posts/<id>| B
  C -->|campaign/track feed| E[計測/分析基盤]
  M[外部AIエディタ] -->|agent-neo/v1 / aseo/v1 試行| B
  MCP[Local MCP Client] -->|mcp tool call (dryRun/apply)| X[MCP Server]
  X -->|Agent JSON Contracts| B
  B -->|public snapshot/トラッキング/health| P[公開利用者・Crawler]
  B -->|監査ログ| L[Log/監視]
```

### 主要フロー

| FlowID | フロー | データ | 主要プロトコル | 認証・制御 |
|---|---|---|---|---|
| F-01 | AGENT NEO Core Plugin → Automation SEO | catalog-update push（section/cta/seo metadata） | HTTPS/JSON | HMAC + timestamp + replay防止（確定値） |
| F-02 | Automation SEO → AGENT NEO `agent-neo/v1` PATCH | posts/blocks/sections patch, swap 指示 | HTTPS REST | Application Password + `X-App-Timestamp/nonce` + JSON Schema検証 |
| F-03 | 外部AIエディタ → AGENT NEO `agent-neo/v1` | 外部構造変更要求（POST/PATCH） | HTTPS REST | 外部 AI/外部エディタからの write は**全経路 403（例外なし）**。正規 write = `aseo/v1` + `agent-neo/v1` のみ（REQ-F-042 / ADR-024: REQ-F-043 廃止 2026-06-18） |
| F-04 | 外部AIエディタ → `wp/v2` | 投稿構造変更 | HTTPS REST（WP標準） | デフォルト拒否（403） |
| F-05 | WP Internal → AGENT NEO Core | 計測イベント（public 接続） | 署名付き公開API | 入力制限 + 速度制御 |
| F-06 | 外部AIエディタ/MCP クライアント → MCP Server → AGENT NEO | Agent JSON Contracts（dryRun/diff/apply） | MCP transport + HTTP bridge | MCP ツール許可制御 + capability + local session |

---

## 5. 認証・認可方式の凍結（候補表現を排除）

### 5.1 決定値

| 対象 | 認証方式 | 補強 |
|---|---|---|
| WP 内部 REST 書き込み | **nonce + capability（確定）** | `edit_*` ロール＋nonce検証。`application password`・OAuthは併用候補として廃止 |
| 外部サーバー間通信（Automation SEO 連携） | **HMAC-SHA256 + timestamp + replay防止（once-token）** | `timestamp` の許容ウィンドウ厳密化 + シングルユース once-token + payload hash で検証 |
| 外部 API 認証（Automation SEO ↔ AGENT NEO） | **Application Password（確定）** | WordPress標準との親和性を理由に固定 |
| MCP Tool 経路 | **MCP session token + tool allowlist（確定）** | `mcp-tools.schema.json` の tool_id/権限/危険操作分類を厳密適用 |
| 外部 AI エディタ | **デフォルト 403、許可経路は `agent-neo/v1` と `aseo/v1` のみ** | REQ-F-042、ACC-042/ACC-042a で強制確認 |

#### HMAC 実装値（再現可能）
- `X-App-Timestamp`: UNIX 秒。許容ウィンドウは `±300` 秒（厳密に 300）。
- `X-App-Once-Token`: 長さ 43 文字の URL-safe base64（128bit乱数）を各リクエストで要求。
- once-token 保存先:
  - 第一優先: Redis (`agent-neo:replay:v1`)
  - フォールバック: WP transient（`_agent_neo_replay_tokens`）
- TTL:
  - once-token: `600` 秒
  - リプレイ検知記録: `900` 秒（監査目的）
- 保存ポリシー:
  - 受信時は `event_signature` とキーIDを組み合わせ、**重複無効化を伴う排他保存**を実行する。
  - Redis: `SET <once-token> <val> NX EX 600`
  - WP transient fallback: `add(<once-token>, <val>, 600)`（`set` ではなく `add`）
  - **DB fallback 仕様（L4受入条件）:**
    - `agent_neo_replay_tokens` に `once_token + event_signature` の複合ユニーク制約を持つ `UNIQUE` を持たせる。
    - `INSERT ... ON CONFLICT DO NOTHING` で 1 クエリ書込を行う。
    - `affected_rows = 0` の場合は replay 判定（重複再送とみなす）として `409` を返却し、以後処理を中断する。
  - いずれの経路でも先着成功/競合判定をトランザクション外の追加問い合わせなしで確定し、重複再送は reject（409）して TOCTOU を残さない（CARRY-G2-007 / TB-05a / HMAC replay）。
  - TTL 経過または `agent_neo_replay_gc` による自然削除を前提とし、検証後の即時削除は実施しない。
- 署名検証:
  - `canonical_json = json_encode(body, JSON_UNESCAPED_SLASHES + JSON_UNESCAPED_UNICODE + key sort)`  
  - `payload = method|path|timestamp|once_token|body_hash`  
  - `X-App-Signature = base64(hmac_sha256(payload, active_key))`

### 5.2 鍵管理ライフサイクル（運用固定ルール）
- **ローテーション周期**: 90日。`inactive -> active -> retiring` の 3 段管理。
- **緊急無効化**:
  1. 鍵セットを即時無効化（`rotate --immediate`）  
  2. `F-01` / `F-02` 受信を 30 秒間 read-only 化  
  3. 監査ログに `hmac_key_revoked` を記録  
  4. `active` を新規発行し、運用チーム承認を待つまで監視ログ出力を強化
- **移行ウィンドウ（旧鍵受付）**:
  - `active` 変更時点の `T0` から `+900` 秒は旧鍵を受理。
  - 受入側責務として、**旧鍵は `F-01` のみ受理対象**とし、`F-02` では旧鍵拒否。`F-03/F-04/F-20` 等他 operation でも旧鍵は受理しない（権限逆流対策）。
  - **設計解決済み（L2→L3 設計転記で解消）**: 旧鍵受理スコープ（F-01 のみ）および F-02 以降での即時拒否は上記ルールとして本節で確定済みのため、CARRY-G2-008 は L4 carry を解除し設計確定扱いとする。
- **バックアップ/復旧**:
  - `rotation_reason`, `key_id`, `issued_at`, `retired_at` を監査ログ化
  - 旧鍵は 30 日保管後破棄

### 5.3 L4 Carry 受入条件（今回確定）
- **CARRY-G2-007（TB-05a）**: once-token は「DB 連携時に複合 UNIQUE + `INSERT ... ON CONFLICT DO NOTHING` で原子的に登録し、`affected_rows=0` なら replay 判定」を満たすこと。
- **CARRY-G2-009（slug 型 ID サニタイズ）**: slug 型 ID 生成時、`[a-z0-9-]` 以外を含む入力は `sanitize_slug()` で ASCII-only に変換する。変換後も `[a-z0-9-]` に一致しない（空・全非 ASCII 等）場合は UUIDv4 短縮形（先頭 12 文字）を fallback として使用する。CSSセレクタ・WPブロック属性・APIルートパラメータに非 ASCII slug を出力しないことを CI で保証すること。**主たる受入条件は `sanitize_slug()` の単体テスト**とし、「入力に非 ASCII を含む場合も出力が `^[a-z0-9-]+$` に一致すること」および「全非 ASCII 入力で fallback UUID が返ること」をアサートする。ファイル全体への静的 grep は使用しない（GNU `grep -E` は `\xNN` をバイトエスケープ解釈せず誤マッチを起こす）。必要な場合は `sanitize_slug()` の呼び出し点に限定し `LC_ALL=C grep -nP '[^\x00-\x7F]'` を用いること。
- **CARRY-G2-013（sanitize_title の直接参照禁止）**: `sanitize_title()` の結果を `section_id` / `cta_id` の内部機械参照値（DBカラム・API route・WP属性）へ直接使用しない。非 ASCII 入力はサニタイズ後の ASCII-only slug に変換してからのみ保存する。TC-017b（section_id 非 ASCII 入力テスト）で検証済みであることを確認すること。
- **CARRY-G2-014（ライセンス失敗時 2モード縮退）**: ライセンス失敗を2モードで分離し、L4 実装者が混同しないよう挙動を確定する。  
  - **(A) transient 失敗モード**（ライセンスサーバが 502 または 3 回連続失敗など一時的到達不能）: grace period **48 時間**（package-matrix PF-002 SSOT）を適用し、期間中は現スコープを **readonly 縮退**（計測・公開・閲覧は維持、`apply` 系操作はブロック）で継続する。grace 満了後に個人版スコープ（記事 CRUD のみ）へ自動降格する。法人版 write 系 endpoint（代表: `POST /pages/{id}/apply`）は grace 中は `503 / StandardResponse { success: false, data: null, meta: { request_id: "..." }, error: { code: "LICENSE_GRACE_PERIOD", message: "license server unreachable, write operations suspended", details: { reason: "license_unreachable", grace_remaining_hours: N } } }` を返す。  
  - **(B) invalid / 失効モード**（ライセンス自体が invalid・失効・未ライセンスとしてサーバが応答）: **即時 deny**（grace なし）。法人版機能を無効化し個人版スコープ（記事 CRUD のみ）へ縮退する。法人版 write 系 endpoint は `403 / StandardResponse { success: false, data: null, meta: { request_id: "..." }, error: { code: "FEATURE_DISABLED", message: "license invalid or expired", details: { reason: "license_invalid" } } }` を返す。  
  - bare JSON `{"code":"FEATURE_DISABLED","reason":"..."}` はエンベロープ非準拠のため使用しない。`error` オブジェクトのフィールドは openapi `StandardResponse.error` スキーマ（`code` / `message` / `details`）に従う。  
  - TC-011 拡張テストで両モードの縮退動作を検証すること（transient: grace 中 503 + grace 超過後 403 / invalid: 即時 403）。
- **CARRY-G2-017（TB-20）**: `catalog-update` の送信先（Automation SEO）向け URL は、以下の 4 ガードをすべて満たすこと: 1) スキーム `https` 固定（HTTP 禁止）、2) 送信先ホストを allowlist で固定、3) DNS 解決後 private/loopback/link-local/metadata IP を拒否、4) リダイレクト追従禁止。R-013（§10）が要求する rate limit / 署名検証 / SSRF guard / schema validation / error catalog / 監査ログの各ガードとの対応を実装時に確認すること。
- **CARRY-G2-025（TB-22）**: webhook 再送時は各 retry で URL 再解決を再実施し、再解決後 IP 判定（private/loopback/link-local/metadata 拒否）を再適用すること。
- **CARRY-G2-026（TB-24）**: 公開 snapshot/crawl-map での `section_id` は `section_id_public`（内部逆引き不可）へ、`cta_id` は `cta_id_public` へ変換して公開し、監査レイヤーのみ逆引きを許可すること。`data-model-ids.md §R-10` の公開 ID 変換仕様（`section_id_public` / `cta_id_public` 命名）に従って実装すること。

### 5.4 ADR-AP01: Application Password 採用判断（OAuth2との差分）
- ADR 形式の決定:  
  - **採択:** Application Password  
  - **除外:** OAuth2（現行WP運用時の配備コストが高く、監査境界の複線化を招くため）
- 技術選定比較:
  - OAuth2 は token endpoint, refresh, scope 定義、外部 IdP 連携が前提になり運用障壁が高い。
  - AP は WP 標準機構で設定/取り消し/監査がシンプルで、`agent-neo/v1` と相性が良い。
- 既知弱点対策（要件化）:
  - **スコープ制限の欠如** → endpoint allowlist、`mcp-tools.schema.json`同様の権限マトリクス、`REQ-F-010` ベースの package scope で制限。
  - **有効期限の固定** → 管理者主導で 90 日ローテーションを定期運用。
- AP 保存・管理（REQ-F-020同等）:
  - AP文字列は `openssl_encrypt` で機密保存し、管理画面は復号表示のみを許可。
  - `wp_users` 停止/削除時は AP を同時無効化。
  - TLS 終端層では認証ヘッダをログマスキングし、`X-App-*` は監査例外として除外。
- WPユーザー削除/無効化時の扱い:
  - 所有者と紐づく AP は即時停止。
  - 停止対象 API 経路は監査に `403` + `actor_change` で残す。
- AP失効アラート（monitor_ap_revoke）:
  - 発火条件:
    - AP保有 WP ユーザーの権限変更 / 無効化 / 削除（`monitor_ap_revoke`）  
    - 短時間の連続認証失敗（短時間窓で閾値到達）
    - 想定外 IP/ASN からの AP 利用
    - AP 新規発行イベント
  - 監視・対応:
    - 異常検知時は `monitor_ap_revoke` を即時発火し、AP を該当ユーザー分即時失効
    - 通知先は運用チャネル（`ops-alert`）とセキュリティチャネル（`security-alert`）へ 1 秒以内配信
    - `actor_change` と関連 AP イベント ID を監査ログで突合可能に保持

---

## 6. STRIDE × DREAD（主要脅威）

> DREAD は `D,R,E,R,D`（Damage/Reproducibility/Exploitability/Affected users/Discoverability）を 1–10 で採点。  
> **E 採点は現状実装状態でのみ評価**する（未実装対策（例: WAF/rate limit）は加点しない）。

| ID | 対象（境界/フロー） | STRIDE | 脅威シナリオ | D/R/E/Dis/A | 総計 | 対策 | 残留リスク | 所有者 | 実施時期 |
|---|---|---|---|---|---:|---|---|---|---|
| TB-01 | WP内部 ↔ Core Plugin | Spoofing | ログイン失効ユーザーがセッション固定化で write 可能になる | 7/6/4/7/6 | 30 | 期限付きnonce、capability厳格化、同一ユーザー再認証監査 | 中（管理者端末侵害時） | Security Lead | L3 |
| TB-02 | WP内部 ↔ Core Plugin | Tampering | Gutenberg/フォーム入力から XSS/破損 JSON が DB に混入 | 8/8/6/8/5 | 35 | schema validation、sanitize、block allowlist、diff review | 中（未対策プラグイン併用時） | Core Plugin Team | L3 |
| TB-03 | AGENT NEO Core Plugin | Information Disclosure | 設定変更履歴にシークレット値が平文保存される | 9/5/3/6/8 | 31 | 秘匿値マスキング、secret vault化、監査ログからの機密除去 | 中（権限付与漏れ時） | Plugin Owner | L4 |
| TB-04 | Core Plugin ↔ 外部 | Repudiation | 重要変更が actor/path/timestamp で追跡できない | 6/5/2/6/6 | 25 | actor_id、request_id、署名済み監査ログ、ログ改ざん防止 | 低（証跡保守不具合） | Security Lead | L4 |
| TB-05 | Core Plugin ↔ Automation SEO (F-01) | Spoofing | catalog-update 送信元偽装 | 8/7/4/8/7 | 34 | HMAC検証、sender_id allowlist、TLSピン止め（運用方針） | 低〜中（鍵漏えい時） | SRE/Integration | L3 |
| TB-05a | Core Plugin ↔ Automation SEO (F-01) | Spoofing | HMAC鍵漏洩で攻撃者が `catalog-update` 全体を偽造実行（記事構成/Blueprint を一括改ざん） | 10/8/6/9/8 | 41 | 鍵ローテーション + 緊急停止 + 旧鍵拒否 + 攻撃時は `F-01` 受信 read-only化 | 高（即時）→中（対応後） | SRE/Integration/Security | L3 |
| TB-06 | Core Plugin ↔ Automation SEO (F-01) | Tampering | 変更イベントの JSON を改ざんし誤同期 | 8/8/6/8/5 | 35 | JSON Schema + payload hash + 署名再計算一致検証（`expected_signature` と再計算値を比較） | 中（旧バージョン差分不整合） | Security Lead | L4 |
| TB-07 | Core Plugin ↔ Automation SEO (F-01) | DoS | push flood により job queue 飽和 | 7/8/6/6/4 | 31 | rate limit、キュー長制限、指数バックオフ、DLQ | 中（大量汎用 bot 攻撃） | Infra/Operations | L4 |
| TB-08 | Automation SEO → `agent-neo/v1` PATCH (F-02) | Spoofing | aseo/v1 資格情報盗難で偽 patch 実行 | 9/7/4/9/8 | 37 | Application Password 固定化、IP allowlist、監査連携 | 中（端末侵害時） | API Owner | L3 |
| TB-08a | Automation SEO → `agent-neo/v1` PATCH (F-02) | Tampering | 管理者アカウント侵害により AP が差替えされ、攻撃者のpatchを正規経路に偽装 | 10/7/7/9/8 | 41 | 管理者AP自動監査、管理者変更時の即時セッション失効、AP失効アラート、定期ローテーション | 高（管理者端末侵害） | Security Lead | L3 |
| TB-09 | Automation SEO → `agent-neo/v1` (F-02) | Tampering | patch 内容が想定外フィールドへ書き込む（AI判定ロジック混入） | 8/8/5/8/6 | 35 | strict schema validation、diff review、AI判定ロジックは拒否ルールで除外 | 中（API仕様不一致） | API Owner | L3/L4 |
| TB-10 | Automation SEO → `agent-neo/v1` (F-02) | Repudiation | patch 適用結果の経路紐付け欠如 | 5/6/4/5/6 | 26 | req-id、差分 hash、監査テーブル（経路/署名/アクションID） | 低（証跡欠損） | Security Lead | L4 |
| TB-11 | 外部AIエディタ → `agent-neo/v1` / `wp-v2` (F-03/F-04) | Spoofing | 外部エディタが許可経路を偽装し構造変更を試行 | 9/7/4/8/8 | 36 | `/wp-json/` discovery では公開許可 namespace 以外を 404（`agent-neo/v1`, `aseo/v1`, `wp/v2`のみ列挙）、content-type が `application/json` の場合のみ write 受け付け、multipart は `415`、外部 AI/外部エディタからの write は**全経路 403（例外なし）**。正規 write = `aseo/v1`（Automation SEO）+ `agent-neo/v1`（テーマ自身）のみ（REQ-F-042 / ADR-024: REQ-F-043 廃止 2026-06-18）、`wp/v2` への write ルートは固定拒否 |
| TB-12 | 外部AIエディタ → `agent-neo/v1` / `wp/v2` (F-03/F-04) | Tampering | wp/v2 経由でHTML構造やメタを上書き | 8/8/5/9/7 | 37 | `wp/v2` 構造変更系 write の明示拒否、`wp/v2` は body schema 検査ログで監査、許可経路との差分ハッシュ監査。Open Editor Bridge Plugin 廃止（ADR-024 / REQ-F-043 廃止 2026-06-18）により bridge 例外は不要化・削除済み。外部 AI からの write attack surface は消滅（例外経路ゼロ） |
| TB-13 | 外部AIエディタ → `agent-neo/v1` | Information Disclosure | エラーメッセージに internal endpoint / stack / token が流出 | 6/6/3/5/9 | 29 | 汎用エラー共通化、トレースIDのみ返却、5xx 詳細非表示 | 低 | Documentation/Support | L3 |
| TB-14 | API 仕様/契約境界 | Repudiation | 破壊的変更時に後方互換性判断不能 | 6/4/3/6/7 | 26 | openapi contract test、schema diff、破壊変更は6ヶ月併走運用 | 中（運用逸脱） | API Owner | L4/L6 |
| TB-15 | Theme側 ↔ Automation SEO（AI境界） | Tampering | 予期せぬファイルで判断ロジック（variant生成等）を含めて配布 | 9/5/4/8/4 | 30 | REQ-NF-025に基づき実装禁止領域を固定、AST/grep静的検査（ACC-NF-015） | 中（外部ライブラリ混入） | Security Lead | L4 |
| TB-16 | Theme側 ↔ Automation SEO（AI境界） | Information Disclosure | 配布前提の Theme から `agent-neo/v1` endpoint名、param 名、`security-baseline.schema`、once-token/署名生成ロジックが推定される | 7/4/5/6/6 | 28 | AI判断/署名生成ロジックは Core Plugin/サーバー側固定、テーマ配布物には hook/API ハンドラとID/計測のみを残置 | 低〜中（配布時に発生） | Docs/Architecture | L4 |
| TB-17 | Core Plugin 公開API | DoS | public snapshot/tracking API を短期高頻度で叩かれ表示劣化 | 6/9/5/7/6 | 33 | レート制御、キャッシュ、bot filter、重要資源分離 | 中（大規模 bot） | Infra/Operations | L4/L6 |
| TB-18 | Core Plugin | Elevation of Privilege | package制御バイパスで法人系機能を個人権限から実行 | 9/6/3/9/5 | 32 | package gate + capability + 実行権限レイヤ分離、受注実行テスト | 低〜中（管理者誤設定） | Product Security | L4 |
| TB-18a | Core Plugin | Elevation of Privilege | 外部ライセンスサーバ障害時に法人機能へ一時昇格（grace period の悪用） | 9/7/4/9/6 | 35 | ライセンス失敗を2モードで分離: (1) **transient 失敗**（サーバ到達不能・502・3回連続）→ grace period 48h 中は readonly 縮退（apply ブロック）、grace 満了後に個人版スコープへ降格。(2) **invalid / 失効**（ライセンス自体が無効・失効・未ライセンス）→ 即時 deny、個人版スコープへ縮退（grace なし）。fail-open は絶対禁止。grace 48h 値は package-matrix PF-002 を SSOT とする | 高（障害同時時）→低（L4後） | Product Security | L3/L4 |
| TB-19 | MCP local route | Privilege Escension / Tampering | MCP クライアントがマルウェアにより乗っ取られ、危険 tool で apply（dryRunを省略）を実行 | 10/7/6/9/8 | 40 | local MCP client は署名付き registration、実行 tool は `mcp-tools.schema.json` で risk class=apply-only の場合は明示承認必須、dryRun必須、apply 前最終差分ハッシュ確認、将来 remote MCP では mTLS + session-bound allowlist |
| TB-20 | F-01/F-02 | SSRF | `F-02` の payload に任意外部URL（画像/HTML）を指定され、Automation SEO→AGENT NEO 側で内向きアクセスを誘発 | 8/8/6/6/7 | 35 | URL バリデータを 4 つ同時に適用: 1) スキームは `https` のみ（HTTP 禁止）、2) 送信先ホストを allowlist で固定（`catalog-update` 送信先含む）、3) DNS 解決後に private/loopback/link-local/metadata IP を拒否、4) リダイレクト追従禁止（redirect follow 禁止）。（CARRY-G2-017） | 中（対策後） | Security Lead | L4 |
| TB-21 | Migration plugin | SSRF | migration-plugin が外部WPを無制限 pull し、管理端末/社内向けIPへの接続を行う | 8/7/5/7/7 | 34 | migration 先 URL は allowlist 配下のみ、timeout 上限 3 秒、redirect 上限 2 回 |
| TB-22 | Webhook/Outbound | SSRF | webhook 配信先URL が internal IP（`169.254.*`/`127.0.0.0/8`/RFC1918）へ到達する | 9/8/5/8/8 | 38 | 配信 URL は retry 毎に `resolve + denylist` を再実行し、リトライごとに再解決結果で `private IP / loopback / link-local / metadata` 再拒否を行う。`content-length` 上限 1MB、`content-type allowlist`、timeout 3s（CARRY-G2-025）。 | 中（対策後） | Security Lead | L4 |
| TB-23 | 計測API | Repudiation | bot/spam により tracking / A-B API が汚染され、結果が捏造される（variant_id 偽装含む） | 7/7/6/6/5 | 31 | トークン付与イベントのみ採用、variant_id/section_id の整合検証、bot filter、重複検知、署名付き集計 |
| TB-24 | Core Plugin ↔ 公開面（snapshot/crawl-map） | Information Disclosure | 公開 snapshot/crawl-map から `section_id` / `cta_id` / `variant_id` が平文取得され、競合が公開ページ構造を継続クロールして A/B テストの variant 構成・CTA 配置を推定できる | 9/5/4/6/5 | 29 | `GET /public/pages/{id}/snapshot` と `GET /public/crawl-map` は `variant_id` を公開レスポンスから除外し、`section_id`/`cta_id` は内部 ID と分離した公開用 opaque ID（`section_id_public`, `cta_id_public`）へ**常時変換**して返却する。`section_id_public` の逆引きは監査レイヤーのみ許可、認証済み管理 API でのみ内部 ID を参照可能とする。LLMO(ADR-013/015)要件で `opaque` ID は引用性を維持。該当設計は CARRY-G2-026。 | 低（対策後） | Security Lead | L4 |

### 6.1 DREAD Exploitability 根拠（現状実装ベース）
- TB-01: E=4（既存セッション管理＋nonceで一般的な乗っ取りが必要）
- TB-02: E=6（悪意あるコンテンツ投稿を通じた攻撃経路がある）
- TB-03: E=3（監査ログ保護は実装済みだが運用監査未完全）
- TB-04: E=2（内部監査未読取りでは再現が難しい）
- TB-05: E=4（鍵未漏洩時の経路は限定的）
- TB-05a: E=6（鍵流出時は偽装送信者として `catalog-update` の再発行を短時間で実施可能）
- TB-06: E=6（署名再計算は実装可能だが payload 取得があれば改ざん検知なしでは成立）
- TB-07: E=6（DoS 攻撃は帯域とリクエスト量の準備があれば成立）
- TB-08: E=4（AP 情報なしでは困難、窃取後のみ成立）
- TB-08a: E=7（管理者端末侵害や権限奪取が必要）
  - TB-05a R=8 > TB-08a R=7 は、TB-05a は HMAC 鍵漏洩で `catalog-update` 全体を一度に偽造できるのに対し、TB-08a は対象 AP の管理者アカウント侵害に依存し影響範囲が限定されるため。
- TB-09: E=5（payload 仕様外検知ロジックで緩和済み）
- TB-10: E=4（監査欠損は実装未整備領域）
- TB-11: E=4（経路固定＋namespace制御があるため追加侵入が必要）
- TB-12: E=5（wp/v2 側を経由した上書きのみ成立）
- TB-13: E=3（内部情報流出を誘発するためには例外経路の存在が必要）
- TB-14: E=3（破壊的変更は公開経路に出ない設計だが既存差分管理が不十分）
- TB-15: E=4（外部ライブラリ混入は supply chain 依存経路が先行）
- TB-16: E=5（配布物公開で逆アセンブルは可能だが実装制御済み）
- TB-17: E=5（公開APIへの高頻度アクセスは bot 利用で成立）
- TB-18: E=3（package 管理経路に障壁あり）
- TB-18a: E=4（license API 故障時のフェイルロジック誤設定で成立）
- TB-19: E=6（local endpoint 権限を得た場合に成立）
- TB-20: E=6（payload 送信権限を持つ攻撃者がURLを注入）
- TB-21: E=5（migration 実行権限がないと成立しない）
- TB-22: E=5（webhook の受取先を編集できる場合に成立）
- TB-23: E=6（tracking 送信権限/公開バッチからの不正送信が成立）
- TB-24: E=4（公開 API を読む能力があれば成立）

---

## 7. 残留リスク一覧（暫定 vs L4後）

| TB | 脅威 | 実装前（暫定） | 実装後（L4） | 実施時期 |
|---|---|---|---|---|
| TB-02 | Core Plugin ↔ Automation SEO (F-01) | MEDIUM（JSON混入経路） | MEDIUM（schema + payload hash で抑止） | L4 |
| TB-05 | Core Plugin ↔ Automation SEO (F-01) | HIGH（送信元偽装） | MEDIUM（HMAC + sender allowlist） | L4 |
| TB-05a | HMAC鍵漏洩時の `catalog-update` 偽造 | HIGH（即応体制未整備） | MEDIUM（鍵ローテーション/即時無効化手順） | L4 |
| TB-06 | 変更イベント JSON 改ざん | MEDIUM（監視対象） | MEDIUM（署名再計算 + schema 検証） | L4 |
| TB-08 | AP 偽装で patch 実行 | MEDIUM（AP盗難） | MEDIUM（AP固定化 + 監査） | L3 |
| TB-09 | patch 内容の不正改ざん | MEDIUM（許可外フィールド） | MEDIUM（schema/hardening） | L4 |
| TB-11 | 外部AIエディタ経路偽装 | MEDIUM（discovery経路） | **LOW（namespace固定化 + write全経路拒否 + bridge 廃止で attack surface 消滅 / ADR-024 2026-06-18）** | L4 |
| TB-12 | wp/v2 経由で構造上書き | MEDIUM（path固定で悪用可能） | **LOW（wp/v2 write 固定拒否 + bridge 例外削除により外部 write 経路がゼロに / ADR-024 2026-06-18）** | L4 |
| TB-17 | 公開API DoS | MEDIUM（既知） | MEDIUM（レート/キャッシュ継続） | L4 |
| TB-18a | ライセンス障害時昇格 | HIGH（実装前は仕様不足） | LOW（deny→個人版縮退） | L4 |
| TB-19 | MCP 権限制御（remote 対応 carry） | HIGH（ローカル依存リスク） | MEDIUM（local MCP 実装後） / `Post-L4 carry`（remote MCP） | L4 |
| TB-20 | URL 注入 SSRF | MEDIUM（payload混入） | MEDIUM（DNS/URL 制御） | L4 |
| TB-21 | migration plugin 無制限 pull | HIGH（実装前） | MEDIUM（allowlist + timeout） | L4 |
| TB-22 | webhook 送信先への SSRF | HIGH（送信先改ざん） | MEDIUM（pre-resolve + private IP deny） | L4 |
| TB-24 | 公開snapshot/crawl-map 情報露出 | HIGH（競合が変化傾向を復元可能） | 低（対策後） | L4 |

---

## 8. AIロジック分離の脅威観点（REQ-NF-025）

- **前提**: AGENT NEO Theme/Coreは「実行基盤」「AIフック」「計測 ID 生成」のみを担当し、AI判断ロジックは持たない。
- **Threat Check（必要）**:
  - テーマ内に `variant_generate` / `statistical_significance` / `cv_audit` / `bias_pattern_apply` などの判断ロジック候補が存在しない。
  - `AST/grep` 監査では `外部HTTP呼び出し` の先頭を `agent-neo/v1`, `wp/v2`, `aseo/v1` 外で許可しない。
  - AIエディタの出力は `dryRun→diffReview→apply` を経て最終反映。
- **残留リスク**:
  - 外部追加アダプタ経由で AI 判定に関する新規ロジックが混入すると、GPL 配布下で推定不能化。`TB-15/TB-16` の継続監査で軽減。

---

## 9. 受入チェック（G2完了条件）

1. 資産一覧、信頼境界、DFD、STRIDE×DREAD を本文書に収載
2. 主要フロー F-01/F-02/F-03/F-06 を脅威ベースで評価（残留リスク付き）
3. 認証方式を実装値まで明文化（候補を残さない）
4. REQ-F-042（外部AI/外部エディタからの write は全経路 403 / 正規 write = `agent-neo/v1`+`aseo/v1` のみ）を採択。REQ-F-043（Open Editor Bridge Plugin）は ADR-024 により 2026-06-18 廃止。bridge 経由 write 例外は**存在しない**
5. REQ-NF-025 に基づく AI 判定ロジック分離を監査観点まで反映
6. HMAC replay 防止の実装値（`timestamp`、once-token、TTL、保存先）を記載
7. LICENSE 障害時 `deny` のフォールバックを明示し、個人版へ縮退
8. MCP 境界、`mcp-tools.schema.json` 連携、DB/監査残留リスクを含めて統合

---

## 10. P2 carry（時間があれば次実施）

- WP CLI 経路の脅威は次回実装で `CLI 実行権限`, `--allow-root`/`sudo` 経路を追加監査する。  
- PrivacyConsentGuard 外部送信の PII Information Disclosure は `privacy-retention-policy` と照合して carry。  
- 全 STRIDE 行の `残留リスク` 集約監査を別セクションで自動生成レポート化。  
- `public snapshot` / `crawl-map` の DoS 値をトラフィック別に追加（例: 1 秒あたり 60 rps でキャッシュヒット率 85% 以下時の degraded mode 発火）  
- OWASP A06（vuln/依存）を `sbom.cdx.json` + 外部ライブラリ脆弱性観点で `docs/security` 側の追跡テーブルに接続。
- TB-19 remote MCP: `local MCP` 固定のうち carry を残し、remote MCP は Post-L4 で実装計画化（mTLS + session-bound allowlist + operator approval を追加）。  

---

## 11. 作成ファイルと要点（最終報告用）

- 作成ファイル: `docs/security/threat-model.md`
- 主要脅威要点:
  - 外部サーバー通信は **HMAC + timestamp + replay防止**を固定。timestamp は **±300秒**、once-token TTL **600秒**、保存先は **Redis or WP transient**。
  - 外部 AI/外部エディタからの write は**全経路 403（例外なし）**（REQ-F-042 / ADR-024: REQ-F-043 廃止 2026-06-18）。正規 write = `agent-neo/v1`（テーマ自身）+ `aseo/v1`（Automation SEO）の 2 経路のみ。`wp/v2` は固定 403。bridge 例外は不要化・削除済み。
  - `agent-neo/v1` 書き込みは **nonce + capability + DREAD採点済みログ**を前提に固定。
  - 外部 API 連携認証は **Application Password**（OAuth2 は採用対象外）。既知弱点は scope/expiry で、対策を明文化。
  - AI判断ロジックは **Automation SEO 側のみ**固定。Theme/配布物から署名/AI判定ロジックを分離。
