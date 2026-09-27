<?php
/**
 * Dry-run transient store.
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * dry-run の diff と適用予定 content を短命保存する。
 */
final class Agent_Neo_Core_Dry_Run_Store {
	private const TTL = HOUR_IN_SECONDS;

	/**
	 * dry-run 結果を保存する。
	 *
	 * @param string               $request_id Request id。
	 * @param string               $diff_hash Diff hash。
	 * @param array<string, mixed> $payload Payload。
	 * @return string dry_run_token。
	 */
	public function save( string $request_id, string $diff_hash, array $payload ): string {
		$token = wp_generate_uuid4();

		$payload['dry_run_token'] = $token;
		set_transient( $this->transient_key( $request_id, $diff_hash ), $payload, self::TTL );

		return $token;
	}

	/**
	 * dry-run 結果を取得する。
	 *
	 * @param string $request_id Request id。
	 * @param string $diff_hash Diff hash。
	 * @return array<string, mixed>|WP_Error
	 */
	public function get( string $request_id, string $diff_hash ) {
		$stored = get_transient( $this->transient_key( $request_id, $diff_hash ) );

		if ( false === $stored || ! is_array( $stored ) ) {
			return Agent_Neo_Core_Auth::error(
				'PRECONDITION_FAILED',
				__( 'Dry-run result was not found or has expired.', 'agent-neo-core' ),
				array(
					'request_id' => $request_id,
					'diff_hash'  => $diff_hash,
				)
			);
		}

		return $stored;
	}

	/**
	 * transient key を返す。
	 *
	 * @param string $request_id Request id。
	 * @param string $diff_hash Diff hash。
	 * @return string
	 */
	private function transient_key( string $request_id, string $diff_hash ): string {
		return 'agent_neo_dry_' . md5( $request_id . '|' . $diff_hash );
	}
}
