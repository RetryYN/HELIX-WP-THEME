<?php
/**
 * AGENT NEO の起動入口。
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AGENT_NEO_VERSION', '0.1.0' );
define( 'AGENT_NEO_DIR', trailingslashit( get_template_directory() ) );
define( 'AGENT_NEO_URI', trailingslashit( get_template_directory_uri() ) );

require AGENT_NEO_DIR . 'inc/bootstrap.php';
