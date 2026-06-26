# AGENT-NEO L6 運用準備レビュー（G6 RC 観点）

- 対象バージョン: v0.1.0
- レビュー日: 2026-06-27
- レビュアー: DevOps / deploy / observability-sre スキル適用
- 対象コンポーネント: agent-neo-theme / agent-neo-core / agent-neo-embed
- 配布モデル: Automation SEO 専用配布（ADR-024）

---

## 判定サマリ

| カテゴリ | 状態 | ブロッカー |
|---------|------|----------|
| 配布手順 | PARTIAL | Critical 1件（zip 生成手順 未文書化） |
| バージョニング | PASS | なし |
| SBOM | PASS | なし（check-sbom-gate.sh 整備済み） |
| ロールバック | PASS | なし（runbook-rollback.md 整備済み） |
| tracking 健全性監視 | CRITICAL | イベント消失を検知する監視手段が未整備 |
| 鍵管理 | IMPORTANT | tracking 系 2 鍵の解決順衝突 / ローテーション手順 未文書化 |
| 互換性 | MINOR | agent-neo-embed に "Tested up to" 行がない |
| 初期セットアップ | PARTIAL | activation 時に tracking トークンが自動生成されない |
| smoke test | PARTIAL | 初回配布後の確認チェックリストが runbook に部分記載のみ |

**G6 RC 判定: 条件付き保留**
本番配布前に Critical 1件・Important 1件を解消すること。

---

## 1. 配布手順

### 充足

- `docs/adr/ADR-024.md`: 配布モデル（Automation SEO 専用・wp.org 非公開・GPL）が確定している。
- `docs/runbook-rollback.md §2.3`: Automation SEO 管理画面経由での再配布手順が記載されている。
- バージョニング: `themes/agent-neo-theme/style.css`・`plugins/agent-neo-core/agent-neo-core.php`・`plugins/agent-neo-embed/agent-neo-embed.php` の Version ヘッダがすべて `0.1.0` で揃っている。
- SBOM: `sbom.cdx.json` + `bin/check-sbom-gate.sh`（5チェック）が整備済み。3 コンポーネント全 CHANGELOG.md も存在。

### 不足

**[Critical] 配布 zip パッケージの生成手順が未文書化**

Automation SEO 管理画面から配布する際に使用するアーカイブ（`agent-neo-core.zip`・`agent-neo-embed.zip`・`agent-neo-theme.zip`）の生成コマンド/スクリプトが存在しない。

- 影響: ロールバック時（§2.3）でも「前バージョン zip の入手元」が不明確。
- 対応案: `bin/build-dist.sh`（zip 生成）を新設し、生成物のチェックサムを SBOM にも記録する。または `docs/L6-ops-runbook.md`（本ドキュメントの §補足）に手順を記述する。

**参照先**: `docs/runbook-rollback.md §2.3`（再配布前提だが zip 生成方法への言及なし）

---

## 2. ロールバック

### 充足

`docs/runbook-rollback.md`（2026-06-21 版）は現構成（agent-neo-core / agent-neo-embed / agent-neo-theme の 3 コンポーネント）と整合している。

- 各コンポーネントの個別ロールバック可否: WP 管理画面・WP-CLI・Automation SEO 経由の 3 経路が記載済み。
- git ロールバック手順（タグ・SBOM 照合）が記載されている。
- smoke test チェックリスト（§5.1）が存在する。
- テーマと plugin の切り替え順序（テーマは別テーマ経由）が明記されている。

### 不足

**[Minor] ロールバック完了通知先が "Automation SEO 管理ログ" と曖昧**

`§5.3` で「Automation SEO 管理ログに記録する」とあるが、ログフォーマット・通知チャネル（Slack 等）が未定義。現状は Manual 運用でも問題ないが、L9 Run 工程で自動監視と連携する際に具体化が必要。

---

## 3. tracking 健全性監視

### 現状

