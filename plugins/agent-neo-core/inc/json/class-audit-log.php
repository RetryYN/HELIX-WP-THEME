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
				),
			),
			true
		);

		if ( ! is_wp_error( $post_id ) && $post_id > 0 ) {
			update_post_meta( (int) $post_id, '_agent_neo_details', wp_json_encode( $details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) );
		}

		return $audit_id;
	}
}
