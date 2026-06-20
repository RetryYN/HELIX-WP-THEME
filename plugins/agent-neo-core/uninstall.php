<?php
/**
 * AGENT NEO Core uninstall cleanup.
 *
 * @package AgentNeoCore
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$agent_neo_core_policy_path = __DIR__ . '/config/uninstall-cleanup-policy.json';
$agent_neo_core_policy      = array();

if ( is_readable( $agent_neo_core_policy_path ) ) {
	$agent_neo_core_policy_contents = file_get_contents( $agent_neo_core_policy_path );
	$agent_neo_core_decoded_policy  = false !== $agent_neo_core_policy_contents ? json_decode( $agent_neo_core_policy_contents, true ) : null;

	if ( is_array( $agent_neo_core_decoded_policy ) ) {
		$agent_neo_core_policy = $agent_neo_core_decoded_policy;
	}
}

$agent_neo_core_batch_size = isset( $agent_neo_core_policy['batch_size'] ) ? (int) $agent_neo_core_policy['batch_size'] : 500;
if ( $agent_neo_core_batch_size < 1 ) {
	$agent_neo_core_batch_size = 500;
}

global $wpdb;

$agent_neo_core_options = isset( $agent_neo_core_policy['options'] ) && is_array( $agent_neo_core_policy['options'] )
	? $agent_neo_core_policy['options']
	: array(
		'agent_neo_core_version',
		'agent_neo_license_state',
		'agent_neo_feature_flags',
		'agent_neo_schema_errors',
		'agent_neo_catalog_cache',
		'agent_neo_tracking_signature_cache',
		'agent_neo_once_tokens',
		'agent_neo_replay_tokens',
	);

foreach ( $agent_neo_core_options as $agent_neo_core_option ) {
	if ( is_string( $agent_neo_core_option ) && 0 === strpos( $agent_neo_core_option, 'agent_neo_' ) ) {
		delete_option( $agent_neo_core_option );
	}
}

$agent_neo_core_transient_prefixes = isset( $agent_neo_core_policy['transient_prefixes'] ) && is_array( $agent_neo_core_policy['transient_prefixes'] )
	? $agent_neo_core_policy['transient_prefixes']
	: array( 'agent_neo_' );

foreach ( $agent_neo_core_transient_prefixes as $agent_neo_core_transient_prefix ) {
	if ( ! is_string( $agent_neo_core_transient_prefix ) || 0 !== strpos( $agent_neo_core_transient_prefix, 'agent_neo_' ) ) {
		continue;
	}

	$agent_neo_core_like_transient         = $wpdb->esc_like( '_transient_' . $agent_neo_core_transient_prefix ) . '%';
	$agent_neo_core_like_transient_timeout = $wpdb->esc_like( '_transient_timeout_' . $agent_neo_core_transient_prefix ) . '%';

	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$agent_neo_core_like_transient,
			$agent_neo_core_like_transient_timeout
		)
	);
}

do {
	$agent_neo_core_meta_ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT meta_id FROM {$wpdb->postmeta} WHERE meta_key LIKE %s LIMIT %d",
			$wpdb->esc_like( '_agent_neo_' ) . '%',
			$agent_neo_core_batch_size
		)
	);

	if ( empty( $agent_neo_core_meta_ids ) ) {
		break;
	}

	$agent_neo_core_meta_ids = array_map( 'absint', $agent_neo_core_meta_ids );
	$agent_neo_core_ids_sql  = implode( ',', $agent_neo_core_meta_ids );

	$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_id IN ({$agent_neo_core_ids_sql})" );
} while ( count( $agent_neo_core_meta_ids ) === $agent_neo_core_batch_size );

$agent_neo_core_cpts = isset( $agent_neo_core_policy['custom_post_types'] ) && is_array( $agent_neo_core_policy['custom_post_types'] )
	? $agent_neo_core_policy['custom_post_types']
	: array( 'agent_action' );

foreach ( $agent_neo_core_cpts as $agent_neo_core_cpt ) {
	if ( ! is_string( $agent_neo_core_cpt ) || ! in_array( $agent_neo_core_cpt, array( 'agent_action', 'agent_section_registry', 'agent_agent_license' ), true ) ) {
		continue;
	}

	do {
		$agent_neo_core_post_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s LIMIT %d",
				$agent_neo_core_cpt,
				$agent_neo_core_batch_size
			)
		);

		if ( empty( $agent_neo_core_post_ids ) ) {
			break;
		}

		foreach ( array_map( 'absint', $agent_neo_core_post_ids ) as $agent_neo_core_post_id ) {
			wp_delete_post( $agent_neo_core_post_id, true );
		}
	} while ( count( $agent_neo_core_post_ids ) === $agent_neo_core_batch_size );
}
