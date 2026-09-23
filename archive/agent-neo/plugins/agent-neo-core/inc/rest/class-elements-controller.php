<?php
/**
 * POST /elements/swap controller。
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 要素差し替え汎用 API。
 *
 * link_id / banner_id / media_id / blueprint_id / reusable_part_id の
 * swap に対応する。diff_hash + idempotency_key 必須。
 * リクエストで渡された確定済み要素を静的に差し替え、
 * rollback point 保存と audit 記録を行う。AI ロジックは持たない。
 */
final class Agent_Neo_Core_Elements_Controller extends Agent_Neo_Core_REST_Controller_Base {

	/**
	 * サポートする element_type 一覧。
	 *
	 * @var array<string, string> element_type => post_meta キー
	 */
	private const ELEMENT_TYPES = array(
		'link_id'          => '_agent_neo_element_link',
		'banner_id'        => '_agent_neo_element_banner',
		'media_id'         => '_agent_neo_element_media',
		'blueprint_id'     => '_agent_neo_element_blueprint',
		'reusable_part_id' => '_agent_neo_element_reusable_part',
	);

	/**
	 * Rollback point の保存先 post_meta キー。
	 *
	 * @var string
	 */
	private const ROLLBACK_META_KEY = '_agent_neo_elements_rollback_points';

	/**
	 * rollback point の最大保持数。
	 *
	 * @var int
	 */
	private const MAX_ROLLBACKS = 30;

	private Agent_Neo_Core_Auth $auth;
	private Agent_Neo_Core_JSON_Patch $json_patch;
	private Agent_Neo_Core_Idempotency_Store $idempotency_store;
	private Agent_Neo_Core_Rollback_Store $rollback_store;
	private Agent_Neo_Core_Audit_Log $audit_log;

