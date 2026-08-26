<?php
/**
 * REST write permission の Application Password 境界テスト。
 *
 * @package AgentNeoCore\Tests\Security
 */

declare( strict_types=1 );

namespace AgentNeo\Tests\Security;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Yoast\PHPUnitPolyfills\TestCases\TestCase;

/**
 * Cookie nonce と Application Password の認証分岐を固定する。
 */
final class TC_ApplicationPasswordPermissionTest extends TestCase {
	protected function set_up(): void {
		parent::set_up();
		Monkey\setUp();

		if ( ! class_exists( 'Agent_Neo_Core_Auth' ) ) {
			require_once AGENT_NEO_CORE_DIR . 'inc/rest/class-auth.php';
		}
	}

	protected function tear_down(): void {
		Monkey\tearDown();
		parent::tear_down();
	}

	public function test_application_password_authentication_does_not_require_cookie_nonce(): void {
		Functions\expect( 'did_action' )
			->once()
			->with( 'application_password_did_authenticate' )
			->andReturn( 1 );
		Functions\expect( 'wp_verify_nonce' )->never();
		Functions\expect( 'current_user_can' )
			->once()
			->with( 'edit_posts' )
			->andReturn( true );

		$request = new \WP_REST_Request( 'POST' );
		$auth    = new \Agent_Neo_Core_Auth();

		$this->assertTrue( $auth->check_write_permission( $request ) );
	}

	public function test_cookie_authentication_still_rejects_missing_nonce(): void {
		Functions\expect( 'did_action' )
			->once()
			->with( 'application_password_did_authenticate' )
			->andReturn( 0 );
		Functions\expect( '__' )
			->once()
			->andReturnFirstArg();
		Functions\expect( 'current_user_can' )->never();

		$request = new \WP_REST_Request( 'POST' );
		$auth    = new \Agent_Neo_Core_Auth();
		$result  = $auth->check_write_permission( $request );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'UNAUTHORIZED', $result->get_error_code() );
		$this->assertSame( 401, $result->get_error_data()['status'] );
	}
}
