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

- P0: CAT-001〜CAT-008、TC-002、TC-003、TC-005、TC-006、TC-007、TC-009、TC-010、TC-011、TC-016、TC-019、TC-020、TC-021、TC-023a、TC-023b、TC-024、TC-042、TC-043、TC-045、TC-047、TC-048、TC-049、TC-050
- P1: CAT-009、TC-001、TC-004、TC-008、TC-012、TC-013、TC-014、TC-015、TC-017a、TC-017b、TC-018、TC-025、TC-026、TC-027、TC-028、TC-029、TC-030、TC-031、TC-032、TC-033、TC-034、TC-035、TC-037、TC-038、TC-040、TC-044、TC-046、TC-051、TC-052、TC-053、TC-054、TC-055、TC-056、TC-057、TC-058、TC-059、TC-060、TC-061、TC-062、TC-063、TC-064
- P2: TC-022（監査ログ整合と運用連携）、TC-036（SLO レポート）、TC-039（AI citation log）、TC-041（LLMO 計測サマリ）、TC-065（disclosure 非注入時デフォルト動作）

※ 優先度整合・受入条件は INT-002 / DC-F-002 に基づき `§4` 一覧を `TC` テーブルに合わせて更新済み（CAT-009 は P1）。TC-017 を TC-017a/TC-017b に分割。TC-027〜TC-030 を P1 で追加。§8 追加分 P0（TC-042/043/045/047/048/049/050）を §4 正本リストに反映済み（2026-06-18）。§8 追加分（TC-031〜060）を P0/P1/P2 とも §4 正本に反映済み（2026-06-18）。

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

---

## 8. 2026-06-18 追加（実テーマギャップ監査由来 / GAP-RT-036〜041）

> 本節は `docs/reviews/L3-real-theme-gap-register.md` の GAP-RT-036〜041 により判明した「受入条件はあるが独立 TC が存在しない」ギャップを埋める TC 群です。  
> 既存 TC の最大採番 TC-030 に続き、TC-031〜TC-060 として連番付与しています。  
> 各 TC の対象要件・前提・手順・期待結果・分類（unit / integration / E2E / CI gate）・優先度（P0〜P3）を記載します。

---

### 8.1 GAP-RT-036: 運用品質（ACC-NF-007 / REQ-NF-013）— 独立 TC 群

**背景**: ACC-NF-007 は REQ-NF-013（運用品質: WP/PHP 互換・更新前後チェック・rollback・plugin 衝突検出・可用性 fallback・SLO/health check を契約化する）の受入条件だが、個別の TC が存在しなかった。

| TC | 対象要件 | 前提 | 手順 | 期待結果 | 分類 | 優先度 |
|---|---|---|---|---|---|---|
| TC-031 | ACC-NF-007 / REQ-NF-013 | WP 6.6+ + PHP 8.1+ + AGENT NEO 有効化済み環境 | (1) WP コアをマイナーバージョンアップ (2) `GET /agent-neo/v1/status` でヘルスチェックエンドポイントを呼ぶ (3) `wp agent-neo health-check` CLI コマンドを実行 | (a) `status: healthy` が返り fatal error なし (b) CLI が compatibility matrix の検証結果を JSON で出力し、PASS / WARN / FAIL が判定される | integration | P1 |
| TC-032 | ACC-NF-007 / REQ-NF-013 | PHP 8.2 で AGENT NEO を動作させられるテスト環境 | (1) PHP バージョンを 8.1→8.2 へ切り替え (2) AGENT NEO の全主要クラスをインスタンス化 (3) PHPCS + PHPCompatibilityWP を実行 | (a) deprecated dynamic properties 警告ゼロ (b) PHPCompatibilityWP CI チェックが PASS (c) 管理画面・フロント両方でエラーなく動作する | CI gate | P1 |
| TC-033 | ACC-NF-007 / REQ-NF-013 | AGENT NEO 有効化環境に互換テスト用ダミープラグインを追加 | (1) Yoast SEO / Contact Form 7 / WP Rocket の 3 プラグインを有効化 (2) `wp agent-neo conflict-scan` CLI を実行 (3) 管理画面「プラグイン競合」パネルを確認 | (a) 各プラグインとの衝突検出結果（conflict_severity: none / low / medium / high）が JSON で返る (b) 高衝突（high）が検出された場合、管理画面に admin_notice が表示される | integration | P1 |
| TC-034 | ACC-NF-007 / REQ-NF-013 | AGENT NEO 有効化環境 | (1) テーマアップデート前に `wp agent-neo update-preflight` を実行 (2) アップデートを適用 (3) `wp agent-neo update-postflight` を実行 | (a) preflight が DB スキーマ・設定値・rollback_point を記録し JSON で出力 (b) アップデート後に postflight が事前記録と差分 0 を確認 (c) 差分ありの場合、rollback_point から設定値を復元できる | integration | P1 |
| TC-035 | ACC-NF-007 / REQ-NF-013 | WP-Cron が有効な環境 | (1) WP-Cron の scheduled events を確認 (`wp cron event list`) (2) AGENT NEO が登録した定期ジョブ（health-check / catalog-sync 等）が一覧に存在することを確認 (3) `wp cron event run agent_neo_health_check` でジョブを強制実行 | (a) ジョブが正常に実行され、ログに `cron_run: success` が記録される (b) 実行失敗（外部依存エラー）時は DLQ エントリが生成され管理画面に警告が表示される | integration | P1 |
| TC-036 | ACC-NF-007 / REQ-NF-013 | `GET /agent-neo/v1/status` エンドポイントが実装済み | (1) 外部監視ツール（UptimeRobot 等）が 1 分間隔でエンドポイントをポーリングする状況をシミュレート (2) DB 接続を意図的に切断（テスト環境） (3) エンドポイントのレスポンスを確認 | (a) DB 切断時も `status: degraded` または `status: unavailable` を JSON で返し 500 を返さない (b) SLO 目標（稼働率 99.5%）に対するレポートが CLI で出力できる | integration | P2 |