- PULL 型計測ループ（ADR-030）: `GET /agent-neo/v1/tracking/export` で Automation SEO が pull する。
- イベントは `agent_neo_tracking_event_queue` option（最大 100 件・新着 prepend・`array_slice(,100)`）に保持される。各イベント本体は transient（`agent_neo_tracking_event_<sha256 prefix>`）に格納される。
- 今セッションで `viewable_impression` の消失バグが発生した実績がある（part41 教訓）。

### 充足

- `docs/features/tracking-pull/D-CONTRACT/export-contract.md`: スキーマ・カーソル仕様・pagination が文書化されている。
- `docs/features/tracking-pull/D-ACC/acceptance.md`: 受入条件（TP-ACC-001〜005）が定義されている。

### 不足

**[Critical] イベント消失を検知する監視手段が未整備**

以下のいずれの監視も現状存在しない:

| 監視観点 | 現状 | リスク |
|---------|------|-------|
| キュー件数の観測 | 未実装 | option が 100 件で頭打ちになり古いイベントがサイレントドロップされても検知できない |
| pull 頻度の観測 | 未実装 | Automation SEO 側の pull が止まった場合（connection 障害等）に気づかない |
| event_type 別の発生率 | 未実装 | `viewable_impression` が 0 件になっても正常と区別できない |
| transient の存在確認 | 未実装 | transient の TTL 切れによるイベント本体消失が不可視 |
| HMAC 失敗率 | 未実装 | tracking POST への署名エラーが継続していても検知できない |

**対応案**（本番配布前に最低限 1 つを実装または確認手順として文書化すること）:

1. **WP-CLI による手動確認コマンド** を `docs/L6-ops-runbook.md` に記載する（最低限の対応）。
   ```bash
   # キュー件数確認
   wp option get agent_neo_tracking_event_queue --format=json | php -r "echo count(json_decode(file_get_contents('php://stdin'), true));"
   # 直近 export 確認（Automation SEO 接続権限が必要）
   wp eval 'echo wp_remote_retrieve_response_code( wp_remote_get( home_url("/wp-json/agent-neo/v1/tracking/export?limit=5") ) );'
   ```

2. **キュー件数アラート**: キューが 80 件超（80% 閾値）で Slack 通知するフックを実装する（L9 以降での改善課題）。

3. **export 取得ログ**: `GET /tracking/export` 成功時に件数・カーソルを WP ログへ記録する。Automation SEO 側で確認できるようにする。

**参照**: `plugins/agent-neo-core/inc/rest/class-tracking-export-controller.php`（export 処理）、`inc/rest/class-tracking-controller.php §queue_event()`（キュー上限 100 件）

---

## 4. 設定 / シークレット（鍵管理）

### 現状の鍵解決フロー

`class-tracking-controller.php §load_secrets()` での解決順:

```
site_token 解決順:
  ENV('AGENT_NEO_SITE_TOKEN')
  → get_option('agent_neo_site_token')        // 旧キー名
  → get_option('agent_neo_tracking_site_token') // 新キー名

hmac_key 解決順:
  ENV('AGENT_NEO_HMAC_KEY')
  → get_option('agent_neo_tracking_hmac_key') // 新キー名
  → get_option('agent_neo_hmac_key')          // 旧キー名
```

`class-tracking-assets.php §resolve_tokens()` での解決順（JS への localize 用）:

```
OPTION_SITE_TOKEN = 'agent_neo_tracking_site_token'  // 新キー名のみ
OPTION_HMAC_KEY   = 'agent_neo_tracking_hmac_key'   // 新キー名のみ
```

### 充足

- `docs/security/threat-model.md §5.2`: 90 日ローテーション・緊急無効化・移行ウィンドウ（旧鍵 900 秒受付）が設計として確定している。
- `docs/security/threat-model.md §5.3`: L4 carry 受入条件（CARRY-G2-007、CARRY-G2-009 等）が明確。

### 不足

**[Important] tracking 系 2 鍵の解決順が class 間で非対称（security 監査指摘事項）**

