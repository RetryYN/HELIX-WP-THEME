<?php
/**
 * AgentAction CPT.
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 監査ログ用 CPT を登録する。
 */
final class Agent_Neo_Core_Agent_Action_CPT {
	/**
	 * CPT name。
	 */
	public const POST_TYPE = 'agent_action';

	/**
	 * CPT と meta を登録する。
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_meta' ) );
	}

	/**
	 * 非公開 AgentAction CPT を登録する。
	 *
	 * @return void
	 */
	public function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'          => __( 'Agent Actions', 'agent-neo-core' ),
					'singular_name' => __( 'Agent Action', 'agent-neo-core' ),
				),
				'public'              => false,
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'show_ui'             => false,
				'show_in_menu'        => false,
				'show_in_nav_menus'   => false,
				'show_in_admin_bar'   => false,
				'show_in_rest'        => false,
				'query_var'           => false,
				'rewrite'             => false,
				'has_archive'         => false,
				'supports'            => array( 'title', 'custom-fields' ),
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
			)
		);
	}

	/**
	 * 監査用 meta の型を固定する。
	 *
	 * @return void
	 */
	public function register_meta(): void {
		foreach ( $this->meta_schema() as $key => $args ) {
			register_post_meta( self::POST_TYPE, $key, $args );
		}
	}

	/**
	 * AgentAction meta schema。
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private function meta_schema(): array {
		$string_meta = array(
			'type'              => 'string',
			'single'            => true,
			'show_in_rest'      => false,
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => static function (): bool {
				return current_user_can( 'edit_posts' );
			},
		);

		return array(
			'_agent_neo_audit_id'        => $string_meta,
			'_agent_neo_action_type'     => $string_meta,
			'_agent_neo_request_id'      => $string_meta,
			'_agent_neo_diff_hash'       => $string_meta,
			'_agent_neo_idempotency_key' => $string_meta,
			'_agent_neo_status'          => $string_meta,
			'_agent_neo_actor'           => $string_meta,
			'_agent_neo_target'          => $string_meta,
			'_agent_neo_diff'            => array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => false,
				'sanitize_callback' => 'sanitize_textarea_field',
				'auth_callback'     => static function (): bool {
					return current_user_can( 'edit_posts' );
				},
			),
		);
	}
}
