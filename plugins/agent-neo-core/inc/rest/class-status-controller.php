<?php
/**
 * GET /agent-neo/v1/status controller.
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Core Plugin の status endpoint。
 */
final class Agent_Neo_Core_Status_Controller extends Agent_Neo_Core_REST_Controller_Base {
	/**
	 * Schema loader。
	 *
	 * @var Agent_Neo_Core_Schema_Loader
	 */
	private Agent_Neo_Core_Schema_Loader $schema_loader;

	/**
	 * License state helper。
	 *
	 * @var Agent_Neo_Core_License_State
	 */
	private Agent_Neo_Core_License_State $license_state;

	/**
	 * @param Agent_Neo_Core_Schema_Loader  $schema_loader Schema loader。
	 * @param Agent_Neo_Core_License_State $license_state License state。
	 */
	public function __construct( Agent_Neo_Core_Schema_Loader $schema_loader, Agent_Neo_Core_License_State $license_state ) {
		$this->schema_loader = $schema_loader;
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
	 * GET /status を登録する。
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$this->register_agent_route(
			'/status',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_status' ),
				'permission_callback' => array( $this, 'check_status_permission' ),
			)
		);
	}

	/**
	 * GET /status の閲覧権限を確認する。
	 *
	 * @return true|WP_Error
	 */
	public function check_status_permission() {
		if ( ! is_user_logged_in() ) {
			return Agent_Neo_Core_Auth::error(
				'UNAUTHORIZED',
				__( 'Authentication required for AGENT NEO status.', 'agent-neo-core' )
			);
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return Agent_Neo_Core_Auth::error(
				'FORBIDDEN',
				__( 'Insufficient capability for AGENT NEO status.', 'agent-neo-core' )
			);
		}

		return true;
	}

	/**
	 * status response を返す。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return WP_REST_Response
	 */
	public function get_status( WP_REST_Request $request ): WP_REST_Response {
		$request_id = $request->get_header( 'X-Request-Id' );
		if ( ! is_string( $request_id ) || '' === $request_id ) {
			$request_id = wp_generate_uuid4();
		}

		$data = array(
			'status'              => $this->schema_loader->is_valid() ? 'healthy' : 'degraded',
			'license_mode'        => $this->license_state->license_mode(),
			'package'             => $this->license_state->package(),
			'integration_health'  => array(
				'automation_seo' => $this->license_state->integration_status(),
				'schema'         => $this->schema_loader->is_valid() ? 'ok' : 'degraded',
			),
			'theme'               => $this->theme_summary(),
			'core_plugin_version' => AGENT_NEO_CORE_VERSION,
			'loaded_modules'      => function_exists( 'agent_neo_core_health' ) ? agent_neo_core_health()['loaded_modules'] : array(),
		);

		$response_data = Agent_Neo_Core_Auth::success_response( $data, $request_id );
		$validation    = $this->schema_loader->validate_schema( 'status-response', $response_data );

		if ( is_wp_error( $validation ) ) {
			$response_data['data']['status'] = 'degraded';
			$response_data['data']['integration_health']['schema'] = 'degraded';
		}

		return rest_ensure_response( $response_data );
	}

	/**
	 * AGENT NEO theme の有効状態を返す。
	 *
	 * @return array<string, mixed>
	 */
	private function theme_summary(): array {
		$theme        = wp_get_theme();
		$stylesheet   = $theme->get_stylesheet();
		$template     = $theme->get_template();
		$is_agent_neo = $this->is_agent_neo_theme_slug( $stylesheet ) || $this->is_agent_neo_theme_slug( $template );

		return array(
			'active'     => $is_agent_neo,
			'stylesheet' => $stylesheet,
			'template'   => $template,
			'version'    => (string) $theme->get( 'Version' ),
		);
	}

	/**
	 * Theme slug が AGENT NEO theme か判定する。
	 *
	 * @param string $slug Theme slug。
	 * @return bool
	 */
	private function is_agent_neo_theme_slug( string $slug ): bool {
		return 'agent-neo-theme' === basename( $slug ) || str_ends_with( $slug, '/agent-neo-theme' );
	}
}
