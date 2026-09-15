<?php
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || get_option( 'blogname' ) !== 'HELIX Content Lab' ) { throw new RuntimeException( 'Dedicated lab required' ); }
$site_ids = array();
foreach ( wtcf_site_manifest()['pages'] as $key => $page ) {
	$old = get_page_by_path( $page['slug'] );
	$id = wp_insert_post( array( 'ID' => $old ? $old->ID : 0, 'post_type' => 'page', 'post_name' => $page['slug'], 'post_title' => $page['title'], 'post_status' => 'publish',
		'post_content' => '<!-- wp:helix-wt/site-page ' . wp_json_encode( array( 'pageKey' => $key ) ) . ' /-->', 'meta_input' => array( '_wp_page_template' => 'page-site-guide' ) ), true );
	if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
	$site_ids[ $key ] = $id;
}
update_option( 'wtcf_site_page_ids', $site_ids, false );
