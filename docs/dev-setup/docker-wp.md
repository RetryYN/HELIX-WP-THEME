# Docker WP 検証環境

AGENT NEO テーマ・移行プラグイン・WP CLI 拡張を検証する Docker 環境。

## 構成

| コンポーネント | バージョン | ポート | 用途 |
|---|---|---|---|
| WordPress | 6.6 + PHP 8.2 + Apache | 8086 | 検証本体 |
| MariaDB | 10.11 | 3308 | DB |
| WP CLI | cli-php8.2 | — | コマンド実行 |

ポート設定は Automation SEO 側（8085 / 3307）と競合しないように 8086 / 3308 を使用。

## ボリュームマウント

```
themes/                                     → wp-content/themes/agent-neo-themes/
plugins/                                    → wp-content/plugins/agent-neo-plugins/
../seo-tool-v2-docs/.../seo-tool-connector  → wp-content/plugins/seo-tool-connector/ (read-only)
```

`themes/` 配下に AGENT NEO テーマ本体を、`plugins/` 配下に移行プラグイン等を配置する想定。
seo-tool-connector は隣接ディレクトリから読み取り専用でマウントされ、統合テストに利用。

## 起動

```bash
# 初回 / 再構築
bash scripts/dev-init.sh

# 以降
docker compose up -d
docker compose down
```

`scripts/dev-init.sh` は以下を実行:

1. db / wordpress サービス起動
2. WP の応答待機
3. `wp core install`（管理者: admin / admin）
4. seo-tool-connector プラグイン有効化
5. パーマリンク `/%postname%/` 設定

## アクセス

| | URL / 接続情報 |
|---|---|
| サイト | http://localhost:8086 |
| 管理画面 | http://localhost:8086/wp-admin/ |
| 認証情報 | admin / admin |
| DB | localhost:3308 / wp / wp |
| REST API | http://localhost:8086/wp-json/wp/v2/ |

## WP CLI

```bash
# テーマ一覧
docker compose run --rm wpcli theme list

# プラグイン一覧
docker compose run --rm wpcli plugin list

# 投稿作成（CLI 経由）
docker compose run --rm wpcli post create --post_type=post --post_title='Test' --post_status=publish

# 任意 WP CLI コマンド
docker compose run --rm wpcli <command>
```

AGENT NEO 自身の `wp agent-neo` 系コマンドは、テーマがインストール後に有効化される（L4 で実装）。

## 削除（完全リセット）

```bash
docker compose down -v
```

`-v` でボリュームも削除されるので、DB / WP コアごと初期化される。

## 想定外時のトラブルシュート

| 症状 | 対処 |
|---|---|
| ポート 8086 / 3308 が既に使用中 | docker-compose.yml の ports を編集、または既存サービスを停止 |
| `wp core install` で既に存在エラー | 想定通り（idempotent。スクリプトは継続） |
| seo-tool-connector が見つからない | `../seo-tool-v2-docs/Automation SEO/wordpress-plugin/seo-tool-connector` の存在を確認 |
| theme/plugin 変更が反映されない | volume の再マウント / コンテナ再起動 `docker compose restart wordpress` |
