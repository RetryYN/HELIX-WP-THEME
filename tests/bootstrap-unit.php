<?php
/**
 * PHPUnit Unit スイート bootstrap。
 *
 * WP 環境は不要。Brain Monkey で WP 関数をスタブし、
 * Yoast PHPUnit Polyfills で PHPUnit バージョン差を吸収する。
 *
 * 使用方法:
 *   vendor/bin/phpunit --testsuite unit
 *
 * @package AgentNeoCore\Tests
 */

declare( strict_types=1 );

// Composer autoloader。
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// WP クラス・定数スタブ（Brain Monkey が提供しない WP_Error 等）。
require_once __DIR__ . '/stubs/wp-stubs.php';

// Yoast PHPUnit Polyfills は composer の autoload.php 経由で自動登録される。
// v2.x では Autoload::load() を手動呼び出しする必要はなく、
// TestCase が Yoast\PHPUnitPolyfills\TestCases\TestCase を継承するだけで
// set_up() / tear_down() の PHPUnit 8/9/10 互換エイリアスが有効になる。

// テスト実行前に WP 定数の最低限セットを定義する。
// Brain Monkey の Monkey::setUp() が呼ばれるまで WP 関数は未定義のため、
// 定数だけここで定義しておく（各 TestCase の setUp() で Brain Monkey を起動）。
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'WPINC' ) ) {
    define( 'WPINC', 'wp-includes' );
}

if ( ! defined( 'AGENT_NEO_CORE_DIR' ) ) {
    define( 'AGENT_NEO_CORE_DIR', dirname( __DIR__ ) . '/plugins/agent-neo-core/' );
}

if ( ! defined( 'AGENT_NEO_CORE_FILE' ) ) {
    define( 'AGENT_NEO_CORE_FILE', AGENT_NEO_CORE_DIR . 'agent-neo-core.php' );
}

if ( ! defined( 'AGENT_NEO_CORE_VERSION' ) ) {
    define( 'AGENT_NEO_CORE_VERSION', '0.1.0-test' );
}

if ( ! defined( 'AGENT_NEO_CORE_URL' ) ) {
    define( 'AGENT_NEO_CORE_URL', 'http://localhost/' );
}

if ( ! defined( 'WP_DEBUG' ) ) {
    define( 'WP_DEBUG', false );
}
