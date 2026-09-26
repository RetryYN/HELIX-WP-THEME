<?php
/** HOME hero の非選択案の画像を、表示するまで読み込まない。 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wt_defer_inactive_home_hero_images( $html ) {
	if ( ! str_contains( $html, 'wt-home-hero-slot__html' ) ) {
		return $html;
	}
	$selected = wt_opt( 'home_hero' );
	$tags     = new WP_HTML_Tag_Processor( $html );
	$sections = array();
	while ( $tags->next_tag( array( 'tag_closers' => 'visit' ) ) ) {
		if ( 'SECTION' === $tags->get_tag() ) {
			if ( $tags->is_tag_closer() ) {
				array_pop( $sections );
				continue;
			}
			$hidden = ! empty( $sections ) && end( $sections );
			if ( $tags->has_class( 'wt-home-hero' ) ) {
				$hidden = $hidden || ! $tags->has_class( 'wt-home-hero--' . $selected );
			}
			$sections[] = $hidden;
		}
		if ( 'IMG' === $tags->get_tag() && ! empty( $sections ) && end( $sections ) ) {
			$tags->set_attribute( 'loading', 'lazy' );
			$tags->remove_attribute( 'fetchpriority' );
		}
	}
	return $tags->get_updated_html();
}

add_filter( 'render_block_core/html', 'wt_defer_inactive_home_hero_images' );
