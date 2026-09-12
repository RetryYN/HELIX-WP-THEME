<?php
/** Shared site information. The theme renders this projection; no form processor lives here. */
defined( 'ABSPATH' ) || exit;
function wtcf_site_manifest() {
	return json_decode( file_get_contents( __DIR__ . '/site-pages.json' ), true );
}
function wtcf_site_settings() {
	$manifest = wtcf_site_manifest();
	$override = get_option( 'wtcf_site_settings', array() );
	if ( ! is_array( $override ) ) { $override = array(); }
	$business = $manifest['business'];
	foreach ( $business as $key => $value ) {
		if ( isset( $override['business'][ $key ] ) && is_string( $override['business'][ $key ] ) ) { $business[ $key ] = sanitize_text_field( $override['business'][ $key ] ); }
	}
	$destinations = $manifest['destinations'];
	if ( isset( $override['destinations'] ) && is_array( $override['destinations'] ) ) {
		$destinations = array();
		foreach ( $override['destinations'] as $row ) {
			if ( ! is_array( $row ) ) { continue; }
			$clean = array();
			foreach ( array( 'name', 'purpose', 'data', 'condition' ) as $key ) { $clean[ $key ] = isset( $row[ $key ] ) && is_string( $row[ $key ] ) ? sanitize_text_field( $row[ $key ] ) : ''; }
			if ( $clean['name'] !== '' ) { $destinations[] = $clean; }
		}
	}
	return array( 'business' => $business, 'destinations' => $destinations );
}
