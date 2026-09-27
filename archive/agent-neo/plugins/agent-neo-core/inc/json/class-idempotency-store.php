<?php
/**
 * Idempotency transient store.
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * idempotency_key ごとの結果を 24h 保存する。
 */
final class Agent_Neo_Core_Idempotency_Store {
	private const TTL = DAY_IN_SECONDS;

	/**
	 * 保存済み結果を返す。payload が違えば 409。
	 *
	 * @param string $idempotency_key Key。
	 * @param string $payload_hash Payload hash。
	 * @return array<string, mixed>|null|WP_Error
	 */
	public function get( string $idempotency_key, string $payload_hash ) {
		$stored = get_transient( $this->transient_key( $idempotency_key ) );
		if ( false === $stored ) {
			return null;
		}

		if ( ! is_array( $stored ) || ! isset( $stored['payload_hash'], $stored['result'] ) ) {
			return null;
		}

		if ( ! hash_equals( (string) $stored['payload_hash'], $payload_hash ) ) {
			return Agent_Neo_Core_Auth::error(
				'CONFLICT',
				__( 'Idempotency key was already used with a different payload.', 'agent-neo-core' ),
				array( 'idempotency_key' => $idempotency_key )
			);
		}

		return is_array( $stored['result'] ) ? $stored['result'] : null;
	}

	/**
	 * 結果を保存する。
	 *
	 * @param string               $idempotency_key Key。
	 * @param string               $payload_hash Payload hash。
	 * @param array<string, mixed> $result Result。
	 * @return void
	 */
	public function save( string $idempotency_key, string $payload_hash, array $result ): void {
		set_transient(
			$this->transient_key( $idempotency_key ),
			array(
				'payload_hash' => $payload_hash,
				'result'       => $result,
				'created_at'   => time(),
			),
			self::TTL
		);
	}

	/**
	 * payload hash を作る。
	 *
	 * @param array<string, mixed> $payload Payload。
	 * @return string
	 */
	public function payload_hash( array $payload ): string {
		$json = wp_json_encode( $this->sort_recursive( $payload ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		return hash( 'sha256', is_string( $json ) ? $json : '' );
	}

	/**
	 * transient key を返す。
	 *
	 * @param string $idempotency_key Key。
	 * @return string
	 */
	private function transient_key( string $idempotency_key ): string {
		return 'agent_neo_idem_' . md5( $idempotency_key );
	}

	/**
	 * 再帰的に key sort する。
	 *
	 * @param mixed $value Value。
	 * @return mixed
	 */
	private function sort_recursive( $value ) {
		if ( ! is_array( $value ) ) {
			return $value;
		}

		foreach ( $value as $key => $child ) {
			$value[ $key ] = $this->sort_recursive( $child );
		}

		if ( array_keys( $value ) !== range( 0, count( $value ) - 1 ) ) {
			ksort( $value );
		}

		return $value;
	}
}
