<?php
/**
 * POST /actions/dry-run and /actions/apply controller.
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 汎用 JSON 操作 dry-run/apply endpoint。
 */
final class Agent_Neo_Core_Actions_Controller extends Agent_Neo_Core_REST_Controller_Base {
	/**
	 * @var Agent_Neo_Core_Auth
	 */
	private Agent_Neo_Core_Auth $auth;

	/**
	 * @var Agent_Neo_Core_Schema_Loader
	 */
	private Agent_Neo_Core_Schema_Loader $schema_loader;

	/**
	 * @var Agent_Neo_Core_JSON_Patch
	 */
	private Agent_Neo_Core_JSON_Patch $json_patch;

	/**
	 * @var Agent_Neo_Core_Dry_Run_Store
	 */
	private Agent_Neo_Core_Dry_Run_Store $dry_run_store;

	/**
	 * @var Agent_Neo_Core_Idempotency_Store
	 */
	private Agent_Neo_Core_Idempotency_Store $idempotency_store;

	/**
	 * @var Agent_Neo_Core_Rollback_Store
	 */
	private Agent_Neo_Core_Rollback_Store $rollback_store;

	/**
	 * @var Agent_Neo_Core_Audit_Log
	 */
	private Agent_Neo_Core_Audit_Log $audit_log;

