<?php
/**
 * Ad Tag CRUD REST controller。
 *
 * CARRY-A2-002: agent_neo_ad_tag CPT の CRUD + 一覧 = 5本エンドポイント。
 * GET /ad-tags、POST /ad-tags、GET /ad-tags/{id}、PATCH /ad-tags/{id}、DELETE /ad-tags/{id}。
 *
 * REQ-NF-025 厳守: AIロジック・モデル呼び出し・統計判定を一切含まない。
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 広告タグ CRUD REST controller。
 */
final class Agent_Neo_Core_Ad_Tags_Controller extends Agent_Neo_Core_REST_Controller_Base {

	/**
	 * 一覧取得時のデフォルト件数。
	 */
	private const DEFAULT_PER_PAGE = 20;

	/**
	 * 一覧取得時の最大件数。
	 */
	private const MAX_PER_PAGE = 100;

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
		// GET /ad-tags — 一覧取得。
		$this->register_agent_route(
			'/ad-tags',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'list_tags' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
			)
		);

		// POST /ad-tags — 作成。
		$this->register_agent_route(
			'/ad-tags',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_tag' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
			)
		);

		// GET /ad-tags/{id} — 単体取得。
		$this->register_agent_route(
			'/ad-tags/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_tag' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
			)
		);

		// PATCH /ad-tags/{id} — 部分更新。
		$this->register_agent_route(
			'/ad-tags/(?P<id>\d+)',
			array(
				'methods'             => 'PATCH',
				'callback'            => array( $this, 'update_tag' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
			)
		);

		// DELETE /ad-tags/{id} — 削除（trash へ移動）。
		$this->register_agent_route(
			'/ad-tags/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_tag' ),
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
				__( 'Insufficient capability to read ad tags.', 'agent-neo-core' )
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
				__( 'Administrator capability required for ad tag management.', 'agent-neo-core' )
			);
		}

		return true;
	}

	/**
	 * GET /ad-tags — 一覧取得。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return WP_REST_Response
	 */
	public function list_tags( WP_REST_Request $request ) {
		$per_page = min( self::MAX_PER_PAGE, max( 1, (int) $request->get_param( 'per_page' ) ?: self::DEFAULT_PER_PAGE ) );
		$page     = max( 1, (int) $request->get_param( 'page' ) ?: 1 );
		$ad_type  = $request->get_param( 'ad_type' );

		$args = array(
			'post_type'      => Agent_Neo_Core_Ad_Tag_CPT::POST_TYPE,
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'post_status'    => array( 'publish', 'draft' ),
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		// ad_type フィルター。
		if ( is_string( $ad_type ) && '' !== $ad_type && in_array( $ad_type, Agent_Neo_Core_Ad_Tag_CPT::ALLOWED_TYPES, true ) ) {
			$args['meta_query'] = array(
				array(
					'key'     => '_agent_neo_ad_type',
					'value'   => sanitize_key( $ad_type ),
					'compare' => '=',
				),
			);
		}

		$query = new WP_Query( $args );
		$tags  = array();

		foreach ( $query->posts as $post ) {
			if ( ! ( $post instanceof WP_Post ) ) {
				continue;
			}
			$tags[] = $this->format_tag( $post );
		}

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response(
				array(
					'tags'       => $tags,
					'total'      => (int) $query->found_posts,
					'total_pages' => (int) $query->max_num_pages,
					'page'       => $page,
					'per_page'   => $per_page,
				),
				wp_generate_uuid4()
			)
		);
	}

	/**
	 * GET /ad-tags/{id} — 単体取得。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_tag( WP_REST_Request $request ) {
		$post = $this->get_ad_tag_post( (int) $request->get_param( 'id' ) );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response(
				array( 'tag' => $this->format_tag( $post ) ),
				wp_generate_uuid4()
			)
		);
	}

	/**
	 * POST /ad-tags — 新規作成。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_tag( WP_REST_Request $request ) {
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'JSON body is required.', 'agent-neo-core' )
			);
		}

		$validation = $this->validate_tag_input( $params, true );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'   => Agent_Neo_Core_Ad_Tag_CPT::POST_TYPE,
				'post_title'  => sanitize_text_field( (string) $params['title'] ),
				'post_status' => 'publish',
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return Agent_Neo_Core_Auth::error(
				'INTERNAL_ERROR',
				__( 'Failed to create ad tag.', 'agent-neo-core' )
			);
		}

		$this->save_tag_meta( $post_id, $params );

		$post = get_post( $post_id );
		if ( ! ( $post instanceof WP_Post ) ) {
			return Agent_Neo_Core_Auth::error(
				'INTERNAL_ERROR',
				__( 'Failed to retrieve created ad tag.', 'agent-neo-core' )
			);
		}

		$response = rest_ensure_response(
			Agent_Neo_Core_Auth::success_response(
				array( 'tag' => $this->format_tag( $post ) ),
				wp_generate_uuid4()
			)
		);
		if ( $response instanceof WP_REST_Response ) {
			$response->set_status( 201 );
		}

		return $response;
	}

	/**
	 * PATCH /ad-tags/{id} — 部分更新。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_tag( WP_REST_Request $request ) {
		$post = $this->get_ad_tag_post( (int) $request->get_param( 'id' ) );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'JSON body is required.', 'agent-neo-core' )
			);
		}

		$validation = $this->validate_tag_input( $params, false );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// タイトル更新（指定時のみ）。
		if ( isset( $params['title'] ) && is_string( $params['title'] ) ) {
			wp_update_post(
				array(
					'ID'         => $post->ID,
					'post_title' => sanitize_text_field( $params['title'] ),
				)
			);
		}

		$this->save_tag_meta( $post->ID, $params );

		$updated = get_post( $post->ID );
		if ( ! ( $updated instanceof WP_Post ) ) {
			return Agent_Neo_Core_Auth::error(
				'INTERNAL_ERROR',
				__( 'Failed to retrieve updated ad tag.', 'agent-neo-core' )
			);
		}

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response(
				array( 'tag' => $this->format_tag( $updated ) ),
				wp_generate_uuid4()
			)
		);
	}

	/**
	 * DELETE /ad-tags/{id} — trash へ移動。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_tag( WP_REST_Request $request ) {
		$post = $this->get_ad_tag_post( (int) $request->get_param( 'id' ) );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$result = wp_trash_post( $post->ID );
		if ( ! $result ) {
			return Agent_Neo_Core_Auth::error(
				'INTERNAL_ERROR',
				__( 'Failed to delete ad tag.', 'agent-neo-core' )
			);
		}

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response(
				array(
					'id'      => $post->ID,
					'deleted' => true,
				),
				wp_generate_uuid4()
			)
		);
	}

	/**
	 * ad_tag CPT の投稿を取得して種別を検証する。
	 *
	 * @param int $post_id 投稿 ID。
	 * @return WP_Post|WP_Error
	 */
	private function get_ad_tag_post( int $post_id ) {
		$post = get_post( $post_id );
		if ( ! ( $post instanceof WP_Post ) || Agent_Neo_Core_Ad_Tag_CPT::POST_TYPE !== $post->post_type ) {
			return Agent_Neo_Core_Auth::error(
				'NOT_FOUND',
				/* translators: %d: post ID */
				sprintf( __( 'Ad tag #%d not found.', 'agent-neo-core' ), $post_id )
			);
		}

		if ( 'trash' === $post->post_status ) {
			return Agent_Neo_Core_Auth::error(
				'NOT_FOUND',
				/* translators: %d: post ID */
				sprintf( __( 'Ad tag #%d has been deleted.', 'agent-neo-core' ), $post_id )
			);
		}

		return $post;
	}

	/**
	 * タグ入力バリデーション。
	 *
	 * @param array<string, mixed> $params 入力パラメータ。
	 * @param bool                 $require_all 全フィールド必須（POST 時 true / PATCH 時 false）。
	 * @return true|WP_Error
	 */
	private function validate_tag_input( array $params, bool $require_all ) {
		// title 必須チェック（POST 時のみ）。
		if ( $require_all && ( empty( $params['title'] ) || ! is_string( $params['title'] ) ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'title is required.', 'agent-neo-core' ),
				array( 'field' => 'title' )
			);
		}

		// ad_type 必須チェック（POST 時のみ）。
		if ( $require_all && ( empty( $params['ad_type'] ) || ! is_string( $params['ad_type'] ) ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'ad_type is required.', 'agent-neo-core' ),
				array( 'field' => 'ad_type' )
			);
		}

		// ad_type が指定されている場合は enum チェック。
		if ( isset( $params['ad_type'] ) ) {
			if ( ! in_array( $params['ad_type'], Agent_Neo_Core_Ad_Tag_CPT::ALLOWED_TYPES, true ) ) {
				return Agent_Neo_Core_Auth::error(
					'VALIDATION_ERROR',
					__( 'ad_type must be one of: amazon, affiliate, ranking, normal, text.', 'agent-neo-core' ),
					array( 'field' => 'ad_type', 'allowed' => Agent_Neo_Core_Ad_Tag_CPT::ALLOWED_TYPES )
				);
			}
		}

		// ad_url が指定されている場合は URL 形式チェック。
		if ( isset( $params['ad_url'] ) && is_string( $params['ad_url'] ) && '' !== $params['ad_url'] ) {
			if ( false === filter_var( $params['ad_url'], FILTER_VALIDATE_URL ) ) {
				return Agent_Neo_Core_Auth::error(
					'VALIDATION_ERROR',
					__( 'ad_url must be a valid URL.', 'agent-neo-core' ),
					array( 'field' => 'ad_url' )
				);
			}
		}

		return true;
	}

	/**
	 * タグ meta を保存する（指定されたフィールドのみ更新）。
	 *
	 * @param int                  $post_id Post ID。
	 * @param array<string, mixed> $params 入力パラメータ。
	 * @return void
	 */
	private function save_tag_meta( int $post_id, array $params ): void {
		$meta_map = array(
			'ad_type'       => '_agent_neo_ad_type',
			'ad_code'       => '_agent_neo_ad_code',
			'ad_asin'       => '_agent_neo_ad_asin',
			'ad_url'        => '_agent_neo_ad_url',
			'ad_enabled'    => '_agent_neo_ad_enabled',
			'ad_page_types' => '_agent_neo_ad_page_types',
		);

		foreach ( $meta_map as $param_key => $meta_key ) {
			if ( ! array_key_exists( $param_key, $params ) ) {
				continue;
			}

			$value = $params[ $param_key ];

			switch ( $param_key ) {
				case 'ad_type':
					update_post_meta( $post_id, $meta_key, sanitize_key( (string) $value ) );
					break;
				case 'ad_code':
					update_post_meta( $post_id, $meta_key, wp_kses_post( (string) $value ) );
					break;
				case 'ad_url':
					update_post_meta( $post_id, $meta_key, esc_url_raw( (string) $value ) );
					break;
				case 'ad_enabled':
					update_post_meta( $post_id, $meta_key, $value ? '1' : '0' );
					break;
				case 'ad_asin':
				case 'ad_page_types':
					update_post_meta( $post_id, $meta_key, sanitize_text_field( (string) $value ) );
					break;
			}
		}
	}

	/**
	 * WP_Post を REST レスポンス用配列に整形する。
	 *
	 * @param WP_Post $post Post オブジェクト。
	 * @return array<string, mixed>
	 */
	private function format_tag( WP_Post $post ): array {
		$ad_enabled = get_post_meta( $post->ID, '_agent_neo_ad_enabled', true );

		return array(
			'id'            => $post->ID,
			'title'         => $post->post_title,
			'status'        => $post->post_status,
			'ad_type'       => (string) get_post_meta( $post->ID, '_agent_neo_ad_type', true ),
			'ad_code'       => (string) get_post_meta( $post->ID, '_agent_neo_ad_code', true ),
			'ad_asin'       => (string) get_post_meta( $post->ID, '_agent_neo_ad_asin', true ),
			'ad_url'        => (string) get_post_meta( $post->ID, '_agent_neo_ad_url', true ),
			'ad_enabled'    => '0' !== (string) $ad_enabled,
			'ad_page_types' => (string) get_post_meta( $post->ID, '_agent_neo_ad_page_types', true ),
			'created_at'    => get_post_time( 'c', true, $post ),
			'updated_at'    => get_post_modified_time( 'c', true, $post ),
		);
	}
}

add_action(
	'agent_neo_core_register_rest',
	static function ( Agent_Neo_Core_Container $container ): void {
		// CPT 登録。
		$cpt = new Agent_Neo_Core_Ad_Tag_CPT();
		$cpt->register();

		// REST controller 登録。
		$controller = new Agent_Neo_Core_Ad_Tags_Controller();
		$controller->register();
		$container->register_module( 'rest-ad-tags' );
	}
);
