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
 *   - docker-compose up -d（db サービスが稼働していること）
 *   - tests/wp-tests-config.php が存在すること
 *   - vendor/bin/phpunit --testsuite integration
 *
 * CI ではスケルトン（手動トリガーまたは docker service 付き job）でのみ実行。
 * WP 環境（WP_UnitTestCase）が存在しない場合は各テストを markTestSkipped する。
 *
 * @package AgentNeoCore\Tests\Integration
 */

declare( strict_types=1 );

namespace AgentNeo\Tests\Integration;

use PHPUnit\Framework\TestCase;

/**
 * 実 WP 環境でのスモークテスト。
 *
 * WP_UnitTestCase が利用可能な場合のみ実際に検証する。
 * それ以外の環境（ローカル / CI lite）では全テストを skipped にする。
 */
class SmokeTest extends TestCase {

    /**
     * WP 環境が存在するかどうかを返す。
     *
     * @return bool
     */
    private function has_wp_env(): bool {
        return class_exists( 'WP_UnitTestCase' );
    }

    // ------------------------------------------------------------------
    // TC-SMOKE-INT-001: WP DB 接続確認
    // ------------------------------------------------------------------

    /**
     * $wpdb グローバルが存在し、WP テーブルにアクセスできること。
     *
     * @return void
     */
    public function test_wpdb_is_available(): void {
        if ( ! $this->has_wp_env() ) {
            $this->markTestSkipped( 'WP 環境（wp-phpunit）が存在しないためスキップ。' );
        }

        global $wpdb;

        $this->assertInstanceOf(
            \wpdb::class,
            $wpdb,
            '$wpdb が wpdb インスタンスであること'
        );
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
        if ( ! $this->has_wp_env() ) {
            $this->markTestSkipped( 'WP 環境（wp-phpunit）が存在しないためスキップ。' );
        }

        global $agent_neo_core;

        $this->assertTrue(
            class_exists( 'Agent_Neo_Core' ),
            'Agent_Neo_Core クラスが WP 環境で定義されていること'
        );

        $this->assertInstanceOf(
            'Agent_Neo_Core',
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
        if ( ! $this->has_wp_env() ) {
            $this->markTestSkipped( 'WP 環境（wp-phpunit）が存在しないためスキップ。' );
        }

        $this->assertTrue(
            function_exists( 'agent_neo_core_health' ),
            'agent_neo_core_health() 関数が定義されていること'
        );

        $health = agent_neo_core_health();

        $this->assertIsArray( $health );
        $this->assertArrayHasKey( 'loaded', $health );
        $this->assertTrue( $health['loaded'] );
    }
}
