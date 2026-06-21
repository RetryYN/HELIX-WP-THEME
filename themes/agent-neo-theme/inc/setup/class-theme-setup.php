<?php
/**
 * Theme setup module。
 *
 * @package AgentNeo
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress core へ最小 theme support を登録する。
 */
final class Agent_Neo_Theme_Setup {
	/**
	 * Hooks を登録する。
	 *
	 * @return void
	 */
	public function register(): void {
		add_action( 'after_setup_theme', array( $this, 'setup_theme' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	/**
	 * フロントエンド補助 CSS（a11y カラーオーバーライド等）を読み込む。
	 *
	 * FSE テーマでは theme.json が主スタイルを担うが、style.css は
	 * WP preset クラスへの a11y override 用として front-end にも enqueue する。
	 *
	 * @return void
	 */
	public function enqueue_styles(): void {
		wp_enqueue_style(
			'agent-neo-style',
			get_stylesheet_uri(),
			array(),
			wp_get_theme()->get( 'Version' )
		);
	}

	/**
	 * Theme support を宣言する。
	 *
	 * @return void
	 */
	public function setup_theme(): void {
		load_theme_textdomain( 'agent-neo', AGENT_NEO_DIR . 'languages' );

		add_theme_support( 'wp-block-styles' );
		add_theme_support( 'editor-styles' );
		add_theme_support( 'post-thumbnails' );
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
		add_theme_support(
			'custom-logo',
			array(
				'height'      => 100,
				'width'       => 300,
				'flex-height' => true,
				'flex-width'  => true,
			)
		);
	}
}
