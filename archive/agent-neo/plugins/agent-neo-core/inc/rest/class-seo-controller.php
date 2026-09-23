<?php
/**
 * GET /seo/{post_id}, POST /seo/{post_id}/apply, and deprecated POST /seo/meta.
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SEO meta persistence and coexistence warnings.
 */
final class Agent_Neo_Core_SEO_Controller extends Agent_Neo_Core_REST_Controller_Base {
	private const META_KEY          = '_agent_neo_seo_meta';
	private const ROLLBACK_META_KEY = '_agent_neo_seo_rollback_points';
	private const MAX_ROLLBACKS     = 30;

	private Agent_Neo_Core_Auth $auth;
	private Agent_Neo_Core_JSON_Patch $json_patch;
	private Agent_Neo_Core_Idempotency_Store $idempotency_store;
	private Agent_Neo_Core_Audit_Log $audit_log;

	/**
	 * @param Agent_Neo_Core_Auth              $auth Auth helper.
	 * @param Agent_Neo_Core_JSON_Patch        $json_patch JSON diff helper.
	 * @param Agent_Neo_Core_Idempotency_Store $idempotency_store Idempotency store.
	 * @param Agent_Neo_Core_Audit_Log         $audit_log Audit log.
	 */
	public function __construct(
		Agent_Neo_Core_Auth $auth,
		Agent_Neo_Core_JSON_Patch $json_patch,
		Agent_Neo_Core_Idempotency_Store $idempotency_store,
		Agent_Neo_Core_Audit_Log $audit_log
	) {
		$this->auth              = $auth;
		$this->json_patch        = $json_patch;
		$this->idempotency_store = $idempotency_store;
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
			'/seo/(?P<post_id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_seo' ),
				'permission_callback' => array( $this, 'check_read_permission' ),
			)
		);

		$this->register_agent_route(
			'/seo/(?P<post_id>\d+)/apply',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'apply_seo' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
			)
		);

		$this->register_agent_route(
			'/seo/meta',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'apply_deprecated_meta' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
			)
		);
	}

	/**
	 * Read permission を確認する。
	 *
	 * @return true|WP_Error
	 */
	public function check_read_permission() {
		if ( ! is_user_logged_in() ) {
			return Agent_Neo_Core_Auth::error( 'UNAUTHORIZED', __( 'Authentication required for AGENT NEO SEO metadata.', 'agent-neo-core' ) );
		}

		if ( ! current_user_can( 'read' ) ) {
			return Agent_Neo_Core_Auth::error( 'FORBIDDEN', __( 'Current user cannot read AGENT NEO SEO metadata.', 'agent-neo-core' ) );
		}

		return true;
	}

	/**
	 * Write permission を確認する。
	 *
	 * @param WP_REST_Request $request Request.
	 * @return true|WP_Error
	 */
	public function check_write_permission( WP_REST_Request $request ) {
		return $this->auth->check_write_permission( $request, 'edit_posts' );
	}

	/**
	 * GET /seo/{post_id}.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_seo( WP_REST_Request $request ) {
		$post_id = (int) $request['post_id'];
		$post    = $this->post_for_read( $post_id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$meta       = $this->seo_meta( $post_id, $post );
		$request_id = $this->request_id( $request, $meta );
		$warnings   = $this->detect_coexistence_warnings( $post_id, $meta, $meta );

		$data = array_merge(
			array(
				'post_id'  => $post_id,
				'warnings' => $warnings,
			),
			$meta
		);

		return rest_ensure_response( Agent_Neo_Core_Auth::success_response( $data, $request_id ) );
	}

	/**
	 * POST /seo/{post_id}/apply.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function apply_seo( WP_REST_Request $request ) {
		return $this->apply_payload( (int) $request['post_id'], $request, false );
	}

	/**
	 * Deprecated POST /seo/meta.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function apply_deprecated_meta( WP_REST_Request $request ) {
		$params = $this->json_params( $request );
		if ( is_wp_error( $params ) ) {
			return $params;
		}

		$post_id = isset( $params['post_id'] ) ? (int) $params['post_id'] : (int) ( $params['target_id'] ?? 0 );
		if ( $post_id < 1 ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'post_id is required for deprecated SEO meta endpoint.', 'agent-neo-core' ) );
		}

		$response = $this->apply_payload( $post_id, $request, true, $params );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response = rest_ensure_response( $response );
		$response->header( 'Deprecation', 'true' );
		$response->header( 'Link', '</wp-json/agent-neo/v1/seo/' . $post_id . '/apply>; rel="successor-version"' );

		return $response;
	}

	/**
	 * SEO apply の共通処理。
	 *
	 * @param int                       $post_id Post id.
	 * @param WP_REST_Request           $request Request.
	 * @param bool                      $deprecated Deprecated route.
	 * @param array<string, mixed>|null $prefetched_params Prefetched params.
	 * @return WP_REST_Response|WP_Error
	 */
	private function apply_payload( int $post_id, WP_REST_Request $request, bool $deprecated, ?array $prefetched_params = null ) {
		$params = null === $prefetched_params ? $this->json_params( $request ) : $prefetched_params;
		if ( is_wp_error( $params ) ) {
			return $params;
		}

		$post = $this->post_for_write( $post_id );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$payload = $this->normalize_apply_payload( $params );
		if ( is_wp_error( $payload ) ) {
			return $payload;
		}

		$before          = $this->stored_meta( $post_id, $post );
		$after           = $this->merged_meta( $before, $payload );
		$diff            = $this->json_patch->diff( $before, $after );
		$diff_hash       = $this->json_patch->diff_hash( $diff );
		$request_id      = $this->request_id( $request, $params );
		$idempotency_key = $this->idempotency_key( $params, $request_id );

		if ( isset( $params['diff_hash'] ) && is_string( $params['diff_hash'] ) && '' !== $params['diff_hash'] && ! hash_equals( $params['diff_hash'], $diff_hash ) ) {
			return Agent_Neo_Core_Auth::error( 'PRECONDITION_FAILED', __( 'SEO diff_hash does not match current metadata.', 'agent-neo-core' ), array( 'expected' => $diff_hash ) );
		}

		$payload_hash = $this->idempotency_store->payload_hash(
			array(
				'post_id'       => $post_id,
				'payload'       => $payload,
				'deprecated'    => $deprecated,
				'computed_hash' => $diff_hash,
			)
		);

		$stored_result = $this->idempotency_store->get( $idempotency_key, $payload_hash );
		if ( is_wp_error( $stored_result ) ) {
			return $stored_result;
		}

		if ( is_array( $stored_result ) ) {
			$stored_result['applied'] = false;
			$stored_result['warnings'][] = array(
				'code'    => 'IDEMPOTENT_REPLAY',
				'message' => 'Stored SEO result returned without reapplying.',
			);
			return rest_ensure_response( Agent_Neo_Core_Auth::success_response( $stored_result, $request_id ) );
		}

		$warnings = $this->detect_coexistence_warnings( $post_id, $before, $after );
		if ( $deprecated ) {
			array_unshift(
				$warnings,
				array(
					'code'    => 'DEPRECATED_ENDPOINT',
					'message' => '/seo/meta is deprecated. Use /seo/{post_id}/apply.',
					'field'   => 'endpoint',
				)
			);
		}

		$rollback_point = $this->snapshot_seo_meta( $post_id, $before, $request_id );
		$encoded_after   = wp_json_encode( $after, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		$encoded_after   = is_string( $encoded_after ) ? $encoded_after : '{}';
		$saved           = update_post_meta( $post_id, self::META_KEY, wp_slash( $encoded_after ) );
		$current_encoded = get_post_meta( $post_id, self::META_KEY, true );
		if ( false === $saved && $current_encoded !== $encoded_after ) {
			return Agent_Neo_Core_Auth::error( 'CONFLICT', __( 'SEO metadata could not be persisted.', 'agent-neo-core' ), array( 'post_id' => $post_id ) );
		}

		$audit_id = $this->audit_log->record(
			'seo_apply',
			$request_id,
			$diff_hash,
			$idempotency_key,
			array(
				'post_id'           => $post_id,
				'rollback_point_id' => $rollback_point['rollback_point_id'],
				'warnings'          => $warnings,
				'deprecated'        => $deprecated,
			)
		);

		$result = array(
			'post_id'           => $post_id,
			'applied'           => true,
			'diff_hash'         => $diff_hash,
			'diff'              => $diff,
			'seo_risk_diff'     => $payload['seo_risk_diff'],
			'warnings'          => $warnings,
			'rollback_point'    => $rollback_point,
			'rollback_point_id' => $rollback_point['rollback_point_id'],
			'request_id'        => $request_id,
			'audit_id'          => $audit_id,
			'meta'              => $this->seo_meta( $post_id, $post ),
		);

		$this->idempotency_store->save( $idempotency_key, $payload_hash, $result );

		return rest_ensure_response( Agent_Neo_Core_Auth::success_response( $result, $request_id ) );
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
	 * Apply payload を正規化する。
	 *
	 * @param array<string, mixed> $params Params.
	 * @return array<string, mixed>|WP_Error
	 */
	private function normalize_apply_payload( array $params ) {
		if ( isset( $params['seo'] ) && is_array( $params['seo'] ) ) {
			$params = array_merge( $params, $params['seo'] );
		}

		if ( ! array_key_exists( 'seo_risk_diff', $params ) || ! is_array( $params['seo_risk_diff'] ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'seo_risk_diff is required and must be an array.', 'agent-neo-core' ), array( 'field' => 'seo_risk_diff' ) );
		}

		$risk_diff_validation = $this->validate_seo_risk_diff( $params['seo_risk_diff'] );
		if ( is_wp_error( $risk_diff_validation ) ) {
			return $risk_diff_validation;
		}

		if ( empty( $params['canonical'] ) || ! is_string( $params['canonical'] ) || ! $this->is_http_url( $params['canonical'] ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'canonical must be an absolute http(s) URL.', 'agent-neo-core' ), array( 'field' => 'canonical' ) );
		}

		if ( isset( $params['noindex'] ) && ! is_bool( $params['noindex'] ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'noindex must be boolean.', 'agent-neo-core' ), array( 'field' => 'noindex' ) );
		}

		foreach ( array( 'ogp', 'json_ld' ) as $key ) {
			if ( isset( $params[ $key ] ) && ! is_array( $params[ $key ] ) ) {
				return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'SEO nested field must be an object.', 'agent-neo-core' ), array( 'field' => $key ) );
			}
		}

		return array(
			'canonical'     => esc_url_raw( $params['canonical'] ),
			'noindex'       => isset( $params['noindex'] ) ? (bool) $params['noindex'] : false,
			'ogp'           => isset( $params['ogp'] ) && is_array( $params['ogp'] ) ? $this->sanitize_deep( $params['ogp'] ) : array(),
			'json_ld'       => isset( $params['json_ld'] ) && is_array( $params['json_ld'] ) ? $this->sanitize_deep( $params['json_ld'] ) : array(),
			'seo_risk_diff' => $this->sanitize_deep( $params['seo_risk_diff'] ),
		);
	}

	/**
	 * seo_risk_diff の Diff item schema を最小検証する。
	 *
	 * @param array<int, mixed> $diff Diff.
	 * @return true|WP_Error
	 */
	private function validate_seo_risk_diff( array $diff ) {
		$allowed_ops = array( 'add', 'remove', 'replace', 'move', 'copy', 'test' );
		foreach ( $diff as $index => $operation ) {
			if ( ! is_array( $operation ) ) {
				return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'seo_risk_diff operation must be an object.', 'agent-neo-core' ), array( 'index' => $index ) );
			}

			if ( empty( $operation['op'] ) || ! is_string( $operation['op'] ) || ! in_array( $operation['op'], $allowed_ops, true ) ) {
				return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'seo_risk_diff operation op is invalid.', 'agent-neo-core' ), array( 'index' => $index ) );
			}

			if ( empty( $operation['path'] ) || ! is_string( $operation['path'] ) || ! str_starts_with( $operation['path'], '/' ) ) {
				return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'seo_risk_diff operation path is invalid.', 'agent-neo-core' ), array( 'index' => $index ) );
			}
		}

		return true;
	}

	/**
	 * 現在値へ apply payload を反映する。
	 *
	 * @param array<string, mixed> $before Before.
	 * @param array<string, mixed> $payload Payload.
	 * @return array<string, mixed>
	 */
	private function merged_meta( array $before, array $payload ): array {
		$after                   = $before;
		$after['canonical']      = $payload['canonical'];
		$after['noindex']        = $payload['noindex'];
		$after['ogp']            = $payload['ogp'];
		$after['json_ld']        = $payload['json_ld'];
		$after['seo_risk_diff']  = $payload['seo_risk_diff'];
		$after['source']         = 'agent_neo_core';
		$after['schema_version'] = 'agent-neo-seo-meta.v1';
		$after['updated_at']     = gmdate( 'c' );

		return $after;
	}

	/**
	 * GET response 用 SEO meta を返す。
	 *
	 * @param int     $post_id Post id.
	 * @param WP_Post $post Post.
	 * @return array<string, mixed>
	 */
	private function seo_meta( int $post_id, WP_Post $post ): array {
		$stored = $this->stored_meta( $post_id, $post );
		$ogp    = isset( $stored['ogp'] ) && is_array( $stored['ogp'] ) ? $stored['ogp'] : array();

		$ogp = array_merge(
			array(
				'title'       => get_the_title( $post ),
				'description' => $this->post_description( $post ),
				'image'       => $this->featured_image_url( $post_id ),
				'type'        => 'page' === $post->post_type ? 'website' : 'article',
			),
			$ogp
		);

		return array(
			'canonical'      => isset( $stored['canonical'] ) && is_string( $stored['canonical'] ) && '' !== $stored['canonical'] ? $stored['canonical'] : get_permalink( $post ),
			'noindex'        => isset( $stored['noindex'] ) ? (bool) $stored['noindex'] : false,
			'ogp'            => $ogp,
			'json_ld'        => isset( $stored['json_ld'] ) && is_array( $stored['json_ld'] ) ? $stored['json_ld'] : array(),
			'seo_risk_diff'  => isset( $stored['seo_risk_diff'] ) && is_array( $stored['seo_risk_diff'] ) ? $stored['seo_risk_diff'] : array(),
			'source'         => isset( $stored['source'] ) && is_string( $stored['source'] ) ? $stored['source'] : 'fallback',
			'schema_version' => isset( $stored['schema_version'] ) && is_string( $stored['schema_version'] ) ? $stored['schema_version'] : 'agent-neo-seo-meta.v1',
			'updated_at'     => isset( $stored['updated_at'] ) && is_string( $stored['updated_at'] ) ? $stored['updated_at'] : '',
		);
	}

	/**
	 * 保存済み SEO meta を取得する。
	 *
	 * @param int     $post_id Post id.
	 * @param WP_Post $post Post.
	 * @return array<string, mixed>
	 */
	private function stored_meta( int $post_id, WP_Post $post ): array {
		$raw = get_post_meta( $post_id, self::META_KEY, true );
		if ( is_string( $raw ) && '' !== $raw ) {
			$decoded = json_decode( $raw, true );
			if ( is_array( $decoded ) ) {
				return $decoded;
			}
		}

		if ( is_array( $raw ) ) {
			return $raw;
		}

		return array(
			'canonical'      => get_permalink( $post ),
			'noindex'        => false,
			'ogp'            => array(),
			'json_ld'        => array(),
			'seo_risk_diff'  => array(),
			'source'         => 'fallback',
			'schema_version' => 'agent-neo-seo-meta.v1',
			'updated_at'     => '',
		);
	}

	/**
	 * 重複・競合 warning を検出する。
	 *
	 * @param int                  $post_id Post id.
	 * @param array<string, mixed> $before Before.
	 * @param array<string, mixed> $after After.
	 * @return array<int, array<string, mixed>>
	 */
	private function detect_coexistence_warnings( int $post_id, array $before, array $after ): array {
		$warnings = array();

		$before_canonical = isset( $before['canonical'] ) && is_string( $before['canonical'] ) ? $before['canonical'] : '';
		$after_canonical  = isset( $after['canonical'] ) && is_string( $after['canonical'] ) ? $after['canonical'] : '';
		if ( '' !== $before_canonical && '' !== $after_canonical && ! hash_equals( $before_canonical, $after_canonical ) ) {
			$warnings[] = $this->warning( 'CANONICAL_CHANGED', 'canonical', 'Existing AGENT NEO canonical will be changed.', 'agent_neo_core', $before_canonical, $after_canonical );
		}

		if ( array_key_exists( 'noindex', $before ) && array_key_exists( 'noindex', $after ) && (bool) $before['noindex'] !== (bool) $after['noindex'] ) {
			$warnings[] = $this->warning( 'NOINDEX_CHANGED', 'noindex', 'Existing AGENT NEO noindex value will be changed.', 'agent_neo_core', (bool) $before['noindex'], (bool) $after['noindex'] );
		}

		foreach ( $this->external_seo_sources( $post_id ) as $source ) {
			if ( '' !== $after_canonical && isset( $source['canonical'] ) && is_string( $source['canonical'] ) && '' !== $source['canonical'] ) {
				$warnings[] = $this->warning(
					hash_equals( $source['canonical'], $after_canonical ) ? 'CANONICAL_DUPLICATE' : 'CANONICAL_CONFLICT',
					'canonical',
					'Another SEO source already defines canonical.',
					(string) $source['source'],
					$source['canonical'],
					$after_canonical
				);
			}

			if ( array_key_exists( 'noindex', $source ) && null !== $source['noindex'] ) {
				$external_noindex = (bool) $source['noindex'];
				$after_noindex    = isset( $after['noindex'] ) ? (bool) $after['noindex'] : false;
				if ( $external_noindex !== $after_noindex ) {
					$warnings[] = $this->warning( 'NOINDEX_CONFLICT', 'noindex', 'Another SEO source defines a different noindex value.', (string) $source['source'], $external_noindex, $after_noindex );
				} elseif ( $after_noindex ) {
					$warnings[] = $this->warning( 'NOINDEX_DUPLICATE', 'noindex', 'Another SEO source also defines noindex.', (string) $source['source'], $external_noindex, $after_noindex );
				}
			}

			if ( ! empty( $after['json_ld'] ) && ! empty( $source['json_ld'] ) ) {
				$warnings[] = $this->warning( 'JSON_LD_DUPLICATE', 'json_ld', 'Another SEO source already defines JSON-LD.', (string) $source['source'], true, true );
			}
		}

		return $this->unique_warnings( $warnings );
	}

	/**
	 * 外部 SEO source を post meta から読む。
	 *
	 * @param int $post_id Post id.
	 * @return array<int, array<string, mixed>>
	 */
	private function external_seo_sources( int $post_id ): array {
		$sources = array(
			'yoast'     => array(
				'canonical' => $this->string_meta( $post_id, '_yoast_wpseo_canonical' ),
				'noindex'   => $this->bool_meta( $post_id, '_yoast_wpseo_meta-robots-noindex' ),
			),
			'rank_math' => array(
				'canonical' => $this->string_meta( $post_id, 'rank_math_canonical_url' ),
				'noindex'   => $this->rank_math_noindex( $post_id ),
				'json_ld'   => $this->string_meta( $post_id, 'rank_math_schema' ),
			),
			'aioseo'    => array(
				'canonical' => $this->string_meta( $post_id, '_aioseo_canonical_url' ),
				'noindex'   => $this->bool_meta( $post_id, '_aioseo_robots_noindex' ),
			),
			'seopress'  => array(
				'canonical' => $this->string_meta( $post_id, '_seopress_titles_canonical' ),
				'noindex'   => $this->bool_meta( $post_id, '_seopress_robots_index', true ),
			),
			'swell'     => array(
				'canonical' => $this->first_string_meta( $post_id, array( 'swell_meta_canonical', '_swell_meta_canonical', 'swell_canonical_url', '_swell_canonical_url' ) ),
				'noindex'   => $this->first_bool_meta( $post_id, array( 'swell_meta_noindex', '_swell_meta_noindex', 'swell_noindex', '_swell_noindex' ) ),
			),
			'jinr'      => array(
				'canonical' => $this->first_string_meta( $post_id, array( 'jinr_canonical', '_jinr_canonical', 'jin_canonical', '_jin_canonical' ) ),
				'noindex'   => $this->first_bool_meta( $post_id, array( 'jinr_noindex', '_jinr_noindex', 'jin_noindex', '_jin_noindex' ) ),
			),
		);

		$normalized = array();
		foreach ( $sources as $name => $source ) {
			$source['source'] = $name;
			if ( ! empty( $source['canonical'] ) || null !== $source['noindex'] || ! empty( $source['json_ld'] ) ) {
				$normalized[] = $source;
			}
		}

		/**
		 * Extend SEO coexistence source detection without changing this controller.
		 *
		 * @param array<int, array<string, mixed>> $normalized Sources.
		 * @param int                              $post_id Post id.
		 */
		return apply_filters( 'agent_neo_core_seo_external_sources', $normalized, $post_id );
	}

	/**
	 * SEO rollback point を保存する。
	 *
	 * @param int                  $post_id Post id.
	 * @param array<string, mixed> $before Before meta.
	 * @param string               $request_id Request id.
	 * @return array<string, mixed>
	 */
	private function snapshot_seo_meta( int $post_id, array $before, string $request_id ): array {
		$points = get_post_meta( $post_id, self::ROLLBACK_META_KEY, true );
		$points = is_array( $points ) ? $points : array();
		$point  = array(
			'rollback_point_id' => 'seo_rb_' . wp_generate_uuid4(),
			'post_id'           => $post_id,
			'meta_key'          => self::META_KEY,
			'meta'              => $before,
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
	 * Read 対象 post を返す。
	 *
	 * @param int $post_id Post id.
	 * @return WP_Post|WP_Error
	 */
	private function post_for_read( int $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return Agent_Neo_Core_Auth::error( 'NOT_FOUND', __( 'Post was not found.', 'agent-neo-core' ), array( 'post_id' => $post_id ) );
		}

		if ( ! current_user_can( 'read_post', $post_id ) ) {
			return Agent_Neo_Core_Auth::error( 'FORBIDDEN', __( 'Current user cannot read this post.', 'agent-neo-core' ), array( 'post_id' => $post_id ) );
		}

		return $post;
	}

	/**
	 * Write 対象 post を返す。
	 *
	 * @param int $post_id Post id.
	 * @return WP_Post|WP_Error
	 */
	private function post_for_write( int $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post instanceof WP_Post ) {
			return Agent_Neo_Core_Auth::error( 'NOT_FOUND', __( 'Post was not found.', 'agent-neo-core' ), array( 'post_id' => $post_id ) );
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return Agent_Neo_Core_Auth::error( 'FORBIDDEN', __( 'Current user cannot edit this post.', 'agent-neo-core' ), array( 'post_id' => $post_id ) );
		}

		return $post;
	}

	/**
	 * Warning object を返す。
	 *
	 * @param string $code Code.
	 * @param string $field Field.
	 * @param string $message Message.
	 * @param string $source Source.
	 * @param mixed  $existing Existing value.
	 * @param mixed  $incoming Incoming value.
	 * @return array<string, mixed>
	 */
	private function warning( string $code, string $field, string $message, string $source, $existing, $incoming ): array {
		return array(
			'code'     => $code,
			'field'    => $field,
			'message'  => $message,
			'source'   => $source,
			'existing' => $existing,
			'incoming' => $incoming,
		);
	}

	/**
	 * Warnings を重複排除する。
	 *
	 * @param array<int, array<string, mixed>> $warnings Warnings.
	 * @return array<int, array<string, mixed>>
	 */
	private function unique_warnings( array $warnings ): array {
		$seen = array();
		$out  = array();

		foreach ( $warnings as $warning ) {
			$key = md5( wp_json_encode( $warning ) ?: '' );
			if ( isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;
			$out[]        = $warning;
		}

		return $out;
	}

	/**
	 * @param mixed $value Value.
	 * @return mixed
	 */
	private function sanitize_deep( $value ) {
		if ( is_array( $value ) ) {
			$sanitized = array();
			foreach ( $value as $key => $child ) {
				$sanitized[ $key ] = $this->sanitize_deep( $child );
			}
			return $sanitized;
		}

		if ( is_string( $value ) ) {
			return sanitize_text_field( $value );
		}

		return $value;
	}

	/**
	 * @param string $value URL.
	 * @return bool
	 */
	private function is_http_url( string $value ): bool {
		$parts = wp_parse_url( $value );
		if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return false;
		}

		return in_array( strtolower( (string) $parts['scheme'] ), array( 'http', 'https' ), true );
	}

	/**
	 * @param WP_REST_Request      $request Request.
	 * @param array<string, mixed> $seed Seed.
	 * @return string
	 */
	private function request_id( WP_REST_Request $request, array $seed ): string {
		$request_id = $request->get_header( 'X-Request-Id' );
		if ( ! is_string( $request_id ) || '' === $request_id ) {
			$request_id = isset( $seed['request_id'] ) && is_string( $seed['request_id'] ) ? $seed['request_id'] : '';
		}

		if ( '' !== $request_id && $this->is_uuid_v4( $request_id ) ) {
			return $request_id;
		}

		return wp_generate_uuid4();
	}

	/**
	 * @param array<string, mixed> $params Params.
	 * @param string               $request_id Request id.
	 * @return string
	 */
	private function idempotency_key( array $params, string $request_id ): string {
		if ( isset( $params['idempotency_key'] ) && is_string( $params['idempotency_key'] ) && '' !== $params['idempotency_key'] ) {
			return $params['idempotency_key'];
		}

		return 'seo_' . $request_id;
	}

	/**
	 * @param int    $post_id Post id.
	 * @param string $key Meta key.
	 * @return string
	 */
	private function string_meta( int $post_id, string $key ): string {
		$value = get_post_meta( $post_id, $key, true );
		return is_string( $value ) ? trim( $value ) : '';
	}

	/**
	 * @param int          $post_id Post id.
	 * @param string       $key Meta key.
	 * @param bool|null    $inverse Invert yes/no values.
	 * @return bool|null
	 */
	private function bool_meta( int $post_id, string $key, ?bool $inverse = false ): ?bool {
		$value = get_post_meta( $post_id, $key, true );
		if ( '' === $value || null === $value ) {
			return null;
		}

		$normalized = is_bool( $value ) ? $value : in_array( strtolower( (string) $value ), array( '1', 'true', 'yes', 'on', 'noindex' ), true );
		return $inverse ? ! $normalized : $normalized;
	}

	/**
	 * @param int                $post_id Post id.
	 * @param array<int,string>  $keys Meta keys.
	 * @return string
	 */
	private function first_string_meta( int $post_id, array $keys ): string {
		foreach ( $keys as $key ) {
			$value = $this->string_meta( $post_id, $key );
			if ( '' !== $value ) {
				return $value;
			}
		}

		return '';
	}

	/**
	 * @param int                $post_id Post id.
	 * @param array<int,string>  $keys Meta keys.
	 * @return bool|null
	 */
	private function first_bool_meta( int $post_id, array $keys ): ?bool {
		foreach ( $keys as $key ) {
			$value = $this->bool_meta( $post_id, $key );
			if ( null !== $value ) {
				return $value;
			}
		}

		return null;
	}

	/**
	 * @param int $post_id Post id.
	 * @return bool|null
	 */
	private function rank_math_noindex( int $post_id ): ?bool {
		$value = get_post_meta( $post_id, 'rank_math_robots', true );
		if ( is_array( $value ) ) {
			return in_array( 'noindex', array_map( 'strval', $value ), true );
		}

		if ( is_string( $value ) && '' !== $value ) {
			return str_contains( $value, 'noindex' );
		}

		return null;
	}

	/**
	 * @param WP_Post $post Post.
	 * @return string
	 */
	private function post_description( WP_Post $post ): string {
		$excerpt = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( $post->post_content ), 28, '' );
		return is_string( $excerpt ) ? $excerpt : '';
	}

	/**
	 * @param int $post_id Post id.
	 * @return string
	 */
	private function featured_image_url( int $post_id ): string {
		$image = get_the_post_thumbnail_url( $post_id, 'full' );
		return is_string( $image ) ? $image : '';
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
		$controller = new Agent_Neo_Core_SEO_Controller(
			$container->auth(),
			$container->json_patch(),
			$container->idempotency_store(),
			$container->audit_log()
		);
		$controller->register();
		$container->register_module( 'rest-seo' );
	}
);
