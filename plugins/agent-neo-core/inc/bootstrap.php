<?php
/**
 * Core plugin kernel を起動する。
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once AGENT_NEO_CORE_DIR . 'inc/schema/class-schema-loader.php';
require_once AGENT_NEO_CORE_DIR . 'inc/rest/class-auth.php';
require_once AGENT_NEO_CORE_DIR . 'inc/rest/class-rest-controller-base.php';
require_once AGENT_NEO_CORE_DIR . 'inc/rest/class-status-controller.php';
require_once AGENT_NEO_CORE_DIR . 'inc/cpt/class-agent-action-cpt.php';
require_once AGENT_NEO_CORE_DIR . 'inc/license/class-license-state.php';
require_once AGENT_NEO_CORE_DIR . 'inc/lifecycle/class-lifecycle.php';
require_once AGENT_NEO_CORE_DIR . 'inc/catalog/class-catalog-update-producer.php';
require_once AGENT_NEO_CORE_DIR . 'inc/class-agent-neo-core.php';

register_activation_hook( AGENT_NEO_CORE_FILE, array( 'Agent_Neo_Core_Lifecycle', 'activate' ) );
register_deactivation_hook( AGENT_NEO_CORE_FILE, array( 'Agent_Neo_Core_Lifecycle', 'deactivate' ) );

global $agent_neo_core;
$agent_neo_core = new Agent_Neo_Core();
$agent_neo_core->register();

if ( ! function_exists( 'agent_neo_core_health' ) ) {
	/**
	 * Core plugin の health サマリを返す。
	 *
	 * @return array<string, mixed>
	 */
	function agent_neo_core_health(): array {
		global $agent_neo_core;

		if ( ! $agent_neo_core instanceof Agent_Neo_Core ) {
			return array(
				'loaded'         => false,
				'loaded_modules' => array(),
				'errors'         => array( 'core_instance_missing' ),
			);
		}

		return $agent_neo_core->health();
	}
}
