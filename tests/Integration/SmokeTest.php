<?php
/**
 * Integration スイート Smoke Test。
 *
 * 目的:
 *   1. bootstrap-integration.php が正常に読み込まれること
 *   2. wp-phpunit の WP_UnitTestCase が利用可能なこと
 *   3. WP DB 接続が成立していること（wpdb グローバルが存在すること）
 *   4. プラグインが WP 環境に読み込まれていること
 *
 * 実行前提:
 *   - wordpress_test DB が作成済みであること
 *   - tests/wp-tests-config.php が存在すること（.gitignore 済）
 *   - vendor/bin/phpunit --testsuite integration
 *
 * WP_UnitTestCase を継承することで、実 WP 環境（wpdb・REST サーバ等）を
 * 各テストで利用できる。
 *
 * @package AgentNeoCore\Tests\Integration
 */

declare( strict_types=1 );

/**
 * 実 WP 環境でのスモークテスト。
 *
 * WP_UnitTestCase を継承して実 WordPress 機能を検証する。
 * bootstrap-integration.php が正常に動作している場合のみクラスが利用可能。
 */
class SmokeTest extends WP_UnitTestCase {

    // ------------------------------------------------------------------
    // TC-SMOKE-INT-001: WP DB 接続確認
    // ------------------------------------------------------------------

    /**
     * $wpdb グローバルが存在し、WP テーブルにアクセスできること。
     *
     * @return void
     */
    public function test_wpdb_is_available(): void {
        global $wpdb;

        $this->assertInstanceOf(
            wpdb::class,
            $wpdb,
            '$wpdb が wpdb インスタンスであること'
        );

        // wptests_options テーブルが存在すること（WP インストール済みの確認）。
        $tables = $wpdb->get_col( 'SHOW TABLES' );
        $this->assertNotEmpty( $tables, 'テスト DB にテーブルが存在すること' );
    }

    // ------------------------------------------------------------------
    // TC-SMOKE-INT-002: プラグインロード確認
    // ------------------------------------------------------------------

    /**
     * agent-neo-core プラグインがロードされ、
     * global $agent_neo_core が Agent_Neo_Core インスタンスであること。
     *
     * @return void
     */
    public function test_plugin_is_loaded(): void {
        global $agent_neo_core;

        $this->assertTrue(
            class_exists( 'Agent_Neo_Core' ),
            'Agent_Neo_Core クラスが WP 環境で定義されていること'
        );

        $this->assertInstanceOf(
            Agent_Neo_Core::class,
            $agent_neo_core,
            'グローバル $agent_neo_core が Agent_Neo_Core インスタンスであること'
        );
    }

    // ------------------------------------------------------------------
    // TC-SMOKE-INT-003: health() が基本構造を返すこと
    // ------------------------------------------------------------------

    /**
     * agent_neo_core_health() 関数が loaded=true を返すこと。
     *
     * @return void
     */
    public function test_health_returns_loaded_true(): void {
        $this->assertTrue(
            function_exists( 'agent_neo_core_health' ),
            'agent_neo_core_health() 関数が定義されていること'
        );

        $health = agent_neo_core_health();

        $this->assertIsArray( $health );
        $this->assertArrayHasKey( 'loaded', $health );
        $this->assertTrue( $health['loaded'] );
    }

    // ------------------------------------------------------------------
    // TC-SMOKE-INT-004: home_url() が値を返すこと
    // ------------------------------------------------------------------

    /**
     * 実 WP 環境で home_url() が空でない文字列を返すこと。
     *
     * @return void
     */
    public function test_home_url_returns_value(): void {
        $url = home_url();

        $this->assertIsString( $url, 'home_url() が文字列を返すこと' );
        $this->assertNotEmpty( $url, 'home_url() が空でないこと' );
    }

    // ------------------------------------------------------------------
    // TC-SMOKE-INT-005: /agent-neo/v1 名前空間が REST サーバに登録されていること
    // ------------------------------------------------------------------

    /**
     * REST API サーバに /agent-neo/v1 名前空間が登録されていること。
     *
     * @return void
     */
    public function test_rest_namespace_registered(): void {
        // do_action( 'rest_api_init' ) を発火して REST コントローラを初期化する。
        do_action( 'rest_api_init' );

        $server     = rest_get_server();
        $namespaces = $server->get_namespaces();

        $this->assertContains(
            'agent-neo/v1',
            $namespaces,
            'REST サーバに agent-neo/v1 名前空間が登録されていること'
        );
    }
}
