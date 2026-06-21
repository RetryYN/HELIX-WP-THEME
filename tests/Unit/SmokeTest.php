<?php
/**
 * Unit スイート Smoke Test。
 *
 * 目的:
 *   1. Composer autoloader 経由で tests/ のクラスが解決できること
 *   2. Brain Monkey の setUp / tearDown サイクルが正常に回ること
 *   3. WP 関数スタブが Brain Monkey 経由で定義されること
 *   4. プラグインソースクラスを require したときに構文エラーが起きないこと
 *   5. WP 非依存メソッドが期待どおりの値を返すこと
 *
 * ※ WP 依存メソッド（wp_generate_uuid4 を呼ぶフォールバック）は
 *   Brain Monkey でスタブし、Unit スイート内で安全に検証する。
 *
 * @package AgentNeoCore\Tests\Unit
 */

declare( strict_types=1 );

namespace AgentNeo\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Unit ハーネスの起動確認。
 */
class SmokeTest extends TestCase {

    /**
     * Brain Monkey を初期化する。
     *
     * Yoast\PHPUnitPolyfills\TestCases\TestCase は
     * set_up() / tear_down() の PHPUnit 8/9/10 互換エイリアスを提供する。
     *
     * @return void
     */
    protected function set_up(): void {
        parent::set_up();
        Monkey\setUp();
    }

    /**
     * Brain Monkey を後片付けする。
     *
     * @return void
     */
    protected function tear_down(): void {
        Monkey\tearDown();
        parent::tear_down();
    }

    // ------------------------------------------------------------------
    // TC-SMOKE-UNIT-001: Brain Monkey サイクル確認
    // ------------------------------------------------------------------

    /**
     * Brain Monkey の setUp / tearDown が例外なく実行されること。
     *
     * @return void
     */
    public function test_brain_monkey_lifecycle_runs_without_exception(): void {
        // setUp/tearDown は TestCase に委譲済み。
        // ここに到達できれば Brain Monkey の基本サイクルが成立している。
        $this->assertTrue( true );
    }

    // ------------------------------------------------------------------
    // TC-SMOKE-UNIT-002: WP 関数スタブ確認
    // ------------------------------------------------------------------

    /**
     * Brain Monkey で WP 関数をスタブできること。
     *
     * @return void
     */
    public function test_wp_function_can_be_stubbed_via_brain_monkey(): void {
        // Brain Monkey で apply_filters をスタブする。
        Functions\expect( 'apply_filters' )
            ->once()
            ->with( 'agent_neo_test_filter', 'original' )
            ->andReturn( 'filtered' );

        $result = apply_filters( 'agent_neo_test_filter', 'original' );

        $this->assertSame( 'filtered', $result );
    }

    // ------------------------------------------------------------------
    // TC-SMOKE-UNIT-003: Slug クラスのロード確認
    // ------------------------------------------------------------------

    /**
     * Agent_Neo_Core_Slug クラスを require してもエラーが起きないこと。
     *
     * Brain Monkey の setUp 後に ABSPATH が定義されているため、
     * クラスファイル先頭の `! defined( 'ABSPATH' )` ガードを通過できる。
     *
     * @return void
     */
    public function test_slug_class_can_be_required_without_error(): void {
        $slug_file = AGENT_NEO_CORE_DIR . 'inc/util/class-slug.php';

        $this->assertFileExists(
            $slug_file,
            'Agent_Neo_Core_Slug のソースファイルが存在すること'
        );

        if ( ! class_exists( 'Agent_Neo_Core_Slug' ) ) {
            require_once $slug_file;
        }

        $this->assertTrue(
            class_exists( 'Agent_Neo_Core_Slug' ),
            'Agent_Neo_Core_Slug クラスが定義されていること'
        );
    }

    // ------------------------------------------------------------------
    // TC-SMOKE-UNIT-004: Slug の WP 非依存パスを検証
    // ------------------------------------------------------------------

    /**
     * ASCII 入力に対して wp_generate_uuid4 を呼ばずに slug が返ること。
     *
     * フォールバック（UUID 短縮）を踏まない正常系のみ検証する。
     * WP 依存のフォールバックパスは Wave 1 の TC で検証する。
     *
     * @return void
     */
    public function test_sanitize_slug_ascii_input_does_not_need_wp_uuid(): void {
        if ( ! class_exists( 'Agent_Neo_Core_Slug' ) ) {
            require_once AGENT_NEO_CORE_DIR . 'inc/util/class-slug.php';
        }

        // "Hello World!" → "hello-world"（WP 関数呼び出しなし）
        $result = \Agent_Neo_Core_Slug::sanitize_slug( 'Hello World!' );

        $this->assertSame( 'hello-world', $result );
        $this->assertMatchesRegularExpression( '/^[a-z0-9-]+$/', $result );
    }

    /**
     * 空文字列の場合はフォールバックが呼ばれる。
     * Brain Monkey で wp_generate_uuid4 をスタブして安全に検証する。
     *
     * @return void
     */
    public function test_sanitize_slug_empty_input_calls_wp_generate_uuid4(): void {
        if ( ! class_exists( 'Agent_Neo_Core_Slug' ) ) {
            require_once AGENT_NEO_CORE_DIR . 'inc/util/class-slug.php';
        }

        // Brain Monkey で wp_generate_uuid4 をスタブする。
        Functions\expect( 'wp_generate_uuid4' )
            ->once()
            ->andReturn( 'abcd1234-0000-4000-8000-000000000000' );

        // 全非 ASCII → ASCII 除去 → 空 → フォールバック発火。
        $result = \Agent_Neo_Core_Slug::sanitize_slug( '日本語のみ' );

        // UUID 先頭 12 文字（ハイフン除去後）が返ること。
        $this->assertMatchesRegularExpression( '/^[a-z0-9]+$/', $result );
        $this->assertSame( 12, strlen( $result ) );
    }
}
