<?php
/**
 * PATCH /posts/{id}/blocks/{block_id} controller.
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * block 単位部分更新 endpoint。
 */
final class Agent_Neo_Core_Blocks_Controller extends Agent_Neo_Core_REST_Controller_Base {
	private Agent_Neo_Core_Auth $auth;
	private Agent_Neo_Core_Schema_Loader $schema_loader;
	private Agent_Neo_Core_JSON_Patch $json_patch;
	private Agent_Neo_Core_Idempotency_Store $idempotency_store;
	private Agent_Neo_Core_Rollback_Store $rollback_store;
	private Agent_Neo_Core_Audit_Log $audit_log;

	/**
	 * @param Agent_Neo_Core_Auth              $auth Auth helper。
	 * @param Agent_Neo_Core_Schema_Loader     $schema_loader Schema loader。
	 * @param Agent_Neo_Core_JSON_Patch        $json_patch JSON Patch helper。
	 * @param Agent_Neo_Core_Idempotency_Store $idempotency_store Idempotency store。
	 * @param Agent_Neo_Core_Rollback_Store    $rollback_store Rollback store。
	 * @param Agent_Neo_Core_Audit_Log         $audit_log Audit log。
	 */
	public function __construct(
		Agent_Neo_Core_Auth $auth,
		Agent_Neo_Core_Schema_Loader $schema_loader,
		Agent_Neo_Core_JSON_Patch $json_patch,
		Agent_Neo_Core_Idempotency_Store $idempotency_store,
		Agent_Neo_Core_Rollback_Store $rollback_store,
		Agent_Neo_Core_Audit_Log $audit_log
	) {
		$this->auth              = $auth;
		$this->schema_loader     = $schema_loader;
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
			'/posts/(?P<id>\d+)/blocks/(?P<block_id>[A-Za-z0-9_-]+)',
			array(
				'methods'             => 'PATCH',
				'callback'            => array( $this, 'patch_block' ),
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
	 * PATCH /posts/{id}/blocks/{block_id}。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function patch_block( WP_REST_Request $request ) {
		$post_id  = (int) $request['id'];
		$block_id = (string) $request['block_id'];
		$params   = $request->get_json_params();

		if ( ! is_array( $params ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'JSON body is required.', 'agent-neo-core' ) );
		}

		$schema_validation = $this->schema_loader->validate_schema( 'block-patch-request', $params );
		if ( is_wp_error( $schema_validation ) ) {
			return $schema_validation;
		}

		$idempotency_key = isset( $params['idempotency_key'] ) && is_string( $params['idempotency_key'] ) ? $params['idempotency_key'] : $request->get_header( 'Idempotency-Key' );
		if ( ! is_string( $idempotency_key ) || '' === $idempotency_key ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'idempotency_key is required.', 'agent-neo-core' ) );
		}

		if ( ! isset( $params['operations'] ) || ! is_array( $params['operations'] ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'operations must be a JSON Patch array.', 'agent-neo-core' ) );
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return Agent_Neo_Core_Auth::error( 'NOT_FOUND', __( 'Post was not found.', 'agent-neo-core' ), array( 'post_id' => $post_id ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return Agent_Neo_Core_Auth::error( 'FORBIDDEN', __( 'Current user cannot edit this post.', 'agent-neo-core' ), array( 'post_id' => $post_id ) );
		}

		$payload_hash  = $this->idempotency_store->payload_hash( array( 'post_id' => $post_id, 'block_id' => $block_id, 'operations' => $params['operations'] ) );
		$stored_result = $this->idempotency_store->get( $idempotency_key, $payload_hash );
		if ( is_wp_error( $stored_result ) ) {
			return $stored_result;
		}
		if ( is_array( $stored_result ) ) {
			return rest_ensure_response( Agent_Neo_Core_Auth::success_response( $stored_result, wp_generate_uuid4() ) );
		}

		$document = $this->json_patch->document_from_post_content( $post->post_content );
		$block    = $this->json_patch->find_block_by_id( $document['blocks'], $block_id );
		if ( null === $block ) {
			return Agent_Neo_Core_Auth::error( 'NOT_FOUND', __( 'Block was not found.', 'agent-neo-core' ), array( 'block_id' => $block_id ) );
		}

		$patched_block = $this->json_patch->apply( $block, $params['operations'] );
		if ( is_wp_error( $patched_block ) ) {
			return $patched_block;
		}

		$diff      = $this->json_patch->diff( $block, $patched_block );
		$diff_hash = $this->json_patch->diff_hash( $diff );
		$blocks    = $this->json_patch->replace_block_by_id( $document['blocks'], $block_id, $patched_block );
		if ( is_wp_error( $blocks ) ) {
			return $blocks;
		}

		$document['blocks'] = $blocks;
		$new_content        = $this->json_patch->post_content_from_document( $document );
		$rollback_point_id  = $this->rollback_store->snapshot( $post_id, $post->post_content, 'patch_block' );
		$previous_version   = $this->rollback_store->resource_version( $post_id );

		$updated = wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $new_content,
			),
			true
		);

		if ( is_wp_error( $updated ) ) {
			return Agent_Neo_Core_Auth::error( 'CONFLICT', __( 'Post update failed.', 'agent-neo-core' ), array( 'reason' => $updated->get_error_message() ) );
		}

		$resource_version = $this->rollback_store->increment_resource_version( $post_id );
		$history          = $this->rollback_store->append_block_history( $post_id, $block_id, $block, $previous_version );
		$request_id       = wp_generate_uuid4();
		$this->audit_log->record(
			'patch_block',
			$request_id,
			$diff_hash,
			$idempotency_key,
			array(
				'post_id'  => $post_id,
				'block_id' => $block_id,
			)
		);

		$result = array(
			'post_id'           => $post_id,
			'block_id'          => $block_id,
			'diff_hash'         => $diff_hash,
			'resource_version'  => $resource_version,
			'rollback_point_id' => $rollback_point_id,
			'history'           => $history,
		);

		$this->idempotency_store->save( $idempotency_key, $payload_hash, $result );
		return rest_ensure_response( Agent_Neo_Core_Auth::success_response( $result, $request_id ) );
	}
}
