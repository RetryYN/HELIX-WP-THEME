<?php
/**
 * License state.
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Automation SEO entitlement の状態永続化と license fallback gate。
 */
final class Agent_Neo_Core_License_State {
	/**
	 * Option name。
	 */
	private const OPTION_NAME = 'agent_neo_license_state';

	/**
	 * package-matrix PF-002/PF-010 の grace period SSOT。
	 */
	private const GRACE_PERIOD_SECONDS = 48 * HOUR_IN_SECONDS;

	/**
	 * ライセンス検証キャッシュ TTL（PF-002）。
	 */
	private const CACHE_TTL_SECONDS = DAY_IN_SECONDS;

	/**
	 * package 階層。
	 *
	 * @var array<string, int>
	 */
	private const PACKAGE_RANK = array(
		'personal'  => 1,
		'corporate' => 2,
		'addon'     => 3,
	);

	/**
	 * Hook 登録の拡張点。
	 *
	 * @return void
	 */
	public function register(): void {
		add_filter( 'rest_request_before_callbacks', array( $this, 'guard_write_request' ), 10, 3 );
	}

	/**
	 * license mode を返す。
	 *
	 * @return string
	 */
	public function license_mode(): string {
		$state = $this->state();
		return isset( $state['license_mode'] ) && is_string( $state['license_mode'] ) ? $state['license_mode'] : 'readonly';
	}

	/**
	 * package を返す。
	 *
	 * @return string
	 */
	public function package(): string {
		$state = $this->state();
		$package = isset( $state['package'] ) && is_string( $state['package'] ) ? $state['package'] : 'personal';
		$package = $this->normalize_package( $package );

		if ( 'addon' === $package ) {
			return 'corporate';
		}

		return $package;
	}

	/**
	 * 連携状態を返す。
	 *
	 * @return string
	 */
	public function integration_status(): string {
		$state = $this->state();
		return isset( $state['integration_status'] ) && is_string( $state['integration_status'] ) ? $state['integration_status'] : 'not_configured';
	}

	/**
	 * License validate request を処理する。
	 *
	 * @param array<string, mixed> $params Request params。
	 * @return array<string, mixed>|WP_Error
	 */
	public function validate_license( array $params ) {
		$normalized = $this->normalize_validate_params( $params );
		if ( is_wp_error( $normalized ) ) {
			return $normalized;
		}

		if ( empty( $normalized['refresh'] ) ) {
			$cached = $this->cached_license_response( $normalized );
			if ( null !== $cached ) {
				return $cached;
			}
		}

		$result = $this->verify_upstream( $normalized );
		if ( is_wp_error( $result ) ) {
			$result = array(
				'status'     => 'transient',
				'error_code' => 'LICENSE_GATEWAY_ERROR',
				'message'    => $result->get_error_message(),
			);
		}

		$status = isset( $result['status'] ) && is_string( $result['status'] ) ? sanitize_key( $result['status'] ) : 'transient';
		if ( isset( $result['valid'] ) && is_bool( $result['valid'] ) ) {
			$status = $result['valid'] ? 'valid' : 'invalid';
		}
		if ( 'expired' === $status ) {
			$status = 'invalid';
		}

		if ( 'valid' === $status ) {
			return $this->record_valid_license( $normalized, $result );
		}

		if ( 'invalid' === $status ) {
			$this->record_invalid_license( $normalized, $result );
			return Agent_Neo_Core_Auth::error(
				'FEATURE_DISABLED',
				__( 'License is invalid or expired.', 'agent-neo-core' ),
				array(
					'reason' => 'license_invalid',
				)
			);
		}

		return $this->record_transient_failure( $normalized, $result );
	}

