<?php
/**
 * wp eval-file 用: style variation を user global styles (wp_global_styles 投稿) へ書き込む。
 * 使い方: wp eval-file set-variation.php <slug|reset>
 * slug は 子テーマ styles/<slug>.json → 親テーマ styles/<slug>.json の順に探す。
 * reset は user global styles を空 ({}) に戻す。
 */
$slug = isset( $args[0] ) ? $args[0] : '';
if ( '' === $slug ) {
	WP_CLI::error( 'slug required' );
}
$post_id = WP_Theme_JSON_Resolver::get_user_global_styles_post_id();
// wp-cli を --user なしで動かすと tax_input が落ちて wp_theme term が付かないため補正する。
wp_set_object_terms( $post_id, get_stylesheet(), 'wp_theme', false );
if ( 'reset' === $slug ) {
	$content = wp_json_encode( array( 'version' => WP_Theme_JSON::LATEST_SCHEMA, 'isGlobalStylesUserThemeJSON' => true ) );
} else {
	$candidates = array(
		get_stylesheet_directory() . '/styles/' . $slug . '.json',
		get_template_directory() . '/styles/' . $slug . '.json',
	);
	$file = null;
	foreach ( $candidates as $c ) {
		if ( file_exists( $c ) ) { $file = $c; break; }
	}
	if ( ! $file ) {
		WP_CLI::error( "variation not found: $slug" );
	}
	$data = json_decode( file_get_contents( $file ), true );
	if ( ! is_array( $data ) ) {
		WP_CLI::error( "invalid json: $file" );
	}
	unset( $data['$schema'], $data['title'], $data['description'] );
	$data['version'] = WP_Theme_JSON::LATEST_SCHEMA;
	$data['isGlobalStylesUserThemeJSON'] = true;
	$content = wp_json_encode( $data );
}
$r = wp_update_post( array( 'ID' => $post_id, 'post_content' => wp_slash( $content ) ), true );
if ( is_wp_error( $r ) ) {
	WP_CLI::error( $r->get_error_message() );
}
WP_Theme_JSON_Resolver::clean_cached_data();
wp_cache_flush();
$check = json_decode( get_post( $post_id )->post_content, true );
$pal = array();
if ( isset( $check['settings']['color']['palette'] ) ) {
	foreach ( $check['settings']['color']['palette'] as $p ) { $pal[ $p['slug'] ] = $p['color']; }
}
WP_CLI::line( wp_json_encode( array( 'post_id' => $post_id, 'slug' => $slug, 'palette' => $pal ) ) );
