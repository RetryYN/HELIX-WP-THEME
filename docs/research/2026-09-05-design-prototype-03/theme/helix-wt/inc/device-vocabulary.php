<?php
/** Core block styles for a received device vocabulary. */
if ( ! defined( 'ABSPATH' ) ) {
	exit; }
add_action(
	'init',
	function () {
		register_block_style(
			'core/tabs',
			array(
				'name'  => 'wt-device-tabs',
				'label' => __( 'PCタブ・SP開閉', 'helix-wt' ),
			)
		);
	}
);
add_action(
	'wp_enqueue_scripts',
	function () {
		wp_enqueue_style( 'wt-device-vocabulary', get_theme_file_uri( 'assets/css/device-vocabulary.css' ), array( 'helix-wt' ), '0.3.25' );
		wp_enqueue_script( 'wt-device-vocabulary', get_theme_file_uri( 'assets/js/device-vocabulary.js' ), array(), '0.3.25', array( 'strategy' => 'defer' ) );
	}
);
// This style owns enhancement; keep every panel in the initial response without core's competing interaction.
add_filter(
	'render_block_core/tabs',
	function ( $html, $block ) {
		if ( ! in_array( 'is-style-wt-device-tabs', explode( ' ', $block['attrs']['className'] ?? '' ), true ) ) {
			return $html; }
		$panels = array();
		foreach ( $block['innerBlocks'] ?? array() as $group ) {
			if ( 'core/tab-panels' !== $group['blockName'] ) {
				continue; }
			foreach ( $group['innerBlocks'] ?? array() as $panel ) {
				if ( 'core/tab-panel' === $panel['blockName'] ) {
					$panels[] = $panel; }
			}
		}
		if ( ! $panels ) {
			return $html; }
		$id  = wp_unique_id( 'wt-device-tabs-' );
		$out = '<div class="wt-device-tabs" data-wt-device-tabs><div class="wt-device-tabs__list" hidden>';
		foreach ( $panels as $i => $panel ) {
			$out .= '<button type="button" id="' . esc_attr( $id . '-tab-' . $i ) . '" aria-controls="' . esc_attr( $id . '-panel-' . $i ) . '">' . esc_html( $panel['attrs']['label'] ?? '' ) . '</button>';
		}
		$out .= '</div>';
		foreach ( $panels as $i => $panel ) {
			$label = $panel['attrs']['label'] ?? '';
			$out  .= '<section class="wt-device-tabs__item"><h3 class="wt-device-tabs__heading"><span>' . esc_html( $label ) . '</span><button type="button" hidden id="' . esc_attr( $id . '-toggle-' . $i ) . '" aria-controls="' . esc_attr( $id . '-panel-' . $i ) . '" aria-expanded="true">' . esc_html( $label ) . '<span aria-hidden="true">＋</span></button></h3><div class="wt-device-tabs__panel" id="' . esc_attr( $id . '-panel-' . $i ) . '">';
			foreach ( $panel['innerBlocks'] ?? array() as $inner ) {
				$out .= render_block( $inner ); }
			$out .= '</div></section>';
		}
		return $out . '</div>';
	},
	20,
	2
);
add_filter(
	'render_block_core/group',
	function ( $html, $block ) {
		$classes = explode( ' ', $block['attrs']['className'] ?? '' );
		$name    = in_array( 'wt-device-read', $classes, true ) ? 'read' : ( in_array( 'wt-device-compare', $classes, true ) ? 'compare' : '' );
		if ( ! $name ) {
			return $html; }
		$contract = json_decode( file_get_contents( __DIR__ . '/../config/device-vocabulary.json' ), true );
		if ( ! is_array( $contract ) || ! isset( $contract['presets'][ $name ] ) ) {
			return $html; }
		$p = new WP_HTML_Tag_Processor( $html );
		if ( $p->next_tag() ) {
			$p->set_attribute( 'data-wt-device-preset', $name );
			$p->set_attribute( 'data-wt-device-contract', wp_json_encode( $contract['presets'][ $name ] ) );
		}
		return $p->get_updated_html();
	},
	20,
	2
);
