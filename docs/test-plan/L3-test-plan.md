# L3 テスト計画（SSOT）

本書は `docs/design/L3-detailed-design.md` の §5「テスト設計」を分離して SSOT 化したものです。  
`L3-detailed-design.md` では本章へのリンク先としてのみ参照されます。

## 1. 対象 / 参照

- 対象: `REQ-F-001` 〜 `REQ-F-042`、`REQ-NF-001` 〜 `REQ-NF-037` の L3 受入範囲
- 正本参照:
  - `docs/design/api-catalog.md`
  - `docs/design/L2-design.md` §8.9
  - `docs/api/openapi.yaml`
  - `automation SEO D-PLUGIN-CONTRACT §17`（`catalog-update`）

## 2. テスト戦略

### 2.1 テスト戦略表

| レベル | 対象 | ツール | カバレッジ目標 |
|---|---|---|---|
| Unit | 差分生成、idempotency/キー正規化、schema validate、gating helpers | PHPUnit | 主要ロジック 80% |
| Integration | REST 送受信、option/meta/CPT、ジョブ遷移、outbox/retry 遷移 | PHPUnit + wp-cli | 主要経路 100% |
| E2E | dry-run→apply→rollback、preview→apply、catalog-update 受信一連 | Playwright（管理画面） | P0 シナリオ 100% |
| Contract | API / Schema / セキュリティ / 外部連携 | PHPUnit + custom harness | 契約 endpoint 100% |
| Static/Lint | ThemeCheck / PHPCS-WPCS / axe / Lighthouse / a11y / RTL lint | CI（Theme Check / PHPCS / axe / Lighthouse） | P0 を全件通過 |

### 2.2 テストピラミッド（方針）

- Unit: 45%
- Integration: 30%
- E2E: 15%
- Contract/Security/Static 合算: 10%

統合テスト失敗時は Unit → Integration → E2E の順で早期に原因切り分けし、E2E は `apply/rollback` などの状態遷移を集中検証する。

### 2.3 品質ゲート（共通）

- a11y: `axe` / `WCAG2.2 AA`（fail on violations）
- i18n/RTL: `i18n-profile` 準拠（textdomain / RTL 崩れ）
- 性能: `LCP <= 2.5s`, `INP <= 200ms`, `CLS <= 0.1`
- バンドル: 初期 CSS `<=20KB`, 初期 JS `<=70KB`（gzip）
- 外部資産: render-blocking `3rd party = 0`

## 3. カテゴリ別テスト観点

### 3.1 Contract テスト（catalog-update, automation SEO §17 準拠）

| TestID | endpoint | 種別 | 優先度 | 受入観点 |
|---|---|---|---|---|
| CAT-001 | catalog-update | Contract | P0 | `event_kind=block_registered` で `received=true` / `event_id` / `deduplicated=false` / `next_action=none`（4フィールドのみ、§17.11） |
| CAT-002 | catalog-update | Contract | P0 | `event_kind=block_unregistered` で `received=true` / `event_id` / `deduplicated=false` / `next_action=none`（4フィールドのみ、§17.11） |
| CAT-003 | catalog-update | Contract | P0 | `event_kind=template_updated` で `received=true` / `event_id` / `deduplicated=false` / `next_action=none`（4フィールドのみ、§17.11） |
| CAT-004 | catalog-update | Contract | P0 | `event_kind=theme_token_updated` で `received=true` / `event_id` / `deduplicated=false` / `next_action=none`（4フィールドのみ、§17.11） |
| CAT-005 | catalog-update | Contract | P0 | 同一 `event_id` 再送時 `received=true` / `event_id` / `deduplicated=true` / `next_action=none`（4フィールドのみ、§17.11） |
| CAT-006 | catalog-update | Contract | P0 | `event_kind` 欠落時に `400 VALIDATION_ERROR` |
| CAT-007 | catalog-update | Contract | P0 | `5xx` / `429` / `timeout` 応答時、再試行間隔が初回 `1s`、指数 `2^n`、最大5回、各回 ±10% jitter |
| CAT-008 | catalog-update | Contract | P0 | 未インストール時に `409 AGENT_NEO_NOT_INSTALLED` |
| CAT-009 | catalog-update | Contract | P1 | `400`/`401`/`409` 受信時は再試行なし（ただし `429 RATE_LIMITED` は再試行対象） |

※ CAT-001〜005 の4フィールド仕様は DC-F-001、CAT-007〜009 の再試行定義は DC-F-002、かつ §17.11（CARRY-G2-006 含む）準拠。