- `class-tracking-controller.php`: `agent_neo_site_token`（旧）→ `agent_neo_tracking_site_token`（新）の順で解決する。旧キー名に値があると旧キーが常に優先される。
- `class-tracking-assets.php`: `agent_neo_tracking_site_token`（新）のみを参照する。

新規配布サイトでは旧キーが存在しないため問題は発生しないが、**既存データが混在するサイトでは tracking-controller と tracking-assets で異なるトークンを参照する可能性がある**。この場合、HMAC 検証が全件失敗してイベントが記録されなくなる。

対応案:
- 本番配布前に「旧キー名 option が存在するサイトでの動作」を integration test で確認すること。
- または旧キー名の fallback を削除してキー名を一本化すること。

**[Important] tracking トークン/鍵のローテーション実行手順が未文書化**

`threat-model.md §5.2` に設計は記述されているが、運用手順（いつ・誰が・どのコマンドで実施するか）がない。特に以下が未整備:

- `wp option update agent_neo_tracking_site_token <new-value>` 等の具体的コマンド
- ローテーション後の Automation SEO 側への通知手順
- 旧トークンで送信されてきた既存イベントの扱い（900 秒の移行ウィンドウ中の動作確認）

**[Minor] env 変数による override の本番構成が未文書化**

`class-tracking-controller.php` は `getenv('AGENT_NEO_SITE_TOKEN')` を第一優先で参照するが、WP Docker 環境での env 設定例・推奨方法（wp-config.php 定数 vs OS 環境変数）が未記載。

---

## 5. 互換性

### 充足

| コンポーネント | Requires at least | Tested up to | Requires PHP |
|--------------|------------------|--------------|-------------|
| agent-neo-theme | WP 6.6 | WP 7.0 | PHP 8.1 |
| agent-neo-core | WP 6.6 | — | PHP 8.1 |
| agent-neo-embed | WP 6.3 | — | PHP 8.1 |

FSE テーマとして WP 6.6+ 要件は妥当（FSE GA は WP 5.9 だが Site Editor の成熟度から 6.6 が現実的な下限）。

### 不足

**[Minor] agent-neo-core / agent-neo-embed に "Tested up to" 行がない**

テーマ側は `Tested up to: 7.0` が明記されているが、plugin ヘッダに `Tested up to:` が存在しない。wp.org 非公開であっても、ロールバック手順や互換性確認時の参照情報として明記することを推奨する。

```php
// 追加推奨（例）
 * Tested up to: 7.0
```

---

## 6. データ移行 / 初期化

### 充足

`class-lifecycle.php §activate()` にて activation フックで以下が自動設定される:

- `agent_neo_core_version`
- `agent_neo_license_state`（初期値: `readonly` / `personal` / `not_configured`）
- `agent_neo_feature_flags`
- CPT 登録・rewrite rules フラッシュ

### 不足

**[Important] tracking トークン / hmac_key が activation 時に自動生成されない**

`class-tracking-assets.php §resolve_tokens()` は「取得時に空なら生成して保存」するが、activation フック（`class-lifecycle.php`）では tracking オプション（`agent_neo_tracking_site_token` / `agent_neo_tracking_hmac_key`）が生成されない。

- 影響: 初回有効化後、tracking 関連ページにアクセスするまでトークンが生成されないため、Automation SEO 側が `GET /tracking/export` を呼び出しても `load_secrets()` が失敗しうる。
- 対応案: `Lifecycle::activate()` 内で `Agent_Neo_Core_Tracking_Assets::ensure_tokens()` 相当を呼び出す（or `resolve_tokens()` を activation 時にも呼ぶ）。

---

## 7. デプロイ検証（L9 への布石）

### smoke test 手段

既存の runbook-rollback.md §5 に部分的なチェックリストが存在する。L9 用の初回稼働確認手順として以下を補完する必要がある。

**[Minor] 初回稼働確認チェックリストの不足項目**

現在 runbook-rollback.md §5.1 に記載のある項目:

```
[ ] プラグイン有効 — 管理画面でステータス「有効」
[ ] API 疎通 — GET /agent-neo/v1/status が HTTP 200
[ ] テーマ描画 — PHP fatal error / 白画面がない
[ ] ライセンス — license_state が active / grace
[ ] ブロックカタログ — 管理画面でカタログ表示
```

