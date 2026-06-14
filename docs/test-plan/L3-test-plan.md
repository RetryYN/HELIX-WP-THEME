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
| TC-011 | `/license/validate` | API | P1 | 無効ライセンス時 `readonly=true` |
| TC-012 | `/license/validate` | API | P1 | Fallback が既存テーマに安全に適用される |
| TC-013 | `/tracking/context` | API | P1 | plugin 署名整合、`tracking/context` 受信スキーマ照合 |
| TC-014 | SEO coexistence | Function | P1 | 重複 meta / JSON-LD 検出時に warning を必須化 |
| TC-015 | SEO coexistence | E2E | P1 | 既存テーマ互換で既定メタ/構造化データの二重挿入を検知 |
| TC-016 | a11y gate | Gate | P0 | axe でWCAG2.2 AA 以上、失敗があるとCI fail |
| TC-017 | i18n/RTL gate | Gate | P1 | RTL/i18n 違反があると CI fail |
| TC-018 | Performance gate | Gate | P1 | LCP/INP/CLS、初期CSS/JS、render-blocking を超過すると CI fail |
| TC-019 | apply/rollback フロー | E2E | P0 | dry-run → apply → rollback 正常系、監査ログと状態更新が一致 |
| TC-020 | apply/rollback 異常系 | E2E | P0 | rollback 失敗時に差し戻し済み状態を破綻させず、再試行可能 |
| TC-021 | diff_hash 整合性 | API | P0 | dry-run/apply で同一 `diff_hash` 必須、変更内容と差分が一致 |
| TC-022 | SEO 共存（外部/内部） | Security | P2 | 重複/競合検知で apply を中断し、警告レベルに集約 |
| TC-023a | SSRF | Security | P0 | 初回検証時に private/loopback/link-local/metadata アクセスを拒否し、redirect は追従しない |
| TC-023b | SSRF | Security | P0 | 再試行時に URL を再解決し、再解決先が private/loopback/link-local/metadata であれば再拒否 |
| TC-024 | once-token replay | Security | P0 | once-token replay が atomic INSERT で再送除外 |
| TC-025 | slug / selector 安全性 | Security | P1 | 非 ASCII / 多言語 slug が selector 破壊しない（影響範囲がログ化される） |
| TC-026 | public snapshot | Security | P1 | `public snapshot` が内部 ID を返さない（公開ID変換のみ） |

## 4. P0 / P1 / P2 分類

- P0: CAT-001〜CAT-008、TC-002、TC-003、TC-005、TC-006、TC-007、TC-009、TC-010、TC-016、TC-019、TC-020、TC-021、TC-023a、TC-023b、TC-024
- P1: CAT-009、TC-001、TC-004、TC-008、TC-011、TC-012、TC-013、TC-014、TC-015、TC-017、TC-018、TC-025、TC-026
- P2: TC-022（監査ログ整合と運用連携）

※ 優先度整合・受入条件は INT-002 / DC-F-002 に基づき `§4` 一覧を `TC` テーブルに合わせて更新済み（CAT-009 は P1）。

## 5. L4 carry（007/009/013/015/017/025/026）検証観点

本項目は L4 実装で満たすべき受入テストを明記する。

| carry-id | 検証観点（L4） | 根拠 |
|---|---|---|
| 007 | once-token replay の atomic insert と重複排除（TC-024） | `docs/reviews/G2-carry-register.md` |
| 015 | SBOM gate（release build 時に `sbom.cdx.json` 作成）で依存元 / ライセンス / checksum 検証を行い、Theme Review 提出前に確定する（carry-G2-015） | `docs/design/L2-design.md` |
| 009 | slug/selector の非ASCII耐性（TC-025） | `docs/security/threat-model.md` |
| 013 | i18n/RTL 違反時の文言表示および `sanitize_title` 運用の分離（TC-017） | `docs/design/L2-design.md` §8.9 |
| 017 | URL バリデーション（SSRf）、リダイレクト拒否、再解決（TC-023a） | `docs/security/threat-model.md` |
| 025 | 送信再試行時の re-resolve + private 判定（TC-023b） | `docs/design/api-catalog.md` |
| 026 | public snapshot に内部 ID を漏らさない（TC-026） | `docs/design/L2-design.md` |

## 6. 受入時チェックリスト

- すべての P0 が完了していること
- Contract テストで CAT-001〜CAT-009 が green
- a11y / i18n / perf の 3 ゲートが green
- `diff_hash` / `event_id` / `once-token` に関する整合不具合が残存しないこと
- `apply/rollback` の正常・異常・冪等性が検証済みであること
