<?php
/**
 * Plugin Name: HELIX Content Faces PoC
 * Description: Local-only independent content records and entitlement fixtures. Not a production payment system.
 */
defined( 'ABSPATH' ) || exit;

function wtcf_manifest() {
	static $manifest;
	if ( ! $manifest ) { $manifest = json_decode( file_get_contents( __DIR__ . '/manifest.json' ), true ); }
	return $manifest;
}
add_action( 'init', function () {
	foreach ( wtcf_manifest()['types'] as $type => $definition ) {
		register_post_type( $type, array( 'label' => $definition['label'], 'public' => true, 'show_in_rest' => true,
			'has_archive' => true, 'rewrite' => array( 'slug' => $definition['slug'] ),
			'supports' => array( 'title', 'editor', 'excerpt', 'revisions' ) ) );
	}
} );

// The protected document is never placed in public post_content/excerpt or REST metadata.
// This local fixture replaces an external content/entitlement adapter, not a payment provider.
function wtcf_document( $post_id ) {
	$data = get_post_meta( $post_id, '_wtcf_document', true );
	return is_array( $data ) ? $data : array();
}
function wtcf_access( $post_id ) {
	$doc = wtcf_document( $post_id );
	$user = get_current_user_id();
	if ( ! $user ) { return false; }
	$grant = get_user_meta( $user, '_wtcf_entitlement', true );
	if ( ! is_array( $grant ) ) { return false; }
	if ( ( $doc['billing'] ?? '' ) === 'subscription' ) {
		return ( $grant['state'] ?? '' ) === 'subscription' && ( $grant['expires'] ?? 0 ) > time();
	}
	return ( $grant['state'] ?? '' ) === 'oneoff' && in_array( $post_id, $grant['posts'] ?? array(), true );
}

// Only a sanitized display projection crosses from the fixture adapter to the theme.
function wtcf_display( $post_id ) {
	$post = get_post( $post_id );
	if ( ! $post || ! isset( wtcf_manifest()['types'][ $post->post_type ] ) ) { return null; }
	$doc = wtcf_document( $post_id );
	$display = array( 'id' => $post_id, 'type' => $post->post_type, 'title' => get_the_title( $post_id ),
		'url' => get_permalink( $post_id ), 'summary' => $post->post_excerpt, 'content' => $post->post_content );
	if ( $post->post_type === 'wt_paid' ) {
		$display['billing'] = $doc['billing'] ?? 'oneoff';
		$display['price'] = $doc['price'] ?? '';
		$display['chapters'] = $doc['chapters'] ?? array();
		$display['granted'] = wtcf_access( $post_id );
		$display['body'] = $display['granted'] ? ( $doc['body'] ?? '' ) : '';
	} elseif ( $post->post_type === 'wt_interview' ) {
		$display['people'] = $doc['people'] ?? array();
		$display['exchanges'] = $doc['exchanges'] ?? array();
		$display['confirmed'] = ( $doc['confirmed'] ?? false ) === true;
	} else {
		$display['sections'] = $doc['sections'] ?? array();
		$target = get_page_by_path( $doc['target'] ?? '', OBJECT, 'wt_lp' );
		$display['target'] = $target && $target->post_status === 'publish' ? get_permalink( $target ) : '';
	}
	return $display;
}

add_action( 'template_redirect', function () {
	if ( is_singular( 'wt_paid' ) ) { nocache_headers(); }
} );

// Publication confirmation is data governance in the plugin; an unconfirmed interview cannot be published.
add_filter( 'wp_insert_post_data', function ( $data, $postarr ) {
	if ( $data['post_type'] === 'wt_interview' && $data['post_status'] === 'publish' ) {
		$doc = $postarr['meta_input']['_wtcf_document'] ?? wtcf_document( $postarr['ID'] ?? 0 );
		if ( ( $doc['confirmed'] ?? false ) !== true ) { $data['post_status'] = 'draft'; }
	}
	return $data;
}, 10, 2 );

add_shortcode( 'wtcf_interview_card', function ( $attributes ) {
	$slug = sanitize_title( $attributes['slug'] ?? '' );
	$post = get_page_by_path( $slug, OBJECT, 'wt_interview' );
	if ( ! $post || $post->post_status !== 'publish' ) { return ''; }
	$data = wtcf_display( $post->ID );
	if ( ! $data['confirmed'] ) { return ''; }
	return '<aside class="wtcf-reference"><p>VOICE / 制作の現場から</p><h2><a href="' . esc_url( $data['url'] ) . '">' . esc_html( $data['title'] ) . '</a></h2><p>' . esc_html( $data['summary'] ) . '</p></aside>';
} );