	/**
	 * @param Agent_Neo_Core_Auth              $auth Auth helper。
	 * @param Agent_Neo_Core_Schema_Loader     $schema_loader Schema loader。
	 * @param Agent_Neo_Core_JSON_Patch        $json_patch JSON Patch helper。
	 * @param Agent_Neo_Core_Dry_Run_Store     $dry_run_store Dry-run store。
	 * @param Agent_Neo_Core_Idempotency_Store $idempotency_store Idempotency store。
	 * @param Agent_Neo_Core_Rollback_Store    $rollback_store Rollback store。
	 * @param Agent_Neo_Core_Audit_Log         $audit_log Audit log。
	 */
	public function __construct(
		Agent_Neo_Core_Auth $auth,
		Agent_Neo_Core_Schema_Loader $schema_loader,
		Agent_Neo_Core_JSON_Patch $json_patch,
		Agent_Neo_Core_Dry_Run_Store $dry_run_store,
		Agent_Neo_Core_Idempotency_Store $idempotency_store,
		Agent_Neo_Core_Rollback_Store $rollback_store,
		Agent_Neo_Core_Audit_Log $audit_log
	) {
		$this->auth              = $auth;
		$this->schema_loader     = $schema_loader;
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
			'/actions/dry-run',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'dry_run' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
			)
		);

		$this->register_agent_route(
			'/actions/apply',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'apply_action' ),
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
	 * POST /actions/dry-run。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function dry_run( WP_REST_Request $request ) {
		$params = $this->json_params( $request );
		if ( is_wp_error( $params ) ) {
			return $params;
		}

		$validation = $this->validate_dry_run_request( $params );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$post_id = (int) $params['resource_id'];
		$post    = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return Agent_Neo_Core_Auth::error( 'NOT_FOUND', __( 'Post was not found.', 'agent-neo-core' ), array( 'post_id' => $post_id ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return Agent_Neo_Core_Auth::error( 'FORBIDDEN', __( 'Current user cannot edit this post.', 'agent-neo-core' ), array( 'post_id' => $post_id ) );
		}

		$prepared = $this->simulate_change( $post, $params );
		if ( is_wp_error( $prepared ) ) {
			return $prepared;
		}

		$diff           = $this->json_patch->diff( $prepared['before'], $prepared['after'] );
		$diff_hash      = $this->json_patch->diff_hash( $diff );
		$request_id     = (string) $params['request_id'];
		$rollback_token = 'rbp_' . wp_generate_uuid4();
		$dry_run_token  = $this->dry_run_store->save(
			$request_id,
			$diff_hash,
			array(
				'action'                 => (string) $params['action'],
				'resource_id'            => $post_id,
				'resource_sub_id'        => isset( $params['resource_sub_id'] ) ? (string) $params['resource_sub_id'] : '',
				'before_content_hash'    => hash( 'sha256', $post->post_content ),
				'patched_post_content'   => $prepared['patched_post_content'],
				'diff'                   => $diff,
				'rollback_preview_token' => $rollback_token,
			)
		);

		$response = array(
			'request_id'             => $request_id,
			'diff_hash'              => $diff_hash,
			'diff'                   => $diff,
			'risk'                   => isset( $params['risk'] ) && is_array( $params['risk'] ) ? $params['risk'] : array(),
			'validation'             => array(
				'valid'    => true,
				'warnings' => array(),
			),
			'dry_run_token'          => $dry_run_token,
			'rollback_preview_token' => $rollback_token,
		);

		return rest_ensure_response( Agent_Neo_Core_Auth::success_response( $response, $request_id ) );
	}

	/**
	 * POST /actions/apply。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function apply_action( WP_REST_Request $request ) {
		$params = $this->json_params( $request );
		if ( is_wp_error( $params ) ) {
			return $params;
		}

		$validation = $this->validate_apply_request( $params );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$request_id      = (string) $params['request_id'];
		$diff_hash       = (string) $params['diff_hash'];
		$idempotency_key = (string) $params['idempotency_key'];
		$payload_hash    = $this->idempotency_store->payload_hash( $params );

		$dry_run = $this->dry_run_store->get( $request_id, $diff_hash );
		if ( is_wp_error( $dry_run ) ) {
			return $dry_run;
		}

		$post_id = (int) $params['resource_id'];
		$metadata_validation = $this->validate_dry_run_metadata( $dry_run, $params, $post_id );
		if ( is_wp_error( $metadata_validation ) ) {
			return $metadata_validation;
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return Agent_Neo_Core_Auth::error( 'NOT_FOUND', __( 'Post was not found.', 'agent-neo-core' ), array( 'post_id' => $post_id ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return Agent_Neo_Core_Auth::error( 'FORBIDDEN', __( 'Current user cannot edit this post.', 'agent-neo-core' ), array( 'post_id' => $post_id ) );
		}

		$stored_result = $this->idempotency_store->get( $idempotency_key, $payload_hash );
		if ( is_wp_error( $stored_result ) ) {
			return $stored_result;
		}

		if ( is_array( $stored_result ) ) {
			$stored_result['applied'] = false;
			$stored_result['warnings'][] = array(
				'code'    => 'IDEMPOTENT_REPLAY',
				'message' => 'Stored result returned without reapplying.',
			);
			return rest_ensure_response( Agent_Neo_Core_Auth::success_response( $stored_result, $request_id ) );
		}

		if ( ! hash_equals( (string) $dry_run['before_content_hash'], hash( 'sha256', $post->post_content ) ) ) {
			return Agent_Neo_Core_Auth::error( 'PRECONDITION_FAILED', __( 'Post content changed after dry-run.', 'agent-neo-core' ), array( 'post_id' => $post_id ) );
		}

		$rollback_point_id = $this->rollback_store->snapshot( $post_id, $post->post_content, isset( $params['rollback_reason'] ) ? (string) $params['rollback_reason'] : '' );
		$updated          = wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => (string) $dry_run['patched_post_content'],
			),
			true
		);

		if ( is_wp_error( $updated ) ) {
			return Agent_Neo_Core_Auth::error( 'CONFLICT', __( 'Post update failed.', 'agent-neo-core' ), array( 'reason' => $updated->get_error_message() ) );
		}

		$resource_version = $this->rollback_store->increment_resource_version( $post_id );
		$audit_id         = $this->audit_log->record(
			(string) $params['action'],
			$request_id,
			$diff_hash,
			$idempotency_key,
			array(
				'post_id'           => $post_id,
				'rollback_point_id' => $rollback_point_id,
			)
		);

		$result = array(
			'applied'           => true,
			'diff_hash'         => $diff_hash,
			'rollback_point_id' => $rollback_point_id,
			'resource_version'  => (string) $resource_version,
			'request_id'        => $request_id,
			'audit_id'          => $audit_id,
			'warnings'          => array(),
		);

		$this->idempotency_store->save( $idempotency_key, $payload_hash, $result );
		return rest_ensure_response( Agent_Neo_Core_Auth::success_response( $result, $request_id ) );
	}

	/**
	 * 変更を試算する。
	 *
	 * @param WP_Post              $post Post。
	 * @param array<string, mixed> $params Params。
	 * @return array<string, mixed>|WP_Error
	 */
	private function simulate_change( WP_Post $post, array $params ) {
		$action     = (string) $params['action'];
		$operations = $params['changes'];
		$document   = $this->json_patch->document_from_post_content( $post->post_content );

		if ( 'patch_block' === $action ) {
			$block_id = isset( $params['resource_sub_id'] ) ? (string) $params['resource_sub_id'] : '';
			$block    = $this->json_patch->find_block_by_id( $document['blocks'], $block_id );
			if ( null === $block ) {
				return Agent_Neo_Core_Auth::error( 'CONFLICT', __( 'Block was not found for dry-run.', 'agent-neo-core' ), array( 'block_id' => $block_id ) );
			}
			$patched_block = $this->json_patch->apply( $block, $operations );
			if ( is_wp_error( $patched_block ) ) {
				return $patched_block;
			}
			$blocks = $this->json_patch->replace_block_by_id( $document['blocks'], $block_id, $patched_block );
			if ( is_wp_error( $blocks ) ) {
				return $blocks;
			}
			$document['blocks'] = $blocks;
		} elseif ( in_array( $action, array( 'edit_section', 'swap_section' ), true ) ) {
			$section_id = isset( $params['resource_sub_id'] ) ? (string) $params['resource_sub_id'] : '';
			$range      = $this->json_patch->find_section_range( $document['blocks'], $section_id );
			if ( is_wp_error( $range ) ) {
				return Agent_Neo_Core_Auth::error( 'CONFLICT', __( 'Section was not found for dry-run.', 'agent-neo-core' ), array( 'section_id' => $section_id ) );
			}

			$section_document = array(
				'blocks' => array_slice( $document['blocks'], $range['start'], $range['length'] ),
			);
			$section_document = $this->json_patch->apply( $section_document, $operations );
			if ( is_wp_error( $section_document ) ) {
				return $section_document;
			}

			$section_blocks = isset( $section_document['blocks'] ) && is_array( $section_document['blocks'] ) ? $section_document['blocks'] : array();
			$blocks         = $this->json_patch->replace_section_by_id( $document['blocks'], $section_id, $section_blocks );
			if ( is_wp_error( $blocks ) ) {
				return $blocks;
			}

			$document['blocks'] = $blocks;
		} else {
			$before_document = $document;
			$document = $this->json_patch->apply( $document, $operations );
			if ( is_wp_error( $document ) ) {
				return $document;
			}

			if (
				isset( $document['post_content'], $before_document['post_content'], $document['blocks'], $before_document['blocks'] )
				&& $document['post_content'] !== $before_document['post_content']
				&& $document['blocks'] === $before_document['blocks']
			) {
				unset( $document['blocks'] );
			}
		}

		return array(
			'before'               => $this->json_patch->document_from_post_content( $post->post_content ),
			'after'                => $document,
			'patched_post_content' => $this->json_patch->post_content_from_document( $document ),
		);
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
	 * dry-run request を検証する。
	 *
	 * @param array<string, mixed> $params Params。
	 * @return true|WP_Error
	 */
	private function validate_dry_run_request( array $params ) {
		$schema_validation = $this->schema_loader->validate_schema( 'action-dry-run-request', $params );
		if ( is_wp_error( $schema_validation ) ) {
			return $schema_validation;
		}

		$required = array( 'action', 'resource_id', 'changes', 'request_id' );
		foreach ( $required as $key ) {
			if ( ! array_key_exists( $key, $params ) ) {
				return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'Required field is missing.', 'agent-neo-core' ), array( 'field' => $key ) );
			}
		}

		return $this->validate_common_fields( $params );
	}

	/**
	 * apply request を検証する。
	 *
	 * @param array<string, mixed> $params Params。
	 * @return true|WP_Error
	 */
	private function validate_apply_request( array $params ) {
		$schema_validation = $this->schema_loader->validate_schema( 'action-apply-request', $params );
		if ( is_wp_error( $schema_validation ) ) {
			return $schema_validation;
		}

		$required = array( 'action', 'resource_id', 'diff_hash', 'idempotency_key', 'request_id' );
		foreach ( $required as $key ) {
			if ( ! array_key_exists( $key, $params ) ) {
				return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'Required field is missing.', 'agent-neo-core' ), array( 'field' => $key ) );
			}
		}

		if ( 'apply_page' === $params['action'] && empty( $params['from_preview_token'] ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'from_preview_token is required for apply_page.', 'agent-neo-core' ) );
		}

		if ( ! isset( $params['diff_hash'] ) || ! is_string( $params['diff_hash'] ) || '' === $params['diff_hash'] ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'diff_hash must be a non-empty string.', 'agent-neo-core' ) );
		}

		if ( ! isset( $params['idempotency_key'] ) || ! is_string( $params['idempotency_key'] ) || '' === $params['idempotency_key'] ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'idempotency_key must be a non-empty string.', 'agent-neo-core' ) );
		}

		return $this->validate_common_fields( $params, false );
	}

	/**
	 * apply payload と保存済み dry-run metadata の同一性を検証する。
	 *
	 * @param array<string, mixed> $dry_run Dry-run metadata。
	 * @param array<string, mixed> $params Apply params。
	 * @param int                  $post_id Post id。
	 * @return true|WP_Error
	 */
	private function validate_dry_run_metadata( array $dry_run, array $params, int $post_id ) {
		$expected_resource_sub_id = isset( $dry_run['resource_sub_id'] ) ? (string) $dry_run['resource_sub_id'] : '';
		$actual_resource_sub_id   = isset( $params['resource_sub_id'] ) ? (string) $params['resource_sub_id'] : '';

		if ( ! isset( $dry_run['resource_id'], $dry_run['action'] ) ) {
			return Agent_Neo_Core_Auth::error( 'PRECONDITION_FAILED', __( 'Dry-run metadata is incomplete.', 'agent-neo-core' ) );
		}

		if ( (int) $dry_run['resource_id'] !== $post_id ) {
			return Agent_Neo_Core_Auth::error( 'PRECONDITION_FAILED', __( 'Dry-run resource does not match apply request.', 'agent-neo-core' ) );
		}

		if ( (string) $dry_run['action'] !== (string) $params['action'] ) {
			return Agent_Neo_Core_Auth::error( 'PRECONDITION_FAILED', __( 'Dry-run action does not match apply request.', 'agent-neo-core' ) );
		}

		if ( ! hash_equals( $expected_resource_sub_id, $actual_resource_sub_id ) ) {
			return Agent_Neo_Core_Auth::error( 'PRECONDITION_FAILED', __( 'Dry-run sub resource does not match apply request.', 'agent-neo-core' ) );
		}

		return true;
	}

	/**
	 * 共通 field を検証する。
	 *
	 * @param array<string, mixed> $params Params。
	 * @param bool                $require_changes changes required。
	 * @return true|WP_Error
	 */
	private function validate_common_fields( array $params, bool $require_changes = true ) {
		$allowed_actions = array( 'patch_post', 'patch_block', 'edit_section', 'apply_page', 'swap_section' );
		if ( ! isset( $params['action'] ) || ! is_string( $params['action'] ) || ! in_array( $params['action'], $allowed_actions, true ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'Action is invalid.', 'agent-neo-core' ) );
		}

		if ( ! isset( $params['resource_id'] ) || (int) $params['resource_id'] < 1 ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'resource_id must be a positive integer.', 'agent-neo-core' ) );
		}

		if ( $require_changes && ( ! isset( $params['changes'] ) || ! is_array( $params['changes'] ) ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'changes must be a JSON Patch array.', 'agent-neo-core' ) );
		}

		// 連想配列（{"title": ...} 等）は is_array を通過して json_patch 層で 500 になるため、
		// ここで「op / path を持つ操作オブジェクトのリスト」であることまで検証して 400 で落とす。
		if ( $require_changes && isset( $params['changes'] ) ) {
			if ( ! wp_is_numeric_array( $params['changes'] ) ) {
				return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'changes must be a list of JSON Patch operations.', 'agent-neo-core' ) );
			}
			foreach ( $params['changes'] as $index => $operation ) {
				if ( ! is_array( $operation ) || ! isset( $operation['op'], $operation['path'] ) ) {
					return Agent_Neo_Core_Auth::error(
						'VALIDATION_ERROR',
						__( 'Each change must be an object with op and path.', 'agent-neo-core' ),
						array( 'index' => $index )
					);
				}
			}
		}

		if ( in_array( $params['action'], array( 'patch_block', 'edit_section', 'swap_section' ), true ) && ( empty( $params['resource_sub_id'] ) || ! is_string( $params['resource_sub_id'] ) ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'resource_sub_id is required for block or section actions.', 'agent-neo-core' ) );
		}

		if ( isset( $params['request_id'] ) && ( ! is_string( $params['request_id'] ) || ! $this->is_uuid_v4( $params['request_id'] ) ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'request_id must be UUIDv4.', 'agent-neo-core' ) );
		}

		return true;
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
		$controller = new Agent_Neo_Core_Actions_Controller(
			$container->auth(),
			$container->schema_loader(),
			$container->json_patch(),
			$container->dry_run_store(),
			$container->idempotency_store(),
			$container->rollback_store(),
			$container->audit_log()
		);
		$controller->register();
		$container->register_module( 'rest-actions' );
	}
);