	/**
	 * REST callbacks の直前に license readonly gate を適用する。
	 *
	 * @param mixed           $response Current response。
	 * @param array<mixed>    $handler Matched handler。
	 * @param WP_REST_Request $request REST request。
	 * @return mixed
	 */
	public function guard_write_request( $response, array $handler, WP_REST_Request $request ) {
		if ( null !== $response ) {
			return $response;
		}

		if ( ! $this->is_guarded_write_route( $request ) ) {
			return $response;
		}

		$state  = $this->state();
		$reason = isset( $state['reason'] ) && is_string( $state['reason'] ) ? $state['reason'] : '';

		if ( $this->is_grace_active( $state ) ) {
			return Agent_Neo_Core_Auth::error(
				'LICENSE_GRACE_PERIOD',
				__( 'License server unreachable, write operations suspended.', 'agent-neo-core' ),
				array(
					'reason'                => 'license_unreachable',
					'grace_remaining_hours' => $this->grace_remaining_hours( $state ),
				)
			);
		}

		if ( $this->is_license_denied( $state ) ) {
			return Agent_Neo_Core_Auth::error(
				'FEATURE_DISABLED',
				__( 'License invalid, expired, or grace period elapsed.', 'agent-neo-core' ),
				array(
					'reason' => '' !== $reason ? $reason : 'license_invalid',
				)
			);
		}

		return $response;
	}

	/**
	 * License summary を返す。
	 *
	 * @return array<string, mixed>
	 */
	public function summary(): array {
		$state = $this->state();

		return array(
			'license_mode'       => $this->license_mode(),
			'package'            => $this->package(),
			'readonly_mode'      => $this->readonly_mode(),
			'reason'             => isset( $state['reason'] ) && is_string( $state['reason'] ) ? $state['reason'] : '',
			'expires_at'         => isset( $state['expires_at'] ) && is_string( $state['expires_at'] ) ? $state['expires_at'] : null,
			'next_check_at'      => isset( $state['next_check_at'] ) && is_string( $state['next_check_at'] ) ? $state['next_check_at'] : null,
			'integration_status' => $this->integration_status(),
		);
	}

	/**
	 * readonly 状態かを返す。
	 *
	 * @return bool
	 */
	public function readonly_mode(): bool {
		$state = $this->state();
		return ! empty( $state['readonly_mode'] );
	}

	/**
	 * Response 用 state を返す。
	 *
	 * @return array<string, mixed>
	 */
	public function response_state(): array {
		$state = $this->state();

		return array(
			'source'       => isset( $state['source'] ) && is_string( $state['source'] ) ? $state['source'] : 'option',
			'last_checked' => isset( $state['last_checked'] ) && is_string( $state['last_checked'] ) ? $state['last_checked'] : null,
			'error_code'   => isset( $state['error_code'] ) && is_string( $state['error_code'] ) ? $state['error_code'] : null,
		);
	}

	/**
	 * Option state を返す。
	 *
	 * @return array<string, mixed>
	 */
	private function state(): array {
		$state = get_option( self::OPTION_NAME, array() );
		return is_array( $state ) ? $state : array();
	}

	/**
	 * Request params を正規化する。
	 *
	 * @param array<string, mixed> $params Request params。
	 * @return array<string, mixed>|WP_Error
	 */
	private function normalize_validate_params( array $params ) {
		foreach ( array( 'site_id', 'package_id' ) as $field ) {
			if ( empty( $params[ $field ] ) || ! is_string( $params[ $field ] ) ) {
				return Agent_Neo_Core_Auth::error(
					'VALIDATION_ERROR',
					__( 'Required license field is missing or invalid.', 'agent-neo-core' ),
					array( 'field' => $field )
				);
			}
		}

		if ( isset( $params['license_key'] ) && ! is_string( $params['license_key'] ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'license_key must be a string.', 'agent-neo-core' ), array( 'field' => 'license_key' ) );
		}

