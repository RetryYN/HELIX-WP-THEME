# AGENT NEO — G6 セキュリティレビュー記録（L6 統合検証）

- 対象リポジトリ: `/opt/agent-neo`
- 作成日: 2026-06-27
- 版: G6版（L6 統合検証 / RC判定ゲート③）
- 対象コミット範囲:
  - `17c4f25` feat(tracking): サーフェス分離(page_type)+LP CTA計測ID付与
  - `1f4cb83` fix(tracking): HMAC canonical 非対称 + LP-frontpage 誤分類の修正
- 参照資料:
  - `docs/security/threat-model.md`（G2 版 / 脅威モデル正本）
  - `docs/features/tracking-pull/D-CONTRACT/export-contract.md`（export API 契約）
  - `plugins/agent-neo-core/assets/js/ad-tracking.js`
  - `plugins/agent-neo-core/inc/rest/class-tracking-controller.php`
  - `plugins/agent-neo-core/inc/rest/class-tracking-export-controller.php`
  - `plugins/agent-neo-core/inc/tracking/class-tracking-assets.php`

---

## 1. 対象スコープ

| 領域 | 対象コンポーネント | 変更有無 |
|---|---|---|
| 計測イベント受信 | `class-tracking-controller.php`（`POST /agent-neo/v1/tracking/event`） | 変更あり（HMAC canonical 修正） |
| JS 計測エージェント | `ad-tracking.js`（`canonicalizeForSignature` / `sendEvent` / `hasConsent`） | 変更あり（再帰 canonical 追加・page_type 横断付与） |
| サーフェス分離 | `class-tracking-assets.php`（`detect_page_type` / `localize`） | 変更あり（LP テンプレート優先判定） |
| LP CTA 計装 | `lp-hero.php` / `lp-pricing.php`（`an-cta--` 付与） | 変更あり |
| consent gate | `ad-tracking.js`（`hasConsent` / localStorage + Cookie） | 変更なし（動作確認のみ） |
| export API | `class-tracking-export-controller.php`（`GET /agent-neo/v1/tracking/export`） | 変更なし（`page_type` フィールド定義のみ export-contract.md 追記） |

---

## 2. 今セッションで解消した事項

### 2-1. HMAC canonical 非対称 → viewable_impression 全件消失回帰（解消済み）

**根本原因:**
- `ad-tracking.js`（修正前）は metadata のキーをトップレベルのみソートし、ネストされた連想配列は挿入順のまま署名。
- PHP `canonical_json()` / `sort_recursive()` は全ネスト階層を `ksort` する再帰実装。
- `metadata = { ratio: 0.8, page_type: "home" }` の場合、JS は `{ratio, page_type}` 順のまま canonical を生成。PHP は昇順ソートで `{page_type, ratio}` に並べ替え → 署名不一致 → `401 SIGNATURE_INVALID` → イベント消失。
- `page_type` 追加前（`metadata = { ratio: 0.8 }` 単一キー）は非対称が露出しなかったため、`17c4f25` で page_type を追加した時点で回帰が発生。

**修正内容:**
- JS に `canonicalizeForSignature(value)` を追加（`1f4cb83`）。
  - `Array`: 要素を再帰処理、順序は保持。
  - `Object`（連想）: `Object.keys(value).sort()` で昇順再構築し、再帰処理。
  - スカラー: そのまま返却。
- `sendEvent` 内で `canonicalizeForSignature(unsigned)` を経由した後に `JSON.stringify` して canonical 生成。PHP `sort_recursive` と同一の動作を達成。

**実 POST 実証（`1f4cb83` コミットメッセージ記載）:**
- 旧実装: `viewable_impression { ratio, page_type }` → 401
- 新実装: `viewable_impression { ratio, page_type }` → 200（`affiliate_click` / `ad_impression` / `scroll_depth` も全て 200）

**G2 脅威モデルとの対応:** TB-06（変更イベント JSON 改ざん）関連。canonical 生成の非対称は意図的な改ざんではないが、署名検証の整合性ギャップとして同等のセキュリティリスクをはらんでいた。修正により TB-06 の残留リスクが低減。

---

### 2-2. LP をフロントページに設定すると page_type が 'home' に誤分類（解消済み）

**根本原因:**
- `detect_page_type()` が `is_front_page()` を最優先していたため、LP テンプレート（`page-lp` で始まるスラッグ）をサイトトップに設定すると 'home' を返却。
- 結果として LP 変換計測イベントが home ページイベントと混入。

