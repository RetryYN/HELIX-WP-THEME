<?php
/** 空の専用 Compose ラボで使用する、合成 HOME fixture。 */
declare(strict_types=1);

$base = getenv( 'HELIX_PERF_BASE_URL' );
if ( ! $base || ! in_array( parse_url( $base, PHP_URL_HOST ), array( 'localhost', '127.0.0.1' ), true ) ) {
	throw new RuntimeException( 'Loopback HELIX_PERF_BASE_URL is required' );
}
$_SERVER['HTTP_HOST']       = (string) parse_url( $base, PHP_URL_HOST );
$_SERVER['REQUEST_URI']     = '/';
$_SERVER['REQUEST_METHOD']  = 'GET';
$_SERVER['SERVER_PROTOCOL'] = 'HTTP/1.1';
define( 'WP_INSTALLING', true );
require '/var/www/html/wp-load.php';
add_filter( 'pre_wp_mail', static function () { return true; } );
if ( ! is_blog_installed() ) {
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	wp_install( 'Theme compatibility fixture', 'local-review', 'local@example.invalid', 0, '', bin2hex( random_bytes( 24 ) ) );
}
update_option( 'home', rtrim( $base, '/' ) );
update_option( 'siteurl', rtrim( $base, '/' ) );
update_option( 'blog_public', 0 );
switch_theme( 'helix-wt' );
$page = get_page_by_path( 'perf352-home', OBJECT, 'page' );
$id = $page ? $page->ID : wp_insert_post( array(
	'post_type' => 'page', 'post_status' => 'publish', 'post_name' => 'perf352-home',
	'post_title' => 'Performance fixture home', 'post_content' => '<p>Synthetic HOME fixture.</p>',
), true );
if ( is_wp_error( $id ) ) { throw new RuntimeException( $id->get_error_message() ); }
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $id );
set_theme_mod( 'wt_home_hero', 'text-only' );
echo "HOME fixture ready\n";
