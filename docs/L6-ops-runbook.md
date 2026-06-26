# AGENT-NEO L6 運用 Runbook（配布・鍵管理・tracking 監視）

対象バージョン: v0.1.0 以降
作成日: 2026-06-27
補足対象: `docs/runbook-rollback.md`（ロールバック手順本体）

本ドキュメントは `runbook-rollback.md` と重複しない範囲で、以下の運用手順を補完する:
1. 配布パッケージの生成手順
2. tracking 健全性の確認手順
3. 鍵ローテーション手順
4. 初回配布後の完全 smoke test チェックリスト

---

## 1. 配布パッケージ生成手順

### 1.1 対象コンポーネントと生成物

| コンポーネント | ディレクトリ | 生成 zip 名 |
|--------------|------------|------------|
| agent-neo-core | `plugins/agent-neo-core/` | `agent-neo-core-v{VERSION}.zip` |
| agent-neo-embed | `plugins/agent-neo-embed/` | `agent-neo-embed-v{VERSION}.zip` |
| agent-neo-theme | `themes/agent-neo-theme/` | `agent-neo-theme-v{VERSION}.zip` |

### 1.2 生成手順（手動）

```bash
# リポジトリルートで実行する
VERSION=$(grep "Version:" themes/agent-neo-theme/style.css | awk '{print $2}')
echo "Build version: ${VERSION}"

# SBOM Gate を先に通過させる
bash bin/check-sbom-gate.sh
# exit 0 以外なら SBOM を再生成してから実施すること
# php bin/generate-sbom.php && bash bin/check-sbom-gate.sh

# 除外対象ファイル（開発専用）
EXCLUDES=(
  "*.git*"
  "node_modules"
  "vendor/bin"
  "tests"
  "tmp"
  "*.dist"
  "*.log"
  ".env*"
  "phpunit*"
  "playwright.config.ts"
)

EXCLUDE_ARGS=()
for excl in "${EXCLUDES[@]}"; do
  EXCLUDE_ARGS+=("--exclude=${excl}")
done

# zip 生成
zip -r "dist/agent-neo-core-v${VERSION}.zip" plugins/agent-neo-core/ "${EXCLUDE_ARGS[@]}"
zip -r "dist/agent-neo-embed-v${VERSION}.zip" plugins/agent-neo-embed/ "${EXCLUDE_ARGS[@]}"
zip -r "dist/agent-neo-theme-v${VERSION}.zip" themes/agent-neo-theme/ "${EXCLUDE_ARGS[@]}"

# チェックサム記録
sha256sum dist/*.zip > dist/SHA256SUMS

echo "生成完了:"
ls -lh dist/*.zip
```

> dist/ ディレクトリが存在しない場合は事前に `mkdir -p dist` を実行すること。

### 1.3 Automation SEO への配布

Automation SEO 管理画面 > プラグイン配布 にアクセスし、生成した zip を以下の順でアップロードする:

1. `agent-neo-core-v{VERSION}.zip` をアップロードし「配布対象サイト」を選択する
2. `agent-neo-embed-v{VERSION}.zip` をアップロードする
3. `agent-neo-theme-v{VERSION}.zip` をアップロードする
4. 配布ログで全サイトへの適用完了を確認する

詳細は `docs/runbook-rollback.md §2.3` を参照すること。

---

## 2. tracking 健全性の確認手順

### 2.1 キュー件数の確認（WP-CLI）

```bash
# SSH または wp-cli コンテナ内で実行する
# キューに保持されているイベント数を確認する
wp option get agent_neo_tracking_event_queue --format=json 2>/dev/null \
  | php -r "echo 'queue count: ' . count(json_decode(file_get_contents('php://stdin'), true)) . PHP_EOL;"

# 期待値: 0〜100。100 に張り付いている場合は Automation SEO 側 pull が停止している可能性がある。
```

### 2.2 tracking export エンドポイントの疎通確認

```bash
# 管理者 Application Password を使用する（<AP> は "xxxx xxxx xxxx xxxx xxxx xxxx" 形式）
WP_URL="https://<wp-site>"
AP_USER="<admin-username>"
AP_PASS="<application-password>"

curl -s -u "${AP_USER}:${AP_PASS}" \
  "${WP_URL}/wp-json/agent-neo/v1/tracking/export?limit=5" \
  | php -r "
    \$r = json_decode(file_get_contents('php://stdin'), true);
    echo 'schema_version: ' . \$r['schema_version'] . PHP_EOL;
    echo 'count: ' . \$r['count'] . PHP_EOL;
    echo 'next_cursor: ' . \$r['next_cursor'] . PHP_EOL;
  "
```

期待値:
- `schema_version: 1`
- `count: 0〜5`（件数はサイト状況による）
- HTTP 200

### 2.3 tracking トークンの存在確認

```bash
# site_token と hmac_key が生成されているか確認する
wp option get agent_neo_tracking_site_token
wp option get agent_neo_tracking_hmac_key
# どちらも非空の文字列が返ること

# もし空の場合: tracking ページにアクセスするか、以下で手動生成する
wp option update agent_neo_tracking_site_token "$(openssl rand -base64 36)"
wp option update agent_neo_tracking_hmac_key "$(openssl rand -base64 36)"
```

### 2.4 イベント消失の兆候チェック

以下を定期的（週次推奨）に実施すること:

```bash
# 1. キューがゼロ件で Automation SEO 側に pull 実績がある → 正常
# 2. キューが 100 件に張り付いている → Automation SEO 側の pull 障害を疑う
# 3. consent granted なのにイベントが来ない → CTA 計装または JS エラーを確認する

# PHP エラーログで tracking 関連エラーを確認する
grep -i 'agent.neo\|tracking\|hmac\|site_token' /var/log/php/error.log | tail -20
```

