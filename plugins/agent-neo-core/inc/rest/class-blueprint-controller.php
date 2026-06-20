<?php
/**
 * POST /pages/blueprint and /lp/sections controller.
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LP/HP blueprint endpoint.
 */
final class Agent_Neo_Core_Blueprint_Controller extends Agent_Neo_Core_REST_Controller_Base {
	private const BLUEPRINTS_OPTION = 'agent_neo_blueprints_json';
	private const SECTIONS_OPTION   = 'agent_neo_lp_sections_json';

	// L2-design.md lines 438/441: frozen lp-blueprint 12-section contract (INT-001 / CARRY-G2-012 / TC-029).
	private const SECTION_KINDS = array(
		'hero',
		'problem',
		'agitation',
		'solution',
		'feature',
		'benefit',
		'use-case',
		'proof',
		'comparison',
		'pricing',
		'faq',
		'final-cta',
	);

	private Agent_Neo_Core_Auth $auth;
	private Agent_Neo_Core_License_State $license_state;
	private Agent_Neo_Core_JSON_Patch $json_patch;
	private Agent_Neo_Core_Dry_Run_Store $dry_run_store;

	/**
	 * @param Agent_Neo_Core_Auth          $auth Auth helper.
	 * @param Agent_Neo_Core_License_State $license_state License state.
	 * @param Agent_Neo_Core_JSON_Patch    $json_patch JSON Patch helper.
	 * @param Agent_Neo_Core_Dry_Run_Store $dry_run_store Dry-run store.
	 */
	public function __construct(
		Agent_Neo_Core_Auth $auth,
		Agent_Neo_Core_License_State $license_state,
		Agent_Neo_Core_JSON_Patch $json_patch,
		Agent_Neo_Core_Dry_Run_Store $dry_run_store
	) {
		$this->auth          = $auth;
		$this->license_state = $license_state;
		$this->json_patch    = $json_patch;
		$this->dry_run_store = $dry_run_store;
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
			'/pages/blueprint',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'upsert_blueprint' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
			)
		);

		$this->register_agent_route(
			'/lp/sections',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'upsert_section' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
			)
		);
	}

	/**
	 * Write permission と corporate package 境界を確認する。
	 *
	 * @param WP_REST_Request $request Request.
	 * @return true|WP_Error
	 */
	public function check_write_permission( WP_REST_Request $request ) {
		$permission = $this->auth->check_write_permission( $request, 'edit_posts' );
		if ( is_wp_error( $permission ) ) {
			return $permission;
		}

		return $this->auth->check_package_scope( 'corporate', $this->license_state->package() );
	}

	/**
	 * POST /pages/blueprint.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function upsert_blueprint( WP_REST_Request $request ) {
		$params = $this->json_params( $request );
		if ( is_wp_error( $params ) ) {
			return $params;
		}

		$normalized = $this->normalize_blueprint_request( $params );
		if ( is_wp_error( $normalized ) ) {
			return $normalized;
		}

		$page_apply_target = $this->validate_page_apply_target( $params );
		if ( is_wp_error( $page_apply_target ) ) {
			return $page_apply_target;
		}

		$sections    = $this->blueprint_sections( $normalized );
		$blueprint   = $this->blueprint_payload( $normalized, $sections );
		$request_id  = $this->request_id( $params );
		$stored      = $this->store_record( self::BLUEPRINTS_OPTION, $normalized['blueprint_id'], $blueprint );
		$consistency = $this->consistency_result( $normalized['blueprint_id'], $sections );

		if ( is_wp_error( $stored ) ) {
			return $stored;
		}

		$page_apply = $this->prepare_page_apply( $page_apply_target, $blueprint, $request_id );
		if ( is_wp_error( $page_apply ) ) {
			return $page_apply;
		}

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response(
				array(
					'blueprint_id' => $normalized['blueprint_id'],
					'sections'     => $sections,
					'consistency'  => $consistency,
					'page_apply'   => $page_apply,
					'blueprint'    => $blueprint,
				),
				$request_id
			)
		);
	}

	/**
	 * POST /lp/sections.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function upsert_section( WP_REST_Request $request ) {
		$params = $this->json_params( $request );
		if ( is_wp_error( $params ) ) {
			return $params;
		}

		$normalized = $this->normalize_section_request( $params );
		if ( is_wp_error( $normalized ) ) {
			return $normalized;
		}

		$section = $this->section_payload(
			$normalized['section_id'],
			$normalized['section_kind'],
			$normalized['blueprint_id'],
			$normalized['cta_id'],
			$normalized['offer_id'],
			$normalized['service_id']
		);
		$stored  = $this->store_record( self::SECTIONS_OPTION, $normalized['section_id'], $section );
		if ( is_wp_error( $stored ) ) {
			return $stored;
		}

		$request_id = $this->request_id( $params );
		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response(
				array(
					'blueprint_id' => $normalized['blueprint_id'],
					'sections'     => array( $section ),
					'consistency'  => array(
						'valid'                       => true,
						'blueprint_id'                => $normalized['blueprint_id'],
						'all_section_ids_consistent'  => true,
						'standard_section_kind_count' => count( self::SECTION_KINDS ),
					),
					'page_apply'   => array(
						'available' => false,
						'reason'    => 'section_only',
					),
				),
				$request_id
			)
		);
	}

	/**
	 * JSON body を返す。
	 *
	 * @param WP_REST_Request $request Request.
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
	 * Blueprint request を正規化する。
	 *
	 * @param array<string, mixed> $params Params.
	 * @return array<string, string>|WP_Error
	 */
	private function normalize_blueprint_request( array $params ) {
		foreach ( array( 'section_id', 'cta_id', 'offer_id', 'service_id' ) as $field ) {
			if ( ! array_key_exists( $field, $params ) ) {
				return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'Required field is missing.', 'agent-neo-core' ), array( 'field' => $field ) );
			}
		}

		$section_id = $this->slug_field( $params, 'section_id' );
		$cta_id     = $this->slug_field( $params, 'cta_id' );
		$offer_id   = $this->slug_field( $params, 'offer_id' );
		$service_id = $this->slug_field( $params, 'service_id' );
		if ( is_wp_error( $section_id ) || is_wp_error( $cta_id ) || is_wp_error( $offer_id ) || is_wp_error( $service_id ) ) {
			return is_wp_error( $section_id ) ? $section_id : ( is_wp_error( $cta_id ) ? $cta_id : ( is_wp_error( $offer_id ) ? $offer_id : $service_id ) );
		}

		$blueprint_id = $this->blueprint_id_from_params( $params, $section_id );
		if ( is_wp_error( $blueprint_id ) ) {
			return $blueprint_id;
		}

		$consistency = $this->validate_section_belongs_to_blueprint( $blueprint_id, $section_id );
		if ( is_wp_error( $consistency ) ) {
			return $consistency;
		}

		return array(
			'blueprint_id' => $blueprint_id,
			'section_id'   => $section_id,
			'cta_id'       => $cta_id,
			'offer_id'     => $offer_id,
			'service_id'   => $service_id,
		);
	}

	/**
	 * Section request を正規化する。
	 *
	 * @param array<string, mixed> $params Params.
	 * @return array<string, string>|WP_Error
	 */
	private function normalize_section_request( array $params ) {
		$section_kind = $this->section_kind_from_params( $params );
		if ( is_wp_error( $section_kind ) ) {
			return $section_kind;
		}

		$blueprint_id = $this->blueprint_id_from_params( $params, '' );
		if ( is_wp_error( $blueprint_id ) ) {
			return $blueprint_id;
		}

		$section_id = isset( $params['section_id'] ) ? $this->slug_field( $params, 'section_id' ) : $blueprint_id . '-' . $section_kind;
		if ( is_wp_error( $section_id ) ) {
			return $section_id;
		}

		$consistency = $this->validate_section_belongs_to_blueprint( $blueprint_id, $section_id );
		if ( is_wp_error( $consistency ) ) {
			return $consistency;
		}

		$cta_id     = isset( $params['cta_id'] ) ? $this->slug_field( $params, 'cta_id' ) : '';
		$offer_id   = isset( $params['offer_id'] ) ? $this->slug_field( $params, 'offer_id' ) : '';
		$service_id = isset( $params['service_id'] ) ? $this->slug_field( $params, 'service_id' ) : '';
		if ( is_wp_error( $cta_id ) || is_wp_error( $offer_id ) || is_wp_error( $service_id ) ) {
			return is_wp_error( $cta_id ) ? $cta_id : ( is_wp_error( $offer_id ) ? $offer_id : $service_id );
		}

		return array(
			'blueprint_id' => $blueprint_id,
			'section_id'   => $section_id,
			'section_kind' => $section_kind,
			'cta_id'       => $cta_id,
			'offer_id'     => $offer_id,
			'service_id'   => $service_id,
		);
	}

	/**
	 * Slug field を検証して返す。
	 *
	 * @param array<string, mixed> $params Params.
	 * @param string               $field Field name.
	 * @return string|WP_Error
	 */
	private function slug_field( array $params, string $field ) {
		if ( ! isset( $params[ $field ] ) || ! is_string( $params[ $field ] ) || '' === $params[ $field ] ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'Slug field is required.', 'agent-neo-core' ), array( 'field' => $field ) );
		}

		$value = (string) $params[ $field ];
		if ( strlen( $value ) > 128 || ! preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'Slug field format is invalid.', 'agent-neo-core' ), array( 'field' => $field ) );
		}

		return $value;
	}

	/**
	 * blueprint_id を request から取得または section_id から導出する。
	 *
	 * @param array<string, mixed> $params Params.
	 * @param string               $section_id Section id.
	 * @return string|WP_Error
	 */
	private function blueprint_id_from_params( array $params, string $section_id ) {
		if ( isset( $params['blueprint_id'] ) ) {
			return $this->slug_field( $params, 'blueprint_id' );
		}

		if ( isset( $params['template_id'] ) ) {
			return $this->slug_field( $params, 'template_id' );
		}

		if ( isset( $params['blueprint'] ) && is_array( $params['blueprint'] ) && isset( $params['blueprint']['blueprint_id'] ) ) {
			return $this->slug_field( $params['blueprint'], 'blueprint_id' );
		}

		if ( '' === $section_id ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'blueprint_id is required.', 'agent-neo-core' ), array( 'field' => 'blueprint_id' ) );
		}

		foreach ( self::SECTION_KINDS as $kind ) {
			$suffix = '-' . $kind;
			if ( str_ends_with( $section_id, $suffix ) ) {
				return substr( $section_id, 0, -strlen( $suffix ) );
			}
		}

		return $section_id;
	}

	/**
	 * section_kind を request から取得する。
	 *
	 * @param array<string, mixed> $params Params.
	 * @return string|WP_Error
	 */
	private function section_kind_from_params( array $params ) {
		$value = '';
		if ( isset( $params['section_kind'] ) && is_string( $params['section_kind'] ) ) {
			$value = $params['section_kind'];
		} elseif ( isset( $params['section_type'] ) && is_string( $params['section_type'] ) ) {
			$value = $params['section_type'];
		}

		if ( '' === $value || ! in_array( $value, self::SECTION_KINDS, true ) ) {
			return Agent_Neo_Core_Auth::error(
				'VALIDATION_ERROR',
				__( 'section_kind is invalid.', 'agent-neo-core' ),
				array(
					'field'   => 'section_kind',
					'allowed' => self::SECTION_KINDS,
				)
			);
		}

		return $value;
	}

	/**
	 * section_id が blueprint_id 配下の標準 section であることを確認する。
	 *
	 * @param string $blueprint_id Blueprint id.
	 * @param string $section_id Section id.
	 * @return true|WP_Error
	 */
	private function validate_section_belongs_to_blueprint( string $blueprint_id, string $section_id ) {
		if ( $section_id === $blueprint_id ) {
			return true;
		}

		foreach ( self::SECTION_KINDS as $kind ) {
			if ( $section_id === $blueprint_id . '-' . $kind ) {
				return true;
			}
		}

		return Agent_Neo_Core_Auth::error(
			'VALIDATION_ERROR',
			__( 'blueprint_id and section_id are inconsistent.', 'agent-neo-core' ),
			array(
				'blueprint_id' => $blueprint_id,
				'section_id'   => $section_id,
			)
		);
	}

	/**
	 * Blueprint section list を作る。
	 *
	 * @param array<string, string> $normalized Normalized params.
	 * @return array<int, array<string, mixed>>
	 */
	private function blueprint_sections( array $normalized ): array {
		$sections = array();
		foreach ( self::SECTION_KINDS as $index => $kind ) {
			$sections[] = $this->section_payload(
				$normalized['blueprint_id'] . '-' . $kind,
				$kind,
				$normalized['blueprint_id'],
				$normalized['cta_id'],
				$normalized['offer_id'],
				$normalized['service_id'],
				$index + 1
			);
		}

		return $sections;
	}

	/**
	 * Section payload を作る。
	 *
	 * @param string $section_id Section id.
	 * @param string $section_kind Section kind.
	 * @param string $blueprint_id Blueprint id.
	 * @param string $cta_id CTA id.
	 * @param string $offer_id Offer id.
	 * @param string $service_id Service id.
	 * @param int    $order Order.
	 * @return array<string, mixed>
	 */
	private function section_payload( string $section_id, string $section_kind, string $blueprint_id, string $cta_id, string $offer_id, string $service_id, int $order = 1 ): array {
		return array(
			'section_id'   => $section_id,
			'section_kind' => $section_kind,
			'order'        => $order,
			'blueprint_id' => $blueprint_id,
			'refs'         => array(
				'cta_id'     => $cta_id,
				'offer_id'   => $offer_id,
				'service_id' => $service_id,
			),
			'static'       => true,
		);
	}

	/**
	 * Blueprint payload を作る。
	 *
	 * @param array<string, string>             $normalized Normalized params.
	 * @param array<int, array<string, mixed>> $sections Sections.
	 * @return array<string, mixed>
	 */
	private function blueprint_payload( array $normalized, array $sections ): array {
		return array(
			'blueprint_id' => $normalized['blueprint_id'],
			'blueprint_type' => 'lp-blueprint',
			'section_id'   => $normalized['section_id'],
			'cta_id'       => $normalized['cta_id'],
			'offer_id'     => $normalized['offer_id'],
			'service_id'   => $normalized['service_id'],
			'sections'     => $sections,
			'generated_by'  => 'agent-neo-core-static-blueprint',
		);
	}

	/**
	 * Page apply 対象を検証する。ここでは永続化しない。
	 *
	 * @param array<string, mixed> $params Params.
	 * @return array<string, mixed>|WP_Error
	 */
	private function validate_page_apply_target( array $params ) {
		if ( empty( $params['page_id'] ) ) {
			return array(
				'available' => false,
				'reason'    => 'page_id_not_provided',
			);
		}

		$page_id = (int) $params['page_id'];
		$page    = get_post( $page_id );
		if ( ! $page instanceof WP_Post || 'page' !== $page->post_type ) {
			return Agent_Neo_Core_Auth::error( 'NOT_FOUND', __( 'Page was not found.', 'agent-neo-core' ), array( 'page_id' => $page_id ) );
		}

		if ( ! current_user_can( 'edit_post', $page_id ) ) {
			return Agent_Neo_Core_Auth::error( 'FORBIDDEN', __( 'Current user cannot edit this page.', 'agent-neo-core' ), array( 'page_id' => $page_id ) );
		}

		return array(
			'available' => true,
			'page_id'   => $page_id,
			'page'      => $page,
		);
	}

	/**
	 * Page apply 用 dry-run を準備する。
	 *
	 * @param array<string, mixed> $target Validated page apply target.
	 * @param array<string, mixed> $blueprint Blueprint.
	 * @param string               $request_id Request id.
	 * @return array<string, mixed>|WP_Error
	 */
	private function prepare_page_apply( array $target, array $blueprint, string $request_id ) {
		if ( empty( $target['available'] ) ) {
			return array(
				'available' => false,
				'reason'    => isset( $target['reason'] ) && is_string( $target['reason'] ) ? $target['reason'] : 'page_id_not_provided',
			);
		}

		$page_id = (int) $target['page_id'];
		$page    = $target['page'] ?? null;
		if ( ! $page instanceof WP_Post ) {
			return Agent_Neo_Core_Auth::error( 'NOT_FOUND', __( 'Page was not found.', 'agent-neo-core' ), array( 'page_id' => $page_id ) );
		}

		$after_content = $this->post_content_from_blueprint( $blueprint );
		$before       = $this->json_patch->document_from_post_content( $page->post_content );
		$after        = $this->json_patch->document_from_post_content( $after_content );
		$diff         = $this->json_patch->diff( $before, $after );
		$diff_hash    = $this->json_patch->diff_hash( $diff );
		$rollback     = 'rbp_' . wp_generate_uuid4();
		$blueprint_id = (string) $blueprint['blueprint_id'];
		$dry_run      = $this->dry_run_store->save(
			$request_id,
			$diff_hash,
			array(
				'action'                 => 'apply_page',
				'resource_id'            => $page_id,
				'resource_sub_id'        => $blueprint_id,
				'before_content_hash'    => hash( 'sha256', $page->post_content ),
				'patched_post_content'   => $after_content,
				'diff'                   => $diff,
				'rollback_preview_token' => $rollback,
				'blueprint_id'           => $blueprint_id,
			)
		);

		return array(
			'available'              => true,
			'method'                 => 'POST',
			'endpoint'               => '/wp-json/agent-neo/v1/pages/' . $page_id . '/apply',
			'page_id'                => $page_id,
			'request_id'             => $request_id,
			'diff_hash'              => $diff_hash,
			'dry_run_token'          => $dry_run,
			'rollback_preview_token' => $rollback,
			'apply_body'             => array(
				'diff_hash'          => $diff_hash,
				'idempotency_key'    => 'idem-' . $request_id,
				'request_id'         => $request_id,
				'template_id'        => $blueprint_id,
				'from_preview_token' => $rollback,
			),
			'rollback_endpoint'      => '/wp-json/agent-neo/v1/pages/' . $page_id . '/rollback',
		);
	}

	/**
	 * Blueprint から静的 block markup を生成する。
	 *
	 * @param array<string, mixed> $blueprint Blueprint.
	 * @return string
	 */
	private function post_content_from_blueprint( array $blueprint ): string {
		$sections = isset( $blueprint['sections'] ) && is_array( $blueprint['sections'] ) ? $blueprint['sections'] : array();
		$content  = '';

		foreach ( $sections as $section ) {
			if ( ! is_array( $section ) ) {
				continue;
			}

			$attrs = array(
				'section_id' => (string) $section['section_id'],
				'agentNeo'   => array(
					'blueprint_id' => (string) $section['blueprint_id'],
					'section_id'   => (string) $section['section_id'],
					'section_kind' => (string) $section['section_kind'],
					'order'        => (int) $section['order'],
				),
				'className'  => 'agent-neo-section agent-neo-section-' . (string) $section['section_kind'],
			);

			$encoded = wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES );
			$content .= '<!-- wp:group ' . ( is_string( $encoded ) ? $encoded : '{}' ) . ' -->' . "\n";
			$content .= '<div class="wp-block-group"></div>' . "\n";
			$content .= '<!-- /wp:group -->' . "\n\n";
		}

		return $content;
	}

	/**
	 * 一貫性検証結果を返す。
	 *
	 * @param string                              $blueprint_id Blueprint id.
	 * @param array<int, array<string, mixed>> $sections Sections.
	 * @return array<string, mixed>
	 */
	private function consistency_result( string $blueprint_id, array $sections ): array {
		$valid = count( $sections ) === count( self::SECTION_KINDS );
		foreach ( $sections as $index => $section ) {
			$expected_kind = self::SECTION_KINDS[ $index ] ?? '';
			$valid = $valid
				&& isset( $section['section_id'], $section['section_kind'], $section['order'] )
				&& $section['section_id'] === $blueprint_id . '-' . $expected_kind
				&& $section['section_kind'] === $expected_kind
				&& (int) $section['order'] === $index + 1;
		}

		return array(
			'valid'                       => $valid,
			'blueprint_id'                => $blueprint_id,
			'all_section_ids_consistent'  => $valid,
			'standard_section_kind_count' => count( self::SECTION_KINDS ),
			'section_kinds'               => self::SECTION_KINDS,
		);
	}

	/**
	 * JSON option に record を保存する。
	 *
	 * @param string               $option Option name.
	 * @param string               $key Record key.
	 * @param array<string, mixed> $record Record.
	 * @return true|WP_Error
	 */
	private function store_record( string $option, string $key, array $record ) {
		$records = $this->json_option( $option );
		if ( is_wp_error( $records ) ) {
			return $records;
		}

		$records[ $key ] = $record;
		$json            = wp_json_encode( $records, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		if ( ! is_string( $json ) ) {
			return Agent_Neo_Core_Auth::error( 'CONFLICT', __( 'Blueprint JSON serialization failed.', 'agent-neo-core' ) );
		}

		$updated = update_option( $option, $json, false );
		if ( ! $updated && get_option( $option, '' ) !== $json ) {
			return Agent_Neo_Core_Auth::error( 'CONFLICT', __( 'Blueprint storage update failed.', 'agent-neo-core' ) );
		}

		return true;
	}

	/**
	 * JSON option を配列として返す。
	 *
	 * @param string $option Option name.
	 * @return array<string, mixed>|WP_Error
	 */
	private function json_option( string $option ) {
		$value = get_option( $option, '{}' );
		if ( ! is_string( $value ) || '' === $value ) {
			return array();
		}

		$decoded = json_decode( $value, true );
		if ( ! is_array( $decoded ) ) {
			return Agent_Neo_Core_Auth::error( 'CONFLICT', __( 'Stored blueprint JSON is invalid.', 'agent-neo-core' ) );
		}

		return $decoded;
	}

	/**
	 * Request id を返す。
	 *
	 * @param array<string, mixed> $params Params.
	 * @return string
	 */
	private function request_id( array $params ): string {
		return isset( $params['request_id'] ) && is_string( $params['request_id'] ) && $this->is_uuid_v4( $params['request_id'] ) ? $params['request_id'] : wp_generate_uuid4();
	}

	/**
	 * UUIDv4 形式か判定する。
	 *
	 * @param string $value Value.
	 * @return bool
	 */
	private function is_uuid_v4( string $value ): bool {
		return 1 === preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{12}$/i', $value );
	}
}

add_action(
	'agent_neo_core_register_rest',
	static function ( Agent_Neo_Core_Container $container ): void {
		$controller = new Agent_Neo_Core_Blueprint_Controller(
			$container->auth(),
			$container->license_state(),
			$container->json_patch(),
			$container->dry_run_store()
		);
		$controller->register();
		$container->register_module( 'rest-blueprint' );
	}
);
