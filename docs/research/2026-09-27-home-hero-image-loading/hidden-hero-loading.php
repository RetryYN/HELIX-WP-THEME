<?php
/**
 * #352 の隔離 WordPress ラボ専用 PoC。本体への組込み前の読み込み属性実験。
 * 選択軸・DOM・CSS・画像は保持し、非選択の HOME hero 画像だけ遅延読込にする。
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'render_block_core/html', function ( $html ) {
	if ( ! str_contains( $html, 'wt-home-hero-slot__html' ) || ! function_exists( 'wt_opt' ) ) {
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
} );