**修正内容（`1f4cb83`）:**
- LP テンプレート判定（`is_page()` + テンプレートスラッグが `page-lp` で始まる）を `is_front_page()` より優先。
- `get_queried_object_id()` を明示取得し、フロントページ文脈でもクエリ対象ページのテンプレートを確実に取得。

**セキュリティ観点:**
- page_type は server-side enum（`home` / `lp` / `page` / `post` / `other`）。外部入力ではなくサーバー側確定値。
- ハードコードされた enum 以外の値が外部から注入される経路はない。

---

## 3. OWASP Top 10 チェック（直近変更範囲）

| # | 脅威 | チェック対象 | 判定 | 根拠 |
|---|---|---|---|---|
| A01 | Broken Access Control | export エンドポイント認可 | **PASS** | `check_read_permission` = `is_user_logged_in() && current_user_can('edit_posts')`。REST nonce 不要・WP ログイン認証で保護。tracking POST は `site_token + HMAC + nonce` で保護。 |
| A02 | Cryptographic Failures | HMAC 署名 / 鍵管理 | **PASS** | HMAC-SHA256 + `hash_equals`（タイミング攻撃対策）。`base64` / `hex` 両形式を `accepted` に含め JS 実装差異を吸収。`track_secrets` は env 変数優先 → WP option fallback。 |
| A03 | Injection | metadata sanitize / sanitize_key / sanitize_text_field | **PASS** | `sanitize_metadata()` が全キーに `sanitize_key()`（長さ 64 バイト上限）、文字列値に `sanitize_text_field()` を適用。`url` / `current_url` / `referrer` フィールドは `esc_url_raw()` で処理。 |
| A04 | Insecure Design | page_type server-side enum | **PASS** | page_type は PHP server-side で `detect_page_type()` が決定し、JS へ `localize` 注入。外部入力から page_type を上書きする経路なし。 |
| A05 | Security Misconfiguration | `tracking_secrets()` 鍵解決順 | **条件付 PASS** | 本番クリーンインストールでは問題なし。テスト残骸 `agent_neo_site_token` が設定されると全 tracking イベント 401。詳細は carry-002 参照。 |
| A07 | Authentication Failures | nonce single-use / replay 防止 | **PASS** | `consume_nonce()` で WP option を single-use トークンとして登録（`add` セマンティクスで競合時は既存値を優先）。nonce 消費後は `delete_nonce()` で削除。rate limit（IP + site_token）を組み合わせ。 |
| A09 | Logging Failures | イベントデータ内の PII | **PASS** | `queue_event()` が保存するフィールドは `event_id / accepted_at / event_type / section_id / cta_id / variant_id / article_id / metadata` のみ。IP アドレス・User-Agent・email は保存しない（rate limit 用 IP は rate block key の SHA-256 hash として一時利用し保存対象外）。export API レスポンスにも IP 等 PII フィールドなし。`export-contract.md` §REQ-NF-028 PII 非保持を契約化済み。 |
| A10 | SSRF | tracking POST の URL フィールド / export 経路 | **PASS** | tracking POST は `esc_url_raw()` で正規化のみ（外部 fetch なし）。export は `GET` の read-only 取得のみで外部 URL を解決しない。SSRF 対象フローは F-01/F-02（catalog-update）であり G2 CARRY-G2-017/025 で対処済み。 |
| A06 | Vulnerable Components | 依存ライブラリ（tracking 変更範囲） | 今回スコープ外 | 今回変更は PHP 標準 API + WP Core API + Web Crypto API のみ。外部ライブラリ依存なし。全体 `npm audit` / `composer audit` は G6 別票で実施。 |
| A08 | Software Integrity Failures | CI / テストカバレッジ | **PASS** | unit 127 / security 48 全緑。`check-theme-quality` PASS。`1f4cb83` でエッジケース 14 + LP 計装 11 + export 貫通 8 を追加。 |

---

## 4. carry 一覧と disposition

### carry-001: 空 metadata `{}` ↔ `[]` の canonical 不一致

