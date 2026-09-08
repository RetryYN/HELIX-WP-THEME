<?php
/** Event state display fixture. No booking, capacity mutation, or external delivery. */
defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', function () {
	if ( ! function_exists( 'wt_is_event_page' ) || ! wt_is_event_page() ) { return; }
	wp_enqueue_style( 'wt-event-state', get_theme_file_uri( 'assets/css/event-state.css' ), array( 'helix-wt' ), filemtime( get_theme_file_path( 'assets/css/event-state.css' ) ) );
} );

function wt_event_fixture_time( $value ) {
	if ( ! is_string( $value ) || ! preg_match( '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(Z|[+-](?:[01]\d|2[0-3]):[0-5]\d)$/D', $value ) ) { return null; }
	$date = DateTimeImmutable::createFromFormat( '!Y-m-d\TH:i:sP', $value );
	$errors = DateTimeImmutable::getLastErrors();
	return $date && ( false === $errors || ( ! $errors['warning_count'] && ! $errors['error_count'] ) ) ? $date->getTimestamp() : null;
}

function wt_event_fixture_state() {
	if ( ! function_exists( 'wt_is_event_page' ) || ! wt_is_event_page() ) { return null; }
	$raw = get_post_meta( get_queried_object_id(), '_wtcf_event_fixture', true );
	if ( '' === $raw ) { return null; }
	$data = is_string( $raw ) ? json_decode( $raw, true ) : null;
	$invalid = array( 'state' => 'invalid', 'label' => '受付情報を確認中', 'open' => false );
	if ( ! is_array( $data ) ) { return $invalid; }
	$start = wt_event_fixture_time( $data['opens_at'] ?? null );
	$end = wt_event_fixture_time( $data['closes_at'] ?? null );
	// 観測時刻の差替えは専用labのfixtureだけ。通常環境では実時刻を使う。
	$now = 'HELIX Content Lab' === get_option( 'blogname' ) ? wt_event_fixture_time( $data['observed_at'] ?? null ) : time();
	$capacity = $data['capacity'] ?? null; $registered = $data['registered'] ?? null;
	if ( null === $start || null === $end || null === $now || $start >= $end || ! is_int( $capacity ) || ! is_int( $registered ) || $capacity < 0 || $registered < 0 ) { return $invalid; }
	if ( $now < $start ) { return array( 'state' => 'before', 'label' => '受付前', 'open' => false ); }
	if ( $now >= $end ) { return array( 'state' => 'ended', 'label' => '受付終了', 'open' => false ); }
	if ( $registered >= $capacity ) { return array( 'state' => 'full', 'label' => '満席', 'open' => false ); }
	return array( 'state' => 'open', 'label' => '受付中', 'open' => true );
}
