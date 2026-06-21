# AGENT-NEO リリースロールバック手順書

対象バージョン: v0.1.0 以降
最終更新: 2026-06-21

---

## 1. 対象と前提

### 対象コンポーネント

| コンポーネント | 種別 | 説明 |
|--------------|------|------|
| `agent-neo-core` | プラグイン | ライセンス管理・API 基盤・設定管理 |
| `agent-neo-embed` | プラグイン | AI 生成 HTML 埋め込みブロック（Shadow DOM / sandbox iframe） |
| `agent-neo-theme` | テーマ | SWELL 主軸 × JIN:R ハイブリッド FSE テーマ本体 |

### 配布モデル

- Automation SEO 経由での自動配布（ADR-024 準拠）
- WordPress.org には公開しない（専用配布）
- ユーザーによる手動インストール操作は不要

### ロールバック判断基準

以下のいずれかを満たす場合にロールバックを実施する:

| 状態 | 対応 |
|------|------|
| プラグイン有効化直後に PHP fatal error / デッドループ | 即時ロールバック |
| `GET /agent-neo/v1/status` が 200 以外を返す | 即時ロールバック |
| テーマ有効化後にフロントエンドで 500 / 白画面 | 即時ロールバック |
| ライセンス認証が全テナントで失敗する | 即時ロールバック |
| エラー率 > 2% または p95 レイテンシ > 800ms が 15 分継続 | ロールバック検討 |

---

## 2. プラグイン / テーマのロールバック

### 2.1 WordPress 管理画面から切り戻す（推奨）

1. WordPress 管理画面 > プラグイン にアクセスする
2. 対象プラグイン（agent-neo-core / agent-neo-embed）を「無効化」する
3. 「削除」→ 前バージョンの zip をアップロード・有効化する
4. テーマを切り戻す場合は先に別テーマ（例: Twenty Twenty-Four）へ切り替えてから実施する

> 有効化中テーマは直接削除できない。必ず別テーマへ切り替えてから削除・再インストールを行うこと。

### 2.2 WP-CLI から切り戻す（ SSH アクセスがある場合）

```bash
# プラグインを前バージョンへ強制上書きインストール
wp plugin install <zip-path-or-url> --force --activate

# バージョン指定例（WordPress.org 配布の場合）
wp plugin install agent-neo-core --version=0.1.0 --force

# テーマを前バージョンへ切り戻す（事前に別テーマを有効化しておく）
wp theme activate twentytwentyfour
wp theme install <zip-path-or-url> --force
wp theme activate agent-neo-theme

# プラグイン・テーマのバージョン確認
wp plugin get agent-neo-core --field=version
wp theme get agent-neo-theme --field=version
```

### 2.3 Automation SEO 経由での自動ロールバック（ADR-024）

Automation SEO 管理画面から前バージョンの配布パッケージを再プッシュすることで、対象サイト全体に適用される。

1. Automation SEO 管理画面 > プラグイン配布 にアクセスする
2. `agent-neo-core` / `agent-neo-embed` / `agent-neo-theme` の対象サイトを選択する
3. 「前バージョン（vX.Y.Z）を再配布」を実行する
4. 配布ログで全サイトへの適用完了を確認する

---

## 3. ソース（git）ロールバック

### 3.1 リリースタグへの切り戻し

```bash
# リリースタグ一覧を確認する
git tag -l 'v*' --sort=-version:refname | head -10

# 前バージョン（例: v0.1.0）のコードを確認する
git show v0.1.0 --stat

# 前バージョンへのロールバックコミットを作成する（revert 推奨）
git revert v0.1.1..HEAD --no-commit
git commit -m "rollback: revert to v0.1.0 due to <reason>"

# 緊急時: 前タグのコードを直接チェックアウトして再ビルド
git checkout v0.1.0
# ビルド後、配布パッケージを生成して Automation SEO 経由で再配布する
```

### 3.2 SBOM との照合

各リリースタグに対応する `sbom.cdx.json` はタグ時点のコミットに固定されている。ロールバック先バージョンの SBOM を確認する:

```bash
# ロールバック先バージョンの SBOM を確認する
git show v0.1.0:sbom.cdx.json | jq '.metadata.component.version'

# 現在の SBOM と比較する
diff <(git show v0.1.0:sbom.cdx.json | jq '.components[].name') \
     <(jq '.components[].name' sbom.cdx.json)

# SBOM Gate を再実行してロールバック後の整合性を確認する
bash bin/check-sbom-gate.sh
```

---

## 4. データ / オプションの考慮

### 4.1 agent_neo_* オプション一覧

| オプション名 | 内容 | ロールバック時の影響 |
|-------------|------|------------------|
| `agent_neo_license_state` | ライセンス状態（active / grace / suspended） | 基本は後方互換。破壊的変更時は §4.2 参照 |
| `agent_neo_ctas` | CTA 設定キャッシュ | 後方互換。古いバージョンで自動再生成される |
| `agent_neo_catalog_cache` | ブロックカタログキャッシュ | 後方互換。ロールバック後に再スキャンで更新される |
| `agent_neo_embed_settings` | embed ブロック設定 | 後方互換維持が原則 |

### 4.2 スキーマ非互換が起きた場合

- **原則**: v0.x.y のリリース間では後方互換を維持する
- **破壊的変更が含まれる場合**: リリースノートに `BREAKING CHANGE` を明記し、`CHANGELOG.md` にダウングレード手順を記載する

```bash
# オプション値を手動で確認する（WP-CLI）
wp option get agent_neo_license_state
wp option get agent_neo_catalog_cache

# キャッシュのみ削除してロールバック後の再生成を促す
wp option delete agent_neo_catalog_cache
```

### 4.3 アンインストール時

`uninstall.php`（cleanup-policy）に従ってオプションが削除される。ロールバック前にデータが消えないよう、アンインストールではなく「無効化→旧バージョン有効化」の手順を踏むこと。

---

## 5. 検証（ロールバック後の smoke test）

### 5.1 チェックリスト

```
[ ] プラグイン有効 — WordPress 管理画面でステータス「有効」を確認
[ ] API 疎通 — GET /agent-neo/v1/status が HTTP 200 を返す
[ ] テーマ描画 — フロントページで PHP fatal error / 白画面がない
[ ] ライセンス — license_state が期待値（active / grace）である
[ ] ブロックカタログ — 管理画面でカタログが正常に表示される
```

### 5.2 コマンド例

```bash
# API ヘルスチェック
curl -s -o /dev/null -w "%{http_code}" \
  https://<wp-site>/wp-json/agent-neo/v1/status
# 期待値: 200

# WP-CLI による確認
wp plugin status agent-neo-core
wp plugin status agent-neo-embed
wp theme status agent-neo-theme

# ライセンス状態確認
wp option get agent_neo_license_state

# PHP エラーログ確認（直近 50 行）
tail -50 /var/log/php/error.log | grep -i 'agent.neo\|fatal\|error'
```

### 5.3 ロールバック完了後

1. Automation SEO 管理ログに「rollback 完了 / バージョン vX.Y.Z」を記録する
2. 原因調査 → 修正 PR 作成 → 再リリースの通常フローに戻る
3. `bin/check-sbom-gate.sh` を実行して SBOM Gate が PASS することを確認する

---

## 関連ドキュメント

- `docs/adr/ADR-024-distribution-model.md` — Automation SEO 経由配布方針
- `sbom.cdx.json` — 現在のリリース SBOM
- `bin/check-sbom-gate.sh` — Release/SBOM Gate 検査スクリプト
- `bin/generate-sbom.php` — SBOM 生成スクリプト
