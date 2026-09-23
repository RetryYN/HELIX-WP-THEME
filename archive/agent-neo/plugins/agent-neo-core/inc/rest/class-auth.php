<?php
/**
 * REST auth and error helpers.
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Write route の nonce/capability/package gate を集約する。
 */
final class Agent_Neo_Core_Auth {
	/**
	 * API error code と HTTP status の対応。
	 *
	 * @var array<string, int>
	 */
	private const ERROR_STATUS = array(
		'VALIDATION_ERROR'      => 400,
		'UNAUTHORIZED'          => 401,
		'FORBIDDEN'             => 403,
		'NOT_FOUND'             => 404,
		'CONFLICT'              => 409,
		'GONE'                  => 410,
		'PRECONDITION_FAILED'   => 412,
		'RATE_LIMITED'          => 429,
		'FEATURE_DISABLED'      => 403,
		'LICENSE_GRACE_PERIOD'  => 503,
		'AGENT_NEO_NOT_INSTALLED' => 409,
		'PLUGIN_AUTH_FAILED'    => 401,
		'RETRY_EXHAUSTED'       => 409,
		'SIGNATURE_INVALID'     => 401,
		'LICENSE_GATEWAY_ERROR' => 503,
		'INTERNAL_ERROR'        => 500,
	);

	/**
	 * Hook 登録の拡張点。
	 *
	 * @return void
	 */
	public function register(): void {
		/**
		 * Future write controllers can obtain this helper through the global kernel.
		 * This module intentionally registers no routes in the scaffold sprint.
		 */
	}

	/**
	 * REST write 経路の標準 permission callback。
	 *
	 * @param WP_REST_Request $request REST request。
	 * @param string          $capability 必要 capability。
	 * @return true|WP_Error
	 */
	public function check_write_permission( WP_REST_Request $request, string $capability = 'edit_posts' ) {
		$nonce = $request->get_header( 'X-WP-Nonce' );

		if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return self::error(
				'UNAUTHORIZED',
				__( 'REST nonce is missing or invalid.', 'agent-neo-core' )
			);
		}

		if ( ! current_user_can( $capability ) ) {
			return self::error(
				'FORBIDDEN',
				__( 'Current user cannot perform this AGENT NEO operation.', 'agent-neo-core' )
			);
		}

		return true;
	}

	/**
	 * package / route 境界の骨組み。
	 *
	 * @param string $required_package personal|corporate。
	 * @param string $current_package 現在 package。
	 * @return true|WP_Error
	 */
	public function check_package_scope( string $required_package, string $current_package ) {
		$rank = array(
			'personal'  => 1,
			'corporate' => 2,
		);

		if ( ! isset( $rank[ $required_package ], $rank[ $current_package ] ) ) {
			return self::error(
				'FORBIDDEN',
				__( 'Package scope is not available for this route.', 'agent-neo-core' )
			);
		}

		if ( $rank[ $current_package ] < $rank[ $required_package ] ) {
			return self::error(
				'FORBIDDEN',
				__( 'Package scope is insufficient for this route.', 'agent-neo-core' )
			);
		}

		return true;
	}

	/**
	 * API catalog の error code 体系に合わせた WP_Error を生成する。
	 *
	 * @param string               $code Error code。
	 * @param string               $message Error message。
	 * @param array<string, mixed> $details Details。
	 * @return WP_Error
	 */
	public static function error( string $code, string $message, array $details = array() ): WP_Error {
		$status = self::ERROR_STATUS[ $code ] ?? 500;

		return new WP_Error(
			$code,
			$message,
			array(
				'status' => $status,
				'error'  => array(
					'code'    => $code,
					'message' => $message,
					'details' => $details,
				),
			)
		);
	}

	/**
	 * StandardResponse エンベロープの成功応答を作る。
	 *
	 * @param array<string, mixed> $data Response data。
	 * @param string               $request_id Request id。
	 * @return array<string, mixed>
	 */
	public static function success_response( array $data, string $request_id ): array {
		return array(
			'success' => true,
			'data'    => $data,
			'meta'    => array(
				'request_id' => $request_id,
			),
			'error'   => null,
		);
	}
}
