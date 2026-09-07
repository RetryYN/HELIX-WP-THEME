<?php
// Create the lab's initial navigation without replacing later editorial choices.
if ( ! defined( 'WP_CLI' ) || ! WP_CLI || get_option( 'blogname' ) !== 'HELIX Content Lab' ) { throw new RuntimeException( 'Dedicated lab required' ); }
if ( false !== get_theme_mod( 'wt_content_navigation_ref', false ) ) { return; }
$navigation = get_page_by_path( 'helix-content-navigation', OBJECT, 'wp_navigation' );
if ( ! $navigation ) {
	$links = array( '記事を読む' => '/library/', '人を知る' => '/voices/', '学習・ヘルプ' => '/learn/', '会社案内' => '/site-company/' );
	$content = '';
	foreach ( $links as $label => $route ) {
		$content .= '<!-- wp:navigation-link ' . wp_json_encode( array( 'label' => $label, 'url' => home_url( $route ), 'kind' => 'custom' ) ) . ' /-->' . "\n";
	}
	$id = wp_insert_post( array( 'post_type' => 'wp_navigation', 'post_status' => 'publish', 'post_name' => 'helix-content-navigation', 'post_title' => '共通ナビゲーション', 'post_content' => $content ), true );
	if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
	$navigation = get_post( $id );
}
if ( 'publish' === $navigation->post_status ) { set_theme_mod( 'wt_content_navigation_ref', $navigation->ID ); }
