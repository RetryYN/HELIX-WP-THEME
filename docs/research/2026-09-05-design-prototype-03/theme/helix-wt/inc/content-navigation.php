<?php
/** 共通ヘッダーのナビ参照。本文・footerのナビには適用しない。 */
defined( 'ABSPATH' ) || exit;
/** 正規ヘッダー部品だけを共有対象とし、名前やクラスの複製は根拠にしない。 */
function helix_wt_shared_header_parts() {
	return array( 'header', 'header-nav', 'header-cta', 'header-announce', 'header-center', 'header-two-rows', 'header-overlay', 'header-tel', 'header-band' );
}
function wtcf_shared_navigation_ref() {
	$ref = get_theme_mod( 'wt_content_navigation_ref', 0 );
	if ( ! is_scalar( $ref ) || ! ctype_digit( (string) $ref ) ) {
		return 0; }
	$post = get_post( (int) $ref );
	return $post && 'wp_navigation' === $post->post_type && 'publish' === $post->post_status ? $post->ID : 0;
}
add_filter(
	'pre_render_block',
	function ( $pre, $block ) {
		if ( null !== $pre ) {
			return $pre; }
		static $header_depth       = 0;
		static $part_binding       = false;
		static $navigation_binding = false;
		$name                      = $block['blockName'];
		if ( 'core/template-part' === $name && in_array( $block['attrs']['slug'] ?? '', helix_wt_shared_header_parts(), true ) && ! $part_binding ) {
			$part_binding = true;
			++$header_depth;
			try {
				return render_block( $block );
			} finally {
				--$header_depth;
				$part_binding = false; }
		}
		if ( 'core/navigation' !== $name || $navigation_binding || ! $header_depth ) {
			return $pre; }
		$ref = wtcf_shared_navigation_ref();
		if ( ! $ref ) {
			return ''; }
		$post = get_post( $ref );
		if ( '' === trim( $post->post_content ) ) {
			return ''; }
		$block['attrs']['ref'] = $ref;
		$block['innerBlocks']  = array();
		$block['innerHTML']    = '';
		$block['innerContent'] = array();
		$navigation_binding    = true;
		try {
			return render_block( $block );
		} finally {
			$navigation_binding = false; }
	},
	10,
	2
);
require_once __DIR__ . '/header-navigation-settings.php';
