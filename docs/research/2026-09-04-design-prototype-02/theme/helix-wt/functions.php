<?php
/**
 * HELIX WT prototype 02 — 新規ブロックテーマ。既存テーマからの流用なし。
 */
add_action( 'after_setup_theme', function () {
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/theme.css' );
	remove_theme_support( 'core-block-patterns' );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'helix-wt-icons', get_theme_file_uri( 'assets/css/icons.css' ), array(), '0.2.0' );
	wp_enqueue_style( 'helix-wt', get_theme_file_uri( 'assets/css/theme.css' ), array( 'helix-wt-icons' ), '0.2.0' );
} );

add_action( 'init', function () {
	register_block_pattern_category( 'helix-wt', array( 'label' => 'HELIX WT' ) );
	// 記事用 block style（値差し替えではなく区画の役割を表す最小の style）
	register_block_style( 'core/group', array( 'name' => 'wt-note', 'label' => '注記（囲み）' ) );
	register_block_style( 'core/group', array( 'name' => 'wt-point', 'label' => 'ポイント（アクセント囲み）' ) );
	register_block_style( 'core/group', array( 'name' => 'wt-warn', 'label' => '注意（警告囲み）' ) );
	register_block_style( 'core/group', array( 'name' => 'wt-card', 'label' => 'カード（罫線）' ) );
	register_block_style( 'core/group', array( 'name' => 'wt-card-shadow', 'label' => 'カード（影）' ) );
	register_block_style( 'core/list', array( 'name' => 'wt-check', 'label' => 'チェックリスト' ) );
	register_block_style( 'core/heading', array( 'name' => 'wt-bar', 'label' => '左バー見出し' ) );
	register_block_style( 'core/heading', array( 'name' => 'wt-underline', 'label' => '下線見出し' ) );
	register_block_style( 'core/button', array( 'name' => 'wt-pill', 'label' => 'ピル' ) );
	register_block_style( 'core/table', array( 'name' => 'wt-compare', 'label' => '比較表（先頭列固定）' ) );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_script( 'helix-wt-reveal', get_theme_file_uri( 'assets/js/reveal.js' ), array(), '0.2.0', array( 'strategy' => 'defer' ) );
} );
