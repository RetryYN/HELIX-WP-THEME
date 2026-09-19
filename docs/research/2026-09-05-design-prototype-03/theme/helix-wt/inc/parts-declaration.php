<?php
/**
 * 端末別パーツ宣言の保存・描画 PoC。
 *
 * @package HelixWT
 */

defined( 'ABSPATH' ) || exit;

/**
 * 明示された共通値と両端末差分を検査する。null は共通値の継承。
 *
 * @param mixed $declaration 宣言.
 * @return bool 有効なら true。
 */
function helix_wt_parts_declaration_valid( $declaration ) {
	if ( ! is_array( $declaration ) || count( $declaration ) !== 3 || ! array_key_exists( 'common', $declaration ) || ! array_key_exists( 'pc', $declaration ) || ! array_key_exists( 'sp', $declaration ) ) {
		return false;
	}
	foreach ( $declaration as $device => $slug ) {
		if ( null === $slug && 'common' !== $device ) {
			continue;
		}
		if ( ! is_string( $slug ) || ! preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug ) || ! file_exists( get_theme_file_path( 'parts/' . $slug . '.html' ) ) ) {
			return false;
		}
	}
	return true;
}

/**
 * コアのパーツに宣言属性を追加する。新規ブロックは作らない。
 *
 * @param array  $args 登録引数.
 * @param string $name ブロック名.
 * @return array 登録引数。
 */
function helix_wt_parts_declaration_attributes( $args, $name ) {
	if ( 'core/template-part' === $name ) {
		$args['attributes']['wtPartsDeclaration'] = array( 'type' => 'object' );
	}
	return $args;
}
add_filter( 'register_block_type_args', 'helix_wt_parts_declaration_attributes', 10, 2 );

/**
 * 不正な宣言は描画前に拒否する。
 *
 * @param string|null $pre_render 先行結果.
 * @param array       $block ブロック.
 * @return string|null 結果。
 */
function helix_wt_parts_declaration_guard( $pre_render, $block ) {
	$name  = isset( $block['blockName'] ) ? $block['blockName'] : '';
	$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
	if ( 'core/template-part' === $name && array_key_exists( 'wtPartsDeclaration', $attrs ) && ! helix_wt_parts_declaration_valid( $attrs['wtPartsDeclaration'] ) ) {
		return '';
	}
	return $pre_render;
}
add_filter( 'pre_render_block', 'helix_wt_parts_declaration_guard', 10, 2 );

/**
 * 選択された参照だけをコアへ渡す。
 *
 * @param array $block ブロック.
 * @return array 選択結果。
 */
function helix_wt_parts_declaration_select( $block ) {
	$name  = isset( $block['blockName'] ) ? $block['blockName'] : '';
	$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();
	if ( 'core/template-part' !== $name || ! array_key_exists( 'wtPartsDeclaration', $attrs ) ) {
		return $block;
	}
	$declaration = $attrs['wtPartsDeclaration'];
	if ( helix_wt_parts_declaration_valid( $declaration ) ) {
		$device                    = wp_is_mobile() ? 'sp' : 'pc';
		$block['attrs']['slug']    = $declaration[ $device ] ?? $declaration['common'];
		$block['attrs']['theme']   = get_stylesheet();
		$block['attrs']['tagName'] = 'div';
	}
	return $block;
}
add_filter( 'render_block_data', 'helix_wt_parts_declaration_select' );

/**
 * 専用検証ページの操作対象サイズを確保する。
 */
function helix_wt_parts_catalog_style() {
	if ( is_page_template( 'page-parts-catalog' ) ) {
		wp_enqueue_style( 'wt-parts-catalog', get_theme_file_uri( 'assets/css/parts-catalog.css' ), array(), filemtime( get_theme_file_path( 'assets/css/parts-catalog.css' ) ) );
	}
}
add_action( 'wp_enqueue_scripts', 'helix_wt_parts_catalog_style' );

/**
 * 宣言に使えるテーマパーツを編集画面へ渡す。
 */
function helix_wt_parts_editor_assets() {
	$parts = array_map(
		static function ( $file ) {
			return basename( $file, '.html' );
		},
		glob( get_theme_file_path( 'parts/*.html' ) )
	);
	wp_enqueue_script( 'helix-wt-parts-editor', get_theme_file_uri( 'assets/js/parts-editor.js' ), array( 'wp-blocks', 'wp-hooks', 'wp-compose', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-data', 'wp-i18n' ), filemtime( get_theme_file_path( 'assets/js/parts-editor.js' ) ), true );
	wp_add_inline_script( 'helix-wt-parts-editor', 'window.helixWTParts=' . wp_json_encode( $parts ) . ';', 'before' );
}
add_action( 'enqueue_block_editor_assets', 'helix_wt_parts_editor_assets' );

/**
 * 子ブロックを含め保存内容の宣言を検査する。
 *
 * @param array $blocks 保存候補のブロック.
 * @return bool 有効な内容。
 */
function helix_wt_parts_blocks_valid( $blocks ) {
	foreach ( $blocks as $block ) {
		if ( 'core/template-part' === $block['blockName'] && array_key_exists( 'wtPartsDeclaration', $block['attrs'] ) && ! helix_wt_parts_declaration_valid( $block['attrs']['wtPartsDeclaration'] ) ) {
			return false;
		}
		if ( ! helix_wt_parts_blocks_valid( $block['innerBlocks'] ) ) {
			return false;
		}
	}
	return true;
}

/**
 * REST の保存境界で不正宣言を拒否する。
 *
 * @param stdClass|WP_Error $prepared 保存候補.
 * @return stdClass|WP_Error 保存候補またはエラー。
 */
function helix_wt_parts_rest_guard( $prepared ) {
	if ( ! is_wp_error( $prepared ) && isset( $prepared->post_content ) && ! helix_wt_parts_blocks_valid( parse_blocks( $prepared->post_content ) ) ) {
		return new WP_Error( 'helix_wt_invalid_parts', __( 'パーツ参照が不正です。共通・PC・SPを明示し、存在するパーツを選択してください。', 'helix-wt' ), array( 'status' => 400 ) );
	}
	return $prepared;
}
foreach ( array( 'wp_template', 'wp_template_part', 'page', 'post' ) as $helix_wt_parts_post_type ) {
	add_filter( 'rest_pre_insert_' . $helix_wt_parts_post_type, 'helix_wt_parts_rest_guard' );
}
