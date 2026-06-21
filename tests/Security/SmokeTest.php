<?php
/**
 * Security スイート Smoke Test。
 *
 * 目的:
 *   REST コントローラファイルを静的に走査し、
 *   security ハーネスが起動することを最小限に確認する。
 *
 * このスイートは WP 非依存（Brain Monkey bootstrap を使用）。
 * 個別の認証ロジック・権限昇格テストは Wave 4 で実装する。
 *
 * @package AgentNeoCore\Tests\Security
 */

declare( strict_types=1 );

namespace AgentNeo\Tests\Security;

use Brain\Monkey;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Security ハーネスの起動確認。
 */
class SmokeTest extends TestCase {

    /**
     * @return void
     */
    protected function set_up(): void {
        parent::set_up();
        Monkey\setUp();
    }

    /**
     * @return void
     */
    protected function tear_down(): void {
        Monkey\tearDown();
        parent::tear_down();
    }

    // ------------------------------------------------------------------
    // TC-SMOKE-SEC-001: REST コントローラファイルが存在すること
    // ------------------------------------------------------------------

    /**
     * plugins/agent-neo-core/inc/rest/ に *-controller.php が 1 本以上あること。
     *
     * @return void
     */
    public function test_rest_controller_files_exist(): void {
        $rest_dir   = AGENT_NEO_CORE_DIR . 'inc/rest/';
        $controllers = glob( $rest_dir . '*-controller.php' );

        $this->assertNotEmpty(
            $controllers,
            'REST コントローラファイルが少なくとも 1 本存在すること'
        );
    }

    // ------------------------------------------------------------------
    // TC-SMOKE-SEC-002: Auth クラスが require できること
    // ------------------------------------------------------------------

    /**
     * inc/rest/class-auth.php が構文エラーなく require できること。
     *
     * @return void
     */
    public function test_auth_class_can_be_required(): void {
        $auth_file = AGENT_NEO_CORE_DIR . 'inc/rest/class-auth.php';

        $this->assertFileExists(
            $auth_file,
            'class-auth.php が存在すること'
        );

        if ( ! class_exists( 'Agent_Neo_Core_Auth' ) ) {
            // Auth クラスが依存する定数・関数を Brain Monkey でスタブする。
            // （実際の宣言は bootstrap-unit.php で済み）
            require_once $auth_file;
        }

        $this->assertTrue(
            class_exists( 'Agent_Neo_Core_Auth' ),
            'Agent_Neo_Core_Auth クラスが定義されていること'
        );
    }
}
