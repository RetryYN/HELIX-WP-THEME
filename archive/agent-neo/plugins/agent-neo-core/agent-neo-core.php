<?php
/**
 * Plugin Name: AGENT NEO Core
 * Description: AGENT NEO REST API, audit log, schema, and lifecycle foundation.
 * Version: 0.1.0
 * Requires at least: 6.6
 * Requires PHP: 8.1
 * Author: AGENT NEO
 * License: GPL v2 or later
 * Text Domain: agent-neo-core
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AGENT_NEO_CORE_VERSION', '0.1.0' );
define( 'AGENT_NEO_CORE_FILE', __FILE__ );
define( 'AGENT_NEO_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'AGENT_NEO_CORE_URL', plugin_dir_url( __FILE__ ) );

require AGENT_NEO_CORE_DIR . 'inc/bootstrap.php';
