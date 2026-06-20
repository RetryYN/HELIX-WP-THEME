<?php
/**
 * POST /pages/{id}/apply and rollback controller.
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * page apply / rollback endpoint。
 */
final class Agent_Neo_Core_Pages_Controller extends Agent_Neo_Core_REST_Controller_Base {
	private Agent_Neo_Core_Auth $auth;
	private Agent_Neo_Core_License_State $license_state;
	private Agent_Neo_Core_JSON_Patch $json_patch;
	private Agent_Neo_Core_Dry_Run_Store $dry_run_store;
	private Agent_Neo_Core_Idempotency_Store $idempotency_store;
	private Agent_Neo_Core_Rollback_Store $rollback_store;
	private Agent_Neo_Core_Audit_Log $audit_log;

	/**
	 * @param Agent_Neo_Core_Auth              $auth Auth helper。
	 * @param Agent_Neo_Core_License_State     $license_state License state。
	 * @param Agent_Neo_Core_JSON_Patch        $json_patch JSON Patch helper。
	 * @param Agent_Neo_Core_Dry_Run_Store     $dry_run_store Dry-run store。
	 * @param Agent_Neo_Core_Idempotency_Store $idempotency_store Idempotency store。
	 * @param Agent_Neo_Core_Rollback_Store    $rollback_store Rollback store。
	 * @param Agent_Neo_Core_Audit_Log         $audit_log Audit log。
	 */
	public function __construct(
		Agent_Neo_Core_Auth $auth,
		Agent_Neo_Core_License_State $license_state,
		Agent_Neo_Core_JSON_Patch $json_patch,
		Agent_Neo_Core_Dry_Run_Store $dry_run_store,
		Agent_Neo_Core_Idempotency_Store $idempotency_store,
		Agent_Neo_Core_Rollback_Store $rollback_store,
		Agent_Neo_Core_Audit_Log $audit_log
	) {
		$this->auth              = $auth;
		$this->license_state     = $license_state;
		$this->json_patch        = $json_patch;
		$this->dry_run_store     = $dry_run_store;
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
			'/pages/(?P<id>\d+)/apply',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'apply_page' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
			)
		);

		$this->register_agent_route(
			'/pages/(?P<id>\d+)/rollback',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rollback_page' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
			)
		);

		$this->register_agent_route(
			'/rollback/(?P<rollback_id>[A-Za-z0-9_-]+)',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'rollback_generic' ),
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
	 * POST /pages/{id}/apply。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function apply_page( WP_REST_Request $request ) {
		$page_id = (int) $request['id'];
		$params  = $this->json_params( $request );
		if ( is_wp_error( $params ) ) {
			return $params;
		}

		$validation = $this->validate_apply_request( $params );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$request_id      = isset( $params['request_id'] ) ? (string) $params['request_id'] : '';
		$diff_hash       = (string) $params['diff_hash'];
		$idempotency_key = (string) $params['idempotency_key'];
		$payload_hash    = $this->idempotency_store->payload_hash(
			array(
				'page_id'            => $page_id,
				'diff_hash'          => $diff_hash,
				'from_preview_token' => isset( $params['from_preview_token'] ) ? (string) $params['from_preview_token'] : '',
				'template_id'        => isset( $params['template_id'] ) ? (string) $params['template_id'] : '',
				'rollback_note'      => isset( $params['rollback_note'] ) ? (string) $params['rollback_note'] : '',
			)
		);

		$page = $this->page_post( $page_id );
		if ( is_wp_error( $page ) ) {
			return $page;
		}

		$access = $this->check_page_access( $page_id );
		if ( is_wp_error( $access ) ) {
			return $access;
		}

		$package = $this->check_apply_package_scope( $params );
		if ( is_wp_error( $package ) ) {
			return $package;
		}

		$dry_run = $this->find_page_dry_run( $diff_hash, $page_id, $params );
		if ( is_wp_error( $dry_run ) ) {
			return $dry_run;
		}
		$request_id = isset( $dry_run['request_id'] ) ? (string) $dry_run['request_id'] : $request_id;

		$metadata_validation = $this->validate_apply_dry_run_metadata( $dry_run, $page_id, $params );
		if ( is_wp_error( $metadata_validation ) ) {
			return $metadata_validation;
		}

		$preview_state = $this->preview_state( $dry_run, $params );
		if ( is_wp_error( $preview_state ) ) {
			return $preview_state;
		}

		$stored_result = $this->idempotency_store->get( $idempotency_key, $payload_hash );
		if ( is_wp_error( $stored_result ) ) {
			return $stored_result;
		}

		if ( is_array( $stored_result ) ) {
			$stored_result['applied'] = false;
			return rest_ensure_response( Agent_Neo_Core_Auth::success_response( $stored_result, $request_id ) );
		}

		if ( ! isset( $dry_run['before_content_hash'], $dry_run['patched_post_content'] ) || ! is_string( $dry_run['patched_post_content'] ) ) {
			return Agent_Neo_Core_Auth::error( 'PRECONDITION_FAILED', __( 'Dry-run payload is incomplete.', 'agent-neo-core' ) );
		}

		if ( ! hash_equals( (string) $dry_run['before_content_hash'], hash( 'sha256', $page->post_content ) ) ) {
			return Agent_Neo_Core_Auth::error( 'PRECONDITION_FAILED', __( 'Page content changed after dry-run.', 'agent-neo-core' ), array( 'page_id' => $page_id ) );
		}

		$new_content        = (string) $dry_run['patched_post_content'];
		$rollback_point_id  = $this->rollback_store->snapshot( $page_id, $page->post_content, isset( $params['rollback_note'] ) ? (string) $params['rollback_note'] : '' );
		$updated            = wp_update_post(
			array(
				'ID'           => $page_id,
				'post_content' => $new_content,
			),
			true
		);

		if ( is_wp_error( $updated ) ) {
			return Agent_Neo_Core_Auth::error( 'CONFLICT', __( 'Page update failed.', 'agent-neo-core' ), array( 'reason' => $updated->get_error_message() ) );
		}

		$this->rollback_store->increment_resource_version( $page_id );
		$audit_id = $this->audit_log->record(
			'apply_page',
			$request_id,
			$diff_hash,
			$idempotency_key,
			array(
				'page_id'           => $page_id,
				'rollback_point_id' => $rollback_point_id,
				'template_id'       => isset( $params['template_id'] ) ? (string) $params['template_id'] : '',
			)
		);

		$result = array(
			'page_id'           => $page_id,
			'rollback_point_id' => $rollback_point_id,
			'diff_hash'         => $diff_hash,
			'preview_state'     => $preview_state,
			'request_id'        => $request_id,
			'applied_blocks'    => $this->count_blocks( $new_content ),
			'audit_id'          => $audit_id,
			'applied'           => true,
		);

		$this->idempotency_store->save( $idempotency_key, $payload_hash, $result );
		return rest_ensure_response( Agent_Neo_Core_Auth::success_response( $result, $request_id ) );
	}

	/**
	 * POST /pages/{id}/rollback。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function rollback_page( WP_REST_Request $request ) {
		$page_id = (int) $request['id'];
		$params  = $this->json_params( $request );
		if ( is_wp_error( $params ) ) {
			return $params;
		}

		if ( empty( $params['rollback_point_id'] ) || ! is_string( $params['rollback_point_id'] ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'rollback_point_id is required.', 'agent-neo-core' ) );
		}

		$validation = $this->validate_rollback_request( $params );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$snapshot = $this->rollback_store->get_snapshot( $page_id, (string) $params['rollback_point_id'] );
		return $this->restore_snapshot( $snapshot, $params, $page_id );
	}

	/**
	 * POST /rollback/{rollback_id}。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function rollback_generic( WP_REST_Request $request ) {
		$params = $this->json_params( $request );
		if ( is_wp_error( $params ) ) {
			return $params;
		}

		$validation = $this->validate_rollback_request( $params );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$rollback_id = (string) $request['rollback_id'];
		$snapshot    = $this->rollback_store->find_snapshot( $rollback_id );
		return $this->restore_snapshot( $snapshot, $params, null );
	}

	/**
	 * Snapshot をページへ復元する。
	 *
	 * @param array<string, mixed>|WP_Error $snapshot Snapshot。
	 * @param array<string, mixed>          $params Params。
	 * @param int|null                      $expected_page_id Expected page id。
	 * @return WP_REST_Response|WP_Error
	 */
	private function restore_snapshot( $snapshot, array $params, ?int $expected_page_id ) {
		if ( is_wp_error( $snapshot ) ) {
			return $snapshot;
		}

		$post_id = isset( $snapshot['post_id'] ) ? (int) $snapshot['post_id'] : 0;
		if ( null !== $expected_page_id && $post_id !== $expected_page_id ) {
			return Agent_Neo_Core_Auth::error( 'NOT_FOUND', __( 'Rollback point does not belong to this page.', 'agent-neo-core' ), array( 'page_id' => $expected_page_id ) );
		}

		$post = null === $expected_page_id ? $this->snapshot_post( $post_id ) : $this->page_post( $post_id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$access = $this->check_post_access( $post_id );
		if ( is_wp_error( $access ) ) {
			return $access;
		}

		$request_id        = wp_generate_uuid4();
		$idempotency_key   = (string) $params['idempotency_key'];
		$rollback_point_id = (string) $snapshot['rollback_point_id'];
		$payload_hash      = $this->idempotency_store->payload_hash(
			array(
				'post_id'           => $post_id,
				'rollback_point_id' => $rollback_point_id,
				'reason'            => isset( $params['reason'] ) ? (string) $params['reason'] : '',
				'force'             => ! empty( $params['force'] ),
			)
		);

		$stored_result = $this->idempotency_store->get( $idempotency_key, $payload_hash );
		if ( is_wp_error( $stored_result ) ) {
			return $stored_result;
		}

		if ( is_array( $stored_result ) ) {
			$stored_result['restored'] = false;
			return rest_ensure_response( Agent_Neo_Core_Auth::success_response( $stored_result, $request_id ) );
		}

		$restored_content = isset( $snapshot['content'] ) && is_string( $snapshot['content'] ) ? $snapshot['content'] : '';
		$diff            = $this->json_patch->diff(
			$this->json_patch->document_from_post_content( $post->post_content ),
			$this->json_patch->document_from_post_content( $restored_content )
		);
		$diff_hash       = $this->json_patch->diff_hash( $diff );

		$updated = wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $restored_content,
			),
			true
		);

		if ( is_wp_error( $updated ) ) {
			return Agent_Neo_Core_Auth::error( 'CONFLICT', __( 'Rollback failed.', 'agent-neo-core' ), array( 'reason' => $updated->get_error_message() ) );
		}

		$restored_version = (string) $this->rollback_store->increment_resource_version( $post_id );
		$response_page_id = null === $expected_page_id ? $post_id : $expected_page_id;
		$audit_id         = $this->audit_log->record(
			'rollback_page',
			$request_id,
			$diff_hash,
			$idempotency_key,
			array(
				'page_id'           => $response_page_id,
				'post_id'           => $post_id,
				'post_type'         => $this->snapshot_post_type( $snapshot, $post ),
				'rollback_point_id' => $rollback_point_id,
				'reason'            => isset( $params['reason'] ) ? (string) $params['reason'] : '',
			)
		);

		$result = array(
			'restored_version'  => $restored_version,
			'page_id'           => $response_page_id,
			'post_id'           => $post_id,
			'post_type'         => $this->snapshot_post_type( $snapshot, $post ),
			'rollback_point_id' => $rollback_point_id,
			'request_id'        => $request_id,
			'audit_id'          => $audit_id,
			'restored'          => true,
		);

		$this->idempotency_store->save( $idempotency_key, $payload_hash, $result );
		return rest_ensure_response( Agent_Neo_Core_Auth::success_response( $result, $request_id ) );
	}

	/**
	 * JSON body を返す。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return array<string, mixed>|WP_Error
	 */
	private function json_params( WP_REST_Request $request ) {
		$params = $request->get_json_params();
		if ( ! is_array( $params ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'JSON body is required.', 'agent-neo-core' ) );
		}

		return $params;
	}

	/**
	 * apply request を検証する。
	 *
	 * @param array<string, mixed> $params Params。
	 * @return true|WP_Error
	 */
	private function validate_apply_request( array $params ) {
		foreach ( array( 'diff_hash', 'idempotency_key' ) as $key ) {
			if ( empty( $params[ $key ] ) || ! is_string( $params[ $key ] ) ) {
				return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'Required field is missing or invalid.', 'agent-neo-core' ), array( 'field' => $key ) );
			}
		}

		if ( isset( $params['request_id'] ) && ( ! is_string( $params['request_id'] ) || ! $this->is_uuid_v4( (string) $params['request_id'] ) ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'request_id must be UUIDv4.', 'agent-neo-core' ) );
		}

		foreach ( array( 'from_preview_token', 'template_id', 'rollback_note' ) as $key ) {
			if ( isset( $params[ $key ] ) && ! is_string( $params[ $key ] ) ) {
				return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'Optional field must be a string.', 'agent-neo-core' ), array( 'field' => $key ) );
			}
		}

		return true;
	}

	/**
	 * A-005 は request_id を必須にしないため、diff_hash で dry-run を解決する。
	 *
	 * @param string               $diff_hash Diff hash。
	 * @param int                  $page_id Page id。
	 * @param array<string, mixed> $params Apply params。
	 * @return array<string, mixed>|WP_Error
	 */
	private function find_page_dry_run( string $diff_hash, int $page_id, array $params ) {
		if ( isset( $params['request_id'] ) && is_string( $params['request_id'] ) && '' !== $params['request_id'] ) {
			$stored = $this->dry_run_store->get( (string) $params['request_id'], $diff_hash );
			if ( is_wp_error( $stored ) ) {
				return $stored;
			}
			$stored['request_id'] = (string) $params['request_id'];
			return $stored;
		}

		global $wpdb;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s ORDER BY option_id DESC LIMIT 100",
				$wpdb->esc_like( '_transient_agent_neo_dry_' ) . '%'
			)
		);

		foreach ( $rows as $row ) {
			$option_name = isset( $row->option_name ) ? (string) $row->option_name : '';
			$payload     = isset( $row->option_value ) ? maybe_unserialize( $row->option_value ) : null;
			if ( ! is_array( $payload ) || $this->is_transient_option_expired( $option_name ) ) {
				continue;
			}

			if ( (int) ( $payload['resource_id'] ?? 0 ) !== $page_id || ! isset( $payload['diff'] ) || ! is_array( $payload['diff'] ) ) {
				continue;
			}

			if ( hash_equals( $diff_hash, $this->json_patch->diff_hash( $payload['diff'] ) ) ) {
				$payload['request_id'] = wp_generate_uuid4();
				return $payload;
			}
		}

		return Agent_Neo_Core_Auth::error(
			'PRECONDITION_FAILED',
			__( 'Dry-run result was not found or has expired.', 'agent-neo-core' ),
			array(
				'page_id'   => $page_id,
				'diff_hash' => $diff_hash,
			)
		);
	}

	/**
	 * transient option が timeout 済みかを確認する。
	 *
	 * @param string $option_name Option name。
	 * @return bool
	 */
	private function is_transient_option_expired( string $option_name ): bool {
		$key     = str_replace( '_transient_', '_transient_timeout_', $option_name );
		$timeout = get_option( $key );
		return false !== $timeout && (int) $timeout < time();
	}

	/**
	 * rollback request を検証する。
	 *
	 * @param array<string, mixed> $params Params。
	 * @return true|WP_Error
	 */
	private function validate_rollback_request( array $params ) {
		if ( empty( $params['idempotency_key'] ) || ! is_string( $params['idempotency_key'] ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'idempotency_key is required.', 'agent-neo-core' ) );
		}

		if ( isset( $params['reason'] ) && ! is_string( $params['reason'] ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'reason must be a string.', 'agent-neo-core' ) );
		}

		if ( isset( $params['force'] ) && ! is_bool( $params['force'] ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'force must be boolean.', 'agent-neo-core' ) );
		}

		return true;
	}

	/**
	 * 保存済み dry-run metadata と apply request の同一性を確認する。
	 *
	 * @param array<string, mixed> $dry_run Dry-run metadata。
	 * @param int                  $page_id Page id。
	 * @param array<string, mixed> $params Params。
	 * @return true|WP_Error
	 */
	private function validate_apply_dry_run_metadata( array $dry_run, int $page_id, array $params ) {
		if ( ! isset( $dry_run['resource_id'], $dry_run['action'] ) ) {
			return Agent_Neo_Core_Auth::error( 'PRECONDITION_FAILED', __( 'Dry-run metadata is incomplete.', 'agent-neo-core' ) );
		}

		if ( (int) $dry_run['resource_id'] !== $page_id ) {
			return Agent_Neo_Core_Auth::error( 'PRECONDITION_FAILED', __( 'Dry-run resource does not match page apply request.', 'agent-neo-core' ) );
		}

		if ( 'apply_page' !== (string) $dry_run['action'] ) {
			return Agent_Neo_Core_Auth::error( 'PRECONDITION_FAILED', __( 'Dry-run action does not match page apply.', 'agent-neo-core' ) );
		}

		$expected_resource_sub_id = isset( $dry_run['resource_sub_id'] ) ? (string) $dry_run['resource_sub_id'] : '';
		$actual_resource_sub_id   = isset( $params['template_id'] ) ? (string) $params['template_id'] : '';
		if ( ! hash_equals( $expected_resource_sub_id, $actual_resource_sub_id ) ) {
			return Agent_Neo_Core_Auth::error( 'PRECONDITION_FAILED', __( 'Dry-run sub resource does not match page apply request.', 'agent-neo-core' ) );
		}

		return true;
	}

	/**
	 * preview token の消費状態を返す。
	 *
	 * @param array<string, mixed> $dry_run Dry-run metadata。
	 * @param array<string, mixed> $params Params。
	 * @return string|WP_Error
	 */
	private function preview_state( array $dry_run, array $params ) {
		if ( empty( $params['from_preview_token'] ) ) {
			return 'ignored';
		}

		$expected = isset( $dry_run['rollback_preview_token'] ) ? (string) $dry_run['rollback_preview_token'] : '';
		if ( '' !== $expected && ! hash_equals( $expected, (string) $params['from_preview_token'] ) ) {
			return Agent_Neo_Core_Auth::error( 'CONFLICT', __( 'Preview token does not match dry-run.', 'agent-neo-core' ) );
		}

		return 'consumed';
	}

	/**
	 * ページ取得と post_type を確認する。
	 *
	 * @param int $page_id Page id。
	 * @return WP_Post|WP_Error
	 */
	private function page_post( int $page_id ) {
		$page = get_post( $page_id );
		if ( ! $page instanceof WP_Post || 'page' !== $page->post_type ) {
			return Agent_Neo_Core_Auth::error( 'NOT_FOUND', __( 'Page was not found.', 'agent-neo-core' ), array( 'page_id' => $page_id ) );
		}

		return $page;
	}

	/**
	 * Snapshot 対象 post を取得する。
	 *
	 * @param int $post_id Post id。
	 * @return WP_Post|WP_Error
	 */
	private function snapshot_post( int $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return Agent_Neo_Core_Auth::error( 'NOT_FOUND', __( 'Post was not found.', 'agent-neo-core' ), array( 'post_id' => $post_id ) );
		}

		return $post;
	}

	/**
	 * Snapshot に保存された post_type を優先して返す。
	 *
	 * @param array<string, mixed> $snapshot Snapshot。
	 * @param WP_Post             $post Current post。
	 * @return string
	 */
	private function snapshot_post_type( array $snapshot, WP_Post $post ): string {
		return isset( $snapshot['post_type'] ) && is_string( $snapshot['post_type'] ) && '' !== $snapshot['post_type'] ? $snapshot['post_type'] : $post->post_type;
	}

	/**
	 * object-level edit_post を確認する。
	 *
	 * @param int $page_id Page id。
	 * @return true|WP_Error
	 */
	private function check_page_access( int $page_id ) {
		$access = $this->check_post_access( $page_id );
		if ( is_wp_error( $access ) ) {
			return Agent_Neo_Core_Auth::error( 'FORBIDDEN', __( 'Current user cannot edit this page.', 'agent-neo-core' ), array( 'page_id' => $page_id ) );
		}

		return true;
	}

	/**
	 * object-level edit_post を確認する。
	 *
	 * @param int $post_id Post id。
	 * @return true|WP_Error
	 */
	private function check_post_access( int $post_id ) {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return Agent_Neo_Core_Auth::error( 'FORBIDDEN', __( 'Current user cannot edit this post.', 'agent-neo-core' ), array( 'post_id' => $post_id ) );
		}

		return true;
	}

	/**
	 * LP/HP/template 適用時のみ corporate package を要求する。
	 *
	 * @param array<string, mixed> $params Params。
	 * @return true|WP_Error
	 */
	private function check_apply_package_scope( array $params ) {
		$is_corporate_apply = ! empty( $params['template_id'] ) || ! empty( $params['from_preview_token'] );
		if ( ! $is_corporate_apply ) {
			return true;
		}

		return $this->auth->check_package_scope( 'corporate', $this->license_state->package() );
	}

	/**
	 * 適用後 block 数を数える。
	 *
	 * @param string $post_content Post content。
	 * @return int
	 */
	private function count_blocks( string $post_content ): int {
		if ( ! function_exists( 'parse_blocks' ) ) {
			return 0;
		}

		$blocks = parse_blocks( $post_content );
		return is_array( $blocks ) ? count( $blocks ) : 0;
	}

	/**
	 * UUIDv4 形式か判定する。
	 *
	 * @param string $value Value。
	 * @return bool
	 */
	private function is_uuid_v4( string $value ): bool {
		return 1 === preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $value );
	}
}

add_action(
	'agent_neo_core_register_rest',
	static function ( Agent_Neo_Core_Container $container ): void {
		$controller = new Agent_Neo_Core_Pages_Controller(
			$container->auth(),
			$container->license_state(),
			$container->json_patch(),
			$container->dry_run_store(),
			$container->idempotency_store(),
			$container->rollback_store(),
			$container->audit_log()
		);
		$controller->register();
		$container->register_module( 'rest-pages' );
	}
);
