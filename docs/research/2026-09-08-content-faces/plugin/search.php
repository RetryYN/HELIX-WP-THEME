<?php
/** Keep public search pagination within the available result set. */
defined( 'ABSPATH' ) || exit;
add_action( 'pre_get_posts', function ( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_search() ) { return; }
	$page = (int) $query->get( 'paged' );
	if ( $page <= 1 ) { return; }
	$args = $query->query_vars;
	$args['paged'] = 1;
	$args['fields'] = 'ids';
	$args['no_found_rows'] = false;
	$probe = new WP_Query( $args );
	$per_page = (int) $probe->get( 'posts_per_page' );
	if ( $per_page <= 0 ) { return; }
	$last = max( 1, (int) ceil( $probe->found_posts / $per_page ) );
	$query->set( 'paged', min( $page, $last ) );
} );
