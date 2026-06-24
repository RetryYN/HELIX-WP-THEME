<?php
/**
 * Ad Zone CRUD REST controller。
 *
 * CARRY-A2-001: ad-zone.schema.json に基づく広告ゾーン定義の静的管理。
 * GET 一覧 / GET 単体 / POST 作成 / DELETE 削除 の4エンドポイント。
 *
 * データ永続化は WordPress option（`agent_neo_ad_zones`）に JSON 配列として保存する。
 * REQ-NF-025 厳守: AIロジック・モデル呼び出し・統計判定を一切含まない。
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 広告ゾーン管理 REST controller。
 */
final class Agent_Neo_Core_Ad_Zones_Controller extends Agent_Neo_Core_REST_Controller_Base {

	/**
	 * 広告ゾーンを保存する option キー。
	 */
	private const OPTION_KEY = 'agent_neo_ad_zones';

	/**
	 * 許容される position 値。
	 *
	 * @var string[]
	 */
	private const ALLOWED_POSITIONS = array(
		'before_h2',
		'after_article',
		'above_related',
		'category_override',
		'custom',
	);

	/**
	 * zone_id の最大件数（大量登録防止）。
	 */
	private const MAX_ZONES = 50;

	/**
	 * rest_api_init に route 登録を接続する。
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Routes を登録する。
	 *
	 * @return void
	 */
	public function register_routes(): void {
		// GET /ad-zones — 一覧取得。
		$this->register_agent_route(
			'/ad-zones',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'list_zones' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
			)
		);

