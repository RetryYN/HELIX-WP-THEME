<?php
/** Keep public search pagination within the available result set. */
defined( 'ABSPATH' ) || exit;
// Query objects are registered internally; a URL parameter cannot opt into this scope.
function wtcf_search_password_scopes() {
	static $scopes;
	if ( ! $scopes ) { $scopes = new WeakMap(); }
	return $scopes;
}
function wtcf_search_unlocked_posts() {
	$allowed = array();
	if ( ! isset( $_COOKIE[ 'wp-postpass_' . COOKIEHASH ] ) || ! is_string( $_COOKIE[ 'wp-postpass_' . COOKIEHASH ] ) ) { return $allowed; }
	global $wpdb;
	$after = 0;
	// Inspect protected records in bounded batches. Never return password values to the browser.
	do {
		$ids = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_password <> '' AND ID > %d ORDER BY ID LIMIT 200", $after ) );
		foreach ( $ids as $id ) {
			if ( ! post_password_required( (int) $id ) ) { $allowed[] = (int) $id; }
			$after = (int) $id;
		}
	} while ( count( $ids ) === 200 );
	return $allowed;
}
add_filter( 'posts_search', function ( $search, $query ) {
	$scopes = wtcf_search_password_scopes();
	if ( ! isset( $scopes[ $query ] ) ) { return $search; }
	global $wpdb;
	// Core adds this restriction for anonymous searches, regardless of a valid postpass cookie.
	// Replace only that clause; retain search terms and all other publication/capability filters.
	$search = str_replace( " AND ({$wpdb->posts}.post_password = '') ", '', $search );
	$allowed = $scopes[ $query ];
	$guard = "{$wpdb->posts}.post_password = ''";
	if ( $allowed ) { $guard .= " OR {$wpdb->posts}.ID IN (" . implode( ',', array_map( 'intval', $allowed ) ) . ')'; }
	return $search . ' AND (' . $guard . ') ';
}, 20, 2 );
add_action( 'template_redirect', function () {
	if ( is_search() ) { nocache_headers(); }
} );
add_action( 'pre_get_posts', function ( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) { return; }
	$scopes = wtcf_search_password_scopes();
	$scopes[ $query ] = wtcf_search_unlocked_posts();
	$page = (int) $query->get( 'paged' );
	if ( $page <= 1 ) { return; }
	$args = $query->query_vars;
	$args['paged'] = 1;
	$args['fields'] = 'ids';
	$args['no_found_rows'] = false;
	$probe = new WP_Query();
	$scopes[ $probe ] = $scopes[ $query ];
	$probe->query( $args );
	$per_page = (int) $probe->get( 'posts_per_page' );
	if ( $per_page <= 0 ) { return; }
	$last = max( 1, (int) ceil( $probe->found_posts / $per_page ) );
	$query->set( 'paged', min( $page, $last ) );
} );
