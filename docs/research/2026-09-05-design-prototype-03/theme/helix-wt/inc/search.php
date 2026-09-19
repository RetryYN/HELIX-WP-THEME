<?php
/** Search presentation: distinguish an unentered query from zero results. */
defined( 'ABSPATH' ) || exit;
add_filter( 'pre_render_block', function ( $pre, $block ) {
	if ( null !== $pre || ! is_search() || preg_match( '/[^\s\x{3000}]/u', get_search_query( false ) ) ) { return $pre; }
	if ( 'core/query-title' === $block['blockName'] && 'search' === ( $block['attrs']['type'] ?? '' ) ) { return ''; }
	if ( 'core/query' !== $block['blockName'] || ! preg_match( '/(?:^|\s)wt-site-search-results(?:\s|$)/', $block['attrs']['className'] ?? '' ) ) { return $pre; }
	return '<section class="wt-search-start" aria-labelledby="wt-search-start-title"><h2 id="wt-search-start-title">検索語を入力してください</h2><p>知りたいことを短いキーワードで入力すると、関連する情報を探せます。</p></section>';
}, 10, 2 );
