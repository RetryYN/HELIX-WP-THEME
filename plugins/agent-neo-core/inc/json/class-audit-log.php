<?php
/**
 * AgentAction audit writer.
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * apply/patch 操作を agent_action CPT に記録する。
 */
final class Agent_Neo_Core_Audit_Log {
	/**
	 * 監査ログを作成する。
	 *
	 * @param string               $action_type Action type。
	 * @param string               $request_id Request id。
	 * @param string               $diff_hash Diff hash。
	 * @param string               $idempotency_key Idempotency key。
	 * @param array<string, mixed> $details Details。
	 * @return string audit_id。
	 */
	public function record( string $action_type, string $request_id, string $diff_hash, string $idempotency_key, array $details = array() ): string {
		$audit_id = 'act_' . wp_generate_uuid4();
		$actor    = $this->actor();
		$target   = $this->target( $details );
		$diff     = $this->diff( $diff_hash, $details );

		$post_id = wp_insert_post(
			array(
				'post_type'   => Agent_Neo_Core_Agent_Action_CPT::POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => sprintf( '%s %s', $action_type, $request_id ),
				'meta_input'  => array(
					'_agent_neo_audit_id'        => $audit_id,
					'_agent_neo_action_type'     => $action_type,
					'_agent_neo_request_id'      => $request_id,
					'_agent_neo_diff_hash'       => $diff_hash,
					'_agent_neo_idempotency_key' => $idempotency_key,
					'_agent_neo_status'          => 'applied',
					'_agent_neo_actor'           => $actor,
					'_agent_neo_target'          => $target,
					'_agent_neo_diff'            => wp_slash( $diff ),
				),
			),
			true
		);

		if ( ! is_wp_error( $post_id ) && $post_id > 0 ) {
			$details_json = wp_json_encode( $details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			update_post_meta( (int) $post_id, '_agent_neo_details', wp_slash( is_string( $details_json ) ? $details_json : '{}' ) );
		}

		return $audit_id;
	}

	/**
	 * 現在の actor を保存用に返す。
	 *
	 * @return string
	 */
	private function actor(): string {
		$user_id = get_current_user_id();

		return $user_id > 0 ? (string) $user_id : 'system';
	}

	/**
	 * 監査対象を保存用に返す。
	 *
	 * @param array<string, mixed> $details Details。
	 * @return string
	 */
	private function target( array $details ): string {
		if ( isset( $details['target'] ) && is_scalar( $details['target'] ) ) {
			return sanitize_text_field( (string) $details['target'] );
		}

		foreach ( array( 'post_id', 'page_id', 'resource_id' ) as $key ) {
			if ( isset( $details[ $key ] ) && is_numeric( $details[ $key ] ) ) {
				return 'post:' . (string) absint( $details[ $key ] );
			}
		}

		if ( isset( $details['rollback_point_id'] ) && is_scalar( $details['rollback_point_id'] ) ) {
			return 'rollback:' . sanitize_text_field( (string) $details['rollback_point_id'] );
		}

		return 'unknown';
	}

	/**
	 * diff を JSON 文字列で保存する。
	 *
	 * @param string               $diff_hash Diff hash。
	 * @param array<string, mixed> $details Details。
	 * @return string
	 */
	private function diff( string $diff_hash, array $details ): string {
		$diff = isset( $details['diff'] ) ? $details['diff'] : array( 'hash' => $diff_hash );
		$json = wp_json_encode( $diff, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		if ( is_string( $json ) ) {
			return $json;
		}

		$fallback = wp_json_encode( array( 'hash' => $diff_hash ) );

		return is_string( $fallback ) ? $fallback : '{"hash":""}';
	}
}