---

### 8.2 GAP-RT-037: LLMO — AI クローラビリティ・構造化データ品質（ACC-NF-011 / REQ-NF-017）

**背景**: ACC-NF-011 は REQ-NF-017（LLMO/AI 検索最適化: answer unit・evidence graph・content origin・AI visibility policy・citation anchor・LLMO 計測・claim risk を契約化する）の受入条件だが、個別の TC が存在しなかった。

| TC | 対象要件 | 前提 | 手順 | 期待結果 | 分類 | 優先度 |
|---|---|---|---|---|---|---|
| TC-037 | ACC-NF-011 / REQ-NF-017 | AGENT NEO Core Plugin 有効化・記事が 1 件以上公開済み | (1) answer unit が定義された記事（H2 + 短回答 + 根拠リンク + CTA を持つセクション）を作成 (2) `GET /agent-neo/v1/posts/{id}/markdown` を呼ぶ | (a) レスポンスに `answer_units` 配列が含まれ、各要素が `question / short_answer / details / evidence_refs / updated_at / cta_id` フィールドを持つ (b) 空の H2 セクションは answer unit として出力されない | integration | P1 |
| TC-038 | ACC-NF-011 / REQ-NF-017 | Robots.txt / X-Robots-Tag が設定済みの記事環境 | (1) `GET /agent-neo/v1/ai-crawlers/access-matrix` を呼ぶ | (a) Googlebot / GPTBot / ClaudeBot / PerplexityBot 等の主要 AI クローラ別に `allowed / blocked / noindex` 状態が列挙された JSON が返る (b) クローラプリセット切替（`agent_neo_ai_crawler_preset: permissive / balanced / restrictive`）を設定変更後に再取得すると値が変わる | integration | P1 |
| TC-039 | ACC-NF-011 / REQ-NF-017 | GA4 または custom tracking endpoint が設定済み | (1) `<link rel="me">` または `data-agent-citation-anchor` 属性付きコンテンツを含む記事ページを表示 (2) AI クローラ UA（例: GPTBot）でページを GET | (a) レスポンスヘッダに `X-Agent-Citation-Policy` が含まれる (b) フロント HTML の主要コンテンツに `data-agent-citation-anchor` が付与されている (c) AI クローラからのアクセスログが `ai_crawler_log` テーブルに記録される | integration | P2 |
| TC-040 | ACC-NF-011 / REQ-NF-017 | Entity Graph が設定された記事（§ L3-A3 entity-graph.schema.json） | (1) 記事を公開 (2) `GET /agent-neo/v1/posts/{id}/snapshot` でスナップショットを取得 | (a) レスポンスに `entity_graph` フィールドが含まれ、`@graph` 内に Article / Person / Organization の少なくとも 2 ノードが存在する (b) `evidence_graph` が claim / source / reviewer / verified_date フィールドを持つ | integration | P1 |
| TC-041 | ACC-NF-011 / REQ-NF-017 | LLMO 計測イベントが tracking/event に統合済み | (1) AI クローラからページにアクセスして tracking event を発火させる (2) `GET /agent-neo/v1/tracking/llmo-summary` でサマリを取得 | (a) `ai_impressions / ai_citations / ai_referral_clicks` の 3 指標が 24 時間集計で返る (b) AI referral（Perplexity / Claude 等経由の訪問）が UA 解析で分類され、通常訪問と区別してカウントされる | integration | P2 |

---

### 8.3 GAP-RT-038: Consent Gate（Cookie 同意前後のスクリプト発火タイムライン）

**背景**: TC-028 は Lighthouse render-blocking に特化した TC だが、「同意バナー表示 → 第三者タグ / GA4 の非発火 → 同意付与 → 発火」のタイムライン検証 TC が独立して存在しなかった。REQ-NF-004（データ保護）・REQ-NF-009（外部送信同意）・L3-A4（`third-party-tags.schema.json` / Consent Mode v2）に基づく。