### 3.2 API/状態遷移テスト

| TC | endpoint / 機能 | 種別 | 優先度 | 受入観点 |
|---|---|---|---|---|
| TC-001 | `/actions/dry-run` | API | P1 | patch block の dry-run 成功で `diff_hash` を返却 |
| TC-002 | `/actions/apply`（正常） | API | P0 | dry-run 直後に apply 成功し rollback_point を保存 |
| TC-003 | `/actions/apply`（異常） | API | P0 | `diff_hash` 不一致時 `412` で拒否 |
| TC-004 | `/actions/apply`（冪等） | API | P1 | 同一 `diff_hash` 再送で no-op（副作用なし） |
| TC-005 | `/pages/{id}/apply` | API | P0 | 2シナリオ: ①`preview` 昇格経路(CARRY-016)では `from_preview_token` 未指定時に `403` 相当で拒否、②通常 apply（非 preview）では `from_preview_token` なしでも正常完了 |
| TC-006 | `/pages/{id}/rollback` | API | P0 | rollback_point 不在時 `410`、既存履歴の復元成功 |
| TC-007 | `/posts/{id}/blocks/{block_id}` | API | P0 | PATCH idempotency key 未再送時のみ更新を実行 |
| TC-008 | `/posts/{id}/sections/{section_id}/edit` | API | P1 | 参照のみの section 変更で他 section 不変 |
| TC-009 | `/tracking/event` | API | P0 | 必須項目未送付時 `400` |
| TC-010 | `/tracking/event` | Security | P0 | 署名不正/nonce 不正時 `401` |
| TC-011 | `/license/validate` および `/pages/{id}/apply` | API | P0 | 2モード受入条件（TB-18a / deny-first）: ①**invalid/失効モード（即時deny）**: ライセンスキーが無効・失効の場合、`/license/validate` が `readonly=true` を返し、法人機能 write endpoint（apply 等）が即時 `403 FEATURE_DISABLED` で拒否され個人版 CRUD のみ通過すること。②**transient モード（grace 中）**: ライセンスサーバ 502 / 3 回連続失敗の場合、48h grace 中は readonly 縮退を維持し write 系 endpoint が `503 LICENSE_GRACE_PERIOD` を返すこと（readonly GET は継続通過）。grace 満了後は `403 FEATURE_DISABLED` で個人版縮退に移行すること。invalid（確定的失効→即時 403）と transient（サーバ障害→grace 中 503→grace 満了後 403）は別モードとして区別して P0 検証すること |
| TC-012 | `/license/validate` | API | P1 | Fallback が既存テーマに安全に適用される |
| TC-013 | `/tracking/context` | API | P1 | plugin 署名整合、`tracking/context` 受信スキーマ照合 |
| TC-014 | SEO coexistence | Function | P1 | 重複 meta / JSON-LD 検出時に warning を必須化 |
| TC-015 | SEO coexistence | E2E | P1 | 既存テーマ互換で既定メタ/構造化データの二重挿入を検知 |
| TC-016 | a11y gate | Gate | P0 | axe でWCAG2.2 AA 以上、失敗があるとCI fail |
| TC-017a | i18n/RTL gate | Gate | P1 | RTL/i18n 違反があると CI fail |
| TC-017b | sanitize_slug / sanitize_title 分離 | Unit | P1 | ①`sanitize_slug()` が非ASCII入力（例: "SEO 基礎"・"日本語のみ"）を `[a-z0-9-]` 内部 slug へ正規化することを単体テストで検証する（全非ASCII入力はフォールバックで UUID 短縮形を返すこと、R-09a / CARRY-G2-009 準拠）。②`sanitize_title()` はWP標準の表示用関数であり `[a-z0-9-]` を保証しないため、その戻り値を `section_id`・`cta_id` の DB カラム・API route パラメータ・WP ブロック属性・CSS セレクタへ直接使わないことを確認する（CARRY-G2-013 準拠）。すなわち「内部ID = `sanitize_slug()` 出力」「`sanitize_title()` は表示用（ログ・管理画面ラベル）であり `section_id` には不使用」の分離を単体テストで証明すること |
| TC-018 | Performance gate | Gate | P1 | LCP/INP/CLS、初期CSS/JS、render-blocking を超過すると CI fail |
| TC-019 | apply/rollback フロー | E2E | P0 | dry-run → apply → rollback 正常系、監査ログと状態更新が一致 |
| TC-020 | apply/rollback 異常系 | E2E | P0 | rollback 失敗時に差し戻し済み状態を破綻させず、再試行可能 |
| TC-021 | diff_hash 整合性 | API | P0 | dry-run/apply で同一 `diff_hash` 必須、変更内容と差分が一致 |
| TC-022 | SEO 共存（外部/内部） | Security | P2 | 重複/競合検知で apply を中断し、警告レベルに集約 |
| TC-023a | SSRF | Security | P0 | 初回検証時に private/loopback/link-local/metadata アクセスを拒否し、redirect は追従しない |
| TC-023b | SSRF | Security | P0 | 再試行時に URL を再解決し、再解決先が private/loopback/link-local/metadata であれば再拒否 |
| TC-024 | once-token replay | Security | P0 | once-token replay が atomic INSERT で再送除外 |
| TC-025 | slug / selector 安全性 | Security | P1 | 非ASCII入力を `sanitize_slug()` が `[a-z0-9-]` へ正規化し、CSSセレクタ / ブロック属性に非ASCII slug を出力しないことを単体テストで検証（ログ化のみでの合格は不可） |
| TC-026 | public snapshot | Security | P1 | `public snapshot` が内部 ID を返さない（公開ID変換のみ） |
| TC-027 | SBOM gate | Security | P1 | `sbom.cdx.json` が release build で生成され、依存元 / ライセンス / checksum 検証が PASS し、Theme Review 提出前に artifact が確定していること |
| TC-028 | Lighthouse CI render-blocking / consent | Gate | P1 | Lighthouse CI で render-blocking third-party = 0 を検証し、consent 前後のタグ発火タイムラインを確認する。同意後 `document.createElement('script')` による動的注入は FCP 前 parser-blocking に該当しないため、GA4/GTM consent mode v2 準拠と合わせて検証する |
| TC-029 | lp-blueprint セクション整合 | Contract | P1 | `lp-blueprint` のセクション定義が `api-catalog` および L2 設計と一致すること（12セクション整合確認） |
| TC-030 | bridge-profile safe_apply_state | API | P1 | 既存テーマ環境で `GET /automation-seo/bridge-profile` が `safe_apply_state=preview-only` を返却し、write 系 endpoint 呼び出しが拒否されること（ADR-019 準拠） |

