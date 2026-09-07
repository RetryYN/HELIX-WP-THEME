<?php
/** Existing shared-part resolution is also available to independent content faces. */
defined( 'ABSPATH' ) || exit;
function wtcf_chrome_faces() {
	static $faces = null;
	if ( null === $faces ) { $faces = json_decode( file_get_contents( get_theme_file_path( 'config/content-chrome.json' ) ), true ); }
	return is_array( $faces ) ? $faces : array();
}
function wtcf_chrome_face() {
	foreach ( wtcf_chrome_faces() as $face => $rule ) {
		if ( ! is_singular( $rule['post_type'] ) && ! is_post_type_archive( $rule['post_type'] ) ) { continue; }
		if ( isset( $rule['block'] ) && ! has_block( $rule['block'], get_queried_object() ) ) { continue; }
		return $face;
	}
	return null;
}
function wtcf_shared_chrome() { return null !== wtcf_chrome_face() && 'shared' === wt_opt( 'content_chrome' ); }
add_action( 'wp_enqueue_scripts', function () {
	if ( wtcf_shared_chrome() ) { wp_enqueue_style( 'wt-content-chrome', get_theme_file_uri( 'assets/css/content-chrome.css' ), array( 'helix-wt' ), filemtime( get_theme_file_path( 'assets/css/content-chrome.css' ) ) ); }
} );
function wtcf_shared_part( $slug ) {
	if ( ! wtcf_shared_chrome() ) { return ''; }
	$previous = $GLOBALS['wtcf_shared_part_context'] ?? null;
	$GLOBALS['wtcf_shared_part_context'] = $slug;
	try {
		return do_blocks( '<!-- wp:template-part ' . wp_json_encode( array( 'slug' => $slug, 'tagName' => $slug ) ) . ' /-->' );
	} finally { $GLOBALS['wtcf_shared_part_context'] = $previous; }
}
function wtcf_shared_layout_start() { return wtcf_shared_chrome() ? '<div class="wt-side-layout"><div class="wt-side-main">' : ''; }
function wtcf_shared_layout_end() {
	if ( ! wtcf_shared_chrome() ) { return ''; }
	$sidebar = 'none' === wt_side_eff( 'layout' ) ? '' : wt_render_sidebar();
	$nav = 'none' === wt_side_eff( 'nav' ) ? '' : wt_render_side_nav();
	return '</div>' . $sidebar . '</div>' . $nav;
}
require_once __DIR__ . '/content-navigation.php';