	/**
	 * @param Agent_Neo_Core_Auth              $auth Auth helper。
	 * @param Agent_Neo_Core_JSON_Patch        $json_patch JSON diff helper。
	 * @param Agent_Neo_Core_Idempotency_Store $idempotency_store Idempotency store。
	 * @param Agent_Neo_Core_Rollback_Store    $rollback_store Rollback store。
	 * @param Agent_Neo_Core_Audit_Log         $audit_log Audit log。
	 */
	public function __construct(
		Agent_Neo_Core_Auth $auth,
		Agent_Neo_Core_JSON_Patch $json_patch,
		Agent_Neo_Core_Idempotency_Store $idempotency_store,
		Agent_Neo_Core_Rollback_Store $rollback_store,
		Agent_Neo_Core_Audit_Log $audit_log
	) {
		$this->auth              = $auth;
		$this->json_patch        = $json_patch;
		$this->idempotency_store = $idempotency_store;
		$this->rollback_store    = $rollback_store;
		$this->audit_log         = $audit_log;
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
	 * Routes を登録する。
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$this->register_agent_route(
			'/elements/swap',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'swap_element' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
			)
		);
	}

	/**
	 * Write 権限を確認する。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return true|WP_Error
	 */
	public function check_write_permission( WP_REST_Request $request ) {
		return $this->auth->check_write_permission( $request, 'edit_posts' );
	}

	/**
	 * POST /elements/swap。
	 *
	 * 渡された element_type + post_id に対して、
	 * 確定済み element 値を静的に差し替える。
	 * diff_hash と idempotency_key が必須。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function swap_element( WP_REST_Request $request ) {
		// request_id を決定する。
		$request_id = $this->resolve_request_id( $request );

		// JSON body を取得する。
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'JSON body is required.', 'agent-neo-core' ) );
		}

		// 必須フィールドを検証する。
		$validation = $this->validate_swap_params( $params );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$element_type = sanitize_key( (string) $params['element_type'] );
		$post_id      = (int) $params['post_id'];

		// element_type が既知か確認する。
		if ( ! array_key_exists( $element_type, self::ELEMENT_TYPES ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'element_type is not supported.', 'agent-neo-core' ),
				array(
					'field'     => 'element_type',
					'supported' => array_keys( self::ELEMENT_TYPES ),
				)
			);
		}

		// 対象 post を取得する。
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return Agent_Neo_Core_Auth::error(
				'NOT_FOUND',
				__( 'Post was not found.', 'agent-neo-core' ),
				array( 'post_id' => $post_id )
			);
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return Agent_Neo_Core_Auth::error(
				'FORBIDDEN',
				__( 'Current user cannot edit this post.', 'agent-neo-core' ),
				array( 'post_id' => $post_id )
			);
		}

		$meta_key     = self::ELEMENT_TYPES[ $element_type ];
		$incoming_id  = sanitize_text_field( (string) $params[ $element_type ] );

		// 現在値を取得する。
		$before_raw = get_post_meta( $post_id, $meta_key, true );
		$before_id  = is_string( $before_raw ) ? $before_raw : '';

		// before/after の状態で diff を計算する。
		$before_state = array(
			'element_type' => $element_type,
			'element_id'   => $before_id,
			'post_id'      => $post_id,
		);
		$after_state  = array(
			'element_type' => $element_type,
			'element_id'   => $incoming_id,
			'post_id'      => $post_id,
		);

		$diff      = $this->json_patch->diff( $before_state, $after_state );
		$diff_hash = $this->json_patch->diff_hash( $diff );

		// diff_hash 検証（リクエストに含まれる場合のみ）。
		if (
			isset( $params['diff_hash'] ) &&
			is_string( $params['diff_hash'] ) &&
			'' !== $params['diff_hash'] &&
			! hash_equals( $params['diff_hash'], $diff_hash )
		) {
			return Agent_Neo_Core_Auth::error(
				'PRECONDITION_FAILED',
				__( 'Element diff_hash does not match current state.', 'agent-neo-core' ),
				array( 'expected' => $diff_hash )
			);
		}

		// idempotency_key を決定する。
		$idempotency_key = '';
		if ( isset( $params['idempotency_key'] ) && is_string( $params['idempotency_key'] ) && '' !== $params['idempotency_key'] ) {
			$idempotency_key = sanitize_text_field( $params['idempotency_key'] );
		} else {
			$idempotency_key = 'elements_swap_' . $request_id;
		}

		// payload hash を計算する（idempotency store 検索用）。
		$payload_hash = $this->idempotency_store->payload_hash(
			array(
				'post_id'      => $post_id,
				'element_type' => $element_type,
				'element_id'   => $incoming_id,
				'diff_hash'    => $diff_hash,
			)
		);

		// 既存の idempotent 結果があれば返す。
		$stored_result = $this->idempotency_store->get( $idempotency_key, $payload_hash );
		if ( is_wp_error( $stored_result ) ) {
			return $stored_result;
		}

		if ( is_array( $stored_result ) ) {
			$stored_result['applied'] = false;
			$stored_result['idempotent_replay'] = true;
			return rest_ensure_response( Agent_Neo_Core_Auth::success_response( $stored_result, $request_id ) );
		}

		// rollback point を保存する。
		$rollback_point = $this->snapshot_element_meta( $post_id, $element_type, $before_id, $meta_key, $request_id );

		// element_id を post_meta に書き込む。
		$saved = update_post_meta( $post_id, $meta_key, $incoming_id );

		// update_post_meta は既存値と同じ場合に false を返すため、実際の値で確認する。
		$current_value = get_post_meta( $post_id, $meta_key, true );
		if ( false === $saved && $current_value !== $incoming_id ) {
			return Agent_Neo_Core_Auth::error(
				'CONFLICT',
				__( 'Element could not be persisted.', 'agent-neo-core' ),
				array( 'post_id' => $post_id )
			);
		}

		// audit ログを記録する。
		$audit_id = $this->audit_log->record(
			'elements_swap',
			$request_id,
			$diff_hash,
			$idempotency_key,
			array(
				'post_id'           => $post_id,
				'element_type'      => $element_type,
				'before_id'         => $before_id,
				'after_id'          => $incoming_id,
				'meta_key'          => $meta_key,
				'rollback_point_id' => $rollback_point['rollback_point_id'],
			)
		);

		$result = array(
			'post_id'           => $post_id,
			'element_type'      => $element_type,
			'before_id'         => $before_id,
			'after_id'          => $incoming_id,
			'applied'           => true,
			'diff_hash'         => $diff_hash,
			'diff'              => $diff,
			'rollback_point'    => $rollback_point,
			'rollback_point_id' => $rollback_point['rollback_point_id'],
			'request_id'        => $request_id,
			'audit_id'          => $audit_id,
		);

		// idempotency store に保存する。
		$this->idempotency_store->save( $idempotency_key, $payload_hash, $result );

		return rest_ensure_response( Agent_Neo_Core_Auth::success_response( $result, $request_id ) );
	}

	/**
	 * swap リクエストの必須フィールドを検証する。
	 *
	 * @param array<string, mixed> $params Params。
	 * @return true|WP_Error
	 */
	private function validate_swap_params( array $params ) {
		// post_id 必須。
		if ( ! isset( $params['post_id'] ) || ! is_numeric( $params['post_id'] ) || (int) $params['post_id'] < 1 ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'post_id must be a positive integer.', 'agent-neo-core' ),
				array( 'field' => 'post_id' )
			);
		}

		// element_type 必須。
		if ( empty( $params['element_type'] ) || ! is_string( $params['element_type'] ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'element_type is required.', 'agent-neo-core' ),
				array( 'field' => 'element_type' )
			);
		}

		$element_type = sanitize_key( (string) $params['element_type'] );

		// 未知の element_type はここで早期拒否する。
		// validate_swap_params の時点で弾かないと、ハンドラ側での既知チェックまで
		// 不正な element_id フィールドへのアクセスが素通りしてしまうリスクがある。
		if ( ! array_key_exists( $element_type, self::ELEMENT_TYPES ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'element_type is not supported.', 'agent-neo-core' ),
				array(
					'field'     => 'element_type',
					'supported' => array_keys( self::ELEMENT_TYPES ),
				)
			);
		}

		// 既知の element_type に対応する element_id フィールドを検証する。
		// element_type が既知であることを上で確認済みのため、常に検証する。
		if ( ! isset( $params[ $element_type ] ) || ! is_string( $params[ $element_type ] ) || '' === trim( $params[ $element_type ] ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				sprintf(
					/* translators: %s: フィールド名 */
					__( '%s (element id) is required when element_type is %s.', 'agent-neo-core' ),
					$element_type,
					$element_type
				),
				array( 'field' => $element_type )
			);
		}

		// idempotency_key（任意）が指定された場合は文字列であること。
		if ( isset( $params['idempotency_key'] ) && ( ! is_string( $params['idempotency_key'] ) || '' === trim( $params['idempotency_key'] ) ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'idempotency_key must be a non-empty string.', 'agent-neo-core' ),
				array( 'field' => 'idempotency_key' )
			);
		}

		return true;
	}

	/**
	 * element swap の rollback point を post_meta に保存する。
	 *
	 * @param int    $post_id Post id。
	 * @param string $element_type Element type。
	 * @param string $before_id 現在の element id。
	 * @param string $meta_key post_meta キー。
	 * @param string $request_id Request id。
	 * @return array<string, mixed>
	 */
	private function snapshot_element_meta( int $post_id, string $element_type, string $before_id, string $meta_key, string $request_id ): array {
		$points = get_post_meta( $post_id, self::ROLLBACK_META_KEY, true );
		$points = is_array( $points ) ? $points : array();

		$point = array(
			'rollback_point_id' => 'elem_rb_' . wp_generate_uuid4(),
			'post_id'           => $post_id,
			'element_type'      => $element_type,
			'meta_key'          => $meta_key,
			'before_id'         => $before_id,
			'request_id'        => $request_id,
			'created_at'        => gmdate( 'c' ),
		);

		$points[] = $point;
		if ( count( $points ) > self::MAX_ROLLBACKS ) {
			$points = array_slice( $points, -1 * self::MAX_ROLLBACKS );
		}

		update_post_meta( $post_id, self::ROLLBACK_META_KEY, $points );

		return $point;
	}

	/**
	 * X-Request-Id ヘッダから request_id を取得する。
	 * 未指定・不正な場合は UUID v4 を生成する。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return string
	 */
	private function resolve_request_id( WP_REST_Request $request ): string {
		$request_id = $request->get_header( 'X-Request-Id' );
		if ( is_string( $request_id ) && '' !== $request_id && $this->is_uuid_v4( $request_id ) ) {
			return $request_id;
		}

		return wp_generate_uuid4();
	}

	/**
	 * UUIDv4 形式か判定する。
	 *
	 * @param string $value Value。
	 * @return bool
	 */
	private function is_uuid_v4( string $value ): bool {
		return 1 === preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{12}$/i', $value );
	}
}

add_action(
	'agent_neo_core_register_rest',
	static function ( Agent_Neo_Core_Container $container ): void {
		$controller = new Agent_Neo_Core_Elements_Controller(
			$container->auth(),
			$container->json_patch(),
			$container->idempotency_store(),
			$container->rollback_store(),
			$container->audit_log()
		);
		$controller->register();
		$container->register_module( 'rest-elements' );
	}
);