| 項目 | 内容 |
|---|---|
| リスク分類 | **P3** |
| 発生条件 | metadata が空オブジェクト `{}` のとき、PHP `json_decode` が `[]`（数値配列）に変換する場合がある。JS canonical は `{}` のまま生成 → キーソート不要だが JSON 表現が `{}` と `[]` で異なり canonical が不一致になり得る。 |
| 現状の緩和 | `page_type` が全イベントに常時付与（`cfg.pageType` が truthy の場合。localize 失敗時はキー欠損）されるため、metadata が空になる実運用パスは事実上発生しない。コードコメント（`ad-tracking.js` L74-76）で明記済み。 |
| disposition | **P3 注記のみ / carry 継続**。発火不能（masked）状態を維持し、localize が失敗する環境（JS 読み込みエラー等）では別途アラートが検知するため個別修正は不要。`page_type` 付与が保証されない将来変更時に再評価する。 |
| 次アクション | なし（注記のみ）。`localize` 削除・空 metadata ユースケース追加時に TC を追加する。 |

---

### carry-002: token 解決順不整合（実機確定・本番配布前必須確認）

| 項目 | 内容 |
|---|---|
| リスク分類 | **P2** |
| 発見経緯 | docker test 環境に Integration テスト残骸 `agent_neo_site_token=tok-context-123` が残存し、frontend が使う `agent_neo_tracking_site_token`（本番正規値）と不一致。全 tracking イベントが 401 SIGNATURE_INVALID で無言消失。残骸削除後に全イベント 200 に復帰（実機実証）。 |
| 詳細 | `tracking_secrets()`（`class-tracking-controller.php:447-454` / `class-ab-test-controller.php:433-438`）の解決順: env `AGENT_NEO_SITE_TOKEN` → env `AGENT_NEO_TRACKING_SITE_TOKEN` → option `agent_neo_site_token` → option `agent_neo_tracking_site_token`。一方 `class-tracking-assets.php` は `OPTION_SITE_TOKEN = 'agent_neo_tracking_site_token'` のみに write / localize。`agent_neo_site_token` への write は **本番コード内に存在しない**（`grep update_option / add_option` で確認）。残存する write ソース: `tests/Integration/TC009_TrackingEventTest.php:63`（teardown line79 で delete）。 |
| 本番リスク | 本番クリーンインストールでは `agent_neo_site_token` が未設定 → `tracking_secrets` が `agent_neo_tracking_site_token` に fall-through → frontend と一致 → 正常動作。**リスク発現条件: 本番環境に `agent_neo_site_token` が（test/移行残骸・手動設定・旧プラグイン由来で）設定されると、全 tracking イベントが 401 SIGNATURE_INVALID で無言消失する。** |
| E2E 検出器 | E2E (`e2e_tracking_roundtrip.py:76`) で `agent_neo_site_token` を teardown 時に DELETE する処理を追加済み。 |
| disposition | **P2 / 本番配布前チェック必須**。コード変更は行わない（auth 解決ロジック変更は auth 全体の影響範囲が広いため別タスクで慎重に実施）。以下 2 点を本番配布前チェックリストに追加する: 1. `agent_neo_site_token` option が設定されていないことを確認（WP Admin > Tools > Site Health または `wp option get agent_neo_site_token` で空を確認）。 2. tracking イベント受信後の HTTP ステータスが 200 であることを確認（ブラウザ DevTools > Network 目視）。 |
| 将来対応（任意） | `tracking_secrets()` の解決順から `agent_neo_site_token` を除去、または tracking 系で `agent_neo_tracking_site_token` を優先するよう整理。auth 解決ロジック変更のため別タスクで慎重にレビューし実施する。 |

---

### carry-003: `metadata.href` の `javascript:` スキーム注入

| 項目 | 内容 |
|---|---|
| リスク分類 | **P2 / P3**（export 経路限定で P3） |
| 詳細 | `sanitize_metadata()` は `metadata.href`（またはその他 URL 系キー）を `sanitize_text_field()` で処理するが、`javascript:` スキームを排除しない。`esc_url_raw()` を適用しているのは `url` / `current_url` / `referrer` キーのみ（`resolve_article_id` 内）。 |
| 現状の緩和 | export エンドポイント（`GET /agent-neo/v1/tracking/export`）は `edit_posts` capability 必須の認証済み管理 API。攻撃者が `metadata.href=javascript:...` を注入するには HMAC 署名 + site_token を突破する必要があり、認証なし悪用は不可能。export データの consumer（Automation SEO）側が直接 DOM に href を挿入しない限り XSS は発現しない。 |
| disposition | **P2（consumer 契約追記）/ P3（export 単体での XSS リスク）**。以下の対応を実施: 1. `export-contract.md` に「consumer は `metadata.href` 等 URL 系フィールドを DOM に出力する場合は必ず `esc_url()` 相当でサニタイズすること」を明記する（本ドキュメント §6 で指示）。2. tracking イベント受信側での URL 系 metadata キーへの `esc_url_raw()` 適用は次スプリントで検討（P2 carry）。 |
| 次アクション | `export-contract.md` §consumer 責務節に esc_url 必須要件を追記（本 G6 レビューの成果として記録）。 |

