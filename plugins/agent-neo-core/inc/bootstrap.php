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
require_once AGENT_NEO_CORE_DIR . 'inc/json/class-json-patch.php';
require_once AGENT_NEO_CORE_DIR . 'inc/json/class-idempotency-store.php';
require_once AGENT_NEO_CORE_DIR . 'inc/json/class-rollback-store.php';
require_once AGENT_NEO_CORE_DIR . 'inc/json/class-audit-log.php';
require_once AGENT_NEO_CORE_DIR . 'inc/json/class-dry-run-store.php';
require_once AGENT_NEO_CORE_DIR . 'inc/cpt/class-agent-action-cpt.php';
require_once AGENT_NEO_CORE_DIR . 'inc/license/class-license-state.php';
require_once AGENT_NEO_CORE_DIR . 'inc/lifecycle/class-lifecycle.php';
require_once AGENT_NEO_CORE_DIR . 'inc/catalog/class-catalog-update-producer.php';
require_once AGENT_NEO_CORE_DIR . 'inc/class-container.php';

$agent_neo_core_rest_controllers = glob( AGENT_NEO_CORE_DIR . 'inc/rest/*-controller.php' );
if ( is_array( $agent_neo_core_rest_controllers ) ) {
	sort( $agent_neo_core_rest_controllers, SORT_STRING );

	$agent_neo_core_legacy_rest_order = array(
		'class-status-controller.php'   => 0,
		'class-actions-controller.php'  => 1,
		'class-blocks-controller.php'   => 2,
		'class-sections-controller.php' => 3,
		'class-pages-controller.php'    => 4,
	);

	usort(
		$agent_neo_core_rest_controllers,
		static function ( string $left, string $right ) use ( $agent_neo_core_legacy_rest_order ): int {
			$left_order  = $agent_neo_core_legacy_rest_order[ basename( $left ) ] ?? 100;
			$right_order = $agent_neo_core_legacy_rest_order[ basename( $right ) ] ?? 100;

			if ( $left_order === $right_order ) {
				return strcmp( $left, $right );
			}

			return $left_order <=> $right_order;
		}
	);

	foreach ( $agent_neo_core_rest_controllers as $agent_neo_core_rest_controller ) {
		require_once $agent_neo_core_rest_controller;
	}
}
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