## 4. P0 / P1 / P2 分類

- P0: CAT-001〜CAT-008、TC-002、TC-003、TC-005、TC-006、TC-007、TC-009、TC-010、TC-011、TC-016、TC-019、TC-020、TC-021、TC-023a、TC-023b、TC-024
- P1: CAT-009、TC-001、TC-004、TC-008、TC-012、TC-013、TC-014、TC-015、TC-017a、TC-017b、TC-018、TC-025、TC-026、TC-027、TC-028、TC-029、TC-030
- P2: TC-022（監査ログ整合と運用連携）

※ 優先度整合・受入条件は INT-002 / DC-F-002 に基づき `§4` 一覧を `TC` テーブルに合わせて更新済み（CAT-009 は P1）。TC-017 を TC-017a/TC-017b に分割。TC-027〜TC-030 を P1 で追加。

## 5. L4 carry（007/009/011/012/013/015/017/021/025/026/028）検証観点（設計解決済みL4検証: 006/014）

本項目は L4 実装で満たすべき受入テストを明記する。

| carry-id | 検証観点（L4） | TC 参照 | 根拠 |
|---|---|---|---|
| 006 | AGENT-NEO producer の catalog-update 再送が initial_backoff=1s・指数 2^n・最大5回・429含む retry に従うことを単体テストで検証する | CAT-007 | `docs/design/api-catalog.md` §17.11 |
| 007 | once-token replay の atomic INSERT と重複排除 | TC-024 | `docs/reviews/G2-carry-register.md` |
| 009 | slug / selector の非ASCII耐性（`sanitize_slug()` が `[a-z0-9-]` へ正規化し、CSSセレクタ / ブロック属性への非ASCII出力がないことを単体テストで検証） | TC-025 | `docs/security/threat-model.md` |
| 011 | Lighthouse CI で render-blocking third-party = 0 を検証し、consent 前後のタグ発火タイムラインを確認する | TC-028 | `docs/design/L2-design.md` §8.9 |
| 012 | `lp-blueprint` のセクション定義が `api-catalog` と L2 で一致（12セクション整合） | TC-029 | `docs/design/api-catalog.md` |
| 013 | i18n/RTL 違反時の CI fail（TC-017a）、`sanitize_slug()` による非ASCII入力の `[a-z0-9-]` 正規化、および `sanitize_title()` 戻り値を `section_id` に直接使わない分離（TC-017b / R-09a / CARRY-G2-009 / CARRY-G2-013） | TC-017b | `docs/design/data-model-ids.md` §R-09a |
| 014 | 2モード検証（TB-18a 準拠 / TC-011 参照）: ①**invalid/失効モード**: ライセンスキーが無効・失効の場合、`/license/validate` が `readonly=true` を返し、法人機能 write endpoint が即時 `403 FEATURE_DISABLED` で拒否され個人版 CRUD のみ通過すること。②**transient モード**: ライセンスサーバ 502 / 3 回連続失敗の場合、48h grace 中は readonly 縮退を維持し write 系 endpoint が `503 LICENSE_GRACE_PERIOD` を返すこと（readonly GET は継続通過）。grace 満了後は `403 FEATURE_DISABLED` で個人版縮退に移行すること。invalid（確定的失効→即時 403）と transient（サーバ障害→grace 中 503→grace 満了後 403）は別モードとして区別して単体テストで P0 検証すること | TC-011 | `docs/design/L2-design.md` |
| 015 | SBOM gate（release build 時に `sbom.cdx.json` 作成）で依存元 / ライセンス / checksum 検証が PASS し、Theme Review 提出前に artifact を確定する | TC-027 | `docs/design/L2-design.md` |
| 017 | URL バリデーション（SSRF）、リダイレクト拒否、初回 private/loopback/link-local/metadata 判定 | TC-023a | `docs/security/threat-model.md` |
| 021 | 既存テーマで `GET /automation-seo/bridge-profile` が `safe_apply_state=preview-only` を返却し、write 系 endpoint 呼び出しが拒否される（ADR-019 準拠） | TC-030 | `docs/design/api-catalog.md` |
| 025 | 送信再試行時の re-resolve + private 判定（再解決先が private/loopback/link-local/metadata なら再拒否） | TC-023b | `docs/design/api-catalog.md` |
| 026 | public snapshot に内部 ID を漏らさない（公開 ID 変換のみ） | TC-026 | `docs/design/L2-design.md` |
| 028 | 既存テーマ write 例外条件・preview-only enforcement（既存テーマ環境で write 系 endpoint が拒否され、`safe_apply_state=preview-only` が返却されること） | TC-030 | `docs/design/L3-WBS.md`（T-010 / .2） |

