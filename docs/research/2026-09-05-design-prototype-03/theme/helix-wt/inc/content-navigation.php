<?php
/** Shared headers reference a published core navigation entity. */
defined( 'ABSPATH' ) || exit;
function wtcf_shared_navigation_ref() {
	$ref = get_theme_mod( 'wt_content_navigation_ref', 0 );
	if ( ! is_scalar( $ref ) || ! ctype_digit( (string) $ref ) ) { return 0; }
	$post = get_post( (int) $ref );
	return $post && 'wp_navigation' === $post->post_type && 'publish' === $post->post_status ? $post->ID : 0;
}
function wtcf_in_shared_header() { return 'header' === ( $GLOBALS['wtcf_shared_part_context'] ?? null ) && wtcf_shared_chrome(); }
add_filter( 'pre_render_block', function ( $pre, $block ) {
	if ( null !== $pre || ! wtcf_in_shared_header() ) { return $pre; }
	if ( 'core/navigation' === $block['blockName'] ) {
		$ref = wtcf_shared_navigation_ref();
		if ( ! $ref ) { return ''; }
		static $binding = false;
		if ( $binding ) { return $pre; }
		$block['attrs']['ref'] = $ref;
		$block['innerBlocks'] = array();
		$block['innerHTML'] = '';
		$block['innerContent'] = array();
		$binding = true;
		try { return render_block( $block ); } finally { $binding = false; }
	}
	if ( 'core/pattern' === $block['blockName'] && 'helix-wt/header-sp-extras' === ( $block['attrs']['slug'] ?? '' ) ) {
		$ref = wtcf_shared_navigation_ref();
		return $ref ? do_blocks( '<!-- wp:navigation ' . wp_json_encode( array( 'ref' => $ref, 'overlayMenu' => 'never', 'className' => 'wt-header__textnav' ) ) . ' /-->' ) : '';
	}
	return $pre;
}, 10, 2 );
