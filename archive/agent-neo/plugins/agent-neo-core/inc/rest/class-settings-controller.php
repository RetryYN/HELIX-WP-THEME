<?php
/**
 * POST /settings/export and /settings/import controller.
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * JSON 統合 settings export/import endpoint。
 */
final class Agent_Neo_Core_Settings_Controller extends Agent_Neo_Core_REST_Controller_Base {
	private const SCHEMA_VERSION = 'agent-neo-settings.v1';
	private const CATALOG_OPTION = 'agent_neo_catalog_cache';
	private const CACHE_KEY      = 'settings_payload';
	private const TARGETS        = array(
		'design-tokens',
		'blueprints',
		'package-matrix',
	);

	private Agent_Neo_Core_Auth $auth;
	private Agent_Neo_Core_License_State $license_state;

	/**
	 * @param Agent_Neo_Core_Auth          $auth Auth helper。
	 * @param Agent_Neo_Core_License_State $license_state License state。
	 */
	public function __construct( Agent_Neo_Core_Auth $auth, Agent_Neo_Core_License_State $license_state ) {
		$this->auth          = $auth;
		$this->license_state = $license_state;
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
			'/settings/export',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'export_settings' ),
				'permission_callback' => array( $this, 'check_write_permission' ),
			)
		);

		$this->register_agent_route(
			'/settings/import',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'import_settings' ),
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
	 * POST /settings/export。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function export_settings( WP_REST_Request $request ) {
		$params = $this->json_params( $request, true );
		if ( is_wp_error( $params ) ) {
			return $params;
		}

		$targets = $this->normalize_targets( $params );
		if ( is_wp_error( $targets ) ) {
			return $targets;
		}

		$payload = $this->build_export_payload( $targets );
		$result  = $this->response_payload( $payload );

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response(
				$result,
				$this->stable_request_id( $result['hash'] )
			)
		);
	}

	/**
	 * POST /settings/import。
	 *
	 * @param WP_REST_Request $request Request。
	 * @return WP_REST_Response|WP_Error
	 */
	public function import_settings( WP_REST_Request $request ) {
		$params = $this->json_params( $request, false );
		if ( is_wp_error( $params ) ) {
			return $params;
		}

		$payload = $this->extract_import_payload( $params );
		if ( is_wp_error( $payload ) ) {
			return $payload;
		}

		$validation = $this->validate_settings_payload( $payload );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$payload = $this->normalize_payload( $payload );
		$targets = array_keys( $payload['targets'] );
		$updated = $this->save_imported_targets( $payload['targets'] );

		if ( ! $updated ) {
			return Agent_Neo_Core_Auth::error(
				'CONFLICT',
				__( 'Settings import could not be persisted.', 'agent-neo-core' )
			);
		}

		$roundtrip_payload = $this->build_export_payload( $targets );
		$result            = $this->response_payload( $roundtrip_payload );

		return rest_ensure_response(
			Agent_Neo_Core_Auth::success_response(
				array(
					'imported'       => true,
					'targets'        => $targets,
					'hash'           => $result['hash'],
					'canonical_json' => $result['canonical_json'],
				),
				$this->stable_request_id( $result['hash'] )
			)
		);
	}

	/**
	 * JSON body を返す。
	 *
	 * @param WP_REST_Request $request Request。
	 * @param bool            $allow_empty Empty body allowed。
	 * @return array<string, mixed>|WP_Error
	 */
	private function json_params( WP_REST_Request $request, bool $allow_empty ) {
		$params = $request->get_json_params();
		if ( null === $params && $allow_empty ) {
			return array();
		}

		if ( ! is_array( $params ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'JSON body is required.', 'agent-neo-core' ) );
		}

		return $params;
	}

	/**
	 * Export 対象を決定的順序へ正規化する。
	 *
	 * @param array<string, mixed> $params Request params。
	 * @return array<int, string>|WP_Error
	 */
	private function normalize_targets( array $params ) {
		$requested = $params['target'] ?? ( $params['targets'] ?? self::TARGETS );
		if ( is_string( $requested ) ) {
			$requested = array( $requested );
		}

		if ( ! is_array( $requested ) || empty( $requested ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'target must be a non-empty array.', 'agent-neo-core' ) );
		}

		$selected = array();
		foreach ( $requested as $target ) {
			if ( ! is_string( $target ) || ! in_array( $target, self::TARGETS, true ) ) {
				return Agent_Neo_Core_Auth::error(
					'VALIDATION_ERROR',
					__( 'Settings export target is invalid.', 'agent-neo-core' ),
					array( 'target' => $target )
				);
			}
			$selected[ $target ] = true;
		}

		return array_values(
			array_filter(
				self::TARGETS,
				static fn( string $target ): bool => isset( $selected[ $target ] )
			)
		);
	}

	/**
	 * Export payload を構築する。
	 *
	 * @param array<int, string> $targets Targets。
	 * @return array<string, mixed>
	 */
	private function build_export_payload( array $targets ): array {
		$stored  = $this->stored_settings_payload();
		$payload = array(
			'schema_version' => self::SCHEMA_VERSION,
			'target_order'   => array_values( $targets ),
			'targets'        => array(),
		);

		foreach ( $targets as $target ) {
			$value = isset( $stored[ $target ] ) && is_array( $stored[ $target ] )
				? $stored[ $target ]
				: $this->default_target_payload( $target );

			$payload['targets'][ $target ] = $this->normalize_json_value( $value );
		}

		return $this->normalize_payload( $payload );
	}

	/**
	 * Import payload を取り出す。
	 *
	 * @param array<string, mixed> $params Request params。
	 * @return array<string, mixed>|WP_Error
	 */
	private function extract_import_payload( array $params ) {
		if ( isset( $params['settings_payload'] ) && is_array( $params['settings_payload'] ) ) {
			return $params['settings_payload'];
		}

		if (
			isset( $params['data'] )
			&& is_array( $params['data'] )
			&& isset( $params['data']['settings_payload'] )
			&& is_array( $params['data']['settings_payload'] )
		) {
			return $params['data']['settings_payload'];
		}

		if ( isset( $params['schema_version'], $params['targets'] ) && is_array( $params['targets'] ) ) {
			return $params;
		}

		return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'settings_payload is required.', 'agent-neo-core' ) );
	}

	/**
	 * Settings payload を検証する。
	 *
	 * @param array<string, mixed> $payload Payload。
	 * @return true|WP_Error
	 */
	private function validate_settings_payload( array $payload ) {
		if ( ! isset( $payload['schema_version'] ) || ! is_string( $payload['schema_version'] ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'schema_version is required.', 'agent-neo-core' ) );
		}

		if ( self::SCHEMA_VERSION !== $payload['schema_version'] ) {
			return Agent_Neo_Core_Auth::error(
				'CONFLICT',
				__( 'Settings schema version is not supported.', 'agent-neo-core' ),
				array(
					'expected' => self::SCHEMA_VERSION,
					'actual'   => $payload['schema_version'],
				)
			);
		}

		if ( ! isset( $payload['targets'] ) || ! is_array( $payload['targets'] ) || empty( $payload['targets'] ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'targets must be a non-empty object.', 'agent-neo-core' ) );
		}

		foreach ( $payload['targets'] as $target => $value ) {
			if ( ! is_string( $target ) || ! in_array( $target, self::TARGETS, true ) ) {
				return Agent_Neo_Core_Auth::error(
					'VALIDATION_ERROR',
					__( 'Settings import target is invalid.', 'agent-neo-core' ),
					array( 'target' => $target )
				);
			}

			if ( ! is_array( $value ) ) {
				return Agent_Neo_Core_Auth::error(
					'VALIDATION_ERROR',
					__( 'Settings target payload must be an object or array.', 'agent-neo-core' ),
					array( 'target' => $target )
				);
			}

			$json_validation = $this->validate_json_value( $value );
			if ( is_wp_error( $json_validation ) ) {
				return $json_validation;
			}
		}

		return true;
	}

	/**
	 * JSON として保存可能な値だけを許可する。
	 *
	 * @param mixed $value Value。
	 * @param int   $depth Nesting depth。
	 * @return true|WP_Error
	 */
	private function validate_json_value( $value, int $depth = 0 ) {
		if ( $depth > 20 ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'Settings payload is too deeply nested.', 'agent-neo-core' ) );
		}

		if ( null === $value || is_bool( $value ) || is_int( $value ) || is_float( $value ) || is_string( $value ) ) {
			return true;
		}

		if ( is_array( $value ) ) {
			foreach ( $value as $child ) {
				$child_validation = $this->validate_json_value( $child, $depth + 1 );
				if ( is_wp_error( $child_validation ) ) {
					return $child_validation;
				}
			}
			return true;
		}

		return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'Settings payload contains a non-JSON value.', 'agent-neo-core' ) );
	}

	/**
	 * Payload 全体を正規化する。
	 *
	 * @param array<string, mixed> $payload Payload。
	 * @return array<string, mixed>
	 */
	private function normalize_payload( array $payload ): array {
		$targets = isset( $payload['targets'] ) && is_array( $payload['targets'] ) ? $payload['targets'] : array();
		$ordered = array();

		foreach ( self::TARGETS as $target ) {
			if ( array_key_exists( $target, $targets ) ) {
				$ordered[ $target ] = $this->normalize_json_value( $targets[ $target ] );
			}
		}

		return array(
			'schema_version' => self::SCHEMA_VERSION,
			'target_order'   => array_keys( $ordered ),
			'targets'        => $ordered,
		);
	}

	/**
	 * JSON 値の key order を安定させる。
	 *
	 * @param mixed $value Value。
	 * @return mixed
	 */
	private function normalize_json_value( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}

		if ( array_is_list( $value ) ) {
			return array_map( array( $this, 'normalize_json_value' ), $value );
		}

		ksort( $value, SORT_STRING );

		foreach ( $value as $key => $child ) {
			$value[ $key ] = $this->normalize_json_value( $child );
		}

		return $value;
	}

	/**
	 * Export response data を生成する。
	 *
	 * @param array<string, mixed> $payload Payload。
	 * @return array<string, mixed>
	 */
	private function response_payload( array $payload ): array {
		$canonical_json = $this->canonical_json( $payload );

		return array(
			'settings_payload' => $payload,
			'canonical_json'   => $canonical_json,
			'hash'             => 'sha256:' . hash( 'sha256', $canonical_json ),
			'targets'          => $payload['target_order'],
		);
	}

	/**
	 * Deterministic JSON を生成する。
	 *
	 * @param array<string, mixed> $payload Payload。
	 * @return string
	 */
	private function canonical_json( array $payload ): string {
		$json = wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION );
		return is_string( $json ) ? $json : '{}';
	}

	/**
	 * Stable request id を返す。
	 *
	 * @param string $hash Hash。
	 * @return string
	 */
	private function stable_request_id( string $hash ): string {
		return 'settings-' . substr( str_replace( 'sha256:', '', $hash ), 0, 24 );
	}

	/**
	 * 保存済み import payload を返す。
	 *
	 * @return array<string, mixed>
	 */
	private function stored_settings_payload(): array {
		$cache = get_option( self::CATALOG_OPTION, array() );
		if ( ! is_array( $cache ) || ! isset( $cache[ self::CACHE_KEY ] ) || ! is_array( $cache[ self::CACHE_KEY ] ) ) {
			return array();
		}

		return $cache[ self::CACHE_KEY ];
	}

	/**
	 * Import 対象を保存する。
	 *
	 * @param array<string, mixed> $targets Targets。
	 * @return bool
	 */
	private function save_imported_targets( array $targets ): bool {
		$cache = get_option( self::CATALOG_OPTION, array() );
		if ( ! is_array( $cache ) ) {
			$cache = array();
		}

		$current = isset( $cache[ self::CACHE_KEY ] ) && is_array( $cache[ self::CACHE_KEY ] )
			? $cache[ self::CACHE_KEY ]
			: array();

		foreach ( $targets as $target => $payload ) {
			$current[ $target ] = $this->normalize_json_value( $payload );
		}

		$cache[ self::CACHE_KEY ]             = $current;
		$cache['settings_schema_version']    = self::SCHEMA_VERSION;
		$cache['settings_payload_hash']      = 'sha256:' . hash( 'sha256', $this->canonical_json( $this->normalize_payload( array( 'targets' => $current ) ) ) );
		$cache['settings_payload_target_set'] = array_keys( $current );

		$updated = update_option( self::CATALOG_OPTION, $cache, false );
		if ( $updated ) {
			return true;
		}

		$stored_cache = get_option( self::CATALOG_OPTION, array() );
		return $stored_cache === $cache;
	}

	/**
	 * Target の初期 payload を返す。
	 *
	 * @param string $target Target。
	 * @return array<string, mixed>
	 */
	private function default_target_payload( string $target ): array {
		if ( 'design-tokens' === $target ) {
			return $this->theme_design_tokens();
		}

		if ( 'package-matrix' === $target ) {
			return array(
				'features' => array(),
				'license'  => $this->license_state->summary(),
			);
		}

		return array(
			'items' => array(),
		);
	}

	/**
	 * Active theme の theme.json から design token 相当を読む。
	 *
	 * @return array<string, mixed>
	 */
	private function theme_design_tokens(): array {
		$theme_json_path = trailingslashit( get_stylesheet_directory() ) . 'theme.json';
		$data            = array();

		if ( is_readable( $theme_json_path ) ) {
			$contents = file_get_contents( $theme_json_path );
			$decoded  = false !== $contents ? json_decode( $contents, true ) : null;
			$data     = is_array( $decoded ) ? $decoded : array();
		}

		return array(
			'settings' => isset( $data['settings'] ) && is_array( $data['settings'] ) ? $data['settings'] : array(),
			'styles'   => isset( $data['styles'] ) && is_array( $data['styles'] ) ? $data['styles'] : array(),
			'version'  => isset( $data['version'] ) ? $data['version'] : null,
		);
	}
}

add_action(
	'agent_neo_core_register_rest',
	static function ( Agent_Neo_Core_Container $container ): void {
		$controller = new Agent_Neo_Core_Settings_Controller(
			$container->auth(),
			$container->license_state()
		);
		$controller->register();
		$container->register_module( 'rest-settings' );
	}
);
