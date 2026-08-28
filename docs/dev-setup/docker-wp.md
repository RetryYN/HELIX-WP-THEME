# Docker WP 検証環境

AGENT NEO テーマ・移行プラグイン・WP CLI 拡張を検証する Docker 環境。

## 第三者テーマの mount と検証スクリプトの設定

`docker-compose.yml` の第三者テーマ mount と `scripts/verify-themes.sh` は実スラッグ・実パスを持たない。`.env.example` を `.env` にコピーし、`THEME_A_SLUG` / `THEME_B_SLUG` / `*_DIR` を自分の環境の値にする（`.env` は gitignore）。`verify-themes.sh` は `THEME_A_SLUG` `THEME_B_SLUG` 未設定なら停止する。

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

## Linux 環境での mount path 修正 (2026-05-20 追記)

`docker-compose.yml` の mount path は Windows ローカル開発想定 (`<local-path>\seo-tool-v2-docs\Automation SEO-v2\wordpress-plugin\seo-tool-connector`) で記述されている。Linux 環境 (= VPS / Docker Linux host) で起動する場合、以下の path 調整が必要。

### 想定 vs Linux 実環境

| mount 対象 | docker-compose.yml 記載 (Windows 想定) | Linux 環境での実 path |
|---|---|---|
| seo-tool-connector | `../seo-tool-v2-docs/Automation SEO/wordpress-plugin/seo-tool-connector` | `../seo-tool/v2/plugin` (= /opt/seo-tool/v2/plugin の隣接想定) |
| themeB theme | `./themeB-2.16.0/themeB` | 開発環境内で別途配置 (= AGENT-NEO repo 外部) |
| themeB_child | `./themeB_child/themeB_child` | 同上 |
| themeA-parent | `./themeA-parent/themeA/themeA` | 同上 |
| themeA-child | `./themeA-child/themeA-child` | 同上 |

### 修正方法

#### 方法 A: docker-compose.yml を直接書き換え (= 個別開発環境用)
個人開発環境の docker-compose.override.yml を作成し、対応 service の volumes を上書きする。

```yaml
# docker-compose.override.yml (個別環境 / gitignore 対象)
services:
  wordpress:
    volumes:
      - ../seo-tool/v2/plugin:/var/www/html/wp-content/plugins/seo-tool-connector:ro
      # themeB / themeA 系は実環境で配置できれば追加、できない場合は省略可
  wpcli:
    volumes:
      - ../seo-tool/v2/plugin:/var/www/html/wp-content/plugins/seo-tool-connector:ro
```

`docker-compose.override.yml` は `.gitignore` 対象として個別開発環境用に保持し、本体の `docker-compose.yml` は Windows ローカル想定のまま維持する。

#### 方法 B: symlink で対応 (= 環境差異吸収)
AGENT-NEO repo の隣接位置に `seo-tool-v2-docs/Automation SEO/wordpress-plugin/seo-tool-connector` の symlink を作成。

```bash
mkdir -p ../seo-tool-v2-docs/"Automation SEO"/wordpress-plugin
ln -s /opt/seo-tool/v2/plugin ../seo-tool-v2-docs/"Automation SEO"/wordpress-plugin/seo-tool-connector
```

#### 推奨: 方法 A (= override file 方式)
- docker-compose.yml 本体を変更しないので git diff が出ない
- 各環境で個別調整可能
- themeB / themeA 系も同様に対応可能

### automation SEO 側 (= /opt/seo-tool) の関係

automation SEO 本体は `git@github.com:RetryYN/Automation-SEO.git` (= 別名 SEO Tool v2 / seo-tool-connector)。`/opt/seo-tool/v2/plugin/` に WP plugin (= automation-seo.php / 26 ルート / v2.0.0) が配置されている。AGENT-NEO の docker-compose.yml が mount しているのは **この既存 plugin**。

L1 要件確定 (2026-05-20 user 判断): AGENT-NEO Core Plugin (= AGENT-NEO テーマ専用 Companion Plugin) は **別 plugin として** AGENT-NEO repo 内 `./plugins/agent-neo-plugins/` 配下に実装予定。Plugin A (= seo-tool-connector / 既存) と Plugin B (= AGENT NEO Core / 未実装) の 2 plugin 分離設計。

参照: automation SEO 側 memory `project_agent_neo_theme.md` §3.1。

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
