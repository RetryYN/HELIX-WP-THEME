<?php
/**
 * PHPUnit Integration スイート bootstrap。
 *
 * 実 WordPress DB 環境（wp-phpunit）を起動し、プラグインを有効化する。
 * CI では docker-compose.yml の wordpress サービスが稼働している前提。
 *
 * 環境変数（.env.testing または CI 変数で設定）:
 *   WP_PHPUNIT__TESTS_CONFIG  — wp-tests-config.php へのパス（既定: tests/wp-tests-config.php）
 *   AGENT_NEO_WP_DB_HOST      — DB ホスト（既定: 127.0.0.1）
 *   AGENT_NEO_WP_DB_PORT      — DB ポート（既定: 3308 ← docker-compose 設定に合わせる）
 *   AGENT_NEO_WP_DB_USER      — DB ユーザ（既定: wp）
 *   AGENT_NEO_WP_DB_PASSWORD  — DB パスワード（既定: wp）
 *   AGENT_NEO_WP_DB_NAME      — DB 名（既定: agent_neo）
 *
 * 使用方法:
 *   vendor/bin/phpunit --testsuite integration
 *
 * @package AgentNeoCore\Tests
 */

declare( strict_types=1 );

// Composer autoloader。
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Yoast PHPUnit Polyfills は composer の autoload.php 経由で自動登録される。
// v2.x では Autoload::load() の手動呼び出しは不要。

// wp-phpunit のインストールパスを解決する。
// wp-phpunit は vendor/wp-phpunit/wp-phpunit に展開される。
$wp_phpunit_dir = dirname( __DIR__ ) . '/vendor/wp-phpunit/wp-phpunit';

if ( ! is_dir( $wp_phpunit_dir ) ) {
    echo "ERROR: wp-phpunit が vendor に見つかりません。\n";
    echo "  composer install を実行してください。\n";
    exit( 1 );
}

// WP テスト設定ファイルへのパスを環境変数から取得する。
// 存在しない場合は tests/wp-tests-config.php を使用する。
$wp_tests_config = getenv( 'WP_PHPUNIT__TESTS_CONFIG' );
if ( ! $wp_tests_config ) {
    $wp_tests_config = dirname( __DIR__ ) . '/tests/wp-tests-config.php';
}

if ( ! file_exists( $wp_tests_config ) ) {
    echo "WARNING: {$wp_tests_config} が存在しません。\n";
    echo "  tests/wp-tests-config.php.example をコピーして設定してください。\n";
    echo "  統合テストをスキップします。\n";
    // 統合テストは設定ファイルがなければスキップ。exit しない（unit は走る）。
    return;
}

// wp-tests-config.php を環境変数に流し込む。
putenv( "WP_PHPUNIT__TESTS_CONFIG={$wp_tests_config}" );

// WP テストスイートを起動する。
require $wp_phpunit_dir . '/includes/functions.php';

/**
 * プラグインを WP テスト環境でロードするコールバック。
 *
 * @return void
 */
function agent_neo_load_plugin_for_tests(): void {
    require dirname( __DIR__ ) . '/plugins/agent-neo-core/agent-neo-core.php';
}
tests_add_filter( 'muplugins_loaded', 'agent_neo_load_plugin_for_tests' );

// WP テストスイートを起動する。
require $wp_phpunit_dir . '/includes/bootstrap.php';
