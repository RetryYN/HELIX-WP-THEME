# AGENT NEO WP7 Abilities API PoC

WordPress 6.9.4 / 7.0 (GA) で Abilities API の category 登録、ability 登録、取得、execute を確認するための PoC です。

## 構成

- `zz-abilities-verify.php`
  - `wp_abilities_api_categories_init` で `agent-neo` category を登録する mu-plugin。
  - `wp_abilities_api_init` で `agent-neo/diag-ping` ability を登録する。
  - `execute_callback` は `{"pong":true,"echo":"<input.echo>"}` を返す。
- `RESULTS.md`
  - 2026-06-21 の実測証跡。

## 再現手順

1. Docker Compose で WordPress を起動する。
2. `wp core install` で対象 WordPress を install する。
3. `wp-content/mu-plugins/` を作成する。
4. `zz-abilities-verify.php` を `wp-content/mu-plugins/zz-abilities-verify.php` へ配置する。
5. `wp eval` で `wp_get_ability( 'agent-neo/diag-ping' )` と execute を確認する。

例:

```bash
mkdir -p wp-content/mu-plugins
cp /opt/agent-neo/poc/wp7-abilities/zz-abilities-verify.php wp-content/mu-plugins/

wp eval '
$ability = wp_get_ability( "agent-neo/diag-ping" );
var_dump( $ability ? get_class( $ability ) : null );
var_dump( $ability ? $ability->execute( array( "echo" => "neo7" ) ) : null );
'
```

期待結果:

```json
{"pong":true,"echo":"neo7"}
```

## Docker / WP-CLI 注意点

- `wpcli` コンテナにも `WORDPRESS_DB_HOST` / `WORDPRESS_DB_NAME` / `WORDPRESS_DB_USER` / `WORDPRESS_DB_PASSWORD` を渡す。
- `getenv_docker()` は実行時に環境変数を読むため、WordPress コンテナだけでなく WP-CLI コンテナ側の env も揃える。
- 本 PoC の記録タスクでは WordPress の起動・実行は行わない。構文確認は `php -l poc/wp7-abilities/zz-abilities-verify.php` で行う。