| TC | 対象要件 | 前提 | 手順 | 期待結果 | 分類 | 優先度 |
|---|---|---|---|---|---|---|
| TC-042 | REQ-NF-004 / REQ-NF-009 | GA4 タグが `async_after_consent` / `consentRequired: ["analytics_storage"]` で設定済み・同意バナーあり・Consent Mode v2 設定済み（`defaultConsentState` が全 `denied`） | (1) プライベートブラウジングで記事ページを開く（Cookie なし状態） (2) DevTools Network パネルを監視 (3) 同意バナーが表示された状態のまま 5 秒待機 | (a) **analytics/ads の計測 ping**（`google-analytics.com/g/collect` 等の collect エンドポイント）へのリクエストが 0 件 (b) **GTM コンテナ本体（`googletagmanager.com/gtm.js` 等）のロードが発生しない** (c) 注意: Consent Mode v2 の `gtag('consent','default',{...denied})` 初期化スニペット自体は `<head>` で呼ばれることが正常仕様であり、本 TC の非発火対象に含まない（TC-044 で独立検証する） | E2E | P0 |
| TC-043 | REQ-NF-004 / REQ-NF-009 | TC-042 に続けて実施 | (1) 同意バナーで「すべて受け入れる」を押す (2) DevTools Network を確認 | (a) 同意 click から 1 秒以内に GA4 ping リクエストが送信される (b) `gtag('consent', 'update', { analytics_storage: 'granted' })` が実行されたことが Console log で確認できる (c) GA4 タグの読み込みが `async`（parser-blocking でない）で完了する | E2E | P0 |
| TC-044 | REQ-NF-009 / L3-A4 §third-party-tags | Consent Mode v2 設定済み（`defaultConsentState` が全 `denied`） | (1) ページロード直後に `window.dataLayer` の内容を確認 | (a) `gtag('consent', 'default', { analytics_storage: 'denied', ad_storage: 'denied', ad_user_data: 'denied', ad_personalization: 'denied' })` が `<head>` 最上位で呼ばれている | unit / E2E | P1 |
| TC-045 | REQ-NF-009 / L3-A4 §third-party-tags | `advertising` カテゴリのタグが設定済み | (1) 同意なし状態でページを表示 (2) `advertising` タグの HTML 出力を確認 | (a) `advertising` カテゴリのタグ HTML が `<head>` / `<body>` に出力されていない (b) Playwright でタグ要素の存在を確認 → 0 件 | E2E | P0 |
| TC-046 | REQ-NF-009 / L3-A4 §third-party-tags | Playwright E2E 環境 + DevTools Protocol 利用可能 | (1) 同意バナー表示→選択「拒否」ルートを Playwright でシミュレート | (a) 拒否選択後も GA4 ネットワークリクエストが発生しない (b) 拒否フラグが Cookie に保存され、再訪問時も同意バナーが再表示されない | E2E | P1 |

---

### 8.4 GAP-RT-039: 公開 Snapshot Allowlist（draft/private 記事・機密データ漏洩防止）

**背景**: TC-026 は「public snapshot が内部 ID を返さない」を検証するが、draft/private 記事・WP nonce・ライセンスキー等の機密情報が snapshot や crawl-map に含まれないことを確認する独立 TC が存在しなかった。REQ-NF-004（データ保護）・REQ-NF-015（AI 運用性/クローラビリティ）に基づく。

> **【軸分離注記】本節（TC-047〜051）は snapshot の情報漏洩（draft/private 記事・nonce・ライセンスキーの公開エンドポイントへの漏洩）を検証するセキュリティテストです。視覚回帰テスト（スタイル差分の承認フロー / BackstopJS による pixel 比較 / ADR-021 PoC）とは別軸であり、本 TC 群で視覚回帰カバレッジが担保されるわけではありません。視覚回帰は MG-009 系 carry および ADR-021 PoC として別途 L4〜L5 carry で追跡されます。**

