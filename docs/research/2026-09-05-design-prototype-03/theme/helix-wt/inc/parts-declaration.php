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
