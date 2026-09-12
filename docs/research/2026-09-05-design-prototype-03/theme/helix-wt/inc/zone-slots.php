<?php
/**
 * ZONE-01 再現 PoC: 共通宣言を端末差分で置換し、空の面はサーバーで省略する。
 *
 * @package HelixWT
 */

defined( 'ABSPATH' ) || exit;

/**
 * 選択された端末向けのカードだけを描画する。
 *
 * @param array $attributes ブロック属性.
 * @return string 描画する HTML。空の slot は空文字。
 */
function wt_zone_render( $attributes ) {
	$ids = array( 'before-content', 'before-related', 'after-related', 'page-top', 'page-bottom', 'header-inner', 'sp-bottom', 'sticky-sidebar' );
	$id  = $attributes['slot'] ?? '';
	if ( ! in_array( $id, $ids, true ) ) {
		return '';
	}
	$device = wp_is_mobile() ? 'sp' : 'pc';
	$cards  = $attributes['common'] ?? array();
	// null は継承、空配列は明示的な非表示。重い面の非選択側は HTML に入れない.
	if ( isset( $attributes[ $device ] ) ) {
		$cards = $attributes[ $device ];
	}
	if ( 'sp-bottom' === $id && 'sp' !== $device ) {
		return '';
	}
	$body = '';
	foreach ( is_array( $cards ) ? $cards : array() as $card ) {
		if ( ! is_array( $card ) || empty( $card['title'] ) || ! is_string( $card['title'] ) ) {
			continue;
		}
		$title = trim( wp_strip_all_tags( $card['title'] ) );
		if ( '' === $title ) {
			continue;
		}
		$body .= '<article class="wt-zone__card"><p class="wt-zone__eyebrow">' . esc_html( $card['label'] ?? __( 'READ NEXT', 'helix-wt' ) ) . '</p><h2>' . esc_html( $title ) . '</h2><p>' . esc_html( $card['text'] ?? '' ) . '</p>';
		$url   = isset( $card['url'] ) && is_string( $card['url'] ) ? esc_url( $card['url'] ) : '';
		if ( '' !== $url ) {
			$body .= '<a class="wt-zone__link" href="' . $url . '">' . esc_html( $card['action'] ?? __( '詳しく見る', 'helix-wt' ) ) . '<span aria-hidden="true"> ↗</span></a>';
		}
		$body .= '</article>';
	}
	if ( '' === $body ) {
		return '';
	}
	return '<div class="wt-zone wt-zone--' . esc_attr( $id ) . '" data-wt-zone="' . esc_attr( $id ) . '" data-wt-zone-device="' . $device . '">' . $body . '</div>';
}
add_action(
	'init',
	function () {
		$style_path = __DIR__ . '/../assets/css/zone-slots.css';
		wp_register_style( 'wt-zone-slots', get_theme_file_uri( 'assets/css/zone-slots.css' ), array(), (string) filemtime( $style_path ) );
		register_block_type(
			'helix-wt/zone-slot',
			array(
				'api_version'     => 3,
				'style'           => 'wt-zone-slots',
				'render_callback' => 'wt_zone_render',
				'attributes'      => array(
					'slot'   => array( 'type' => 'string' ),
					'common' => array(
						'type'    => 'array',
						'default' => array(),
					),
					'pc'     => array( 'type' => 'array' ),
					'sp'     => array( 'type' => 'array' ),
				),
			)
		);
	}
);