| TC | 対象要件 | 前提 | 手順 | 期待結果 | 分類 | 優先度 |
|---|---|---|---|---|---|---|
| TC-047 | REQ-NF-004 / REQ-NF-015 | draft ステータスの記事が 1 件以上存在する | (1) 未認証 HTTP GET で `GET /agent-neo/v1/public/pages/snapshot` を呼ぶ | (a) draft / private ステータスの記事 ID・スラッグ・コンテンツがレスポンスに含まれない (b) レスポンスの `allowed_posts` が published かつ snapshot_allowed=true の記事のみを含む | integration | P0 |
| TC-048 | REQ-NF-004 / REQ-NF-015 | public crawl-map エンドポイントが実装済み | (1) 未認証で `GET /agent-neo/v1/public/crawl-map` を呼ぶ | (a) draft / private / password-protected 記事の URL がリストに含まれない (b) `noindex=true` の記事 URL もリストから除外されている | integration | P0 |
| TC-049 | REQ-NF-004 / REQ-NF-015 | `GET /agent-neo/v1/public/pages/{id}/snapshot` が実装済み | (1) 有効な WP nonce を HTML 内に持つページの snapshot を未認証で取得 | (a) レスポンス HTML / JSON に `_wpnonce` 文字列が含まれない (b) `nonce` キーを持つフィールドが snapshot レスポンスのどの階層にも存在しない | integration | P0 |
| TC-050 | REQ-NF-004 / REQ-NF-002 | ライセンスキーが `wp_options` に保存済み | (1) 未認証で `/agent-neo/v1/public/` 配下の全公開エンドポイントに GET を送る (2) 認証済み管理者で `/agent-neo/v1/settings/` を GET する | (a) 公開エンドポイントのどのレスポンスにも `license_key` / `api_key` フィールドが含まれない (b) 認証済み管理者の `/settings/` レスポンスでもライセンスキーが平文で返らない（マスク済み：例 `sk-xxxx...xxxx`） | integration | P0 |
| TC-051 | REQ-NF-015 | snapshot allowlist 設定が管理画面で変更可能な環境 | (1) 管理画面で特定の記事を `snapshot_allowed=false` に設定 (2) `GET /agent-neo/v1/public/pages/{id}/snapshot` を未認証で呼ぶ | (a) `403 Forbidden` または `404 Not Found` が返る (b) 対象記事のコンテンツがレスポンスに一切含まれない | integration | P1 |

---

### 8.5 GAP-RT-040: Canonical + OGP の同時評価（同時 toggle 禁止・二重出力排除）

**背景**: TC-014/TC-015 は SEO 競合検知（重複 meta / JSON-LD）に特化しているが、「同一ページで canonical URL と `og:url` が整合する」「既存 SEO プラグインとの共存時に OGP が二重出力されない」「noindex が robots と sitemap に同時反映される」という複合検証 TC が独立して存在しなかった。REQ-F-011（SEO Core）・REQ-NF-018（SEO/WP 運用ハザード管理）に基づく。

| TC | 対象要件 | 前提 | 手順 | 期待結果 | 分類 | 優先度 |
|---|---|---|---|---|---|---|
| TC-052 | REQ-F-011 / REQ-NF-018 | SEO プラグインなし環境で記事が公開済み | (1) 記事の SEO メタで canonical を `https://example.com/post-a/` に設定 (2) 記事ページの HTML ソースを確認 | (a) `<link rel="canonical" href="https://example.com/post-a/">` が 1 件のみ出力される (b) `<meta property="og:url" content="https://example.com/post-a/">` が canonical と同一 URL で出力される (c) canonical と og:url が異なる場合は管理画面に warning が表示される | integration | P1 |
| TC-053 | REQ-F-011 / REQ-NF-018 | Yoast SEO が有効化されている環境 | (1) Yoast SEO が有効な状態で記事ページを表示 (2) HTML ソースの `<head>` を確認 | (a) `<meta property="og:*">` が 2 セット出力されない（Yoast または AGENT NEO のどちらか一方のみ） (b) AGENT NEO の `SeoConflictDetector` が canonical / robots は AGENT NEO 優先、OGP は Yoast 優先として出力を制御している | integration | P1 |
| TC-054 | REQ-F-011 / REQ-NF-018 | AGENT NEO SEO 設定で特定ページを noindex に設定 | (1) 記事の SEO メタで `robots.index=false` を設定 (2) ページの HTML ソース・sitemap.xml・robots.txt を確認 | (a) `<meta name="robots" content="noindex,follow">` が出力される (b) XML sitemap からその URL が除外されている（単純 toggle ではなく両方に同時反映） (c) robots.txt に個別ルールが不要（sitemap 除外で十分に機能する） | integration | P1 |
| TC-055 | REQ-F-011 / REQ-NF-018 | JSON-LD が有効な環境 | (1) 記事に Article + FAQPage + BreadcrumbList の 3 ノードを持つ @graph を設定 (2) ページの `<script type="application/ld+json">` を検証 | (a) `<script type="application/ld+json">` が 1 件のみ出力される（@graph 統合、複数の個別 script タグに分割されない） (b) `@graph` 配列に重複 `@type` ノードが存在しない | integration | P1 |

---

### 8.6 GAP-RT-041: 移行 SEO（URL マッピング / redirect / canonical / 画像 diff）

**背景**: ACC-008 は「移行プレビューと投入結果が一致する」を検証するが、「旧テーマ→AGENT NEO 移行時の 301 リダイレクト保持・canonical 整合・パーマリンク変化・画像差分レポート」の独立 TC が存在しなかった。REQ-F-008（移行プラグイン）・REQ-NF-018（SEO ハザード管理）に基づく。

