<?php
/**
 * Single received banner catalog and scoped zone assignments. No tracking or optimization.
 *
 * @package HelixWT
 */
defined( 'ABSPATH' ) || exit;

function wt_banner_slots() {
	return array( 'header-below', 'header-inner', 'before-content', 'after-content', 'before-related', 'after-related', 'sidebar', 'sticky-sidebar', 'list', '404', 'footer', 'page-top', 'page-bottom', 'sp-bottom' );
}

/** Resolve one banner from its single source; product creative fields are never copied. */
function wt_banner_record( $catalog, $id, $now = null ) {
	if ( ! is_array( $catalog ) || ! is_string( $id ) || ! is_array( $catalog['banners'] ?? null ) ) {
		return null; }
	$banner = $catalog['banners'][ $id ] ?? null;
	if ( ! is_array( $banner ) || ! in_array( $banner['type'] ?? '', array( 'own', 'affiliate', 'advertisement', 'product' ), true ) || ! is_bool( $banner['pr'] ?? null ) ) {
		return null;
	}
	foreach ( array( 'starts_at', 'ends_at' ) as $key ) {
		if ( ! is_string( $banner[ $key ] ?? null ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/D', $banner[ $key ] ) ) {
			return null;
		}
		$date = DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i:s\Z', $banner[ $key ], new DateTimeZone( 'UTC' ) );
		if ( ! $date || $date->format( 'Y-m-d\TH:i:s\Z' ) !== $banner[ $key ] ) {
			return null;
		}
		$times[ $key ] = $date->getTimestamp();
	}
	$now = null === $now ? time() : $now;
	if ( $times['starts_at'] >= $times['ends_at'] || $now < $times['starts_at'] || $now >= $times['ends_at'] ) {
		return null;
	}
	$creative = $banner;
	if ( 'product' === $banner['type'] ) {
		if ( ! is_string( $banner['product_id'] ?? null ) || ! is_array( $catalog['products'] ?? null ) || ! isset( $catalog['products'][ $banner['product_id'] ] ) ) {
			return null;
		}
		$creative = $catalog['products'][ $banner['product_id'] ];
		if ( ! is_array( $creative ) ) {
			return null; }
		foreach ( array( 'title', 'url', 'alt', 'pc', 'sp' ) as $field ) {
			if ( isset( $banner[ $field ] ) ) {
				return null; // A second creative source would drift from the product.
			}
		}
	}
	foreach ( array( 'title', 'alt', 'url' ) as $field ) {
		if ( ! is_string( $creative[ $field ] ?? null ) || '' === trim( $creative[ $field ] ) ) {
			return null;
		}
	}
	if ( ! wp_http_validate_url( $creative['url'] ) && ! ( str_starts_with( $creative['url'], '/' ) && ! str_starts_with( $creative['url'], '//' ) ) ) {
		return null;
	}
	$budget = $catalog['image_budget_bytes'] ?? 0;
	if ( ! is_int( $budget ) || $budget < 1 ) {
		return null;
	}
	foreach ( array( 'pc', 'sp' ) as $device ) {
		$attachment = $creative[ $device ] ?? null;
		if ( ! is_int( $attachment ) || ! wp_attachment_is_image( $attachment ) ) {
			return null;
		}
		$file = get_attached_file( $attachment );
		if ( ! $file || ! is_file( $file ) || filesize( $file ) > $budget ) {
			return null;
		}
		$image = wp_get_attachment_image_src( $attachment, 'full' );
		if ( ! $image || $image[1] < 1 || $image[2] < 1 ) {
			return null;
		}
		$images[ $device ] = array(
			'url'    => $image[0],
			'width'  => $image[1],
			'height' => $image[2],
			'bytes'  => filesize( $file ),
		);
	}
	return array(
		'id'     => $id,
		'type'   => $banner['type'],
		'pr'     => $banner['pr'] || in_array( $banner['type'], array( 'affiliate', 'advertisement' ), true ),
		'title'  => $creative['title'],
		'url'    => $creative['url'],
		'alt'    => $creative['alt'],
		'images' => $images,
	);
}

function wt_banner_catalog() {
	$value = get_option( 'helix_wt_banner_catalog', array() );
	return is_array( $value ) && 'wt-banner-catalog.v1' === ( $value['schema'] ?? '' ) ? $value : array();
}

/** Resolve page/category/post scope before choosing a valid creative. */
function wt_banner_assignment( $catalog, $slot, $context, $now = null ) {
	if ( ! in_array( $slot, wt_banner_slots(), true ) ) {
		return null;
	}
	if ( ! is_array( $catalog['assignments'] ?? null ) ) {
		return null; }
	foreach ( $catalog['assignments'] as $assignment ) {
		if ( ! is_array( $assignment ) || ( $assignment['slot'] ?? '' ) !== $slot || ! in_array( $assignment['mode'] ?? '', array( 'fixed', 'rotate' ), true ) ) {
			continue;
		}
		$scope = $assignment['scope'] ?? array();
		if ( ! is_array( $scope ) || ! is_array( $assignment['banner_ids'] ?? null ) ) {
			continue; }
		foreach ( array( 'faces', 'post_ids', 'category_ids' ) as $field ) {
			if ( isset( $scope[ $field ] ) && ! is_array( $scope[ $field ] ) ) {
				continue 2; }
		}
		if ( ! empty( $scope['faces'] ) && ! in_array( $context['face'] ?? '', $scope['faces'], true ) ) {
			continue;
		}
		if ( ! empty( $scope['post_ids'] ) && ! in_array( $context['post_id'] ?? 0, $scope['post_ids'], true ) ) {
			continue;
		}
		if ( ! empty( $scope['category_ids'] ) && ! array_intersect( $context['category_ids'] ?? array(), $scope['category_ids'] ) ) {
			continue;
		}
		$cap = $assignment['max_viewport_area_ratio'] ?? null;
		if ( ! is_numeric( $cap ) || $cap <= 0 || $cap > 1 ) {
			continue;
		}
		$records = array();
		foreach ( $assignment['banner_ids'] ?? array() as $id ) {
			if ( is_string( $id ) ) {
				$record = wt_banner_record( $catalog, $id, $now );
				if ( $record ) {
					$records[] = $record;
				}
			}
		}
		if ( ! $records ) {
			continue;
		}
		$index = 0;
		if ( 'rotate' === $assignment['mode'] ) {
			$seconds = $assignment['rotation_seconds'] ?? 0;
			if ( ! is_int( $seconds ) || $seconds < 60 ) {
				continue;
			}
			$index = intdiv( null === $now ? time() : $now, $seconds ) % count( $records );
		}
		return array(
			'record'     => $records[ $index ],
			'assignment' => $assignment,
		);
	}
	return null;
}

function wt_banner_context() {
	return array(
		'face'         => is_404() ? '404' : ( is_category() ? 'category' : ( function_exists( 'wt_side_face' ) ? wt_side_face() : 'page' ) ),
		'post_id'      => get_queried_object_id(),
		'category_ids' => is_category() ? array( get_queried_object_id() ) : wp_get_post_categories( get_queried_object_id() ),
	);
}

function wt_banner_zone( $slot ) {
	$resolved = wt_banner_assignment( wt_banner_catalog(), $slot, wt_banner_context() );
	if ( ! $resolved ) {
		return '';
	}
	$r      = $resolved['record'];
	$a      = $resolved['assignment'];
	$notice = 'header-below' === $slot;
	$rel    = $r['pr'] ? ' sponsored nofollow' : '';
	$out    = '<aside class="wt-banner-zone wt-banner-zone--' . esc_attr( $slot ) . '" data-wt-banner-id="' . esc_attr( $r['id'] ) . '" data-wt-banner-slot="' . esc_attr( $slot ) . '" data-wt-area-cap="' . esc_attr( $a['max_viewport_area_ratio'] ) . '" aria-label="' . esc_attr__( 'お知らせ・ご案内', 'helix-wt' ) . '">';
	if ( $r['pr'] ) {
		$out .= '<span class="wt-banner-zone__pr">' . esc_html__( '広告・PR', 'helix-wt' ) . '</span>';
	}
	$out .= '<a class="wt-banner-zone__link" href="' . esc_url( $r['url'] ) . '" rel="' . esc_attr( trim( $rel ) ) . '">';
	if ( ! $notice ) {
		$pc   = $r['images']['pc'];
		$sp   = $r['images']['sp'];
		$out .= '<picture><source media="(max-width: 599px)" srcset="' . esc_url( $sp['url'] ) . '" width="' . esc_attr( $sp['width'] ) . '" height="' . esc_attr( $sp['height'] ) . '"><img src="' . esc_url( $pc['url'] ) . '" alt="' . esc_attr( $r['alt'] ) . '" width="' . esc_attr( $pc['width'] ) . '" height="' . esc_attr( $pc['height'] ) . '" loading="lazy" decoding="async"></picture>';
	}
	$out .= '<span>' . esc_html( $r['title'] ) . '</span><span aria-hidden="true"> ↗</span></a>';
	if ( $notice ) {
		$out .= '<button type="button" class="wt-banner-zone__close" data-wt-banner-close aria-label="' . esc_attr__( 'このお知らせを閉じる', 'helix-wt' ) . '" hidden>×</button>';
	}
	return $out . '</aside>';
}

function wt_banner_insert_before_close( $html, $tag, $addition ) {
	$position = strripos( $html, '</' . $tag . '>' );
	return false === $position ? $html : substr( $html, 0, $position ) . $addition . substr( $html, $position );
}

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! wt_banner_catalog() ) {
			return; }
		wp_enqueue_style( 'wt-banner-zones', get_theme_file_uri( 'assets/css/banner-zones.css' ), array( 'helix-wt' ), '0.3.24' );
		wp_enqueue_script( 'wt-banner-zones', get_theme_file_uri( 'assets/js/banner-zones.js' ), array(), '0.3.24', array( 'strategy' => 'defer' ) );
	}
);

