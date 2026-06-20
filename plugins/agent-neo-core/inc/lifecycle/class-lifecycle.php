<?php
/**
 * Plugin lifecycle hooks.
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Activation / deactivation を管理する。
 */
final class Agent_Neo_Core_Lifecycle {
	/**
	 * Activation hook。
	 *
	 * @return void
	 */
	public static function activate(): void {
		$schema_loader = new Agent_Neo_Core_Schema_Loader( AGENT_NEO_CORE_DIR . 'schema/' );
		$schema_loader->load();

		add_option( 'agent_neo_core_version', AGENT_NEO_CORE_VERSION, '', false );
		add_option(
			'agent_neo_license_state',
			array(
				'license_mode'       => 'readonly',
				'package'            => 'personal',
				'integration_status' => 'not_configured',
			),
			'',
			false
		);
		add_option(
			'agent_neo_feature_flags',
			array(
				'rest_scaffold' => true,
				'status'        => true,
			),
			'',
			false
		);

		if ( ! $schema_loader->is_valid() ) {
			update_option( 'agent_neo_schema_errors', $schema_loader->get_errors(), false );
		} else {
			delete_option( 'agent_neo_schema_errors' );
		}

		$cpt = new Agent_Neo_Core_Agent_Action_CPT();
		$cpt->register_post_type();
		flush_rewrite_rules();
	}

	/**
	 * Deactivation hook。
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		wp_clear_scheduled_hook( 'agent_neo_catalog_update_retry' );
		self::delete_agent_neo_transients();
		flush_rewrite_rules();
	}

	/**
	 * AGENT NEO transient を削除する。
	 *
	 * @return void
	 */
	private static function delete_agent_neo_transients(): void {
		global $wpdb;

		$like_transient         = $wpdb->esc_like( '_transient_agent_neo_' ) . '%';
		$like_transient_timeout = $wpdb->esc_like( '_transient_timeout_agent_neo_' ) . '%';

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				$like_transient,
				$like_transient_timeout
			)
		);
	}
}
