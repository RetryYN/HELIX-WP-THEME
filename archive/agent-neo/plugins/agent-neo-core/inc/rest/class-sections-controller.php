<?php
/**
 * POST /posts/{id}/sections/{section_id}/edit controller.
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * section 単位編集 endpoint。
 */
final class Agent_Neo_Core_Sections_Controller extends Agent_Neo_Core_REST_Controller_Base {
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
			'/posts/(?P<id>\d+)/sections/(?P<section_id>[a-z0-9-]+)/edit',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'edit_section' ),
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
	 * POST /posts/{id}/sections/{section_id}/edit。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function edit_section( WP_REST_Request $request ) {
		$post_id    = (int) $request['id'];
		$section_id = (string) $request['section_id'];
		$params     = $request->get_json_params();

		if ( ! preg_match( '/^[a-z0-9-]+$/', $section_id ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'section_id format is invalid.', 'agent-neo-core' ) );
		}

		if ( ! is_array( $params ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'JSON body is required.', 'agent-neo-core' ) );
		}

		$schema_validation = $this->schema_loader->validate_schema( 'section-edit-request', $params );
		if ( is_wp_error( $schema_validation ) ) {
			return $schema_validation;
		}

		$validation = $this->validate_request( $params, $section_id );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return Agent_Neo_Core_Auth::error( 'NOT_FOUND', __( 'Post was not found.', 'agent-neo-core' ), array( 'post_id' => $post_id ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return Agent_Neo_Core_Auth::error( 'FORBIDDEN', __( 'Current user cannot edit this post.', 'agent-neo-core' ), array( 'post_id' => $post_id ) );
		}

		$section_blocks = $this->section_blocks_from_payload( $params['section_payload'], $section_id );
		$document       = $this->json_patch->document_from_post_content( $post->post_content );
		$before_range   = $this->json_patch->find_section_range( $document['blocks'], $section_id );
		if ( is_wp_error( $before_range ) ) {
			return $before_range;
		}

		$before_blocks = array_slice( $document['blocks'], $before_range['start'], $before_range['length'] );
		$patched      = $this->json_patch->replace_section_by_id( $document['blocks'], $section_id, $section_blocks );
		if ( is_wp_error( $patched ) ) {
			return $patched;
		}

		$diff          = $this->json_patch->diff( $before_blocks, $section_blocks );
		$diff_hash     = $this->json_patch->diff_hash( $diff );
		$preview_only  = ! empty( $params['preview_only'] );
		$request_id    = wp_generate_uuid4();
		$response_data = array(
			'post_id'           => $post_id,
			'section_id'        => $section_id,
			'diff_hash'         => $diff_hash,
			'applied'           => false,
			'rollback_point_id' => '',
			'risk'              => isset( $params['risk'] ) && is_array( $params['risk'] ) ? $params['risk'] : array(),
		);

		if ( $preview_only ) {
			return rest_ensure_response( Agent_Neo_Core_Auth::success_response( $response_data, $request_id ) );
		}

		$idempotency_key = (string) $params['idempotency_key'];
		$payload_hash    = $this->idempotency_store->payload_hash( array( 'post_id' => $post_id, 'section_id' => $section_id, 'payload' => $params['section_payload'], 'changes' => $params['changes'] ) );
		$stored_result   = $this->idempotency_store->get( $idempotency_key, $payload_hash );
		if ( is_wp_error( $stored_result ) ) {
			return $stored_result;
		}
		if ( is_array( $stored_result ) ) {
			return rest_ensure_response( Agent_Neo_Core_Auth::success_response( $stored_result, $request_id ) );
		}

		$document['blocks']  = $patched;
		$new_content         = $this->json_patch->post_content_from_document( $document );
		$rollback_point_id   = $this->rollback_store->snapshot( $post_id, $post->post_content, 'edit_section' );
		$response_data['rollback_point_id'] = $rollback_point_id;

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

		$this->rollback_store->increment_resource_version( $post_id );
		$this->audit_log->record(
			'edit_section',
			$request_id,
			$diff_hash,
			$idempotency_key,
			array(
				'post_id'    => $post_id,
				'section_id' => $section_id,
			)
		);

		$response_data['applied'] = true;
		$this->idempotency_store->save( $idempotency_key, $payload_hash, $response_data );

		return rest_ensure_response( Agent_Neo_Core_Auth::success_response( $response_data, $request_id ) );
	}

	/**
	 * request を検証する。
	 *
	 * @param array<string, mixed> $params Params。
	 * @param string              $section_id Section id。
	 * @return true|WP_Error
	 */
	private function validate_request( array $params, string $section_id ) {
		foreach ( array( 'section_payload', 'changes', 'idempotency_key' ) as $key ) {
			if ( ! array_key_exists( $key, $params ) ) {
				return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'Required field is missing.', 'agent-neo-core' ), array( 'field' => $key ) );
			}
		}

		if ( isset( $params['section_id'] ) && $params['section_id'] !== $section_id ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'section_id body/path mismatch.', 'agent-neo-core' ) );
		}

		if ( ! is_array( $params['section_payload'] ) || ! is_array( $params['changes'] ) || ! is_string( $params['idempotency_key'] ) || '' === $params['idempotency_key'] ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'section payload, changes, or idempotency_key is invalid.', 'agent-neo-core' ) );
		}

		return true;
	}

	/**
	 * section_payload を block 配列に変換する。
	 *
	 * @param array<string, mixed> $payload Payload。
	 * @param string              $section_id Section id。
	 * @return array<int, array<string, mixed>>
	 */
	private function section_blocks_from_payload( array $payload, string $section_id ): array {
		$content = isset( $payload['content'] ) && is_string( $payload['content'] ) ? $payload['content'] : '';
		if ( function_exists( 'parse_blocks' ) ) {
			$blocks = parse_blocks( $content );
			return is_array( $blocks ) ? $this->ensure_section_marker( $blocks, $section_id ) : array();
		}

		return $this->ensure_section_marker(
			array(
				array(
					'blockName'    => null,
					'attrs'        => array(),
					'innerBlocks'  => array(),
					'innerHTML'    => $content,
					'innerContent' => array( $content ),
				),
			),
			$section_id
		);
	}

	/**
	 * 置換後 section の先頭に section_id marker を保持する。
	 *
	 * @param array<int, array<string, mixed>> $blocks Blocks。
	 * @param string                          $section_id Section id。
	 * @return array<int, array<string, mixed>>
	 */
	private function ensure_section_marker( array $blocks, string $section_id ): array {
		foreach ( $blocks as $block ) {
			if ( is_array( $block ) && $section_id === $this->json_patch->section_id( $block ) ) {
				return $blocks;
			}
		}

		if ( empty( $blocks ) || ! isset( $blocks[0] ) || ! is_array( $blocks[0] ) ) {
			$blocks = array(
				array(
					'blockName'    => null,
					'attrs'        => array(),
					'innerBlocks'  => array(),
					'innerHTML'    => '',
					'innerContent' => array( '' ),
				),
			);
		}

		$attrs = isset( $blocks[0]['attrs'] ) && is_array( $blocks[0]['attrs'] ) ? $blocks[0]['attrs'] : array();
		$attrs['section_id'] = $section_id;

		$agent_neo = isset( $attrs['agentNeo'] ) && is_array( $attrs['agentNeo'] ) ? $attrs['agentNeo'] : array();
		$agent_neo['section_id'] = $section_id;

		$attrs['agentNeo'] = $agent_neo;
		$blocks[0]['attrs'] = $attrs;

		return $blocks;
	}
}

add_action(
	'agent_neo_core_register_rest',
	static function ( Agent_Neo_Core_Container $container ): void {
		$controller = new Agent_Neo_Core_Sections_Controller(
			$container->auth(),
			$container->schema_loader(),
			$container->json_patch(),
			$container->idempotency_store(),
			$container->rollback_store(),
			$container->audit_log()
		);
		$controller->register();
		$container->register_module( 'rest-sections' );
	}
);