| TC | 対象要件 | 前提 | 手順 | 期待結果 | 分類 | 優先度 |
|---|---|---|---|---|---|---|
| TC-056 | REQ-F-008 / REQ-NF-018 | 移行元の旧テーマサイト（WP REST アクセス可）と移行先 AGENT NEO 環境が準備済み | (1) 移行プラグインで旧テーマから投稿 10 件を抽出 (2) URL マッピングファイル（旧 URL → 新 URL）を生成 | (a) URL マッピングが JSON / CSV 形式で出力される (b) 旧パーマリンクと新パーマリンクが全 10 件分マップされ、漏れゼロ | integration | P1 |
| TC-057 | REQ-F-008 / REQ-NF-018 | TC-056 の URL マッピングが生成済み | (1) `wp agent-neo migration redirect-setup --mapping=mapping.json` を実行 (2) 旧 URL にアクセス | (a) HTTP 301 レスポンスが返り、`Location` ヘッダに新 URL が設定されている (b) 全 10 件の旧 URL で 301 が確認できる (c) 301 チェーン（301→301 の連鎖）が発生していない | integration | P1 |
| TC-058 | REQ-F-008 / REQ-NF-018 | 移行後に canonical が設定済み | (1) 移行後の記事ページの HTML ソースを確認 (2) 移行元 URL での canonical 設定と比較 | (a) 移行後の `<link rel="canonical">` が新 AGENT NEO パーマリンクを指している (b) 旧テーマの canonical を引き継ぐ場合は `seo-meta.schema.json` の `canonical.source=manual` で明示的に指定されている | integration | P1 |
| TC-059 | REQ-F-008 / REQ-NF-018 | 移行元サイトに画像付き記事が 5 件以上存在 | (1) `wp agent-neo migration diff-images --post_ids=1,2,3,4,5` を実行 | (a) 移行前後の画像 URL 差分レポートが出力される (b) 移行後に media_id が再割り当てされた場合、旧 img src と新 img src のマッピングが diff に含まれる (c) 消失した画像（旧にあり新にない）が `missing_images` リストに列挙される | integration | P1 |
| TC-060 | REQ-F-008 / REQ-NF-018 | 移行プラグインが構造変換プレビュー機能を持つ | (1) 移行プレビューを実行（dry-run mode） (2) 旧 WP ブロック構造と AGENT NEO ブロック構造の変換差分を確認 | (a) プレビューで変換結果が表示され、本番適用前に管理者が確認できる (b) プレビューと実際の投入結果が bit-identical（ACC-008 との整合） (c) 変換できないブロック（旧テーマ固有ショートコード等）が `unconverted_elements` として明示される | E2E | P1 |

---

### 8.7 P0 / P1 / P2 分類（§8 追加分）

- **P0 追加分**: TC-042（同意前 GA4 非発火）/ TC-043（同意後 GA4 発火）/ TC-045（advertising タグ非出力）/ TC-047（draft snapshot 除外）/ TC-048（crawl-map draft 除外）/ TC-049（nonce 漏洩防止）/ TC-050（ライセンスキー漏洩防止）
- **P1 追加分**: TC-031〜TC-035（運用品質）/ TC-037〜TC-038（LLMO）/ TC-040（LLMO entity graph）/ TC-044（Consent Mode v2 default denied）/ TC-046（拒否ルート）/ TC-051（snapshot allowlist 個別制御）/ TC-052〜TC-055（canonical+OGP 同時評価）/ TC-056〜TC-060（移行 SEO）
- **P2 追加分**: TC-036（SLO レポート）/ TC-039（AI citation log）/ TC-041（LLMO 計測サマリ）

---

### 8.8 GAP-RT ↔ TC マッピング表