		if ( isset( $params['refresh'] ) && ! is_bool( $params['refresh'] ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'refresh must be boolean.', 'agent-neo-core' ), array( 'field' => 'refresh' ) );
		}

		$product_tier = isset( $params['product_tier'] ) && is_string( $params['product_tier'] ) ? sanitize_key( $params['product_tier'] ) : 'personal';
		if ( ! isset( self::PACKAGE_RANK[ $product_tier ] ) ) {
			return Agent_Neo_Core_Auth::error( 'VALIDATION_ERROR', __( 'product_tier is invalid.', 'agent-neo-core' ), array( 'field' => 'product_tier' ) );
		}

		return array(
			'license_key'  => isset( $params['license_key'] ) ? (string) $params['license_key'] : '',
			'site_id'      => sanitize_text_field( (string) $params['site_id'] ),
			'product_tier' => $product_tier,
			'package_id'   => sanitize_key( (string) $params['package_id'] ),
			'refresh'      => ! empty( $params['refresh'] ),
		);
	}

	/**
	 * Upstream verification を実行する。
	 *
	 * @param array<string, mixed> $params Normalized params。
	 * @return array<string, mixed>|WP_Error
	 */
	private function verify_upstream( array $params ) {
		$filtered = apply_filters( 'agent_neo_core_license_verification_result', null, $params, $this );
		if ( null !== $filtered ) {
			return is_array( $filtered ) || is_wp_error( $filtered ) ? $filtered : new WP_Error( 'invalid_license_verifier', __( 'License verifier returned an invalid result.', 'agent-neo-core' ) );
		}

		$endpoint = get_option( 'agent_neo_license_endpoint', '' );
		if ( ! is_string( $endpoint ) || '' === $endpoint ) {
			return new WP_Error( 'license_endpoint_missing', __( 'License verification endpoint is not configured.', 'agent-neo-core' ) );
		}

		$response = wp_remote_post(
			$endpoint,
			array(
				'timeout' => 10,
				'body'    => wp_json_encode(
					array(
						'license_key' => $params['license_key'],
						'site_id'     => $params['site_id'],
						'package_id'  => $params['package_id'],
					)
				),
				'headers' => array(
					'Content-Type' => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = (int) wp_remote_retrieve_response_code( $response );
		if ( 502 === $status_code || 500 <= $status_code ) {
			return new WP_Error( 'license_gateway_error', __( 'License verification server is unavailable.', 'agent-neo-core' ) );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) ) {
			return new WP_Error( 'license_invalid_response', __( 'License verification response is invalid.', 'agent-neo-core' ) );
		}

		if ( 401 === $status_code || 403 === $status_code ) {
			$body['status'] = 'invalid';
		}

		return $body;
	}

	/**
	 * 有効 license を保存する。
	 *
	 * @param array<string, mixed> $params Request params。
	 * @param array<string, mixed> $result Verification result。
	 * @return array<string, mixed>
	 */
	private function record_valid_license( array $params, array $result ): array {
		$now        = time();
		$package    = isset( $result['package'] ) && is_string( $result['package'] ) ? $this->normalize_package( $result['package'] ) : $params['product_tier'];
		$expires_at = isset( $result['expires_at'] ) && is_string( $result['expires_at'] ) ? $result['expires_at'] : gmdate( DATE_ATOM, $now + ( 30 * DAY_IN_SECONDS ) );

		$state = array(
			'license_mode'       => 'valid',
			'package'            => $package,
			'package_id'         => $params['package_id'],
			'license_key_hash'   => '' !== $params['license_key'] ? hash( 'sha256', $params['license_key'] ) : '',
			'readonly_mode'      => false,
			'reason'             => '',
			'expires_at'         => $expires_at,
			'next_check_at'      => gmdate( DATE_ATOM, $now + self::CACHE_TTL_SECONDS ),
			'last_checked'       => gmdate( DATE_ATOM, $now ),
			'last_valid_at'      => gmdate( DATE_ATOM, $now ),
			'grace_started_at'   => null,
			'grace_expires_at'   => null,
			'failure_count'      => 0,
			'error_code'         => null,
			'integration_status' => 'ok',
			'source'             => 'upstream',
			'site_id'            => $params['site_id'],
		);

		$this->save_state( $state );
		return $this->response_from_state( $state, true );
	}

	/**
	 * invalid license を保存する。
	 *
	 * @param array<string, mixed> $params Request params。
	 * @param array<string, mixed> $result Verification result。
	 * @return void
	 */
	private function record_invalid_license( array $params, array $result ): void {
		$now   = time();
		$state = array(
			'license_mode'       => 'invalid',
			'package'            => 'personal',
			'package_id'         => $params['package_id'],
			'license_key_hash'   => '' !== $params['license_key'] ? hash( 'sha256', $params['license_key'] ) : '',
			'readonly_mode'      => true,
			'reason'             => 'license_invalid',
			'expires_at'         => isset( $result['expires_at'] ) && is_string( $result['expires_at'] ) ? $result['expires_at'] : null,
			'next_check_at'      => gmdate( DATE_ATOM, $now + self::CACHE_TTL_SECONDS ),
			'last_checked'       => gmdate( DATE_ATOM, $now ),
			'grace_started_at'   => null,
			'grace_expires_at'   => null,
			'failure_count'      => 0,
			'error_code'         => isset( $result['error_code'] ) && is_string( $result['error_code'] ) ? sanitize_key( $result['error_code'] ) : 'LICENSE_INVALID',
			'integration_status' => 'license_invalid',
			'source'             => 'upstream',
			'site_id'            => $params['site_id'],
		);

		$this->save_state( $state );
	}

	/**
	 * transient failure を保存する。
	 *
	 * @param array<string, mixed> $params Request params。
	 * @param array<string, mixed> $result Verification result。
	 * @return array<string, mixed>|WP_Error
	 */
	private function record_transient_failure( array $params, array $result ) {
		$now            = time();
		$current        = $this->state();
		$failure_count  = (int) ( $current['failure_count'] ?? 0 ) + 1;
		$grace_started  = isset( $current['grace_started_at'] ) && is_string( $current['grace_started_at'] ) ? strtotime( $current['grace_started_at'] ) : false;
		$grace_started  = false === $grace_started ? $now : (int) $grace_started;
		$grace_expires  = $grace_started + self::GRACE_PERIOD_SECONDS;
		$error_code     = isset( $result['error_code'] ) && is_string( $result['error_code'] ) ? sanitize_key( $result['error_code'] ) : 'LICENSE_GATEWAY_ERROR';
		$cached_package = isset( $current['package'] ) && is_string( $current['package'] ) ? $this->normalize_package( $current['package'] ) : $params['product_tier'];

		if ( $now > $grace_expires ) {
			$state = array_merge(
				$current,
				array(
					'license_mode'       => 'invalid',
					'package'            => 'personal',
					'package_id'         => $params['package_id'],
					'readonly_mode'      => true,
					'reason'             => 'grace_expired',
					'last_checked'       => gmdate( DATE_ATOM, $now ),
					'next_check_at'      => gmdate( DATE_ATOM, $now + self::CACHE_TTL_SECONDS ),
					'failure_count'      => $failure_count,
					'error_code'         => $error_code,
					'integration_status' => 'license_degraded',
					'source'             => 'upstream',
					'site_id'            => $params['site_id'],
				)
			);
			$this->save_state( $state );

			return Agent_Neo_Core_Auth::error(
				'FEATURE_DISABLED',
				__( 'License grace period elapsed.', 'agent-neo-core' ),
				array(
					'reason' => 'grace_expired',
				)
			);
		}

		$state = array_merge(
			$current,
			array(
				'license_mode'       => 'grace',
				'package'            => $cached_package,
				'package_id'         => $params['package_id'],
				'readonly_mode'      => true,
				'reason'             => 'license_unreachable',
				'last_checked'       => gmdate( DATE_ATOM, $now ),
				'next_check_at'      => gmdate( DATE_ATOM, $now + HOUR_IN_SECONDS ),
				'grace_started_at'   => gmdate( DATE_ATOM, $grace_started ),
				'grace_expires_at'   => gmdate( DATE_ATOM, $grace_expires ),
				'failure_count'      => $failure_count,
				'error_code'         => $error_code,
				'integration_status' => 'license_degraded',
				'source'             => 'upstream',
				'site_id'            => $params['site_id'],
			)
		);
		$this->save_state( $state );

		if ( empty( $current['last_valid_at'] ) && empty( $current['grace_started_at'] ) ) {
			return Agent_Neo_Core_Auth::error(
				'LICENSE_GATEWAY_ERROR',
				__( 'License verification server is unavailable.', 'agent-neo-core' ),
				array(
					'reason' => 'license_unreachable',
				)
			);
		}

		return $this->response_from_state( $state, false );
	}

	/**
	 * Fresh cached state があれば upstream 検証なしで返す。
	 *
	 * @param array<string, mixed> $params Request params。
	 * @return array<string, mixed>|WP_Error|null
	 */
	private function cached_license_response( array $params ) {
		$state = $this->state();
		if ( ! $this->is_cache_eligible_state( $state, $params ) ) {
			return null;
		}

		$mode = isset( $state['license_mode'] ) && is_string( $state['license_mode'] ) ? $state['license_mode'] : '';
		if ( 'valid' === $mode ) {
			return $this->response_from_state( $state, true );
		}

		if ( 'grace' === $mode && $this->is_grace_active( $state ) ) {
			return $this->response_from_state( $state, false );
		}

		if ( $this->is_license_denied( $state ) || in_array( $mode, array( 'invalid', 'expired' ), true ) ) {
			$reason = isset( $state['reason'] ) && is_string( $state['reason'] ) && '' !== $state['reason'] ? $state['reason'] : 'license_invalid';
			return Agent_Neo_Core_Auth::error(
				'FEATURE_DISABLED',
				__( 'License invalid, expired, or grace period elapsed.', 'agent-neo-core' ),
				array( 'reason' => $reason )
			);
		}

		return null;
	}

	/**
	 * Cached state を今回 request に利用できるか判定する。
	 *
	 * @param array<string, mixed> $state State。
	 * @param array<string, mixed> $params Request params。
	 * @return bool
	 */
	private function is_cache_eligible_state( array $state, array $params ): bool {
		$next_check_at = isset( $state['next_check_at'] ) && is_string( $state['next_check_at'] ) ? strtotime( $state['next_check_at'] ) : false;
		if ( false === $next_check_at || time() >= (int) $next_check_at ) {
			return false;
		}

		$mode = isset( $state['license_mode'] ) && is_string( $state['license_mode'] ) ? $state['license_mode'] : '';
		if ( ! in_array( $mode, array( 'valid', 'grace', 'invalid', 'expired' ), true ) ) {
			return false;
		}

		if ( isset( $state['site_id'] ) && is_string( $state['site_id'] ) && $state['site_id'] !== $params['site_id'] ) {
			return false;
		}

		if ( isset( $state['package_id'] ) && is_string( $state['package_id'] ) && $state['package_id'] !== $params['package_id'] ) {
			return false;
		}

		if ( '' !== $params['license_key'] ) {
			$cached_hash = isset( $state['license_key_hash'] ) && is_string( $state['license_key_hash'] ) ? $state['license_key_hash'] : '';
			if ( '' !== $cached_hash && ! hash_equals( $cached_hash, hash( 'sha256', (string) $params['license_key'] ) ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * State から response data を作る。
	 *
	 * @param array<string, mixed> $state State。
	 * @param bool                 $valid Valid。
	 * @return array<string, mixed>
	 */
	private function response_from_state( array $state, bool $valid ): array {
		return array(
			'valid'         => $valid,
			'package'       => isset( $state['package'] ) && is_string( $state['package'] ) ? $state['package'] : 'personal',
			'readonly_mode' => ! empty( $state['readonly_mode'] ),
			'reason'        => isset( $state['reason'] ) && is_string( $state['reason'] ) ? $state['reason'] : '',
			'expires_at'    => isset( $state['expires_at'] ) && is_string( $state['expires_at'] ) ? $state['expires_at'] : null,
			'next_check_at' => isset( $state['next_check_at'] ) && is_string( $state['next_check_at'] ) ? $state['next_check_at'] : null,
			'license_state' => array(
				'source'       => isset( $state['source'] ) && is_string( $state['source'] ) ? $state['source'] : 'option',
				'last_checked' => isset( $state['last_checked'] ) && is_string( $state['last_checked'] ) ? $state['last_checked'] : null,
				'error_code'   => isset( $state['error_code'] ) && is_string( $state['error_code'] ) ? $state['error_code'] : null,
			),
		);
	}

	/**
	 * State を保存する。
	 *
	 * @param array<string, mixed> $state State。
	 * @return void
	 */
	private function save_state( array $state ): void {
		update_option( self::OPTION_NAME, $state, false );
	}

	/**
	 * package 名を正規化する。
	 *
	 * @param string $package Package。
	 * @return string
	 */
	private function normalize_package( string $package ): string {
		$package = sanitize_key( $package );
		if ( str_contains( $package, 'corporate' ) ) {
			return 'corporate';
		}
		if ( str_contains( $package, 'addon' ) ) {
			return 'addon';
		}

		return 'personal';
	}

	/**
	 * guarded route かを判定する。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @return bool
	 */
	private function is_guarded_write_route( WP_REST_Request $request ): bool {
		if ( ! in_array( $request->get_method(), array( 'POST', 'PUT', 'PATCH', 'DELETE' ), true ) ) {
			return false;
		}

			$route = $request->get_route();
			if ( ! is_string( $route ) || ! str_starts_with( $route, '/agent-neo/v1/' ) ) {
				return false;
			}
			$route = untrailingslashit( $route );

		if ( '/agent-neo/v1/license/validate' === $route ) {
			return false;
		}

			foreach (
				array(
					'#^/agent-neo/v1/pages/#',
					'#^/agent-neo/v1/posts/\d+/blocks/[A-Za-z0-9_-]+$#',
					'#^/agent-neo/v1/posts/\d+/sections/[a-z0-9-]+/edit$#',
					'#^/agent-neo/v1/rollback/#',
					'#^/agent-neo/v1/actions/apply$#',
					'#^/agent-neo/v1/settings/import$#',
					// CTA swap（agent_neo_ctas option 永続更新）を license guard 対象に追加。
					'#^/agent-neo/v1/ctas/[a-z0-9-]+/apply$#',
				) as $pattern
			) {
				if ( 1 === preg_match( $pattern, $route ) ) {
					return true;
				}
		}

		return false;
	}

	/**
	 * grace active かを返す。
	 *
	 * @param array<string, mixed> $state State。
	 * @return bool
	 */
	private function is_grace_active( array $state ): bool {
		if ( 'grace' !== (string) ( $state['license_mode'] ?? '' ) || empty( $state['readonly_mode'] ) ) {
			return false;
		}

		$expires = isset( $state['grace_expires_at'] ) && is_string( $state['grace_expires_at'] ) ? strtotime( $state['grace_expires_at'] ) : false;
		return false !== $expires && time() <= (int) $expires;
	}

	/**
	 * denied state かを返す。
	 *
	 * @param array<string, mixed> $state State。
	 * @return bool
	 */
	private function is_license_denied( array $state ): bool {
		$mode = isset( $state['license_mode'] ) && is_string( $state['license_mode'] ) ? $state['license_mode'] : '';
		if ( 'grace' === $mode && ! empty( $state['readonly_mode'] ) ) {
			$expires = isset( $state['grace_expires_at'] ) && is_string( $state['grace_expires_at'] ) ? strtotime( $state['grace_expires_at'] ) : false;
			return false !== $expires && time() > (int) $expires;
		}

		return in_array( $mode, array( 'invalid', 'expired' ), true ) && ! empty( $state['readonly_mode'] );
	}

	/**
	 * grace 残り時間を切り上げ hours で返す。
	 *
	 * @param array<string, mixed> $state State。
	 * @return int
	 */
	private function grace_remaining_hours( array $state ): int {
		$expires = isset( $state['grace_expires_at'] ) && is_string( $state['grace_expires_at'] ) ? strtotime( $state['grace_expires_at'] ) : false;
		if ( false === $expires ) {
			return 0;
		}

		return max( 0, (int) ceil( ( (int) $expires - time() ) / HOUR_IN_SECONDS ) );
	}
}
