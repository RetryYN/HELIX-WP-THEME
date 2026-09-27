<?php
/**
 * Plugin Name: AGENT NEO Embed
 * Description: AGENT NEO の dual-mode 埋め込みブロックを登録します。
 * Version: 0.1.0
 * Requires at least: 6.3
 * Requires PHP: 8.1
 * Author: AGENT NEO
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: agent-neo-embed
 *
 * @package AgentNeoEmbed
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AGENT_NEO_EMBED_VERSION', '0.1.0' );
define( 'AGENT_NEO_EMBED_PATH', plugin_dir_path( __FILE__ ) );
define( 'AGENT_NEO_EMBED_URL', plugin_dir_url( __FILE__ ) );

/**
 * URL から origin（scheme + host + port）を抽出する。
 *
 * @param string $url URL または origin。
 * @return string
 */
function agent_neo_embed_sandbox_origin_from_url( string $url ): string {
	$parts = wp_parse_url( trim( $url ) );

	if ( ! is_array( $parts ) || empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
		return '';
	}

	$scheme = strtolower( (string) $parts['scheme'] );

	if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
		return '';
	}

	$host   = strtolower( (string) $parts['host'] );
	$origin = $scheme . '://' . $host;
	$port   = isset( $parts['port'] ) ? (int) $parts['port'] : 0;

	if ( $port > 0 && ! ( 'http' === $scheme && 80 === $port ) && ! ( 'https' === $scheme && 443 === $port ) ) {
		$origin .= ':' . $port;
	}

	return $origin;
}

/**
 * 設定値を origin 候補配列へ展開する。
 *
 * @param mixed $value 設定値。
 * @return array<int, string>
 */
function agent_neo_embed_expand_sandbox_origin_values( $value ): array {
	if ( is_array( $value ) ) {
		return $value;
	}

	if ( is_string( $value ) ) {
		return preg_split( '/[\s,]+/', $value, -1, PREG_SPLIT_NO_EMPTY ) ?: array();
	}

	return array();
}

/**
 * Interactive iframe を許可する sandbox-origin allowlist を返す。
 *
 * Sandbox-origin の生成・ホスティング・HTTP CSP 配信は Automation SEO 側
 * （CARRY-EMBED-005）の責務。AGENT NEO は設定済み origin だけを薄く描画する。
 *
 * @return array<int, string>
 */
function agent_neo_embed_allowed_sandbox_origins(): array {
	$origin_values = array();

	if ( defined( 'AGENT_NEO_EMBED_SANDBOX_ORIGIN' ) ) {
		$origin_values = array_merge(
			$origin_values,
			agent_neo_embed_expand_sandbox_origin_values( AGENT_NEO_EMBED_SANDBOX_ORIGIN )
		);
	}

	$origin_values = array_merge(
		$origin_values,
		agent_neo_embed_expand_sandbox_origin_values( get_option( 'agent_neo_embed_sandbox_origin', '' ) ),
		agent_neo_embed_expand_sandbox_origin_values( get_option( 'agent_neo_embed_sandbox_origins', array() ) )
	);

	$origins = array();

	foreach ( $origin_values as $origin_value ) {
		$origin = is_string( $origin_value ) ? agent_neo_embed_sandbox_origin_from_url( $origin_value ) : '';

		if ( '' !== $origin ) {
			$origins[] = $origin;
		}
	}

	/**
	 * Interactive iframe の許可 sandbox-origin 一覧を調整する。
	 *
	 * @param array<int, string> $origins 正規化済み origin 配列。
	 */
	$filtered_origins = apply_filters( 'agent_neo_embed_allowed_sandbox_origins', array_values( array_unique( $origins ) ) );
	$allowed_origins  = array();

	foreach ( agent_neo_embed_expand_sandbox_origin_values( $filtered_origins ) as $origin_value ) {
		$origin = is_string( $origin_value ) ? agent_neo_embed_sandbox_origin_from_url( $origin_value ) : '';

		if ( '' !== $origin ) {
			$allowed_origins[] = $origin;
		}
	}

	return array_values( array_unique( $allowed_origins ) );
}

/**
 * ブロック用アセットを登録し、block.json の handle 宣言から参照できるようにする。
 *
 * @return void
 */
