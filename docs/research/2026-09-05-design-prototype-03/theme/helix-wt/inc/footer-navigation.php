<?php
/** Footer navigation keeps empty and unavailable menus empty. */
defined( 'ABSPATH' ) || exit;

function wt_footer_navigation( $ref, $label ) {
	if ( ! ( is_int( $ref ) || is_string( $ref ) ) || ! ctype_digit( (string) $ref ) || (int) $ref < 1 ) {
		return '';
	}
	$post = get_post( (int) $ref );
	if ( ! $post || 'wp_navigation' !== $post->post_type || 'publish' !== $post->post_status || '' === trim( $post->post_content ) ) {
		return '';
	}
	if ( ! is_string( $label ) || '' === trim( $label ) ) {
		return '';
	}
	// Whitespace-only labels can otherwise leave an unnamed link in core output.
	$omit_empty_label = static function ( $html, $block ) {
		$name = $block['attrs']['label'] ?? '';
		return is_string( $name ) && '' !== trim( wp_strip_all_tags( html_entity_decode( $name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) ) ? $html : '';
	};
	add_filter( 'render_block_core/navigation-link', $omit_empty_label, 10, 2 );
	try {
		$html = do_blocks( '<!-- wp:navigation ' . wp_json_encode( array( 'ref' => $post->ID, 'overlayMenu' => 'never', 'ariaLabel' => $label ) ) . ' /-->' );
	} finally {
		remove_filter( 'render_block_core/navigation-link', $omit_empty_label, 10 );
	}
	$tags = new WP_HTML_Tag_Processor( $html );
	$has_link = false;
	while ( $tags->next_tag( 'A' ) ) {
		$href = $tags->get_attribute( 'href' );
		if ( is_string( $href ) && '' !== trim( $href ) ) {
			$has_link = true;
			break;
		}
	}
	return $has_link ? $html : '';
}


// Standard blocks stay editable in Site Editor; only the addressed footer blocks are bound.
add_filter( 'pre_render_block', function ( $pre, $block ) {
	$classes = explode( ' ', $block['attrs']['className'] ?? '' );
	if ( null !== $pre || 'core/navigation' !== $block['blockName'] || ! in_array( 'wt-footer-data-navigation', $classes, true ) ) {
		return $pre;
	}
	return wt_footer_navigation( $block['attrs']['ref'] ?? 0, $block['attrs']['ariaLabel'] ?? 'フッター案内' );
}, 10, 2 );

add_filter( 'render_block', function ( $html, $block ) {
	$classes = explode( ' ', $block['attrs']['className'] ?? '' );
	if ( ! in_array( 'wt-footer-navigation-group', $classes, true ) ) {
		return $html;
	}
	if ( in_array( 'wt-footer-extra-slot--sites', $classes, true ) && ! in_array( 'sites', explode( '-', wt_chrome_eff( 'footer_extra' ) ), true ) && 'all' !== wt_chrome_eff( 'footer_extra' ) ) {
		return '';
	}
	$tags = new WP_HTML_Tag_Processor( $html );
	return $tags->next_tag( 'A' ) ? $html : '';
}, 10, 2 );