| GAP-ID | カテゴリ | カバーする TC | 残存 carry |
|---|---|---|---|
| GAP-RT-036 | 運用品質（ACC-NF-007 / REQ-NF-013） | TC-031（WP 更新 preflight/postflight health-check）/ TC-032（PHP 8.2 互換 CI gate）/ TC-033（plugin conflict scan）/ TC-034（update preflight/postflight rollback）/ TC-035（cron 信頼性 + DLQ）/ TC-036（SLO health check 縮退） | PHP 8.3/8.4/8.5 の互換 TC は L4 carry（PERF-CARRY-002 相当）/ SLO 数値目標は PO 裁定待ち（GAP-RT-045 相当） |
| GAP-RT-037 | LLMO（ACC-NF-011 / REQ-NF-017） | TC-037（answer unit 生成）/ TC-038（AI crawler プリセット切替）/ TC-039（citation anchor + AI crawler log）/ TC-040（evidence graph + entity-graph スナップショット）/ TC-041（AI referral 計測イベント） | evidence graph の Automation SEO 連携 API 契約は L4 carry（CARRY-A3-003 相当）/ LLMO visibility ダッシュボードは Phase 2 |
| GAP-RT-038 | Consent Gate（REQ-NF-004 / REQ-NF-009） | TC-042（同意前非発火）/ TC-043（同意後発火 async 確認）/ TC-044（Consent Mode v2 default denied）/ TC-045（advertising タグ非出力 E2E）/ TC-046（拒否ルート） | 同意バナープラグイン選定（PERF-CARRY-002）が P1 blocking carry。選定前は TC-042〜046 は「外部バナープラグインを Mock に差し替え」で実施 |
| GAP-RT-039 | Snapshot allowlist（REQ-NF-004 / REQ-NF-015） | TC-047（draft snapshot 除外）/ TC-048（crawl-map draft 除外）/ TC-049（nonce 漏洩防止）/ TC-050（ライセンスキー漏洩防止）/ TC-051（snapshot_allowed=false 個別制御） | public ID opaque 化（CARRY-TO-L4 の L4 繰延分）は TC-026 と TC-047〜051 が補完関係。L4 で public_id 変換が実装された段階で TC-049 を更新 |
| GAP-RT-040 | Canonical + OGP 同時評価（REQ-F-011 / REQ-NF-018） | TC-052（canonical ↔ og:url 整合）/ TC-053（Yoast 共存時 OGP 二重出力排除）/ TC-054（noindex → sitemap 同時除外）/ TC-055（@graph 統合 JSON-LD 1件出力） | seo-conflict-rules.json OGP 優先ルール詳細は CARRY-A3-001（ADR Wave3 申し送り）。ADR 確定後に TC-053 の期待結果を更新 |
| GAP-RT-041 | 移行 SEO（REQ-F-008 / REQ-NF-018） | TC-056（URL マッピング生成）/ TC-057（301 リダイレクト設定・検証）/ TC-058（移行後 canonical 整合）/ TC-059（画像 diff レポート）/ TC-060（構造変換プレビュー ↔ 投入整合） | 移行差分表示粒度（HTML diff / セマンティック diff）は Q-007（未確定）。TC-060 の「bit-identical」条件は Q-007 解決後に精度を更新 |

---

### 8.9 L4 実装で実テスト化が必要な carry 一覧

以下の TC は L4 実装が完了するまで実テスト化が困難な項目（carry）です。

| Carry-ID | 関連 TC | 理由 | 解消条件 |
|---|---|---|---|
| CARRY-GRT036-001 | TC-031〜036 | `wp agent-neo health-check` / `conflict-scan` / `update-preflight` 等の CLI コマンドが未実装 | L4 で CLI コマンド実装後に実テスト化 |
| CARRY-GRT036-002 | TC-032 | PHP 8.2+ 専用テスト環境の準備が必要 | CI matrix に PHP バージョン別 job を追加（GAP-RT-031 ADR 対応後） |
| CARRY-GRT037-001 | TC-037 | answer unit フィールドを持つ `/posts/{id}/markdown` レスポンス拡張が未実装 | L4 LLMO 実装 Sprint で対応 |
| CARRY-GRT037-002 | TC-038〜041 | AI crawler access matrix / citation anchor / LLMO tracking endpoint が未実装 | L4 LLMO 実装 Sprint で対応（REQ-NF-017 サブタスク） |
| CARRY-GRT038-001 | TC-042〜046 | 同意バナープラグイン選定（PERF-CARRY-002）が blocking P1 carry。選定前は Mock バナーで代替 | PERF-CARRY-002 解消（ADR 更新）後にフル E2E に切り替え |
| CARRY-GRT039-001 | TC-047〜051 | `GET /agent-neo/v1/public/pages/snapshot` エンドポイントの snapshot allowlist 機能が未実装 | L4 snapshot 実装 Sprint で対応 |
| CARRY-GRT040-001 | TC-053 | Yoast SEO 共存時の OGP 二重出力排除は CARRY-A3-001（ADR Wave3 SEO 出力境界 ADR）が確定するまで期待結果が確定しない | ADR Wave3 確定後に TC-053 期待結果を更新し実テスト化 |
| CARRY-GRT041-001 | TC-056〜060 | 移行プラグイン（REQ-F-008）が未実装 | L4 移行プラグイン Sprint（P1）で対応 |
| CARRY-GRT041-002 | TC-060 | 移行差分表示粒度（HTML diff / セマンティック diff）は Q-007 未確定 | PO が Q-007 を裁定後に TC-060 の受入条件を精緻化 |

---

---

## 9. 2026-06-20 追加（ADR-025 由来 / AI 生成コンテンツ開示法規制対応）

> 本節は `docs/adr/ADR-025.md` の L4 検証 TC 候補（TC-061〜TC-065）を test-plan SSOT へ登録するものです。
> ADR-025 の L4 carry（CARRY-ADR025-001〜005）が実装されるまでは原則的に L4 carry として管理します。
> TC-ID は GAP-RT-041 対応 TC-060 に続き **TC-061 から採番**しています。

---

### 9.1 GAP-RT-055: AI 生成コンテンツ開示法規制（ADR-025）

**背景**: EU AI Act Article 50 / California SB 942 / C2PA v2.4 への対応方針が ADR-025（2026-06-20 PO確定）で確定した。対応責務は Automation SEO 登録時の同意に集約し、AGENT-NEO は disclosure レンダリングフックのみ提供する。本節ではその設計意図を検証するための TC 群を登録する。

