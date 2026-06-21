<?php
/**
 * テーマ kernel を起動する。
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once AGENT_NEO_DIR . 'inc/class-config-loader.php';
require_once AGENT_NEO_DIR . 'inc/setup/class-boundary-guard.php';
require_once AGENT_NEO_DIR . 'inc/setup/class-theme-setup.php';
require_once AGENT_NEO_DIR . 'inc/class-related-query.php';
require_once AGENT_NEO_DIR . 'inc/assets/class-third-party-manager.php';
require_once AGENT_NEO_DIR . 'inc/class-agent-neo-theme.php';

$agent_neo_theme = new Agent_Neo_Theme();
$agent_neo_theme->register();

if ( ! function_exists( 'agent_neo_health' ) ) {
	/**
	 * テーマ bootstrap 状態を返す。
	 *
	 * @return array<string, mixed>
	 */
	function agent_neo_health(): array {
		global $agent_neo_theme;

		if ( ! $agent_neo_theme instanceof Agent_Neo_Theme ) {
			return array(
				'loaded'  => false,
				'errors'  => array( 'theme_instance_missing' ),
				'modules' => array(),
				'steps'   => array(),
			);
		}

		return $agent_neo_theme->health();
	}
}
