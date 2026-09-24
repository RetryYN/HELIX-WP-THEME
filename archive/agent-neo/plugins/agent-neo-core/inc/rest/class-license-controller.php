<?php
/**
 * POST /agent-neo/v1/license/validate controller.
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * License validate endpoint。
 */
final class Agent_Neo_Core_License_Controller extends Agent_Neo_Core_REST_Controller_Base {
	private Agent_Neo_Core_Auth $auth;
	private Agent_Neo_Core_License_State $license_state;

	/**
	 * @param Agent_Neo_Core_Auth          $auth Auth helper。
	 * @param Agent_Neo_Core_License_State $license_state License state。
	 */
	public function __construct( Agent_Neo_Core_Auth $auth, Agent_Neo_Core_License_State $license_state ) {
		$this->auth          = $auth;
		$this->license_state = $license_state;
	}

	/**
	 * rest_api_init に route 登録を接続する。
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * POST /license/validate を登録する。
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$this->register_agent_route(
			'/license/validate',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'validate_license' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
			)
		);
	}

	/**
	 * Write permission を確認する。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return true|WP_Error
	 */
	public function check_write_permission( WP_REST_Request $request ) {
		return $this->auth->check_write_permission( $request, 'edit_posts' );
	}

	/**
	 * POST /license/validate。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function validate_license( WP_REST_Request $request ) {
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'JSON body is required.', 'agent-neo-core' ) );
		}

		$result = $this->license_state->validate_license( $params );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$request_id = $request->get_header( 'X-Request-Id' );
		if ( ! is_string( $request_id ) || '' === $request_id ) {
			$request_id = wp_generate_uuid4();
		}

		return rest_ensure_response( Agent_Neo_Core_Auth::success_response( $result, $request_id ) );
	}
}

add_action(
	'agent_neo_core_register_rest',
	static function ( Agent_Neo_Core_Container $container ): void {
		$controller = new Agent_Neo_Core_License_Controller(
			$container->auth(),
			$container->license_state()
		);
		$controller->register();
		$container->register_module( 'rest-license' );
	}
);
