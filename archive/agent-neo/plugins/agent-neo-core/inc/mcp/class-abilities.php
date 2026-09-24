<?php
/**
 * WP Abilities API アダプタ（MCP 操作面）。
 *
 * ADR-020「READ を Abilities+MCP で公開」に基づき、
 * WP 6.9+ の wp_register_ability() を使って agent-neo カテゴリを登録し、
 * status / features の 2 ability を薄いアダプタとして実装する。
 *
 * 各 execute_callback は内部で rest_do_request() を呼び、
 * 既存 REST ルートへ委譲することで同一 JSON 契約（StandardResponse）を再利用する。
 * 判断ロジックは一切持たない（REQ-NF-025 準拠）。
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WP Abilities API 登録クラス。
 *
 * - カテゴリ: `agent-neo`
 * - ability:  `agent-neo/status`  → REST GET /agent-neo/v1/status へ委譲
 * - ability:  `agent-neo/features` → REST GET /agent-neo/v1/features へ委譲
 *
 * WP 6.9 未満では function_exists ガードにより登録をスキップする。
 */
final class Agent_Neo_Core_Abilities {

	/**
	 * Ability カテゴリ slug。
	 */
	private const CATEGORY_SLUG = 'agent-neo';

	/**
	 * REST namespace。
	 */
	private const REST_NAMESPACE = 'agent-neo/v1';

	/**
	 * WordPress フックに登録する。
	 *
	 * @return void
	 */
	public function register(): void {
		// WP 6.9+ 未満では Abilities API が存在しないためスキップ。
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
	}

	/**
	 * agent-neo カテゴリを登録する。
	 *
	 * wp_register_ability() は category が未登録だと null を返すため、
	 * abilities_init より先に categories_init で登録する。
	 *
	 * @return void
	 */
	public function register_category(): void {
		wp_register_ability_category(
			self::CATEGORY_SLUG,
			array(
				'label'       => 'AGENT NEO',
				'description' => 'AGENT NEO theme operations', // description 必須。
			)
		);
	}

	/**
	 * agent-neo ability 群を登録する。
	 *
	 * @return void
	 */
	public function register_abilities(): void {
		// ---- agent-neo/status ------------------------------------------------
		wp_register_ability(
			'agent-neo/status',
			array(
				'label'              => __( 'AGENT NEO Status', 'agent-neo-core' ),
				'description'        => __( 'Returns plugin health, license mode, integration status, and active theme summary.', 'agent-neo-core' ),
				'category'           => self::CATEGORY_SLUG,
				'execute_callback'   => array( $this, 'execute_status' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
				// WP 6.9+ は execute() 呼び出し時に input_schema による検証を要求する。
				// status は引数なしのため空オブジェクトスキーマを宣言する。
				'input_schema'       => array(
					'type'       => 'object',
					'properties' => array(),
				),
			)
		);

		// ---- agent-neo/features ----------------------------------------------
		wp_register_ability(
			'agent-neo/features',
			array(
				'label'              => __( 'AGENT NEO Features', 'agent-neo-core' ),
				'description'        => __( 'Returns package feature flags. Optional include parameter accepts "package" (default) or "all".', 'agent-neo-core' ),
				'category'           => self::CATEGORY_SLUG,
				'execute_callback'   => array( $this, 'execute_features' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
				'input_schema'       => array(
					'type'       => 'object',
					'properties' => array(
						'include' => array(
							'type'        => 'string',
							'enum'        => array( 'package', 'all' ),
							'description' => 'Feature flag scope. "package" returns active package only; "all" returns personal and corporate blocks.',
						),
					),
				),
			)
		);
	}

	/**
	 * READ 相当の permission callback。
	 *
	 * edit_posts capability を要求する（REST /status・/features と同一ガード）。
	 *
	 * @param mixed $args Ability 引数（未使用）。
	 * @return bool|WP_Error
	 */
	public function check_read_permission( $args ) {
		if ( ! is_user_logged_in() ) {
			return Agent_Neo_Core_Auth::error(
				'UNAUTHORIZED',
				__( 'Authentication required for AGENT NEO abilities.', 'agent-neo-core' )
			);
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return Agent_Neo_Core_Auth::error(
				'FORBIDDEN',
				__( 'Insufficient capability for AGENT NEO abilities.', 'agent-neo-core' )
			);
		}

		return true;
	}

	/**
	 * agent-neo/status の execute callback。
	 *
	 * 内部で rest_do_request() を呼び、GET /agent-neo/v1/status の
	 * StandardResponse をそのまま返す（同一 JSON 契約再利用）。
	 *
	 * @param mixed $args Ability 引数（未使用）。
	 * @return array<string, mixed>|WP_Error
	 */
	public function execute_status( $args ) {
		$request = new WP_REST_Request( 'GET', '/' . self::REST_NAMESPACE . '/status' );
		$request->set_header( 'X-Request-Id', wp_generate_uuid4() );

		return $this->dispatch_rest( $request );
	}

	/**
	 * agent-neo/features の execute callback。
	 *
	 * 引数 include を REST クエリパラメータに転写し、
	 * GET /agent-neo/v1/features へ委譲する。
	 *
	 * @param mixed $args Ability 引数。include キーを受け付ける。
	 * @return array<string, mixed>|WP_Error
	 */
	public function execute_features( $args ) {
		$request = new WP_REST_Request( 'GET', '/' . self::REST_NAMESPACE . '/features' );
		$request->set_header( 'X-Request-Id', wp_generate_uuid4() );

		// include パラメータを転写する（未指定時は REST デフォルト動作に委ねる）。
		if ( is_array( $args ) && isset( $args['include'] ) && is_string( $args['include'] ) ) {
			$request->set_param( 'include', sanitize_text_field( $args['include'] ) );
		}

		return $this->dispatch_rest( $request );
	}

	/**
	 * 内部 REST dispatch の共通処理。
	 *
	 * rest_do_request() で実行し、StandardResponse データを返す。
	 * REST エラーは WP_Error で返してコール元へ伝播させる。
	 *
	 * @param WP_REST_Request $request REST リクエスト。
	 * @return array<string, mixed>|WP_Error
	 */
	private function dispatch_rest( WP_REST_Request $request ) {
		$response = rest_do_request( $request );

		// HTTP エラー（4xx/5xx）は WP_Error に変換して返す。
		if ( $response->is_error() ) {
			$data    = $response->get_data();
			$code    = is_array( $data ) && isset( $data['error']['code'] ) ? $data['error']['code'] : 'INTERNAL_ERROR';
			$message = is_array( $data ) && isset( $data['error']['message'] ) ? $data['error']['message'] : __( 'REST dispatch failed.', 'agent-neo-core' );

			return Agent_Neo_Core_Auth::error( (string) $code, (string) $message );
		}

		// StandardResponse 全体を返す（success / data / meta / error キーを保持）。
		return (array) $response->get_data();
	}
}

// agent_neo_core_register_rest アクション内で container 経由登録する。
add_action(
	'agent_neo_core_register_rest',
	static function ( Agent_Neo_Core_Container $container ): void {
		$abilities = new Agent_Neo_Core_Abilities();
		$abilities->register();
		$container->register_module( 'mcp-abilities' );
	}
);