function agent_neo_embed_register_block(): void {
	$view_script   = AGENT_NEO_EMBED_PATH . 'src/embed/view.js';
	$editor_script = AGENT_NEO_EMBED_PATH . 'src/embed/edit.js';
	$block_style   = AGENT_NEO_EMBED_PATH . 'src/embed/style.css';

	wp_register_script(
		'agent-neo-embed-view',
		AGENT_NEO_EMBED_URL . 'src/embed/view.js',
		array(),
		file_exists( $view_script ) ? (string) filemtime( $view_script ) : AGENT_NEO_EMBED_VERSION,
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	wp_register_script(
		'agent-neo-embed-editor',
		AGENT_NEO_EMBED_URL . 'src/embed/edit.js',
		array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n' ),
		file_exists( $editor_script ) ? (string) filemtime( $editor_script ) : AGENT_NEO_EMBED_VERSION,
		true
	);

	wp_register_style(
		'agent-neo-embed-style',
		AGENT_NEO_EMBED_URL . 'src/embed/style.css',
		array(),
		file_exists( $block_style ) ? (string) filemtime( $block_style ) : AGENT_NEO_EMBED_VERSION
	);

	register_block_type( AGENT_NEO_EMBED_PATH . 'src/embed' );
}
add_action( 'init', 'agent_neo_embed_register_block' );

/**
 * Static mode の HTML を defense-in-depth としてサニタイズする。
 *
 * Automation SEO 側の生成時 sanitize が正だが、AGENT NEO 側でも
 * ページ本体コンテキストに script / event handler / javascript: URL を出さない。
 *
 * @param string $html Static mode 用 HTML。
 * @return string
 */
function agent_neo_embed_sanitize_static_html( string $html ): string {
	$allowed_html = array(
		'a'          => array(
			'href'   => true,
			'title'  => true,
			'target' => true,
			'rel'    => true,
			'class'  => true,
			'id'     => true,
		),
		'br'         => array(),
		'div'        => array(
			'class'      => true,
			'id'         => true,
			'role'       => true,
			'aria-label' => true,
			'style'      => true,
		),
		'em'         => array(),
		'figcaption' => array(
			'class' => true,
			'id'    => true,
		),
		'figure'     => array(
			'class' => true,
			'id'    => true,
			'style' => true,
		),
		'h1'         => array(
			'class' => true,
			'id'    => true,
			'style' => true,
		),
		'h2'         => array(
			'class' => true,
			'id'    => true,
			'style' => true,
		),
		'h3'         => array(
			'class' => true,
			'id'    => true,
			'style' => true,
		),
		'h4'         => array(
			'class' => true,
			'id'    => true,
			'style' => true,
		),
		'h5'         => array(
			'class' => true,
			'id'    => true,
			'style' => true,
		),
		'h6'         => array(
			'class' => true,
			'id'    => true,
			'style' => true,
		),
		'hr'         => array(),
		'img'        => array(
			'alt'     => true,
			'class'   => true,
			'height'  => true,
			'id'      => true,
			'loading' => true,
			'src'     => true,
			'style'   => true,
			'width'   => true,
		),
		'li'         => array(
			'class' => true,
			'id'    => true,
			'style' => true,
		),
		'ol'         => array(
			'class' => true,
			'id'    => true,
			'style' => true,
		),
		'p'          => array(
			'class' => true,
			'id'    => true,
			'style' => true,
		),
		'span'       => array(
			'class'      => true,
			'id'         => true,
			'role'       => true,
			'aria-label' => true,
			'style'      => true,
		),
		'strong'     => array(),
		'ul'         => array(
			'class' => true,
			'id'    => true,
			'style' => true,
		),
		'svg'        => array(
			'aria-hidden' => true,
			'aria-label'  => true,
			'class'       => true,
			'fill'        => true,
			'focusable'   => true,
			'height'      => true,
			'id'          => true,
			'role'        => true,
			'style'       => true,
			'viewbox'     => true,
			'viewBox'     => true,
			'width'       => true,
			'xmlns'       => true,
		),
		'circle'     => array(
			'cx'           => true,
			'cy'           => true,
			'fill'         => true,
			'r'            => true,
			'stroke'       => true,
			'stroke-width' => true,
		),
		'defs'       => array(),
		'ellipse'    => array(
			'cx'           => true,
			'cy'           => true,
			'fill'         => true,
			'rx'           => true,
			'ry'           => true,
			'stroke'       => true,
			'stroke-width' => true,
		),
		'g'          => array(
			'class'     => true,
			'fill'      => true,
			'stroke'    => true,
			'transform' => true,
		),
		'line'       => array(
			'stroke'       => true,
			'stroke-width' => true,
			'x1'           => true,
			'x2'           => true,
			'y1'           => true,
			'y2'           => true,
		),
		'path'       => array(
			'd'            => true,
			'fill'         => true,
			'stroke'       => true,
			'stroke-width' => true,
		),
		'polygon'    => array(
			'fill'         => true,
			'points'       => true,
			'stroke'       => true,
			'stroke-width' => true,
		),
		'polyline'   => array(
			'fill'         => true,
			'points'       => true,
			'stroke'       => true,
			'stroke-width' => true,
		),
		'rect'       => array(
			'fill'         => true,
			'height'       => true,
			'rx'           => true,
			'ry'           => true,
			'stroke'       => true,
			'stroke-width' => true,
			'width'        => true,
			'x'            => true,
			'y'            => true,
		),
		'text'       => array(
			'class'       => true,
			'fill'        => true,
			'font-size'   => true,
			'text-anchor' => true,
			'x'           => true,
			'y'           => true,
		),
	);

	return wp_kses( $html, $allowed_html, array( 'http', 'https' ) );
}