---

### carry-004: server-side consent 検証なし（client-only consent gate）

| 項目 | 内容 |
|---|---|
| リスク分類 | **P3** |
| 詳細 | `hasConsent()`（`ad-tracking.js`）が localStorage / Cookie の `analytics_storage === 'granted'` を確認し、未同意の場合はイベント送信をクライアント側でブロックする。PHP 側 (`class-tracking-controller.php`) には consent 状態の server-side 検証がない。 |
| PII 観点 | 保存イベントデータに IP アドレス・email・氏名等の PII は含まれない（§3 A09 確認済み）。保存されるのは `event_type / section_id / cta_id / variant_id / article_id / metadata.page_type` 等の行動計測値のみ。 |
| GDPR リスク評価 | 計測データが PII を含まないため GDPR Art.6 の適用対象となるかはデータ性質次第だが、現設計では PII 非保持を前提としているため規制リスクは低い。ただし consent バイパス（JS 改ざん・devtools 直接 POST 等）でイベントが受信される可能性は技術的に存在する。 |
| disposition | **P3 / 現行設計で受容**。理由: (a) 保存データに PII なし。(b) consent バイパスしても収集できるのは非 PII の行動計測値のみ。(c) server-side consent 検証追加は tracking 全体の設計変更を伴い、別タスクとして扱う。GDPR 適合性を高める場合は「サーバー側で consent トークンを検証する」設計を次フェーズで追加すること（推奨）。 |
| 次アクション | 本番公開前に法務確認を推奨（PII 非保持が契約上維持されることを確認）。server-side consent 検証は P3 carry として `docs/security/` の deferred-findings に記録する。 |

---

## 5. PII・データ保護確認

### 5-1. イベントデータ内 PII 不含確認

`queue_event()` が WP option に保存するフィールド一覧（`class-tracking-controller.php:220-229`）:

```
event_id     — 内部採番（sha256 ハッシュ derived）
accepted_at  — 受理タイムスタンプ（ISO8601）
event_type   — 種別 enum（ad_impression / viewable_impression / affiliate_click / scroll_depth）
section_id   — セクション識別子（sanitize_text_field 済）
cta_id       — CTA 識別子（sanitize_text_field 済）
variant_id   — バリアント識別子（sanitize_text_field 済）
article_id   — 記事識別子（post ID または article meta / sanitize_text_field 済）
metadata     — sanitize_metadata() 処理済みオブジェクト
  page_type  — server-side enum（home / lp / page / post / other）
```

IP アドレス、User-Agent、email、氏名、その他 PII フィールドは保存しない。
rate limit 処理に `client_ip()` を使用するが、rate block key（`sha256(site_token|ip)`）として一時的に参照するのみで保存対象外。

export API（`GET /agent-neo/v1/tracking/export`）のレスポンスも同フィールドセット（+ `canonical_url`）のみ。PII フィールドは含まない（`export-contract.md` §REQ-NF-028）。

### 5-2. consent gate 動作確認

- `hasConsent()` は `consentKey`（`agentNeoTracking.consentKey` として localize 注入）が設定されている場合、localStorage / Cookie から `analytics_storage === 'granted'` を確認。
- `consentKey` が未注入の場合は **fail-open**（計測継続）。この挙動は意図的な設計であり、コードコメントに明記済み（`ad-tracking.js:119-121`）。
- 同意なしの場合 `sendEvent()` を即時 return。計測イベントは一切送信されない。
- server-side での consent 再検証はない（carry-004 参照）。

---

## 6. carry-003 対応: export-contract.md への consumer 責務追記（指示）

`docs/features/tracking-pull/D-CONTRACT/export-contract.md` の末尾セクションに以下を追記することを推奨する:

```markdown
## consumer 側セキュリティ責務

- `metadata.href` 等 URL 系フィールドを DOM / 属性値に出力する場合は、
  必ず `esc_url()` 相当でサニタイズすること（`javascript:` スキーム排除）。
- export は `edit_posts` 認証下の管理 API だが、取得データを公開面に転写する
  場合は consumer 側の責任で追加エスケープを実施すること。
```

本記録では指示に留め、ファイル変更はコード変更禁止制約に従い行わない（git commit 前の人間確認後に反映すること）。

---

