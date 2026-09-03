<?php
/**
 * Plugin Name: WT pack PoC (abilities from pack.json)
 * Description: PoC mu-plugin. Reads pack.json and registers abilities via wp_register_ability(). Also registers a dedicated MCP server exposing the pack when MCP Adapter is active.
 * Version: 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const WT_PACK_OPTION = 'wt_poc_site_selection';

function wt_pack_load(): ?array {
	static $pack = null;
	if ( null === $pack ) {
		$file = __DIR__ . '/wt-pack.json';
		$pack = file_exists( $file ) ? json_decode( (string) file_get_contents( $file ), true ) : null;
	}
	return is_array( $pack ) ? $pack : null;
}

function wt_pack_state(): array {
	$state = get_option( WT_PACK_OPTION, array() );
	return wp_parse_args(
		is_array( $state ) ? $state : array(),
		array( 'header_part' => 'header-a', 'template_variant' => 'variant-1' )
	);
}

function wt_pack_diff( array $input ): array {
	$current = wt_pack_state();
	$diff    = array();
	foreach ( array( 'header_part', 'template_variant' ) as $key ) {
		if ( isset( $input[ $key ] ) && $input[ $key ] !== $current[ $key ] ) {
			$diff[ $key ] = array( 'from' => $current[ $key ], 'to' => $input[ $key ] );
		}
	}
	return $diff;
}

function wt_pack_receipt( array $diff ): string {
	return 'wt-dryrun-' . substr( hash_hmac( 'sha256', wp_json_encode( $diff ), wp_salt( 'nonce' ) ), 0, 16 );
}

function wt_pack_execute_callbacks(): array {
	return array(
		'wt/site-selection-read'    => static function () {
			$state = wt_pack_state();
			return array(
				'header_part'       => $state['header_part'],
				'header_candidates' => array( 'header-a', 'header-b', 'header-c' ),
				'template_variant'  => $state['template_variant'],
				'template_variants' => array( 'variant-1', 'variant-2' ),
			);
		},
		'wt/site-selection-dry-run' => static function ( $input ) {
			$diff = wt_pack_diff( is_array( $input ) ? $input : array() );
			return array( 'diff' => $diff, 'receipt' => wt_pack_receipt( $diff ) );
		},
		'wt/site-selection-apply'   => static function ( $input ) {
			$input = is_array( $input ) ? $input : array();
			if ( empty( $input['receipt'] ) || ! is_string( $input['receipt'] ) ) {
				return new WP_Error( 'wt_receipt_required', 'A dry-run receipt is required before apply.', array( 'status' => 400 ) );
			}
			$diff = wt_pack_diff( $input );
			if ( ! hash_equals( wt_pack_receipt( $diff ), $input['receipt'] ) ) {
				return new WP_Error( 'wt_receipt_mismatch', 'The receipt does not match the current dry-run diff.', array( 'status' => 409 ) );
			}
			$state = wt_pack_state();
			foreach ( $diff as $key => $change ) {
				$state[ $key ] = $change['to'];
			}
			update_option( WT_PACK_OPTION, $state, false );
			return array( 'applied' => ! empty( $diff ), 'state' => $state );
		},
	);
}

add_action( 'wp_abilities_api_categories_init', static function () {
	$pack = wt_pack_load();
	if ( ! $pack || ! function_exists( 'wp_register_ability_category' ) ) {
		return;
	}
	wp_register_ability_category( $pack['category']['slug'], array(
		'label'       => $pack['category']['label'],
		'description' => $pack['category']['description'],
	) );
} );

add_action( 'wp_abilities_api_init', static function () {
	$pack = wt_pack_load();
	if ( ! $pack || ! function_exists( 'wp_register_ability' ) ) {
		return;
	}
	$callbacks = wt_pack_execute_callbacks();
	foreach ( $pack['abilities'] as $def ) {
		$meta       = $def['meta'];
		$capability = $meta['permission'] ?? 'read';
		unset( $meta['permission'] ); // Not an Abilities API meta key; consumed here for permission_callback.
		$args = array(
			'label'               => $def['label'],
			'description'         => $def['description'],
			'category'            => $pack['category']['slug'],
			'output_schema'       => $def['output_schema'],
			'meta'                => $meta,
			'execute_callback'    => $callbacks[ $def['name'] ],
			'permission_callback' => static function () use ( $capability ) {
				return current_user_can( $capability );
			},
		);
		if ( isset( $def['input_schema'] ) ) {
			// A no-argument ability must omit input_schema: REST GET passes null input, which fails an object schema.
			$args['input_schema'] = $def['input_schema'];
		}
		wp_register_ability( $def['name'], $args );
	}
} );

// Face C: a dedicated MCP server exposing the pack abilities directly as tools (MCP Adapter).
add_action( 'mcp_adapter_init', static function ( $adapter ) {
	$pack = wt_pack_load();
	if ( ! $pack || ! class_exists( '\WP\MCP\Transport\HttpTransport' ) ) {
		return;
	}
	$adapter->create_server(
		'wt-pack-server',
		'mcp',
		'wt-pack',
		'WT pack PoC server',
		'Exposes the wt site-selection pack directly as MCP tools.',
		'v0.1.0',
		array( \WP\MCP\Transport\HttpTransport::class ),
		\WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class,
		\WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler::class,
		array_column( $pack['abilities'], 'name' )
	);
} );