		// POST /ad-zones — 作成。
		$this->register_agent_route(
			'/ad-zones',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_zone' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
			)
		);

		// GET /ad-zones/{zone_id} — 単体取得。
		$this->register_agent_route(
			'/ad-zones/(?P<zone_id>[a-z0-9_-]+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_zone' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
			)
		);

		// DELETE /ad-zones/{zone_id} — 削除。
		$this->register_agent_route(
			'/ad-zones/(?P<zone_id>[a-z0-9_-]+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_zone' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
			)
		);
	}

	/**
	 * Read permission callback。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return true|WP_Error
	 */
	public function check_read_permission( WP_REST_Request $request ) {
		if ( ! is_user_logged_in() ) {
			return Agent_Neo_Core_Auth::error(
				'UNAUTHORIZED',
				__( 'Authentication required.', 'agent-neo-core' )
			);
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			return Agent_Neo_Core_Auth::error(
				'FORBIDDEN',
				__( 'Insufficient capability to read ad zones.', 'agent-neo-core' )
			);
		}

		return true;
	}

	/**
	 * Write permission callback（管理者のみ）。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return true|WP_Error
	 */
	public function check_write_permission( WP_REST_Request $request ) {
		if ( ! is_user_logged_in() ) {
			return Agent_Neo_Core_Auth::error(
				'UNAUTHORIZED',
				__( 'Authentication required.', 'agent-neo-core' )
			);
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return Agent_Neo_Core_Auth::error(
				'FORBIDDEN',
				__( 'Administrator capability required for ad zone management.', 'agent-neo-core' )
			);
		}

		return true;
	}

	/**
	 * GET /ad-zones — 全ゾーン一覧を返す。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return WP_REST_Response
	 */
	public function list_zones( WP_REST_Request $request ) {
		$zones = $this->load_zones();
		$list  = array_values( $zones );

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response(
				array(
					'zones' => $list,
					'total' => count( $list ),
				),
				wp_generate_uuid4()
			)
		);
	}

	/**
	 * GET /ad-zones/{zone_id} — 単体取得。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_zone( WP_REST_Request $request ) {
		$zone_id = sanitize_key( (string) $request->get_param( 'zone_id' ) );
		$zones   = $this->load_zones();

		if ( ! isset( $zones[ $zone_id ] ) ) {
			return Agent_Neo_Core_Auth::error(
				'NOT_FOUND',
				/* translators: %s: zone_id */
				sprintf( __( 'Ad zone "%s" not found.', 'agent-neo-core' ), esc_html( $zone_id ) )
			);
		}

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response(
				array( 'zone' => $zones[ $zone_id ] ),
				wp_generate_uuid4()
			)
		);
	}

	/**
	 * POST /ad-zones — 新規広告ゾーン作成。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_zone( WP_REST_Request $request ) {
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'JSON body is required.', 'agent-neo-core' )
			);
		}

		// 必須フィールド検証。
		$validation = $this->validate_zone_input( $params );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$zone_id = sanitize_key( (string) $params['zone_id'] );
		$zones   = $this->load_zones();

		// 重複チェック。
		if ( isset( $zones[ $zone_id ] ) ) {
			return Agent_Neo_Core_Auth::error(
				'CONFLICT',
				/* translators: %s: zone_id */
				sprintf( __( 'Ad zone "%s" already exists.', 'agent-neo-core' ), esc_html( $zone_id ) )
			);
		}

		// 件数上限チェック。
		if ( count( $zones ) >= self::MAX_ZONES ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				/* translators: %d: max zones */
				sprintf( __( 'Cannot create more than %d ad zones.', 'agent-neo-core' ), self::MAX_ZONES )
			);
		}

		$now  = gmdate( 'c' );
		$zone = array(
			'zone_id'      => $zone_id,
			'zone_name'    => sanitize_text_field( (string) $params['zone_name'] ),
			'position'     => sanitize_key( (string) $params['position'] ),
			'enabled'      => isset( $params['enabled'] ) ? (bool) $params['enabled'] : true,
			'ad_tag_id'    => isset( $params['ad_tag_id'] ) && is_string( $params['ad_tag_id'] ) ? sanitize_text_field( $params['ad_tag_id'] ) : null,
			'category_ids' => $this->sanitize_category_ids( $params['category_ids'] ?? array() ),
			'priority'     => isset( $params['priority'] ) && is_int( $params['priority'] ) ? max( 1, min( 999, $params['priority'] ) ) : 10,
			'created_at'   => $now,
			'updated_at'   => $now,
		);

		$zones[ $zone_id ] = $zone;
		$this->save_zones( $zones );

		$response = rest_ensure_response(
			Agent_Neo_Core_Auth::success_response(
				array( 'zone' => $zone ),
				wp_generate_uuid4()
			)
		);
		if ( $response instanceof WP_REST_Response ) {
			$response->set_status( 201 );
		}

		return $response;
	}

	/**
	 * DELETE /ad-zones/{zone_id} — 削除。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_zone( WP_REST_Request $request ) {
		$zone_id = sanitize_key( (string) $request->get_param( 'zone_id' ) );
		$zones   = $this->load_zones();

		if ( ! isset( $zones[ $zone_id ] ) ) {
			return Agent_Neo_Core_Auth::error(
				'NOT_FOUND',
				/* translators: %s: zone_id */
				sprintf( __( 'Ad zone "%s" not found.', 'agent-neo-core' ), esc_html( $zone_id ) )
			);
		}

		unset( $zones[ $zone_id ] );
		$this->save_zones( $zones );

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response(
				array(
					'zone_id' => $zone_id,
					'deleted' => true,
				),
				wp_generate_uuid4()
			)
		);
	}

	/**
	 * 入力バリデーション。
	 *
	 * @param array<string, mixed> $params 入力パラメータ。
	 * @return true|WP_Error
	 */
	private function validate_zone_input( array $params ) {
		// zone_id 必須 + 形式チェック。
		if ( empty( $params['zone_id'] ) || ! is_string( $params['zone_id'] ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'zone_id is required.', 'agent-neo-core' ),
				array( 'field' => 'zone_id' )
			);
		}
		if ( ! preg_match( '/^[a-z0-9_-]+$/', $params['zone_id'] ) || strlen( $params['zone_id'] ) > 64 ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'zone_id must be lowercase alphanumeric with underscores/hyphens, max 64 chars.', 'agent-neo-core' ),
				array( 'field' => 'zone_id' )
			);
		}

		// zone_name 必須。
		if ( empty( $params['zone_name'] ) || ! is_string( $params['zone_name'] ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'zone_name is required.', 'agent-neo-core' ),
				array( 'field' => 'zone_name' )
			);
		}

		// position 必須 + enum 検証。
		if ( empty( $params['position'] ) || ! is_string( $params['position'] ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'position is required.', 'agent-neo-core' ),
				array( 'field' => 'position' )
			);
		}
		if ( ! in_array( $params['position'], self::ALLOWED_POSITIONS, true ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'position must be one of: before_h2, after_article, above_related, category_override, custom.', 'agent-neo-core' ),
				array( 'field' => 'position', 'allowed' => self::ALLOWED_POSITIONS )
			);
		}

		return true;
	}

	/**
	 * category_ids を整数配列にサニタイズする。
	 *
	 * @param mixed $value 入力値。
	 * @return int[]
	 */
	private function sanitize_category_ids( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$ids = array();
		foreach ( $value as $id ) {
			if ( is_numeric( $id ) && (int) $id > 0 ) {
				$ids[] = (int) $id;
			}
		}

		return array_values( array_unique( array_slice( $ids, 0, 50 ) ) );
	}

	/**
	 * option からゾーン一覧を読み込む。
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function load_zones(): array {
		$raw = get_option( self::OPTION_KEY, array() );
		return is_array( $raw ) ? $raw : array();
	}

	/**
	 * ゾーン一覧を option に保存する。
	 *
	 * @param array<string, array<string, mixed>> $zones Zones。
	 * @return void
	 */
	private function save_zones( array $zones ): void {
		update_option( self::OPTION_KEY, $zones, false );
	}
}

add_action(
	'agent_neo_core_register_rest',
	static function ( Agent_Neo_Core_Container $container ): void {
		$controller = new Agent_Neo_Core_Ad_Zones_Controller();
		$controller->register();
		$container->register_module( 'rest-ad-zones' );
	}
);
