<?php
/** Public learning-data projection. Rendering belongs to the theme. */
defined( 'ABSPATH' ) || exit;

function wtcf_learning_search() {
	$query = isset( $_GET['learn_q'] ) && is_string( $_GET['learn_q'] ) ? sanitize_text_field( wp_unslash( $_GET['learn_q'] ) ) : '';
	$query = mb_substr( $query, 0, 200 );
	$raw_page = isset( $_GET['learn_page'] ) && is_string( $_GET['learn_page'] ) ? wp_unslash( $_GET['learn_page'] ) : '1';
	$requested = ctype_digit( $raw_page ) ? max( 1, (int) $raw_page ) : 1;
	$args = array( 'post_type' => 'wt_learning', 'post_status' => 'publish', 's' => $query,
		'posts_per_page' => 6, 'paged' => 1, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) );
	// An empty, out-of-range WP_Query does not populate found_posts. Count from
	// the first page before applying the requested offset, including huge inputs.
	$first = new WP_Query( $args );
	$pages = (int) $first->max_num_pages;
	$page = min( $requested, max( 1, $pages ) );
	$results = $first;
	if ( $page > 1 ) { $args['paged'] = $page; $args['no_found_rows'] = true; $results = new WP_Query( $args ); }
	return array( 'query' => $query, 'page' => $page, 'pages' => $pages, 'adjusted' => $requested !== $page,
		'count' => (int) $first->found_posts, 'items' => array_map( 'wtcf_learning_item', $results->posts ) );
}
function wtcf_learning_item( $post ) {
	return array( 'id' => $post->ID, 'title' => get_the_title( $post ), 'url' => get_permalink( $post ),
		'summary' => post_password_required( $post ) ? '閲覧にはパスワードが必要です。' : $post->post_excerpt, 'parent' => $post->post_parent, 'order' => $post->menu_order );
}
function wtcf_learning_display( $id ) {
	$post = get_post( $id );
	if ( ! $post || post_password_required( $post ) || $post->post_type !== 'wt_learning' || $post->post_status !== 'publish' ) { return null; }
	$data = wtcf_learning_item( $post );
	$doc = wtcf_document( $id );
	$data['kind'] = in_array( $doc['kind'] ?? '', wtcf_manifest()['learning_kinds'], true ) ? $doc['kind'] : 'lesson';
	// Read the editor's actual blocks; do not maintain a second copy of the body in metadata.
	$data['sections'] = array();
	$data['intro_blocks'] = array();
	foreach ( parse_blocks( $post->post_content ) as $block ) {
		if ( $block['blockName'] === 'core/heading' && ( $block['attrs']['level'] ?? 2 ) === 2 ) {
			$data['sections'][] = array( 'title' => wp_strip_all_tags( $block['innerHTML'] ), 'blocks' => array() );
		} elseif ( $data['sections'] ) {
			$data['sections'][ count( $data['sections'] ) - 1 ]['blocks'][] = $block;
		} else { $data['intro_blocks'][] = $block; }
	}

	$data['ancestors'] = array();
	foreach ( array_reverse( get_post_ancestors( $post ) ) as $parent_id ) {
		$parent = get_post( $parent_id );
		if ( $parent && $parent->post_status === 'publish' && $parent->post_type === 'wt_learning' ) { $data['ancestors'][] = wtcf_learning_item( $parent ); }
	}
	$parent = $post->post_parent ?: $id;
	$data['lessons'] = array_map( 'wtcf_learning_item', get_posts( array( 'post_type' => 'wt_learning', 'post_status' => 'publish',
		'post_parent' => $parent, 'posts_per_page' => -1, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) ) );
	$data['previous'] = null;
	$data['next'] = null;
	foreach ( $data['lessons'] as $index => $lesson ) {
		if ( $lesson['id'] === $id ) { $data['previous'] = $data['lessons'][ $index - 1 ] ?? null; $data['next'] = $data['lessons'][ $index + 1 ] ?? null; }
	}
	return $data;
}
add_filter( 'wp_robots', function ( $robots ) {
	if ( is_post_type_archive( 'wt_learning' ) && isset( $_GET['learn_q'] ) ) { $robots['noindex'] = true; }
	return $robots;
} );