| TC | 対象 | 種別 | 優先度 | 受入観点 |
|---|---|---|---|---|
| TC-061 | disclosure レンダリングフック / スロットの出力確認（注入データあり時） | Unit | P1 | AGENT-NEO テーマが disclosure スロット（フックポイント）を提供しており、Automation SEO からの注入データが存在する場合に disclosure ラベルが正しく HTML 出力されることを検証する。具体的には: (a) disclosure フック呼び出し時にスロットの HTML 出力が存在すること。※ 注入データなし時のデフォルト動作は TC-065（P2）が単独 owner であるため本 TC の受入観点に含まない（TC-065 参照）。※ Automation SEO 登録フロー自体の同意取得検証（未同意時のサービス停止等）は AGENT-NEO リポの検証対象外（Automation SEO 側の外部依存テスト）のため、CARRY-ADR025-001 に carry として分離する |
| TC-062 | テーマ disclosure スロットのレンダリング | Integration | P1 | Automation SEO が disclosure マーキング情報を AGENT-NEO の disclosure フック（スロット）へ注入した場合、フロントエンドに開示ラベルが正しくレンダリングされることを検証する |
| TC-063 | schema.org creator フィールドの AI マーキング出力 | Integration | P1 | Article JSON-LD の `creator` / `author` フィールドが、Automation SEO から受領した値を用いて標準 schema.org プロパティ（`creator` / `author` を `Person` または `Organization` の `@type` で）正しく出力できることを検証する。具体的な AI 生成を示すスキーマ形状（非標準プロパティ等）は「Automation SEO から受領する schema 形状に従い L4 で確定」（CARRY-ADR025-003 参照）のため、本 TC では出力構造の正確性のみを検証し、AI-generated を示す具体的プロパティ名を受入条件として固定しない |
| TC-064 | "fully AI-generated" vs "AI-assisted" 区別表示 | Integration | P1 | Automation SEO が disclosure フック経由で "fully AI-generated" または "AI-assisted" の区別情報を送信した場合、AGENT-NEO が対応するラベルを正しく表示することを検証する（EU AI Act Article 50 の 2 種別要件） |
| TC-065 | disclosure スロット非注入時のデフォルト動作 | Unit | P2 | Automation SEO から disclosure 情報が注入されない場合、AGENT-NEO が独自に AI 生成ラベルを出力しないことを検証する（テーマは AI 判断をしない原則 / REQ-NF-025 整合確認） |

---

### 9.2 P0 / P1 / P2 分類（§9 追加分）

- **P0 追加分**: なし（法規制対応は L4 実装 carry のため P1 以下）
- **P1 追加分**: TC-061（AGENT-NEO disclosure スロット出力確認・注入データあり時のみ / 非注入時デフォルト動作は TC-065 が単独 owner / 登録フロー検証は CARRY-ADR025-001 に分離）/ TC-062（disclosure スロットレンダリング）/ TC-063（schema.org AI マーキング）/ TC-064（AI 種別区別表示）
- **P2 追加分**: TC-065（非注入時デフォルト動作）

§4 P0/P1 リスト更新:

- P1 に追加: TC-061、TC-062、TC-063、TC-064
- P2 に追加: TC-065

---

### 9.3 GAP-RT ↔ TC マッピング（§9 追加分）

| GAP-ID | カテゴリ | カバーする TC | 残存 carry |
|---|---|---|---|
| GAP-RT-055 | 法規制対応（AI 開示）| TC-061（disclosure スロット出力確認・**注入データあり時のみ（P1）** / 非注入時デフォルトは TC-065 が owner / 旧「登録時同意フロー」はCARRY-ADR025-001へ分離）/ TC-062（disclosure スロット）/ TC-063（schema.org AI マーキング）/ TC-064（AI 種別区別表示）/ TC-065（**非注入時デフォルト動作（P2）** / TC-061 と責務分離） | CARRY-ADR025-001（同意文言法務確認 + Automation SEO 登録フロー同意ステップの外部依存契約テスト）/ CARRY-ADR025-002（SB 942 Legal opinion）/ CARRY-ADR025-003（disclosure フック仕様 L4 確定）/ CARRY-ADR025-004（EU AI Act 2026-12-02 猶予 WBS）/ CARRY-ADR025-005（C2PA 画像 latent マーキング Automation SEO 側実装計画） |

---

### 9.4 L4 実装で実テスト化が必要な carry 一覧（§9 追加分）