## 6. 受入時チェックリスト

- すべての P0 が完了していること
- Contract テストで CAT-001〜CAT-009 が green
- a11y / i18n / perf の 3 ゲートが green
- `diff_hash` / `event_id` / `once-token` に関する整合不具合が残存しないこと
- `apply/rollback` の正常・異常・冪等性が検証済みであること

## 7. 変更履歴（changelog）

| 日付 | 修正者 | 内容 |
|---|---|---|
| 2026-06-15 | L4着手前敵対検証修正 | TC-017 → TC-017a / TC-017b に分割（i18n/RTL gate + sanitize_title 分離単体テスト）。TC-025 受入条件を強化（`sanitize_slug()` `[a-z0-9-]` 正規化・単体テスト必須、ログ化のみ合格不可）。TC-027（SBOM gate / P1）を新設。TC-028（Lighthouse CI render-blocking / consent / P1）新設。TC-029（lp-blueprint 12セクション整合 / P1）新設。TC-030（bridge-profile safe_apply_state / ADR-019準拠 / P1）新設。TC-011 に P0 シナリオ追加（ライセンスサーバ 502 / deny-first / TB-18a 準拠）、優先度を P1 → P0 に昇格。§5 carry テーブルを全面是正: 列に TC 参照列を追加、carry-006/011/012/014/021 を新規追加、carry-013→TC-017b、carry-015→TC-027、carry-017→TC-023a、carry-025→TC-023b に参照修正。§4 P0/P1 リスト整合。 |

**本ファイルが TC / CAT 件数の正本（2026-06-15 時点）**

- CAT 系: CAT-001〜CAT-009 = **9 件**
- TC 系: TC-001〜TC-030（TC-017a / TC-017b を個別カウント、TC-023a / TC-023b を個別カウント）= **32 件**
  - 内訳: TC-001〜TC-016, TC-017a, TC-017b, TC-018〜TC-024, TC-025〜TC-030（TC-023a / TC-023b 含む）
- **合計: 41 件**

※ 旧来の「35 TC」はこの更新以前の件数であり、以後は本ファイルの 41 件（CAT 9 + TC 32）が正本。