---

## 3. 鍵ローテーション手順

> 設計の正本: `docs/security/threat-model.md §5.2`（90 日ローテーション・緊急無効化の仕様）

### 3.1 定期ローテーション（90 日サイクル）

```bash
# 新規鍵を生成する
NEW_SITE_TOKEN="$(openssl rand -base64 36)"
NEW_HMAC_KEY="$(openssl rand -base64 36)"

# WP option を更新する（update: 既存値の上書き）
wp option update agent_neo_tracking_site_token "${NEW_SITE_TOKEN}"
wp option update agent_neo_tracking_hmac_key "${NEW_HMAC_KEY}"

# 更新後を確認する
wp option get agent_neo_tracking_site_token
wp option get agent_neo_tracking_hmac_key
```

ローテーション後に必ず実施すること:

1. Automation SEO 管理画面でこのサイトの接続情報を再取得させる（pull 接続を使うため自動で新鍵を参照する）。
2. 旧鍵で発行済みの HMAC 付き tracking イベントは 900 秒の移行ウィンドウで処理される（threat-model §5.2 参照）。
3. ローテーション日時を `docs/security/key-rotation-log.md`（存在しない場合は新規作成）に記録する。

### 3.2 緊急無効化（鍵漏えい時）

```bash
# 即時新規鍵に差し替える（移行ウィンドウなし）
wp option update agent_neo_tracking_site_token "$(openssl rand -base64 36)"
wp option update agent_neo_tracking_hmac_key "$(openssl rand -base64 36)"

# 差し替え後、Automation SEO 側に連絡して再接続を実施させる
# 監査ログに hmac_key_revoked イベントとして記録すること
wp option update agent_neo_security_audit_log \
  "$(date -u +%Y-%m-%dT%H:%M:%SZ) EMERGENCY ROTATION: site_token + hmac_key revoked"
```

### 3.3 鍵名の解決順に関する注意事項

`class-tracking-controller.php §load_secrets()` は以下の順で解決する（2026-06-27 時点）:

```
site_token: ENV('AGENT_NEO_SITE_TOKEN') → agent_neo_site_token → agent_neo_tracking_site_token
hmac_key:   ENV('AGENT_NEO_HMAC_KEY')  → agent_neo_tracking_hmac_key → agent_neo_hmac_key
```

注意: `agent_neo_site_token`（旧キー名）が DB に存在すると、`agent_neo_tracking_site_token`（新キー名）より優先される。`class-tracking-assets.php` は新キー名のみを参照するため、両 class でトークンが食い違う可能性がある。

**確認コマンド**:

```bash
# 旧キー名の存在確認
OLD_ST=$(wp option get agent_neo_site_token 2>/dev/null)
OLD_HK=$(wp option get agent_neo_hmac_key 2>/dev/null)

if [ -n "${OLD_ST}" ]; then
  echo "[WARNING] agent_neo_site_token (旧キー) が存在します: ${OLD_ST:0:8}..."
  echo "新キー agent_neo_tracking_site_token との一致を確認してください"
fi

if [ -n "${OLD_HK}" ]; then
  echo "[WARNING] agent_neo_hmac_key (旧キー) が存在します"
fi
```

旧キーが存在する場合は、新旧の値を一致させるか旧キーを削除してから運用すること:

```bash
# 旧キー削除（新キー名に統一する）
wp option delete agent_neo_site_token
wp option delete agent_neo_hmac_key
```

---

## 4. 初回配布後の完全 smoke test チェックリスト

`docs/runbook-rollback.md §5.1` に加え、初回配布時は以下も確認すること。

```markdown
## 初回配布 smoke test チェックリスト（L9 観点）

### 基本（runbook-rollback.md §5.1 より）
[ ] プラグイン有効 — WordPress 管理画面でステータス「有効」を確認
[ ] API 疎通 — GET /agent-neo/v1/status が HTTP 200 を返す
[ ] テーマ描画 — フロントページで PHP fatal error / 白画面がない
[ ] ライセンス — agent_neo_license_state が期待値（active / grace）である
[ ] ブロックカタログ — 管理画面でカタログが正常に表示される

### tracking 系（本 runbook §2 追加）
[ ] tracking トークン生成 — agent_neo_tracking_site_token が非空
[ ] tracking hmac_key 生成 — agent_neo_tracking_hmac_key が非空
[ ] export 疎通 — GET /agent-neo/v1/tracking/export が HTTP 200 を返す（schema_version: 1）
[ ] consent バナー — フロントページで consent バナーが出力される
[ ] CTA 計装 — CTA 要素に data-agent-neo-ad または data-agent-neo-affiliate が付与される
[ ] HMAC 検証 — POST /agent-neo/v1/tracking/event に正しい site_token + HMAC で送信 → 201 / 200

### 互換性
[ ] WP バージョン確認 — 6.6 以上（Requires at least 充足）
[ ] PHP バージョン確認 — 8.1 以上（Requires PHP 充足）
[ ] admin ユーザー確認 — Application Password が発行されている（Automation SEO 接続用）
```

---

## 5. 関連ドキュメント

- `docs/runbook-rollback.md` — ロールバック手順（本 runbook と役割分担）
- `docs/adr/ADR-030.md` — PULL 型計測ループ
- `docs/features/tracking-pull/D-CONTRACT/export-contract.md` — export API 契約
- `docs/security/threat-model.md §5.2` — 鍵管理ライフサイクル（設計正本）
- `bin/check-sbom-gate.sh` — SBOM Gate 検査スクリプト
- `docs/L6-ops-readiness-review.md` — L6 運用準備レビュー（不足項目一覧）
