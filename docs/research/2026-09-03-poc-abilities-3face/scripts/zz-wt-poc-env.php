<?php
/**
 * Plugin Name: WT PoC env (local only)
 * Description: Local Docker PoC only. Enables Application Passwords over plain HTTP so REST/MCP faces can authenticate. Never deploy.
 */
add_filter( 'wp_is_application_passwords_available', '__return_true' );
