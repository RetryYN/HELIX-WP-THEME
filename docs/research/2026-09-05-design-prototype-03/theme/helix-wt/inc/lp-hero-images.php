<?php
/** LP ヒーローの非選択案にある画像を、表示するまで読み込まない。 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wt_defer_inactive_lp_hero_images( $html ) {
	if ( ! is_string( $html ) || ! str_contains( $html, 'wt-lp-hero-slot' ) || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
		return $html;
	}
	if ( ! function_exists( 'wt_opt' ) || ! function_exists( 'wt_axes' ) ) {
		return $html;
	}
	$selected = wt_opt( 'lp_hero' );
	$allowed  = wt_axes()['lp_hero'][1] ?? array();
	if ( ! in_array( $selected, $allowed, true ) ) {
		return $html;
	}

	$tags          = new WP_HTML_Tag_Processor( $html );
	$elements      = array();
	$slot_count    = 0;
	$hero_count    = 0;
	$selected_count = 0;
	$safe          = true;
	while ( $tags->next_tag( array( 'tag_closers' => 'visit' ) ) ) {
		$tag = $tags->get_tag();
		if ( in_array( $tag, array( 'DIV', 'SECTION' ), true ) ) {
			if ( $tags->is_tag_closer() ) {
				if ( empty( $elements ) || $tag !== end( $elements )['tag'] ) {
					$safe = false;
					continue;
				}
				array_pop( $elements );
				continue;
			}

			$parent  = empty( $elements ) ? array( 'in_slot' => false, 'inactive_hero' => false ) : end( $elements );
			$in_slot = $parent['in_slot'];
			$inactive_hero = $parent['inactive_hero'];
			if ( 'DIV' === $tag ) {
				$is_slot = $tags->has_class( 'wt-lp-hero-slot' );
				if ( $is_slot ) {
					++$slot_count;
					$in_slot = true;
				}
			} elseif ( $in_slot && $tags->has_class( 'wt-lp-hero' ) ) {
				$classes = preg_split( '/\s+/', trim( (string) $tags->get_attribute( 'class' ) ) );
				$variants = array_values( array_filter( $classes, static function ( $class ) {
					return str_starts_with( $class, 'wt-lp-hero--' );
				} ) );
				$variant = 1 === count( $variants ) ? substr( $variants[0], strlen( 'wt-lp-hero--' ) ) : '';
				if ( ! in_array( $variant, $allowed, true ) ) {
					$safe = false;
				} else {
					++$hero_count;
					if ( $variant === $selected ) {
						++$selected_count;
					} else {
						$inactive_hero = true;
					}
				}
			}
			$elements[] = array( 'tag' => $tag, 'in_slot' => $in_slot, 'inactive_hero' => $inactive_hero );
			continue;
		}

		if ( 'IMG' === $tag && $safe && ! empty( $elements ) && end( $elements )['inactive_hero'] ) {
			$tags->set_attribute( 'loading', 'lazy' );
			$tags->remove_attribute( 'fetchpriority' );
		}
	}

	if ( ! empty( $elements ) || 1 !== $slot_count || 0 === $hero_count || 0 === $selected_count || ! $safe ) {
		return $html;
	}
	return $tags->get_updated_html();
}

add_filter( 'render_block_core/html', 'wt_defer_inactive_lp_hero_images' );