add_filter(
	'render_block',
	function ( $html, $block ) {
		if ( is_admin() || ! wt_banner_catalog() ) {
			return $html; }
		$name  = $block['blockName'] ?? '';
		$attrs = $block['attrs'] ?? array();
		if ( 'core/template-part' === $name && 'header' === ( $attrs['tagName'] ?? '' ) && ! wt_is_lp_page() ) {
			return wt_banner_insert_before_close( $html, 'header', wt_banner_zone( 'header-inner' ) ) . wt_banner_zone( 'header-below' );
		}
		if ( 'core/group' === $name && 'header' === ( $attrs['tagName'] ?? '' ) && wt_is_lp_page() && in_array( 'wt-lp-header--' . wt_opt( 'lp_header' ), explode( ' ', $attrs['className'] ?? '' ), true ) ) {
			return wt_banner_insert_before_close( $html, 'header', wt_banner_zone( 'header-inner' ) ) . wt_banner_zone( 'header-below' );
		}
		if ( 'core/template-part' === $name && 'footer' === ( $attrs['tagName'] ?? '' ) ) {
			return wt_banner_insert_before_close( $html, 'footer', wt_banner_zone( 'footer' ) . wt_banner_zone( 'page-top' ) ) . wt_banner_zone( 'page-bottom' );
		}
		if ( 'helix-wt/sidebar' === $name ) {
			return preg_replace( '/<\/aside>/i', wt_banner_zone( 'sidebar' ) . wt_banner_zone( 'sticky-sidebar' ) . '</aside>', $html, 1 ); }
		if ( 'core/group' === $name && 'main' === ( $attrs['tagName'] ?? '' ) ) {
			return wt_banner_zone( 'before-content' ) . ( is_404() ? wt_banner_zone( '404' ) : '' ) . $html . wt_banner_zone( 'after-content' ) . ( wp_is_mobile() ? wt_banner_zone( 'sp-bottom' ) : '' );
		}
		if ( 'core/group' === $name && in_array( 'wt-tail__slot--related', explode( ' ', $attrs['className'] ?? '' ), true ) ) {
			return wt_banner_zone( 'before-related' ) . $html . wt_banner_zone( 'after-related' ); }
		if ( 'core/query' === $name && ! empty( $attrs['query']['inherit'] ) && ( is_category() || is_archive() || is_home() ) ) {
			return $html . wt_banner_zone( 'list' ); }
		return $html;
	},
	25,
	2
);

