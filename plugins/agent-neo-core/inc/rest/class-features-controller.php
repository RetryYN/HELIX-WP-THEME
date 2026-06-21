<?php
/**
 * GET /agent-neo/v1/features controller.
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Package feature flags endpoint.
 *
 * 応答形状は ACC-PF-003 の正本契約に従い package-keyed マップを返す。
 *   include=package（省略時）: アクティブ package のブロックのみ返す
 *   include=all              : personal / corporate 両ブロックを返す
 */
final class Agent_Neo_Core_Features_Controller extends Agent_Neo_Core_REST_Controller_Base {

	/**
	 * 全 flag の required_package 定義。
	 * キー名は ACC-PF-003 pin 済み名称を使用する。
	 */
	private const FEATURE_REQUIREMENTS = array(
		// personal-tier（personal / corporate 両方で true）
		'rest_read'              => 'personal',
		'post_content_edit'      => 'personal',
		'affiliation_blocks'     => 'personal',  // 旧: affiliate_blocks
		'seo_core'               => 'personal',
		'basic_tracking'         => 'personal',
		'settings_io'            => 'personal',
		'ab_testing_limited'     => 'personal',
		// corporate-only（corporate のみ true）
		'corporate_lp'           => 'corporate', // 旧: hp_lp_blueprint
		'lp_sections'            => 'corporate',
		'page_structural_apply'  => 'corporate',
		'corporate_leads'        => 'corporate',
		'service_aware_ia'       => 'corporate',
		'automation_seo_advanced' => 'corporate',
		'job_log_detail'         => 'corporate',
		'multiple_api_keys'      => 'corporate',
		'ab_testing_full'        => 'corporate',
	);

	private Agent_Neo_Core_License_State $license_state;

	public function __construct( Agent_Neo_Core_License_State $license_state ) {
		$this->license_state = $license_state;
	}

	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes(): void {
		$this->register_agent_route(
			'/features',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_features' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
				'args'                => array(
					'include' => array(
						'description'       => '返却する機能フラグ種別。package（省略時）= 現在アクティブな package のブロックのみ。all = personal / corporate 両ブロック。',
						'type'              => 'string',
						'enum'              => array( 'package', 'all' ),
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	public function check_read_permission() {
		if ( ! is_user_logged_in() ) {
			return Agent_Neo_Core_Auth::error( 'UNAUTHORIZED', __( 'Authentication required for AGENT NEO features.', 'agent-neo-core' ) );
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return Agent_Neo_Core_Auth::error( 'FORBIDDEN', __( 'Insufficient capability for AGENT NEO features.', 'agent-neo-core' ) );
		}

		return true;
	}

	/**
	 * GET /features ハンドラ。
	 *
	 * data は ACC-PF-003 の package-keyed マップのみ。
	 * license_mode / readonly_mode / package は /status・/license で取得可能なため含めない。
	 *
	 * @param WP_REST_Request $request リクエスト。
	 * @return WP_REST_Response
	 */
	public function get_features( WP_REST_Request $request ): WP_REST_Response {
		$request_id = $request->get_header( 'X-Request-Id' );
		if ( ! is_string( $request_id ) || '' === $request_id ) {
			$request_id = wp_generate_uuid4();
		}

		// include パラメータ: 省略・'package' どちらも現 package のみ返す。
		$include = $request->get_param( 'include' );
		$package = $this->license_state->package(); // 'personal' | 'corporate'

		if ( 'all' === $include ) {
			// personal / corporate 両ブロックを返す。
			$data = array(
				'personal'  => $this->feature_flags( 'personal' ),
				'corporate' => $this->feature_flags( 'corporate' ),
			);
		} else {
			// 省略または 'package': 現 package のブロックのみ返す。
			// addon は corporate 扱いに正規化済みであることを前提とする。
			$data = array(
				$package => $this->feature_flags( $package ),
			);
		}

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response( $data, $request_id )
		);
	}

	/**
	 * 指定 package ブロックの全 flag bool マップを返す。
	 *
	 * - 'corporate' ブロック: 全 16 flag = true（上位互換）
	 * - 'personal'  ブロック: required_package === 'personal' の flag のみ true
	 *
	 * @param string $package_block 'personal' または 'corporate'。
	 * @return array<string, bool>
	 */
	private function feature_flags( string $package_block ): array {
		$flags = array();
		foreach ( self::FEATURE_REQUIREMENTS as $flag => $required_package ) {
			if ( 'corporate' === $package_block ) {
				// corporate は全機能が有効（personal の上位互換）。
				$flags[ $flag ] = true;
			} else {
				// personal は personal-tier flag のみ true。
				$flags[ $flag ] = ( 'personal' === $required_package );
			}
		}

		return $flags;
	}
}

add_action(
	'agent_neo_core_register_rest',
	static function ( Agent_Neo_Core_Container $container ): void {
		$controller = new Agent_Neo_Core_Features_Controller( $container->license_state() );
		$controller->register();
		$container->register_module( 'rest-features' );
	}
);