| Carry-ID | 関連 TC | 理由 | 解消条件 |
|---|---|---|---|
| CARRY-ADR025-001 | TC-061（外部依存部分）| (a) 登録時同意フローの具体的な文言・UI が法務確認前に未確定、(b) Automation SEO 登録フローの同意ステップ検証（未同意時のサービス停止）は AGENT-NEO リポでは実行不可の外部依存テスト | 法務レビュー完了後に Automation SEO 側と連動した契約テストとして実テスト化。TC-061（AGENT-NEO 側 disclosure スロット出力確認）は AGENT-NEO リポ内で独立実施可能 |
| CARRY-ADR025-003 | TC-062〜064（特に TC-063） | (a) disclosure フック仕様（disclosure スロット API、"fully AI-generated" / "AI-assisted" 区別情報の受け渡し形式）が L4 entry 前に未確定、および (b) Automation SEO から受領する disclosure の **JSON-LD payload 形状**（schema.org 標準プロパティ `creator` / `author` を用いた AI マーキング表現の具体的契約 — 値の型・必須フィールド・"fully AI-generated" / "AI-assisted" 区別フラグの埋め込み方式）が L4 entry 前に未確定。TC-063 は (b) の契約確定まで受入条件の精緻化が不可能 | L4 disclosure Sprint 着手前に (a)(b) 両仕様を確定後、TC-062〜064 の受入条件を精緻化。TC-063 は JSON-LD payload 形状の L4 確定が owner となる |
| CARRY-ADR025-005 | — | C2PA 画像 latent マーキングは Automation SEO 画像生成パイプラインの実装に依存（AGENT-NEO 側 not-in-scope） | Automation SEO 側 PM 議題化・実装完了後に AGENT-NEO 側への影響がないことを確認 |

---

## 7. 変更履歴（changelog）

| 日付 | 修正者 | 内容 |
|---|---|---|
| 2026-06-18 | 実テーマギャップ監査由来 TC 追加 | §8 を新設（GAP-RT-036〜041 対応）。TC-031〜TC-060 を新規追加（30 TC）。内訳: TC-031〜036（GAP-RT-036 運用品質）/ TC-037〜041（GAP-RT-037 LLMO）/ TC-042〜046（GAP-RT-038 Consent Gate）/ TC-047〜051（GAP-RT-039 snapshot allowlist）/ TC-052〜055（GAP-RT-040 canonical+OGP 同時評価）/ TC-056〜060（GAP-RT-041 移行 SEO）。GAP-RT↔TC マッピング表（§8.8）・L4 carry 一覧（§8.9）を追記。P0 追加: TC-042/043/045/047/048/049/050。 |
| 2026-06-20 | ADR-025 由来 TC 追加 | §9 を新設（GAP-RT-055 / AI 生成コンテンツ開示法規制 / ADR-025 対応）。TC-061〜TC-065 を新規追加（5 TC）。P1 追加: TC-061〜064。P2 追加: TC-065。GAP-RT↔TC マッピング（§9.3）・L4 carry 一覧（§9.4）を追記。 |
| 2026-06-15 | L4着手前敵対検証修正 | TC-017 → TC-017a / TC-017b に分割（i18n/RTL gate + sanitize_title 分離単体テスト）。TC-025 受入条件を強化（`sanitize_slug()` `[a-z0-9-]` 正規化・単体テスト必須、ログ化のみ合格不可）。TC-027（SBOM gate / P1）を新設。TC-028（Lighthouse CI render-blocking / consent / P1）新設。TC-029（lp-blueprint 12セクション整合 / P1）新設。TC-030（bridge-profile safe_apply_state / ADR-019準拠 / P1）新設。TC-011 に P0 シナリオ追加（ライセンスサーバ 502 / deny-first / TB-18a 準拠）、優先度を P1 → P0 に昇格。§5 carry テーブルを全面是正: 列に TC 参照列を追加、carry-006/011/012/014/021 を新規追加、carry-013→TC-017b、carry-015→TC-027、carry-017→TC-023a、carry-025→TC-023b に参照修正。§4 P0/P1 リスト整合。 |

**本ファイルが TC / CAT 件数の正本（2026-06-20 時点）**

- CAT 系: CAT-001〜CAT-009 = **9 件**
- TC 系（§3.2 既存）: TC-001〜TC-030（TC-017a / TC-017b を個別カウント、TC-023a / TC-023b を個別カウント）= **32 件**
- TC 系（§8 追加）: TC-031〜TC-060 = **30 件**
  - TC-031〜036: GAP-RT-036 運用品質（6 件）
  - TC-037〜041: GAP-RT-037 LLMO（5 件）
  - TC-042〜046: GAP-RT-038 Consent Gate（5 件）
  - TC-047〜051: GAP-RT-039 snapshot allowlist（5 件）
  - TC-052〜055: GAP-RT-040 canonical+OGP 同時評価（4 件）
  - TC-056〜060: GAP-RT-041 移行 SEO（5 件）
- TC 系（§9 追加）: TC-061〜TC-065 = **5 件**
  - TC-061〜065: GAP-RT-055 AI 生成コンテンツ開示法規制（ADR-025）（5 件）
- **合計: 76 件（CAT 9 + TC 67）**

※ 旧来の「41 件」は 2026-06-15 時点の件数。2026-06-18 追加分 30 件 + 2026-06-20 追加分 5 件を加算し 76 件が正本。