/** Render received utility destinations; consent decisions stay with the consent provider. */
add_action(
	'wp_footer',
	function () {
		$catalog = wt_banner_catalog();
		$stack   = $catalog['stack'] ?? array();
		if ( ! $catalog || ! is_array( $stack ) ) {
			return; }
		$out = '';
		foreach ( array( 'consent', 'menu', 'share' ) as $kind ) {
			$item = $stack[ $kind ] ?? null;
			if ( ! is_array( $item ) || ! is_string( $item['text'] ?? null ) || ! is_string( $item['url'] ?? null ) || '' === trim( $item['text'] ) || '' === esc_url( $item['url'] ) ) {
				continue; }
			$out .= '<div class="wt-banner-stack__' . esc_attr( $kind ) . '" data-wt-stack-layer="' . esc_attr( $kind ) . '"><a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['text'] ) . '</a></div>';
		}
		if ( $out ) {
			echo '<div class="wt-banner-stack" aria-label="' . esc_attr__( 'ページの補助操作', 'helix-wt' ) . '">' . $out . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Each value escaped above.
		}
		// Only an explicitly configured LP may open an initial promotion dialog.
		if ( is_page() && is_page_template( array( 'page-lp', 'page-lp.html' ) ) && ! is_front_page() ) {
			$id     = get_post_meta( get_queried_object_id(), '_wt_banner_initial_modal', true );
			$banner = is_string( $id ) ? wt_banner_record( $catalog, $id ) : null;
			if ( $banner ) {
				echo '<dialog class="wt-banner-modal" data-wt-initial-modal aria-labelledby="wt-banner-modal-title"><h2 id="wt-banner-modal-title">' . esc_html( $banner['title'] ) . '</h2><a href="' . esc_url( $banner['url'] ) . '">' . esc_html( $banner['alt'] ) . '</a><form method="dialog"><button>' . esc_html__( '閉じる', 'helix-wt' ) . '</button></form></dialog>';
			}
		}
	},
	50
);