不足している追加確認項目（L9 / 初回配布時必須）:

```
[ ] tracking トークン生成確認 — wp option get agent_neo_tracking_site_token が非空
[ ] tracking export 疎通 — GET /agent-neo/v1/tracking/export が HTTP 200 を返す（既存接続権限で）
[ ] consent バナー表示 — フロントページで consent バナーが出力される
[ ] CTA data 属性付与 — CTA 要素に data-agent-neo-* が付与されている（TP-ACC-001）
[ ] HMAC 検証サニティ — 正しい site_token + HMAC で POST /tracking/event が 201 / 200 を返す
```

---

## 8. CI / CD パイプライン評価

### 充足

`.github/workflows/` に以下が整備されている:

| workflow | 内容 |
|---------|------|
| `test.yml` | PHPUnit unit / security スイート（PHP 8.1, 8.3 matrix） |
| `phpcs.yml` | PHPCS コーディング規約チェック |
| `theme-quality-gate.yml` | `bin/check-theme-quality.sh`（i18n / RTL / a11y / perf） |
| `embed-isolation.yml` | embed 隔離テスト |

### 不足

**[Minor] SBOM gate が CI に組み込まれていない**

`bin/check-sbom-gate.sh` は存在するが、`.github/workflows/` に SBOM gate ジョブがない。リリースタグ push 時に自動実行される workflow がないため、手動実行を忘れると配布物の SBOM 整合性が保証されない。

**[Minor] zip パッケージ生成 / アーティファクト化 workflow がない**

CI でリリース zip を生成し GitHub Release に添付する workflow（または Automation SEO が取得できるアーティファクト管理）が未整備。

---

## G6 RC 判定詳細

### 本番配布前に必須（Critical / Important）

| 優先 | 項目 | 対応内容 |
|-----|------|---------|
| Critical | zip 生成手順の文書化 | `bin/build-dist.sh` または `docs/L6-ops-runbook.md §配布パッケージ生成` に記述 |
| Critical | tracking イベント消失の最低限監視 | WP-CLI 確認コマンドを `docs/L6-ops-runbook.md` に記載（暫定可） |
| Important | tracking 鍵解決順の非対称修正 | `class-tracking-controller.php §load_secrets()` の旧キー fallback を integration test で検証、または削除 |
| Important | tracking トークンの activation 時自動生成 | `Lifecycle::activate()` に `resolve_tokens()` 呼び出しを追加 |
| Important | ローテーション手順の文書化 | `docs/L6-ops-runbook.md §鍵ローテーション` に具体的コマンドと Automation SEO 側への通知手順を記述 |

### 次サイクル以降（Minor / 任意）

| 優先 | 項目 |
|-----|------|
| Minor | `agent-neo-core` / `agent-neo-embed` に "Tested up to: 7.0" 追記 |
| Minor | SBOM gate を CI workflow に追加 |
| Minor | 初回稼働確認チェックリストを `docs/L6-ops-runbook.md` に追記 |
| Minor | zip 生成 / アーティファクト workflow を CI に追加 |
| Minor（L9 以降） | キュー 80% 超アラートの実装 |

---

## 関連ドキュメント

- `docs/runbook-rollback.md` — ロールバック手順（現状）
- `docs/adr/ADR-024.md` — Automation SEO 専用配布方針
- `docs/adr/ADR-030.md` — PULL 型計測ループ採用
- `docs/features/tracking-pull/D-CONTRACT/export-contract.md` — tracking export 契約
- `docs/security/threat-model.md §5.2` — 鍵管理ライフサイクル
- `plugins/agent-neo-core/inc/tracking/class-tracking-assets.php` — トークン自動生成実装
- `plugins/agent-neo-core/inc/rest/class-tracking-controller.php §load_secrets()` — 鍵解決順実装
- `bin/check-sbom-gate.sh` — SBOM Gate スクリプト