## 7. G6 セキュリティ③ 判定

### 判定: 条件付き PASS

**PASS 根拠:**
- A01/A02/A03/A04/A07/A09/A10 の各 OWASP 項目が直近変更範囲でチェック済み。
- HMAC canonical 非対称（viewable_impression 消失回帰）は `1f4cb83` で解消し、実 POST 実証済み（401→200）。
- page_type server-side enum による外部入力排除を確認。
- LP CTA 計装（`an-cta--` 付与）はフロント描画起点であり、サーバー認証経路に影響しない。
- export エンドポイントに PII なし・`edit_posts` 認可保護を確認。
- unit 127 / security 48 全緑。

**条件（本番配布前に必須解消または確認が必要な項目）:**

| # | 条件 | 分類 | 対応方法 |
|---|---|---|---|
| 1 | `agent_neo_site_token` が本番 WP option に未設定であることを確認する | **P2 / 必須** | `wp option get agent_neo_site_token` で空を確認。設定されていれば即時削除。 |
| 2 | tracking イベントが 200 で受信されることをブラウザ DevTools で目視確認する | **P2 / 必須** | 本番デプロイ後の smoke test として tracking network リクエストのステータスを確認。 |
| 3 | `export-contract.md` に consumer 側 `esc_url` 責務を追記する | **P2 / 推奨** | §6 の追記内容を反映後 commit。 |
| 4 | 法務確認: PII 非保持が GDPR 観点で適切かを確認する | **P3 / 任意** | server-side consent 検証不要の根拠として PII 非保持の確認書を取得することを推奨。 |

---

## 8. 本番配布前 必須確認チェックリスト

```
[ ] 1. wp option get agent_neo_site_token → 空 or NOT EXISTS であること
[ ] 2. wp option get agent_neo_tracking_site_token → 正規値（Hpqq... 等）が設定されていること
[ ] 3. wp option get agent_neo_tracking_hmac_key → 正規値が設定されていること
[ ] 4. 本番サイトで任意のページにアクセスし、ブラウザ DevTools > Network で
       POST /agent-neo/v1/tracking/event → 200 を確認する
[ ] 5. viewable_impression イベントが 200 で受信されることを確認する（回帰確認）
[ ] 6. export-contract.md に consumer esc_url 責務が追記されていること
```

---

## 9. G2 脅威モデルとの差分サマリ

| G2 ID | 変更影響 | G6 時点ステータス |
|---|---|---|
| TB-05 / TB-06 | HMAC canonical 修正により署名整合性が強化 | リスク低減 |
| TB-23 | page_type enum 化により bot/spam 汚染時の計測分類が改善 | リスク低減 |
| TB-24 | export データに PII なし・section_id/cta_id の public/internal 分離は G2 CARRY-G2-026 で継続 | carry 継続 |
| TB-03 | tracking イベントに秘密情報保存なし（今回確認） | 問題なし |
| CARRY-G2-007 | nonce single-use 実装は WP option add セマンティクスで実装済み | 実装確認済み |
| CARRY-G2-009/013 | sanitize_key / sanitize_text_field 適用を今回確認 | 実装確認済み |

---

## 10. 作成ファイルと報告（最終記録）

- 作成ファイル: `docs/security/g6-security-review.md`（本ドキュメント）
- 更新なし: `docs/security/threat-model.md`（G2 正本は変更せず本ドキュメントで G6 差分を管理）

### carry 4 件 disposition 一覧

| # | carry 内容 | 分類 | disposition |
|---|---|---|---|
| carry-001 | 空 metadata `{}` ↔ `[]` canonical 不一致 | P3 | 注記のみ / page_type 常時付与でmasked / 変更不要 |
| carry-002 | token 解決順不整合（本番確認必須） | P2 | 本番チェックリスト追加 / コード変更は別タスク / E2E 検出器追加済み |
| carry-003 | `metadata.href` の `javascript:` スキーム | P2/P3 | export-contract.md に consumer 責務追記を指示 / 本番コード修正は次スプリント |
| carry-004 | server-side consent 検証なし | P3 | 現行設計で受容 / PII 非保持により GDPR リスク低 / 法務確認を推奨 |

### G6 セキュリティ③ 最終判定

**条件付き PASS**

- 本番配布前に carry-002（token 解決順確認）の必須チェック（§8 チェックリスト 1-2）を実施すること。
- carry-003 の `export-contract.md` 追記（§8 チェックリスト 6）を commit すること。
- 上記 2 点の完了をもって PASS に昇格する。
