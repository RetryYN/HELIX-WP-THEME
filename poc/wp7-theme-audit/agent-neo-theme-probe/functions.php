<?php
/**
 * AGENT NEO Theme Probe — functions.php
 *
 * 検証フィクスチャ用の最小 functions.php。
 * WP Coding Standards 準拠 / プレフィックス: agent_neo_probe_
 * コメントは日本語で記述する。
 *
 * @package AgentNeoProbe
 * @version 0.0.1
 */

// 直接アクセス禁止（WP Coding Standards 必須）
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * テーマのセットアップ処理。
 *
 * テキストドメインのロードと最小限のテーマサポートを宣言する。
 * ブロックテーマでは add_theme_support の大半が theme.json 側で管理されるが、
 * `wp-block-styles` と `editor-styles` は functions.php 宣言が必要。
 *
 * @return void
 */
function agent_neo_probe_setup(): void {
	/*
	 * テキストドメインのロード。
	 * ブロックテーマでは languages/ ディレクトリに .pot/.po/.mo を配置する。
	 */
	load_theme_textdomain( 'agent-neo-probe', get_template_directory() . '/languages' );

	/*
	 * WordPress コアのブロック CSS をブロックごとに個別読み込みする。
	 * パフォーマンス観点: true にするとページで使われたブロック分のみ CSS が出力される。
	 * WP6.1+ 推奨設定。
	 */
	add_theme_support( 'wp-block-styles' );

	/*
	 * エディタースタイルの有効化。
	 * theme.json のスタイルがエディター内でも反映されるようになる。
	 */
	add_theme_support( 'editor-styles' );

	/*
	 * HTML5 マークアップの有効化。
	 * search-form / comment-form / comment-list / gallery / caption / style / script
	 */
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
		)
	);

	/*
	 * カスタムロゴの有効化。
	 * ブロックテーマでは wp:site-logo ブロックが使われるが、
	 * Theme Check が add_theme_support('custom-logo') を要求する場合の対応。
	 */
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 100,
			'width'       => 300,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	/*
	 * 投稿サムネイル（アイキャッチ画像）の有効化。
	 */
	add_theme_support( 'post-thumbnails' );
}
add_action( 'after_setup_theme', 'agent_neo_probe_setup' );

/**
 * フロントエンド用スタイル・スクリプトの登録。
 *
 * ブロックテーマでは基本的に theme.json でスタイル管理するため、
 * ここで enqueue する CSS は最小限にとどめる。
 * カスタムフォントの読み込みなど、theme.json に書けない外部リソースのみここで扱う。
 *
 * @return void
 */
function agent_neo_probe_enqueue_assets(): void {
	/*
	 * 検証フィクスチャではフォントは読み込まない。
	 * 本番では以下のパターンでカスタムフォント（Google Fonts 等）を enqueue する。
	 *
	 * wp_enqueue_style(
	 *     'agent-neo-probe-fonts',
	 *     'https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@400;700&display=swap',
	 *     array(),
	 *     null
	 * );
	 */
}
add_action( 'wp_enqueue_scripts', 'agent_neo_probe_enqueue_assets' );

/**
 * ブロックエディター用スタイルの登録。
 *
 * add_editor_style() で登録したファイルはエディター内の iframe にも挿入される。
 * theme.json が自動適用されるためほぼ不要だが、
 * エディター専用の上書きが必要な場合に使用する。
 *
 * @return void
 */
function agent_neo_probe_editor_styles(): void {
	/*
	 * 検証フィクスチャでは editor-style.css は作成しない。
	 * 本番では必要に応じて add_editor_style( 'assets/css/editor.css' ) を呼ぶ。
	 */
}
add_action( 'after_setup_theme', 'agent_neo_probe_editor_styles' );
